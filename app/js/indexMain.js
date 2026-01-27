// app/js/index2.js

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
      const k = (i + 1) * 10;
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
    const p = (provincia?.value || "").toLowerCase();
    const q = (buscar?.value || "").trim().toLowerCase();

    const filterNode = (node) => {
      const np = (node.dataset.provincia || "").toLowerCase();
      const ns = (node.dataset.search || "").toLowerCase();
      const okP = !p || np === p;
      const okQ = !q || ns.includes(q);
      node.style.display = (okP && okQ) ? "" : "none";
    };

    document
      .querySelectorAll('#gridTeatros .card, #gridCartelera .card, #listDestacados .mini-card')
      .forEach(filterNode);
  };

  [provincia, buscar].forEach(el => el && el.addEventListener('input', applyFilter));
  btnReset?.addEventListener('click', () => {
    if (provincia) provincia.value = "";
    if (buscar) buscar.value = "";
    applyFilter();
  });

  // --- Botón arriba ---
  const toTop = document.getElementById('toTop');
  const toggleTop = () => toTop?.classList.toggle('show', window.scrollY > 600);
  window.addEventListener('scroll', toggleTop, { passive: true });
  toggleTop();
  toTop?.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

  // --- MAPA Leaflet ---
  const mapEl = document.getElementById('mapTeatros');
  const jsonUrl = window.__TEATROS_JSON_URL__;

  if (mapEl && window.L && jsonUrl) {
    const map = L.map('mapTeatros', { scrollWheelZoom: false }).setView([41.8, -4.8], 7);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    fetch(jsonUrl, { cache: 'no-store' })
      .then(r => {
        if (!r.ok) throw new Error('No se pudo cargar el JSON (' + r.status + ')');
        return r.json();
      })
      .then(data => {
        const bounds = [];
        const markers = L.featureGroup().addTo(map);

        (data || []).forEach(item => {
          const f = item?.fields || {};
          const coords = item?.geometry?.coordinates;

          // geometry.coordinates = [lng, lat]
          if (!Array.isArray(coords) || coords.length < 2) return;

          const lng = Number(coords[0]);
          const lat = Number(coords[1]);
          if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

          const sala = f.sala || 'Teatro';
          const municipioTxt = f.municipio || '';
          const provinciaTxt = f.provincia || '';
          const direccionTxt = f.direccion || '';
          const telefonoTxt = f.telefono_s || '';
          const emailTxt = f.email || '';

          const popup = `
            <div style="min-width:220px">
              <strong>${escapeHtml(sala)}</strong><br>
              <span>${escapeHtml(municipioTxt)} · ${escapeHtml(provinciaTxt)}</span><br>
              <small>${escapeHtml(direccionTxt)}</small><br>
              ${telefonoTxt ? `<div style="margin-top:6px">📞 ${escapeHtml(telefonoTxt)}</div>` : ''}
              ${emailTxt ? `<div>✉️ ${escapeHtml(emailTxt)}</div>` : ''}
            </div>
          `;

          L.marker([lat, lng]).bindPopup(popup).addTo(markers);
          bounds.push([lat, lng]);
        });

        if (bounds.length) map.fitBounds(bounds, { padding: [30, 30] });
      })
      .catch(err => {
        console.warn(err);
        mapEl.innerHTML = '<div style="padding:14px;color:#fff;opacity:.8">No se pudo cargar el mapa (revisa la ruta del JSON).</div>';
      });

    function escapeHtml(str){
      return String(str)
        .replaceAll('&','&amp;')
        .replaceAll('<','&lt;')
        .replaceAll('>','&gt;')
        .replaceAll('"','&quot;')
        .replaceAll("'","&#039;");
    }
  }
})();
