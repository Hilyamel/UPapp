#!/bin/bash

FTP_HOST="hinol.ftp.dhosting.pl"
FTP_USER="ohj9oo_upappmin"
FTP_PASS="$1"

if [ -z "$FTP_PASS" ]; then
    echo "Usage: bash ftp-upload.sh <FTP_PASSWORD>"
    exit 1
fi

echo "📤 Uploading to $FTP_HOST..."

# Upload frontend
echo "Uploading frontend..."
lftp -c "
set ftp:ssl-allow no;
open ftp://$FTP_USER:$FTP_PASS@$FTP_HOST;
lcd frontend/dist;
cd /public_html;
mirror --reverse --delete --verbose --parallel=10 ./ ./;
"

# Upload backend
echo "Uploading backend..."
lftp -c "
set ftp:ssl-allow no;
open ftp://$FTP_USER:$FTP_PASS@$FTP_HOST;
lcd backend;
cd /backend;
mirror --reverse --delete --verbose --parallel=10 \
  --exclude-glob tests/ \
  --exclude-glob test-*.php \
  --exclude-glob get-*.php \
  --exclude-glob admin-*.php \
  ./ ./;
"

# Upload empathy prompt
echo "Uploading empathy-prompt.txt..."
lftp -c "
set ftp:ssl-allow no;
open ftp://$FTP_USER:$FTP_PASS@$FTP_HOST;
put empathy-prompt.txt -o /empathy-prompt.txt;
"

echo "✅ Upload complete!"
echo ""
echo "⚠️  Don't forget to create /backend/.env on the server!"
