<?php
/**
 * Landing page — daftar proyek SIG.
 * Setiap kartu mengarah ke folder proyek masing-masing.
 */
$projects = [
    [
        'slug'  => 'sig-01',
        'title' => 'WebGIS SPBU',
        'desc'  => 'Peta sebaran Stasiun Pengisian Bahan Bakar Umum (SPBU).',
        'tags'  => ['Leaflet', 'PHP', 'MySQL'],
        'icon'  => '⛽',
        'from'  => 'from-rose-500',
        'to'    => 'to-orange-500',
    ],
    [
        'slug'  => 'sig-02',
        'title' => 'SIG Mapping Tanah & Jalan',
        'desc'  => 'Pemetaan point & click: bidang tanah (polygon) dan jalan (garis), luas/panjang otomatis.',
        'tags'  => ['Leaflet.draw', 'PHP', 'MySQL'],
        'icon'  => '🗺️',
        'from'  => 'from-emerald-500',
        'to'    => 'to-teal-500',
    ],
    [
        'slug'  => 'sig-03',
        'title' => 'Poverty-Mapping GIS',
        'desc'  => 'Distribusi bantuan sosial: peta sebaran rumah penerima, pusat distribusi, dan laporan jangkauan radius.',
        'tags'  => ['Leaflet', 'PHP', 'MySQL'],
        'icon'  => '🕌',
        'from'  => 'from-indigo-500',
        'to'    => 'to-violet-500',
    ],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Proyek SIG</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
    <div class="max-w-6xl mx-auto px-6 py-16">
        <header class="mb-12 text-center">
            <h1 class="text-4xl md:text-5xl font-bold tracking-tight">
                Daftar Proyek <span class="bg-gradient-to-r from-emerald-400 to-cyan-400 bg-clip-text text-transparent">SIG</span>
            </h1>
            <p class="mt-3 text-slate-400">Pilih salah satu proyek untuk membukanya.</p>
        </header>

        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($projects as $p): ?>
            <a href="<?= htmlspecialchars($p['slug']) ?>/"
               class="group block rounded-2xl border border-slate-800 bg-slate-900/60 p-6
                      transition duration-200 hover:-translate-y-1 hover:border-slate-600
                      hover:shadow-xl hover:shadow-black/40 focus:outline-none focus:ring-2 focus:ring-emerald-400">
                <div class="mb-4 inline-flex h-14 w-14 items-center justify-center rounded-xl
                            bg-gradient-to-br <?= $p['from'] ?> <?= $p['to'] ?> text-2xl shadow-lg">
                    <?= $p['icon'] ?>
                </div>
                <div class="mb-1 text-xs font-mono uppercase tracking-widest text-slate-500">
                    <?= htmlspecialchars($p['slug']) ?>
                </div>
                <h2 class="text-xl font-semibold group-hover:text-emerald-300">
                    <?= htmlspecialchars($p['title']) ?>
                </h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-400">
                    <?= htmlspecialchars($p['desc']) ?>
                </p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <?php foreach ($p['tags'] as $tag): ?>
                    <span class="rounded-full bg-slate-800 px-2.5 py-0.5 text-xs text-slate-300">
                        <?= htmlspecialchars($tag) ?>
                    </span>
                    <?php endforeach; ?>
                </div>
                <div class="mt-5 inline-flex items-center gap-1 text-sm font-medium text-emerald-400">
                    Buka proyek
                    <span class="transition-transform group-hover:translate-x-1">&rarr;</span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <footer class="mt-16 text-center text-xs text-slate-600">
            Laragon &middot; <?= date('Y') ?>
        </footer>
    </div>
</body>
</html>
