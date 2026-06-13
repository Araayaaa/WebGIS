/* =========================================================================
 * SIG Mapping Tanah & Jalan
 * Leaflet + Leaflet.draw, backend vanilla PHP.
 * ========================================================================= */

const API = {
  tanah: 'api/tanah.php',
  jalan: 'api/jalan.php',
};

const THEME = {
  tanah: { color: '#22c55e', label: 'Tanah', geom: 'Polygon' },
  jalan: { color: '#ef4444', label: 'Jalan', geom: 'LineString' },
};

// Penyimpanan layer & data per id
const store = {
  tanah: { items: [], layers: new Map() },
  jalan: { items: [], layers: new Map() },
};

let activeTab = 'tanah';
let searchQuery = '';

/* --------------------------- Util format --------------------------- */
function fmtArea(m2) {
  if (m2 >= 10000) return (m2 / 10000).toFixed(2) + ' ha (' + Math.round(m2).toLocaleString('id') + ' m²)';
  return Math.round(m2).toLocaleString('id') + ' m²';
}
function fmtLen(m) {
  if (m >= 1000) return (m / 1000).toFixed(2) + ' km (' + Math.round(m).toLocaleString('id') + ' m)';
  return Math.round(m).toLocaleString('id') + ' m';
}

/* --------------- Perhitungan klien (mirror dari PHP) --------------- */
const R = 6378137.0;
const rad = (d) => (d * Math.PI) / 180;

function haversine(a, b) {
  const dLat = rad(b[1] - a[1]);
  const dLon = rad(b[0] - a[0]);
  const h = Math.sin(dLat / 2) ** 2 +
    Math.cos(rad(a[1])) * Math.cos(rad(b[1])) * Math.sin(dLon / 2) ** 2;
  return 2 * R * Math.asin(Math.min(1, Math.sqrt(h)));
}
function lineLength(coords) {
  let t = 0;
  for (let i = 1; i < coords.length; i++) t += haversine(coords[i - 1], coords[i]);
  return t;
}
function ringArea(ring) {
  const n = ring.length;
  if (n < 3) return 0;
  let area = 0;
  for (let i = 0; i < n; i++) {
    const p1 = ring[i], p2 = ring[(i + 1) % n];
    area += rad(p2[0] - p1[0]) * (2 + Math.sin(rad(p1[1])) + Math.sin(rad(p2[1])));
  }
  return Math.abs((area * R * R) / 2);
}
function ringPerimeter(ring) {
  if (ring.length < 2) return 0;
  const closed = ring.slice();
  const f = closed[0], l = closed[closed.length - 1];
  if (f[0] !== l[0] || f[1] !== l[1]) closed.push(f);
  return lineLength(closed);
}

/* Hitung ukuran dari sebuah GeoJSON geometry */
function measure(kind, geometry) {
  if (kind === 'tanah') {
    const outer = geometry.coordinates[0] || [];
    return { luas: ringArea(outer), keliling: ringPerimeter(outer) };
  }
  return { panjang: lineLength(geometry.coordinates) };
}

/* --------------------------- Peta --------------------------- */
const map = L.map('map', { center: [-0.0263, 109.3425], zoom: 13 }); // Pontianak

const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  maxZoom: 19, attribution: '&copy; OpenStreetMap',
}).addTo(map);

const sat = L.tileLayer(
  'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
  { maxZoom: 19, attribution: 'Esri World Imagery' }
);
L.control.layers({ 'Peta Jalan': osm, 'Satelit': sat }, null, { position: 'bottomright' }).addTo(map);

// Group yang bisa diedit oleh Leaflet.draw
const drawnItems = new L.FeatureGroup().addTo(map);

const drawControl = new L.Control.Draw({
  position: 'topright',
  draw: {
    polygon: {
      showArea: true, metric: true, allowIntersection: false,
      shapeOptions: { color: THEME.tanah.color, weight: 2, fillOpacity: 0.3 },
    },
    polyline: {
      metric: true, showLength: true,
      shapeOptions: { color: THEME.jalan.color, weight: 4 },
    },
    rectangle: false, circle: false, marker: false, circlemarker: false,
  },
  edit: { featureGroup: drawnItems, remove: false },
});
map.addControl(drawControl);

/* --- Tombol "Tambah" di sidebar: aktifkan mode menggambar Leaflet.draw --- */
let activeDrawer = null;
function startDraw(kind) {
  if (activeDrawer) { activeDrawer.disable(); activeDrawer = null; }
  activeDrawer = kind === 'tanah'
    ? new L.Draw.Polygon(map, drawControl.options.draw.polygon)
    : new L.Draw.Polyline(map, drawControl.options.draw.polyline);
  activeDrawer.enable();

  const hint = document.getElementById('draw-hint');
  if (hint) {
    hint.innerHTML = kind === 'tanah'
      ? 'Klik titik-titik batas <b>tanah</b> di peta, klik titik awal untuk menutup.'
      : 'Klik titik-titik <b>jalan</b> di peta, klik dua kali untuk mengakhiri.';
    hint.classList.add('text-emerald-700', 'font-medium');
  }
}
function resetDrawHint() {
  const hint = document.getElementById('draw-hint');
  if (hint) {
    hint.innerHTML = 'Klik tombol lalu gambar di peta. <span class="text-slate-400">Luas &amp; panjang otomatis.</span>';
    hint.classList.remove('text-emerald-700', 'font-medium');
  }
}
document.getElementById('add-tanah').onclick = () => startDraw('tanah');
document.getElementById('add-jalan').onclick = () => startDraw('jalan');
map.on(L.Draw.Event.DRAWSTOP, () => { activeDrawer = null; resetDrawHint(); });

/* --------------------------- API helpers --------------------------- */
async function apiGet(kind) {
  const res = await fetch(API[kind]);
  const j = await res.json();
  return j.data || [];
}
async function apiSave(kind, payload, id) {
  const url = id ? `${API[kind]}?id=${id}` : API[kind];
  const res = await fetch(url, {
    method: id ? 'PUT' : 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
  const j = await res.json();
  if (!res.ok) throw new Error(j.error || 'Gagal menyimpan');
  return j.data;
}
async function apiDelete(kind, id) {
  const res = await fetch(`${API[kind]}?id=${id}`, { method: 'DELETE' });
  const j = await res.json();
  if (!res.ok) throw new Error(j.error || 'Gagal menghapus');
  return true;
}

/* --------------------------- Render layer --------------------------- */
function styleFor(kind, item) {
  const c = item.warna || THEME[kind].color;
  return kind === 'tanah'
    ? { color: c, weight: 2, fillColor: c, fillOpacity: 0.35 }
    : { color: c, weight: 5, opacity: 0.9 };
}

function popupHtml(kind, item) {
  if (kind === 'tanah') {
    return `<div class="text-sm">
      <b>${escapeHtml(item.nama)}</b><br>
      ${item.pemilik ? 'Pemilik: ' + escapeHtml(item.pemilik) + '<br>' : ''}
      ${item.kategori ? 'Status: ' + escapeHtml(item.kategori) + '<br>' : ''}
      Luas: <b>${fmtArea(+item.luas)}</b><br>
      Keliling: ${fmtLen(+item.keliling)}
      ${item.deskripsi ? '<br><i>' + escapeHtml(item.deskripsi) + '</i>' : ''}
    </div>`;
  }
  return `<div class="text-sm">
    <b>${escapeHtml(item.nama)}</b><br>
    ${item.kategori ? 'Kategori: ' + escapeHtml(item.kategori) + '<br>' : ''}
    ${item.jenis ? 'Jenis: ' + escapeHtml(item.jenis) + '<br>' : ''}
    Panjang: <b>${fmtLen(+item.panjang)}</b>
    ${item.deskripsi ? '<br><i>' + escapeHtml(item.deskripsi) + '</i>' : ''}
  </div>`;
}

function addFeature(kind, item) {
  const layer = L.geoJSON(item.geojson, { style: styleFor(kind, item) });
  // geoJSON membuat group; ambil layer dalamnya & masukkan ke drawnItems
  layer.eachLayer((l) => {
    l.feature_id = item.id;
    l.kind = kind;
    l.bindPopup(popupHtml(kind, item));
    drawnItems.addLayer(l);
    store[kind].layers.set(item.id, l);
  });
}

function removeFeatureLayer(kind, id) {
  const l = store[kind].layers.get(id);
  if (l) { drawnItems.removeLayer(l); store[kind].layers.delete(id); }
}

/* --------------------------- Sidebar --------------------------- */
function matchQuery(kind, it) {
  if (!searchQuery) return true;
  const hay = [it.nama, it.deskripsi, it.kategori, kind === 'tanah' ? it.pemilik : it.jenis]
    .filter(Boolean).join(' ').toLowerCase();
  return hay.includes(searchQuery);
}

function renderList(kind) {
  const ul = document.getElementById('list-' + kind);
  const all = store[kind].items;
  const items = all.filter((it) => matchQuery(kind, it));
  // tampilkan "cocok/total" saat mencari, atau total saja saat tidak
  document.getElementById('count-' + kind).textContent =
    searchQuery ? `${items.length}/${all.length}` : all.length;

  if (!all.length) {
    ul.innerHTML = `<li class="p-6 text-center text-sm text-slate-400">Belum ada data.<br>Gambar di peta untuk menambahkan.</li>`;
    return;
  }
  if (!items.length) {
    ul.innerHTML = `<li class="p-6 text-center text-sm text-slate-400">Tidak ada hasil untuk<br>"<b>${escapeHtml(searchQuery)}</b>".</li>`;
    return;
  }

  ul.innerHTML = items.map((it) => {
    const ukuran = kind === 'tanah' ? fmtArea(+it.luas) : fmtLen(+it.panjang);
    const sub = kind === 'tanah'
      ? (it.pemilik ? escapeHtml(it.pemilik) : '—')
      : (it.jenis ? escapeHtml(it.jenis) : '—');
    return `<li class="group p-3 hover:bg-slate-50 cursor-pointer flex items-start gap-3" data-id="${it.id}">
      <span class="mt-1 w-3 h-3 rounded-sm shrink-0" style="background:${it.warna || THEME[kind].color}"></span>
      <div class="flex-1 min-w-0" data-act="focus">
        <div class="font-medium text-sm truncate">${escapeHtml(it.nama)}</div>
        <div class="text-xs text-slate-500 truncate">${sub}</div>
        <div class="text-xs font-semibold text-slate-700 mt-0.5">${ukuran}</div>
      </div>
      <div class="flex flex-col gap-1 opacity-0 group-hover:opacity-100 transition">
        <button data-act="edit" title="Edit" class="text-xs px-2 py-0.5 rounded bg-amber-100 text-amber-700 hover:bg-amber-200">✎</button>
        <button data-act="del" title="Hapus" class="text-xs px-2 py-0.5 rounded bg-red-100 text-red-700 hover:bg-red-200">🗑</button>
      </div>
    </li>`;
  }).join('');

  ul.querySelectorAll('li').forEach((li) => {
    const id = +li.dataset.id;
    li.querySelector('[data-act="focus"]').onclick = () => focusFeature(kind, id);
    li.querySelector('[data-act="edit"]').onclick = (e) => { e.stopPropagation(); openEdit(kind, id); };
    li.querySelector('[data-act="del"]').onclick = (e) => { e.stopPropagation(); doDelete(kind, id); };
  });
}

// Redupkan fitur di peta yang tidak cocok dengan pencarian.
function applyMapFilter() {
  ['tanah', 'jalan'].forEach((kind) => {
    store[kind].items.forEach((it) => {
      const l = store[kind].layers.get(it.id);
      if (!l) return;
      if (matchQuery(kind, it)) {
        l.setStyle(styleFor(kind, it));
      } else {
        l.setStyle(kind === 'tanah' ? { opacity: 0.15, fillOpacity: 0.04 } : { opacity: 0.15 });
      }
    });
  });
}

function updateSummary() {
  const luas = store.tanah.items.reduce((s, i) => s + (+i.luas || 0), 0);
  const panjang = store.jalan.items.reduce((s, i) => s + (+i.panjang || 0), 0);
  document.getElementById('sum-luas').textContent = fmtArea(luas);
  document.getElementById('sum-panjang').textContent = fmtLen(panjang);
}

function focusFeature(kind, id) {
  const l = store[kind].layers.get(id);
  if (!l) return;
  if (l.getBounds) map.fitBounds(l.getBounds(), { maxZoom: 18, padding: [40, 40] });
  l.openPopup();
}

/* --------------------------- Load data --------------------------- */
async function loadAll() {
  for (const kind of ['tanah', 'jalan']) {
    store[kind].items = await apiGet(kind);
    store[kind].layers.forEach((l) => drawnItems.removeLayer(l));
    store[kind].layers.clear();
    store[kind].items.forEach((it) => addFeature(kind, it));
    renderList(kind);
  }
  updateSummary();
  if (searchQuery) applyMapFilter();
}

/* --------------------------- Modal form --------------------------- */
const modal = document.getElementById('modal');
const form = document.getElementById('form');
let pendingLayer = null; // layer baru hasil gambar (belum tersimpan)

function openModal(kind, mode, item) {
  const t = THEME[kind];
  document.getElementById('f-kind').value = kind;
  document.getElementById('f-id').value = item?.id || '';
  document.getElementById('modal-title').textContent =
    (mode === 'edit' ? 'Edit ' : 'Tambah ') + t.label;

  const head = document.getElementById('modal-head');
  const saveBtn = document.getElementById('modal-save');
  const accent = kind === 'tanah' ? 'bg-emerald-600' : 'bg-red-600';
  head.className = 'px-5 py-4 text-white font-semibold flex items-center justify-between ' + accent;
  saveBtn.className = 'flex-1 py-2 rounded-lg text-white text-sm font-semibold ' +
    (kind === 'tanah' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-red-600 hover:bg-red-700');

  // tampilkan field sesuai jenis
  document.querySelectorAll('.kind-tanah').forEach((el) => el.classList.toggle('hidden', kind !== 'tanah'));
  document.querySelectorAll('.kind-jalan').forEach((el) => el.classList.toggle('hidden', kind !== 'jalan'));

  // isi nilai
  document.getElementById('f-nama').value = item?.nama || '';
  document.getElementById('f-pemilik').value = item?.pemilik || '';
  document.getElementById('f-jenis').value = item?.jenis || '';
  document.getElementById('f-kategori-tanah').value = item?.kategori || '';
  document.getElementById('f-kategori-jalan').value = item?.kategori || '';
  document.getElementById('f-deskripsi').value = item?.deskripsi || '';
  document.getElementById('f-warna').value = item?.warna || t.color;
  document.getElementById('f-geojson').value = JSON.stringify(item.geojson);

  // ukuran otomatis
  const m = measure(kind, item.geojson);
  const box = document.getElementById('f-measure');
  box.innerHTML = kind === 'tanah'
    ? `<div><div class="text-xs text-slate-500">Luas</div><div class="font-bold text-emerald-700">${fmtArea(m.luas)}</div></div>
       <div><div class="text-xs text-slate-500">Keliling</div><div class="font-bold">${fmtLen(m.keliling)}</div></div>`
    : `<div class="col-span-2"><div class="text-xs text-slate-500">Panjang</div><div class="font-bold text-red-700">${fmtLen(m.panjang)}</div></div>`;

  modal.classList.remove('hidden');
  setTimeout(() => document.getElementById('f-nama').focus(), 50);
}

function closeModal() {
  modal.classList.add('hidden');
  // bila ada layer gambar yang belum disimpan -> buang
  if (pendingLayer) { drawnItems.removeLayer(pendingLayer); pendingLayer = null; }
}
document.getElementById('modal-close').onclick = closeModal;
document.getElementById('modal-cancel').onclick = closeModal;

form.onsubmit = async (e) => {
  e.preventDefault();
  const kind = document.getElementById('f-kind').value;
  const id = document.getElementById('f-id').value;
  const payload = {
    nama: document.getElementById('f-nama').value.trim(),
    deskripsi: document.getElementById('f-deskripsi').value.trim(),
    warna: document.getElementById('f-warna').value,
    geojson: JSON.parse(document.getElementById('f-geojson').value),
  };
  if (kind === 'tanah') {
    payload.pemilik = document.getElementById('f-pemilik').value.trim();
    payload.kategori = document.getElementById('f-kategori-tanah').value;
  } else {
    payload.jenis = document.getElementById('f-jenis').value;
    payload.kategori = document.getElementById('f-kategori-jalan').value;
  }

  const btn = document.getElementById('modal-save');
  btn.disabled = true; btn.textContent = 'Menyimpan...';
  try {
    await apiSave(kind, payload, id || null);
    pendingLayer = null; // sudah tersimpan; akan dirender ulang dari server
    modal.classList.add('hidden');
    await loadAll();
    switchTab(kind);
  } catch (err) {
    alert(err.message);
  } finally {
    btn.disabled = false; btn.textContent = 'Simpan';
  }
};

/* --------------------------- Aksi CRUD --------------------------- */
function openEdit(kind, id) {
  const item = store[kind].items.find((i) => i.id === id);
  if (item) openModal(kind, 'edit', item);
}

async function doDelete(kind, id) {
  const item = store[kind].items.find((i) => i.id === id);
  if (!confirm(`Hapus "${item?.nama}"?`)) return;
  try {
    await apiDelete(kind, id);
    removeFeatureLayer(kind, id);
    await loadAll();
  } catch (err) { alert(err.message); }
}

/* --------------------------- Event Leaflet.draw --------------------------- */
map.on(L.Draw.Event.CREATED, (e) => {
  const layer = e.layer;
  const kind = e.layerType === 'polygon' ? 'tanah' : 'jalan';
  pendingLayer = layer;
  drawnItems.addLayer(layer);
  const geojson = layer.toGeoJSON().geometry;
  openModal(kind, 'create', { geojson });
});

// Edit geometri (toolbar edit Leaflet.draw)
map.on(L.Draw.Event.EDITED, async (e) => {
  const jobs = [];
  e.layers.eachLayer((l) => {
    if (!l.feature_id) return;
    const kind = l.kind;
    const item = store[kind].items.find((i) => i.id === l.feature_id);
    if (!item) return;
    const geojson = l.toGeoJSON().geometry;
    jobs.push(apiSave(kind, {
      nama: item.nama, deskripsi: item.deskripsi, warna: item.warna,
      pemilik: item.pemilik, jenis: item.jenis, kategori: item.kategori, geojson,
    }, item.id));
  });
  try { await Promise.all(jobs); await loadAll(); }
  catch (err) { alert('Gagal menyimpan perubahan geometri: ' + err.message); await loadAll(); }
});

/* --------------------------- Tab --------------------------- */
function switchTab(kind) {
  activeTab = kind;
  document.querySelectorAll('.tab-btn').forEach((b) => {
    const on = b.dataset.tab === kind;
    b.classList.toggle('border-emerald-500', on && kind === 'tanah');
    b.classList.toggle('border-red-500', on && kind === 'jalan');
    b.classList.toggle('text-emerald-600', on && kind === 'tanah');
    b.classList.toggle('text-red-600', on && kind === 'jalan');
    b.classList.toggle('border-transparent', !on);
    b.classList.toggle('text-slate-500', !on);
  });
  document.getElementById('list-tanah').classList.toggle('hidden', kind !== 'tanah');
  document.getElementById('list-jalan').classList.toggle('hidden', kind !== 'jalan');
}
document.querySelectorAll('.tab-btn').forEach((b) => (b.onclick = () => switchTab(b.dataset.tab)));

/* --------------------------- Pencarian --------------------------- */
const searchInput = document.getElementById('search');
const searchClear = document.getElementById('search-clear');

function runSearch() {
  searchQuery = searchInput.value.trim().toLowerCase();
  searchClear.classList.toggle('hidden', !searchQuery);
  renderList('tanah');
  renderList('jalan');
  applyMapFilter();
}
searchInput.addEventListener('input', runSearch);
searchClear.addEventListener('click', () => {
  searchInput.value = '';
  searchInput.focus();
  runSearch();
});

/* --------------------------- Util --------------------------- */
function escapeHtml(s) {
  return String(s ?? '').replace(/[&<>"']/g, (c) =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

/* --------------------------- Start --------------------------- */
loadAll().catch((err) => {
  console.error(err);
  alert('Gagal memuat data. Pastikan database sudah dibuat (import schema.sql).');
});
