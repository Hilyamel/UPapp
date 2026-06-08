#!/bin/bash

echo "🚀 Deploying UPapp to Production..."
echo ""

# Build frontend
echo "📦 Building frontend..."
cd frontend
VITE_API_URL=https://przetargr-domow.pl/api npm run build
cd ..

# Install backend dependencies
echo "📦 Installing backend dependencies..."
cd backend
composer install --no-dev --optimize-autoloader --no-interaction
cd ..

# Upload via FTP
echo "📤 Uploading to FTP..."
echo "Host: hinol.ftp.dhosting.pl"
echo "User: ohj9oo_upappmin"
echo ""
echo "✅ Ready to upload!"
echo ""
echo "Next steps:"
echo "1. Use FileZilla or FTP client to upload:"
echo "   - frontend/dist/* → /public_html/"
echo "   - backend/* → /backend/"
echo "   - empathy-prompt.txt → /"
echo ""
echo "2. Create backend/.env on server with production values"
