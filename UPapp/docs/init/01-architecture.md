# Phase 1: Foundation & Architecture Setup

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create project structure, initialize dependencies, and configure development environment for UPapp

**Architecture:** Monorepo with frontend (React + Vite), backend (PHP Slim), shared scripts (Python), following repository pattern with dependency injection

**Tech Stack:** Node.js 18+, PHP 8.1+, Composer, AWS CLI, Git

---

## Prerequisites Check

Before starting, verify you have required tools installed.

### Task 1: Verify Development Tools

**Files:**
- None (system check only)

- [ ] **Step 1: Check Node.js version**

Run: `node --version`
Expected: v18.0.0 or higher

- [ ] **Step 2: Check PHP version**

Run: `php --version`
Expected: PHP 8.1.0 or higher

- [ ] **Step 3: Check Composer**

Run: `composer --version`
Expected: Composer version 2.x

- [ ] **Step 4: Check AWS CLI**

Run: `aws --version`
Expected: aws-cli/2.x or higher

- [ ] **Step 5: Check Python**

Run: `python --version`
Expected: Python 3.8 or higher

If any tool is missing, install before proceeding.

---

## Project Initialization

### Task 2: Create Root Project Structure

**Files:**
- Create: `package.json`
- Create: `.gitignore`
- Create: `README.md`
- Create: `.env_dist`

- [ ] **Step 1: Initialize git repository**

```bash
git init
git config user.name "Your Name"
git config user.email "your@email.com"
```

Run: `git status`
Expected: "On branch main" or "On branch master"

- [ ] **Step 2: Create .gitignore**

```gitignore
# Environment
.env
.env.local
.env.*.local

# Dependencies
node_modules/
vendor/

# Build outputs
dist/
build/
frontend/dist/

# Logs
logs/
*.log
backend/logs/

# OS files
.DS_Store
Thumbs.db

# IDE
.vscode/
.idea/
*.swp
*.swo

# Python
__pycache__/
*.pyc
venv/
```

Run: `cat .gitignore | head -5`
Expected: First 5 lines of gitignore visible

- [ ] **Step 3: Create root package.json**

```json
{
  "name": "upapp",
  "version": "1.0.0",
  "description": "NVC Forms Management Application",
  "private": true,
  "workspaces": [
    "frontend"
  ],
  "scripts": {
    "dev": "concurrently \"npm run dev:backend\" \"npm run dev:frontend\"",
    "dev:backend": "cd backend && php -S localhost:8080 -t public",
    "dev:frontend": "cd frontend && npm run dev",
    "build": "cd frontend && npm run build",
    "gui": "python scripts/gui/launcher.py",
    "db:create": "bash scripts/dynamodb/create-tables.sh dev",
    "db:seed": "php scripts/dynamodb/seed-data.php",
    "deploy": "node scripts/deploy/sftp-upload.js"
  },
  "devDependencies": {
    "concurrently": "^8.2.2"
  }
}
```

Run: `cat package.json | grep -E '(name|version)'`
Expected: Shows "upapp" and "1.0.0"

- [ ] **Step 4: Create README.md**

```markdown
# UPapp - NVC Forms Management

Nonviolent Communication forms application with React frontend and PHP backend.

## Quick Start

1. Copy environment template: `cp .env_dist .env`
2. Edit `.env` with your configuration
3. Install dependencies: `npm install && cd frontend && npm install && cd ../backend && composer install`
4. Create database tables: `npm run db:create`
5. Seed reference data: `npm run db:seed`
6. Start development servers: `npm run dev`

## Project Structure

- `frontend/` - React application (Vite)
- `backend/` - PHP Slim Framework API
- `scripts/` - Deployment and database scripts
- `data/` - Reference data (feelings, needs)
- `docs/init/` - Implementation plans

## Documentation

See `docs/init/plan.md` for full implementation plan.
```

Run: `head -3 README.md`
Expected: "# UPapp - NVC Forms Management"

- [ ] **Step 5: Commit initial structure**

```bash
git add .gitignore package.json README.md
git commit -m "feat: initialize project structure

- Add root package.json with npm scripts
- Add .gitignore for common excludes
- Add README with quick start guide

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>"
```

Run: `git log --oneline -1`
Expected: Shows commit message starting with "feat: initialize"

---

### Task 3: Create Directory Structure

**Files:**
- Create: `frontend/` directory
- Create: `backend/` directory
- Create: `scripts/` directory structure
- Create: `data/` directory

- [ ] **Step 1: Create all directories**

```bash
mkdir -p frontend
mkdir -p backend/{public,src/{Controllers,Services,Repositories,Models,Middleware,Utils},config,logs}
mkdir -p scripts/{gui,dynamodb,deploy}
mkdir -p data
mkdir -p docs/init
```

Run: `tree -L 2 -d`
Expected: Shows directory structure with frontend, backend, scripts, data

- [ ] **Step 2: Create backend public/index.php placeholder**

```php
<?php
// Entry point - will be implemented in Phase 3
echo json_encode(['status' => 'ok', 'message' => 'Backend entry point']);
```

Write to: `backend/public/index.php`

Run: `php backend/public/index.php`
Expected: `{"status":"ok","message":"Backend entry point"}`

- [ ] **Step 3: Verify directory structure**

Run: `ls -la`
Expected: Shows frontend/, backend/, scripts/, data/, docs/

- [ ] **Step 4: Commit directory structure**

```bash
git add frontend/ backend/ scripts/ data/ docs/
git commit -m "feat: create project directory structure

- Add frontend/ for React application
- Add backend/ with Slim Framework structure
- Add scripts/ for deployment and database tools
- Add data/ for reference data
- Add docs/init/ for implementation plans

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>"
```

Run: `git log --oneline -2`
Expected: Shows last 2 commits including this one

---

### Task 4: Install Root Dependencies

**Files:**
- Modify: `package.json`

- [ ] **Step 1: Install root dependencies**

```bash
npm install
```

Run: `ls node_modules/ | head -5`
Expected: Shows installed packages including concurrently

- [ ] **Step 2: Verify npm scripts work**

Run: `npm run --silent | grep dev`
Expected: Shows dev scripts (dev, dev:backend, dev:frontend)

- [ ] **Step 3: Commit package-lock.json**

```bash
git add package-lock.json
git commit -m "chore: install root dependencies

- Install concurrently for parallel dev servers

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>"
```

Run: `git log --oneline -1`
Expected: Shows "chore: install root dependencies"

---

## Environment Configuration

### Task 5: Create Environment Template

**Files:**
- Create: `.env_dist`

- [ ] **Step 1: Write .env_dist template**

```env
#############################################
# UPapp Environment Configuration Template
# Copy to .env and fill in your values
#############################################

# Environment
APP_ENV=development
APP_DEBUG=true

# AWS Configuration
AWS_REGION=eu-central-1
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
DYNAMODB_ENDPOINT=
DYNAMODB_TABLE_PREFIX=UpApp.dev

# JWT Authentication
JWT_SECRET=
JWT_EXPIRY=604800

# Google OAuth 2.0
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://localhost:5173/auth/google/callback

# Email Configuration
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=
SMTP_PASSWORD=
SMTP_FROM_EMAIL=noreply@upapp.local
SMTP_FROM_NAME=UPapp

# Admin Configuration
ADMIN_EMAIL=janczewski.piotr@gmail.com

# Frontend URL
FRONTEND_URL=http://localhost:5173

# CORS Configuration
CORS_ALLOWED_ORIGINS=http://localhost:5173,http://localhost:3000

# Security
RATE_LIMIT_ENABLED=true
RATE_LIMIT_MAX_REQUESTS=100
RATE_LIMIT_WINDOW=60

# SFTP Deployment
SFTP_HOST=
SFTP_PORT=22
SFTP_USERNAME=
SFTP_PASSWORD=
SFTP_REMOTE_PATH=/var/www/html/upapp
```

Write to: `.env_dist`

Run: `wc -l .env_dist`
Expected: Shows line count (~50 lines)

- [ ] **Step 2: Create actual .env file**

```bash
cp .env_dist .env
```

Run: `ls -la .env`
Expected: Shows .env file exists

- [ ] **Step 3: Generate JWT secret**

```bash
openssl rand -base64 32
```

Expected: Outputs 44-character base64 string
Action: Copy this value and add to `.env` as `JWT_SECRET=<value>`

- [ ] **Step 4: Verify .env is gitignored**

```bash
git status
```

Expected: .env should NOT appear in untracked files (because .gitignore excludes it)

- [ ] **Step 5: Commit environment template**

```bash
git add .env_dist
git commit -m "feat: add environment configuration template

- Create .env_dist with all required variables
- Include AWS, JWT, OAuth, SMTP, admin settings
- Document each variable with comments
- .env excluded from git via .gitignore

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>"
```

Run: `git log --oneline -1`
Expected: Shows commit about environment template

---

## Reference Data Setup

### Task 6: Create Reference Data Files

**Files:**
- Create: `data/lista_uczuc.json`
- Create: `data/lista_potrzeb.json`

- [ ] **Step 1: Create feelings reference data**

```json
[
  {
    "name_pl": "radosny",
    "category": "fulfilled",
    "subcategory": "Radość",
    "sort_order": 1
  },
  {
    "name_pl": "zadowolony",
    "category": "fulfilled",
    "subcategory": "Radość",
    "sort_order": 2
  },
  {
    "name_pl": "zmartwiony",
    "category": "unfulfilled",
    "subcategory": "Zmartwienie",
    "sort_order": 3
  }
]
```

Write to: `data/lista_uczuc.json`
Note: This is a minimal sample. Full list has ~200 items.

Run: `cat data/lista_uczuc.json | jq length`
Expected: 3

- [ ] **Step 2: Create needs reference data**

```json
[
  {
    "name_pl": "autonomia",
    "category": "Autonomia",
    "sort_order": 1
  },
  {
    "name_pl": "wolność wyboru",
    "category": "Autonomia",
    "sort_order": 2
  },
  {
    "name_pl": "zrozumienie",
    "category": "Komunikacja",
    "sort_order": 3
  }
]
```

Write to: `data/lista_potrzeb.json`
Note: This is a minimal sample. Full list has ~100 items.

Run: `cat data/lista_potrzeb.json | jq length`
Expected: 3

- [ ] **Step 3: Validate JSON files**

```bash
jq empty data/lista_uczuc.json && echo "Valid JSON"
jq empty data/lista_potrzeb.json && echo "Valid JSON"
```

Expected: "Valid JSON" printed twice

- [ ] **Step 4: Commit reference data**

```bash
git add data/
git commit -m "feat: add reference data for feelings and needs

- Add lista_uczuc.json with feelings (sample data)
- Add lista_potrzeb.json with needs (sample data)
- Data in Polish language for NVC forms
- Full lists to be added later

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>"
```

Run: `git log --oneline -1`
Expected: Shows commit about reference data

---

## Phase 1 Completion

### Task 7: Verify Phase 1 Setup

**Files:**
- None (verification only)

- [ ] **Step 1: Check git history**

Run: `git log --oneline`
Expected: Shows all commits from Phase 1 (6-7 commits)

- [ ] **Step 2: Verify directory structure**

Run: `tree -L 2 -d`
Expected: Shows complete directory structure

- [ ] **Step 3: Check environment file**

Run: `grep JWT_SECRET .env | wc -c`
Expected: Number > 20 (JWT secret is set)

- [ ] **Step 4: Verify reference data**

```bash
test -f data/lista_uczuc.json && test -f data/lista_potrzeb.json && echo "Reference data OK"
```

Expected: "Reference data OK"

- [ ] **Step 5: Create Phase 1 summary commit**

```bash
git tag phase-1-complete
git log --oneline
```

Expected: Tag "phase-1-complete" created

---

## System Architecture Reference

This section documents the overall architecture for reference (not executable tasks).

### Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                         User Browser                         │
│  ┌────────────────────────────────────────────────────────┐ │
│  │              React SPA (Vite)                          │ │
│  │  • PrimeReact UI Components                            │ │
│  │  • FontAwesome Icons                                   │ │
│  │  • LocalForage (IndexedDB) for Offline                 │ │
│  │  • Axios HTTP Client                                   │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                            ↕ HTTPS/HTTP
┌─────────────────────────────────────────────────────────────┐
│                    PHP Backend (Slim 4)                      │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  Controllers → Services → Repositories → DynamoDB     │ │
│  │  • AuthController (OAuth, Magic Link)                  │ │
│  │  • FormController (CRUD operations)                    │ │
│  │  • AdminController (User management)                   │ │
│  │  • Middleware (CORS, Auth, Rate Limiting)              │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                            ↕ AWS SDK
┌─────────────────────────────────────────────────────────────┐
│                      AWS DynamoDB                            │
│  • UpApp.{env}.Users                                        │
│  • UpApp.{env}.FormSubmissions                              │
│  • UpApp.{env}.MagicLinks (with TTL)                        │
│  • UpApp.{env}.Feelings (Reference data)                    │
│  • UpApp.{env}.Needs (Reference data)                       │
└─────────────────────────────────────────────────────────────┘
```

### Design Patterns

**Repository Pattern**: Abstracts data access behind repository classes
- FormController → FormService → FormRepository → DynamoDB

**Dependency Injection**: PHP-DI container manages dependencies
- Controllers receive dependencies through constructor injection

**Middleware Pipeline**: Requests pass through layers
- CORS → Error → Rate Limit → Auth → Admin → Controller

**Auto-save Pattern**: Forms save on blur and timer
- Debounced save prevents excessive API calls

**Offline-First**: IndexedDB cache syncs when online
- Saves locally when offline, syncs on reconnect

### Technology Justification

**Why Slim Framework?**
- Lightweight for API-only backend
- PSR-compliant
- Works on shared hosting

**Why DynamoDB?**
- Serverless, no management
- Auto-scaling
- TTL for magic links

**Why React + Vite?**
- Fast HMR
- Optimized builds
- Easy deployment as static files

**Why PrimeReact?**
- Comprehensive components
- DataTable for admin panel
- Accessible (ARIA)

---

## Next Steps

Phase 1 complete! Continue with:

1. **[04-dynamodb-schema.md](04-dynamodb-schema.md)** - Create DynamoDB tables
2. **[03-backend-setup.md](03-backend-setup.md)** - Setup PHP Slim Framework
3. **[02-frontend-setup.md](02-frontend-setup.md)** - Initialize React application
