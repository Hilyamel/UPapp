# Implementation Plan: Project Foundation

**Branch**: `001-project-foundation` | **Date**: 2026-06-02 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/001-project-foundation/spec.md`

## Summary

Establish the foundational project structure for UPapp, including frontend (React + Vite + TypeScript), backend (PHP + Slim), environment configuration (.env management for dev/uat/prod), DynamoDB table provisioning scripts, development tooling (ESLint, Prettier), and seed data scripts. This enables developers to clone, configure, and run the full stack locally within 10 minutes.

## Technical Context

**Language/Version**: 
- Frontend: TypeScript 5.x with React 18+
- Backend: PHP 8.1+
- Build: Node.js 18+, Vite 5.x

**Primary Dependencies**:
- Frontend: React, Vite, TypeScript, PrimeReact, Font Awesome, React Router, Axios
- Backend: Slim Framework 4, AWS SDK for PHP (DynamoDB client), vlucas/phpdotenv, PHPMailer
- Testing: Vitest (frontend), PHPUnit (backend)
- Code Quality: ESLint, Prettier, PHP_CodeSniffer (PSR-12)

**Storage**: AWS DynamoDB with table naming convention `UpApp.<ENV>.<tablename>`

**Testing**:
- Frontend: Vitest for component and integration tests
- Backend: PHPUnit for API endpoint and DynamoDB interaction tests
- Integration: Real DynamoDB dev tables (no mocking per constitution)

**Target Platform**: 
- Development: Windows/Mac/Linux with Node.js 18+, PHP 8.1+, AWS CLI
- Deployment: Shared hosting via SFTP (static files + PHP runtime)

**Project Type**: Web application (SPA frontend + REST API backend)

**Performance Goals**:
- Frontend dev server start: <5 seconds
- Backend dev server start: <5 seconds
- `db:sync` table creation: <30 seconds
- Frontend production bundle: <500KB gzipped
- API response time: <200ms (p95) for CRUD operations

**Constraints**:
- No admin rights on developer machine (avoid system-level installations)
- No Docker/Kubernetes (SFTP deployment to traditional hosting)
- Minimal PHP dependencies (lightweight for shared hosting)
- Multi-environment support (dev/uat/prod) via single `.env` variable

**Scale/Scope**:
- Small development team (1-3 developers)
- Initial deployment: single production instance
- Expected user base: <1000 concurrent users
- DynamoDB tables: ~5-10 tables initially (users, forms, reference data)

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

### I. Code Quality Standards
- ✅ **PASS**: ESLint + Prettier configured for TypeScript/React (FR-007)
- ✅ **PASS**: PHP_CodeSniffer configured for PSR-12 standards (constitution requirement)
- ✅ **PASS**: Pre-commit hooks planned to enforce quality gates (US3)
- ✅ **PASS**: `.gitignore` excludes build artifacts and secrets (FR-011)

### II. Testing Discipline (NON-NEGOTIABLE)
- ✅ **PASS**: Vitest configured for frontend component tests (FR-007)
- ✅ **PASS**: PHPUnit configured for backend API tests (constitution requirement)
- ✅ **PASS**: No DynamoDB mocking - tests use `UpApp.dev.*` tables (constitution requirement)
- ✅ **PASS**: Test-first development enforced in workflow (constitution principle)

### III. User Experience Consistency
- ✅ **PASS**: PrimeReact exclusively for UI components (FR-008)
- ✅ **PASS**: Font Awesome exclusively for icons (FR-008)
- ⚠️ **DEFER**: Accessibility (ARIA, keyboard nav) deferred to form feature implementation
- ⚠️ **DEFER**: Error message UX standards deferred to form feature implementation

### IV. Performance Requirements
- ✅ **PASS**: Frontend bundle size monitored (<500KB gzipped per constitution)
- ✅ **PASS**: Vite build tool configured for code splitting (constitution requirement)
- ⚠️ **DEFER**: DynamoDB query optimization deferred to feature implementation (no queries yet)
- ✅ **PASS**: Dev server start time <5 seconds (SC-003)

### V. Security Standards
- ✅ **PASS**: `.env` excluded from git (FR-011)
- ✅ **PASS**: `.env_dist` template with placeholder secrets (FR-002)
- ⚠️ **DEFER**: Authentication/authorization deferred to auth feature
- ⚠️ **DEFER**: CSRF protection deferred to API feature implementation

### VI. Environment Isolation
- ✅ **PASS**: Three environments (dev/uat/prod) via `APP_ENV` (FR-003)
- ✅ **PASS**: DynamoDB table prefix `UpApp.<ENV>.*` (FR-004)
- ✅ **PASS**: No hardcoded environment values (FR-003)
- ✅ **PASS**: Scripts respect `APP_ENV` (FR-005, FR-006)

### VII. Dependency Minimalism
- ✅ **PASS**: Slim Framework (lightweight PHP routing)
- ✅ **PASS**: AWS SDK for PHP (DynamoDB only, no ORM)
- ✅ **PASS**: vlucas/phpdotenv (environment config)
- ✅ **PASS**: PrimeReact + Font Awesome (consistent UI)
- ✅ **PASS**: No state management library initially (React Context sufficient)
- ✅ **PASS**: Vite (modern, fast build tool)

**Gate Status**: ✅ **PASSED** (deferred items are legitimately out of scope for project foundation)

## Project Structure

### Documentation (this feature)

```text
specs/001-project-foundation/
├── plan.md              # This file
├── spec.md              # Feature specification
├── research.md          # Technology decisions and rationale
├── data-model.md        # Initial DynamoDB schema (tables for foundation)
├── quickstart.md        # Developer onboarding guide
├── contracts/           # API contracts (initial health check endpoint)
│   └── health-check.md
└── checklists/
    └── requirements.md  # Specification quality checklist
```

### Source Code (repository root)

**Web application structure** (frontend + backend detected):

```text
UPapp/
├── .env_dist                   # Environment template
├── .env                        # gitignored
├── .gitignore
├── package.json                # Root npm scripts
├── package-lock.json
├── README.md                   # Setup and running instructions
├── frontend/
│   ├── src/
│   │   ├── main.tsx            # React entry point
│   │   ├── App.tsx             # Root component
│   │   ├── vite-env.d.ts       # Vite type definitions
│   │   └── assets/             # Static assets
│   ├── public/                 # Public assets (favicon, etc.)
│   ├── index.html              # HTML entry point
│   ├── vite.config.ts          # Vite configuration
│   ├── tsconfig.json           # TypeScript config
│   ├── tsconfig.node.json      # TypeScript config for Vite
│   ├── .eslintrc.json          # ESLint rules
│   ├── .prettierrc             # Prettier config
│   └── package.json            # Frontend dependencies
├── backend/
│   ├── public/
│   │   └── index.php           # PHP entry point
│   ├── src/
│   │   ├── Config/
│   │   │   └── Environment.php # Environment configuration loader
│   │   ├── Middleware/
│   │   │   └── CorsMiddleware.php
│   │   └── routes.php          # Slim routes definition
│   ├── tests/
│   │   └── HealthCheckTest.php
│   ├── composer.json           # PHP dependencies
│   ├── composer.lock
│   └── phpunit.xml             # PHPUnit configuration
├── scripts/
│   ├── aws-setup.sh            # DynamoDB table creation (bash)
│   ├── aws-setup.ps1           # DynamoDB table creation (PowerShell)
│   ├── seed.sh                 # Seed data (bash)
│   ├── seed.ps1                # Seed data (PowerShell)
│   └── deploy-sftp.sh          # SFTP deployment (placeholder)
├── docs/
│   └── init/
│       └── (future architecture docs)
├── .claude/
│   └── (Spec Kit metadata)
└── specs/
    └── 001-project-foundation/
        └── (this plan and related docs)
```

**Structure Decision**: Web application structure selected based on requirements for separate React frontend and PHP backend. Frontend uses Vite standard structure. Backend uses Slim Framework conventions with `public/` entry point for shared hosting compatibility.

## Complexity Tracking

*No constitution violations requiring justification.*

---

**PHASE 0 and PHASE 1 outputs will be generated next by the planning workflow.**
