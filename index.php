<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Cegah caching halaman
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

include 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$role_user = $_SESSION['role'];
$nama_user = $_SESSION['nama'];

$hari_ini = date('Y-m-d');

$query_stok_toko = mysqli_query($conn, "
    SELECT r.id_roti, r.nama_roti, 
           IF(r.tanggal_update = '$hari_ini', r.stok_awal, 0) as stok_awal, 
           IFNULL(SUM(t.qty), 0) as total_terjual,
           (IF(r.tanggal_update = '$hari_ini', r.stok_awal, 0) - IFNULL(SUM(t.qty), 0)) as sisa_stok
    FROM roti r 
    LEFT JOIN transaksi t ON r.id_roti = t.id_roti AND t.tanggal = '$hari_ini'
    GROUP BY r.id_roti
");

$query_riwayat = mysqli_query($conn, "
    SELECT t.id_transaksi, t.nama_pembeli, r.nama_roti, t.qty, t.total_harga, t.tanggal 
    FROM transaksi t JOIN roti r ON t.id_roti = r.id_roti 
    ORDER BY t.id_transaksi DESC LIMIT 10
");

$query_roti = mysqli_query($conn, "SELECT * FROM roti");
$query_users = mysqli_query($conn, "SELECT id_user, username, nama_lengkap, role FROM users");
$tgl_hari_ini = date('Y-m-d');
$tgl_kemarin = date('Y-m-d', strtotime("-1 days"));
$q_hari_ini = mysqli_query($conn, "SELECT IFNULL(SUM(qty), 0) as total FROM transaksi WHERE tanggal='$tgl_hari_ini'");
$q_kemarin  = mysqli_query($conn, "SELECT IFNULL(SUM(qty), 0) as total FROM transaksi WHERE tanggal='$tgl_kemarin'");
$penjualan_hari_ini = mysqli_fetch_assoc($q_hari_ini)['total'];
$penjualan_kemarin  = mysqli_fetch_assoc($q_kemarin)['total'];

$laju_perubahan = $penjualan_hari_ini - $penjualan_kemarin;
$prediksi_stok = $penjualan_hari_ini + $laju_perubahan;
if ($prediksi_stok < 10) { $prediksi_stok = 30; }

$query_antrian = mysqli_query($conn, "
    SELECT id_antrian, nomor_antrian, status, jam_dibuka, jam_ditutup, 
           IFNULL(nama_pembeli, '-') as nama_pembeli, 
           IFNULL(nama_roti, '-') as nama_roti
    FROM antrian 
    WHERE DATE(jam_dibuka) = CURDATE() 
    ORDER BY nomor_antrian DESC
");

$q_nomor_terakhir = mysqli_query($conn, "
    SELECT MAX(nomor_antrian) as nomor_max FROM antrian 
    WHERE DATE(jam_dibuka) = CURDATE()
");
$nomor_data = mysqli_fetch_assoc($q_nomor_terakhir);
$nomor_antrian_baru = ($nomor_data['nomor_max'] ?? 0) + 1;

$id_roti_grafik = isset($_GET['id_roti_grafik']) ? $_GET['id_roti_grafik'] : 'all';
$where_roti = "";
if ($id_roti_grafik !== 'all') {
    $id_roti_safe = mysqli_real_escape_string($conn, $id_roti_grafik);
    $where_roti = " AND t.id_roti = '$id_roti_safe' ";
}

$q_hari = mysqli_query($conn, "SELECT t.tanggal as label, IFNULL(SUM(t.qty), 0) as total_qty, IFNULL(SUM(t.total_harga), 0) as total_duit FROM transaksi t JOIN roti r ON t.id_roti = r.id_roti WHERE t.tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) $where_roti GROUP BY t.tanggal ORDER BY t.tanggal ASC");
$g_hari_lbl = []; $g_hari_val = []; $g_hari_duit = []; 
while($r = mysqli_fetch_assoc($q_hari)){ 
    $g_hari_lbl[] = date('d M', strtotime($r['label'])); 
    $g_hari_val[] = (int)$r['total_qty']; 
    $g_hari_duit[] = (int)$r['total_duit'];
}

$q_minggu = mysqli_query($conn, "SELECT CONCAT('Minggu ', WEEK(t.tanggal)) as label, IFNULL(SUM(t.qty), 0) as total_qty, IFNULL(SUM(t.total_harga), 0) as total_duit FROM transaksi t JOIN roti r ON t.id_roti = r.id_roti WHERE t.tanggal >= DATE_SUB(CURDATE(), INTERVAL 4 WEEK) $where_roti GROUP BY WEEK(t.tanggal) ORDER BY t.tanggal ASC");
$g_mgu_lbl = []; $g_mgu_val = []; $g_mgu_duit = [];
while($r = mysqli_fetch_assoc($q_minggu)){ 
    $g_mgu_lbl[] = $r['label']; 
    $g_mgu_val[] = (int)$r['total_qty']; 
    $g_mgu_duit[] = (int)$r['total_duit'];
}

$q_bulan = mysqli_query($conn, "SELECT DATE_FORMAT(t.tanggal, '%M %Y') as label, IFNULL(SUM(t.qty), 0) as total_qty, IFNULL(SUM(t.total_harga), 0) as total_duit FROM transaksi t JOIN roti r ON t.id_roti = r.id_roti WHERE t.tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) $where_roti GROUP BY MONTH(t.tanggal) ORDER BY t.tanggal ASC");
$g_bln_lbl = []; $g_bln_val = []; $g_bln_duit = [];
while($r = mysqli_fetch_assoc($q_bulan)){ 
    $g_bln_lbl[] = $r['label']; 
    $g_bln_val[] = (int)$r['total_qty']; 
    $g_bln_duit[] = (int)$r['total_duit'];
}

$q_tahun = mysqli_query($conn, "SELECT YEAR(t.tanggal) as label, IFNULL(SUM(t.qty), 0) as total_qty, IFNULL(SUM(t.total_harga), 0) as total_duit FROM transaksi t JOIN roti r ON t.id_roti = r.id_roti WHERE 1=1 $where_roti GROUP BY YEAR(t.tanggal) ORDER BY YEAR(t.tanggal) ASC");
$g_thn_lbl = []; $g_thn_val = []; $g_thn_duit = [];
while($r = mysqli_fetch_assoc($q_tahun)){ 
    $g_thn_lbl[] = $r['label']; 
    $g_thn_val[] = (int)$r['total_qty']; 
    $g_thn_duit[] = (int)$r['total_duit'];
}

$q_detail_hari = mysqli_query($conn, "
    SELECT r.nama_roti, IFNULL(SUM(t.qty), 0) as total_qty 
    FROM roti r 
    LEFT JOIN transaksi t ON r.id_roti = t.id_roti AND t.tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) $where_roti
    GROUP BY r.id_roti, r.nama_roti
    HAVING total_qty > 0
    ORDER BY total_qty DESC
");
$detail_hari_data = [];
while($r = mysqli_fetch_assoc($q_detail_hari)){
    $detail_hari_data[] = $r;
}

$q_detail_minggu = mysqli_query($conn, "
    SELECT r.nama_roti, IFNULL(SUM(t.qty), 0) as total_qty 
    FROM roti r 
    LEFT JOIN transaksi t ON r.id_roti = t.id_roti AND t.tanggal >= DATE_SUB(CURDATE(), INTERVAL 4 WEEK) $where_roti
    GROUP BY r.id_roti, r.nama_roti
    HAVING total_qty > 0
    ORDER BY total_qty DESC
");
$detail_minggu_data = [];
while($r = mysqli_fetch_assoc($q_detail_minggu)){
    $detail_minggu_data[] = $r;
}

$q_detail_bulan = mysqli_query($conn, "
    SELECT r.nama_roti, IFNULL(SUM(t.qty), 0) as total_qty 
    FROM roti r 
    LEFT JOIN transaksi t ON r.id_roti = t.id_roti AND t.tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) $where_roti
    GROUP BY r.id_roti, r.nama_roti
    HAVING total_qty > 0
    ORDER BY total_qty DESC
");
$detail_bulan_data = [];
while($r = mysqli_fetch_assoc($q_detail_bulan)){
    $detail_bulan_data[] = $r;
}

$q_detail_tahun = mysqli_query($conn, "
    SELECT r.nama_roti, IFNULL(SUM(t.qty), 0) as total_qty 
    FROM roti r 
    LEFT JOIN transaksi t ON r.id_roti = t.id_roti $where_roti
    GROUP BY r.id_roti, r.nama_roti
    HAVING total_qty > 0
    ORDER BY total_qty DESC
");
$detail_tahun_data = [];
while($r = mysqli_fetch_assoc($q_detail_tahun)){
    $detail_tahun_data[] = $r;
}

$sum_hari_ini = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(t.total_harga), 0) as total FROM transaksi t JOIN roti r ON t.id_roti = r.id_roti WHERE t.tanggal = CURDATE() $where_roti"))['total'];
$sum_hari_lalu = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(t.total_harga), 0) as total FROM transaksi t JOIN roti r ON t.id_roti = r.id_roti WHERE t.tanggal = DATE_SUB(CURDATE(), INTERVAL 1 DAY) $where_roti"))['total'];

$sum_mgu_ini = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(t.total_harga), 0) as total FROM transaksi t JOIN roti r ON t.id_roti = r.id_roti WHERE WEEK(t.tanggal) = WEEK(CURDATE()) AND YEAR(t.tanggal) = YEAR(CURDATE()) $where_roti"))['total'];
$sum_mgu_lalu = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(t.total_harga), 0) as total FROM transaksi t JOIN roti r ON t.id_roti = r.id_roti WHERE WEEK(t.tanggal) = WEEK(DATE_SUB(CURDATE(), INTERVAL 1 WEEK)) AND YEAR(t.tanggal) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 WEEK)) $where_roti"))['total'];

$sum_bln_ini = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(t.total_harga), 0) as total FROM transaksi t JOIN roti r ON t.id_roti = r.id_roti WHERE MONTH(t.tanggal) = MONTH(CURDATE()) AND YEAR(t.tanggal) = YEAR(CURDATE()) $where_roti"))['total'];
$sum_bln_lalu = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(t.total_harga), 0) as total FROM transaksi t JOIN roti r ON t.id_roti = r.id_roti WHERE MONTH(t.tanggal) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(t.tanggal) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) $where_roti"))['total'];

$sum_thn_ini = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(t.total_harga), 0) as total FROM transaksi t JOIN roti r ON t.id_roti = r.id_roti WHERE YEAR(t.tanggal) = YEAR(CURDATE()) $where_roti"))['total'];
$sum_thn_lalu = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(t.total_harga), 0) as total FROM transaksi t JOIN roti r ON t.id_roti = r.id_roti WHERE YEAR(t.tanggal) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 YEAR)) $where_roti"))['total'];

// Prediksi per roti
$query_prediksi_roti = mysqli_query($conn, "
    SELECT r.id_roti, r.nama_roti,
           IFNULL(SUM(CASE WHEN t.tanggal = CURDATE() THEN t.qty ELSE 0 END), 0) as penjualan_hari_ini,
           IFNULL(SUM(CASE WHEN t.tanggal = DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN t.qty ELSE 0 END), 0) as penjualan_kemarin
    FROM roti r
    LEFT JOIN transaksi t ON r.id_roti = t.id_roti
    GROUP BY r.id_roti, r.nama_roti
    ORDER BY r.nama_roti ASC
");
$prediksi_per_roti = [];
while($p = mysqli_fetch_assoc($query_prediksi_roti)) {
    $laju = $p['penjualan_hari_ini'] - $p['penjualan_kemarin'];
    $prediksi = $p['penjualan_hari_ini'] + $laju;
    if ($prediksi < 5) { $prediksi = 10; }
    $prediksi_per_roti[] = [
        'id_roti' => $p['id_roti'],
        'nama_roti' => $p['nama_roti'],
        'penjualan_hari_ini' => $p['penjualan_hari_ini'],
        'penjualan_kemarin' => $p['penjualan_kemarin'],
        'laju_perubahan' => $laju,
        'prediksi' => $prediksi,
        'faktor_multiplier' => $p['penjualan_kemarin'] > 0 ? round($p['penjualan_hari_ini'] / $p['penjualan_kemarin'], 2) : 1
    ];
}

// --- KODE DIAGRAM BATANG-DAUN (STATISTIKA) ---
$query_pohon_daun = mysqli_query($conn, "SELECT qty FROM transaksi ORDER BY qty ASC");
$data_qty = [];
while ($row = mysqli_fetch_assoc($query_pohon_daun)) {
    $data_qty[] = (int)$row['qty'];
}

$stem_leaf = [];
foreach ($data_qty as $nilai) {
    $stem = floor($nilai / 10); // Batang mengambil nilai puluhan
    $leaf = $nilai % 10;        // Daun mengambil nilai satuan
    
    if (!isset($stem_leaf[$stem])) {
        $stem_leaf[$stem] = [];
    }
    $stem_leaf[$stem][] = $leaf;
}
ksort($stem_leaf); // Mengurutkan batang dari kecil ke besar
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <link rel="stylesheet" href="style.css">
    <title>Roti Why</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <div id="notifModal" class="modal-box">
        <div class="modal-content">
            <p id="pesanNotif"></p>
            <button onclick="tutupModal()">OK</button>
        </div>
    </div>
    <style>
        #notifikasiCustom {
            position: fixed;
            top: 20px;
            right: 20px;
            min-width: 300px;
            padding: 16px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.2);
            z-index: 10000;
            animation: slideInRight 0.4s ease-out;
            font-weight: 600;
            display: none;
            flex-direction: column;
            gap: 8px;
            backdrop-filter: blur(10px);
            font-size: 14px;
        }

        #notifikasiCustom.sukses {
            background: linear-gradient(135deg, #4a8522 0%, #3a6818 100%);
            color: white;
            border-left: 5px solid #6bb63e;
        }

        #notifikasiCustom.error {
            background: linear-gradient(135deg, #cc4125 0%, #a83520 100%);
            color: white;
            border-left: 5px solid #ff6b4a;
        }

        #notifikasiCustom.warning {
            background: linear-gradient(135deg, #d9a724 0%, #b88a1f 100%);
            color: white;
            border-left: 5px solid #ffb84d;
        }

        #notifikasiCustom.info {
            background: linear-gradient(135deg, #2078f4 0%, #1a5cbe 100%);
            color: white;
            border-left: 5px solid #4a9eff;
        }

        #notifikasiCustom.show {
            display: flex !important;
        }

        #notifikasiCustom.hide {
            animation: slideOutRight 0.4s ease-in forwards;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }

        @media (max-width: 768px) {
            #notifikasiCustom {
                right: 10px;
                left: 10px;
                min-width: auto;
                width: auto;
            }
        }
    </style>
</head>

<body>

<div id="notifikasiCustom"></div>

<div class="header-bar">
    <div>User: <strong><?php echo htmlspecialchars($nama_user); ?></strong> [<?php echo strtoupper($role_user); ?>]</div>
    <div>
        <button class="theme-toggle" onclick="toggleTema()">🌙 Mode Gelap/Terang</button>
        <a href="proses.php?logout=true">🍪 Keluar</a>
    </div>
</div>

<div class="tab-menu">
    <button class="tab-btn active" onclick="bukaTab(event, 'dashboard')">🍞 Dashboard Stok</button>
    <button class="tab-btn" onclick="bukaTab(event, 'input-kasir')">🧁 Input Kasir</button>
    <button class="tab-btn" onclick="bukaTab(event, 'antrian')">🎫 Kelola Antrian</button>
    <button class="tab-btn" onclick="bukaTab(event, 'riwayat-transaksi')">📋 Riwayat & Hapus</button>
    <button class="tab-btn" onclick="bukaTab(event, 'grafik-tab')">📈 Grafik Penjualan</button>
        
    <?php if ($role_user === 'admin') : ?>
        <button class="tab-btn" onclick="bukaTab(event, 'kelola-harga')">💰 Kelola Produk</button>
        <button class="tab-btn" onclick="bukaTab(event, 'kelola-user')">👨‍💼 Kelola Karyawan</button>
    <?php endif; ?>
    <button class="tab-btn" onclick="bukaTab(event, 'pohon-daun')">🍃 Ringkasan Statistik</button>
    <button class="tab-btn" onclick="bukaTab(event, 'kalkulator-spl')">🧮 Kalkulator Produksi (SPL)</button>
</div>

<div id="dashboard" class="tab-content active">
    <h2>🍞 Pantauan Stok & Sisa Roti Hari Ini</h2>
    <table>
        <thead>
            <tr><th>Nama Roti</th><th>Stok Awal Pagi</th><th>Terjual (Pcs)</th><th>Sisa di Etalase</th><th>Status</th></tr>
        </thead>
        <tbody>
            <?php 
            mysqli_data_seek($query_stok_toko, 0);
            while($row = mysqli_fetch_assoc($query_stok_toko)) { 
                $warna_sisa = ($row['sisa_stok'] <= 5) ? "color: #cc4125; font-weight: bold;" : "";
            ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($row['nama_roti']); ?></strong></td>
                <td><?php echo $row['stok_awal']; ?> pcs</td>
                <td><?php echo $row['total_terjual']; ?> pcs</td>
                <td style="<?php echo $warna_sisa; ?>"><?php echo $row['sisa_stok']; ?> pcs</td>
                <td>
                    <?php 
                    if($row['sisa_stok'] <= 0) echo "<span class='badge' style='background:#cc4125;'>Habis Keteteran</span>";
                    elseif($row['sisa_stok'] <= 5) echo "<span class='badge' style='background:#d9a724; color:#fff;'>Sisa Sedikit</span>";
                    else echo "<span class='badge' style='background:#4a8522;'>Aman Bersisa</span>";
                    ?>
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>

    <div class="predict-box">
        <h3>📈 Inteligensi Prediksi Stok Esok Hari (Semua Roti)</h3>
        <p>Total Penjualan Kemarin: <strong><?php echo $penjualan_kemarin; ?> pcs</strong></p>
        <p>Total Penjualan Hari Ini: <strong><?php echo $penjualan_hari_ini; ?> pcs</strong></p>
        <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 15px 0;">
        <h4>🔮 Hasil Prediksi Kebutuhan Produksi Besok: <span style="text-decoration: underline; font-size:20px; font-weight: bold;"><?php echo $prediksi_stok; ?> Unit Kue/Roti</span></h4>
        <small>*Rekomendasi jumlah adonan yang harus disiapkan berdasarkan analisis dinamika fluktuasi orderan terakhir. Untuk detail per roti, lihat di tab Grafik Penjualan.</small>
    </div>
</div>

<div id="input-kasir" class="tab-content">
    <h2>🧁 Input Transaksi Penjualan Baru</h2>
    <form action="proses.php" method="POST" autocomplete="off">
        <div class="form-group"><label>Nama Pembeli</label><input type="text" name="nama_pembeli" autocomplete="off" required placeholder="Nama..."></div>
        <div class="form-group">
            <label>Jenis Roti</label>
            <select name="id_roti" id="id_roti" onchange="updateHarga()" required>
                <option value="">-- Pilih Roti --</option>
                <?php 
                mysqli_data_seek($query_roti, 0); 
                while($r = mysqli_fetch_assoc($query_roti)) {
                    echo "<option value='".$r['id_roti']."' data-harga='".$r['harga']."'>".$r['nama_roti']." (Rp ".number_format($r['harga']).")</option>";
                } 
                ?>
            </select>
        </div>
        <div class="form-group"><label>Harga Satuan</label><input type="number" id="harga_tampil" readonly style="background:#f4f0ea; color:#333;"></div>
        <div class="form-group"><label>Quantity</label>
            <div class="qty-container">
                <button type="button" class="btn-qty" onclick="ubahQty(-1)">−</button>
                <input type="number" name="qty" id="qty" value="1" min="1" style="text-align:center; width:80px;" required>
                <button type="button" class="btn-qty" onclick="ubahQty(1)">+</button>
            </div>
        </div>
        
        <div class="form-group">
            <label>Tanggal Transaksi</label>
            <input type="date" name="tanggal" required value="<?php echo date('Y-m-d'); ?>">
        </div>

        <button type="submit" name="submit_transaksi" class="btn-submit">Simpan Transaksi</button>
    </form>
</div>

<div id="antrian" class="tab-content">
    <h2>🎫 Kelola Nomor Antrian Penjualan</h2>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
        <!-- Form Buat Antrian Baru -->
        <div style="background: var(--accent-cream); padding: 20px; border-radius: 8px; border: 1px solid var(--border-color);">
            <h3 style="margin-bottom: 15px;">➕ Buat Antrian Baru</h3>
            <form action="proses.php" method="POST">
                <div style="background: var(--bg-card); padding: 16px; border-radius: 8px; margin-bottom: 15px; text-align: center; border: 2px solid var(--primary-orange);">
                    <p style="font-size: 12px; color: var(--text-light); margin-bottom: 5px;">Nomor Antrian Berikutnya:</p>
                    <div style="font-size: 36px; font-weight: bold; color: var(--primary-orange);"><?php echo str_pad($nomor_antrian_baru, 3, '0', STR_PAD_LEFT); ?></div>
                </div>
                <button type="submit" name="buat_antrian" class="btn-submit" style="background: var(--primary-orange);">🎫 Buat Antrian</button>
            </form>
        </div>

        <!-- Daftar Antrian Hari Ini -->
        <div style="background: var(--accent-cream); padding: 20px; border-radius: 8px; border: 1px solid var(--border-color);">
            <h3 style="margin-bottom: 15px;">📊 Status Antrian Hari Ini</h3>
            <div style="max-height: 300px; overflow-y: auto;">
                <?php 
                mysqli_data_seek($query_antrian, 0);
                $total_antrian = 0;
                $antrian_selesai = 0;
                while($a = mysqli_fetch_assoc($query_antrian)) { 
                    $total_antrian++;
                    if($a['status'] === 'selesai') $antrian_selesai++;
                    
                    // Tentukan warna border dan badge berdasarkan status
                    $border_color = '#d9a724';
                    $badge_color = '#d9a724';
                    $badge_text = '⏳ PROSES';
                    
                    if ($a['status'] === 'selesai') {
                        $border_color = '#4a8522';
                        $badge_color = '#4a8522';
                        $badge_text = '✅ SELESAI';
                    } elseif ($a['status'] === 'dibatalkan') {
                        $border_color = '#cc4125';
                        $badge_color = '#cc4125';
                        $badge_text = '❌ DIBATALKAN';
                    }
                ?>
                <div style="background: var(--bg-card); padding: 12px; border-radius: 6px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; border-left: 4px solid <?php echo $border_color; ?>;">
                    <div>
                        <div style="font-size: 16px; font-weight: bold; color: var(--primary-orange);">No. <?php echo str_pad($a['nomor_antrian'], 3, '0', STR_PAD_LEFT); ?></div>
                        <div style="font-size: 12px; font-weight: 600; color: var(--text-color); margin-top: 2px;"><?php echo htmlspecialchars($a['nama_pembeli']); ?></div>
                        <div style="font-size: 11px; color: var(--text-light);">🍞 <?php echo htmlspecialchars($a['nama_roti']); ?></div>
                        <div style="font-size: 11px; color: var(--text-light);">⏰ <?php echo date('H:i', strtotime($a['jam_dibuka'])); ?></div>
                    </div>
                    <div style="text-align: right;">
                        <span style="display: inline-block; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 600; background: <?php echo $badge_color; ?>; color: white;">
                            <?php echo $badge_text; ?>
                        </span>
                        <?php if($a['status'] === 'open'): ?>
                            <br>
                            <a href="proses.php?tutup_antrian=<?php echo $a['id_antrian']; ?>" class="btn-danger" style="font-size: 10px; padding: 4px 8px; margin-top: 5px; display: inline-block;" onclick="return confirm('Tutup antrian ini?')">Selesai</a>
                            <br>
                            <a href="proses.php?batal_antrian=<?php echo $a['id_antrian']; ?>" class="btn-danger" style="font-size: 10px; padding: 4px 8px; margin-top: 3px; display: inline-block; background: #d97b2e;" onclick="return confirm('Batalkan antrian ini? Stock akan dikembalikan!')">Batalkan</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php } ?>
                <?php if($total_antrian === 0): ?>
                <p style="color: var(--text-light); text-align: center; padding: 20px 0;">Belum ada antrian hari ini</p>
                <?php else: ?>
                <div style="background: var(--bg-body); padding: 10px; border-radius: 6px; margin-top: 10px; text-align: center; font-size: 12px;">
                    <strong><?php echo $antrian_selesai; ?>/<?php echo $total_antrian; ?></strong> antrian selesai hari ini
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div id="riwayat-transaksi" class="tab-content">
    <h2>📋 Daftar 10 Transaksi Terakhir</h2>
    <table>
        <thead><tr><th>Nama</th><th>Produk</th><th>Qty</th><th>Total</th><th>Tanggal</th><th>Aksi</th></tr></thead>
        <tbody>
            <?php 
            mysqli_data_seek($query_riwayat, 0);
            while($rw = mysqli_fetch_assoc($query_riwayat)) { 
            ?>
            <tr>
                <td><?php echo htmlspecialchars($rw['nama_pembeli']); ?></td>
                <td><?php echo htmlspecialchars($rw['nama_roti']); ?></td>
                <td><?php echo $rw['qty']; ?> pcs</td>
                <td>Rp <?php echo number_format($rw['total_harga']); ?></td>
                <td><?php echo $rw['tanggal']; ?></td>
                <td><a href="proses.php?hapus_transaksi=<?php echo $rw['id_transaksi']; ?>" class="btn-danger" onclick="return confirm('Yakin ingin hapus? Stock akan dikembalikan!')">❌ Hapus</a></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<div id="grafik-tab" class="tab-content" style="text-align: center;">
    <h2>📈 Analisis Data Grafik Omset Penjualan & Pendapatan Berkala</h2>
    
    <div style="display: flex; flex-direction: column; align-items: center; gap: 12px; margin-bottom: 20px;">
        
        <div class="sub-tab-menu">
            <button class="sub-btn active" id="btn_hari" onclick="gantiFilterGrafik('hari', this)">Hari</button>
            <button class="sub-btn" onclick="gantiFilterGrafik('minggu', this)">Minggu</button>
            <button class="sub-btn" onclick="gantiFilterGrafik('bulan', this)">Bulan</button>
            <button class="sub-btn" onclick="gantiFilterGrafik('tahun', this)">Tahun</button>
        </div>

        <div style="display: flex; gap: 10px; flex-wrap: wrap; justify-content: center;">
            
            <div style="background: var(--accent-cream); padding: 8px 15px; border-radius: 6px; font-size: 13px; font-weight: bold;">
                <label style="margin-right: 5px; color: var(--text-color);">Pilih Varian Roti:</label>
                <select id="filter_roti_grafik" onchange="gantiRotiGrafik(this.value)" style="padding: 4px 8px; border-radius: 4px; border: 1px solid var(--border-color); font-weight: bold; color: var(--text-color); cursor: pointer; background: var(--bg-card);">
                    <option value="all" <?php echo $id_roti_grafik === 'all' ? 'selected' : ''; ?>>-- Semua Varian Roti --</option>
                    <?php 
                    mysqli_data_seek($query_roti, 0); 
                    while($r = mysqli_fetch_assoc($query_roti)) {
                        $selected = ($id_roti_grafik == $r['id_roti']) ? 'selected' : '';
                        echo "<option value='".$r['id_roti']."' $selected>".htmlspecialchars($r['nama_roti'])."</option>";
                    } 
                    ?>
                </select>
            </div>

            <div id="tipe-grafik-container" style="background: var(--accent-cream); padding: 8px 15px; border-radius: 6px; font-size: 13px; font-weight: bold;">
                <label style="margin-right: 5px; color: var(--text-color);">Bentuk Grafik Kuantitas:</label>
                <select id="tipe_chart" onchange="gantiTipeChart(this.value)" style="padding: 4px 8px; border-radius: 4px; border: 1px solid var(--border-color); font-weight: bold; color: var(--text-color); cursor: pointer; background: var(--bg-card);">
                    <option value="bar">📊 Batang (Bar)</option>
                    <option value="line">📈 Garis (Line)</option>
                </select>
            </div>

        </div>
    </div>

    <div style="max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 250px 1fr; gap: 20px;">
        <div style="background: var(--bg-card); padding: 16px; border-radius: 8px; border: 1px solid var(--border-color); height: fit-content; max-height: 500px; overflow-y: auto;">
            <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 12px; color: var(--text-color);">📊 Roti Terjual</h3>
            <div id="detail-roti-container" style="font-size: 12px;">
                <p style="color: var(--text-light); text-align: center; padding: 10px 0;">Pilih periode untuk melihat detail</p>
            </div>
        </div>

        <div>
            <div style="background: var(--bg-card); padding: 20px; border-radius: 8px; border:1px solid var(--border-color); text-align: left; box-shadow: 0 4px 10px rgba(0,0,0,0.02);">
                <div style="position: relative; height: 350px; min-height: 350px; width: 100%;">
                    <canvas id="chartPenjualan"></canvas>
                </div>
                <div class="summary-income-box" id="box_summary_text">
                </div>
            </div>
            
            <div style="background: var(--bg-card); padding: 20px; border-radius: 8px; border:1px solid var(--border-color); margin-top: 20px;">
                <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 16px; color: var(--text-color);">🔮 Prediksi Stok Esok Hari Per Roti</h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: linear-gradient(135deg, var(--primary-orange) 0%, var(--secondary-orange) 100%); color: white;">
                            <th style="padding: 12px; text-align: left; border: 1px solid var(--border-color);">Nama Roti</th>
                            <th style="padding: 12px; text-align: center; border: 1px solid var(--border-color);">Kemarin</th>
                            <th style="padding: 12px; text-align: center; border: 1px solid var(--border-color);">Hari Ini</th>
                            <th style="padding: 12px; text-align: center; border: 1px solid var(--border-color);">Faktor</th>
                            <th style="padding: 12px; text-align: center; border: 1px solid var(--border-color);">Prediksi Besok</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($prediksi_per_roti as $p): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px; border: 1px solid var(--border-color); font-weight: 600;"><?php echo htmlspecialchars($p['nama_roti']); ?></td>
                            <td style="padding: 12px; text-align: center; border: 1px solid var(--border-color);"><?php echo $p['penjualan_kemarin']; ?> pcs</td>
                            <td style="padding: 12px; text-align: center; border: 1px solid var(--border-color);"><?php echo $p['penjualan_hari_ini']; ?> pcs</td>
                            <td style="padding: 12px; text-align: center; border: 1px solid var(--border-color); font-weight: 600; color: var(--primary-orange);">
                                <?php 
                                if ($p['faktor_multiplier'] > 2) {
                                    echo "⚡ " . $p['faktor_multiplier'] . "x";
                                } elseif ($p['faktor_multiplier'] < 1) {
                                    echo "↓ " . $p['faktor_multiplier'] . "x";
                                } else {
                                    echo "→ " . $p['faktor_multiplier'] . "x";
                                }
                                ?>
                            </td>
                            <td style="padding: 12px; text-align: center; border: 1px solid var(--border-color); font-weight: 700; font-size: 14px; background: linear-gradient(135deg, rgba(232,137,58,0.1), rgba(245,169,98,0.05)); color: var(--primary-orange);"><?php echo $p['prediksi']; ?> pcs</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="margin-top: 15px; padding: 12px; background: var(--accent-cream); border-radius: 6px; border-left: 4px solid var(--primary-orange); font-size: 13px; color: var(--text-color);">
                    <strong>💡 Panduan Pembacaan:</strong>
                    <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                        <li>⚡ <strong>Faktor > 2x:</strong> Penjualan melonjak drastis, butuh adonan lebih banyak</li>
                        <li>→ <strong>Faktor ≈ 1x:</strong> Penjualan stabil, adonan sama seperti hari ini</li>
                        <li>↓ <strong>Faktor < 1x:</strong> Penjualan menurun, kurangi adonan</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($role_user === 'admin') : ?>
<div id="kelola-harga" class="tab-content">
    <h2>💰 Atur Harga Produk Tersedia</h2>
    <form action="proses.php" method="POST" autocomplete="off">
        <div class="form-group">
            <select name="id_roti" required>
                <?php 
                mysqli_data_seek($query_roti, 0); 
                while($r = mysqli_fetch_assoc($query_roti)) {
                    echo "<option value='".$r['id_roti']."'>".$r['nama_roti']." - Rp ".number_format($r['harga'])."</option>";
                } 
                ?>
            </select>
        </div>
        <div class="form-group"><input type="number" name="harga_baru" required placeholder="Harga Baru..."></div>
        <button type="submit" name="update_harga" class="btn-submit" style="background:#007bff;">Update Harga</button>
    </form>

    <hr style="margin: 30px 0; border:0; border-top:1px dashed var(--border-color);">
    
    <h2>🍞 Kelola Stock Harian</h2>
    <form action="proses.php" method="POST" autocomplete="off">
        <div class="form-group">
            <label>Pilih Roti</label>
            <select name="id_roti" required>
                <?php 
                mysqli_data_seek($query_roti, 0); 
                while($r = mysqli_fetch_assoc($query_roti)) {
                    echo "<option value='".$r['id_roti']."'>".$r['nama_roti']." (Stok Pagi Ini: ".$r['stok_awal']." pcs)</option>";
                } 
                ?>
            </select>
        </div>
        <div class="form-group">
            <label>Jumlah Stok Produksi Baru (Pcs)</label>
            <input type="number" name="stok_baru" required placeholder="Contoh: 50">
        </div>
        <button type="submit" name="update_stok" class="btn-submit" style="background:#17a2b8;">Perbarui Stok Etalase</button>
    </form>

    <hr style="margin: 30px 0; border:0; border-top:1px dashed var(--border-color);">

    <h2>🆕 Tambah Varian Roti dan Kue Baru</h2>
    <form action="proses.php" method="POST" autocomplete="off">
        <div class="form-group">
            <label>Nama Varian Roti Baru</label>
            <input type="text" name="nama_roti_baru" autocomplete="off" required placeholder="Contoh: Roti Keju Melted">
        </div>
        <div class="form-group">
            <label>Harga Jual Standar (Rp)</label>
            <input type="number" name="harga_roti_baru" required placeholder="Contoh: 15000">
        </div>
        <div class="form-group">
            <label>Stok Awal Produksi (Pcs)</label>
            <input type="number" name="stok_awal_baru" required placeholder="Contoh: 50">
        </div>
        <button type="submit" name="tambah_roti_baru" class="btn-submit" style="background:#28a745;">Tambah Roti Baru</button>
    </form>

    <hr style="margin: 30px 0; border:0; border-top:1px dashed var(--border-color);">

    <h2>🗑️ Hapus Varian Roti</h2>
    <table>
        <thead><tr><th>Nama Roti</th><th>Harga</th><th>Stok</th><th>Aksi</th></tr></thead>
        <tbody>
            <?php 
            mysqli_data_seek($query_roti, 0); 
            while($r = mysqli_fetch_assoc($query_roti)) { 
            ?>
            <tr>
                <td><?php echo htmlspecialchars($r['nama_roti']); ?></td>
                <td>Rp <?php echo number_format($r['harga']); ?></td>
                <td><?php echo $r['stok_awal']; ?> pcs</td>
                <td><a href="proses.php?hapus_roti=<?php echo $r['id_roti']; ?>" class="btn-danger" onclick="return confirm('Hapus roti ini?')">❌ Hapus</a></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<div id="kelola-user" class="tab-content">
    <h2>👨‍💼 Kelola Akun User</h2>
    <form action="proses.php" method="POST" autocomplete="off">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" autocomplete="off" required placeholder="Contoh: budi_karyawan">
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" autocomplete="new-password" required placeholder="Kata sandi...">
        </div>
        <div class="form-group">
            <label>Nama Lengkap</label>
            <input type="text" name="nama_lengkap" autocomplete="off" required placeholder="Contoh: Budi Santoso">
        </div>
        <div class="form-group">
            <label>Role Akses</label>
            <select name="role" required>
                <option value="kasir">Kasir (Input Penjualan)</option>
                <option value="admin">Admin (Full Control)</option>
            </select>
        </div>
        <button type="submit" name="tambah_user" class="btn-submit" style="background:#28a745;">Daftarkan Karyawan Baru</button>
    </form>

    <hr style="margin: 30px 0; border:0; border-top:1px dashed var(--border-color);">

    <h2>📋 Daftar Karyawan Aktif</h2>
    <table>
        <thead><tr><th>Username</th><th>Nama Lengkap</th><th>Role</th><th>Aksi</th></tr></thead>
        <tbody>
            <?php 
            mysqli_data_seek($query_users, 0); 
            while($u = mysqli_fetch_assoc($query_users)) { 
            ?>
            <tr>
                <td><?php echo htmlspecialchars($u['username']); ?></td>
                <td><?php echo htmlspecialchars($u['nama_lengkap']); ?></td>
                <td><span class="badge"><?php echo strtoupper($u['role']); ?></span></td>
                <td><a href="proses.php?hapus_user=<?php echo $u['id_user']; ?>" class="btn-danger" onclick="return confirm('Hapus user ini?')">❌ Hapus</a></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div id="pohon-daun" class="tab-content" style="text-align: center;">
    <h2>🍃 Analisis Statistika: Diagram Batang-Daun (Stem-and-Leaf Plot)</h2>
    <p style="color: var(--text-light); max-width: 700px; margin: 0 auto;">
        Mengukur distribusi frekuensi ukuran kuantitas (*qty*) pesanan per transaksi untuk mengetahui perilaku pembelian pelanggan.
    </p>
    
    <div style="background: var(--bg-card); padding: 25px; border-radius: 8px; border: 1px solid var(--border-color); margin: 25px auto; max-width: 650px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); text-align: left;">
        <?php if (empty($data_qty)): ?>
            <p style="color: var(--text-light); text-align: center; padding: 20px 0;">Belum ada data transaksi masuk untuk dipetakan.</p>
        <?php else: ?>
            
            <div style="display: flex; font-size: 14px; font-weight: bold; color: var(--text-light); margin-bottom: 10px; border-bottom: 2px solid var(--primary-orange); padding-bottom: 8px;">
                <div style="width: 120px; text-align: right; padding-right: 20px;">Batang (Puluhan)</div>
                <div style="padding-left: 20px;">Daun (Satuan)</div>
            </div>

            <div style="font-family: 'Courier New', Courier, monospace; font-size: 22px; display: flex; flex-direction: column; gap: 8px; margin-bottom: 25px;">
                <?php foreach ($stem_leaf as $stem => $leaves): ?>
                    <div style="display: flex; align-items: center;">
                        <div style="width: 120px; text-align: right; font-weight: bold; color: var(--primary-orange); padding-right: 20px; border-right: 3px solid var(--primary-orange);">
                            <?php echo $stem; ?>
                        </div>
                        <div style="padding-left: 20px; letter-spacing: 10px; color: var(--text-color); font-weight: bold; word-break: break-all;">
                            <?php echo implode('', $leaves); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div style="padding: 15px; background: var(--accent-cream); border-radius: 6px; border-left: 4px solid var(--primary-orange); font-size: 13px; color: var(--text-color); font-family: 'Segoe UI', sans-serif;">
                <strong>📊 Ringkasan Statistika Deskriptif:</strong>
                <ul style="margin: 8px 0 0 0; padding-left: 20px; line-height: 1.6;">
                    <li>Total Sampel Transaksi ($n$): <strong><?php echo count($data_qty); ?></strong> data</li>
                    <li>Pembelian Paling Sedikit (Min): <strong><?php echo min($data_qty); ?> pcs</strong></li>
                    <li>Pembelian Paling Banyak (Max): <strong><?php echo max($data_qty); ?> pcs</strong></li>
                    <li style="margin-top: 8px;">
                        <strong>💡 Cara Membaca Diagram:</strong> Garis oranye bertindak sebagai pembatas. Angka di sebelah kiri adalah puluhan (Batang), angka di kanan adalah satuan (Daun).
                        <br><em>Contoh:</em> Jika baris menunjukkan <code>1 | 0 2 5</code>, artinya ada 3 transaksi terpisah dengan kuantitas pembelian sebanyak <strong>10 pcs</strong>, <strong>12 pcs</strong>, dan <strong>15 pcs</strong>.
                    </li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="kalkulator-spl" class="tab-content">
    <h2>🧮 Kalkulator Kapasitas Produksi</h2>
    <p style="color: var(--text-light); margin-bottom: 20px;">
        Masukkan sisa bahan baku di gudang. Sistem akan menghitung secara eksak berapa jumlah maksimal Croissant, Roti Tawar, dan Kue Cokelat yang bisa diproduksi hingga bahan baku habis tanpa sisa.
    </p>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div style="background: var(--accent-cream); padding: 20px; border-radius: 8px; border: 1px solid var(--border-color);">
            <h3>📥 Input Sisa Bahan Baku</h3>
            <form action="index.php?tab=kalkulator-spl" method="POST" autocomplete="off" style="margin-top: 15px;">
                <div class="form-group">
                    <label>Total Tepung Terigu (Gram)</label>
                    <input type="number" name="stok_tepung" required placeholder="Contoh: 5000" value="<?php echo isset($_POST['stok_tepung']) ? $_POST['stok_tepung'] : ''; ?>">
                </div>
                <div class="form-group">
                    <label>Total Susu Cair (Mililiter)</label>
                    <input type="number" name="stok_susu" required placeholder="Contoh: 2000" value="<?php echo isset($_POST['stok_susu']) ? $_POST['stok_susu'] : ''; ?>">
                </div>
                <div class="form-group">
                    <label>Total Mentega (Gram)</label>
                    <input type="number" name="stok_mentega" required placeholder="Contoh: 1000" value="<?php echo isset($_POST['stok_mentega']) ? $_POST['stok_mentega'] : ''; ?>">
                </div>
                <button type="submit" name="hitung_spl" class="btn-submit" style="background: var(--primary-orange);">Hitung Kapasitas</button>
            </form>
        </div>

        <div style="background: var(--bg-card); padding: 20px; border-radius: 8px; border: 1px solid var(--border-color);">
            <h3>📤 Hasil Perhitungan SPL</h3>
            <?php
            if (isset($_POST['hitung_spl'])) {
                // Asumsi Resep (Matriks Koefisien)
                // Roti A (Croissant) -> x
                $a1 = 200; // Gram tepung
                $a2 = 50;  // ml susu
                $a3 = 30;  // Gram mentega

                // Roti B (Roti Tawar) -> y
                $b1 = 300; 
                $b2 = 100; 
                $b3 = 20;  

                // Roti C (Kue Cokelat) -> z
                $c1 = 150; 
                $c2 = 200; 
                $c3 = 50;  

                // Stok dari Input User (Konstanta)
                $d1 = (float)$_POST['stok_tepung'];
                $d2 = (float)$_POST['stok_susu'];
                $d3 = (float)$_POST['stok_mentega'];

                // Determinan Utama (D)
                $D = ($a1*$b2*$c3) + ($b1*$c2*$a3) + ($c1*$a2*$b3) - ($c1*$b2*$a3) - ($a1*$c2*$b3) - ($b1*$a2*$c3);

                if ($D == 0) {
                    echo "<p style='color: #cc4125; font-weight: bold;'>Sistem tidak memiliki solusi unik (Determinan = 0). Resep proporsional atau data tidak valid.</p>";
                } else {
                    // Determinan Dx, Dy, Dz
                    $Dx = ($d1*$b2*$c3) + ($b1*$c2*$d3) + ($c1*$d2*$b3) - ($c1*$b2*$d3) - ($d1*$c2*$b3) - ($b1*$d2*$c3);
                    $Dy = ($a1*$d2*$c3) + ($d1*$c2*$a3) + ($c1*$a2*$d3) - ($c1*$d2*$a3) - ($a1*$c2*$d3) - ($d1*$a2*$c3);
                    $Dz = ($a1*$b2*$d3) + ($b1*$d2*$a3) + ($d1*$a2*$b3) - ($d1*$b2*$a3) - ($a1*$d2*$b3) - ($b1*$a2*$d3);

                    // Aturan Cramer
                    $x = $Dx / $D;
                    $y = $Dy / $D;
                    $z = $Dz / $D;

                    echo "<div style='margin-top: 15px;'>";
                    
                    if ($x < 0 || $y < 0 || $z < 0) {
                        echo "<div style='padding: 10px; background: #fff3cd; color: #856404; border-radius: 6px; margin-bottom: 15px; font-size: 13px;'>⚠️ Peringatan: Hasil ada yang minus. Proporsi bahan baku di gudang tidak pas untuk menghabiskan semua bahan secara bersamaan dengan resep ini.</div>";
                    }

                    echo "<table style='width:100%; border-collapse: collapse;'>";
                    echo "<tr><th style='background: var(--primary-orange); color: white; padding: 10px;'>Varian Roti</th><th style='background: var(--primary-orange); color: white; padding: 10px;'>Potensi Produksi</th></tr>";
                    echo "<tr><td style='padding: 10px; border: 1px solid var(--border-color);'>🥐 Croissant</td><td style='padding: 10px; border: 1px solid var(--border-color); font-weight: bold;'>" . round($x, 2) . " pcs</td></tr>";
                    echo "<tr><td style='padding: 10px; border: 1px solid var(--border-color);'>🍞 Roti Tawar</td><td style='padding: 10px; border: 1px solid var(--border-color); font-weight: bold;'>" . round($y, 2) . " pcs</td></tr>";
                    echo "<tr><td style='padding: 10px; border: 1px solid var(--border-color);'>🧁 Kue Cokelat</td><td style='padding: 10px; border: 1px solid var(--border-color); font-weight: bold;'>" . round($z, 2) . " pcs</td></tr>";
                    echo "</table>";
                    echo "</div>";
                }
            } else {
                echo "<p style='color: var(--text-light); text-align: center; margin-top: 40px;'>Silakan masukkan data bahan baku di samping untuk mulai menghitung.</p>";
            }
            ?>
        </div>
    </div>
</div>

<script>
function showNotifikasi(pesan, tipe) {
    const notif = document.getElementById('notifikasiCustom');
    notif.textContent = pesan;
    notif.className = tipe + ' show';
    setTimeout(() => {
        notif.classList.add('hide');
        setTimeout(() => {
            notif.classList.remove('show', 'hide');
        }, 400);
    }, 3500);
}

function toggleTema() {
    const html = document.documentElement;
    const tema = html.getAttribute('data-theme');
    const temaBaru = tema === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', temaBaru);
    sessionStorage.setItem('tema', temaBaru);
}

function bukaTab(evt, nama) {
    let tabs = document.getElementsByClassName("tab-content");
    for (let i = 0; i < tabs.length; i++) {
        tabs[i].classList.remove("active");
    }
    if (evt) {
        let buttons = document.getElementsByClassName("tab-btn");
        for (let i = 0; i < buttons.length; i++) {
            buttons[i].classList.remove("active");
        }
        evt.currentTarget.classList.add("active");
    }
    document.getElementById(nama).classList.add("active");
}

function updateHarga() {
    let select = document.getElementById('id_roti');
    let pilihan = select.options[select.selectedIndex];
    document.getElementById('harga_tampil').value = pilihan.getAttribute('data-harga');
}

function ubahQty(delta) {
    let input = document.getElementById('qty');
    let nilai = parseInt(input.value) || 1;
    nilai += delta;
    if (nilai < 1) nilai = 1;
    input.value = nilai;
}

function tutupModal() {
    document.getElementById("notifModal").style.display = "none";
}

let ctx = null;
let currentFilter = 'hari';

const detailRotiData = {
    hari: <?php echo json_encode($detail_hari_data); ?>,
    minggu: <?php echo json_encode($detail_minggu_data); ?>,
    bulan: <?php echo json_encode($detail_bulan_data); ?>,
    tahun: <?php echo json_encode($detail_tahun_data); ?>
};

const dataGrafik = {
    hari: { titleQty: 'Penjualan Harian', titleDuit: 'Omset Harian', qty: <?php echo json_encode(array_values($g_hari_val)); ?>, duit: <?php echo json_encode(array_values($g_hari_duit)); ?>, labels: <?php echo json_encode(array_values($g_hari_lbl)); ?> },
    minggu: { titleQty: 'Penjualan Mingguan', titleDuit: 'Omset Mingguan', qty: <?php echo json_encode(array_values($g_mgu_val)); ?>, duit: <?php echo json_encode(array_values($g_mgu_duit)); ?>, labels: <?php echo json_encode(array_values($g_mgu_lbl)); ?> },
    bulan: { titleQty: 'Penjualan Bulanan', titleDuit: 'Omset Bulanan', qty: <?php echo json_encode(array_values($g_bln_val)); ?>, duit: <?php echo json_encode(array_values($g_bln_duit)); ?>, labels: <?php echo json_encode(array_values($g_bln_lbl)); ?> },
    tahun: { titleQty: 'Penjualan Tahunan', titleDuit: 'Omset Tahunan', qty: <?php echo json_encode(array_values($g_thn_val)); ?>, duit: <?php echo json_encode(array_values($g_thn_duit)); ?>, labels: <?php echo json_encode(array_values($g_thn_lbl)); ?> }
};

const teksteksPendapatan = {
    hari: 'Pendapatan Hari Ini <strong>Rp <?php echo number_format($sum_hari_ini); ?></strong>, Kemarin <strong>Rp <?php echo number_format($sum_hari_lalu); ?></strong>.',
    minggu: 'Pendapatan Minggu Ini <strong>Rp <?php echo number_format($sum_mgu_ini); ?></strong>, Minggu Lalu <strong>Rp <?php echo number_format($sum_mgu_lalu); ?></strong>.',
    bulan: 'Pendapatan Bulan Ini <strong>Rp <?php echo number_format($sum_bln_ini); ?></strong>, Bulan Lalu <strong>Rp <?php echo number_format($sum_bln_lalu); ?></strong>.',
    tahun: 'Pendapatan Tahun Ini <strong>Rp <?php echo number_format($sum_thn_ini); ?></strong>, Tahun Lalu <strong>Rp <?php echo number_format($sum_thn_lalu); ?></strong>.'
};

function updateTeksRingkasan(tipe) {
    const elem = document.getElementById('box_summary_text');
    if (elem) {
        elem.innerHTML = teksteksPendapatan[tipe];
    }
}

let chartInstance = null;

function renderChart(tipeFilter, tipeChartQty) {
    const canvasElement = document.getElementById('chartPenjualan');
    if (!canvasElement) return;
    
    if (!ctx) {
        ctx = canvasElement.getContext('2d');
    }
    
    if (chartInstance) {
        chartInstance.destroy();
        chartInstance = null;
    }

    const data = dataGrafik[tipeFilter];
    if (!data) return;

    const bgWarnaQty = tipeChartQty === 'bar' 
        ? 'rgba(232, 137, 58, 0.7)'
        : 'rgba(232, 137, 58, 0.2)';
    const fillOpsiQty = tipeChartQty === 'bar' ? false : true;

    try {
        chartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels && data.labels.length > 0 ? data.labels : [],
                datasets: [
                    {
                        type: tipeChartQty,
                        label: data.titleQty,
                        data: data.qty && data.qty.length > 0 ? data.qty : [],
                        backgroundColor: bgWarnaQty,
                        borderColor: 'rgba(217, 119, 36, 1)',
                        borderWidth: 2,
                        borderRadius: tipeChartQty === 'bar' ? 4 : 0,
                        fill: fillOpsiQty,
                        yAxisID: 'y_qty'
                    },
                    {
                        type: 'line',
                        label: data.titleDuit,
                        data: data.duit && data.duit.length > 0 ? data.duit : [],
                        backgroundColor: 'rgba(32, 120, 244, 0.1)',
                        borderColor: 'rgba(32, 120, 244, 1)',
                        borderWidth: 2.5,
                        pointBackgroundColor: 'rgba(32, 120, 244, 1)',
                        fill: true,
                        tension: 0.15,
                        yAxisID: 'y_duit'
                    }
                ]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: { 
                    x: {
                        display: true
                    },
                    y_qty: { 
                        type: 'linear', 
                        position: 'left', 
                        beginAtZero: true, 
                        ticks: { stepSize: 1 },
                        title: { display: true, text: 'Jumlah Terjual (Pcs)', color: '#d97724', font: { weight: 'bold' } }
                    },
                    y_duit: {
                        type: 'linear', 
                        position: 'right', 
                        beginAtZero: true,
                        grid: { drawOnChartArea: false },
                        title: { display: true, text: 'Pendapatan (Rupiah)', color: '#2078f4', font: { weight: 'bold' } },
                        ticks: { callback: function(value) { return 'Rp ' + value.toLocaleString('id-ID'); } }
                    }
                },
                plugins: {
                    legend: { display: true },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) { label += ': '; }
                                if (context.datasetIndex === 1) {
                                    label += 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                                } else {
                                    label += context.parsed.y + ' pcs';
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });

        updateTeksRingkasan(tipeFilter);
    } catch(e) {
        console.error('Error rendering chart:', e);
    }
}

function updateDetailRoti(tipe) {
    const container = document.getElementById('detail-roti-container');
    if (!container) return;
    
    const detailList = detailRotiData[tipe] || [];
    let html = '';
    
    if (detailList.length === 0) {
        html = '<p style="color: var(--text-light); text-align: center; padding: 10px 0;">📊 Tidak ada data</p>';
    } else {
        const maxQty = Math.max(...detailList.map(d => d.total_qty));
        
        html = detailList.map(item => {
            const percentage = (item.total_qty / maxQty) * 100;
            return `
                <div style="margin-bottom: 12px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span style="font-weight: 600; color: var(--text-color); font-size: 11px;">${htmlEscape(item.nama_roti)}</span>
                        <span style="font-weight: 700; color: var(--primary-orange);">${item.total_qty} pcs</span>
                    </div>
                    <div style="background: var(--border-color); height: 8px; border-radius: 4px; overflow: hidden;">
                        <div style="background: linear-gradient(90deg, var(--primary-orange), var(--secondary-orange)); height: 100%; width: ${percentage}%; border-radius: 4px;"></div>
                    </div>
                </div>
            `;
        }).join('');
    }
    
    container.innerHTML = html;
}
    
function htmlEscape(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function gantiFilterGrafik(tipe, elemen) {
    currentFilter = tipe;
    let subBtns = document.getElementsByClassName("sub-btn");
    for (let i = 0; i < subBtns.length; i++) { 
        subBtns[i].className = subBtns[i].className.replace(" active", ""); 
    }
    if (elemen) { 
        elemen.className += " active"; 
    }

    let elemenTipeChart = document.getElementById('tipe_chart');
    let tipeChartQty = elemenTipeChart ? elemenTipeChart.value : 'bar';
    
    setTimeout(() => {
        renderChart(tipe, tipeChartQty);
        updateDetailRoti(tipe);
    }, 50);
}

function gantiTipeChart(tipeChartQty) {
    renderChart(currentFilter, tipeChartQty);
}

function gantiRotiGrafik(idRoti) {
    window.location.href = 'index.php?id_roti_grafik=' + idRoti;
}

window.addEventListener('DOMContentLoaded', (event) => {
    const tema = sessionStorage.getItem('tema');
    if (tema) {
        document.documentElement.setAttribute('data-theme', tema);
    }

    const canvasElement = document.getElementById('chartPenjualan');
    if (canvasElement) {
        ctx = canvasElement.getContext('2d');
        if (ctx) {
            setTimeout(() => {
                renderChart('hari', 'bar');
                updateDetailRoti('hari');
            }, 100);
        }
    }

    const urlParams = new URLSearchParams(window.location.search);
    const tabTarget = urlParams.get('tab');
    
    if (tabTarget) {
        bukaTab(null, tabTarget);
        let tablinks = document.getElementsByClassName("tab-btn");
        for (let i = 0; i < tablinks.length; i++) {
            let onClickAttr = tablinks[i].getAttribute("onclick");
            if (onClickAttr && onClickAttr.includes(tabTarget)) {
                tablinks[i].className += " active";
            } else {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
        }
    } else if (urlParams.get('id_roti_grafik')) {
        bukaTab(null, 'grafik-tab');
        let tablinks = document.getElementsByClassName("tab-btn");
        for (let i = 0; i < tablinks.length; i++) {
            let onClickAttr = tablinks[i].getAttribute("onclick");
            if (onClickAttr && onClickAttr.includes('grafik-tab')) {
                tablinks[i].className += " active";
            } else {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
        }
    } else {
        bukaTab(null, 'dashboard');
        let tablinks = document.getElementsByClassName("tab-btn");
        if (tablinks.length > 0) tablinks[0].className += " active";
    }
});
 
</script>
<?php if (isset($_SESSION['notif_pesan'])): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        showNotifikasi('<?php echo addslashes($_SESSION['notif_pesan']); ?>', '<?php echo $_SESSION['notif_tipe']; ?>');
    });
</script>
<?php 
    // Hapus session setelah ditampilkan agar tidak muncul terus saat di-refresh
    unset($_SESSION['notif_pesan']);
    unset($_SESSION['notif_tipe']);
endif; 
?>
</body>
</html>
