<?php

declare(strict_types=1);

use GlpiPlugin\Esjv4\Catalog;
use GlpiPlugin\Esjv4\PlanningService;

esjv4_assert_true(class_exists(PlanningService::class), 'PlanningService class must exist');

$repo = new class {
    public array $projects = [];
    public array $stages = [];
    public array $phases = [];
    public array $activities = [];
    public array $events = [];

    public function createProject(array $project): int
    {
        $id = count($this->projects) + 1;
        $project['id'] = $id;
        $this->projects[] = $project;
        return $id;
    }

    public function createStage(int $project_id, array $stage): int
    {
        $id = count($this->stages) + 1;
        $stage['id'] = $id;
        $stage['project_id'] = $project_id;
        $this->stages[] = $stage;
        return $id;
    }

    public function createPhase(int $project_id, array $phase): int
    {
        $id = count($this->phases) + 1;
        $phase['id'] = $id;
        $phase['project_id'] = $project_id;
        $this->phases[] = $phase;
        return $id;
    }

    public function createActivity(int $project_id, int $phase_id, array $activity): int
    {
        $id = count($this->activities) + 1;
        $activity['id'] = $id;
        $activity['project_id'] = $project_id;
        $activity['phase_id'] = $phase_id;
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
$result = $service->createProjectFromPlanning([
    'project_code' => 'EAA260102',
    'project_name' => 'MBA San Rafael',
    'customer_name' => 'Punto Estructural',
    'quotation_code' => '0020033984',
    'location' => 'Guadalajara',
    'phase_slots' => [
        ['slot' => 1, 'name' => 'Fase 01', 'enabled' => '1', 'activity_template_keys' => ['core_engineering']],
        ['slot' => 2, 'name' => 'Fase 02', 'enabled' => '1', 'activity_template_keys' => []],
    ],
]);

esjv4_assert_same(1, $result['project_id'], 'Planning service returns project id');
esjv4_assert_same('EAA260102', $repo->projects[0]['project_code'], 'Project code is persisted');
esjv4_assert_same(Catalog::STATUS_PLANNED, $repo->projects[0]['status'], 'Project starts planned');
esjv4_assert_same(3, count($repo->stages), 'Three gate stages are persisted');
esjv4_assert_same('planning', $repo->stages[0]['key'], 'First gate stage is planning');
esjv4_assert_same(Catalog::STATUS_ACTIVE, $repo->stages[0]['status'], 'Planning gate stage starts active');
esjv4_assert_same(2, count($repo->phases), 'Selected phases are persisted');
esjv4_assert_same(Catalog::STATUS_BLOCKED, $repo->phases[0]['status'], 'Construction phase starts blocked');
esjv4_assert_true(count($repo->activities) > 0, 'Template activities are persisted');
esjv4_assert_true(
    in_array('Modelo de conexiones', array_column($repo->activities, 'name'), true),
    'Core template activity is persisted'
);
esjv4_assert_same('project_created', $repo->events[0]['event_type'], 'Creation event is recorded');

$invalid_errors = $service->createProjectFromPlanning([
    'project_code' => '',
    'project_name' => '',
    'phase_slots' => [],
]);

esjv4_assert_same(false, $invalid_errors['ok'], 'Invalid input does not create project');
esjv4_assert_true(isset($invalid_errors['errors']['project_code']), 'Invalid input returns validation errors');
