<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../DAO/UsuarioDAO.php';

$dao = new UsuarioDAO($pdo);
$errors = [];

if (!empty($_POST['btnRegister'])) {
  $Nombre = trim($_POST['Nombre'] ?? '');
  $Email = trim($_POST['Email'] ?? '');
  $Password = (string)($_POST['Password'] ?? '');
  $Password2 = (string)($_POST['Password2'] ?? '');

  if ($Nombre === '') $errors[] = 'Nombre obligatorio';
  if (!filter_var($Email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email no válido';
  if (strlen($Password) < 4) $errors[] = 'Contraseña mínimo 4 caracteres';
  if ($Password !== $Password2) $errors[] = 'Las contraseñas no coinciden';

  if (!$errors && $dao->buscarPorEmail($Email)) $errors[] = 'Ese email ya existe';

  $fotoNombre = null;
  if (!$errors && !empty($_FILES['FotoPerfil']['name']) && $_FILES['FotoPerfil']['error'] === UPLOAD_ERR_OK) {
    $tmp = $_FILES['FotoPerfil']['tmp_name'];
    $size = (int)$_FILES['FotoPerfil']['size'];

    if ($size > 2 * 1024 * 1024) {
      $errors[] = "La foto supera 2MB";
    } else {
      $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
      $ext = strtolower(pathinfo($_FILES['FotoPerfil']['name'], PATHINFO_EXTENSION));
      if (!in_array($ext, $allowedExt, true)) {
        $errors[] = "Formato no permitido (solo jpg/png/webp)";
      } else {
        // uploads cuelga de /app/uploads (NO de /views/uploads)
        $dir = __DIR__ . "/../../uploads";
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $fotoNombre = "user_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
        $dest = $dir . "/" . $fotoNombre;

        if (!move_uploaded_file($tmp, $dest)) {
          $errors[] = "No se pudo guardar la foto";
          $fotoNombre = null;
        }
      }
    }
  }

  if (!$errors) {
    $hash = password_hash($Password, PASSWORD_DEFAULT);
    $ok = $dao->insertar($Nombre, $Email, $hash, $fotoNombre);

    if (!$ok) $errors[] = "No se pudo registrar el usuario";
    else {
      header('Location: ' . BASE_URL . 'views/user/login.php');
      exit;
    }
  }
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Registro usuario</title>
  <link rel="stylesheet" href="<?= h(BASE_URL) ?>styles/styleIndex.css">
</head>
<body>
<main>
  <div class="form-box">
    <h1>Registro usuario</h1>

    <?php if ($errors): ?>
      <div class="notice">
        <?php foreach ($errors as $e) echo "• " . h($e) . "<br>"; ?>
      </div>
    <?php endif; ?>

    <form id="formUserRegister" method="post" enctype="multipart/form-data" novalidate>
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

      <div class="form-row">
        <label>Foto de perfil (opcional)</label>
        <input type="file" name="FotoPerfil" accept="image/*">
      </div>

      <input type="submit" name="btnRegister" value="✅ Crear cuenta">
      <a href="<?= h(BASE_URL) ?>views/user/login.php">🔐 Ya tengo cuenta</a>
    </form>
  </div>
</main>

<script src="<?= h(BASE_URL) ?>js/formAuth.js"></script>
<script>
  AuthForms.enhanceForm(document.getElementById("formUserRegister"), [
    { selector: 'input[name="Nombre"]', required:true, message:"Nombre obligatorio" },
    { selector: 'input[name="Email"]', required:true, email:true, message:"Email no válido" },
    { selector: 'input[name="Password"]', required:true, minLength:4, message:"Contraseña mínimo 4 caracteres" },
    { selector: 'input[name="Password2"]', required:true, minLength:4, message:"Repite la contraseña" }
  ]);
</script>
</body>
</html>
