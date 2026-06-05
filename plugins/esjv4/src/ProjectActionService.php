<?php

declare(strict_types=1);

namespace GlpiPlugin\Esjv4;

final class ProjectActionService
{
    public function __construct(
        private object $repository,
        private object $gate_release_service
    ) {
    }

    public function handle(int $project_id, string $action, array $payload): array
    {
        if ($action === 'close_stage') {
            $stage_key = (string) ($payload['stage_key'] ?? '');

            if ($stage_key === '' || !$this->repository->closeStage($project_id, $stage_key)) {
                return [
                    'ok' => false,
                    'message' => 'No se pudo cerrar la etapa.',
                ];
            }

            return [
                'ok' => true,
                'message' => 'Etapa cerrada.',
            ];
        }

        if ($action === 'release_gate') {
            return $this->releaseGate($project_id);
        }

        return [
            'ok' => false,
            'message' => 'Accion no reconocida.',
        ];
    }

    private function releaseGate(int $project_id): array
    {
        $result = $this->gate_release_service->releaseConstruction($project_id);

        if (($result['released'] ?? false) === true) {
            return [
                'ok' => true,
                'message' => 'Gate liberado. Fases activadas: ' . (int) $result['activated_phases'] . '.',
                'details' => $result,
            ];
        }

        $reasons = [];
        if (($result['missing_stages'] ?? []) !== []) {
            $reasons[] = 'etapas pendientes ' . implode(', ', $result['missing_stages']);
        }

        if (($result['blocking_rfi_ids'] ?? []) !== []) {
            $reasons[] = 'RFIs bloqueantes ' . implode(', ', $result['blocking_rfi_ids']);
        }

        return [
            'ok' => false,
            'message' => 'No se puede liberar: ' . implode('; ', $reasons) . '.',
            'details' => $result,
        ];
    }
}
