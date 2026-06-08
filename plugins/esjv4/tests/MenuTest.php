<?php

declare(strict_types=1);

use GlpiPlugin\Esjv4\Menu;

esjv4_assert_true(class_exists(Menu::class), 'Menu class must exist');

$menu = Menu::getMenuContent();

esjv4_assert_same(true, $menu['is_multi_entries'], 'ESJ menu must expose multiple sidebar entries');
esjv4_assert_true(isset($menu['esjv4_projects']), 'Menu must include project access');
esjv4_assert_true(isset($menu['esjv4_planning']), 'Menu must include SAP planning access');
esjv4_assert_true(isset($menu['esjv4_kanban']), 'Menu must include Kanban access');
esjv4_assert_true(isset($menu['esjv4_rfis']), 'Menu must include RFI access');
esjv4_assert_true(isset($menu['esjv4_times']), 'Menu must include time report access');
esjv4_assert_same('ESJ Proyectos', $menu['esjv4_projects']['title'], 'Project menu title must be meaningful');
esjv4_assert_same('/plugins/esjv4/front/planning.php', $menu['esjv4_planning']['page'], 'Planning menu points to planning inbox');
