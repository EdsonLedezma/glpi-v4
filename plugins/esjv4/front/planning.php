<?php

declare(strict_types=1);

use GlpiPlugin\Esjv4\Catalog;
use GlpiPlugin\Esjv4\PlanningRepository;
use GlpiPlugin\Esjv4\PlanningService;
use GlpiPlugin\Esjv4\Plugin;
use GlpiPlugin\Esjv4\TemplateService;

require_once dirname(__DIR__, 3) . '/inc/includes.php';
require_once dirname(__DIR__) . '/setup.php';

Session::checkCentralAccess();

$service = new PlanningService(new PlanningRepository());
$result = null;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $service->createProjectFromPlanning($_POST);

    if (($result['ok'] ?? false) === true) {
        Session::addMessageAfterRedirect('Proyecto ESJ V4 creado desde Planeacion.', true, INFO);
        Html::redirect('/plugins/esjv4/front/project.php?id=' . (int) $result['project_id']);
    }

    $errors = $result['errors'] ?? [];
}

$template = TemplateService::defaultProjectTemplate();

Html::header('Planeacion ESJ V4', $_SERVER['PHP_SELF'], 'tools', 'esjv4');

echo '<div class="container-fluid">';
echo '<h1>Nuevo proyecto ESJ V4</h1>';
echo '<form method="post" action="/plugins/esjv4/front/planning.php">';

echo '<div class="card mb-3">';
echo '<div class="card-header">Datos del proyecto</div>';
echo '<div class="card-body">';
echo '<div class="row g-3">';
field('project_code', 'Codigo SAP / Proyecto', $errors);
field('project_name', 'Nombre del proyecto', $errors);
field('customer_name', 'Cliente', $errors);
field('quotation_code', 'Cotizacion', $errors);
field('location', 'Ubicacion', $errors);
echo '</div>';
echo '</div>';
echo '</div>';

echo '<div class="card mb-3">';
echo '<div class="card-header">Fases planeadas</div>';
echo '<div class="card-body">';
if (isset($errors['phase_slots'])) {
    echo '<div class="alert alert-danger">' . htmlescape($errors['phase_slots']) . '</div>';
}
echo '<div class="table-responsive">';
echo '<table class="table table-sm align-middle">';
echo '<thead><tr>';
echo '<th>Usar</th><th>Fase</th><th>Nombre</th><th>Inicio planeado</th><th>Termino limite</th><th>Actividades preestablecidas</th>';
echo '</tr></thead><tbody>';

foreach ($template['phase_slots'] as $phase_slot) {
    $slot = (int) $phase_slot['slot'];
    $name = 'phase_slots[' . $slot . ']';
    echo '<tr>';
    echo '<td><input class="form-check-input" type="checkbox" name="' . $name . '[enabled]" value="1"></td>';
    echo '<td>Fase ' . sprintf('%02d', $slot) . '<input type="hidden" name="' . $name . '[slot]" value="' . $slot . '"></td>';
    echo '<td><input class="form-control form-control-sm" name="' . $name . '[name]" value="' . htmlescape($phase_slot['name']) . '"></td>';
    echo '<td><input class="form-control form-control-sm" type="date" name="' . $name . '[planned_start]"></td>';
    echo '<td><input class="form-control form-control-sm" type="date" name="' . $name . '[planned_end]"></td>';
    echo '<td>';
    foreach (Catalog::defaultActivityTemplates() as $key => $activity_template) {
        echo '<label class="form-check form-check-inline mb-0">';
        echo '<input class="form-check-input" type="checkbox" name="' . $name . '[activity_template_keys][]" value="' . htmlescape($key) . '">';
        echo '<span class="form-check-label">' . htmlescape($activity_template['name']) . '</span>';
        echo '</label>';
    }
    echo '</td>';
    echo '</tr>';
}

echo '</tbody></table>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<div class="d-flex gap-2">';
echo '<button class="btn btn-primary" type="submit">Crear proyecto</button>';
echo '<a class="btn btn-outline-secondary" href="/plugins/esjv4/front/dashboard.php">Cancelar</a>';
echo '</div>';
echo '</form>';
echo '</div>';

Html::footer();

function field(string $name, string $label, array $errors): void
{
    $value = $_POST[$name] ?? '';
    echo '<div class="col-md-6">';
    echo '<label class="form-label" for="' . htmlescape($name) . '">' . htmlescape($label) . '</label>';
    echo '<input class="form-control" id="' . htmlescape($name) . '" name="' . htmlescape($name) . '" value="' . htmlescape((string) $value) . '">';
    if (isset($errors[$name])) {
        echo '<div class="text-danger small">' . htmlescape($errors[$name]) . '</div>';
    }
    echo '</div>';
}
