<?php

require_once '../config.php';
session_start();

// Cek role asisten
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'Laporan Masuk';
$activePage = 'laporan';

// Ambil data filter
$praktikum_id = isset($_GET['praktikum_id']) ? intval($_GET['praktikum_id']) : '';
$modul_id = isset($_GET['modul_id']) ? intval($_GET['modul_id']) : '';
$mahasiswa_id = isset($_GET['mahasiswa_id']) ? intval($_GET['mahasiswa_id']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Proses penilaian
if (isset($_POST['nilai']) && isset($_POST['laporan_id'])) {
    $laporan_id = intval($_POST['laporan_id']);
    $nilai = intval($_POST['nilai']);
    $catatan = trim($_POST['catatan']);
    $stmt = $conn->prepare("UPDATE laporan SET nilai=?, catatan=? WHERE id=?");
    $stmt->bind_param("isi", $nilai, $catatan, $laporan_id);
    $stmt->execute();
    $success = "Nilai berhasil disimpan.";
}

// Ambil data untuk filter
$praktikumList = $conn->query("SELECT * FROM praktikum ORDER BY nama_praktikum ASC");
$modulList = $conn->query("SELECT * FROM modul ORDER BY judul ASC");
$mahasiswaList = $conn->query("SELECT * FROM users WHERE role='mahasiswa' ORDER BY nama ASC");

// Query laporan masuk dengan filter
$where = [];
$params = [];
$types = '';

if ($praktikum_id) {
    $where[] = "p.id = ?";
    $params[] = $praktikum_id;
    $types .= 'i';
}
if ($modul_id) {
    $where[] = "m.id = ?";
    $params[] = $modul_id;
    $types .= 'i';
}
if ($mahasiswa_id) {
    $where[] = "u.id = ?";
    $params[] = $mahasiswa_id;
    $types .= 'i';
}
if ($status === 'belum') {
    $where[] = "l.nilai IS NULL";
} elseif ($status === 'sudah') {
    $where[] = "l.nilai IS NOT NULL";
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT l.*, m.judul AS judul_modul, m.file_materi, p.nama_praktikum, u.nama AS nama_mahasiswa, u.email
        FROM laporan l
        JOIN modul m ON l.modul_id = m.id
        JOIN praktikum p ON m.praktikum_id = p.id
        JOIN users u ON l.user_id = u.id
        $whereSql
        ORDER BY l.tanggal_kumpul DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$laporanResult = $stmt->get_result();

require_once 'templates/header.php';
?>

<?php if (!empty($success)): ?>
    <div class="bg-green-100 text-green-700 p-3 rounded mb-4"><?php echo $success; ?></div>
<?php endif; ?>

<!-- Filter -->
<div class="bg-white p-4 rounded-lg shadow mb-6">
    <form method="get" class="flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-sm mb-1">Praktikum</label>
            <select name="praktikum_id" class="border p-2 rounded">
                <option value="">Semua</option>
                <?php foreach ($praktikumList as $p): ?>
                    <option value="<?php echo $p['id']; ?>" <?php if ($praktikum_id == $p['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($p['nama_praktikum']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm mb-1">Modul</label>
            <select name="modul_id" class="border p-2 rounded">
                <option value="">Semua</option>
                <?php foreach ($modulList as $m): ?>
                    <option value="<?php echo $m['id']; ?>" <?php if ($modul_id == $m['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($m['judul']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm mb-1">Mahasiswa</label>
            <select name="mahasiswa_id" class="border p-2 rounded">
                <option value="">Semua</option>
                <?php foreach ($mahasiswaList as $mhs): ?>
                    <option value="<?php echo $mhs['id']; ?>" <?php if ($mahasiswa_id == $mhs['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($mhs['nama']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm mb-1">Status</label>
            <select name="status" class="border p-2 rounded">
                <option value="">Semua</option>
                <option value="belum" <?php if ($status === 'belum') echo 'selected'; ?>>Belum Dinilai</option>
                <option value="sudah" <?php if ($status === 'sudah') echo 'selected'; ?>>Sudah Dinilai</option>
            </select>
        </div>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Filter</button>
    </form>
</div>

<!-- Daftar Laporan -->
<div class="bg-white p-6 rounded-lg shadow">
    <h2 class="text-xl font-bold mb-4">Daftar Laporan Masuk</h2>
    <div class="overflow-x-auto">
    <table class="min-w-full border">
        <thead>
            <tr class="bg-gray-100">
                <th class="py-2 px-4 border">Tanggal</th>
                <th class="py-2 px-4 border">Praktikum</th>
                <th class="py-2 px-4 border">Modul</th>
                <th class="py-2 px-4 border">Mahasiswa</th>
                <th class="py-2 px-4 border">Laporan</th>
                <th class="py-2 px-4 border">Nilai</th>
                <th class="py-2 px-4 border">Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($laporanResult->num_rows > 0): ?>
            <?php while ($laporan = $laporanResult->fetch_assoc()): ?>
                <tr>
                    <td class="py-2 px-4 border text-xs"><?php echo htmlspecialchars($laporan['tanggal_kumpul']); ?></td>
                    <td class="py-2 px-4 border"><?php echo htmlspecialchars($laporan['nama_praktikum']); ?></td>
                    <td class="py-2 px-4 border"><?php echo htmlspecialchars($laporan['judul_modul']); ?></td>
                    <td class="py-2 px-4 border">
                        <div class="font-bold"><?php echo htmlspecialchars($laporan['nama_mahasiswa']); ?></div>
                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($laporan['email']); ?></div>
                    </td>
                    <td class="py-2 px-4 border">
                        <?php if ($laporan['file_laporan']): ?>
                            <a href="../uploads/laporan/<?php echo htmlspecialchars($laporan['file_laporan']); ?>" target="_blank" class="text-blue-600 underline">Download</a>
                        <?php else: ?>
                            <span class="text-gray-400">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="py-2 px-4 border text-center">
                        <?php if ($laporan['nilai'] !== null): ?>
                            <span class="font-bold text-green-700"><?php echo htmlspecialchars($laporan['nilai']); ?></span>
                        <?php else: ?>
                            <span class="text-gray-400">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="py-2 px-4 border">
                        <!-- Tombol nilai (toggle form) -->
                        <button onclick="document.getElementById('nilai-<?php echo $laporan['id']; ?>').style.display='block'" class="bg-yellow-400 text-white px-3 py-1 rounded text-sm">Nilai</button>
                        <!-- Form nilai (popup sederhana) -->
                        <div id="nilai-<?php echo $laporan['id']; ?>" style="display:none;" class="mt-2">
                            <form method="post" class="bg-gray-50 p-3 rounded space-y-2">
                                <input type="hidden" name="laporan_id" value="<?php echo $laporan['id']; ?>">
                                <input type="number" name="nilai" min="0" max="100" value="<?php echo htmlspecialchars($laporan['nilai']); ?>" class="border p-2 rounded w-full" placeholder="Nilai" required>
                                <textarea name="catatan" class="border p-2 rounded w-full" placeholder="Feedback"><?php echo htmlspecialchars($laporan['catatan']); ?></textarea>
                                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Simpan</button>
                                <button type="button" onclick="document.getElementById('nilai-<?php echo $laporan['id']; ?>').style.display='none'" class="bg-gray-400 text-white px-4 py-2 rounded">Batal</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="7" class="text-center text-gray-500 py-4">Tidak ada laporan ditemukan.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<?php require_once 'templates/footer.php'; ?>