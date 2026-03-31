# Task

# **FinDesk — High-Level Design (HLD)**

**Project:** Expense & Invoice Management System

**Stack:** Laravel 13 + Livewire 4

**PHP:** 8.3+ (required by Laravel 13)

**Base repo:** `nunomaduro/laravel-starter-kit`

**Duration:** 30 days (intern evaluation)

---

## **0) Purpose**

This document is the **single source of truth** for the FinDesk project. A companion guide (`task-guide.md`) is provided separately with learning direction.

### **Scope Tiers**

- **Core (Must Complete)** — Mandatory modules covering fundamental Laravel and Livewire concepts. Focus here first.
- **Stretch (Extra Credit)** — Attempt only after Core is complete and working. Covers advanced patterns.

**You will be evaluated primarily on Core quality, not on how many Stretch features you attempt.**

---

## **1) AI Usage Policy**

You may use AI/LLM tools for:

- Looking up syntax and understanding error messages
- Explaining concepts you've read in the official docs

You must NOT use AI/LLM tools for:

- Generating entire files, components, or features
- Writing migrations, models, controllers, or Livewire components wholesale
- Writing tests

**Rule:** If you cannot explain a line of code without looking it up, you should not have written it. Review meetings will include "explain this line" questions on randomly selected code.

**Commit discipline:** Each commit message must include a one-line note about what you learned. Example: `feat: add expense approval — learned state transitions with enum + match`

---

## **2) Glossary**

- **Organization**: The company/entity using the system. Has departments, employees, and clients.
- **Department**: A subdivision of the organization (Engineering, Marketing, Sales, etc.). Has a budget.
- **Expense**: A cost incurred by an employee (travel, meals, software, etc.) submitted for reimbursement.
- **Expense Category**: A classification of expenses (Travel, Meals, Software, Office Supplies, etc.) with configurable rules.
- **Approval**: A manager's decision to approve or reject an expense. Simple flow: Submit → Manager Approves/Rejects.
- **Invoice**: A bill sent to a client for services/products. Has line items, tax, and a payment lifecycle.
- **Line Item**: A single row on an invoice (description, quantity, unit price, tax rate).
- **Payment**: A record of money received against an invoice (partial or full).
- **Client**: An external company/person to whom invoices are sent.
- **Receipt**: A photo or PDF attached to an expense as proof.
- **Tax Rate**: A configurable tax percentage (GST, VAT, etc.) applied to invoice line items.

---

## **3) Roles & Permissions**

| **Role** | **Can do** |
| --- | --- |
| **Admin** | Everything. Manage organization settings, departments, categories, tax rates, users, view all reports. |
| **Manager** | Approve/reject expenses for their department. View department reports. Create invoices. |
| **Employee** | Submit expenses, view own expenses, view own reimbursement history. |
| **Accountant** | Create/edit/send invoices. Record payments. View all financial reports. Cannot approve expenses. |

**Implementation:** Use Laravel's built-in Gate and Policy system (NOT Spatie package — this project uses native authorization to learn a different approach than task2).

**Verify:** Employee cannot approve expenses. Accountant cannot approve expenses. Manager can only approve expenses from their own department. Employee cannot view another employee's expenses.

---

## **4) Data Model**

### **4.1 Required entities**

| **Entity** | **Key Fields** | **Notes** |
| --- | --- | --- |
| **User** | name, email, password, role (enum), department_id, manager_id (nullable) | Employee belongs to department, has a manager |
| **Department** | name, description, monthly_budget | Has many employees |
| **ExpenseCategory** | name, description, max_amount (nullable), requires_receipt (bool) | Configurable rules per category |
| **Expense** | user_id, category_id, department_id, title, description, amount, currency, status (enum), receipt_path, submitted_at, reviewed_at, reviewed_by, rejection_reason | Core entity |
| **Client** | name, email, phone, address, tax_number, notes | Invoice recipient |
| **Invoice** | client_id, created_by, invoice_number (auto-generated), status (enum), issue_date, due_date, notes, subtotal, tax_total, total, currency | Has many line items |
| **InvoiceLineItem** | invoice_id, description, quantity, unit_price, tax_rate_id, line_total, tax_amount | Individual invoice row |
| **TaxRate** | name, percentage, is_default, is_active | Configurable tax rates (e.g., GST 18%, VAT 20%) |
| **Payment** | invoice_id, amount, payment_date, payment_method (enum), reference_number, notes | Partial/full payment record |
| **Activity** | Auto-generated event log | Who did what, when |
| **Attachment** | attachable (polymorphic), user_id, path, disk, original_name, mime_type, size | On expenses AND invoice line items |

### **4.2 Required relationship types**

| **Relationship** | **Example** |
| --- | --- |
| **One to Many** | Department has many Users, Client has many Invoices |
| **Many to Many** | (none required — simpler model than task2) |
| **Self-referential** | User has a manager (manager_id → users.id) |
| **Polymorphic** | Attachment belongs to Expense OR InvoiceLineItem |
| **Has Many Through** | Department has many Expenses through Users |
| **Has One of Many** | Invoice has one latest Payment |
| **One to One** | User has one Department assignment |

### **4.3 Required enums**

| **Enum** | **Values** |
| --- | --- |
| `UserRole` | Admin, Manager, Employee, Accountant |
| `ExpenseStatus` | Draft, Submitted, Approved, Rejected, Reimbursed |
| `InvoiceStatus` | Draft, Sent, Viewed, Partially Paid, Paid, Overdue, Cancelled |
| `PaymentMethod` | BankTransfer, CreditCard, Cash, Cheque, UPI, Other |
| `Currency` | INR, USD, EUR, GBP (minimum 4) |

Each enum must have `label()` and `color()` helper methods.

---

## **5) Core Modules (Must Complete)**

---

### **Module A — Authentication & Organization Setup**

1. User registration and login
2. Organization settings page (name, address, logo, default currency, fiscal year start — Admin only)
3. Department CRUD (Admin only)
4. User management: create users, assign roles, assign to department, assign manager (Admin only)
5. User profile page (view/edit own details)

**Verify:** Employee cannot access admin settings. Users without a department cannot submit expenses.

---

### **Module B — Expense Categories & Tax Rates**

1. Expense Category CRUD (Admin only) — name, description, max_amount, requires_receipt
2. Tax Rate CRUD (Admin only) — name, percentage, is_default, is_active
3. Mark one tax rate as default (when creating invoice line items, the default tax is pre-selected)
4. Deactivate a tax rate (cannot be used on new invoices but existing invoices keep their rate)

**Validation rules:**

- Category max_amount must be positive if set
- Tax rate percentage must be between 0 and 100
- Cannot delete a category that has expenses associated with it
- Cannot delete a tax rate that is used on any invoice line item

**Verify:** Try deleting a category with existing expenses — must fail with clear error. Deactivated tax rate does not appear in dropdowns but existing invoices show it correctly.

---

### **Module C — Expense Submission & Management**

1. Submit expense (title, description, amount, category, receipt upload)
2. My Expenses list with filters: status, category, date range, amount range
3. Expense detail page (title, amount, category, status, receipt preview, approval history)
4. Edit expense (only while in Draft status)
5. Delete expense (only while in Draft status)
6. Expense status lifecycle: Draft → Submitted → Approved/Rejected → Reimbursed

**State machine rules (must be enforced):**

- Draft → Submitted (employee submits)
- Submitted → Approved (manager approves)
- Submitted → Rejected (manager rejects — must provide reason)
- Approved → Reimbursed (admin/accountant marks as reimbursed)
- Rejected → Draft (employee can re-edit and resubmit)
- No other transitions allowed (e.g., cannot go from Approved back to Draft)

**Validation rules:**

- Amount must be positive
- If category has max_amount set, expense amount cannot exceed it
- If category has requires_receipt = true, receipt file is mandatory
- Receipt must be an image (jpg, png) or PDF, max 5MB
- Cannot submit an expense that would cause department's monthly total to exceed department budget
- Cannot edit/delete an expense that is not in Draft status

**Verify:** Submit expense in a category with max_amount $500, enter $600 — must fail. Submit in a requires_receipt category without receipt — must fail. Submit an expense that pushes department over budget — must fail. Try editing a Submitted expense — must fail.

---

### **Module D — Expense Approval Workflow**

1. Manager dashboard: pending approvals list (expenses from their department in Submitted status)
2. Approve expense (single click with confirmation)
3. Reject expense (must provide rejection reason)
4. View approval history on expense detail (who approved/rejected, when, reason)
5. Notification to employee when expense is approved or rejected

**Requirement:** The approval action must update the expense status AND create an activity log entry AND send a notification — all through events and listeners, not inline code.

**Verify:** Manager sees only their department's expenses. Approve an expense — employee gets notification, activity log shows "Manager X approved Expense Y". Reject — reason is saved and visible on detail page. Manager of Department A cannot approve Department B expenses.

---

### **Module E — Invoice Creation & Management**

1. Create invoice for a client (select client, issue date, due date, notes)
2. Add line items to invoice (description, quantity, unit price, tax rate)
3. Auto-calculate: line total = quantity * unit price, tax amount = line total * tax rate %, subtotal, tax total, grand total
4. Invoice list with filters: status, client, date range, amount range
5. Invoice detail page (header, line items, totals, payment history)
6. Edit invoice (only while in Draft status)
7. Send invoice (changes status from Draft to Sent — generates PDF and records the action)
8. Cancel invoice (only from Draft or Sent status — must provide reason)
9. Invoice number auto-generation: `INV-YYYY-NNNN` format (e.g., INV-2026-0042), sequential, no gaps

**Calculation rules (must be precise):**

- Line total = quantity * unit_price (rounded to 2 decimal places)
- Tax amount per line = line_total * (tax_rate_percentage / 100) (rounded to 2 decimal places)
- Subtotal = sum of all line totals
- Tax total = sum of all tax amounts
- Grand total = subtotal + tax total
- All monetary values stored as integers (cents/paise) to avoid floating point errors

**Verify:** Create invoice with 3 line items at different tax rates. Totals must be mathematically correct. Edit after sending — must fail. Invoice number increments correctly across invoices.

---

### **Module F — Payment Tracking**

1. Record payment against an invoice (amount, date, method, reference number)
2. Support partial payments (multiple payments against one invoice)
3. Auto-update invoice status based on payments:
    - Total payments < invoice total → Partially Paid
    - Total payments >= invoice total → Paid
4. Payment history on invoice detail page
5. Cannot record payment on a Cancelled or Draft invoice
6. Cannot record payment that exceeds remaining balance

**Verify:** Invoice for $1000 — record $400 → status becomes Partially Paid. Record $600 → status becomes Paid. Try recording $100 more — must fail (overpayment). Try paying a Cancelled invoice — must fail.

---

### **Module G — Notifications & Scheduling**

1. Notification bell in header (unread count badge)
2. Notification list page (mark as read, mark all as read)
3. Notification events:
    - Expense approved/rejected (to employee)
    - Expense submitted (to manager)
    - Invoice overdue (to accountant + admin)
    - Payment received (to invoice creator)
4. Scheduled command: daily at 9:00 AM — check for invoices past due_date with status Sent or Partially Paid, update status to Overdue, notify accountant

**Requirement:** Notifications must be queued. Overdue check must be an artisan command registered with the scheduler.

**Verify:** Submit expense — manager gets notification. Approve — employee gets notification. Run `schedule:run` with an overdue invoice — status changes to Overdue, accountant gets notification.

---

## **6) Stretch Modules (Extra Credit)**

Attempt ONLY after all Core Modules are complete.

---

### **Stretch A — PDF Invoice Generation**

1. Generate professional invoice PDF from a Blade template (company logo, client details, line items, totals, payment terms)
2. PDF generation runs as a background job
3. "Send Invoice" button generates PDF and marks invoice as Sent
4. Download previously generated PDF from invoice detail page

**Package:** Use **barryvdh/laravel-dompdf** or **spatie/laravel-pdf**.

**Job requirements:**

- Must implement `ShouldQueue`
- Must have `failed()` method
- Use `#[Tries(3)]`, `#[Timeout(60)]` attributes
- Store generated PDF path on the invoice record

**Verify:** Click "Send Invoice" → loading state → PDF generated → status becomes Sent. Download PDF — contains correct line items and totals.

---

### **Stretch B — Recurring Invoices**

1. Mark an invoice as a recurring template (frequency: weekly, monthly, quarterly, yearly)
2. Scheduled command: daily — check for recurring invoices due today, auto-create a new invoice from the template
3. New invoice gets next sequential invoice number
4. Recurring invoice list shows next generation date

**Verify:** Create a monthly recurring invoice on March 1. On April 1 (or simulated), a new invoice is auto-created with the same line items and a new invoice number.

---

### **Stretch C — Financial Reports & Dashboard**

1. Expense report: total expenses by category, by department, by month (configurable date range)
2. Revenue report: total invoiced, total collected, total outstanding, by client, by month
3. Department budget utilization: budget vs actual spending per department per month
4. Cash flow summary: money in (payments) vs money out (reimbursements) per month

**Reporting requirement:** All reports must use DB Facade aggregate queries (SUM, GROUP BY, JOIN). Each query must have inline comment explaining why DB Facade was chosen.

**Caching:** Report data must be cached (5 min TTL) with invalidation when underlying data changes.

**Verify:** Reports show correct totals. Numbers match when manually calculated from raw data.

---

### **Stretch D — Receipt OCR (AI-Powered)**

1. When a receipt image is uploaded, run OCR to extract: vendor name, amount, date
2. Auto-fill expense form fields from OCR results (user can edit before submitting)
3. OCR runs as a background job

**Implementation:** Use **Laravel AI SDK** with a vision-capable model (or a dedicated OCR API). The agent receives the image and returns structured output (vendor, amount, date).

**Requirements:**

- Agent with `HasStructuredOutput`
- Background job for OCR processing
- Results stored in DB, linked to the expense
- Tests use `Agent::fake()`

**Verify:** Upload receipt photo → loading state → form auto-fills with extracted vendor/amount/date. User corrects if needed and submits.

---

### **Stretch E — Client Portal**

1. Clients can log in with a separate auth guard (or magic link)
2. Client sees only their invoices
3. Client can download invoice PDF
4. Client can mark invoice as "Viewed" (updates status from Sent → Viewed)

**Verify:** Client logs in, sees only their invoices. Cannot see other clients' invoices. Downloading PDF works. Viewing marks status.

---

### **Stretch F — Multi-Currency Support**

1. Expenses and invoices can be in different currencies (INR, USD, EUR, GBP)
2. Exchange rates table (admin managed) with date-based rates
3. Dashboard shows totals converted to organization's default currency
4. Invoice totals shown in invoice currency, reports in default currency

**Verify:** Create USD invoice, record payment in USD. Dashboard shows equivalent in default currency using the correct exchange rate.

---

## **7) Engineering Standards**

### **7.1 Architecture layers**

```
Livewire Components    → UI state, user interaction, dispatch
Actions / Services     → Business logic (reusable, testable)
Form Requests          → Validation (min 5 form request classes)
Custom Rule Classes    → DB-dependent validation (min 3 custom rules)
Events & Listeners     → Side effects: notifications, activity logging (min 4 events)
Jobs                   → Async: notifications, PDF generation, scheduled tasks (min 3 jobs)
Observers              → Model lifecycle hooks (min 2)
Models                 → Relationships, scopes, accessors, casts — NO business logic
```

### **7.2 State machines (required)**

Both Expense and Invoice have strict status lifecycles. You must enforce valid transitions — invalid transitions must throw an exception. Implement a transition validation method (using `match` on the current status) that defines allowed next states.

### **7.3 Money handling (required)**

All monetary values must be stored as **integers** (cents/paise) in the database to avoid floating point precision issues. Display as formatted currency in the UI using an accessor.

Example: $19.99 stored as `1999` in the database, displayed as `$19.99` via a model accessor.

### **7.4 Query approach**

**Eloquent:** for CRUD, relationships, scopes, model events. **DB Facade:** for financial reports, aggregate queries, budget calculations.

Every `DB::table()` call must have an inline comment explaining why DB Facade was chosen.

### **7.5 Livewire features required (Core)**

All of these must be used at least once:

`wire:model`, `wire:model.live` (with debounce), `wire:click`, `wire:submit`, `wire:loading`, `wire:confirm`, `wire:poll`, `#[Validate]`, `#[Url]`, `#[Locked]`, `#[Computed]`, `WithPagination`, `WithFileUploads`, Form Objects (at least 1), nested components, events/dispatch, lifecycle hooks (`mount`, `updated`, `dehydrate`).

### **7.6 Dynamic line items (Livewire pattern — required)**

The invoice line item form is a critical Livewire learning exercise:

- Start with one empty line item row
- "Add Line" button adds another row dynamically
- "Remove" button on each row removes it
- Changing quantity/price/tax on any row recalculates that row's total AND the invoice totals in real-time
- All of this happens server-side via Livewire (no client-side JS calculation)

This teaches array-based Livewire properties, dynamic form rows, and computed recalculation.

### **7.7 Auto-generated invoice numbers**

Invoice numbers must be sequential with no gaps: `INV-2026-0001`, `INV-2026-0002`, etc. Year resets the counter. This requires careful handling of concurrent creates (think about race conditions). Use database-level locking or a sequence table.

### **7.8 Scheduling**

1. Daily at 9:00 AM — overdue invoice check
2. (Stretch B) Daily — recurring invoice generation

---

## **8) Testing Requirements**

### **8.1 Rules**

- Every test must test a BEHAVIOR from this HLD
- No padding tests (DTO constructors, enum labels, factory assertions)
- Every test must fail if the feature breaks
- No circular or unfailable assertions
- Fake external services: `Notification::fake()`, `Queue::fake()`, `Storage::fake()`

### **8.2 Core minimum tests (20)**

| **Category** | **Min** | **Examples** |
| --- | --- | --- |
| Authorization / RBAC | 4 | Employee cannot approve, manager cannot approve other department, accountant cannot approve, employee cannot view others' expenses |
| Expense state machine | 4 | Draft→Submitted works, Submitted→Approved works, Approved→Draft blocked, Rejected→Draft works (re-edit) |
| Expense validation | 3 | Amount exceeds category max, missing receipt when required, over department budget |
| Invoice calculations | 3 | Line item total correct, tax calculation correct, grand total correct with multiple items/rates |
| Payment logic | 2 | Partial payment updates status, overpayment blocked |
| Invoice state machine | 2 | Cannot edit after Sent, cannot pay Cancelled invoice |
| Notifications | 1 | Expense approved triggers notification to employee |
| Scheduler | 1 | Overdue check updates status and notifies |

### **8.3 Stretch tests (if attempting)**

| **Category** | **Min** | **Examples** |
| --- | --- | --- |
| PDF generation | 1 | Job creates PDF file |
| Recurring invoice | 1 | Scheduler creates new invoice from template |
| Reports | 1 | Revenue report totals match raw data |
| OCR | 1 | AI job returns structured receipt data |

### **8.4 Test quality rules**

- Test names describe behavior: `it('blocks expense over category max amount')`
- Clear Arrange / Act / Assert
- No circular assertions
- No unfailable assertions

---

## **9) Seeders**

Create seeders with realistic demo data:

- 1 organization with settings
- 3-4 departments with monthly budgets
- 8-10 users (mix of Admin, Manager, Employee, Accountant) across departments
- 5-6 expense categories (some with max_amount, some with requires_receipt)
- 4-5 tax rates (one default, one inactive)
- 30-50 expenses (mix of statuses, some with receipts)
- 5-8 clients
- 15-20 invoices (mix of statuses, with line items, some with payments, some overdue)
- Activity log entries

Must be runnable via `php artisan db:seed`.

---

## **10) Definition of Done**

### **Core (Must Complete)**

**Functional:**

- [ ]  User management with roles (Admin, Manager, Employee, Accountant)
- [ ]  Department CRUD with budgets
- [ ]  Expense Category CRUD with configurable rules (max_amount, requires_receipt)
- [ ]  Tax Rate CRUD with default and deactivation
- [ ]  Expense submission with receipt upload
- [ ]  Expense list with filters and URL persistence
- [ ]  Expense state machine enforced (Draft→Submitted→Approved/Rejected→Reimbursed)
- [ ]  All expense validation rules enforced with clear errors
- [ ]  Department budget check on expense submission
- [ ]  Expense approval workflow (manager approves/rejects own department only)
- [ ]  Rejection requires reason, visible on detail page
- [ ]  Invoice creation with dynamic line items
- [ ]  Real-time line item calculation (quantity * price * tax → totals)
- [ ]  Invoice auto-numbering (INV-YYYY-NNNN, sequential, no gaps)
- [ ]  Invoice state machine enforced (Draft→Sent→Paid/Overdue/Cancelled)
- [ ]  All monetary values stored as integers (cents/paise)
- [ ]  Payment recording with partial payment support
- [ ]  Auto-update invoice status based on payment totals
- [ ]  Overpayment blocked
- [ ]  Event-driven activity logging
- [ ]  Notification bell with unread count
- [ ]  Queued notifications for approval, rejection, payment, overdue
- [ ]  Scheduled overdue invoice check (daily)

**Technical:**

- [ ]  Gates and Policies for authorization (native Laravel, not Spatie)
- [ ]  5+ Form Request classes
- [ ]  3+ custom Rule classes with DB queries
- [ ]  State machine transition validation on both Expense and Invoice
- [ ]  4+ Events with Listeners
- [ ]  2+ Model Observers
- [ ]  3+ Eloquent scopes
- [ ]  3+ model accessors (including money formatting)
- [ ]  DB Facade for budget calculations and reports (with comments)
- [ ]  2+ cached data points with invalidation
- [ ]  3+ queued Jobs
- [ ]  1 scheduled command
- [ ]  All Livewire features from section 7.5
- [ ]  Dynamic line item form (add/remove rows, real-time calculation)
- [ ]  1+ Livewire Form Object
- [ ]  Polymorphic attachments
- [ ]  Integer money storage with formatted accessors

**Testing:**

- [ ]  20+ tests passing
- [ ]  All tests test real behavior
- [ ]  No real external calls in tests

**Quality:**

- [ ]  [ ] `declare(strict_types=1)` everywhere
- [ ]  Full type annotations
- [ ]  Commit messages with learning notes
- [ ]  README with setup, queue worker, scheduler instructions

### **Stretch (Extra Credit)**

- [ ]  PDF invoice generation via background job
- [ ]  Recurring invoices with scheduled auto-creation
- [ ]  Financial reports with cached DB Facade queries
- [ ]  Receipt OCR via Laravel AI SDK
- [ ]  Client portal with separate auth
- [ ]  Multi-currency with exchange rates

---

## **11) Demo Script**

### **Core Demo (3 minutes)**

1. Login as Admin → show departments, categories (one with max_amount, one with requires_receipt), tax rates
2. Login as Employee → submit expense → show validation: exceed category max (fail), missing receipt (fail), then valid submit
3. Login as Manager → see pending approvals → approve one (notification sent), reject one with reason
4. Show expense detail → approval history and activity log
5. Login as Accountant → create invoice with 3 line items at different tax rates → show real-time total calculation
6. Send invoice → invoice number generated → status changes to Sent
7. Record partial payment → status becomes Partially Paid → record remaining → status becomes Paid
8. Run `schedule:run` with an overdue invoice → status updates, accountant notified

### **Stretch Demo (2 minutes, if applicable)**

1. Send invoice → PDF generated in background → download PDF
2. Show recurring invoice → next generation date → run scheduler → new invoice created
3. Upload receipt photo → OCR extracts vendor/amount → form auto-fills
4. Financial report → expense by department, revenue by client