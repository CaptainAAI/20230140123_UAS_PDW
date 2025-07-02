<?php


require_once '../config.php';
session_start();

date_default_timezone_set('Asia/Jakarta');

$pageTitle = 'Dashboard';
$activePage = 'dashboard';
require_once 'templates/header_mahasiswa.php';

$user_id = $_SESSION['user_id'];

// Jumlah praktikum diikuti
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM pendaftaran_praktikum WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$praktikumDiikuti = $stmt->get_result()->fetch_assoc()['total'];

// Jumlah tugas selesai (sudah upload laporan & sudah dinilai)
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM laporan WHERE user_id=? AND file_laporan IS NOT NULL AND nilai IS NOT NULL");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tugasSelesai = $stmt->get_result()->fetch_assoc()['total'];

// Jumlah tugas menunggu (sudah upload laporan, belum dinilai)
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM laporan WHERE user_id=? AND file_laporan IS NOT NULL AND nilai IS NULL");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tugasMenunggu = $stmt->get_result()->fetch_assoc()['total'];

// Notifikasi terbaru (ambil 5 laporan terakhir user ini, ambil juga praktikum_id)
$stmt = $conn->prepare(
    "SELECT l.*, m.judul AS judul_modul, m.praktikum_id, p.nama_praktikum
     FROM laporan l
     JOIN modul m ON l.modul_id = m.id
     JOIN praktikum p ON m.praktikum_id = p.id
     WHERE l.user_id=?
     ORDER BY l.tanggal_kumpul DESC
     LIMIT 5"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifResult = $stmt->get_result();
?>

<div class="bg-gradient-to-r from-blue-500 to-cyan-400 text-white p-8 rounded-xl shadow-lg mb-8">
    <h1 class="text-3xl font-bold">Selamat Datang Kembali, <?php echo htmlspecialchars($_SESSION['nama']); ?>!</h1>
    <p class="mt-2 opacity-90">Terus semangat dalam menyelesaikan semua modul praktikummu.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-6 rounded-xl shadow-md flex flex-col items-center justify-center">
        <div class="text-5xl font-extrabold text-blue-600"><?php echo $praktikumDiikuti; ?></div>
        <div class="mt-2 text-lg text-gray-600">Praktikum Diikuti</div>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-md flex flex-col items-center justify-center">
        <div class="text-5xl font-extrabold text-green-500"><?php echo $tugasSelesai; ?></div>
        <div class="mt-2 text-lg text-gray-600">Tugas Selesai</div>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-md flex flex-col items-center justify-center">
        <div class="text-5xl font-extrabold text-yellow-500"><?php echo $tugasMenunggu; ?></div>
        <div class="mt-2 text-lg text-gray-600">Tugas Menunggu</div>
    </div>
</div>

<div class="bg-white p-6 rounded-xl shadow-md">
    <h3 class="text-2xl font-bold text-gray-800 mb-4">Notifikasi Terbaru</h3>
    <ul class="space-y-4">
        <?php if ($notifResult->num_rows > 0): ?>
            <?php while ($n = $notifResult->fetch_assoc()): ?>
                <li class="flex items-start p-3 border-b border-gray-100 last:border-b-0">
                    <span class="text-xl mr-4">
                        <?php
                        if ($n['nilai'] !== null) {
                            echo "🔔";
                        } elseif ($n['file_laporan']) {
                            echo "⏳";
                        } else {
                            echo "✅";
                        }
                        ?>
                    </span>
                    <div>
                        <?php if ($n['nilai'] !== null): ?>
                            Nilai untuk 
                            <a href="praktikum_detail.php?id=<?php echo htmlspecialchars($n['praktikum_id']); ?>" 
                               class="font-semibold text-blue-600 underline hover:text-blue-800">
                               <?php echo htmlspecialchars($n['judul_modul']); ?>
                            </a>
                            telah diberikan (<?php echo htmlspecialchars($n['nilai']); ?>).
                        <?php elseif ($n['file_laporan']): ?>
                            Laporan 
                            <a href="praktikum_detail.php?id=<?php echo htmlspecialchars($n['praktikum_id']); ?>" 
                               class="font-semibold text-blue-600 underline hover:text-blue-800">
                               <?php echo htmlspecialchars($n['judul_modul']); ?>
                            </a>
                            menunggu penilaian.
                        <?php else: ?>
                            Anda berhasil mendaftar pada mata praktikum <span class="font-semibold text-blue-600"><?php echo htmlspecialchars($n['nama_praktikum']); ?></span>.
                        <?php endif; ?>
                        <div class="text-xs text-gray-500">
                            <?php echo date('d M Y H:i', strtotime($n['tanggal_kumpul'])); ?>
                        </div>
                    </div>
                </li>
            <?php endwhile; ?>
        <?php else: ?>
            <li class="text-gray-500">Belum ada notifikasi.</li>
        <?php endif; ?>
    </ul>
</div>

<?php
require_once 'templates/footer_mahasiswa.php';