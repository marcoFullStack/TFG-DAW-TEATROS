<?php

require_once __DIR__ . '/config/db.php';

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

if (!defined('BASE_URL')) {
  define('BASE_URL', '/TFG-DAW-TEATROS/app/');
}
?>
<?php include_once __DIR__ . '/inc/header.php'; ?>

<main class="page">
  <div class="bg" aria-hidden="true">
    <div class="curtain left"></div>
    <div class="curtain right"></div>
    <div class="spot s1"></div>
    <div class="spot s2"></div>
    <div class="grain"></div>
  </div>

  <section class="hero" id="inicio">
    <div class="container">
      <div class="hero-left reveal">
        <div class="kicker">
          <span class="mask">✨</span>
          <span>Servicios · Red de Teatros</span>
        </div>

        <h1 class="title">
          Lo que ofrecemos a la
          <span class="gold">comunidad teatral</span>
        </h1>

        <p class="lead">
          Nuestra plataforma conecta teatros, obras y público en Castilla y León. 
          Descubre cómo facilitamos el acceso a la cultura y promovemos el teatro en nuestra región.
        </p>

        <div class="divider"></div>
      </div>
    </div>
  </section>

  <!-- SERVICIOS PRINCIPALES -->
  <section class="cards" style="padding: 40px 0 60px;">
    <div class="container">
      <div class="section-head reveal" style="text-align: center; margin-bottom: 30px;">
        <h2 style="font-size: 28px;">Nuestros Servicios</h2>
        <p>Herramientas y recursos para disfrutar del teatro al máximo</p>
      </div>

      <div class="grid reveal" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        <!-- Servicio 1: Catálogo -->
        <article class="card" style="border-radius: 20px;">
          <div class="cover" style="height: 180px; background: radial-gradient(circle at 30% 30%, rgba(214,181,109,.35), rgba(123,27,42,.18)); display: flex; align-items: center; justify-content: center;">
            <div style="font-size: 64px; opacity: 0.9;">🎭</div>
          </div>
          <div class="body">
            <h3>Catálogo Completo</h3>
            <p class="meta">
              Accede a la información de todos los teatros de Castilla y León. 
              Consulta ubicaciones, capacidades, características y horarios de forma fácil y rápida.
            </p>
            <div class="row">
              <span class="pill2">📍 9 provincias</span>
              <a class="link" href="index.php#explorar">Explorar →</a>
            </div>
          </div>
        </article>

        <!-- Servicio 2: Cartelera -->
        <article class="card" style="border-radius: 20px;">
          <div class="cover" style="height: 180px; background: radial-gradient(circle at 30% 30%, rgba(161,38,59,.35), rgba(123,27,42,.18)); display: flex; align-items: center; justify-content: center;">
            <div style="font-size: 64px; opacity: 0.9;">🎬</div>
          </div>
          <div class="body">
            <h3>Cartelera Actualizada</h3>
            <p class="meta">
              Mantente al día con las próximas funciones teatrales. Filtra por provincia, 
              teatro u obra, y no te pierdas ninguna representación que te interese.
            </p>
            <div class="row">
              <span class="pill2">🗓️ En tiempo real</span>
              <a class="link" href="index.php#cartelera">Ver cartelera →</a>
            </div>
          </div>
        </article>

        <!-- Servicio 3: Mapa Interactivo -->
        <article class="card" style="border-radius: 20px;">
          <div class="cover" style="height: 180px; background: radial-gradient(circle at 30% 30%, rgba(214,181,109,.35), rgba(70,10,20,.18)); display: flex; align-items: center; justify-content: center;">
            <div style="font-size: 64px; opacity: 0.9;">🗺️</div>
          </div>
          <div class="body">
            <h3>Mapa de Teatros</h3>
            <p class="meta">
              Visualiza la ubicación de todos los teatros en un mapa interactivo. 
              Encuentra el teatro más cercano a ti y planifica tu visita con facilidad.
            </p>
            <div class="row">
              <span class="pill2">📌 Geolocalizado</span>
              <a class="link" href="index.php#mapa">Ver mapa →</a>
            </div>
          </div>
        </article>

        <!-- Servicio 4: Comunidad -->
        <article class="card" style="border-radius: 20px;">
          <div class="cover" style="height: 180px; background: radial-gradient(circle at 30% 30%, rgba(241,212,138,.25), rgba(123,27,42,.18)); display: flex; align-items: center; justify-content: center;">
            <div style="font-size: 64px; opacity: 0.9;">👥</div>
          </div>
          <div class="body">
            <h3>Comunidad Teatral</h3>
            <p class="meta">
              Comparte tus experiencias teatrales, sube fotos de tus visitas, 
              participa en valoraciones y conecta con otros amantes del teatro.
            </p>
            <div class="row">
              <span class="pill2">⭐ Social</span>
              <a class="link" href="views/user/register.php">Unirse →</a>
            </div>
          </div>
        </article>

        <!-- Servicio 5: Estadísticas -->
        <article class="card" style="border-radius: 20px;">
          <div class="cover" style="height: 180px; background: radial-gradient(circle at 30% 30%, rgba(161,38,59,.35), rgba(70,10,20,.18)); display: flex; align-items: center; justify-content: center;">
            <div style="font-size: 64px; opacity: 0.9;">📊</div>
          </div>
          <div class="body">
            <h3>Datos y Análisis</h3>
            <p class="meta">
              Accede a estadísticas sobre obras, autores y épocas teatrales. 
              Descubre tendencias y explora el patrimonio cultural de nuestra región.
            </p>
            <div class="row">
              <span class="pill2">📈 Insights</span>
              <a class="link" href="index.php#estadisticas">Ver datos →</a>
            </div>
          </div>
        </article>

        <!-- Servicio 6: Búsqueda Avanzada -->
        <article class="card" style="border-radius: 20px;">
          <div class="cover" style="height: 180px; background: radial-gradient(circle at 30% 30%, rgba(214,181,109,.35), rgba(123,27,42,.18)); display: flex; align-items: center; justify-content: center;">
            <div style="font-size: 64px; opacity: 0.9;">🔍</div>
          </div>
          <div class="body">
            <h3>Búsqueda Inteligente</h3>
            <p class="meta">
              Encuentra exactamente lo que buscas con nuestros filtros avanzados. 
              Busca por teatro, obra, autor, municipio o provincia sin recargar la página.
            </p>
            <div class="row">
              <span class="pill2">⚡ Instantáneo</span>
              <a class="link" href="index.php#filtro">Buscar →</a>
            </div>
          </div>
        </article>
      </div>

      <!-- Información adicional -->
      <div class="info reveal" style="margin-top: 40px;">
        <div class="info-card glass">
          <h3>Para el Público</h3>
          <p>
            Encuentra fácilmente obras de teatro cerca de ti, descubre nuevas producciones 
            y planifica tus salidas culturales con toda la información necesaria.
          </p>
        </div>
        <div class="info-card glass">
          <h3>Para los Teatros</h3>
          <p>
            Aumenta la visibilidad de tu teatro y tus producciones. Llega a más público 
            y facilita que los espectadores encuentren tu programación.
          </p>
        </div>
        <div class="info-card glass">
          <h3>Para la Cultura</h3>
          <p>
            Promovemos el acceso a la cultura teatral en Castilla y León, preservamos 
            el patrimonio escénico y fomentamos la participación cultural.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- LLAMADA A LA ACCIÓN -->
  <section style="padding: 50px 0 80px;">
    <div class="container">
      <div class="glass reveal" style="padding: 40px 30px; text-align: center; max-width: 800px; margin: 0 auto;">
        <h2 style="margin: 0 0 16px; font-size: 26px;">¿Listo para explorar el teatro?</h2>
        <p style="color: var(--muted); margin: 0 0 24px; font-size: 16px; line-height: 1.6;">
          Comienza a descubrir todos los teatros y obras disponibles en Castilla y León. 
          Tu próxima experiencia cultural está a un clic de distancia.
        </p>
        <div class="cta" style="justify-content: center;">
          <a class="btn primary" href="index.php#explorar">Explorar Catálogo</a>
          <a class="btn ghost" href="index.php#mapa">Ver en el Mapa</a>
        </div>
      </div>
    </div>
  </section>

  <button class="toTop" id="toTop" aria-label="Volver arriba">↑</button>
</main>

<script src="js/indexMain.js?v=1"></script>

<?php include_once __DIR__ . '/inc/footer.php'; ?>