<?php

declare(strict_types=1);

use GlpiPlugin\Esjv4\Schema;

esjv4_assert_true(class_exists(Schema::class), 'Schema class must exist');

$tables = Schema::tableDefinitions();

foreach ([
    'glpi_plugin_esjv4_projects',
    'glpi_plugin_esjv4_phases',
    'glpi_plugin_esjv4_buildings',
    'glpi_plugin_esjv4_stages',
    'glpi_plugin_esjv4_activities',
    'glpi_plugin_esjv4_templates',
    'glpi_plugin_esjv4_template_activities',
    'glpi_plugin_esjv4_gates',
    'glpi_plugin_esjv4_events',
    'glpi_plugin_esjv4_ticket_links',
] as $table) {
    esjv4_assert_true(isset($tables[$table]), 'Schema must define table ' . $table);
    esjv4_assert_true(str_contains($tables[$table], 'CREATE TABLE'), 'Table definition must be SQL for ' . $table);
    esjv4_assert_true(!str_contains($tables[$table], ' datetime '), 'Schema must use timestamp fields for GLPI compatibility in ' . $table);
    esjv4_assert_true(!str_contains($tables[$table], ' datetime'), 'Schema must not use datetime fields for GLPI compatibility in ' . $table);
}
