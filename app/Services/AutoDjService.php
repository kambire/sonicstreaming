<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
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
        // Bitrate de emision del AutoDJ: usa el configurado si existe, si no el max de la estacion.
        // Nunca supera el limite (max_bitrate) del plan/estacion.
        $maxBitrate = (int) $station['max_bitrate'];
        $bitrate = (int) ($station['bitrate'] ?? 0);
        if ($bitrate <= 0) {
            $bitrate = $maxBitrate;
        }
        $bitrate = min($bitrate, $maxBitrate);
        $sourcePass = (string) $station['source_password'];
        $name = $this->esc((string) $station['name']);
        $genre = $this->esc((string) ($station['genre'] ?? ''));

        // Construir m3u por playlist activa
        $playlists = Playlist::forStation($sid);
        $sources = [];
        $generalWeights = [];
        $scheduledSources = [];

        $jingleSources = [];
        $commercialSources = [];
        $topOfHourSources = [];

        foreach ($playlists as $pl) {
            if ((int) $pl['is_active'] !== 1) {
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

            $plType = (string) $pl['type'];
            if ($plType === 'jingle') {
                $everyX = max(1, (int) ($pl['play_every_x'] ?? 3));
                $jingleSources[] = ['var' => $var, 'every' => $everyX];
            } elseif ($plType === 'commercial') {
                $everyX = max(1, (int) ($pl['play_every_x'] ?? 5));
                $commercialSources[] = ['var' => $var, 'every' => $everyX];
            } elseif ($plType === 'top_of_hour') {
                $min = (int) ($pl['cron_minute'] ?? 0);
                $interrupt = ((int) ($pl['interrupt_immediately'] ?? 0) === 1);
                $topOfHourSources[] = ['var' => $var, 'min' => $min, 'interrupt' => $interrupt];
            } elseif ($plType === 'scheduled' && !empty($pl['start_time']) && !empty($pl['end_time'])) {
                $startH = str_replace(':', 'h', substr((string) $pl['start_time'], 0, 5));
                $endH   = str_replace(':', 'h', substr((string) $pl['end_time'], 0, 5));
                $scheduledSources[] = "({ {$startH}-{$endH} }, {$var})";
            } else {
                $generalWeights[(int) $pl['id']] = ['var' => $var, 'weight' => max(1, (int) $pl['weight'])];
            }
        }

        $telnetPort = $port + 20000;

        $liq  = "#!/usr/bin/liquidsoap\n";
        $liq .= "# Script generado por SonicStreaming Panel. No editar a mano.\n";
        $liq .= "# Estacion #{$sid} - {$station['name']}\n";
        $liq .= "# El log lo captura systemd (journalctl -u liquidsoap@{$sid}).\n\n";

        $liq .= "settings.server.telnet.set(true)\n";
        $liq .= "settings.server.telnet.port.set({$telnetPort})\n";
        $liq .= "settings.server.telnet.bind_addr.set(\"127.0.0.1\")\n\n";

        // Latencia ultra-baja (~3 a 5 segundos de buffer maximo)
        $liq .= "settings.root.max_latency.set(3.0)\n\n";

        if ($sources) {
            $liq .= implode("\n", $sources) . "\n\n";

            if ($generalWeights) {
                if (count($generalWeights) === 1) {
                    $only = array_values($generalWeights)[0];
                    $liq .= "autodj_music = {$only['var']}\n";
                } else {
                    $vars = [];
                    $ws = [];
                    foreach ($generalWeights as $w) {
                        $vars[] = $w['var'];
                        $ws[]   = (string) $w['weight'];
                    }
                    $liq .= "autodj_music = random(weights=[" . implode(', ', $ws) . "], [" . implode(', ', $vars) . "])\n";
                }
            } else {
                $liq .= "autodj_music = playlist(mode=\"randomize\", reload_mode=\"watch\", \"{$mediaDir}\")\n";
            }

            if ($scheduledSources) {
                $scheduledSources[] = "({ true }, autodj_music)";
                $schedLines = implode(", ", $scheduledSources);
                $liq .= "autodj_gen = switch([{$schedLines}])\n";
            } else {
                $liq .= "autodj_gen = autodj_music\n";
            }
        } else {
            // Sin playlists: reproducir todo el directorio de medios.
            $liq .= "autodj_gen = playlist(mode=\"randomize\", reload_mode=\"watch\", \"{$mediaDir}\")\n";
        }

        $streamPipeline = 'autodj_gen';

        // Intercalar viñetas / separadores por N canciones
        if ($jingleSources) {
            foreach ($jingleSources as $j) {
                $varJ = $j['var'];
                $everyX = $j['every'];
                $streamPipeline = "rotate(weights=[1, {$everyX}], [{$varJ}, {$streamPipeline}])";
            }
        }

        // Intercalar publicidad rotativa por N canciones
        if ($commercialSources) {
            foreach ($commercialSources as $c) {
                $varC = $c['var'];
                $everyX = $c['every'];
                $streamPipeline = "rotate(weights=[1, {$everyX}], [{$varC}, {$streamPipeline}])";
            }
        }

        // Disparar pautas a Hora Exacta / En Punto
        if ($topOfHourSources) {
            $switchCases = [];
            foreach ($topOfHourSources as $t) {
                $min = $t['min'];
                $varT = $t['var'];
                $switchCases[] = "({ {$min}m0s }, {$varT})";
            }
            $switchCases[] = "({ true }, {$streamPipeline})";
            $casesStr = implode(', ', $switchCases);
            $hasInterrupt = false;
            foreach ($topOfHourSources as $t) {
                if ($t['interrupt']) {
                    $hasInterrupt = true;
                    break;
                }
            }
            $trackSens = $hasInterrupt ? 'false' : 'true';
            $streamPipeline = "switch(track_sensitive={$trackSens}, [{$casesStr}])";
        }

        // Enganche rapido y suave de 1.5s entre canciones al saltar o cambiar de tema
        $liq .= "autodj = crossfade(duration=1.5, fade_in=1.0, fade_out=1.0, {$streamPipeline})\n\n";

        // Cola dinamica para microfono del navegador "Hablar en Vivo" con atenuacion de musica (auto-ducking)
        $liq .= "# Cola de microfono en vivo desvaneciendo volumen de musica\n";
        $liq .= "mic_queue = request.queue(id=\"mic_stream\")\n";
        $liq .= "autodj_mic = smooth_add(normal=autodj, special=mic_queue)\n\n";

        // Transición suave entre AutoDJ y DJ en vivo
        $liq .= "# Funciones de transicion suave entre AutoDJ y DJ en vivo\n";
        $liq .= "def to_live(a, b) =\n";
        $liq .= "  add(weights=[1.0, 1.0], [fade.initial(duration=3.0, b), fade.final(duration=3.0, a)])\n";
        $liq .= "end\n\n";
        $liq .= "def to_autodj(a, b) =\n";
        $liq .= "  add(weights=[1.0, 1.0], [fade.initial(duration=3.0, b), fade.final(duration=3.0, a)])\n";
        $liq .= "end\n\n";

        $rawRelayUrl = trim((string) ($station['relay_url'] ?? ''));
        $relayUrl    = $this->resolveRelayUrl($rawRelayUrl);
        $relayMode   = (string) ($station['relay_mode'] ?? 'fulltime');
        $startHour   = trim((string) ($station['relay_start_hour'] ?? ''));
        $endHour     = trim((string) ($station['relay_end_hour'] ?? ''));

        $sourcesList = ['live'];
        $autodjRunning = ((int) ($station['autodj_enabled'] ?? 0) === 1 && (string) ($station['autodj_status'] ?? 'stopped') === 'running');

        if ($autodjRunning) {
            $sourcesList[] = 'mksafe(autodj_mic)';
        }

        if ($relayUrl !== '' && $relayMode !== 'disabled') {
            $liq .= "# Fuente de retransmision Relay externa con decodificador universal FFmpeg\n";
            $liq .= "relay_stream = mksafe(input.ffmpeg(\"{$relayUrl}\"))\n\n";

            if ($relayMode === 'exclusive') {
                $sourcesList = ['live', 'relay_stream'];
            } elseif ($relayMode === 'scheduled' && $startHour !== '' && $endHour !== '') {
                $sParts = explode(':', $startHour);
                $eParts = explode(':', $endHour);
                $sH = (int) ($sParts[0] ?? 0);
                $sM = (int) ($sParts[1] ?? 0);
                $eH = (int) ($eParts[0] ?? 0);
                $eM = (int) ($eParts[1] ?? 0);

                $liq .= "# Retransmision programada por horario ({$sH}h{$sM}m0s-{$eH}h{$eM}m0s)\n";
                $liq .= "relay_scheduled = switch([({ {$sH}h{$sM}m0s-{$eH}h{$eM}m0s }, relay_stream)])\n\n";
                $sourcesList[] = 'relay_scheduled';
            } else {
                // fulltime (respaldo)
                $sourcesList[] = 'relay_stream';
            }
        }

        $fallbackSources = '[' . implode(', ', $sourcesList) . ']';

        // Entrada de DJ en vivo (harbor)
        $liq .= "live = input.harbor(\"/stream\", port={$djPort}, password=\"{$sourcePass}\")\n";
        $liq .= "radio = fallback(track_sensitive=false, transitions=[to_live, to_autodj], {$fallbackSources})\n\n";

        // Salida hacia Shoutcast (sc_serv) como fuente
        $liq .= "output.shoutcast(\n";
        $liq .= "  %mp3(bitrate={$bitrate}),\n";
        $liq .= "  host=\"{$host}\", port={$port},\n";
        $liq .= "  password=\"{$sourcePass}\",\n";
        $liq .= "  name=\"{$name}\", genre=\"{$genre}\",\n";
        $liq .= "  mksafe(radio)\n";
        $liq .= ")\n";

        $path = $this->liqPath($sid);
        file_put_contents($path, $liq);
        return $path;
    }

    /** Salto de canción suave con enganche crossfade por comando Telnet sin reiniciar el proceso. */
    public function skipTrack(array $station): bool
    {
        $sid = (int) $station['id'];
        $port = (int) $station['port'];
        $telnetPort = $port + 20000;
        $name = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) ($station['name'] ?? 'radio'));

        $fp = @fsockopen('127.0.0.1', $telnetPort, $errno, $errstr, 2);
        if ($fp) {
            $playlists = Playlist::forStation($sid);
            foreach ($playlists as $pl) {
                if ((int) $pl['is_active'] === 1) {
                    $pid = (int) $pl['id'];
                    fwrite($fp, "pl_{$pid}.skip\r\n");
                    fwrite($fp, "playlist_{$pid}.m3u.skip\r\n");
                }
            }
            fwrite($fp, "{$name}.skip\r\n");
            fwrite($fp, "quit\r\n");
            fclose($fp);
            return true;
        }

        // Si no responde telnet, recargar
        $this->reloadIfRunning($station);
        return false;
    }

    /** Transmite un archivo de audio grabado por el micrófono del navegador desvaneciendo la música al aire. */
    public function pushLiveMic(array $station, string $audioFilePath): bool
    {
        $sid = (int) $station['id'];
        $port = (int) $station['port'];
        $telnetPort = $port + 20000;

        $fp = @fsockopen('127.0.0.1', $telnetPort, $errno, $errstr, 2);
        if ($fp) {
            fwrite($fp, "mic_stream.push {$audioFilePath}\r\n");
            fwrite($fp, "quit\r\n");
            fclose($fp);
            return true;
        }
        return false;
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
        Station::update($sid, ['autodj_status' => 'running', 'autodj_enabled' => 1]);
        $updated = Station::findWithServer($sid) ?: $station;

        $this->generateScript($updated);
        $driver = (string) ($updated['driver'] ?? env('SHOUTCAST_DRIVER', 'mock'));

        $result = match ($driver) {
            'linux'   => $this->systemctl('restart', $sid),
            'windows' => $this->winStart($updated),
            default   => $this->mockSet($sid, true),
        };
        if ($result['ok']) {
            ActivityLog::record('autodj_start', 'Station #' . $sid);
        }
        return $result;
    }

    /** @param array<string,mixed> $station */
    public function stop(array $station): array
    {
        $sid = (int) $station['id'];
        Station::update($sid, ['autodj_status' => 'stopped']);
        $updated = Station::findWithServer($sid) ?: $station;

        $this->generateScript($updated);
        $driver = (string) ($updated['driver'] ?? env('SHOUTCAST_DRIVER', 'mock'));

        $rawRelay = trim((string) ($updated['relay_url'] ?? ''));
        $relayMode = (string) ($updated['relay_mode'] ?? 'fulltime');

        // Si hay una URL de Relay configurada y activa, Liquidsoap debe mantenerse activo emitiendo el Relay externo
        if ($rawRelay !== '' && $relayMode !== 'disabled') {
            $result = match ($driver) {
                'linux'   => $this->systemctl('restart', $sid),
                'windows' => $this->winStart($updated),
                default   => $this->mockSet($sid, false),
            };
        } else {
            $result = match ($driver) {
                'linux'   => $this->systemctl('stop', $sid),
                'windows' => $this->winStop($sid),
                default   => $this->mockSet($sid, false),
            };
        }

        if ($result['ok']) {
            ActivityLog::record('autodj_stop', 'Station #' . $sid);
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

    /** Resuelve URLs de reproduccion M3U / PLS a URLs directas de stream de audio */
    public function resolveRelayUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (preg_match('/\.(m3u|pls)(\?.*)?$/i', $url)) {
            $context = stream_context_create([
                'http' => [
                    'method'  => 'GET',
                    'header'  => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n",
                    'timeout' => 5,
                ],
                'ssl' => [
                    'verify_peer'      => false,
                    'verify_peer_name' => false,
                ],
            ]);
            $content = @file_get_contents($url, false, $context);
            if ($content) {
                $lines = explode("\n", str_replace("\r", "", $content));
                foreach ($lines as $line) {
                    $l = trim($line);
                    if ($l !== '' && !str_starts_with($l, '#') && !str_starts_with($l, '[')) {
                        if (str_starts_with(strtolower($l), 'file1=')) {
                            $target = trim(substr($l, 6));
                            if (filter_var($target, FILTER_VALIDATE_URL)) {
                                return $target;
                            }
                        }
                        if (filter_var($l, FILTER_VALIDATE_URL)) {
                            return $l;
                        }
                    }
                }
            }
        }

        return $url;
    }

    /** Escapa comillas para insertar en el script .liq. */
    private function esc(string $s): string
    {
        return str_replace('"', '\"', $s);
    }
}
