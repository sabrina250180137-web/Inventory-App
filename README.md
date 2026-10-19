# Inventory-App
# Sistem Manajemen Inventaris Toko Sepatu (Mini Project 3)

Aplikasi web sistem inventaris toko sepatu berbasis **PHP Native (PDO)** dan **MySQL** yang dirancang untuk mengelola data produk, kategori, serta pencatatan alur transaksi barang secara aman menggunakan metode *Database Transaction*. Proyek ini dibuat untuk memenuhi tugas **Mini Project 3 Pemrograman Web**.

---

## 👥 Identitas Pengembang
* **Nama:** Sabrina Ayu Putri Effendi[cite: 17]
* **NIM:** 250180137[cite: 17]
* **Program Studi:** Sistem Informasi, Fakultas Teknik, Universitas Malikussaleh[cite: 17]

---

## 🚀 Fitur Utama Sistem
1. **Autentikasi & Role-Based Access Control (RBAC):**
   * Pembagian hak akses antara **Admin** dan **Staff**[cite: 13].
   * Admin memiliki hak penuh untuk menambah kategori dan produk, sementara Staff fokus pada pencatatan transaksi[cite: 17].
2. **Modul Produk (Katalog):**
   * Dilengkapi fitur **Search** (pencarian nama produk *real-time*).
   * **Filter** berdasarkan kategori sepatu.
   * **Sorting** (pengurutan berdasarkan nama, harga, atau stok)[cite: 13].
   * **Pagination** untuk membatasi jumlah tampilan data per halaman[cite: 13].
3. **Modul Transaksi & Proteksi Stok:**
   * Pencatatan **Barang Masuk** (menambah stok) dan **Barang Keluar** (mengurangi stok)[cite: 17].
   * Menerapkan **Database Transaction** (`BEGIN`, `COMMIT`, `ROLLBACK`) serta *Pessimistic Locking* (`FOR UPDATE`) untuk mencegah inkonsistensi data dan *stok minus* akibat kesalahan input[cite: 17].
4. **Dashboard Sederhana:**
   * Menampilkan ringkasan statistik total model sepatu, total kategori, serta akumulasi pergerakan barang[cite: 17].

---

## 📋 Aturan Bisnis (Business Rule)
* **Inisialisasi Stok Otomatis:** Saat produk baru didaftarkan oleh Admin, jumlah stok awal otomatis dikunci pada angka `0`[cite: 17].
* **Validasi Stok Keluar:** Sistem akan menolak transaksi pengeluaran barang secara otomatis jika jumlah yang diminta melebihi sisa stok fisik di gudang[cite: 17].

---

## 🛠️ Teknologi yang Digunakan
* **Bahasa Pemrograman:** PHP 8 (Native PDO)[cite: 17]
* **Database:** MySQL / MariaDB[cite: 17]
* **Frontend:** HTML5, CSS3 (Modern Card & Modal UI)
* **Server Lokal:** XAMPP (Apache)

---

## ⚙️ Cara Instalasi & Menjalankan Proyek
1. **Clone atau Unduh Repositori ini:**
   Letakkan folder proyek (misalnya `inventory_app2`) ke dalam direktori server lokal XAMPP Anda: `C:\xampp\htdocs\`[cite: 17].
2. **Impor Database:**
   * Buka *phpMyAdmin* di browser (`http://localhost/phpmyadmin`)[cite: 17].
   * Buat database baru atau impor file `schema.sql` yang tersedia di dalam repositori ini[cite: 17].
3. **Konfigurasi Koneksi:**
   * Sesuaikan pengaturan *host*, *username*, dan *password* database Anda di dalam file `config.php` jika diperlukan.
4. **Jalankan Aplikasi:**
   * Buka *browser* dan akses URL berikut:  
     `http://localhost/inventory_app2/login.php`[cite: 17]
   * Gunakan akun pengujian yang tersedia pada database untuk masuk sebagai Admin atau Staff.
