# UPapp - NVC Forms Management

Nonviolent Communication forms application with React frontend and PHP backend.

## Quick Start

1. Copy environment template: `cp .env_dist .env`
2. Edit `.env` with your configuration
3. Install dependencies: `npm install && cd frontend && npm install && cd ../backend && composer install`
4. Create database tables: `npm run db:create`
5. Seed reference data: `npm run db:seed`
6. Start development servers: `npm run dev`

## Project Structure

- `frontend/` - React application (Vite)
- `backend/` - PHP Slim Framework API
- `scripts/` - Deployment and database scripts
- `data/` - Reference data (feelings, needs)
- `docs/init/` - Implementation plans

## Documentation

See `docs/init/plan.md` for full implementation plan.
