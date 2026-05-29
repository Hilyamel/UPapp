# Phase 1.5: Environment Configuration & Validation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Configure and validate all environment variables for development, create validation utilities

**Architecture:** Environment files for backend and frontend, validation scripts, secure credential management

**Tech Stack:** vlucas/phpdotenv (PHP), Vite env (React), openssl (secrets), bash (validation)

---

## Backend Environment Setup

### Task 1: Create Backend Environment Files

**Files:**
- Create: `backend/.env.example`
- Create: `backend/.env`

- [ ] **Step 1: Create backend .env.example**

```env
# Environment
APP_ENV=development
APP_DEBUG=true

# AWS
AWS_REGION=eu-central-1
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
DYNAMODB_ENDPOINT=
DYNAMODB_TABLE_PREFIX=UpApp.dev

# JWT
JWT_SECRET=
JWT_EXPIRY=604800

# Google OAuth
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://localhost:5173/auth/google/callback

# Email
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=
SMTP_PASSWORD=
SMTP_FROM_EMAIL=noreply@upapp.local
SMTP_FROM_NAME=UPapp

# Admin
ADMIN_EMAIL=janczewski.piotr@gmail.com

# Frontend
FRONTEND_URL=http://localhost:5173

# CORS
CORS_ALLOWED_ORIGINS=http://localhost:5173,http://localhost:3000

# Security
RATE_LIMIT_ENABLED=true
RATE_LIMIT_MAX_REQUESTS=100
RATE_LIMIT_WINDOW=60
```

Write to: `backend/.env.example`

Run: `wc -l backend/.env.example`
Expected: ~40 lines

- [ ] **Step 2: Copy to actual .env file**

```bash
cp backend/.env.example backend/.env
```

Run: `test -f backend/.env && echo "Created"`
Expected: "Created"

- [ ] **Step 3: Generate JWT secret**

```bash
openssl rand -base64 32
```

Expected: Outputs 44-char base64 string (e.g., "8h3kJd9sK2mNv4bP...")
Action: Copy this value

- [ ] **Step 4: Add JWT secret to backend/.env**

Edit `backend/.env` and replace the empty JWT_SECRET line:
```env
JWT_SECRET=<paste-the-generated-secret-here>
```

Run: `grep JWT_SECRET backend/.env | grep -v '^#' | wc -c`
Expected: Number > 40 (secret is set)

- [ ] **Step 5: Update AWS credentials in backend/.env**

If using AWS CLI profile, leave empty:
```env
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
```

If using explicit credentials, add them:
```env
AWS_ACCESS_KEY_ID=your-actual-key-id
AWS_SECRET_ACCESS_KEY=your-actual-secret
```

Run: `grep AWS_REGION backend/.env`
Expected: AWS_REGION=eu-central-1

- [ ] **Step 6: Commit example file only**

```bash
git add backend/.env.example
git commit -m "feat: add backend environment configuration template

- Create .env.example with all required variables
- Include AWS, JWT, OAuth, SMTP settings
- Document each section with comments
- Actual .env excluded from git

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>"
```

Run: `git log --oneline -1`
Expected: Shows commit about backend env

---

### Task 2: Install Backend Dotenv Package

**Files:**
- Modify: `backend/composer.json`

- [ ] **Step 1: Add vlucas/phpdotenv dependency**

```bash
cd backend
composer require vlucas/phpdotenv:^5.6
cd ..
```

Expected: Package installed, composer.json and composer.lock updated

- [ ] **Step 2: Test .env loading**

Create test file `backend/test-env.php`:
```php
<?php
require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "APP_ENV: " . ($_ENV['APP_ENV'] ?? 'NOT SET') . "\n";
echo "JWT_SECRET: " . (isset($_ENV['JWT_SECRET']) ? 'SET (length: ' . strlen($_ENV['JWT_SECRET']) . ')' : 'NOT SET') . "\n";
echo "AWS_REGION: " . ($_ENV['AWS_REGION'] ?? 'NOT SET') . "\n";
echo "DYNAMODB_TABLE_PREFIX: " . ($_ENV['DYNAMODB_TABLE_PREFIX'] ?? 'NOT SET') . "\n";
```

Run: `php backend/test-env.php`
Expected: Shows all variables as SET with correct values

- [ ] **Step 3: Remove test file**

```bash
rm backend/test-env.php
```

- [ ] **Step 4: Commit dotenv package**

```bash
git add backend/composer.json backend/composer.lock
git commit -m "feat: add environment variable loading for backend

- Install vlucas/phpdotenv ^5.6
- Enables .env file loading in PHP
- Test confirms variables load correctly

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>"
```

---

## Frontend Environment Setup

### Task 3: Create Frontend Environment Files

**Files:**
- Create: `frontend/.env.example`
- Create: `frontend/.env`

- [ ] **Step 1: Create frontend .env.example**

```env
# API Base URL
VITE_API_BASE_URL=http://localhost:8080/api/v1

# Google OAuth Client ID (public, safe to expose)
VITE_GOOGLE_CLIENT_ID=

# Admin Email (for display purposes only)
VITE_ADMIN_EMAIL=janczewski.piotr@gmail.com
```

Write to: `frontend/.env.example`

Run: `cat frontend/.env.example | grep VITE_`
Expected: Shows 3 VITE_ variables

- [ ] **Step 2: Copy to actual .env**

```bash
cp frontend/.env.example frontend/.env
```

Run: `test -f frontend/.env && echo "Created"`
Expected: "Created"

- [ ] **Step 3: Update frontend/.env (if you have Google OAuth)**

Edit `frontend/.env`:
```env
VITE_GOOGLE_CLIENT_ID=your-actual-client-id.apps.googleusercontent.com
```

For now, you can leave empty and add later when setting up OAuth.

Run: `cat frontend/.env`
Expected: Shows VITE_ variables

- [ ] **Step 4: Commit example file**

```bash
git add frontend/.env.example
git commit -m "feat: add frontend environment configuration template

- Create .env.example with VITE_ prefixed variables
- Include API URL and Google OAuth client ID
- Vite automatically loads VITE_ variables

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>"
```

---

## Configuration Validation

### Task 4: Create Backend Config Validator

**Files:**
- Create: `backend/src/Utils/ConfigValidator.php`

- [ ] **Step 1: Create Utils directory**

```bash
mkdir -p backend/src/Utils
```

- [ ] **Step 2: Write ConfigValidator class**

```php
<?php
namespace UpApp\Utils;

class ConfigValidator
{
    private array $errors = [];

    public function validate(): array
    {
        $this->checkRequired();
        $this->checkJwtSecret();
        $this->checkEnvironment();
        $this->checkEmail();
        $this->checkTablePrefix();
        
        return $this->errors;
    }

    private function checkRequired(): void
    {
        $required = [
            'APP_ENV',
            'JWT_SECRET',
            'AWS_REGION',
            'DYNAMODB_TABLE_PREFIX',
            'ADMIN_EMAIL',
            'FRONTEND_URL',
            'CORS_ALLOWED_ORIGINS',
        ];

        foreach ($required as $var) {
            if (empty($_ENV[$var])) {
                $this->errors[] = "Missing required variable: {$var}";
            }
        }
    }

    private function checkJwtSecret(): void
    {
        $secret = $_ENV['JWT_SECRET'] ?? '';
        
        if (strlen($secret) < 32) {
            $this->errors[] = "JWT_SECRET must be at least 32 characters (current: " . strlen($secret) . ")";
        }
    }

    private function checkEnvironment(): void
    {
        $validEnvs = ['development', 'uat', 'production'];
        $env = $_ENV['APP_ENV'] ?? '';
        
        if (!in_array($env, $validEnvs)) {
            $this->errors[] = "APP_ENV must be one of: " . implode(', ', $validEnvs) . " (current: {$env})";
        }
    }

    private function checkEmail(): void
    {
        $email = $_ENV['ADMIN_EMAIL'] ?? '';
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = "ADMIN_EMAIL must be a valid email address (current: {$email})";
        }
    }

    private function checkTablePrefix(): void
    {
        $prefix = $_ENV['DYNAMODB_TABLE_PREFIX'] ?? '';
        
        if (!preg_match('/^UpApp\.(dev|uat|prod)$/', $prefix)) {
            $this->errors[] = "DYNAMODB_TABLE_PREFIX must match pattern 'UpApp.(dev|uat|prod)' (current: {$prefix})";
        }
    }

    public static function validateOrDie(): void
    {
        $validator = new self();
        $errors = $validator->validate();

        if (!empty($errors)) {
            echo "Configuration Errors:\n";
            foreach ($errors as $error) {
                echo "  ✗ {$error}\n";
            }
            exit(1);
        }

        echo "✓ Configuration valid\n";
    }
}
```

Write to: `backend/src/Utils/ConfigValidator.php`

Run: `test -f backend/src/Utils/ConfigValidator.php && echo "Created"`
Expected: "Created"

- [ ] **Step 3: Create validation test script**

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use UpApp\Utils\ConfigValidator;

// Load environment
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Validate
ConfigValidator::validateOrDie();

echo "All configuration checks passed!\n";
```

Write to: `backend/validate-config.php`

Run: `php backend/validate-config.php`
Expected: "✓ Configuration valid" and "All configuration checks passed!"

- [ ] **Step 4: Test validation with missing variable**

Temporarily comment out JWT_SECRET in backend/.env:
```bash
sed -i.bak 's/^JWT_SECRET=/#JWT_SECRET=/' backend/.env
```

Run: `php backend/validate-config.php 2>&1`
Expected: Shows error "Missing required variable: JWT_SECRET"

Restore JWT_SECRET:
```bash
mv backend/.env.bak backend/.env
```

- [ ] **Step 5: Commit validator**

```bash
git add backend/src/Utils/ConfigValidator.php backend/validate-config.php
git commit -m "feat: add configuration validation utility

- Create ConfigValidator class
- Check required variables exist
- Validate JWT secret length
- Validate email format
- Validate table prefix pattern
- Add validation test script

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>"
```

---

### Task 5: Create Environment Verification Script

**Files:**
- Create: `scripts/verify-env.sh`

- [ ] **Step 1: Write verification script**

```bash
#!/bin/bash

echo "================================"
echo "Environment Verification"
echo "================================"
echo ""

ALL_OK=true

# Check root .env
echo "Checking root .env..."
if [ -f .env ]; then
    echo "  ✓ .env exists"
    if grep -q "JWT_SECRET=.\{30,\}" .env; then
        echo "  ✓ JWT_SECRET configured"
    else
        echo "  ✗ JWT_SECRET not configured or too short"
        ALL_OK=false
    fi
else
    echo "  ✗ .env not found"
    ALL_OK=false
fi
echo ""

# Check backend .env
echo "Checking backend/.env..."
if [ -f backend/.env ]; then
    echo "  ✓ backend/.env exists"
    
    # Check JWT secret
    if grep -q "JWT_SECRET=.\{30,\}" backend/.env; then
        echo "  ✓ JWT_SECRET configured"
    else
        echo "  ✗ JWT_SECRET not configured or too short"
        ALL_OK=false
    fi
    
    # Check AWS region
    if grep -q "AWS_REGION=eu-central-1" backend/.env; then
        echo "  ✓ AWS_REGION configured"
    else
        echo "  ✗ AWS_REGION not configured"
        ALL_OK=false
    fi
    
    # Check table prefix
    if grep -q "DYNAMODB_TABLE_PREFIX=UpApp\." backend/.env; then
        echo "  ✓ DYNAMODB_TABLE_PREFIX configured"
    else
        echo "  ✗ DYNAMODB_TABLE_PREFIX not configured"
        ALL_OK=false
    fi
else
    echo "  ✗ backend/.env not found"
    ALL_OK=false
fi
echo ""

# Check frontend .env
echo "Checking frontend/.env..."
if [ -f frontend/.env ]; then
    echo "  ✓ frontend/.env exists"
    
    if grep -q "VITE_API_BASE_URL=" frontend/.env; then
        echo "  ✓ VITE_API_BASE_URL configured"
    else
        echo "  ✗ VITE_API_BASE_URL not configured"
        ALL_OK=false
    fi
else
    echo "  ✗ frontend/.env not found"
    ALL_OK=false
fi
echo ""

# Run backend config validator
echo "Running backend configuration validator..."
if [ -f backend/validate-config.php ]; then
    if php backend/validate-config.php > /dev/null 2>&1; then
        echo "  ✓ Backend configuration valid"
    else
        echo "  ✗ Backend configuration invalid"
        php backend/validate-config.php
        ALL_OK=false
    fi
else
    echo "  ⚠ Validator not found (expected after backend setup)"
fi
echo ""

# Summary
echo "================================"
if [ "$ALL_OK" = true ]; then
    echo "✓ All environment checks passed"
    exit 0
else
    echo "✗ Some environment checks failed"
    echo ""
    echo "To fix:"
    echo "  1. Ensure .env files exist (copy from .env.example/.env_dist)"
    echo "  2. Generate JWT secret: openssl rand -base64 32"
    echo "  3. Configure AWS credentials"
    echo "  4. Update DYNAMODB_TABLE_PREFIX"
    exit 1
fi
```

Write to: `scripts/verify-env.sh`

Run: `test -f scripts/verify-env.sh && echo "Created"`
Expected: "Created"

- [ ] **Step 2: Make executable**

```bash
chmod +x scripts/verify-env.sh
```

- [ ] **Step 3: Run verification**

```bash
bash scripts/verify-env.sh
```

Expected: All checks pass with "✓ All environment checks passed"

- [ ] **Step 4: Add npm script**

Edit `package.json` and add to scripts section:
```json
"env:verify": "bash scripts/verify-env.sh"
```

Run: `npm run env:verify`
Expected: All checks pass

- [ ] **Step 5: Commit verification script**

```bash
git add scripts/verify-env.sh package.json
git commit -m "feat: add environment verification script

- Check all .env files exist
- Verify JWT secret configured
- Validate AWS settings
- Run backend config validator
- Add npm script for easy verification

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>"
```

---

## Security Best Practices

### Task 6: Document Security Guidelines

**Files:**
- Create: `docs/SECURITY.md`

- [ ] **Step 1: Create security documentation**

```markdown
# Security Guidelines

## Environment Variables

### Never Commit Secrets

❌ **DO NOT** commit these files:
- `.env`
- `.env.local`
- `backend/.env`
- `frontend/.env`

✅ **DO** commit:
- `.env_dist`
- `.env.example`
- `backend/.env.example`
- `frontend/.env.example`

### Verify .gitignore

Run: `git status`

If you see `.env` files listed, they are NOT properly ignored!

### Strong Secrets

**JWT Secret Requirements:**
- Minimum 32 characters
- Use cryptographically secure random generation
- Different secret per environment

Generate: `openssl rand -base64 32`

### Secret Rotation

Rotate secrets every 90 days:
- JWT_SECRET
- AWS_ACCESS_KEY_ID / AWS_SECRET_ACCESS_KEY
- SMTP_PASSWORD

## AWS Security

### Use IAM Roles (Preferred)

Instead of access keys, use AWS CLI profiles:
```bash
aws configure --profile upapp
```

Then leave empty in `.env`:
```env
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
```

### Minimum Permissions

IAM user should have only:
- DynamoDB: CreateTable, DescribeTable, PutItem, GetItem, Query, Scan, UpdateItem, DeleteItem
- DynamoDB: UpdateTimeToLive

No other AWS services needed.

## Frontend Security

### Only VITE_ Variables Exposed

Frontend `.env` variables are PUBLIC. Never put:
- ❌ API keys
- ❌ Secrets
- ❌ AWS credentials
- ❌ JWT secrets

Only safe values:
- ✅ API base URL
- ✅ Google OAuth Client ID (public)
- ✅ Admin email (display only)

## Email Security

### Gmail App Passwords

For SMTP with Gmail:
1. Enable 2FA on Google account
2. Generate App Password: https://myaccount.google.com/apppasswords
3. Use App Password in `SMTP_PASSWORD`, NOT your regular password

## Environment-Specific Settings

### Development
```env
APP_ENV=development
APP_DEBUG=true
CORS_ALLOWED_ORIGINS=http://localhost:5173,http://localhost:3000
```

### Production
```env
APP_ENV=production
APP_DEBUG=false
CORS_ALLOWED_ORIGINS=https://yourdomain.com
```

**Production Requirements:**
- HTTPS only
- Debug disabled
- Strict CORS
- Strong secrets (unique from dev)
- Rate limiting enabled

## Incident Response

If secrets are committed to git:

1. **Rotate immediately** - Generate new secrets
2. **Revoke old** - Deactivate leaked AWS keys, change passwords
3. **Clean git history** - Use `git filter-branch` or BFG Repo-Cleaner
4. **Notify team** - If leaked to remote repository

## Checklist

Before deploying:
- [ ] All secrets unique and strong
- [ ] `.env` files not in git
- [ ] Frontend variables safe (no secrets)
- [ ] HTTPS enforced in production
- [ ] Debug mode disabled in production
- [ ] CORS restricted to production domain
- [ ] Rate limiting enabled
- [ ] AWS credentials minimal permissions
```

Write to: `docs/SECURITY.md`

Run: `wc -l docs/SECURITY.md`
Expected: ~100+ lines

- [ ] **Step 2: Verify .gitignore properly excludes .env**

```bash
git check-ignore -v .env backend/.env frontend/.env
```

Expected: Shows all three files are ignored with line numbers from .gitignore

- [ ] **Step 3: Commit security documentation**

```bash
git add docs/SECURITY.md
git commit -m "docs: add security guidelines

- Document secret management best practices
- Explain environment variable security
- AWS IAM and permission guidelines
- Email security with Gmail App Passwords
- Incident response procedures

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>"
```

---

### Task 7: Final Environment Validation

**Files:**
- None (validation only)

- [ ] **Step 1: Run full environment verification**

```bash
npm run env:verify
```

Expected: "✓ All environment checks passed"

- [ ] **Step 2: Verify backend can load environment**

```bash
php backend/validate-config.php
```

Expected: "✓ Configuration valid"

- [ ] **Step 3: Check no secrets in git**

```bash
git log --all --full-history --source --pretty=format: --name-only --diff-filter=A | grep -E '\.env$' | grep -v example | grep -v dist
```

Expected: Empty output (no .env files in history)

- [ ] **Step 4: Verify JWT secret strength**

```bash
grep JWT_SECRET backend/.env | grep -v '^#' | cut -d'=' -f2 | wc -c
```

Expected: Number > 40 (includes newline)

- [ ] **Step 5: Create Phase 1.5 completion tag**

```bash
git tag phase-1.5-complete
git log --oneline --graph --decorate | head -20
```

Expected: Shows tags for phase-1, phase-2, phase-1.5-complete

---

## Environment Variables Reference

### Backend Environment Variables

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `APP_ENV` | Yes | - | Environment: development\|uat\|production |
| `APP_DEBUG` | No | true | Enable debug mode |
| `JWT_SECRET` | Yes | - | JWT signing key (min 32 chars) |
| `JWT_EXPIRY` | No | 604800 | JWT expiry seconds (7 days) |
| `AWS_REGION` | Yes | - | AWS region (eu-central-1) |
| `DYNAMODB_TABLE_PREFIX` | Yes | - | Table prefix (UpApp.dev) |
| `ADMIN_EMAIL` | Yes | - | Admin user email |
| `FRONTEND_URL` | Yes | - | Frontend URL for CORS |
| `CORS_ALLOWED_ORIGINS` | Yes | - | Comma-separated origins |
| `GOOGLE_CLIENT_ID` | No | - | Google OAuth client ID |
| `GOOGLE_CLIENT_SECRET` | No | - | Google OAuth secret |
| `SMTP_HOST` | No | - | SMTP server hostname |
| `SMTP_PORT` | No | 587 | SMTP server port |
| `SMTP_USERNAME` | No | - | SMTP username |
| `SMTP_PASSWORD` | No | - | SMTP password |

### Frontend Environment Variables

| Variable | Required | Description |
|----------|----------|-------------|
| `VITE_API_BASE_URL` | Yes | Backend API URL |
| `VITE_GOOGLE_CLIENT_ID` | No | Google OAuth client ID (public) |
| `VITE_ADMIN_EMAIL` | No | Admin email for display |

---

## Troubleshooting

### "JWT_SECRET not found"

```bash
# Check if .env exists
ls -la backend/.env

# Copy from example
cp backend/.env.example backend/.env

# Generate and add secret
openssl rand -base64 32
# Edit backend/.env and paste
```

### "Configuration invalid"

```bash
# Run validator to see specific errors
php backend/validate-config.php

# Common fixes:
# - JWT_SECRET too short (min 32 chars)
# - Invalid email format
# - Wrong table prefix pattern
```

### "AWS credentials not found"

```bash
# Option 1: Use AWS CLI (recommended)
aws configure

# Option 2: Add to backend/.env
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
```

### Frontend can't access environment variables

```bash
# Ensure variables prefixed with VITE_
grep VITE_ frontend/.env

# Restart dev server after .env changes
npm run dev:frontend
```

---

## Next Steps

Phase 1.5 complete! Environment configured and validated. Continue with:

1. **[03-backend-setup.md](03-backend-setup.md)** - Setup PHP Slim Framework
2. **[02-frontend-setup.md](02-frontend-setup.md)** - Initialize React application
3. **[05-authentication.md](05-authentication.md)** - Implement authentication
