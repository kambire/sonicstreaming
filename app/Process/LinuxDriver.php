<?php

declare(strict_types=1);

namespace App\Process;

/**
 * Driver para produccion en Linux mediante systemd.
 *
 * Requiere una unidad plantilla /etc/systemd/system/shoutcast@.service que
 * arranque:  sc_serv daemon /ruta/storage/configs/station_%i.conf
 * y una regla sudoers que permita al usuario web ejecutar systemctl sobre
 * shoutcast@* sin contrasena. Ver README (seccion despliegue).
 */
final class LinuxDriver implements ProcessController
{
    private function unit(array $station): string
    {
        return 'shoutcast@' . (int) $station['id'] . '.service';
    }

    private function systemctl(string $action, array $station): array
    {
        $unit = escapeshellarg($this->unit($station));
        $cmd  = 'sudo systemctl ' . escapeshellarg($action) . ' ' . $unit . ' 2>&1';
        $out  = [];
        $code = 0;
        @exec($cmd, $out, $code);
        return [
            'ok'      => $code === 0,
            'message' => $code === 0
                ? "systemctl {$action} ok."
                : ('systemctl fallo: ' . implode(' ', $out)),
        ];
    }

    public function start(array $station, string $configPath): array
    {
        // La config ya fue generada por ShoutcastService; systemd la referencia por id.
        return $this->systemctl('start', $station);
    }

    public function stop(array $station): array
    {
        return $this->systemctl('stop', $station);
    }

    public function restart(array $station, string $configPath): array
    {
        return $this->systemctl('restart', $station);
    }

    public function isRunning(array $station): bool
    {
        $unit = escapeshellarg($this->unit($station));
        $out  = [];
        $code = 0;
        @exec('systemctl is-active ' . $unit . ' 2>&1', $out, $code);
        return trim(implode('', $out)) === 'active';
    }
}
