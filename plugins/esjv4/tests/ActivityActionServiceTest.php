<?php

declare(strict_types=1);

use GlpiPlugin\Esjv4\ActivityActionService;
use GlpiPlugin\Esjv4\Catalog;
use GlpiPlugin\Esjv4\EventLog;

esjv4_assert_true(class_exists(ActivityActionService::class), 'ActivityActionService class must exist');

$repo = new class {
    public array $activities = [
        5 => [
            'id' => 5,
            'esj_projects_id' => 9,
            'esj_phases_id' => 3,
            'status' => Catalog::STATUS_ACTIVE,
            'name' => 'Planos de fabricacion',
        ],
    ];
    public array $updates = [];
    public array $events = [];
    public array $blocking_rfis = [];

    public function activity(int $activity_id): array
    {
        return $this->activities[$activity_id] ?? [];
    }

    public function updateActivityStatus(int $activity_id, string $status): bool
    {
        $this->updates[] = [$activity_id, $status];
        $this->activities[$activity_id]['status'] = $status;

        return true;
    }

    public function recordEvent(array $event): int
    {
        $event['id'] = count($this->events) + 1;
        $this->events[] = $event;

        return $event['id'];
    }

    public function blockingRfisForActivity(int $activity_id): array
    {
        return $this->blocking_rfis[$activity_id] ?? [];
    }
};

$service = new ActivityActionService($repo);

$started = $service->handle(5, 'start');
esjv4_assert_same(true, $started['ok'], 'Start action succeeds');
esjv4_assert_same(Catalog::STATUS_IN_PROGRESS, $repo->updates[0][1], 'Start action moves activity to in progress');
esjv4_assert_same(EventLog::TASK_STARTED, $repo->events[0]['event_type'], 'Start action records start event');

$paused = $service->handle(5, 'pause_rfi');
esjv4_assert_same(true, $paused['ok'], 'Pause RFI action succeeds');
esjv4_assert_same(Catalog::STATUS_PAUSED_RFI, $repo->updates[1][1], 'Pause RFI action moves activity to paused RFI');
esjv4_assert_same(EventLog::TASK_PAUSED_RFI, $repo->events[1]['event_type'], 'Pause RFI action records pause event');

$resumed = $service->handle(5, 'resume');
esjv4_assert_same(true, $resumed['ok'], 'Resume action succeeds');
esjv4_assert_same(Catalog::STATUS_IN_PROGRESS, $repo->updates[2][1], 'Resume action moves activity back to in progress');
esjv4_assert_same(EventLog::TASK_RESUMED, $repo->events[2]['event_type'], 'Resume action records resume event');

$closed = $service->handle(5, 'close');
esjv4_assert_same(true, $closed['ok'], 'Close action succeeds');
esjv4_assert_same(Catalog::STATUS_CLOSED, $repo->updates[3][1], 'Close action moves activity to closed');
esjv4_assert_same(EventLog::TASK_CLOSED, $repo->events[3]['event_type'], 'Close action records close event');

$unknown = $service->handle(5, 'unknown');
esjv4_assert_same(false, $unknown['ok'], 'Unknown action fails');

$repo->activities[5]['status'] = Catalog::STATUS_IN_PROGRESS;
$repo->blocking_rfis[5] = [['id' => 88]];
$blocked_close = $service->handle(5, 'close');

esjv4_assert_same(false, $blocked_close['ok'], 'Close fails when activity has blocking RFIs');
esjv4_assert_same('No se puede cerrar: hay RFIs bloqueantes abiertos.', $blocked_close['message'], 'Blocked close explains RFI condition');
esjv4_assert_same(Catalog::STATUS_IN_PROGRESS, $repo->activities[5]['status'], 'Blocked close keeps activity in progress');
