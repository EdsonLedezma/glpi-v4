<?php

declare(strict_types=1);

use GlpiPlugin\Esjv4\PlanningRepository;
use GlpiPlugin\Esjv4\Schema;

esjv4_assert_true(class_exists(PlanningRepository::class), 'PlanningRepository class must exist');

$map = PlanningRepository::requiredTablesByMethod();

esjv4_assert_same(Schema::TABLE_PROJECTS, $map['createProject'], 'createProject writes ESJ projects');
esjv4_assert_same(Schema::TABLE_STAGES, $map['createStage'], 'createStage writes ESJ stages');
esjv4_assert_same(Schema::TABLE_PHASES, $map['createPhase'], 'createPhase writes ESJ phases');
esjv4_assert_same(Schema::TABLE_ACTIVITIES, $map['createActivity'], 'createActivity writes ESJ activities');
esjv4_assert_same(Schema::TABLE_EVENTS, $map['recordEvent'], 'recordEvent writes ESJ events');
esjv4_assert_same(Schema::TABLE_STAGES, $map['constructionReleaseContext'], 'constructionReleaseContext reads ESJ stages');
esjv4_assert_same(Schema::TABLE_PHASES, $map['activateConstructionPhases'], 'activateConstructionPhases updates ESJ phases');
