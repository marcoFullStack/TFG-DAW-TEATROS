<?php
// app/views/auth/register_admin.php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../DAO/AdminDAO.php';
require_once __DIR__ . '/../../models/Admin.php';


function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// (Opcional) Si quieres que solo un admin ya logeado pueda crear otros admins,
// descomenta esto:
//
// if (empty($_SESSION['admin_id'])) {
//   header('Location: ' . BASE_URL . 'views/auth/login_admin.php');
//   exit;
// }

$dao = new AdminDAO($pdo);
$errors = [];

if (!empty($_POST['btnRegisterAdmin'])) {
  $nombre = trim($_POST['Nombre'] ?? '');
  $email  = trim($_POST['Email'] ?? '');
  $pass1  = (string)($_POST['Password'] ?? '');
  $pass2  = (string)($_POST['Password2'] ?? '');

  if ($nombre === '') $errors[] = 'Nombre obligatorio';
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email no válido';
  if (strlen($pass1) < 4) $errors[] = 'Contraseña mínimo 4 caracteres';
  if ($pass1 !== $pass2) $errors[] = 'Las contraseñas no coinciden';

  if (!$errors && $dao->buscarPorEmail($email)) {
    $errors[] = 'Ese email de admin ya existe';
  }

if (!$errors) {
  $hash = password_hash($pass1, PASSWORD_DEFAULT);

  $admin = new Admin(
    nombre: $nombre,
    email: $email,
    passwordHash: $hash
  );

  if ($dao->insertar($admin)) {
    header('Location: ' . BASE_URL . 'views/auth/login_admin.php');
    exit;
  } else {
    $errors[] = 'No se pudo crear el admin';
  }
}

}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Registro Admin</title>

  <link rel="stylesheet" href="<?= h(BASE_URL) ?>styles/styleIndex.css">
</head>
<body>
<main>
  <div class="form-box">
    <h1>Crear administrador</h1>

    <?php if ($errors): ?>
      <div class="notice">
        <?php foreach ($errors as $e): ?>
          • <?= h($e) ?><br>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form id="formAdminRegister" method="post" novalidate>
      <div class="form-row">
        <label>Nombre</label>
        <input name="Nombre" value="<?= h($_POST['Nombre'] ?? '') ?>">
      </div>

      <div class="form-row">
        <label>Email</label>
        <input name="Email" type="email" value="<?= h($_POST['Email'] ?? '') ?>">
      </div>

      <div class="form-row">
        <label>Contraseña</label>
        <input name="Password" type="password">
      </div>

      <div class="form-row">
        <label>Repetir contraseña</label>
        <input name="Password2" type="password">
      </div>

      <input type="submit" name="btnRegisterAdmin" value="✅ Crear admin">
      <a href="<?= h(BASE_URL) ?>views/auth/login_admin.php">🔐 Volver al login</a>
    </form>
  </div>
</main>

<script src="<?= h(BASE_URL) ?>js/validator.js"></script>
</body>
</html>
