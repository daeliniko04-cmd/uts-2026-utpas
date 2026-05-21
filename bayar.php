<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['keranjang']) || empty($_SESSION['keranjang'])) {
    header('Location: index.php');
    exit;
}

$db = getDB();
$keranjang = $_SESSION['keranjang'];
$porsi = $_SESSION['porsi'] ?? 1;

$ids = implode(',', array_map('intval', array_keys($keranjang)));
$bahans = $db->query("SELECT * FROM bahan WHERE id IN ($ids)")->fetchAll();

$items = [];
$subtotal = 0;
foreach ($bahans as $b) {
    $qty = $keranjang[$b['id']] ?? 1;
    $b['qty'] = $qty;
    $b['subtotal'] = $b['harga'] * $qty;
    $subtotal += $b['subtotal'];
    $items[] = $b;
}
$total = $subtotal * $porsi;

// Setelah bayar, kosongkan keranjang
$_SESSION['keranjang'] = [];
$_SESSION['porsi'] = 1;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Berhasil — Jamuku</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --green-900: #14532d;
            --green-700: #15803d;
            --green-600: #16a34a;
            --green-500: #22c55e;
            --green-400: #4ade80;
            --green-300: #86efac;
            --green-200: #bbf7d0;
            --green-100: #dcfce7;
            --green-50:  #f0fdf4;
            --amber-300: #fcd34d;
            --cream: #fefdf8;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--cream);
            min-height: 100vh;
            display: flex; flex-direction: column; align-items: center;
            background-image: radial-gradient(ellipse at 50% 0%, rgba(134,239,172,0.3) 0%, transparent 60%);
        }
        nav {
            width: 100%;
            background: var(--green-900);
            padding: 0 2rem;
            display: flex; align-items: center; justify-content: space-between;
            height: 64px;
            box-shadow: 0 2px 20px rgba(5,46,22,0.4);
        }
        .nav-brand { font-family: 'Playfair Display', serif; font-size: 1.6rem; font-weight: 900; color: var(--green-300); }
        .nav-brand span { color: var(--amber-300); }
        .nav-links a { color: var(--green-200); text-decoration: none; margin-left: 1.5rem; font-weight: 500; font-size: 0.9rem; }
        .nav-links a:hover { color: var(--green-400); }

        .success-container {
            max-width: 560px;
            width: 100%;
            margin: 3rem auto;
            padding: 0 1.5rem;
        }

        .success-icon {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .success-circle {
            width: 90px; height: 90px;
            background: linear-gradient(135deg, var(--green-500), var(--green-400));
            border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 2.5rem;
            box-shadow: 0 8px 30px rgba(34,197,94,0.4);
            animation: pop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) both;
        }
        @keyframes pop {
            from { transform: scale(0); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .success-title {
            font-family: 'Playfair Display', serif;
            font-size: 2rem; font-weight: 900;
            color: var(--green-900);
            text-align: center;
            margin-bottom: 0.4rem;
        }
        .success-sub {
            text-align: center;
            color: #6b7280;
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }

        .receipt {
            background: white;
            border-radius: 20px;
            border: 1.5px solid var(--green-100);
            box-shadow: 0 8px 40px rgba(22,163,74,0.1);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        .receipt-header {
            background: var(--green-900);
            padding: 1.25rem 1.75rem;
            color: white;
            display: flex; align-items: center; justify-content: space-between;
        }
        .receipt-header h3 { font-family: 'Playfair Display', serif; font-size: 1rem; color: var(--green-300); }
        .receipt-header .time { font-size: 0.8rem; color: var(--green-400); }

        .receipt-body { padding: 1.25rem 1.75rem; }
        .receipt-item {
            display: flex; justify-content: space-between;
            padding: 0.6rem 0;
            border-bottom: 1px solid var(--green-50);
            font-size: 0.9rem;
        }
        .receipt-item:last-child { border-bottom: none; }
        .item-name { color: var(--green-900); font-weight: 500; }
        .item-detail { font-size: 0.78rem; color: #9ca3af; }
        .item-price { font-family: 'Playfair Display', serif; color: var(--green-700); font-weight: 700; text-align: right; }

        .receipt-footer {
            background: var(--green-50);
            padding: 1rem 1.75rem;
            border-top: 2px dashed var(--green-200);
        }
        .receipt-subtotal { display: flex; justify-content: space-between; margin-bottom: 0.4rem; font-size: 0.85rem; color: #6b7280; }
        .receipt-total {
            display: flex; justify-content: space-between; align-items: center;
            margin-top: 0.5rem;
        }
        .receipt-total-label { font-family: 'Playfair Display', serif; font-size: 1.1rem; font-weight: 700; color: var(--green-900); }
        .receipt-total-value { font-family: 'Playfair Display', serif; font-size: 1.8rem; font-weight: 900; color: var(--green-700); }

        .btn-home {
            display: block; width: 100%;
            background: var(--green-700);
            color: white; border: none;
            padding: 1rem;
            border-radius: 99px;
            font-family: 'DM Sans', sans-serif;
            font-size: 1rem; font-weight: 700;
            text-align: center; text-decoration: none;
            transition: all 0.2s;
            box-shadow: 0 4px 15px rgba(22,163,74,0.3);
            cursor: pointer;
        }
        .btn-home:hover { background: var(--green-600); transform: translateY(-1px); }
    </style>
</head>
<body>
<nav>
    <div class="nav-brand">Jamu<span>ku</span></div>
    <div class="nav-links">
        <a href="index.php">Pilih Bahan</a>
        <a href="keranjang.php">🛒 Keranjang</a>
        <a href="racikan.php">📜 Racikan</a>
    </div>
</nav>

<div class="success-container">
    <div class="success-icon">
        <div class="success-circle">✓</div>
    </div>
    <div class="success-title">Pembayaran Berhasil!</div>
    <div class="success-sub">Jamu segarmu sedang diracik. Terima kasih telah memesan di Jamuku!</div>

    <div class="receipt">
        <div class="receipt-header">
            <h3>🧾 Struk Pembelian</h3>
            <span class="time"><?= date('d M Y, H:i') ?> WIB</span>
        </div>
        <div class="receipt-body">
            <?php foreach ($items as $item): ?>
            <div class="receipt-item">
                <div>
                    <div class="item-name"><?= htmlspecialchars($item['nama']) ?></div>
                    <div class="item-detail"><?= $item['qty'] ?>x komposisi × <?= $porsi ?> porsi</div>
                </div>
                <div class="item-price">Rp <?= number_format($item['subtotal'] * $porsi, 0, ',', '.') ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="receipt-footer">
            <div class="receipt-subtotal">
                <span>Subtotal bahan</span>
                <span>Rp <?= number_format($subtotal, 0, ',', '.') ?></span>
            </div>
            <div class="receipt-subtotal">
                <span>Porsi</span>
                <span><?= $porsi ?>x</span>
            </div>
            <div class="receipt-total">
                <span class="receipt-total-label">Total Dibayar</span>
                <span class="receipt-total-value">Rp <?= number_format($total, 0, ',', '.') ?></span>
            </div>
        </div>
    </div>

    <a href="index.php" class="btn-home">🌿 Racik Jamu Lagi</a>
</div>
</body>
</html>