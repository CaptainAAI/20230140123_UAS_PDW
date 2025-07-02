<?php
require_once '../config.php'; // Tambahkan ini di baris paling atas!
$pageTitle = 'Dashboard';
$activePage = 'dashboard';
require_once 'templates/header.php';


// Query total modul
$totalModul = $conn->query("SELECT COUNT(*) AS total FROM modul")->fetch_assoc()['total'];

// Query total laporan masuk
$totalLaporan = $conn->query("SELECT COUNT(*) AS total FROM laporan")->fetch_assoc()['total'];

// Query laporan belum dinilai
$belumDinilai = $conn->query("SELECT COUNT(*) AS total FROM laporan WHERE nilai IS NULL")->fetch_assoc()['total'];

// Query aktivitas laporan terbaru (5 terakhir)
$aktivitas = $conn->query(
    "SELECT l.tanggal_kumpul, u.nama AS nama_mahasiswa, m.judul AS judul_modul
     FROM laporan l
     JOIN users u ON l.user_id = u.id
     JOIN modul m ON l.modul_id = m.id
     ORDER BY l.tanggal_kumpul DESC
     LIMIT 5"
);
?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div class="bg-white p-6 rounded-lg shadow-md flex items-center space-x-4">
        <div class="bg-blue-100 p-3 rounded-full">
            <svg class="w-6 h-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500">Total Modul Diajarkan</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo $totalModul; ?></p>
        </div>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-md flex items-center space-x-4">
        <div class="bg-green-100 p-3 rounded-full">
            <svg class="w-6 h-6 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500">Total Laporan Masuk</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo $totalLaporan; ?></p>
        </div>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-md flex items-center space-x-4">
        <div class="bg-yellow-100 p-3 rounded-full">
            <svg class="w-6 h-6 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500">Laporan Belum Dinilai</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo $belumDinilai; ?></p>
        </div>
    </div>
</div>

<div class="bg-white p-6 rounded-lg shadow-md mt-8">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Aktivitas Laporan Terbaru</h3>
    <div class="space-y-4">
        <?php if ($aktivitas->num_rows > 0): ?>
            <?php while ($a = $aktivitas->fetch_assoc()): ?>
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center mr-4">
                        <span class="font-bold text-gray-500">
                            <?php
                                $inisial = '';
                                $namaArr = explode(' ', $a['nama_mahasiswa']);
                                foreach ($namaArr as $n) {
                                    $inisial .= strtoupper(substr($n,0,1));
                                }
                                echo substr($inisial,0,2);
                            ?>
                        </span>
                    </div>
                    <div>
                        <p class="text-gray-800">
                            <strong><?php echo htmlspecialchars($a['nama_mahasiswa']); ?></strong>
                            mengumpulkan laporan untuk <strong><?php echo htmlspecialchars($a['judul_modul']); ?></strong>
                        </p>
                        <p class="text-sm text-gray-500">
                            <?php
                                // Tampilkan waktu relatif (misal: "2 jam lalu")
                                $waktu = strtotime($a['tanggal_kumpul']);
                                $selisih = time() - $waktu;
                                if ($selisih < 60) {
                                    echo $selisih . " detik lalu";
                                } elseif ($selisih < 3600) {
                                    echo floor($selisih/60) . " menit lalu";
                                } elseif ($selisih < 86400) {
                                    echo floor($selisih/3600) . " jam lalu";
                                } else {
                                    echo date('d M Y H:i', $waktu);
                                }
                            ?>
                        </p>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-gray-500">Belum ada aktivitas laporan.</div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'templates/footer.php'; ?>