<?php

declare(strict_types=1);

namespace GlpiPlugin\Esjv4;

final class Schema
{
    public const TABLE_PROJECTS = 'glpi_plugin_esjv4_projects';
    public const TABLE_PHASES = 'glpi_plugin_esjv4_phases';
    public const TABLE_BUILDINGS = 'glpi_plugin_esjv4_buildings';
    public const TABLE_STAGES = 'glpi_plugin_esjv4_stages';
    public const TABLE_ACTIVITIES = 'glpi_plugin_esjv4_activities';
    public const TABLE_TEMPLATES = 'glpi_plugin_esjv4_templates';
    public const TABLE_TEMPLATE_ACTIVITIES = 'glpi_plugin_esjv4_template_activities';
    public const TABLE_GATES = 'glpi_plugin_esjv4_gates';
    public const TABLE_EVENTS = 'glpi_plugin_esjv4_events';
    public const TABLE_TICKET_LINKS = 'glpi_plugin_esjv4_ticket_links';

    public static function install(): bool
    {
        global $DB;

        if (!isset($DB) || !method_exists($DB, 'tableExists')) {
            return true;
        }

        foreach (self::tableDefinitions() as $table => $sql) {
            if ($DB->tableExists($table)) {
                continue;
            }

            if (!$DB->doQuery($sql)) {
                return false;
            }
        }

        return self::migrate();
    }

    private static function migrate(): bool
    {
        global $DB;

        if (!isset($DB) || !method_exists($DB, 'fieldExists')) {
            return true;
        }

        if ($DB->tableExists(self::TABLE_PHASES)) {
            if (!$DB->fieldExists(self::TABLE_PHASES, 'esj_buildings_id')) {
                if (!$DB->doQuery(
                    "ALTER TABLE `" . self::TABLE_PHASES . "` ADD `esj_buildings_id` int unsigned NOT NULL DEFAULT 0 AFTER `projecttasks_id`, ADD KEY `building` (`esj_buildings_id`)"
                )) {
                    return false;
                }
            }

            if (!$DB->fieldExists(self::TABLE_PHASES, 'groups_id')) {
                if (!$DB->doQuery(
                    "ALTER TABLE `" . self::TABLE_PHASES . "` ADD `groups_id` int unsigned NOT NULL DEFAULT 0 AFTER `esj_buildings_id`, ADD KEY `groups_id_idx` (`groups_id`)"
                )) {
                    return false;
                }
            }
        }

        return true;
    }

    public static function health(): array
    {
        global $DB;

        $result = [];

        foreach (array_keys(self::tableDefinitions()) as $table) {
            $result[$table] = isset($DB) && method_exists($DB, 'tableExists') && $DB->tableExists($table);
        }

        return $result;
    }

    public static function tableDefinitions(): array
    {
        return [
            self::TABLE_PROJECTS => "CREATE TABLE `" . self::TABLE_PROJECTS . "` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `projects_id` int unsigned NOT NULL DEFAULT 0,
                `sap_project_code` varchar(255) NOT NULL DEFAULT '',
                `quotation_code` varchar(255) NOT NULL DEFAULT '',
                `customer_name` varchar(255) NOT NULL DEFAULT '',
                `location` varchar(255) NOT NULL DEFAULT '',
                `status` varchar(80) NOT NULL DEFAULT 'planned',
                `raw_payload_hash` char(64) DEFAULT NULL,
                `date_creation` timestamp NOT NULL,
                `date_mod` timestamp NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `sap_project_code` (`sap_project_code`),
                KEY `projects_id` (`projects_id`),
                KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            self::TABLE_PHASES => "CREATE TABLE `" . self::TABLE_PHASES . "` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `esj_projects_id` int unsigned NOT NULL DEFAULT 0,
                `projecttasks_id` int unsigned NOT NULL DEFAULT 0,
                `esj_buildings_id` int unsigned NOT NULL DEFAULT 0,
                `groups_id` int unsigned NOT NULL DEFAULT 0,
                `slot` int unsigned NOT NULL DEFAULT 0,
                `name` varchar(255) NOT NULL DEFAULT '',
                `status` varchar(80) NOT NULL DEFAULT 'planned',
                `is_active` tinyint NOT NULL DEFAULT 0,
                `planned_start` timestamp DEFAULT NULL,
                `planned_end` timestamp DEFAULT NULL,
                `date_creation` timestamp NOT NULL,
                `date_mod` timestamp NOT NULL,
                PRIMARY KEY (`id`),
                KEY `project_slot` (`esj_projects_id`, `slot`),
                KEY `building` (`esj_buildings_id`),
                KEY `groups_id_idx` (`groups_id`),
                KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            self::TABLE_BUILDINGS => "CREATE TABLE `" . self::TABLE_BUILDINGS . "` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `esj_projects_id` int unsigned NOT NULL DEFAULT 0,
                `esj_phases_id` int unsigned NOT NULL DEFAULT 0,
                `name` varchar(255) NOT NULL DEFAULT '',
                `client_label` varchar(255) NOT NULL DEFAULT '',
                `date_creation` timestamp NOT NULL,
                `date_mod` timestamp NOT NULL,
                PRIMARY KEY (`id`),
                KEY `phase` (`esj_phases_id`),
                KEY `project` (`esj_projects_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            self::TABLE_STAGES => "CREATE TABLE `" . self::TABLE_STAGES . "` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `esj_projects_id` int unsigned NOT NULL DEFAULT 0,
                `esj_phases_id` int unsigned NOT NULL DEFAULT 0,
                `stage_key` varchar(80) NOT NULL DEFAULT '',
                `name` varchar(255) NOT NULL DEFAULT '',
                `status` varchar(80) NOT NULL DEFAULT 'planned',
                `date_creation` timestamp NOT NULL,
                `date_mod` timestamp NOT NULL,
                PRIMARY KEY (`id`),
                KEY `phase_stage` (`esj_phases_id`, `stage_key`),
                KEY `project` (`esj_projects_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            self::TABLE_ACTIVITIES => "CREATE TABLE `" . self::TABLE_ACTIVITIES . "` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `projecttasks_id` int unsigned NOT NULL DEFAULT 0,
                `esj_projects_id` int unsigned NOT NULL DEFAULT 0,
                `esj_phases_id` int unsigned NOT NULL DEFAULT 0,
                `esj_buildings_id` int unsigned NOT NULL DEFAULT 0,
                `esj_stages_id` int unsigned NOT NULL DEFAULT 0,
                `product` varchar(255) NOT NULL DEFAULT '',
                `name` varchar(255) NOT NULL DEFAULT '',
                `status` varchar(80) NOT NULL DEFAULT 'planned',
                `assigned_users_id` int unsigned NOT NULL DEFAULT 0,
                `date_creation` timestamp NOT NULL,
                `date_mod` timestamp NOT NULL,
                PRIMARY KEY (`id`),
                KEY `project` (`esj_projects_id`),
                KEY `phase` (`esj_phases_id`),
                KEY `assignee` (`assigned_users_id`),
                KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            self::TABLE_TEMPLATES => "CREATE TABLE `" . self::TABLE_TEMPLATES . "` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `code` varchar(120) NOT NULL DEFAULT '',
                `name` varchar(255) NOT NULL DEFAULT '',
                `kind` varchar(80) NOT NULL DEFAULT '',
                `payload` longtext NOT NULL,
                `is_active` tinyint NOT NULL DEFAULT 1,
                `date_creation` timestamp NOT NULL,
                `date_mod` timestamp NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `code` (`code`),
                KEY `kind` (`kind`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            self::TABLE_TEMPLATE_ACTIVITIES => "CREATE TABLE `" . self::TABLE_TEMPLATE_ACTIVITIES . "` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `templates_id` int unsigned NOT NULL DEFAULT 0,
                `stage_key` varchar(80) NOT NULL DEFAULT '',
                `name` varchar(255) NOT NULL DEFAULT '',
                `product` varchar(255) NOT NULL DEFAULT '',
                `sort_order` int unsigned NOT NULL DEFAULT 0,
                `date_creation` timestamp NOT NULL,
                `date_mod` timestamp NOT NULL,
                PRIMARY KEY (`id`),
                KEY `template` (`templates_id`),
                KEY `stage_key` (`stage_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            self::TABLE_GATES => "CREATE TABLE `" . self::TABLE_GATES . "` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `esj_projects_id` int unsigned NOT NULL DEFAULT 0,
                `gate_key` varchar(80) NOT NULL DEFAULT '',
                `status` varchar(80) NOT NULL DEFAULT 'blocked',
                `released_at` timestamp DEFAULT NULL,
                `date_creation` timestamp NOT NULL,
                `date_mod` timestamp NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `project_gate` (`esj_projects_id`, `gate_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            self::TABLE_EVENTS => "CREATE TABLE `" . self::TABLE_EVENTS . "` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `esj_projects_id` int unsigned NOT NULL DEFAULT 0,
                `esj_phases_id` int unsigned NOT NULL DEFAULT 0,
                `esj_activities_id` int unsigned NOT NULL DEFAULT 0,
                `tickets_id` int unsigned NOT NULL DEFAULT 0,
                `event_type` varchar(120) NOT NULL DEFAULT '',
                `event_at` timestamp NOT NULL,
                `users_id` int unsigned NOT NULL DEFAULT 0,
                `payload` longtext DEFAULT NULL,
                `date_creation` timestamp NOT NULL,
                PRIMARY KEY (`id`),
                KEY `project_event` (`esj_projects_id`, `event_type`, `event_at`),
                KEY `phase_event` (`esj_phases_id`, `event_at`),
                KEY `activity_event` (`esj_activities_id`, `event_at`),
                KEY `ticket_event` (`tickets_id`, `event_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            self::TABLE_TICKET_LINKS => "CREATE TABLE `" . self::TABLE_TICKET_LINKS . "` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `tickets_id` int unsigned NOT NULL DEFAULT 0,
                `esj_projects_id` int unsigned NOT NULL DEFAULT 0,
                `esj_phases_id` int unsigned NOT NULL DEFAULT 0,
                `esj_buildings_id` int unsigned NOT NULL DEFAULT 0,
                `esj_activities_id` int unsigned NOT NULL DEFAULT 0,
                `scope` varchar(80) NOT NULL DEFAULT 'phase',
                `impact` varchar(80) NOT NULL DEFAULT 'blocking',
                `status` varchar(80) NOT NULL DEFAULT 'open',
                `date_creation` timestamp NOT NULL,
                `date_mod` timestamp NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `tickets_id` (`tickets_id`),
                KEY `project_scope` (`esj_projects_id`, `scope`, `impact`, `status`),
                KEY `phase` (`esj_phases_id`),
                KEY `activity` (`esj_activities_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        ];
    }
}
