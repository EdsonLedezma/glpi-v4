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
                'building_key' => trim((string) ($slot['building_key'] ?? '')),
                'groups_id' => max(0, (int) ($slot['groups_id'] ?? 0)),
                'status' => Catalog::STATUS_BLOCKED,
                'activities' => self::buildActivities($slot['activity_package_key'] ?? 'none', $slot['stage_tasks'] ?? []),
            ];
        }

        return $phases;
    }

    private static function buildActivities(string $package_key, array $stage_tasks): array
    {
        $templates = Catalog::defaultActivityTemplates();
        $packages = Catalog::phaseActivityPackages();
        $template_keys = $packages[$package_key]['template_keys'] ?? [];
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
                    'stage_key' => '',
                ];
            }
        }

        foreach ($stage_tasks as $stage_task) {
            $stage_key = trim((string) ($stage_task['stage_key'] ?? ''));
            foreach (($stage_task['tasks'] ?? []) as $task_name) {
                $task_name = trim((string) $task_name);
                if ($stage_key === '' || $task_name === '') {
                    continue;
                }

                $activities[] = [
                    'name' => $task_name,
                    'status' => Catalog::STATUS_BLOCKED,
                    'source_template' => 'custom_stage_task',
                    'stage_key' => $stage_key,
                ];
            }
        }

        return $activities;
    }
}
