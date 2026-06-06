# OK10 Form Implementation Design

**Date:** 2026-06-06  
**Feature:** Add OK10 (Obrachunek wg kroku 10 AA) form to NVC application

## Overview

Add a new comprehensive form type "OK10" (Step 10 Inventory from AA) to the UPapp NVC forms application. This form includes 13 text fields, 4 checkboxes for violated needs, and collapsible NVC feelings/needs selectors. Only the first 5 fields are sent to AI feedback (empAItycznie) - the rest are private.

## Requirements

### 1. Dashboard Changes

Add a 4th button for OK10 form to the dashboard quick actions:

**Visual Design:**
- Layout: 4 buttons in a row (desktop) / column (mobile)
- Responsive grid: `repeat(4, 1fr)` on desktop, `1fr` on mobile  
- Button color: `#ab47bc` (purple)
- Button text:
  - Title: "OK10"
  - Subtitle: "Obrachunek wg kroku 10 AA"

**Existing buttons for context:**
- TUP: `#4a90e2` (blue) - "Tabela Uczuć i Potrzeb"
- DUP: `#66bb6a` (green) - "Dzienniczek Uczuć i Potrzeb"  
- DOS: `#ffa726` (orange) - "Dziennik Osądów"

**Route:** `/form/OK10`

**Files to modify:**
- `frontend/src/pages/DashboardPage.tsx` - add OK10 button to quick actions grid

### 2. OK10 Form Component

Create new form component: `frontend/src/components/Forms/OK10Form.tsx`

**Form Fields (in order):**

1. **Kto** (Who) - text input, single line
2. **Co** (What) - textarea, 2 rows
3. **Myśli i uczucia** (Thoughts and feelings) - textarea, 2 rows
   - Below textarea: Collapsible "Inne uczucia" list (112 feelings, checkboxes)
4. **Naruszone potrzeby** (Violated needs) - 4 checkboxes in orange box (#f57c00):
   - "Poczucie bezpieczeństwa" (Sense of security)
   - "Potrzeby seksualne" (Sexual needs)
   - "Osobiste relacje / potrzeby społeczne" (Personal relations / social needs)
   - "Ambicja / duma / prestiż" (Ambition / pride / prestige)
   - Layout: 4 columns (desktop) / vertical (mobile)
5. **Inne potrzeby** (Other needs) - textarea, 2 rows (for freetext entry)
   - Below textarea: Collapsible "Potrzeby NVC" list (95 needs, checkboxes)
6. **Wady** (Flaws) - textarea, 2 rows
7. **Pozorne korzyści (błędne przekonania)** (False benefits / erroneous beliefs) - textarea, 2 rows
8. **Ewidentne straty** (Evident losses) - textarea, 2 rows
9. **Co powinno być (zalety)** (What should be / advantages) - textarea, 2 rows
10. **Krzywdy - wobec mnie (kr 8.5)** (Harms against me - step 8.5) - textarea, 2 rows
11. **Moje wobec otoczenia** (Mine toward others) - textarea, 2 rows
12. **Decyzja o wybaczeniu / próbie pojednania** (Decision about forgiveness / reconciliation attempt) - textarea, 2 rows
13. **Zmiany postawy pod wpływem zalet, w kierunku miłości (twardej lub łagodnej)** (Attitude changes under the influence of advantages, toward love - tough or gentle) - textarea, 2 rows

**Closing Text Block (above buttons):**
- Background: `#455a64` (dark gray), white text
- Font-size: `15px`, centered, padding `12px`
- Content:

```
Teraz przejrzyj podsumowanie swojego wpisu. Możesz coś dopisać, jeśli chcesz lub kliknąć po feedback empAItyczny. Odczyta on wpisy pierwszych rubryk: do potrzeb włącznie. Nie odniesie się do innych rubryk, bo to nie przestrzeń dla maszyny. Ale może pomóc Ci uzupełnić uczucia lub potrzeby, które mogł_ś przeoczyć.

Rozważ podzielenie się z kimś tym podsumowaniem, osobiście lub przez telefon. Doświadczenie wielu z nas wskazuje, że gdy otwieramy się na inną, uważnie słuchającą osobę, otwieramy się pełniej na Siłę Większą.

Możesz stosować Obrachunek K10 jako pamiętnik, wyłącznie dla siebie. Życzę Ci, by był narzędziem wzmacniania więzi z Twoją SW i z innymi ważnymi dla Ciebie osobami.
```

**Action Buttons:**
- "Zapisz" (Save) - `#2196f3` (blue), `13px`, centered
- "empAItycznie" (empAIthic feedback) - `#66bb6a` (green), `14px`, centered
- Desktop: horizontal with gap `15px`
- Mobile: vertical stack (`flex-direction: column`), gap `10px`

**No connection scale sliders** - confirmed removed from OK10 form

**Component structure:**
- Reuse existing `CollapsibleList` component for feelings and needs
- Use `useForm` hook for state management (similar to TUP/DUP)
- Form type: `'OK10'`

### 3. Data Model

**Form type:** `OK10`

**TypeScript interface:**
```typescript
interface OK10FormData {
  form_type: 'OK10';
  who: string;                       // Kto
  what: string;                      // Co
  thoughts_feelings: string;         // Myśli i uczucia
  feelings_nvc_selected?: string[];  // Selected NVC feelings IDs
  violated_needs: string[];          // ['security', 'sexual', 'social', 'ambition']
  other_needs: string;               // Inne potrzeby text
  needs_nvc_selected?: string[];     // Selected NVC needs IDs
  flaws: string;                     // Wady
  false_benefits: string;            // Pozorne korzyści
  evident_losses: string;            // Ewidentne straty
  what_should_be: string;            // Co powinno być
  harms_to_me: string;               // Krzywdy wobec mnie
  my_harms_to_others: string;        // Moje wobec otoczenia
  forgiveness_decision: string;      // Decyzja o wybaczeniu
  attitude_changes: string;          // Zmiany postawy
  ai_feedback?: string;              // Generated feedback (optional)
  title?: string;                    // User-editable title
  created_at?: string;
  updated_at?: string;
}
```

**Backend DynamoDB schema:**
- Partition key: `user_id` (string)
- Sort key: `form_id` (string, UUID)
- Attributes: all fields from TypeScript interface + metadata

### 4. AI Feedback (empAItycznie)

**Fields sent to Claude API (first 5 fields only):**
1. `who` (Kto)
2. `what` (Co)
3. `thoughts_feelings` (Myśli i uczucia + selected NVC feelings)
4. `violated_needs` (Naruszone potrzeby - array of selected checkboxes)
5. `other_needs` + `needs_nvc_selected` (Inne potrzeby text + selected NVC needs)

**Fields NOT sent to AI (private):**
- Fields 6-13 (wady, pozorne korzyści, ewidentne straty, co powinno być, krzywdy, decyzja o wybaczeniu, zmiany postawy)
- Reason stated in closing text: "nie przestrzeń dla maszyny" (not space for machine)

**Implementation:**
- Modify `backend/src/Services/ClaudeService.php` to handle OK10 form type
- Use existing empathy prompt structure (OBSERWACJA-UCZUCIE-POTRZEBA-PYTANIE)
- Prompt should focus on helping identify overlooked feelings/needs

### 5. Routing

**Frontend routing (`App.tsx`):**
```tsx
<Route path="/form/OK10" element={<FormPage formType="OK10" />} />
```

**Backend routing (`backend/src/routes.php`):**
- Existing form endpoints already handle different form types
- No changes needed if form type is passed correctly

### 6. Component Reuse

**Reuse existing components:**
- `CollapsibleList` - for NVC feelings and needs selection
- `useForm` hook - for form state management
- `generateAIFeedback` service - for empAItycznie button

**Pattern to follow:**
- Study `TUPForm.tsx` and `DUPForm.tsx` for structure
- Follow same save/submit flow
- Maintain consistent styling with existing forms

### 7. Deployment Changes

**CRITICAL: Remove and recreate deployment workflow**

Current deployment is broken. Need to:

1. **Delete existing:**
   - `.github/workflows/deploy-production.yml`
   - Remove deployment automation section from `DEPLOYMENT.md` (keep manual fallback only)

2. **Create new deployment from scratch:**

**Server credentials (from przykladowy_panel.jpg):**
```
Domain: upapp.mindincoach.com
FTP Server: hinol.ftp.dhosting.pl
FTP Login: ohj9oo_upappmin
FTP Password: ioFiy1bua2tu

MySQL Server: hinol.mysql.dhosting.pl
Database: phagh9_upappmin
MySQL User: uphue9_upappmin
MySQL Password: nohx6Vae1TuH
```

**New workflow should:**
- Build frontend with `VITE_API_URL=https://upapp.mindincoach.com/api`
- Install backend dependencies (`composer install --no-dev --optimize-autoloader`)
- Generate production `.env` from GitHub secrets
- Upload via FTP to server
- Exclude: `.git*`, `node_modules/`, `tests/`, `frontend/src/`, dev files

**GitHub Secrets to configure:**
- `FTP_HOST`, `FTP_USERNAME`, `FTP_PASSWORD`
- `AWS_REGION`, `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`
- `SMTP_HOST`, `SMTP_PORT`, `SMTP_USERNAME`, `SMTP_PASSWORD`, `SMTP_FROM_EMAIL`
- `ANTHROPIC_API_KEY`

## Testing Requirements

### Frontend Tests

**File:** `frontend/src/components/Forms/OK10Form.test.tsx`

Test cases:
1. Component renders all 13 fields correctly
2. Collapsible lists (Uczucia NVC, Potrzeby NVC) start collapsed
3. Collapsible lists expand/collapse on click
4. Violated needs checkboxes are responsive (4 columns desktop, vertical mobile)
5. Form submits with correct data structure
6. empAItycznie button triggers AI feedback
7. Closing text displays correctly above buttons
8. Form saves draft on change (if auto-save enabled)

### Backend Tests

**File:** `backend/tests/Unit/Handlers/FormHandlerTest.php`

Test cases:
1. Create OK10 form - saves all 13 fields correctly
2. Read OK10 form - retrieves all fields
3. Update OK10 form - updates specific fields
4. Delete OK10 form - removes from database
5. List forms - includes OK10 forms in results

**File:** `backend/tests/Unit/Services/ClaudeServiceTest.php`

Test cases:
1. AI feedback for OK10 - only sends first 5 fields
2. AI feedback for OK10 - excludes fields 6-13
3. AI response parsing for OK10 form

### Integration Tests

**File:** `backend/tests/Integration/OK10FormIntegrationTest.php`

Test cases:
1. End-to-end: create form → save → retrieve → verify all fields
2. empAItycznie flow: submit first 5 fields → receive AI feedback → save feedback
3. Form list includes OK10 forms with correct metadata
4. Dashboard displays OK10 button and routes correctly

### Manual Testing Checklist

- [ ] Dashboard shows 4 buttons in correct layout (desktop: row, mobile: column)
- [ ] OK10 button has purple color (#ab47bc)
- [ ] Clicking OK10 navigates to `/form/OK10`
- [ ] Form displays all 13 fields in correct order
- [ ] "Uczucia NVC" list is collapsed by default, expands on click
- [ ] "Potrzeby NVC" list is collapsed by default, expands on click
- [ ] Violated needs: 4 checkboxes in orange box, responsive layout
- [ ] Closing text displays with correct styling (dark gray bg, white text, 15px)
- [ ] "Zapisz" button saves form correctly
- [ ] "empAItycznie" button generates AI feedback (check only first 5 fields sent)
- [ ] Form appears in dashboard list after saving
- [ ] Deployment workflow succeeds and site is accessible at upapp.mindincoach.com

## Architecture Decisions

### Why new component vs. extending existing?

**Decision: Create new `OK10Form.tsx` component**

Rationale:
- OK10 has 13 unique fields vs. TUP/DUP structure
- Different layout (no connection scales, different field grouping)
- Simpler to maintain separate component than complex conditionals
- Clear separation of concerns

Alternative considered: Add `variant="OK10"` to existing form component. Rejected due to increased complexity and coupling.

### Why reuse CollapsibleList?

**Decision: Reuse existing `CollapsibleList` component**

Rationale:
- Already handles feelings/needs selection pattern
- Consistent UX across forms
- Tested and working
- Saves development time

### Why limit AI feedback to 5 fields?

**Decision: Only send first 5 fields to Claude API**

Rationale:
- User requirement: fields 6-13 are "not space for machine"
- Privacy: latter fields contain sensitive personal work (forgiveness decisions, attitude changes)
- AI purpose: help identify overlooked feelings/needs, not process entire inventory
- Explicitly stated in closing text to manage user expectations

## Open Questions & Assumptions

### Questions (to be confirmed before implementation):

1. **Validation rules:** Are any fields required? Or all optional like existing forms?
   - **Assumption:** All fields optional (like TUP/DUP) unless specified otherwise
   
2. **Auto-save:** Should OK10 form auto-save drafts like other forms?
   - **Assumption:** Yes, follow existing auto-save pattern with `useForm` hook
   
3. **Title editing:** Should users be able to edit form title after creation?
   - **Assumption:** Yes, same as existing forms (title appears in dashboard list)

4. **Violated needs checkbox IDs:** What values to store?
   - **Assumption:** `['security', 'sexual', 'social', 'ambition']` as strings
   
5. **Mobile breakpoint:** At what screen width does layout switch to mobile?
   - **Assumption:** Follow existing responsive breakpoint (likely 768px or use existing CSS)

6. **AI feedback prompt:** Should we create OK10-specific prompt or reuse existing?
   - **Assumption:** Reuse existing empathy prompt, it's generic enough for OK10 fields

7. **Deployment timing:** Should new deployment workflow be tested in staging first?
   - **Assumption:** Yes, test on staging branch before merging to main

8. **MySQL vs. DynamoDB:** Current app uses DynamoDB. Is MySQL for future migration?
   - **Assumption:** Continue using DynamoDB, MySQL credentials saved for potential future use

### Assumptions:

- Existing `CollapsibleList` component can handle OK10's feelings/needs without modification
- `useForm` hook can handle OK10 form type without changes
- Backend `FormHandler` already supports arbitrary form types and field structures
- Existing authentication/authorization applies to OK10 forms (no special permissions)
- Form data retention policy same as other forms (no auto-deletion)
- No pagination needed for OK10 forms in dashboard (assuming reasonable number of entries)

## Implementation Approach

**Recommended: Incremental implementation**

1. **Phase 1: Core form (no AI, no deployment)**
   - Add OK10 button to dashboard
   - Create `OK10Form.tsx` component with all fields
   - Add routing
   - Backend support for OK10 form type
   - Basic unit tests

2. **Phase 2: AI feedback**
   - Modify `ClaudeService` for OK10
   - Implement 5-field filtering
   - Integration tests for AI feedback

3. **Phase 3: Deployment**
   - Remove old deployment workflow
   - Create new deployment from scratch
   - Test in staging
   - Deploy to production

4. **Phase 4: Polish & tests**
   - Full test coverage
   - Manual testing checklist
   - Bug fixes
   - Performance optimization if needed

**Rationale:** Incremental approach allows testing each piece independently and reduces risk of breaking existing functionality.

## Success Criteria

Implementation is complete when:

1. ✅ OK10 button appears on dashboard with correct styling
2. ✅ Clicking OK10 opens form with all 13 fields
3. ✅ Form saves all fields correctly to backend
4. ✅ empAItycznie generates feedback using only first 5 fields
5. ✅ Form appears in dashboard list after saving
6. ✅ All tests pass (unit, integration, manual checklist)
7. ✅ Deployment workflow successfully deploys to upapp.mindincoach.com
8. ✅ No regressions in existing forms (TUP, DUP, DOS)
9. ✅ Mobile responsive layout works correctly
10. ✅ Collapsible NVC lists function properly

## Files to Create

**Frontend:**
- `frontend/src/components/Forms/OK10Form.tsx` - main form component
- `frontend/src/tests/components/Forms/OK10Form.test.tsx` - unit tests

**Backend:**
- `backend/tests/Unit/Handlers/OK10FormHandlerTest.php` - form handler tests
- `backend/tests/Integration/OK10FormIntegrationTest.php` - integration tests

**Deployment:**
- `.github/workflows/deploy-production-new.yml` - new deployment workflow (after removing old one)

**Documentation:**
- Update `README.md` - document OK10 form type
- Update `DEPLOYMENT.md` - new deployment instructions (manual fallback only, remove broken automation section)

## Files to Modify

**Frontend:**
- `frontend/src/pages/DashboardPage.tsx` - add OK10 button
- `frontend/src/pages/FormPage.tsx` - add OK10 case to form type switch
- `frontend/src/App.tsx` - add OK10 route (if not using FormPage routing)
- `frontend/src/services/forms.ts` - ensure OK10 type is handled

**Backend:**
- `backend/src/Services/ClaudeService.php` - add OK10 handling, filter fields for AI
- `backend/src/Handlers/FormHandler.php` - ensure OK10 form type is supported (may already be generic enough)

**Deployment:**
- Remove: `.github/workflows/deploy-production.yml`
- Remove automation section from: `DEPLOYMENT.md`

## Dependencies

**No new dependencies required:**
- Reuses existing components, services, and infrastructure
- Uses existing NVC reference data (feelings/needs lists)
- Uses existing Claude API integration

**Existing dependencies:**
- React 18 + TypeScript
- PrimeReact (if used for UI components)
- PHP 8.1 + Slim Framework
- AWS SDK (DynamoDB)
- Anthropic SDK (Claude API)

## Risk Assessment

**Low risks:**
- Form rendering - standard React patterns
- Data persistence - existing infrastructure
- Component reuse - `CollapsibleList` proven

**Medium risks:**
- AI feedback filtering - need to ensure only 5 fields sent (mitigated by unit tests)
- Responsive layout for 4 dashboard buttons (mitigated by testing)
- Deployment recreation - starting from scratch (mitigated by careful testing in staging)

**High risks:**
- None identified

**Mitigation strategies:**
- Comprehensive testing at each phase
- Code review before deployment workflow changes
- Staging environment testing before production
- Rollback plan: keep old deployment code in git history until new deployment verified

## Timeline Estimate

**Total: ~8-12 hours of development time**

- Phase 1 (Core form): 4-5 hours
- Phase 2 (AI feedback): 2-3 hours
- Phase 3 (Deployment): 2-3 hours
- Phase 4 (Tests & polish): 1-2 hours

**Assumptions:**
- Developer familiar with codebase
- No unexpected blockers
- Deployment testing can be done quickly

## Notes

- This design is based on interactive brainstorming with visual mockups
- User confirmed: no connection scale sliders needed
- User confirmed: fields 6-13 are private (not for AI)
- User confirmed: dashboard layout with 4 equal-sized buttons
- User confirmed: collapsible NVC lists should start collapsed
- User confirmed: closing text font-size is 15px (2pt larger than field labels)
- Deployment credentials are from przykladowy_panel.jpg reference image
- Old deployment workflow has issues and needs complete rewrite
