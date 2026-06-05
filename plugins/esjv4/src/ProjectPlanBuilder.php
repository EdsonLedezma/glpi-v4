<?php

declare(strict_types=1);

namespace GlpiPlugin\Esjv4;

final class ProjectPlanBuilder
{
    public static function buildInitialPlan(array $input): array
    {
        return [
            'gate_stages' => self::buildGateStages(),
            'phases' => self::buildPhases($input['phase_slots'] ?? []),
        ];
    }

    private static function buildGateStages(): array
    {
        $stages = Catalog::stages();
        $gate_stages = [];

        foreach (Catalog::constructionReleaseRequiredStages() as $index => $stage_key) {
            $gate_stages[] = [
                'key' => $stage_key,
                'name' => $stages[$stage_key],
                'status' => $index === 0 ? Catalog::STATUS_ACTIVE : Catalog::STATUS_BLOCKED,
            ];
        }

        return $gate_stages;
    }

    private static function buildPhases(array $phase_slots): array
    {
        $phases = [];

        foreach ($phase_slots as $slot) {
            $slot_number = (int) ($slot['slot'] ?? 0);
            $name = trim((string) ($slot['name'] ?? ''));

            if ($slot_number <= 0 || $name === '') {
                continue;
            }

            $phases[] = [
                'slot' => $slot_number,
                'name' => $name,
                'status' => Catalog::STATUS_BLOCKED,
                'activities' => self::buildActivities($slot['activity_template_keys'] ?? []),
            ];
        }

        return $phases;
    }

    private static function buildActivities(array $template_keys): array
    {
        $templates = Catalog::defaultActivityTemplates();
        $activities = [];
        $seen = [];

        foreach ($template_keys as $template_key) {
            if (!isset($templates[$template_key])) {
                continue;
            }

            foreach ($templates[$template_key]['activities'] as $activity_name) {
                if (isset($seen[$activity_name])) {
                    continue;
                }

                $seen[$activity_name] = true;
                $activities[] = [
                    'name' => $activity_name,
                    'status' => Catalog::STATUS_BLOCKED,
                    'source_template' => $template_key,
                ];
            }
        }

        return $activities;
    }
}
