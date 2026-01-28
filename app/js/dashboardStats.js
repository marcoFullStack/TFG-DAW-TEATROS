// app/js/dashboardStats.js
(() => {
  // ====== CHART (izquierda) ======
  const API_DRACOR = "https://dracor.org/api/v1/corpora/span";

  function bin20(year) {
    const start = Math.floor(year / 20) * 20;
    return `${start}-${start + 19}`;
  }

  async function loadEpocasPie() {
    const hint = document.getElementById("chartEpocasHint");
    try {
      if (hint) hint.textContent = "Cargando datos de Dracor…";

      const res = await fetch(API_DRACOR, { headers: { "Accept": "application/json" } });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);

      const json = await res.json();
      const plays = Array.isArray(json.plays) ? json.plays : [];

      const counts = new Map();

      for (const p of plays) {
        const y = p?.yearNormalized;
        if (typeof y !== "number" || !Number.isFinite(y)) continue;
        const label = bin20(y);
        counts.set(label, (counts.get(label) || 0) + 1);
      }

      const labels = [...counts.keys()].sort((a, b) => {
        const a0 = parseInt(a.split("-")[0], 10);
        const b0 = parseInt(b.split("-")[0], 10);
        return a0 - b0;
      });

      const data = labels.map(l => counts.get(l));
      const total = data.reduce((acc, n) => acc + n, 0);

      if (hint) hint.textContent = `Obras con año conocido: ${total} (rangos de 20 años).`;

      const canvas = document.getElementById("chartEpocas");
      if (!canvas) return;

      // Chart.js
      // eslint-disable-next-line no-undef
      new Chart(canvas, {
        type: "pie",
        data: { labels, datasets: [{ data }] },
        options: {
          responsive: true,
          plugins: {
            legend: { position: "bottom" },
            tooltip: { callbacks: { label: (tt) => `${tt.label}: ${tt.parsed}` } }
          }
        }
      });

    } catch (err) {
      console.error(err);
      if (hint) hint.textContent = "No se pudo cargar el gráfico (Dracor).";
    }
  }

  // ====== RANKING (derecha) ======
  const API_RANKING = "./api/ranking_top3.php";

  // desde app/index2.php
  const UPLOADS_DIR = "uploads/";
  const DEFAULT_IMG = "images/default_user.png";

  const state = {
    q: "",
    page: 1,
    totalPages: 1
  };

  function theaterVibe(points) {
    const p = Number(points ?? 0);
    if (p >= 250) return "Vive por y para el teatro 🎭🔥";
    if (p >= 150) return "Se sabe la cartelera de memoria 🎟️✨";
    if (p >= 80)  return "Siempre repite… y trae amigos 👏";
    if (p >= 30)  return "Va calentando motores: ¡más función! 🙂";
    if (p > 0)    return "Buen comienzo: el teatro engancha 😉";
    return "Aún está empezando… ¡a por la primera visita! 🌟";
  }

  function resolveFotoPerfilPath(fotoPerfilRaw) {
    const fp = (fotoPerfilRaw ?? "").toString().trim();
    if (!fp) return DEFAULT_IMG;

    if (fp.startsWith("http://") || fp.startsWith("https://")) return fp;
    if (fp.startsWith("uploads/") || fp.startsWith("images/") || fp.startsWith("/")) return fp;

    // caso típico: "user_1769612054_8ab316a2.png"
    return UPLOADS_DIR + fp;
  }

  function setPodiumCard(el, user, fallbackName, positionNumber) {
    const img = el.querySelector(".avatar");
    const name = el.querySelector(".name");
    const points = el.querySelector(".points");
    const vibe = el.querySelector(".vibe");
    const medal = el.querySelector(".medal");

    if (medal && positionNumber) medal.textContent = String(positionNumber);

    if (!user) {
      img.src = DEFAULT_IMG;
      img.alt = fallbackName;
      name.textContent = fallbackName;
      points.textContent = "0 pts";
      vibe.textContent = "—";
      return;
    }

    const username = user.Nombre ?? fallbackName;
    const pts = Number(user.Puntos ?? 0);

    name.textContent = username;
    points.textContent = `${pts} pts`;
    vibe.textContent = theaterVibe(pts);

    img.src = resolveFotoPerfilPath(user.FotoPerfil);
    img.alt = `Foto de ${username}`;

    img.onerror = () => {
      img.onerror = null;
      img.src = DEFAULT_IMG;
    };
  }

  function renderTableRows(rows) {
    const tbody = document.getElementById("rankTbody");
    if (!tbody) return;

    if (!rows || rows.length === 0) {
      tbody.innerHTML = `<tr><td colspan="3" class="rankEmpty">No hay resultados.</td></tr>`;
      return;
    }

    tbody.innerHTML = rows.map(r => {
      const pos = Number(r.Posicion ?? "");
      const nombre = (r.Nombre ?? "").toString();
      const puntos = Number(r.Puntos ?? 0);
      return `
        <tr>
          <td>${pos}</td>
          <td>${escapeHtml(nombre)}</td>
          <td>${puntos}</td>
        </tr>
      `;
    }).join("");
  }

  function escapeHtml(str) {
    return str
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function updatePager() {
    const prev = document.getElementById("rankPrev");
    const next = document.getElementById("rankNext");
    const info = document.getElementById("rankPageInfo");

    if (info) info.textContent = `Página ${state.page} / ${state.totalPages}`;
    if (prev) prev.disabled = state.page <= 1;
    if (next) next.disabled = state.page >= state.totalPages;
  }

  async function fetchRanking() {
    const hint = document.getElementById("podiumHint");
    const tbody = document.getElementById("rankTbody");

    try {
      if (hint) hint.textContent = "Cargando ranking…";
      if (tbody) tbody.innerHTML = `<tr><td colspan="3" class="rankEmpty">Cargando…</td></tr>`;

      const url = new URL(API_RANKING, window.location.href);
      url.searchParams.set("q", state.q);
      url.searchParams.set("page", String(state.page));

      const res = await fetch(url.toString(), { headers: { "Accept": "application/json" } });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);

      const json = await res.json();
      if (!json.ok) throw new Error("Respuesta no OK");

      const top3 = Array.isArray(json.top3) ? json.top3 : [];
      const rows = Array.isArray(json.rows) ? json.rows : [];

      state.totalPages = Number(json.total_pages ?? 1) || 1;

      // Podium (1-2-3)
      setPodiumCard(document.getElementById("podium1"), top3[0], "Sin datos", 1);
      setPodiumCard(document.getElementById("podium2"), top3[1], "Sin datos", 2);
      setPodiumCard(document.getElementById("podium3"), top3[2], "Sin datos", 3);

      // Tabla
      renderTableRows(rows);

      updatePager();

      const total = Number(json.total ?? 0);
      hint.textContent = state.q
        ? `Resultados para “${state.q}”: ${total} usuarios.`
        : `Total usuarios en ranking: ${total}.`;

    } catch (err) {
      console.error(err);
      if (hint) hint.textContent = "No se pudo cargar el ranking.";
      if (tbody) tbody.innerHTML = `<tr><td colspan="3" class="rankEmpty">Error cargando datos.</td></tr>`;
    }
  }

  function debounce(fn, ms) {
    let t = null;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...args), ms);
    };
  }

  function initRankingUI() {
    const input = document.getElementById("rankSearch");
    const clear = document.getElementById("rankClear");
    const prev = document.getElementById("rankPrev");
    const next = document.getElementById("rankNext");

    if (input) {
      const onInput = debounce(() => {
        state.q = input.value.trim();
        state.page = 1;
        fetchRanking();
      }, 250);

      input.addEventListener("input", onInput);
    }

    if (clear) {
      clear.addEventListener("click", () => {
        state.q = "";
        state.page = 1;
        if (input) input.value = "";
        fetchRanking();
      });
    }

    if (prev) {
      prev.addEventListener("click", () => {
        if (state.page > 1) {
          state.page -= 1;
          fetchRanking();
        }
      });
    }

    if (next) {
      next.addEventListener("click", () => {
        if (state.page < state.totalPages) {
          state.page += 1;
          fetchRanking();
        }
      });
    }
  }

  document.addEventListener("DOMContentLoaded", async () => {
    await loadEpocasPie();
    initRankingUI();
    await fetchRanking();
  });
})();
