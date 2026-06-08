<?php

declare(strict_types=1);

use GlpiPlugin\Esjv4\Catalog;
use GlpiPlugin\Esjv4\KanbanService;

esjv4_assert_true(class_exists(KanbanService::class), 'KanbanService class must exist');

$repo = new class {
    public function project(int $project_id): array
    {
        return ['id' => $project_id, 'project_name' => 'MBA San Rafael'];
    }

    public function buildings(int $project_id): array
    {
        return [
            ['id' => 10, 'name' => 'Nave A', 'client_label' => 'Edificio A1'],
        ];
    }

    public function phases(int $project_id): array
    {
        return [
            ['id' => 20, 'slot' => 1, 'name' => 'Fase 01', 'esj_buildings_id' => 10],
        ];
    }

    public function activities(int $project_id): array
    {
        return [
            [
                'id' => 100,
                'name' => 'Modelo de conexiones',
                'status' => Catalog::STATUS_IN_PROGRESS,
                'esj_phases_id' => 20,
                'esj_buildings_id' => 10,
                'product' => 'Columnas',
                'assigned_users_id' => 4,
            ],
            [
                'id' => 101,
                'name' => 'Submittal',
                'status' => Catalog::STATUS_BLOCKED,
                'esj_phases_id' => 20,
                'esj_buildings_id' => 10,
                'product' => '',
                'assigned_users_id' => 0,
            ],
        ];
    }
};

$board = (new KanbanService($repo))->projectBoard(7);

esjv4_assert_same('MBA San Rafael', $board['project']['project_name'], 'Board includes project header');
esjv4_assert_true(isset($board['columns'][Catalog::STATUS_BLOCKED]), 'Board includes blocked column');
esjv4_assert_true(isset($board['columns'][Catalog::STATUS_IN_PROGRESS]), 'Board includes in progress column');
esjv4_assert_same(1, count($board['columns'][Catalog::STATUS_IN_PROGRESS]['cards']), 'In progress column has expected card');

$card = $board['columns'][Catalog::STATUS_IN_PROGRESS]['cards'][0];
esjv4_assert_same(100, $card['id'], 'Card includes activity id');
esjv4_assert_same('Fase 01', $card['phase_name'], 'Card includes phase name');
esjv4_assert_same('Nave A / Edificio A1', $card['building_name'], 'Card includes building display name');
esjv4_assert_same('Columnas', $card['product'], 'Card includes product');
esjv4_assert_same(4, $card['assigned_users_id'], 'Card includes assigned user id');
