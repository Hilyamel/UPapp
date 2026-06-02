# Feature Specification: Project Foundation

**Feature Branch**: `001-project-foundation`

**Created**: 2026-06-02

**Status**: Draft

**Input**: User description: "Project foundation with React + PHP + DynamoDB setup, environment configuration, and basic scaffolding"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Local Development Environment Setup (Priority: P1)

A developer clones the repository and sets up their local development environment to begin working on UPapp features.

**Why this priority**: Without a working development environment, no feature development can occur. This is the foundational prerequisite for all subsequent work.

**Independent Test**: Developer can clone the repo, run setup commands, and see both frontend and backend running locally with connectivity to DynamoDB dev tables.

**Acceptance Scenarios**:

1. **Given** a fresh repository clone, **When** developer runs `npm install` and copies `.env_dist` to `.env`, **Then** all dependencies install without errors and environment file is ready for configuration
2. **Given** `.env` is configured with AWS credentials, **When** developer runs `npm run db:sync`, **Then** all DynamoDB dev tables are created with correct naming convention (`UpApp.dev.*`)
3. **Given** tables are created, **When** developer runs `npm run gui` and `npm run backend` in separate terminals, **Then** React dev server starts on port 5173 and PHP backend starts on port 8080
4. **Given** both servers are running, **When** developer opens browser to localhost:5173, **Then** the React app loads and can communicate with the backend API

---

### User Story 2 - Environment Configuration Management (Priority: P2)

A developer or administrator needs to configure the application for different environments (dev, uat, prod) without modifying source code.

**Why this priority**: Multi-environment support is critical for safe deployment progression. Must be in place before any features go to production.

**Independent Test**: Change `APP_ENV` in `.env` file and verify that DynamoDB table prefixes, API URLs, and other environment-specific settings update accordingly without code changes.

**Acceptance Scenarios**:

1. **Given** `.env` file with `APP_ENV=dev`, **When** application initializes, **Then** all DynamoDB operations target `UpApp.dev.*` tables and API uses dev configuration
2. **Given** `.env` file with `APP_ENV=uat`, **When** application initializes, **Then** all DynamoDB operations target `UpApp.uat.*` tables and API uses uat configuration
3. **Given** `.env` file with `APP_ENV=prod`, **When** application initializes, **Then** all DynamoDB operations target `UpApp.prod.*` tables and API uses production configuration
4. **Given** missing or invalid environment variables, **When** application starts, **Then** clear error messages guide developer to fix configuration issues

---

### User Story 3 - Code Quality Enforcement (Priority: P3)

Developers write code that automatically adheres to project standards through automated linting and formatting.

**Why this priority**: Establishes code quality baseline early, preventing technical debt accumulation. Can be added after basic functionality works.

**Independent Test**: Introduce intentional linting errors and formatting issues, then run linters to verify they catch and fix issues.

**Acceptance Scenarios**:

1. **Given** TypeScript code with linting errors, **When** developer runs `npm run lint`, **Then** ESLint reports all violations with clear messages
2. **Given** code with formatting inconsistencies, **When** developer runs Prettier, **Then** all files are auto-formatted to project standards
3. **Given** PHP code violating PSR-12 standards, **When** developer runs PHP linter, **Then** violations are reported
4. **Given** linting errors exist, **When** developer attempts to commit, **Then** pre-commit hooks prevent the commit and display error messages

---

### User Story 4 - Database Seeding for Development (Priority: P4)

A developer needs sample data in their local DynamoDB tables to develop and test features without creating test data manually.

**Why this priority**: Accelerates feature development by providing realistic test data. Lower priority since initial development can proceed with manual data entry.

**Independent Test**: Run seed command and verify that predefined sample data appears in DynamoDB dev tables, visible through AWS CLI queries.

**Acceptance Scenarios**:

1. **Given** empty DynamoDB dev tables, **When** developer runs `npm run db:seed`, **Then** sample users, forms, and reference data are inserted
2. **Given** tables already contain seed data, **When** developer runs seed command again, **Then** command detects existing data and either skips or warns about duplication
3. **Given** seed data is loaded, **When** developer queries tables via AWS CLI or backend API, **Then** all expected sample records are present and valid

---

### Edge Cases

- What happens when AWS credentials are invalid or expired? Clear error message guides user to re-authenticate with AWS CLI
- What happens when trying to run `db:sync` without AWS CLI installed? Command detects missing CLI and provides installation instructions
- What happens when environment variables are missing from `.env`? Application fails to start with specific list of missing variables
- What happens when switching environments without running `db:sync`? Application detects missing tables for current environment and prompts to run sync
- What happens when ports 5173 or 8080 are already in use? Dev servers detect port conflicts and suggest alternatives or provide clear error messages

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST provide a root `package.json` with scripts for `gui`, `backend`, `db:sync`, `db:seed`, `build`, and `deploy`
- **FR-002**: System MUST include `.env_dist` template file with all required environment variables documented with clear comments
- **FR-003**: System MUST support three distinct environments (dev, uat, prod) controlled by `APP_ENV` variable
- **FR-004**: System MUST use DynamoDB table naming convention `UpApp.<ENV>.<tablename>` for all database tables
- **FR-005**: System MUST include `db:sync` script that creates DynamoDB tables using AWS CLI commands
- **FR-006**: System MUST include `db:seed` script that populates development tables with sample data
- **FR-007**: Frontend MUST be built with React, Vite, and TypeScript with ESLint and Prettier configured
- **FR-008**: Frontend MUST use PrimeReact component library and Font Awesome icons exclusively
- **FR-009**: Backend MUST be PHP-based with minimal dependencies, exposing REST API for frontend consumption
- **FR-010**: Backend MUST use AWS SDK for PHP to interact with DynamoDB (no ORM)
- **FR-011**: System MUST include `.gitignore` excluding `.env`, `node_modules`, `vendor`, `dist`, and `backend/cache`
- **FR-012**: System MUST provide clear documentation in `README.md` for initial setup and running locally
- **FR-013**: Frontend dev server MUST run on port 5173 by default
- **FR-014**: Backend dev server MUST run on port 8080 by default
- **FR-015**: System MUST validate all required environment variables on startup and fail with clear error messages if any are missing

### Key Entities

- **Environment Configuration**: Represents environment-specific settings (dev/uat/prod), including AWS region, DynamoDB table prefix, application URL, and environment name
- **Project Structure**: Represents directory layout including frontend/, backend/, scripts/, docs/ with proper file organization
- **Package Configuration**: Represents root package.json with npm scripts and dependencies, plus backend composer.json with PHP dependencies
- **Database Schema**: Represents DynamoDB table definitions with naming conventions, partition keys, sort keys, and indexes (specific tables defined in later features)

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: New developer can complete full environment setup (clone, install, configure, run) in under 10 minutes with provided documentation
- **SC-002**: Running `db:sync` successfully creates all required DynamoDB tables within 30 seconds
- **SC-003**: Frontend and backend servers start without errors within 5 seconds of running npm scripts
- **SC-004**: Environment switching (dev to uat to prod) requires only changing one variable (`APP_ENV`) and restarting servers
- **SC-005**: Code linting and formatting checks execute in under 5 seconds for entire codebase
- **SC-006**: Seed data population completes within 10 seconds and creates at least 10 sample records per entity type

## Assumptions

- AWS CLI is pre-installed and authenticated with valid credentials before project setup
- Developer machine has Node.js 18+ and PHP 8.1+ already installed
- Developer has basic familiarity with React, PHP, and npm/composer package managers
- AWS account has necessary permissions to create DynamoDB tables in eu-central-1 region
- Developer is working on Windows, Mac, or Linux with bash/PowerShell available
- Internet connectivity is stable for downloading npm and composer packages
- No existing processes are using ports 5173 or 8080 on developer machine
- Git is installed for version control operations
- Project will not require Docker, Kubernetes, or containerization (deployment via SFTP to traditional hosting)
- Frontend build output will be compatible with standard web hosting (static files)
- PHP backend will run on shared hosting environment without advanced server configuration
