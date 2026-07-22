<?php

declare(strict_types=1);

namespace App\Process;

/**
 * Contrato para controlar el ciclo de vida de una instancia Shoutcast.
 * Cada driver (mock/windows/linux) implementa esta interfaz.
 *
 * Todos los metodos devuelven: ['ok' => bool, 'message' => string]
 */
interface ProcessController
{
    /** @param array<string,mixed> $station */
    public function start(array $station, string $configPath): array;

    /** @param array<string,mixed> $station */
    public function stop(array $station): array;

    /** @param array<string,mixed> $station */
    public function restart(array $station, string $configPath): array;

    /** @param array<string,mixed> $station */
    public function isRunning(array $station): bool;
}
