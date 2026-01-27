<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_user();

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../DAO/UsuarioDAO.php';

$dao = new UsuarioDAO($pdo);
$perfil = $dao->buscarPerfilPorId((int)$_SESSION['user_id']);

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// uploads está en /app/uploads
$fotoRel = (string)($perfil['FotoPerfil'] ?? '');
$fotoUrl = BASE_URL . 'uploads/' . $fotoRel;

$fotoPath = __DIR__ . '/../../uploads/' . $fotoRel;
$fotoExiste = ($fotoRel !== '') && file_exists($fotoPath);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Zona usuario</title>
  <link rel="stylesheet" href="<?= h(BASE_URL) ?>styles/styleIndex.css">
</head>
<body>
<main>
  <div class="notice">
    <h1>Zona de usuario</h1>
    <p>Sesión iniciada correctamente.</p>
  </div>

  <div class="profile-card">
    <div class="profile-left">
      <?php if ($fotoExiste): ?>
        <img class="avatar-lg" src="<?= h($fotoUrl) ?>" alt="Foto de perfil">
      <?php else: ?>
        <div class="avatar-lg avatar-fallback">👤</div>
      <?php endif; ?>
    </div>

    <div class="profile-right">
      <div class="profile-kicker">SESION INICIADA</div>
      <div class="profile-name"><?= h($perfil['Nombre'] ?? 'Usuario') ?></div>
      <div class="profile-sub">
        Puntos: <b><?= (int)($perfil['Puntos'] ?? 0) ?></b> · Alta: <?= h($perfil['FechaAlta'] ?? '') ?>
      </div>
    </div>
  </div>

  <div style="margin-top:16px; display:flex; gap:10px; flex-wrap:wrap;">
    <a class="btn" href="<?= h(BASE_URL) ?>index.php">🎭 Ir a teatros</a>
    <a class="btn" href="<?= h(BASE_URL) ?>views/user/logout.php">🚪 Cerrar sesión</a>
  </div>
</main>
</body>
</html>
