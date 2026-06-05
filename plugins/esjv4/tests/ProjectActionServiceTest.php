<?php

declare(strict_types=1);

use GlpiPlugin\Esjv4\ProjectActionService;

esjv4_assert_true(class_exists(ProjectActionService::class), 'ProjectActionService class must exist');

$repo = new class {
    public array $closed = [];

    public function closeStage(int $project_id, string $stage_key): bool
    {
        $this->closed[] = [$project_id, $stage_key];

        return true;
    }
};

$release_service = new class {
    public array $released = [];
    public array $result = [
        'released' => true,
        'activated_phases' => 4,
        'missing_stages' => [],
        'blocking_rfi_ids' => [],
    ];

    public function releaseConstruction(int $project_id): array
    {
        $this->released[] = $project_id;

        return $this->result;
    }
};

$service = new ProjectActionService($repo, $release_service);
$closed = $service->handle(12, 'close_stage', ['stage_key' => 'planning']);

esjv4_assert_same(true, $closed['ok'], 'Closing a stage returns success when repository updates');
esjv4_assert_same('Etapa cerrada.', $closed['message'], 'Closing a stage returns clear message');
esjv4_assert_same([[12, 'planning']], $repo->closed, 'Closing a stage delegates to repository');

$released = $service->handle(12, 'release_gate', []);

esjv4_assert_same(true, $released['ok'], 'Gate release returns success when service releases phases');
esjv4_assert_same('Gate liberado. Fases activadas: 4.', $released['message'], 'Gate release reports activated phases');
esjv4_assert_same([12], $release_service->released, 'Gate release delegates to release service');

$release_service->result = [
    'released' => false,
    'activated_phases' => 0,
    'missing_stages' => ['connection_modeling'],
    'blocking_rfi_ids' => [77],
];

$blocked = $service->handle(12, 'release_gate', []);

esjv4_assert_same(false, $blocked['ok'], 'Blocked gate release returns failure');
esjv4_assert_same(
    'No se puede liberar: etapas pendientes connection_modeling; RFIs bloqueantes 77.',
    $blocked['message'],
    'Blocked gate release explains pending conditions'
);

$unknown = $service->handle(12, 'unknown', []);

esjv4_assert_same(false, $unknown['ok'], 'Unknown action returns failure');
esjv4_assert_same('Accion no reconocida.', $unknown['message'], 'Unknown action returns clear message');
