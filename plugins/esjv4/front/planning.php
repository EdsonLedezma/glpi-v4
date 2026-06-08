<?php

declare(strict_types=1);

use GlpiPlugin\Esjv4\Catalog;
use GlpiPlugin\Esjv4\PlanningRepository;
use GlpiPlugin\Esjv4\PlanningService;
use GlpiPlugin\Esjv4\Plugin;
use GlpiPlugin\Esjv4\SapIntakeService;
use GlpiPlugin\Esjv4\SapMailSample;
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
    echo Plugin::csrfField();
    echo '<input type="hidden" name="action" value="receive_sap_mail">';
    echo '<label class="form-label" for="sap_mail">Contenido del correo SAP</label>';
    echo '<textarea class="form-control font-monospace" id="sap_mail" name="sap_mail" rows="18">' . htmlescape(SapMailSample::mbaSanRafael()) . '</textarea>';
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
    echo Plugin::csrfField();
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
    $buildings = planning_building_rows();

    echo '<div class="card mb-3">';
    echo '<div class="card-header d-flex justify-content-between align-items-center">';
    echo '<span>Naves / edificios</span>';
    echo '<button class="btn btn-sm btn-outline-primary" type="button" data-esj-add-building>+ Nave</button>';
    echo '</div>';
    echo '<div class="card-body">';
    if (isset($errors['buildings'])) {
        echo '<div class="alert alert-danger">' . htmlescape($errors['buildings']) . '</div>';
    }

    echo '<div class="row g-3" data-esj-buildings>';
    foreach ($buildings as $index => $building) {
        render_building_card((int) $index, $building);
    }
    echo '</div>';
    echo '</div></div>';
}

function render_phases(array $template, array $errors): void
{
    $buildings = planning_building_rows();
    $phases = planning_phase_rows($template);
    $groups = esjv4_group_options();

    echo '<div class="card mb-3">';
    echo '<div class="card-header d-flex justify-content-between align-items-center">';
    echo '<span>Fases de construccion</span>';
    echo '<button class="btn btn-sm btn-outline-primary" type="button" data-esj-add-phase>+ Fase</button>';
    echo '</div>';
    echo '<div class="card-body">';
    if (isset($errors['phase_slots'])) {
        echo '<div class="alert alert-danger">' . htmlescape($errors['phase_slots']) . '</div>';
    }

    echo '<div class="d-grid gap-3" data-esj-phases>';
    foreach ($phases as $slot => $phase) {
        render_phase_card((int) $slot, $phase, $buildings, $groups);
    }
    echo '</div>';
    echo '</div></div>';
    render_planning_script($groups);
}

function render_building_card(int $index, array $building): void
{
    $key = (string) ($building['key'] ?? ('b' . $index));
    echo '<div class="col-md-6" data-esj-building-card>';
    echo '<div class="border rounded p-3 bg-white">';
    echo '<div class="d-flex justify-content-between align-items-center mb-2">';
    echo '<strong>Nave</strong>';
    echo '<button class="btn btn-sm btn-outline-secondary" type="button" data-esj-remove-card>Quitar</button>';
    echo '</div>';
    echo '<input type="hidden" name="buildings[' . $index . '][key]" value="' . htmlescape($key) . '" data-esj-building-key>';
    echo '<div class="row g-2">';
    echo '<div class="col-md-6"><label class="form-label small">Nave interna</label><input class="form-control form-control-sm" name="buildings[' . $index . '][name]" value="' . htmlescape((string) ($building['name'] ?? '')) . '" placeholder="Nave A" data-esj-building-name></div>';
    echo '<div class="col-md-6"><label class="form-label small">Etiqueta cliente</label><input class="form-control form-control-sm" name="buildings[' . $index . '][client_label]" value="' . htmlescape((string) ($building['client_label'] ?? '')) . '" placeholder="Edificio A1"></div>';
    echo '</div></div></div>';
}

function render_phase_card(int $slot, array $phase, array $buildings, array $groups): void
{
    $name = 'phase_slots[' . $slot . ']';
    echo '<section class="border rounded p-3 bg-white" data-esj-phase-card>';
    echo '<div class="d-flex justify-content-between align-items-center mb-2">';
    echo '<strong>Fase ' . sprintf('%02d', $slot) . '</strong>';
    echo '<button class="btn btn-sm btn-outline-secondary" type="button" data-esj-remove-card>Quitar</button>';
    echo '</div>';
    echo '<input type="hidden" name="' . $name . '[enabled]" value="1">';
    echo '<input type="hidden" name="' . $name . '[slot]" value="' . $slot . '">';
    echo '<div class="row g-2">';
    echo '<div class="col-md-4"><label class="form-label small">Nombre</label><input class="form-control form-control-sm" name="' . $name . '[name]" value="' . htmlescape((string) ($phase['name'] ?? ('Fase ' . sprintf('%02d', $slot)))) . '"></div>';
    echo '<div class="col-md-3"><label class="form-label small">Nave</label>' . building_select($name . '[building_key]', (string) ($phase['building_key'] ?? 'b1'), $buildings) . '</div>';
    echo '<div class="col-md-3"><label class="form-label small">Grupo responsable</label>' . group_select($name . '[groups_id]', (int) ($phase['groups_id'] ?? 0), $groups) . '</div>';
    echo '<div class="col-md-2"><label class="form-label small">Paquete</label>' . package_select($name . '[activity_package_key]', (string) ($phase['activity_package_key'] ?? 'core_engineering')) . '</div>';
    echo '<div class="col-md-3"><label class="form-label small">Inicio</label><input class="form-control form-control-sm" type="date" name="' . $name . '[planned_start]" value="' . htmlescape((string) ($phase['planned_start'] ?? '')) . '"></div>';
    echo '<div class="col-md-3"><label class="form-label small">Termino</label><input class="form-control form-control-sm" type="date" name="' . $name . '[planned_end]" value="' . htmlescape((string) ($phase['planned_end'] ?? '')) . '"></div>';
    echo '</div>';
    echo '<div class="mt-3">';
    echo '<div class="d-flex justify-content-between align-items-center mb-2">';
    echo '<span class="small text-muted">Tareas especificas por etapa</span>';
    echo '<button class="btn btn-sm btn-outline-primary" type="button" data-esj-add-stage-task>+ Etapa / tareas</button>';
    echo '</div>';
    echo '<div class="d-grid gap-2" data-esj-stage-tasks>';
    foreach (planning_stage_task_rows($phase) as $task_index => $stage_task) {
        render_stage_task_row($slot, (int) $task_index, $stage_task);
    }
    echo '</div></div>';
    echo '</section>';
}

function render_stage_task_row(int $slot, int $task_index, array $stage_task): void
{
    $name = 'phase_slots[' . $slot . '][stage_tasks][' . $task_index . ']';
    echo '<div class="row g-2 align-items-start" data-esj-stage-task-row>';
    echo '<div class="col-md-3">' . stage_select($name . '[stage_key]', (string) ($stage_task['stage_key'] ?? 'structural_design')) . '</div>';
    echo '<div class="col-md-8"><textarea class="form-control form-control-sm" rows="2" name="' . $name . '[tasks]" placeholder="Una tarea por linea">' . htmlescape((string) ($stage_task['tasks'] ?? '')) . '</textarea></div>';
    echo '<div class="col-md-1"><button class="btn btn-sm btn-outline-secondary w-100" type="button" data-esj-remove-card>Quitar</button></div>';
    echo '</div>';
}

function summary_field(string $label, string $value): void
{
    echo '<div class="col-md-6 col-xl-4">';
    echo '<div class="text-muted small">' . htmlescape($label) . '</div>';
    echo '<div class="fw-semibold">' . htmlescape($value === '' ? '-' : $value) . '</div>';
    echo '</div>';
}

function planning_building_rows(): array
{
    $posted = $_POST['buildings'] ?? [];
    $rows = is_array($posted) && $posted !== [] ? $posted : [
        1 => ['key' => 'b1', 'name' => 'Nave A', 'client_label' => ''],
    ];

    $normalized = [];
    foreach ($rows as $index => $row) {
        if (!is_array($row)) {
            continue;
        }

        $index = max(1, (int) $index);
        $normalized[$index] = [
            'key' => (string) ($row['key'] ?? ('b' . $index)),
            'name' => (string) ($row['name'] ?? ''),
            'client_label' => (string) ($row['client_label'] ?? ''),
        ];
    }

    return $normalized !== [] ? $normalized : [1 => ['key' => 'b1', 'name' => 'Nave A', 'client_label' => '']];
}

function planning_phase_rows(array $template): array
{
    $posted = $_POST['phase_slots'] ?? [];
    if (is_array($posted) && $posted !== []) {
        $rows = [];
        foreach ($posted as $slot => $row) {
            if (!is_array($row)) {
                continue;
            }

            $slot = max(1, (int) ($row['slot'] ?? $slot));
            $rows[$slot] = $row + [
                'enabled' => '1',
                'slot' => $slot,
                'name' => 'Fase ' . sprintf('%02d', $slot),
                'building_key' => 'b1',
                'groups_id' => 0,
                'activity_package_key' => 'core_engineering',
                'planned_start' => '',
                'planned_end' => '',
                'stage_tasks' => [],
            ];
        }

        ksort($rows);
        if ($rows !== []) {
            return $rows;
        }
    }

    $first = $template['phase_slots'][0] ?? ['slot' => 1, 'name' => 'Fase 01'];

    return [
        1 => [
            'enabled' => '1',
            'slot' => 1,
            'name' => (string) ($first['name'] ?? 'Fase 01'),
            'building_key' => 'b1',
            'groups_id' => 0,
            'activity_package_key' => 'core_engineering',
            'planned_start' => '',
            'planned_end' => '',
            'stage_tasks' => [],
        ],
    ];
}

function planning_stage_task_rows(array $phase): array
{
    $rows = $phase['stage_tasks'] ?? [];
    if (!is_array($rows) || $rows === []) {
        return [
            0 => ['stage_key' => 'structural_design', 'tasks' => ''],
        ];
    }

    $normalized = [];
    foreach ($rows as $index => $row) {
        if (!is_array($row)) {
            continue;
        }

        $tasks = $row['tasks'] ?? '';
        if (is_array($tasks)) {
            $tasks = implode("\n", $tasks);
        }

        $normalized[(int) $index] = [
            'stage_key' => (string) ($row['stage_key'] ?? 'structural_design'),
            'tasks' => (string) $tasks,
        ];
    }

    return $normalized !== [] ? $normalized : [0 => ['stage_key' => 'structural_design', 'tasks' => '']];
}

function building_select(string $name, string $selected, array $buildings): string
{
    $html = '<select class="form-select form-select-sm" name="' . htmlescape($name) . '" data-esj-building-select>';
    foreach ($buildings as $index => $building) {
        $key = (string) ($building['key'] ?? ('b' . $index));
        $label = trim((string) ($building['name'] ?? ''));
        $html .= '<option value="' . htmlescape($key) . '"' . esjv4_selected($selected, $key) . '>' . htmlescape($label !== '' ? $label : ('Nave ' . (int) $index)) . '</option>';
    }
    return $html . '</select>';
}

function group_select(string $name, int $selected, array $groups): string
{
    $html = '<select class="form-select form-select-sm" name="' . htmlescape($name) . '">';
    $html .= '<option value="0">Sin asignar</option>';
    foreach ($groups as $id => $label) {
        $html .= '<option value="' . (int) $id . '"' . ($selected === (int) $id ? ' selected' : '') . '>' . htmlescape($label) . '</option>';
    }

    return $html . '</select>';
}

function stage_select(string $name, string $selected): string
{
    $html = '<select class="form-select form-select-sm" name="' . htmlescape($name) . '">';
    foreach (Catalog::stages() as $key => $label) {
        $html .= '<option value="' . htmlescape((string) $key) . '"' . esjv4_selected($selected, (string) $key) . '>' . htmlescape($label) . '</option>';
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

function esjv4_group_options(): array
{
    global $DB;

    if (!isset($DB) || !is_object($DB)) {
        return [];
    }

    $groups = [];
    $iterator = $DB->request([
        'SELECT' => ['id', 'name', 'completename'],
        'FROM' => 'glpi_groups',
        'WHERE' => [
            'OR' => [
                ['name' => ['LIKE', 'ESJ%']],
                ['completename' => ['LIKE', 'ESJ%']],
            ],
        ],
        'ORDER' => ['completename ASC', 'name ASC'],
    ]);

    foreach ($iterator as $row) {
        $groups[(int) $row['id']] = (string) ($row['completename'] ?: $row['name']);
    }

    return $groups;
}

function render_planning_script(array $groups): void
{
    $packages = [];
    foreach (Catalog::phaseActivityPackages() as $key => $package) {
        $packages[] = ['key' => (string) $key, 'label' => (string) $package['name']];
    }

    $stages = [];
    foreach (Catalog::stages() as $key => $label) {
        $stages[] = ['key' => (string) $key, 'label' => (string) $label];
    }

    $group_options = [];
    foreach ($groups as $id => $label) {
        $group_options[] = ['id' => (int) $id, 'label' => $label];
    }

    $config = json_encode(
        [
            'packages' => $packages,
            'stages' => $stages,
            'groups' => $group_options,
        ],
        JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    );

    echo '<script>';
    echo '(() => {';
    echo 'const config = ' . $config . ';';
    echo <<<'JS'
const buildingsRoot = document.querySelector('[data-esj-buildings]');
const phasesRoot = document.querySelector('[data-esj-phases]');
if (!buildingsRoot || !phasesRoot) return;

const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
  '&': '&amp;',
  '<': '&lt;',
  '>': '&gt;',
  '"': '&quot;',
  "'": '&#039;'
}[char]));

const nextBuildingIndex = () => {
  const keys = [...buildingsRoot.querySelectorAll('[name^="buildings["]')]
    .map((input) => input.name.match(/^buildings\[(\d+)\]/))
    .filter(Boolean)
    .map((match) => Number(match[1]));
  return Math.max(0, ...keys) + 1;
};

const nextPhaseSlot = () => {
  const slots = [...phasesRoot.querySelectorAll('input[name$="[slot]"]')]
    .map((input) => Number(input.value || 0));
  return Math.max(0, ...slots) + 1;
};

const buildingRows = () => [...buildingsRoot.querySelectorAll('[data-esj-building-card]')].map((card, idx) => {
  const keyInput = card.querySelector('[data-esj-building-key]');
  const nameInput = card.querySelector('[data-esj-building-name]');
  return {
    key: keyInput?.value || `b${idx + 1}`,
    label: (nameInput?.value || '').trim() || `Nave ${idx + 1}`,
  };
});

const optionHtml = (items, selected = '') => items.map((item) => {
  const value = item.key ?? item.id;
  return `<option value="${esc(value)}"${String(value) === String(selected) ? ' selected' : ''}>${esc(item.label)}</option>`;
}).join('');

const refreshBuildingSelects = () => {
  const rows = buildingRows();
  document.querySelectorAll('[data-esj-building-select]').forEach((select) => {
    const selected = select.value;
    select.innerHTML = optionHtml(rows, selected);
  });
};

const renderBuilding = (index) => `
  <div class="col-md-6" data-esj-building-card>
    <div class="border rounded p-3 bg-white">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <strong>Nave</strong>
        <button class="btn btn-sm btn-outline-secondary" type="button" data-esj-remove-card>Quitar</button>
      </div>
      <input type="hidden" name="buildings[${index}][key]" value="b${index}" data-esj-building-key>
      <div class="row g-2">
        <div class="col-md-6"><label class="form-label small">Nave interna</label><input class="form-control form-control-sm" name="buildings[${index}][name]" placeholder="Nave A" data-esj-building-name></div>
        <div class="col-md-6"><label class="form-label small">Etiqueta cliente</label><input class="form-control form-control-sm" name="buildings[${index}][client_label]" placeholder="Edificio A1"></div>
      </div>
    </div>
  </div>`;

const renderStageTask = (slot, index) => `
  <div class="row g-2 align-items-start" data-esj-stage-task-row>
    <div class="col-md-3"><select class="form-select form-select-sm" name="phase_slots[${slot}][stage_tasks][${index}][stage_key]">${optionHtml(config.stages, 'structural_design')}</select></div>
    <div class="col-md-8"><textarea class="form-control form-control-sm" rows="2" name="phase_slots[${slot}][stage_tasks][${index}][tasks]" placeholder="Una tarea por linea"></textarea></div>
    <div class="col-md-1"><button class="btn btn-sm btn-outline-secondary w-100" type="button" data-esj-remove-card>Quitar</button></div>
  </div>`;

const renderPhase = (slot) => `
  <section class="border rounded p-3 bg-white" data-esj-phase-card>
    <div class="d-flex justify-content-between align-items-center mb-2">
      <strong>Fase ${String(slot).padStart(2, '0')}</strong>
      <button class="btn btn-sm btn-outline-secondary" type="button" data-esj-remove-card>Quitar</button>
    </div>
    <input type="hidden" name="phase_slots[${slot}][enabled]" value="1">
    <input type="hidden" name="phase_slots[${slot}][slot]" value="${slot}">
    <div class="row g-2">
      <div class="col-md-4"><label class="form-label small">Nombre</label><input class="form-control form-control-sm" name="phase_slots[${slot}][name]" value="Fase ${String(slot).padStart(2, '0')}"></div>
      <div class="col-md-3"><label class="form-label small">Nave</label><select class="form-select form-select-sm" name="phase_slots[${slot}][building_key]" data-esj-building-select>${optionHtml(buildingRows(), 'b1')}</select></div>
      <div class="col-md-3"><label class="form-label small">Grupo responsable</label><select class="form-select form-select-sm" name="phase_slots[${slot}][groups_id]"><option value="0">Sin asignar</option>${optionHtml(config.groups, 0)}</select></div>
      <div class="col-md-2"><label class="form-label small">Paquete</label><select class="form-select form-select-sm" name="phase_slots[${slot}][activity_package_key]">${optionHtml(config.packages, 'core_engineering')}</select></div>
      <div class="col-md-3"><label class="form-label small">Inicio</label><input class="form-control form-control-sm" type="date" name="phase_slots[${slot}][planned_start]"></div>
      <div class="col-md-3"><label class="form-label small">Termino</label><input class="form-control form-control-sm" type="date" name="phase_slots[${slot}][planned_end]"></div>
    </div>
    <div class="mt-3">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="small text-muted">Tareas especificas por etapa</span>
        <button class="btn btn-sm btn-outline-primary" type="button" data-esj-add-stage-task>+ Etapa / tareas</button>
      </div>
      <div class="d-grid gap-2" data-esj-stage-tasks>${renderStageTask(slot, 0)}</div>
    </div>
  </section>`;

document.addEventListener('input', (event) => {
  if (event.target.matches('[data-esj-building-name]')) {
    refreshBuildingSelects();
  }
});

document.addEventListener('click', (event) => {
  const addBuilding = event.target.closest('[data-esj-add-building]');
  if (addBuilding) {
    buildingsRoot.insertAdjacentHTML('beforeend', renderBuilding(nextBuildingIndex()));
    refreshBuildingSelects();
    return;
  }

  const addPhase = event.target.closest('[data-esj-add-phase]');
  if (addPhase) {
    phasesRoot.insertAdjacentHTML('beforeend', renderPhase(nextPhaseSlot()));
    refreshBuildingSelects();
    return;
  }

  const addStageTask = event.target.closest('[data-esj-add-stage-task]');
  if (addStageTask) {
    const card = addStageTask.closest('[data-esj-phase-card]');
    const slot = card.querySelector('input[name$="[slot]"]').value;
    const root = card.querySelector('[data-esj-stage-tasks]');
    root.insertAdjacentHTML('beforeend', renderStageTask(slot, root.querySelectorAll('[data-esj-stage-task-row]').length));
    return;
  }

  const remove = event.target.closest('[data-esj-remove-card]');
  if (remove) {
    const target = remove.closest('[data-esj-stage-task-row], [data-esj-phase-card], [data-esj-building-card]');
    if (target) {
      target.remove();
      refreshBuildingSelects();
    }
  }
});

refreshBuildingSelects();
JS;
    echo '})();';
    echo '</script>';
}

function esjv4_checked(mixed $value): string
{
    return in_array($value, [true, 1, '1', 'on'], true) ? ' checked' : '';
}

function esjv4_selected(string $current, string $expected): string
{
    return $current === $expected ? ' selected' : '';
}
