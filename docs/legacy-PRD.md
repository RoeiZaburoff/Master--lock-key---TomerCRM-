# Product Requirements Document — Solo-Operator CRM & FSM System
## Rev 2 — No-Composer, Plug-and-Play, Single-Operator

---

## 1. Product Overview

A self-hosted, single-operator Field Service Management and CRM platform. Built on PHP 8.3, MySQL, HTML5, CSS3, and Vanilla JavaScript. Zero dependency manager required. All third-party libraries are included as raw files and uploaded via FTP. Designed for immediate deployment on standard shared hosting (cPanel/Plesk).

---

## 2. Deployment Model

| Requirement | Specification |
|-------------|--------------|
| Install method | FTP upload — no terminal, no Composer, no CLI |
| Hosting | Shared hosting with PHP 8.x and MySQL 8.x |
| Third-party libs | Raw `.php` files placed in `/libs/` folder |
| PDF library | FPDF 1.86 (standalone, zero dependencies) |
| Autoloading | Custom PSR-4 style autoloader — single `autoload.php` file, no Composer |

---

## 3. User Roles

| Role | Description |
|------|-------------|
| Admin (Owner) | Single user. Full access to all modules. Session-authenticated. |

No registration flow. Admin account created via a one-time setup script or direct DB insert.

---

## 4. Modules & Feature Map

### 4.1 Authentication (Login)
| Field | Notes |
|-------|-------|
| Username | Text input |
| Password | Text input — verified against `password_hash` using `password_verify()` |

- PHP `$_SESSION` based authentication
- Every page checks session before rendering — redirects to login if unauthenticated
- Logout destroys session and redirects to login
- No "remember me" / no password reset (single operator — manual DB update if needed)

### 4.2 Dashboard
- Daily job count: scheduled, in-progress, completed
- Pending payment total: sum of `job_total - amount_paid` where `payment_status != 'paid'`
- Weekly net profit: sum of `job_financials.net_profit` for jobs completed in the current calendar week
- Upcoming jobs list: next 7 days ordered by `scheduled_date`
- Quick-action buttons: New Job, New Customer

### 4.3 Customer Management
| Field | Type | Notes |
|-------|------|-------|
| customer_id | INT PK | Auto-increment |
| full_name | VARCHAR(150) | Required |
| phone | VARCHAR(30) | Required |
| email | VARCHAR(150) | Optional |
| address_line1 | VARCHAR(255) | Required |
| address_line2 | VARCHAR(255) | Optional |
| city | VARCHAR(100) | Required |
| state | VARCHAR(50) | Required |
| zip | VARCHAR(20) | Required |
| notes | TEXT | Optional |
| created_at | TIMESTAMP | Auto |

- Search/filter by name, city, zip
- Customer history view: all linked jobs and invoices
- Meta-field support via `customer_meta` table

### 4.4 Job Management
| Field | Type | Notes |
|-------|------|-------|
| job_id | INT PK | Auto-increment |
| customer_id | INT FK | Links to customers |
| lead_source_id | INT FK | Nullable |
| contractor_id | INT FK | Nullable |
| job_type | VARCHAR(100) | Manual text entry — e.g. HVAC, Plumbing |
| description | TEXT | Scope of work |
| job_total | DECIMAL(10,2) | Manual price entry |
| scheduled_date | DATE | |
| scheduled_time | TIME | |
| status | ENUM | pending, in_progress, completed, cancelled |
| payment_status | ENUM | unpaid, partial, paid |
| amount_paid | DECIMAL(10,2) | |
| completed_at | TIMESTAMP | Nullable |
| created_at | TIMESTAMP | Auto |

- `job_type` and `job_total` are free-text/manual — no price list or service catalog
- Status progression: pending → in_progress → completed
- On completion: material log prompt triggered, profit snapshot calculated and saved

### 4.5 Lead Source Management
| Field | Type | Notes |
|-------|------|-------|
| lead_source_id | INT PK | |
| name | VARCHAR(100) | e.g. Angi, Yelp, Referral |
| fee_type | ENUM | percent, flat |
| fee_value | DECIMAL(10,4) | 0.1500 = 15% or dollar amount |
| is_active | TINYINT(1) | |

- Fee calculated at job completion
- `percent`: `lead_fee = job_total * fee_value`
- `flat`: `lead_fee = fee_value`
- No lead source assigned: `lead_fee = 0.00`

### 4.6 Contractor Management
| Field | Type | Notes |
|-------|------|-------|
| contractor_id | INT PK | |
| full_name | VARCHAR(150) | |
| phone | VARCHAR(30) | |
| email | VARCHAR(150) | Optional |
| split_type | ENUM | percent, flat |
| split_value | DECIMAL(10,4) | 0.4000 = 40% or dollar amount |
| is_active | TINYINT(1) | |

- Share calculated at job completion
- `percent`: `contractor_share = job_total * split_value`
- `flat`: `contractor_share = split_value`
- No contractor assigned: `contractor_share = 0.00`

### 4.7 Material / Inventory Tracking
**Materials catalog:**
| Field | Type | Notes |
|-------|------|-------|
| material_id | INT PK | |
| name | VARCHAR(150) | |
| unit | VARCHAR(30) | each, ft, lb |
| unit_cost | DECIMAL(10,4) | Owner's purchase cost |
| stock_qty | DECIMAL(10,2) | Current inventory |
| is_active | TINYINT(1) | |

**Job material log (`job_materials`):**
| Field | Type | Notes |
|-------|------|-------|
| id | INT PK | |
| job_id | INT FK | |
| material_id | INT FK | Nullable if contractor-owned |
| ownership | ENUM | company, contractor |
| description | VARCHAR(255) | Free-text for contractor-owned items |
| qty | DECIMAL(10,2) | |
| unit_cost | DECIMAL(10,4) | Snapshot at time of use |
| line_total | DECIMAL(10,2) | qty * unit_cost |

- `company`: deducted from owner's profit; inventory decremented automatically
- `contractor`: NOT deducted from owner's profit; no inventory impact

### 4.8 Invoice Management
| Field | Type | Notes |
|-------|------|-------|
| invoice_id | INT PK | |
| job_id | INT FK | One invoice per job |
| invoice_number | VARCHAR(30) | Auto-generated: INV-YYYYMMDD-XXXX |
| issued_date | DATE | |
| due_date | DATE | |
| subtotal | DECIMAL(10,2) | |
| tax_rate | DECIMAL(6,4) | e.g. 0.0875 |
| tax_amount | DECIMAL(10,2) | |
| total | DECIMAL(10,2) | |
| notes | TEXT | |

- PDF generated via FPDF (standalone library in `/libs/fpdf/`)
- Generated PDFs saved to `/storage/pdf/` — not served directly from web root

### 4.9 Financial Reporting
- Date range selector: weekly / monthly / custom
- Filter by: Lead Source, Contractor, Job Type
- Columns: Job ID, Customer, Date, Total, Lead Fee, Material Cost, Contractor Share, Net Profit
- Summary totals row
- Export to PDF via FPDF

---

## 5. Data Flows

### 5.1 Job Lifecycle
```
New Job
  → Customer (required)
  → Lead Source (optional)
  → Contractor (optional)
  → Job Type: manual text
  → Job Total: manual price
  → Status: pending

In Progress
  → Status: in_progress

Completed
  → Status: completed
  → Material log saved
      company materials  → inventory decremented, cost captured
      contractor materials → recorded, NOT deducted from owner
  → ProfitCalculator runs → snapshot written to job_financials

Invoice
  → Generated from job_total
  → PDF via FPDF

Payment
  → amount_paid updated
  → payment_status updated
```

### 5.2 Authentication Flow
```
Any Request
  → session_start()
  → $_SESSION['user_id'] set?
      NO  → redirect to /login
      YES → continue to requested page

POST /login
  → fetch users row by username
  → password_verify(input, hash)
      FAIL → error message, stay on login
      PASS → $_SESSION['user_id'] = user_id, redirect to dashboard

GET /logout
  → session_destroy()
  → redirect to /login
```

---

## 6. Non-Functional Requirements

| Requirement | Specification |
|-------------|--------------|
| Language | PHP 8.3, `declare(strict_types=1)` |
| OOP | Namespaced classes, custom `autoload.php` — no Composer |
| Database | MySQL 8.x, PDO prepared statements only |
| Frontend | HTML5, CSS3, Vanilla JS — no frameworks, no npm |
| PDF | FPDF 1.86 — raw files in `/libs/fpdf/` |
| Auth | Session-based, `password_hash` / `password_verify` |
| Security | CSRF tokens on all POST forms, `htmlspecialchars()` on all output, parameterized queries |
| Hosting | Shared hosting — Apache + PHP, FTP upload only |
| Extensibility | Module manifest in `config/modules.php`; meta-tables for custom fields |
