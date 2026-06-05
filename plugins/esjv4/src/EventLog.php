<?php

declare(strict_types=1);

namespace GlpiPlugin\Esjv4;

use DateTimeImmutable;
use DateTimeZone;

final class EventLog
{
    public const PROJECT_CREATED = 'project_created';
    public const SAP_MAIL_RECEIVED = 'sap_mail_received';
    public const PLANNING_COMPLETED = 'planning_completed';
    public const PHASE_DEFINED = 'phase_defined';
    public const PHASE_ACTIVATED = 'phase_activated';
    public const TASK_CREATED = 'task_created';
    public const TASK_ASSIGNED = 'task_assigned';
    public const TASK_PAUSED_RFI = 'task_paused_rfi';
    public const RFI_OPENED = 'rfi_opened';
    public const RFI_CLOSED = 'rfi_closed';
    public const TASK_RESUMED = 'task_resumed';
    public const TASK_CLOSED = 'task_closed';
    public const PHASE_CLOSED = 'phase_closed';
    public const STAGE_CLOSED = 'stage_closed';
    public const GATE_RELEASED = 'gate_released';

    public static function durationSeconds(string $start, string $end): int
    {
        $timezone = new DateTimeZone(date_default_timezone_get());
        $start_at = new DateTimeImmutable($start, $timezone);
        $end_at = new DateTimeImmutable($end, $timezone);
        $seconds = $end_at->getTimestamp() - $start_at->getTimestamp();

        return max(0, $seconds);
    }

    public static function sumIntervals(array $intervals): int
    {
        $total = 0;

        foreach ($intervals as $interval) {
            $total += self::durationSeconds((string) $interval['start'], (string) $interval['end']);
        }

        return $total;
    }
}
