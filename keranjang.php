<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['keranjang'])) $_SESSION['keranjang'] = [];
if (!isset($_SESSION['porsi'])) $_SESSION['porsi'] = 1;

$db = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'hapus') {
        $id = intval($_POST['bahan_id']);
        unset($_SESSION['keranjang'][$id]);

    } elseif ($action === 'tambah_komposisi') {
        $id = intval($_POST['bahan_id']);
        if (isset($_SESSION['keranjang'][$id])) {
            $_SESSION['keranjang'][$id]++;
        }

    } elseif ($action === 'kurang_komposisi') {
        $id = intval($_POST['bahan_id']);
        if (isset($_SESSION['keranjang'][$id])) {
            $_SESSION['keranjang'][$id]--;
            if ($_SESSION['keranjang'][$id] <= 0) {
                unset($_SESSION['keranjang'][$id]);
            }
        }

    } elseif ($action === 'set_porsi') {
        $_SESSION['porsi'] = max(1, intval($_POST['porsi']));

    } elseif ($action === 'bayar') {
        header('Location: bayar.php');
        exit;

    } elseif ($action === 'kosongkan') {
        $_SESSION['keranjang'] = [];
        $_SESSION['porsi'] = 1;
    }

    header('Location: keranjang.php');
    exit;
}

// Ambil data bahan dari DB
$keranjang = $_SESSION['keranjang'];
$porsi = $_SESSION['porsi'];
$items = [];
$subtotal = 0;

if (!empty($keranjang)) {
    $ids = implode(',', array_map('intval', array_keys($keranjang)));
    $bahans = $db->query("SELECT * FROM bahan WHERE id IN ($ids)")->fetchAll();
    foreach ($bahans as $b) {
        $qty = $keranjang[$b['id']] ?? 1;
        $b['qty'] = $qty;
        $b['subtotal'] = $b['harga'] * $qty;
        $subtotal += $b['subtotal'];
        $items[] = $b;
    }
}

$total = $subtotal * $porsi;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang — Jamuku</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --green-950: #052e16;
            --green-900: #14532d;
            --green-800: #166534;
            --green-700: #15803d;
            --green-600: #16a34a;
            --green-500: #22c55e;
            --green-400: #4ade80;
            --green-300: #86efac;
            --green-200: #bbf7d0;
            --green-100: #dcfce7;
            --green-50:  #f0fdf4;
            --amber-400: #fbbf24;
            --amber-300: #fcd34d;
            --red-500:   #ef4444;
            --red-100:   #fee2e2;
            --cream:     #fefdf8;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--cream);
            min-height: 100vh;
            background-image:
                radial-gradient(ellipse at 20% 0%, rgba(134,239,172,0.2) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 100%, rgba(74,222,128,0.12) 0%, transparent 50%);
        }

        nav {
            background: var(--green-900);
            padding: 0 2rem;
            display: flex; align-items: center; justify-content: space-between;
            height: 64px;
            position: sticky; top: 0; z-index: 100;
            box-shadow: 0 2px 20px rgba(5,46,22,0.4);
        }
        .nav-brand { font-family: 'Playfair Display', serif; font-size: 1.6rem; font-weight: 900; color: var(--green-300); }
        .nav-brand span { color: var(--amber-300); }
        .nav-links a { color: var(--green-200); text-decoration: none; margin-left: 1.5rem; font-weight: 500; font-size: 0.9rem; transition: color 0.2s; }
        .nav-links a:hover, .nav-links a.active { color: var(--green-400); }

        .container { max-width: 860px; margin: 0 auto; padding: 2.5rem 1.5rem 4rem; }

        .page-title {
            font-family: 'Playfair Display', serif;
            font-size: 2rem; font-weight: 900;
            color: var(--green-900);
            margin-bottom: 0.3rem;
        }
        .page-sub { color: #6b7280; font-size: 0.9rem; margin-bottom: 2rem; }

        /* PORSI ROW */
        .porsi-row {
            background: white;
            border: 1.5px solid var(--green-200);
            border-radius: 14px;
            padding: 1rem 1.5rem;
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 12px rgba(22,163,74,0.1);
            flex-wrap: wrap; gap: 0.75rem;
        }
        .porsi-row label { font-weight: 600; color: var(--green-900); }
        .porsi-controls { display: flex; align-items: center; gap: 0.75rem; }
        .porsi-btn {
            width: 34px; height: 34px; border-radius: 50%;
            border: 2px solid var(--green-500); background: white;
            color: var(--green-700); font-size: 1rem; font-weight: 700;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: all 0.15s;
        }
        .porsi-btn:hover { background: var(--green-500); color: white; }
        #porsiDisplay { font-family: 'Playfair Display', serif; font-size: 1.4rem; font-weight: 700; color: var(--green-800); min-width: 32px; text-align: center; }

        /* EMPTY STATE */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #9ca3af;
        }
        .empty-state .icon { font-size: 3.5rem; margin-bottom: 1rem; }
        .empty-state h3 { font-family: 'Playfair Display', serif; font-size: 1.4rem; color: var(--green-800); margin-bottom: 0.5rem; }
        .empty-state p { margin-bottom: 1.5rem; }
        .btn-back { display: inline-block; background: var(--green-700); color: white; padding: 0.7rem 1.8rem; border-radius: 99px; text-decoration: none; font-weight: 600; transition: background 0.2s; }
        .btn-back:hover { background: var(--green-600); }

        /* TABLE */
        .cart-table {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            border: 1.5px solid var(--green-100);
            box-shadow: 0 4px 20px rgba(22,163,74,0.08);
            margin-bottom: 1.5rem;
        }
        .cart-table table {
            width: 100%; border-collapse: collapse;
        }
        .cart-table thead tr {
            background: var(--green-900);
        }
        .cart-table thead th {
            color: var(--green-200);
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            padding: 0.9rem 1.25rem;
            text-align: left;
        }
        .cart-table tbody tr {
            border-bottom: 1px solid var(--green-50);
            transition: background 0.15s;
        }
        .cart-table tbody tr:last-child { border-bottom: none; }
        .cart-table tbody tr:hover { background: var(--green-50); }
        .cart-table td { padding: 1rem 1.25rem; vertical-align: middle; }

        .bahan-nama-cell { font-weight: 600; color: var(--green-900); }
        .bahan-jenis-cell { font-size: 0.78rem; color: #9ca3af; margin-top: 0.2rem; }
        .harga-cell { font-family: 'Playfair Display', serif; font-size: 0.9rem; color: var(--green-700); font-weight: 700; }
        .subtotal-cell { font-family: 'Playfair Display', serif; font-size: 0.9rem; color: var(--green-800); font-weight: 700; }

        /* QTY Controls */
        .qty-controls { display: flex; align-items: center; gap: 0.5rem; }
        .qty-btn {
            width: 28px; height: 28px; border-radius: 50%;
            border: 1.5px solid var(--green-300); background: white;
            color: var(--green-700); font-size: 0.85rem; font-weight: 700;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: all 0.15s;
        }
        .qty-btn:hover { background: var(--green-500); color: white; border-color: var(--green-500); }
        .qty-num { font-weight: 700; color: var(--green-900); min-width: 24px; text-align: center; font-size: 0.95rem; }

        /* Delete btn */
        .btn-hapus {
            background: var(--red-100);
            color: var(--red-500);
            border: none; border-radius: 8px;
            padding: 0.4rem 0.75rem;
            font-size: 0.8rem; font-weight: 600;
            cursor: pointer; transition: all 0.15s;
        }
        .btn-hapus:hover { background: var(--red-500); color: white; }

        /* SUMMARY */
        .summary-card {
            background: var(--green-900);
            border-radius: 16px;
            padding: 1.5rem 1.75rem;
            color: white;
            margin-bottom: 1rem;
        }
        .summary-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; font-size: 0.9rem; color: var(--green-300); }
        .summary-row:last-child { margin-bottom: 0; }
        .summary-divider { border: none; border-top: 1px solid rgba(255,255,255,0.15); margin: 0.75rem 0; }
        .summary-total { display: flex; justify-content: space-between; align-items: center; }
        .summary-total-label { font-family: 'Playfair Display', serif; font-size: 1.1rem; font-weight: 700; color: white; }
        .summary-total-value { font-family: 'Playfair Display', serif; font-size: 1.6rem; font-weight: 900; color: var(--amber-300); }

        /* ACTION BUTTONS */
        .action-row { display: flex; gap: 0.75rem; flex-wrap: wrap; }
        .btn-bayar {
            flex: 1;
            background: var(--green-500);
            color: white; border: none;
            padding: 0.9rem 2rem; border-radius: 99px;
            font-family: 'DM Sans', sans-serif;
            font-size: 1rem; font-weight: 700;
            cursor: pointer; transition: all 0.2s;
            box-shadow: 0 4px 15px rgba(34,197,94,0.4);
            min-width: 180px;
        }
        .btn-bayar:hover { background: var(--green-400); transform: translateY(-1px); }
        .btn-lanjut {
            background: white;
            color: var(--green-800);
            border: 2px solid var(--green-200);
            padding: 0.9rem 1.5rem; border-radius: 99px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem; font-weight: 600;
            cursor: pointer; transition: all 0.2s;
            text-decoration: none; display: inline-flex; align-items: center;
        }
        .btn-lanjut:hover { border-color: var(--green-400); color: var(--green-700); }
        .btn-kosong {
            background: transparent;
            color: #9ca3af;
            border: none;
            font-size: 0.85rem; font-weight: 500;
            cursor: pointer; transition: color 0.15s; text-decoration: underline;
            padding: 0.5rem;
        }
        .btn-kosong:hover { color: var(--red-500); }
    </style>
</head>
<body>
<nav>
    <div class="nav-brand">Jamu<span>ku</span></div>
    <div class="nav-links">
        <a href="index.php">Pilih Bahan</a>
        <a href="keranjang.php" class="active">🛒 Keranjang</a>
        <a href="racikan.php">📜 Racikan</a>
    </div>
</nav>

<div class="container">
    <div class="page-title">🛒 Keranjang Belanja</div>
    <div class="page-sub">Tinjau bahan yang dipilih dan atur jumlah komposisi sesuai selera.</div>

    <?php if (empty($items)): ?>
    <div class="empty-state">
        <div class="icon">🌿</div>
        <h3>Keranjang Kosong</h3>
        <p>Belum ada bahan yang dipilih. Mulai racik jamu favoritmu!</p>
        <a href="index.php" class="btn-back">← Pilih Bahan</a>
    </div>
    <?php else: ?>

    <!-- Porsi -->
    <div class="porsi-row">
        <label>⚗️ Jumlah Porsi</label>
        <div class="porsi-controls">
            <button type="button" class="porsi-btn" onclick="changePorsi(-1)">−</button>
            <div id="porsiDisplay"><?= $porsi ?></div>
            <button type="button" class="porsi-btn" onclick="changePorsi(1)">+</button>
        </div>
        <form method="POST" id="porsiForm">
            <input type="hidden" name="action" value="set_porsi">
            <input type="hidden" name="porsi" id="porsiInput" value="<?= $porsi ?>">
        </form>
    </div>

    <!-- Tabel Bahan -->
    <div class="cart-table">
        <table>
            <thead>
                <tr>
                    <th>Bahan</th>
                    <th>Harga Satuan</th>
                    <th>Komposisi</th>
                    <th>Subtotal</th>
                    <th>Hapus</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <div class="bahan-nama-cell"><?= htmlspecialchars($item['nama']) ?></div>
                        <div class="bahan-jenis-cell"><?= htmlspecialchars($item['jenis']) ?></div>
                    </td>
                    <td class="harga-cell">Rp <?= number_format($item['harga'], 0, ',', '.') ?></td>
                    <td>
                        <div class="qty-controls">
                            <form method="POST" style="display:contents">
                                <input type="hidden" name="action" value="kurang_komposisi">
                                <input type="hidden" name="bahan_id" value="<?= $item['id'] ?>">
                                <button type="submit" class="qty-btn">−</button>
                            </form>
                            <span class="qty-num"><?= $item['qty'] ?>x</span>
                            <form method="POST" style="display:contents">
                                <input type="hidden" name="action" value="tambah_komposisi">
                                <input type="hidden" name="bahan_id" value="<?= $item['id'] ?>">
                                <button type="submit" class="qty-btn">+</button>
                            </form>
                        </div>
                    </td>
                    <td class="subtotal-cell">Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="action" value="hapus">
                            <input type="hidden" name="bahan_id" value="<?= $item['id'] ?>">
                            <button type="submit" class="btn-hapus">🗑 Hapus</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Summary -->
    <div class="summary-card">
        <div class="summary-row">
            <span>Subtotal bahan</span>
            <span>Rp <?= number_format($subtotal, 0, ',', '.') ?></span>
        </div>
        <div class="summary-row">
            <span>Jumlah porsi</span>
            <span><?= $porsi ?>x</span>
        </div>
        <hr class="summary-divider">
        <div class="summary-total">
            <span class="summary-total-label">Total Pembayaran</span>
            <span class="summary-total-value">Rp <?= number_format($total, 0, ',', '.') ?></span>
        </div>
    </div>

    <!-- Actions -->
    <div class="action-row">
        <a href="index.php" class="btn-lanjut">← Tambah Bahan</a>
        <form method="POST" style="flex:1">
            <input type="hidden" name="action" value="bayar">
            <button type="submit" class="btn-bayar">💳 Bayar Sekarang</button>
        </form>
    </div>
    <div style="text-align:right; margin-top: 0.75rem;">
        <form method="POST">
            <input type="hidden" name="action" value="kosongkan">
            <button type="submit" class="btn-kosong">Kosongkan keranjang</button>
        </form>
    </div>
    <?php endif; ?>
</div>

<script>
let porsi = <?= $porsi ?>;
const form = document.getElementById('porsiForm');
let timeout;

function changePorsi(delta) {
    porsi = Math.max(1, porsi + delta);
    document.getElementById('porsiDisplay').textContent = porsi;
    document.getElementById('porsiInput').value = porsi;
    clearTimeout(timeout);
    timeout = setTimeout(() => form.submit(), 600);
}
</script>
</body>
</html>