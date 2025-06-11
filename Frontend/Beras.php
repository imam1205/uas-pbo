<?php
// Set zona waktu ke WIB (Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');

// URL endpoint API Flask
$api_url = 'http://localhost:5000/beras';

// Inisialisasi variabel
$data = []; // Inisialisasi sebagai array kosong
$error = null;

// Inisialisasi cURL
$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

// Eksekusi permintaan cURL
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Dekode respons JSON
if ($response !== false && $http_code === 200) {
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $error = 'Gagal mendekode data JSON: ' . json_last_error_msg();
    }
} else {
    $error = 'Gagal mengambil data beras: ' . ($http_code ? "HTTP $http_code" : 'Koneksi gagal');
}

// Fungsi untuk menambah data beras
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_harga'])) {
    $data_to_send = ['harga' => floatval($_POST['add_harga'])];
    $api_url_post = 'http://localhost:5000/beras';
    $ch = curl_init($api_url_post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data_to_send));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 201) {
        $success = "Data beras berhasil ditambahkan.";
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($response !== false && $http_code === 200) {
            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $error = 'Gagal mendekode data JSON setelah penambahan: ' . json_last_error_msg();
            }
        } else {
            $error = 'Gagal mengambil data beras setelah penambahan: ' . ($http_code ? "HTTP $http_code" : 'Koneksi gagal');
        }
    } else {
        $error = "Gagal menambahkan data beras: HTTP $http_code";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Beras</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit-id.js" crossorigin="anonymous"></script> <!-- Ganti dengan ID Kit FontAwesome Anda -->
    <style>
        .header-icon {
            @apply text-2xl mr-2 text-yellow-500;
        }
        .table-custom {
            @apply min-w-full text-sm text-left;
        }
        .table-custom th {
            @apply px-4 py-2 font-semibold text-gray-700 bg-gray-200;
        }
        .table-custom td {
            @apply px-4 py-2 text-gray-800;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center font-sans">
    <div class="bg-white shadow-lg rounded-lg w-full max-w-4xl">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <div class="flex items-center">
                <i class="fas fa-sack header-icon"></i>
                <h1 class="text-2xl font-bold text-gray-800">Data Harga Beras</h1>
            </div>
            <div class="space-x-2">
                <a href="dashboard.php" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">Kembali</a>
                <button onclick="openAddModal()" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Tambah Data</button>
            </div>
        </div>

        <div class="p-6">
            <?php if ($error): ?>
                <div class="bg-red-100 text-red-700 border border-red-300 rounded p-4 mb-4">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php elseif (isset($success)): ?>
                <div class="bg-green-100 text-green-700 border border-green-300 rounded p-4 mb-4">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <div class="overflow-x-auto">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Harga</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        <?php if (!empty($data) && is_array($data)): ?>
                            <?php foreach ($data as $record): ?>
                                <tr class="border-b hover:bg-gray-100">
                                    <td><?= htmlspecialchars($record['id']) ?></td>
                                    <td><?= number_format($record['harga'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" class="px-4 py-4 text-center text-gray-500">Tidak ada data ditemukan</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 shadow-xl w-full max-w-md">
            <h2 class="text-xl font-bold mb-4 text-gray-700 text-center">Tambah Data Beras</h2>
            <form id="addForm" onsubmit="submitAddForm(event)" class="space-y-4">
                <div>
                    <label for="add_harga" class="block text-sm font-medium text-gray-600">Harga</label>
                    <input type="number" id="add_harga" name="add_harga" class="mt-1 w-full p-2 border rounded" placeholder="Masukkan harga" step="0.01" required>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Simpan</button>
                    <button type="button" onclick="closeAddModal()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('add_harga').value = '';
            document.getElementById('addModal').classList.remove('hidden');
        }

        function closeAddModal() {
            document.getElementById('addModal').classList.add('hidden');
        }

        function submitAddForm(event) {
            event.preventDefault();
            const harga = document.getElementById('add_harga').value;
            if (!harga || isNaN(harga)) {
                alert("Harap masukkan harga yang valid!");
                return;
            }
            fetch('http://localhost:5000/beras', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ harga: parseFloat(harga) })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Gagal menyimpan data');
                }
                return response.json();
            })
            .then(result => {
                if (result.message === "Beras created successfully") {
                    alert("Data beras berhasil ditambahkan!");
                    location.reload();
                } else {
                    throw new Error(result.message || 'Error tidak diketahui');
                }
            })
            .catch(error => {
                alert("Terjadi kesalahan: " + error.message);
            });
        }
    </script>
</body>
</html>