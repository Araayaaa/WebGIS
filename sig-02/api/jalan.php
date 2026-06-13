<?php
require_once __DIR__ . '/../includes/crud.php';

handle_crud([
    'table'    => 'jalan',
    'geomType' => 'LineString',
    'fields'   => ['nama', 'jenis', 'kategori', 'deskripsi', 'warna'],
    'measure'  => function (array $geom): array {
        // LineString: coordinates = [ [lng,lat], ... ]
        [, $coords] = $geom;
        return [
            'panjang' => round(line_length($coords), 2),
        ];
    },
]);
