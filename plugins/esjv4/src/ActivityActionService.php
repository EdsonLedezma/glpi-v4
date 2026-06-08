<?php

declare(strict_types=1);

namespace GlpiPlugin\Esjv4;

final class ActivityActionService
{
    private const ACTIONS = [
        'start' => [Catalog::STATUS_IN_PROGRESS, EventLog::TASK_STARTED, 'Actividad iniciada.'],
        'pause_rfi' => [Catalog::STATUS_PAUSED_RFI, EventLog::TASK_PAUSED_RFI, 'Actividad pausada por RFI.'],
        'resume' => [Catalog::STATUS_IN_PROGRESS, EventLog::TASK_RESUMED, 'Actividad reanudada.'],
        'close' => [Catalog::STATUS_CLOSED, EventLog::TASK_CLOSED, 'Actividad cerrada.'],
    ];

    public function __construct(private object $repository)
    {
    }

    public function handle(int $activity_id, string $action): array
    {
        if (!isset(self::ACTIONS[$action])) {
            return [
                'ok' => false,
                'message' => 'Accion de actividad no reconocida.',
            ];
        }

        $activity = $this->repository->activity($activity_id);
        if ($activity === []) {
            return [
                'ok' => false,
                'message' => 'Actividad no encontrada.',
            ];
        }

        [$status, $event_type, $message] = self::ACTIONS[$action];
        if ($action === 'close' && $this->blockingRfis($activity_id) !== []) {
            return [
                'ok' => false,
                'message' => 'No se puede cerrar: hay RFIs bloqueantes abiertos.',
            ];
        }

        if (!$this->repository->updateActivityStatus($activity_id, $status)) {
            return [
                'ok' => false,
                'message' => 'No se pudo actualizar la actividad.',
            ];
        }

        $this->repository->recordEvent([
            'project_id' => (int) ($activity['esj_projects_id'] ?? 0),
            'phase_id' => (int) ($activity['esj_phases_id'] ?? 0),
            'activity_id' => $activity_id,
            'event_type' => $event_type,
            'event_at' => date('Y-m-d H:i:s'),
            'payload' => [
                'from_status' => (string) ($activity['status'] ?? ''),
                'to_status' => $status,
            ],
        ]);

        return [
            'ok' => true,
            'message' => $message,
        ];
    }

    private function blockingRfis(int $activity_id): array
    {
        if (!method_exists($this->repository, 'blockingRfisForActivity')) {
            return [];
        }

        return $this->repository->blockingRfisForActivity($activity_id);
    }
}
