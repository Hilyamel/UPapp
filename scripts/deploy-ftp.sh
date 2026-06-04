#!/bin/bash
# FTP Deployment Script for UPapp to dhosting.pl
# This script builds the frontend, packages backend, and uploads to FTP server

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}Starting FTP deployment to dhosting.pl...${NC}"

# Load environment variables
if [ ! -f .env ]; then
    echo -e "${RED}Error: .env file not found${NC}"
    echo "Please create .env file with FTP credentials"
    exit 1
fi

source .env

# Validate required environment variables
if [ -z "$FTP_HOST" ] || [ -z "$FTP_USER" ] || [ -z "$FTP_PASSWORD" ]; then
    echo -e "${RED}Error: FTP credentials missing in .env${NC}"
    echo "Required: FTP_HOST, FTP_USER, FTP_PASSWORD"
    exit 1
fi

FTP_REMOTE_PATH=${FTP_REMOTE_PATH:-/}

echo -e "${YELLOW}Building frontend...${NC}"
cd frontend
npm run build
cd ..

echo -e "${YELLOW}Creating deployment package...${NC}"
rm -rf dist_deploy
mkdir -p dist_deploy

# Copy backend files
echo "Copying backend files..."
cp -r backend/src dist_deploy/
cp -r backend/public dist_deploy/
cp backend/composer.json dist_deploy/
cp backend/composer.lock dist_deploy/

# Copy backend data files
mkdir -p dist_deploy/data
cp -r backend/data/* dist_deploy/data/ 2>/dev/null || true

# Copy frontend build
echo "Copying frontend build..."
mkdir -p dist_deploy/public
cp -r frontend/dist/* dist_deploy/public/

# Copy config files
cp .env.example dist_deploy/

# Create .htaccess for Apache
cat > dist_deploy/public/.htaccess << 'EOF'
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
EOF

# Create deployment info file
cat > dist_deploy/DEPLOY_INFO.txt << EOF
Deployment Date: $(date)
Git Commit: $(git rev-parse HEAD)
Git Branch: $(git rev-parse --abbrev-ref HEAD)
Environment: production
EOF

echo -e "${YELLOW}Uploading to FTP server...${NC}"

# Create FTP upload script
cat > /tmp/ftp_upload.sh << FTPSCRIPT
#!/bin/bash
cd dist_deploy
lftp -u "$FTP_USER","$FTP_PASSWORD" "$FTP_HOST" << FTPEOF
set ftp:ssl-allow no
set net:timeout 10
set net:max-retries 3
set net:reconnect-interval-base 5

lcd dist_deploy
cd $FTP_REMOTE_PATH

mirror --reverse --delete --verbose --exclude .git/ --exclude node_modules/ --exclude .env

bye
FTPEOF
FTPSCRIPT

chmod +x /tmp/ftp_upload.sh
bash /tmp/ftp_upload.sh

if [ $? -eq 0 ]; then
    echo -e "${GREEN}Deployment completed successfully!${NC}"
    echo -e "${GREEN}Your app should be available at your domain${NC}"
    rm -f /tmp/ftp_upload.sh
else
    echo -e "${RED}Deployment failed${NC}"
    rm -f /tmp/ftp_upload.sh
    exit 1
fi

echo -e "${YELLOW}Cleaning up...${NC}"
rm -rf dist_deploy

echo -e "${GREEN}Done!${NC}"
