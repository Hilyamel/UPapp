# Contributing to UPapp

Thank you for your interest in contributing to UPapp!

## Development Workflow

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd UPapp
   ```

2. **Set up your environment**
   - Follow the [Quick Start Guide](specs/001-project-foundation/quickstart.md)
   - Copy `.env_dist` to `.env` and configure your credentials

3. **Create a feature branch**
   ```bash
   git checkout -b feature/your-feature-name
   ```

4. **Make your changes**
   - Write tests first (TDD approach per constitution)
   - Implement your feature
   - Run linters: `npm run lint`
   - Run tests: `npm run test`

5. **Commit your changes**
   - Pre-commit hooks will automatically run linters
   - Follow commit message format: `<type>: <description>`
   - Types: `feat`, `fix`, `docs`, `chore`, `test`, `refactor`

6. **Push and create a pull request**
   ```bash
   git push origin feature/your-feature-name
   ```

## Code Quality Standards

- **Frontend**: ESLint + Prettier (runs automatically on commit)
- **Backend**: PHP_CodeSniffer (PSR-12 standard)
- **Tests**: Required for all new features
- **Documentation**: Update README.md if adding new features

## Constitution

All contributions must follow the [Project Constitution](.specify/memory/constitution.md), which defines:
- Code quality standards
- Testing discipline (TDD)
- UX consistency
- Performance requirements
- Security standards
- Environment isolation
- Dependency minimalism

## Testing

- Write tests before implementation (TDD)
- Frontend: Vitest (`npm run test:frontend`)
- Backend: PHPUnit (`npm run test:backend`)
- No mocking of DynamoDB (use `UpApp.dev.*` tables)

## Questions?

- Check the [README.md](README.md) for setup instructions
- Review [Project Constitution](.specify/memory/constitution.md) for principles
- See [Feature Specifications](specs/) for implementation plans
