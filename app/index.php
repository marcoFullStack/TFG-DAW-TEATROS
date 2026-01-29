<?php
// app/index2.php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/DAO/HomeDAO.php';

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// Datos desde DAO
$provincias = dao_getProvincias($pdo);
$teatros    = dao_getTeatrosDestacados($pdo, null);
$cartelera  = dao_getCartelera($pdo, null);

$totalTeatros = dao_countTeatros($pdo);
$totalObras   = dao_countObras($pdo);


//borrar si eso
// app/index.php (al principio)
$provincias = dao_getProvincias($pdo);
$teatros    = dao_getTeatrosDestacados($pdo, null);
$cartelera  = dao_getCartelera($pdo, null);

// --- NUEVA LÍNEA ---
$galeriaHome = dao_getGaleriaAleatoria($pdo, 4);
// -------------------

$totalTeatros = dao_countTeatros($pdo);
// ... resto del código
//hasta aqui

// URL JSON (está 1 nivel por encima de /app/)
if (!defined('BASE_URL')) {
  define('BASE_URL', '/TFG-DAW-TEATROS/app/');
}
$jsonUrl = BASE_URL . "../red_teatros.json";
?>
<?php include_once __DIR__ . '/inc/header.php'; ?>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
  integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

<main class="page">
  <!-- Fondo teatral -->
  <div class="bg" aria-hidden="true">
    <div class="curtain left"></div>
    <div class="curtain right"></div>
    <div class="spot s1"></div>
    <div class="spot s2"></div>
    <div class="grain"></div>
  </div>

  <?php include_once __DIR__ . '/views/index/hero.php'; ?>

  <?php include_once __DIR__ . '/views/index/cards.php'; ?>
  
  <?php include_once __DIR__ . '/views/index/estadistics.php'; ?>

  <?php include_once __DIR__ . '/views/index/galery.php'; ?>

  <?php include_once __DIR__ . '/views/index/maps.php'; ?>

  <?php include_once __DIR__ . '/views/index/maps.php'; ?>
<!-- Chat bubble -->
<button id="chatFab" aria-label="Abrir chat">💬</button>

<div id="chatBox" class="chat-hidden" role="dialog" aria-label="Asistente IA">
  <div class="chat-header">
    <div>
      <strong>Asistente Red Teatros</strong><br>
      <small>Pregúntame por teatros, obras, horarios, ranking…</small>
    </div>
    <button id="chatClose" aria-label="Cerrar">✕</button>
  </div>

  <div id="chatMessages" class="chat-messages"></div>

  <form id="chatForm" class="chat-form" autocomplete="off">
    <input id="chatInput" type="text" placeholder="Ej: teatros en Valladolid" required />
    <button type="submit">Enviar</button>
  </form>
</div>

<link rel="stylesheet" href="./styles/chat.css">
<script defer src="./js/chatbot.js"></script>

  <button class="toTop" id="toTop" aria-label="Volver arriba">↑</button>
</main>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="js/dashboardStats.js?v=1"></script>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
  integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<!-- Pasamos variables PHP a JS -->
<script>
  window.__TEATROS_JSON_URL__ = <?= json_encode($jsonUrl) ?>;
</script>

<!-- Tu JS externo -->
<script src="js/indexMain.js?v=1"></script>

<?php include_once __DIR__ . '/inc/footer.php'; ?>
