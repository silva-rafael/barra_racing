<?php
require_once '../lib/Auth.php';
require_once '../config/database.php';

$auth = new Auth($pdo);
$auth->logout();

header('Location: login.php?status=logout');
exit();
?>