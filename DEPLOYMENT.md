# Deployment Guide

## Server Information

- **Host:** 57.131.47.46
- **User:** debian
- **Domain:** przetargr-domow.pl
- **Protocol:** FTP
- **Port:** 21
- **Deploy Path:** /home/debian/public_html/

## 🚀 Automated Deployment via GitHub Actions

### Setup (One-time)

1. Go to GitHub repository: **Settings → Secrets and variables → Actions**

2. Add the following secrets (click "New repository secret" for each):

   **FTP Credentials:**
   - `FTP_PASSWORD` = [your FTP password for debian user]

   **Database Credentials:**
   - `DB_HOST` = [your database host]
   - `DB_PORT` = 3306
   - `DB_DATABASE` = [your database name]
   - `DB_USERNAME` = [your database username]
   - `DB_PASSWORD` = [your database password]

   **AWS Credentials (optional):**
   - `AWS_REGION` = eu-central-1
   - `AWS_ACCESS_KEY_ID` = [your AWS key]
   - `AWS_SECRET_ACCESS_KEY` = [your AWS secret]

   **SMTP Configuration:**
   - `SMTP_HOST` = smtp.gmail.com
   - `SMTP_PORT` = 587
   - `SMTP_ENCRYPTION` = tls
   - `SMTP_USERNAME` = [your email]
   - `SMTP_PASSWORD` = [your SMTP password or app-specific password]
   - `SMTP_FROM_EMAIL` = [your email]

   **Claude API:**
   - `ANTHROPIC_API_KEY` = [your Claude API key]

### Deploy to Production

1. Go to **Actions** tab in GitHub
2. Click **Deploy to Production (FTP)** workflow
3. Click **Run workflow** button
4. Type `DEPLOY` in the confirmation field
5. Select environment: **production** or **staging**
6. Click **Run workflow**

The deployment will:
- ✅ Build frontend with production settings (https://przetargr-domow.pl)
- ✅ Install backend dependencies (production only, optimized)
- ✅ Create production .env from secrets
- ✅ Upload everything to FTP server at 57.131.47.46
- ✅ Exclude test files and development files
- ✅ Create deployment metadata file
- ✅ Perform health check

### After Deployment

- **Frontend**: https://przetargr-domow.pl
- **Backend API**: https://przetargr-domow.pl/api
- **Health Check**: https://przetargr-domow.pl/api/health

## 🔐 Security

- ✅ All credentials stored as GitHub Secrets (encrypted)
- ✅ .env files NEVER committed to repository
- ✅ Production .env generated during deployment
- ✅ FTP passwords never appear in logs

## 📋 Manual Deployment (Fallback)

If GitHub Actions is unavailable:

1. Build frontend locally:
   ```bash
   cd frontend
   VITE_API_URL=https://przetargr-domow.pl/api npm run build
   ```

2. Install backend dependencies:
   ```bash
   cd backend
   composer install --no-dev --optimize-autoloader
   ```

3. Create backend/.env with production values

4. Upload via FTP client (FileZilla, WinSCP, etc.):
   - **Host:** 57.131.47.46
   - **Username:** debian
   - **Password:** [your FTP password]
   - **Protocol:** FTP
   - **Port:** 21

   Upload structure:
   - Upload `frontend/dist/*` → `/home/debian/public_html/frontend/dist/`
   - Upload `backend/*` → `/home/debian/public_html/backend/`
   - Upload `empathy-prompt.txt` → `/home/debian/public_html/`

## 🗄️ Server Structure

After deployment, your server should have:

```
/home/debian/public_html/
├── backend/
│   ├── src/
│   ├── public/index.php
│   ├── vendor/
│   ├── .env
│   └── composer.json
├── frontend/
│   └── dist/
│       ├── index.html
│       └── assets/
├── empathy-prompt.txt
└── DEPLOYMENT_INFO.txt
```

## ⚠️ Important Notes

- Always test in development before deploying to production
- Check workflow logs after deployment for errors
- Monitor https://upapp.mindincoach.com/api/health after deployment
- Keep GitHub Secrets up to date if credentials change
