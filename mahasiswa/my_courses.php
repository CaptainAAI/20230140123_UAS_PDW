<?php

require_once '../config.php';
session_start();

// Pastikan user sudah login sebagai mahasiswa
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'mahasiswa') {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil daftar praktikum yang diikuti mahasiswa ini
$sql = "SELECT p.id, p.nama_praktikum, p.deskripsi
        FROM pendaftaran_praktikum pp
        JOIN praktikum p ON pp.praktikum_id = p.id
        WHERE pp.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$pageTitle = 'Praktikum Saya';
require_once 'templates/header_mahasiswa.php';
?>

<h1 class="text-2xl font-bold mb-6">Praktikum yang Diikuti</h1>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
<?php if ($result && $result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
        <div class="bg-white p-6 rounded-xl shadow-md flex flex-col mb-4">
            <div class="font-bold text-lg mb-2"><?php echo htmlspecialchars($row['nama_praktikum']); ?></div>
            <div class="text-gray-600 mb-2"><?php echo htmlspecialchars($row['deskripsi']); ?></div>
            <a href="praktikum_detail.php?id=<?php echo $row['id']; ?>" class="text-blue-600 hover:underline mt-2">Lihat Detail & Tugas</a>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <div class="col-span-3 text-center text-gray-500">Anda belum mengikuti praktikum apapun.</div>
<?php endif; ?>
</div>

<?php require_once 'templates/footer_mahasiswa.php';