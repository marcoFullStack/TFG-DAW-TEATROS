<?php
// app/views/admin/dashboard.php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../config/config.php';

// Si no hay sesión de admin, fuera
if (empty($_SESSION['admin_id'])) {
  header('Location: ' . BASE_URL . 'views/auth/login_admin.php');
  exit;
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$adminName = $_SESSION['admin_name'] ?? 'Administrador';
$adminId   = (int)($_SESSION['admin_id'] ?? 0);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Dashboard Admin</title>

  <!-- Ajusta a tu CSS real -->
  <link rel="stylesheet" href="<?= h(BASE_URL) ?>styles/styleIndex.css">

  <style>
    /* Mini estilos para el panel si tu styleIndex no incluye estos bloques */
    .admin-wrap{ padding: 26px 0 60px; }
    .admin-grid{ display:grid; grid-template-columns: 1fr 1fr 1fr; gap:16px; margin-top:16px; }
    .admin-card{ padding:16px; border-radius:18px; border:1px solid rgba(255,255,255,.14); background:rgba(0,0,0,.18); }
    .admin-card h3{ margin:0 0 8px; }
    .admin-card p{ margin:0; opacity:.8; line-height:1.4; }
    .admin-actions{ margin-top:16px; display:flex; gap:10px; flex-wrap:wrap; }
    .btn{ display:inline-flex; align-items:center; justify-content:center; padding:12px 16px; border-radius:999px; border:1px solid rgba(255,255,255,.16); text-decoration:none; color:inherit; background:rgba(255,255,255,.06); }
    .btn.primary{ background: rgba(214,181,109,.12); border-color: rgba(214,181,109,.35); }
    @media(max-width:980px){ .admin-grid{ grid-template-columns:1fr; } }
  </style>
</head>
<body>

<main class="page">
  <section class="admin-wrap">
    <div class="container">
      <div class="section-head">
        <h2>Panel de administración</h2>
        <p>Bienvenido, <b><?= h($adminName) ?></b> (ID: <?= $adminId ?>).</p>
      </div>

      <div class="admin-actions">
        <a class="btn primary" href="<?= h(BASE_URL) ?>index.php">🎭 Ver web pública</a>
        <a class="btn" href="<?= h(BASE_URL) ?>views/admin/logout.php">🚪 Cerrar sesión</a>
      </div>

      <div class="admin-grid">
        <div class="admin-card">
          <h3>Teatros</h3>
          <p>Gestiona altas/bajas/modificación de teatros.</p>
          <div class="admin-actions">
            <a class="btn" href="<?= h(BASE_URL) ?>views/admin/teatros.php">Abrir</a>
          </div>
        </div>

        <div class="admin-card">
          <h3>Obras</h3>
          <p>Gestiona catálogo de obras e imágenes asociadas.</p>
          <div class="admin-actions">
            <a class="btn" href="<?= h(BASE_URL) ?>views/admin/obras.php">Abrir</a>
          </div>
        </div>

        <div class="admin-card">
          <h3>Horarios</h3>
          <p>Crea y edita sesiones (cartelera) por teatro/obra.</p>
          <div class="admin-actions">
            <a class="btn" href="<?= h(BASE_URL) ?>views/admin/horarios.php">Abrir</a>
          </div>
        </div>
      </div>

      <div style="margin-top:18px; opacity:.8;">
        <small>Nota: si aún no tienes creadas esas páginas (teatros.php/obras.php/horarios.php), puedes dejarlas como “pendiente”.</small>
      </div>
    </div>
  </section>
</main>

</body>
</html>
