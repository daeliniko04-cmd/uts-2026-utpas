<?php
// init_db.php — Jalankan sekali untuk membuat database

$dbPath = __DIR__ . '/jamuku.db';

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = file_get_contents(__DIR__ . '/init.sql');
    $db->exec($sql);

    echo "✅ Database berhasil dibuat di: $dbPath\n";
} catch (PDOException $e) {
    echo "❌ Gagal: " . $e->getMessage() . "\n";
}