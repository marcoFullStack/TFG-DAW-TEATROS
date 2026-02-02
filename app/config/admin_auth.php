<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function require_admin(): void {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();

  if (empty($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . 'views/auth/login_admin.php');
    exit;
  }
}
