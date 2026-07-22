<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Station;

final class StreamController extends Controller
{
    public function listen(Request $request, string $id): void
    {
        $station = Station::findWithServer((int) $id);
        if (!$station) {
            http_response_code(404);
            echo 'Estación no encontrada.';
            return;
        }

        $host = !empty($station['hostname']) ? $station['hostname'] : 'sonic.geeks.com.py';
        $port = (int) $station['port'];
        $streamUrl = "http://{$host}:{$port}/stream";

        header("Location: {$streamUrl}");
        exit;
    }

    public function m3u(Request $request, string $id): void
    {
        $station = Station::findWithServer((int) $id);
        if (!$station) {
            http_response_code(404);
            echo 'Estación no encontrada.';
            return;
        }

        $host = !empty($station['hostname']) ? $station['hostname'] : 'sonic.geeks.com.py';
        $port = (int) $station['port'];
        $name = $station['name'] ?? 'SonicStreaming Radio';
        $streamUrl = "http://{$host}:{$port}/stream";

        $content  = "#EXTM3U\r\n";
        $content .= "#EXTINF:-1,{$name}\r\n";
        $content .= "{$streamUrl}\r\n";

        header('Content-Type: audio/x-mpegurl');
        header('Content-Disposition: attachment; filename="station_' . (int) $id . '.m3u"');
        echo $content;
        exit;
    }

    public function pls(Request $request, string $id): void
    {
        $station = Station::findWithServer((int) $id);
        if (!$station) {
            http_response_code(404);
            echo 'Estación no encontrada.';
            return;
        }

        $host = !empty($station['hostname']) ? $station['hostname'] : 'sonic.geeks.com.py';
        $port = (int) $station['port'];
        $name = $station['name'] ?? 'SonicStreaming Radio';
        $streamUrl = "http://{$host}:{$port}/stream";

        $content  = "[playlist]\r\n";
        $content .= "NumberOfEntries=1\r\n";
        $content .= "File1={$streamUrl}\r\n";
        $content .= "Title1={$name}\r\n";
        $content .= "Length1=-1\r\n";
        $content .= "Version=2\r\n";

        header('Content-Type: audio/x-scpls');
        header('Content-Disposition: attachment; filename="station_' . (int) $id . '.pls"');
        echo $content;
        exit;
    }
}
