# Research: Project Foundation

**Created**: 2026-06-02

**Purpose**: Document technology choices, rationale, and alternatives considered for the UPapp project foundation.

## Technology Decisions

### Frontend Framework: React 18 + Vite + TypeScript

**Decision**: Use React 18 with Vite build tool and TypeScript for type safety.

**Rationale**:
- React 18 provides modern hooks, concurrent features, and excellent ecosystem
- Vite offers significantly faster dev server startup (<5s vs 30s+ with Webpack)
- TypeScript catches errors at compile time, improving code quality and maintainability
- PrimeReact component library (requirement) has first-class React support
- Large community and extensive documentation reduce onboarding time

**Alternatives Considered**:
- **Vue 3 + Vite**: Rejected - PrimeReact requirement mandates React
- **Next.js**: Rejected - adds unnecessary complexity for SPA use case, requires Node.js runtime for SSR (shared hosting constraint)
- **Create React App**: Rejected - deprecated, slower build times, Vite is modern replacement

**Best Practices**:
- Use functional components with hooks (no class components)
- Implement code splitting via `React.lazy()` for route-level components
- Use `React.StrictMode` in development to catch potential issues
- Follow React 18 best practices for `useEffect` cleanup and dependency arrays

---

### Backend Framework: PHP 8.1 + Slim Framework 4

**Decision**: PHP 8.1 with Slim Framework 4 for lightweight REST API routing.

**Rationale**:
- PHP 8.1 provides modern features (enums, readonly properties, fibers) while maintaining wide hosting compatibility
- Slim 4 is minimalist (routes + middleware) with no ORM/template baggage - perfect for API-only backend
- Shared hosting constraint requires traditional PHP (no Node.js server-side runtime available)
- AWS SDK for PHP works seamlessly with any framework
- Low memory footprint (<50MB) suitable for shared hosting

**Alternatives Considered**:
- **Laravel**: Rejected - too heavy (full MVC framework, 100MB+ memory), unnecessary features (Eloquent ORM, Blade templates)
- **Symfony**: Rejected - heavy framework, complex configuration
- **Plain PHP (no framework)**: Considered but rejected - Slim's routing and middleware simplify API structure without bloat

**Best Practices**:
- Use PSR-7 HTTP message interfaces (Slim standard)
- Implement PSR-15 middleware for cross-cutting concerns (CORS, error handling)
- Follow PSR-12 coding standards (enforced via PHP_CodeSniffer)
- Use dependency injection container (Slim built-in) for testability

---

### UI Component Library: PrimeReact 10

**Decision**: PrimeReact 10 with PrimeIcons and Font Awesome for icons.

**Rationale**:
- **Requirement**: Explicitly specified in project constraints
- Comprehensive component library (100+ components) reduces custom UI code
- Accessible by default (WCAG AA compliance) aligns with constitution
- Theming system supports customization for NVC branding
- Active development and strong community support

**Integration Strategy**:
- Import PrimeReact CSS in main entry point
- Use tree-shaking to include only used components in bundle
- Combine PrimeIcons (included) with Font Awesome for icon coverage
- Create reusable component wrappers for common patterns (forms, dialogs)

---

### State Management: React Context (No External Library Initially)

**Decision**: Start with React Context API + `useReducer` for global state. Add external library only if justified.

**Rationale**:
- Constitution principle: Dependency minimalism - no state library unless justified
- React Context sufficient for initial scope (auth state, environment config)
- Adding Redux/Zustand/MobX later is straightforward if complexity grows
- Reduces bundle size and learning curve for new developers

**When to Reconsider**:
- More than 5 deeply nested context providers
- Performance issues from frequent context updates
- Complex async state management patterns emerge

**Best Practices**:
- Separate contexts by domain (AuthContext, ConfigContext)
- Use `useReducer` for complex state logic
- Memoize context values to prevent unnecessary re-renders
- Document when to split contexts or add external state library

---

### Environment Configuration: vlucas/phpdotenv + Vite Env Variables

**Decision**: Use `vlucas/phpdotenv` for PHP environment loading, Vite's built-in env variable support for frontend.

**Rationale**:
- `phpdotenv` is standard PHP solution (7k+ GitHub stars, widely adopted)
- Vite natively supports `.env` files with `VITE_` prefix for frontend variables
- Single `.env` file at project root reduces configuration complexity
- `.env_dist` template approach prevents accidental secret commits

**Environment Variable Strategy**:
- Backend: All vars accessible via `$_ENV` after `phpdotenv` load
- Frontend: Only `VITE_*` prefixed vars exposed (security - prevents backend secrets leaking to client)
- Validation: Backend checks required vars on startup, fails fast with clear error messages

**Best Practices**:
- Never commit `.env` (gitignored)
- Keep `.env_dist` updated with all required variables (placeholder values)
- Use descriptive comments in `.env_dist` for each variable
- Fail application startup if required variables missing

---

### DynamoDB Client: AWS SDK for PHP v3

**Decision**: AWS SDK for PHP v3 with DynamoDbClient for database operations.

**Rationale**:
- Official AWS SDK maintained by Amazon, guaranteed compatibility
- Version 3 uses PSR-7 interfaces, integrates cleanly with Slim
- No ORM requirement (constitution) - SDK provides direct table operations
- Async support via promises (future optimization path)

**Table Naming Strategy**:
- Prefix: `UpApp.<ENV>.` where ENV is `dev`, `uat`, or `prod`
- Example: `UpApp.dev.users`, `UpApp.prod.forms`
- Environment variable: `DYNAMODB_TABLE_PREFIX=UpApp` combined with `APP_ENV`

**Connection Management**:
- Single DynamoDbClient instance per request (created in dependency container)
- Credentials loaded from AWS CLI profile or `.env` (fallback)
- Region: `eu-central-1` (specified in project requirements)

---

### Testing Frameworks: Vitest (Frontend) + PHPUnit (Backend)

**Decision**: Vitest for frontend testing, PHPUnit for backend testing.

**Rationale**:
- **Vitest**: Vite-native test runner, faster than Jest, same API (easy migration if needed)
- **PHPUnit**: Industry standard for PHP testing, excellent DynamoDB mocking support
- Both support integration testing against real DynamoDB `dev` tables (constitution requirement)

**Testing Strategy**:
- Frontend: Component tests (render + interactions), integration tests (API calls)
- Backend: Unit tests (business logic), integration tests (DynamoDB operations)
- **No mocking** of DynamoDB in integration tests - use `UpApp.dev.*` tables
- Test environment variable: `APP_ENV=test` triggers test-specific configuration

---

### Code Quality Tools: ESLint + Prettier (Frontend), PHP_CodeSniffer (Backend)

**Decision**: ESLint + Prettier for TypeScript/React, PHP_CodeSniffer for PSR-12 enforcement.

**Rationale**:
- Constitution requirement: code quality from project inception
- ESLint catches logical errors and anti-patterns
- Prettier removes formatting debates (auto-format on save)
- PHP_CodeSniffer enforces PSR-12 standards (constitution requirement)

**Pre-commit Hook Strategy**:
- Use `husky` + `lint-staged` to run linters on staged files only
- Block commits with linting errors
- Auto-fix Prettier issues when possible

**Configuration**:
- ESLint: TypeScript + React plugins, extend recommended rules
- Prettier: 2-space indentation, single quotes, trailing commas
- PHP_CodeSniffer: PSR-12 ruleset, 4-space indentation

---

### Development Scripts: Cross-Platform (Bash + PowerShell)

**Decision**: Provide both Bash and PowerShell versions of infrastructure scripts (`aws-setup`, `seed`, `deploy`).

**Rationale**:
- Developer constraint: Windows/Mac/Linux support required
- Bash: Standard on Mac/Linux, available via Git Bash on Windows
- PowerShell: Native on Windows, available on Mac/Linux (PowerShell Core)
- `package.json` scripts detect platform and run appropriate version

**Script Patterns**:
- Error handling: Exit on first error (`set -e` in bash, `$ErrorActionPreference='Stop'` in PowerShell)
- Environment detection: Check `APP_ENV`, default to `dev`, prompt if `prod`
- AWS CLI validation: Check AWS CLI installed before running DynamoDB commands
- Idempotency: Scripts safe to run multiple times (check existence before creating)

---

### HTTP Client: Axios (Frontend)

**Decision**: Axios for frontend HTTP requests to backend API.

**Rationale**:
- More ergonomic than `fetch` (automatic JSON parsing, request/response interceptors)
- Built-in request cancellation (useful for search/autocomplete features)
- Consistent error handling across requests
- TypeScript support via `@types/axios`

**Configuration**:
- Base URL from environment variable: `VITE_API_URL` (defaults to `http://localhost:8080`)
- Request interceptor: Add `Authorization` header when token present
- Response interceptor: Handle common errors (401 redirect to login, 500 error toast)

**Alternatives Considered**:
- **fetch API**: Native, but less ergonomic (manual JSON parsing, verbose error handling)
- **SWR/React Query**: Deferred to future iteration (adds caching complexity)

---

### Port Configuration: 5173 (Frontend) + 8080 (Backend)

**Decision**: React dev server on port 5173 (Vite default), PHP server on port 8080.

**Rationale**:
- 5173: Vite default, widely recognized in React community
- 8080: Common alternative HTTP port, unlikely to conflict with system services
- Constitution requirement: FR-013 (frontend 5173), FR-014 (backend 8080)

**Port Conflict Handling**:
- Vite auto-increments port if 5173 taken (5174, 5175, etc.)
- PHP server: Check port availability, suggest alternatives if 8080 taken
- Document port configuration in README

---

## Open Questions

*None remaining - all technical decisions resolved for project foundation scope.*

## Next Steps

Proceed to Phase 1: Design & Contracts
- Define data-model.md (initial DynamoDB tables)
- Create contracts/health-check.md (initial API contract)
- Generate quickstart.md (developer onboarding)
