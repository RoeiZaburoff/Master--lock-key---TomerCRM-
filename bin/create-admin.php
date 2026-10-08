<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
$username=$argv[1] ?? 'admin';
if (!preg_match('/^[A-Za-z0-9_.-]{3,100}$/D',$username)) { throw new RuntimeException('Invalid username.'); }
$password=rtrim(stream_get_contents(STDIN),"\r\n");
if (strlen($password)<12) { throw new RuntimeException('Password must contain at least 12 characters. Supply it via stdin.'); }
$pdo=App\Core\Database::getInstance();
$q=$pdo->prepare("INSERT INTO users(username,password_hash,role) VALUES(?,?,'admin')");
$q->execute([$username,password_hash($password,PASSWORD_BCRYPT)]);
echo 'Administrator created. Existing users are never overwritten.'.PHP_EOL;
