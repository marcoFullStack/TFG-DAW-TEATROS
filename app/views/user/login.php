<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../DAO/UsuarioDAO.php';

$dao = new UsuarioDAO($pdo);
$error = null;

/* This block of code is handling the login functionality when the user submits the login form. Here's
a breakdown of what each part of the code is doing: */
if (!empty($_POST['btnLogin'])) {
  $Email = trim($_POST['Email'] ?? '');
  $Password = (string)($_POST['Password'] ?? '');

  $u = $dao->buscarPorEmail($Email);

  if ($u && password_verify($Password, (string)$u['PasswordHash'])) {
    $_SESSION['user_id'] = (int)$u['idUsuario'];
    header('Location: ' . BASE_URL . 'views/user/indexUsuario.php');
    exit;
  } else {
    $error = "Credenciales incorrectas";
  }
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login usuario</title>
  <link rel="stylesheet" href="<?= h(BASE_URL) ?>styles/styleIndex.css">
  <link rel="stylesheet" href="<?= h(BASE_URL) ?>styles/login.css?v=1">
    <link rel="preload" as="image" href="<?= h(BASE_URL) ?>video/ImagenPosterLogin.webp">

</head>
<body>
  <!-- Video de fondo -->
<div class="video-bg" >
  <video autoplay muted  playsinline preload="metadata"  poster="<?= h(BASE_URL) ?>video/ImagenPosterLogin.webp">
    <source src="<?= h(BASE_URL) ?>video/vecteezy_opening-doors-reveals-empty-seats-in-dark-theater-at-night_72007843.mp4" type="video/mp4">
  </video>
</div>

<main>
  <div class="form-box">
    <h1>Login usuario</h1>

    <?php if ($error): ?>
      <div class="notice"><?= h($error) ?></div>
    <?php endif; ?>

    <form id="formUserLogin" method="post" novalidate>
      <div class="form-row">
        <label>Email</label>
        <input name="Email" type="email" value="<?= h($_POST['Email'] ?? '') ?>">
      </div>
      <div class="form-row">
        <label>Contraseña</label>
        <input name="Password" type="password">
      </div>

      <input type="submit" name="btnLogin" value="🔓 Entrar">
      <a href="<?= h(BASE_URL) ?>views/user/register.php">➕ Registrarme</a>
      <a href="<?= h(BASE_URL) ?>index.php">🏠 Inicio</a>
    </form>
  </div>
</main>

<script src="<?= h(BASE_URL) ?>js/formAuth.js"></script>
<script>
 /* The code `AuthForms.enhanceForm(document.getElementById("formUserLogin"), [...]);` is enhancing the
 form validation for the login form with specific rules for the input fields. Here's a breakdown of
 what each part of the code is doing: */
  AuthForms.enhanceForm(document.getElementById("formUserLogin"), [
    { selector: 'input[name="Email"]', required:true, email:true, message:"Email no válido" },
    { selector: 'input[name="Password"]', required:true, minLength:4, message:"Contraseña mínimo 4 caracteres" }
  ]);
     const v = document.querySelector('.video-bg video');
  if (v) {
    const show = () => v.classList.add('ready');
    v.addEventListener('canplay', show, { once:true });
    v.addEventListener('loadeddata', show, { once:true });
    // fallback por si el navegador no dispara eventos
    setTimeout(show, 1500);
  }
</script>
</body>
</html>
