<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Playlist;
use App\Models\Station;

/**
 * AutoDJ basado en Liquidsoap.
 *
 * Genera un script .liq por estacion que:
 *  - Reproduce las playlists activas (rotacion aleatoria ponderada).
 *  - Acepta un DJ en vivo por input.harbor en el puerto dj_port.
 *  - Hace fallback: si hay DJ en vivo, suena el vivo; si no, el AutoDJ.
 *  - Envia el audio a la instancia Shoutcast (sc_serv) como fuente.
 *
 * El control del proceso Liquidsoap usa el mismo driver del servidor:
 *  - mock   -> marcador en storage/pids (desarrollo, sin Liquidsoap).
 *  - windows-> liquidsoap.exe via proc_open (LIQUIDSOAP_BIN).
 *  - linux  -> systemd  liquidsoap@<id>.service.
 */
final class AutoDjService
{
    public function mediaDir(int $stationId): string
    {
        $dir = BASE_PATH . '/storage/media/station_' . $stationId;
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }

    public function liqPath(int $stationId): string
    {
        return BASE_PATH . '/storage/configs/station_' . $stationId . '.liq';
    }

    private function marker(int $stationId): string
    {
        return BASE_PATH . '/storage/pids/station_' . $stationId . '.autodj.running';
    }

    /**
     * Genera los .m3u de cada playlist activa y el script .liq de la estacion.
     * @param array<string,mixed> $station
     */
    public function generateScript(array $station): string
    {
        $sid = (int) $station['id'];
        $mediaDir = $this->mediaDir($sid);
        $host = $station['hostname'] ?? env('SHOUTCAST_HOST', '127.0.0.1');
        $port = (int) $station['port'];
        $djPort = (int) ($station['dj_port'] ?? ($port + 10000));
        $bitrate = (int) $station['max_bitrate'];
        $sourcePass = (string) $station['source_password'];
        $name = $this->esc((string) $station['name']);
        $genre = $this->esc((string) ($station['genre'] ?? ''));

        // Construir m3u por playlist activa
        $playlists = Playlist::forStation($sid);
        $sources = [];
        $generalWeights = [];
        $scheduledSources = [];

        foreach ($playlists as $pl) {
            if ((int) $pl['is_active'] !== 1 || $pl['type'] === 'jingle') {
                continue;
            }
            $items = Playlist::items((int) $pl['id']);
            if (!$items) {
                continue;
            }
            $m3u = $mediaDir . '/playlist_' . (int) $pl['id'] . '.m3u';
            $lines = [];
            foreach ($items as $it) {
                $lines[] = $mediaDir . '/' . $it['filename'];
            }
            file_put_contents($m3u, implode("\n", $lines) . "\n");

            $mode = ((int) $pl['shuffle'] === 1) ? 'randomize' : 'normal';
            $var = 'pl_' . (int) $pl['id'];
            $sources[] = "{$var} = playlist(mode=\"{$mode}\", reload_mode=\"watch\", \"{$m3u}\")";

            if ($pl['type'] === 'scheduled' && !empty($pl['start_time']) && !empty($pl['end_time'])) {
                $startH = str_replace(':', 'h', substr((string) $pl['start_time'], 0, 5));
                $endH   = str_replace(':', 'h', substr((string) $pl['end_time'], 0, 5));
                $scheduledSources[] = "({ {$startH}-{$endH} }, {$var})";
            } else {
                $generalWeights[(int) $pl['id']] = ['var' => $var, 'weight' => max(1, (int) $pl['weight'])];
            }
        }

        $liq  = "#!/usr/bin/liquidsoap\n";
        $liq .= "# Script generado por SonicStreaming Panel. No editar a mano.\n";
        $liq .= "# Estacion #{$sid} - {$station['name']}\n";
        $liq .= "# El log lo captura systemd (journalctl -u liquidsoap@{$sid}).\n\n";

        if ($sources) {
            $liq .= implode("\n", $sources) . "\n\n";

            if ($generalWeights) {
                if (count($generalWeights) === 1) {
                    $only = array_values($generalWeights)[0];
                    $liq .= "autodj_gen = {$only['var']}\n";
                } else {
                    $vars = [];
                    $ws = [];
                    foreach ($generalWeights as $w) {
                        $vars[] = $w['var'];
                        $ws[]   = (string) $w['weight'];
                    }
                    $liq .= "autodj_gen = random(weights=[" . implode(', ', $ws) . "], [" . implode(', ', $vars) . "])\n";
                }
            } else {
                $liq .= "autodj_gen = playlist(mode=\"randomize\", reload_mode=\"watch\", \"{$mediaDir}\")\n";
            }

            if ($scheduledSources) {
                $scheduledSources[] = "({ true }, autodj_gen)";
                $schedLines = implode(", ", $scheduledSources);
                $liq .= "autodj = switch([{$schedLines}])\n";
            } else {
                $liq .= "autodj = autodj_gen\n";
            }
        } else {
            // Sin playlists: reproducir todo el directorio de medios.
            $liq .= "autodj = playlist(mode=\"randomize\", reload_mode=\"watch\", \"{$mediaDir}\")\n";
        }

        $liq .= "autodj = mksafe(autodj)\n\n";

        // Transición suave entre AutoDJ y DJ en vivo
        $liq .= "# Funciones de transicion suave entre AutoDJ y DJ en vivo\n";
        $liq .= "def to_live(a, b) =\n";
        $liq .= "  add(weights=[1.0, 1.0], [fade.initial(duration=3.0, b), fade.final(duration=3.0, a)])\n";
        $liq .= "end\n\n";
        $liq .= "def to_autodj(a, b) =\n";
        $liq .= "  add(weights=[1.0, 1.0], [fade.initial(duration=3.0, b), fade.final(duration=3.0, a)])\n";
        $liq .= "end\n\n";

        // Entrada de DJ en vivo (harbor)
        $liq .= "live = input.harbor(\"/stream\", port={$djPort}, password=\"{$sourcePass}\")\n";
        $liq .= "radio = fallback(track_sensitive=false, transitions=[to_live, to_autodj], [live, autodj])\n\n";

        // Salida hacia Shoutcast (sc_serv) como fuente
        $liq .= "output.shoutcast(\n";
        $liq .= "  %mp3(bitrate={$bitrate}),\n";
        $liq .= "  host=\"{$host}\", port={$port},\n";
        $liq .= "  password=\"{$sourcePass}\",\n";
        $liq .= "  name=\"{$name}\", genre=\"{$genre}\",\n";
        $liq .= "  radio\n";
        $liq .= ")\n";

        $path = $this->liqPath($sid);
        file_put_contents($path, $liq);
        return $path;
    }

    /** @param array<string,mixed> $station */
    public function reloadIfRunning(array $station): void
    {
        $sid = (int) $station['id'];
        $this->generateScript($station);
        if (($station['autodj_status'] ?? '') === 'running') {
            $driver = (string) ($station['driver'] ?? env('SHOUTCAST_DRIVER', 'mock'));
            if ($driver === 'linux') {
                $this->systemctl('restart', $sid);
            }
        }
    }

    /** @param array<string,mixed> $station */
    public function start(array $station): array
    {
        $sid = (int) $station['id'];
        $this->generateScript($station);
        $driver = (string) ($station['driver'] ?? env('SHOUTCAST_DRIVER', 'mock'));

        $result = match ($driver) {
            'linux'   => $this->systemctl('start', $sid),
            'windows' => $this->winStart($station),
            default   => $this->mockSet($sid, true),
        };
        if ($result['ok']) {
            Station::update($sid, ['autodj_status' => 'running']);
        }
        return $result;
    }

    /** @param array<string,mixed> $station */
    public function stop(array $station): array
    {
        $sid = (int) $station['id'];
        $driver = (string) ($station['driver'] ?? env('SHOUTCAST_DRIVER', 'mock'));

        $result = match ($driver) {
            'linux'   => $this->systemctl('stop', $sid),
            'windows' => $this->winStop($sid),
            default   => $this->mockSet($sid, false),
        };
        if ($result['ok']) {
            Station::update($sid, ['autodj_status' => 'stopped']);
        }
        return $result;
    }

    // ---- mock ----
    private function mockSet(int $sid, bool $on): array
    {
        $m = $this->marker($sid);
        if ($on) {
            @touch($m);
            return ['ok' => true, 'message' => 'AutoDJ iniciado (modo simulado).'];
        }
        if (is_file($m)) {
            @unlink($m);
        }
        return ['ok' => true, 'message' => 'AutoDJ detenido (modo simulado).'];
    }

    // ---- linux (systemd) ----
    private function systemctl(string $action, int $sid): array
    {
        $unit = escapeshellarg('liquidsoap@' . $sid);
        $out = [];
        $code = 0;
        @exec('sudo -n /usr/bin/systemctl ' . escapeshellarg($action) . ' ' . $unit . ' 2>&1', $out, $code);
        return ['ok' => $code === 0, 'message' => $code === 0 ? "AutoDJ {$action} ok." : ('systemctl: ' . implode(' ', $out))];
    }

    // ---- windows (dev) ----
    private function winStart(array $station): array
    {
        $bin = (string) env('LIQUIDSOAP_BIN', '');
        if ($bin === '' || !is_file($bin)) {
            return ['ok' => false, 'message' => 'No se encuentra liquidsoap (revisa LIQUIDSOAP_BIN en .env).'];
        }
        $sid = (int) $station['id'];
        $cmd = '"' . $bin . '" "' . $this->liqPath($sid) . '"';
        $proc = @proc_open($cmd, [
            0 => ['pipe', 'r'],
            1 => ['file', BASE_PATH . '/storage/logs/station_' . $sid . '_liq.log', 'a'],
            2 => ['file', BASE_PATH . '/storage/logs/station_' . $sid . '_liq.log', 'a'],
        ], $pipes, dirname($bin), null, ['bypass_shell' => true]);
        if (!is_resource($proc)) {
            return ['ok' => false, 'message' => 'No se pudo iniciar liquidsoap.'];
        }
        $st = proc_get_status($proc);
        if (!empty($st['pid'])) {
            file_put_contents(BASE_PATH . '/storage/pids/station_' . $sid . '.autodj.pid', (string) $st['pid']);
        }
        return ['ok' => true, 'message' => 'AutoDJ (liquidsoap) iniciado.'];
    }

    private function winStop(int $sid): array
    {
        $pidFile = BASE_PATH . '/storage/pids/station_' . $sid . '.autodj.pid';
        if (is_file($pidFile)) {
            $pid = (int) trim((string) file_get_contents($pidFile));
            if ($pid > 0) {
                @exec('taskkill /F /T /PID ' . escapeshellarg((string) $pid) . ' 2>&1');
            }
            @unlink($pidFile);
        }
        return ['ok' => true, 'message' => 'AutoDJ detenido.'];
    }

    /** Escapa comillas para insertar en el script .liq. */
    private function esc(string $s): string
    {
        return str_replace('"', '\"', $s);
    }
}
