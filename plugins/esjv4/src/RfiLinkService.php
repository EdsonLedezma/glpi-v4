<?php

declare(strict_types=1);

namespace GlpiPlugin\Esjv4;

final class RfiLinkService
{
    public function __construct(private object $repository)
    {
    }

    public function linkExistingTicket(int $project_id, array $input): array
    {
        $tickets_id = (int) ($input['tickets_id'] ?? 0);
        $scope = trim((string) ($input['scope'] ?? ''));
        $impact = trim((string) ($input['impact'] ?? ''));
        $errors = [];

        if ($tickets_id <= 0) {
            $errors['tickets_id'] = 'El ID del ticket es obligatorio.';
        }

        if (!in_array($scope, Catalog::rfiScopes(), true)) {
            $errors['scope'] = 'Selecciona un alcance valido.';
        }

        if (!in_array($impact, Catalog::rfiImpacts(), true)) {
            $errors['impact'] = 'Selecciona un impacto valido.';
        }

        if ($errors !== []) {
            return [
                'ok' => false,
                'errors' => $errors,
            ];
        }

        $link_id = $this->repository->createTicketLink([
            'tickets_id' => $tickets_id,
            'project_id' => $project_id,
            'phase_id' => (int) ($input['phase_id'] ?? 0),
            'building_id' => (int) ($input['building_id'] ?? 0),
            'activity_id' => (int) ($input['activity_id'] ?? 0),
            'scope' => $scope,
            'impact' => $impact,
            'status' => 'open',
        ]);

        return [
            'ok' => true,
            'ticket_link_id' => $link_id,
            'message' => 'RFI vinculado al proyecto.',
        ];
    }
}
