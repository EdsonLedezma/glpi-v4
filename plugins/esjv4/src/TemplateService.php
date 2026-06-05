<?php

declare(strict_types=1);

namespace GlpiPlugin\Esjv4;

final class TemplateService
{
    public static function defaultProjectTemplate(): array
    {
        $phase_slots = [];

        for ($slot = 1; $slot <= 20; $slot++) {
            $phase_slots[] = [
                'slot' => $slot,
                'name' => sprintf('Fase %02d', $slot),
                'is_active_by_default' => false,
            ];
        }

        return [
            'code' => 'ESJ-GENERAL-20',
            'name' => 'Plantilla ESJ V4 General',
            'max_phases' => 20,
            'phase_slots' => $phase_slots,
            'activity_templates' => Catalog::defaultActivityTemplates(),
        ];
    }

    public static function seedDefaults(): bool
    {
        global $DB;

        if (!isset($DB) || !method_exists($DB, 'tableExists')) {
            return true;
        }

        $table = Schema::TABLE_TEMPLATES;
        if (!$DB->tableExists($table)) {
            return true;
        }

        $template = self::defaultProjectTemplate();
        $existing = $DB->request([
            'FROM' => $table,
            'WHERE' => ['code' => $template['code']],
            'LIMIT' => 1,
        ]);

        foreach ($existing as $_row) {
            return true;
        }

        return (bool) $DB->insert($table, [
            'code' => $template['code'],
            'name' => $template['name'],
            'kind' => 'project',
            'payload' => json_encode($template, JSON_THROW_ON_ERROR),
            'is_active' => 1,
            'date_creation' => date('Y-m-d H:i:s'),
            'date_mod' => date('Y-m-d H:i:s'),
        ]);
    }
}
