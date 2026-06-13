<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SIG &mdash; Mapping Tanah &amp; Jalan</title>

  <!-- TailwindCSS -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Leaflet -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

  <!-- Leaflet.draw (point & click drawing) -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css" />
  <script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>

  <style>
    #map { height: 100%; width: 100%; }
    .leaflet-draw-toolbar a { background-color: #fff; }
    /* Tooltip pengukuran saat menggambar */
    .leaflet-tooltip.measure-tip {
      background: #111827; color: #fff; border: 0; font-weight: 600;
      box-shadow: 0 1px 3px rgba(0,0,0,.4);
    }
  </style>
</head>
<body class="h-screen overflow-hidden bg-slate-100 text-slate-800">

  <div class="flex h-full">

    <!-- ============ SIDEBAR ============ -->
    <aside class="w-96 shrink-0 bg-white border-r border-slate-200 flex flex-col shadow-lg z-[1000]">
      <header class="px-5 py-4 bg-slate-900 text-white">
        <h1 class="text-lg font-bold flex items-center gap-2">
          <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
          SIG Mapping
        </h1>
        <p class="text-xs text-slate-300 mt-0.5">Pemetaan Tanah &amp; Jalan &mdash; point &amp; click</p>
      </header>

      <!-- Tombol Tambah -->
      <div class="px-4 py-3 border-b border-slate-200">
        <div class="grid grid-cols-2 gap-2">
          <button id="add-tanah" type="button"
                  class="flex items-center justify-center gap-1.5 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-sm font-semibold shadow-sm transition">
            <span class="text-base leading-none">＋</span> Tambah Tanah
          </button>
          <button id="add-jalan" type="button"
                  class="flex items-center justify-center gap-1.5 py-2.5 rounded-lg bg-red-600 hover:bg-red-700 active:bg-red-800 text-white text-sm font-semibold shadow-sm transition">
            <span class="text-base leading-none">＋</span> Tambah Jalan
          </button>
        </div>
        <p id="draw-hint" class="mt-2 text-xs text-slate-500 text-center">
          Klik tombol lalu gambar di peta. <span class="text-slate-400">Luas &amp; panjang otomatis.</span>
        </p>
      </div>

      <!-- Pencarian -->
      <div class="px-4 py-3 border-b border-slate-200">
        <div class="relative">
          <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">🔍</span>
          <input id="search" type="text" placeholder="Cari nama, pemilik, jenis…"
                 class="w-full pl-9 pr-8 py-2 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 outline-none">
          <button id="search-clear" class="hidden absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-lg leading-none">&times;</button>
        </div>
      </div>

      <!-- Tab -->
      <div class="flex border-b border-slate-200 text-sm font-medium">
        <button data-tab="tanah" class="tab-btn flex-1 py-2.5 border-b-2 border-emerald-500 text-emerald-600">
          🟩 Tanah <span id="count-tanah" class="ml-1 text-xs bg-emerald-100 text-emerald-700 rounded-full px-1.5">0</span>
        </button>
        <button data-tab="jalan" class="tab-btn flex-1 py-2.5 border-b-2 border-transparent text-slate-500 hover:text-slate-700">
          🟥 Jalan <span id="count-jalan" class="ml-1 text-xs bg-red-100 text-red-700 rounded-full px-1.5">0</span>
        </button>
      </div>

      <!-- Daftar -->
      <div class="flex-1 overflow-y-auto">
        <ul id="list-tanah" class="tab-panel divide-y divide-slate-100"></ul>
        <ul id="list-jalan" class="tab-panel hidden divide-y divide-slate-100"></ul>
      </div>

      <!-- Ringkasan -->
      <footer class="px-5 py-3 border-t border-slate-200 bg-slate-50 text-xs text-slate-600 space-y-1">
        <div class="flex justify-between"><span>Total luas tanah</span><b id="sum-luas">0 m²</b></div>
        <div class="flex justify-between"><span>Total panjang jalan</span><b id="sum-panjang">0 m</b></div>
      </footer>
    </aside>

    <!-- ============ PETA ============ -->
    <main class="relative flex-1">
      <div id="map"></div>
      <!-- badge pengukuran live -->
      <div id="live-measure"
           class="hidden absolute top-3 left-1/2 -translate-x-1/2 z-[1000] bg-slate-900 text-white text-sm font-semibold px-4 py-2 rounded-lg shadow-lg">
      </div>
    </main>
  </div>

  <!-- ============ MODAL FORM ============ -->
  <div id="modal" class="hidden fixed inset-0 z-[2000] flex items-center justify-center bg-black/40 p-4">
    <div class="bg-white w-full max-w-md rounded-xl shadow-2xl overflow-hidden">
      <div id="modal-head" class="px-5 py-4 text-white font-semibold flex items-center justify-between">
        <span id="modal-title">Simpan Data</span>
        <button id="modal-close" class="text-white/80 hover:text-white text-xl leading-none">&times;</button>
      </div>
      <form id="form" class="p-5 space-y-4">
        <input type="hidden" id="f-id">
        <input type="hidden" id="f-kind">
        <input type="hidden" id="f-geojson">

        <!-- Ukuran otomatis -->
        <div id="f-measure" class="rounded-lg bg-slate-50 border border-slate-200 p-3 text-sm grid grid-cols-2 gap-2"></div>

        <div>
          <label class="block text-sm font-medium mb-1">Nama <span class="text-red-500">*</span></label>
          <input id="f-nama" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 outline-none">
        </div>

        <!-- field khusus tanah -->
        <div class="kind-tanah">
          <label class="block text-sm font-medium mb-1">Pemilik</label>
          <input id="f-pemilik" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-400 outline-none">
        </div>
        <div class="kind-tanah">
          <label class="block text-sm font-medium mb-1">Kategori / Status Tanah</label>
          <select id="f-kategori-tanah" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-400 outline-none">
            <option value="">- pilih -</option>
            <option value="Sertifikat Hak Milik (SHM)">Sertifikat Hak Milik (SHM)</option>
            <option value="Sertifikat Hak Guna Bangunan (HGB)">Sertifikat Hak Guna Bangunan (HGB)</option>
            <option value="Sertifikat Hak Guna Usaha (HGU)">Sertifikat Hak Guna Usaha (HGU)</option>
            <option value="Sertifikat Hak Pakai (HP)">Sertifikat Hak Pakai (HP)</option>
          </select>
        </div>

        <!-- field khusus jalan -->
        <div class="kind-jalan hidden">
          <label class="block text-sm font-medium mb-1">Kategori Jalan</label>
          <select id="f-kategori-jalan" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-red-400 outline-none">
            <option value="">- pilih -</option>
            <option value="Jalan Nasional">Jalan Nasional</option>
            <option value="Jalan Provinsi">Jalan Provinsi</option>
            <option value="Jalan Kabupaten">Jalan Kabupaten</option>
          </select>
        </div>
        <div class="kind-jalan hidden">
          <label class="block text-sm font-medium mb-1">Jenis Jalan</label>
          <select id="f-jenis" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-red-400 outline-none">
            <option value="">- pilih -</option>
            <option>Aspal</option>
            <option>Beton / Cor</option>
            <option>Paving</option>
            <option>Tanah / Kerikil</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Deskripsi</label>
          <textarea id="f-deskripsi" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-400 outline-none"></textarea>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Warna</label>
          <input id="f-warna" type="color" value="#22c55e" class="h-9 w-16 rounded border border-slate-300 cursor-pointer">
        </div>

        <div class="flex gap-2 pt-1">
          <button type="button" id="modal-cancel" class="flex-1 py-2 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 text-sm font-medium">Batal</button>
          <button type="submit" id="modal-save" class="flex-1 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold">Simpan</button>
        </div>
      </form>
    </div>
  </div>

  <script src="assets/app.js?v=<?php echo filemtime(__DIR__ . '/assets/app.js'); ?>"></script>
</body>
</html>
