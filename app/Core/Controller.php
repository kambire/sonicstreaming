<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Controlador base con helpers de vista y respuesta.
 */
abstract class Controller
{
    /**
     * Renderiza una vista con layout y la envia al navegador.
     *
     * @param array<string,mixed> $data
     */
    protected function view(string $view, array $data = [], string $layout = 'layouts/app'): void
    {
        echo View::render($view, $data, $layout);
    }

    /**
     * Devuelve JSON.
     *
     * @param array<string,mixed> $data
     */
    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Guarda input en sesion para repoblar formularios.
     *
     * @param array<string,mixed> $data
     */
    protected function flashOld(array $data): void
    {
        unset($data['password'], $data['password_confirm'], $data['csrf_token']);
        $_SESSION['_old'] = $data;
    }

    protected function clearOld(): void
    {
        unset($_SESSION['_old']);
    }
}
