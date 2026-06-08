<?php

declare(strict_types=1);

use GlpiPlugin\Esjv4\Catalog;
use GlpiPlugin\Esjv4\PlanningRepository;
use GlpiPlugin\Esjv4\RfiLinkService;

require_once dirname(__DIR__, 3) . '/inc/includes.php';
require_once dirname(__DIR__) . '/setup.php';

Session::checkCentralAccess();

$repository = new PlanningRepository();
$project_id = (int) ($_POST['project_id'] ?? $_GET['project_id'] ?? 0);
$errors = [];

if ($project_id <= 0) {
    Session::addMessageAfterRedirect('Selecciona un proyecto para ver RFIs.', true, ERROR);
    Html::redirect('/plugins/esjv4/front/projects.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = (new RfiLinkService($repository))->linkExistingTicket($project_id, $_POST);

    if (($result['ok'] ?? false) === true) {
        Session::addMessageAfterRedirect($result['message'], true, INFO);
        Html::redirect('/plugins/esjv4/front/rfis.php?project_id=' . $project_id);
    }

    $errors = $result['errors'] ?? [];
}

$project = $repository->project($project_id);
$phases = $repository->phases($project_id);
$buildings = $repository->buildings($project_id);
$activities = $repository->activities($project_id);
$links = $repository->ticketLinks($project_id);

Html::header('RFIs ESJ V4', $_SERVER['PHP_SELF'], 'tools', 'esjv4');

echo '<div class="container-fluid">';
echo '<div class="d-flex justify-content-between align-items-start mb-3">';
echo '<div>';
echo '<a class="text-muted" href="/plugins/esjv4/front/project.php?id=' . $project_id . '">&larr; Proyecto</a>';
echo '<h1>RFIs y restricciones</h1>';
echo '<p class="text-muted mb-0">' . htmlescape((string) ($project['project_name'] ?? $project['project_code'] ?? 'Proyecto ESJ')) . '</p>';
echo '</div>';
echo '<a class="btn btn-primary" href="/plugins/esjv4/front/kanban.php?project_id=' . $project_id . '">Kanban</a>';
echo '</div>';

echo '<div class="card mb-3">';
echo '<div class="card-header">Vincular ticket existente</div>';
echo '<div class="card-body">';
echo '<form method="post" action="/plugins/esjv4/front/rfis.php">';
echo '<input type="hidden" name="project_id" value="' . $project_id . '">';
echo '<div class="row g-3">';
esjv4_rfi_input('tickets_id', 'ID ticket GLPI', $errors);
echo '<div class="col-md-3"><label class="form-label">Alcance</label>' . esjv4_rfi_select('scope', Catalog::rfiScopes(), 'activity') . esjv4_rfi_field_error($errors, 'scope') . '</div>';
echo '<div class="col-md-3"><label class="form-label">Impacto</label>' . esjv4_rfi_select('impact', Catalog::rfiImpacts(), Catalog::DEFAULT_RFI_IMPACT) . esjv4_rfi_field_error($errors, 'impact') . '</div>';
echo '<div class="col-md-3"><label class="form-label">Fase</label>' . esjv4_entity_select('phase_id', $phases, 'name') . '</div>';
echo '<div class="col-md-3"><label class="form-label">Nave</label>' . esjv4_entity_select('building_id', $buildings, 'name') . '</div>';
echo '<div class="col-md-6"><label class="form-label">Actividad</label>' . esjv4_entity_select('activity_id', $activities, 'name') . '</div>';
echo '</div>';
echo '<div class="mt-3"><button class="btn btn-primary" type="submit">Vincular RFI</button></div>';
echo '</form>';
echo '</div></div>';

echo '<div class="card">';
echo '<div class="card-header">Tickets vinculados</div>';
echo '<div class="card-body p-0">';

if ($links === []) {
    echo '<div class="p-4 text-muted">No hay RFIs vinculados a este proyecto.</div>';
} else {
    echo '<div class="table-responsive">';
    echo '<table class="table table-sm align-middle mb-0">';
    echo '<thead><tr><th>Ticket</th><th>Alcance</th><th>Impacto</th><th>Estado</th><th>Fase</th><th>Nave</th><th>Actividad</th></tr></thead><tbody>';
    foreach ($links as $link) {
        echo '<tr>';
        echo '<td>#' . (int) $link['tickets_id'] . '</td>';
        echo '<td>' . htmlescape((string) $link['scope']) . '</td>';
        echo '<td>' . htmlescape((string) $link['impact']) . '</td>';
        echo '<td>' . htmlescape((string) $link['status']) . '</td>';
        echo '<td>' . (int) $link['esj_phases_id'] . '</td>';
        echo '<td>' . (int) $link['esj_buildings_id'] . '</td>';
        echo '<td>' . (int) $link['esj_activities_id'] . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '</div>';
}

echo '</div></div></div>';

Html::footer();

function esjv4_rfi_input(string $name, string $label, array $errors): void
{
    echo '<div class="col-md-3">';
    echo '<label class="form-label">' . htmlescape($label) . '</label>';
    echo '<input class="form-control" name="' . htmlescape($name) . '" value="' . htmlescape((string) ($_POST[$name] ?? '')) . '">';
    echo esjv4_rfi_field_error($errors, $name);
    echo '</div>';
}

function esjv4_rfi_select(string $name, array $options, string $default): string
{
    $selected = (string) ($_POST[$name] ?? $default);
    $html = '<select class="form-select" name="' . htmlescape($name) . '">';
    foreach ($options as $option) {
        $html .= '<option value="' . htmlescape((string) $option) . '"' . ($selected === (string) $option ? ' selected' : '') . '>' . htmlescape((string) $option) . '</option>';
    }

    return $html . '</select>';
}

function esjv4_entity_select(string $name, array $rows, string $label_key): string
{
    $selected = (int) ($_POST[$name] ?? 0);
    $html = '<select class="form-select" name="' . htmlescape($name) . '">';
    $html .= '<option value="0">Sin ligar</option>';
    foreach ($rows as $row) {
        $id = (int) $row['id'];
        $label = (string) ($row[$label_key] ?? ('#' . $id));
        $html .= '<option value="' . $id . '"' . ($selected === $id ? ' selected' : '') . '>' . htmlescape($label) . '</option>';
    }

    return $html . '</select>';
}

function esjv4_rfi_field_error(array $errors, string $name): string
{
    if (!isset($errors[$name])) {
        return '';
    }

    return '<div class="text-danger small">' . htmlescape((string) $errors[$name]) . '</div>';
}
