<?php


require_once '../config.php';
session_start();

// Cek role asisten
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'Kelola Mata Praktikum & Modul';
$activePage = 'modul';

// --- CRUD Praktikum ---
// CREATE
if (isset($_POST['tambah'])) {
    $nama = trim($_POST['nama_praktikum']);
    $deskripsi = trim($_POST['deskripsi']);
    if ($nama) {
        $stmt = $conn->prepare("INSERT INTO praktikum (nama_praktikum, deskripsi) VALUES (?, ?)");
        $stmt->bind_param("ss", $nama, $deskripsi);
        $stmt->execute();
        $success = "Mata praktikum berhasil ditambahkan.";
    }
}
// UPDATE
if (isset($_POST['edit'])) {
    $id = intval($_POST['id']);
    $nama = trim($_POST['nama_praktikum']);
    $deskripsi = trim($_POST['deskripsi']);
    $stmt = $conn->prepare("UPDATE praktikum SET nama_praktikum=?, deskripsi=? WHERE id=?");
    $stmt->bind_param("ssi", $nama, $deskripsi, $id);
    $stmt->execute();
    $success = "Mata praktikum berhasil diubah.";
}
// DELETE
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    $stmt = $conn->prepare("DELETE FROM praktikum WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $success = "Mata praktikum berhasil dihapus.";
}
// READ
$praktikum = $conn->query("SELECT * FROM praktikum ORDER BY id DESC");

// --- CRUD Modul ---
if (isset($_POST['tambah_modul'])) {
    $praktikum_id = intval($_POST['praktikum_id']);
    $judul = trim($_POST['judul']);
    $file_materi = null;

    // Upload file materi jika ada
    if (isset($_FILES['file_materi']) && $_FILES['file_materi']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/materi/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $filename = uniqid() . '_' . basename($_FILES['file_materi']['name']);
        $targetPath = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['file_materi']['tmp_name'], $targetPath)) {
            $file_materi = $filename;
        }
    }

    $stmt = $conn->prepare("INSERT INTO modul (praktikum_id, judul, file_materi) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $praktikum_id, $judul, $file_materi);
    $stmt->execute();
    $success = "Modul berhasil ditambahkan.";
}

if (isset($_POST['edit_modul'])) {
    $id = intval($_POST['id']);
    $judul = trim($_POST['judul']);
    $praktikum_id = intval($_POST['praktikum_id']);
    $file_materi = null;

    // Cek jika ada file baru
    if (isset($_FILES['file_materi']) && $_FILES['file_materi']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/materi/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $filename = uniqid() . '_' . basename($_FILES['file_materi']['name']);
        $targetPath = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['file_materi']['tmp_name'], $targetPath)) {
            $file_materi = $filename;
        }
    }

    if ($file_materi) {
        $stmt = $conn->prepare("UPDATE modul SET judul=?, file_materi=?, praktikum_id=? WHERE id=?");
        $stmt->bind_param("ssii", $judul, $file_materi, $praktikum_id, $id);
    } else {
        $stmt = $conn->prepare("UPDATE modul SET judul=?, praktikum_id=? WHERE id=?");
        $stmt->bind_param("sii", $judul, $praktikum_id, $id);
    }
    $stmt->execute();
    $success = "Modul berhasil diubah.";
}

if (isset($_GET['hapus_modul'])) {
    $id = intval($_GET['hapus_modul']);
    $stmt = $conn->prepare("DELETE FROM modul WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $success = "Modul berhasil dihapus.";
}

// Ambil semua praktikum untuk pilihan
$praktikumList = $conn->query("SELECT * FROM praktikum ORDER BY nama_praktikum ASC");

// Ambil semua modul (join praktikum)
$modulList = $conn->query("SELECT m.*, p.nama_praktikum FROM modul m JOIN praktikum p ON m.praktikum_id = p.id ORDER BY m.id DESC");

require_once 'templates/header.php';
?>

<?php if (!empty($success)): ?>
    <div class="bg-green-100 text-green-700 p-3 rounded mb-4"><?php echo $success; ?></div>
<?php endif; ?>

<!-- CRUD Praktikum -->
<div class="bg-white p-6 rounded-lg shadow-md mb-8">
    <h2 class="text-xl font-bold mb-4">Tambah Mata Praktikum</h2>
    <form method="post" class="space-y-3">
        <input type="text" name="nama_praktikum" class="border p-2 w-full rounded" placeholder="Nama Praktikum" required>
        <textarea name="deskripsi" class="border p-2 w-full rounded" placeholder="Deskripsi"></textarea>
        <button type="submit" name="tambah" class="bg-blue-600 text-white px-4 py-2 rounded">Tambah</button>
    </form>
</div>

<div class="bg-white p-6 rounded-lg shadow-md mb-8">
    <h2 class="text-xl font-bold mb-4">Daftar Mata Praktikum</h2>
    <table class="min-w-full border">
        <thead>
            <tr class="bg-gray-100">
                <th class="py-2 px-4 border">Nama Praktikum</th>
                <th class="py-2 px-4 border">Deskripsi</th>
                <th class="py-2 px-4 border">Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $praktikum->fetch_assoc()): ?>
            <tr>
                <td class="py-2 px-4 border"><?php echo htmlspecialchars($row['nama_praktikum']); ?></td>
                <td class="py-2 px-4 border"><?php echo htmlspecialchars($row['deskripsi']); ?></td>
                <td class="py-2 px-4 border">
                    <button onclick="document.getElementById('edit-<?php echo $row['id']; ?>').style.display='block'" class="bg-yellow-400 text-white px-3 py-1 rounded text-sm">Edit</button>
                    <a href="?hapus=<?php echo $row['id']; ?>" onclick="return confirm('Yakin hapus?')" class="bg-red-500 text-white px-3 py-1 rounded text-sm ml-2">Hapus</a>
                </td>
            </tr>
            <tr id="edit-<?php echo $row['id']; ?>" style="display:none;">
                <td colspan="3" class="py-2 px-4 border bg-gray-50">
                    <form method="post" class="flex flex-col md:flex-row gap-2">
                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                        <input type="text" name="nama_praktikum" value="<?php echo htmlspecialchars($row['nama_praktikum']); ?>" class="border p-2 rounded w-full md:w-1/4" required>
                        <input type="text" name="deskripsi" value="<?php echo htmlspecialchars($row['deskripsi']); ?>" class="border p-2 rounded w-full md:w-1/2">
                        <button type="submit" name="edit" class="bg-green-600 text-white px-4 py-2 rounded">Simpan</button>
                        <button type="button" onclick="document.getElementById('edit-<?php echo $row['id']; ?>').style.display='none'" class="bg-gray-400 text-white px-4 py-2 rounded">Batal</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- CRUD Modul -->
<div class="bg-white p-6 rounded-lg shadow-md mb-8">
    <h2 class="text-xl font-bold mb-4">Tambah Modul / Pertemuan</h2>
    <form method="post" enctype="multipart/form-data" class="space-y-3">
        <select name="praktikum_id" class="border p-2 w-full rounded" required>
            <option value="">-- Pilih Praktikum --</option>
            <?php while ($p = $praktikumList->fetch_assoc()): ?>
                <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nama_praktikum']); ?></option>
            <?php endwhile; ?>
        </select>
        <input type="text" name="judul" class="border p-2 w-full rounded" placeholder="Judul Modul" required>
        <input type="file" name="file_materi" class="border p-2 w-full rounded" accept=".pdf,.doc,.docx">
        <button type="submit" name="tambah_modul" class="bg-blue-600 text-white px-4 py-2 rounded">Tambah Modul</button>
    </form>
</div>

<div class="bg-white p-6 rounded-lg shadow-md">
    <h2 class="text-xl font-bold mb-4">Daftar Modul</h2>
    <table class="min-w-full border">
        <thead>
            <tr class="bg-gray-100">
                <th class="py-2 px-4 border">Praktikum</th>
                <th class="py-2 px-4 border">Judul Modul</th>
                <th class="py-2 px-4 border">Materi</th>
                <th class="py-2 px-4 border">Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($modul = $modulList->fetch_assoc()): ?>
            <tr>
                <td class="py-2 px-4 border"><?php echo htmlspecialchars($modul['nama_praktikum']); ?></td>
                <td class="py-2 px-4 border"><?php echo htmlspecialchars($modul['judul']); ?></td>
                <td class="py-2 px-4 border">
                    <?php if ($modul['file_materi']): ?>
                        <a href="../uploads/materi/<?php echo htmlspecialchars($modul['file_materi']); ?>" target="_blank" class="text-blue-600 underline">Unduh</a>
                    <?php else: ?>
                        <span class="text-gray-400">-</span>
                    <?php endif; ?>
                </td>
                <td class="py-2 px-4 border">
                    <button onclick="document.getElementById('edit-modul-<?php echo $modul['id']; ?>').style.display='block'" class="bg-yellow-400 text-white px-3 py-1 rounded text-sm">Edit</button>
                    <a href="?hapus_modul=<?php echo $modul['id']; ?>" onclick="return confirm('Yakin hapus modul ini?')" class="bg-red-500 text-white px-3 py-1 rounded text-sm ml-2">Hapus</a>
                </td>
            </tr>
            <tr id="edit-modul-<?php echo $modul['id']; ?>" style="display:none;">
                <td colspan="4" class="py-2 px-4 border bg-gray-50">
                    <form method="post" enctype="multipart/form-data" class="flex flex-col md:flex-row gap-2">
                        <input type="hidden" name="id" value="<?php echo $modul['id']; ?>">
                        <select name="praktikum_id" class="border p-2 rounded w-full md:w-1/4" required>
                            <?php
                            $praktikumEdit = $conn->query("SELECT * FROM praktikum ORDER BY nama_praktikum ASC");
                            while ($p = $praktikumEdit->fetch_assoc()):
                            ?>
                                <option value="<?php echo $p['id']; ?>" <?php if ($p['id'] == $modul['praktikum_id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($p['nama_praktikum']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <input type="text" name="judul" value="<?php echo htmlspecialchars($modul['judul']); ?>" class="border p-2 rounded w-full md:w-1/4" required>
                        <input type="file" name="file_materi" class="border p-2 rounded w-full md:w-1/4" accept=".pdf,.doc,.docx">
                        <button type="submit" name="edit_modul" class="bg-green-600 text-white px-4 py-2 rounded">Simpan</button>
                        <button type="button" onclick="document.getElementById('edit-modul-<?php echo $modul['id']; ?>').style.display='none'" class="bg-gray-400 text-white px-4 py-2 rounded">Batal</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php require_once 'templates/footer.php'; ?>