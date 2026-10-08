# System Architecture — Solo-Operator CRM & FSM
## Rev 2 — No-Composer, Plug-and-Play, Flat Structure

---

## 1. Folder Structure

```
/CRM  (web root or subfolder)
│
├── index.php                       # Front controller — all requests enter here
├── autoload.php                    # Custom PSR-4 style class autoloader (no Composer)
├── .htaccess                       # Rewrite all requests to index.php
│
├── config/
│   ├── app.php                     # App name, timezone, debug mode
│   ├── database.php                # DB host, name, user, password
│   └── modules.php                 # Registered module list
│
├── app/
│   ├── Core/
│   │   ├── Router.php
│   │   ├── Request.php
│   │   ├── Response.php
│   │   ├── Session.php
│   │   ├── Auth.php
│   │   ├── Database.php
│   │   ├── QueryBuilder.php
│   │   ├── View.php
│   │   ├── Validator.php
│   │   └── CSRF.php
│   │
│   ├── Modules/
│   │   ├── Auth/
│   │   │   ├── LoginController.php
│   │   │   └── AuthService.php
│   │   ├── Dashboard/
│   │   │   ├── DashboardController.php
│   │   │   └── DashboardService.php
│   │   ├── Customers/
│   │   │   ├── CustomerController.php
│   │   │   ├── CustomerService.php
│   │   │   └── CustomerRepository.php
│   │   ├── Jobs/
│   │   │   ├── JobController.php
│   │   │   ├── JobService.php
│   │   │   └── JobRepository.php
│   │   ├── LeadSources/
│   │   │   ├── LeadSourceController.php
│   │   │   ├── LeadSourceService.php
│   │   │   └── LeadSourceRepository.php
│   │   ├── Contractors/
│   │   │   ├── ContractorController.php
│   │   │   ├── ContractorService.php
│   │   │   └── ContractorRepository.php
│   │   ├── Materials/
│   │   │   ├── MaterialController.php
│   │   │   ├── MaterialService.php
│   │   │   └── MaterialRepository.php
│   │   ├── Invoices/
│   │   │   ├── InvoiceController.php
│   │   │   ├── InvoiceService.php
│   │   │   ├── InvoiceRepository.php
│   │   │   └── InvoicePdf.php
│   │   └── Reports/
│   │       ├── ReportController.php
│   │       ├── ReportService.php
│   │       └── ReportPdf.php
│   │
│   ├── Models/
│   │   ├── User.php
│   │   ├── Customer.php
│   │   ├── Job.php
│   │   ├── JobFinancial.php
│   │   ├── JobMaterial.php
│   │   ├── LeadSource.php
│   │   ├── Contractor.php
│   │   ├── Material.php
│   │   └── Invoice.php
│   │
│   ├── Services/
│   │   └── ProfitCalculator.php
│   │
│   └── Contracts/
│       ├── RepositoryInterface.php
│       └── PdfInterface.php
│
├── views/
│   ├── layouts/
│   │   ├── main.php                # Authenticated layout (nav + content slot)
│   │   └── auth.php                # Bare layout for login page
│   ├── auth/
│   │   └── login.php
│   ├── dashboard/
│   │   └── index.php
│   ├── customers/
│   │   ├── index.php
│   │   ├── create.php
│   │   ├── edit.php
│   │   └── show.php
│   ├── jobs/
│   │   ├── index.php
│   │   ├── create.php
│   │   ├── edit.php
│   │   ├── show.php
│   │   └── complete.php            # Material log entry on job completion
│   ├── lead_sources/
│   │   ├── index.php
│   │   ├── create.php
│   │   └── edit.php
│   ├── contractors/
│   │   ├── index.php
│   │   ├── create.php
│   │   └── edit.php
│   ├── materials/
│   │   ├── index.php
│   │   ├── create.php
│   │   └── edit.php
│   ├── invoices/
│   │   ├── index.php
│   │   ├── show.php
│   │   └── create.php
│   └── reports/
│       └── index.php
│
├── libs/
│   └── fpdf/
│       ├── fpdf.php                # FPDF core — manually uploaded
│       └── font/                   # FPDF font files
│
├── storage/
│   └── pdf/                        # Generated PDF output — not publicly browsable
│
└── assets/
    ├── css/
    │   └── app.css
    └── js/
        └── app.js
```

---

## 2. autoload.php

Single file placed at the project root. Loaded once in `index.php`.

```
Namespace prefix : App\
Base directory   : app/

Examples:
  App\Core\Database          → app/Core/Database.php
  App\Modules\Jobs\JobService → app/Modules/Jobs/JobService.php
  App\Services\ProfitCalculator → app/Services/ProfitCalculator.php
  App\Models\Job             → app/Models/Job.php
```

FPDF is not namespaced — it is included directly with `require_once` inside `InvoicePdf.php` and `ReportPdf.php`:
```php
require_once __DIR__ . '/../../../libs/fpdf/fpdf.php';
```

---

## 3. Class Hierarchy

### 3.1 Core Layer

```
index.php
  → require_once autoload.php
  → require_once config/app.php
  → App\Core\Session::start()
  → App\Core\Auth::check()   [redirects to /login if no session]
  → App\Core\Router::dispatch(Request)
      → [Module]Controller::[action](Request)
          → [Module]Service::[method](data)
              → [Module]Repository::[query]()
              → App\Services\ProfitCalculator (Jobs module, on completion)
          → App\Core\View::render(template, data)
```

### 3.2 Auth Module

```
LoginController
  GET  /login  → View::render('auth/login')
  POST /login  → AuthService::attempt(username, password)
                   → fetch users row by username
                   → password_verify(input, stored_hash)
                       FAIL → back to login with error
                       PASS → Session::set('user_id', id)
                            → redirect to /dashboard
  GET  /logout → Session::destroy() → redirect to /login
```

### 3.3 Module Pattern

```
[Module]Controller
  └── [Module]Service
        ├── [Module]Repository  (DB access via QueryBuilder)
        └── ProfitCalculator    (Jobs only, on completion)
```

### 3.4 ProfitCalculator

```
ProfitCalculator
  ├── calculate(array $job, array $leadSource, array|null $contractor, array $materials): array
  ├── resolveLeadFee(string $feeType, float $feeValue, float $jobTotal): float
  ├── resolveContractorShare(string $splitType, float $splitValue, float $jobTotal): float
  └── resolveCompanyMaterialCost(array $materials): float
```

Returns associative array:
```
[
  'job_total'        => float,
  'material_cost'    => float,
  'lead_fee'         => float,
  'contractor_share' => float,
  'gross_profit'     => float,
  'net_profit'       => float,
]
```

### 3.5 RepositoryInterface

```php
interface RepositoryInterface {
    public function findById(int $id): array|null;
    public function findAll(array $filters = []): array;
    public function create(array $data): int;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
}
```

---

## 4. Route Map

| Method | URI | Controller | Action |
|--------|-----|------------|--------|
| GET | /login | LoginController | showLogin |
| POST | /login | LoginController | handleLogin |
| GET | /logout | LoginController | logout |
| GET | / | DashboardController | index |
| GET | /customers | CustomerController | index |
| GET | /customers/create | CustomerController | create |
| POST | /customers | CustomerController | store |
| GET | /customers/{id} | CustomerController | show |
| GET | /customers/{id}/edit | CustomerController | edit |
| POST | /customers/{id} | CustomerController | update |
| GET | /jobs | JobController | index |
| GET | /jobs/create | JobController | create |
| POST | /jobs | JobController | store |
| GET | /jobs/{id} | JobController | show |
| GET | /jobs/{id}/edit | JobController | edit |
| POST | /jobs/{id} | JobController | update |
| GET | /jobs/{id}/complete | JobController | showComplete |
| POST | /jobs/{id}/complete | JobController | handleComplete |
| GET | /lead-sources | LeadSourceController | index |
| POST | /lead-sources | LeadSourceController | store |
| POST | /lead-sources/{id} | LeadSourceController | update |
| GET | /contractors | ContractorController | index |
| POST | /contractors | ContractorController | store |
| POST | /contractors/{id} | ContractorController | update |
| GET | /materials | MaterialController | index |
| POST | /materials | MaterialController | store |
| POST | /materials/{id} | MaterialController | update |
| GET | /invoices | InvoiceController | index |
| GET | /invoices/{id} | InvoiceController | show |
| POST | /invoices | InvoiceController | store |
| GET | /invoices/{id}/pdf | InvoiceController | pdf |
| GET | /reports | ReportController | index |
| POST | /reports | ReportController | generate |
| GET | /reports/pdf | ReportController | pdf |

All POST routes are protected by CSRF token validation.
All routes except `/login` are protected by `Auth::check()`.

---

## 5. Security Controls

| Layer | Control |
|-------|---------|
| Authentication | `$_SESSION['user_id']` checked on every request |
| Password storage | `password_hash($password, PASSWORD_BCRYPT)` |
| Password verification | `password_verify($input, $hash)` |
| CSRF | Token generated per session, validated on every POST |
| SQL | PDO prepared statements exclusively — no string interpolation in queries |
| XSS | `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')` on all rendered output |
| PDF files | Stored in `/storage/pdf/` — blocked from direct browser access via `.htaccess` |
| Session fixation | `session_regenerate_id(true)` on successful login |
