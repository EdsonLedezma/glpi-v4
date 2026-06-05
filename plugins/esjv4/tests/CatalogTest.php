<?php

declare(strict_types=1);

use GlpiPlugin\Esjv4\Catalog;

esjv4_assert_true(class_exists(Catalog::class), 'Catalog class must exist');

$required_gate_stages = Catalog::constructionReleaseRequiredStages();

esjv4_assert_same(
    ['planning', 'structural_design', 'connection_modeling'],
    $required_gate_stages,
    'Construction release gate must require the three main stages'
);

$templates = Catalog::defaultActivityTemplates();

esjv4_assert_true(isset($templates['core_engineering']), 'Core engineering template must exist');
esjv4_assert_true(
    in_array('Modelo de conexiones', $templates['core_engineering']['activities'], true),
    'Core engineering template must include connection modeling'
);

esjv4_assert_same('blocking', Catalog::DEFAULT_RFI_IMPACT, 'RFI impact must default to blocking');
esjv4_assert_true(in_array('phase', Catalog::rfiScopes(), true), 'RFI scopes must include phase');
esjv4_assert_true(in_array('task', Catalog::rfiScopes(), true), 'RFI scopes must include task');
