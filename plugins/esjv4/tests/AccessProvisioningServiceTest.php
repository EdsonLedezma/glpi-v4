<?php

declare(strict_types=1);

use GlpiPlugin\Esjv4\AccessProvisioningService;

esjv4_assert_true(class_exists(AccessProvisioningService::class), 'AccessProvisioningService class must exist');

$groups = AccessProvisioningService::defaultGroups();
$rights = AccessProvisioningService::profileRights();

esjv4_assert_true(in_array('ESJ - Planeacion', $groups, true), 'Default groups must include Planeacion');
esjv4_assert_true(in_array('ESJ - Diseno Estructural', $groups, true), 'Default groups must include structural design');
esjv4_assert_true(in_array('ESJ - Modelado de Conexiones', $groups, true), 'Default groups must include connection modeling');
esjv4_assert_true(in_array('ESJ - Project Managers', $groups, true), 'Default groups must include PMs');

esjv4_assert_true(isset($rights['ticket']), 'ESJ profile must allow tickets for RFIs');
esjv4_assert_true(isset($rights['project']), 'ESJ profile must allow GLPI projects');
esjv4_assert_true(isset($rights['projecttask']), 'ESJ profile must allow project tasks');
esjv4_assert_true(isset($rights['planning']), 'ESJ profile must allow planning access');
esjv4_assert_true(!isset($rights['computer']), 'ESJ profile must not include asset computer rights');
esjv4_assert_true(!isset($rights['software']), 'ESJ profile must not include software rights');
