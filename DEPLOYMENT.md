# Deployment Guide

## 🚀 Automated Deployment via GitHub Actions

### Setup (One-time)

1. Go to GitHub repository: **Settings → Secrets and variables → Actions**

2. Add the following secrets (click "New repository secret" for each):

   **FTP Credentials:**
   - `FTP_HOST` = hinol.ftp.dhosting.pl
   - `FTP_USERNAME` = ohj9oo_upappmin
   - `FTP_PASSWORD` = [your FTP password]

   **AWS Credentials:**
   - `AWS_REGION` = eu-central-1
   - `AWS_ACCESS_KEY_ID` = [your AWS key]
   - `AWS_SECRET_ACCESS_KEY` = [your AWS secret]

   **SMTP Configuration:**
   - `SMTP_HOST` = smtp.gmail.com
   - `SMTP_PORT` = 587
   - `SMTP_USERNAME` = [your email]
   - `SMTP_PASSWORD` = [your SMTP password]
   - `SMTP_FROM_EMAIL` = [your email]

   **Claude API:**
   - `ANTHROPIC_API_KEY` = [your Claude API key]

### Deploy to Production

1. Go to **Actions** tab in GitHub
2. Click **Deploy to Production** workflow
3. Click **Run workflow** button
4. Type `DEPLOY` in the confirmation field
5. Click **Run workflow**

The deployment will:
- ✅ Build frontend with production settings
- ✅ Install backend dependencies (production only)
- ✅ Create production .env from secrets
- ✅ Upload everything to FTP server
- ✅ Exclude test files and development files

### After Deployment

- **Frontend**: https://upapp.mindincoach.com
- **Backend API**: https://upapp.mindincoach.com/api
- **Health Check**: https://upapp.mindincoach.com/api/health

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
   VITE_API_URL=https://upapp.mindincoach.com/api npm run build
   ```

2. Create backend/.env from `.env.production.example`

3. Upload via FTP client (FileZilla, etc.):
   - Upload `frontend/dist/` → `/public_html/`
   - Upload `backend/` → `/backend/`
   - Upload `empathy-prompt.txt` → root

## 🗄️ Database Information

**MySQL Server:** hinol.mysql.dhosting.pl  
**Database:** phagh9_upappmin  
**Username:** uphue9_upappmin  

Currently using DynamoDB, but MySQL credentials saved for future use.

## ⚠️ Important Notes

- Always test in development before deploying to production
- Check workflow logs after deployment for errors
- Monitor https://upapp.mindincoach.com/api/health after deployment
- Keep GitHub Secrets up to date if credentials change
