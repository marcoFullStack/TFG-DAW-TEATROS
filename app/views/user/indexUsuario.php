<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/uploads.php';
require_once __DIR__ . '/../../DAO/UsuarioDAO.php';

function h($s): string
{
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
function clamp_int($v, int $min, int $max, int $fallback): int
{
  $n = filter_var($v, FILTER_VALIDATE_INT);
  if ($n === false) return $fallback;
  return max($min, min($max, $n));
}
function slugify(string $s): string
{
  $s = trim($s);
  $s = iconv('UTF-8', 'ASCII//TRANSLIT', $s) ?: $s;
  $s = strtolower($s);
  $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? $s;
  $s = trim($s, '-');
  return $s !== '' ? $s : 'foto';
}

/* ===================== AUTH USER ===================== */
if (empty($_SESSION['user_id'])) {
  header('Location: ' . BASE_URL . 'views/user/login.php');
  exit;
}

$idUsuario = (int)$_SESSION['user_id'];
$usuarioDAO = new UsuarioDAO($pdo);
$perfil = $usuarioDAO->buscarPerfilPorId($idUsuario);
if (!$perfil) {
  session_destroy();
  header('Location: ' . BASE_URL . 'views/auth/login_usuario.php');
  exit;
}

/* ===================== CONFIG ===================== */
const POINTS_PER_TICKET = 10; // puntos por entrada comprada (cámbialo si quieres)

/* ===================== UI / STATE ===================== */
$tab = (string)($_GET['tab'] ?? 'teatros');
$validTabs = ['teatros', 'obras', 'mis', 'subir'];
if (!in_array($tab, $validTabs, true)) $tab = 'teatros';

$notice = null;
$error  = null;

/* ===================== POST ACTIONS ===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = (string)($_POST['action'] ?? '');

  try {
    /* ---------- COMPRAR ENTRADAS ---------- */
    if ($action === 'buy_tickets') {
      $idHorario = (int)($_POST['idHorario'] ?? 0);
      $qty = (int)($_POST['qty'] ?? 0);

      if ($idHorario <= 0) throw new RuntimeException('Horario inválido.');
      if ($qty <= 0) throw new RuntimeException('Cantidad inválida.');

      // Transacción para evitar sobreventa
      $pdo->beginTransaction();

      // 1) Datos del horario + capacidad (bloquea fila de horarios)
   $st = $pdo->prepare("
  SELECT
    h.idHorario, h.idTeatro, h.Precio,
    t.CapacidadMax
  FROM horarios h
  INNER JOIN teatros t ON t.idTeatro = h.idTeatro
  WHERE h.idHorario = ?
  FOR UPDATE
");

      $st->execute([$idHorario]);
      $row = $st->fetch(PDO::FETCH_ASSOC);
      if (!$row) throw new RuntimeException('Horario no existe.');

      $cap = (int)$row['CapacidadMax'];
      $idTeatro = (int)$row['idTeatro'];
      $precioDb = (float)$row['Precio'];
      // 2) Entradas ya vendidas (bloquea rango de compras del horario)
      $st2 = $pdo->prepare("
        SELECT COALESCE(SUM(Entradas), 0) AS vendidas
        FROM compras_entradas
        WHERE idHorario = ?
        FOR UPDATE
      ");
      $st2->execute([$idHorario]);
      $vendidas = (int)$st2->fetchColumn();

      $restantes = $cap - $vendidas;
      if ($qty > $restantes) {
        throw new RuntimeException('No quedan suficientes entradas. Quedan: ' . max(0, $restantes));
      }

      // 3) Insertar compra
      $st3 = $pdo->prepare("INSERT INTO compras_entradas (idUsuario, idHorario, Entradas) VALUES (?, ?, ?)");
      $st3->execute([$idUsuario, $idHorario, $qty]);

      // 4) Sumar puntos al usuario
      $sumPoints = $qty * POINTS_PER_TICKET;
      $st4 = $pdo->prepare("UPDATE usuarios SET Puntos = Puntos + ? WHERE idUsuario = ?");
      $st4->execute([$sumPoints, $idUsuario]);

      // 5) (Opcional) marcar visita/ranking por teatro una vez (PK idUsuario,idTeatro)
      $st5 = $pdo->prepare("INSERT IGNORE INTO visitas_ranking (idUsuario, idTeatro) VALUES (?, ?)");
      $st5->execute([$idUsuario, $idTeatro]);

      $pdo->commit();

      $notice = "Compra realizada: $qty entradas (+$sumPoints puntos).";
      header('Location: ' . BASE_URL . 'views/user/indexUsuario.php?tab=mis&ok=1');
      exit;
    }
    /* ---------- CANCELAR COMPRA ---------- */
    if ($action === 'cancel_ticket') {
      $idCompra = (int)($_POST['idCompra'] ?? 0);

      if ($idCompra <= 0) throw new RuntimeException('Compra inválida.');

      $pdo->beginTransaction();

      // 1) La compra debe ser del usuario
      $st = $pdo->prepare("
        SELECT idCompra, idUsuario, idHorario, Entradas
        FROM compras_entradas
        WHERE idCompra = ? AND idUsuario = ?
        FOR UPDATE
      ");
      $st->execute([$idCompra, $idUsuario]);
      $c = $st->fetch(PDO::FETCH_ASSOC);
      if (!$c) throw new RuntimeException('No existe esa compra o no es tuya.');

      $entradas = (int)$c['Entradas'];

      // 2) Borrar compra
      $st2 = $pdo->prepare("DELETE FROM compras_entradas WHERE idCompra = ? AND idUsuario = ?");
      $st2->execute([$idCompra, $idUsuario]);

      // 3) Restar puntos (sin bajar de 0)
      $restPoints = $entradas * POINTS_PER_TICKET;
      $st3 = $pdo->prepare("UPDATE usuarios SET Puntos = GREATEST(Puntos - ?, 0) WHERE idUsuario = ?");
      $st3->execute([$restPoints, $idUsuario]);

      $pdo->commit();

      header('Location: ' . BASE_URL . 'views/user/indexUsuario.php?tab=mis&ok=1');
      exit;
    }


    /* ---------- SUBIR FOTO A GALERÍA ---------- */
    if ($action === 'upload_photo') {
      $idTeatro = (int)($_POST['idTeatro'] ?? 0);
      $idObra   = (int)($_POST['idObra'] ?? 0);

      if ($idTeatro <= 0) throw new RuntimeException('Selecciona un teatro.');
      if ($idObra <= 0) throw new RuntimeException('Selecciona una obra.');
      if (empty($_FILES['Imagen'])) throw new RuntimeException('Falta la imagen.');

      $file = $_FILES['Imagen'];

      if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Error en la subida.');
      }
      if (!is_allowed_image((string)$file['name'])) {
        throw new RuntimeException('Formato no permitido (jpg/jpeg/png/webp).');
      }
      $size = (int)($file['size'] ?? 0);
      if ($size <= 0 || $size > 5 * 1024 * 1024) throw new RuntimeException('Máximo 5MB.');

      // Obtener nombres para el filename
      $stT = $pdo->prepare("SELECT Sala FROM teatros WHERE idTeatro=?");
      $stT->execute([$idTeatro]);
      $sala = (string)($stT->fetchColumn() ?: '');

      $stO = $pdo->prepare("SELECT Titulo FROM obras WHERE idObra=?");
      $stO->execute([$idObra]);
      $titulo = (string)($stO->fetchColumn() ?: '');

      $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
      $baseName = slugify($sala) . '__' . slugify($titulo) . '__' . date('Ymd_His') . '_' . bin2hex(random_bytes(3));
      $finalName = $baseName . '.' . $ext;

      // Guardar en /app/fotosSubidasUsuarios/
      $subfolder = 'fotosSubidasUsuarios';
      $absDir = rtrim(app_root_path(), '/\\') . DIRECTORY_SEPARATOR . $subfolder;
      ensure_dir($absDir);

      $destAbs = $absDir . DIRECTORY_SEPARATOR . $finalName;
      if (!move_uploaded_file((string)$file['tmp_name'], $destAbs)) {
        throw new RuntimeException('No se pudo guardar el archivo.');
      }

      // Ruta relativa desde /app/
      $rel = $subfolder . '/' . $finalName;

      // Insert en galeria_revision (no existe idObra en tu tabla, lo metemos en el nombre del archivo)
      $stG = $pdo->prepare("INSERT INTO galeria_revision (idUsuario, idTeatro, RutaImagen, Estado) VALUES (?, ?, ?, 'pendiente')");
      $stG->execute([$idUsuario, $idTeatro, $rel]);

      $notice = "Foto subida correctamente (pendiente de revisión).";
      header('Location: ' . BASE_URL . 'views/user/indexUsuario.php?tab=subir&ok=1');
      exit;
    }
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $error = $e->getMessage();
  }
}

/* ===================== DATA QUERIES ===================== */
$ok = !empty($_GET['ok']);
if ($ok && !$notice) $notice = 'Operación realizada correctamente.';

// Foto perfil (default)
$foto = (string)($perfil['FotoPerfil'] ?? '');
$fotoUrl = '';
if ($foto !== '') {
  // si guardas ruta relativa en BD:
  $fotoUrl = BASE_URL . 'uploads/' . $foto;
} else {
  // pon una imagen real en /app/images/default_user.png (recomendado)
  $fotoUrl = BASE_URL . 'images/default_user.png';
}

// Listas simples para selects (subir fotos / elegir horarios)
$sel_teatros = $pdo->query("SELECT idTeatro, Sala, Provincia, Municipio FROM teatros ORDER BY Provincia, Municipio, Sala")
  ->fetchAll(PDO::FETCH_ASSOC);
$sel_obras = $pdo->query("SELECT idObra, Titulo FROM obras ORDER BY Titulo")
  ->fetchAll(PDO::FETCH_ASSOC);

/* ---- filtros ---- */
$teatros_q = trim((string)($_GET['teatros_q'] ?? ''));
$obras_q   = trim((string)($_GET['obras_q'] ?? ''));

$teatros_page = clamp_int($_GET['teatros_page'] ?? 1, 1, 999999, 1);
$obras_page   = clamp_int($_GET['obras_page'] ?? 1, 1, 999999, 1);

$pp = 10; // simple

/* ---- teatros list ---- */
$teatros = []; {
  $off = ($teatros_page - 1) * $pp;
  if ($teatros_q !== '') {
    $like = '%' . $teatros_q . '%';
    $st = $pdo->prepare("
      SELECT t.*,
        (SELECT RutaImagen FROM imagenes_teatros it WHERE it.idTeatro=t.idTeatro ORDER BY it.idImagenTeatro ASC LIMIT 1) AS img
      FROM teatros t
      WHERE t.Sala LIKE :q1 OR t.Provincia LIKE :q2 OR t.Municipio LIKE :q3
      ORDER BY t.Provincia, t.Municipio, t.Sala
      LIMIT :lim OFFSET :off
    ");
    $st->bindValue(':q1', $like, PDO::PARAM_STR);
    $st->bindValue(':q2', $like, PDO::PARAM_STR);
    $st->bindValue(':q3', $like, PDO::PARAM_STR);
  } else {
    $st = $pdo->prepare("
      SELECT t.*,
        (SELECT RutaImagen FROM imagenes_teatros it WHERE it.idTeatro=t.idTeatro ORDER BY it.idImagenTeatro ASC LIMIT 1) AS img
      FROM teatros t
      ORDER BY t.Provincia, t.Municipio, t.Sala
      LIMIT :lim OFFSET :off
    ");
  }
  $st->bindValue(':lim', $pp, PDO::PARAM_INT);
  $st->bindValue(':off', $off, PDO::PARAM_INT);
  $st->execute();
  $teatros = $st->fetchAll(PDO::FETCH_ASSOC);
}

/* ---- obras list ---- */
$obras = []; {
  $off = ($obras_page - 1) * $pp;
  if ($obras_q !== '') {
    $like = '%' . $obras_q . '%';
    $st = $pdo->prepare("
      SELECT o.*,
        (SELECT RutaImagen FROM imagenes_obras io WHERE io.idObra=o.idObra ORDER BY io.idImagenObra ASC LIMIT 1) AS img
      FROM obras o
      WHERE o.Titulo LIKE :q1 OR o.Autor LIKE :q2
      ORDER BY o.idObra DESC
      LIMIT :lim OFFSET :off
    ");
    $st->bindValue(':q1', $like, PDO::PARAM_STR);
    $st->bindValue(':q2', $like, PDO::PARAM_STR);
  } else {
    $st = $pdo->prepare("
      SELECT o.*,
        (SELECT RutaImagen FROM imagenes_obras io WHERE io.idObra=o.idObra ORDER BY io.idImagenObra ASC LIMIT 1) AS img
      FROM obras o
      ORDER BY o.idObra DESC
      LIMIT :lim OFFSET :off
    ");
  }
  $st->bindValue(':lim', $pp, PDO::PARAM_INT);
  $st->bindValue(':off', $off, PDO::PARAM_INT);
  $st->execute();
  $obras = $st->fetchAll(PDO::FETCH_ASSOC);
}

/* ---- horarios por obra seleccionada ---- */
$obra_sel = (int)($_GET['obra_sel'] ?? 0);
$horarios = [];
if ($obra_sel > 0) {
 $st = $pdo->prepare("
  SELECT
    h.idHorario, h.FechaHora, h.Precio,
    t.idTeatro, t.Sala, t.Provincia, t.Municipio, t.CapacidadMax,
    COALESCE((
      SELECT SUM(c.Entradas)
      FROM compras_entradas c
      WHERE c.idHorario = h.idHorario
    ), 0) AS vendidas
  FROM horarios h
  INNER JOIN teatros t ON t.idTeatro = h.idTeatro
  WHERE h.idObra = ?
  ORDER BY h.FechaHora ASC
");

  $st->execute([$obra_sel]);
  $horarios = $st->fetchAll(PDO::FETCH_ASSOC);
}

/* ---- mis compras ---- */
$mis = []; {
  $st = $pdo->prepare("
    SELECT
      c.idCompra, c.Entradas, c.FechaCompra,
      h.FechaHora,
      o.Titulo AS obra,
      t.Sala AS teatro, t.Provincia, t.Municipio
    FROM compras_entradas c
    INNER JOIN horarios h ON h.idHorario = c.idHorario
    INNER JOIN obras o ON o.idObra = h.idObra
    INNER JOIN teatros t ON t.idTeatro = h.idTeatro
    WHERE c.idUsuario = ?
    ORDER BY c.FechaCompra DESC
  ");
  $st->execute([$idUsuario]);
  $mis = $st->fetchAll(PDO::FETCH_ASSOC);
}

?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <title>Área de usuario</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="<?= h(BASE_URL) ?>styles/styleIndex.css">

  <style>
    body {
      padding: 14px;
    }

    .shell {
      width: min(1100px, calc(100% - 32px));
      margin: 0 auto;
    }

    .top {
      display: flex;
      gap: 14px;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      padding: 16px;
      border-radius: 18px;
      border: 1px solid rgba(255, 255, 255, .12);
      background: rgba(0, 0, 0, .18);
    }

    .who {
      display: flex;
      gap: 14px;
      align-items: center;
    }

    .avatar {
      width: 86px;
      height: 86px;
      border-radius: 22px;
      object-fit: cover;
      border: 1px solid rgba(255, 255, 255, .14);
      background: rgba(0, 0, 0, .25);
    }

    .name {
      font-size: 28px;
      font-weight: 900;
      line-height: 1.1;
    }

    .meta {
      color: var(--muted);
      margin-top: 6px;
    }

    .tabs {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin: 14px 0;
    }

    .tabs a {
      padding: 10px 14px;
      border-radius: 999px;
      text-decoration: none;
      border: 1px solid rgba(255, 255, 255, .14);
      background: rgba(0, 0, 0, .18);
      color: var(--muted);
    }

    .tabs a.active {
      color: var(--text);
      border-color: rgba(214, 181, 109, .35);
      background: rgba(214, 181, 109, .08);
    }

    .notice {
      padding: 12px 14px;
      border-radius: 16px;
      border: 1px solid rgba(255, 255, 255, .12);
      background: rgba(0, 0, 0, .18);
      margin: 10px 0;
    }

    .notice.ok {
      border-color: rgba(214, 181, 109, .30);
    }

    .notice.err {
      border-color: rgba(160, 38, 59, .45);
    }

    .grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }

    @media (max-width: 980px) {
      .grid {
        grid-template-columns: 1fr;
      }
    }

    .card {
      padding: 16px;
      border-radius: 18px;
      border: 1px solid rgba(255, 255, 255, .12);
      background: rgba(0, 0, 0, .18);
    }

    .card h2 {
      margin: 0;
    }

    .muted {
      color: var(--muted);
    }

    .list {
      display: grid;
      gap: 10px;
      margin-top: 12px;
    }

    .row {
      display: flex;
      gap: 12px;
      align-items: flex-start;
      justify-content: space-between;
      flex-wrap: wrap;
      padding: 12px;
      border-radius: 16px;
      border: 1px solid rgba(255, 255, 255, .10);
      background: rgba(0, 0, 0, .16);
    }

    .thumb {
      width: 120px;
      height: 80px;
      border-radius: 14px;
      object-fit: cover;
      border: 1px solid rgba(255, 255, 255, .12);
    }

    .btn {
      padding: 10px 12px;
      border-radius: 14px;
      border: 1px solid rgba(255, 255, 255, .16);
      background: rgba(0, 0, 0, .18);
      color: var(--text);
      cursor: pointer;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .btn.ok {
      border-color: rgba(214, 181, 109, .35);
    }

    .btn.danger {
      border-color: rgba(160, 38, 59, .45);
    }

    input,
    select {
      width: 100%;
      padding: 12px 12px;
      border-radius: 14px;
      border: 1px solid rgba(255, 255, 255, .12);
      background: rgba(0, 0, 0, .22);
      color: var(--text);
      outline: none;
    }

    label {
      display: block;
      font-size: 12px;
      color: var(--muted);
      margin-bottom: 6px;
    }

    .form {
      display: grid;
      gap: 10px;
      margin-top: 12px;
    }

    /* MODAL */
    .modal-backdrop {
      position: fixed;
      inset: 0;
      display: none;
      align-items: center;
      justify-content: center;
      background: rgba(0, 0, 0, .55);
      padding: 16px;
      z-index: 9999;
    }

    .modal {
      width: min(520px, 100%);
      border-radius: 18px;
      border: 1px solid rgba(255, 255, 255, .14);
      background: rgba(10, 10, 10, .92);
      padding: 16px;
    }

    .modal-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
    }

    .modal-head h3 {
      margin: 0;
    }

    .spinner {
      width: 18px;
      height: 18px;
      border-radius: 999px;
      border: 3px solid rgba(255, 255, 255, .20);
      border-top-color: rgba(214, 181, 109, .9);
      animation: spin .9s linear infinite;
    }

    @keyframes spin {
      to {
        transform: rotate(360deg);
      }
    }

    .bar {
      height: 10px;
      border-radius: 999px;
      border: 1px solid rgba(255, 255, 255, .14);
      background: rgba(0, 0, 0, .18);
      overflow: hidden;
    }

    .barIn {
      height: 100%;
      width: 0%;
      background: rgba(214, 181, 109, .55);
      transition: width .2s ease;
    }
  </style>
</head>

<body>
  <div class="shell">
    <div class="top">
      <div class="who">
        <img class="avatar" src="<?= h($fotoUrl) ?>" alt="Foto de perfil"
          onerror="this.src='<?= h(BASE_URL) ?>images/default_user.png'">
        <div>
          <div class="name"><?= h((string)$perfil['Nombre']) ?></div>
          <div class="meta">
            <?= h((string)$perfil['Email']) ?> ·
            Puntos: <b><?= (int)($perfil['Puntos'] ?? 0) ?></b>
          </div>
        </div>
      </div>
      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a class="btn" href="<?= h(BASE_URL) ?>index.php">🏠 Inicio</a>
        <a class="btn danger" href="<?= h(BASE_URL) ?>views/user/logout.php">🚪 Cerrar sesión</a>
      </div>
    </div>

    <?php if ($notice): ?><div class="notice ok"><?= h($notice) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="notice err"><?= h($error) ?></div><?php endif; ?>

    <nav class="tabs">
      <a class="<?= $tab === 'teatros' ? 'active' : '' ?>" href="<?= h(BASE_URL) ?>views/user/indexUsuario.php?tab=teatros">Teatros</a>
      <a class="<?= $tab === 'obras' ? 'active' : '' ?>" href="<?= h(BASE_URL) ?>views/user/indexUsuario.php?tab=obras">Obras + horarios</a>
      <a class="<?= $tab === 'mis' ? 'active' : '' ?>" href="<?= h(BASE_URL) ?>views/user/indexUsuario.php?tab=mis">Mis entradas</a>
      <a class="<?= $tab === 'subir' ? 'active' : '' ?>" href="<?= h(BASE_URL) ?>views/user/indexUsuario.php?tab=subir">Subir fotos</a>
    </nav>

    <?php if ($tab === 'teatros'): ?>
      <div class="card">
        <h2>Teatros</h2>
        <p class="muted" style="margin:6px 0 0;">Busca por sala, provincia o municipio.</p>

        <form class="form" method="get">
          <input type="hidden" name="tab" value="teatros">
          <div>
            <label>Filtrar</label>
            <input name="teatros_q" value="<?= h($teatros_q) ?>" placeholder="Ej: Valladolid, Teatro..., etc">
          </div>
          <button class="btn ok" type="submit">Aplicar filtro</button>
        </form>

        <div class="list">
          <?php foreach ($teatros as $t): ?>
            <?php $img = (string)($t['img'] ?? '');
            $imgUrl = $img !== '' ? rel_to_url($img) : (BASE_URL . 'images/default_teatro.png'); ?>
            <div class="row">
              <div style="display:flex; gap:12px; align-items:flex-start; flex-wrap:wrap;">
                <img class="thumb" src="<?= h($imgUrl) ?>" alt="teatro" onerror="this.src='<?= h(BASE_URL) ?>images/default_teatro.png'">
                <div>
                  <div style="font-weight:900; font-size:18px;"><?= h((string)$t['Sala']) ?></div>
                  <div class="muted"><?= h((string)$t['Provincia']) ?> · <?= h((string)$t['Municipio']) ?></div>
                  <div class="muted">Capacidad: <b><?= (int)$t['CapacidadMax'] ?></b></div>
                  <?php if (!empty($t['Direccion'])): ?><div class="muted"><?= h((string)$t['Direccion']) ?></div><?php endif; ?>
                </div>
              </div>
              <a class="btn" href="<?= h(BASE_URL) ?>views/user/indexUsuario.php?tab=obras">Ver obras</a>
            </div>
          <?php endforeach; ?>
          <?php if (!$teatros): ?><div class="row"><span class="muted">Sin resultados.</span></div><?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($tab === 'obras'): ?>
      <div class="grid">
        <div class="card">
          <h2>Obras</h2>
          <p class="muted" style="margin:6px 0 0;">Filtra por título o autor y selecciona una obra para ver horarios.</p>

          <form class="form" method="get">
            <input type="hidden" name="tab" value="obras">
            <div>
              <label>Filtrar</label>
              <input name="obras_q" value="<?= h($obras_q) ?>" placeholder="Ej: Lorca, Hamlet...">
            </div>
            <button class="btn ok" type="submit">Aplicar filtro</button>
          </form>

          <div class="list">
            <?php foreach ($obras as $o): ?>
              <?php $img = (string)($o['img'] ?? '');
              $imgUrl = $img !== '' ? rel_to_url($img) : (BASE_URL . 'images/default_obra.png'); ?>
              <div class="row">
                <div style="display:flex; gap:12px; align-items:flex-start; flex-wrap:wrap;">
                  <img class="thumb" src="<?= h($imgUrl) ?>" alt="obra" onerror="this.src='<?= h(BASE_URL) ?>images/default_obra.png'">
                  <div>
                    <div style="font-weight:900; font-size:18px;"><?= h((string)$o['Titulo']) ?></div>
                    <div class="muted"><?= h((string)($o['Autor'] ?? '')) ?></div>
                  </div>
                </div>
                <a class="btn ok" href="<?= h(BASE_URL) ?>views/user/indexUsuario.php?tab=obras&obra_sel=<?= (int)$o['idObra'] ?>">Ver horarios</a>
              </div>
            <?php endforeach; ?>
            <?php if (!$obras): ?><div class="row"><span class="muted">Sin resultados.</span></div><?php endif; ?>
          </div>
        </div>

        <div class="card">
          <h2>Horarios <?= $obra_sel > 0 ? 'de la obra seleccionada' : '' ?></h2>
          <p class="muted" style="margin:6px 0 0;">Compra entradas para un horario concreto. Si no quedan, no deja comprar.</p>

          <?php if ($obra_sel <= 0): ?>
            <div class="notice" style="margin-top:12px;">
              <span class="muted">Selecciona una obra (botón “Ver horarios”).</span>
            </div>
          <?php else: ?>
            <div class="list" style="margin-top:12px;">
              <?php foreach ($horarios as $hrow): ?>
                <?php
                $precio = (float)($hrow['Precio'] ?? 0);

                $cap = (int)$hrow['CapacidadMax'];
                $vend = (int)$hrow['vendidas'];
                $rest = max(0, $cap - $vend);
                ?>
                <div class="row">
                  <div>
                    <div style="font-weight:900;"><?= h((string)$hrow['FechaHora']) ?></div>
                    <div class="muted">
                      <?= h((string)$hrow['Provincia']) ?> · <?= h((string)$hrow['Municipio']) ?> · <b><?= h((string)$hrow['Sala']) ?></b>
                    </div>
                    <div class="muted">Quedan: <b><?= $rest ?></b> / <?= $cap ?></div>
                    <div class="muted">Precio: <b><?= number_format($precio, 2, ',', '.') ?> €</b></div>

                  </div>

                  <button
                    class="btn ok"
                    type="button"
                    <?= $rest <= 0 ? 'disabled' : '' ?>
                    onclick="openPayModal(
  <?= (int)$hrow['idHorario'] ?>,
  <?= (int)$rest ?>,
  '<?= h((string)$hrow['FechaHora']) ?>',
  '<?= h((string)$hrow['Sala']) ?>',
  <?= json_encode((float)$hrow['Precio']) ?>
)">
                    🎟️ Comprar
                  </button>
                </div>
              <?php endforeach; ?>
              <?php if (!$horarios): ?><div class="row"><span class="muted">No hay horarios para esta obra.</span></div><?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($tab === 'mis'): ?>
      <div class="card">
        <h2>Mis entradas</h2>
        <p class="muted" style="margin:6px 0 0;">Aquí ves las compras realizadas.</p>

        <div class="list">
          <?php foreach ($mis as $m): ?>
            <div class="row">
              <div>
                <div style="font-weight:900; font-size:16px;"><?= h((string)$m['obra']) ?></div>
                <div class="muted"><?= h((string)$m['FechaHora']) ?> · <?= h((string)$m['Provincia']) ?> · <?= h((string)$m['Municipio']) ?> · <b><?= h((string)$m['teatro']) ?></b></div>
                <div class="muted">Entradas: <b><?= (int)$m['Entradas'] ?></b> · Comprado: <?= h((string)$m['FechaCompra']) ?></div>
              </div>
              <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                <span class="muted">#<?= (int)$m['idCompra'] ?></span>

                <form method="post" onsubmit="return confirm('¿Seguro que quieres cancelar esta compra? Se devolverán las entradas y se restarán los puntos.');">
                  <input type="hidden" name="action" value="cancel_ticket">
                  <input type="hidden" name="idCompra" value="<?= (int)$m['idCompra'] ?>">
                  <button class="btn danger" type="submit">❌ Cancelar</button>
                </form>
              </div>

            </div>
          <?php endforeach; ?>
          <?php if (!$mis): ?><div class="row"><span class="muted">Aún no has comprado entradas.</span></div><?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($tab === 'subir'): ?>
      <div class="card">
        <h2>Subir fotos</h2>
        <p class="muted" style="margin:6px 0 0;">
          La foto se guardará en <b>fotosSubidasUsuarios/</b> y entrará en revisión (pendiente).
          El nombre del archivo se crea con <b>teatro + obra</b>.
        </p>

        <form class="form" method="post" enctype="multipart/form-data">
          <input type="hidden" name="action" value="upload_photo">

          <div>
            <label>Teatro</label>
            <select name="idTeatro" required>
              <option value="">Selecciona...</option>
              <?php foreach ($sel_teatros as $t): ?>
                <option value="<?= (int)$t['idTeatro'] ?>">
                  <?= h($t['Provincia'] . ' · ' . $t['Municipio'] . ' · ' . $t['Sala']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label>Obra</label>
            <select name="idObra" required>
              <option value="">Selecciona...</option>
              <?php foreach ($sel_obras as $o): ?>
                <option value="<?= (int)$o['idObra'] ?>"><?= h((string)$o['Titulo']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label>Imagen (jpg/png/webp, máx 5MB)</label>
            <input type="file" name="Imagen" accept="image/*" required>
          </div>

          <button class="btn ok" type="submit">📷 Subir foto</button>
        </form>
      </div>
    <?php endif; ?>
  </div>

  <!-- MODAL DE PAGO -->
  <div id="payBackdrop" class="modal-backdrop" onclick="closePayModal(event)">
    <div class="modal" onclick="event.stopPropagation()">
      <div class="modal-head">
        <h3>Pago simulado</h3>
        <button class="btn" type="button" onclick="hidePayModal()">✖</button>
      </div>

      <div class="muted" id="payInfo" style="margin-top:8px;"></div>

      <form class="form" method="post" id="payForm" style="margin-top:12px;">
        <input type="hidden" name="precio" id="payPrecio" value="">

        <input type="hidden" name="action" value="buy_tickets">
        <input type="hidden" name="idHorario" id="payIdHorario" value="">

        <div>
          <label>Nº entradas</label>
          <input type="number" name="qty" id="payQty" min="1" value="1" required>
          <div class="muted" style="margin-top:6px;">
            Puntos: <b><span id="payPoints">0</span></b> (<?= (int)POINTS_PER_TICKET ?> por entrada)
          </div>
        </div>

        <div class="grid" style="grid-template-columns:1fr 1fr; gap:10px;">
          <div>
            <label>Titular</label>
            <input type="text" id="cardName" placeholder="Nombre Apellidos" required>
          </div>
          <div>
            <label>DNI</label>
            <input type="text" id="dni" placeholder="12345678A" required>
          </div>
        </div>

        <div class="grid" style="grid-template-columns:1.2fr .8fr .8fr; gap:10px;">
          <div>
            <label>Nº tarjeta</label>
            <input type="text" id="cardNumber" inputmode="numeric" placeholder="4111 1111 1111 1111" required>
          </div>
          <div>
            <label>Caducidad</label>
            <input type="text" id="cardExp" placeholder="MM/AA" required>
          </div>
          <div>
            <label>CVV</label>
            <input type="password" id="cardCvv" inputmode="numeric" placeholder="123" required>
          </div>
          <div class="muted" style="margin-top:8px;">
            Precio por entrada: <b><span id="payPrice">0.00</span> €</b><br>
            Total: <b><span id="payTotal">0.00</span> €</b>
          </div>

        </div>

        <div class="row" style="gap:10px; justify-content:flex-start;">
          <button class="btn ok" type="submit" id="btnPay">✅ Pagar y confirmar</button>
          <button class="btn" type="button" onclick="hidePayModal()">Cancelar</button>
        </div>

        <!-- Loader -->
        <div id="payLoader" class="notice" style="margin-top:10px; display:none;">
          <div style="display:flex; align-items:center; gap:10px;">
            <div class="spinner"></div>
            <div>
              <b>Procesando pago...</b><br>
              <span class="muted">No cierres esta ventana</span>
            </div>
          </div>
          <div class="bar" style="margin-top:10px;">
            <div class="barIn" id="barIn"></div>
          </div>
        </div>

        <div class="notice" style="margin-top:10px;">
          <span class="muted">Simulación: no se valida ninguna tarjeta real.</span>
        </div>
      </form>

    </div>
  </div>

  <script>
    const PRICE = document.getElementById('payPrice');
const TOTAL = document.getElementById('payTotal');
const HPRICE = document.getElementById('payPrecio');
let currentPrice = 0;

    const BACKDROP = document.getElementById('payBackdrop');
    const INFO = document.getElementById('payInfo');
    const IDHOR = document.getElementById('payIdHorario');
    const QTY = document.getElementById('payQty');
    const PTS = document.getElementById('payPoints');
    const P_PER = <?= (int)POINTS_PER_TICKET ?>;

   function openPayModal(idHorario, restantes, fechaHora, sala, precio) {
  currentPrice = parseFloat(precio || 0);

  IDHOR.value = idHorario;
  QTY.max = restantes;
  QTY.value = 1;

  HPRICE.value = currentPrice.toFixed(2);
  PRICE.textContent = currentPrice.toFixed(2);
  TOTAL.textContent = (currentPrice * 1).toFixed(2);

  INFO.textContent = `Horario: ${fechaHora} · Teatro: ${sala} · Quedan: ${restantes}`;
  PTS.textContent = (1 * P_PER).toString();

  BACKDROP.style.display = 'flex';
  setTimeout(()=>QTY.focus(), 50);
}


    function hidePayModal() {
      BACKDROP.style.display = 'none';
    }

    function closePayModal(e) {
      hidePayModal();
    }

    QTY.addEventListener('input', () => {
  const v = Math.max(1, parseInt(QTY.value || '1', 10));
  QTY.value = v;

  PTS.textContent = (v * P_PER).toString();
  TOTAL.textContent = (currentPrice * v).toFixed(2);
});

    const payForm = document.getElementById('payForm');
    const payLoader = document.getElementById('payLoader');
    const btnPay = document.getElementById('btnPay');
    const barIn = document.getElementById('barIn');

    function onlyDigits(s) {
      return (s || '').replace(/\D+/g, '');
    }

    payForm.addEventListener('submit', (e) => {
      // Validación simulada simple
      const num = onlyDigits(document.getElementById('cardNumber').value);
      const exp = (document.getElementById('cardExp').value || '').trim();
      const cvv = onlyDigits(document.getElementById('cardCvv').value);
      const dni = (document.getElementById('dni').value || '').trim();
      const name = (document.getElementById('cardName').value || '').trim();

      if (name.length < 4) {
        e.preventDefault();
        alert('Titular demasiado corto');
        return;
      }
      if (dni.length < 8) {
        e.preventDefault();
        alert('DNI no válido');
        return;
      }
      if (num.length < 13) {
        e.preventDefault();
        alert('Tarjeta no válida');
        return;
      }
      if (!/^\d{2}\/\d{2}$/.test(exp)) {
        e.preventDefault();
        alert('Caducidad debe ser MM/AA');
        return;
      }
      if (cvv.length < 3) {
        e.preventDefault();
        alert('CVV no válido');
        return;
      }

      // Loader simulado (bloquea 1.5s y luego envía)
      e.preventDefault();

      btnPay.disabled = true;
      payLoader.style.display = 'block';
      barIn.style.width = '0%';

      let p = 0;
      const t = setInterval(() => {
        p += 7 + Math.random() * 10;
        if (p >= 100) p = 100;
        barIn.style.width = p + '%';
        if (p >= 100) clearInterval(t);
      }, 120);

      setTimeout(() => {
        payForm.submit(); // ahora sí enviamos al PHP
      }, 1500);
    });
  </script>
</body>

</html>