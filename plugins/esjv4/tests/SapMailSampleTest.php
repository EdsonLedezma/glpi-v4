<?php

declare(strict_types=1);

use GlpiPlugin\Esjv4\SapMailParser;
use GlpiPlugin\Esjv4\SapMailSample;

esjv4_assert_true(class_exists(SapMailSample::class), 'SapMailSample class must exist');

$sample = SapMailSample::mbaSanRafael();
$parsed = (new SapMailParser())->parse($sample);

esjv4_assert_true(str_contains($sample, 'EAA2506 MBA SAN RAFAEL'), 'Sample keeps the SAP observation example');
esjv4_assert_true(str_contains($sample, 'Cotización: 0020033984'), 'Sample keeps accented SAP labels');
esjv4_assert_true($parsed !== null, 'SAP sample must parse');
esjv4_assert_same('EAA2506', $parsed['project_code'], 'Sample project code parses');
esjv4_assert_same('MBA SAN RAFAEL', $parsed['project_name'], 'Sample project name parses');
esjv4_assert_same('0020033984', $parsed['quotation_code'], 'Sample quotation parses');
