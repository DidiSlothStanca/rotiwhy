<div class="tab-menu">
    <button class="tab-btn active" onclick="bukaTab(event, 'dashboard')">🏪 Dashboard Stok</button>
    <button class="tab-btn" onclick="bukaTab(event, 'input-kasir')">🛒 Input Kasir</button>
    <button class="tab-btn" onclick="bukaTab(event, 'riwayat-transaksi')">📜 Riwayat & Hapus</button>
    <button class="tab-btn" onclick="bukaTab(event, 'grafik-tab')">📊 Grafik Penjualan</button>
    <?php if ($role_user === 'admin') : ?>
        <button class="tab-btn" onclick="bukaTab(event, 'kelola-harga')">🛠️ Atur Menu & Stok</button>
        <button class="tab-btn" onclick="bukaTab(event, 'kelola-user')">👥 Kelola Karyawan</button>
    <?php endif; ?>
</div>
