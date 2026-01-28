<?php
// app/views/admin/dashboard.php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/uploads.php';
require_once __DIR__ . '/../../DAO/AdminPanelDAO.php';
require_once __DIR__ . '/../../models/Obra.php';
require_once __DIR__ . '/../../models/Teatro.php';
require_once __DIR__ . '/../../models/Horario.php';
require_once __DIR__ . '/../../models/Usuario.php';


function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* ===================== AUTH ADMIN ===================== */
if (empty($_SESSION['admin_id'])) {
  header('Location: ' . BASE_URL . 'views/auth/login_admin.php');
  exit;
}

/* ===================== DAO ===================== */
$dao = new AdminPanelDAO($pdo);

/* ===================== UI HELPERS ===================== */
function qs(array $overrides = []): string {
  $q = $_GET;
  foreach ($overrides as $k => $v) {
    if ($v === null) unset($q[$k]);
    else $q[$k] = $v;
  }
  return '?' . http_build_query($q);
}

function clamp_int($v, int $min, int $max, int $fallback): int {
  $n = filter_var($v, FILTER_VALIDATE_INT);
  if ($n === false) return $fallback;
  return max($min, min($max, $n));
}

function normalize_datetime_local_to_mysql(string $s): ?string {
  // input type="datetime-local" => "YYYY-MM-DDTHH:MM"
  $s = trim($s);
  if ($s === '') return null;
  $s = str_replace('T', ' ', $s);
  // añadir segundos si no vienen
  if (preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}$/', $s)) $s .= ':00';
  if (!preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/', $s)) return null;
  return $s;
}

function safe_float_or_null($v): ?float {
  $v = trim((string)$v);
  if ($v === '') return null;
  $v = str_replace(',', '.', $v);
  if (!is_numeric($v)) return null;
  return (float)$v;
}

function safe_int_or_null($v): ?int {
  $v = trim((string)$v);
  if ($v === '') return null;
  if (!preg_match('/^-?\d+$/', $v)) return null;
  return (int)$v;
}

/* ===================== ROUTING (tabs) ===================== */
$tabs = [
  'obras'    => 'Obras',
  'teatros'  => 'Teatros',
  'horarios' => 'Horarios',
  'usuarios' => 'Usuarios',
  'galeria'  => 'Galería revisión',
];
$tab = (string)($_GET['tab'] ?? 'obras');
if (!isset($tabs[$tab])) $tab = 'obras';

/* ===================== FLASH / ERRORS ===================== */
$notice = null;
$error  = null;

/* ===================== POST ACTIONS ===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = (string)($_POST['action'] ?? '');

  try {
    /* ---------- OBRAS CRUD ---------- */
    if ($action === 'obra_create') {
      $titulo = trim((string)($_POST['Titulo'] ?? ''));
      $autor = trim((string)($_POST['Autor'] ?? ''));
      $sub = trim((string)($_POST['Subtitulo'] ?? ''));
      $anio = safe_int_or_null($_POST['Anio'] ?? '');
      $url = trim((string)($_POST['UrlDracor'] ?? ''));

      if ($titulo === '') throw new RuntimeException('El título es obligatorio.');

      $obra = new Obra(
  titulo: $titulo,
  autor: $autor !== '' ? $autor : null,
  subtitulo: $sub !== '' ? $sub : null,
  anio: $anio,
  urlDracor: $url !== '' ? $url : null
);

$newId = $dao->createObra($obra);

      if ($newId <= 0) throw new RuntimeException('No se pudo crear la obra.');

      $notice = "Obra creada (ID $newId).";
      header('Location: ' . qs(['tab' => 'obras', 'obra_edit' => $newId]));
      exit;
    }

    if ($action === 'obra_update') {
      $id = (int)($_POST['idObra'] ?? 0);
      $titulo = trim((string)($_POST['Titulo'] ?? ''));
      $autor = trim((string)($_POST['Autor'] ?? ''));
      $sub = trim((string)($_POST['Subtitulo'] ?? ''));
      $anio = safe_int_or_null($_POST['Anio'] ?? '');
      $url = trim((string)($_POST['UrlDracor'] ?? ''));

      if ($id <= 0) throw new RuntimeException('ID de obra inválido.');
      if ($titulo === '') throw new RuntimeException('El título es obligatorio.');

      $obra = new Obra(
  titulo: $titulo,
  autor: $autor !== '' ? $autor : null,
  subtitulo: $sub !== '' ? $sub : null,
  anio: $anio,
  urlDracor: $url !== '' ? $url : null,
  idObra: $id
);

if (!$dao->updateObra($obra)) {
  throw new RuntimeException('No se pudo actualizar la obra.');
}


      header('Location: ' . qs(['tab' => 'obras', 'obra_edit' => $id, 'ok' => '1']));
      exit;
    }

    if ($action === 'obra_delete') {
      $id = (int)($_POST['idObra'] ?? 0);
      if ($id <= 0) throw new RuntimeException('ID de obra inválido.');

      // borrar archivos físicos de sus imágenes (si RutaImagen es relativa desde /app/)
      $imgs = $dao->listImagenesObra($id);
      foreach ($imgs as $im) {
        if (!empty($im['RutaImagen'])) delete_rel_file((string)$im['RutaImagen']);
      }

      if (!$dao->deleteObra($id)) throw new RuntimeException('No se pudo borrar la obra.');
      header('Location: ' . qs(['tab' => 'obras', 'obra_edit' => null, 'ok' => '1']));
      exit;
    }

    if ($action === 'obra_img_add') {
      $id = (int)($_POST['idObra'] ?? 0);
      if ($id <= 0) throw new RuntimeException('ID de obra inválido.');

      $ruta = save_uploaded_image($_FILES['Imagen'] ?? [], 'images', 'obras');
      if (!$ruta) throw new RuntimeException('No se pudo guardar la imagen (formato/size/ruta).');

      if (!$dao->addImagenObra($id, $ruta)) {
        // rollback archivo
        delete_rel_file($ruta);
        throw new RuntimeException('No se pudo registrar la imagen en BD.');
      }

      header('Location: ' . qs(['tab' => 'obras', 'obra_edit' => $id, 'ok' => '1']));
      exit;
    }

    if ($action === 'obra_img_delete') {
      $idImg = (int)($_POST['idImagenObra'] ?? 0);
      $idObra = (int)($_POST['idObra'] ?? 0);
      if ($idImg <= 0) throw new RuntimeException('ID de imagen inválido.');

      $img = $dao->getImagenObra($idImg);
      if ($img && !empty($img['RutaImagen'])) delete_rel_file((string)$img['RutaImagen']);

      if (!$dao->deleteImagenObra($idImg)) throw new RuntimeException('No se pudo borrar la imagen.');
      header('Location: ' . qs(['tab' => 'obras', 'obra_edit' => $idObra > 0 ? $idObra : null, 'ok' => '1']));
      exit;
    }

    /* ---------- TEATROS CRUD ---------- */
  /* ---------- TEATROS CRUD ---------- */
if ($action === 'teatro_create' || $action === 'teatro_update') {
  $isUpdate = ($action === 'teatro_update');
  $idTeatro = (int)($_POST['idTeatro'] ?? 0);

  $t = [
    'Sala'        => trim((string)($_POST['Sala'] ?? '')),
    'Entidad'     => trim((string)($_POST['Entidad'] ?? '')),
    'Provincia'   => trim((string)($_POST['Provincia'] ?? '')),
    'Municipio'   => trim((string)($_POST['Municipio'] ?? '')),
    'Direccion'   => trim((string)($_POST['Direccion'] ?? '')),
    'CP'          => trim((string)($_POST['CP'] ?? '')),
    'Telefono'    => trim((string)($_POST['Telefono'] ?? '')),
    'Email'       => trim((string)($_POST['Email'] ?? '')),
    'CapacidadMax'=> safe_int_or_null($_POST['CapacidadMax'] ?? '') ?? 0,
    'Latitud'     => safe_float_or_null($_POST['Latitud'] ?? ''),
    'Longitud'    => safe_float_or_null($_POST['Longitud'] ?? ''),
  ];

  if ($t['Sala'] === '' || $t['Provincia'] === '' || $t['Municipio'] === '') {
    throw new RuntimeException('Sala, Provincia y Municipio son obligatorios.');
  }
  if ($t['CapacidadMax'] <= 0) throw new RuntimeException('CapacidadMax debe ser > 0.');

  // normaliza vacíos a null (excepto CapacidadMax)
  foreach (['Entidad','Direccion','CP','Telefono','Email'] as $k) {
    if ($t[$k] === '') $t[$k] = null;
  }

  // ✅ CREA OBJETO TEATRO
  $teatroObj = new Teatro(
    sala: $t['Sala'],
    provincia: $t['Provincia'],
    municipio: $t['Municipio'],
    capacidadMax: (int)$t['CapacidadMax'],
    entidad: $t['Entidad'],
    direccion: $t['Direccion'],
    cp: $t['CP'],
    telefono: $t['Telefono'],
    email: $t['Email'],
    latitud: $t['Latitud'],
    longitud: $t['Longitud'],
    idTeatro: $isUpdate ? $idTeatro : null
  );

  if ($isUpdate) {
    if ($idTeatro <= 0) throw new RuntimeException('ID de teatro inválido.');
    if (!$dao->updateTeatro($teatroObj)) throw new RuntimeException('No se pudo actualizar el teatro.');
    header('Location: ' . qs(['tab' => 'teatros', 'teatro_edit' => $idTeatro, 'ok' => '1']));
    exit;
  } else {
    $newId = $dao->createTeatro($teatroObj);
    if ($newId <= 0) throw new RuntimeException('No se pudo crear el teatro.');
    header('Location: ' . qs(['tab' => 'teatros', 'teatro_edit' => $newId, 'ok' => '1']));
    exit;
  }
}


   
if ($action === 'teatro_delete') {
  $id = (int)($_POST['idTeatro'] ?? 0);
  if ($id <= 0) throw new RuntimeException('ID de teatro inválido.');

  $imgs = $dao->listImagenesTeatro($id);
  foreach ($imgs as $im) {
    if (!empty($im['RutaImagen'])) delete_rel_file((string)$im['RutaImagen']);
  }

  if (!$dao->deleteTeatro($id)) throw new RuntimeException('No se pudo borrar el teatro.');
  header('Location: ' . qs(['tab' => 'teatros', 'teatro_edit' => null, 'ok' => '1']));
  exit;
}



    

    if ($action === 'teatro_img_add') {
      $id = (int)($_POST['idTeatro'] ?? 0);
      if ($id <= 0) throw new RuntimeException('ID de teatro inválido.');

      $ruta = save_uploaded_image($_FILES['Imagen'] ?? [], 'images', 'teatros');
      if (!$ruta) throw new RuntimeException('No se pudo guardar la imagen (formato/size/ruta).');

      if (!$dao->addImagenTeatro($id, $ruta)) {
        delete_rel_file($ruta);
        throw new RuntimeException('No se pudo registrar la imagen en BD.');
      }

      header('Location: ' . qs(['tab' => 'teatros', 'teatro_edit' => $id, 'ok' => '1']));
      exit;
    }

    if ($action === 'teatro_img_delete') {
      $idImg = (int)($_POST['idImagenTeatro'] ?? 0);
      $idTeatro = (int)($_POST['idTeatro'] ?? 0);
      if ($idImg <= 0) throw new RuntimeException('ID de imagen inválido.');

      $img = $dao->getImagenTeatro($idImg);
      if ($img && !empty($img['RutaImagen'])) delete_rel_file((string)$img['RutaImagen']);

      if (!$dao->deleteImagenTeatro($idImg)) throw new RuntimeException('No se pudo borrar la imagen.');
      header('Location: ' . qs(['tab' => 'teatros', 'teatro_edit' => $idTeatro > 0 ? $idTeatro : null, 'ok' => '1']));
      exit;
    }

    /* ---------- USUARIOS CRUD (update/delete) ---------- */
    if ($action === 'usuario_update') {
      $id = (int)($_POST['idUsuario'] ?? 0);
      $nombre = trim((string)($_POST['Nombre'] ?? ''));
      $email  = trim((string)($_POST['Email'] ?? ''));
      $puntos = (int)($_POST['Puntos'] ?? 0);

      if ($id <= 0) throw new RuntimeException('ID de usuario inválido.');
      if ($nombre === '') throw new RuntimeException('Nombre obligatorio.');
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Email no válido.');

      // necesitas tener el usuario actual para conservar PasswordHash y FotoPerfil
$old = $dao->getUsuarioById($id);
if (!$old) throw new RuntimeException('Usuario no existe.');

$usuarioObj = new Usuario(
  nombre: $nombre,
  email: $email,
  passwordHash: (string)($old['PasswordHash'] ?? ''),
  fotoPerfil: $old['FotoPerfil'] ?? null,
  idUsuario: $id,
  puntos: $puntos,
  fechaAlta: $old['FechaAlta'] ?? null
);

// ojo: este método lo tienes que crear en AdminPanelDAO (updateUsuarioObj)
if (!$dao->updateUsuarioObj($usuarioObj)) {
  throw new RuntimeException('No se pudo actualizar el usuario.');
}

      header('Location: ' . qs(['tab' => 'usuarios', 'user_edit' => $id, 'ok' => '1']));
      exit;
    }

    if ($action === 'usuario_delete') {
      $id = (int)($_POST['idUsuario'] ?? 0);
      if ($id <= 0) throw new RuntimeException('ID de usuario inválido.');

      $u = $dao->getUsuarioById($id);
      // opcional: borrar foto perfil si la guardas como ruta relativa desde app/
      if ($u && !empty($u['FotoPerfil'])) {
  // FotoPerfil en BD: "usuarios/archivo.jpg"
  // Archivo real: /app/uploads/usuarios/archivo.jpg
  delete_rel_file('uploads/' . (string)$u['FotoPerfil']);
}


      if (!$dao->deleteUsuario($id)) throw new RuntimeException('No se pudo borrar el usuario.');
      header('Location: ' . qs(['tab' => 'usuarios', 'user_edit' => null, 'ok' => '1']));
      exit;
    }

    /* ---------- HORARIOS CRUD ---------- */
    if ($action === 'horario_create') {
      $idTeatro = (int)($_POST['idTeatro'] ?? 0);
      $idObra   = (int)($_POST['idObra'] ?? 0);
      $fechaRaw = (string)($_POST['FechaHora'] ?? '');
      $fecha    = normalize_datetime_local_to_mysql($fechaRaw);

      if ($idTeatro <= 0 || $idObra <= 0) throw new RuntimeException('Selecciona teatro y obra.');
      if (!$fecha) throw new RuntimeException('Fecha/Hora inválida.');

      $precio = mt_rand(1200, 4000) / 100; // 12.00 - 40.00

$horarioObj = new Horario(
  idTeatro: $idTeatro,
  idObra: $idObra,
  fechaHora: $fecha,
  precio: (float)$precio
);

if (!$dao->createHorario($horarioObj)) throw new RuntimeException('No se pudo crear el horario.');

      header('Location: ' . qs(['tab' => 'horarios', 'ok' => '1']));
      exit;
    }

    if ($action === 'horario_update') {
      $idHorario= (int)($_POST['idHorario'] ?? 0);
      $idTeatro = (int)($_POST['idTeatro'] ?? 0);
      $idObra   = (int)($_POST['idObra'] ?? 0);
      $fechaRaw = (string)($_POST['FechaHora'] ?? '');
      $fecha    = normalize_datetime_local_to_mysql($fechaRaw);

      if ($idHorario <= 0) throw new RuntimeException('ID de horario inválido.');
      if ($idTeatro <= 0 || $idObra <= 0) throw new RuntimeException('Selecciona teatro y obra.');
      if (!$fecha) throw new RuntimeException('Fecha/Hora inválida.');

      // Para no perder el precio, lo leemos de BD aquí:
$old = $dao->getHorarioById($idHorario);
$precio = $old && isset($old['Precio']) ? (float)$old['Precio'] : 0.0;

$horarioObj = new Horario(
  idTeatro: $idTeatro,
  idObra: $idObra,
  fechaHora: $fecha,
  precio: $precio,
  idHorario: $idHorario
);

if (!$dao->updateHorario($horarioObj)) throw new RuntimeException('No se pudo actualizar el horario.');

      header('Location: ' . qs(['tab' => 'horarios', 'hor_edit' => $idHorario, 'ok' => '1']));
      exit;
    }

    if ($action === 'horario_delete') {
      $idHorario = (int)($_POST['idHorario'] ?? 0);
      if ($idHorario <= 0) throw new RuntimeException('ID de horario inválido.');

      if (!$dao->deleteHorario($idHorario)) throw new RuntimeException('No se pudo borrar el horario.');
      header('Location: ' . qs(['tab' => 'horarios', 'hor_edit' => null, 'ok' => '1']));
      exit;
    }

    /* ---------- GALERIA REVISION ---------- */
    if ($action === 'galeria_set_estado') {
      $idImagen = (int)($_POST['idImagen'] ?? 0);
      $estado = (string)($_POST['Estado'] ?? 'pendiente');
      if ($idImagen <= 0) throw new RuntimeException('ID inválido.');

      if (!$dao->setGaleriaEstado($idImagen, $estado)) throw new RuntimeException('No se pudo cambiar el estado.');
      header('Location: ' . qs(['tab' => 'galeria', 'ok' => '1']));
      exit;
    }

    if ($action === 'galeria_delete') {
      $idImagen = (int)($_POST['idImagen'] ?? 0);
      if ($idImagen <= 0) throw new RuntimeException('ID inválido.');

      $it = $dao->getGaleriaItem($idImagen);
      if ($it && !empty($it['RutaImagen'])) {
        // RutaImagen debería ser relativa desde app/ (idealmente fotosSubidasUsuarios/...)
        delete_rel_file((string)$it['RutaImagen']);
      }
      if (!$dao->deleteGaleriaItem($idImagen)) throw new RuntimeException('No se pudo borrar el item.');
      header('Location: ' . qs(['tab' => 'galeria', 'ok' => '1']));
      exit;
    }

  } catch (Throwable $e) {
    $error = $e->getMessage();
  }
}

/* ===================== GET STATE ===================== */
$ok = !empty($_GET['ok']);
if ($ok && !$notice) $notice = 'Operación realizada correctamente.';

$adminName = (string)($_SESSION['admin_name'] ?? 'Admin');

/* ===================== DATA PER TAB ===================== */
$obra_edit_id   = (int)($_GET['obra_edit'] ?? 0);
$teatro_edit_id = (int)($_GET['teatro_edit'] ?? 0);
$user_edit_id   = (int)($_GET['user_edit'] ?? 0);
$hor_edit_id    = (int)($_GET['hor_edit'] ?? 0);

$pp_obras   = clamp_int($_GET['obras_pp'] ?? 10, 5, 100, 10);
$pp_teatros = clamp_int($_GET['teatros_pp'] ?? 10, 5, 100, 10);
$pp_users   = clamp_int($_GET['usuarios_pp'] ?? 10, 5, 100, 10);
$pp_hor     = clamp_int($_GET['horarios_pp'] ?? 10, 5, 100, 10);
$pp_gal     = clamp_int($_GET['galeria_pp'] ?? 10, 5, 100, 10);

$p_obras   = clamp_int($_GET['obras_page'] ?? 1, 1, 999999, 1);
$p_teatros = clamp_int($_GET['teatros_page'] ?? 1, 1, 999999, 1);
$p_users   = clamp_int($_GET['usuarios_page'] ?? 1, 1, 999999, 1);
$p_hor     = clamp_int($_GET['horarios_page'] ?? 1, 1, 999999, 1);
$p_gal     = clamp_int($_GET['galeria_page'] ?? 1, 1, 999999, 1);

$q_obras   = trim((string)($_GET['obras_q'] ?? ''));
$q_teatros = trim((string)($_GET['teatros_q'] ?? ''));
$q_users   = trim((string)($_GET['usuarios_q'] ?? ''));

$hor_idTeatro = (int)($_GET['hor_idTeatro'] ?? 0);
$gal_estado   = (string)($_GET['gal_estado'] ?? 'pendiente');

// Inicializa todo vacío (para que no pete el HTML)
$obras_total=$teatros_total=$users_total=$hor_total=$gal_total=0;
$obras_rows=$teatros_rows=$users_rows=$hor_rows=$gal_rows=[];
$obra_edit=$teatro_edit=$user_edit=$hor_edit=null;
$obra_imgs=$teatro_imgs=[];
$sel_teatros=$sel_obras=[];

try {
  if ($tab === 'obras') {
    $pack = $dao->obrasPage($p_obras, $pp_obras, $q_obras);
    $obras_total = (int)$pack['total'];
    $obras_rows  = $pack['rows'];

    $obra_edit = $obra_edit_id > 0 ? $dao->getObraById($obra_edit_id) : null;
    $obra_imgs = ($obra_edit && $obra_edit_id > 0) ? $dao->listImagenesObra($obra_edit_id) : [];
  }

  if ($tab === 'teatros') {
    $pack = $dao->teatrosPage($p_teatros, $pp_teatros, $q_teatros);
    $teatros_total = (int)$pack['total'];
    $teatros_rows  = $pack['rows'];

    $teatro_edit = $teatro_edit_id > 0 ? $dao->getTeatroById($teatro_edit_id) : null;
    $teatro_imgs = ($teatro_edit && $teatro_edit_id > 0) ? $dao->listImagenesTeatro($teatro_edit_id) : [];
  }

  if ($tab === 'usuarios') {
    $pack = $dao->usuariosPage($p_users, $pp_users, $q_users);
    $users_total = (int)$pack['total'];
    $users_rows  = $pack['rows'];

    $user_edit = $user_edit_id > 0 ? $dao->getUsuarioById($user_edit_id) : null;
  }

  if ($tab === 'horarios') {
    $pack = $dao->horariosPage($p_hor, $pp_hor, $hor_idTeatro);
    $hor_total = (int)$pack['total'];
    $hor_rows  = $pack['rows'];

    $hor_edit  = $hor_edit_id > 0 ? $dao->getHorarioById($hor_edit_id) : null;

    // selects SOLO cuando estás en horarios
    $sel_teatros = $dao->listTeatrosSimple();
    $sel_obras   = $dao->listObrasSimple();
  }

  if ($tab === 'galeria') {
    $pack = $dao->galeriaPage($p_gal, $pp_gal, $gal_estado);
    $gal_total = (int)$pack['total'];
    $gal_rows  = $pack['rows'];
  }

} catch (Throwable $e) {
  $error = "ERROR BD: " . $e->getMessage();
}


function pager(int $total, int $page, int $perPage, string $pageKey): array {
  $pages = (int)ceil(max(1, $total) / max(1, $perPage));
  $page = max(1, min($pages, $page));
  return [$pages, $page];
}

[$obras_pages, $p_obras]   = pager($obras_total, $p_obras, $pp_obras, 'obras_page');
[$teatros_pages, $p_teatros]= pager($teatros_total, $p_teatros, $pp_teatros, 'teatros_page');
[$users_pages, $p_users]   = pager($users_total, $p_users, $pp_users, 'usuarios_page');
[$hor_pages, $p_hor]       = pager($hor_total, $p_hor, $pp_hor, 'horarios_page');
[$gal_pages, $p_gal]       = pager($gal_total, $p_gal, $pp_gal, 'galeria_page');

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Dashboard admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Puedes cambiarlo por tu CSS admin si ya tienes uno -->
  <link rel="stylesheet" href="<?= h(BASE_URL) ?>styles/styleIndex.css">

  <style>
    body{ padding: 14px; }
    .admin-top{
      display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;
      margin: 10px auto 14px;
      width:min(var(--max), calc(100% - 32px));
    }
    .admin-top .who{ color: var(--muted); }
    .admin-shell{ width:min(var(--max), calc(100% - 32px)); margin:0 auto; }
    .admin-tabs{ display:flex; gap:10px; flex-wrap:wrap; margin: 14px 0; }
    .admin-tabs a{
      display:inline-flex; align-items:center; justify-content:center;
      padding: 10px 14px;
      border-radius: 999px;
      border:1px solid rgba(255,255,255,.14);
      background: rgba(0,0,0,.18);
      color: var(--muted);
      text-decoration:none;
    }
    .admin-tabs a.active{
      color: var(--text);
      border-color: rgba(214,181,109,.35);
      background: rgba(214,181,109,.08);
    }

    .panelX{ padding:16px; margin-top: 12px; }
    .grid2{ display:grid; grid-template-columns: 1.1fr .9fr; gap: 14px; align-items:start; }
    @media (max-width: 980px){ .grid2{ grid-template-columns: 1fr; } }

    .tableX{ width:100%; border-collapse:collapse; overflow:hidden; border-radius: 16px; }
    .tableX th,.tableX td{
      padding:10px; border-bottom: 1px solid rgba(255,255,255,.08);
      vertical-align:top; text-align:left;
    }
    .tableX th{ color: var(--muted); font-size: 12px; font-weight:700; }
    .tableX tr:hover td{ background: rgba(255,255,255,.03); }
    .actions{ display:flex; gap:8px; flex-wrap:wrap; }
    .btn2{
      display:inline-flex; align-items:center; justify-content:center;
      padding: 8px 10px; border-radius: 12px;
      border:1px solid rgba(255,255,255,.16);
      background: rgba(0,0,0,.18);
      color: var(--text);
      cursor:pointer;
      text-decoration:none;
    }
    .btn2.danger{ border-color: rgba(160,38,59,.45); }
    .btn2.ok{ border-color: rgba(214,181,109,.35); }
    .btn2:disabled{ opacity:.55; cursor:not-allowed; }

    .formX{ display:grid; gap:10px; }
    .formX label{ font-size:12px; color: var(--muted); display:block; margin-bottom:6px; }
    .formX input, .formX textarea, .formX select{
      width:100%; padding: 12px 12px; border-radius: 14px;
      border: 1px solid rgba(255,255,255,.12);
      background: rgba(0,0,0,.22);
      color: var(--text);
      outline:none;
    }
    .formX textarea{ min-height: 90px; resize: vertical; }
    .rowX{ display:grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    @media (max-width: 700px){ .rowX{ grid-template-columns: 1fr; } }

    .pager{ display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-top: 10px; }
    .pager .meta{ color: var(--muted); font-size: 12px; }
    .chipX{
      display:inline-flex; align-items:center;
      padding: 6px 10px; border-radius:999px;
      border:1px solid rgba(255,255,255,.14);
      background: rgba(0,0,0,.18);
      font-size: 12px;
      color: var(--muted);
    }

    .img-grid{
      display:grid; grid-template-columns: repeat(3, 1fr); gap: 10px;
      margin-top: 10px;
    }
    @media (max-width: 980px){ .img-grid{ grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 560px){ .img-grid{ grid-template-columns: 1fr; } }
    .img-card{
      border:1px solid rgba(255,255,255,.12);
      border-radius: 16px;
      background: rgba(0,0,0,.18);
      overflow:hidden;
    }
    .img-card img{ width:100%; height: 150px; object-fit: cover; display:block; }
    .img-card .img-foot{ padding: 10px; display:flex; justify-content:space-between; gap:8px; align-items:center; }
    .noticeX{
      padding: 12px 14px;
      border-radius: 16px;
      border:1px solid rgba(255,255,255,.12);
      background: rgba(0,0,0,.18);
      margin-top: 10px;
    }
    .noticeX.ok{ border-color: rgba(214,181,109,.30); }
    .noticeX.err{ border-color: rgba(160,38,59,.45); }
  </style>
</head>

<body>
  <div class="admin-top">
    <div>
      <div style="font-weight:900; letter-spacing:.02em;">Panel de administración</div>
      <div class="who">Hola, <?= h($adminName) ?> · <span class="chipX">ID <?= (int)($_SESSION['admin_id']) ?></span></div>
    </div>
    <div class="actions">
      <a class="btn2" href="<?= h(BASE_URL) ?>index.php">🎭 Volver al inicio</a>
      <a class="btn2 danger" href="<?= h(BASE_URL) ?>views/admin/logout.php">🚪 Cerrar sesión</a>
    </div>
  </div>

  <div class="admin-shell">
    <?php if ($notice): ?>
      <div class="noticeX ok"><?= h($notice) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="noticeX err"><?= h($error) ?></div>
    <?php endif; ?>

    <nav class="admin-tabs">
      <?php foreach ($tabs as $k => $label): ?>
        <a class="<?= $tab === $k ? 'active' : '' ?>" href="<?= h(qs(['tab' => $k])) ?>"><?= h($label) ?></a>
      <?php endforeach; ?>
    </nav>

    <?php if ($tab === 'obras'): ?>
      <section class="panelX glass">
        <div class="grid2">
          <div>
            <div class="section-head">
              <h2 style="margin:0;">Obras</h2>
              <p style="margin:6px 0 0; color:var(--muted);">CRUD + imágenes · búsqueda + paginación</p>
            </div>

            <form method="get" class="filters glass" style="margin-top:12px;">
              <input type="hidden" name="tab" value="obras">
              <div class="f-group">
                <label>Buscar</label>
                <input name="obras_q" value="<?= h($q_obras) ?>" placeholder="Título o autor...">
              </div>
              <div class="f-group">
                <label>Por página</label>
                <select name="obras_pp">
                  <?php foreach ([10,20,30,50,100] as $v): ?>
                    <option value="<?= $v ?>" <?= $pp_obras===$v?'selected':'' ?>><?= $v ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <button class="btn small" type="submit">Aplicar</button>
            </form>

            <div class="glass" style="margin-top:12px; overflow:hidden;">
              <table class="tableX">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Autor</th>
                    <th>Año</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($obras_rows as $r): ?>
                    <tr>
                      <td><?= (int)$r['idObra'] ?></td>
                      <td><b><?= h($r['Titulo'] ?? '') ?></b></td>
                      <td><?= h($r['Autor'] ?? '') ?></td>
                      <td><?= h($r['Anio'] ?? '') ?></td>
                      <td class="actions">
                        <a class="btn2 ok" href="<?= h(qs(['obra_edit' => (int)$r['idObra']])) ?>">Editar</a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                  <?php if (!$obras_rows): ?>
                    <tr><td colspan="5" style="color:var(--muted);">Sin resultados.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

            <div class="pager">
              <div class="meta">Total: <b><?= (int)$obras_total ?></b> · Página <b><?= (int)$p_obras ?></b> / <?= (int)$obras_pages ?></div>
              <div class="actions">
                <a class="btn2" href="<?= h(qs(['obras_page' => max(1, $p_obras-1)])) ?>">←</a>
                <a class="btn2" href="<?= h(qs(['obras_page' => min($obras_pages, $p_obras+1)])) ?>">→</a>
              </div>
            </div>
          </div>

          <div>
            <div class="section-head">
              <h2 style="margin:0;"><?= $obra_edit ? 'Editar obra' : 'Crear obra' ?></h2>
              <p style="margin:6px 0 0; color:var(--muted);"><?= $obra_edit ? 'Modifica campos e imágenes.' : 'Crea una nueva obra.' ?></p>
            </div>

            <form class="formX" method="post" style="margin-top:12px;">
              <input type="hidden" name="action" value="<?= $obra_edit ? 'obra_update' : 'obra_create' ?>">
              <?php if ($obra_edit): ?>
                <input type="hidden" name="idObra" value="<?= (int)$obra_edit['idObra'] ?>">
              <?php endif; ?>

              <div>
                <label>Título *</label>
                <input name="Titulo" value="<?= h($obra_edit['Titulo'] ?? '') ?>" required>
              </div>

              <div class="rowX">
                <div>
                  <label>Autor</label>
                  <input name="Autor" value="<?= h($obra_edit['Autor'] ?? '') ?>">
                </div>
                <div>
                  <label>Año</label>
                  <input name="Anio" value="<?= h($obra_edit['Anio'] ?? '') ?>" placeholder="2024">
                </div>
              </div>

              <div>
                <label>Subtítulo</label>
                <textarea name="Subtitulo"><?= h($obra_edit['Subtitulo'] ?? '') ?></textarea>
              </div>

              <div>
                <label>URL (Dracor)</label>
                <input name="UrlDracor" value="<?= h($obra_edit['UrlDracor'] ?? '') ?>" placeholder="https://...">
              </div>

              <div class="actions">
                <button class="btn2 ok" type="submit"><?= $obra_edit ? 'Guardar cambios' : 'Crear obra' ?></button>
                <?php if ($obra_edit): ?>
                  <a class="btn2" href="<?= h(qs(['obra_edit' => null])) ?>">Nuevo</a>
                <?php endif; ?>
              </div>
            </form>

            <?php if ($obra_edit): ?>
              <form method="post" onsubmit="return confirm('¿Borrar esta obra? También se borrarán sus imágenes.');" style="margin-top:10px;">
                <input type="hidden" name="action" value="obra_delete">
                <input type="hidden" name="idObra" value="<?= (int)$obra_edit['idObra'] ?>">
                <button class="btn2 danger" type="submit">🗑️ Eliminar obra</button>
              </form>

              <div class="divider"></div>

              <h3 style="margin:0 0 6px;">Imágenes de obra</h3>
              <form class="formX" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="obra_img_add">
                <input type="hidden" name="idObra" value="<?= (int)$obra_edit['idObra'] ?>">
                <div>
                  <label>Subir imagen (jpg/png/webp, máx 5MB)</label>
                  <input type="file" name="Imagen" accept="image/*" required>
                </div>
                <button class="btn2 ok" type="submit">➕ Añadir imagen</button>
              </form>

              <div class="img-grid">
                <?php foreach ($obra_imgs as $im): ?>
                  <?php $url = !empty($im['RutaImagen']) ? rel_to_url((string)$im['RutaImagen']) : ''; ?>
                  <div class="img-card">
                    <?php if ($url): ?><img src="<?= h($url) ?>" alt="imagen obra"><?php endif; ?>
                    <div class="img-foot">
                      <small style="color:var(--muted);">#<?= (int)$im['idImagenObra'] ?></small>
                      <form method="post" onsubmit="return confirm('¿Borrar imagen?');">
                        <input type="hidden" name="action" value="obra_img_delete">
                        <input type="hidden" name="idObra" value="<?= (int)$obra_edit['idObra'] ?>">
                        <input type="hidden" name="idImagenObra" value="<?= (int)$im['idImagenObra'] ?>">
                        <button class="btn2 danger" type="submit">Eliminar</button>
                      </form>
                    </div>
                  </div>
                <?php endforeach; ?>
                <?php if (!$obra_imgs): ?>
                  <div class="noticeX" style="grid-column:1/-1; color:var(--muted);">Aún no hay imágenes.</div>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($tab === 'teatros'): ?>
      <section class="panelX glass">
        <div class="grid2">
          <div>
            <div class="section-head">
              <h2 style="margin:0;">Teatros</h2>
              <p style="margin:6px 0 0; color:var(--muted);">CRUD + imágenes · búsqueda + paginación</p>
            </div>

            <form method="get" class="filters glass" style="margin-top:12px;">
              <input type="hidden" name="tab" value="teatros">
              <div class="f-group">
                <label>Buscar</label>
                <input name="teatros_q" value="<?= h($q_teatros) ?>" placeholder="Sala, provincia o municipio...">
              </div>
              <div class="f-group">
                <label>Por página</label>
                <select name="teatros_pp">
                  <?php foreach ([10,20,30,50,100] as $v): ?>
                    <option value="<?= $v ?>" <?= $pp_teatros===$v?'selected':'' ?>><?= $v ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <button class="btn small" type="submit">Aplicar</button>
            </form>

            <div class="glass" style="margin-top:12px; overflow:hidden;">
              <table class="tableX">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Sala</th>
                    <th>Provincia</th>
                    <th>Municipio</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($teatros_rows as $r): ?>
                    <tr>
                      <td><?= (int)$r['idTeatro'] ?></td>
                      <td><b><?= h($r['Sala'] ?? '') ?></b></td>
                      <td><?= h($r['Provincia'] ?? '') ?></td>
                      <td><?= h($r['Municipio'] ?? '') ?></td>
                      <td class="actions">
                        <a class="btn2 ok" href="<?= h(qs(['teatro_edit' => (int)$r['idTeatro']])) ?>">Editar</a>
                        <a class="btn2" href="<?= h(qs(['tab' => 'horarios', 'hor_idTeatro' => (int)$r['idTeatro']])) ?>">Horarios</a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                  <?php if (!$teatros_rows): ?>
                    <tr><td colspan="5" style="color:var(--muted);">Sin resultados.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

            <div class="pager">
              <div class="meta">Total: <b><?= (int)$teatros_total ?></b> · Página <b><?= (int)$p_teatros ?></b> / <?= (int)$teatros_pages ?></div>
              <div class="actions">
                <a class="btn2" href="<?= h(qs(['teatros_page' => max(1, $p_teatros-1)])) ?>">←</a>
                <a class="btn2" href="<?= h(qs(['teatros_page' => min($teatros_pages, $p_teatros+1)])) ?>">→</a>
              </div>
            </div>
          </div>

          <div>
            <div class="section-head">
              <h2 style="margin:0;"><?= $teatro_edit ? 'Editar teatro' : 'Crear teatro' ?></h2>
              <p style="margin:6px 0 0; color:var(--muted);"><?= $teatro_edit ? 'Edita datos e imágenes.' : 'Crea un nuevo teatro.' ?></p>
            </div>

            <form class="formX" method="post" style="margin-top:12px;">
              <input type="hidden" name="action" value="<?= $teatro_edit ? 'teatro_update' : 'teatro_create' ?>">
              <?php if ($teatro_edit): ?>
                <input type="hidden" name="idTeatro" value="<?= (int)$teatro_edit['idTeatro'] ?>">
              <?php endif; ?>

              <div>
                <label>Sala *</label>
                <input name="Sala" value="<?= h($teatro_edit['Sala'] ?? '') ?>" required>
              </div>

              <div>
                <label>Entidad</label>
                <input name="Entidad" value="<?= h($teatro_edit['Entidad'] ?? '') ?>">
              </div>

              <div class="rowX">
                <div>
                  <label>Provincia *</label>
                  <input name="Provincia" value="<?= h($teatro_edit['Provincia'] ?? '') ?>" required>
                </div>
                <div>
                  <label>Municipio *</label>
                  <input name="Municipio" value="<?= h($teatro_edit['Municipio'] ?? '') ?>" required>
                </div>
              </div>

              <div>
                <label>Dirección</label>
                <input name="Direccion" value="<?= h($teatro_edit['Direccion'] ?? '') ?>">
              </div>

              <div class="rowX">
                <div>
                  <label>CP</label>
                  <input name="CP" value="<?= h($teatro_edit['CP'] ?? '') ?>">
                </div>
                <div>
                  <label>CapacidadMax *</label>
                  <input name="CapacidadMax" value="<?= h($teatro_edit['CapacidadMax'] ?? '') ?>" required>
                </div>
              </div>

              <div class="rowX">
                <div>
                  <label>Teléfono</label>
                  <input name="Telefono" value="<?= h($teatro_edit['Telefono'] ?? '') ?>">
                </div>
                <div>
                  <label>Email</label>
                  <input name="Email" value="<?= h($teatro_edit['Email'] ?? '') ?>">
                </div>
              </div>

              <div class="rowX">
                <div>
                  <label>Latitud</label>
                  <input name="Latitud" value="<?= h($teatro_edit['Latitud'] ?? '') ?>" placeholder="41.65">
                </div>
                <div>
                  <label>Longitud</label>
                  <input name="Longitud" value="<?= h($teatro_edit['Longitud'] ?? '') ?>" placeholder="-4.72">
                </div>
              </div>

              <div class="actions">
                <button class="btn2 ok" type="submit"><?= $teatro_edit ? 'Guardar cambios' : 'Crear teatro' ?></button>
                <?php if ($teatro_edit): ?>
                  <a class="btn2" href="<?= h(qs(['teatro_edit' => null])) ?>">Nuevo</a>
                <?php endif; ?>
              </div>
            </form>

            <?php if ($teatro_edit): ?>
              <form method="post" onsubmit="return confirm('¿Borrar este teatro? Se borrarán horarios e imágenes relacionadas (por FK).');" style="margin-top:10px;">
                <input type="hidden" name="action" value="teatro_delete">
                <input type="hidden" name="idTeatro" value="<?= (int)$teatro_edit['idTeatro'] ?>">
                <button class="btn2 danger" type="submit">🗑️ Eliminar teatro</button>
              </form>

              <div class="divider"></div>

              <h3 style="margin:0 0 6px;">Imágenes de teatro</h3>
              <form class="formX" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="teatro_img_add">
                <input type="hidden" name="idTeatro" value="<?= (int)$teatro_edit['idTeatro'] ?>">
                <div>
                  <label>Subir imagen (jpg/png/webp, máx 5MB)</label>
                  <input type="file" name="Imagen" accept="image/*" required>
                </div>
                <button class="btn2 ok" type="submit">➕ Añadir imagen</button>
              </form>

              <div class="img-grid">
                <?php foreach ($teatro_imgs as $im): ?>
                  <?php $url = !empty($im['RutaImagen']) ? rel_to_url((string)$im['RutaImagen']) : ''; ?>
                  <div class="img-card">
                    <?php if ($url): ?><img src="<?= h($url) ?>" alt="imagen teatro"><?php endif; ?>
                    <div class="img-foot">
                      <small style="color:var(--muted);">#<?= (int)$im['idImagenTeatro'] ?></small>
                      <form method="post" onsubmit="return confirm('¿Borrar imagen?');">
                        <input type="hidden" name="action" value="teatro_img_delete">
                        <input type="hidden" name="idTeatro" value="<?= (int)$teatro_edit['idTeatro'] ?>">
                        <input type="hidden" name="idImagenTeatro" value="<?= (int)$im['idImagenTeatro'] ?>">
                        <button class="btn2 danger" type="submit">Eliminar</button>
                      </form>
                    </div>
                  </div>
                <?php endforeach; ?>
                <?php if (!$teatro_imgs): ?>
                  <div class="noticeX" style="grid-column:1/-1; color:var(--muted);">Aún no hay imágenes.</div>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($tab === 'horarios'): ?>
      <section class="panelX glass">
        <div class="grid2">
          <div>
            <div class="section-head">
              <h2 style="margin:0;">Horarios</h2>
              <p style="margin:6px 0 0; color:var(--muted);">CRUD + paginación · filtrar por teatro</p>
            </div>

            <form method="get" class="filters glass" style="margin-top:12px;">
              <input type="hidden" name="tab" value="horarios">
              <div class="f-group">
                <label>Filtrar por teatro</label>
                <select name="hor_idTeatro">
                  <option value="0">Todos</option>
                  <?php foreach ($sel_teatros as $t): ?>
                    <?php $idT = (int)$t['idTeatro']; ?>
                    <option value="<?= $idT ?>" <?= $hor_idTeatro===$idT?'selected':'' ?>>
                      <?= h($t['Provincia'].' · '.$t['Municipio'].' · '.$t['Sala']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="f-group">
                <label>Por página</label>
                <select name="horarios_pp">
                  <?php foreach ([10,20,30,50,100] as $v): ?>
                    <option value="<?= $v ?>" <?= $pp_hor===$v?'selected':'' ?>><?= $v ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <button class="btn small" type="submit">Aplicar</button>
            </form>

            <div class="glass" style="margin-top:12px; overflow:hidden;">
              <table class="tableX">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Fecha/Hora</th>
                    <th>Teatro</th>
                    <th>Obra</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($hor_rows as $r): ?>
                    <tr>
                      <td><?= (int)$r['idHorario'] ?></td>
                      <td><?= h($r['FechaHora'] ?? '') ?></td>
                      <td>
                        <b><?= h($r['teatro'] ?? '') ?></b><br>
                        <small style="color:var(--muted);"><?= h(($r['Provincia'] ?? '').' · '.($r['Municipio'] ?? '')) ?></small>
                      </td>
                      <td>
                        <b><?= h($r['obra'] ?? '') ?></b><br>
                        <small style="color:var(--muted);"><?= h($r['autor'] ?? '') ?></small>
                      </td>
                      <td class="actions">
                        <a class="btn2 ok" href="<?= h(qs(['hor_edit' => (int)$r['idHorario']])) ?>">Editar</a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                  <?php if (!$hor_rows): ?>
                    <tr><td colspan="5" style="color:var(--muted);">Sin resultados.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

            <div class="pager">
              <div class="meta">Total: <b><?= (int)$hor_total ?></b> · Página <b><?= (int)$p_hor ?></b> / <?= (int)$hor_pages ?></div>
              <div class="actions">
                <a class="btn2" href="<?= h(qs(['horarios_page' => max(1, $p_hor-1)])) ?>">←</a>
                <a class="btn2" href="<?= h(qs(['horarios_page' => min($hor_pages, $p_hor+1)])) ?>">→</a>
              </div>
            </div>
          </div>

          <div>
            <div class="section-head">
              <h2 style="margin:0;"><?= $hor_edit ? 'Editar horario' : 'Crear horario' ?></h2>
              <p style="margin:6px 0 0; color:var(--muted);"><?= $hor_edit ? 'Actualiza la sesión.' : 'Añade una nueva sesión.' ?></p>
            </div>

            <?php
              $dtValue = '';
              if ($hor_edit && !empty($hor_edit['FechaHora'])) {
                // mysql datetime => datetime-local
                $dtValue = str_replace(' ', 'T', substr((string)$hor_edit['FechaHora'], 0, 16));
              }
            ?>

            <form class="formX" method="post" style="margin-top:12px;">
              <input type="hidden" name="action" value="<?= $hor_edit ? 'horario_update' : 'horario_create' ?>">
              <?php if ($hor_edit): ?>
                <input type="hidden" name="idHorario" value="<?= (int)$hor_edit['idHorario'] ?>">
              <?php endif; ?>

              <div>
                <label>Teatro</label>
                <select name="idTeatro" required>
                  <option value="">Selecciona...</option>
                  <?php foreach ($sel_teatros as $t): ?>
                    <?php $idT = (int)$t['idTeatro']; ?>
                    <?php $sel = $hor_edit ? ((int)$hor_edit['idTeatro'] === $idT) : ($hor_idTeatro === $idT); ?>
                    <option value="<?= $idT ?>" <?= $sel?'selected':'' ?>>
                      <?= h($t['Provincia'].' · '.$t['Municipio'].' · '.$t['Sala']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div>
                <label>Obra</label>
                <select name="idObra" required>
                  <option value="">Selecciona...</option>
                  <?php foreach ($sel_obras as $o): ?>
                    <?php $idO = (int)$o['idObra']; ?>
                    <?php $sel = $hor_edit ? ((int)$hor_edit['idObra'] === $idO) : false; ?>
                    <option value="<?= $idO ?>" <?= $sel?'selected':'' ?>><?= h($o['Titulo']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div>
                <label>Fecha/Hora</label>
                <input type="datetime-local" name="FechaHora" value="<?= h($dtValue) ?>" required>
              </div>

              <div class="actions">
                <button class="btn2 ok" type="submit"><?= $hor_edit ? 'Guardar cambios' : 'Crear horario' ?></button>
                <?php if ($hor_edit): ?>
                  <a class="btn2" href="<?= h(qs(['hor_edit' => null])) ?>">Nuevo</a>
                <?php endif; ?>
              </div>
            </form>

            <?php if ($hor_edit): ?>
              <form method="post" onsubmit="return confirm('¿Borrar este horario?');" style="margin-top:10px;">
                <input type="hidden" name="action" value="horario_delete">
                <input type="hidden" name="idHorario" value="<?= (int)$hor_edit['idHorario'] ?>">
                <button class="btn2 danger" type="submit">🗑️ Eliminar horario</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($tab === 'usuarios'): ?>
      <section class="panelX glass">
        <div class="grid2">
          <div>
            <div class="section-head">
              <h2 style="margin:0;">Usuarios</h2>
              <p style="margin:6px 0 0; color:var(--muted);">Editar / eliminar · búsqueda + paginación</p>
            </div>

            <form method="get" class="filters glass" style="margin-top:12px;">
              <input type="hidden" name="tab" value="usuarios">
              <div class="f-group">
                <label>Buscar</label>
                <input name="usuarios_q" value="<?= h($q_users) ?>" placeholder="Nombre o email...">
              </div>
              <div class="f-group">
                <label>Por página</label>
                <select name="usuarios_pp">
                  <?php foreach ([10,20,30,50,100] as $v): ?>
                    <option value="<?= $v ?>" <?= $pp_users===$v?'selected':'' ?>><?= $v ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <button class="btn small" type="submit">Aplicar</button>
            </form>

            <div class="glass" style="margin-top:12px; overflow:hidden;">
              <table class="tableX">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Puntos</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($users_rows as $r): ?>
                    <tr>
                      <td><?= (int)$r['idUsuario'] ?></td>
                      <td><b><?= h($r['Nombre'] ?? '') ?></b></td>
                      <td><?= h($r['Email'] ?? '') ?></td>
                      <td><?= (int)($r['Puntos'] ?? 0) ?></td>
                      <td class="actions">
                        <a class="btn2 ok" href="<?= h(qs(['user_edit' => (int)$r['idUsuario']])) ?>">Editar</a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                  <?php if (!$users_rows): ?>
                    <tr><td colspan="5" style="color:var(--muted);">Sin resultados.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

            <div class="pager">
              <div class="meta">Total: <b><?= (int)$users_total ?></b> · Página <b><?= (int)$p_users ?></b> / <?= (int)$users_pages ?></div>
              <div class="actions">
                <a class="btn2" href="<?= h(qs(['usuarios_page' => max(1, $p_users-1)])) ?>">←</a>
                <a class="btn2" href="<?= h(qs(['usuarios_page' => min($users_pages, $p_users+1)])) ?>">→</a>
              </div>
            </div>
          </div>

          <div>
            <div class="section-head">
              <h2 style="margin:0;"><?= $user_edit ? 'Editar usuario' : 'Selecciona un usuario' ?></h2>
              <p style="margin:6px 0 0; color:var(--muted);"><?= $user_edit ? 'Actualiza datos o elimina.' : 'Usa “Editar” en la tabla.' ?></p>
            </div>

            <?php if ($user_edit): ?>
              <form class="formX" method="post" style="margin-top:12px;">
                <input type="hidden" name="action" value="usuario_update">
                <input type="hidden" name="idUsuario" value="<?= (int)$user_edit['idUsuario'] ?>">

                <div>
                  <label>Nombre</label>
                  <input name="Nombre" value="<?= h($user_edit['Nombre'] ?? '') ?>" required>
                </div>

                <div>
                  <label>Email</label>
                  <input name="Email" value="<?= h($user_edit['Email'] ?? '') ?>" required>
                </div>

                <div>
                  <label>Puntos</label>
                  <input name="Puntos" value="<?= h($user_edit['Puntos'] ?? 0) ?>">
                </div>

                <button class="btn2 ok" type="submit">Guardar cambios</button>
              </form>

              <form method="post" onsubmit="return confirm('¿Borrar este usuario? Se eliminarán sus visitas y galería (por FK).');" style="margin-top:10px;">
                <input type="hidden" name="action" value="usuario_delete">
                <input type="hidden" name="idUsuario" value="<?= (int)$user_edit['idUsuario'] ?>">
                <button class="btn2 danger" type="submit">🗑️ Eliminar usuario</button>
              </form>

              <div style="margin-top:14px;">
                <?php if (!empty($user_edit['FotoPerfil'])): ?>
                  <div class="noticeX" style="color:var(--muted);">
                    FotoPerfil en BD: <b><?= h((string)$user_edit['FotoPerfil']) ?></b>
                  </div>
                <?php endif; ?>
              </div>

            <?php else: ?>
              <div class="noticeX" style="color:var(--muted); margin-top:12px;">
                Selecciona un usuario desde la tabla (botón <b>Editar</b>) para modificarlo o eliminarlo.
              </div>
            <?php endif; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($tab === 'galeria'): ?>
      <section class="panelX glass">
        <div class="grid2">
          <div>
            <div class="section-head">
              <h2 style="margin:0;">Galería de revisión</h2>
              <p style="margin:6px 0 0; color:var(--muted);">Cambiar estado (pendiente/aprobada/rechazada) · borrar · paginación</p>
            </div>

            <form method="get" class="filters glass" style="margin-top:12px;">
              <input type="hidden" name="tab" value="galeria">

              <div class="f-group">
                <label>Estado</label>
                <select name="gal_estado">
                  <?php
                    $estados = [
                      'pendiente' => 'Pendiente',
                      'aprobada'  => 'Aprobada',
                      'rechazada' => 'Rechazada',
                      ''          => 'Todas'
                    ];
                  ?>
                  <?php foreach ($estados as $k => $lbl): ?>
                    <option value="<?= h($k) ?>" <?= $gal_estado===$k?'selected':'' ?>><?= h($lbl) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="f-group">
                <label>Por página</label>
                <select name="galeria_pp">
                  <?php foreach ([10,20,30,50,100] as $v): ?>
                    <option value="<?= $v ?>" <?= $pp_gal===$v?'selected':'' ?>><?= $v ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <button class="btn small" type="submit">Aplicar</button>
            </form>

            <div class="glass" style="margin-top:12px; overflow:hidden;">
              <table class="tableX">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Imagen</th>
                    <th>Usuario</th>
                    <th>Teatro</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($gal_rows as $r): ?>
                    <?php
                      $imgRel = (string)($r['RutaImagen'] ?? '');
                      $imgUrl = $imgRel !== '' ? rel_to_url($imgRel) : '';
                    ?>
                    <tr>
                      <td><?= (int)$r['idImagen'] ?></td>
                      <td>
                        <?php if ($imgUrl): ?>
                          <a class="btn2" href="<?= h($imgUrl) ?>" target="_blank" rel="noopener">Ver</a>
                        <?php else: ?>
                          <span style="color:var(--muted);">—</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <b><?= h($r['usuario'] ?? '') ?></b><br>
                        <small style="color:var(--muted);"><?= h($r['emailUsuario'] ?? '') ?></small>
                      </td>
                      <td>
                        <b><?= h($r['teatro'] ?? '') ?></b><br>
                        <small style="color:var(--muted);"><?= h(($r['Provincia'] ?? '').' · '.($r['Municipio'] ?? '')) ?></small>
                      </td>
                      <td><span class="chipX"><?= h($r['Estado'] ?? '') ?></span></td>
                      <td><small style="color:var(--muted);"><?= h($r['FechaSubida'] ?? '') ?></small></td>
                      <td class="actions">
                        <form method="post" style="display:inline-flex; gap:8px; flex-wrap:wrap;">
                          <input type="hidden" name="action" value="galeria_set_estado">
                          <input type="hidden" name="idImagen" value="<?= (int)$r['idImagen'] ?>">
                          <select name="Estado" style="padding:8px 10px; border-radius:12px;">
                            <option value="pendiente" <?= ($r['Estado'] ?? '')==='pendiente'?'selected':'' ?>>Pendiente</option>
                            <option value="aprobada"  <?= ($r['Estado'] ?? '')==='aprobada'?'selected':'' ?>>Aprobada</option>
                            <option value="rechazada" <?= ($r['Estado'] ?? '')==='rechazada'?'selected':'' ?>>Rechazada</option>
                          </select>
                          <button class="btn2 ok" type="submit">Guardar</button>
                        </form>

                        <form method="post" onsubmit="return confirm('¿Borrar esta imagen de la galería?');" style="display:inline-flex;">
                          <input type="hidden" name="action" value="galeria_delete">
                          <input type="hidden" name="idImagen" value="<?= (int)$r['idImagen'] ?>">
                          <button class="btn2 danger" type="submit">Eliminar</button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>

                  <?php if (!$gal_rows): ?>
                    <tr><td colspan="7" style="color:var(--muted);">Sin resultados.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

            <div class="pager">
              <div class="meta">Total: <b><?= (int)$gal_total ?></b> · Página <b><?= (int)$p_gal ?></b> / <?= (int)$gal_pages ?></div>
              <div class="actions">
                <a class="btn2" href="<?= h(qs(['galeria_page' => max(1, $p_gal-1)])) ?>">←</a>
                <a class="btn2" href="<?= h(qs(['galeria_page' => min($gal_pages, $p_gal+1)])) ?>">→</a>
              </div>
            </div>
          </div>

          <div>
            <div class="section-head">
              <h2 style="margin:0;">Vista previa</h2>
              <p style="margin:6px 0 0; color:var(--muted);">Abre “Ver” para comprobar la imagen en una pestaña nueva.</p>
            </div>

            <div class="noticeX" style="margin-top:12px; color:var(--muted);">
              Consejo: guarda en BD rutas relativas tipo <b>fotosSubidasUsuarios/...</b> para que <code>rel_to_url()</code>
              las convierta a URL correctamente.
            </div>
          </div>
        </div>
      </section>
    <?php endif; ?>

  </div>
</body>
</html>

