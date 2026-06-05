<?php

declare(strict_types=1);

namespace GlpiPlugin\Esjv4;

final class Menu
{
    public static function getMenuName(): string
    {
        return Plugin::NAME;
    }

    public static function getMenuContent(): array
    {
        return [
            'title' => Plugin::NAME,
            'page' => '/plugins/esjv4/front/dashboard.php',
            'icon' => 'ti ti-building-factory-2',
            'links' => [
                'search' => '/plugins/esjv4/front/dashboard.php',
                'templates' => '/plugins/esjv4/front/templates.php',
            ],
        ];
    }
}
