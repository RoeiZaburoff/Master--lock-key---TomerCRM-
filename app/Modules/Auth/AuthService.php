<?php
declare(strict_types=1);

namespace App\Modules\Auth;

use App\Core\QueryBuilder;

class AuthService
{
    private QueryBuilder $db;

    public function __construct()
    {
        $this->db = new QueryBuilder();
    }

    /**
     * Returns [userId, role] on success, or false on failure.
     */
    public function attempt(string $username, string $password): array|false
    {
        $key=hash('sha256', strtolower($username) . "\0" . ($_SERVER['REMOTE_ADDR'] ?? 'cli'));
        $this->db->rawExecute("INSERT INTO auth_attempts(attempt_key,failures,window_start) VALUES(:k,1,UTC_TIMESTAMP())
            ON DUPLICATE KEY UPDATE failures=IF(window_start < UTC_TIMESTAMP()-INTERVAL 15 MINUTE,1,failures+1),
            window_start=IF(window_start < UTC_TIMESTAMP()-INTERVAL 15 MINUTE,UTC_TIMESTAMP(),window_start)", [':k'=>$key]);
        $attempt=$this->db->rawFirst('SELECT failures FROM auth_attempts WHERE attempt_key=:k',[':k'=>$key]);
        if ((int)$attempt['failures']>5) { return false; }
        $user = $this->db->table('users')
            ->where('username', $username)
            ->first();

        if ($user === null) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        $this->db->rawExecute('DELETE FROM auth_attempts WHERE attempt_key=:k',[':k'=>$key]);
        $role = $user['role'] ?? 'user';

        return [(int) $user['user_id'], (string) $role];
    }
}
