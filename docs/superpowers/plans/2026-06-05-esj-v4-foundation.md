# ESJ V4 Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the first professional foundation of the new `esjv4` GLPI plugin from scratch.

**Architecture:** The plugin owns the ESJ domain model in dedicated tables and references GLPI projects, project tasks, tickets, users, groups, and documents. The first milestone installs the plugin, creates schema, exposes a minimal ESJ menu, defines default templates/gates, and includes CLI tests that can run inside the GLPI container.

**Tech Stack:** GLPI 11 plugin PHP, MySQL 8.4, Docker Compose, plain PHP CLI tests executed in the GLPI container.

---

## File Structure

- `plugins/esjv4/setup.php`: GLPI plugin metadata, initialization hooks, autoload, menu registration.
- `plugins/esjv4/hook.php`: install/uninstall entrypoints.
- `plugins/esjv4/src/Plugin.php`: constants, path helpers, base bootstrap.
- `plugins/esjv4/src/Schema.php`: creates and validates ESJ V4 tables.
- `plugins/esjv4/src/Catalog.php`: canonical stages, statuses, RFI scopes, impacts, and default activity templates.
- `plugins/esjv4/src/EventLog.php`: append-only 24/7 event recorder and duration calculator primitives.
- `plugins/esjv4/src/TemplateService.php`: seeds default project/activity templates.
- `plugins/esjv4/src/ProjectPlanBuilder.php`: builds the initial ESJ plan from selected phase slots and activity templates.
- `plugins/esjv4/src/GateService.php`: gate rules for construction release.
- `plugins/esjv4/src/Menu.php`: ESJ menu shell.
- `plugins/esjv4/front/dashboard.php`: first landing page confirming plugin health.
- `plugins/esjv4/front/templates.php`: simple read-only view of default templates.
- `plugins/esjv4/tests/run.php`: standalone test runner for pure domain logic.
- `plugins/esjv4/tests/CatalogTest.php`: validates catalog and defaults.
- `plugins/esjv4/tests/PluginTest.php`: validates plugin identity and version metadata.
- `plugins/esjv4/tests/SchemaTest.php`: validates table definitions and GLPI-compatible timestamp fields.
- `plugins/esjv4/tests/TemplateServiceTest.php`: validates the default 20-phase project template.
- `plugins/esjv4/tests/ProjectPlanBuilderTest.php`: validates selected phases, preloaded activities, and blocked construction phases.
- `plugins/esjv4/tests/GateServiceTest.php`: validates construction release gate logic.
- `plugins/esjv4/tests/EventLogTest.php`: validates duration math in seconds using calendar time.

## Task 1: Scaffold Plugin And Metadata

- [ ] Create `setup.php`, `hook.php`, and `src/Plugin.php`.
- [ ] Register GLPI compatibility `11.0.0` to `11.0.99`.
- [ ] Add a small autoloader for `GlpiPlugin\Esjv4`.
- [ ] Verify GLPI can see the plugin files.

Run:

```powershell
docker compose exec glpi php -r "require '/var/www/glpi/plugins/esjv4/setup.php'; echo plugin_version_esjv4()['name'];"
```

Expected:

```text
ESJ V4
```

## Task 2: Add Domain Catalog

- [ ] Create `src/Catalog.php`.
- [ ] Define the three gate stages: planning, structural_design, connection_modeling.
- [ ] Define downstream core stages: fabrication_drawings, erection_drawings, submittals, requisitions, engineering_closeout.
- [ ] Define phase/task statuses and RFI scopes/impacts.
- [ ] Define default activity templates used by Planeacion.
- [ ] Write catalog tests.

Run:

```powershell
docker compose exec glpi php /var/www/glpi/plugins/esjv4/tests/run.php
```

Expected:

```text
PASS CatalogTest
```

## Task 3: Add Schema

- [ ] Create `src/Schema.php`.
- [ ] Create tables for projects, phases, buildings, stages, activities, templates, gate states, events, and ticket links.
- [ ] Install hook calls `Schema::install()`.
- [ ] Uninstall hook leaves data intact for now and returns true.
- [ ] Add schema health method for dashboard.

Run:

```powershell
docker compose exec glpi php -r "require '/var/www/glpi/plugins/esjv4/setup.php'; var_export(plugin_esjv4_install());"
```

Expected:

```text
true
```

## Task 4: Add Event Timing Core

- [ ] Create `src/EventLog.php`.
- [ ] Add immutable event type constants.
- [ ] Add pure `durationSeconds($start, $end)` calendar-time calculation.
- [ ] Add pure `sumIntervals($intervals)` helper.
- [ ] Add tests for 24/7 timing, including overnight/weekend-style intervals with no exclusion.

Run:

```powershell
docker compose exec glpi php /var/www/glpi/plugins/esjv4/tests/run.php
```

Expected:

```text
PASS EventLogTest
```

## Task 5: Add Gate Logic

- [ ] Create `src/GateService.php`.
- [ ] Gate opens only when planning, structural_design, and connection_modeling are closed.
- [ ] Gate stays blocked if any required stage is not closed.
- [ ] Gate stays blocked if a blocking RFI exists at project, phase, task, building, or product scope.
- [ ] Write tests for open, missing-stage blocked, and blocking-RFI blocked cases.

Run:

```powershell
docker compose exec glpi php /var/www/glpi/plugins/esjv4/tests/run.php
```

Expected:

```text
PASS GateServiceTest
```

## Task 6: Add Template Seeding

- [ ] Create `src/TemplateService.php`.
- [ ] Seed one default project template with 20 possible phases.
- [ ] Seed default activity templates for the core stages.
- [ ] Ensure seeding is idempotent.
- [ ] Call seeding during install.

Run:

```powershell
docker compose exec glpi php -r "require '/var/www/glpi/plugins/esjv4/setup.php'; plugin_esjv4_install(); echo 'ok';"
```

Expected:

```text
ok
```

## Task 7: Add Minimal ESJ Views

- [ ] Create `src/Menu.php`.
- [ ] Add dashboard and template pages.
- [ ] Dashboard shows plugin version and schema health.
- [ ] Templates page lists default core stages and default activities.
- [ ] Keep UI simple and operational, not decorative.

Manual check:

```text
http://localhost:8084/plugins/esjv4/front/dashboard.php
http://localhost:8084/plugins/esjv4/front/templates.php
```

Expected: pages render without PHP fatal errors.

## Task 8: Verify And Commit

- [ ] Run all CLI tests.
- [ ] Run plugin install command.
- [ ] Check HTTP status for dashboard.
- [ ] Review `git status`.
- [ ] Commit foundation.

Run:

```powershell
docker compose exec glpi php /var/www/glpi/plugins/esjv4/tests/run.php
docker compose exec glpi php -r "require '/var/www/glpi/plugins/esjv4/setup.php'; var_export(plugin_esjv4_install());"
Invoke-WebRequest -UseBasicParsing http://localhost:8084/plugins/esjv4/front/dashboard.php
```

Expected:

```text
All tests passed
true
HTTP 200
```

## Self-Review

- Spec coverage: this plan covers the first implementable scope from the design: plugin base, tables, menu, templates, gate, RFI blocking logic primitives, and event timing.
- Deferred intentionally: SAP parser, full project creation workflow, role-specific kanbans, PDF/Gantt date import, and GLPI profile hardening. These require the foundation tables and services first.
- Placeholder scan: no implementation step depends on undefined TODO behavior.
- Type consistency: all domain services use the `GlpiPlugin\Esjv4` namespace and are referenced by explicit file paths above.
