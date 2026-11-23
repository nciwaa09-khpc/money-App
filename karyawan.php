<?php

require_once __DIR__ . '/data/backendkaryawan.php';

function aman($teks)
{
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

$pesan = '';
$modeEdit = false;
$dataEdit = [
    'id' => '',
    'nama' => '',
    'jabatan' => '',
    'masa_kerja' => '',
    'no_hp' => '',
    'status' => 'Aktif'
];

// Proses tambah, edit, dan hapus
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'simpan') {
        $data = [
            'nama' => trim($_POST['nama'] ?? ''),
            'jabatan' => trim($_POST['jabatan'] ?? ''),
            'masa_kerja' => trim($_POST['masa_kerja'] ?? ''),
            'no_hp' => trim($_POST['no_hp'] ?? ''),
            'status' => $_POST['status'] ?? 'Aktif'
        ];

        if (
            $data['nama'] === '' ||
            $data['jabatan'] === '' ||
            $data['masa_kerja'] === '' ||
            $data['no_hp'] === '' ||
            !in_array($data['status'], ['Aktif', 'Tidak Aktif'], true)
        ) {
            header('Location: karyawan.php?pesan=tidak_valid');
            exit;
        }

        if (!empty($_POST['id'])) {
            $berhasil = editKaryawan($_POST['id'], $data);
            $pesan = $berhasil ? 'edit_berhasil' : 'gagal';
        } else {
            $berhasil = tambahKaryawan($data);
            $pesan = $berhasil ? 'tambah_berhasil' : 'gagal';
        }

        header("Location: karyawan.php?pesan=$pesan");
        exit;
    }

    if ($aksi === 'hapus') {
        $berhasil = hapusKaryawan($_POST['id'] ?? 0);

        header('Location: karyawan.php?pesan=' .
            ($berhasil ? 'hapus_berhasil' : 'gagal'));
        exit;
    }
}

// Mengambil data karyawan
$karyawan = semuaKaryawan();

// Mengambil data untuk diedit
if (isset($_GET['edit'])) {
    foreach ($karyawan as $item) {
        if ((int) $item['id'] === (int) $_GET['edit']) {
            $dataEdit = $item;
            $modeEdit = true;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Karyawan | PayRoll</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="app-container">

    <aside class="sidebar">
        <div class="brand">
            <div class="brand-icon">P</div>
            <div>
                <h2>PayRoll</h2>
                <small>Sistem Penggajian</small>
            </div>
        </div>

        <p class="sidebar-label">MENU UTAMA</p>

        <nav class="menu">
            <a href="index.php" class="menu-item">
                <span>⌂</span> Dashboard
            </a>

            <a href="karyawan.php" class="menu-item active">
                <span>♙</span> Data Karyawan
            </a>

            <a href="penggajian.php" class="menu-item">
                <span>▤</span> Penggajian
            </a>

            <a href="laporan.php" class="menu-item">
                <span>▣</span> Laporan
            </a>
        </nav>

        <div class="profile">
            <div class="profile-avatar">A</div>
            <div class="profile-info">
                <small>Selamat datang,</small>
                <strong>Admin</strong>
            </div>
        </div>
    </aside>

    <main class="main-content">

        <header class="topbar">
            <p>Sistem Informasi Penggajian</p>

            <div class="topbar-right">
                <span><?php echo date('d F Y'); ?></span>
                <div class="topbar-avatar">A</div>
            </div>
        </header>

        <section class="page-heading">
            <div>
                <p class="eyebrow">MANAJEMEN DATA</p>
                <h1>Data Karyawan</h1>
                <p class="welcome-description">
                    Kelola informasi karyawan perusahaan.
                </p>
            </div>
        </section>

        <?php if (isset($_GET['pesan'])): ?>
            <p>
                <?php
                $pesan = [
                    'tambah_berhasil' => 'Karyawan berhasil ditambahkan.',
                    'edit_berhasil' => 'Data karyawan berhasil diperbarui.',
                    'hapus_berhasil' => 'Karyawan berhasil dihapus.',
                    'tidak_valid' => 'Lengkapi seluruh data dengan benar.',
                    'gagal' => 'Proses gagal. Silakan coba kembali.'
                ];

                echo aman($pesan[$_GET['pesan']] ?? '');
                ?>
            </p>
        <?php endif; ?>

        <section class="toolbar">
            <input
                type="search"
                id="searchEmployee"
                placeholder="Cari nama karyawan..."
                aria-label="Cari nama karyawan"
            >

            <button
                type="button"
                class="btn-primary"
                onclick="document.getElementById('formKaryawan').hidden = false"
            >
                + Tambah Karyawan
            </button>
        </section>

        <!-- FORM TAMBAH DAN EDIT -->
        <section id="formKaryawan" class="data-section"
            <?php echo $modeEdit ? '' : 'hidden'; ?>>

            <h3><?php echo $modeEdit ? 'Edit Karyawan' : 'Tambah Karyawan'; ?></h3>

            <form method="POST" action="karyawan.php">
                <input type="hidden" name="aksi" value="simpan">
                <input type="hidden" name="id"
                    value="<?php echo aman($dataEdit['id']); ?>">

                <p>
                    <label>Nama Karyawan</label><br>
                    <input type="text" name="nama"
                        value="<?php echo aman($dataEdit['nama']); ?>"
                        required>
                </p>

                <p>
                    <label>Jabatan</label><br>
                    <input type="text" name="jabatan"
                        value="<?php echo aman($dataEdit['jabatan']); ?>"
                        required>
                </p>

                <p>
                    <label>Masa Kerja</label><br>
                    <input type="text" name="masa_kerja"
                        value="<?php echo aman($dataEdit['masa_kerja']); ?>"
                        placeholder="Contoh: 2 tahun"
                        required>
                </p>

                <p>
                    <label>Nomor HP</label><br>
                    <input type="text" name="no_hp"
                        value="<?php echo aman($dataEdit['no_hp']); ?>"
                        required>
                </p>

                <p>
                    <label>Status</label><br>
                    <select name="status" required>
                        <option value="Aktif"
                            <?php echo $dataEdit['status'] === 'Aktif' ? 'selected' : ''; ?>>
                            Aktif
                        </option>

                        <option value="Tidak Aktif"
                            <?php echo $dataEdit['status'] === 'Tidak Aktif' ? 'selected' : ''; ?>>
                            Tidak Aktif
                        </option>
                    </select>
                </p>

                <button type="submit" class="btn-primary">
                    <?php echo $modeEdit ? 'Simpan Perubahan' : 'Simpan Karyawan'; ?>
                </button>

                <a href="karyawan.php">Batal</a>
            </form>
        </section>

        <!-- TABEL KARYAWAN -->
        <section class="data-section">

            <div class="table-wrapper">
                <table class="activity-table employee-table">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Nama Karyawan</th>
                            <th>Jabatan</th>
                            <th>Masa Kerja</th>
                            <th>No. HP</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody id="employeeTable">

                        <?php if (empty($karyawan)): ?>
                            <tr>
                                <td colspan="7" class="empty-table">
                                    Belum ada data karyawan.
                                </td>
                            </tr>
                        <?php else: ?>

                            <?php foreach ($karyawan as $nomor => $item): ?>
                                <tr>
                                    <td><?php echo $nomor + 1; ?></td>
                                    <td><?php echo aman($item['nama'] ?? ''); ?></td>
                                    <td><?php echo aman($item['jabatan'] ?? ''); ?></td>
                                    <td><?php echo aman($item['masa_kerja'] ?? ''); ?></td>
                                    <td><?php echo aman($item['no_hp'] ?? ''); ?></td>
                                    <td><?php echo aman($item['status'] ?? ''); ?></td>
                                    <td>
                                        <a href="karyawan.php?edit=<?php echo (int) $item['id']; ?>">
                                            Edit
                                        </a>

                                        <form method="POST"
                                            action="karyawan.php"
                                            onsubmit="return confirm('Yakin ingin menghapus karyawan ini?');">

                                            <input type="hidden" name="aksi" value="hapus">
                                            <input type="hidden" name="id"
                                                value="<?php echo (int) $item['id']; ?>">

                                            <button type="submit">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>
                </table>
            </div>

            <p id="emptyMessage" hidden>
                Data karyawan tidak ditemukan.
            </p>

            <div class="table-footer">
                Total karyawan: <?php echo count($karyawan); ?> orang
            </div>

        </section>

        <footer class="footer">
            <span>PAYROLL MANAGEMENT SYSTEM</span>
            <span>2026</span>
        </footer>

    </main>
</div>

<script>
const searchInput = document.getElementById('searchEmployee');
const employeeTable = document.getElementById('employeeTable');
const emptyMessage = document.getElementById('emptyMessage');

searchInput.addEventListener('input', function () {
    const keyword = this.value.toLowerCase();
    const rows = employeeTable.querySelectorAll('tr');
    let visibleCount = 0;
    let hasData = false;

    rows.forEach(function (row) {
        if (row.querySelectorAll('td').length === 1) {
            return;
        }

        hasData = true;

        const cocok = row.textContent.toLowerCase().includes(keyword);
        row.hidden = !cocok;

        if (cocok) {
            visibleCount++;
        }
    });

    emptyMessage.hidden = !hasData || keyword === '' || visibleCount > 0;
});
</script>

</body>
</html>
