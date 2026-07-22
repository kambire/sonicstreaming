<?php

declare(strict_types=1);

namespace App\Process;

/**
 * Driver para Windows (desarrollo): arranca sc_serv.exe con la config de la
 * estacion y guarda el PID. Es "best-effort": pensado para probar Shoutcast
 * localmente. En produccion usa el driver linux.
 */
final class WindowsDriver implements ProcessController
{
    private function pidFile(array $station): string
    {
        return BASE_PATH . '/storage/pids/station_' . (int) $station['id'] . '.pid';
    }

    public function start(array $station, string $configPath): array
    {
        $bin = (string) env('SHOUTCAST_BIN', '');
        if ($bin === '' || !is_file($bin)) {
            return ['ok' => false, 'message' => 'No se encuentra el binario sc_serv.exe (revisa SHOUTCAST_BIN).'];
        }

        // Lanzar en segundo plano sin ventana. bypass_shell para obtener el PID real.
        $cmd = '"' . $bin . '" "' . $configPath . '"';
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['file', BASE_PATH . '/storage/logs/station_' . (int) $station['id'] . '.log', 'a'],
            2 => ['file', BASE_PATH . '/storage/logs/station_' . (int) $station['id'] . '.log', 'a'],
        ];
        $proc = @proc_open($cmd, $descriptors, $pipes, dirname($bin), null, ['bypass_shell' => true]);
        if (!is_resource($proc)) {
            return ['ok' => false, 'message' => 'No se pudo iniciar el proceso sc_serv.exe.'];
        }
        $status = proc_get_status($proc);
        if (!empty($status['pid'])) {
            file_put_contents($this->pidFile($station), (string) $status['pid']);
        }
        return ['ok' => true, 'message' => 'sc_serv.exe iniciado (PID ' . ($status['pid'] ?? '?') . ').'];
    }

    public function stop(array $station): array
    {
        $pidFile = $this->pidFile($station);
        if (!is_file($pidFile)) {
            return ['ok' => false, 'message' => 'No hay PID registrado para esta estacion.'];
        }
        $pid = (int) trim((string) file_get_contents($pidFile));
        if ($pid > 0) {
            @exec('taskkill /F /T /PID ' . escapeshellarg((string) $pid) . ' 2>&1');
        }
        @unlink($pidFile);
        return ['ok' => true, 'message' => 'Proceso detenido.'];
    }

    public function restart(array $station, string $configPath): array
    {
        $this->stop($station);
        return $this->start($station, $configPath);
    }

    public function isRunning(array $station): bool
    {
        // Best-effort: comprobar que el puerto responde.
        $host = (string) env('SHOUTCAST_HOST', '127.0.0.1');
        $port = (int) $station['port'];
        $conn = @fsockopen($host, $port, $errno, $errstr, 1);
        if (is_resource($conn)) {
            fclose($conn);
            return true;
        }
        return false;
    }
}
