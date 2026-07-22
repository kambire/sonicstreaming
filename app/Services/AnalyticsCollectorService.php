<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ListenerSession;
use App\Models\Station;

final class AnalyticsCollectorService
{
    private GeoIpService $geoIp;

    public function __construct(?GeoIpService $geoIp = null)
    {
        $this->geoIp = $geoIp ?? new GeoIpService();
    }

    /**
     * Sincroniza los oyentes activos de una estación desde el servidor Shoutcast DNAS.
     * @param array<string,mixed> $station
     */
    public function syncActiveListeners(array $station): void
    {
        $sid = (int) $station['id'];
        $host = (string) ($station['hostname'] ?? env('SHOUTCAST_HOST', '127.0.0.1'));
        $port = (int) $station['port'];
        $rawAdmin = (string) ($station['admin_password'] ?? '');
        $adminPass = \App\Services\Crypto::decrypt($rawAdmin) ?: $rawAdmin;
        $sourcePass = (string) ($station['source_password'] ?? '');

        // Consultar clientes conectados via Shoutcast DNAS2 XML con sid=1
        $url = "http://{$host}:{$port}/admin.cgi?sid=1&mode=viewxml&page=3";
        $clients = $this->fetchShoutcastClients($url, $adminPass);
        if (!$clients && $sourcePass !== '' && $sourcePass !== $adminPass) {
            $clients = $this->fetchShoutcastClients($url, $sourcePass);
        }

        $activeIps = [];
        $now = date('Y-m-d H:i:s');

        foreach ($clients as $client) {
            $ip = (string) ($client['ip'] ?? '');
            if ($ip === '') {
                continue;
            }
            $activeIps[] = $ip;
            $ua = (string) ($client['user_agent'] ?? '');
            $connectedTimeSec = (int) ($client['connect_time'] ?? 0);
            $bitrate = (int) ($station['max_bitrate'] ?? 128);
            $bytesPerSec = ($bitrate * 1000) / 8;
            $bytesSent = (int) ($client['bytes_sent'] ?? 0);
            if ($bytesSent <= 0 && $connectedTimeSec > 0) {
                $bytesSent = (int) round($connectedTimeSec * $bytesPerSec);
            }

            // Buscar si ya existe una sesión activa para esta IP y estación
            $stmt = \App\Core\Model::db()->prepare(
                'SELECT id, connected_at FROM listener_sessions 
                 WHERE station_id = ? AND listener_ip = ? AND disconnected_at IS NULL 
                 LIMIT 1'
            );
            $stmt->execute([$sid, $ip]);
            $existing = $stmt->fetch();

            if ($existing) {
                // Actualizar timestamp de última vista y duración acumulada
                $sessId = (int) $existing['id'];
                $connAt = strtotime((string) $existing['connected_at']);
                $duration = max($connectedTimeSec, time() - $connAt);
                if ($bytesSent <= 0) {
                    $bytesSent = (int) round($duration * $bytesPerSec);
                }

                $upd = \App\Core\Model::db()->prepare(
                    'UPDATE listener_sessions 
                     SET last_seen_at = ?, duration_seconds = ?, bytes_sent = ? 
                     WHERE id = ?'
                );
                $upd->execute([$now, $duration, $bytesSent, $sessId]);
            } else {
                // Nueva sesión: resolver GeoIP y User-Agent
                $geo = $this->geoIp->resolve($ip);
                $uaInfo = $this->geoIp->parseUserAgent($ua);

                ListenerSession::create([
                    'station_id'       => $sid,
                    'listener_ip'      => $ip,
                    'country'          => $geo['country'],
                    'country_code'     => $geo['country_code'],
                    'city'             => $geo['city'],
                    'latitude'         => $geo['latitude'],
                    'longitude'        => $geo['longitude'],
                    'user_agent'       => mb_substr($ua, 0, 255),
                    'device_type'      => $uaInfo['device_type'],
                    'player_name'      => $uaInfo['player_name'],
                    'connected_at'     => $now,
                    'last_seen_at'     => $now,
                    'duration_seconds' => $connectedTimeSec,
                    'bytes_sent'       => $bytesSent,
                ]);
            }
        }

        // Marcar como desconectadas las sesiones que ya no están activas en Shoutcast
        if ($activeIps) {
            $inClause = implode(',', array_fill(0, count($activeIps), '?'));
            $sql = "UPDATE listener_sessions 
                    SET disconnected_at = ? 
                    WHERE station_id = ? AND disconnected_at IS NULL AND listener_ip NOT IN ({$inClause})";
            $params = array_merge([$now, $sid], $activeIps);
            $stmt = \App\Core\Model::db()->prepare($sql);
            $stmt->execute($params);
        } else {
            $sql = "UPDATE listener_sessions 
                    SET disconnected_at = ? 
                    WHERE station_id = ? AND disconnected_at IS NULL";
            $stmt = \App\Core\Model::db()->prepare($sql);
            $stmt->execute([$now, $sid]);
        }
    }

    /** @return array<int,array{ip:string,user_agent:string,connect_time:int,bytes_sent:int}> */
    private function fetchShoutcastClients(string $url, string $pass): array
    {
        if (!function_exists('curl_init') || $pass === '') {
            return [];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 3,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_USERAGENT      => 'SonicStreamingPanel/1.0',
            CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
            CURLOPT_USERPWD        => 'admin:' . $pass,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);

        if (!$body || !str_contains((string) $body, '<SHOUTCASTSERVER>')) {
            return [];
        }

        $clients = [];
        @$xml = simplexml_load_string((string) $body);
        if ($xml && isset($xml->LISTENERS->LISTENER)) {
            foreach ($xml->LISTENERS->LISTENER as $l) {
                $rawIp = (string) ($l->XFF ?? $l->HOSTNAME ?? $l->IP ?? '');
                if (str_contains($rawIp, ',')) {
                    $rawIp = trim(explode(',', $rawIp)[0]);
                }
                if ($rawIp !== '') {
                    $clients[] = [
                        'ip'           => $rawIp,
                        'user_agent'   => (string) ($l->USERAGENT ?? ''),
                        'connect_time' => (int) ($l->CONNECTTIME ?? 0),
                        'bytes_sent'   => (int) ($l->BYTES ?? 0),
                    ];
                }
            }
        }

        return $clients;
    }
}
