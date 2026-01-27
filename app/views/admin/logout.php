<?php
// app/views/admin/logout.php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../../config/config.php';

unset($_SESSION['admin_id'], $_SESSION['admin_name']);
header('Location: ' . BASE_URL . 'views/auth/login_admin.php');
exit;
