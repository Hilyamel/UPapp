# Quick Start Guide: UPapp Development

**Last Updated**: 2026-06-08

**Purpose**: Get a new developer from zero to running the full UPapp stack locally in under 10 minutes.

**Environments**: This guide covers dev setup. See research.md for uat and prod configurations.

## Prerequisites

Before starting, ensure you have:

- ✅ **Node.js 18+** ([download](https://nodejs.org/))
- ✅ **PHP 8.1+** ([download](https://www.php.net/downloads))
- ✅ **Composer** ([download](https://getcomposer.org/))
- ✅ **AWS CLI** configured with credentials ([setup guide](https://docs.aws.amazon.com/cli/latest/userguide/cli-chap-configure.html))
- ✅ **Git** for version control

### Verify Prerequisites

```bash
# Check versions
node --version    # Should be 18.0.0 or higher
php --version     # Should be 8.1.0 or higher
composer --version
aws --version

# Verify AWS credentials
aws sts get-caller-identity
```

If any command fails, install the missing tool before proceeding.

---

## Step 1: Clone Repository

```bash
git clone <repository-url> UPapp
cd UPapp
```

---

## Step 2: Install Dependencies

### Install Node.js dependencies (frontend + scripts)

```bash
npm install
```

**Expected time**: ~30-60 seconds

### Install PHP dependencies (backend)

```bash
cd backend
composer install
cd ..
```

**Expected time**: ~15-30 seconds

---

## Step 3: Configure Environment

### Copy environment template

```bash
cp .env_dist .env
```

### Edit `.env` file

Open `.env` in your editor and configure:

```dotenv
# App Configuration
APP_ENV=dev                            # Keep as 'dev' for local development
APP_URL=http://localhost:5173          # Frontend URL (do not change)

# AWS Configuration
AWS_REGION=eu-central-1                # Keep as-is
AWS_ACCESS_KEY_ID=                     # Leave empty to use AWS CLI profile
AWS_SECRET_ACCESS_KEY=                 # Leave empty to use AWS CLI profile

# DynamoDB
DYNAMODB_TABLE_PREFIX=UpApp            # Do not change

# Backend API
BACKEND_URL=http://localhost:8080      # Backend URL (do not change)
```

**AWS Credentials**: If your AWS CLI is configured (you ran `aws sts get-caller-identity` successfully), leave `AWS_ACCESS_KEY_ID` and `AWS_SECRET_ACCESS_KEY` empty. The backend will use your AWS CLI profile automatically.

**Optional**: If you need to use explicit credentials, add them to `.env`:
```dotenv
AWS_ACCESS_KEY_ID=AKIAIOSFODNN7EXAMPLE
AWS_SECRET_ACCESS_KEY=wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY
```

---

## Step 4: Create DynamoDB Tables

Run the database sync script to create all required DynamoDB tables for the `dev` environment:

```bash
npm run db:sync
```

**What this does**:
- Checks your `APP_ENV` setting (should be `dev`)
- Creates `UpApp.dev.config` table in DynamoDB
- Reports success/failure for each table

**Expected output**:
```
[db:sync] Environment: dev
[db:sync] Creating table: UpApp.dev.config
[db:sync] ✓ Table UpApp.dev.config created successfully
[db:sync] All tables synchronized
```

**Expected time**: ~10-20 seconds

**Troubleshooting**:
- If you see "CredentialsNotFound": Run `aws configure` to set up your AWS credentials
- If you see "AccessDenied": Ensure your AWS account has DynamoDB permissions
- If table already exists: Script will skip creation (safe to run multiple times)

---

## Step 5: Seed Sample Data (Optional)

Populate the development tables with sample data:

```bash
npm run db:seed
```

**What this does**:
- Inserts initial configuration records into `UpApp.dev.config`
- (Future features will seed users, forms, reference data)

**Expected output**:
```
[db:seed] Environment: dev
[db:seed] Seeding table: UpApp.dev.config
[db:seed] ✓ Inserted 2 configuration records
[db:seed] Seeding complete
```

**Expected time**: ~5-10 seconds

---

## Step 6: Start Development Servers

You'll need **two terminal windows** (or tabs):

### Terminal 1: Start Backend (PHP)

```bash
npm run backend
```

**Expected output**:
```
[backend] Starting PHP development server on http://localhost:8080
[backend] Document root: backend/public
[backend] Press Ctrl+C to stop
PHP 8.1.0 Development Server (http://localhost:8080) started
```

**Verify backend is running**:
```bash
# In another terminal
curl http://localhost:8080/api/health
```

**Expected response**:
```json
{
  "success": true,
  "data": {
    "status": "healthy",
    "timestamp": "2026-06-02T14:30:00Z",
    "environment": "dev",
    "services": {
      "api": "ok",
      "dynamodb": "ok"
    }
  },
  "error": null
}
```

### Terminal 2: Start Frontend (React)

```bash
npm run gui
```

**Expected output**:
```
[gui] Starting Vite development server...

  VITE v5.0.0  ready in 823 ms

  ➜  Local:   http://localhost:5173/
  ➜  Network: use --host to expose
  ➜  press h + enter to show help
```

**Expected time**: <5 seconds to start

---

## Step 7: Open Application

Open your browser and navigate to:

```
http://localhost:5173
```

You should see the UPapp homepage (placeholder initially - features added in subsequent iterations).

---

## Verify Setup

### Backend Health Check

```bash
curl http://localhost:8080/api/health | jq .
```

**Expected**: `"status": "healthy"` and `"dynamodb": "ok"`

### Frontend Loads

- Browser shows React app at `http://localhost:5173`
- No console errors in browser DevTools
- Network tab shows successful fetch to backend API

### DynamoDB Tables Exist

```bash
aws dynamodb list-tables --region eu-central-1 | grep "UpApp.dev"
```

**Expected output**:
```
"UpApp.dev.config"
```

---

## Common Issues

### Port Already in Use

**Problem**: Error: `EADDRINUSE: address already in use :::5173`

**Solution**: Another process is using port 5173 or 8080
- Frontend: Vite will auto-increment (try 5174, 5175, etc.)
- Backend: Stop other PHP processes or change port in npm script

### AWS Credentials Not Found

**Problem**: Health check returns `"dynamodb": "error"` with "CredentialsNotFound"

**Solution**: Configure AWS CLI
```bash
aws configure
# Enter: AWS Access Key ID, Secret Access Key, Region (eu-central-1)
```

### DynamoDB Table Does Not Exist

**Problem**: Backend error "ResourceNotFoundException: Requested resource not found"

**Solution**: Run table creation script
```bash
npm run db:sync
```

### PHP Version Too Old

**Problem**: Error: "Your PHP version (7.4) does not meet the minimum requirement (8.1)"

**Solution**: Upgrade PHP to 8.1 or higher
- Windows: Download from [windows.php.net](https://windows.php.net/download/)
- Mac: `brew install php@8.1`
- Linux: `sudo apt install php8.1` (Debian/Ubuntu)

---

## Development Workflow

### Making Changes

1. **Frontend changes**: Vite hot-reloads automatically (no restart needed)
2. **Backend changes**: Restart backend server (`Ctrl+C` then `npm run backend`)
3. **Environment changes**: Update `.env`, restart affected server

### Running Tests

```bash
# Frontend tests
npm run test

# Backend tests
cd backend
composer test
cd ..
```

### Linting & Formatting

```bash
# Run linters
npm run lint

# Auto-fix formatting issues
npm run format
```

### Stopping Servers

- Press `Ctrl+C` in each terminal window
- Servers stop gracefully

---

## Next Steps

After successful setup:

1. **Explore codebase structure** (see `README.md` for directory layout)
2. **Read feature specifications** in `specs/` directory
3. **Review constitution** at `.specify/memory/constitution.md` for project principles
4. **Start implementing features** (see active feature branch)

---

## Getting Help

- **Constitution questions**: See `.specify/memory/constitution.md`
- **Feature specs**: See `specs/<feature-name>/spec.md`
- **Implementation plans**: See `specs/<feature-name>/plan.md`
- **API contracts**: See `specs/<feature-name>/contracts/`

---

**Time to Complete**: ~10 minutes (as per SC-001 success criteria)

**Checklist**:
- ✅ Prerequisites installed and verified
- ✅ Repository cloned
- ✅ Dependencies installed (npm + composer)
- ✅ `.env` configured with AWS credentials
- ✅ DynamoDB tables created (`db:sync`)
- ✅ Sample data seeded (`db:seed`)
- ✅ Backend running on port 8080
- ✅ Frontend running on port 5173
- ✅ Health check returns healthy status
- ✅ Browser loads React app successfully

**Congratulations! Your UPapp development environment is ready.**

---

## UAT Environment Setup

### Option 1: Local UAT (Recommended for Testing)

Run UAT environment locally on different ports:

```bash
# Copy UAT environment file
cp .env.uat .env

# Start backend on port 8081
cd backend && php -S localhost:8081 -t public/ &

# Start frontend on port 5174
cd frontend && npm run dev -- --port 5174
```

**Verify**: Open http://localhost:5174 and test forms

### Option 2: Dedicated UAT Server

For a separate UAT server (e.g., uat.przetargr-domow.pl):

1. Update `.env.uat` with UAT domain:
   ```bash
   APP_ENV=uat
   APP_URL=https://uat.przetargr-domow.pl
   BACKEND_URL=https://uat.przetargr-domow.pl/api
   ```

2. Add UAT domain to CORS in `backend/src/Middleware/CorsMiddleware.php`:
   ```php
   'https://uat.przetargr-domow.pl',  // UAT domain
   ```

3. Deploy to UAT server via same process as production

**Verify**: Visit UAT URL and test dropdown lists load

---

## Troubleshooting

### Dropdown Lists Not Loading

**Symptom**: Forms show empty dropdowns for feelings/needs

**Check**:
1. Open browser DevTools (F12) → Network tab
2. Look for failed requests to `/api/reference/feelings` or `/api/reference/needs`

**Common Causes**:
- **CORS error**: Add your domain to `backend/src/Middleware/CorsMiddleware.php`
- **Wrong API URL**: Verify `VITE_API_URL` matches your backend URL
- **Missing data files**: Verify `data/lista_uczuc.json` and `data/lista_potrzeb.json` exist

### DynamoDB Connection Fails

**Check AWS credentials**:
```bash
aws dynamodb list-tables --region eu-central-1
```

**If fails**: Run `aws configure` to set up credentials

### Port Already in Use

**Frontend (5173)**:
```bash
# Kill process using port 5173
npx kill-port 5173
```

**Backend (8080)**:
```bash
# Kill PHP process
pkill -f "php -S localhost:8080"
```

