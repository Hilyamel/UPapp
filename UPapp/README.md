# UPapp - NVC Forms Application

A web application for Nonviolent Communication (NVC) forms with React frontend and PHP backend.

## Quick Start

For detailed setup instructions, see [Quick Start Guide](specs/001-project-foundation/quickstart.md).

### Prerequisites

- Node.js 18+
- PHP 8.1+
- Composer
- AWS CLI (configured with credentials)
- Git

### Setup

```bash
# Clone repository
git clone <repository-url> UPapp
cd UPapp

# Install dependencies
npm install
cd backend && composer install && cd ..

# Configure environment
cp .env_dist .env
# Edit .env with your AWS credentials

# Create DynamoDB tables
npm run db:sync

# Seed sample data (optional)
npm run db:seed

# Start development servers (in separate terminals)
npm run gui       # Frontend on http://localhost:5173
npm run backend   # Backend on http://localhost:8080
```

### Verify Setup

```bash
# Check backend health
curl http://localhost:8080/api/health

# Open frontend
open http://localhost:5173
```

## Project Structure

```
UPapp/
├── frontend/          # React + Vite + TypeScript
├── backend/           # PHP + Slim Framework
├── scripts/           # Utility scripts (db:sync, seed, deploy)
├── docs/              # Documentation
└── specs/             # Feature specifications
```

## Development

- **Frontend**: React 18, Vite, TypeScript, PrimeReact
- **Backend**: PHP 8.1, Slim Framework 4, AWS SDK
- **Database**: AWS DynamoDB
- **Testing**: Vitest (frontend), PHPUnit (backend)

## Available Scripts

- `npm run gui` - Start frontend dev server
- `npm run backend` - Start PHP backend server
- `npm run db:sync` - Create/update DynamoDB tables
- `npm run db:seed` - Populate sample data
- `npm run test` - Run all tests
- `npm run lint` - Run linters
- `npm run build` - Build for production
- `npm run deploy` - Deploy to server

## Documentation

- [Project Constitution](.specify/memory/constitution.md) - Development principles
- [Feature Specifications](specs/) - Feature designs and plans
- [Quick Start Guide](specs/001-project-foundation/quickstart.md) - Detailed setup

## License

[Add license information]
