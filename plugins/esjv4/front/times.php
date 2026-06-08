<?php

declare(strict_types=1);

use GlpiPlugin\Esjv4\PlanningRepository;
use GlpiPlugin\Esjv4\TimeReportService;

require_once dirname(__DIR__, 3) . '/inc/includes.php';
require_once dirname(__DIR__) . '/setup.php';

Session::checkCentralAccess();

$repository = new PlanningRepository();
$project_id = (int) ($_GET['project_id'] ?? 0);

if ($project_id <= 0) {
    Session::addMessageAfterRedirect('Selecciona un proyecto para ver tiempos.', true, ERROR);
    Html::redirect('/plugins/esjv4/front/projects.php');
}

$project = $repository->project($project_id);
$activities = [];
foreach ($repository->activities($project_id) as $activity) {
    $activities[(int) $activity['id']] = $activity;
}

$report = (new TimeReportService($repository))->projectActivityTimes($project_id);

Html::header('Tiempos ESJ V4', $_SERVER['PHP_SELF'], 'tools', 'esjv4');

echo '<div class="container-fluid">';
echo '<div class="d-flex justify-content-between align-items-start mb-3">';
echo '<div>';
echo '<a class="text-muted" href="/plugins/esjv4/front/project.php?id=' . $project_id . '">&larr; Proyecto</a>';
echo '<h1>Reporte de tiempos</h1>';
echo '<p class="text-muted mb-0">' . htmlescape((string) ($project['project_name'] ?? $project['project_code'] ?? 'Proyecto ESJ')) . '</p>';
echo '</div>';
echo '<a class="btn btn-primary" href="/plugins/esjv4/front/kanban.php?project_id=' . $project_id . '">Kanban</a>';
echo '</div>';

echo '<div class="row g-3 mb-3">';
esjv4_time_metric('Trabajo operativo', (int) $report['totals']['work_seconds']);
esjv4_time_metric('Pausa por RFI', (int) $report['totals']['pause_rfi_seconds']);
echo '</div>';

echo '<div class="card">';
echo '<div class="card-header">Tiempos por actividad</div>';
echo '<div class="card-body p-0">';

if (($report['activities'] ?? []) === []) {
    echo '<div class="p-4 text-muted">Aun no hay eventos de tiempo para este proyecto.</div>';
} else {
    echo '<div class="table-responsive">';
    echo '<table class="table table-sm align-middle mb-0">';
    echo '<thead><tr><th>Actividad</th><th>Trabajo operativo</th><th>Pausa RFI</th></tr></thead><tbody>';
    foreach ($report['activities'] as $activity_id => $times) {
        echo '<tr>';
        echo '<td>' . htmlescape((string) ($activities[(int) $activity_id]['name'] ?? ('Actividad #' . $activity_id))) . '</td>';
        echo '<td>' . htmlescape(esjv4_duration((int) $times['work_seconds'])) . '</td>';
        echo '<td>' . htmlescape(esjv4_duration((int) $times['pause_rfi_seconds'])) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '</div>';
}

echo '</div></div></div>';

Html::footer();

function esjv4_time_metric(string $label, int $seconds): void
{
    echo '<div class="col-md-4">';
    echo '<div class="card"><div class="card-body">';
    echo '<div class="text-muted small">' . htmlescape($label) . '</div>';
    echo '<div class="display-6">' . htmlescape(esjv4_duration($seconds)) . '</div>';
    echo '</div></div></div>';
}

function esjv4_duration(int $seconds): string
{
    $hours = intdiv($seconds, 3600);
    $minutes = intdiv($seconds % 3600, 60);

    return $hours . 'h ' . str_pad((string) $minutes, 2, '0', STR_PAD_LEFT) . 'm';
}
