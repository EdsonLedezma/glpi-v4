<?php

declare(strict_types=1);

use GlpiPlugin\Esjv4\PlanningInput;

esjv4_assert_true(class_exists(PlanningInput::class), 'PlanningInput class must exist');

$input = PlanningInput::normalize([
    'project_code' => ' EAA260102 ',
    'project_name' => ' MBA San Rafael ',
    'customer_name' => ' Punto Estructural ',
    'quotation_code' => ' 0020033984 ',
    'location' => ' Guadalajara, Jalisco ',
    'phase_slots' => [
        [
            'slot' => '1',
            'name' => ' Fase cimentacion ',
            'enabled' => '1',
            'activity_template_keys' => ['core_engineering'],
            'planned_start' => '2026-06-10',
            'planned_end' => '2026-06-20',
        ],
        [
            'slot' => '2',
            'name' => ' Fase vacia ',
            'enabled' => '0',
            'activity_template_keys' => ['core_engineering'],
        ],
        [
            'slot' => '3',
            'name' => ' Fase estructura ',
            'enabled' => '1',
            'activity_template_keys' => [],
        ],
    ],
]);

esjv4_assert_same('EAA260102', $input['project_code'], 'Project code must be trimmed');
esjv4_assert_same('MBA San Rafael', $input['project_name'], 'Project name must be trimmed');
esjv4_assert_same('Punto Estructural', $input['customer_name'], 'Customer must be trimmed');
esjv4_assert_same('0020033984', $input['quotation_code'], 'Quotation must be trimmed');
esjv4_assert_same('Guadalajara, Jalisco', $input['location'], 'Location must be trimmed');
esjv4_assert_same(2, count($input['phase_slots']), 'Only enabled phase slots are returned');
esjv4_assert_same(1, $input['phase_slots'][0]['slot'], 'Phase slot is normalized to integer');
esjv4_assert_same('Fase cimentacion', $input['phase_slots'][0]['name'], 'Phase name is trimmed');
esjv4_assert_same(['core_engineering'], $input['phase_slots'][0]['activity_template_keys'], 'Template keys are preserved');
esjv4_assert_same('2026-06-10', $input['phase_slots'][0]['planned_start'], 'Planned start is preserved');
esjv4_assert_same('', $input['phase_slots'][1]['planned_start'], 'Missing planned start defaults to empty string');

$errors = PlanningInput::validate($input);
esjv4_assert_same([], $errors, 'Valid planning input has no validation errors');

$invalid = PlanningInput::normalize([
    'project_code' => '',
    'project_name' => '',
    'phase_slots' => [],
]);

esjv4_assert_same(
    ['project_code', 'project_name', 'phase_slots'],
    array_keys(PlanningInput::validate($invalid)),
    'Validation must require code, name, and at least one phase'
);
