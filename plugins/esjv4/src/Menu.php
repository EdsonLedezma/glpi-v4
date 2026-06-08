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
            'is_multi_entries' => true,
            'esjv4_home' => [
                'title' => 'ESJ Inicio',
                'page' => '/plugins/esjv4/front/dashboard.php',
                'icon' => 'ti ti-building-factory-2',
            ],
            'esjv4_projects' => [
                'title' => 'ESJ Proyectos',
                'page' => '/plugins/esjv4/front/projects.php',
                'icon' => 'ti ti-folders',
            ],
            'esjv4_planning' => [
                'title' => 'Planeacion SAP',
                'page' => '/plugins/esjv4/front/planning.php',
                'icon' => 'ti ti-clipboard-list',
            ],
            'esjv4_kanban' => [
                'title' => 'Kanban Ingenieria',
                'page' => '/plugins/esjv4/front/projects.php',
                'icon' => 'ti ti-layout-kanban',
            ],
            'esjv4_rfis' => [
                'title' => 'RFIs y Bloqueos',
                'page' => '/plugins/esjv4/front/projects.php',
                'icon' => 'ti ti-alert-triangle',
            ],
            'esjv4_times' => [
                'title' => 'Tiempos y SLA',
                'page' => '/plugins/esjv4/front/projects.php',
                'icon' => 'ti ti-clock-hour-4',
            ],
            'esjv4_templates' => [
                'title' => 'Plantillas ESJ',
                'page' => '/plugins/esjv4/front/templates.php',
                'icon' => 'ti ti-template',
            ],
        ];
    }
}
