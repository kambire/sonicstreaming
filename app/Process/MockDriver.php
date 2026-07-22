<?php

declare(strict_types=1);

namespace App\Process;

/**
 * Driver simulado: no arranca procesos reales. Marca el estado con un
 * archivo "running" en storage/pids para que el panel se comporte de
 * forma realista durante el desarrollo en Windows/XAMPP sin Shoutcast.
 */
final class MockDriver implements ProcessController
{
    private function marker(array $station): string
    {
        return BASE_PATH . '/storage/pids/station_' . (int) $station['id'] . '.running';
    }

    public function start(array $station, string $configPath): array
    {
        @touch($this->marker($station));
        return ['ok' => true, 'message' => 'Estacion iniciada (modo simulado).'];
    }

    public function stop(array $station): array
    {
        $m = $this->marker($station);
        if (is_file($m)) {
            @unlink($m);
        }
        return ['ok' => true, 'message' => 'Estacion detenida (modo simulado).'];
    }

    public function restart(array $station, string $configPath): array
    {
        $this->stop($station);
        return $this->start($station, $configPath);
    }

    public function isRunning(array $station): bool
    {
        return is_file($this->marker($station));
    }
}
