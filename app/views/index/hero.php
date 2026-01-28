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