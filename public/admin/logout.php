<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use App\Auth;

$auth = new Auth();
$auth->logout();

header('Location: /admin/login.php');
exit;

