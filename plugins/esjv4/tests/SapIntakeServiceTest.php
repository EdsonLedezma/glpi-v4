<?php

declare(strict_types=1);

use GlpiPlugin\Esjv4\Catalog;
use GlpiPlugin\Esjv4\EventLog;
use GlpiPlugin\Esjv4\SapIntakeService;

esjv4_assert_true(class_exists(SapIntakeService::class), 'SapIntakeService class must exist');

$repo = new class {
    public array $projects = [];
    public array $events = [];

    public function findProjectByRawHash(string $raw_hash): array
    {
        foreach ($this->projects as $project) {
            if (($project['raw_hash'] ?? '') === $raw_hash) {
                return $project;
            }
        }

        return [];
    }

    public function findProjectByCode(string $project_code): array
    {
        foreach ($this->projects as $project) {
            if (($project['project_code'] ?? '') === $project_code) {
                return $project;
            }
        }

        return [];
    }

    public function createProjectFromSap(array $project): int
    {
        $id = count($this->projects) + 1;
        $project['id'] = $id;
        $this->projects[] = $project;

        return $id;
    }

    public function recordEvent(array $event): int
    {
        $event['id'] = count($this->events) + 1;
        $this->events[] = $event;

        return $event['id'];
    }
};

$mail = <<<MAIL
OBSERVACIONES:
EAA2506 MBA SAN RAFAEL

Nombre: MBA SAN RAFAEL
Cotizacion: 0020033984
Cliente: 5000004221 PUNTO ESTRUCTURAL
Ubicacion: GUADALAJARA, Jalisco, Mexico
MAIL;

$service = new SapIntakeService($repo);
$created = $service->receiveMail($mail);

esjv4_assert_same(true, $created['ok'], 'SAP intake accepts valid SAP mail');
esjv4_assert_same(true, $created['created'], 'First SAP mail creates a pending project');
esjv4_assert_same(1, $created['project_id'], 'SAP intake returns project id');
esjv4_assert_same(Catalog::STATUS_PENDING_PLANNING, $repo->projects[0]['status'], 'SAP project starts pending planning');
esjv4_assert_same('EAA2506', $repo->projects[0]['project_code'], 'SAP intake persists project code');
esjv4_assert_same(EventLog::SAP_MAIL_RECEIVED, $repo->events[0]['event_type'], 'SAP intake records mail received event');

$duplicate = $service->receiveMail($mail);

esjv4_assert_same(true, $duplicate['ok'], 'Duplicate SAP mail still returns ok');
esjv4_assert_same(false, $duplicate['created'], 'Duplicate SAP mail does not create another project');
esjv4_assert_same(1, count($repo->projects), 'Duplicate SAP mail is idempotent');

$invalid = $service->receiveMail('correo sin estructura');

esjv4_assert_same(false, $invalid['ok'], 'Invalid SAP mail is rejected');
