<?php


require_once __DIR__ . '/config/db.php';

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

if (!defined('BASE_URL')) {
  define('BASE_URL', '/TFG-DAW-TEATROS/app/');
}
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

  <!-- HERO Nosotros -->
  <section class="hero" id="inicio">
    <div class="container">
      <div class="hero-left reveal">
        <div class="kicker">
          <span class="mask">💼</span>
          <span>Sobre Nosotros · Red de Teatros</span>
        </div>

        <h1 class="title">
          Conectando el teatro con
          <span class="gold">las personas</span>
        </h1>

        <p class="lead">
          Somos una plataforma dedicada a promover y facilitar el acceso a la cultura teatral 
          en Castilla y León. Nuestra misión es acercar el teatro a todos los públicos y 
          apoyar a los espacios escénicos de nuestra comunidad.
        </p>

        <div class="stats">
          <div class="stat">
            <div class="num" data-count="2026" data-fallback="2026">2026</div>
            <div class="lbl">Año de inicio</div>
          </div>
          <div class="stat">
            <div class="num" data-count="9" data-fallback="9">9</div>
            <div class="lbl">Provincias</div>
          </div>
          <div class="stat">
            <div class="num" data-count="100" data-fallback="100">100%</div>
            <div class="lbl">Dedicación</div>
          </div>
        </div>

        <div class="divider"></div>
      </div>
    </div>
  </section>

  <!-- NUESTRA HISTORIA -->
  <section class="cards" style="padding: 40px 0 60px;">
    <div class="container">
      <div class="section-head reveal" style="text-align: center; margin-bottom: 30px;">
        <h2 style="font-size: 28px;">Nuestra Historia</h2>
        <p>El origen de este proyecto cultural</p>
      </div>

      <div class="glass reveal" style="padding: 30px; max-width: 900px; margin: 0 auto 40px;">
        <p style="color: var(--muted); line-height: 1.8; margin: 0 0 16px; font-size: 16px;">
          La Red de Teatros de Castilla y León nace de la necesidad de centralizar y facilitar 
          el acceso a la información sobre los teatros y la cartelera de nuestra comunidad autónoma. 
          Como estudiante del ciclo de Desarrollo de Aplicaciones Web, he desarrollado este proyecto 
          final con el objetivo de crear una herramienta útil tanto para el público como para los 
          propios espacios teatrales.
        </p>
        <p style="color: var(--muted); line-height: 1.8; margin: 0; font-size: 16px;">
          Castilla y León cuenta con un rico patrimonio teatral distribuido en sus nueve provincias. 
          Este proyecto busca poner en valor estos espacios culturales, facilitar su descubrimiento 
          y promover la asistencia a las representaciones teatrales, contribuyendo así al desarrollo 
          cultural de nuestra región.
        </p>
      </div>

      <!-- VALORES -->
      <div class="section-head reveal" style="text-align: center; margin: 40px 0 30px;">
        <h2 style="font-size: 28px;">Nuestros Valores</h2>
        <p>Los principios que guían nuestro proyecto</p>
      </div>

      <div class="grid reveal" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
        <!-- Valor 1 -->
        <article class="card">
          <div class="cover" style="height: 140px; background: radial-gradient(circle at 30% 30%, rgba(214,181,109,.35), rgba(123,27,42,.18)); display: flex; align-items: center; justify-content: center;">
            <div style="font-size: 56px; opacity: 0.9;">🎯</div>
          </div>
          <div class="body">
            <h3>Accesibilidad</h3>
            <p class="meta">
              Creemos que la cultura debe estar al alcance de todos. Por eso, diseñamos una 
              plataforma intuitiva y fácil de usar para cualquier persona.
            </p>
          </div>
        </article>

        <!-- Valor 2 -->
        <article class="card">
          <div class="cover" style="height: 140px; background: radial-gradient(circle at 30% 30%, rgba(161,38,59,.35), rgba(123,27,42,.18)); display: flex; align-items: center; justify-content: center;">
            <div style="font-size: 56px; opacity: 0.9;">💡</div>
          </div>
          <div class="body">
            <h3>Innovación</h3>
            <p class="meta">
              Aplicamos las últimas tecnologías web para ofrecer una experiencia moderna, 
              rápida y eficiente en la consulta de información teatral.
            </p>
          </div>
        </article>

        <!-- Valor 3 -->
        <article class="card">
          <div class="cover" style="height: 140px; background: radial-gradient(circle at 30% 30%, rgba(241,212,138,.25), rgba(70,10,20,.18)); display: flex; align-items: center; justify-content: center;">
            <div style="font-size: 56px; opacity: 0.9;">🤝</div>
          </div>
          <div class="body">
            <h3>Comunidad</h3>
            <p class="meta">
              Fomentamos la conexión entre teatros, artistas y público, creando una 
              verdadera comunidad teatral en Castilla y León.
            </p>
          </div>
        </article>

        <!-- Valor 4 -->
        <article class="card">
          <div class="cover" style="height: 140px; background: radial-gradient(circle at 30% 30%, rgba(214,181,109,.35), rgba(123,27,42,.18)); display: flex; align-items: center; justify-content: center;">
            <div style="font-size: 56px; opacity: 0.9;">🎨</div>
          </div>
          <div class="body">
            <h3>Cultura</h3>
            <p class="meta">
              Valoramos y promovemos el patrimonio cultural de nuestra región, 
              dándole la visibilidad que merece en la era digital.
            </p>
          </div>
        </article>

        <!-- Valor 5 -->
        <article class="card">
          <div class="cover" style="height: 140px; background: radial-gradient(circle at 30% 30%, rgba(161,38,59,.35), rgba(70,10,20,.18)); display: flex; align-items: center; justify-content: center;">
            <div style="font-size: 56px; opacity: 0.9;">🔍</div>
          </div>
          <div class="body">
            <h3>Transparencia</h3>
            <p class="meta">
              Ofrecemos información clara, veraz y actualizada sobre teatros y 
              cartelera, sin intermediarios ni intereses comerciales.
            </p>
          </div>
        </article>

        <!-- Valor 6 -->
        <article class="card">
          <div class="cover" style="height: 140px; background: radial-gradient(circle at 30% 30%, rgba(241,212,138,.25), rgba(123,27,42,.18)); display: flex; align-items: center; justify-content: center;">
            <div style="font-size: 56px; opacity: 0.9;">🚀</div>
          </div>
          <div class="body">
            <h3>Mejora Continua</h3>
            <p class="meta">
              Escuchamos a nuestros usuarios y trabajamos constantemente en mejorar 
              y ampliar las funcionalidades de la plataforma.
            </p>
          </div>
        </article>
      </div>

      <!-- TECNOLOGÍA -->
      <div class="info reveal" style="margin-top: 50px;">
        <div class="info-card glass">
          <h3>Tecnología Web</h3>
          <p>
            Desarrollado con PHP, MySQL, JavaScript y CSS3. Utilizamos tecnologías 
            modernas como Leaflet para mapas interactivos y Chart.js para visualización de datos.
          </p>
        </div>
        <div class="info-card glass">
          <h3>Diseño Responsive</h3>
          <p>
            Nuestra plataforma se adapta perfectamente a cualquier dispositivo: ordenador, 
            tablet o móvil. Disfruta de la misma experiencia en todos tus dispositivos.
          </p>
        </div>
        <div class="info-card glass">
          <h3>Rendimiento</h3>
          <p>
            Optimizado para una carga rápida y navegación fluida. Filtrado instantáneo 
            sin recargas de página para una experiencia de usuario excepcional.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- CONTACTO Y OBJETIVO -->
  <section style="padding: 50px 0 80px;">
    <div class="container">
      <div class="glass reveal" style="padding: 40px 30px; max-width: 900px; margin: 0 auto;">
        <div style="text-align: center; margin-bottom: 30px;">
          <h2 style="margin: 0 0 16px; font-size: 28px;">Nuestro Objetivo</h2>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; margin-bottom: 30px;">
          <div style="text-align: center;">
            <div style="font-size: 48px; margin-bottom: 12px;">🎭</div>
            <h3 style="margin: 0 0 10px; font-size: 18px;">Promoción Cultural</h3>
            <p style="color: var(--muted); margin: 0; font-size: 14px; line-height: 1.6;">
              Dar a conocer la oferta teatral de Castilla y León y aumentar la asistencia a las representaciones.
            </p>
          </div>

          <div style="text-align: center;">
            <div style="font-size: 48px; margin-bottom: 12px;">🌐</div>
            <h3 style="margin: 0 0 10px; font-size: 18px;">Digitalización</h3>
            <p style="color: var(--muted); margin: 0; font-size: 14px; line-height: 1.6;">
              Llevar el patrimonio teatral al mundo digital, haciéndolo más accesible para las nuevas generaciones.
            </p>
          </div>

          <div style="text-align: center;">
            <div style="font-size: 48px; margin-bottom: 12px;">🔗</div>
            <h3 style="margin: 0 0 10px; font-size: 18px;">Conexión</h3>
            <p style="color: var(--muted); margin: 0; font-size: 14px; line-height: 1.6;">
              Crear puentes entre teatros, compañías y público, fortaleciendo el ecosistema cultural regional.
            </p>
          </div>
        </div>

        <div style="border-top: 1px solid rgba(255,255,255,.1); padding-top: 30px; text-align: center;">
          <p style="color: var(--muted); margin: 0 0 20px; font-size: 16px; line-height: 1.6;">
            Este proyecto es un Trabajo de Fin de Grado del Ciclo Superior de Desarrollo de 
            Aplicaciones Web. Esperamos que sea útil para todos los amantes del teatro en nuestra región.
          </p>
          <div class="cta" style="justify-content: center;">
            <a class="btn primary" href="index.php">Explorar la Plataforma</a>
            <a class="btn ghost" href="servicios.php">Ver Servicios</a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <button class="toTop" id="toTop" aria-label="Volver arriba">↑</button>
</main>

<script src="js/indexMain.js?v=1"></script>

<?php include_once __DIR__ . '/inc/footer.php'; ?>