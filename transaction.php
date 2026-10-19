<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'config.php';

$userRole = $_SESSION['role'] ?? 'staff';
$message = '';
$msgType = ''; // 'success' atau 'error'

// PROSES TRANSAKSI (BARANG MASUK / KELUAR)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = $_POST['product_id'];
    $type = $_POST['type']; // 'masuk' atau 'keluar'
    $qty = (int)$_POST['qty'];
    $user_id = $_SESSION['user_id'];

    if ($qty <= 0) {
        $message = "Error Validasi: Kuantitas barang harus lebih dari 0.";
        $msgType = "error";
    } else {
        try {
            // Memulai Database Transaction
            $pdo->beginTransaction();

            // Mengunci baris produk (Pessimistic Locking) agar tidak bentrok jika diakses bersamaan
            $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ? FOR UPDATE");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();

            if (!$product) {
                throw new Exception("Produk tidak ditemukan.");
            }

            if ($type === 'keluar' && $product['stock'] < $qty) {
                throw new Exception("Error Validasi: Stok saat ini ({$product['stock']}) tidak mencukupi untuk mengeluarkan barang sebanyak $qty.");
            }

            // Insert ke tabel riwayat transaksi
            $stmtInsert = $pdo->prepare("INSERT INTO transactions (user_id, product_id, type, qty) VALUES (?, ?, ?, ?)");
            $stmtInsert->execute([$user_id, $product_id, $type, $qty]);

            // Update stok produk
            $operator = ($type === 'masuk') ? '+' : '-';
            $stmtUpdate = $pdo->prepare("UPDATE products SET stock = stock $operator ? WHERE id = ?");
            $stmtUpdate->execute([$qty, $product_id]);

            // Commit transaksi
            $pdo->commit();
            $message = "Transaksi berhasil dicatat. Stok produk telah diperbarui.";
            $msgType = "success";

        } catch (Exception $e) {
            // Rollback jika gagal
            $pdo->rollBack();
            $message = $e->getMessage();
            $msgType = "error";
        }
    }
}

// Mengambil daftar produk untuk dropdown form
$products = $pdo->query("SELECT id, name, stock FROM products ORDER BY name ASC")->fetchAll();

// Mengambil riwayat transaksi terbaru untuk ditampilkan di tabel
$queryRiwayat = "SELECT t.*, p.name as product_name, u.username as admin_name 
                 FROM transactions t 
                 JOIN products p ON t.product_id = p.id 
                 LEFT JOIN users u ON t.user_id = u.id 
                 ORDER BY t.transaction_date DESC LIMIT 50";
$transactions = $pdo->query($queryRiwayat)->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi - Inventaris Toko Sepatu</title>
    <style>
        :root {
            --primary-color: #2b3a42; 
            --accent-color: #3f51b5;
            --bg-color: #f4f7f6;
            --text-color: #333;
        }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background-color: var(--bg-color); margin: 0; }
        
        /* Navigasi */
        .navbar { background-color: var(--primary-color); color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        .nav-left { display: flex; align-items: center; gap: 20px; }
        .nav-left h1 { margin: 0; font-size: 20px; margin-right: 15px; }
        .nav-left a { color: #ccc; text-decoration: none; font-size: 15px; }
        .nav-left a:hover, .nav-left a.active { color: white; text-decoration: underline; font-weight: bold; }
        .user-menu { display: flex; align-items: center; gap: 20px; }
        .user-role { background-color: rgba(255,255,255,0.1); padding: 5px 12px; border-radius: 20px; font-size: 14px; }
        .btn-logout { color: white; text-decoration: none; background-color: #dc3545; padding: 8px 15px; border-radius: 4px; font-weight: bold; }

        .container { padding: 30px; max-width: 1200px; margin: 0 auto; display: flex; gap: 30px; flex-wrap: wrap; }
        
        /* Panel Form & Tabel */
        .panel { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .panel-form { flex: 1; min-width: 300px; }
        .panel-table { flex: 2; min-width: 600px; }
        .panel h2 { margin-top: 0; color: var(--primary-color); border-bottom: 2px solid #eee; padding-bottom: 10px; font-size: 20px; }

        /* Form Elements */
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: #555; font-weight: bold; }
        .form-group select, .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn-submit { width: 100%; padding: 12px; background-color: var(--accent-color); color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; font-weight: bold; margin-top: 10px; }
        .btn-submit:hover { background-color: #303f9f; }

        /* Tabel Data */
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; font-size: 14px; }
        th { background-color: #f2f2f2; color: #555; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; color: white; }
        .badge-masuk { background-color: #28a745; }
        .badge-keluar { background-color: #ffc107; color: #333; }

        /* Alert Messages */
        .alert { padding: 15px; border-radius: 4px; margin-bottom: 20px; font-weight: bold; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="nav-left">
            <h1>Inventaris Sepatu</h1>
            <a href="dashboard.php">Dashboard</a>
            <a href="products.php">Produk</a>
            <a href="transaction.php" class="active">Transaksi</a>
        </div>
        <div class="user-menu">
            <span class="user-role">Sesi aktif: <strong><?= htmlspecialchars(strtoupper($userRole)) ?></strong></span>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>

    <div class="container">
        
        <!-- Panel Kiri: Form Input Transaksi -->
        <div class="panel panel-form">
            <h2>Catat Transaksi</h2>
            
            <?php if ($message): ?>
                <div class="alert <?= $msgType === 'error' ? 'alert-error' : 'alert-success' ?>">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="transaction.php">
                <div class="form-group">
                    <label>Pilih Produk</label>
                    <select name="product_id" required>
                        <option value="">-- Pilih Produk --</option>
                        <?php foreach($products as $p): ?>
                            <option value="<?= $p['id'] ?>">
                                <?= htmlspecialchars($p['name']) ?> (Stok: <?= $p['stock'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Jenis Transaksi</label>
                    <select name="type" required>
                        <option value="masuk">Barang Masuk (Tambah Stok)</option>
                        <option value="keluar">Barang Keluar / Terjual (Kurangi Stok)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Kuantitas (Qty)</label>
                    <input type="number" name="qty" placeholder="Masukkan jumlah barang..." required>
                    <small style="color:#888;">*Tidak boleh 0 atau minus</small>
                </div>

                <button type="submit" class="btn-submit">Simpan Transaksi</button>
            </form>
        </div>

        <!-- Panel Kanan: Tabel Riwayat Transaksi -->
        <div class="panel panel-table">
            <h2>Riwayat Transaksi Terbaru</h2>
            <table>
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Produk</th>
                        <th>Jenis</th>
                        <th>Qty</th>
                        <th>User</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($transactions) > 0): ?>
                        <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td><?= date('d-m-Y H:i', strtotime($t['transaction_date'])) ?></td>
                                <td><?= htmlspecialchars($t['product_name']) ?></td>
                                <td>
                                    <span class="badge <?= $t['type'] === 'masuk' ? 'badge-masuk' : 'badge-keluar' ?>">
                                        <?= strtoupper($t['type']) ?>
                                    </span>
                                </td>
                                <td><strong><?= $t['qty'] ?></strong></td>
                                <td><?= htmlspecialchars($t['admin_name']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #888;">Belum ada riwayat transaksi.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</body>
</html>