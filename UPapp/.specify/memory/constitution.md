<!--
Sync Impact Report:
Version: 1.0.0 (initial constitution)
Modified principles: N/A (new constitution)
Added sections: All core principles, Technical Standards, Development Workflow, Governance
Removed sections: N/A
Templates requiring updates:
  ✅ plan-template.md - Constitution Check section compatible
  ✅ spec-template.md - Requirements align with constitution
  ✅ tasks-template.md - Task phases align with principles
Follow-up TODOs: None
-->

# UPapp Constitution

## Core Principles

### I. Code Quality Standards

**MUST enforce code quality from project inception**:
- All TypeScript/React code MUST pass ESLint and Prettier checks before commit
- All PHP code MUST follow PSR-12 coding standards
- No unused variables, imports, or dead code in production branches
- Code reviews MUST verify adherence to naming conventions and project structure

**Rationale**: The application handles sensitive NVC (Nonviolent Communication) data and user sessions. Clean, consistent code reduces bugs and security vulnerabilities.

**How to apply**: Configure pre-commit hooks; fail CI builds on linting errors; include code quality checks in all PR reviews.

---

### II. Testing Discipline (NON-NEGOTIABLE)

**Testing requirements by component**:
- **Frontend**: React component tests for form validation, state management, and user workflows
- **Backend**: PHPUnit tests for API endpoints, authentication flows, and DynamoDB interactions
- **Integration**: End-to-end tests for OAuth flows, Magic Link authentication, and form submission pipelines
- **NO MOCKING** of DynamoDB or external AWS services in integration tests — use test environments

**Test-first development**:
- Write tests BEFORE implementation for all new features
- Tests MUST fail before implementation begins (red-green-refactor)
- All acceptance criteria from spec.md MUST have corresponding automated tests

**Rationale**: Past incidents showed that mocked tests passed while production DynamoDB interactions failed. Real integration tests prevent environment divergence issues.

**How to apply**: Maintain separate DynamoDB tables for `dev`, `uat`, `prod` environments; run integration tests against `UpApp.dev.*` tables; require test evidence in PR descriptions.

---

### III. User Experience Consistency

**UX standards across all interfaces**:
- **PrimeReact components only** — no mixing of UI libraries
- **Font Awesome icons only** — consistent icon set throughout
- Form layouts MUST match TUPapp reference designs (DUP, TUP, DOS forms)
- Error messages MUST be user-friendly and actionable (not raw technical errors)
- Loading states MUST be visible for all async operations (forms, authentication, data fetches)

**Accessibility requirements**:
- All forms MUST be keyboard-navigable
- ARIA labels required for all interactive elements
- Color contrast MUST meet WCAG AA standards

**Rationale**: Users are practicing NVC techniques; confusing or inconsistent UI disrupts their emotional state and undermines the application's purpose.

**How to apply**: Use PrimeReact theme customization for branding; create reusable form components; conduct accessibility audits before each release.

---

### IV. Performance Requirements

**Response time targets**:
- Frontend page load: <2 seconds (initial load), <500ms (subsequent navigation)
- API response time: <200ms (p95) for form CRUD operations
- DynamoDB queries: MUST use indexes; avoid full table scans
- Authentication: <1 second for OAuth redirect, <3 seconds for Magic Link email delivery

**Bundle size limits**:
- React production bundle: <500KB gzipped
- Code splitting MUST be used for admin panel routes
- Lazy loading for rarely-used components (e.g., deployment UI)

**Rationale**: Application is deployed via SFTP to shared hosting; no CDN or advanced caching available. Client-side performance is critical.

**How to apply**: Monitor bundle size in CI; use Vite's rollup-plugin-visualizer; profile DynamoDB queries in development; load-test authentication flows before production deployment.

---

### V. Security Standards

**Authentication & Authorization**:
- JWT tokens MUST expire within 24 hours
- Magic Link tokens MUST expire within 15 minutes (configurable via `MAGIC_LINK_TTL_MINUTES`)
- Admin access MUST verify email against `ADMIN_ALLOWED_EMAILS` on every privileged operation
- Session tokens MUST be stored in httpOnly cookies (not localStorage)

**Secrets Management**:
- NEVER commit `.env` files
- AWS credentials MUST be loaded from environment variables or AWS CLI profiles
- SFTP credentials MUST be stored in `.env` only
- Google OAuth secrets MUST be stored in `.env` only

**Input Validation**:
- All form inputs MUST be validated on both client (React) and server (PHP)
- SQL injection protection: N/A (DynamoDB only; AWS SDK handles escaping)
- XSS protection: React escapes by default; verify all `dangerouslySetInnerHTML` usages
- CSRF protection: Required for all POST/PUT/DELETE endpoints

**Rationale**: Application handles user identity data and NVC reflections (potentially sensitive emotional content). Security breaches would violate user trust.

**How to apply**: Security review checklist required for all authentication PRs; penetration testing before each major release; audit `.env_dist` for accidental secret inclusion.

---

### VI. Environment Isolation

**Three-environment strategy**:
- `dev`: Local development; DynamoDB tables prefixed `UpApp.dev.*`
- `uat`: Pre-production testing; DynamoDB tables prefixed `UpApp.uat.*`
- `prod`: Production; DynamoDB tables prefixed `UpApp.prod.*`

**Environment switching**:
- MUST be accomplished via `APP_ENV` variable in `.env`
- NO hardcoded environment values in source code
- All scripts (db:sync, db:seed, deploy) MUST respect `APP_ENV`

**Deployment gates**:
- `dev` → `uat`: Code review + passing tests
- `uat` → `prod`: User acceptance testing + admin approval

**Rationale**: Past incidents included accidental deployment to production or test data polluting production tables. Strict environment discipline prevents these failures.

**How to apply**: CI checks for environment variable validation; deployment scripts require explicit environment confirmation; production deployments require two-person approval.

---

### VII. Dependency Minimalism

**Backend (PHP)**:
- **Slim Framework** preferred for routing
- AWS SDK for PHP (DynamoDB client)
- PHPMailer for Magic Link emails
- vlucas/phpdotenv for environment configuration
- PHPUnit for testing
- NO Laravel, NO Symfony (too heavy for shared hosting)

**Frontend (React)**:
- PrimeReact + PrimeIcons (UI components)
- Font Awesome (icons)
- React Router (navigation)
- Axios (HTTP client)
- Vite (build tool)
- NO state management libraries unless justified (start with React Context)

**Rationale**: Shared hosting has limited resources; large frameworks increase deployment size and memory usage. Lightweight stack ensures predictable performance.

**How to apply**: Require justification for all new dependencies in PR descriptions; audit `package.json` and `composer.json` quarterly; reject PRs that add redundant libraries.

---

## Technical Standards

### Database Design

**DynamoDB table structure**:
- Table naming: `UpApp.<ENV>.<tablename>` (e.g., `UpApp.dev.forms`)
- Partition key design MUST avoid hot partitions
- GSI (Global Secondary Index) required for query patterns beyond primary key
- All tables MUST be defined in `scripts/aws-setup.sh` for reproducibility

**Data modeling**:
- Single-table design preferred where appropriate
- Use composite sort keys for hierarchical data (e.g., `USER#<id>#FORM#<formid>`)
- Avoid N+1 queries; use batch operations where possible

---

### API Design

**REST conventions**:
- GET: Idempotent reads
- POST: Create new resources
- PUT: Full resource update
- PATCH: Partial resource update
- DELETE: Remove resources

**Response format**:
```json
{
  "success": true,
  "data": { ... },
  "error": null
}
```

**Error format**:
```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "User-friendly error message",
    "details": { ... }
  }
}
```

**Headers**:
- `Content-Type: application/json` for all responses
- `Authorization: Bearer <token>` for authenticated requests
- CORS headers MUST allow frontend origin (configurable via `APP_URL`)

---

### Deployment Workflow

**Build process**:
1. Run tests: `npm run test` (frontend) and `composer test` (backend)
2. Lint: `npm run lint` and ensure Prettier formatting
3. Build frontend: `npm run build` (outputs to `dist/`)
4. Execute deployment script: `npm run deploy` (triggers `scripts/deploy-sftp.sh`)

**SFTP deployment steps** (handled by deploy script):
1. Connect to SFTP server using credentials from `.env`
2. Upload `dist/` to `<SFTP_REMOTE_PATH>/public/`
3. Upload `backend/` to `<SFTP_REMOTE_PATH>/backend/`
4. Verify `.htaccess` and `index.php` entry points
5. Run smoke tests (health check endpoint)

**Rollback procedure**:
- Keep previous deployment as `<SFTP_REMOTE_PATH>.backup/`
- On failure, swap symlinks or re-upload backup
- Requires manual intervention (no automated rollback)

---

## Development Workflow

### Branching Strategy

**Branch naming**:
- Feature branches: `###-feature-name` (e.g., `001-google-oauth`)
- Bugfix branches: `fix-###-bug-description` (e.g., `fix-042-magic-link-expiry`)
- Release branches: `release-v<version>` (e.g., `release-v1.2.0`)

**Commit messages**:
- Format: `<type>: <description>` (e.g., `feat: add Magic Link authentication`)
- Types: `feat`, `fix`, `docs`, `chore`, `test`, `refactor`
- Include Co-Authored-By trailer for AI assistance

**Pull request requirements**:
- All CI checks passing (linting, tests, build)
- Code review approval from at least one team member
- Linked to feature spec or issue (e.g., `Implements #42`)
- Screenshots for UI changes

---

### Local Development Setup

**Prerequisites**:
- Node.js 18+ (for React/Vite)
- PHP 8.1+ (for backend)
- AWS CLI configured with credentials
- Composer (PHP package manager)

**Initial setup**:
```bash
npm install
cd backend && composer install
cp .env_dist .env  # Edit with your credentials
npm run db:sync    # Create DynamoDB tables
npm run db:seed    # Populate sample data
```

**Running locally**:
```bash
npm run gui        # Starts React dev server (http://localhost:5173)
npm run backend    # Starts PHP server (http://localhost:8080)
```

**Environment variables**:
- `.env` is git-ignored and personal to each developer
- `.env_dist` is the template committed to the repository
- Never commit secrets; use placeholder values in `.env_dist`

---

### Code Review Checklist

**For all PRs**:
- [ ] Code passes linting and formatting checks
- [ ] No console.log or var_dump left in code
- [ ] Error handling present for all failure modes
- [ ] Tests cover new/changed functionality
- [ ] Performance impact assessed (bundle size, query count)

**For authentication PRs**:
- [ ] Security review conducted
- [ ] Token expiry tested manually
- [ ] Session management verified
- [ ] CSRF protection confirmed

**For database PRs**:
- [ ] DynamoDB queries use indexes (no scans)
- [ ] Tested against `dev` environment tables
- [ ] Migration plan documented (if schema changes)

**For UI PRs**:
- [ ] Screenshots attached
- [ ] Keyboard navigation tested
- [ ] Mobile responsive (if applicable)
- [ ] Loading/error states implemented

---

## Governance

**Constitution Authority**:
- This constitution supersedes all conflicting practices and conventions
- When in doubt, this document defines the project's development standards
- Violations MUST be justified in PR descriptions with rationale and approval

**Amendment Process**:
1. Propose amendment via issue or PR
2. Discuss rationale and impact on existing work
3. Require approval from project maintainers
4. Update constitution with new version number
5. Update dependent templates and documentation

**Version Control**:
- Version format: MAJOR.MINOR.PATCH (semantic versioning)
- MAJOR: Backward-incompatible principle changes or removals
- MINOR: New principles added or materially expanded guidance
- PATCH: Clarifications, typo fixes, non-semantic refinements

**Compliance Review**:
- All PRs MUST be reviewed against constitution principles
- Quarterly audits to identify technical debt violating principles
- Constitution violations flagged in code reviews block merge until resolved

**Runtime Guidance**:
- Use `CLAUDE.md` for agent-specific development instructions
- Constitution defines WHAT; `CLAUDE.md` defines HOW for AI agents
- `CLAUDE.md` MUST NOT contradict constitution

---

**Version**: 1.0.0 | **Ratified**: 2026-06-02 | **Last Amended**: 2026-06-02
