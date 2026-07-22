<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\ActivityLog;
use App\Models\MediaTrack;
use App\Models\Plan;
use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\Station;
use App\Services\AutoDjService;

/**
 * Logica compartida de gestion del AutoDJ (biblioteca, playlists, control).
 * Cada rol define $base y como autoriza el acceso a la estacion.
 */
abstract class BaseAutoDjController extends Controller
{
    protected string $base = 'admin';
    protected AutoDjService $autodj;

    public function __construct()
    {
        $this->autodj = new AutoDjService();
    }

    /** Devuelve la estacion (con servidor) si el usuario puede gestionarla, o null. */
    abstract protected function authorizeStation(int $id): ?array;

    private function guard(int $id): array
    {
        $station = $this->authorizeStation($id);
        if (!$station) {
            http_response_code(404);
            echo \App\Core\View::render('errors/404', [], 'layouts/blank');
            exit;
        }
        if ((int) ($station['autodj_enabled'] ?? 0) !== 1) {
            set_flash('warning', 'El AutoDJ no esta habilitado para esta estacion.');
            redirect($this->base . '/stations/' . $id);
        }
        return $station;
    }

    private function back(int $id): never
    {
        redirect($this->base . '/stations/' . $id . '/autodj');
    }

    public function index(Request $request, string $id): void
    {
        $sid = (int) $id;
        $station = $this->guard($sid);

        $playlists = Playlist::forStation($sid);
        if (!$playlists) {
            Playlist::create([
                'station_id' => $sid,
                'name'       => 'General',
                'type'       => 'general',
                'shuffle'    => 1,
                'is_active'  => 1,
                'weight'     => 1,
            ]);
            $playlists = Playlist::forStation($sid);
        }

        $quotaMb = 0;
        if (!empty($station['plan_id'])) {
            $plan = Plan::find((int) $station['plan_id']);
            $quotaMb = (int) ($plan['disk_quota_mb'] ?? 0);
        }

        $this->view('autodj/index', [
            'title'     => 'AutoDJ · ' . $station['name'],
            'station'   => $station,
            'base'      => $this->base,
            'tracks'    => MediaTrack::forStation($sid),
            'playlists' => $playlists,
            'usageBytes'=> MediaTrack::diskUsage($sid),
            'quotaMb'   => $quotaMb,
        ]);
    }

    public function upload(Request $request, string $id): void
    {
        $sid = (int) $id;
        $station = $this->guard($sid);

        $rawFiles = $_FILES['tracks'] ?? $_FILES['track'] ?? null;
        if (empty($rawFiles)) {
            set_flash('danger', 'No se recibio ningun archivo.');
            $this->back($sid);
        }

        $files = [];
        if (is_array($rawFiles['name'])) {
            foreach ($rawFiles['name'] as $idx => $name) {
                if (!empty($name)) {
                    $files[] = [
                        'name'     => $name,
                        'type'     => $rawFiles['type'][$idx] ?? '',
                        'tmp_name' => $rawFiles['tmp_name'][$idx] ?? '',
                        'error'    => $rawFiles['error'][$idx] ?? UPLOAD_ERR_NO_FILE,
                        'size'     => $rawFiles['size'][$idx] ?? 0,
                    ];
                }
            }
        } else {
            $files[] = $rawFiles;
        }

        if (empty($files)) {
            set_flash('danger', 'No se selecciono ningun archivo valido.');
            $this->back($sid);
        }

        $allowed = array_map('trim', explode(',', (string) env('AUTODJ_ALLOWED_EXT', 'mp3,aac,m4a,ogg,flac,wav')));
        $success = 0;
        $errors  = [];

        $quotaBytes = 0;
        if (!empty($station['plan_id'])) {
            $plan = Plan::find((int) $station['plan_id']);
            $quotaBytes = (int) ($plan['disk_quota_mb'] ?? 0) * 1024 * 1024;
        }

        $targetPlId = $request->int('playlist_id', 0);

        foreach ($files as $file) {
            $errCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($errCode !== UPLOAD_ERR_OK) {
                if ($errCode === UPLOAD_ERR_INI_SIZE || $errCode === UPLOAD_ERR_FORM_SIZE) {
                    $errors[] = "{$file['name']}: supera el limite de tamano de PHP.";
                } elseif ($errCode !== UPLOAD_ERR_NO_FILE) {
                    $errors[] = "{$file['name']}: error de subida (codigo {$errCode}).";
                }
                continue;
            }

            $original = (string) $file['name'];
            $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) {
                $errors[] = "{$original}: formato no permitido ({$ext}).";
                continue;
            }
            if (!is_uploaded_file($file['tmp_name'])) {
                $errors[] = "{$original}: subida invalida.";
                continue;
            }

            $size = (int) $file['size'];
            if ($quotaBytes > 0 && (MediaTrack::diskUsage($sid) + $size) > $quotaBytes) {
                $errors[] = "{$original}: superaria la cuota de disco del plan.";
                break;
            }

            $baseName = preg_replace('/[^A-Za-z0-9._-]/', '_', pathinfo($original, PATHINFO_FILENAME));
            $baseName = trim((string) $baseName, '._-') ?: 'track';
            $filename = $baseName . '_' . substr(bin2hex(random_bytes(4)), 0, 8) . '.' . $ext;

            $dest = $this->autodj->mediaDir($sid) . '/' . $filename;
            if (!move_uploaded_file($file['tmp_name'], $dest)) {
                $errors[] = "{$original}: no se pudo guardar en el servidor.";
                continue;
            }

            $title = pathinfo($original, PATHINFO_FILENAME);
            $artist = null;
            if (str_contains($title, ' - ')) {
                [$artist, $title] = array_map('trim', explode(' - ', $title, 2));
            }

            $trackId = MediaTrack::create([
                'station_id'    => $sid,
                'filename'      => $filename,
                'original_name' => mb_substr($original, 0, 255),
                'title'         => mb_substr($title, 0, 255),
                'artist'        => $artist ? mb_substr($artist, 0, 255) : null,
                'duration'      => 0,
                'filesize'      => $size,
            ]);

            if ($targetPlId > 0) {
                PlaylistItem::create([
                    'playlist_id' => $targetPlId,
                    'track_id'    => (int) $trackId,
                    'position'    => PlaylistItem::nextPosition($targetPlId),
                ]);
            }

            ActivityLog::record('autodj_upload', 'Station #' . $sid . ' ' . $filename);
            $success++;
        }

        $this->autodj->reloadIfRunning($station);

        if ($success > 0) {
            $msg = $success === 1 ? 'Pista subida correctamente.' : "Se subieron {$success} pistas correctamente.";
            if ($errors) {
                $msg .= ' Advertencias: ' . implode(' | ', $errors);
                set_flash('warning', $msg);
            } else {
                set_flash('success', $msg);
            }
        } else {
            set_flash('danger', 'No se pudo subir ningun archivo: ' . implode(' | ', $errors));
        }

        $this->back($sid);
    }

    public function deleteTrack(Request $request, string $id, string $tid): void
    {
        $sid = (int) $id;
        $station = $this->guard($sid);
        $track = MediaTrack::find((int) $tid);
        if ($track && (int) $track['station_id'] === $sid) {
            @unlink($this->autodj->mediaDir($sid) . '/' . $track['filename']);
            MediaTrack::delete((int) $tid);
            ActivityLog::record('autodj_track_delete', 'Track #' . $tid);
            $this->autodj->reloadIfRunning($station);
            set_flash('success', 'Pista eliminada.');
        }
        $this->back($sid);
    }

    public function skipTrack(Request $request, string $id): void
    {
        $sid = (int) $id;
        $station = $this->guard($sid);
        $this->autodj->reloadIfRunning($station);
        ActivityLog::record('autodj_skip', 'Station #' . $sid);
        set_flash('success', 'Canción saltada. Transmitiendo siguiente tema.');
        $this->back($sid);
    }

    public function createPlaylist(Request $request, string $id): void
    {
        $sid = (int) $id;
        $station = $this->guard($sid);
        $name = $request->str('name');
        if ($name === '') {
            set_flash('danger', 'El nombre de la playlist es obligatorio.');
            $this->back($sid);
        }
        $type = $request->str('type', 'general') === 'scheduled' ? 'scheduled' : 'general';
        $startTime = $request->str('start_time');
        $endTime   = $request->str('end_time');

        Playlist::create([
            'station_id' => $sid,
            'name'       => $name,
            'type'       => $type,
            'shuffle'    => $request->input('shuffle') ? 1 : 0,
            'is_active'  => 1,
            'weight'     => max(1, $request->int('weight', 1)),
            'start_time' => $type === 'scheduled' ? ($startTime ?: '00:00') : null,
            'end_time'   => $type === 'scheduled' ? ($endTime ?: '23:59') : null,
        ]);
        $this->autodj->reloadIfRunning($station);
        set_flash('success', 'Playlist creada.');
        $this->back($sid);
    }

    public function updatePlaylist(Request $request, string $id, string $pid): void
    {
        $sid = (int) $id;
        $station = $this->guard($sid);
        $pl = Playlist::find((int) $pid);
        if ($pl && (int) $pl['station_id'] === $sid) {
            $name = $request->str('name', $pl['name']);
            $type = $request->str('type', $pl['type']) === 'scheduled' ? 'scheduled' : 'general';
            $startTime = $request->str('start_time', $pl['start_time'] ?? '');
            $endTime   = $request->str('end_time', $pl['end_time'] ?? '');

            Playlist::update((int) $pid, [
                'name'       => $name,
                'type'       => $type,
                'shuffle'    => $request->input('shuffle') ? 1 : 0,
                'weight'     => max(1, $request->int('weight', (int) $pl['weight'])),
                'start_time' => $type === 'scheduled' ? ($startTime ?: '00:00') : null,
                'end_time'   => $type === 'scheduled' ? ($endTime ?: '23:59') : null,
            ]);
            $this->autodj->reloadIfRunning($station);
            set_flash('success', 'Playlist actualizada.');
        }
        $this->back($sid);
    }

    public function deletePlaylist(Request $request, string $id, string $pid): void
    {
        $sid = (int) $id;
        $station = $this->guard($sid);
        $pl = Playlist::find((int) $pid);
        if ($pl && (int) $pl['station_id'] === $sid) {
            Playlist::delete((int) $pid);
            $this->autodj->reloadIfRunning($station);
            set_flash('success', 'Playlist eliminada.');
        }
        $this->back($sid);
    }

    public function addTrack(Request $request, string $id, string $pid): void
    {
        $sid = (int) $id;
        $station = $this->guard($sid);
        $pl = Playlist::find((int) $pid);
        $trackId = $request->int('track_id', 0);
        $track = MediaTrack::find($trackId);
        if ($pl && (int) $pl['station_id'] === $sid && $track && (int) $track['station_id'] === $sid) {
            PlaylistItem::create([
                'playlist_id' => (int) $pid,
                'track_id'    => $trackId,
                'position'    => PlaylistItem::nextPosition((int) $pid),
            ]);
            $this->autodj->reloadIfRunning($station);
            set_flash('success', 'Pista agregada a la playlist.');
        }
        $this->back($sid);
    }

    public function removeItem(Request $request, string $id, string $pid, string $itemId): void
    {
        $sid = (int) $id;
        $station = $this->guard($sid);
        $item = PlaylistItem::find((int) $itemId);
        $pl = Playlist::find((int) $pid);
        if ($item && $pl && (int) $pl['station_id'] === $sid && (int) $item['playlist_id'] === (int) $pid) {
            PlaylistItem::delete((int) $itemId);
            $this->autodj->reloadIfRunning($station);
            set_flash('success', 'Pista quitada de la playlist.');
        }
        $this->back($sid);
    }

    public function togglePlaylist(Request $request, string $id, string $pid): void
    {
        $sid = (int) $id;
        $station = $this->guard($sid);
        $pl = Playlist::find((int) $pid);
        if ($pl && (int) $pl['station_id'] === $sid) {
            $newActive = (int) $pl['is_active'] === 1 ? 0 : 1;
            Playlist::update((int) $pid, ['is_active' => $newActive]);
            $this->autodj->reloadIfRunning($station);
            set_flash('success', $newActive ? 'Playlist activada.' : 'Playlist desactivada.');
        }
        $this->back($sid);
    }

    public function playPlaylist(Request $request, string $id, string $pid): void
    {
        $sid = (int) $id;
        $station = $this->guard($sid);
        $pl = Playlist::find((int) $pid);
        if ($pl && (int) $pl['station_id'] === $sid) {
            Playlist::update((int) $pid, ['is_active' => 1]);
            $res = $this->autodj->start($station);
            set_flash($res['ok'] ? 'success' : 'danger', "Playlist \"{$pl['name']}\" enviada al aire. " . $res['message']);
        }
        $this->back($sid);
    }

    public function clearPlaylist(Request $request, string $id, string $pid): void
    {
        $sid = (int) $id;
        $station = $this->guard($sid);
        $pl = Playlist::find((int) $pid);
        if ($pl && (int) $pl['station_id'] === $sid) {
            $pdo = \App\Core\Model::db();
            $stmt = $pdo->prepare('DELETE FROM playlist_items WHERE playlist_id = ?');
            $stmt->execute([(int) $pid]);
            $this->autodj->reloadIfRunning($station);
            set_flash('success', 'Playlist vaciada.');
        }
        $this->back($sid);
    }

    public function bulkAddTracks(Request $request, string $id): void
    {
        $sid = (int) $id;
        $station = $this->guard($sid);
        $pid = $request->int('playlist_id', 0);
        $pl = Playlist::find($pid);
        if (!$pl || (int) $pl['station_id'] !== $sid) {
            set_flash('danger', 'Selecciona una playlist valida.');
            $this->back($sid);
        }

        $addAll = $request->input('add_all') ? true : false;
        $trackIds = array_map('intval', (array) $request->input('track_ids', []));

        if ($addAll) {
            $allTracks = MediaTrack::forStation($sid);
            $trackIds = array_column($allTracks, 'id');
        }

        if (empty($trackIds)) {
            set_flash('warning', 'No se selecciono ninguna cancion.');
            $this->back($sid);
        }

        $added = 0;
        foreach ($trackIds as $tid) {
            $t = MediaTrack::find($tid);
            if ($t && (int) $t['station_id'] === $sid) {
                PlaylistItem::create([
                    'playlist_id' => $pid,
                    'track_id'    => $tid,
                    'position'    => PlaylistItem::nextPosition($pid),
                ]);
                $added++;
            }
        }

        $this->autodj->reloadIfRunning($station);
        set_flash('success', "Se agregaron {$added} canciones a la playlist \"{$pl['name']}\".");
        $this->back($sid);
    }

    public function bulkDeleteTracks(Request $request, string $id): void
    {
        $sid = (int) $id;
        $station = $this->guard($sid);
        $trackIds = array_map('intval', (array) $request->input('track_ids', []));

        if (empty($trackIds)) {
            set_flash('warning', 'No se selecciono ninguna cancion para eliminar.');
            $this->back($sid);
        }

        $deleted = 0;
        foreach ($trackIds as $tid) {
            $track = MediaTrack::find($tid);
            if ($track && (int) $track['station_id'] === $sid) {
                @unlink($this->autodj->mediaDir($sid) . '/' . $track['filename']);
                MediaTrack::delete($tid);
                $deleted++;
            }
        }

        $this->autodj->reloadIfRunning($station);
        set_flash('success', "Se eliminaron {$deleted} canciones.");
        $this->back($sid);
    }
}
