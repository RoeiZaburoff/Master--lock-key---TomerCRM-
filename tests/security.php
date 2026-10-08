<?php
declare(strict_types=1);
require dirname(__DIR__).'/bin/bootstrap.php';
if (getenv('TEST_ALLOW_DB_WRITE')!=='1' || App\Core\RuntimeConfig::enabled('REQUIRE_HTTPS',true)) { throw new RuntimeException('Disposable local database required.'); }
$pdo=App\Core\Database::getInstance();
$password=trim(stream_get_contents(STDIN));
if (strlen($password)<12) { throw new RuntimeException('Supply test password on stdin.'); }
$username='operator-fixture';
$q=$pdo->prepare('INSERT INTO users(username,password_hash,role) VALUES(?,?,?)');
$q->execute([$username,password_hash($password,PASSWORD_BCRYPT),'user']);$id=(int)$pdo->lastInsertId();
session_start();App\Core\Auth::login($id,'admin');
$refreshed=App\Core\Auth::check() && !App\Core\Auth::isAdmin();
function check(bool $ok,string $name): void { if (!$ok) { throw new RuntimeException($name); }echo 'PASS '.$name.PHP_EOL; }
check($refreshed,'database role overrides stale session role');
$auth=new App\Modules\Auth\AuthService();
for ($i=0;$i<5;$i++) { check($auth->attempt($username,'wrong-password')===false,'invalid login rejected '.($i+1)); }
check($auth->attempt($username,$password)===false,'valid password blocked after five failed attempts');
$pdo->exec('DELETE FROM auth_attempts');
check($auth->attempt($username,$password)!==false,'login works after rate limit expires/reset in fixture');
$pdo->beginTransaction();
$pdo->exec("UPDATE schema_migrations SET checksum=REPEAT('0',64) WHERE version='001_core'");
check(!App\Core\SchemaState::current(),'changed migration checksum fails readiness');
$pdo->rollBack();check(App\Core\SchemaState::current(),'restored migration checksum passes readiness');
$q=$pdo->prepare('INSERT INTO users(username,password_hash,role) VALUES(?,?,?)');
$q->execute(['deleted-fixture',password_hash($password,PASSWORD_BCRYPT),'user']);$deleted=(int)$pdo->lastInsertId();
$_SESSION['user_id']=$deleted;$pdo->prepare('DELETE FROM users WHERE user_id=?')->execute([$deleted]);
check(!App\Core\Auth::check(),'deleted account loses existing session');
