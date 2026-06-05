<?php

declare(strict_types=1);

namespace GlpiPlugin\Esjv4;

final class GateReleaseService
{
    public function __construct(private object $repository)
    {
    }

    public function releaseConstruction(int $project_id): array
    {
        $context = $this->repository->constructionReleaseContext($project_id);
        $evaluation = GateService::evaluateConstructionRelease($context);

        if (!$evaluation['can_release']) {
            return [
                'released' => false,
                'activated_phases' => 0,
                'missing_stages' => $evaluation['missing_stages'],
                'blocking_rfi_ids' => $evaluation['blocking_rfi_ids'],
            ];
        }

        $activated_phases = $this->repository->activateConstructionPhases($project_id);
        $this->repository->recordEvent([
            'project_id' => $project_id,
            'event_type' => EventLog::GATE_RELEASED,
            'event_at' => date('Y-m-d H:i:s'),
            'payload' => [
                'activated_phases' => $activated_phases,
            ],
        ]);

        return [
            'released' => true,
            'activated_phases' => $activated_phases,
            'missing_stages' => [],
            'blocking_rfi_ids' => [],
        ];
    }
}
