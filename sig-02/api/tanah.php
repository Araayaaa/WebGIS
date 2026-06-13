<?php
require_once __DIR__ . '/../includes/crud.php';

handle_crud([
    'table'    => 'tanah',
    'geomType' => 'Polygon',
    'fields'   => ['nama', 'pemilik', 'kategori', 'deskripsi', 'warna'],
    'measure'  => function (array $geom): array {
        // Polygon: coordinates = [ring, ...]; ring pertama = outer ring.
        [, $coords] = $geom;
        $outer = $coords[0] ?? [];
        return [
            'luas'     => round(ring_area($outer), 2),
            'keliling' => round(ring_perimeter($outer), 2),
        ];
    },
]);
