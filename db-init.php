<?php
// HAPUS FILE INI SETELAH DIPAKAI!
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die("<b style='color:red'>Koneksi gagal: " . $conn->connect_error . "</b>");
}

$sqls = file_get_contents(__DIR__ . '/database/init.sql');
$statements = array_filter(array_map('trim', explode(';', $sqls)));

$success = 0;
$errors  = [];

foreach ($statements as $sql) {
    if (empty($sql)) continue;
    if ($conn->query($sql) === TRUE) {
        $success++;
    } else {
        $errors[] = htmlspecialchars($sql) . "<br><b style='color:red'>" . $conn->error . "</b>";
    }
}

echo "<h2>DB Init Result</h2>";
echo "<p style='color:green'>✅ $success statements OK</p>";
if (!empty($errors)) {
    echo "<h3 style='color:orange'>Errors (" . count($errors) . "):</h3>";
    foreach ($errors as $e) echo "<pre style='background:#fee;padding:8px'>$e</pre>";
} else {
    echo "<p style='color:green'><b>✅ Semua database berhasil dibuat!</b></p>";
    echo "<p style='color:red'><b>⚠️ Sekarang hapus file ini dari repo!</b></p>";
}
$conn->close();