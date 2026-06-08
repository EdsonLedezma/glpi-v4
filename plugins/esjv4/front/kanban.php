<?php

declare(strict_types=1);

use GlpiPlugin\Esjv4\ActivityActionService;
use GlpiPlugin\Esjv4\Catalog;
use GlpiPlugin\Esjv4\KanbanService;
use GlpiPlugin\Esjv4\PlanningRepository;
use GlpiPlugin\Esjv4\Plugin;

require_once dirname(__DIR__, 3) . '/inc/includes.php';
require_once dirname(__DIR__) . '/setup.php';

Session::checkCentralAccess();

$repository = new PlanningRepository();
$project_id = (int) ($_POST['project_id'] ?? $_GET['project_id'] ?? 0);

if ($project_id <= 0) {
    Session::addMessageAfterRedirect('Selecciona un proyecto para abrir Kanban.', true, ERROR);
    Html::redirect('/plugins/esjv4/front/projects.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $activity_id = (int) ($_POST['activity_id'] ?? 0);
    $action = (string) ($_POST['activity_action'] ?? '');
    $result = (new ActivityActionService($repository))->handle($activity_id, $action);
    Session::addMessageAfterRedirect($result['message'], true, ($result['ok'] ?? false) ? INFO : ERROR);
    Html::redirect('/plugins/esjv4/front/kanban.php?project_id=' . $project_id);
}

$board = (new KanbanService($repository))->projectBoard($project_id);
$project = $board['project'];

Html::header('Kanban ESJ V4', $_SERVER['PHP_SELF'], 'tools', 'esjv4');

echo '<div class="container-fluid">';
echo '<div class="d-flex justify-content-between align-items-start mb-3">';
echo '<div>';
echo '<a class="text-muted" href="/plugins/esjv4/front/project.php?id=' . $project_id . '">&larr; Proyecto</a>';
echo '<h1>Kanban de ingenieria</h1>';
echo '<p class="text-muted mb-0">' . htmlescape((string) ($project['project_name'] ?? $project['project_code'] ?? 'Proyecto ESJ')) . '</p>';
echo '</div>';
echo '<a class="btn btn-outline-secondary" href="/plugins/esjv4/front/projects.php">Proyectos</a>';
echo '</div>';

echo '<div class="esj-kanban d-flex gap-3 overflow-auto pb-3">';
foreach ($board['columns'] as $status => $column) {
    render_column($project_id, (string) $status, $column);
}
echo '</div>';
echo '</div>';

echo '<style>
.esj-kanban-column{min-width:280px;max-width:320px}
.esj-kanban-card{border:1px solid var(--tblr-border-color);border-radius:6px;background:var(--tblr-bg-surface);padding:10px;margin-bottom:10px}
.esj-kanban-card-title{font-weight:600;line-height:1.25}
.esj-kanban-meta{font-size:12px;color:var(--tblr-muted)}
.esj-kanban-actions form{display:inline}
</style>';

Html::footer();

function render_column(int $project_id, string $status, array $column): void
{
    $cards = $column['cards'] ?? [];
    echo '<section class="esj-kanban-column">';
    echo '<div class="card h-100">';
    echo '<div class="card-header d-flex justify-content-between align-items-center">';
    echo '<span>' . htmlescape((string) $column['label']) . '</span>';
    echo '<span class="badge bg-secondary">' . count($cards) . '</span>';
    echo '</div>';
    echo '<div class="card-body">';

    if ($cards === []) {
        echo '<div class="text-muted small">Sin actividades.</div>';
    }

    foreach ($cards as $card) {
        render_card($project_id, $status, $card);
    }

    echo '</div></div></section>';
}

function render_card(int $project_id, string $status, array $card): void
{
    echo '<article class="esj-kanban-card">';
    echo '<div class="esj-kanban-card-title">' . htmlescape((string) $card['name']) . '</div>';
    echo '<div class="esj-kanban-meta mt-1">' . htmlescape((string) $card['phase_name']) . '</div>';
    echo '<div class="esj-kanban-meta">' . htmlescape((string) $card['building_name']) . '</div>';
    if ((string) ($card['product'] ?? '') !== '') {
        echo '<div class="esj-kanban-meta">Producto: ' . htmlescape((string) $card['product']) . '</div>';
    }

    echo '<div class="esj-kanban-actions mt-2 d-flex flex-wrap gap-1">';
    foreach (actions_for_status($status) as $action => $label) {
        echo '<form method="post" action="/plugins/esjv4/front/kanban.php">';
        echo Plugin::csrfField();
        echo '<input type="hidden" name="project_id" value="' . $project_id . '">';
        echo '<input type="hidden" name="activity_id" value="' . (int) $card['id'] . '">';
        echo '<input type="hidden" name="activity_action" value="' . htmlescape($action) . '">';
        echo '<button class="btn btn-sm btn-outline-primary" type="submit">' . htmlescape($label) . '</button>';
        echo '</form>';
    }
    echo '</div>';
    echo '</article>';
}

function actions_for_status(string $status): array
{
    return match ($status) {
        Catalog::STATUS_ACTIVE, Catalog::STATUS_BLOCKED => ['start' => 'Iniciar'],
        Catalog::STATUS_IN_PROGRESS => ['pause_rfi' => 'Pausa RFI', 'close' => 'Cerrar'],
        Catalog::STATUS_PAUSED_RFI, Catalog::STATUS_PAUSED_RESTRICTION => ['resume' => 'Reanudar'],
        default => [],
    };
}
