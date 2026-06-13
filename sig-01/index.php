<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>WebGIS SPBU — Peta Sebaran Stasiun BBM</title>

<!-- TailwindCSS -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
    theme: {
        extend: {
            colors: {
                brand: { DEFAULT: '#1d4ed8', dark: '#1e3a8a' }
            }
        }
    }
}
</script>

<!-- Leaflet.js -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
  html, body { height: 100%; margin: 0; }
  #map { height: 100%; width: 100%; }

  .leaflet-popup-content-wrapper {
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,.15);
    padding: 0;
    overflow: hidden;
  }
  .leaflet-popup-content { margin: 0; width: 240px !important; }
  .leaflet-popup-tip-container { display: none; }

  /* Custom marker */
  .spbu-marker {
    display: flex; align-items: center; justify-content: center;
    width: 36px; height: 36px;
    border-radius: 50% 50% 50% 0;
    transform: rotate(-45deg);
    border: 3px solid #fff;
    box-shadow: 0 3px 10px rgba(0,0,0,.35);
    cursor: pointer;
    transition: transform .15s;
  }
  .spbu-marker:hover { transform: rotate(-45deg) scale(1.15); }
  .spbu-marker span {
    transform: rotate(45deg);
    font-size: 11px; font-weight: 800;
    color: #fff; line-height: 1;
  }
  .marker-24   { background: #16a34a; }
  .marker-biasa{ background: #ea580c; }

  /* Sidebar scroll */
  #spbu-list { overflow-y: auto; max-height: calc(100vh - 200px); }

  /* Pulse animation for active filter */
  .filter-active { box-shadow: 0 0 0 3px rgba(29,78,216,.35); }

  /* Spinner */
  .spinner {
    width: 28px; height: 28px;
    border: 3px solid #e2e8f0;
    border-top-color: #1d4ed8;
    border-radius: 50%;
    animation: spin .7s linear infinite;
    margin: 24px auto;
  }
  @keyframes spin { to { transform: rotate(360deg); } }
</style>
</head>
<body class="bg-gray-100 font-sans">

<!-- ── LAYOUT ─────────────────────────────────────────────────────────── -->
<div class="flex h-screen">

  <!-- SIDEBAR -->
  <aside class="w-80 bg-white shadow-xl flex flex-col z-10 flex-shrink-0">

    <!-- Header -->
    <div class="bg-gradient-to-br from-blue-700 to-blue-900 text-white px-5 py-4">
      <div class="flex items-center gap-3 mb-1">
        <svg class="w-7 h-7 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2"
             viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round"
                d="M3 10h2l1 2h13l1-2h2M5 12v6a1 1 0 001 1h12a1 1 0 001-1v-6
                   M9 22v-4h6v4M7 4h10l1 4H6L7 4z"/>
        </svg>
        <div>
          <h1 class="text-lg font-bold leading-tight">WebGIS SPBU</h1>
          <p class="text-blue-200 text-xs">Peta Sebaran Stasiun BBM</p>
        </div>
      </div>
    </div>

    <!-- Filter -->
    <div class="px-4 py-3 border-b border-gray-100">
      <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Filter Operasional</p>
      <div class="flex gap-2">
        <button onclick="setFilter('semua')" id="btn-semua"
          class="flex-1 py-1.5 text-sm rounded-lg font-medium transition-all filter-btn filter-active
                 bg-blue-600 text-white">
          Semua
        </button>
        <button onclick="setFilter('24jam')" id="btn-24jam"
          class="flex-1 py-1.5 text-sm rounded-lg font-medium transition-all filter-btn
                 bg-gray-100 text-gray-600 hover:bg-green-50 hover:text-green-700">
          24 Jam
        </button>
        <button onclick="setFilter('tidak')" id="btn-tidak"
          class="flex-1 py-1.5 text-sm rounded-lg font-medium transition-all filter-btn
                 bg-gray-100 text-gray-600 hover:bg-orange-50 hover:text-orange-700">
          Non-24 Jam
        </button>
      </div>
    </div>

    <!-- Stats -->
    <div class="px-4 py-2.5 border-b border-gray-100 flex gap-3">
      <div class="flex-1 text-center bg-blue-50 rounded-lg py-2">
        <p class="text-xl font-bold text-blue-700" id="stat-total">—</p>
        <p class="text-xs text-blue-500">Total</p>
      </div>
      <div class="flex-1 text-center bg-green-50 rounded-lg py-2">
        <p class="text-xl font-bold text-green-700" id="stat-24">—</p>
        <p class="text-xs text-green-500">24 Jam</p>
      </div>
      <div class="flex-1 text-center bg-orange-50 rounded-lg py-2">
        <p class="text-xl font-bold text-orange-700" id="stat-biasa">—</p>
        <p class="text-xs text-orange-500">Non-24 Jam</p>
      </div>
    </div>

    <!-- Search -->
    <div class="px-4 py-2.5 border-b border-gray-100">
      <div class="relative">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
             fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
        </svg>
        <input id="search-input" type="text" placeholder="Cari SPBU..."
          class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg
                 focus:outline-none focus:ring-2 focus:ring-blue-300">
      </div>
    </div>

    <!-- SPBU List -->
    <div id="spbu-list" class="flex-1 px-3 py-2 space-y-1.5">
      <div class="spinner"></div>
    </div>

    <!-- Footer -->
    <div class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
      <p class="text-xs text-gray-400">© 2026 WebGIS SPBU</p>
      <a href="admin.php"
         class="text-xs text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
             viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round"
                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6
                   a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
        Admin
      </a>
    </div>
  </aside>

  <!-- MAP -->
  <main class="flex-1 relative">
    <div id="map"></div>

    <!-- Legend -->
    <div class="absolute bottom-6 right-4 bg-white rounded-xl shadow-lg px-4 py-3 z-[1000]">
      <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Keterangan</p>
      <div class="flex items-center gap-2 mb-1">
        <span class="inline-block w-3 h-3 rounded-full bg-green-600 flex-shrink-0"></span>
        <span class="text-sm text-gray-700">Buka 24 Jam</span>
      </div>
      <div class="flex items-center gap-2">
        <span class="inline-block w-3 h-3 rounded-full bg-orange-600 flex-shrink-0"></span>
        <span class="text-sm text-gray-700">Non-24 Jam</span>
      </div>
    </div>
  </main>
</div>

<!-- ── SCRIPTS ─────────────────────────────────────────────────────────── -->
<script>
// ── State ──────────────────────────────────────────────────────────────────
let allSpbu    = [];
let markers    = {};
let activeFilter = 'semua';
let searchTerm   = '';

// ── Map Init ───────────────────────────────────────────────────────────────
const map = L.map('map', { zoomControl: false }).setView([-0.0263, 109.3425], 13);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 19
}).addTo(map);

L.control.zoom({ position: 'bottomleft' }).addTo(map);

// ── Marker Factory ─────────────────────────────────────────────────────────
function makeIcon(is24) {
    return L.divIcon({
        className: '',
        html: `<div class="spbu-marker ${is24 ? 'marker-24' : 'marker-biasa'}">
                 <span>${is24 ? '24' : ''}</span>
               </div>`,
        iconSize:   [36, 36],
        iconAnchor: [18, 36],
        popupAnchor:[0, -38]
    });
}

// ── Popup HTML ─────────────────────────────────────────────────────────────
function popupHtml(s) {
    const badge = s.is_24jam
        ? `<span class="inline-flex items-center gap-1 bg-green-100 text-green-700
                        text-xs font-semibold px-2 py-0.5 rounded-full">
             <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
               <path fill-rule="evenodd"
                 d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2
                    0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415
                    L11 9.586V6z" clip-rule="evenodd"/>
             </svg>
             Buka 24 Jam
           </span>`
        : `<span class="inline-flex items-center gap-1 bg-orange-100 text-orange-700
                        text-xs font-semibold px-2 py-0.5 rounded-full">
             Non-24 Jam
           </span>`;

    return `
      <div>
        <div class="px-4 py-3 border-b border-gray-100">
          <p class="font-bold text-gray-800 text-sm leading-snug">${s.nama}</p>
          <p class="text-xs text-gray-400 mt-0.5">Kode: ${s.kode}</p>
        </div>
        <div class="px-4 py-3 space-y-2">
          ${badge}
          <div class="flex items-start gap-1.5">
            <svg class="w-3.5 h-3.5 mt-0.5 text-gray-400 flex-shrink-0" fill="none"
                 stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827
                       0l-4.244-4.243a8 8 0 1111.314 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <p class="text-xs text-gray-500">${s.latitude.toFixed(6)}, ${s.longitude.toFixed(6)}</p>
          </div>
        </div>
      </div>`;
}

// ── Render List Sidebar ────────────────────────────────────────────────────
function renderList(data) {
    const el = document.getElementById('spbu-list');
    if (!data.length) {
        el.innerHTML = `<div class="text-center py-10">
          <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor"
               stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01
                     M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <p class="text-sm text-gray-400">Tidak ada SPBU ditemukan</p>
        </div>`;
        return;
    }

    el.innerHTML = data.map(s => `
      <div onclick="focusSpbu(${s.id})"
           class="flex items-start gap-3 p-3 rounded-xl cursor-pointer
                  hover:bg-blue-50 transition-colors group">
        <span class="mt-0.5 flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center
                     ${s.is_24jam ? 'bg-green-100' : 'bg-orange-100'}">
          <span class="text-xs font-bold ${s.is_24jam ? 'text-green-700' : 'text-orange-700'}">
            ${s.is_24jam ? '24' : '—'}
          </span>
        </span>
        <div class="min-w-0">
          <p class="text-sm font-semibold text-gray-800 truncate group-hover:text-blue-700">${s.nama}</p>
          <p class="text-xs text-gray-400">Kode: ${s.kode}</p>
          <span class="text-xs ${s.is_24jam ? 'text-green-600' : 'text-orange-500'}">
            ${s.is_24jam ? '● Buka 24 Jam' : '● Non-24 Jam'}
          </span>
        </div>
      </div>`).join('');
}

// ── Filter & Search Helpers ────────────────────────────────────────────────
function applyFilterSearch() {
    let data = allSpbu;
    if (activeFilter === '24jam')  data = data.filter(s => s.is_24jam);
    if (activeFilter === 'tidak')  data = data.filter(s => !s.is_24jam);
    if (searchTerm) {
        const q = searchTerm.toLowerCase();
        data = data.filter(s =>
            s.nama.toLowerCase().includes(q) || s.kode.toLowerCase().includes(q)
        );
    }

    // Show/hide markers
    Object.values(markers).forEach(m => map.removeLayer(m));
    markers = {};
    data.forEach(s => {
        const m = L.marker([s.latitude, s.longitude], { icon: makeIcon(s.is_24jam) })
            .bindPopup(popupHtml(s), { maxWidth: 260 });
        m.addTo(map);
        markers[s.id] = m;
    });

    renderList(data);
}

function setFilter(f) {
    activeFilter = f;

    document.querySelectorAll('.filter-btn').forEach(b => {
        b.classList.remove('bg-blue-600', 'text-white', 'bg-green-600',
                           'bg-orange-500', 'filter-active');
        b.classList.add('bg-gray-100', 'text-gray-600');
    });

    const map_ = { semua: ['bg-blue-600','text-white'],
                   '24jam': ['bg-green-600','text-white'],
                   tidak: ['bg-orange-500','text-white'] };
    const btn  = document.getElementById('btn-' + f);
    btn.classList.remove('bg-gray-100','text-gray-600');
    btn.classList.add(...map_[f], 'filter-active');

    applyFilterSearch();
}

function focusSpbu(id) {
    const m = markers[id];
    if (!m) return;
    map.flyTo(m.getLatLng(), 16, { duration: .8 });
    setTimeout(() => m.openPopup(), 850);
}

// ── Update Stats ───────────────────────────────────────────────────────────
function updateStats(data) {
    document.getElementById('stat-total').textContent = data.length;
    document.getElementById('stat-24').textContent    = data.filter(s => s.is_24jam).length;
    document.getElementById('stat-biasa').textContent = data.filter(s => !s.is_24jam).length;
}

// ── Load Data ──────────────────────────────────────────────────────────────
function loadSpbu() {
    fetch('api/spbu.php?filter=semua')
        .then(r => r.json())
        .then(res => {
            if (!res.success) throw new Error(res.message);
            allSpbu = res.data;
            updateStats(allSpbu);
            applyFilterSearch();
        })
        .catch(err => {
            document.getElementById('spbu-list').innerHTML =
                `<p class="text-sm text-red-500 text-center py-6">${err.message}</p>`;
        });
}

// ── Search Input ───────────────────────────────────────────────────────────
document.getElementById('search-input').addEventListener('input', function() {
    searchTerm = this.value.trim();
    applyFilterSearch();
});

// ── Boot ───────────────────────────────────────────────────────────────────
loadSpbu();
</script>
</body>
</html>
