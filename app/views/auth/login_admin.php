<?php
// app/views/auth/login_admin.php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../DAO/AdminDAO.php';

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// Si ya está logeado como admin, fuera
if (!empty($_SESSION['admin_id'])) {
  header('Location: ' . BASE_URL . 'views/admin/dashboard.php');
  exit;
}

$dao = new AdminDAO($pdo);
$error = null;

if (!empty($_POST['btnLoginAdmin'])) {
  $email = trim($_POST['Email'] ?? '');
  $pass  = (string)($_POST['Password'] ?? '');

  $admin = $dao->buscarPorEmail($email);

  if ($admin && password_verify($pass, (string)$admin['PasswordHash'])) {
    $_SESSION['admin_id'] = (int)$admin['idAdmin'];
    $_SESSION['admin_name'] = (string)$admin['Nombre'];

    $dao->actualizarUltimoLogin((int)$admin['idAdmin']);

    header('Location: ' . BASE_URL . 'views/admin/dashboard.php');
    exit;
  } else {
    $error = "Credenciales de administrador incorrectas.";
  }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Login Admin</title>

  <!-- Usa tu CSS real -->
  <link rel="stylesheet" href="<?= h(BASE_URL) ?>styles/styleIndex.css">
  <link rel="stylesheet" href="<?= h(BASE_URL) ?>styles/login_admin.css?v=1">
</head>
<body>
<main>
  <div class="form-box">
    <h1>Panel de Administración</h1>

    <?php if ($error): ?>
      <div class="notice"><?= h($error) ?></div>
    <?php endif; ?>

    <form id="formAdminLogin" method="post" novalidate>
      <div class="form-row">
        <label>Email Admin</label>
        <input name="Email" type="email" value="<?= h($_POST['Email'] ?? '') ?>">
      </div>

      <div class="form-row">
        <label>Contraseña</label>
        <input name="Password" type="password">
      </div>

      <input type="submit" name="btnLoginAdmin" value="🔐 Entrar como Admin">

      <div style="margin-top:10px; display:flex; gap:10px; flex-wrap:wrap;">
        <a href="<?= h(BASE_URL) ?>views/auth/register_admin.php">➕ Crear admin</a>
        <a href="<?= h(BASE_URL) ?>views/user/login.php">👤 Login usuario</a>
        <a href="<?= h(BASE_URL) ?>index.php">🏠 Inicio</a>
      </div>
    </form>
  </div>
</main>

<script src="<?= h(BASE_URL) ?>js/validator.js"></script>
</body>
</html>
