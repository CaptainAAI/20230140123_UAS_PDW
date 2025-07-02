<?php

require_once '../config.php';
session_start();

// Pastikan user login sebagai mahasiswa
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'mahasiswa') {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$praktikum_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Ambil info praktikum
$stmt = $conn->prepare("SELECT * FROM praktikum WHERE id = ?");
$stmt->bind_param("i", $praktikum_id);
$stmt->execute();
$praktikum = $stmt->get_result()->fetch_assoc();
if (!$praktikum) {
    echo "Praktikum tidak ditemukan.";
    exit;
}

// Proses upload laporan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modul_id'])) {
    $modul_id = intval($_POST['modul_id']);
    if (isset($_FILES['file_laporan']) && $_FILES['file_laporan']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/laporan/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $filename = uniqid() . '_' . basename($_FILES['file_laporan']['name']);
        $targetPath = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['file_laporan']['tmp_name'], $targetPath)) {
            // Cek jika sudah pernah upload, update, jika belum insert
            $cek = $conn->prepare("SELECT id FROM laporan WHERE user_id=? AND modul_id=?");
            $cek->bind_param("ii", $user_id, $modul_id);
            $cek->execute();
            $cek->store_result();
            if ($cek->num_rows > 0) {
                $stmt = $conn->prepare("UPDATE laporan SET file_laporan=? WHERE user_id=? AND modul_id=?");
                $stmt->bind_param("sii", $filename, $user_id, $modul_id);
                $stmt->execute();
            } else {
                $stmt = $conn->prepare("INSERT INTO laporan (user_id, modul_id, file_laporan) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $user_id, $modul_id, $filename);
                $stmt->execute();
            }
            $uploadSuccess = "Laporan berhasil diunggah.";
        } else {
            $uploadError = "Gagal mengunggah file.";
        }
    } else {
        $uploadError = "Pilih file laporan yang valid.";
    }
}

// Ambil daftar modul
$stmt = $conn->prepare("SELECT * FROM modul WHERE praktikum_id = ?");
$stmt->bind_param("i", $praktikum_id);
$stmt->execute();
$modulResult = $stmt->get_result();

$pageTitle = 'Detail Praktikum';
require_once 'templates/header_mahasiswa.php';
?>

<h1 class="text-2xl font-bold mb-4">Detail Praktikum: <?php echo htmlspecialchars($praktikum['nama_praktikum']); ?></h1>
<p class="mb-6"><?php echo htmlspecialchars($praktikum['deskripsi']); ?></p>

<?php if (isset($uploadSuccess)): ?>
    <div class="bg-green-100 text-green-700 p-3 rounded mb-4"><?php echo $uploadSuccess; ?></div>
<?php endif; ?>
<?php if (isset($uploadError)): ?>
    <div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?php echo $uploadError; ?></div>
<?php endif; ?>

<h2 class="text-xl font-semibold mb-3">Daftar Modul & Tugas</h2>
<table class="min-w-full border mb-8">
    <thead>
        <tr class="bg-gray-100">
            <th class="py-2 px-4 border">Modul</th>
            <th class="py-2 px-4 border">Materi</th>
            <th class="py-2 px-4 border">Laporan</th>
            <th class="py-2 px-4 border">Nilai</th>
        </tr>
    </thead>
    <tbody>
    <?php while ($modul = $modulResult->fetch_assoc()): ?>
        <tr>
            <td class="py-2 px-4 border"><?php echo htmlspecialchars($modul['judul']); ?></td>
            <td class="py-2 px-4 border">
                <?php if (!empty($modul['file_materi'])): ?>
                    <a href="../uploads/materi/<?php echo htmlspecialchars($modul['file_materi']); ?>" target="_blank" class="text-blue-600 underline">Unduh</a>
                <?php else: ?>
                    <span class="text-gray-400">Belum ada</span>
                <?php endif; ?>
            </td>
            <td class="py-2 px-4 border">
                <?php
                // Cek laporan mahasiswa
                $stmt2 = $conn->prepare("SELECT * FROM laporan WHERE user_id=? AND modul_id=?");
                $stmt2->bind_param("ii", $user_id, $modul['id']);
                $stmt2->execute();
                $laporan = $stmt2->get_result()->fetch_assoc();
                ?>
                <form method="post" enctype="multipart/form-data" style="display:inline;">
                    <input type="hidden" name="modul_id" value="<?php echo $modul['id']; ?>">
                    <input type="file" name="file_laporan" required>
                    <button type="submit" class="bg-blue-500 text-white px-2 py-1 rounded text-sm">Upload</button>
                </form>
                <?php if ($laporan && !empty($laporan['file_laporan'])): ?>
                    <br>
                    <a href="../uploads/laporan/<?php echo htmlspecialchars($laporan['file_laporan']); ?>" target="_blank" class="text-green-600 underline text-sm">Lihat Laporan</a>
                <?php endif; ?>
            </td>
            <td class="py-2 px-4 border text-center">
                <?php
                if ($laporan && $laporan['nilai'] !== null) {
                    echo '<span class="font-bold text-green-700">' . htmlspecialchars($laporan['nilai']) . '</span>';
                } else {
                    echo '<span class="text-gray-400">Belum dinilai</span>';
                }
                ?>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<a href="my_courses.php" class="text-blue-600 hover:underline">&larr; Kembali ke Praktikum Saya</a>

<?php require_once 'templates/footer_mahasiswa.php'; ?>