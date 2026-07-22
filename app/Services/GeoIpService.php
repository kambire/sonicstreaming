<?php

declare(strict_types=1);

namespace App\Services;

final class GeoIpService
{
    private static array $cache = [];

    /**
     * Resuelve geolocalización para una IP dada.
     * @return array{country:string,country_code:string,city:string,latitude:float|null,longitude:float|null}
     */
    public function resolve(string $ip): array
    {
        $ip = trim($ip);
        if ($ip === '' || $ip === '127.0.0.1' || $ip === '::1' || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
            return [
                'country'      => 'Local / Servidor',
                'country_code' => 'PY',
                'city'         => 'Asunción',
                'latitude'     => -25.2637,
                'longitude'    => -57.5759,
            ];
        }

        if (isset(self::$cache[$ip])) {
            return self::$cache[$ip];
        }

        // Consultar ip-api.com (gratuito, 45 req/min)
        $url = "http://ip-api.com/json/{$ip}?fields=status,country,countryCode,city,lat,lon";
        $data = null;
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 2,
                CURLOPT_CONNECTTIMEOUT => 1,
            ]);
            $body = curl_exec($ch);
            curl_close($ch);
            if ($body) {
                $data = json_decode((string) $body, true);
            }
        }

        if (is_array($data) && ($data['status'] ?? '') === 'success') {
            $res = [
                'country'      => (string) ($data['country'] ?? 'Desconocido'),
                'country_code' => strtoupper((string) ($data['countryCode'] ?? 'XX')),
                'city'         => (string) ($data['city'] ?? 'Desconocida'),
                'latitude'     => isset($data['lat']) ? (float) $data['lat'] : null,
                'longitude'    => isset($data['lon']) ? (float) $data['lon'] : null,
            ];
        } else {
            // Fallback por defecto si no hay conexión externa
            $res = [
                'country'      => 'Internacional',
                'country_code' => 'XX',
                'city'         => 'Desconocida',
                'latitude'     => 0.0,
                'longitude'    => 0.0,
            ];
        }

        self::$cache[$ip] = $res;
        return $res;
    }

    /**
     * Analiza el User-Agent para determinar tipo de dispositivo y reproductor.
     * @return array{device_type:string,player_name:string}
     */
    public function parseUserAgent(?string $ua): array
    {
        if (empty($ua)) {
            return ['device_type' => 'unknown', 'player_name' => 'Web Player / HTTP Client'];
        }

        $uaLower = strtolower($ua);
        $deviceType = 'desktop';

        if (str_contains($uaLower, 'mobile') || str_contains($uaLower, 'android') || str_contains($uaLower, 'iphone')) {
            $deviceType = 'mobile';
        } elseif (str_contains($uaLower, 'ipad') || str_contains($uaLower, 'tablet')) {
            $deviceType = 'tablet';
        } elseif (str_contains($uaLower, 'bot') || str_contains($uaLower, 'spider') || str_contains($uaLower, 'crawler')) {
            $deviceType = 'bot';
        }

        $playerName = 'Navegador Web';
        if (str_contains($uaLower, 'winamp')) {
            $playerName = 'Winamp';
        } elseif (str_contains($uaLower, 'vlc')) {
            $playerName = 'VLC Media Player';
        } elseif (str_contains($uaLower, 'tunein')) {
            $playerName = 'TuneIn Radio';
        } elseif (str_contains($uaLower, 'radioboss')) {
            $playerName = 'RadioBOSS';
        } elseif (str_contains($uaLower, 'itunes')) {
            $playerName = 'Apple iTunes';
        } elseif (str_contains($uaLower, 'firefox')) {
            $playerName = 'Mozilla Firefox';
        } elseif (str_contains($uaLower, 'edg')) {
            $playerName = 'Microsoft Edge';
        } elseif (str_contains($uaLower, 'chrome')) {
            $playerName = 'Google Chrome';
        } elseif (str_contains($uaLower, 'safari')) {
            $playerName = 'Apple Safari';
        } elseif (str_contains($uaLower, 'curl') || str_contains($uaLower, 'python') || str_contains($uaLower, 'gstreamer')) {
            $playerName = 'Cliente Técnico HTTP';
        }

        return [
            'device_type' => $deviceType,
            'player_name' => $playerName,
        ];
    }
}
