<?php

declare(strict_types=1);

use GlpiPlugin\Esjv4\Catalog;
use GlpiPlugin\Esjv4\PlanningRepository;
use GlpiPlugin\Esjv4\PlanningService;
use GlpiPlugin\Esjv4\Plugin;
use GlpiPlugin\Esjv4\SapIntakeService;
use GlpiPlugin\Esjv4\TemplateService;

require_once dirname(__DIR__, 3) . '/inc/includes.php';
require_once dirname(__DIR__) . '/setup.php';

Session::checkCentralAccess();

$repository = new PlanningRepository();
$planning_service = new PlanningService($repository);
$sap_service = new SapIntakeService($repository);
$errors = [];
$project_id = (int) ($_POST['project_id'] ?? $_GET['project_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'receive_sap_mail') {
        $result = $sap_service->receiveMail((string) ($_POST['sap_mail'] ?? ''));
        Session::addMessageAfterRedirect($result['message'], true, ($result['ok'] ?? false) ? INFO : ERROR);

        if (($result['ok'] ?? false) === true) {
            Html::redirect('/plugins/esjv4/front/planning.php?project_id=' . (int) $result['project_id']);
        }

        Html::redirect('/plugins/esjv4/front/planning.php');
    }

    if ($action === 'complete_planning') {
        $result = $planning_service->completePlanning($project_id, $_POST);

        if (($result['ok'] ?? false) === true) {
            Session::addMessageAfterRedirect('Planeacion completada. Proyecto listo para ejecucion controlada.', true, INFO);
            Html::redirect('/plugins/esjv4/front/project.php?id=' . (int) $result['project_id']);
        }

        $errors = $result['errors'] ?? [];
    }
}

Html::header('Planeacion ESJ V4', $_SERVER['PHP_SELF'], 'tools', 'esjv4');

echo '<div class="container-fluid">';

if ($project_id <= 0) {
    render_inbox($repository);
} else {
    render_planning_form($repository, $project_id, $errors);
}

echo '</div>';

Html::footer();

function render_inbox(PlanningRepository $repository): void
{
    $projects = array_filter(
        $repository->projects(),
        static fn(array $project): bool => (string) ($project['status'] ?? '') === Catalog::STATUS_PENDING_PLANNING
    );

    echo '<div class="d-flex justify-content-between align-items-start mb-3">';
    echo '<div>';
    echo '<h1>Bandeja SAP de Planeacion</h1>';
    echo '<p class="text-muted mb-0">Proyectos recibidos desde SAP pendientes de naves, fases y fechas.</p>';
    echo '</div>';
    echo '<a class="btn btn-outline-secondary" href="/plugins/esjv4/front/projects.php">Ver proyectos</a>';
    echo '</div>';

    echo '<div class="card mb-3">';
    echo '<div class="card-header">Pendientes de planear</div>';
    echo '<div class="card-body p-0">';

    if ($projects === []) {
        echo '<div class="p-4 text-muted">No hay proyectos SAP pendientes de Planeacion.</div>';
    } else {
        echo '<div class="table-responsive">';
        echo '<table class="table table-hover align-middle mb-0">';
        echo '<thead><tr><th>Proyecto</th><th>Cliente</th><th>Cotizacion</th><th>Ubicacion</th><th></th></tr></thead><tbody>';

        foreach ($projects as $project) {
            echo '<tr>';
            echo '<td><strong>' . htmlescape((string) ($project['project_name'] ?? '')) . '</strong><div class="text-muted small">' . htmlescape((string) ($project['project_code'] ?? '')) . '</div></td>';
            echo '<td>' . htmlescape((string) ($project['customer_name'] ?? '')) . '</td>';
            echo '<td>' . htmlescape((string) ($project['quotation_code'] ?? '')) . '</td>';
            echo '<td>' . htmlescape((string) ($project['location'] ?? '')) . '</td>';
            echo '<td class="text-end"><a class="btn btn-sm btn-primary" href="/plugins/esjv4/front/planning.php?project_id=' . (int) $project['id'] . '">Completar planeacion</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
    }

    echo '</div></div>';

    echo '<div class="card">';
    echo '<div class="card-header">Registrar correo SAP</div>';
    echo '<div class="card-body">';
    echo '<form method="post" action="/plugins/esjv4/front/planning.php">';
    echo '<input type="hidden" name="action" value="receive_sap_mail">';
    echo '<label class="form-label" for="sap_mail">Contenido del correo SAP</label>';
    echo '<textarea class="form-control font-monospace" id="sap_mail" name="sap_mail" rows="10"></textarea>';
    echo '<div class="mt-3">';
    echo '<button class="btn btn-primary" type="submit">Enviar a Planeacion</button>';
    echo '</div>';
    echo '</form>';
    echo '</div></div>';
}

function render_planning_form(PlanningRepository $repository, int $project_id, array $errors): void
{
    $project = $repository->project($project_id);

    if ($project === []) {
        echo '<div class="alert alert-danger">Proyecto SAP no encontrado.</div>';
        echo '<a class="btn btn-outline-secondary" href="/plugins/esjv4/front/planning.php">Volver</a>';
        return;
    }

    $template = TemplateService::defaultProjectTemplate();

    echo '<div class="d-flex justify-content-between align-items-start mb-3">';
    echo '<div>';
    echo '<a class="text-muted" href="/plugins/esjv4/front/planning.php">&larr; Bandeja SAP</a>';
    echo '<h1>Completar planeacion</h1>';
    echo '<p class="text-muted mb-0">' . htmlescape((string) ($project['project_name'] ?? '')) . '</p>';
    echo '</div>';
    echo '<span class="badge bg-warning text-dark">Pendiente de Planeacion</span>';
    echo '</div>';

    echo '<form method="post" action="/plugins/esjv4/front/planning.php">';
    echo '<input type="hidden" name="action" value="complete_planning">';
    echo '<input type="hidden" name="project_id" value="' . $project_id . '">';

    render_sap_summary($project);
    render_buildings($errors);
    render_phases($template, $errors);

    echo '<div class="d-flex gap-2">';
    echo '<button class="btn btn-primary" type="submit">Completar planeacion</button>';
    echo '<a class="btn btn-outline-secondary" href="/plugins/esjv4/front/planning.php">Cancelar</a>';
    echo '</div>';
    echo '</form>';
}

function render_sap_summary(array $project): void
{
    echo '<div class="card mb-3">';
    echo '<div class="card-header">Datos SAP</div>';
    echo '<div class="card-body">';
    echo '<div class="row g-3">';
    summary_field('Codigo SAP', (string) ($project['project_code'] ?? ''));
    summary_field('Proyecto', (string) ($project['project_name'] ?? ''));
    summary_field('Cliente', (string) ($project['customer_name'] ?? ''));
    summary_field('Cotizacion', (string) ($project['quotation_code'] ?? ''));
    summary_field('Ubicacion', (string) ($project['location'] ?? ''));
    echo '</div></div></div>';
}

function render_buildings(array $errors): void
{
    echo '<div class="card mb-3">';
    echo '<div class="card-header">Naves / edificios</div>';
    echo '<div class="card-body">';
    if (isset($errors['buildings'])) {
        echo '<div class="alert alert-danger">' . htmlescape($errors['buildings']) . '</div>';
    }

    echo '<div class="table-responsive">';
    echo '<table class="table table-sm align-middle mb-0">';
    echo '<thead><tr><th>Nave interna</th><th>Etiqueta cliente</th></tr></thead><tbody>';

    for ($index = 1; $index <= 8; $index++) {
        $key = 'b' . $index;
        $posted = $_POST['buildings'][$index] ?? [];
        echo '<tr>';
        echo '<td>';
        echo '<input type="hidden" name="buildings[' . $index . '][key]" value="' . $key . '">';
        echo '<input class="form-control form-control-sm" name="buildings[' . $index . '][name]" value="' . htmlescape((string) ($posted['name'] ?? ($index === 1 ? 'Nave A' : ''))) . '" placeholder="Nave A">';
        echo '</td>';
        echo '<td><input class="form-control form-control-sm" name="buildings[' . $index . '][client_label]" value="' . htmlescape((string) ($posted['client_label'] ?? '')) . '" placeholder="Edificio A1"></td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '</div></div></div>';
}

function render_phases(array $template, array $errors): void
{
    echo '<div class="card mb-3">';
    echo '<div class="card-header">Fases de construccion</div>';
    echo '<div class="card-body">';
    if (isset($errors['phase_slots'])) {
        echo '<div class="alert alert-danger">' . htmlescape($errors['phase_slots']) . '</div>';
    }

    echo '<div class="table-responsive">';
    echo '<table class="table table-sm align-middle">';
    echo '<thead><tr>';
    echo '<th>Usar</th><th>Fase</th><th>Nombre</th><th>Nave</th><th>Inicio</th><th>Termino</th><th>Paquete</th>';
    echo '</tr></thead><tbody>';

    foreach ($template['phase_slots'] as $phase_slot) {
        $slot = (int) $phase_slot['slot'];
        $name = 'phase_slots[' . $slot . ']';
        $posted = $_POST['phase_slots'][$slot] ?? [];
        echo '<tr>';
        echo '<td><input class="form-check-input" type="checkbox" name="' . $name . '[enabled]" value="1"' . esjv4_checked($posted['enabled'] ?? false) . '></td>';
        echo '<td>Fase ' . sprintf('%02d', $slot) . '<input type="hidden" name="' . $name . '[slot]" value="' . $slot . '"></td>';
        echo '<td><input class="form-control form-control-sm" name="' . $name . '[name]" value="' . htmlescape((string) ($posted['name'] ?? $phase_slot['name'])) . '"></td>';
        echo '<td>' . building_select($name . '[building_key]', (string) ($posted['building_key'] ?? 'b1')) . '</td>';
        echo '<td><input class="form-control form-control-sm" type="date" name="' . $name . '[planned_start]" value="' . htmlescape((string) ($posted['planned_start'] ?? '')) . '"></td>';
        echo '<td><input class="form-control form-control-sm" type="date" name="' . $name . '[planned_end]" value="' . htmlescape((string) ($posted['planned_end'] ?? '')) . '"></td>';
        echo '<td>' . package_select($name . '[activity_package_key]', (string) ($posted['activity_package_key'] ?? 'core_engineering')) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '</div></div></div>';
}

function summary_field(string $label, string $value): void
{
    echo '<div class="col-md-6 col-xl-4">';
    echo '<div class="text-muted small">' . htmlescape($label) . '</div>';
    echo '<div class="fw-semibold">' . htmlescape($value === '' ? '-' : $value) . '</div>';
    echo '</div>';
}

function building_select(string $name, string $selected): string
{
    $html = '<select class="form-select form-select-sm" name="' . htmlescape($name) . '">';
    for ($index = 1; $index <= 8; $index++) {
        $key = 'b' . $index;
        $html .= '<option value="' . $key . '"' . esjv4_selected($selected, $key) . '>Nave ' . $index . '</option>';
    }
    return $html . '</select>';
}

function package_select(string $name, string $selected): string
{
    $html = '<select class="form-select form-select-sm" name="' . htmlescape($name) . '">';
    foreach (Catalog::phaseActivityPackages() as $key => $package) {
        $html .= '<option value="' . htmlescape($key) . '"' . esjv4_selected($selected, (string) $key) . '>' . htmlescape($package['name']) . '</option>';
    }
    return $html . '</select>';
}

function esjv4_checked(mixed $value): string
{
    return in_array($value, [true, 1, '1', 'on'], true) ? ' checked' : '';
}

function esjv4_selected(string $current, string $expected): string
{
    return $current === $expected ? ' selected' : '';
}
