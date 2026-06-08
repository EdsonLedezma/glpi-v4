<?php

declare(strict_types=1);

namespace GlpiPlugin\Esjv4;

final class Catalog
{
    public const STATUS_PLANNED = 'planned';
    public const STATUS_PENDING_PLANNING = 'pending_planning';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_PAUSED_RFI = 'paused_rfi';
    public const STATUS_PAUSED_RESTRICTION = 'paused_restriction';
    public const STATUS_READY_TO_CLOSE = 'ready_to_close';
    public const STATUS_CLOSED = 'closed';

    public const DEFAULT_RFI_IMPACT = 'blocking';

    public static function constructionReleaseRequiredStages(): array
    {
        return [
            'planning',
            'structural_design',
            'connection_modeling',
        ];
    }

    public static function stages(): array
    {
        return [
            'planning' => 'Planeacion',
            'structural_design' => 'Diseno estructural',
            'team_assignment' => 'Asignacion de equipo',
            'connection_modeling' => 'Modelo de conexiones',
            'fabrication_drawings' => 'Planos de fabricacion',
            'erection_drawings' => 'Planos de montaje',
            'submittals' => 'Submittals',
            'requisitions' => 'Requisiciones',
            'engineering_closeout' => 'Cierre de ingenieria',
        ];
    }

    public static function phaseStatuses(): array
    {
        return [
            self::STATUS_PLANNED,
            self::STATUS_BLOCKED,
            self::STATUS_ACTIVE,
            self::STATUS_IN_PROGRESS,
            self::STATUS_PAUSED_RFI,
            self::STATUS_PAUSED_RESTRICTION,
            self::STATUS_READY_TO_CLOSE,
            self::STATUS_CLOSED,
        ];
    }

    public static function taskStatuses(): array
    {
        return [
            self::STATUS_PLANNED,
            self::STATUS_BLOCKED,
            'unassigned',
            'assigned',
            self::STATUS_IN_PROGRESS,
            self::STATUS_PAUSED_RFI,
            self::STATUS_PAUSED_RESTRICTION,
            'done',
            self::STATUS_CLOSED,
        ];
    }

    public static function rfiScopes(): array
    {
        return [
            'project',
            'phase',
            'building',
            'activity',
            'task',
            'product',
        ];
    }

    public static function rfiImpacts(): array
    {
        return [
            self::DEFAULT_RFI_IMPACT,
            'informational',
        ];
    }

    public static function defaultActivityTemplates(): array
    {
        return [
            'core_engineering' => [
                'name' => 'Actividades core de ingenieria',
                'activities' => [
                    'Planeacion',
                    'Diseno estructural',
                    'Asignacion de equipo',
                    'Modelo de conexiones',
                    'Planos de fabricacion',
                    'Planos de montaje',
                    'Submittals',
                    'Requisiciones',
                    'Cierre de ingenieria',
                ],
            ],
            'modeling_products' => [
                'name' => 'Productos de modelado',
                'activities' => [
                    'Anclas y Plantillas',
                    'Embebidos',
                    'Columnas',
                    'Vigas',
                    'Miscelaneos',
                    'Escaleras',
                    'Barandales',
                ],
            ],
        ];
    }

    public static function phaseActivityPackages(): array
    {
        return [
            'none' => [
                'name' => 'Sin plantilla',
                'template_keys' => [],
            ],
            'core_engineering' => [
                'name' => 'Ingenieria core',
                'template_keys' => ['core_engineering'],
            ],
            'modeling_products' => [
                'name' => 'Productos de modelado',
                'template_keys' => ['modeling_products'],
            ],
            'core_and_modeling' => [
                'name' => 'Ingenieria core + modelado',
                'template_keys' => ['core_engineering', 'modeling_products'],
            ],
        ];
    }
}
