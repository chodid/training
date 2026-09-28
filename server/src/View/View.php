<?php

declare(strict_types=1);

namespace Training\View;

/**
 * Serverseitig gerenderte Seiten aus server/templates/ (Gestaltung nach docs/branding/, D-19).
 * Templates sind PHP-Dateien; Ausgaben immer über e() maskieren.
 */
final class View
{
    public function __construct(private readonly string $templateDir, private readonly string $assetDir)
    {
    }

    /** @param array<string, mixed> $vars */
    public function render(string $template, array $vars = [], string $layout = 'layout-auth'): string
    {
        $content = $this->include($template, $vars);

        return $this->include($layout, [...$vars, 'content' => $content]);
    }

    public function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** Tabler-Icon inline (lokal aus public/assets/icons, Branding B-05); leer, wenn nicht vorhanden. */
    public function icon(string $name, string $class = 'ic'): string
    {
        if (!preg_match('/^[a-z0-9-]+$/', $name)) {
            return '';
        }
        $svg = @file_get_contents($this->assetDir . '/icons/' . $name . '.svg');
        if ($svg === false) {
            return '';
        }
        $svg = (string) preg_replace('/<!--.*?-->/s', '', $svg);
        $svg = (string) preg_replace('/\s(width|height|class)="[^"]*"/', '', $svg);

        return trim(str_replace('<svg', '<svg class="' . $this->e($class) . '" aria-hidden="true" focusable="false"', $svg));
    }

    /** @param array<string, mixed> $vars */
    private function include(string $template, array $vars): string
    {
        $file = $this->templateDir . '/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('Template fehlt: ' . $template);
        }
        $render = function (string $__file, array $__vars): string {
            extract($__vars, EXTR_SKIP);
            ob_start();
            try {
                include $__file;
            } catch (\Throwable $e) {
                ob_end_clean();
                throw $e;
            }

            return (string) ob_get_clean();
        };

        return $render($file, $vars);
    }
}
