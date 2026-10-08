<?php
declare(strict_types=1);

namespace App\Modules\Settings;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\QueryBuilder;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

class SettingsController extends \App\Core\BaseController
{
    private CompanyService $companyService;
    private UserService    $userService;
    private QueryBuilder   $db;

    public function __construct()
    {
        $this->companyService = new CompanyService();
        $this->userService    = new UserService();
        $this->db             = new QueryBuilder();
        $this->adminActions   = ['*'];
    }

    public function index(Request $request, array $params = []): void
    {
        $ccFeeRow = $this->db->table('system_meta')->where('meta_key', 'cc_fee_percentage')->first();

        View::render('settings/index', [
            'companies'          => $this->companyService->getAll(),
            'users'              => $this->userService->getAll(),
            'current_user_id'    => (int) Auth::userId(),
            'migration_needed'   => false,
            'tab'                => $request->get('tab', 'companies'),
            'pw_errors'          => [],
            'user_errors'        => [],
            'cc_fee_percentage'  => $ccFeeRow !== null ? (float) $ccFeeRow['meta_value'] : 0.0,
        ]);
    }

    public function saveGeneral(Request $request, array $params = []): void
    {
        $pct = round(max(0.0, (float) $request->post('cc_fee_percentage', '0')), 4);

        $this->db->rawExecute(
            "INSERT INTO system_meta (meta_key, meta_value) VALUES ('cc_fee_percentage', :v)
             ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value)",
            [':v' => (string) $pct]
        );

        $this->redirectWithMessage('/settings?tab=general', 'General settings saved.');
    }

    public function create(Request $request, array $params = []): void
    {
        View::render('settings/companies/create', ['errors' => [], 'old' => []]);
    }

    public function store(Request $request, array $params = []): void
    {
        $result = $this->companyService->create($request->all(), $_FILES);
        if ($result['success']) {
            $this->redirectWithMessage('/settings', 'Company created.');
        }
        View::render('settings/companies/create', [
            'errors' => $result['errors'],
            'old'    => $request->all(),
        ]);
    }

    public function edit(Request $request, array $params = []): void
    {
        $company = $this->companyService->getById((int) $params['id']);
        if ($company === null) {
            Response::abort(404, 'Company not found.');
        }
        View::render('settings/companies/edit', ['company' => $company, 'errors' => []]);
    }

    public function update(Request $request, array $params = []): void
    {
        $id     = (int) $params['id'];
        $result = $this->companyService->update($id, $request->all(), $_FILES);
        if ($result['success']) {
            $this->redirectWithMessage('/settings', 'Company updated.');
        }
        $company = array_merge($this->companyService->getById($id) ?? [], $request->all());
        View::render('settings/companies/edit', ['company' => $company, 'errors' => $result['errors']]);
    }

    public function destroy(Request $request, array $params = []): void
    {
        $this->companyService->delete((int) $params['id']);
        $this->redirectWithMessage('/settings', 'Company deleted.');
    }

    public function userStore(Request $request, array $params = []): void
    {
        $result = $this->userService->create($request->all());
        if ($result['success']) {
            $this->redirectWithMessage('/settings?tab=users', 'User created.');
        }
        View::render('settings/index', [
            'companies'       => $this->companyService->getAll(),
            'users'           => $this->userService->getAll(),
            'current_user_id' => (int) Auth::userId(),
            'migration_needed'=> false,
            'tab'             => 'users',
            'pw_errors'       => [],
            'user_errors'     => $result['errors'],
        ]);
    }

    public function userDestroy(Request $request, array $params = []): void
    {
        $result = $this->userService->delete((int) $params['id']);
        if (!$result['success']) {
            $this->redirectWithMessage('/settings?tab=users', $result['error'], 'error');
        }
        $this->redirectWithMessage('/settings?tab=users', 'User deleted.');
    }

    public function updatePassword(Request $request, array $params = []): void
    {
        $result = $this->userService->changePassword($request->all());
        if ($result['success']) {
            $this->redirectWithMessage('/settings?tab=users', 'Password updated.');
        }
        View::render('settings/index', [
            'companies'       => $this->companyService->getAll(),
            'users'           => $this->userService->getAll(),
            'current_user_id' => (int) Auth::userId(),
            'migration_needed'=> false,
            'tab'             => 'users',
            'pw_errors'       => $result['errors'],
            'user_errors'     => [],
        ]);
    }

    public function serveLogo(Request $request, array $params = []): void
    {
        $file = basename($params['file'] ?? '');
        $path = STORAGE_PATH . '/logos/' . $file;
        if ($file === '' || !file_exists($path)) {
            Response::abort(404, 'Not found.');
        }
        $mime = mime_content_type($path);
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: public, max-age=31536000');
        readfile($path);
        exit;
    }

    public function downloadBackup(Request $request, array $params = []): void
    {
        $dbConfig = require BASE_PATH . '/config/database.php';

        $tmpSql = tempnam(sys_get_temp_dir(), 'crm_sql_');
        $tmpZip = tempnam(sys_get_temp_dir(), 'crm_zip_');

        try {
            file_put_contents($tmpSql, $this->generateSqlDump($dbConfig));

            $zip = new \ZipArchive();
            if ($zip->open($tmpZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                Response::abort(500, 'Failed to create backup archive.');
            }

            $basePath  = realpath(STORAGE_PATH);
            $realTmpSql = realpath($tmpSql);
            $realTmpZip = realpath($tmpZip);

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($basePath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $fileInfo) {
                $filePath = $fileInfo->getRealPath();
                if ($filePath === $realTmpSql || $filePath === $realTmpZip) {
                    continue;
                }
                $relative = 'storage/' . ltrim(substr($filePath, strlen($basePath)), DIRECTORY_SEPARATOR);
                $fileInfo->isDir()
                    ? $zip->addEmptyDir($relative)
                    : $zip->addFile($filePath, $relative);
            }

            $zip->addFile($realTmpSql, 'database_backup/database.sql');
            $zip->close();

            $filename = 'crm_backup_' . date('Y-m-d_His') . '.zip';
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($tmpZip));
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');

            readfile($tmpZip);
        } finally {
            if (file_exists($tmpSql)) {
                unlink($tmpSql);
            }
            if (file_exists($tmpZip)) {
                unlink($tmpZip);
            }
        }

        exit;
    }

    private function generateSqlDump(array $cfg): string
    {
        $pdo = new \PDO(
            "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['dbname']};charset={$cfg['charset']}",
            $cfg['username'],
            $cfg['password'],
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );

        $out  = "-- CRM Database Backup\n";
        $out .= '-- Generated: ' . date('Y-m-d H:i:s') . "\n\n";
        $out .= "SET FOREIGN_KEY_CHECKS=0;\n";
        $out .= "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n";

        $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $createRow = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_NUM);
            $out .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $out .= $createRow[1] . ";\n\n";

            $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(\PDO::FETCH_ASSOC);
            if (empty($rows)) {
                continue;
            }

            $colList = implode(', ', array_map(
                static fn(string $c): string => "`{$c}`",
                array_keys($rows[0])
            ));

            $valueLines = [];
            foreach ($rows as $row) {
                $vals = array_map(
                    static fn($v): string => $v === null ? 'NULL' : $pdo->quote((string) $v),
                    array_values($row)
                );
                $valueLines[] = '(' . implode(', ', $vals) . ')';
            }

            $out .= "INSERT INTO `{$table}` ({$colList}) VALUES\n";
            $out .= implode(",\n", $valueLines) . ";\n\n";
        }

        $out .= "SET FOREIGN_KEY_CHECKS=1;\n";

        return $out;
    }
}
