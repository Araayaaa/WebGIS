<?php
session_start();

// ── Konfigurasi password admin ─────────────────────────────────────────────
define('ADMIN_PASSWORD', 'admin123');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['logout'])) {
        session_destroy();
        header('Location: admin.php');
        exit;
    }
    if (isset($_POST['password']) && $_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['admin'] = true;
        header('Location: admin.php');
        exit;
    }
}

$isAdmin = !empty($_SESSION['admin']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — WebGIS SPBU</title>

<!-- TailwindCSS -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
    theme: {
        extend: {
            colors: { brand: { DEFAULT: '#1d4ed8', dark: '#1e3a8a' } }
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
    padding: 0; overflow: hidden;
  }
  .leaflet-popup-content { margin: 0; width: 260px !important; }
  .leaflet-popup-tip-container { display: none; }

  .spbu-marker {
    display: flex; align-items: center; justify-content: center;
    width: 36px; height: 36px;
    border-radius: 50% 50% 50% 0;
    transform: rotate(-45deg);
    border: 3px solid #fff;
    box-shadow: 0 3px 10px rgba(0,0,0,.35);
    cursor: grab;
    transition: transform .15s, box-shadow .15s;
  }
  .spbu-marker:hover { box-shadow: 0 6px 18px rgba(0,0,0,.45); }
  .spbu-marker span {
    transform: rotate(45deg);
    font-size: 11px; font-weight: 800;
    color: #fff; line-height: 1;
  }
  .marker-24    { background: #16a34a; }
  .marker-biasa { background: #ea580c; }
  .marker-temp  { background: #6d28d9; opacity: .85; }

  /* Cursor saat klik peta untuk tambah */
  .map-adding { cursor: crosshair !important; }

  /* Sidebar */
  #spbu-list { overflow-y: auto; max-height: calc(100vh - 280px); }

  /* Modal overlay */
  .modal-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,.5);
    display: flex; align-items: center; justify-content: center;
    z-index: 9999;
    opacity: 0; pointer-events: none; transition: opacity .2s;
  }
  .modal-overlay.show { opacity: 1; pointer-events: auto; }
  .modal-box {
    background: #fff; border-radius: 16px;
    width: 420px; max-width: 95vw;
    box-shadow: 0 20px 60px rgba(0,0,0,.25);
    transform: translateY(12px); transition: transform .2s;
  }
  .modal-overlay.show .modal-box { transform: translateY(0); }

  /* Toast */
  #toast {
    position: fixed; bottom: 24px; right: 24px;
    background: #1e293b; color: #fff;
    padding: 12px 20px; border-radius: 10px;
    font-size: 13px; z-index: 99999;
    display: none; align-items: center; gap-8px;
    box-shadow: 0 8px 24px rgba(0,0,0,.25);
    min-width: 220px;
  }

  /* Spinner */
  .spinner {
    width: 24px; height: 24px;
    border: 3px solid #e2e8f0; border-top-color: #1d4ed8;
    border-radius: 50%;
    animation: spin .7s linear infinite;
    margin: 20px auto;
  }
  @keyframes spin { to { transform: rotate(360deg); } }

  /* Drag hint badge */
  #drag-hint {
    position: absolute; top: 12px; left: 50%; transform: translateX(-50%);
    background: rgba(109,40,217,.9); color: #fff;
    border-radius: 99px; padding: 6px 16px; font-size: 13px;
    z-index: 1000; pointer-events: none;
    display: none;
  }
</style>
</head>
<body class="bg-gray-100 font-sans">

<?php if (!$isAdmin): ?>
<!-- ══════════════════════════════════════════════════════════════════════
     LOGIN PAGE
════════════════════════════════════════════════════════════════════════ -->
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br
            from-blue-900 via-blue-800 to-blue-600 p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
    <div class="bg-gradient-to-r from-blue-700 to-blue-900 px-8 py-6 text-white text-center">
      <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor"
           stroke-width="1.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2
                 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
      </svg>
      <h1 class="text-xl font-bold">Panel Admin</h1>
      <p class="text-blue-200 text-sm mt-0.5">WebGIS SPBU</p>
    </div>
    <form method="POST" class="px-8 py-6 space-y-4">
      <?php if (isset($_POST['password']) && $_POST['password'] !== ADMIN_PASSWORD): ?>
        <p class="bg-red-50 text-red-600 text-sm rounded-lg px-3 py-2 text-center">
          Password salah, coba lagi.
        </p>
      <?php endif; ?>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
        <input type="password" name="password" autofocus required
          class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm
                 focus:outline-none focus:ring-2 focus:ring-blue-400"
          placeholder="Masukkan password admin">
      </div>
      <button type="submit"
        class="w-full bg-blue-700 hover:bg-blue-800 text-white font-semibold
               py-2.5 rounded-xl transition-colors text-sm">
        Masuk
      </button>
      <a href="index.php"
         class="block text-center text-sm text-gray-400 hover:text-blue-600 mt-1">
        ← Kembali ke Peta Publik
      </a>
    </form>
  </div>
</div>

<?php else: ?>
<!-- ══════════════════════════════════════════════════════════════════════
     ADMIN DASHBOARD
════════════════════════════════════════════════════════════════════════ -->

<!-- Toast Notification -->
<div id="toast" class="flex items-center gap-2">
  <span id="toast-icon"></span>
  <span id="toast-msg"></span>
</div>

<!-- ── MODAL TAMBAH / EDIT ─────────────────────────────────────────────── -->
<div id="modal" class="modal-overlay" onclick="closeModal(event)">
  <div class="modal-box">
    <!-- Header -->
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
      <h2 id="modal-title" class="text-base font-bold text-gray-800">Tambah SPBU</h2>
      <button onclick="closeModal()" class="text-gray-400 hover:text-gray-700 transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <!-- Body -->
    <form id="spbu-form" class="px-6 py-5 space-y-4">
      <input type="hidden" id="f-id">
      <input type="hidden" id="f-lat">
      <input type="hidden" id="f-lng">

      <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1 uppercase tracking-wide">
          Kode SPBU <span class="text-red-500">*</span>
        </label>
        <input id="f-kode" type="text" required placeholder="Contoh: 31.401.06"
          class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm
                 focus:outline-none focus:ring-2 focus:ring-blue-400">
      </div>

      <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1 uppercase tracking-wide">
          Nama SPBU <span class="text-red-500">*</span>
        </label>
        <input id="f-nama" type="text" required placeholder="Contoh: SPBU Pertamina Sudirman"
          class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm
                 focus:outline-none focus:ring-2 focus:ring-blue-400">
      </div>

      <div>
        <label class="block text-xs font-semibold text-gray-600 mb-2 uppercase tracking-wide">
          Jam Operasional
        </label>
        <div class="flex gap-3">
          <label class="flex-1 flex items-center gap-2 border-2 border-gray-100 rounded-xl px-4 py-3
                        cursor-pointer has-[:checked]:border-green-500 has-[:checked]:bg-green-50
                        transition-all">
            <input type="radio" name="is24" value="1" id="r-24" class="accent-green-600">
            <span class="text-sm font-medium text-gray-700">Buka 24 Jam</span>
          </label>
          <label class="flex-1 flex items-center gap-2 border-2 border-gray-100 rounded-xl px-4 py-3
                        cursor-pointer has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50
                        transition-all">
            <input type="radio" name="is24" value="0" id="r-biasa" class="accent-orange-500">
            <span class="text-sm font-medium text-gray-700">Non-24 Jam</span>
          </label>
        </div>
      </div>

      <!-- Koordinat (info only) -->
      <div class="bg-gray-50 rounded-xl px-4 py-3">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Koordinat</p>
        <p id="f-coord-display" class="text-sm text-gray-700 font-mono">—</p>
        <p id="coord-hint" class="text-xs text-blue-500 mt-1 hidden">
          Klik peta untuk mengubah lokasi
        </p>
      </div>

      <!-- Error msg -->
      <p id="form-error" class="text-sm text-red-500 hidden"></p>
    </form>

    <!-- Footer -->
    <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3">
      <button onclick="closeModal()"
        class="px-5 py-2 text-sm font-medium text-gray-600 bg-gray-100
               hover:bg-gray-200 rounded-xl transition-colors">
        Batal
      </button>
      <button id="btn-submit" onclick="submitForm()"
        class="px-5 py-2 text-sm font-semibold text-white bg-blue-700
               hover:bg-blue-800 rounded-xl transition-colors flex items-center gap-2">
        <span id="btn-submit-text">Simpan</span>
        <svg id="btn-spin" class="hidden w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
      </button>
    </div>
  </div>
</div>

<!-- ── CONFIRM DELETE MODAL ────────────────────────────────────────────── -->
<div id="modal-del" class="modal-overlay" onclick="closeDeleteModal(event)">
  <div class="modal-box max-w-xs">
    <div class="px-6 py-5 text-center">
      <div class="w-14 h-14 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-3">
        <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor"
             stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round"
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5
                   7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
        </svg>
      </div>
      <h3 class="font-bold text-gray-800 mb-1">Hapus SPBU?</h3>
      <p class="text-sm text-gray-500" id="del-name"></p>
    </div>
    <div class="px-6 pb-5 flex gap-3">
      <button onclick="closeDeleteModal()"
        class="flex-1 py-2 text-sm font-medium text-gray-600 bg-gray-100
               hover:bg-gray-200 rounded-xl transition-colors">
        Batal
      </button>
      <button onclick="confirmDelete()"
        class="flex-1 py-2 text-sm font-semibold text-white bg-red-600
               hover:bg-red-700 rounded-xl transition-colors">
        Hapus
      </button>
    </div>
  </div>
</div>

<!-- ── LAYOUT ──────────────────────────────────────────────────────────── -->
<div class="flex h-screen">

  <!-- SIDEBAR -->
  <aside class="w-80 bg-white shadow-xl flex flex-col z-10 flex-shrink-0">

    <!-- Header -->
    <div class="bg-gradient-to-br from-blue-700 to-blue-900 text-white px-5 py-4">
      <div class="flex items-center justify-between mb-2">
        <div class="flex items-center gap-2">
          <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2"
               viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M3 10h2l1 2h13l1-2h2M5 12v6a1 1 0 001 1h12a1 1 0 001-1v-6
                     M9 22v-4h6v4M7 4h10l1 4H6L7 4z"/>
          </svg>
          <div>
            <h1 class="text-base font-bold leading-tight">Admin Panel</h1>
            <p class="text-blue-200 text-xs">WebGIS SPBU</p>
          </div>
        </div>
        <form method="POST">
          <button name="logout" type="submit"
            class="bg-blue-800 hover:bg-blue-900 text-xs text-blue-100
                   px-3 py-1.5 rounded-lg transition-colors">
            Keluar
          </button>
        </form>
      </div>

      <!-- Add button -->
      <button id="btn-add-mode" onclick="toggleAddMode()"
        class="w-full mt-1 bg-white text-blue-700 font-semibold text-sm py-2
               rounded-xl hover:bg-blue-50 transition-colors flex items-center
               justify-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
             viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        <span id="btn-add-label">Tambah SPBU (Klik Peta)</span>
      </button>
    </div>

    <!-- Filter -->
    <div class="px-4 py-3 border-b border-gray-100">
      <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Filter</p>
      <div class="flex gap-2">
        <button onclick="setFilter('semua')" id="btn-semua"
          class="flex-1 py-1.5 text-xs rounded-lg font-medium transition-all filter-btn
                 bg-blue-600 text-white">
          Semua
        </button>
        <button onclick="setFilter('24jam')" id="btn-24jam"
          class="flex-1 py-1.5 text-xs rounded-lg font-medium transition-all filter-btn
                 bg-gray-100 text-gray-600 hover:bg-green-50 hover:text-green-700">
          24 Jam
        </button>
        <button onclick="setFilter('tidak')" id="btn-tidak"
          class="flex-1 py-1.5 text-xs rounded-lg font-medium transition-all filter-btn
                 bg-gray-100 text-gray-600 hover:bg-orange-50 hover:text-orange-700">
          Non-24 Jam
        </button>
      </div>
    </div>

    <!-- Stats -->
    <div class="px-4 py-2.5 border-b border-gray-100 flex gap-2">
      <div class="flex-1 text-center bg-blue-50 rounded-lg py-2">
        <p class="text-lg font-bold text-blue-700" id="stat-total">—</p>
        <p class="text-xs text-blue-400">Total</p>
      </div>
      <div class="flex-1 text-center bg-green-50 rounded-lg py-2">
        <p class="text-lg font-bold text-green-700" id="stat-24">—</p>
        <p class="text-xs text-green-400">24 Jam</p>
      </div>
      <div class="flex-1 text-center bg-orange-50 rounded-lg py-2">
        <p class="text-lg font-bold text-orange-700" id="stat-biasa">—</p>
        <p class="text-xs text-orange-400">Non-24 Jam</p>
      </div>
    </div>

    <!-- SPBU List -->
    <div id="spbu-list" class="flex-1 px-3 py-2 space-y-1">
      <div class="spinner"></div>
    </div>

    <!-- Footer -->
    <div class="px-4 py-3 border-t border-gray-100">
      <a href="index.php"
         class="flex items-center justify-center gap-1.5 text-xs text-gray-500
                hover:text-blue-600 font-medium">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
             viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round"
                d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Lihat Tampilan Publik
      </a>
    </div>
  </aside>

  <!-- MAP -->
  <main class="flex-1 relative">
    <div id="map"></div>
    <div id="drag-hint">Seret marker untuk pindah lokasi</div>

    <!-- Legend -->
    <div class="absolute bottom-6 right-4 bg-white rounded-xl shadow-lg px-4 py-3 z-[1000]">
      <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Keterangan</p>
      <div class="flex items-center gap-2 mb-1">
        <span class="inline-block w-3 h-3 rounded-full bg-green-600"></span>
        <span class="text-xs text-gray-700">Buka 24 Jam</span>
      </div>
      <div class="flex items-center gap-2 mb-1">
        <span class="inline-block w-3 h-3 rounded-full bg-orange-600"></span>
        <span class="text-xs text-gray-700">Non-24 Jam</span>
      </div>
      <div class="flex items-center gap-2">
        <span class="inline-block w-3 h-3 rounded-full bg-purple-700"></span>
        <span class="text-xs text-gray-700">Posisi baru</span>
      </div>
    </div>
  </main>
</div>

<!-- ── SCRIPTS ─────────────────────────────────────────────────────────── -->
<script>
// ── State ──────────────────────────────────────────────────────────────────
let allSpbu      = [];
let markers      = {};       // id → Leaflet marker
let activeFilter = 'semua';
let addMode      = false;    // klik peta = tambah SPBU
let editingId    = null;     // ID yg sedang diedit
let deleteId     = null;
let pendingLat   = null;
let pendingLng   = null;
let tempMarker   = null;     // marker sementara saat picking lokasi baru di edit

// ── Map Init ───────────────────────────────────────────────────────────────
const map = L.map('map', { zoomControl: false }).setView([-0.0263, 109.3425], 13);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 19
}).addTo(map);

L.control.zoom({ position: 'bottomleft' }).addTo(map);

// ── Toast ──────────────────────────────────────────────────────────────────
function toast(msg, type = 'success') {
    const el  = document.getElementById('toast');
    const ico = document.getElementById('toast-icon');
    const txt = document.getElementById('toast-msg');

    ico.innerHTML = type === 'success'
        ? '<svg class="w-4 h-4 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>'
        : '<svg class="w-4 h-4 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>';

    txt.textContent = msg;
    el.style.display = 'flex';
    clearTimeout(el._t);
    el._t = setTimeout(() => { el.style.display = 'none'; }, 3000);
}

// ── Icons ──────────────────────────────────────────────────────────────────
function makeIcon(is24, isTemp = false) {
    const cls = isTemp ? 'marker-temp' : (is24 ? 'marker-24' : 'marker-biasa');
    const lbl = isTemp ? '?' : (is24 ? '24' : '');
    return L.divIcon({
        className: '',
        html: `<div class="spbu-marker ${cls}"><span>${lbl}</span></div>`,
        iconSize:   [36, 36],
        iconAnchor: [18, 36],
        popupAnchor:[0, -38]
    });
}

// ── Popup ──────────────────────────────────────────────────────────────────
function makePopup(s) {
    const badge = s.is_24jam
        ? `<span class="inline-flex items-center gap-1 bg-green-100 text-green-700
                        text-xs font-semibold px-2 py-0.5 rounded-full">
             Buka 24 Jam
           </span>`
        : `<span class="inline-flex items-center gap-1 bg-orange-100 text-orange-700
                        text-xs font-semibold px-2 py-0.5 rounded-full">
             Non-24 Jam
           </span>`;
    return `
      <div>
        <div class="px-4 py-3 border-b border-gray-100">
          <p class="font-bold text-gray-800 text-sm">${s.nama}</p>
          <p class="text-xs text-gray-400">Kode: ${s.kode}</p>
        </div>
        <div class="px-4 py-3 space-y-2">
          ${badge}
          <p class="text-xs text-gray-500 font-mono">
            ${s.latitude.toFixed(6)}, ${s.longitude.toFixed(6)}
          </p>
          <p class="text-xs text-purple-600 italic">
            Seret marker untuk pindah lokasi
          </p>
        </div>
        <div class="px-4 pb-3 flex gap-2">
          <button onclick="openEditModal(${s.id})"
            class="flex-1 py-1.5 text-xs font-semibold text-white bg-blue-600
                   hover:bg-blue-700 rounded-lg transition-colors">
            Edit
          </button>
          <button onclick="openDeleteModal(${s.id})"
            class="flex-1 py-1.5 text-xs font-semibold text-white bg-red-500
                   hover:bg-red-600 rounded-lg transition-colors">
            Hapus
          </button>
        </div>
      </div>`;
}

// ── Add Marker to Map ──────────────────────────────────────────────────────
function addMarker(s) {
    const m = L.marker([s.latitude, s.longitude], {
        icon: makeIcon(s.is_24jam),
        draggable: true
    });

    m.on('dragstart', () => {
        document.getElementById('drag-hint').style.display = 'block';
        m.closePopup();
    });

    m.on('dragend', (e) => {
        document.getElementById('drag-hint').style.display = 'none';
        const { lat, lng } = e.target.getLatLng();
        updateLocation(s.id, lat, lng);
    });

    m.bindPopup(makePopup(s), { maxWidth: 280 });
    m.addTo(map);
    markers[s.id] = m;
}

// ── Render Sidebar List ────────────────────────────────────────────────────
function renderList(data) {
    const el = document.getElementById('spbu-list');
    if (!data.length) {
        el.innerHTML = `<p class="text-sm text-gray-400 text-center py-8">Tidak ada data.</p>`;
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
        <div class="min-w-0 flex-1">
          <p class="text-sm font-semibold text-gray-800 truncate">${s.nama}</p>
          <p class="text-xs text-gray-400">Kode: ${s.kode}</p>
        </div>
        <div class="flex gap-1 flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
          <button onclick="event.stopPropagation(); openEditModal(${s.id})"
            class="p-1 rounded-lg bg-blue-100 hover:bg-blue-200 text-blue-600 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5
                       m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828
                       l8.586-8.586z"/>
            </svg>
          </button>
          <button onclick="event.stopPropagation(); openDeleteModal(${s.id})"
            class="p-1 rounded-lg bg-red-100 hover:bg-red-200 text-red-500 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858
                       L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
          </button>
        </div>
      </div>`).join('');
}

// ── Filter ─────────────────────────────────────────────────────────────────
function setFilter(f) {
    activeFilter = f;
    document.querySelectorAll('.filter-btn').forEach(b => {
        b.classList.remove('bg-blue-600','bg-green-600','bg-orange-500','text-white');
        b.classList.add('bg-gray-100','text-gray-600');
    });
    const colorMap = { semua:['bg-blue-600','text-white'],
                       '24jam':['bg-green-600','text-white'],
                       tidak:['bg-orange-500','text-white'] };
    const btn = document.getElementById('btn-' + f);
    btn.classList.remove('bg-gray-100','text-gray-600');
    btn.classList.add(...colorMap[f]);
    applyFilter();
}

function applyFilter() {
    let data = allSpbu;
    if (activeFilter === '24jam') data = data.filter(s => s.is_24jam);
    if (activeFilter === 'tidak') data = data.filter(s => !s.is_24jam);

    // Redraw markers
    Object.values(markers).forEach(m => map.removeLayer(m));
    markers = {};
    data.forEach(addMarker);
    renderList(data);
}

function updateStats() {
    document.getElementById('stat-total').textContent = allSpbu.length;
    document.getElementById('stat-24').textContent    = allSpbu.filter(s => s.is_24jam).length;
    document.getElementById('stat-biasa').textContent = allSpbu.filter(s => !s.is_24jam).length;
}

function focusSpbu(id) {
    const m = markers[id];
    if (!m) return;
    map.flyTo(m.getLatLng(), 16, { duration: .8 });
    setTimeout(() => m.openPopup(), 850);
}

// ── Load Data ──────────────────────────────────────────────────────────────
function loadSpbu() {
    fetch('api/spbu.php?filter=semua')
        .then(r => r.json())
        .then(res => {
            if (!res.success) throw new Error(res.message);
            allSpbu = res.data;
            updateStats();
            applyFilter();
        })
        .catch(err => toast(err.message, 'error'));
}

// ── Add Mode ───────────────────────────────────────────────────────────────
function toggleAddMode() {
    addMode = !addMode;
    const btn   = document.getElementById('btn-add-mode');
    const label = document.getElementById('btn-add-label');
    if (addMode) {
        btn.classList.replace('bg-white','bg-yellow-300');
        btn.classList.replace('text-blue-700','text-yellow-900');
        label.textContent = 'Mode Tambah AKTIF — Klik Peta';
        map.getContainer().classList.add('map-adding');
    } else {
        btn.classList.replace('bg-yellow-300','bg-white');
        btn.classList.replace('text-yellow-900','text-blue-700');
        label.textContent = 'Tambah SPBU (Klik Peta)';
        map.getContainer().classList.remove('map-adding');
        removeTempMarker();
    }
}

map.on('click', function(e) {
    if (!addMode) return;
    const { lat, lng } = e.latlng;
    pendingLat = lat;
    pendingLng = lng;
    removeTempMarker();
    tempMarker = L.marker([lat, lng], { icon: makeIcon(false, true) }).addTo(map);
    openAddModal(lat, lng);
});

function removeTempMarker() {
    if (tempMarker) { map.removeLayer(tempMarker); tempMarker = null; }
}

// ── Modal TAMBAH ───────────────────────────────────────────────────────────
function openAddModal(lat, lng) {
    editingId = null;
    document.getElementById('modal-title').textContent = 'Tambah SPBU Baru';
    document.getElementById('btn-submit-text').textContent = 'Tambah';
    document.getElementById('f-id').value   = '';
    document.getElementById('f-kode').value = '';
    document.getElementById('f-nama').value = '';
    document.querySelector('input[name="is24"][value="1"]').checked = true;
    document.getElementById('f-lat').value  = lat;
    document.getElementById('f-lng').value  = lng;
    document.getElementById('f-coord-display').textContent =
        `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
    document.getElementById('coord-hint').classList.add('hidden');
    document.getElementById('form-error').classList.add('hidden');
    showModal();
}

// ── Modal EDIT ─────────────────────────────────────────────────────────────
function openEditModal(id) {
    const s = allSpbu.find(x => x.id === id);
    if (!s) return;
    editingId = id;

    document.getElementById('modal-title').textContent = 'Edit SPBU';
    document.getElementById('btn-submit-text').textContent = 'Simpan Perubahan';
    document.getElementById('f-id').value   = s.id;
    document.getElementById('f-kode').value = s.kode;
    document.getElementById('f-nama').value = s.nama;
    document.querySelector(`input[name="is24"][value="${s.is_24jam ? 1 : 0}"]`).checked = true;
    document.getElementById('f-lat').value  = s.latitude;
    document.getElementById('f-lng').value  = s.longitude;
    document.getElementById('f-coord-display').textContent =
        `${s.latitude.toFixed(6)}, ${s.longitude.toFixed(6)}`;
    document.getElementById('coord-hint').classList.remove('hidden');
    document.getElementById('form-error').classList.add('hidden');

    // Tutup popup jika ada
    if (markers[id]) markers[id].closePopup();

    // Saat modal edit terbuka, klik peta bisa update koordinat
    map._editCoordListener = function(e) {
        if (!document.getElementById('modal').classList.contains('show')) return;
        const { lat, lng } = e.latlng;
        document.getElementById('f-lat').value = lat;
        document.getElementById('f-lng').value = lng;
        document.getElementById('f-coord-display').textContent =
            `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        removeTempMarker();
        tempMarker = L.marker([lat, lng], { icon: makeIcon(false, true) }).addTo(map);
    };
    map.on('click', map._editCoordListener);

    showModal();
}

function showModal() {
    document.getElementById('modal').classList.add('show');
}

function closeModal(e) {
    if (e && e.target !== document.getElementById('modal')) return;
    document.getElementById('modal').classList.remove('show');
    removeTempMarker();
    if (map._editCoordListener) {
        map.off('click', map._editCoordListener);
        map._editCoordListener = null;
    }
    if (addMode && !editingId) {
        // tutup add mode jika batal tambah
        toggleAddMode();
    }
}

// ── Submit Form ────────────────────────────────────────────────────────────
function submitForm() {
    const kode  = document.getElementById('f-kode').value.trim();
    const nama  = document.getElementById('f-nama').value.trim();
    const is24  = document.querySelector('input[name="is24"]:checked')?.value;
    const lat   = parseFloat(document.getElementById('f-lat').value);
    const lng   = parseFloat(document.getElementById('f-lng').value);
    const errEl = document.getElementById('form-error');

    if (!kode || !nama || is24 === undefined || isNaN(lat) || isNaN(lng)) {
        errEl.textContent = 'Semua field wajib diisi.';
        errEl.classList.remove('hidden');
        return;
    }

    errEl.classList.add('hidden');
    document.getElementById('btn-spin').classList.remove('hidden');
    document.getElementById('btn-submit').disabled = true;

    const body    = { kode, nama, is_24jam: is24 === '1', latitude: lat, longitude: lng };
    const isEdit  = !!editingId;
    if (isEdit) body.id = editingId;

    fetch('api/spbu.php', {
        method:  isEdit ? 'PUT' : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify(body)
    })
    .then(r => r.json())
    .then(res => {
        document.getElementById('btn-spin').classList.add('hidden');
        document.getElementById('btn-submit').disabled = false;

        if (!res.success) {
            errEl.textContent = res.message;
            errEl.classList.remove('hidden');
            return;
        }

        const s = res.data;
        if (isEdit) {
            // Update local array
            const idx = allSpbu.findIndex(x => x.id === s.id);
            if (idx >= 0) allSpbu[idx] = s;
            // Rebuild marker
            if (markers[s.id]) map.removeLayer(markers[s.id]);
            delete markers[s.id];
            addMarker(s);
        } else {
            allSpbu.push(s);
            addMarker(s);
        }

        updateStats();
        applyFilter();
        removeTempMarker();
        document.getElementById('modal').classList.remove('show');
        if (map._editCoordListener) {
            map.off('click', map._editCoordListener);
            map._editCoordListener = null;
        }
        if (addMode) toggleAddMode();
        toast(res.message);
    })
    .catch(() => {
        document.getElementById('btn-spin').classList.add('hidden');
        document.getElementById('btn-submit').disabled = false;
        errEl.textContent = 'Terjadi kesalahan, coba lagi.';
        errEl.classList.remove('hidden');
    });
}

// ── Update Location (Drag) ─────────────────────────────────────────────────
function updateLocation(id, lat, lng) {
    fetch('api/spbu.php', {
        method:  'PUT',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ id, latitude: lat, longitude: lng })
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) { toast(res.message, 'error'); return; }
        const idx = allSpbu.findIndex(x => x.id === id);
        if (idx >= 0) {
            allSpbu[idx].latitude  = lat;
            allSpbu[idx].longitude = lng;
        }
        // Refresh popup content
        if (markers[id]) {
            markers[id].setPopupContent(makePopup(allSpbu[idx]));
        }
        toast('Lokasi SPBU berhasil diperbarui');
    })
    .catch(() => toast('Gagal memperbarui lokasi', 'error'));
}

// ── Delete ─────────────────────────────────────────────────────────────────
function openDeleteModal(id) {
    deleteId = id;
    const s  = allSpbu.find(x => x.id === id);
    document.getElementById('del-name').textContent = s ? s.nama : '';
    document.getElementById('modal-del').classList.add('show');
    if (markers[id]) markers[id].closePopup();
}

function closeDeleteModal(e) {
    if (e && e.target !== document.getElementById('modal-del')) return;
    document.getElementById('modal-del').classList.remove('show');
    deleteId = null;
}

function confirmDelete() {
    if (!deleteId) return;
    fetch(`api/spbu.php?id=${deleteId}`, { method: 'DELETE' })
        .then(r => r.json())
        .then(res => {
            if (!res.success) { toast(res.message, 'error'); return; }
            if (markers[deleteId]) map.removeLayer(markers[deleteId]);
            delete markers[deleteId];
            allSpbu = allSpbu.filter(x => x.id !== deleteId);
            updateStats();
            applyFilter();
            document.getElementById('modal-del').classList.remove('show');
            deleteId = null;
            toast(res.message);
        })
        .catch(() => toast('Gagal menghapus SPBU', 'error'));
}

// ── Keyboard shortcut ──────────────────────────────────────────────────────
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        if (document.getElementById('modal').classList.contains('show')) {
            closeModal();
        } else if (document.getElementById('modal-del').classList.contains('show')) {
            closeDeleteModal();
        } else if (addMode) {
            toggleAddMode();
        }
    }
    if (e.key === 'Enter' && document.getElementById('modal').classList.contains('show')) {
        submitForm();
    }
});

// ── Boot ───────────────────────────────────────────────────────────────────
loadSpbu();
</script>
<?php endif; ?>
</body>
</html>
