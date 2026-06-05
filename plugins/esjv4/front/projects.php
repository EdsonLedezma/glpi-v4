<?php

declare(strict_types=1);

use GlpiPlugin\Esjv4\PlanningRepository;
use GlpiPlugin\Esjv4\Plugin;

require_once dirname(__DIR__, 3) . '/inc/includes.php';
require_once dirname(__DIR__) . '/setup.php';

Session::checkCentralAccess();

$repository = new PlanningRepository();
$projects = $repository->projects();

Html::header('Proyectos ESJ V4', $_SERVER['PHP_SELF'], 'tools', 'esjv4');

echo '<div class="container-fluid">';
echo '<div class="d-flex justify-content-between align-items-center mb-3">';
echo '<div>';
echo '<h1>Proyectos ESJ V4</h1>';
echo '<p class="text-muted mb-0">Resumen operativo de proyectos creados desde Planeacion.</p>';
echo '</div>';
echo '<a class="btn btn-primary" href="/plugins/esjv4/front/planning.php">Nuevo proyecto</a>';
echo '</div>';

echo '<div class="card">';
echo '<div class="card-header">' . htmlescape(Plugin::NAME) . '</div>';
echo '<div class="card-body p-0">';

if ($projects === []) {
    echo '<div class="p-4 text-muted">Aun no hay proyectos ESJ V4 registrados.</div>';
} else {
    echo '<div class="table-responsive">';
    echo '<table class="table table-hover align-middle mb-0">';
    echo '<thead><tr>';
    echo '<th>Proyecto</th><th>Cliente</th><th>Cotizacion</th><th>Ubicacion</th><th>Estado</th><th></th>';
    echo '</tr></thead><tbody>';

    foreach ($projects as $project) {
        $project_id = (int) $project['id'];
        echo '<tr>';
        echo '<td>';
        echo '<strong>' . htmlescape((string) ($project['project_name'] ?? $project['sap_project_code'] ?? '')) . '</strong>';
        echo '<div class="text-muted small">' . htmlescape((string) ($project['project_code'] ?? $project['sap_project_code'] ?? '')) . '</div>';
        echo '</td>';
        echo '<td>' . htmlescape((string) ($project['customer_name'] ?? '')) . '</td>';
        echo '<td>' . htmlescape((string) ($project['quotation_code'] ?? '')) . '</td>';
        echo '<td>' . htmlescape((string) ($project['location'] ?? '')) . '</td>';
        echo '<td>' . status_badge((string) ($project['status'] ?? '')) . '</td>';
        echo '<td class="text-end"><a class="btn btn-sm btn-outline-primary" href="/plugins/esjv4/front/project.php?id=' . $project_id . '">Abrir</a></td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '</div>';
}

echo '</div></div></div>';

Html::footer();

function status_badge(string $status): string
{
    $label = $status === '' ? 'sin estado' : $status;

    return '<span class="badge bg-secondary">' . htmlescape($label) . '</span>';
}
