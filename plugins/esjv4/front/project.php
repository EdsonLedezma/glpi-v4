<?php

declare(strict_types=1);

use GlpiPlugin\Esjv4\Catalog;
use GlpiPlugin\Esjv4\GateReleaseService;
use GlpiPlugin\Esjv4\PlanningRepository;
use GlpiPlugin\Esjv4\Plugin;
use GlpiPlugin\Esjv4\ProjectActionService;
use GlpiPlugin\Esjv4\ProjectOverviewService;

require_once dirname(__DIR__, 3) . '/inc/includes.php';
require_once dirname(__DIR__) . '/setup.php';

Session::checkCentralAccess();

$repository = new PlanningRepository();
$project_id = (int) ($_POST['project_id'] ?? $_GET['id'] ?? 0);

if ($project_id <= 0) {
    Session::addMessageAfterRedirect('Proyecto ESJ V4 no encontrado.', true, ERROR);
    Html::redirect('/plugins/esjv4/front/projects.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_service = new ProjectActionService($repository, new GateReleaseService($repository));
    $result = $action_service->handle($project_id, (string) ($_POST['action'] ?? ''), $_POST);
    Session::addMessageAfterRedirect($result['message'], true, ($result['ok'] ?? false) ? INFO : ERROR);
    Html::redirect('/plugins/esjv4/front/project.php?id=' . $project_id);
}

$overview_service = new ProjectOverviewService($repository);
$overview = $overview_service->overview($project_id);
$project = $overview['project'];

if ($project === []) {
    Session::addMessageAfterRedirect('Proyecto ESJ V4 no encontrado.', true, ERROR);
    Html::redirect('/plugins/esjv4/front/projects.php');
}

$phase_names = [];
foreach ($overview['phases'] as $phase) {
    $phase_names[(int) $phase['id']] = (string) $phase['name'];
}

$building_names = [];
foreach ($overview['buildings'] as $building) {
    $label = (string) ($building['client_label'] ?? '');
    $name = (string) ($building['name'] ?? '');
    $building_names[(int) $building['id']] = $label !== '' ? $name . ' / ' . $label : $name;
}

Html::header('Proyecto ESJ V4', $_SERVER['PHP_SELF'], 'tools', 'esjv4');

echo '<div class="container-fluid">';
echo '<div class="d-flex justify-content-between align-items-start mb-3">';
echo '<div>';
echo '<a class="text-muted" href="/plugins/esjv4/front/projects.php">&larr; Proyectos</a>';
echo '<h1 class="mb-1">' . htmlescape((string) ($project['project_name'] ?? $project['project_code'] ?? 'Proyecto ESJ V4')) . '</h1>';
echo '<p class="text-muted mb-0">';
echo htmlescape((string) ($project['customer_name'] ?? '')) . ' / ';
echo htmlescape((string) ($project['quotation_code'] ?? '')) . ' / ';
echo htmlescape((string) ($project['location'] ?? ''));
echo '</p>';
echo '</div>';
echo '<div class="d-flex gap-2">';
echo '<a class="btn btn-primary" href="/plugins/esjv4/front/kanban.php?project_id=' . $project_id . '">Kanban</a>';
echo '<a class="btn btn-outline-primary" href="/plugins/esjv4/front/rfis.php?project_id=' . $project_id . '">RFIs</a>';
echo '<a class="btn btn-outline-secondary" href="/plugins/esjv4/front/planning.php">Planeacion</a>';
echo '</div>';
echo '</div>';

echo '<div class="row g-3 mb-3">';
metric_card('Avance fases', (string) $overview['phase_progress_percent'] . '%', counts_text($overview['phase_counts']));
metric_card('Avance actividades', (string) $overview['activity_progress_percent'] . '%', counts_text($overview['activity_counts']));
metric_card('Gate construccion', gate_label($overview['gate']), gate_hint($overview['gate']));
echo '</div>';

echo '<div class="card mb-3">';
echo '<div class="card-header d-flex justify-content-between align-items-center">';
echo '<span>Etapas core</span>';
echo '<form method="post" action="/plugins/esjv4/front/project.php" class="m-0">';
echo '<input type="hidden" name="project_id" value="' . $project_id . '">';
echo '<input type="hidden" name="action" value="release_gate">';
echo '<button class="btn btn-sm btn-primary" type="submit">Liberar fases de construccion</button>';
echo '</form>';
echo '</div>';
echo '<div class="card-body p-0">';
echo '<div class="table-responsive">';
echo '<table class="table table-sm align-middle mb-0">';
echo '<thead><tr><th>Etapa</th><th>Estado</th><th>Creada</th><th>Actualizada</th><th></th></tr></thead><tbody>';

foreach ($overview['stages'] as $stage) {
    $stage_key = (string) $stage['stage_key'];
    $status = (string) $stage['status'];
    echo '<tr>';
    echo '<td><strong>' . htmlescape((string) $stage['name']) . '</strong><div class="text-muted small">' . htmlescape($stage_key) . '</div></td>';
    echo '<td>' . status_badge($status) . '</td>';
    echo '<td>' . htmlescape(short_date((string) ($stage['date_creation'] ?? ''))) . '</td>';
    echo '<td>' . htmlescape(short_date((string) ($stage['date_mod'] ?? ''))) . '</td>';
    echo '<td class="text-end">';
    if (in_array($stage_key, Catalog::constructionReleaseRequiredStages(), true) && $status !== Catalog::STATUS_CLOSED) {
        echo '<form method="post" action="/plugins/esjv4/front/project.php" class="d-inline">';
        echo '<input type="hidden" name="project_id" value="' . $project_id . '">';
        echo '<input type="hidden" name="action" value="close_stage">';
        echo '<input type="hidden" name="stage_key" value="' . htmlescape($stage_key) . '">';
        echo '<button class="btn btn-sm btn-outline-success" type="submit">Cerrar etapa</button>';
        echo '</form>';
    }
    echo '</td>';
    echo '</tr>';
}

echo '</tbody></table>';
echo '</div></div></div>';

echo '<div class="card mb-3">';
echo '<div class="card-header">Fases</div>';
echo '<div class="card-body p-0">';
echo '<div class="table-responsive">';
echo '<table class="table table-sm align-middle mb-0">';
echo '<thead><tr><th>Fase</th><th>Nombre</th><th>Nave</th><th>Estado</th><th>Inicio planeado</th><th>Termino limite</th></tr></thead><tbody>';

foreach ($overview['phases'] as $phase) {
    $building_id = (int) ($phase['esj_buildings_id'] ?? 0);
    echo '<tr>';
    echo '<td>Fase ' . sprintf('%02d', (int) $phase['slot']) . '</td>';
    echo '<td>' . htmlescape((string) $phase['name']) . '</td>';
    echo '<td>' . htmlescape($building_names[$building_id] ?? '-') . '</td>';
    echo '<td>' . status_badge((string) $phase['status']) . '</td>';
    echo '<td>' . htmlescape(short_date((string) ($phase['planned_start'] ?? ''))) . '</td>';
    echo '<td>' . htmlescape(short_date((string) ($phase['planned_end'] ?? ''))) . '</td>';
    echo '</tr>';
}

echo '</tbody></table>';
echo '</div></div></div>';

echo '<div class="card mb-3">';
echo '<div class="card-header">Actividades</div>';
echo '<div class="card-body p-0">';
if ($overview['activities'] === []) {
    echo '<div class="p-4 text-muted">Este proyecto aun no tiene actividades precargadas.</div>';
} else {
    echo '<div class="table-responsive">';
    echo '<table class="table table-sm align-middle mb-0">';
    echo '<thead><tr><th>Actividad</th><th>Fase</th><th>Nave</th><th>Producto</th><th>Estado</th><th>Asignado</th></tr></thead><tbody>';

    foreach ($overview['activities'] as $activity) {
        $phase_id = (int) ($activity['esj_phases_id'] ?? 0);
        $building_id = (int) ($activity['esj_buildings_id'] ?? 0);
        echo '<tr>';
        echo '<td>' . htmlescape((string) $activity['name']) . '</td>';
        echo '<td>' . htmlescape($phase_names[$phase_id] ?? 'Sin fase') . '</td>';
        echo '<td>' . htmlescape($building_names[$building_id] ?? '-') . '</td>';
        echo '<td>' . htmlescape((string) ($activity['product'] ?? '')) . '</td>';
        echo '<td>' . status_badge((string) $activity['status']) . '</td>';
        echo '<td>' . ((int) ($activity['assigned_users_id'] ?? 0) > 0 ? (int) $activity['assigned_users_id'] : '-') . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '</div>';
}

echo '</div></div>';
echo '</div>';

Html::footer();

function metric_card(string $label, string $value, string $hint): void
{
    echo '<div class="col-md-4">';
    echo '<div class="card h-100">';
    echo '<div class="card-body">';
    echo '<div class="text-muted small">' . htmlescape($label) . '</div>';
    echo '<div class="display-6">' . htmlescape($value) . '</div>';
    echo '<div class="text-muted">' . htmlescape($hint) . '</div>';
    echo '</div></div></div>';
}

function status_badge(string $status): string
{
    $class = match ($status) {
        Catalog::STATUS_CLOSED, 'done' => 'bg-success',
        Catalog::STATUS_ACTIVE, Catalog::STATUS_IN_PROGRESS => 'bg-primary',
        Catalog::STATUS_BLOCKED => 'bg-warning text-dark',
        Catalog::STATUS_PAUSED_RFI, Catalog::STATUS_PAUSED_RESTRICTION => 'bg-info text-dark',
        default => 'bg-secondary',
    };

    return '<span class="badge ' . $class . '">' . htmlescape($status === '' ? 'sin estado' : $status) . '</span>';
}

function short_date(string $value): string
{
    return $value === '' ? '-' : substr($value, 0, 10);
}

function counts_text(array $counts): string
{
    return 'Activas ' . (int) $counts['active'] .
        ' / Bloqueadas ' . (int) $counts['blocked'] .
        ' / Cerradas ' . (int) $counts['closed'];
}

function gate_label(array $gate): string
{
    return ($gate['can_release'] ?? false) ? 'Listo' : 'Bloqueado';
}

function gate_hint(array $gate): string
{
    if (($gate['can_release'] ?? false) === true) {
        return 'Las fases de construccion pueden activarse.';
    }

    $parts = [];
    if (($gate['missing_stages'] ?? []) !== []) {
        $parts[] = 'Pendientes: ' . implode(', ', $gate['missing_stages']);
    }

    if (($gate['blocking_rfi_ids'] ?? []) !== []) {
        $parts[] = 'RFIs: ' . implode(', ', $gate['blocking_rfi_ids']);
    }

    return $parts === [] ? 'Condiciones pendientes.' : implode(' / ', $parts);
}
