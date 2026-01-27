<?php
// index.php
require_once __DIR__ . '/config/db.php'; // crea $pdo con getConexion()

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// Provincias para filtro
$provincias = [];
try {
  $provincias = $pdo->query("SELECT DISTINCT Provincia FROM teatros ORDER BY Provincia")->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) { $provincias = []; }

// Teatros destacados (con 1 imagen si existe)
$teatros = [];
try {
$sql = "
  SELECT
    t.idTeatro AS id, t.Sala, t.Provincia, t.Municipio, t.Direccion, t.CapacidadMax,
    (SELECT RutaImagen
     FROM imagenes_teatros it
     WHERE it.idTeatro = t.idTeatro
     ORDER BY it.idImagenTeatro ASC
     LIMIT 1) AS img
  FROM teatros t
  ORDER BY t.Provincia, t.Municipio, t.Sala
  LIMIT 12
";
$teatros = $pdo->query($sql)->fetchAll();

} catch (Throwable $e) { $teatros = []; }

// Cartelera próxima (join horarios + teatros + obras + imagen obra)
$cartelera = [];
try {
 $sql = "
  SELECT
    h.idHorario,
    h.FechaHora,
    t.idTeatro AS idTeatro, t.Sala AS teatro, t.Provincia, t.Municipio,
    o.idObra AS idObra, o.Titulo AS titulo, o.Autor AS autor, o.Anio AS anio, o.UrlDracor AS url,
    (SELECT RutaImagen
     FROM imagenes_obras io
     WHERE io.idObra = o.idObra
     ORDER BY io.idImagenObra ASC
     LIMIT 1) AS img
  FROM horarios h
  INNER JOIN teatros t ON t.idTeatro = h.idTeatro
  INNER JOIN obras o   ON o.idObra   = h.idObra
  ORDER BY h.FechaHora ASC
  LIMIT 12
";
$cartelera = $pdo->query($sql)->fetchAll();

  $cartelera = $pdo->query($sql)->fetchAll();
} catch (Throwable $e) { $cartelera = []; }

// Contadores (para numeritos animados)
$totalTeatros = 0;
$totalObras   = 0;
try { $totalTeatros = (int)$pdo->query("SELECT COUNT(*) FROM teatros")->fetchColumn(); } catch (Throwable $e) {}
try { $totalObras   = (int)$pdo->query("SELECT COUNT(*) FROM obras")->fetchColumn(); } catch (Throwable $e) {}
?>
<?php include_once __DIR__ . '/inc/header.php'; ?>


<main class="page">
  <!-- Fondo teatral -->
  <div class="bg" aria-hidden="true">
    <div class="curtain left"></div>
    <div class="curtain right"></div>
    <div class="spot s1"></div>
    <div class="spot s2"></div>
    <div class="grain"></div>
  </div>

  <!-- HERO (mockup: hero + panel derecho) -->
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
            <div class="num" data-count="<?= (int)$totalTeatros ?>" data-fallback="120">0</div>
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
        </div>

        <div class="divider"></div>

        <!-- Filtros (preferencia del usuario) -->
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

      <!-- Panel derecho (en vez de login: “Destacados de hoy”) -->
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

  <!-- SECCIÓN GRANDE “cards, info, etc” (mockup) -->
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

      <!-- Bloque “info” -->
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

  <button class="toTop" id="toTop" aria-label="Volver arriba">↑</button>
</main>

<script>
(() => {
  // --- Reveal on scroll ---
  const io = new IntersectionObserver((entries) => {
    for (const e of entries) if (e.isIntersecting) e.target.classList.add('in');
  }, { threshold: 0.12 });
  document.querySelectorAll('.reveal').forEach(el => io.observe(el));

  // --- Parallax suave (solo decor) ---
  const spots = document.querySelectorAll('.spot');
  window.addEventListener('mousemove', (ev) => {
    const x = (ev.clientX / window.innerWidth) - 0.5;
    const y = (ev.clientY / window.innerHeight) - 0.5;
    spots.forEach((s, i) => {
      const k = (i+1) * 10;
      s.style.transform = `translate(${x*k}px, ${y*k}px)`;
    });
  }, { passive: true });

  // --- Contadores animados ---
  const counters = document.querySelectorAll('.num');
  const animateCount = (el) => {
    const target = parseInt(el.dataset.count || el.dataset.fallback || "0", 10);
    const start = 0;
    const duration = 900;
    const t0 = performance.now();
    const step = (t) => {
      const p = Math.min(1, (t - t0) / duration);
      const val = Math.floor(start + (target - start) * (1 - Math.pow(1 - p, 3)));
      el.textContent = val.toLocaleString('es-ES');
      if (p < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
  };
  const ioCount = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting && !e.target.dataset.done) {
        e.target.dataset.done = "1";
        animateCount(e.target);
      }
    });
  }, { threshold: 0.6 });
  counters.forEach(c => ioCount.observe(c));

  // --- Tabs (Teatros / Cartelera) ---
  const tabs = document.querySelectorAll('.tab');
  const panels = document.querySelectorAll('.tab-panel');
  tabs.forEach(btn => btn.addEventListener('click', () => {
    tabs.forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    const name = btn.dataset.tab;
    panels.forEach(p => p.classList.toggle('active', p.dataset.panel === name));
  }));

  // --- Filtro instantáneo ---
  const provincia = document.getElementById('provincia');
  const buscar = document.getElementById('buscar');
  const btnReset = document.getElementById('btnReset');

  const applyFilter = () => {
    const p = (provincia.value || "").toLowerCase();
    const q = (buscar.value || "").trim().toLowerCase();

    const filterNode = (node) => {
      const np = (node.dataset.provincia || "").toLowerCase();
      const ns = (node.dataset.search || "").toLowerCase();
      const okP = !p || np === p;
      const okQ = !q || ns.includes(q);
      node.style.display = (okP && okQ) ? "" : "none";
    };

    document.querySelectorAll('#gridTeatros .card, #gridCartelera .card, #listDestacados .mini-card')
      .forEach(filterNode);
  };

  [provincia, buscar].forEach(el => el && el.addEventListener('input', applyFilter));
  btnReset?.addEventListener('click', () => {
    provincia.value = "";
    buscar.value = "";
    applyFilter();
  });

  // --- Botón arriba ---
  const toTop = document.getElementById('toTop');
  const toggleTop = () => toTop.classList.toggle('show', window.scrollY > 600);
  window.addEventListener('scroll', toggleTop, { passive: true });
  toggleTop();
  toTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
})();
</script>

<?php include_once __DIR__ . '/inc/footer.php'; ?>
