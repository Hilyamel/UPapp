# Phase 6: Deployment & Build Process

> **STATUS:** This file needs conversion to executable format with superpowers standards (checkboxes, TDD, 2-5 min tasks).
> **PRIORITY:** Convert when ready to setup deployment (after main features complete).

## Overview

UPapp uses a WordPress-style deployment model:
- **Frontend**: Build locally to static files, upload via SFTP
- **Backend**: Upload PHP files as-is (no compilation needed)
- **GUI Tool**: Python Tkinter application for one-click deployment

**Note:** Below is reference documentation. Will be converted to executable tasks when setting up deployment.

## Deployment Architecture

```
┌──────────────────────────────────────────┐
│   Developer Machine (Local)              │
│                                           │
│   1. Build React → frontend/dist/        │
│   2. Collect PHP files → backend/        │
│   3. SFTP Upload                          │
│      ├─ frontend/dist/ → /public/        │
│      └─ backend/ → /backend/             │
└──────────────────────────────────────────┘
                    │
                    │ SFTP (Port 22)
                    ▼
┌──────────────────────────────────────────┐
│   Production Server (Shared Hosting)     │
│                                           │
│   /var/www/html/upapp/                   │
│   ├─ public/          (React build)      │
│   │  ├─ index.html                       │
│   │  └─ assets/                          │
│   └─ backend/         (PHP files)        │
│      ├─ public/index.php                 │
│      ├─ src/                             │
│      ├─ config/                          │
│      └─ vendor/       (run composer)     │
└──────────────────────────────────────────┘
```

## Build Process

### Frontend Build

**Development**:
```bash
cd frontend
npm run dev  # Vite dev server on localhost:5173
```

**Production Build**:
```bash
cd frontend
npm run build  # Outputs to frontend/dist/
```

**Build Output**:
```
frontend/dist/
├── index.html
├── assets/
│   ├── index-abc123.js    (minified, hashed)
│   ├── index-def456.css   (minified, hashed)
│   └── logo-ghi789.png
└── favicon.ico
```

**Build Configuration (vite.config.js)**:
```javascript
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  base: '/',  // Change if deploying to subdirectory
  build: {
    outDir: 'dist',
    assetsDir: 'assets',
    sourcemap: false,  // Disable for production
    minify: 'terser',
    rollupOptions: {
      output: {
        manualChunks: {
          vendor: ['react', 'react-dom', 'react-router-dom'],
          primereact: ['primereact'],
        },
      },
    },
  },
})
```

### Backend Preparation

**No build step needed** - PHP runs as-is.

**Files to upload**:
- ✅ All PHP source files (src/, config/, public/)
- ✅ composer.json and composer.lock
- ❌ vendor/ (run `composer install` on server)
- ❌ .env (create manually on server)
- ❌ logs/ (create on server)

**On server after upload**:
```bash
cd /var/www/html/upapp/backend
composer install --no-dev --optimize-autoloader
mkdir -p logs
chmod 777 logs
cp .env.example .env
# Edit .env with production values
```

## SFTP Deployment

### SFTP Configuration

**Environment variables (.env)**:
```env
SFTP_HOST=your-server.com
SFTP_PORT=22
SFTP_USERNAME=your-username
SFTP_PASSWORD=your-password  # or use SSH key
SFTP_REMOTE_PATH=/var/www/html/upapp
```

### Manual SFTP Upload

**Using FileZilla**:
1. Connect to server
2. Navigate to `/var/www/html/upapp/`
3. Upload `frontend/dist/` contents to `public/`
4. Upload `backend/` to `backend/`
5. Exclude: `vendor/`, `.env`, `logs/`, `node_modules/`

**Using command-line SFTP**:
```bash
sftp username@your-server.com
cd /var/www/html/upapp
put -r frontend/dist/* public/
put -r backend/* backend/
bye
```

### Automated SFTP Upload

**scripts/deploy/sftp-upload.js**:

```javascript
const Client = require('ssh2-sftp-client')
const path = require('path')
const sftp = new Client()

async function deploy() {
  const config = {
    host: process.env.SFTP_HOST,
    port: parseInt(process.env.SFTP_PORT) || 22,
    username: process.env.SFTP_USERNAME,
    password: process.env.SFTP_PASSWORD,
  }

  try {
    console.log('Connecting to SFTP server...')
    await sftp.connect(config)

    // Upload frontend build
    console.log('Uploading frontend...')
    const frontendLocal = path.join(__dirname, '../../frontend/dist')
    const frontendRemote = `${process.env.SFTP_REMOTE_PATH}/public`
    await sftp.uploadDir(frontendLocal, frontendRemote)

    // Upload backend (exclude vendor, logs, .env)
    console.log('Uploading backend...')
    const backendLocal = path.join(__dirname, '../../backend')
    const backendRemote = `${process.env.SFTP_REMOTE_PATH}/backend`
    await sftp.uploadDir(backendLocal, backendRemote, {
      filter: (itemPath) => {
        const exclude = ['vendor', 'logs', '.env', 'node_modules', '.git']
        return !exclude.some((dir) => itemPath.includes(`/${dir}`))
      },
    })

    console.log('✓ Deployment complete!')
  } catch (error) {
    console.error('Deployment failed:', error)
    process.exit(1)
  } finally {
    await sftp.end()
  }
}

deploy()
```

**Install dependencies**:
```bash
npm install ssh2-sftp-client
```

**Run deployment**:
```bash
npm run deploy
```

## GUI Launcher Tool

### Overview

Python Tkinter application with tabs for:
1. **Services** - Start/stop dev servers
2. **Database** - Create tables, seed data
3. **Sync** - Copy data between environments
4. **Deploy** - Build and upload to production

### Installation

**requirements.txt**:
```
boto3>=1.26.0
python-dotenv>=1.0.0
paramiko>=3.0.0
```

**Install**:
```bash
pip install -r scripts/gui/requirements.txt
```

### GUI Structure

**scripts/gui/launcher.py**:

```python
import tkinter as tk
from tkinter import ttk, messagebox, scrolledtext
import subprocess
import os
import sys
from pathlib import Path

class UPappLauncher:
    def __init__(self, root):
        self.root = root
        self.root.title("UPapp Launcher")
        self.root.geometry("800x600")
        
        self.project_root = Path(__file__).parent.parent.parent
        self.php_process = None
        self.react_process = None
        
        # Create notebook (tabs)
        self.notebook = ttk.Notebook(root)
        self.notebook.pack(fill='both', expand=True, padx=10, pady=10)
        
        # Create tabs
        self.create_services_tab()
        self.create_database_tab()
        self.create_sync_tab()
        self.create_deploy_tab()
    
    def create_services_tab(self):
        frame = ttk.Frame(self.notebook)
        self.notebook.add(frame, text='Services')
        
        # PHP Server
        ttk.Label(frame, text="PHP Backend Server", font=('Arial', 12, 'bold')).pack(pady=10)
        
        self.php_status = ttk.Label(frame, text="Status: Stopped", foreground="red")
        self.php_status.pack()
        
        btn_frame = ttk.Frame(frame)
        btn_frame.pack(pady=5)
        
        ttk.Button(btn_frame, text="Start PHP", command=self.start_php).pack(side='left', padx=5)
        ttk.Button(btn_frame, text="Stop PHP", command=self.stop_php).pack(side='left', padx=5)
        
        # React Server
        ttk.Label(frame, text="React Frontend Server", font=('Arial', 12, 'bold')).pack(pady=10)
        
        self.react_status = ttk.Label(frame, text="Status: Stopped", foreground="red")
        self.react_status.pack()
        
        btn_frame2 = ttk.Frame(frame)
        btn_frame2.pack(pady=5)
        
        ttk.Button(btn_frame2, text="Start React", command=self.start_react).pack(side='left', padx=5)
        ttk.Button(btn_frame2, text="Stop React", command=self.stop_react).pack(side='left', padx=5)
        
        # Output log
        ttk.Label(frame, text="Output Log:").pack(pady=10)
        self.log_text = scrolledtext.ScrolledText(frame, height=15, state='disabled')
        self.log_text.pack(fill='both', expand=True, padx=10)
    
    def create_database_tab(self):
        frame = ttk.Frame(self.notebook)
        self.notebook.add(frame, text='Database')
        
        ttk.Label(frame, text="DynamoDB Management", font=('Arial', 14, 'bold')).pack(pady=20)
        
        # Environment selector
        env_frame = ttk.Frame(frame)
        env_frame.pack(pady=10)
        
        ttk.Label(env_frame, text="Environment:").pack(side='left', padx=5)
        self.env_var = tk.StringVar(value='dev')
        ttk.Combobox(env_frame, textvariable=self.env_var, values=['dev', 'uat', 'prod'], state='readonly', width=10).pack(side='left')
        
        # Buttons
        ttk.Button(frame, text="Create Tables", command=self.create_tables, width=30).pack(pady=10)
        ttk.Button(frame, text="Seed Reference Data", command=self.seed_data, width=30).pack(pady=10)
        ttk.Button(frame, text="List Tables", command=self.list_tables, width=30).pack(pady=10)
        
        # Output
        ttk.Label(frame, text="Output:").pack(pady=10)
        self.db_log = scrolledtext.ScrolledText(frame, height=15, state='disabled')
        self.db_log.pack(fill='both', expand=True, padx=10)
    
    def create_sync_tab(self):
        frame = ttk.Frame(self.notebook)
        self.notebook.add(frame, text='Sync Tables')
        
        ttk.Label(frame, text="Synchronize DynamoDB Tables", font=('Arial', 14, 'bold')).pack(pady=20)
        
        # Source environment
        src_frame = ttk.Frame(frame)
        src_frame.pack(pady=10)
        ttk.Label(src_frame, text="Source:").pack(side='left', padx=5)
        self.sync_src = tk.StringVar(value='dev')
        ttk.Combobox(src_frame, textvariable=self.sync_src, values=['dev', 'uat', 'prod'], state='readonly', width=10).pack(side='left')
        
        # Target environment
        tgt_frame = ttk.Frame(frame)
        tgt_frame.pack(pady=10)
        ttk.Label(tgt_frame, text="Target:").pack(side='left', padx=5)
        self.sync_tgt = tk.StringVar(value='uat')
        ttk.Combobox(tgt_frame, textvariable=self.sync_tgt, values=['dev', 'uat', 'prod'], state='readonly', width=10).pack(side='left')
        
        # Table selector
        ttk.Label(frame, text="Select tables to sync:").pack(pady=10)
        self.table_listbox = tk.Listbox(frame, selectmode='multiple', height=6)
        self.table_listbox.pack(pady=5)
        for table in ['Users', 'FormSubmissions', 'MagicLinks', 'Feelings', 'Needs']:
            self.table_listbox.insert(tk.END, table)
        
        # Sync button
        ttk.Button(frame, text="Sync Tables", command=self.sync_tables, width=30).pack(pady=20)
        
        # Progress
        self.sync_progress = ttk.Progressbar(frame, mode='indeterminate')
        self.sync_progress.pack(fill='x', padx=10, pady=5)
    
    def create_deploy_tab(self):
        frame = ttk.Frame(self.notebook)
        self.notebook.add(frame, text='Deploy')
        
        ttk.Label(frame, text="Build & Deploy to Production", font=('Arial', 14, 'bold')).pack(pady=20)
        
        # Build button
        ttk.Button(frame, text="1. Build Frontend", command=self.build_frontend, width=30).pack(pady=10)
        
        # Deploy button
        ttk.Button(frame, text="2. Upload via SFTP", command=self.deploy_sftp, width=30).pack(pady=10)
        
        # Full deploy button
        ttk.Button(frame, text="Build & Deploy (All-in-One)", command=self.full_deploy, width=30).pack(pady=20)
        
        # Progress
        self.deploy_progress = ttk.Progressbar(frame, mode='determinate')
        self.deploy_progress.pack(fill='x', padx=10, pady=5)
        
        # Output
        ttk.Label(frame, text="Output:").pack(pady=10)
        self.deploy_log = scrolledtext.ScrolledText(frame, height=15, state='disabled')
        self.deploy_log.pack(fill='both', expand=True, padx=10)
    
    def log(self, message, widget=None):
        if widget is None:
            widget = self.log_text
        widget.config(state='normal')
        widget.insert(tk.END, message + '\n')
        widget.see(tk.END)
        widget.config(state='disabled')
        self.root.update()
    
    def start_php(self):
        try:
            backend_dir = self.project_root / 'backend' / 'public'
            self.php_process = subprocess.Popen(
                ['php', '-S', 'localhost:8080', '-t', str(backend_dir)],
                stdout=subprocess.PIPE,
                stderr=subprocess.PIPE,
                cwd=str(self.project_root / 'backend')
            )
            self.php_status.config(text="Status: Running on localhost:8080", foreground="green")
            self.log("PHP server started on localhost:8080")
        except Exception as e:
            messagebox.showerror("Error", f"Failed to start PHP: {e}")
    
    def stop_php(self):
        if self.php_process:
            self.php_process.terminate()
            self.php_process = None
            self.php_status.config(text="Status: Stopped", foreground="red")
            self.log("PHP server stopped")
    
    def start_react(self):
        try:
            frontend_dir = self.project_root / 'frontend'
            self.react_process = subprocess.Popen(
                ['npm', 'run', 'dev'],
                stdout=subprocess.PIPE,
                stderr=subprocess.PIPE,
                cwd=str(frontend_dir),
                shell=True
            )
            self.react_status.config(text="Status: Running on localhost:5173", foreground="green")
            self.log("React server started on localhost:5173")
        except Exception as e:
            messagebox.showerror("Error", f"Failed to start React: {e}")
    
    def stop_react(self):
        if self.react_process:
            self.react_process.terminate()
            self.react_process = None
            self.react_status.config(text="Status: Stopped", foreground="red")
            self.log("React server stopped")
    
    def create_tables(self):
        env = self.env_var.get()
        self.log(f"Creating tables for {env} environment...", self.db_log)
        try:
            script = self.project_root / 'scripts' / 'dynamodb' / 'create-tables.sh'
            result = subprocess.run(['bash', str(script), env], capture_output=True, text=True)
            self.log(result.stdout, self.db_log)
            if result.returncode == 0:
                messagebox.showinfo("Success", f"Tables created for {env}")
            else:
                messagebox.showerror("Error", result.stderr)
        except Exception as e:
            messagebox.showerror("Error", str(e))
    
    def seed_data(self):
        env = self.env_var.get()
        self.log(f"Seeding reference data for {env}...", self.db_log)
        try:
            script = self.project_root / 'scripts' / 'dynamodb' / 'seed-data.php'
            result = subprocess.run(['php', str(script)], capture_output=True, text=True, 
                                  env={**os.environ, 'DYNAMODB_TABLE_PREFIX': f'UpApp.{env}'})
            self.log(result.stdout, self.db_log)
            if result.returncode == 0:
                messagebox.showinfo("Success", "Reference data seeded")
            else:
                messagebox.showerror("Error", result.stderr)
        except Exception as e:
            messagebox.showerror("Error", str(e))
    
    def list_tables(self):
        self.log("Listing DynamoDB tables...", self.db_log)
        try:
            result = subprocess.run(['aws', 'dynamodb', 'list-tables'], capture_output=True, text=True)
            self.log(result.stdout, self.db_log)
        except Exception as e:
            messagebox.showerror("Error", str(e))
    
    def sync_tables(self):
        src = self.sync_src.get()
        tgt = self.sync_tgt.get()
        
        if src == tgt:
            messagebox.showerror("Error", "Source and target must be different")
            return
        
        if tgt == 'prod':
            confirm = messagebox.askyesno("Confirm", 
                "You are about to sync to PRODUCTION. This will overwrite production data. Continue?")
            if not confirm:
                return
        
        selected = [self.table_listbox.get(i) for i in self.table_listbox.curselection()]
        if not selected:
            messagebox.showerror("Error", "Select at least one table")
            return
        
        self.sync_progress.start()
        messagebox.showinfo("Sync", f"Syncing {len(selected)} tables from {src} to {tgt}...")
        # Call db_sync.py script here
        self.sync_progress.stop()
    
    def build_frontend(self):
        self.log("Building frontend...", self.deploy_log)
        try:
            frontend_dir = self.project_root / 'frontend'
            result = subprocess.run(['npm', 'run', 'build'], capture_output=True, text=True, 
                                  cwd=str(frontend_dir), shell=True)
            self.log(result.stdout, self.deploy_log)
            if result.returncode == 0:
                messagebox.showinfo("Success", "Frontend built successfully")
            else:
                messagebox.showerror("Error", result.stderr)
        except Exception as e:
            messagebox.showerror("Error", str(e))
    
    def deploy_sftp(self):
        self.log("Deploying via SFTP...", self.deploy_log)
        try:
            script = self.project_root / 'scripts' / 'deploy' / 'sftp-upload.js'
            result = subprocess.run(['node', str(script)], capture_output=True, text=True)
            self.log(result.stdout, self.deploy_log)
            if result.returncode == 0:
                messagebox.showinfo("Success", "Deployment complete!")
            else:
                messagebox.showerror("Error", result.stderr)
        except Exception as e:
            messagebox.showerror("Error", str(e))
    
    def full_deploy(self):
        self.build_frontend()
        self.deploy_sftp()

if __name__ == '__main__':
    root = tk.Tk()
    app = UPappLauncher(root)
    root.mainloop()
```

## Server Configuration

### Apache Configuration

**.htaccess** (in `public/` directory):
```apache
RewriteEngine On

# API requests go to backend
RewriteRule ^api/(.*)$ /backend/public/index.php [L,QSA]

# Everything else goes to React app
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.html [L]
```

### Nginx Configuration

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/html/upapp/public;
    index index.html;

    # Frontend (React)
    location / {
        try_files $uri $uri/ /index.html;
    }

    # Backend API
    location /api {
        alias /var/www/html/upapp/backend/public;
        try_files $uri /index.php$is_args$args;
        
        location ~ \.php$ {
            fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
            fastcgi_param SCRIPT_FILENAME $request_filename;
            include fastcgi_params;
        }
    }
}
```

## Post-Deployment Checklist

- [ ] Frontend accessible at https://yourdomain.com
- [ ] API health check works: https://yourdomain.com/api/health
- [ ] Environment variables configured correctly
- [ ] Composer dependencies installed on server
- [ ] Logs directory writable
- [ ] DynamoDB production tables created
- [ ] Reference data seeded
- [ ] Google OAuth redirect URI updated
- [ ] Email service configured
- [ ] SSL certificate installed (Let's Encrypt)
- [ ] Monitoring and logging enabled

## Rollback Procedure

1. Keep previous deployment as backup
2. If new deployment fails, restore from backup via SFTP
3. Verify health check after rollback

## Monitoring & Logs

**Backend logs**: `/var/www/html/upapp/backend/logs/app.log`

**Check logs**:
```bash
tail -f /var/www/html/upapp/backend/logs/app.log
```

**CloudWatch (optional)**: Stream logs to AWS CloudWatch for centralized monitoring

## Next Steps

1. Test deployment process on staging server
2. Document production server setup
3. Create deployment runbook
4. Setup monitoring and alerts
5. Plan backup and disaster recovery strategy
