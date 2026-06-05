# ESJ Planning SAP Inbox Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the rough phase checkbox form with a SAP-driven planning inbox where planners complete buildings, phases, dates, and compact activity packages.

**Architecture:** SAP mail parsing creates or updates an ESJ project in `pending_planning`. Planning completion persists gate stages, buildings, phases, and template activities in one service transaction-style flow. The UI becomes an inbox plus a focused planning form for one project instead of a blank project creation grid.

**Tech Stack:** GLPI 11 plugin PHP, existing ESJ tables, GLPI Project/ProjectTask APIs, plain GLPI/Twig-style PHP front pages, local PHP test runner.

---

### Task 1: Planning Data Contracts

**Files:**
- Modify: `plugins/esjv4/src/Catalog.php`
- Modify: `plugins/esjv4/src/PlanningInput.php`
- Modify: `plugins/esjv4/src/ProjectPlanBuilder.php`
- Test: `plugins/esjv4/tests/PlanningInputTest.php`
- Test: `plugins/esjv4/tests/ProjectPlanBuilderTest.php`

- [ ] Add `pending_planning` and compact phase package keys.
- [ ] Normalize `buildings[]` with `name` and `client_label`.
- [ ] Normalize `phase_slots[]` with `building_key` and one `activity_package_key`.
- [ ] Build activities from a package instead of repeated checkbox keys.

### Task 2: SAP Intake Service

**Files:**
- Create: `plugins/esjv4/src/SapIntakeService.php`
- Modify: `plugins/esjv4/src/PlanningRepository.php`
- Test: `plugins/esjv4/tests/SapIntakeServiceTest.php`
- Test: `plugins/esjv4/tests/PlanningRepositoryTest.php`

- [ ] Write a failing test that `receiveMail()` parses SAP content and creates a pending planning project.
- [ ] Add repository methods `findProjectByCode()`, `findProjectByRawHash()`, and `createProjectFromSap()`.
- [ ] Make intake idempotent by raw hash/project code.

### Task 3: Complete Planning Service

**Files:**
- Modify: `plugins/esjv4/src/PlanningService.php`
- Modify: `plugins/esjv4/src/PlanningRepository.php`
- Test: `plugins/esjv4/tests/PlanningServiceTest.php`

- [ ] Change planning from creating a blank project to completing an existing SAP project.
- [ ] Add `createBuilding()` and pass building IDs into phases/activities.
- [ ] Create gate stages and construction phases only after Planeación submits the planning form.
- [ ] Record a `planning_completed` event.

### Task 4: Planning UI

**Files:**
- Modify: `plugins/esjv4/front/planning.php`
- Modify: `plugins/esjv4/front/projects.php`
- Modify: `plugins/esjv4/front/project.php`

- [ ] Show a SAP pending projects inbox when no `project_id` is selected.
- [ ] Add a development/manual SAP email capture form on the inbox so the parser path is testable before mailbox automation.
- [ ] Replace repeated checkboxes with a compact package selector per phase.
- [ ] Add a buildings section before phases.
- [ ] Show building names in project detail phases and activities.

### Task 5: Verification

**Files:**
- Run tests only.

- [ ] Run `docker compose exec -T glpi php /var/www/glpi/plugins/esjv4/tests/run.php`.
- [ ] Run PHP lint for every plugin PHP file.
- [ ] Confirm `plugin:list` still shows `esjv4` enabled.
- [ ] Browser-check `/plugins/esjv4/front/planning.php` for GLPI protected load without fatal errors.
- [ ] Commit and push to `feature/esj-v4-foundation`.
