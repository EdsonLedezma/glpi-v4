<?php

declare(strict_types=1);

use GlpiPlugin\Esjv4\Catalog;
use GlpiPlugin\Esjv4\EventLog;
use GlpiPlugin\Esjv4\PlanningService;

esjv4_assert_true(class_exists(PlanningService::class), 'PlanningService class must exist');

$repo = new class {
    public array $projects = [];
    public array $stages = [];
    public array $phases = [];
    public array $buildings = [];
    public array $activities = [];
    public array $events = [];

    public function project(int $project_id): array
    {
        return [
            'id' => $project_id,
            'project_code' => 'EAA260102',
            'project_name' => 'MBA San Rafael',
            'customer_name' => 'Punto Estructural',
            'quotation_code' => '0020033984',
            'location' => 'Guadalajara',
            'status' => Catalog::STATUS_PENDING_PLANNING,
        ];
    }

    public function markPlanningCompleted(int $project_id): bool
    {
        $this->projects[] = ['id' => $project_id, 'status' => Catalog::STATUS_PLANNED];
        return true;
    }

    public function createStage(int $project_id, array $stage): int
    {
        $id = count($this->stages) + 1;
        $stage['id'] = $id;
        $stage['project_id'] = $project_id;
        $this->stages[] = $stage;
        return $id;
    }

    public function createBuilding(int $project_id, array $building): int
    {
        $id = count($this->buildings) + 1;
        $building['id'] = $id;
        $building['project_id'] = $project_id;
        $this->buildings[] = $building;
        return $id;
    }

    public function createPhase(int $project_id, array $phase, int $building_id = 0): int
    {
        $id = count($this->phases) + 1;
        $phase['id'] = $id;
        $phase['project_id'] = $project_id;
        $phase['building_id'] = $building_id;
        $this->phases[] = $phase;
        return $id;
    }

    public function createActivity(int $project_id, int $phase_id, array $activity, int $building_id = 0): int
    {
        $id = count($this->activities) + 1;
        $activity['id'] = $id;
        $activity['project_id'] = $project_id;
        $activity['phase_id'] = $phase_id;
        $activity['building_id'] = $building_id;
        $this->activities[] = $activity;
        return $id;
    }

    public function recordEvent(array $event): int
    {
        $id = count($this->events) + 1;
        $event['id'] = $id;
        $this->events[] = $event;
        return $id;
    }
};

$service = new PlanningService($repo);
$result = $service->completePlanning(7, [
    'project_code' => 'EAA260102',
    'project_name' => 'MBA San Rafael',
    'customer_name' => 'Punto Estructural',
    'quotation_code' => '0020033984',
    'location' => 'Guadalajara',
    'buildings' => [
        ['key' => 'b1', 'name' => 'Nave A', 'client_label' => 'Edificio A1'],
    ],
    'phase_slots' => [
        ['slot' => 1, 'name' => 'Fase 01', 'enabled' => '1', 'building_key' => 'b1', 'activity_package_key' => 'core_engineering'],
        ['slot' => 2, 'name' => 'Fase 02', 'enabled' => '1', 'building_key' => 'b1', 'activity_package_key' => 'none'],
    ],
]);

esjv4_assert_same(7, $result['project_id'], 'Planning service returns existing project id');
esjv4_assert_same(Catalog::STATUS_PLANNED, $repo->projects[0]['status'], 'Project is marked planned after planning completion');
esjv4_assert_same(3, count($repo->stages), 'Three gate stages are persisted');
esjv4_assert_same('planning', $repo->stages[0]['key'], 'First gate stage is planning');
esjv4_assert_same(Catalog::STATUS_ACTIVE, $repo->stages[0]['status'], 'Planning gate stage starts active');
esjv4_assert_same(1, count($repo->buildings), 'Buildings are persisted during planning');
esjv4_assert_same('Nave A', $repo->buildings[0]['name'], 'Building name is persisted');
esjv4_assert_same(2, count($repo->phases), 'Selected phases are persisted');
esjv4_assert_same(1, $repo->phases[0]['building_id'], 'Phase is linked to building');
esjv4_assert_same(Catalog::STATUS_BLOCKED, $repo->phases[0]['status'], 'Construction phase starts blocked');
esjv4_assert_true(count($repo->activities) > 0, 'Template activities are persisted');
esjv4_assert_same(1, $repo->activities[0]['building_id'], 'Activity is linked to building');
esjv4_assert_true(
    in_array('Modelo de conexiones', array_column($repo->activities, 'name'), true),
    'Core template activity is persisted'
);
esjv4_assert_same(EventLog::PLANNING_COMPLETED, $repo->events[0]['event_type'], 'Planning completion event is recorded');

$invalid_errors = $service->completePlanning(7, [
    'project_code' => '',
    'project_name' => '',
    'buildings' => [],
    'phase_slots' => [],
]);

esjv4_assert_same(false, $invalid_errors['ok'], 'Invalid input does not create project');
esjv4_assert_true(isset($invalid_errors['errors']['project_code']), 'Invalid input returns validation errors');
