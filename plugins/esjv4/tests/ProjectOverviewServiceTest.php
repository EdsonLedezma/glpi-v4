<?php

declare(strict_types=1);

use GlpiPlugin\Esjv4\Catalog;
use GlpiPlugin\Esjv4\ProjectOverviewService;

esjv4_assert_true(class_exists(ProjectOverviewService::class), 'ProjectOverviewService class must exist');

$repo = new class {
    public function project(int $project_id): array
    {
        return [
            'id' => $project_id,
            'project_code' => 'EAA260102',
            'project_name' => 'MBA San Rafael',
            'customer_name' => 'Punto Estructural',
            'status' => Catalog::STATUS_PLANNED,
        ];
    }

    public function stages(int $project_id): array
    {
        return [
            ['key' => 'planning', 'name' => 'Planeacion', 'status' => Catalog::STATUS_CLOSED],
            ['key' => 'structural_design', 'name' => 'Diseno estructural', 'status' => Catalog::STATUS_CLOSED],
            ['key' => 'connection_modeling', 'name' => 'Modelo de conexiones', 'status' => Catalog::STATUS_IN_PROGRESS],
        ];
    }

    public function phases(int $project_id): array
    {
        return [
            ['id' => 1, 'name' => 'Fase 01', 'status' => Catalog::STATUS_BLOCKED],
            ['id' => 2, 'name' => 'Fase 02', 'status' => Catalog::STATUS_CLOSED],
            ['id' => 3, 'name' => 'Fase 03', 'status' => Catalog::STATUS_ACTIVE],
        ];
    }

    public function activities(int $project_id): array
    {
        return [
            ['id' => 1, 'name' => 'Modelo de conexiones', 'status' => Catalog::STATUS_CLOSED],
            ['id' => 2, 'name' => 'Planos de fabricacion', 'status' => Catalog::STATUS_ACTIVE],
            ['id' => 3, 'name' => 'Submittal', 'status' => Catalog::STATUS_BLOCKED],
            ['id' => 4, 'name' => 'Cierre', 'status' => Catalog::STATUS_CLOSED],
        ];
    }

    public function constructionReleaseContext(int $project_id): array
    {
        return [
            'stage_statuses' => [
                'planning' => Catalog::STATUS_CLOSED,
                'structural_design' => Catalog::STATUS_CLOSED,
                'connection_modeling' => Catalog::STATUS_IN_PROGRESS,
            ],
            'blocking_rfis' => [],
        ];
    }
};

$overview = (new ProjectOverviewService($repo))->overview(55);

esjv4_assert_same('EAA260102', $overview['project']['project_code'], 'Overview includes project data');
esjv4_assert_same(50, $overview['activity_progress_percent'], 'Activity progress counts closed activities');
esjv4_assert_same(33, $overview['phase_progress_percent'], 'Phase progress counts closed phases');
esjv4_assert_same(false, $overview['gate']['can_release'], 'Gate cannot release while connection modeling is open');
esjv4_assert_same(['connection_modeling'], $overview['gate']['missing_stages'], 'Gate reports missing stage');
esjv4_assert_same(1, $overview['phase_counts']['active'], 'Overview counts active phases');
esjv4_assert_same(1, $overview['phase_counts']['blocked'], 'Overview counts blocked phases');
esjv4_assert_same(1, $overview['phase_counts']['closed'], 'Overview counts closed phases');
