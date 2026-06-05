<?php

declare(strict_types=1);

namespace GlpiPlugin\Esjv4;

final class PlanningInput
{
    public static function normalize(array $input): array
    {
        return [
            'project_code' => self::text($input['project_code'] ?? ''),
            'project_name' => self::text($input['project_name'] ?? ''),
            'customer_name' => self::text($input['customer_name'] ?? ''),
            'quotation_code' => self::text($input['quotation_code'] ?? ''),
            'location' => self::text($input['location'] ?? ''),
            'phase_slots' => self::phaseSlots($input['phase_slots'] ?? []),
        ];
    }

    public static function validate(array $input): array
    {
        $errors = [];

        if (($input['project_code'] ?? '') === '') {
            $errors['project_code'] = 'El codigo de proyecto es obligatorio.';
        }

        if (($input['project_name'] ?? '') === '') {
            $errors['project_name'] = 'El nombre del proyecto es obligatorio.';
        }

        if (($input['phase_slots'] ?? []) === []) {
            $errors['phase_slots'] = 'Selecciona al menos una fase.';
        }

        return $errors;
    }

    private static function phaseSlots(array $phase_slots): array
    {
        $normalized = [];

        foreach ($phase_slots as $phase_slot) {
            if (!self::enabled($phase_slot['enabled'] ?? false)) {
                continue;
            }

            $slot = (int) ($phase_slot['slot'] ?? 0);
            $name = self::text($phase_slot['name'] ?? '');

            if ($slot <= 0 || $name === '') {
                continue;
            }

            $normalized[] = [
                'slot' => $slot,
                'name' => $name,
                'activity_template_keys' => self::templateKeys($phase_slot['activity_template_keys'] ?? []),
                'planned_start' => self::text($phase_slot['planned_start'] ?? ''),
                'planned_end' => self::text($phase_slot['planned_end'] ?? ''),
            ];
        }

        usort($normalized, static fn(array $a, array $b): int => $a['slot'] <=> $b['slot']);

        return $normalized;
    }

    private static function templateKeys(mixed $value): array
    {
        if (!is_array($value)) {
            $value = [$value];
        }

        $allowed = array_keys(Catalog::defaultActivityTemplates());
        $keys = [];

        foreach ($value as $key) {
            $key = self::text($key);

            if ($key !== '' && in_array($key, $allowed, true) && !in_array($key, $keys, true)) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    private static function enabled(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'on', 'yes', 'true'], true);
    }

    private static function text(mixed $value): string
    {
        return trim((string) $value);
    }
}
