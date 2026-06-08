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

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

include 'koneksi.php';

// --- BUAT ANTRIAN ---
if (isset($_POST['buat_antrian'])) {
    $jam_dibuka = date('Y-m-d H:i:s');
    
    $q_nomor = mysqli_query($conn, "SELECT MAX(nomor_antrian) as nomor_max FROM antrian WHERE DATE(jam_dibuka) = CURDATE()");
    $nomor_data = mysqli_fetch_assoc($q_nomor);
    $nomor_baru = ($nomor_data['nomor_max'] ?? 0) + 1;
    
    $insert_antrian = mysqli_query($conn, "INSERT INTO antrian (nomor_antrian, status, jam_dibuka) VALUES ('$nomor_baru', 'open', '$jam_dibuka')");
    
    if ($insert_antrian) {
        $_SESSION['notif_pesan'] = "Antrian #" . str_pad($nomor_baru, 3, '0', STR_PAD_LEFT) . " berhasil dibuat!";
        $_SESSION['notif_tipe'] = "sukses";
    } else {
        $_SESSION['notif_pesan'] = "Gagal membuat antrian.";
        $_SESSION['notif_tipe'] = "error";
    }
    header("Location: index.php?tab=antrian");
    exit();
}

// --- TUTUP ANTRIAN ---
if (isset($_GET['tutup_antrian'])) {
    $id_antrian = intval($_GET['tutup_antrian']);
    mysqli_query($conn, "UPDATE antrian SET status = 'selesai' WHERE id_antrian = '$id_antrian'");
    header("Location: index.php?tab=antrian");
    exit();
}

// --- BATAL ANTRIAN ---
if (isset($_GET['batal_antrian'])) {
    $id_antrian = intval($_GET['batal_antrian']);
    $q_cek = mysqli_query($conn, "SELECT nama_pembeli, nama_roti FROM antrian WHERE id_antrian = $id_antrian");
    $data_antrian = mysqli_fetch_assoc($q_cek);
    
    if ($data_antrian && $data_antrian['nama_roti'] && $data_antrian['nama_roti'] !== '-') {
        $nama_pembeli = mysqli_real_escape_string($conn, $data_antrian['nama_pembeli']);
        $nama_roti = mysqli_real_escape_string($conn, $data_antrian['nama_roti']);
        
        $q_roti = mysqli_query($conn, "SELECT id_roti FROM roti WHERE nama_roti = '$nama_roti'");
        $roti_data = mysqli_fetch_assoc($q_roti);
        
        if ($roti_data) {
            $id_roti = $roti_data['id_roti'];
            $q_trans = mysqli_query($conn, "SELECT id_transaksi FROM transaksi WHERE nama_pembeli = '$nama_pembeli' AND id_roti = $id_roti AND tanggal = CURDATE() ORDER BY id_transaksi DESC LIMIT 1");
            $trans_data = mysqli_fetch_assoc($q_trans);
            
            if ($trans_data) {
                mysqli_query($conn, "DELETE FROM transaksi WHERE id_transaksi = " . $trans_data['id_transaksi']);
            }
        }
    }
    
    mysqli_query($conn, "UPDATE antrian SET status = 'dibatalkan' WHERE id_antrian = $id_antrian");
    $_SESSION['notif_pesan'] = "Antrian dibatalkan dan transaksi dihapus!";
    $_SESSION['notif_tipe'] = "sukses";
    header("Location: index.php?tab=antrian");
    exit();
}

// --- SUBMIT TRANSAKSI ---
if (isset($_POST['submit_transaksi'])) {
    $nama_pembeli = mysqli_real_escape_string($conn, $_POST['nama_pembeli']);
    $id_roti = intval($_POST['id_roti']);
    $qty = intval($_POST['qty']);
    $tanggal = $_POST['tanggal'];

    $query_harga = mysqli_query($conn, "SELECT harga, nama_roti, IF(tanggal_update = CURDATE(), stok_awal, 0) as stok_awal FROM roti WHERE id_roti = '$id_roti'");
    $data_harga = mysqli_fetch_assoc($query_harga);
    
    if (!$data_harga) {
        $_SESSION['notif_pesan'] = "Roti tidak ditemukan!";
        $_SESSION['notif_tipe'] = "error";
        header("Location: index.php?tab=input-kasir");
        exit();
    }
    
    $q_terjual = mysqli_query($conn, "SELECT IFNULL(SUM(qty), 0) as total_terjual FROM transaksi WHERE id_roti = $id_roti AND tanggal = CURDATE()");
    $terjual_data = mysqli_fetch_assoc($q_terjual);
    $sisa_stok = $data_harga['stok_awal'] - $terjual_data['total_terjual'];
    
    if ($sisa_stok <= 0) {
        $_SESSION['notif_pesan'] = "Tidak bisa order, stock habis!";
        $_SESSION['notif_tipe'] = "error";
        header("Location: index.php?tab=input-kasir");
        exit();
    }
    
    if ($sisa_stok < $qty) {
        $_SESSION['notif_pesan'] = "Tidak bisa order stock kurang! Tersedia: " . $sisa_stok . " pcs";
        $_SESSION['notif_tipe'] = "error";
        header("Location: index.php?tab=input-kasir");
        exit();
    }

    $total_harga = $data_harga['harga'] * $qty;
    $nama_roti = mysqli_real_escape_string($conn, $data_harga['nama_roti']);

    $insert = mysqli_query($conn, "INSERT INTO transaksi (nama_pembeli, id_roti, qty, total_harga, tanggal) VALUES ('$nama_pembeli', '$id_roti', '$qty', '$total_harga', '$tanggal')");
    if ($insert) {
        $jam_dibuka = date('Y-m-d H:i:s');
        $q_nomor = mysqli_query($conn, "SELECT MAX(nomor_antrian) as nomor_max FROM antrian WHERE DATE(jam_dibuka) = CURDATE()");
        $nomor_data = mysqli_fetch_assoc($q_nomor);
        $nomor_baru = ($nomor_data['nomor_max'] ?? 0) + 1;

        mysqli_query($conn, "INSERT INTO antrian (nomor_antrian, status, jam_dibuka, nama_pembeli, nama_roti) VALUES ('$nomor_baru', 'open', '$jam_dibuka', '$nama_pembeli', '$nama_roti')");

        $no_antrian_fmt = str_pad($nomor_baru, 3, '0', STR_PAD_LEFT);
        $_SESSION['notif_pesan'] = "Transaksi Berhasil! Antrian #$no_antrian_fmt atas nama $nama_pembeli";
        $_SESSION['notif_tipe'] = "sukses";
    } else {
        $_SESSION['notif_pesan'] = "Gagal memproses transaksi!";
        $_SESSION['notif_tipe'] = "error";
    }
    header("Location: index.php?tab=input-kasir");
    exit();
}

// --- KELOLA HARGA ---
if (isset($_POST['update_harga'])) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { 
        $_SESSION['notif_pesan'] = "Akses ditolak!";
        $_SESSION['notif_tipe'] = "error";
        header("Location: index.php?tab=kelola-harga");
        exit();
    }
    $id_roti = $_POST['id_roti'];
    $harga_baru = $_POST['harga_baru'];

    $update = mysqli_query($conn, "UPDATE roti SET harga = '$harga_baru' WHERE id_roti = '$id_roti'");
    if ($update) {
        $_SESSION['notif_pesan'] = "Harga Roti Berhasil Diperbarui!";
        $_SESSION['notif_tipe'] = "sukses";
    }
    header("Location: index.php?tab=kelola-harga");
    exit();
}

// --- TAMBAH USER ---
if (isset($_POST['tambah_user'])) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { 
        $_SESSION['notif_pesan'] = "Akses ditolak!";
        $_SESSION['notif_tipe'] = "error";
        header("Location: index.php?tab=kelola-user");
        exit();
    }
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = MD5($_POST['password']);
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $role = $_POST['role'];

    $insert_user = mysqli_query($conn, "INSERT INTO users (username, password, nama_lengkap, role) VALUES ('$username', '$password', '$nama_lengkap', '$role')");
    if ($insert_user) {
        $_SESSION['notif_pesan'] = "User baru berhasil didaftarkan!";
        $_SESSION['notif_tipe'] = "sukses";
    } else {
        $_SESSION['notif_pesan'] = "Gagal! Username mungkin sudah terpakai.";
        $_SESSION['notif_tipe'] = "error";
    }
    header("Location: index.php?tab=kelola-user");
    exit();
}

// --- HAPUS USER ---
if (isset($_GET['hapus_user'])) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { 
        $_SESSION['notif_pesan'] = "Akses ditolak! Anda bukan admin.";
        $_SESSION['notif_tipe'] = "error";
        header("Location: index.php?tab=kelola-user");
        exit();
    }
    
    $id_user = intval($_GET['hapus_user']);
    $query_cek = mysqli_query($conn, "SELECT id_user, username FROM users WHERE id_user = $id_user");
    $u = mysqli_fetch_assoc($query_cek);
    
    if (!$u) {
        $_SESSION['notif_pesan'] = "Error! User tidak ditemukan.";
        $_SESSION['notif_tipe'] = "error";
        header("Location: index.php?tab=kelola-user");
        exit();
    }
    
    if ($u['username'] == $_SESSION['username']) {
        $_SESSION['notif_pesan'] = "❌ Anda tidak bisa menghapus akun Anda sendiri!";
        $_SESSION['notif_tipe'] = "error";
        header("Location: index.php?tab=kelola-user");
        exit();
    }

    $hapus = mysqli_query($conn, "DELETE FROM users WHERE id_user = $id_user");
    if ($hapus) {
        $_SESSION['notif_pesan'] = "✅ User berhasil dihapus!";
        $_SESSION['notif_tipe'] = "sukses";
    } else {
        $_SESSION['notif_pesan'] = "❌ Gagal menghapus user.";
        $_SESSION['notif_tipe'] = "error";
    }
    header("Location: index.php?tab=kelola-user");
    exit();
}

// --- HAPUS TRANSAKSI ---
if (isset($_GET['hapus_transaksi'])) {
    $id_transaksi = intval($_GET['hapus_transaksi']);
    mysqli_query($conn, "DELETE FROM transaksi WHERE id_transaksi = $id_transaksi");
    header("Location: index.php?tab=riwayat-transaksi");
    exit();
}

// --- LOGOUT ---
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// --- UPDATE STOK ---// --- UPDATE STOK ---
if (isset($_POST['update_stok'])) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { 
        $_SESSION['notif_pesan'] = "Akses ditolak!";
        $_SESSION['notif_tipe'] = "error";
        header("Location: index.php?tab=kelola-harga");
        exit();
    }
    $id_roti = intval($_POST['id_roti']);
    $tambah_stok = intval($_POST['stok_baru']);
    $tanggal_hari_ini = date('Y-m-d');

    // Cek data roti saat ini
    $cek_roti = mysqli_query($conn, "SELECT stok_awal, tanggal_update FROM roti WHERE id_roti = '$id_roti'");
    $data_roti = mysqli_fetch_assoc($cek_roti);

    if ($data_roti) {
        if ($data_roti['tanggal_update'] == $tanggal_hari_ini) {
            // Jika HARI SAMA: Tambahkan ke stok awal pagi yang sudah ada
            $stok_fix = $data_roti['stok_awal'] + $tambah_stok;
        } else {
            // Jika GANTI HARI: Reset ke 0, lalu jadikan inputan ini sebagai stok awal baru
            $stok_fix = $tambah_stok;
        }

        $update_stok = mysqli_query($conn, "UPDATE roti SET stok_awal = '$stok_fix', tanggal_update = '$tanggal_hari_ini' WHERE id_roti = '$id_roti'");
        
        if ($update_stok) {
            $_SESSION['notif_pesan'] = "Stok Produksi Harian Berhasil Diperbarui!";
            $_SESSION['notif_tipe'] = "sukses";
        } else {
            $_SESSION['notif_pesan'] = "Gagal memperbarui stok!";
            $_SESSION['notif_tipe'] = "error";
        }
    }
    header("Location: index.php?tab=kelola-harga");
    exit();
}

// --- TAMBAH ROTI BARU ---
if (isset($_POST['tambah_roti_baru'])) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { 
        $_SESSION['notif_pesan'] = "Akses ditolak!";
        $_SESSION['notif_tipe'] = "error";
        header("Location: index.php?tab=kelola-harga");
        exit();
    }
    
    $nama_roti = mysqli_real_escape_string($conn, $_POST['nama_roti_baru']);
    $harga_roti = $_POST['harga_roti_baru'];
    $stok_awal = $_POST['stok_awal_baru'];

    $insert_roti = mysqli_query($conn, "INSERT INTO roti (nama_roti, harga, stok_awal, tanggal_update) VALUES ('$nama_roti', '$harga_roti', '$stok_awal', CURDATE())");
    
    if ($insert_roti) {
        $_SESSION['notif_pesan'] = "Varian Roti Baru Berhasil Ditambahkan!";
        $_SESSION['notif_tipe'] = "sukses";
    } else {
        $_SESSION['notif_pesan'] = "Gagal menambahkan roti baru.";
        $_SESSION['notif_tipe'] = "error";
    }
    header("Location: index.php?tab=kelola-harga");
    exit();
}

// --- HAPUS ROTI ---
if (isset($_GET['hapus_roti'])) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { 
        $_SESSION['notif_pesan'] = "Akses ditolak! Anda bukan admin.";
        $_SESSION['notif_tipe'] = "error";
        header("Location: index.php?tab=kelola-harga");
        exit();
    }
    $id_roti = intval($_GET['hapus_roti']);
    
    $hapus = mysqli_query($conn, "DELETE FROM roti WHERE id_roti = $id_roti");
    if ($hapus) {
        $_SESSION['notif_pesan'] = "Varian Roti Berhasil Dihapus!";
        $_SESSION['notif_tipe'] = "sukses";
    } else {
        $_SESSION['notif_pesan'] = "Gagal menghapus! Roti ini sudah memiliki riwayat transaksi penjualan.";
        $_SESSION['notif_tipe'] = "error";
    }
    header("Location: index.php?tab=kelola-harga");
    exit();
}

// --- AKSI HAPUS LAINNYA ---
if (isset($_GET['aksi']) && $_GET['aksi'] == 'hapus') {
    $id = intval($_GET['id']);
    $query = "DELETE FROM users WHERE id_user = $id";
    $hasil = mysqli_query($conn, $query);

    if ($hasil) {
        $_SESSION['notif_pesan'] = "Data berhasil dihapus";
        $_SESSION['notif_tipe'] = "sukses";
    } else {
        $_SESSION['notif_pesan'] = "Data gagal dihapus";
        $_SESSION['notif_tipe'] = "error";
    }
    header("Location: index.php");
    exit();
}
?>
