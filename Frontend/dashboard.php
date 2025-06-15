<?php
date_default_timezone_set('Asia/Jakarta');

// Ambil data pembayaran dari API
$pembayaran_data = [];
$pembayaran_error = null;
$api_url_pembayaran = 'http://localhost:5000/pembayaran';
$ch = curl_init($api_url_pembayaran);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    $pembayaran_data = json_decode($response, true);
    // Urutkan data berdasarkan tanggal_bayar secara descending
    usort($pembayaran_data, function($a, $b) {
        return strtotime($b['tanggal_bayar']) - strtotime($a['tanggal_bayar']);
    });
} else {
    $pembayaran_error = 'Gagal mengambil data: HTTP ' . $http_code;
}

$total_pembayaran = 0;
$jumlah_transaksi = count($pembayaran_data);
$tanggal_terbaru = '-';

if ($jumlah_transaksi > 0) {
    foreach ($pembayaran_data as $item) {
        $total_pembayaran += floatval($item['total_bayar']);
    }
    $tanggal_terbaru = $pembayaran_data[0]['tanggal_bayar']; // Ambil tanggal terbaru setelah diurutkan
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Pembayaran Zakat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit-id.js" crossorigin="anonymous"></script> <!-- Ganti -->
</head>
<body class="bg-gray-50 text-gray-700 font-sans">
    <div class="max-w-6xl mx-auto p-6">
        <h1 class="text-3xl font-bold text-center mb-8">Dashboard Pembayaran Zakat</h1>

        <?php if ($pembayaran_error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?= htmlspecialchars($pembayaran_error); ?>
            </div>
        <?php endif; ?>

        <!-- Ringkasan -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            <div class="flex items-center bg-white shadow p-4 rounded-lg">
                <div class="bg-yellow-100 p-3 rounded-full mr-4">
                    <i class="fas fa-clipboard-list text-yellow-500 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Total Pembayaran</p>
                    <p class="text-xl font-semibold text-gray-800">Rp <?= number_format($total_pembayaran, 2); ?></p>
                </div>
            </div>
            <div class="flex items-center bg-white shadow p-4 rounded-lg">
                <div class="bg-green-100 p-3 rounded-full mr-4">
                    <i class="fas fa-check-circle text-green-500 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Jumlah Transaksi</p>
                    <p class="text-xl font-semibold text-gray-800"><?= $jumlah_transaksi; ?></p>
                </div>
            </div>
            <div class="flex items-center bg-white shadow p-4 rounded-lg">
                <div class="bg-blue-100 p-3 rounded-full mr-4">
                    <i class="fas fa-calendar-alt text-blue-500 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Tanggal Terbaru</p>
                    <p class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($tanggal_terbaru); ?></p>
                </div>
            </div>
        </div>

        <!-- Tabel Data -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Daftar Pembayaran Terbaru</h2>
            <?php if ($jumlah_transaksi === 0): ?>
                <p class="text-center text-gray-500">Belum ada data pembayaran.</p>
            <?php else: ?>
                <table class="w-full text-left table-auto">
                    <thead>
                        <tr class="bg-gray-100 text-sm">
                            <th class="px-4 py-2">Nama</th>
                            <th class="px-4 py-2">Jenis Zakat</th>
                            <th class="px-4 py-2 text-right">Total Bayar (Rp)</th>
                            <th class="px-4 py-2">Tanggal Bayar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($pembayaran_data, 0, 5) as $data): ?>
                            <tr class="hover:bg-gray-50 border-b">
                                <td class="px-4 py-2"><?= htmlspecialchars($data['nama']); ?></td>
                                <td class="px-4 py-2"><?= htmlspecialchars($data['jenis_zakat']); ?></td>
                                <td class="px-4 py-2 text-right"><?= number_format($data['total_bayar'], 2); ?></td>
                                <td class="px-4 py-2"><?= htmlspecialchars($data['tanggal_bayar']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Navigasi -->
        <div class="text-center mt-8 space-x-3">
            <a href="pembayaran.php" class="inline-block bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-full text-sm">
                <i class="fas fa-plus mr-1"></i> Tambah Pembayaran
            </a>
            <a href="index.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-full text-sm">
                <i class="fas fa-history mr-1"></i> History Pembayaran
            </a>
            <a href="beras.php" class="inline-block bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-full text-sm">
                <i class="fas fa-seedling mr-1"></i> Data Beras
            </a>
        </div>
    </div>
</body>
</html>