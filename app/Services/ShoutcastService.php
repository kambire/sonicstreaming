<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Station;
use App\Process\LinuxDriver;
use App\Process\MockDriver;
use App\Process\ProcessController;
use App\Process\WindowsDriver;

/**
 * Orquesta la generacion de configuracion, el control de procesos y la
 * lectura de estadisticas de las instancias Shoutcast DNAS v2.
 */
final class ShoutcastService
{
    /**
     * Selecciona el driver segun el servidor de la estacion (o el .env por defecto).
     * @param array<string,mixed> $station Debe incluir 'driver' (del JOIN con servers) o se usa el .env.
     */
    public function driverFor(array $station): ProcessController
    {
        $driver = (string) ($station['driver'] ?? env('SHOUTCAST_DRIVER', 'mock'));
        return match ($driver) {
            'linux'   => new LinuxDriver(),
            'windows' => new WindowsDriver(),
            default   => new MockDriver(),
        };
    }

    public function configPath(int $stationId): string
    {
        return BASE_PATH . '/storage/configs/station_' . $stationId . '.conf';
    }

    /**
     * Genera el archivo de configuracion sc_serv para una estacion a partir
     * de la plantilla. Devuelve la ruta del archivo escrito.
     *
     * @param array<string,mixed> $station
     */
    public function generateConfig(array $station): string
    {
        $tpl = file_get_contents(BASE_PATH . '/templates/sc_serv.conf.tpl') ?: '';

        $adminPass = Crypto::decrypt((string) $station['admin_password']);
        $logPath   = BASE_PATH . '/storage/logs/station_' . (int) $station['id'] . '_sc.log';

        $relayLine = '';
        if (($station['type'] ?? 'live') === 'relay' && !empty($station['relay_url'])) {
            $relayLine = 'streamrelayurl_1=' . $station['relay_url'];
        }

        $replacements = [
            '{{PORT}}'          => (string) (int) $station['port'],
            '{{PASSWORD}}'      => (string) $station['source_password'],
            '{{ADMIN_PASSWORD}}'=> $adminPass,
            '{{MAX_LISTENERS}}' => (string) (int) $station['max_listeners'],
            '{{MAX_BITRATE}}'   => (string) ((int) $station['max_bitrate'] * 1000),
            '{{STATION_NAME}}'  => (string) $station['name'],
            '{{GENRE}}'         => (string) ($station['genre'] ?? ''),
            '{{LOG_FILE}}'      => $logPath,
            '{{RELAY_LINE}}'    => $relayLine,
        ];

        $config = strtr($tpl, $replacements);

        $path = $this->configPath((int) $station['id']);
        file_put_contents($path, $config);
        return $path;
    }

    /**
     * Inicia una estacion: genera config y arranca el proceso.
     * @param array<string,mixed> $station (con datos del servidor via JOIN)
     * @return array{ok:bool,message:string}
     */
    public function start(array $station): array
    {
        $path = $this->generateConfig($station);
        $result = $this->driverFor($station)->start($station, $path);
        if ($result['ok']) {
            Station::update((int) $station['id'], ['status' => 'running']);
        }
        return $result;
    }

    /** @param array<string,mixed> $station */
    public function stop(array $station): array
    {
        $result = $this->driverFor($station)->stop($station);
        if ($result['ok']) {
            Station::update((int) $station['id'], ['status' => 'stopped']);
        }
        return $result;
    }

    /** @param array<string,mixed> $station */
    public function restart(array $station): array
    {
        $path = $this->generateConfig($station);
        $result = $this->driverFor($station)->restart($station, $path);
        if ($result['ok']) {
            Station::update((int) $station['id'], ['status' => 'running']);
        }
        return $result;
    }

    /**
     * Lee estadisticas en vivo desde el DNAS (JSON). Devuelve datos
     * normalizados; si el server no responde, is_up=false.
     *
     * @param array<string,mixed> $station
     * @return array{is_up:bool,current_listeners:int,peak_listeners:int,unique_listeners:int,bitrate:int,song_title:string}
     */
    public function fetchStats(array $station): array
    {
        $empty = [
            'is_up' => false, 'current_listeners' => 0, 'peak_listeners' => 0,
            'unique_listeners' => 0, 'bitrate' => 0, 'song_title' => '',
        ];

        // En modo simulado, inventamos datos plausibles si la estacion "corre".
        $driver = (string) ($station['driver'] ?? env('SHOUTCAST_DRIVER', 'mock'));
        if ($driver === 'mock') {
            $running = $this->driverFor($station)->isRunning($station);
            if (!$running) {
                return $empty;
            }
            $listeners = random_int(0, (int) $station['max_listeners']);
            return [
                'is_up' => true,
                'current_listeners' => $listeners,
                'peak_listeners' => min((int) $station['max_listeners'], $listeners + random_int(0, 10)),
                'unique_listeners' => $listeners,
                'bitrate' => (int) $station['max_bitrate'],
                'song_title' => 'Demo Stream - Pista ' . random_int(1, 20),
            ];
        }

        $host = (string) ($station['hostname'] ?? env('SHOUTCAST_HOST', '127.0.0.1'));
        $port = (int) $station['port'];
        $url  = "http://{$host}:{$port}/statistics?json=1";

        $json = $this->httpGet($url);
        if ($json === null) {
            return $empty;
        }
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return $empty;
        }

        // DNAS2 devuelve streams[]; tomamos el primero.
        $stream = $data['streams'][0] ?? $data;
        return [
            'is_up' => true,
            'current_listeners' => (int) ($stream['currentlisteners'] ?? 0),
            'peak_listeners'    => (int) ($stream['peaklisteners'] ?? 0),
            'unique_listeners'  => (int) ($stream['uniquelisteners'] ?? 0),
            'bitrate'           => (int) (($stream['bitrate'] ?? 0)),
            'song_title'        => (string) ($stream['songtitle'] ?? ''),
        ];
    }

    private function httpGet(string $url): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 3,
                CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_USERAGENT      => 'SonicStreamingPanel/1.0',
            ]);
            $body = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return ($body !== false && $code >= 200 && $code < 400) ? (string) $body : null;
        }

        $ctx = stream_context_create(['http' => ['timeout' => 3]]);
        $body = @file_get_contents($url, false, $ctx);
        return $body === false ? null : $body;
    }
}
