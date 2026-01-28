<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/uploads.php';
require_once __DIR__ . '/../../DAO/UsuarioDAO.php';

$usuarioDao = new UsuarioDAO($pdo);
$errors = [];

if (!empty($_POST['btnRegister'])) {
  $nombre = trim((string)($_POST['Nombre'] ?? ''));
  $email  = trim((string)($_POST['Email'] ?? ''));
  $pass   = (string)($_POST['Password'] ?? '');

  if ($nombre === '') $errors[] = "El nombre es obligatorio.";
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email no válido.";
  if (strlen($pass) < 4) $errors[] = "La contraseña debe tener al menos 4 caracteres.";

  if (!$errors && $usuarioDao->buscarPorEmail($email)) {
    $errors[] = "El email ya está registrado.";
  }

  // Foto perfil (opcional)
  $fotoRel = null;
  if (!$errors && !empty($_FILES['Foto']['name']) && ($_FILES['Foto']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
    // Guarda en: app/images/usuarios/
    $fotoRel = save_uploaded_image($_FILES['Foto'], 'images', 'usuarios'); // devuelve "images/usuarios/xxx.webp"
    if (!$fotoRel) $errors[] = "No se pudo guardar la foto (formato/size/ruta).";
  }

  if (!$errors) {
    $hash = password_hash($pass, PASSWORD_DEFAULT);

    if ($usuarioDao->insertar($nombre, $email, $hash, $fotoRel)) {
      header("Location: " . BASE_URL . "views/user/login_user.php?success=1");
      exit;
    }
    $errors[] = "No se pudo registrar el usuario.";
  }
}

function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Registro</title>
  <link rel="stylesheet" href="<?= h(BASE_URL) ?>styles/styleIndex.css">
</head>
<body>
<main>
  <div class="form-box">
    <h1>Registro de Usuario</h1>

    <?php if ($errors): ?>
      <div class="notice"><?php foreach ($errors as $e) echo h($e) . "<br>"; ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <div class="form-row"><label>Nombre</label><input type="text" name="Nombre" required></div>
      <div class="form-row"><label>Email</label><input type="email" name="Email" required></div>
      <div class="form-row"><label>Contraseña</label><input type="password" name="Password" required></div>
      <div class="form-row"><label>Foto Perfil (opcional)</label><input type="file" name="Foto" accept="image/*"></div>

      <input type="submit" name="btnRegister" value="Registrarme">
      <a href="<?= h(BASE_URL) ?>views/user/login_user.php">Volver al login</a>
    </form>
  </div>
</main>
</body>
</html>
