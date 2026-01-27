<?php
// config/auth.php
declare(strict_types=1);

function require_user(): void {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  if (empty($_SESSION['user_id'])) {
    header("Location: /user/login.php");
    exit;
  }
}

function require_admin(): void {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  if (empty($_SESSION['admin_id'])) {
    header("Location: /admin/login.php");
    exit;
  }
}
