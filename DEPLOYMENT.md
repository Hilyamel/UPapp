# UPapp - Instrukcja Deployment na VPS

## Wymagania

### Serwer VPS
- System: Ubuntu 20.04+ lub Debian 11+
- RAM: minimum 2GB
- Dysk: minimum 10GB
- Dostęp SSH z uprawnieniami sudo

### Oprogramowanie zainstalowane na serwerze
- **PHP 8.2+** z rozszerzeniami:
  - php-curl
  - php-mbstring
  - php-json
  - php-xml
- **Node.js 18+** i npm
- **Git**
- **Nginx** (zostanie skonfigurowany automatycznie)

### AWS
- Konto AWS z dostępem do DynamoDB
- Access Key ID i Secret Access Key
- Region: eu-central-1 (domyślnie)

## Instalacja w 3 krokach

### 1. Zaloguj się na serwer

```bash
ssh user@your-vps-ip
```

### 2. Pobierz skrypt deployment

```bash
# Pobierz repozytorium
git clone -b 001-project-foundation https://github.com/Hilyamel/UPapp.git /tmp/upapp-deploy
cd /tmp/upapp-deploy

# Lub pobierz tylko skrypt
curl -O https://raw.githubusercontent.com/Hilyamel/UPapp/001-project-foundation/deploy.sh
```

### 3. Uruchom skrypt

```bash
chmod +x deploy.sh

# WAŻNE: Przed uruchomieniem edytuj plik deploy.sh i zmień:
nano deploy.sh

# Zmień te wartości:
# DOMAIN="your-domain.com"  -> Twoja domena lub IP serwera
# BACKEND_PORT=8080         -> Port backendu (domyślnie 8080)
# FRONTEND_PORT=3000        -> Port frontendu (nie używany przy Nginx)

# Uruchom deployment
./deploy.sh
```

Skrypt zapyta o:
- AWS Access Key ID
- AWS Secret Access Key

Wszystko inne zostanie skonfigurowane automatycznie!

## Co robi skrypt deployment?

1. ✓ Sprawdza wymagania (PHP, Node.js, Git)
2. ✓ Instaluje Composer (jeśli nie ma)
3. ✓ Klonuje repozytorium do `/var/www/upapp`
4. ✓ Instaluje zależności PHP (backend)
5. ✓ Instaluje zależności Node.js (frontend)
6. ✓ Tworzy i konfiguruje pliki `.env`
7. ✓ Tworzy tabele w AWS DynamoDB
8. ✓ Builduje frontend (Vite production build)
9. ✓ Konfiguruje Nginx jako reverse proxy
10. ✓ Tworzy systemd service dla backendu
11. ✓ Uruchamia aplikację

## Po deploymencie

### Dostęp do aplikacji
```
http://your-domain.com
```

### Zarządzanie serwisem backend

```bash
# Sprawdź status
sudo systemctl status upapp-backend

# Restart
sudo systemctl restart upapp-backend

# Stop
sudo systemctl stop upapp-backend

# Start
sudo systemctl start upapp-backend

# Logi (na żywo)
sudo journalctl -u upapp-backend -f

# Ostatnie 100 linii logów
sudo journalctl -u upapp-backend -n 100
```

### Aktualizacja aplikacji

Gdy wprowadzisz zmiany w kodzie i pushesz na GitHub:

```bash
cd /var/www/upapp
./deploy.sh
```

Skrypt automatycznie:
- Pobierze najnowszy kod
- Zainstaluje nowe zależności
- Przebuduje frontend
- Zrestartuje backend

## Konfiguracja domeny (opcjonalnie)

### Wskaż domenę na serwer

W panelu DNS swojej domeny:
```
Type: A
Name: @
Value: [IP-SERWERA]
TTL: 3600
```

### Zainstaluj SSL (HTTPS)

```bash
# Zainstaluj Certbot
sudo apt install certbot python3-certbot-nginx

# Wygeneruj certyfikat SSL
sudo certbot --nginx -d your-domain.com
```

Certbot automatycznie:
- Wygeneruje certyfikat Let's Encrypt
- Skonfiguruje Nginx dla HTTPS
- Ustawi auto-renewal

Po tym aplikacja będzie dostępna na `https://your-domain.com`

## Struktura plików na serwerze

```
/var/www/upapp/
├── backend/
│   ├── public/          # PHP entry point
│   ├── src/             # Backend code
│   ├── config/          # Empathy prompt
│   ├── data/            # Feelings/needs lists
│   ├── scripts/         # DynamoDB setup
│   ├── .env             # Backend configuration
│   └── composer.json
├── frontend/
│   ├── dist/            # Production build (served by Nginx)
│   ├── src/             # React source
│   ├── .env             # Frontend configuration
│   └── package.json
└── deploy.sh            # Deployment script
```

## Nginx Configuration

Plik: `/etc/nginx/sites-available/upapp`

Nginx obsługuje:
- **Frontend**: serwuje statyczne pliki z `/var/www/upapp/frontend/dist`
- **Backend API**: przekierowuje `/api/*` do `http://localhost:8080`
- **CORS**: automatycznie dodaje nagłówki CORS
- **Compression**: gzip dla JS/CSS/JSON

## Troubleshooting

### Problem: Backend nie startuje

```bash
# Sprawdź logi
sudo journalctl -u upapp-backend -n 50

# Sprawdź czy port jest zajęty
sudo lsof -i :8080

# Sprawdź konfigurację PHP
php -v
php -m
```

### Problem: Nginx 502 Bad Gateway

```bash
# Sprawdź czy backend działa
sudo systemctl status upapp-backend

# Sprawdź logi Nginx
sudo tail -f /var/log/nginx/error.log

# Restart wszystkiego
sudo systemctl restart upapp-backend
sudo systemctl restart nginx
```

### Problem: DynamoDB connection error

```bash
# Sprawdź AWS credentials w .env
cat /var/www/upapp/backend/.env | grep AWS

# Test AWS connection
cd /var/www/upapp/backend
php -r "require 'vendor/autoload.php'; use Aws\DynamoDb\DynamoDbClient; \$client = new DynamoDbClient(['region' => 'eu-central-1', 'version' => 'latest']); print_r(\$client->listTables());"
```

### Problem: Frontend błędy CORS

Sprawdź czy w pliku `.env` frontend ma poprawny URL:
```bash
cat /var/www/upapp/frontend/.env
# Powinno być: VITE_API_URL=http://your-domain.com/api
```

## Backup i Monitoring

### Backup DynamoDB (zalecane)

W AWS Console:
1. DynamoDB → Tables
2. Wybierz tabelę → Backups
3. Create on-demand backup

Lub ustaw Point-in-Time Recovery (PITR).

### Monitoring

```bash
# CPU/RAM usage
htop

# Disk usage
df -h

# Backend logs (ostatnie 24h)
sudo journalctl -u upapp-backend --since "24 hours ago"

# Nginx access logs
sudo tail -f /var/log/nginx/access.log
```

## Zabezpieczenia

### Firewall (UFW)

```bash
sudo ufw allow 22/tcp   # SSH
sudo ufw allow 80/tcp   # HTTP
sudo ufw allow 443/tcp  # HTTPS
sudo ufw enable
```

### Aktualizacje systemu

```bash
sudo apt update
sudo apt upgrade -y
```

### Zmień domyślne porty (opcjonalnie)

Jeśli chcesz zmienić port backendu z 8080 na inny:

1. Edytuj `/etc/systemd/system/upapp-backend.service`
2. Zmień port w `ExecStart`
3. Edytuj `/etc/nginx/sites-available/upapp`
4. Zmień `proxy_pass http://localhost:NOWY_PORT`
5. Reload:
   ```bash
   sudo systemctl daemon-reload
   sudo systemctl restart upapp-backend
   sudo systemctl reload nginx
   ```

## Support

W razie problemów:
1. Sprawdź logi: `sudo journalctl -u upapp-backend -n 100`
2. Sprawdź status: `sudo systemctl status upapp-backend`
3. Sprawdź Nginx: `sudo nginx -t`
4. Sprawdź AWS connectivity z serwera

## Specyfikacja techniczna

- **Backend**: PHP 8.2 + Slim Framework 4
- **Frontend**: React 18 + TypeScript + Vite
- **Database**: AWS DynamoDB
- **Web Server**: Nginx (reverse proxy)
- **Process Manager**: systemd
- **SSL**: Let's Encrypt (opcjonalnie)
- **Deployment**: Git-based, single script

---

**Copyright © www.mindincoach.com**
