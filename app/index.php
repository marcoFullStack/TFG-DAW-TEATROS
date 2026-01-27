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

<style>
  .map-wrap{ padding: 18px 0 60px; }
  .map-card{ padding: 16px; }
  #mapTeatros{
    width: 100%;
    height: 520px;
    border-radius: 18px;
    border: 1px solid rgba(255,255,255,.14);
    overflow: hidden;
  }
  @media (max-width: 980px){
    #mapTeatros{ height: 420px; }
  }
</style>

<main class="page">
  <!-- Fondo teatral -->
  <div class="bg" aria-hidden="true">
    <div class="curtain left"></div>
    <div class="curtain right"></div>
    <div class="spot s1"></div>
    <div class="spot s2"></div>
    <div class="grain"></div>
  </div>

  <!-- HERO -->
  <section class="hero" id="inicio">
    <div class="container hero-grid">
      <!-- HERO izquierdo -->
      <div class="hero-left reveal">
        <div class="kicker">
          <span class="mask">🎭</span>
          <span>Red de Teatros · Castilla y León</span>
        </div>

        <h1 class="title">
          Un inicio clásico para una
          <span class="gold">experiencia cultural</span> moderna
        </h1>

        <p class="lead">
          Descubre teatros por provincia y consulta la cartelera. Filtra al instante, guarda ideas y
          navega con una interfaz cuidada, tradicional y elegante.
        </p>

        <div class="stats">
          <div class="stat">
            <div class="num" data-count="<?= (int)$totalTeatros ?>" data-fallback="25">0</div>
            <div class="lbl">Teatros</div>
          </div>
          <div class="stat">
            <div class="num" data-count="<?= (int)$totalObras ?>" data-fallback="300">0</div>
            <div class="lbl">Obras</div>
          </div>
          <div class="stat">
            <div class="num" data-count="9" data-fallback="9">0</div>
            <div class="lbl">Provincias</div>
          </div>
        </div>

        <div class="cta">
          <a class="btn primary" href="#explorar">Explorar</a>
          <a class="btn ghost" href="#cartelera">Ver cartelera</a>
          <a class="btn ghost" href="#mapa">Ver mapa</a>
        </div>

        <div class="divider"></div>

        <!-- Filtros -->
        <div class="filters glass">
          <div class="f-group">
            <label for="provincia">Provincia</label>
            <select id="provincia">
              <option value="">Todas</option>
              <?php foreach ($provincias as $p): ?>
                <option value="<?= h($p) ?>"><?= h($p) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="f-group">
            <label for="buscar">Buscar</label>
            <input id="buscar" type="text" placeholder="Teatro, municipio, obra, autor…">
          </div>

          <button class="btn small" id="btnReset" type="button">Reset</button>
        </div>
      </div>

      <!-- Panel derecho -->
      <aside class="hero-right reveal" id="cartelera">
        <div class="panel glass">
          <div class="panel-head">
            <h2>Destacados</h2>
            <p>Próximas funciones (según horarios)</p>
          </div>

          <div class="panel-list" id="listDestacados">
            <?php if (count($cartelera) === 0): ?>
              <div class="empty">
                <strong>Aún no hay cartelera cargada.</strong>
                <span>En cuanto tengas datos en <code>horarios</code> aparecerán aquí.</span>
              </div>
            <?php else: ?>
              <?php foreach ($cartelera as $c): ?>
                <?php
                  $img = $c['img'] ?: '';
                  $fecha = date('d/m/Y H:i', strtotime($c['FechaHora']));
                ?>
                <a class="mini-card"
                   href="index2.php?obra=<?= (int)$c['idObra'] ?>&teatro=<?= (int)$c['idTeatro'] ?>"
                   data-provincia="<?= h($c['Provincia']) ?>"
                   data-search="<?= h(mb_strtolower($c['titulo'].' '.$c['autor'].' '.$c['teatro'].' '.$c['Municipio'].' '.$c['Provincia'])) ?>">
                  <div class="thumb" style="<?= $img ? "background-image:url('".h($img)."')" : "" ?>"></div>
                  <div class="mini-body">
                    <div class="mini-top">
                      <span class="tag"><?= h($c['Provincia']) ?></span>
                      <span class="time"><?= h($fecha) ?></span>
                    </div>
                    <div class="mini-title"><?= h($c['titulo']) ?></div>
                    <div class="mini-sub"><?= h($c['teatro']) ?> · <?= h($c['Municipio']) ?></div>
                  </div>
                </a>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <div class="panel-foot">
            <a class="btn ghost w-100" href="#explorar">Ver todo abajo</a>
          </div>
        </div>
      </aside>
    </div>
  </section>

  <!-- SECCIÓN CARDS -->
  <section class="cards" id="explorar">
    <div class="container">
      <div class="section-head reveal">
        <h2>Explorar catálogo</h2>
        <p>Alterna entre teatros y cartelera. Filtra sin recargar la página.</p>
      </div>

      <!-- Tabs -->
      <div class="tabs reveal" role="tablist" aria-label="Explorar">
        <button class="tab active" data-tab="teatros" role="tab" aria-selected="true">Teatros</button>
        <button class="tab" data-tab="cartelera" role="tab" aria-selected="false">Cartelera</button>
      </div>

      <!-- TEATROS -->
      <div class="grid tab-panel active" data-panel="teatros" id="gridTeatros">
        <?php if (count($teatros) === 0): ?>
          <div class="empty big">
            <strong>No hay teatros listados.</strong>
            <span>Revisa que exista la tabla <code>teatros</code> y que `app/config/db.php` conecte bien.</span>
          </div>
        <?php else: ?>
          <?php foreach ($teatros as $t): ?>
            <?php
              $img = $t['img'] ?: '';
              $search = mb_strtolower(($t['Sala'] ?? '').' '.($t['Municipio'] ?? '').' '.($t['Provincia'] ?? ''));
            ?>
            <article class="card reveal"
              data-provincia="<?= h($t['Provincia'] ?? '') ?>"
              data-search="<?= h($search) ?>">
              <div class="cover" style="<?= $img ? "background-image:url('".h($img)."')" : "" ?>">
                <div class="cover-overlay"></div>
                <div class="chip"><?= h($t['Provincia'] ?? '') ?></div>
              </div>
              <div class="body">
                <h3><?= h($t['Sala'] ?? 'Teatro') ?></h3>
                <p class="meta"><?= h($t['Municipio'] ?? '') ?> · <?= h($t['Direccion'] ?? '') ?></p>
                <div class="row">
                  <span class="pill2">Aforo: <?= h($t['CapacidadMax'] ?? '—') ?></span>
                  <a class="link" href="index2.php?teatro=<?= (int)$t['id'] ?>">Ver detalles →</a>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- CARTELERA -->
      <div class="grid tab-panel" data-panel="cartelera" id="gridCartelera">
        <?php if (count($cartelera) === 0): ?>
          <div class="empty big">
            <strong>Sin funciones próximas.</strong>
            <span>Cuando insertes sesiones en <code>horarios</code> (idTeatro, idObra, FechaHora) se mostrará aquí.</span>
          </div>
        <?php else: ?>
          <?php foreach ($cartelera as $c): ?>
            <?php
              $img = $c['img'] ?: '';
              $fecha = date('d/m/Y H:i', strtotime($c['FechaHora']));
              $search = mb_strtolower(($c['titulo'] ?? '').' '.($c['autor'] ?? '').' '.($c['teatro'] ?? '').' '.($c['Municipio'] ?? '').' '.($c['Provincia'] ?? ''));
            ?>
            <article class="card reveal"
              data-provincia="<?= h($c['Provincia'] ?? '') ?>"
              data-search="<?= h($search) ?>">
              <div class="cover poster" style="<?= $img ? "background-image:url('".h($img)."')" : "" ?>">
                <div class="cover-overlay"></div>
                <div class="chip"><?= h($c['Provincia'] ?? '') ?></div>
              </div>
              <div class="body">
                <h3><?= h($c['titulo'] ?? 'Obra') ?></h3>
                <p class="meta"><?= h($c['autor'] ?? 'Autor desconocido') ?><?= $c['anio'] ? ' · '.h($c['anio']) : '' ?></p>
                <p class="meta2"><?= h($c['teatro'] ?? '') ?> · <?= h($c['Municipio'] ?? '') ?></p>
                <div class="row">
                  <span class="pill2">🗓 <?= h($fecha) ?></span>
                  <a class="link" href="<?= h($c['url'] ?? '#') ?>" target="_blank" rel="noopener">Ficha →</a>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Bloque info -->
      <div class="info reveal">
        <div class="info-card glass">
          <h3>Experiencia “tradicional”</h3>
          <p>
            Inspirada en carteleras clásicas: tipografía elegante, tonos terciopelo y detalles dorados,
            con transiciones suaves y filtros rápidos.
          </p>
        </div>
        <div class="info-card glass">
          <h3>Interacción moderna</h3>
          <p>
            Filtrado instantáneo en cliente (sin recargar), animaciones al scroll y microinteracciones
            en cards y botones.
          </p>
        </div>
        <div class="info-card glass">
          <h3>Lista para crecer</h3>
          <p>
            Puedes conectar un endpoint con <code>fetch()</code> para paginar o cargar más datos (cuando quieras te lo monto).
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- MAPA -->
  <section class="map-wrap" id="mapa">
    <div class="container">
      <div class="section-head reveal">
        <h2>Mapa de teatros</h2>
        <p>Ubicación de los teatros (datos desde <code>red_teatros.json</code>).</p>
      </div>

      <div class="map-card glass reveal">
        <div id="mapTeatros" aria-label="Mapa de teatros"></div>
      </div>
    </div>
  </section>

  <button class="toTop" id="toTop" aria-label="Volver arriba">↑</button>
</main>

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
