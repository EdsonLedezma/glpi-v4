<?php

declare(strict_types=1);

use GlpiPlugin\Esjv4\GateService;

esjv4_assert_true(class_exists(GateService::class), 'GateService class must exist');

$open_result = GateService::evaluateConstructionRelease([
    'stage_statuses' => [
        'planning' => 'closed',
        'structural_design' => 'closed',
        'connection_modeling' => 'closed',
    ],
    'blocking_rfis' => [],
]);

esjv4_assert_same(true, $open_result['can_release'], 'Gate must open when all required stages are closed');
esjv4_assert_same([], $open_result['missing_stages'], 'Open gate must not report missing stages');

$missing_stage = GateService::evaluateConstructionRelease([
    'stage_statuses' => [
        'planning' => 'closed',
        'structural_design' => 'in_progress',
        'connection_modeling' => 'closed',
    ],
    'blocking_rfis' => [],
]);

esjv4_assert_same(false, $missing_stage['can_release'], 'Gate must stay blocked while a required stage is open');
esjv4_assert_same(['structural_design'], $missing_stage['missing_stages'], 'Gate must identify incomplete required stage');

$blocked_by_rfi = GateService::evaluateConstructionRelease([
    'stage_statuses' => [
        'planning' => 'closed',
        'structural_design' => 'closed',
        'connection_modeling' => 'closed',
    ],
    'blocking_rfis' => [
        ['id' => 101, 'scope' => 'phase'],
    ],
]);

esjv4_assert_same(false, $blocked_by_rfi['can_release'], 'Gate must stay blocked while blocking RFIs exist');
esjv4_assert_same([101], $blocked_by_rfi['blocking_rfi_ids'], 'Gate must report blocking RFI IDs');
