<?php

declare(strict_types=1);

namespace GlpiPlugin\Esjv4;

final class PlanningService
{
    public function __construct(private object $repository)
    {
    }

    public function completePlanning(int $project_id, array $raw_input): array
    {
        $project = $this->repository->project($project_id);
        if ($project === []) {
            return [
                'ok' => false,
                'errors' => ['project_id' => 'Proyecto SAP no encontrado.'],
            ];
        }

        $input = PlanningInput::normalize(array_merge($project, $raw_input));
        $errors = PlanningInput::validate($input);

        if ($errors !== []) {
            return [
                'ok' => false,
                'errors' => $errors,
            ];
        }

        $plan = ProjectPlanBuilder::buildInitialPlan($input);
        $building_ids = [];

        foreach ($input['buildings'] as $building) {
            $building_ids[$building['key']] = $this->repository->createBuilding($project_id, $building);
        }

        foreach ($plan['gate_stages'] as $stage) {
            $this->repository->createStage($project_id, $stage);
        }

        foreach ($plan['phases'] as $phase) {
            $building_id = $building_ids[$phase['building_key']] ?? 0;
            $phase_id = $this->repository->createPhase($project_id, $phase, $building_id);

            foreach ($phase['activities'] as $activity) {
                $this->repository->createActivity($project_id, $phase_id, $activity, $building_id);
            }
        }

        $this->repository->markPlanningCompleted($project_id);
        $this->repository->recordEvent([
            'project_id' => $project_id,
            'event_type' => EventLog::PLANNING_COMPLETED,
            'event_at' => date('Y-m-d H:i:s'),
            'payload' => [
                'buildings' => count($input['buildings']),
                'phases' => count($plan['phases']),
            ],
        ]);

        return [
            'ok' => true,
            'project_id' => $project_id,
        ];
    }

    public function createProjectFromPlanning(array $raw_input): array
    {
        $input = PlanningInput::normalize($raw_input);
        $errors = PlanningInput::validate($input);

        if ($errors !== []) {
            return [
                'ok' => false,
                'errors' => $errors,
            ];
        }

        $plan = ProjectPlanBuilder::buildInitialPlan($input);
        $project_id = $this->repository->createProject([
            'project_code' => $input['project_code'],
            'project_name' => $input['project_name'],
            'customer_name' => $input['customer_name'],
            'quotation_code' => $input['quotation_code'],
            'location' => $input['location'],
            'status' => Catalog::STATUS_PLANNED,
        ]);

        foreach ($plan['gate_stages'] as $stage) {
            $this->repository->createStage($project_id, $stage);
        }

        foreach ($plan['phases'] as $phase) {
            $phase_id = $this->repository->createPhase($project_id, $phase);

            foreach ($phase['activities'] as $activity) {
                $this->repository->createActivity($project_id, $phase_id, $activity);
            }
        }

        $this->repository->recordEvent([
            'project_id' => $project_id,
            'event_type' => EventLog::PROJECT_CREATED,
            'event_at' => date('Y-m-d H:i:s'),
            'payload' => [
                'project_code' => $input['project_code'],
                'project_name' => $input['project_name'],
            ],
        ]);

        return [
            'ok' => true,
            'project_id' => $project_id,
        ];
    }
}
