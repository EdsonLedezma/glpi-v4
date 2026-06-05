<?php

declare(strict_types=1);

use GlpiPlugin\Esjv4\TemplateService;

esjv4_assert_true(class_exists(TemplateService::class), 'TemplateService class must exist');

$template = TemplateService::defaultProjectTemplate();

esjv4_assert_same(20, $template['max_phases'], 'Default project template must offer 20 possible phases');
esjv4_assert_same('Plantilla ESJ V4 General', $template['name'], 'Default project template must have a stable name');
esjv4_assert_true(count($template['phase_slots']) === 20, 'Default template must define 20 phase slots');
esjv4_assert_same('Fase 01', $template['phase_slots'][0]['name'], 'First phase slot must be Fase 01');
esjv4_assert_same('Fase 20', $template['phase_slots'][19]['name'], 'Last phase slot must be Fase 20');
