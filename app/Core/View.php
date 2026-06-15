<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    private static function resolvePath(string $view): string
    {
        return view_path(str_replace('.', '/', $view) . '.php');
    }

    public static function render(string $view, array $viewData = [], ?string $layout = 'layouts.app'): string
    {
        extract($viewData, EXTR_SKIP);
        ob_start();
        require self::resolvePath($view);
        $content = ob_get_clean() ?: '';

        if ($layout === null) {
            return $content;
        }

        ob_start();
        require self::resolvePath($layout);
        return ob_get_clean() ?: '';
    }

    public static function make(string $view, array $viewData = [], ?string $layout = 'layouts.app'): never
    {
        echo self::render($view, $viewData, $layout);
        exit;
    }

    public static function component(string $name, array $viewData = []): void
    {
        extract($viewData, EXTR_SKIP);
        require view_path('components/' . $name . '.php');
    }
}
