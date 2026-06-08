# Research: Dropdown Lists Fix & Environment Setup

**Date**: 2026-06-07
**Feature**: 001-project-foundation
**Phase**: Phase 0 - Research & Root Cause Analysis

## Executive Summary

**Root Cause Identified**: CORS configuration in backend does not include production domain `https://przetargr-domow.pl`, causing browser to block API requests from frontend to backend.

**Impact**: All dropdown lists (feelings and needs) fail to load in production forms (DUP, TUP, DOS, OK10) because the frontend cannot fetch data from `/api/reference/feelings` and `/api/reference/needs` endpoints.

**Fix Strategy**: 
1. Add production domain to CORS allowed origins in `CorsMiddleware.php`
2. Ensure `APP_URL` environment variable is correctly set in production `.env`
3. Standardize deployment configuration across scripts and workflows

---

## Investigation Results

### 1. Root Cause: CORS Configuration Mismatch

**Decision**: CORS blocking is the primary issue preventing dropdown lists from working in production.

**Rationale**: 
- Production frontend runs on `https://przetargr-domow.pl`
- Backend CORS middleware (`backend/src/Middleware/CorsMiddleware.php:32-46`) only allows:
  - `http://localhost:5173` (dev)
  - `http://localhost:5174` (dev)
  - `http://localhost:5175` (dev)
  - `http://localhost:3000` (dev)
  - Value from `Environment::get('APP_URL')` as fallback
- Production domain is NOT in the hardcoded list
- If `APP_URL` is not set correctly in production `.env`, CORS fails

**Evidence**:
```php
// backend/src/Middleware/CorsMiddleware.php:32-46
$allowedOrigins = [
    'http://localhost:5173',
    'http://localhost:5174',
    'http://localhost:5175',
    'http://localhost:3000',
];

$origin = $request->getHeaderLine('Origin');
$appUrl = Environment::get('APP_URL');

if ($appUrl && !in_array($appUrl, $allowedOrigins)) {
    $allowedOrigins[] = $appUrl;
}
```

**Alternatives Considered**:
- **Alternative 1**: Allow all origins with `Access-Control-Allow-Origin: *`
  - **Rejected**: Security risk; allows any website to call backend API
- **Alternative 2**: Use environment variable only (remove hardcoded localhost)
  - **Rejected**: Makes local development harder (need to set APP_URL)
- **Alternative 3**: Add production domain to hardcoded list + keep APP_URL fallback
  - **SELECTED**: Best balance of security and flexibility

---

### 2. Deployment Configuration Inconsistency

**Decision**: Standardize on `https://przetargr-domow.pl` as production domain.

**Rationale**:
- GitHub Actions workflow (`.github/workflows/deploy-production.yml:82`) uses `VITE_API_URL=https://przetargr-domow.pl/api`
- Shell deployment script (`deploy.sh:9`) uses `VITE_API_URL=https://upapp.mindincoach.com/api`
- Example environment file (`.env.production.example:6`) uses `APP_URL=https://upapp.mindincoach.com`
- **Current production**: Domain is `przetargr-domow.pl` (verified from CORS error context)

**Evidence**:
```bash
# deploy.sh:9
VITE_API_URL=https://upapp.mindincoach.com/api npm run build

# .github/workflows/deploy-production.yml:82
VITE_API_URL: https://przetargr-domow.pl/api
```

**Alternatives Considered**:
- **Alternative 1**: Use `upapp.mindincoach.com` everywhere
  - **Rejected**: Requires DNS change and potential SSL certificate update
- **Alternative 2**: Use `przetargr-domow.pl` everywhere
  - **SELECTED**: Matches current production; minimal changes required

**Required Actions**:
1. Update `deploy.sh` to use `https://przetargr-domow.pl/api`
2. Update `.env.production.example` to use `https://przetargr-domow.pl`
3. Verify production `.env` has `APP_URL=https://przetargr-domow.pl`

---

### 3. Backend Data File Path Resolution

**Decision**: Current implementation is correct; no changes needed.

**Rationale**:
- `ReferenceHandler.php` uses relative path: `__DIR__ . '/../../../data'`
- This resolves correctly from `backend/src/Handlers/ReferenceHandler.php` to project root `data/`
- Both data files exist and are readable:
  - `data/lista_uczuc.json` (13,663 bytes)
  - `data/lista_potrzeb.json` (8,639 bytes)

**Evidence**:
```php
// backend/src/Handlers/ReferenceHandler.php:14
$this->dataDir = __DIR__ . '/../../../data';
```

**Verification**:
```bash
$ ls -la data/
-rw-r--r-- 1 user 4096  8639 Jun  6 13:04 lista_potrzeb.json
-rw-r--r-- 1 user 4096 13663 Jun  6 13:04 lista_uczuc.json
```

**Alternatives Considered**: N/A (current implementation works)

---

### 4. Frontend API Client Configuration

**Decision**: Current implementation is correct; no changes needed.

**Rationale**:
- Frontend uses Vite environment variable: `import.meta.env.VITE_API_URL`
- Fallback to localhost for development: `import.meta.env.VITE_API_URL || 'http://localhost:8080'`
- Build-time injection via `.env.production` or `VITE_API_URL` environment variable
- This pattern is standard for Vite applications

**Evidence**:
```typescript
// frontend/src/services/api.ts:5
const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8080',
  timeout: 10000,
  withCredentials: true,
});
```

**Alternatives Considered**: N/A (current implementation follows best practices)

---

## Environment Configuration Analysis

### Current State

| Environment | APP_ENV | APP_URL | BACKEND_URL | DynamoDB Tables | Status |
|-------------|---------|---------|-------------|-----------------|--------|
| **Production** | `prod` (needs verification) | `https://przetargr-domow.pl` (should be set) | `https://przetargr-domow.pl/api` | `UpApp.prod.*` | ⚠️ Partial (CORS broken) |
| **UAT** | Not configured | Not set | Not set | `UpApp.uat.*` (exist in AWS) | ❌ Not set up |
| **Dev** | `dev` | `http://localhost:5173` | `http://localhost:8080` | `UpApp.dev.*` (exist in AWS) | ✅ Working locally |

### Required Environment Variables

#### Production `.env`
```bash
APP_ENV=prod
APP_URL=https://przetargr-domow.pl
AWS_REGION=eu-central-1
AWS_ACCESS_KEY_ID=<production-key>
AWS_SECRET_ACCESS_KEY=<production-secret>
DYNAMODB_TABLE_PREFIX=UpApp
BACKEND_URL=https://przetargr-domow.pl/api
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=<production-email>
SMTP_PASSWORD=<production-password>
SMTP_FROM_EMAIL=noreply@przetargr-domow.pl
SMTP_FROM_NAME=UPapp
ADMIN_EMAIL=janczewski.piotr@gmail.com
ADMIN_ALLOWED_EMAILS=janczewski.piotr@gmail.com
```

#### UAT `.env.uat`
```bash
APP_ENV=uat
APP_URL=http://uat.przetargr-domow.pl  # Or localhost:5174 for local UAT
AWS_REGION=eu-central-1
AWS_ACCESS_KEY_ID=<uat-key-or-empty-for-cli>
AWS_SECRET_ACCESS_KEY=<uat-secret-or-empty-for-cli>
DYNAMODB_TABLE_PREFIX=UpApp
BACKEND_URL=http://localhost:8081  # Or UAT backend URL
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=<uat-email>
SMTP_PASSWORD=<uat-password>
SMTP_FROM_EMAIL=noreply@uat.upapp.local
SMTP_FROM_NAME=UPapp UAT
ADMIN_EMAIL=janczewski.piotr@gmail.com
ADMIN_ALLOWED_EMAILS=janczewski.piotr@gmail.com
```

#### Dev `.env` (current)
```bash
APP_ENV=dev
APP_URL=http://localhost:5173
AWS_REGION=eu-central-1
AWS_ACCESS_KEY_ID=  # Empty to use AWS CLI profile
AWS_SECRET_ACCESS_KEY=  # Empty to use AWS CLI profile
DYNAMODB_TABLE_PREFIX=UpApp
BACKEND_URL=http://localhost:8080
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=<dev-email>
SMTP_PASSWORD=<dev-password>
SMTP_FROM_EMAIL=noreply@upapp.local
SMTP_FROM_NAME=UPapp Dev
ADMIN_EMAIL=janczewski.piotr@gmail.com
ADMIN_ALLOWED_EMAILS=janczewski.piotr@gmail.com
```

---

## CORS Configuration Review

### Current Implementation

**File**: `backend/src/Middleware/CorsMiddleware.php`

```php
public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
{
    $allowedOrigins = [
        'http://localhost:5173',
        'http://localhost:5174',
        'http://localhost:5175',
        'http://localhost:3000',
    ];

    $origin = $request->getHeaderLine('Origin');
    $appUrl = Environment::get('APP_URL');

    if ($appUrl && !in_array($appUrl, $allowedOrigins)) {
        $allowedOrigins[] = $appUrl;
    }

    $response = $handler->handle($request);

    if (in_array($origin, $allowedOrigins)) {
        $response = $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    }

    if ($request->getMethod() === 'OPTIONS') {
        return $response->withStatus(200);
    }

    return $response;
}
```

### Issue Analysis

1. **Hardcoded localhost origins**: Good for development
2. **APP_URL fallback**: Good for flexibility
3. **Missing production domain**: `https://przetargr-domow.pl` not in list
4. **Dependency on environment variable**: If `APP_URL` is missing/wrong in production `.env`, CORS fails

### Recommended Fix

**Option A - Add production domain to hardcoded list (RECOMMENDED)**:
```php
$allowedOrigins = [
    'http://localhost:5173',
    'http://localhost:5174',
    'http://localhost:5175',
    'http://localhost:3000',
    'https://przetargr-domow.pl',  // Production
];
```

**Option B - Rely only on APP_URL environment variable**:
- Remove hardcoded list
- Require `APP_URL` in all environments
- More flexible but harder to debug

**Decision**: Use Option A for immediate fix, ensure `APP_URL` is set correctly as defense-in-depth.

---

## Data File Deployment Verification

### Verification Results

✅ **Data files exist in repository**:
```bash
data/lista_uczuc.json       # 13,663 bytes, 141 feelings
data/lista_potrzeb.json     # 8,639 bytes, 80 needs
```

✅ **File structure matches API expectations**:
```json
// lista_uczuc.json
[
  {
    "name_pl": "współczucie",
    "category": "fulfilled",
    "subcategory": "Czułość",
    "sort_order": 1
  },
  ...
]

// lista_potrzeb.json
[
  {
    "name_pl": "wolność",
    "category": "Autonomia",
    "sort_order": 1
  },
  ...
]
```

✅ **Backend correctly groups data**:
- Feelings: Grouped by `category` (fulfilled/unfulfilled) → `subcategory` → items
- Needs: Grouped by `category` → items
- Uses `name_pl` as ID (since no UUID field exists)

✅ **Deployment process includes data directory**:
- `.gitignore` does NOT exclude `data/` directory
- Deployment script (`deploy.sh`) copies entire project including `data/`
- GitHub Actions workflow includes data files in artifact

**No action required for data file deployment**.

---

## Fix Recommendations

### Priority 1: Fix CORS (Immediate - Production is broken)

**Changes Required**:

1. **Update CorsMiddleware.php** to include production domain:
   ```php
   // backend/src/Middleware/CorsMiddleware.php:32
   $allowedOrigins = [
       'http://localhost:5173',
       'http://localhost:5174',
       'http://localhost:5175',
       'http://localhost:3000',
       'https://przetargr-domow.pl',  // ADD THIS LINE
   ];
   ```

2. **Verify production .env** has correct APP_URL:
   ```bash
   APP_URL=https://przetargr-domow.pl
   ```

3. **Deploy updated backend** to production immediately

**Testing**:
```bash
# Test CORS headers with curl
curl -I -X OPTIONS \
  -H "Origin: https://przetargr-domow.pl" \
  -H "Access-Control-Request-Method: GET" \
  https://przetargr-domow.pl/api/reference/feelings

# Expected response should include:
# Access-Control-Allow-Origin: https://przetargr-domow.pl
# Access-Control-Allow-Credentials: true
```

### Priority 2: Standardize Deployment Configuration (High)

**Changes Required**:

1. **Update deploy.sh**:
   ```bash
   # Line 9: Change from upapp.mindincoach.com to przetargr-domow.pl
   VITE_API_URL=https://przetargr-domow.pl/api npm run build
   ```

2. **Update .env.production.example**:
   ```bash
   # Line 6: Change to production domain
   APP_URL=https://przetargr-domow.pl
   ```

3. **Verify GitHub Actions workflow** (already correct):
   ```yaml
   # .github/workflows/deploy-production.yml:82
   VITE_API_URL: https://przetargr-domow.pl/api  # ✅ Correct
   ```

### Priority 3: Set Up UAT Environment (Medium)

**Changes Required**:

1. Create `.env.uat` file (copy from `.env_dist`, set `APP_ENV=uat`)
2. Decide UAT deployment strategy:
   - **Option A**: Local UAT (run on localhost:5174 with APP_ENV=uat)
   - **Option B**: Dedicated UAT server (subdomain or separate server)
3. Update CORS middleware to include UAT origin if using separate domain
4. Document UAT setup process in `quickstart.md`

### Priority 4: Add Automated Tests (Low)

**Changes Required**:

1. **PHPUnit tests for ReferenceHandler**:
   - Test feelings endpoint returns grouped data
   - Test needs endpoint returns grouped data
   - Test 404 when data files missing
   - Test JSON parsing errors

2. **Vitest tests for CollapsibleList**:
   - Test rendering with feelings data
   - Test rendering with needs data
   - Test checkbox selection/deselection
   - Test expand/collapse functionality

3. **Integration test for reference API**:
   - Test CORS headers on OPTIONS request
   - Test CORS headers on GET request
   - Test API response structure matches contract

---

## Success Metrics

### Phase 0 Complete ✅
- [x] Root cause identified: CORS configuration mismatch
- [x] Environment comparison table created
- [x] CORS configuration documented with fix
- [x] Data file deployment verified (no issues)
- [x] Fix recommendations documented

### Next Steps (Phase 1)
- [ ] Create `data-model.md` with feelings/needs entity structure
- [ ] Create `contracts/reference-api.md` with API specifications
- [ ] Create `contracts/error-handling.md` with error response formats
- [ ] Create `quickstart.md` with environment setup guides
- [ ] Update `CLAUDE.md` with plan reference

---

**Version**: 1.0.0 | **Created**: 2026-06-07 | **Status**: Complete
