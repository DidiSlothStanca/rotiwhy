# 🥐 RotiWhy - Sistem Manajemen Toko Roti & Kue

Aplikasi web modern untuk mengelola inventori, penjualan, dan operasional toko roti/bakery dengan fitur dashboard real-time, grafik penjualan, dan manajemen karyawan.

## ✨ Fitur Utama

- **📊 Dashboard Stok Real-Time** - Monitor stok harian, sisa stok, dan prediksi kebutuhan otomatis
- **🛒 Input Kasir Cepat** - Pencatatan transaksi penjualan dengan validasi stok otomatis
- **📜 Riwayat & Hapus Transaksi** - Kelola histori penjualan, hapus transaksi dengan sinkronisasi stok
- **📈 Grafik Penjualan Multi-Filter** - Visualisasi data harian, mingguan, bulanan, dan tahunan
- **🛠️ Kelola Menu & Stok** - Tambah/edit/hapus varian roti, atur harga, update stok produksi
- **👥 Manajemen Karyawan** - Tambah user, atur role (admin/kasir), kelola akses
- **🔐 Login Aman** - Session-based authentication dengan limit login 3x + cooldown 30 detik
- **🌙 Dark Mode** - Theme toggle untuk kenyamanan penggunaan 24/7
- **📱 Responsive Design** - Optimal di desktop, tablet, dan mobile

## 🔧 Teknologi

- **Frontend**: HTML5, CSS3, JavaScript, Chart.js
- **Backend**: PHP 7+
- **Database**: MySQL
- **Session**: PHP Native Session (HttpOnly, SameSite protection)

## 📋 Prasyarat

- PHP 7.0+
- MySQL 5.7+
- Apache/Nginx
- Browser modern (Chrome, Firefox, Safari, Edge)

## 🚀 Instalasi

1. **Clone Repository**
   ```bash
   git clone https://github.com/yourusername/rotiwhy.git
   cd rotiwhy
   ```

2. **Setup Database**
   ```bash
   # Import file SQL
   mysql -u root -p toko_roti < database.sql
   ```

3. **Konfigurasi Koneksi**
   Edit `koneksi.php`:
   ```php
   $host = "localhost";
   $user = "root"; 
   $pass = "";     
   $db   = "toko_roti";
   ```

4. **Akses Aplikasi**
   ```
   http://localhost/rotiwhy/login.php
   ```

5. **Akun Default**
   - Username: `admin`
   - Password: `password`

## 📸 Screenshots

### Login Page
![Login Page](screenshots/login.png)

### Dashboard Stok
![Dashboard](screenshots/dashboard.png)

### Input Kasir
![Input Kasir](screenshots/input-kasir.png)

### Grafik Penjualan
![Grafik](screenshots/grafik.png)

### Manajemen Produk (Admin)
![Manajemen](screenshots/manajemen.png)

## 📁 Struktur File

```
rotiwhy/
├── index.php              # Dashboard utama
├── login.php              # Halaman login
├── koneksi.php            # Konfigurasi database
├── header.php             # Layout & header dengan logika query
├── menu.php               # Tab menu navigasi
├── proses.php             # Backend logic (CRUD operations)
├── style.css              # Styling global
├── database.sql           # Schema database
└── screenshots/           # Folder screenshot
    ├── login.png
    ├── dashboard.png
    ├── input-kasir.png
    ├── grafik.png
    └── manajemen.png
```

## 🎯 Penggunaan

### Untuk Kasir
1. Login dengan akun kasir
2. Klik "Input Kasir"
3. Pilih roti, masukkan qty pembeli
4. Submit → Otomatis cek stok & buat antrian

### Untuk Admin
1. Login dengan akun admin
2. Akses tab "Atur Menu & Stok" untuk kelola produk
3. Akses tab "Kelola Karyawan" untuk manage user
4. Monitor grafik penjualan di "Grafik Penjualan"

## 🐛 Bug yang Sudah Diperbaiki

- ✅ Fixed: Stok ganda saat pembatalan transaksi (stock restore logic)
- ✅ Improved: Session management dengan HttpOnly cookies
- ✅ Optimized: Query performance dengan proper indexing

## 🔒 Security Features

- Password hashing dengan MD5 (rekomendasi upgrade ke bcrypt)
- SQL Injection protection dengan `mysqli_real_escape_string()`
- CSRF protection dengan SameSite cookies
- HttpOnly flag untuk session cookies
- Cache control headers

## 📊 Database Schema

**Tabel Utama:**
- `users` - Data karyawan/staff
- `roti` - Master produk roti
- `transaksi` - Histori penjualan
- `antrian` - Queue management
- `dummy_transaksi` - Data historis untuk grafik

## 🤝 Kontribusi

Contributions, issues, dan feature requests dipersilahkan!

```bash
# Fork repository
git fork https://github.com/yourusername/rotiwhy.git

# Buat branch fitur
git checkout -b feature/AmazingFeature

# Commit changes
git commit -m 'Add some AmazingFeature'

# Push ke branch
git push origin feature/AmazingFeature

# Open Pull Request
```

## 📝 Todo/Roadmap

- [ ] Upgrade password hashing ke bcrypt
- [ ] Tambah fitur laporan PDF
- [ ] Integration dengan payment gateway
- [ ] Mobile app version
- [ ] API endpoint untuk integrasi
- [ ] Backup database otomatis

## 📄 License

GNU General Public License v3 - Free software yang dapat digunakan, dimodifikasi, dan didistribusikan kembali dengan syarat GPL v3.

Lihat file [LICENSE](LICENSE) untuk detail lengkap.

**Disclaimer:** Aplikasi ini disediakan "AS IS" tanpa garansi apapun. Pengguna bertanggung jawab atas penggunaan dan penyimpanan data.

## 👨‍💻 Author

**Didi Sloth Stanca** - By Didi Sloth Stanca

---

## 📞 Support

Jika ada pertanyaan atau bug report, buat issue di repository ini.

---

**Made with ❤️ for Bakery Owners**
