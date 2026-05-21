<?php
require_once 'db.php';

// Inisialisasi session
session_start();
if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}
if (!isset($_SESSION['porsi'])) {
    $_SESSION['porsi'] = 1;
}

// Handle tambah ke keranjang
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'tambah') {
        $bahan_ids = $_POST['bahan_ids'] ?? [];
        $porsi = max(1, intval($_POST['porsi'] ?? 1));
        $_SESSION['porsi'] = $porsi;

        foreach ($bahan_ids as $id) {
            $id = intval($id);
            if (!isset($_SESSION['keranjang'][$id])) {
                $_SESSION['keranjang'][$id] = 1;
            }
        }
        header('Location: keranjang.php');
        exit;
    }
}

$db = getDB();
$bahans = $db->query("SELECT * FROM bahan ORDER BY jenis, nama")->fetchAll();

$jenisList = [];
foreach ($bahans as $b) {
    $jenisList[$b['jenis']][] = $b;
}

$jenisIcon = [
    'Bahan utama'    => '🌿',
    'Rempah tambahan'=> '🫚',
    'Pemanis'        => '🍯',
    'Bahan tambahan' => '🍋',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jamuku — Racik Jamu Sendiri</title>
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
            --cream:     #fefdf8;
            --shadow-green: 0 4px 24px rgba(22,163,74,0.15);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--cream);
            min-height: 100vh;
            background-image:
                radial-gradient(ellipse at 20% 0%, rgba(134,239,172,0.25) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 100%, rgba(74,222,128,0.15) 0%, transparent 50%);
        }

        /* NAV */
        nav {
            background: var(--green-900);
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 64px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 20px rgba(5,46,22,0.4);
        }
        .nav-brand {
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem;
            font-weight: 900;
            color: var(--green-300);
            letter-spacing: -0.5px;
        }
        .nav-brand span { color: var(--amber-300); }
        .nav-links a {
            color: var(--green-200);
            text-decoration: none;
            margin-left: 1.5rem;
            font-weight: 500;
            font-size: 0.9rem;
            transition: color 0.2s;
        }
        .nav-links a:hover, .nav-links a.active { color: var(--green-400); }

        /* HERO */
        .hero {
            text-align: center;
            padding: 3.5rem 2rem 2rem;
        }
        .hero-badge {
            display: inline-block;
            background: var(--green-100);
            color: var(--green-800);
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 0.35rem 1rem;
            border-radius: 99px;
            margin-bottom: 1rem;
            border: 1px solid var(--green-300);
        }
        .hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2rem, 5vw, 3.2rem);
            font-weight: 900;
            color: var(--green-900);
            line-height: 1.1;
            margin-bottom: 0.75rem;
        }
        .hero h1 em { color: var(--green-600); font-style: normal; }
        .hero p {
            color: #5a6a5a;
            font-size: 1rem;
            max-width: 480px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* MAIN LAYOUT */
        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 1.5rem 4rem;
        }

        form { display: contents; }

        /* PORSI BAR */
        .porsi-bar {
            background: white;
            border: 1.5px solid var(--green-200);
            border-radius: 16px;
            padding: 1.25rem 1.75rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-green);
            flex-wrap: wrap;
        }
        .porsi-bar label {
            font-weight: 600;
            color: var(--green-900);
            font-size: 0.95rem;
            white-space: nowrap;
        }
        .porsi-bar label span { color: var(--green-600); }
        .porsi-controls {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .porsi-btn {
            width: 36px; height: 36px;
            border-radius: 50%;
            border: 2px solid var(--green-500);
            background: white;
            color: var(--green-700);
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.15s;
            line-height: 1;
        }
        .porsi-btn:hover { background: var(--green-500); color: white; }
        #porsiDisplay {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--green-800);
            min-width: 36px;
            text-align: center;
        }
        #porsiInput { display: none; }

        /* SECTION */
        .section-label {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin-bottom: 1rem;
        }
        .section-label h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            color: var(--green-900);
            font-weight: 700;
        }
        .section-label .badge {
            background: var(--green-700);
            color: white;
            font-size: 0.7rem;
            padding: 0.2rem 0.6rem;
            border-radius: 99px;
            font-weight: 600;
        }
        .section-divider {
            border: none;
            border-top: 1.5px solid var(--green-100);
            margin: 1.5rem 0 2rem;
        }

        /* GRID BAHAN */
        .bahan-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .bahan-card {
            position: relative;
            background: white;
            border: 2px solid var(--green-100);
            border-radius: 14px;
            padding: 1.1rem 1.1rem 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            user-select: none;
            overflow: hidden;
        }
        .bahan-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, var(--green-50), transparent);
            opacity: 0;
            transition: opacity 0.2s;
        }
        .bahan-card:hover {
            border-color: var(--green-400);
            box-shadow: 0 4px 20px rgba(34,197,94,0.15);
            transform: translateY(-2px);
        }
        .bahan-card:hover::before { opacity: 1; }
        .bahan-card.selected {
            border-color: var(--green-500);
            background: linear-gradient(135deg, var(--green-50) 0%, white 100%);
            box-shadow: 0 4px 20px rgba(34,197,94,0.25);
        }
        .bahan-card.selected::after {
            content: '✓';
            position: absolute;
            top: 0.6rem; right: 0.7rem;
            width: 22px; height: 22px;
            background: var(--green-500);
            color: white;
            border-radius: 50%;
            font-size: 0.7rem;
            font-weight: 700;
            display: flex; align-items: center; justify-content: center;
        }
        .bahan-card input[type="checkbox"] { display: none; }

        .bahan-nama {
            font-weight: 600;
            color: var(--green-900);
            font-size: 0.95rem;
            margin-bottom: 0.35rem;
        }
        .bahan-desc {
            font-size: 0.75rem;
            color: #6b7280;
            line-height: 1.5;
            margin-bottom: 0.6rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .bahan-harga {
            font-family: 'Playfair Display', serif;
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--green-700);
        }

        /* STICKY BOTTOM */
        .sticky-bottom {
            position: sticky;
            bottom: 0;
            background: white;
            border-top: 2px solid var(--green-200);
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            box-shadow: 0 -4px 24px rgba(22,163,74,0.12);
            z-index: 50;
            flex-wrap: wrap;
        }
        .selected-count {
            font-size: 0.9rem;
            color: #5a6a5a;
        }
        .selected-count strong { color: var(--green-800); font-size: 1rem; }
        .btn-primary {
            background: var(--green-700);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 99px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex; align-items: center; gap: 0.5rem;
            box-shadow: 0 4px 15px rgba(22,163,74,0.3);
        }
        .btn-primary:hover {
            background: var(--green-600);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(22,163,74,0.4);
        }

        @media (max-width: 600px) {
            .bahan-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>

<nav>
    <div class="nav-brand">Jamu<span>ku</span></div>
    <div class="nav-links">
        <a href="index.php" class="active">Pilih Bahan</a>
        <a href="keranjang.php">🛒 Keranjang</a>
        <a href="racikan.php">📜 Racikan</a>
    </div>
</nav>

<div class="hero">
    <div class="hero-badge">🌿 Jamu Herbal Nusantara</div>
    <h1>Racik Jamu <em>Pilihanmu</em><br>Sendiri</h1>
    <p>Pilih bahan-bahan terbaik dari alam, tentukan porsinya, dan dapatkan racikan jamu yang sempurna untukmu.</p>
</div>

<div class="container">
    <form method="POST" action="index.php" id="mainForm">
        <input type="hidden" name="action" value="tambah">
        <input type="hidden" name="porsi" id="porsiInput" value="1">

        <!-- Porsi Selector -->
        <div class="porsi-bar">
            <label>Jumlah Porsi <span>(banyaknya racikan yang dibuat)</span></label>
            <div class="porsi-controls">
                <button type="button" class="porsi-btn" onclick="changePorsi(-1)">−</button>
                <div id="porsiDisplay">1</div>
                <button type="button" class="porsi-btn" onclick="changePorsi(1)">+</button>
            </div>
        </div>

        <!-- Bahan per jenis -->
        <?php foreach ($jenisList as $jenis => $items): ?>
        <div class="section-label">
            <h2><?= $jenisIcon[$jenis] ?? '🌱' ?> <?= htmlspecialchars($jenis) ?></h2>
            <span class="badge"><?= count($items) ?> bahan</span>
        </div>
        <div class="bahan-grid">
            <?php foreach ($items as $b): ?>
            <label class="bahan-card" id="card-<?= $b['id'] ?>">
                <input type="checkbox" name="bahan_ids[]" value="<?= $b['id'] ?>"
                    onchange="updateCard(this)">
                <div class="bahan-nama"><?= htmlspecialchars($b['nama']) ?></div>
                <div class="bahan-desc"><?= htmlspecialchars($b['deskripsi']) ?></div>
                <div class="bahan-harga">Rp <?= number_format($b['harga'], 0, ',', '.') ?></div>
            </label>
            <?php endforeach; ?>
        </div>
        <hr class="section-divider">
        <?php endforeach; ?>

        <!-- Sticky Bottom Bar -->
        <div class="sticky-bottom">
            <div class="selected-count">
                <strong id="selectedCount">0</strong> bahan dipilih &nbsp;|&nbsp; Porsi: <strong id="porsiLabel">1</strong>
            </div>
            <button type="submit" class="btn-primary">
                🛒 Masukkan ke Keranjang
            </button>
        </div>
    </form>
</div>

<script>
let porsi = 1;

function changePorsi(delta) {
    porsi = Math.max(1, porsi + delta);
    document.getElementById('porsiDisplay').textContent = porsi;
    document.getElementById('porsiInput').value = porsi;
    document.getElementById('porsiLabel').textContent = porsi;
}

function updateCard(checkbox) {
    const card = checkbox.closest('.bahan-card');
    if (checkbox.checked) card.classList.add('selected');
    else card.classList.remove('selected');
    updateCount();
}

function updateCount() {
    const count = document.querySelectorAll('input[type=checkbox]:checked').length;
    document.getElementById('selectedCount').textContent = count;
}
</script>
</body>
</html>