<?php
declare(strict_types=1);

namespace App\Modules\Settings;

use App\Core\Auth;
use App\Core\QueryBuilder;

class UserService
{
    private QueryBuilder $db;

    public function __construct()
    {
        $this->db = new QueryBuilder();
    }

    public function getAll(): array
    {
        return $this->db->table('users')->orderBy('username')->get();
    }

    public function create(array $data): array
    {
        $username = trim($data['new_username'] ?? '');
        $password = $data['new_password'] ?? '';
        $confirm  = $data['new_password_confirm'] ?? '';
        $role     = in_array($data['new_role'] ?? '', ['admin', 'user'], true)
            ? $data['new_role']
            : 'user';

        $errors = [];
        if ($username === '') {
            $errors['new_username'] = 'Username is required.';
        } elseif ($this->db->table('users')->where('username', $username)->first() !== null) {
            $errors['new_username'] = 'Username already exists.';
        }
        if (strlen($password) < 12) {
            $errors['new_password'] = 'Password must be at least 12 characters.';
        }
        if ($password !== $confirm) {
            $errors['new_password_confirm'] = 'Passwords do not match.';
        }
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $row = [
            'username'      => $username,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ];
        $row['role'] = $role;
        $this->db->table('users')->insert($row);
        return ['success' => true];
    }

    public function delete(int $id): array
    {
        if ($id === (int) Auth::userId()) {
            return ['success' => false, 'error' => 'You cannot delete your own account.'];
        }
        $count = $this->db->rawFirst("SELECT COUNT(*) AS cnt FROM users");
        if ((int) ($count['cnt'] ?? 0) <= 1) {
            return ['success' => false, 'error' => 'Cannot delete the last user.'];
        }
        $this->db->table('users')->where('user_id', $id)->delete();
        return ['success' => true];
    }

    public function changePassword(array $data): array
    {
        $userId  = (int) Auth::userId();
        $current = $data['current_password'] ?? '';
        $new     = $data['new_password'] ?? '';
        $confirm = $data['confirm_password'] ?? '';

        $user = $this->db->table('users')->where('user_id', $userId)->first();
        $errors = [];
        if (!password_verify($current, $user['password_hash'] ?? '')) {
            $errors['current_password'] = 'Current password is incorrect.';
        }
        if (strlen($new) < 12) {
            $errors['new_password'] = 'New password must be at least 12 characters.';
        }
        if ($new !== $confirm) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $this->db->table('users')->where('user_id', $userId)->update([
            'password_hash' => password_hash($new, PASSWORD_BCRYPT),
        ]);
        return ['success' => true];
    }
}
