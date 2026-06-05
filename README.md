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
# Edit .env with:
# - AWS credentials
# - ANTHROPIC_API_KEY (required for empAItycznie feature)
#   Get your API key from: https://console.anthropic.com/settings/keys

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

## empAItycznie Feature

The empAItycznie button uses Claude AI to provide empathetic NVC feedback on form submissions.

### Configuration

1. **API Key**: Add your Anthropic API key to `.env` (project root):
   ```bash
   ANTHROPIC_API_KEY=sk-ant-api03-...
   ```
   Get your key from: https://console.anthropic.com/settings/keys

2. **Prompt Customization**: Edit `empathy-prompt.txt` in the UPapp root to customize AI responses. The prompt defines:
   - Response structure (OBSERWACJA-UCZUCIE-POTRZEBA-PYTANIE)
   - NVC feelings and needs lists
   - Tone and emoticons
   - Examples

3. **Testing**: Run integration tests with:
   ```bash
   cd backend
   vendor/bin/phpunit --group integration
   ```

### How It Works

- Button click sends form data to `/api/forms/{id}/ai-feedback`
- Backend calls Claude API with system prompt from `empathy-prompt.txt`
- Response follows NVC "dwójka empatyczna" structure
- If API key is missing, returns fallback message

## Documentation

- [Project Constitution](.specify/memory/constitution.md) - Development principles
- [Feature Specifications](specs/) - Feature designs and plans
- [Quick Start Guide](specs/001-project-foundation/quickstart.md) - Detailed setup

## License

[Add license information]
