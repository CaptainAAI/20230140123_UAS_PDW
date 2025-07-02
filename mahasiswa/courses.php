<?php
require_once '../config.php';
session_start();

// Cek apakah user sudah login dan role mahasiswa
$isMahasiswa = isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'mahasiswa';
$user_id = $isMahasiswa ? $_SESSION['user_id'] : null;

// Proses pendaftaran ke praktikum
$success = $error = '';
if ($isMahasiswa && isset($_POST['praktikum_id'])) {
    $praktikum_id = intval($_POST['praktikum_id']);
    $cek = $conn->prepare("SELECT * FROM pendaftaran_praktikum WHERE user_id=? AND praktikum_id=?");
    $cek->bind_param("ii", $user_id, $praktikum_id);
    $cek->execute();
    $cek->store_result();
    if ($cek->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO pendaftaran_praktikum (user_id, praktikum_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $praktikum_id);
        $stmt->execute();
        $success = "Berhasil mendaftar ke praktikum!";
    } else {
        $error = "Anda sudah terdaftar di praktikum ini.";
    }
}

// Ambil semua mata praktikum dari database
$sql = "SELECT * FROM praktikum";
$result = $conn->query($sql);

$pageTitle = 'Katalog Mata Praktikum';
require_once 'templates/header_mahasiswa.php';
?>

<h1 class="text-2xl font-bold mb-6">Katalog Mata Praktikum</h1>

<?php if ($success): ?>
    <div class="bg-green-100 text-green-700 p-3 rounded mb-4"><?php echo $success; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?php echo $error; ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
<?php if ($result && $result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
        <div class="bg-white p-6 rounded-xl shadow-md flex flex-col mb-4">
            <div class="font-bold text-lg mb-2"><?php echo htmlspecialchars($row['nama_praktikum']); ?></div>
            <div class="text-gray-600 mb-2"><?php echo htmlspecialchars($row['deskripsi']); ?></div>
            <?php if ($isMahasiswa): ?>
                <form method="post" class="mt-2">
                    <input type="hidden" name="praktikum_id" value="<?php echo $row['id']; ?>">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Daftar</button>
                </form>
            <?php else: ?>
                <div class="text-sm text-gray-400 mt-2">Login sebagai mahasiswa untuk mendaftar</div>
            <?php endif; ?>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <div class="col-span-3 text-center text-gray-500">Belum ada mata praktikum tersedia.</div>
<?php endif; ?>
</div>

<?php require_once 'templates/footer_mahasiswa.php'; ?>