<?php

declare(strict_types=1);

namespace GlpiPlugin\Esjv4;

final class AccessProvisioningService
{
    public const PROFILE_NAME = 'ESJ Ingenieria';
    public const ROOT_GROUP = 'ESJ';

    public static function defaultGroups(): array
    {
        return [
            'ESJ - Project Managers',
            'ESJ - Lider Ingenieria',
            'ESJ - Planeacion',
            'ESJ - Diseno Estructural',
            'ESJ - Modelado de Conexiones',
            'ESJ - Planos Fabricacion',
            'ESJ - Planos Montaje',
            'ESJ - Submittals',
            'ESJ - Requisiciones',
            'ESJ - Ingenieros',
        ];
    }

    public static function profileRights(): array
    {
        return [
            'ticket' => 429063,
            'project' => 1151,
            'projecttask' => 1025,
            'planning' => 1,
            'document' => 127,
            'dashboard' => 1,
        ];
    }

    public static function provision(): bool
    {
        global $DB;

        if (!isset($DB) || !is_object($DB)) {
            return true;
        }

        $profile_id = self::ensureProfile($DB);
        self::ensureProfileRights($DB, $profile_id);
        self::ensureGroups($DB);

        return true;
    }

    private static function ensureProfile(object $DB): int
    {
        $existing = self::findIdByName($DB, 'glpi_profiles', self::PROFILE_NAME);
        if ($existing > 0) {
            return $existing;
        }

        $now = date('Y-m-d H:i:s');
        $DB->insert('glpi_profiles', [
            'name' => self::PROFILE_NAME,
            'interface' => 'central',
            'is_default' => 0,
            'comment' => 'Perfil operativo limitado para usuarios del sistema ESJ V4.',
            'date_creation' => $now,
            'date_mod' => $now,
        ]);

        return (int) $DB->insertId();
    }

    private static function ensureProfileRights(object $DB, int $profile_id): void
    {
        foreach (self::profileRights() as $name => $rights) {
            $iterator = $DB->request([
                'FROM' => 'glpi_profilerights',
                'WHERE' => [
                    'profiles_id' => $profile_id,
                    'name' => $name,
                ],
                'LIMIT' => 1,
            ]);

            $exists = false;
            foreach ($iterator as $row) {
                $exists = true;
                $DB->update(
                    'glpi_profilerights',
                    ['rights' => $rights],
                    ['id' => (int) $row['id']]
                );
            }

            if (!$exists) {
                $DB->insert('glpi_profilerights', [
                    'profiles_id' => $profile_id,
                    'name' => $name,
                    'rights' => $rights,
                ]);
            }
        }
    }

    private static function ensureGroups(object $DB): void
    {
        $root_id = self::ensureGroup($DB, self::ROOT_GROUP, 0);

        foreach (self::defaultGroups() as $group_name) {
            self::ensureGroup($DB, $group_name, $root_id);
        }
    }

    private static function ensureGroup(object $DB, string $name, int $parent_id): int
    {
        $existing = self::findIdByName($DB, 'glpi_groups', $name);
        if ($existing > 0) {
            return $existing;
        }

        $now = date('Y-m-d H:i:s');
        $completename = $parent_id > 0 ? self::ROOT_GROUP . ' > ' . $name : $name;
        $DB->insert('glpi_groups', [
            'entities_id' => 0,
            'is_recursive' => 1,
            'name' => $name,
            'comment' => 'Grupo creado por ESJ V4.',
            'groups_id' => $parent_id,
            'completename' => $completename,
            'level' => $parent_id > 0 ? 2 : 1,
            'is_requester' => 1,
            'is_watcher' => 1,
            'is_assign' => 1,
            'is_task' => 1,
            'is_notify' => 1,
            'is_itemgroup' => 1,
            'is_usergroup' => 1,
            'is_manager' => 0,
            'date_creation' => $now,
            'date_mod' => $now,
        ]);

        return (int) $DB->insertId();
    }

    private static function findIdByName(object $DB, string $table, string $name): int
    {
        $iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM' => $table,
            'WHERE' => ['name' => $name],
            'LIMIT' => 1,
        ]);

        foreach ($iterator as $row) {
            return (int) $row['id'];
        }

        return 0;
    }
}
