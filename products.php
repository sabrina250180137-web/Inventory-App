<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require 'config.php';

$userRole = $_SESSION['role'] ?? 'staff';
$successMsg = '';

// --- LOGIKA TAMBAH DATA (KHUSUS ADMIN) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $userRole === 'admin') {
    // Proses Tambah Kategori
    if (isset($_POST['add_category'])) {
        $cat_name = trim($_POST['category_name']);
        if (!empty($cat_name)) {
            $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->execute([$cat_name]);
            $successMsg = "Kategori berhasil ditambahkan!";
        }
    }
    // Proses Tambah Produk
    if (isset($_POST['add_product'])) {
        $prod_name = trim($_POST['product_name']);
        $cat_id = $_POST['category_id'];
        $price = (float)$_POST['price'];
        if (!empty($prod_name) && !empty($cat_id)) {
            // Stok diset 0. Aturan bisnis: penambahan stok lewat Transaksi Barang Masuk
            $stmt = $pdo->prepare("INSERT INTO products (category_id, name, price, stock) VALUES (?, ?, ?, 0)");
            $stmt->execute([$cat_id, $prod_name, $price]);
            $successMsg = "Produk berhasil ditambahkan dengan stok awal 0!";
        }
    }
}

// --- LOGIKA TAMPIL DATA (PENCARIAN, FILTER, SORTING, PAGINATION) ---
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'name';
$order = $_GET['order'] ?? 'ASC';
$page = (int)($_GET['page'] ?? 1);

$limit = 5; 
$offset = ($page - 1) * $limit;

$allowedSort = ['name', 'stock', 'price', 'category_name'];
$sort = in_array($sort, $allowedSort) ? $sort : 'name';
$order = ($order === 'DESC') ? 'DESC' : 'ASC';

$params = [];
$whereSql = "WHERE 1=1";

if (!empty($search)) {
    $whereSql .= " AND p.name LIKE ?";
    $params[] = "%$search%";
}
if (!empty($category)) {
    $whereSql .= " AND p.category_id = ?";
    $params[] = $category;
}

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM products p $whereSql");
$stmtCount->execute($params);
$totalRows = $stmtCount->fetchColumn();
$totalPages = ceil($totalRows / $limit);

$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          $whereSql 
          ORDER BY $sort $order 
          LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Mengambil daftar kategori untuk dropdown filter dan form tambah produk
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modul Produk - Inventaris</title>
    <style>
        :root {
            --primary-color: #2b3a42; 
            --accent-color: #3f51b5;
            --bg-color: #f4f7f6;
            --text-color: #333;
        }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background-color: var(--bg-color); margin: 0; }
        
        /* Navigasi sama seperti Dashboard */
        .navbar { background-color: var(--primary-color); color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        .nav-left { display: flex; align-items: center; gap: 20px; }
        .nav-left h1 { margin: 0; font-size: 20px; margin-right: 15px; }
        .nav-left a { color: #ccc; text-decoration: none; font-size: 15px; }
        .nav-left a:hover, .nav-left a.active { color: white; text-decoration: underline; font-weight: bold; }
        .user-menu { display: flex; align-items: center; gap: 20px; }
        .user-role { background-color: rgba(255,255,255,0.1); padding: 5px 12px; border-radius: 20px; font-size: 14px; }
        .btn-logout { color: white; text-decoration: none; background-color: #dc3545; padding: 8px 15px; border-radius: 4px; font-weight: bold; }

        .container { padding: 30px; max-width: 1200px; margin: 0 auto; background: white; margin-top: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        
        .header-action { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
        .header-action h2 { margin: 0; color: var(--primary-color); }
        .btn-add { padding: 8px 15px; background: var(--accent-color); color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-add:hover { opacity: 0.9; }
        .btn-cat { background: #9c27b0; }
        
        /* Area Kontrol & Tabel */
        .controls { background: #f9f9f9; padding: 15px; border-radius: 6px; margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap; align-items: center; border: 1px solid #ddd; }
        .controls input, .controls select { padding: 8px; border: 1px solid #ccc; border-radius: 4px; outline: none; }
        .controls button { padding: 8px 15px; background: var(--accent-color); color: white; border: none; border-radius: 4px; cursor: pointer; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; color: #555; }
        .stok-habis { color: red; font-weight: bold; }
        
        .pagination { display: flex; gap: 5px; justify-content: center; }
        .pagination a { padding: 8px 12px; border: 1px solid #ddd; text-decoration: none; color: var(--text-color); border-radius: 4px; }
        .pagination a.active { background: var(--primary-color); color: white; border-color: var(--primary-color); }
        
        /* CSS Modal (Pop-up) */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: white; margin: 10% auto; padding: 25px; border-radius: 8px; width: 100%; max-width: 400px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        .close { float: right; font-size: 24px; font-weight: bold; cursor: pointer; color: #aaa; }
        .close:hover { color: #333; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn-submit { width: 100%; padding: 12px; background-color: var(--accent-color); color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; margin-top: 10px;}
        
        .alert-success { background-color: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #c3e6cb; }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="nav-left">
            <h1>Inventaris Sepatu</h1>
            <a href="dashboard.php">Dashboard</a>
            <a href="products.php" class="active">Produk</a>
            <a href="transaction.php">Transaksi</a>
        </div>
        <div class="user-menu">
            <span class="user-role">Sesi aktif: <strong><?= htmlspecialchars(strtoupper($userRole)) ?></strong></span>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>

    <div class="container">
        
        <?php if ($successMsg): ?>
            <div class="alert-success"><?= $successMsg ?></div>
        <?php endif; ?>

        <div class="header-action">
            <h2>Daftar Produk</h2>
            <!-- Tombol tambah HANYA muncul jika login sebagai admin -->
            <?php if ($userRole === 'admin'): ?>
                <div>
                    <button onclick="openModal('modalCategory')" class="btn-add btn-cat">+ Tambah Kategori</button>
                    <button onclick="openModal('modalProduct')" class="btn-add">+ Tambah Produk</button>
                </div>
            <?php endif; ?>
        </div>

        <form method="GET" action="products.php" class="controls">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama produk..." style="flex: 1; min-width: 200px;">
            
            <select name="category">
                <option value="">-- Semua Kategori --</option>
                <?php foreach($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $category == $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="sort">
                <option value="name" <?= $sort == 'name' ? 'selected' : '' ?>>Urutkan: Nama</option>
                <option value="price" <?= $sort == 'price' ? 'selected' : '' ?>>Urutkan: Harga</option>
                <option value="stock" <?= $sort == 'stock' ? 'selected' : '' ?>>Urutkan: Stok</option>
            </select>

            <select name="order">
                <option value="ASC" <?= $order == 'ASC' ? 'selected' : '' ?>>Naik (A-Z / Kecil-Besar)</option>
                <option value="DESC" <?= $order == 'DESC' ? 'selected' : '' ?>>Turun (Z-A / Besar-Kecil)</option>
            </select>

            <button type="submit">Terapkan</button>
            <a href="products.php" style="padding: 8px 15px; background: #999; color: white; text-decoration: none; border-radius: 4px;">Reset</a>
        </form>

        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kategori</th>
                    <th>Nama Produk</th>
                    <th>Stok</th>
                    <th>Harga (Rp)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($products) > 0): ?>
                    <?php 
                    $no = $offset + 1;
                    foreach ($products as $row): 
                    ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['category_name'] ?? 'Tanpa Kategori') ?></td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td class="<?= $row['stock'] <= 0 ? 'stok-habis' : '' ?>"><?= $row['stock'] ?></td>
                            <td><?= number_format($row['price'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 20px;">Tidak ada data produk ditemukan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php 
                        $queryParams = http_build_query(['search' => $search, 'category' => $category, 'sort' => $sort, 'order' => $order, 'page' => $i]);
                    ?>
                    <a href="?<?= $queryParams ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- MODAL POP-UP TAMBAH KATEGORI -->
    <div id="modalCategory" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('modalCategory')">&times;</span>
            <h3 style="margin-top:0; color:var(--primary-color);">Tambah Kategori Baru</h3>
            <form method="POST" action="products.php">
                <div class="form-group">
                    <label>Nama Kategori</label>
                    <input type="text" name="category_name" placeholder="Contoh: Sepatu Olahraga" required>
                </div>
                <button type="submit" name="add_category" class="btn-submit" style="background:#9c27b0;">Simpan Kategori</button>
            </form>
        </div>
    </div>

    <!-- MODAL POP-UP TAMBAH PRODUK -->
    <div id="modalProduct" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('modalProduct')">&times;</span>
            <h3 style="margin-top:0; color:var(--primary-color);">Tambah Produk Baru</h3>
            <form method="POST" action="products.php">
                <div class="form-group">
                    <label>Kategori</label>
                    <select name="category_id" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach($categories as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Nama Model Sepatu</label>
                    <input type="text" name="product_name" placeholder="Contoh: Nike Air Max" required>
                </div>
                <div class="form-group">
                    <label>Harga Jual (Rp)</label>
                    <input type="number" name="price" placeholder="Contoh: 1500000" required>
                </div>
                <p style="font-size:12px; color:#888; font-style:italic;">
                    *Stok awal otomatis 0. Silakan menuju menu Transaksi -> Barang Masuk untuk menambahkan jumlah stok.
                </p>
                <button type="submit" name="add_product" class="btn-submit">Simpan Produk</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) {
            document.getElementById(id).style.display = 'block';
        }
        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }
        // Menutup modal jika user mengklik area abu-abu di luar kotak
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>