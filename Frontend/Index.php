<?php
// Set zona waktu ke WIB (Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');

// Impor namespace PhpSpreadsheet (dipindahkan ke atas)
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// URL of the Flask API endpoint
$api_url = 'http://localhost:5000/pembayaran'; // Sesuaikan dengan URL yang digunakan

// Initialize cURL
$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

// Execute cURL request
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Decode JSON response
$data = json_decode($response, true);
$error = null;
if ($http_code !== 200 || json_last_error() !== JSON_ERROR_NONE) {
    $error = 'Gagal mengambil data pembayaran: ' . ($http_code ? "HTTP $http_code" : 'Koneksi gagal');
}

// Fungsi untuk memperbarui data pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $id = intval($_POST['edit_id']);
    $data = [
        'nama' => $_POST['nama'],
        'jumlah_jiwa' => intval($_POST['jumlah_jiwa']),
        'jenis_zakat' => $_POST['jenis_zakat'],
        'metode_pembayaran' => $_POST['metode_pembayaran'],
        'total_bayar' => floatval($_POST['total_bayar']),
        'nominal_dibayar' => floatval($_POST['nominal_dibayar']),
        'kembalian' => floatval($_POST['kembalian']),
        'keterangan' => $_POST['keterangan'],
        'tanggal_bayar' => $_POST['tanggal_bayar']
    ];

    $api_url_put = "http://localhost:5000/pembayaran/$id";
    $ch = curl_init($api_url_put);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200) {
        $success = "Data pembayaran berhasil diperbarui.";
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = json_decode($response, true);
    } else {
        $error = "Gagal memperbarui data pembayaran: HTTP $http_code";
    }
}

// Fungsi untuk menghapus data pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);
    $api_url_delete = "http://localhost:5000/pembayaran/$id";
    $ch = curl_init($api_url_delete);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200) {
        $success = "Data pembayaran berhasil dihapus.";
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = json_decode($response, true);
    } else {
        $error = "Gagal menghapus data pembayaran: HTTP $http_code";
    }
}

// Fungsi untuk generate Excel
if (isset($_GET['generate_excel']) && !$error && !empty($data)) {
    require 'vendor/autoload.php'; // Pastikan path ke autoload.php benar

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Header
    $sheet->setCellValue('A1', 'ID');
    $sheet->setCellValue('B1', 'Jumlah Jiwa');
    $sheet->setCellValue('C1', 'Jenis Zakat');
    $sheet->setCellValue('D1', 'Nama');
    $sheet->setCellValue('E1', 'Metode Pembayaran');
    $sheet->setCellValue('F1', 'Total Bayar');
    $sheet->setCellValue('G1', 'Nominal Dibayar');
    $sheet->setCellValue('H1', 'Kembalian');
    $sheet->setCellValue('I1', 'Keterangan');
    $sheet->setCellValue('J1', 'Tanggal Bayar');

    // Data
    $row = 2;
    foreach ($data as $record) {
        $sheet->setCellValue('A' . $row, $record['id']);
        $sheet->setCellValue('B' . $row, $record['jumlah_jiwa']);
        $sheet->setCellValue('C' . $row, $record['jenis_zakat']);
        $sheet->setCellValue('D' . $row, $record['nama']);
        $sheet->setCellValue('E' . $row, $record['metode_pembayaran']);
        $sheet->setCellValue('F' . $row, $record['total_bayar']);
        $sheet->setCellValue('G' . $row, $record['nominal_dibayar']);
        $sheet->setCellValue('H' . $row, $record['kembalian']);
        $sheet->setCellValue('I' . $row, $record['keterangan']);
        $sheet->setCellValue('J' . $row, $record['tanggal_bayar']);
        $row++;
    }

    // Styling
    $sheet->getStyle('A1:J1')->getFont()->setBold(true);
    $sheet->getStyle('A1:J' . ($row-1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

    // Unduh file
    $writer = new Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="pembayaran_zakat_' . date('Ymd_His') . '.xlsx"');
    header('Cache-Control: max-age=0');
    $writer->save('php://output');
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Zakat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        function openEditModal(id, nama, jumlah_jiwa, jenis_zakat, metode_pembayaran, total_bayar, nominal_dibayar, kembalian, keterangan, tanggal_bayar) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_jumlah_jiwa').value = jumlah_jiwa;
            document.getElementById('edit_jenis_zakat').value = jenis_zakat;
            document.getElementById('edit_metode_pembayaran').value = metode_pembayaran;
            document.getElementById('edit_total_bayar').value = total_bayar;
            document.getElementById('edit_nominal_dibayar').value = nominal_dibayar;
            document.getElementById('edit_kembalian').value = kembalian;
            document.getElementById('edit_keterangan').value = keterangan;
            document.getElementById('edit_tanggal_bayar').value = tanggal_bayar.replace(' ', 'T');
            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('editModal').classList.add('opacity-100', 'scale-100');
        }

        function closeEditModal() {
            const modal = document.getElementById('editModal');
            modal.classList.add('opacity-0', 'scale-95');
            setTimeout(() => modal.classList.add('hidden'), 150);
        }

        function submitEditForm(event) {
            event.preventDefault();
            const formData = new FormData(document.getElementById('editForm'));
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });
            fetch(`http://localhost:5000/pembayaran/${data.edit_id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    nama: data.edit_nama,
                    jumlah_jiwa: parseInt(data.edit_jumlah_jiwa),
                    jenis_zakat: data.edit_jenis_zakat,
                    metode_pembayaran: data.edit_metode_pembayaran,
                    total_bayar: parseFloat(data.edit_total_bayar),
                    nominal_dibayar: parseFloat(data.edit_nominal_dibayar),
                    kembalian: parseFloat(data.edit_kembalian),
                    keterangan: data.edit_keterangan,
                    tanggal_bayar: data.edit_tanggal_bayar
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Gagal menyimpan data');
                }
                return response.json();
            })
            .then(result => {
                if (result.message === "Pembayaran updated successfully") {
                    alert("Data berhasil diperbarui!");
                    location.reload();
                } else {
                    throw new Error(result.message || 'Error tidak diketahui');
                }
            })
            .catch(error => {
                alert("Terjadi kesalahan: " + error.message);
            });
        }

        function deletePayment(id) {
            if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
                fetch(`http://localhost:5000/pembayaran/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Gagal menghapus data');
                    }
                    return response.json();
                })
                .then(result => {
                    if (result.message === "Pembayaran deleted successfully") {
                        alert("Data berhasil dihapus!");
                        location.reload();
                    } else {
                        throw new Error(result.message || 'Error tidak diketahui');
                    }
                })
                .catch(error => {
                    alert("Terjadi kesalahan: " + error.message);
                });
            }
        }
    </script>
</head>
<body class="bg-gray-200 text-gray-800">
    <div class="container mx-auto p-4">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl sm:text-3xl font-bold">Pembayaran Zakat</h1>
            <div>
                <a href="dashboard.php" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">Kembali</a>
                <a href="?generate_excel=1" class="bg-green-500 text-white px-3 sm:px-4 py-1 sm:py-2 rounded hover:bg-green-600 text-sm sm:text-base mr-2">Generate Excel</a>
                <button onclick="openEditModal(
                    <?php echo isset($data[0]) ? $data[0]['id'] : 0; ?>,
                    '<?php echo isset($data[0]) ? htmlspecialchars(addslashes($data[0]['nama'])) : ''; ?>',
                    <?php echo isset($data[0]) ? $data[0]['jumlah_jiwa'] : 0; ?>,
                    '<?php echo isset($data[0]) ? htmlspecialchars(addslashes($data[0]['jenis_zakat'])) : ''; ?>',
                    '<?php echo isset($data[0]) ? htmlspecialchars(addslashes($data[0]['metode_pembayaran'])) : ''; ?>',
                    <?php echo isset($data[0]) ? $data[0]['total_bayar'] : 0; ?>,
                    <?php echo isset($data[0]) ? $data[0]['nominal_dibayar'] : 0; ?>,
                    <?php echo isset($data[0]) ? $data[0]['kembalian'] : 0; ?>,
                    '<?php echo isset($data[0]) ? htmlspecialchars(addslashes($data[0]['keterangan'])) : ''; ?>',
                    '<?php echo isset($data[0]) ? $data[0]['tanggal_bayar'] : ''; ?>'
                )" class="bg-blue-500 text-white px-3 sm:px-4 py-1 sm:py-2 rounded hover:bg-blue-600 text-sm sm:text-base">Edit</button>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <p class="text-red-500 text-center"><?php echo htmlspecialchars($error); ?></p>
        <?php elseif (empty($data)): ?>
            <p class="text-gray-500 text-center">Tidak ada data ditemukan</p>
        <?php else: ?>
            <table class="min-w-full bg-white border border-gray-300 rounded-lg overflow-hidden">
                <thead>
                    <tr class="bg-gray-300">
                        <th class="py-2 px-2 sm:px-4 border-b text-sm sm:text-base">ID</th>
                        <th class="py-2 px-2 sm:px-4 border-b text-sm sm:text-base">Jumlah Jiwa</th>
                        <th class="py-2 px-2 sm:px-4 border-b text-sm sm:text-base">Jenis Zakat</th>
                        <th class="py-2 px-2 sm:px-4 border-b text-sm sm:text-base">Nama</th>
                        <th class="py-2 px-2 sm:px-4 border-b text-sm sm:text-base">Metode Pembayaran</th>
                        <th class="py-2 px-2 sm:px-4 border-b text-sm sm:text-base">Total Bayar</th>
                        <th class="py-2 px-2 sm:px-4 border-b text-sm sm:text-base">Nominal Dibayar</th>
                        <th class="py-2 px-2 sm:px-4 border-b text-sm sm:text-base">Kembalian</th>
                        <th class="py-2 px-2 sm:px-4 border-b text-sm sm:text-base">Keterangan</th>
                        <th class="py-2 px-2 sm:px-4 border-b text-sm sm:text-base">Tanggal Bayar</th>
                        <th class="py-2 px-2 sm:px-4 border-b text-sm sm:text-base">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $record): ?>
                        <tr class="hover:bg-gray-100">
                            <td class="py-2 px-2 sm:px-4 border-b text-sm sm:text-base"><?php echo htmlspecialchars($record['id']); ?></td>
                            <td class="py-2 px-2 sm:px-4 border-b text-sm sm:text-base"><?php echo htmlspecialchars($record['jumlah_jiwa']); ?></td>
                            <td class="py-2 px-2 sm:px-4 border-b text-sm sm:text-base"><?php echo htmlspecialchars($record['jenis_zakat']); ?></td>
                            <td class="py-2 px-2 sm:px-4 border-b text-sm sm:text-base"><?php echo htmlspecialchars($record['nama']); ?></td>
                            <td class="py-2 px-2 sm:px-4 border-b text-sm sm:text-base"><?php echo htmlspecialchars($record['metode_pembayaran']); ?></td>
                            <td class="py-2 px-2 sm:px-4 border-b text-sm sm:text-base"><?php echo number_format($record['total_bayar'], 2); ?></td>
                            <td class="py-2 px-2 sm:px-4 border-b text-sm sm:text-base"><?php echo number_format($record['nominal_dibayar'], 2); ?></td>
                            <td class="py-2 px-2 sm:px-4 border-b text-sm sm:text-base"><?php echo number_format($record['kembalian'], 2); ?></td>
                            <td class="py-2 px-2 sm:px-4 border-b text-sm sm:text-base"><?php echo htmlspecialchars($record['keterangan']); ?></td>
                            <td class="py-2 px-2 sm:px-4 border-b text-sm sm:text-base"><?php echo htmlspecialchars($record['tanggal_bayar']); ?></td>
                            <td class="py-2 px-2 sm:px-4 border-b flex items-center space-x-2">
                                <button onclick="openEditModal(
                                    <?php echo $record['id']; ?>,
                                    '<?php echo htmlspecialchars(addslashes($record['nama'])); ?>',
                                    <?php echo $record['jumlah_jiwa']; ?>,
                                    '<?php echo htmlspecialchars(addslashes($record['jenis_zakat'])); ?>',
                                    '<?php echo htmlspecialchars(addslashes($record['metode_pembayaran'])); ?>',
                                    <?php echo $record['total_bayar']; ?>,
                                    <?php echo $record['nominal_dibayar']; ?>,
                                    <?php echo $record['kembalian']; ?>,
                                    '<?php echo htmlspecialchars(addslashes($record['keterangan'])); ?>',
                                    '<?php echo $record['tanggal_bayar']; ?>'
                                )" class="bg-blue-500 text-white p-1 sm:p-2 rounded hover:bg-blue-600 text-sm sm:text-base w-full sm:w-auto">Edit</button>
                                <span class="mx-1">|</span>
                                <button onclick="deletePayment(<?php echo $record['id']; ?>)" class="bg-red-500 text-white p-1 sm:p-2 rounded hover:bg-red-600 text-sm sm:text-base w-full sm:w-auto">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Modal untuk Edit -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden opacity-0 scale-95 transition-opacity transition-scale duration-200 flex items-center justify-center z-50">
        <div class="bg-white p-4 sm:p-6 rounded-lg shadow-lg w-full max-w-xs sm:max-w-md md:max-w-lg lg:max-w-xl">
            <h2 class="text-lg sm:text-xl font-bold mb-4 text-center">Edit Data Pembayaran</h2>
            <form id="editForm" onsubmit="submitEditForm(event)" class="space-y-3 sm:space-y-4">
                <input type="hidden" id="edit_id" name="edit_id">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-4">
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-gray-700">Nama</label>
                        <input type="text" id="edit_nama" name="edit_nama" class="mt-1 p-2 border rounded w-full text-xs sm:text-sm" required>
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-gray-700">Jumlah Jiwa</label>
                        <input type="number" id="edit_jumlah_jiwa" name="edit_jumlah_jiwa" class="mt-1 p-2 border rounded w-full text-xs sm:text-sm" required>
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-gray-700">Jenis Zakat</label>
                        <input type="text" id="edit_jenis_zakat" name="edit_jenis_zakat" class="mt-1 p-2 border rounded w-full text-xs sm:text-sm" required>
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-gray-700">Metode Pembayaran</label>
                        <input type="text" id="edit_metode_pembayaran" name="edit_metode_pembayaran" class="mt-1 p-2 border rounded w-full text-xs sm:text-sm" required>
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-gray-700">Total Bayar</label>
                        <input type="number" step="0.01" id="edit_total_bayar" name="edit_total_bayar" class="mt-1 p-2 border rounded w-full text-xs sm:text-sm" required>
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-gray-700">Nominal Dibayar</label>
                        <input type="number" step="0.01" id="edit_nominal_dibayar" name="edit_nominal_dibayar" class="mt-1 p-2 border rounded w-full text-xs sm:text-sm" required>
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-gray-700">Kembalian</label>
                        <input type="number" step="0.01" id="edit_kembalian" name="edit_kembalian" class="mt-1 p-2 border rounded w-full text-xs sm:text-sm" required>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs sm:text-sm font-medium text-gray-700">Keterangan</label>
                        <textarea id="edit_keterangan" name="edit_keterangan" class="mt-1 p-2 border rounded w-full text-xs sm:text-sm"></textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs sm:text-sm font-medium text-gray-700">Tanggal Bayar</label>
                        <input type="datetime-local" id="edit_tanggal_bayar" name="edit_tanggal_bayar" class="mt-1 p-2 border rounded w-full text-xs sm:text-sm" required>
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-3 sm:mt-4">
                    <button type="submit" class="bg-blue-500 text-white px-3 sm:px-4 py-1 sm:py-2 rounded hover:bg-blue-600 text-xs sm:text-sm">Simpan</button>
                    <button type="button" onclick="closeEditModal()" class="bg-gray-500 text-white px-3 sm:px-4 py-1 sm:py-2 rounded hover:bg-gray-600 text-xs sm:text-sm">Batal</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>