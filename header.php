<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$role_user = $_SESSION['role'];
$nama_user = $_SESSION['nama'];

// 1. QUERY MONITORING STOK DAN SISA ROTI HARI INI
$query_stok_toko = mysqli_query($conn, "
    SELECT r.id_roti, r.nama_roti, r.stok_awal, 
           IFNULL(SUM(t.qty), 0) as total_terjual,
           (r.stok_awal - IFNULL(SUM(t.qty), 0)) as sisa_stok
    FROM roti r 
    LEFT JOIN transaksi t ON r.id_roti = t.id_roti AND t.tanggal = CURDATE()
    GROUP BY r.id_roti
");

// 2. QUERY DAFTAR TRANSAKSI TERAKHIR (UNTUK FITUR HAPUS TRANSAKSI)
$query_riwayat = mysqli_query($conn, "
    SELECT t.id_transaksi, t.nama_pembeli, r.nama_roti, t.qty, t.total_harga, t.tanggal 
    FROM transaksi t JOIN roti r ON t.id_roti = r.id_roti 
    ORDER BY t.id_transaksi DESC LIMIT 10
");

// 3. QUERY MASTER ROTI
$query_roti = mysqli_query($conn, "SELECT * FROM roti");

// 4. QUERY USER (MANAJEMEN KARYAWAN)
$query_users = mysqli_query($conn, "SELECT id_user, username, nama_lengkap, role FROM users");

// 5. LOGIKA PREDIKSI KEBUTUHAN BESOK (BERDASARKAN LAJU PERUBAHAN)
$tgl_hari_ini = date('Y-m-d');
$tgl_kemarin = date('Y-m-d', strtotime("-1 days"));

$q_hari_ini = mysqli_query($conn, "SELECT IFNULL(SUM(qty), 0) as total FROM transaksi WHERE tanggal='$tgl_hari_ini'");
$q_kemarin  = mysqli_query($conn, "SELECT IFNULL(SUM(qty), 0) as total FROM transaksi WHERE tanggal='$tgl_kemarin'");

$penjualan_hari_ini = mysqli_fetch_assoc($q_hari_ini)['total'];
$penjualan_kemarin  = mysqli_fetch_assoc($q_kemarin)['total'];

$laju_perubahan = $penjualan_hari_ini - $penjualan_kemarin;
$prediksi_stok = $penjualan_hari_ini + $laju_perubahan;
if ($prediksi_stok < 10) { $prediksi_stok = 30; }

// ======================== ENGINE DATA MULTI-FILTER GRAFIK (BACKEND DATA) ========================
// A. Grafik Harian (7 Hari Terakhir)
$q_hari = mysqli_query($conn, "SELECT tanggal as label, IFNULL(SUM(qty), 0) as total FROM dummy_transaksi WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY tanggal ORDER BY tanggal ASC");
$g_hari_lbl = []; $g_hari_val = []; while($r = mysqli_fetch_assoc($q_hari)){ $g_hari_lbl[] = date('d M', strtotime($r['label'])); $g_hari_val[] = (int)$r['total']; }

// B. Grafik Mingguan (4 Minggu Terakhir)
$q_minggu = mysqli_query($conn, "SELECT CONCAT('Minggu ', WEEK(tanggal)) as label, IFNULL(SUM(qty), 0) as total FROM dummy_transaksi WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 4 WEEK) GROUP BY WEEK(tanggal) ORDER BY tanggal ASC");
$g_mgu_lbl = []; $g_mgu_val = []; while($r = mysqli_fetch_assoc($q_minggu)){ $g_mgu_lbl[] = $r['label']; $g_mgu_val[] = (int)$r['total']; }

// C. Grafik Bulanan (6 Bulan Terakhir)
$q_bulan = mysqli_query($conn, "SELECT DATE_FORMAT(tanggal, '%M %Y') as label, IFNULL(SUM(qty), 0) as total FROM dummy_transaksi WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY MONTH(tanggal) ORDER BY tanggal ASC");
$g_bln_lbl = []; $g_bln_val = []; while($r = mysqli_fetch_assoc($q_bulan)){ $g_bln_lbl[] = $r['label']; $g_bln_val[] = (int)$r['total']; }

// D. Grafik Tahunan
$q_tahun = mysqli_query($conn, "SELECT YEAR(tanggal) as label, IFNULL(SUM(qty), 0) as total FROM dummy_transaksi GROUP BY YEAR(tanggal) ORDER BY YEAR(tanggal) ASC");
$g_thn_lbl = []; $g_thn_val = []; while($r = mysqli_fetch_assoc($q_tahun)){ $g_thn_lbl[] = $r['label']; $g_thn_val[] = (int)$r['total']; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bakery Core System</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --bg-body: #fbfaf7;
            --bg-card: #ffffff;
            --text-color: #4a3e3d;
            --border-color: #ebdcd0;
            --primary-orange: #d97724;
            --primary-hover: #b85f19;
            --table-striped: #fcfaf2;
        }
        * { box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-color); margin: 0; padding: 20px; }
        .header-bar { display: flex; justify-content: space-between; align-items: center; background: var(--primary-orange); color: white; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .header-bar a { color: #fbfaf7; text-decoration: none; font-weight: bold; border: 1px solid white; padding: 4px 10px; border-radius: 4px; }
        .tab-menu { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
        .tab-btn { background: #fff; border: 1px solid var(--border-color); padding: 10px 18px; font-size: 14px; cursor: pointer; border-radius: 6px; font-weight: bold; color: var(--text-color); }
        .tab-btn.active { background: var(--primary-orange); color: white; border-color: var(--primary-orange); }
        .tab-content { display: none; background: var(--bg-card); padding: 20px; border-radius: 8px; border: 1px solid var(--border-color); box-shadow: 0 4px 12px rgba(217, 119, 36, 0.04); margin-bottom: 20px; }
        .tab-content.active { display: block; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 14px; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: #fff; color: var(--text-color); font-size: 14px; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: var(--primary-orange); }
        .qty-container { display: flex; align-items: center; gap: 10px; }
        .btn-qty { background: var(--primary-orange); color: white; border: none; padding: 8px 14px; font-size: 16px; cursor: pointer; border-radius: 4px; }
        .btn-submit { background: var(--primary-orange); color: white; border: none; width: 100%; padding: 12px; font-size: 15px; cursor: pointer; border-radius: 6px; font-weight: bold; }
        .btn-submit:hover { background: var(--primary-hover); }
        .btn-danger { background: #cc4125; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; background: #fff; }
        table th, table td { border: 1px solid var(--border-color); padding: 12px; text-align: left; }
        table th { background: #f7eedf; color: var(--primary-orange); font-weight: bold; }
        table tr:nth-child(even) { background-color: var(--table-striped); }
        .badge { background: var(--primary-orange); color: white; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .predict-box { background: #fdf5ea; color: #8a531d; padding: 15px; border-radius: 6px; border-left: 5px solid var(--primary-orange); margin-top: 20px; }
        .sub-tab-menu { display: flex; gap: 5px; margin-bottom: 15px; background: #f3ece6; padding: 5px; border-radius: 6px; width: fit-content; }
        .sub-btn { background: transparent; border: none; padding: 6px 15px; cursor: pointer; font-weight: bold; font-size: 13px; border-radius: 4px; color: var(--text-color); }
        .sub-btn.active { background: white; color: var(--primary-orange); box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

<div class="header-bar">
    <div>User: <strong><?php echo htmlspecialchars($nama_user); ?></strong> [<?php echo strtoupper($role_user); ?>]</div>
    <div><a href="proses.php?logout=true">🚪 Keluar</a></div>
</div>
