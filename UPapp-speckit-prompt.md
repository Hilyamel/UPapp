# Speckit Prompt — UPapp

## Context

Analyze the existing sketch app in `../TUPapp`. It is an NVC (Nonviolent Communication) Forms application with three form types: DUP, TUP, and DOS. Use it as the functional reference — understand the domain, screens, and data models — but **do not copy its tech stack**. The new app goes into a new, currently non-existent subfolder `../UPapp`.

---

## Tech Stack

### Frontend
- **React** (Vite, TypeScript)
- **PrimeReact** as the UI component library
- **Font Awesome** for icons
- **Prettier** + **ESLint** configured and enforced

### Backend
- **PHP** — use **Slim Framework** (lightweight, no bloat) or plain PHP if Slim adds unnecessary complexity
- Exposes a REST API consumed by the React frontend
- No ORM required; use AWS SDK for PHP to talk to DynamoDB directly

### Database
- **AWS DynamoDB** — already authenticated via AWS CLI
- Provision all tables using `aws` CLI commands (no Terraform, no CDK)
- Table naming convention: `UpApp.<ENV>.<tablename>` where `<ENV>` is `dev`, `uat`, or `prod`
  - Example: `UpApp.dev.users`, `UpApp.prod.forms`

---

## Root `package.json` Scripts

The root `package.json` must include at minimum:

```json
{
  "scripts": {
    "gui": "...",           // starts React dev server
    "backend": "...",       // starts PHP backend (e.g. php -S localhost:8080 -t backend/public)
    "db:sync": "...",       // synchronizes DynamoDB table structure across environments
    "db:seed": "...",       // seeds DynamoDB with initial / demo data
    "build": "...",         // builds React app for production
    "deploy": "..."         // runs the full SFTP deployment (see Deployment section)
  }
}
```

---

## Authentication

Implement **two login methods**:

1. **Google OAuth** — standard OAuth2 flow via Google Identity
2. **Magic Link** — passwordless email login: user enters email, receives a time-limited link, clicks it to authenticate

Both methods must produce a session token (JWT or PHP session) that the React app uses.

### Admin user
- The admin email is configured in `.env` via `ADMIN_EMAIL`
- Default: `janczewski.piotr@gmail.com`
- Admin panel allows: managing users, viewing all forms, configuring who can access the admin panel (`ADMIN_ALLOWED_EMAILS` — comma-separated list in `.env`)

---

## Environment Configuration

Create `.env_dist` in the **root** of `UPapp` with all required variables and clear comments. Include at minimum:

```dotenv
# App
APP_ENV=dev                        # dev | uat | prod
APP_URL=http://localhost:5173

# AWS
AWS_REGION=eu-central-1
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=

# DynamoDB table prefix (auto-constructed as UpApp.{APP_ENV}.{table})
DYNAMODB_TABLE_PREFIX=UpApp

# Auth — Google OAuth
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=

# Auth — Magic Link
MAGIC_LINK_SECRET=
MAGIC_LINK_TTL_MINUTES=15
MAIL_FROM=noreply@example.com
MAIL_SMTP_HOST=
MAIL_SMTP_PORT=587
MAIL_SMTP_USER=
MAIL_SMTP_PASS=

# Admin
ADMIN_EMAIL=janczewski.piotr@gmail.com
ADMIN_ALLOWED_EMAILS=janczewski.piotr@gmail.com

# SFTP Deployment
SFTP_HOST=
SFTP_PORT=22
SFTP_USER=
SFTP_REMOTE_PATH=/var/www/upapp
SFTP_KEY_PATH=~/.ssh/id_rsa
```

---

## Deployment

The app deploys via **SFTP** — no Docker, no Kubernetes.

- **Frontend (React)**: build locally with `npm run build`, then upload the `dist/` folder to the server
- **Backend (PHP)**: upload all PHP files to the server as-is (WordPress-style — the server runs PHP natively)
- The **React GUI** must include a **Deploy button** in the admin panel that triggers the deployment process (calls a local script or API endpoint that runs the SFTP upload)
- All deployment logic lives in `scripts/deploy-sftp.sh` (or `.ps1` for Windows compatibility)

---

## GUI — Admin Panel Features

In addition to standard NVC form management (mirroring TUPapp's DUP/TUP/DOS), the admin panel must include:

- **Table Sync** — button/screen that compares existing DynamoDB tables against the expected schema and creates/updates missing tables using AWS CLI
- **Deploy** — button that triggers the SFTP build-and-upload process
- **User Management** — list users, revoke access, promote to admin
- **Seed Data** — button to run the seed script against the current environment

---

## Project Documentation

Before writing any code, create a detailed work plan:

- `docs/init/plan.md` — high-level phased plan (infrastructure → backend → frontend → auth → deployment)
- `docs/init/data-model.md` — DynamoDB table definitions (keys, indexes, attribute shapes)
- `docs/init/auth-flow.md` — sequence diagrams or step-by-step description of Google OAuth and Magic Link flows
- `docs/init/deployment.md` — SFTP deployment process, environment promotion (dev → uat → prod)

---

## Directory Structure (target)

```
UPapp/
├── .env_dist
├── .env                    # gitignored
├── package.json            # root scripts
├── frontend/               # React + Vite
│   ├── src/
│   ├── .eslintrc.json
│   ├── .prettierrc
│   └── vite.config.ts
├── backend/                # PHP (Slim or plain)
│   ├── public/
│   │   └── index.php       # entry point
│   ├── src/
│   └── composer.json
├── scripts/
│   ├── aws-setup.sh        # creates DynamoDB tables via aws CLI
│   ├── seed.sh             # seeds data
│   └── deploy-sftp.sh      # SFTP deploy script
└── docs/
    └── init/
        ├── plan.md
        ├── data-model.md
        ├── auth-flow.md
        └── deployment.md
```

---

## Constraints & Notes

- AWS CLI is already installed and authenticated — use it directly, do not use SDK setup steps for provisioning
- No admin rights on the developer machine — avoid tools that require system installation
- Keep PHP dependencies minimal; prefer Slim 4 over Laravel/Symfony
- The app must work in all three environments (dev/uat/prod) by switching `APP_ENV` in `.env`
- Git-ignore `.env`, `node_modules`, `vendor`, `dist`, `backend/cache`
