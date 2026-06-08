<?php

declare(strict_types=1);

namespace GlpiPlugin\Esjv4;

final class PlanningRepository
{
    public static function requiredTablesByMethod(): array
    {
        return [
            'createProject' => Schema::TABLE_PROJECTS,
            'createProjectFromSap' => Schema::TABLE_PROJECTS,
            'createStage' => Schema::TABLE_STAGES,
            'createBuilding' => Schema::TABLE_BUILDINGS,
            'createPhase' => Schema::TABLE_PHASES,
            'createActivity' => Schema::TABLE_ACTIVITIES,
            'recordEvent' => Schema::TABLE_EVENTS,
            'projectEvents' => Schema::TABLE_EVENTS,
            'findProjectByRawHash' => Schema::TABLE_PROJECTS,
            'findProjectByCode' => Schema::TABLE_PROJECTS,
            'markPlanningCompleted' => Schema::TABLE_PROJECTS,
            'constructionReleaseContext' => Schema::TABLE_STAGES,
            'activateConstructionPhases' => Schema::TABLE_PHASES,
            'projects' => Schema::TABLE_PROJECTS,
            'project' => Schema::TABLE_PROJECTS,
            'buildings' => Schema::TABLE_BUILDINGS,
            'stages' => Schema::TABLE_STAGES,
            'phases' => Schema::TABLE_PHASES,
            'activities' => Schema::TABLE_ACTIVITIES,
            'activity' => Schema::TABLE_ACTIVITIES,
            'updateActivityStatus' => Schema::TABLE_ACTIVITIES,
            'ticketLinks' => Schema::TABLE_TICKET_LINKS,
            'createTicketLink' => Schema::TABLE_TICKET_LINKS,
            'blockingRfisForActivity' => Schema::TABLE_TICKET_LINKS,
            'closeStage' => Schema::TABLE_STAGES,
        ];
    }

    public function createProject(array $project): int
    {
        $DB = $this->db();

        $now = date('Y-m-d H:i:s');
        $glpi_project_id = $this->createGlpiProject($project);

        $DB->insert(Schema::TABLE_PROJECTS, [
            'projects_id' => $glpi_project_id,
            'sap_project_code' => $project['project_code'],
            'quotation_code' => $project['quotation_code'],
            'customer_name' => $project['customer_name'],
            'location' => $project['location'],
            'status' => $project['status'],
            'raw_payload_hash' => $project['raw_payload_hash'] ?? hash('sha256', json_encode($project, JSON_THROW_ON_ERROR)),
            'date_creation' => $now,
            'date_mod' => $now,
        ]);

        return (int) $DB->insertId();
    }

    public function createProjectFromSap(array $project): int
    {
        return $this->createProject([
            'project_code' => $project['project_code'],
            'project_name' => $project['project_name'],
            'customer_name' => $project['customer_name'],
            'quotation_code' => $project['quotation_code'],
            'location' => $project['location'],
            'status' => $project['status'] ?? Catalog::STATUS_PENDING_PLANNING,
            'raw_payload_hash' => $project['raw_hash'] ?? null,
        ]);
    }

    public function createStage(int $project_id, array $stage): int
    {
        $DB = $this->db();

        $now = date('Y-m-d H:i:s');
        $DB->insert(Schema::TABLE_STAGES, [
            'esj_projects_id' => $project_id,
            'esj_phases_id' => 0,
            'stage_key' => $stage['key'],
            'name' => $stage['name'],
            'status' => $stage['status'],
            'date_creation' => $now,
            'date_mod' => $now,
        ]);

        return (int) $DB->insertId();
    }

    public function createBuilding(int $project_id, array $building): int
    {
        $DB = $this->db();

        $now = date('Y-m-d H:i:s');
        $DB->insert(Schema::TABLE_BUILDINGS, [
            'esj_projects_id' => $project_id,
            'esj_phases_id' => 0,
            'name' => $building['name'],
            'client_label' => $building['client_label'] ?? '',
            'date_creation' => $now,
            'date_mod' => $now,
        ]);

        return (int) $DB->insertId();
    }

    public function createPhase(int $project_id, array $phase, int $building_id = 0): int
    {
        $DB = $this->db();

        $now = date('Y-m-d H:i:s');
        $project_task_id = $this->createGlpiProjectTask($project_id, $phase['name']);

        $DB->insert(Schema::TABLE_PHASES, [
            'esj_projects_id' => $project_id,
            'projecttasks_id' => $project_task_id,
            'esj_buildings_id' => $building_id,
            'groups_id' => (int) ($phase['groups_id'] ?? 0),
            'slot' => $phase['slot'],
            'name' => $phase['name'],
            'status' => $phase['status'],
            'is_active' => 0,
            'planned_start' => $this->nullableTimestamp($phase['planned_start'] ?? ''),
            'planned_end' => $this->nullableTimestamp($phase['planned_end'] ?? ''),
            'date_creation' => $now,
            'date_mod' => $now,
        ]);

        return (int) $DB->insertId();
    }

    public function createActivity(int $project_id, int $phase_id, array $activity, int $building_id = 0): int
    {
        $DB = $this->db();

        $now = date('Y-m-d H:i:s');
        $project_task_id = $this->createGlpiProjectTask($project_id, $activity['name']);

        $DB->insert(Schema::TABLE_ACTIVITIES, [
            'projecttasks_id' => $project_task_id,
            'esj_projects_id' => $project_id,
            'esj_phases_id' => $phase_id,
            'esj_buildings_id' => $building_id,
            'esj_stages_id' => 0,
            'product' => $activity['product'] ?? '',
            'name' => $activity['name'],
            'status' => $activity['status'],
            'assigned_users_id' => 0,
            'date_creation' => $now,
            'date_mod' => $now,
        ]);

        return (int) $DB->insertId();
    }

    public function findProjectByRawHash(string $raw_hash): array
    {
        if ($raw_hash === '') {
            return [];
        }

        return $this->projectRow(['raw_payload_hash' => $raw_hash]);
    }

    public function findProjectByCode(string $project_code): array
    {
        if ($project_code === '') {
            return [];
        }

        return $this->projectRow(['sap_project_code' => $project_code]);
    }

    public function markPlanningCompleted(int $project_id): bool
    {
        $DB = $this->db();

        return (bool) $DB->update(
            Schema::TABLE_PROJECTS,
            [
                'status' => Catalog::STATUS_PLANNED,
                'date_mod' => date('Y-m-d H:i:s'),
            ],
            ['id' => $project_id]
        );
    }

    public function recordEvent(array $event): int
    {
        $DB = $this->db();

        $payload = $event['payload'] ?? [];
        $DB->insert(Schema::TABLE_EVENTS, [
            'esj_projects_id' => $event['project_id'] ?? 0,
            'esj_phases_id' => $event['phase_id'] ?? 0,
            'esj_activities_id' => $event['activity_id'] ?? 0,
            'tickets_id' => $event['tickets_id'] ?? 0,
            'event_type' => $event['event_type'],
            'event_at' => $event['event_at'],
            'users_id' => $_SESSION['glpiID'] ?? 0,
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'date_creation' => date('Y-m-d H:i:s'),
        ]);

        return (int) $DB->insertId();
    }

    public function projectEvents(int $project_id): array
    {
        return $this->rows(Schema::TABLE_EVENTS, ['esj_projects_id' => $project_id], ['event_at ASC', 'id ASC']);
    }

    public function constructionReleaseContext(int $project_id): array
    {
        $DB = $this->db();

        $stage_statuses = [];
        $stage_iterator = $DB->request([
            'SELECT' => ['stage_key', 'status'],
            'FROM' => Schema::TABLE_STAGES,
            'WHERE' => [
                'esj_projects_id' => $project_id,
                'stage_key' => Catalog::constructionReleaseRequiredStages(),
            ],
        ]);

        foreach ($stage_iterator as $row) {
            $stage_statuses[(string) $row['stage_key']] = (string) $row['status'];
        }

        $blocking_rfis = [];
        $rfi_iterator = $DB->request([
            'SELECT' => ['tickets_id AS id', 'scope'],
            'FROM' => Schema::TABLE_TICKET_LINKS,
            'WHERE' => [
                'esj_projects_id' => $project_id,
                'impact' => Catalog::DEFAULT_RFI_IMPACT,
                'NOT' => ['status' => ['resolved', 'closed']],
            ],
        ]);

        foreach ($rfi_iterator as $row) {
            $blocking_rfis[] = [
                'id' => (int) $row['id'],
                'scope' => (string) $row['scope'],
            ];
        }

        return [
            'stage_statuses' => $stage_statuses,
            'blocking_rfis' => $blocking_rfis,
        ];
    }

    public function activateConstructionPhases(int $project_id): int
    {
        $DB = $this->db();

        $DB->update(
            Schema::TABLE_PHASES,
            [
                'status' => Catalog::STATUS_ACTIVE,
                'is_active' => 1,
                'date_mod' => date('Y-m-d H:i:s'),
            ],
            [
                'esj_projects_id' => $project_id,
                'status' => Catalog::STATUS_BLOCKED,
            ]
        );

        return (int) $DB->affectedRows();
    }

    public function projects(): array
    {
        $DB = $this->db();
        $rows = [];
        $iterator = $DB->request([
            'FROM' => Schema::TABLE_PROJECTS,
            'ORDER' => ['id DESC'],
            'LIMIT' => 200,
        ]);

        foreach ($iterator as $row) {
            $row['project_code'] = (string) ($row['sap_project_code'] ?? '');
            $row['project_name'] = $this->glpiProjectName((int) ($row['projects_id'] ?? 0), $row['project_code']);
            $rows[] = $row;
        }

        return $rows;
    }

    public function project(int $project_id): array
    {
        return $this->projectRow(['id' => $project_id]);
    }

    public function buildings(int $project_id): array
    {
        return $this->rows(Schema::TABLE_BUILDINGS, ['esj_projects_id' => $project_id], ['id ASC']);
    }

    public function stages(int $project_id): array
    {
        return $this->rows(Schema::TABLE_STAGES, ['esj_projects_id' => $project_id], ['id ASC']);
    }

    public function phases(int $project_id): array
    {
        return $this->rows(Schema::TABLE_PHASES, ['esj_projects_id' => $project_id], ['slot ASC']);
    }

    public function activities(int $project_id): array
    {
        return $this->rows(Schema::TABLE_ACTIVITIES, ['esj_projects_id' => $project_id], ['id ASC']);
    }

    public function activity(int $activity_id): array
    {
        $DB = $this->db();
        $iterator = $DB->request([
            'FROM' => Schema::TABLE_ACTIVITIES,
            'WHERE' => ['id' => $activity_id],
            'LIMIT' => 1,
        ]);

        foreach ($iterator as $row) {
            return $row;
        }

        return [];
    }

    public function updateActivityStatus(int $activity_id, string $status): bool
    {
        $DB = $this->db();

        return (bool) $DB->update(
            Schema::TABLE_ACTIVITIES,
            [
                'status' => $status,
                'date_mod' => date('Y-m-d H:i:s'),
            ],
            ['id' => $activity_id]
        );
    }

    public function ticketLinks(int $project_id): array
    {
        return $this->rows(Schema::TABLE_TICKET_LINKS, ['esj_projects_id' => $project_id], ['id DESC']);
    }

    public function createTicketLink(array $link): int
    {
        $DB = $this->db();
        $now = date('Y-m-d H:i:s');

        $DB->insert(Schema::TABLE_TICKET_LINKS, [
            'tickets_id' => $link['tickets_id'],
            'esj_projects_id' => $link['project_id'],
            'esj_phases_id' => $link['phase_id'] ?? 0,
            'esj_buildings_id' => $link['building_id'] ?? 0,
            'esj_activities_id' => $link['activity_id'] ?? 0,
            'scope' => $link['scope'],
            'impact' => $link['impact'],
            'status' => $link['status'],
            'date_creation' => $now,
            'date_mod' => $now,
        ]);

        return (int) $DB->insertId();
    }

    public function blockingRfisForActivity(int $activity_id): array
    {
        $DB = $this->db();
        $rows = [];
        $iterator = $DB->request([
            'FROM' => Schema::TABLE_TICKET_LINKS,
            'WHERE' => [
                'esj_activities_id' => $activity_id,
                'impact' => Catalog::DEFAULT_RFI_IMPACT,
                'NOT' => ['status' => ['resolved', 'closed']],
            ],
            'ORDER' => ['id ASC'],
        ]);

        foreach ($iterator as $row) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function closeStage(int $project_id, string $stage_key): bool
    {
        $DB = $this->db();
        if (!in_array($stage_key, Catalog::constructionReleaseRequiredStages(), true)) {
            return false;
        }

        $updated = $DB->update(
            Schema::TABLE_STAGES,
            [
                'status' => Catalog::STATUS_CLOSED,
                'date_mod' => date('Y-m-d H:i:s'),
            ],
            [
                'esj_projects_id' => $project_id,
                'stage_key' => $stage_key,
            ]
        );

        if ($updated) {
            $this->recordEvent([
                'project_id' => $project_id,
                'event_type' => EventLog::STAGE_CLOSED,
                'event_at' => date('Y-m-d H:i:s'),
                'payload' => ['stage_key' => $stage_key],
            ]);
        }

        return (bool) $updated;
    }

    private function createGlpiProject(array $project): int
    {
        if (!class_exists('\Project')) {
            return 0;
        }

        $glpi_project = new \Project();
        $id = $glpi_project->add([
            'name' => $project['project_code'] . ' - ' . $project['project_name'],
            'code' => $project['project_code'],
            'content' => implode("\n", array_filter([
                'Proyecto creado desde Planeacion ESJ V4.',
                'Cliente: ' . $project['customer_name'],
                'Cotizacion: ' . $project['quotation_code'],
                'Ubicacion: ' . $project['location'],
            ])),
            'entities_id' => $_SESSION['glpiactive_entity'] ?? 0,
            'is_recursive' => 0,
            'percent_done' => 0,
        ]);

        return $id ? (int) $id : 0;
    }

    private function rows(string $table, array $where, array $order): array
    {
        $DB = $this->db();
        $rows = [];
        $iterator = $DB->request([
            'FROM' => $table,
            'WHERE' => $where,
            'ORDER' => $order,
        ]);

        foreach ($iterator as $row) {
            $rows[] = $row;
        }

        return $rows;
    }

    private function projectRow(array $where): array
    {
        $DB = $this->db();
        $iterator = $DB->request([
            'FROM' => Schema::TABLE_PROJECTS,
            'WHERE' => $where,
            'LIMIT' => 1,
        ]);

        foreach ($iterator as $row) {
            $row['project_code'] = (string) ($row['sap_project_code'] ?? '');
            $row['project_name'] = $this->glpiProjectName((int) ($row['projects_id'] ?? 0), $row['project_code']);
            $row['raw_hash'] = (string) ($row['raw_payload_hash'] ?? '');

            return $row;
        }

        return [];
    }

    private function glpiProjectName(int $projects_id, string $fallback): string
    {
        $DB = $this->db();
        if ($projects_id <= 0) {
            return $fallback;
        }

        $iterator = $DB->request([
            'SELECT' => ['name'],
            'FROM' => 'glpi_projects',
            'WHERE' => ['id' => $projects_id],
            'LIMIT' => 1,
        ]);

        foreach ($iterator as $row) {
            return (string) $row['name'];
        }

        return $fallback;
    }

    private function createGlpiProjectTask(int $project_id, string $name): int
    {
        if (!class_exists('\ProjectTask')) {
            return 0;
        }

        $projects_id = $this->glpiProjectId($project_id);
        if ($projects_id <= 0) {
            return 0;
        }

        $task = new \ProjectTask();
        $id = $task->add([
            'name' => $name,
            'content' => 'Tarea generada por ESJ V4.',
            'projects_id' => $projects_id,
        ]);

        return $id ? (int) $id : 0;
    }

    private function glpiProjectId(int $project_id): int
    {
        $DB = $this->db();

        $iterator = $DB->request([
            'SELECT' => ['projects_id'],
            'FROM' => Schema::TABLE_PROJECTS,
            'WHERE' => ['id' => $project_id],
            'LIMIT' => 1,
        ]);

        foreach ($iterator as $row) {
            return (int) $row['projects_id'];
        }

        return 0;
    }

    private function nullableTimestamp(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value . (strlen($value) === 10 ? ' 00:00:00' : '');
    }

    private function db(): object
    {
        global $DB;

        if (!isset($DB) || !is_object($DB)) {
            throw new \RuntimeException('GLPI database connection is not available. Use this repository inside GLPI web or console bootstrap.');
        }

        return $DB;
    }
}
