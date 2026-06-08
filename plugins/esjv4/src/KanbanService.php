<?php

declare(strict_types=1);

namespace GlpiPlugin\Esjv4;

final class KanbanService
{
    public function __construct(private object $repository)
    {
    }

    public function projectBoard(int $project_id): array
    {
        $buildings = $this->indexBuildings($this->repository->buildings($project_id));
        $phases = $this->indexPhases($this->repository->phases($project_id));
        $columns = $this->emptyColumns();

        foreach ($this->repository->activities($project_id) as $activity) {
            $status = (string) ($activity['status'] ?? Catalog::STATUS_PLANNED);
            if (!isset($columns[$status])) {
                $status = 'other';
            }

            $phase_id = (int) ($activity['esj_phases_id'] ?? 0);
            $building_id = (int) ($activity['esj_buildings_id'] ?? 0);

            $columns[$status]['cards'][] = [
                'id' => (int) $activity['id'],
                'name' => (string) $activity['name'],
                'status' => (string) $activity['status'],
                'phase_name' => $phases[$phase_id]['name'] ?? 'Sin fase',
                'building_name' => $buildings[$building_id] ?? '-',
                'product' => (string) ($activity['product'] ?? ''),
                'assigned_users_id' => (int) ($activity['assigned_users_id'] ?? 0),
            ];
        }

        return [
            'project' => $this->repository->project($project_id),
            'columns' => $columns,
        ];
    }

    private function emptyColumns(): array
    {
        return [
            Catalog::STATUS_BLOCKED => ['label' => 'Bloqueadas', 'cards' => []],
            Catalog::STATUS_ACTIVE => ['label' => 'Activas', 'cards' => []],
            Catalog::STATUS_IN_PROGRESS => ['label' => 'En proceso', 'cards' => []],
            Catalog::STATUS_PAUSED_RFI => ['label' => 'Pausa RFI', 'cards' => []],
            Catalog::STATUS_PAUSED_RESTRICTION => ['label' => 'Pausa restriccion', 'cards' => []],
            Catalog::STATUS_CLOSED => ['label' => 'Cerradas', 'cards' => []],
            'other' => ['label' => 'Otros', 'cards' => []],
        ];
    }

    private function indexBuildings(array $buildings): array
    {
        $indexed = [];

        foreach ($buildings as $building) {
            $name = (string) ($building['name'] ?? '');
            $client_label = (string) ($building['client_label'] ?? '');
            $indexed[(int) $building['id']] = $client_label !== '' ? $name . ' / ' . $client_label : $name;
        }

        return $indexed;
    }

    private function indexPhases(array $phases): array
    {
        $indexed = [];

        foreach ($phases as $phase) {
            $indexed[(int) $phase['id']] = $phase;
        }

        return $indexed;
    }
}
