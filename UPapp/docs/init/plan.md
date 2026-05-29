# UPapp Master Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a complete NVC forms management application with React frontend, PHP Slim backend, DynamoDB database, supporting three form types (DUP, TUP, DOS) with passwordless authentication and admin capabilities.

**Architecture:** React SPA frontend with PrimeReact UI, PHP Slim 4 REST API backend using Repository pattern, AWS DynamoDB for persistence, JWT-based authentication via Google OAuth and Magic Links, SFTP deployment to shared hosting.

**Tech Stack:** React 18 + Vite, PHP 8.1 + Slim 4, DynamoDB, PrimeReact, LocalForage (offline), PHPMailer, Firebase JWT, Python Tkinter (GUI tools)

---

## Overview

Building a new NVC (Nonviolent Communication) forms management application with React frontend, PHP backend, and DynamoDB database. This application enables users to create and manage three types of reflective forms (DUP, TUP, DOS) with passwordless authentication and admin capabilities.

## Related Documentation

This master plan references detailed implementation plans in separate files. Each file contains executable, bite-sized tasks following TDD principles:

- **[01-architecture.md](01-architecture.md)** - Project structure setup, dependency installation (executable tasks)
- **[02-frontend-setup.md](02-frontend-setup.md)** - React application initialization with TDD (executable tasks)
- **[03-backend-setup.md](03-backend-setup.md)** - PHP Slim Framework setup with tests (executable tasks)
- **[04-dynamodb-schema.md](04-dynamodb-schema.md)** - DynamoDB table creation with verification (executable tasks)
- **[05-authentication.md](05-authentication.md)** - Auth implementation with TDD (executable tasks)
- **[06-deployment.md](06-deployment.md)** - Build and deployment automation (executable tasks)
- **[07-environment-config.md](07-environment-config.md)** - Configuration setup and validation (executable tasks)

**Note:** Files 01-07 are being converted from reference documentation to executable implementation plans with checkboxes, TDD cycles, and 2-5 minute tasks.

## Quick Start

1. **Prerequisites**: Node.js 18+, PHP 8.1+, Composer, AWS CLI, Python 3.8+
2. **Setup**: `cp .env_dist .env` and configure variables
3. **Install**: `npm install && cd frontend && npm install && cd ../backend && composer install`
4. **Database**: `npm run db:create && npm run db:seed`
5. **Run**: `npm run gui` or manually start backend and frontend

## Implementation Sequence

### Phase 1: Foundation (Days 1-2)
- Project structure creation
- Dependency initialization
- Environment configuration
- **See**: [01-architecture.md](01-architecture.md), [07-environment-config.md](07-environment-config.md)

### Phase 2: Database Setup (Days 2-3)
- DynamoDB table creation scripts
- Data seeding utilities
- AWS CLI configuration
- **See**: [04-dynamodb-schema.md](04-dynamodb-schema.md)

### Phase 3: Backend Foundation (Days 3-5)
- Slim Framework setup
- Repository pattern implementation
- Middleware configuration
- **See**: [03-backend-setup.md](03-backend-setup.md)

### Phase 4: Authentication (Days 5-7)
- Google OAuth implementation
- Magic Link flow
- JWT token management
- **See**: [05-authentication.md](05-authentication.md)

### Phase 5: Frontend Foundation (Days 7-9)
- React application setup
- Authentication UI
- Routing and navigation
- **See**: [02-frontend-setup.md](02-frontend-setup.md)

### Phase 6: Forms Backend (Days 9-11)
- Form API endpoints
- DynamoDB operations
- Auto-save logic
- **See**: [03-backend-setup.md](03-backend-setup.md)

### Phase 7: Forms Frontend (Days 11-14)
- Form components (DUP, TUP, DOS)
- Auto-save hooks
- Offline support
- **See**: [02-frontend-setup.md](02-frontend-setup.md)

### Phase 8: Admin Panel (Days 14-16)
- Admin API endpoints
- User management UI
- Permission controls
- **See**: [03-backend-setup.md](03-backend-setup.md), [02-frontend-setup.md](02-frontend-setup.md)

### Phase 9: GUI Tools (Days 16-18)
- Python launcher application
- Database sync utility
- Service management
- **See**: [06-deployment.md](06-deployment.md)

### Phase 10: Deployment (Days 18-21)
- Build process
- SFTP upload
- Production configuration
- **See**: [06-deployment.md](06-deployment.md)

## Critical Success Factors

1. **DynamoDB Setup**: All tables created with correct GSIs and TTL
2. **Authentication**: Both Google OAuth and Magic Link working
3. **Forms**: All three form types functional with auto-save
4. **Admin**: User management accessible only to admin@configured.email
5. **Deployment**: Build and SFTP upload working from GUI

## Testing Strategy

**After Each Phase**:
- Unit test new components/services
- Integration test API endpoints
- Manual UI testing for user flows

**Final Acceptance Testing**:
- Complete user journey: Login → Create form → Save → Logout → Login → View form
- Admin journey: Login as admin → List users → Edit user → Delete user
- Deployment: Build → Upload → Verify production

## Estimated Timeline

**Total**: 24 working days (~5 weeks)

- Foundation & Database: 5 days
- Authentication: 5 days  
- Forms Implementation: 6 days
- Admin & Tools: 5 days
- Deployment & Testing: 3 days

## Next Steps

1. Read detailed plan files (01-07)
2. Setup development environment
3. Begin Phase 1 implementation
4. Test incrementally after each phase
5. Document learnings and adjustments

---

**Note**: This is an executive summary. Refer to numbered plan files (01-07) for detailed implementation instructions, code examples, and technical specifications.
