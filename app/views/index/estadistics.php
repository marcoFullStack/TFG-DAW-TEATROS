<section class="dash50" id="estadisticas">
  <!-- gráfico -->
  <div class="dashCol">
    <h3 class="dashTitle">Épocas de las obras (cada 20 años)</h3>
    <div class="dashBox">
      <canvas id="chartEpocas" aria-label="Gráfico circular épocas" role="img"></canvas>
      <p class="dashHint" id="chartEpocasHint"></p>
    </div>
  </div>

  <!-- ranking -->
  <div class="dashCol">
    <h3 class="dashTitle">Ranking (Top 3)</h3>

    <!-- Podium -->
    <div class="podium" id="podium">
      <div class="podiumCol second" id="podium2">
        <div class="medal">2</div>
        <img class="avatar" src="images/default_user.png" alt="Segundo puesto" />
        <div class="name">—</div>
        <div class="points">0 pts</div>
        <div class="vibe">—</div>
      </div>

      <div class="podiumCol first" id="podium1">
        <div class="medal">1</div>
        <img class="avatar" src="images/default_user.png" alt="Primer puesto" />
        <div class="name">—</div>
        <div class="points">0 pts</div>
        <div class="vibe">—</div>
      </div>

      <div class="podiumCol third" id="podium3">
        <div class="medal">3</div>
        <img class="avatar" src="images/default_user.png" alt="Tercer puesto" />
        <div class="name">—</div>
        <div class="points">0 pts</div>
        <div class="vibe">—</div>
      </div>
    </div>

    <!-- Filtro -->
    <div class="rankTools">
      <label for="rankSearch">Buscar usuario:</label>
      <input id="rankSearch" class="rankSearch" type="search" placeholder="Buscar por nombre…" autocomplete="off" />
      <button id="rankClear" class="rankBtn" type="button">Limpiar</button>
    </div>

    <!-- Tabla ranking -->
    <div class="rankTableWrap">
      <table class="rankTable" aria-label="Tabla ranking usuarios">
        <thead>
          <tr>
            <th style="width:110px;">Posición</th>
            <th>Nombre</th>
            <th style="width:120px;">Puntos</th>
          </tr>
        </thead>
        <tbody id="rankTbody">
          <tr><td colspan="3" class="rankEmpty">Cargando…</td></tr>
        </tbody>
      </table>
    </div>

    <!-- Paginación -->
    <div class="rankPager">
      <button id="rankPrev" class="rankBtn" type="button">← Anterior</button>
      <div class="rankPageInfo" id="rankPageInfo">Página 1</div>
      <button id="rankNext" class="rankBtn" type="button">Siguiente →</button>
    </div>

    <p class="dashHint" id="podiumHint"></p>
  </div>
</section>

