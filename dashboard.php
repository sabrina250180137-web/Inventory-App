<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'config.php';

$userRole = $_SESSION['role'] ?? 'staff';

$totalProduk = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalKategori = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$totalBarangMasuk = $pdo->query("SELECT SUM(qty) FROM transactions WHERE type='masuk'")->fetchColumn() ?? 0;
$totalBarangKeluar = $pdo->query("SELECT SUM(qty) FROM transactions WHERE type='keluar'")->fetchColumn() ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Inventaris Toko Sepatu</title>
    <style>
        :root {
            --primary-color: #2b3a42; 
            --accent-color: #3f51b5;
            --bg-color: #f4f7f6;
            --text-color: #333;
            --card-bg: #ffffff;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            margin: 0;
            padding: 0;
        }
        
        /* Navigasi Atas Diperbarui */
        .navbar {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .nav-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .nav-left h1 {
            margin: 0;
            font-size: 20px;
            letter-spacing: 1px;
            margin-right: 15px;
        }
        .nav-left a {
            color: #ccc;
            text-decoration: none;
            font-size: 15px;
            transition: 0.3s;
        }
        .nav-left a:hover, .nav-left a.active {
            color: white;
            text-decoration: underline;
        }
        .nav-left a.active {
            font-weight: bold;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .user-role {
            background-color: rgba(255,255,255,0.1);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 14px;
        }
        .btn-logout {
            color: white;
            text-decoration: none;
            background-color: #dc3545;
            padding: 8px 15px;
            border-radius: 4px;
            font-weight: bold;
            transition: background 0.3s;
        }
        .btn-logout:hover {
            background-color: #c82333;
        }

        /* Area Konten Utama */
        .container {
            padding: 40px 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .welcome-msg { margin-bottom: 40px; }
        .welcome-msg h2 { margin-top: 0; color: var(--primary-color); font-size: 28px; }
        .welcome-msg p { color: #666; font-size: 16px; }

        /* Grid Kartu */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
        }
        .card {
            background-color: var(--card-bg);
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border-bottom: 5px solid #ccc;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .card h3 { margin: 0 0 15px 0; font-size: 16px; color: #777; text-transform: uppercase; letter-spacing: 0.5px; }
        .card .value { font-size: 38px; font-weight: bold; color: var(--text-color); }

        .card.produk { border-bottom-color: var(--accent-color); }
        .card.kategori { border-bottom-color: #9c27b0; }
        .card.masuk { border-bottom-color: #4caf50; }
        .card.keluar { border-bottom-color: #ff9800; }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="nav-left">
            <h1>Inventaris Sepatu</h1>
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="products.php">Produk</a>
            <a href="transaction.php">Transaksi</a>
        </div>
        <div class="user-menu">
            <span class="user-role">Sesi aktif: <strong><?= htmlspecialchars(strtoupper($userRole)) ?></strong></span>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="welcome-msg">
            <h2>Selamat Datang, <?= htmlspecialchars(ucfirst($userRole)) ?>! 👋</h2>
            <p>Berikut adalah ringkasan pergerakan barang dan stok inventaris saat ini.</p>
        </div>

        <div class="dashboard-grid">
            <div class="card produk">
                <h3>Total Model Sepatu</h3>
                <div class="value"><?= $totalProduk ?></div>
            </div>
            
            <div class="card kategori">
                <h3>Kategori Sepatu</h3>
                <div class="value"><?= $totalKategori ?></div>
            </div>
            
            <div class="card masuk">
                <h3>Total Sepatu Masuk</h3>
                <div class="value"><?= $totalBarangMasuk ?></div>
            </div>
            
            <div class="card keluar">
                <h3>Total Sepatu Terjual / Keluar</h3>
                <div class="value"><?= $totalBarangKeluar ?></div>
            </div>
        </div>
    </div>

</body>
</html>