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

// Cegah caching halaman login
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

include 'koneksi.php';

// A. LOGIKA LIMIT LOGIN (MAX 3 KALI SALAH -> COOLDOWN 30 DETIK)
if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = MD5($_POST['password']);

    // Cek apakah akun sedang dalam masa pembatasan waktu (cooldown)
    if (isset($_SESSION['login_ban_time'])) {
        $waktu_tersisa = $_SESSION['login_ban_time'] - time();
        if ($waktu_tersisa > 0) {
            echo "<script>alert('Akun terkunci! Mohon tunggu " . $waktu_tersisa . " detik lagi untuk mencoba.'); window.location='login.php';</script>";
            exit();
        } else {
            // Waktu ban sudah habis, reset counter
            unset($_SESSION['login_attempts']);
            unset($_SESSION['login_ban_time']);
        }
    }

    // Jalankan query cek user
    $login_query = mysqli_query($conn, "SELECT * FROM users WHERE username='$username' AND password='$password'");
    $cek = mysqli_num_rows($login_query);

    if ($cek > 0) {
        $data = mysqli_fetch_assoc($login_query);
        $_SESSION['username'] = $data['username'];
        $_SESSION['nama'] = $data['nama_lengkap'];
        $_SESSION['role'] = $data['role'];
        
        // Reset counter jika sukses login
        unset($_SESSION['login_attempts']);
        unset($_SESSION['login_ban_time']);
        
        header("Location: index.php");
        exit();
    } else {
        // Jika salah, tambahkan angka percobaan
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = 1;
        } else {
            $_SESSION['login_attempts']++;
        }

        // Jika sudah mencapai 3 kali salah
        if ($_SESSION['login_attempts'] >= 3) {
            $_SESSION['login_ban_time'] = time() + 30; // Ban selama 30 detik
            echo "<script>alert('Gagal login 3 kali! Akses dibekukan selama 30 detik.'); window.location='login.php';</script>";
            exit();
        } else {
            $sisa_mencoba = 3 - $_SESSION['login_attempts'];
            echo "<script>alert('Username atau Password Salah! Sisa percobaan: " . $sisa_mencoba . "'); window.location='login.php';</script>";
            exit();
        }
    }
}

// Jika sudah login, langsung ke dashboard
if (isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Login — RotiWhy</title>
    <style>
        :root {
            --bg-body: #faf7f2;
            --bg-card: #fff9f5;
            --text-color: #5a4a45;
            --text-light: #8b7a73;
            --border-color: #f0e6dc;
            --primary-orange: #e8893a;
            --primary-hover: #d97b2e;
            --secondary-orange: #f5a962;
            --accent-cream: #fef3e8;
        }
        [data-theme="dark"] {
            --bg-body: #2a2420;
            --bg-card: #3a3530;
            --text-color: #f0e8e3;
            --text-light: #d4c7bf;
            --border-color: #5a5048;
            --primary-orange: #d97b2e;
            --primary-hover: #e8893a;
            --secondary-orange: #b85f1f;
            --accent-cream: #4a4540;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; transition: background 0.3s, color 0.3s; }

        body {
            min-height: 100vh;
            background-color: var(--bg-body);
            background-image:
                radial-gradient(circle at 15% 50%, rgba(232, 137, 58, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 85% 80%, rgba(232, 137, 58, 0.05) 0%, transparent 50%);
            background-attachment: fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-wrapper {
            width: 100%;
            max-width: 380px;
            text-align: center;
            margin-bottom: 32px;
        }
        .brand-icon {
            font-size: 56px;
            line-height: 1;
            margin-bottom: 10px;
            display: block;
            filter: drop-shadow(0 4px 8px rgba(232,137,58,0.3));
        }
        .brand h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-orange);
            letter-spacing: -0.3px;
        }
        .brand p {
            font-size: 13px;
            color: var(--text-light);
            margin-top: 4px;
        }

        /* Card */
        .login-card {
            background: var(--bg-card);
            border: 1.5px solid var(--border-color);
            border-radius: 16px;
            padding: 36px 32px;
            box-shadow: 0 8px 32px rgba(232, 137, 58, 0.08), 0 2px 8px rgba(0,0,0,0.04);
        }
        .login-card h2 {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 24px;
            text-align: center;
        }

        /* Form */
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 7px;
        }
        .input-wrap {
            position: relative;
        }
        .input-icon {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
            pointer-events: none;
        }
        .form-group input {
            width: 100%;
            padding: 12px 14px 12px 40px;
            border: 1.5px solid var(--border-color);
            border-radius: 9px;
            background: var(--accent-cream);
            color: var(--text-color);
            font-size: 14px;
            transition: all 0.25s;
            -webkit-autofill: none;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--primary-orange);
            box-shadow: 0 0 0 3px rgba(232, 137, 58, 0.12);
            background: var(--bg-card);
        }
        .form-group input::placeholder {
            color: var(--text-light);
        }

        /* Toggle password */
        .toggle-pw {
            position: absolute;
            right: 13px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            color: var(--text-light);
            padding: 0;
            line-height: 1;
        }

        /* Attempts warning */
        .attempts-info {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            color: #856404;
            margin-bottom: 16px;
            display: <?php echo (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] > 0) ? 'block' : 'none'; ?>;
        }
        [data-theme="dark"] .attempts-info {
            background: #3a3010;
            border-color: #c8a020;
            color: #f0c060;
        }

        /* Submit button */
        .btn-login {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, var(--primary-orange) 0%, var(--secondary-orange) 100%);
            color: white;
            border: none;
            border-radius: 9px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            letter-spacing: 0.3px;
            transition: all 0.3s;
            box-shadow: 0 4px 14px rgba(232, 137, 58, 0.25);
            margin-top: 4px;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(232, 137, 58, 0.35);
        }
        .btn-login:active {
            transform: translateY(0);
        }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 24px;
            font-size: 12px;
            color: var(--text-light);
        }

        /* Theme toggle */
        .theme-btn {
            position: fixed;
            top: 16px;
            right: 16px;
            background: var(--bg-card);
            border: 1.5px solid var(--border-color);
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-color);
            cursor: pointer;
            transition: all 0.3s;
        }
        .theme-btn:hover {
            border-color: var(--primary-orange);
            background: var(--accent-cream);
        }
    </style>
</head>
<body>

<button class="theme-btn" onclick="toggleTema()">🌙 Tema</button>

<div class="login-wrapper">
    <div class="brand">
        <span class="brand-icon">🥐</span>
        <h1>RotiWhy</h1>
        <p>Sistem Manajemen Toko Roti & Kue</p>
    </div>
<br>
    <div class="login-card">
        <h2> Login </h2>

        <?php if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] > 0): ?>
        <div class="attempts-info">
            ⚠️ Sisa percobaan login: <strong><?php echo 3 - $_SESSION['login_attempts']; ?> kali</strong>
        </div>
        <?php endif; ?>

        <form action="login.php" method="POST" autocomplete="off">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-wrap">
                    <span class="input-icon">👤</span>
                    <input 
                        type="text" 
                        name="username" 
                        id="username" 
                        placeholder="Masukkan username..." 
                        required 
                        autofocus
                        autocomplete="off"
                    >
                </div>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <span class="input-icon">🔒</span>
                    <input 
                        type="password" 
                        name="password" 
                        id="password" 
                        placeholder="Masukkan password..."
                        autocomplete="new-password"
                    >
                    <button type="button" class="toggle-pw" onclick="togglePassword()" title="Tampilkan/sembunyikan password">👁️</button>
                </div>
            </div>
            <button type="submit" name="login" class="btn-login">Masuk &rarr;</button>
        </form>
    </div>

    <div class="login-footer">
        &copy; <?php echo date('Y'); ?> Roti Why &mdash; By Didi Sloth Stanca
    </div>
</div>

<script>
    // Cegah browser back button setelah logout
    window.history.forward();
    function noBack() {
        window.history.forward();
    }
    window.onload = noBack;
    window.onpageshow = function(evt) {
        if (evt.persisted) {
            noBack();
        }
    };
    window.onunload = function() {
        null;
    };

    // Theme engine - hanya gunakan session, jangan localStorage saat login
    function toggleTema() {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        document.documentElement.setAttribute('data-theme', isDark ? 'light' : 'dark');
        // Simpan ke sessionStorage (hilang saat browser ditutup)
        sessionStorage.setItem('tema', isDark ? 'light' : 'dark');
    }

    // Load tema dari sessionStorage saja di login page
    if (sessionStorage.getItem('tema') === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
    }

    // Toggle show/hide password
    function togglePassword() {
        const pw = document.getElementById('password');
        pw.type = pw.type === 'password' ? 'text' : 'password';
    }

    // Disable form autocomplete
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        if (form) {
            form.setAttribute('autocomplete', 'off');
        }
    });
</script>
</body>
</html>
