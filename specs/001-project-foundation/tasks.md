# Tasks: Dropdown Lists Fix & Environment Setup

**Feature**: 001-project-foundation  
**Date**: 2026-06-07  
**Status**: Ready for implementation

**Input**: UPapp is in production but dropdown lists broken; need to fix CORS and set up dev/uat environments

**Organization**: Tasks organized by urgency - production fix first, then environment setup

## Format: `[ID] [P?] [Story] Description`
- **[P]**: Can run in parallel  
- **[Story]**: PROD1 (prod fix), DEV1 (dev setup), UAT1 (uat setup), CONFIG1 (config), TEST1 (tests)

---

## Phase 1: Production Fix (URGENT)

**Purpose**: Fix broken dropdown lists in production

- [X] T001 [PROD1] Update CORS middleware to include https://przetargr-domow.pl in backend/src/Middleware/CorsMiddleware.php line 32
- [X] T002 [PROD1] Verify production .env has APP_ENV=prod and APP_URL=https://przetargr-domow.pl
- [X] T003 [PROD1] Update deploy.sh line 9 to use VITE_API_URL=https://przetargr-domow.pl/api
- [ ] T004 [PROD1] Deploy updated backend to production via npm run deploy
- [ ] T005 [PROD1] Test CORS headers with curl on production API
- [ ] T006 [PROD1] Verify dropdown lists load in all production forms (DUP, TUP, DOS, OK10)

**Checkpoint**: Production dropdowns working

---

## Phase 2: Dev Environment Setup

**Purpose**: Configure development environment properly

- [X] T007 [DEV1] Verify .env has APP_ENV=dev
- [X] T008 [DEV1] Verify DynamoDB dev tables exist: UpApp.dev.users, UpApp.dev.forms
- [X] T009 [DEV1] Test local servers start and dropdowns load
- [X] T010 [DEV1] Update quickstart.md with dev setup instructions

**Checkpoint**: Dev environment functional

---

## Phase 3: UAT Environment Setup

**Purpose**: Configure UAT environment for testing

- [X] T011 [P] [UAT1] Create .env.uat template file
- [X] T012 [P] [UAT1] Document UAT setup in quickstart.md
- [X] T013 [UAT1] Verify DynamoDB UAT tables exist
- [X] T014 [UAT1] Test UAT environment and dropdowns

**Checkpoint**: UAT environment functional

---

## Phase 4: Testing & Documentation

**Purpose**: Add tests and complete documentation

- [X] T015 [P] [TEST1] Setup PHPUnit in backend/tests/
- [X] T016 [P] [TEST1] Write test for ReferenceHandler::getFeelings()
- [X] T017 [P] [TEST1] Write test for CollapsibleList component
- [X] T018 [P] Update README.md with troubleshooting
- [X] T019 Run full test suite

---

## Execution Order

**Critical Path**: T001 → T004 → T005 → T006 (30 min to fix production)

**Parallel After Phase 1**: DEV1 (T007-T010), UAT1 (T011-T014), TEST1 (T015-T019) can all run in parallel

**Total Time**: 35 min (production fix) + 3 hours (environments & tests)

---

**Total Tasks**: 19 | **MVP**: Phase 1 only (fix production)

**Input**: Design documents from `specs/001-project-foundation/`

**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/

**Tests**: Constitution requires test-first development. Tests marked for all implementation tasks.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3, US4)
- Include exact file paths in descriptions

## Path Conventions

- Web app structure: `frontend/`, `backend/`, `scripts/` at repository root
- Frontend: `frontend/src/`, `frontend/public/`
- Backend: `backend/src/`, `backend/public/`, `backend/tests/`

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Project initialization and basic structure

- [ ] T001 Create root directory structure (frontend/, backend/, scripts/, docs/)
- [ ] T002 Initialize Git repository if not already initialized
- [ ] T003 Create `.gitignore` excluding .env, node_modules, vendor, dist, backend/cache
- [ ] T004 [P] Create `.env_dist` template with documented environment variables per research.md
- [ ] T005 [P] Create `README.md` with setup instructions referencing quickstart.md
- [ ] T006 [P] Initialize root `package.json` with npm scripts (gui, backend, db:sync, db:seed, build, deploy)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure that MUST be complete before ANY user story can be implemented

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

### Testing Foundation

- [ ] T007 [P] Configure Vitest in frontend/vitest.config.ts
- [ ] T008 [P] Configure PHPUnit in backend/phpunit.xml with test database settings
- [ ] T009 [P] Add test npm scripts (test, test:watch, test:coverage) to root package.json

### Frontend Foundation

- [ ] T010 Initialize frontend React + Vite project in frontend/ directory
- [ ] T011 [P] Configure TypeScript in frontend/tsconfig.json and frontend/tsconfig.node.json
- [ ] T012 [P] Install PrimeReact and PrimeIcons dependencies in frontend/package.json
- [ ] T013 [P] Install Font Awesome dependencies (@fortawesome/fontawesome-free) in frontend/package.json
- [ ] T014 [P] Install Axios and React Router dependencies in frontend/package.json
- [ ] T015 [P] Configure Vite in frontend/vite.config.ts (dev server port 5173, code splitting, build output)
- [ ] T016 [P] Create frontend/src/main.tsx entry point with PrimeReact CSS imports
- [ ] T017 [P] Create frontend/src/App.tsx root component with basic layout

### Backend Foundation

- [ ] T018 Initialize Composer project in backend/ directory
- [ ] T019 [P] Add Slim Framework 4 to backend/composer.json
- [ ] T020 [P] Add AWS SDK for PHP (DynamoDB client) to backend/composer.json
- [ ] T021 [P] Add vlucas/phpdotenv to backend/composer.json
- [ ] T022 [P] Add PHPUnit to backend/composer.json (dev dependency)
- [ ] T023 Run `composer install` to install all backend dependencies
- [ ] T024 Create backend/public/index.php entry point with Slim app initialization
- [ ] T025 [P] Create backend/src/Config/Environment.php class to load and validate .env variables
- [ ] T026 [P] Create backend/src/Middleware/CorsMiddleware.php for CORS handling
- [ ] T027 Create backend/src/routes.php to register all API routes

### Code Quality Foundation

- [ ] T028 [P] Configure ESLint in frontend/.eslintrc.json (TypeScript + React rules)
- [ ] T029 [P] Configure Prettier in frontend/.prettierrc (2-space, single quotes, trailing commas)
- [ ] T030 [P] Add PHP_CodeSniffer to backend/composer.json (PSR-12 ruleset)
- [ ] T031 [P] Add lint npm scripts (lint, lint:fix, format) to root package.json
- [ ] T032 [P] Install husky and lint-staged for pre-commit hooks
- [ ] T033 Configure pre-commit hook to run ESLint + Prettier on staged frontend files
- [ ] T034 Configure pre-commit hook to run PHP_CodeSniffer on staged backend files

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel

---

## Phase 3: User Story 1 - Local Development Environment Setup (Priority: P1) 🎯 MVP

**Goal**: Developer can clone repo, run setup commands, and have both frontend and backend running locally with DynamoDB connectivity

**Independent Test**: Clone repo → npm install → configure .env → npm run db:sync → npm run gui + npm run backend → verify health check returns healthy

### Tests for User Story 1 ⚠️

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [ ] T035 [P] [US1] Integration test for DynamoDB connection in backend/tests/Integration/DynamoDBConnectionTest.php
- [ ] T036 [P] [US1] Integration test for health check endpoint in backend/tests/Integration/HealthCheckTest.php
- [ ] T037 [P] [US1] Frontend integration test for API connectivity in frontend/src/tests/integration/api-connection.test.ts

### Implementation for User Story 1

- [ ] T038 [P] [US1] Create DynamoDB config table AWS CLI script in scripts/aws-setup.sh (bash version)
- [ ] T039 [P] [US1] Create DynamoDB config table AWS CLI script in scripts/aws-setup.ps1 (PowerShell version)
- [ ] T040 [US1] Wire `db:sync` npm script to detect platform and run appropriate setup script
- [ ] T041 [P] [US1] Implement DynamoDB client initialization in backend/src/Config/DynamoDBClient.php
- [ ] T042 [US1] Implement health check route handler in backend/src/routes.php (GET /api/health)
- [ ] T043 [US1] Implement health check logic in backend/src/Handlers/HealthCheckHandler.php (check API + DynamoDB)
- [ ] T044 [P] [US1] Create Axios client configuration in frontend/src/services/api.ts (base URL from env)
- [ ] T045 [US1] Add health check API call to frontend startup in frontend/src/App.tsx
- [ ] T046 [US1] Wire `gui` npm script to run `vite` from frontend/package.json
- [ ] T047 [US1] Wire `backend` npm script to run `php -S localhost:8080 -t backend/public`
- [ ] T048 [US1] Verify all tests pass (T035-T037)

**Checkpoint**: At this point, User Story 1 should be fully functional and testable independently

---

## Phase 4: User Story 2 - Environment Configuration Management (Priority: P2)

**Goal**: Application supports dev/uat/prod environments via single APP_ENV variable without code changes

**Independent Test**: Change APP_ENV in .env → restart servers → verify table prefixes and config update automatically

### Tests for User Story 2 ⚠️

- [ ] T049 [P] [US2] Unit test for Environment class in backend/tests/Unit/Config/EnvironmentTest.php
- [ ] T050 [P] [US2] Integration test for environment-specific table naming in backend/tests/Integration/EnvironmentTableNamingTest.php

### Implementation for User Story 2

- [ ] T051 [US2] Implement environment variable validation in backend/src/Config/Environment.php (check required vars, fail fast)
- [ ] T052 [US2] Implement table name builder in backend/src/Config/Environment.php (construct UpApp.<ENV>.tablename)
- [ ] T053 [US2] Add environment validation to backend startup in backend/public/index.php
- [ ] T054 [US2] Add environment-based API URL to frontend Vite config (VITE_API_URL)
- [ ] T055 [US2] Update Axios client to read API URL from environment in frontend/src/services/api.ts
- [ ] T056 [US2] Add error handling for missing env vars in backend/public/index.php (display clear error messages)
- [ ] T057 [US2] Update `db:sync` scripts to respect APP_ENV and create tables for correct environment
- [ ] T058 [US2] Verify all tests pass (T049-T050)

**Checkpoint**: At this point, User Stories 1 AND 2 should both work independently

---

## Phase 5: User Story 3 - Code Quality Enforcement (Priority: P3)

**Goal**: Automated linting and formatting catch issues before commit

**Independent Test**: Introduce linting errors → run linters → verify errors caught → attempt commit → verify pre-commit hook blocks it

### Tests for User Story 3 ⚠️

- [ ] T059 [P] [US3] Test script to verify ESLint catches common TypeScript violations in scripts/test-linting.sh
- [ ] T060 [P] [US3] Test script to verify Prettier auto-formats code in scripts/test-formatting.sh
- [ ] T061 [P] [US3] Test script to verify PHP_CodeSniffer catches PSR-12 violations in scripts/test-php-linting.sh

### Implementation for User Story 3

- [ ] T062 [P] [US3] Add ESLint ignore patterns in frontend/.eslintignore (node_modules, dist, build)
- [ ] T063 [P] [US3] Add Prettier ignore patterns in frontend/.prettierignore (node_modules, dist, build)
- [ ] T064 [P] [US3] Configure lint-staged in root package.json (.ts,.tsx → eslint + prettier, .php → phpcs)
- [ ] T065 [US3] Configure husky pre-commit hook to run lint-staged in .husky/pre-commit
- [ ] T066 [US3] Add npm script to bypass hooks if needed (commit:no-verify) in root package.json
- [ ] T067 [US3] Create sample linting test files to validate setup in scripts/lint-test-samples/
- [ ] T068 [US3] Run test scripts to verify linters catch all violations (T059-T061)

**Checkpoint**: All user stories 1-3 should now be independently functional

---

## Phase 6: User Story 4 - Database Seeding (Priority: P4)

**Goal**: Developers have sample data available via npm run db:seed command

**Independent Test**: Run `npm run db:seed` → query DynamoDB via AWS CLI → verify sample config records present

### Tests for User Story 4 ⚠️

- [ ] T069 [P] [US4] Integration test for seed data insertion in backend/tests/Integration/SeedDataTest.php
- [ ] T070 [P] [US4] Test to verify seed script idempotency (safe to run multiple times) in scripts/test-seed-idempotency.sh

### Implementation for User Story 4

- [ ] T071 [P] [US4] Create seed data JSON file with sample config records in data/seed/config.json
- [ ] T072 [P] [US4] Implement seed script for bash in scripts/seed.sh (read JSON, insert via AWS CLI)
- [ ] T073 [P] [US4] Implement seed script for PowerShell in scripts/seed.ps1 (read JSON, insert via AWS CLI)
- [ ] T074 [US4] Add duplicate detection logic to seed scripts (check if records exist before inserting)
- [ ] T075 [US4] Wire `db:seed` npm script to detect platform and run appropriate seed script
- [ ] T076 [US4] Add seed data documentation to README.md (what data is seeded, how to customize)
- [ ] T077 [US4] Verify all tests pass (T069-T070)

**Checkpoint**: All user stories should now be independently functional

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories

- [ ] T078 [P] Update README.md with complete setup instructions (reference quickstart.md)
- [ ] T079 [P] Add troubleshooting section to README.md (common errors and solutions)
- [ ] T080 [P] Create CONTRIBUTING.md with development workflow guidelines
- [ ] T081 [P] Add npm script to check prerequisites (node version, php version, aws cli) in scripts/check-prerequisites.sh
- [ ] T082 [P] Add bundle size monitoring to frontend build (rollup-plugin-visualizer)
- [ ] T083 [P] Configure Vite to show build stats (bundle size, chunks) in frontend/vite.config.ts
- [ ] T084 Run quickstart.md validation (follow guide from scratch, verify all steps work)
- [ ] T085 Performance test: Measure frontend dev server startup time (<5s target)
- [ ] T086 Performance test: Measure backend dev server startup time (<5s target)
- [ ] T087 Performance test: Measure `db:sync` execution time (<30s target)
- [ ] T088 Security audit: Verify .env is gitignored and .env_dist has no real secrets
- [ ] T089 [P] Add deployment placeholder script in scripts/deploy-sftp.sh (future feature)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phase 3-6)**: All depend on Foundational phase completion
  - User stories can proceed in parallel (if staffed)
  - Or sequentially in priority order (P1 → P2 → P3 → P4)
- **Polish (Phase 7)**: Depends on all desired user stories being complete

### User Story Dependencies

- **User Story 1 (P1)**: Can start after Foundational (Phase 2) - No dependencies on other stories
- **User Story 2 (P2)**: Can start after Foundational (Phase 2) - Integrates with US1 but independently testable
- **User Story 3 (P3)**: Can start after Foundational (Phase 2) - Works across US1 and US2
- **User Story 4 (P4)**: Can start after Foundational (Phase 2) - Requires US1 (DynamoDB setup) completed

### Within Each User Story

- Tests (T035-T037, T049-T050, T059-T061, T069-T070) MUST be written and FAIL before implementation
- Implementation tasks run in logical order (scripts before wiring, client before routes)
- Verify tests pass at end of each story (T048, T058, T068, T077)

### Parallel Opportunities

- All Setup tasks marked [P] can run in parallel (T004, T005, T006)
- All Foundational tasks marked [P] can run in parallel within their subsections
- Once Foundational phase completes, User Story 1 can start
- After US1 completes, US2, US3, US4 can start in parallel (if team capacity allows)
- All tests for a user story marked [P] can run in parallel
- Polish tasks marked [P] can run in parallel

---

## Parallel Example: User Story 1

```bash
# Launch all tests for User Story 1 together:
Task T035: "Integration test for DynamoDB connection in backend/tests/Integration/DynamoDBConnectionTest.php"
Task T036: "Integration test for health check endpoint in backend/tests/Integration/HealthCheckTest.php"
Task T037: "Frontend integration test for API connectivity in frontend/src/tests/integration/api-connection.test.ts"

# Launch parallel implementation tasks for User Story 1:
Task T038: "Create DynamoDB config table AWS CLI script in scripts/aws-setup.sh"
Task T039: "Create DynamoDB config table AWS CLI script in scripts/aws-setup.ps1"
Task T041: "Implement DynamoDB client initialization in backend/src/Config/DynamoDBClient.php"
Task T044: "Create Axios client configuration in frontend/src/services/api.ts"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (CRITICAL - blocks all stories)
3. Complete Phase 3: User Story 1
4. **STOP and VALIDATE**: Test User Story 1 independently
5. Verify health check returns healthy status
6. Verify frontend loads and communicates with backend

### Incremental Delivery

1. Complete Setup + Foundational → Foundation ready
2. Add User Story 1 → Test independently → Validate (MVP!)
3. Add User Story 2 → Test independently → Validate
4. Add User Story 3 → Test independently → Validate
5. Add User Story 4 → Test independently → Validate
6. Each story adds value without breaking previous stories

### Parallel Team Strategy

With multiple developers:

1. Team completes Setup + Foundational together
2. Once Foundational is done:
   - Developer A: User Story 1 (P1 - highest priority)
   - Developer B: User Story 2 (P2 - can start after US1 complete)
   - Developer C: User Story 3 (P3 - can start after US1 complete)
3. User Story 4 starts after US1 complete (depends on DynamoDB setup)

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- Each user story should be independently completable and testable
- Verify tests fail before implementing (TDD per constitution)
- Commit after each task or logical group
- Stop at any checkpoint to validate story independently
- Constitution requires test-first development - tests precede implementation in each phase

---

## Task Summary

**Total Tasks**: 89

**By Phase**:
- Setup: 6 tasks
- Foundational: 28 tasks
- User Story 1 (P1): 14 tasks (3 tests + 11 implementation)
- User Story 2 (P2): 10 tasks (2 tests + 8 implementation)
- User Story 3 (P3): 10 tasks (3 tests + 7 implementation)
- User Story 4 (P4): 9 tasks (2 tests + 7 implementation)
- Polish: 12 tasks

**Parallel Opportunities**: 38 tasks marked [P] can run in parallel (within dependency constraints)

**Independent Test Criteria**:
- US1: Health check returns healthy, frontend loads, backend responds
- US2: Change APP_ENV → tables and config update automatically
- US3: Linters catch errors, pre-commit hooks block bad commits
- US4: Seed command populates data, safe to run multiple times

**Suggested MVP Scope**: Phases 1-3 (Setup + Foundational + User Story 1)
