# FTP Deployment Script for UPapp to dhosting.pl (PowerShell)
# This script builds the frontend, packages backend, and uploads to FTP server

$ErrorActionPreference = "Stop"

Write-Host "Starting FTP deployment to dhosting.pl..." -ForegroundColor Green

# Load environment variables from .env
$envFile = ".env"
if (-not (Test-Path $envFile)) {
    Write-Host "Error: .env file not found" -ForegroundColor Red
    Write-Host "Please create .env file with FTP credentials"
    exit 1
}

Get-Content $envFile | ForEach-Object {
    if ($_ -match '^([^=]+)=(.*)$') {
        $name = $matches[1].Trim()
        $value = $matches[2].Trim()
        Set-Item -Path "env:$name" -Value $value
    }
}

# Validate required environment variables
if (-not $env:FTP_HOST -or -not $env:FTP_USER -or -not $env:FTP_PASSWORD) {
    Write-Host "Error: FTP credentials missing in .env" -ForegroundColor Red
    Write-Host "Required: FTP_HOST, FTP_USER, FTP_PASSWORD"
    exit 1
}

$FTP_REMOTE_PATH = if ($env:FTP_REMOTE_PATH) { $env:FTP_REMOTE_PATH } else { "/" }

Write-Host "Building frontend..." -ForegroundColor Yellow
Set-Location frontend
npm run build
Set-Location ..

Write-Host "Creating deployment package..." -ForegroundColor Yellow
if (Test-Path dist_deploy) {
    Remove-Item -Recurse -Force dist_deploy
}
New-Item -ItemType Directory -Path dist_deploy | Out-Null

# Copy backend files
Write-Host "Copying backend files..."
Copy-Item -Recurse backend/src dist_deploy/
Copy-Item -Recurse backend/public dist_deploy/
Copy-Item backend/composer.json dist_deploy/
Copy-Item backend/composer.lock dist_deploy/

# Copy backend data files
New-Item -ItemType Directory -Path dist_deploy/data -Force | Out-Null
if (Test-Path backend/data) {
    Copy-Item -Recurse backend/data/* dist_deploy/data/
}

# Copy frontend build
Write-Host "Copying frontend build..."
New-Item -ItemType Directory -Path dist_deploy/public -Force | Out-Null
Copy-Item -Recurse frontend/dist/* dist_deploy/public/

# Copy config files
Copy-Item .env.example dist_deploy/

# Create .htaccess for Apache
$htaccess = @"
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # API requests go to backend
    RewriteCond %{REQUEST_URI} ^/api/
    RewriteRule ^api/(.*)$ /public/index.php [L,QSA]

    # Frontend routing - serve index.html for non-file requests
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule . /index.html [L]
</IfModule>
"@
$htaccess | Out-File -FilePath dist_deploy/public/.htaccess -Encoding ASCII

# Create deployment info file
$gitCommit = git rev-parse HEAD
$gitBranch = git rev-parse --abbrev-ref HEAD
$deployInfo = @"
Deployment Date: $(Get-Date)
Git Commit: $gitCommit
Git Branch: $gitBranch
Environment: production
"@
$deployInfo | Out-File -FilePath dist_deploy/DEPLOY_INFO.txt -Encoding UTF8

Write-Host "Uploading to FTP server..." -ForegroundColor Yellow

# Upload using WinSCP or native .NET FTP
try {
    $ftpUri = "ftp://$env:FTP_HOST$FTP_REMOTE_PATH"

    # Use native .NET WebClient for simple FTP upload
    Write-Host "Note: For full deployment, consider using WinSCP or FileZilla"
    Write-Host "Manual upload required to: $ftpUri"
    Write-Host "Upload contents of: $(Resolve-Path dist_deploy)"

    # Create a ZIP for easier manual upload
    $zipPath = "dist_deploy.zip"
    if (Test-Path $zipPath) {
        Remove-Item $zipPath
    }
    Compress-Archive -Path dist_deploy/* -DestinationPath $zipPath
    Write-Host "Created ZIP file: $zipPath" -ForegroundColor Green
    Write-Host "You can upload this ZIP and extract it on the server"

} catch {
    Write-Host "Error during FTP operation: $_" -ForegroundColor Red
    exit 1
}

Write-Host "Deployment package ready!" -ForegroundColor Green
Write-Host "Location: $(Resolve-Path dist_deploy)" -ForegroundColor Green
