<?php

declare(strict_types=1);

use GlpiPlugin\Esjv4\Plugin;
use GlpiPlugin\Esjv4\Schema;

require_once dirname(__DIR__, 3) . '/inc/includes.php';
require_once dirname(__DIR__) . '/setup.php';

Html::header(Plugin::NAME, $_SERVER['PHP_SELF'], 'tools', 'esjv4');

$health = Schema::health();
$ready = count(array_filter($health)) === count($health);

echo '<div class="container-fluid">';
echo '<h1>' . htmlescape(Plugin::NAME) . '</h1>';
echo '<p>Fundacion del sistema ESJ V4 para gestion de proyectos de construccion.</p>';
echo '<div class="card">';
echo '<div class="card-header">Estado del plugin</div>';
echo '<div class="card-body">';
echo '<p><strong>Version:</strong> ' . htmlescape(Plugin::VERSION) . '</p>';
echo '<p><strong>Esquema:</strong> ' . ($ready ? 'Listo' : 'Pendiente de instalacion') . '</p>';
echo '<a class="btn btn-primary" href="/plugins/esjv4/front/templates.php">Ver plantillas</a>';
echo '</div></div></div>';

Html::footer();
