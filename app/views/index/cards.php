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
                  <a class="link" 
                    style="cursor: pointer;" 
                    onclick="buscarWiki('<?= h($t['Sala'] . ' ' . $t['Municipio']) ?>', 'teatro')">
                    Ver detalles →
                  </a>
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
                  <a class="link" 
                    style="cursor: pointer;" 
                    onclick="buscarWiki('<?= h($c['titulo']) ?>', 'obra')">
                    Saber más →
                  </a>
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

  <script>

async function buscarWiki(texto, tipo) {
    // Añadimos una palabra clave para mejorar la puntería del buscador
    const query = tipo === 'teatro' ? `${texto} teatro` : `${texto} obra de teatro`;
    
    // URL de la API de Wikipedia
    const url = `https://es.wikipedia.org/w/api.php?action=opensearch&origin=*&search=${encodeURIComponent(query)}&limit=1&format=json`;

    try {
        const respuesta = await fetch(url);
        const datos = await respuesta.json();

        // contiene el array de enlaces directos
        if (datos[3] && datos[3].length > 0) {
            window.open(datos[3][0], '_blank');
        } else {
            // Si no encuentra nada con el texto específico, intenta una búsqueda general en Wiki
            window.open(`https://es.wikipedia.org/w/index.php?search=${encodeURIComponent(query)}`, '_blank');
        }
    } catch (error) {
        console.error("Error al conectar con Wikipedia:", error);
        // abrir búsqueda normal si la API falla
        window.open(`https://es.wikipedia.org/w/index.php?search=${encodeURIComponent(query)}`, '_blank');
    }
}
</script>