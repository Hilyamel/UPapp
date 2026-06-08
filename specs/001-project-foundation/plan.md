# Implementation Plan: Dropdown Lists Fix & Environment Setup

**Branch**: `main` | **Date**: 2026-06-07 | **Spec**: specs/001-project-foundation/spec.md

**Input**: User request: "UPapp jest już na produkcji. Ale drop down listy z potrzebami i uczuciami nie działają w żadnym z formularzy. Chce też zrobić kilka innych zmian. Opracowałem już kopie wszystkich tabel w AWS dla dev, uat i prod. Aktualne środowisko oznacz jako prod, a nastepnie przygotuj środowiska dev i uat do dalszego developmentu. Zacznij od naprawiania drop down list."

## Summary

Fix non-functional dropdown lists for feelings (uczucia) and needs (potrzeby) in all forms (DUP, TUP, DOS, OK10). The dropdowns are implemented using CollapsibleList component but fail to display data from `/api/reference/feelings` and `/api/reference/needs` endpoints. After fixing the dropdowns, mark current environment as production and set up dev/uat environments for continued development.

Root cause analysis indicates the API endpoints exist and return properly structured data. The issue is likely in:
1. CORS configuration blocking cross-origin requests in production
2. API base URL misconfiguration in frontend
3. Data file path resolution in production environment
4. JSON parsing or response handling

## Technical Context

**Language/Version**: 
- Frontend: TypeScript 5.x with React 18.x
- Backend: PHP 8.1+ with Slim Framework 4.x

**Primary Dependencies**: 
- Frontend: React, Vite, PrimeReact, Axios
- Backend: Slim Framework, AWS SDK for PHP, vlucas/phpdotenv

**Storage**: DynamoDB tables with naming convention `UpApp.<ENV>.<tablename>`, static JSON files in `data/` directory for reference data

**Testing**: 
- Frontend: Vitest (to be configured)
- Backend: PHPUnit (to be configured)
- Integration: Manual testing against dev DynamoDB tables

**Target Platform**: Shared hosting via SFTP deployment (production), local development (dev/uat)

**Project Type**: Web application (React SPA + PHP REST API)

**Performance Goals**: 
- API response time <200ms for reference data endpoints
- Dropdown render time <500ms with full data set
- Page load <2s initial, <500ms navigation

**Constraints**: 
- Shared hosting without CDN or advanced caching
- Reference data loaded from static JSON files (not DynamoDB)
- Must support three environments with isolated data
- Production is live with users; fixes must not cause downtime

**Scale/Scope**: 
- Reference data: ~100 feelings, ~80 needs
- Form types: 4 (DUP, TUP, DOS, OK10)
- Environments: 3 (dev, uat, prod)

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

### Code Quality Standards ✅
- All TypeScript/React code MUST pass ESLint and Prettier checks
- All PHP code MUST follow PSR-12 standards
- Pre-commit hooks configured to enforce quality

### Testing Discipline ✅
- Frontend tests required for dropdown component functionality
- Backend tests required for reference API endpoints
- NO MOCKING of real services in integration tests
- Test against `UpApp.dev.*` tables for DynamoDB operations
- **NOTE**: Reference data endpoints use static JSON files, not DynamoDB

### User Experience Consistency ✅
- PrimeReact components used (CollapsibleList is custom but follows PrimeReact patterns)
- Font Awesome icons throughout
- Form layouts match TUPapp designs
- Loading states visible for async operations

### Performance Requirements ✅
- Reference data API must respond <200ms (static file reads are fast)
- Frontend bundle <500KB gzipped
- Code splitting for admin routes

### Security Standards ✅
- CORS properly configured to allow frontend origin
- Input validation on both client and server
- Environment variables for all sensitive configuration

### Environment Isolation ⚠️ **ATTENTION REQUIRED**
- Three-environment strategy: dev, uat, prod
- Current production environment NOT properly marked in `.env`
- **ACTION**: Update `.env` to set `APP_ENV=prod` for current production
- **ACTION**: Create dev/uat environment configurations
- Environment switching MUST be via `APP_ENV` variable only
- Deployment gates: dev → uat (code review), uat → prod (UAT + approval)

### Dependency Minimalism ✅
- Backend uses Slim Framework (lightweight)
- Frontend uses minimal dependencies (React, PrimeReact, Axios)
- No state management library (using React Context)

### Complexity Violations: NONE

## Project Structure

### Documentation (this feature)

```text
specs/001-project-foundation/
├── plan.md              # This file (updated by /speckit-plan)
├── research.md          # Phase 0 output: dropdown debugging, environment setup
├── data-model.md        # Phase 1 output: reference data structure
├── quickstart.md        # Phase 1 output: environment setup guide
└── contracts/           # Phase 1 output: API response contracts
    ├── reference-api.md
    └── error-handling.md
```

### Source Code (repository root)

```text
backend/
├── src/
│   ├── Config/
│   │   ├── Environment.php      # Environment variable management
│   │   └── DynamoDBClient.php   # DynamoDB connection
│   ├── Handlers/
│   │   ├── ReferenceHandler.php # Reference data API (CRITICAL: fix here)
│   │   ├── FormHandler.php
│   │   ├── AuthHandler.php
│   │   └── HealthCheckHandler.php
│   ├── Middleware/
│   │   └── CorsMiddleware.php   # CORS configuration (CRITICAL: verify here)
│   └── routes.php
├── public/
│   └── index.php                # Entry point
└── tests/                       # PHPUnit tests (to be added)

frontend/
├── src/
│   ├── components/
│   │   ├── Common/
│   │   │   └── CollapsibleList.tsx  # Dropdown component (CRITICAL: test here)
│   │   └── Forms/
│   │       ├── DUPForm.tsx      # Uses feelings & needs
│   │       ├── TUPForm.tsx      # Uses feelings & needs
│   │       ├── DOSForm.tsx      # Uses needs
│   │       └── OK10Form.tsx     # Uses needs
│   ├── services/
│   │   ├── api.ts              # Axios client (CRITICAL: verify base URL)
│   │   └── reference.ts         # Reference data API client
│   └── pages/
└── tests/                       # Vitest tests (to be added)

data/                            # Static reference data
├── lista_uczuc.json            # Feelings list (CRITICAL: verify path)
└── lista_potrzeb.json          # Needs list (CRITICAL: verify path)

.env                             # Environment configuration (CRITICAL: update)
.env_dist                        # Template
```

**Structure Decision**: Standard web application structure (backend/frontend separation) is already in place. Focus on fixing reference data flow: `data/*.json` → `ReferenceHandler.php` → `reference.ts` → `CollapsibleList.tsx`

## Complexity Tracking

> No constitution violations requiring justification.

---

# Phase 0: Research & Root Cause Analysis

## Objectives

1. **Identify root cause** of dropdown list failures in production
2. **Test reference API endpoints** in production vs development
3. **Document environment configuration** differences between dev/uat/prod
4. **Research deployment path** for static data files

## Research Tasks

### Task 1: Diagnose Dropdown Failure

**Investigation points**:
- Check browser console for JavaScript errors when opening dropdowns
- Verify network requests to `/api/reference/feelings` and `/api/reference/needs`
- Inspect API responses: status code, headers, body structure
- Test CollapsibleList component with mock data to isolate frontend vs backend issue

**Hypothesis**:
- **H1**: CORS blocking cross-origin requests (frontend on domain A, backend on domain B)
- **H2**: API base URL misconfigured in production frontend build
- **H3**: Data file paths incorrect in production (relative vs absolute paths)
- **H4**: JSON parsing failure due to encoding issues (UTF-8 with BOM)

**Testing approach**:
1. Open production site, navigate to any form (e.g., DUP)
2. Open browser DevTools (F12) → Network tab
3. Expand a dropdown list (e.g., "Uczucia zaspokojenia")
4. Check for failed API calls to `/api/reference/feelings`
5. If call succeeds, check response body structure
6. If call fails, check CORS headers and status code

**Expected outcome**: Identify which hypothesis is correct and document exact error

### Task 2: Verify Data File Deployment

**Investigation points**:
- Confirm `data/lista_uczuc.json` and `data/lista_potrzeb.json` exist in production
- Verify file paths in `ReferenceHandler.php` resolve correctly in production
- Test file permissions on shared hosting (readable by PHP process)
- Validate JSON structure matches expected format

**Testing approach**:
1. SSH/SFTP to production server
2. Check `<SFTP_REMOTE_PATH>/data/` directory exists
3. Verify both JSON files present and readable
4. Test file path resolution: `__DIR__ . '/../../../data'` from `src/Handlers/ReferenceHandler.php`

**Expected outcome**: Confirm data files are deployed and paths resolve correctly

### Task 3: Environment Configuration Analysis

**Investigation points**:
- Document current `.env` configuration in production
- Compare `APP_ENV`, `APP_URL`, `BACKEND_URL` across environments
- Verify `CORS_ALLOWED_ORIGINS` in production backend
- Check if production uses `.env` or environment variables from hosting control panel

**Testing approach**:
1. Review production `.env` file (or hosting environment variables)
2. Document all values for `APP_*`, `AWS_*`, `BACKEND_*`, `SFTP_*`
3. Create table comparing dev/uat/prod configurations
4. Identify missing or incorrect values

**Expected outcome**: Clear documentation of required environment variables per environment

### Task 4: CORS Configuration Review

**Investigation points**:
- Review `CorsMiddleware.php` implementation
- Verify `Access-Control-Allow-Origin` header in production responses
- Test preflight OPTIONS requests
- Check if production frontend URL is allowed in CORS config

**Testing approach**:
1. Read `backend/src/Middleware/CorsMiddleware.php`
2. Check if `APP_URL` from `.env` is used in CORS headers
3. Use `curl` to test CORS headers:
   ```bash
   curl -I -X OPTIONS \
     -H "Origin: https://production-frontend.com" \
     -H "Access-Control-Request-Method: GET" \
     https://production-backend.com/api/reference/feelings
   ```
4. Verify response includes correct CORS headers

**Expected outcome**: Identify CORS configuration issues and required fixes

## Research Deliverables

**research.md** will contain:
1. **Root Cause**: Exact reason dropdowns fail (one of H1-H4 with evidence)
2. **Environment Comparison Table**: dev vs uat vs prod configurations
3. **CORS Configuration**: Current state and required changes
4. **Data File Deployment**: Path resolution and deployment verification
5. **Fix Recommendations**: Specific code changes to resolve issue

---

# Phase 1: Design & Contracts

## Prerequisites
- `research.md` completed with root cause identified

## Objectives

1. **Design environment-specific configurations** for dev, uat, prod
2. **Document API contracts** for reference endpoints
3. **Create deployment checklist** for static files and environment configs
4. **Update agent context** with plan reference

## Design Tasks

### Task 1: Data Model Documentation

**File**: `data-model.md`

**Content**:
- **Feeling Entity**: Structure of objects in `lista_uczuc.json`
  - `name_pl`: string (Polish name)
  - `category`: "fulfilled" | "unfulfilled"
  - `subcategory`: string (grouping for UI)
  - `sort_order`: number
- **Need Entity**: Structure of objects in `lista_potrzeb.json`
  - `name_pl`: string (Polish name)
  - `category`: string (grouping for UI)
  - `sort_order`: number
- **API Response Structure**: Grouped format returned by backend
  - Feelings: `{ fulfilled: { [subcategory]: Feeling[] }, unfulfilled: { [subcategory]: Feeling[] } }`
  - Needs: `{ [category]: Need[] }`

### Task 2: API Contracts

**Directory**: `contracts/`

**Files**:

#### `reference-api.md`
```markdown
# Reference Data API Contracts

## GET /api/reference/feelings

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "fulfilled": {
      "Czułość": [
        { "id": "współczucie", "name_pl": "współczucie" },
        { "id": "serdeczność", "name_pl": "serdeczność" }
      ],
      "Radość": [ ... ]
    },
    "unfulfilled": {
      "Gniew": [ ... ],
      "Smutek": [ ... ]
    }
  },
  "error": null
}
```

**Response** (404 Not Found):
```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "REFERENCE_ERROR",
    "message": "Feelings data not found"
  }
}
```

## GET /api/reference/needs

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "Autonomia": [
      { "id": "wolność", "name_pl": "wolność" },
      { "id": "niezależność", "name_pl": "niezależność" }
    ],
    "Współzależność": [ ... ]
  },
  "error": null
}
```

**Response** (404 Not Found):
```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "REFERENCE_ERROR",
    "message": "Needs data not found"
  }
}
```

#### `error-handling.md`
Document standard error response format and status codes for all API endpoints.

### Task 3: Environment Setup Quickstart

**File**: `quickstart.md`

**Content**:
- **Dev Environment Setup**: Steps to run locally with `APP_ENV=dev`
- **UAT Environment Setup**: Steps to deploy to UAT with `APP_ENV=uat`
- **Prod Environment Setup**: Steps to deploy to production with `APP_ENV=prod`
- **Environment Variables**: Table of required variables per environment
- **Troubleshooting**: Common issues and solutions (CORS errors, file paths, etc.)

### Task 4: Agent Context Update

**File**: `CLAUDE.md`

**Action**: Update the plan reference between `<!-- SPECKIT START -->` and `<!-- SPECKIT END -->` markers to point to:
```
specs/001-project-foundation/plan.md
```

---

# Phase 2: Implementation Tasks

*NOTE: This section is a preview. Actual task breakdown is generated by `/speckit-tasks` command.*

## High-Level Implementation Steps

### Step 1: Fix Dropdown Lists
1. Diagnose root cause (from research.md findings)
2. Fix identified issue (CORS config, API URL, file paths, or JSON parsing)
3. Test in local dev environment
4. Verify all forms (DUP, TUP, DOS, OK10) load dropdowns correctly

### Step 2: Mark Current Environment as Production
1. Update production `.env` to set `APP_ENV=prod`
2. Verify DynamoDB table prefix resolves to `UpApp.prod.*`
3. Test production deployment with new config
4. Document production environment variables

### Step 3: Set Up Dev Environment
1. Create `.env.dev` template with `APP_ENV=dev`
2. Verify DynamoDB tables `UpApp.dev.*` exist in AWS
3. Test local development workflow
4. Document dev setup in quickstart.md

### Step 4: Set Up UAT Environment
1. Create `.env.uat` template with `APP_ENV=uat`
2. Verify DynamoDB tables `UpApp.uat.*` exist in AWS
3. Deploy UAT environment to separate hosting or subdomain
4. Document UAT deployment process

### Step 5: Automated Testing
1. Write PHPUnit tests for ReferenceHandler
2. Write Vitest tests for CollapsibleList component
3. Add integration test for reference API endpoints
4. Configure CI to run tests before deployment

---

# Completion Criteria

## Phase 0 Complete When:
- [ ] Root cause of dropdown failure identified with evidence
- [ ] Environment comparison table completed
- [ ] CORS configuration documented
- [ ] Data file deployment verified
- [ ] research.md file created with all findings

## Phase 1 Complete When:
- [ ] data-model.md created with entity structures
- [ ] contracts/reference-api.md created with API specs
- [ ] contracts/error-handling.md created with error formats
- [ ] quickstart.md created with environment setup guides
- [ ] CLAUDE.md updated with plan reference

## Implementation Complete When:
- [ ] Dropdown lists display feelings and needs in all forms
- [ ] Production `.env` sets `APP_ENV=prod`
- [ ] Dev environment configured with `APP_ENV=dev` and `UpApp.dev.*` tables
- [ ] UAT environment configured with `APP_ENV=uat` and `UpApp.uat.*` tables
- [ ] All forms tested in dev environment
- [ ] PHPUnit and Vitest test suites added
- [ ] Deployment documentation updated

---

**Version**: 1.0.0 | **Created**: 2026-06-07 | **Last Updated**: 2026-06-07
