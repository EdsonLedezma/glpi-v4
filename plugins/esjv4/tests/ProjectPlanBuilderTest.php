<?php

declare(strict_types=1);

use GlpiPlugin\Esjv4\Catalog;
use GlpiPlugin\Esjv4\ProjectPlanBuilder;

esjv4_assert_true(class_exists(ProjectPlanBuilder::class), 'ProjectPlanBuilder class must exist');

$plan = ProjectPlanBuilder::buildInitialPlan([
    'phase_slots' => [
        ['slot' => 1, 'name' => 'Fase 01', 'building_key' => 'b1', 'activity_package_key' => 'core_and_modeling'],
        ['slot' => 2, 'name' => 'Fase 02', 'building_key' => 'b2', 'activity_package_key' => 'none'],
    ],
]);

esjv4_assert_same(
    Catalog::constructionReleaseRequiredStages(),
    array_column($plan['gate_stages'], 'key'),
    'Initial plan must include the three required gate stages'
);

esjv4_assert_same(Catalog::STATUS_ACTIVE, $plan['gate_stages'][0]['status'], 'Planning starts active');
esjv4_assert_same(Catalog::STATUS_BLOCKED, $plan['gate_stages'][1]['status'], 'Structural design starts blocked');
esjv4_assert_same(Catalog::STATUS_BLOCKED, $plan['gate_stages'][2]['status'], 'Connection modeling starts blocked');

esjv4_assert_same(2, count($plan['phases']), 'Only selected phase slots become project phases');
esjv4_assert_same(Catalog::STATUS_BLOCKED, $plan['phases'][0]['status'], 'Construction phase starts blocked');
esjv4_assert_same('Fase 01', $plan['phases'][0]['name'], 'Phase keeps planning name');
esjv4_assert_same('b1', $plan['phases'][0]['building_key'], 'Phase keeps selected building key');

esjv4_assert_true(
    in_array('Modelo de conexiones', array_column($plan['phases'][0]['activities'], 'name'), true),
    'Phase with core package must preload core activities'
);

esjv4_assert_true(
    in_array('Columnas', array_column($plan['phases'][0]['activities'], 'name'), true),
    'Phase with modeling package must preload modeling product activities'
);

esjv4_assert_same([], $plan['phases'][1]['activities'], 'Phase with none package starts without activities');
