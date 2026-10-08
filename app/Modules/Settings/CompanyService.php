<?php
declare(strict_types=1);

namespace App\Modules\Settings;

use App\Core\Validator;

class CompanyService
{
    private CompanyRepository $repo;

    public function __construct()
    {
        $this->repo = new CompanyRepository();
    }

    public function getAll(): array
    {
        return $this->repo->findAll();
    }

    public function getAllActive(): array
    {
        return $this->repo->findAllActive();
    }

    public function getById(int $id): array|null
    {
        return $this->repo->findById($id);
    }

    public function create(array $data, array $files = []): array
    {
        $v = new Validator();
        if (!$v->validate($data, ['name' => 'required|max:150'])) {
            return ['success' => false, 'errors' => $v->errors()];
        }

        $logoPath = $this->handleLogoUpload($files);
        $row      = [
            'name'      => trim($data['name']),
            'phone'     => trim($data['phone'] ?? ''),
            'address'   => trim($data['address'] ?? ''),
            'logo_path' => $logoPath,
            'is_active' => 1,
        ];
        $row['email'] = trim($data['email'] ?? '');

        $id = $this->repo->create($row);
        return ['success' => true, 'id' => $id];
    }

    public function update(int $id, array $data, array $files = []): array
    {
        $v = new Validator();
        if (!$v->validate($data, ['name' => 'required|max:150'])) {
            return ['success' => false, 'errors' => $v->errors()];
        }

        $existing = $this->repo->findById($id);
        $logoPath = $existing['logo_path'] ?? null;

        if (!empty($data['delete_logo']) && $logoPath !== null) {
            $this->removeLogoFile($logoPath);
            $logoPath = null;
        }

        $newLogo = $this->handleLogoUpload($files);
        if ($newLogo !== null) {
            if ($logoPath !== null) {
                $this->removeLogoFile($logoPath);
            }
            $logoPath = $newLogo;
        }

        $row = [
            'name'      => trim($data['name']),
            'phone'     => trim($data['phone'] ?? ''),
            'address'   => trim($data['address'] ?? ''),
            'logo_path' => $logoPath,
            'is_active' => isset($data['is_active']) ? 1 : 0,
        ];
        $row['email'] = trim($data['email'] ?? '');

        $this->repo->update($id, $row);
        return ['success' => true];
    }

    public function delete(int $id): bool
    {
        $company = $this->repo->findById($id);
        if ($company && !empty($company['logo_path'])) {
            $this->removeLogoFile($company['logo_path']);
        }
        return $this->repo->delete($id);
    }

    private function removeLogoFile(string $filename): void
    {
        $path = STORAGE_PATH . '/logos/' . $filename;
        if (file_exists($path)) {
            unlink($path);
        }
    }

    private function handleLogoUpload(array $files): ?string
    {
        if (empty($files['logo']) || $files['logo']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $mime    = mime_content_type($files['logo']['tmp_name']);
        if (!in_array($mime, $allowed, true)) {
            return null;
        }
        $mimeToExt = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'png',
        ];
        $ext  = $mimeToExt[$mime];
        $dest = STORAGE_PATH . '/logos/';
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }
        $filename = 'company_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $fullDest = $dest . $filename;
        if ($mime === 'image/webp') {
            $img = @imagecreatefromwebp($files['logo']['tmp_name']);
            if ($img === false) {
                return null;
            }
            imagesavealpha($img, true);
            $result = imagepng($img, $fullDest, 9);
            imagedestroy($img);
            return $result ? $filename : null;
        }
        return move_uploaded_file($files['logo']['tmp_name'], $fullDest) ? $filename : null;
    }
}
