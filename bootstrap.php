<?php
declare(strict_types=1);

define('VIEW_PATH', BASE_PATH . '/views');
define('STORAGE_PATH', BASE_PATH . '/storage');

require_once BASE_PATH . '/autoload.php';
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/helpers.php';

use App\Core\Router;
use App\Core\Request;
use App\Core\Session;

set_exception_handler(function (\Throwable $e): void {
    error_log('CRM exception: ' . get_class($e));
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'The request could not be completed. Please contact the administrator.';
});
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cache-Control: no-store');
if (\App\Core\RuntimeConfig::enabled('REQUIRE_HTTPS', true) && PHP_SAPI !== 'cli' && (($_SERVER['HTTPS'] ?? '') === '' || $_SERVER['HTTPS'] === 'off')) {
    http_response_code(400);
    exit('HTTPS is required.');
}

Session::start();

// No provider API is called here. Integrations are independently disabled by default.
$path = (new Request())->uri();
if ($path === '/api/v1/website-inquiries' || $path === '/api/v1/voice/retell') {
    (new \App\Modules\Integrations\WebhookController())->handle(new Request());
}
if ($path === '/health') {
    try {
        if (!\App\Core\SchemaState::current()) { throw new \RuntimeException('Schema is not current.'); }
        \App\Core\Response::json(['status' => 'ok']);
    } catch (\Throwable $e) { \App\Core\Response::json(['status' => 'unavailable'], 503); }
}


$router = new Router();

$router->get('/login',  [\App\Modules\Auth\LoginController::class, 'showLogin']);
$router->post('/login', [\App\Modules\Auth\LoginController::class, 'handleLogin']);
$router->get('/logout', [\App\Modules\Auth\LoginController::class, 'logout']);

$router->get('/', [\App\Modules\Dashboard\DashboardController::class, 'index']);
$router->get('/dashboard/schedule', [\App\Modules\Dashboard\DashboardController::class, 'schedule']);

$router->get('/customers',             [\App\Modules\Customers\CustomerController::class, 'index']);
$router->get('/customers/create',           [\App\Modules\Customers\CustomerController::class, 'create']);
$router->get('/customers/check-duplicate', [\App\Modules\Customers\CustomerController::class, 'checkDuplicate']);
$router->post('/customers',            [\App\Modules\Customers\CustomerController::class, 'store']);
$router->post('/customers/quick',      [\App\Modules\Customers\CustomerController::class, 'quickStore']);
$router->get('/customers/{id}/edit',    [\App\Modules\Customers\CustomerController::class, 'edit']);
$router->post('/customers/{id}',        [\App\Modules\Customers\CustomerController::class, 'update']);
$router->post('/customers/{id}/delete', [\App\Modules\Customers\CustomerController::class, 'destroy']);
$router->get('/customers/{id}',         [\App\Modules\Customers\CustomerController::class, 'show']);

$router->get('/leads',                     [\App\Modules\Leads\LeadController::class, 'index']);
$router->get('/leads/create',              [\App\Modules\Leads\LeadController::class, 'create']);
$router->post('/leads',                    [\App\Modules\Leads\LeadController::class, 'store']);
$router->get('/leads/{id}/edit',           [\App\Modules\Leads\LeadController::class, 'edit']);
$router->post('/leads/{id}/update',        [\App\Modules\Leads\LeadController::class, 'update']);
$router->post('/leads/{id}/delete',        [\App\Modules\Leads\LeadController::class, 'destroy']);
$router->post('/leads/{id}/duplicate',      [\App\Modules\Leads\LeadController::class, 'duplicate']);
$router->get('/leads/{id}/complete',       [\App\Modules\Leads\LeadController::class, 'showComplete']);
$router->post('/leads/{id}/complete',      [\App\Modules\Leads\LeadController::class, 'handleComplete']);
$router->get('/leads/{id}/materials',      [\App\Modules\Leads\LeadController::class, 'showEditMaterials']);
$router->post('/leads/{id}/materials',     [\App\Modules\Leads\LeadController::class, 'handleEditMaterials']);
$router->post('/leads/{id}/convert',       [\App\Modules\Leads\LeadController::class, 'convertToJob']);
$router->get('/leads/{id}/estimate-pdf',   [\App\Modules\Leads\LeadController::class, 'estimatePdf']);
$router->get('/leads/{id}',                [\App\Modules\Leads\LeadController::class, 'show']);

$router->get('/calendar', [\App\Modules\Calendar\CalendarController::class, 'index']);

$router->get('/lead-sources',           [\App\Modules\LeadSources\LeadSourceController::class, 'index']);
$router->get('/lead-sources/create',    [\App\Modules\LeadSources\LeadSourceController::class, 'create']);
$router->post('/lead-sources',          [\App\Modules\LeadSources\LeadSourceController::class, 'store']);
$router->get('/lead-sources/{id}/edit',    [\App\Modules\LeadSources\LeadSourceController::class, 'edit']);
$router->post('/lead-sources/{id}',        [\App\Modules\LeadSources\LeadSourceController::class, 'update']);
$router->post('/lead-sources/{id}/delete', [\App\Modules\LeadSources\LeadSourceController::class, 'destroy']);

$router->get('/contractors',           [\App\Modules\Contractors\ContractorController::class, 'index']);
$router->get('/contractors/create',    [\App\Modules\Contractors\ContractorController::class, 'create']);
$router->post('/contractors',          [\App\Modules\Contractors\ContractorController::class, 'store']);
$router->get('/contractors/{id}/edit',    [\App\Modules\Contractors\ContractorController::class, 'edit']);
$router->post('/contractors/{id}',        [\App\Modules\Contractors\ContractorController::class, 'update']);
$router->post('/contractors/{id}/delete', [\App\Modules\Contractors\ContractorController::class, 'destroy']);

$router->get('/inventory',                                    [\App\Modules\Inventory\InventoryController::class, 'index']);
$router->get('/inventory/create',                             [\App\Modules\Inventory\InventoryController::class, 'create']);
$router->get('/inventory/manufacturers',                       [\App\Modules\Inventory\InventoryController::class, 'manufacturerIndex']);
$router->post('/inventory/manufacturers',                      [\App\Modules\Inventory\InventoryController::class, 'manufacturerStore']);
$router->get('/inventory/manufacturers/{id}/edit',             [\App\Modules\Inventory\InventoryController::class, 'manufacturerEdit']);
$router->post('/inventory/manufacturers/{id}/update',          [\App\Modules\Inventory\InventoryController::class, 'manufacturerUpdate']);
$router->post('/inventory/manufacturers/{id}/delete',          [\App\Modules\Inventory\InventoryController::class, 'manufacturerDestroy']);
$router->get('/inventory/categories',                          [\App\Modules\Inventory\InventoryController::class, 'categoryIndex']);
$router->post('/inventory/categories',                         [\App\Modules\Inventory\InventoryController::class, 'categoryStore']);
$router->get('/inventory/categories/{id}/edit',                [\App\Modules\Inventory\InventoryController::class, 'categoryEdit']);
$router->post('/inventory/categories/{id}/update',             [\App\Modules\Inventory\InventoryController::class, 'categoryUpdate']);
$router->post('/inventory/categories/{id}/delete',             [\App\Modules\Inventory\InventoryController::class, 'categoryDestroy']);
$router->get('/inventory/locations',                           [\App\Modules\Inventory\LocationController::class, 'index']);
$router->post('/inventory/locations',                          [\App\Modules\Inventory\LocationController::class, 'store']);
$router->get('/inventory/locations/{id}/edit',                 [\App\Modules\Inventory\LocationController::class, 'edit']);
$router->post('/inventory/locations/{id}/update',              [\App\Modules\Inventory\LocationController::class, 'update']);
$router->post('/inventory/locations/{id}/delete',              [\App\Modules\Inventory\LocationController::class, 'destroy']);
$router->post('/inventory',                                   [\App\Modules\Inventory\InventoryController::class, 'store']);
$router->get('/inventory/{id}/edit',                          [\App\Modules\Inventory\InventoryController::class, 'edit']);
$router->post('/inventory/{id}',                              [\App\Modules\Inventory\InventoryController::class, 'update']);
$router->post('/inventory/{id}/delete',                       [\App\Modules\Inventory\InventoryController::class, 'destroy']);
$router->post('/inventory/transfer/bulk',                      [\App\Modules\Inventory\InventoryController::class, 'transferBulk']);
$router->post('/inventory/{id}/transfer',                     [\App\Modules\Inventory\InventoryController::class, 'transfer']);
$router->post('/inventory/{id}/adjust',                       [\App\Modules\Inventory\InventoryController::class, 'adjustStock']);
$router->get('/storage/inventory/{file}',                     [\App\Modules\Inventory\InventoryController::class, 'serveImage']);

$router->get('/invoices',          [\App\Modules\Invoices\InvoiceController::class, 'index']);
$router->get('/invoices/create',   [\App\Modules\Invoices\InvoiceController::class, 'create']);
$router->post('/invoices',         [\App\Modules\Invoices\InvoiceController::class, 'store']);
$router->get('/invoices/{id}/pdf',    [\App\Modules\Invoices\InvoiceController::class, 'pdf']);
$router->get('/invoices/{id}/edit',   [\App\Modules\Invoices\InvoiceController::class, 'edit']);
$router->post('/invoices/{id}/update',[\App\Modules\Invoices\InvoiceController::class, 'update']);
$router->post('/invoices/{id}/delete', [\App\Modules\Invoices\InvoiceController::class, 'destroy']);
$router->get('/invoices/{id}',         [\App\Modules\Invoices\InvoiceController::class, 'show']);

$router->get('/reports',     [\App\Modules\Reports\ReportController::class, 'index']);
$router->post('/reports',    [\App\Modules\Reports\ReportController::class, 'generate']);
$router->get('/reports/pdf', [\App\Modules\Reports\ReportController::class, 'pdf']);

$router->get('/settings',                              [\App\Modules\Settings\SettingsController::class, 'index']);
$router->get('/settings/companies/create',             [\App\Modules\Settings\SettingsController::class, 'create']);
$router->post('/settings/companies',                   [\App\Modules\Settings\SettingsController::class, 'store']);
$router->get('/settings/companies/{id}/edit',          [\App\Modules\Settings\SettingsController::class, 'edit']);
$router->post('/settings/companies/{id}/update',       [\App\Modules\Settings\SettingsController::class, 'update']);
$router->post('/settings/companies/{id}/delete',       [\App\Modules\Settings\SettingsController::class, 'destroy']);
$router->post('/settings/users',                       [\App\Modules\Settings\SettingsController::class, 'userStore']);
$router->post('/settings/users/{id}/delete',           [\App\Modules\Settings\SettingsController::class, 'userDestroy']);
$router->post('/settings/password',                    [\App\Modules\Settings\SettingsController::class, 'updatePassword']);
$router->post('/settings/general',                     [\App\Modules\Settings\SettingsController::class, 'saveGeneral']);
$router->get('/settings/backup/download',              [\App\Modules\Settings\SettingsController::class, 'downloadBackup']);


$router->get('/storage/logos/{file}', [\App\Modules\Settings\SettingsController::class, 'serveLogo']);

$router->get('/intake', [\App\Modules\Integrations\IntakeController::class, 'index']);
$router->get('/intake/{id}', [\App\Modules\Integrations\IntakeController::class, 'show']);
$router->post('/intake/{id}/promote', [\App\Modules\Integrations\IntakeController::class, 'promote']);
$router->dispatch(new Request());
