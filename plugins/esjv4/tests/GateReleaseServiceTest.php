<?php

declare(strict_types=1);

use GlpiPlugin\Esjv4\Catalog;
use GlpiPlugin\Esjv4\EventLog;
use GlpiPlugin\Esjv4\GateReleaseService;

esjv4_assert_true(class_exists(GateReleaseService::class), 'GateReleaseService class must exist');

$repo = new class {
    public array $activated = [];
    public array $events = [];
    public array $context = [
        'stage_statuses' => [
            'planning' => 'closed',
            'structural_design' => 'closed',
            'connection_modeling' => 'closed',
        ],
        'blocking_rfis' => [],
    ];

    public function constructionReleaseContext(int $project_id): array
    {
        return $this->context;
    }

    public function activateConstructionPhases(int $project_id): int
    {
        $this->activated[] = $project_id;
        return 5;
    }

    public function recordEvent(array $event): int
    {
        $this->events[] = $event;
        return count($this->events);
    }
};

$service = new GateReleaseService($repo);
$released = $service->releaseConstruction(10);

esjv4_assert_same(true, $released['released'], 'Gate release succeeds when conditions are met');
esjv4_assert_same(5, $released['activated_phases'], 'Release reports activated phases');
esjv4_assert_same([10], $repo->activated, 'Release activates phases for project');
esjv4_assert_same(EventLog::GATE_RELEASED, $repo->events[0]['event_type'], 'Release records gate event');

$repo->activated = [];
$repo->events = [];
$repo->context['stage_statuses']['connection_modeling'] = Catalog::STATUS_IN_PROGRESS;

$blocked = $service->releaseConstruction(10);

esjv4_assert_same(false, $blocked['released'], 'Gate release fails when a required stage is open');
esjv4_assert_same(['connection_modeling'], $blocked['missing_stages'], 'Blocked release reports missing stage');
esjv4_assert_same([], $repo->activated, 'Blocked release does not activate phases');

$repo->context['stage_statuses']['connection_modeling'] = Catalog::STATUS_CLOSED;
$repo->context['blocking_rfis'] = [['id' => 777, 'scope' => 'phase']];

$blocked_rfi = $service->releaseConstruction(10);

esjv4_assert_same(false, $blocked_rfi['released'], 'Gate release fails with blocking RFI');
esjv4_assert_same([777], $blocked_rfi['blocking_rfi_ids'], 'Blocked release reports RFI IDs');
