<?php

declare(strict_types=1);

namespace GlpiPlugin\Esjv4;

final class TimeReportService
{
    public function __construct(private object $repository)
    {
    }

    public function projectActivityTimes(int $project_id): array
    {
        $activities = [];

        foreach ($this->eventsByActivity($project_id) as $activity_id => $events) {
            $activities[$activity_id] = $this->activityTimes($events);
        }

        return [
            'activities' => $activities,
            'totals' => $this->totals($activities),
        ];
    }

    private function eventsByActivity(int $project_id): array
    {
        $grouped = [];

        foreach ($this->repository->projectEvents($project_id) as $event) {
            $activity_id = (int) ($event['esj_activities_id'] ?? 0);
            if ($activity_id <= 0) {
                continue;
            }

            $grouped[$activity_id][] = $event;
        }

        foreach ($grouped as &$events) {
            usort($events, static fn(array $a, array $b): int => strcmp((string) $a['event_at'], (string) $b['event_at']));
        }

        return $grouped;
    }

    private function activityTimes(array $events): array
    {
        $work_seconds = 0;
        $pause_rfi_seconds = 0;
        $work_started_at = null;
        $pause_started_at = null;

        foreach ($events as $event) {
            $type = (string) $event['event_type'];
            $at = (string) $event['event_at'];

            if ($type === EventLog::TASK_STARTED || $type === EventLog::TASK_RESUMED) {
                if ($pause_started_at !== null) {
                    $pause_rfi_seconds += EventLog::durationSeconds($pause_started_at, $at);
                    $pause_started_at = null;
                }
                $work_started_at = $at;
                continue;
            }

            if ($type === EventLog::TASK_PAUSED_RFI) {
                if ($work_started_at !== null) {
                    $work_seconds += EventLog::durationSeconds($work_started_at, $at);
                    $work_started_at = null;
                }
                $pause_started_at = $at;
                continue;
            }

            if ($type === EventLog::TASK_CLOSED && $work_started_at !== null) {
                $work_seconds += EventLog::durationSeconds($work_started_at, $at);
                $work_started_at = null;
            }
        }

        return [
            'work_seconds' => $work_seconds,
            'pause_rfi_seconds' => $pause_rfi_seconds,
        ];
    }

    private function totals(array $activities): array
    {
        $totals = [
            'work_seconds' => 0,
            'pause_rfi_seconds' => 0,
        ];

        foreach ($activities as $activity) {
            $totals['work_seconds'] += (int) $activity['work_seconds'];
            $totals['pause_rfi_seconds'] += (int) $activity['pause_rfi_seconds'];
        }

        return $totals;
    }
}
