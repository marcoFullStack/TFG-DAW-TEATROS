<?php

declare(strict_types=1);

/**
 * The function `require_user` checks if a user is logged in and redirects to the login page if not.
 */
function require_user(): void {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  if (empty($_SESSION['user_id'])) {
    header("Location: /user/login.php");
    exit;
  }
}

