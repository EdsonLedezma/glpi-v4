<?php

declare(strict_types=1);

use GlpiPlugin\Esjv4\EventLog;

esjv4_assert_true(class_exists(EventLog::class), 'EventLog class must exist');
esjv4_assert_same('stage_closed', EventLog::STAGE_CLOSED, 'Stage closures must have their own event type');

$seconds = EventLog::durationSeconds('2026-06-05 10:00:00', '2026-06-06 10:30:00');
esjv4_assert_same(88200, $seconds, 'Duration must use raw 24/7 calendar seconds');

$sum = EventLog::sumIntervals([
    ['start' => '2026-06-05 10:00:00', 'end' => '2026-06-05 11:00:00'],
    ['start' => '2026-06-07 23:00:00', 'end' => '2026-06-08 01:30:00'],
]);
esjv4_assert_same(12600, $sum, 'Interval sum must include nights and weekends');

esjv4_assert_same(0, EventLog::durationSeconds('2026-06-05 10:00:00', '2026-06-05 09:59:59'), 'Negative durations clamp to zero');
