#!/usr/bin/env node

const { execSync } = require('child_process');
const path = require('path');

const command = process.argv[2];
const isWindows = process.platform === 'win32';

const scripts = {
  'db:sync': isWindows ? 'powershell.exe -File scripts/aws-setup.ps1' : 'bash scripts/aws-setup.sh',
  'db:seed': isWindows ? 'powershell.exe -File scripts/seed.ps1' : 'bash scripts/seed.sh',
};

if (!scripts[command]) {
  console.error(`Unknown command: ${command}`);
  process.exit(1);
}

try {
  execSync(scripts[command], { stdio: 'inherit', cwd: process.cwd() });
} catch (error) {
  process.exit(error.status || 1);
}
