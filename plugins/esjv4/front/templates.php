<?php

declare(strict_types=1);

use GlpiPlugin\Esjv4\Catalog;
use GlpiPlugin\Esjv4\Plugin;
use GlpiPlugin\Esjv4\TemplateService;

require_once dirname(__DIR__, 3) . '/inc/includes.php';
require_once dirname(__DIR__) . '/setup.php';

Session::checkCentralAccess();

Html::header('Plantillas ESJ V4', $_SERVER['PHP_SELF'], 'tools', 'esjv4');

$template = TemplateService::defaultProjectTemplate();

echo '<div class="container-fluid">';
echo '<h1>Plantillas ESJ V4</h1>';
echo '<p>' . htmlescape($template['name']) . ' con ' . (int) $template['max_phases'] . ' fases disponibles.</p>';
echo '<h2>Etapas core</h2>';
echo '<ul>';
foreach (Catalog::stages() as $stage) {
    echo '<li>' . htmlescape($stage) . '</li>';
}
echo '</ul>';
echo '<h2>Plantillas de actividades</h2>';
foreach ($template['activity_templates'] as $activity_template) {
    echo '<h3>' . htmlescape($activity_template['name']) . '</h3>';
    echo '<ul>';
    foreach ($activity_template['activities'] as $activity) {
        echo '<li>' . htmlescape($activity) . '</li>';
    }
    echo '</ul>';
}
echo '</div>';

Html::footer();
