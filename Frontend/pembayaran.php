<?php
// Set zona waktu ke WIB (Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');

// Ambil data beras dari API
$beras_data = [];
$beras_error = null;
$api_url_beras = 'http://localhost:5000/beras';
$ch_beras = curl_init($api_url_beras);
curl_setopt($ch_beras, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch_beras, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response_beras = curl_exec($ch_beras);
$http_code_beras = curl_getinfo($ch_beras, CURLINFO_HTTP_CODE);
curl_close($ch_beras);
if ($http_code_beras === 200) {
    $beras_data = json_decode($response_beras, true);
} else {
    $beras_error = 'Gagal mengambil data beras: HTTP ' . $http_code_beras;
}

// Proses pengiriman pembayaran
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nama' => $_POST['nama'],
        'jumlah_jiwa' => intval($_POST['jumlah_jiwa']),
        'jenis_zakat' => $_POST['jenis_zakat'],
        'metode_pembayaran' => $_POST['metode_pembayaran'],
        'total_bayar' => floatval($_POST['total_bayar']),
        'nominal_dibayar' => floatval($_POST['nominal_dibayar']),
        'kembalian' => floatval($_POST['kembalian']),
        'tanggal_bayar' => $_POST['tanggal_bayar']
    ];

    // Tambahkan keterangan otomatis
    if ($_POST['jenis_zakat'] === 'beras' && isset($_POST['beras_pilihan']) && !empty($_POST['beras_pilihan'])) {
        $id_beras = $_POST['beras_pilihan'];
        $harga_beras = null;
        foreach ($beras_data as $beras) {
            if ($beras['id'] == $id_beras) {
                $harga_beras = $beras['harga'];
                break;
            }
        }
        if (!$harga_beras) {
            $error = "Error: ID beras tidak valid!";
        } else {
            $total_bayar_beras = 3.5 * floatval($harga_beras) * $data['jumlah_jiwa'];
            $data['total_bayar'] = $total_bayar_beras;
            $data['keterangan'] = "Beras ID $id_beras: " . (3.5 * $data['jumlah_jiwa']) . " Liter";
        }
    } elseif ($_POST['jenis_zakat'] === 'uang' && isset($_POST['pendapatan_tahunan'])) {
        $pendapatan = floatval($_POST['pendapatan_tahunan']);
        $total_bayar_uang = $pendapatan * 0.025;
        $data['total_bayar'] = $total_bayar_uang;
        $data['keterangan'] = "Uang: 2.5% dari Rp " . number_format($pendapatan, 2);
    }

    if (!$error) {
        $api_url = 'http://localhost:5000/pembayaran';
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (curl_errno($ch)) {
            $error = 'cURL Error: ' . curl_error($ch);
        } else {
            file_put_contents('debug.log', "HTTP Code: $http_code\nResponse: $response\nData Sent: " . json_encode($data) . "\n\n", FILE_APPEND);
        }
        curl_close($ch);

        if ($http_code === 201) {
            $success = "Pembayaran berhasil disimpan.";
        } else {
            $error = "Gagal menyimpan pembayaran: HTTP $http_code\nResponse: $response";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Melakukan Pembayaran Zakat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const jenisZakat = document.getElementById('jenis_zakat');
            const berasSection = document.getElementById('beras_section');
            const pendapatanSection = document.getElementById('pendapatan_section');
            const berasPilihan = document.getElementById('beras_pilihan');
            const totalBayar = document.getElementById('total_bayar');
            const kembalian = document.getElementById('kembalian');

            if (berasPilihan.options.length > 1) {
                berasPilihan.removeAttribute('disabled');
            }

            function updateTotal() {
                const jumlahJiwa = parseFloat(document.getElementById('jumlah_jiwa').value) || 0;
                const nominalDibayar = parseFloat(document.getElementById('nominal_dibayar').value) || 0;

                if (jenisZakat.value === 'beras' && berasPilihan.value) {
                    const hargaBeras = parseFloat(berasPilihan.options[berasPilihan.selectedIndex].dataset.harga) || 0;
                    const total = jumlahJiwa * 3.5 * hargaBeras;
                    totalBayar.value = total.toFixed(2);
                } else if (jenisZakat.value === 'uang') {
                    const pendapatan = parseFloat(document.getElementById('pendapatan_tahunan').value) || 0;
                    const total = pendapatan * 0.025;
                    totalBayar.value = total.toFixed(2);
                } else {
                    totalBayar.value = '0.00';
                }
                kembalian.value = (nominalDibayar - parseFloat(totalBayar.value) || 0).toFixed(2);
            }

            jenisZakat.addEventListener('change', function() {
                berasSection.classList.toggle('hidden', this.value !== 'beras');
                pendapatanSection.classList.toggle('hidden', this.value !== 'uang');
                if (this.value === 'beras' && berasPilihan.options.length > 1) {
                    berasPilihan.removeAttribute('disabled');
                } else {
                    berasPilihan.setAttribute('disabled', 'disabled');
                }
                updateTotal();
            });

            document.getElementById('jumlah_jiwa').addEventListener('input', updateTotal);
            berasPilihan.addEventListener('change', updateTotal);
            document.getElementById('pendapatan_tahunan').addEventListener('input', updateTotal);
            document.getElementById('nominal_dibayar').addEventListener('input', updateTotal);

            function submitPayment(event) {
                event.preventDefault();
                const form = document.getElementById('paymentForm');
                form.submit(); // Alihkan ke pengiriman PHP
            }
        });
    </script>
</head>
<body class="bg-gray-100 font-sans">
    <div class="container mx-auto p-4 max-w-2xl">
        <h1 class="text-3xl font-bold text-center text-green-700 mb-6">Melakukan Pembayaran Zakat</h1>

        <?php if ($beras_error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-center">
                <?php echo htmlspecialchars($beras_error); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-center">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 text-center">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form id="paymentForm" method="post" action="">
            <div>
                <label class="block text-sm font-medium text-gray-700">Nama</label>
                <input type="text" name="nama" class="mt-1 p-2 border rounded w-full focus:ring-2 focus:ring-green-500" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Jumlah Jiwa</label>
                <input type="number" id="jumlah_jiwa" name="jumlah_jiwa" class="mt-1 p-2 border rounded w-full focus:ring-2 focus:ring-green-500" required min="1">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Jenis Zakat</label>
                <select id="jenis_zakat" name="jenis_zakat" class="mt-1 p-2 border rounded w-full focus:ring-2 focus:ring-green-500" required>
                    <option value="">Pilih Jenis Zakat</option>
                    <option value="beras">Beras</option>
                    <option value="uang">Uang</option>
                </select>
            </div>
            <div id="beras_section" class="hidden space-y-4">
                <label class="block text-sm font-medium text-gray-700">Pilih Jenis Beras</label>
                <select id="beras_pilihan" name="beras_pilihan" class="mt-1 p-2 border rounded w-full focus:ring-2 focus:ring-green-500">
                    <option value="">Pilih Beras</option>
                    <?php if (!empty($beras_data)): ?>
                        <?php foreach ($beras_data as $beras): ?>
                            <option value="<?php echo htmlspecialchars($beras['id']); ?>" data-harga="<?php echo htmlspecialchars($beras['harga']); ?>">
                                <?php echo htmlspecialchars($beras['id']) . ' - Rp ' . number_format($beras['harga'], 2); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled>Tidak ada data beras</option>
                    <?php endif; ?>
                </select>
            </div>
            <div id="pendapatan_section" class="hidden">
                <label class="block text-sm font-medium text-gray-700">Pendapatan Tahunan (Rp)</label>
                <input type="number" id="pendapatan_tahunan" name="pendapatan_tahunan" class="mt-1 p-2 border rounded w-full focus:ring-2 focus:ring-green-500" min="0">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Metode Pembayaran</label>
                <input type="text" name="metode_pembayaran" class="mt-1 p-2 border rounded w-full focus:ring-2 focus:ring-green-500" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Total Bayar (Rp)</label>
                <input type="number" step="0.01" id="total_bayar" name="total_bayar" class="mt-1 p-2 border rounded w-full bg-gray-100" readonly required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Nominal Dibayar (Rp)</label>
                <input type="number" step="0.01" id="nominal_dibayar" name="nominal_dibayar" class="mt-1 p-2 border rounded w-full focus:ring-2 focus:ring-green-500" required min="0">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Kembalian (Rp)</label>
                <input type="number" step="0.01" id="kembalian" name="kembalian" class="mt-1 p-2 border rounded w-full bg-gray-100" readonly>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Tanggal Bayar</label>
                <input type="datetime-local" name="tanggal_bayar" class="mt-1 p-2 border rounded w-full focus:ring-2 focus:ring-green-500" required>
            </div>
            <div class="text-center gap-4 mt-6">
                 <a href="dashboard.php" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">Kembali</a>
                </a>
                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition duration-200">Simpan Pembayaran</button>
            </div>
              <div class="space-x-2">
               
            </div>
        </form>
    </div>
</body>
</html>