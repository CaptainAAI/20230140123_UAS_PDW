<?php

require_once '../config.php';
session_start();

// Cek role asisten
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'Kelola Akun Pengguna';
$activePage = 'user';

// CREATE
if (isset($_POST['tambah'])) {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    // Cek email unik
    $cek = $conn->prepare("SELECT id FROM users WHERE email=?");
    $cek->bind_param("s", $email);
    $cek->execute();
    $cek->store_result();
    if ($cek->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nama, $email, $password, $role);
        $stmt->execute();
        $success = "Akun berhasil ditambahkan.";
    } else {
        $error = "Email sudah terdaftar.";
    }
}

// UPDATE
if (isset($_POST['edit'])) {
    $id = intval($_POST['id']);
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET nama=?, email=?, password=?, role=? WHERE id=?");
        $stmt->bind_param("ssssi", $nama, $email, $password, $role, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET nama=?, email=?, role=? WHERE id=?");
        $stmt->bind_param("sssi", $nama, $email, $role, $id);
    }
    $stmt->execute();
    $success = "Akun berhasil diubah.";
}

// DELETE
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    // Tidak boleh hapus diri sendiri
    if ($id != $_SESSION['user_id']) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $success = "Akun berhasil dihapus.";
    } else {
        $error = "Tidak bisa menghapus akun sendiri.";
    }
}

// READ
$userList = $conn->query("SELECT * FROM users ORDER BY role, nama ASC");

require_once 'templates/header.php';
?>

<?php if (!empty($success)): ?>
    <div class="bg-green-100 text-green-700 p-3 rounded mb-4"><?php echo $success; ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?php echo $error; ?></div>
<?php endif; ?>

<div class="bg-white p-6 rounded-lg shadow-md mb-8">
    <h2 class="text-xl font-bold mb-4">Tambah Akun Pengguna</h2>
    <form method="post" class="space-y-3">
        <input type="text" name="nama" class="border p-2 w-full rounded" placeholder="Nama" required>
        <input type="email" name="email" class="border p-2 w-full rounded" placeholder="Email" required>
        <input type="password" name="password" class="border p-2 w-full rounded" placeholder="Password" required>
        <select name="role" class="border p-2 w-full rounded" required>
            <option value="mahasiswa">Mahasiswa</option>
            <option value="asisten">Asisten</option>
        </select>
        <button type="submit" name="tambah" class="bg-blue-600 text-white px-4 py-2 rounded">Tambah</button>
    </form>
</div>

<div class="bg-white p-6 rounded-lg shadow-md">
    <h2 class="text-xl font-bold mb-4">Daftar Akun Pengguna</h2>
    <table class="min-w-full border">
        <thead>
            <tr class="bg-gray-100">
                <th class="py-2 px-4 border">Nama</th>
                <th class="py-2 px-4 border">Email</th>
                <th class="py-2 px-4 border">Role</th>
                <th class="py-2 px-4 border">Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($user = $userList->fetch_assoc()): ?>
            <tr>
                <td class="py-2 px-4 border"><?php echo htmlspecialchars($user['nama']); ?></td>
                <td class="py-2 px-4 border"><?php echo htmlspecialchars($user['email']); ?></td>
                <td class="py-2 px-4 border"><?php echo htmlspecialchars($user['role']); ?></td>
                <td class="py-2 px-4 border">
                    <button onclick="document.getElementById('edit-<?php echo $user['id']; ?>').style.display='block'" class="bg-yellow-400 text-white px-3 py-1 rounded text-sm">Edit</button>
                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                        <a href="?hapus=<?php echo $user['id']; ?>" onclick="return confirm('Yakin hapus akun ini?')" class="bg-red-500 text-white px-3 py-1 rounded text-sm ml-2">Hapus</a>
                    <?php endif; ?>
                </td>
            </tr>
            <!-- Form Edit (Popup Sederhana) -->
            <tr id="edit-<?php echo $user['id']; ?>" style="display:none;">
                <td colspan="4" class="py-2 px-4 border bg-gray-50">
                    <form method="post" class="flex flex-col md:flex-row gap-2">
                        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                        <input type="text" name="nama" value="<?php echo htmlspecialchars($user['nama']); ?>" class="border p-2 rounded w-full md:w-1/4" required>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="border p-2 rounded w-full md:w-1/4" required>
                        <input type="password" name="password" class="border p-2 rounded w-full md:w-1/4" placeholder="Password baru (opsional)">
                        <select name="role" class="border p-2 rounded w-full md:w-1/4" required>
                            <option value="mahasiswa" <?php if ($user['role'] == 'mahasiswa') echo 'selected'; ?>>Mahasiswa</option>
                            <option value="asisten" <?php if ($user['role'] == 'asisten') echo 'selected'; ?>>Asisten</option>
                        </select>
                        <button type="submit" name="edit" class="bg-green-600 text-white px-4 py-2 rounded">Simpan</button>
                        <button type="button" onclick="document.getElementById('edit-<?php echo $user['id']; ?>').style.display='none'" class="bg-gray-400 text-white px-4 py-2 rounded">Batal</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php require_once 'templates/footer.php'; ?>