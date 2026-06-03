#!/bin/bash

################################################################################
# UPapp VPS Deployment Script
#
# Prerequisites:
# - Ubuntu/Debian VPS with SSH access
# - PHP 8.2+ installed with required extensions
# - Node.js 18+ and npm installed
# - Git installed
# - AWS credentials configured
#
# Usage:
#   chmod +x deploy.sh
#   ./deploy.sh
################################################################################

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration - EDIT THESE VALUES
GITHUB_REPO="https://github.com/Hilyamel/UPapp.git"
GITHUB_BRANCH="001-project-foundation"
APP_DIR="/var/www/upapp"
DOMAIN="your-domain.com"  # Change this to your domain or IP
BACKEND_PORT=8080
FRONTEND_PORT=3000

# AWS Configuration - will be prompted if not set
AWS_REGION="${AWS_REGION:-eu-central-1}"
DYNAMODB_PREFIX="${DYNAMODB_PREFIX:-UpApp}"
APP_ENV="${APP_ENV:-prod}"

# Function to print colored messages
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}ℹ $1${NC}"
}

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Banner
echo "=================================="
echo "  UPapp VPS Deployment Script"
echo "=================================="
echo ""

# Check prerequisites
print_info "Checking prerequisites..."

if ! command_exists php; then
    print_error "PHP not found. Please install PHP 8.2+"
    exit 1
fi

PHP_VERSION=$(php -r 'echo PHP_VERSION;')
print_success "PHP $PHP_VERSION found"

if ! command_exists node; then
    print_error "Node.js not found. Please install Node.js 18+"
    exit 1
fi

NODE_VERSION=$(node --version)
print_success "Node.js $NODE_VERSION found"

if ! command_exists git; then
    print_error "Git not found. Please install Git"
    exit 1
fi

print_success "Git found"

if ! command_exists composer; then
    print_info "Composer not found. Installing..."
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
    print_success "Composer installed"
fi

# Create application directory
print_info "Creating application directory at $APP_DIR..."
sudo mkdir -p "$APP_DIR"
sudo chown -R $USER:$USER "$APP_DIR"
print_success "Directory created"

# Clone or pull repository
if [ -d "$APP_DIR/.git" ]; then
    print_info "Repository exists, pulling latest changes..."
    cd "$APP_DIR"
    git fetch origin
    git checkout "$GITHUB_BRANCH"
    git pull origin "$GITHUB_BRANCH"
else
    print_info "Cloning repository..."
    git clone -b "$GITHUB_BRANCH" "$GITHUB_REPO" "$APP_DIR"
    cd "$APP_DIR"
fi

print_success "Code updated"

# Backend setup
print_info "Setting up backend..."
cd "$APP_DIR/backend"

# Install PHP dependencies
print_info "Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    print_info "Creating .env file..."
    cp .env.example .env

    # Prompt for AWS credentials if not set
    if [ -z "$AWS_ACCESS_KEY_ID" ]; then
        read -p "AWS Access Key ID: " AWS_ACCESS_KEY_ID
    fi

    if [ -z "$AWS_SECRET_ACCESS_KEY" ]; then
        read -sp "AWS Secret Access Key: " AWS_SECRET_ACCESS_KEY
        echo ""
    fi

    # Update .env file
    sed -i "s/^AWS_REGION=.*/AWS_REGION=$AWS_REGION/" .env
    sed -i "s/^AWS_ACCESS_KEY_ID=.*/AWS_ACCESS_KEY_ID=$AWS_ACCESS_KEY_ID/" .env
    sed -i "s/^AWS_SECRET_ACCESS_KEY=.*/AWS_SECRET_ACCESS_KEY=$AWS_SECRET_ACCESS_KEY/" .env
    sed -i "s/^DYNAMODB_TABLE_PREFIX=.*/DYNAMODB_TABLE_PREFIX=$DYNAMODB_PREFIX/" .env
    sed -i "s/^APP_ENV=.*/APP_ENV=$APP_ENV/" .env
    sed -i "s|^FRONTEND_URL=.*|FRONTEND_URL=http://$DOMAIN|" .env

    print_success ".env file created"
else
    print_info ".env file already exists, skipping..."
fi

# Create DynamoDB tables
print_info "Creating DynamoDB tables..."
export AWS_REGION AWS_ACCESS_KEY_ID AWS_SECRET_ACCESS_KEY
php scripts/create-forms-table.php

# Frontend setup
print_info "Setting up frontend..."
cd "$APP_DIR/frontend"

# Create .env file for frontend
if [ ! -f .env ]; then
    print_info "Creating frontend .env file..."
    cat > .env << EOF
VITE_API_URL=http://$DOMAIN/api
EOF
    print_success "Frontend .env created"
fi

# Install Node dependencies
print_info "Installing Node.js dependencies..."
npm ci --production

# Build frontend
print_info "Building frontend..."
npm run build
print_success "Frontend built"

# Setup Nginx
print_info "Configuring Nginx..."

NGINX_CONFIG="/etc/nginx/sites-available/upapp"
sudo tee "$NGINX_CONFIG" > /dev/null << EOF
server {
    listen 80;
    server_name $DOMAIN;

    root $APP_DIR/frontend/dist;
    index index.html;

    # Frontend - serve static files
    location / {
        try_files \$uri \$uri/ /index.html;
    }

    # Backend API - reverse proxy to PHP
    location /api {
        proxy_pass http://localhost:$BACKEND_PORT;
        proxy_http_version 1.1;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }

    # CORS headers
    add_header Access-Control-Allow-Origin "http://$DOMAIN" always;
    add_header Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS" always;
    add_header Access-Control-Allow-Headers "Content-Type, Authorization" always;
    add_header Access-Control-Allow-Credentials "true" always;

    # Gzip compression
    gzip on;
    gzip_types text/css application/javascript application/json;
    gzip_min_length 1000;
}
EOF

# Enable site
sudo ln -sf "$NGINX_CONFIG" /etc/nginx/sites-enabled/upapp

# Test Nginx configuration
if sudo nginx -t; then
    print_success "Nginx configuration valid"
    sudo systemctl reload nginx
    print_success "Nginx reloaded"
else
    print_error "Nginx configuration invalid"
    exit 1
fi

# Setup systemd service for PHP backend
print_info "Creating systemd service for backend..."

SYSTEMD_SERVICE="/etc/systemd/system/upapp-backend.service"
sudo tee "$SYSTEMD_SERVICE" > /dev/null << EOF
[Unit]
Description=UPapp PHP Backend
After=network.target

[Service]
Type=simple
User=$USER
WorkingDirectory=$APP_DIR/backend
ExecStart=/usr/bin/php -S localhost:$BACKEND_PORT -t public
Restart=always
RestartSec=5
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

# Reload systemd and start service
sudo systemctl daemon-reload
sudo systemctl enable upapp-backend
sudo systemctl restart upapp-backend

print_success "Backend service started"

# Check service status
if sudo systemctl is-active --quiet upapp-backend; then
    print_success "Backend service is running"
else
    print_error "Backend service failed to start"
    sudo journalctl -u upapp-backend -n 50 --no-pager
    exit 1
fi

# Set proper permissions
print_info "Setting permissions..."
sudo chown -R $USER:www-data "$APP_DIR"
sudo chmod -R 755 "$APP_DIR"
sudo chmod -R 775 "$APP_DIR/backend/storage" 2>/dev/null || true

print_success "Permissions set"

# Final checks
echo ""
echo "=================================="
echo "  Deployment Complete!"
echo "=================================="
echo ""
print_success "Application URL: http://$DOMAIN"
print_success "API URL: http://$DOMAIN/api"
echo ""
print_info "Service management commands:"
echo "  Start:   sudo systemctl start upapp-backend"
echo "  Stop:    sudo systemctl stop upapp-backend"
echo "  Restart: sudo systemctl restart upapp-backend"
echo "  Status:  sudo systemctl status upapp-backend"
echo "  Logs:    sudo journalctl -u upapp-backend -f"
echo ""
print_info "To update the application in the future:"
echo "  cd $APP_DIR && ./deploy.sh"
echo ""

# Test endpoints
print_info "Testing endpoints..."
sleep 2

if curl -f -s "http://localhost:$BACKEND_PORT/api/health" > /dev/null; then
    print_success "Backend health check passed"
else
    print_error "Backend health check failed"
fi

print_success "Deployment complete! 🎉"
