<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Motor de vistas basado en PHP puro con layouts.
 */
final class View
{
    /**
     * Renderiza una vista dentro de un layout y devuelve el HTML.
     *
     * @param array<string,mixed> $data
     */
    public static function render(string $view, array $data = [], string $layout = 'layouts/app'): string
    {
        $content = self::renderPartial($view, $data);

        if ($layout === '') {
            return $content;
        }

        $data['content'] = $content;
        return self::renderPartial($layout, $data);
    }

    /**
     * Renderiza una vista o parcial y devuelve su HTML.
     *
     * @param array<string,mixed> $data
     */
    public static function renderPartial(string $view, array $data = []): string
    {
        $file = BASE_PATH . '/app/Views/' . $view . '.php';
        if (!is_file($file)) {
            return "<!-- Vista no encontrada: {$view} -->";
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }
}
