<?php

declare(strict_types=1);

use GlpiPlugin\Esjv4\EventLog;
use GlpiPlugin\Esjv4\TimeReportService;

esjv4_assert_true(class_exists(TimeReportService::class), 'TimeReportService class must exist');

$repo = new class {
    public function projectEvents(int $project_id): array
    {
        return [
            ['esj_activities_id' => 5, 'event_type' => EventLog::TASK_STARTED, 'event_at' => '2026-06-08 10:00:00'],
            ['esj_activities_id' => 5, 'event_type' => EventLog::TASK_PAUSED_RFI, 'event_at' => '2026-06-08 11:00:00'],
            ['esj_activities_id' => 5, 'event_type' => EventLog::TASK_RESUMED, 'event_at' => '2026-06-08 12:00:00'],
            ['esj_activities_id' => 5, 'event_type' => EventLog::TASK_CLOSED, 'event_at' => '2026-06-08 13:30:00'],
            ['esj_activities_id' => 6, 'event_type' => EventLog::TASK_STARTED, 'event_at' => '2026-06-08 09:00:00'],
            ['esj_activities_id' => 6, 'event_type' => EventLog::TASK_CLOSED, 'event_at' => '2026-06-08 09:45:00'],
        ];
    }
};

$report = (new TimeReportService($repo))->projectActivityTimes(9);

esjv4_assert_same(9000, $report['activities'][5]['work_seconds'], 'Activity work time sums start/resume to pause/close intervals');
esjv4_assert_same(3600, $report['activities'][5]['pause_rfi_seconds'], 'Activity RFI pause time sums pause to resume interval');
esjv4_assert_same(2700, $report['activities'][6]['work_seconds'], 'Single uninterrupted activity reports work seconds');
esjv4_assert_same(11700, $report['totals']['work_seconds'], 'Project total work seconds sums activities');
esjv4_assert_same(3600, $report['totals']['pause_rfi_seconds'], 'Project total pause seconds sums activities');
