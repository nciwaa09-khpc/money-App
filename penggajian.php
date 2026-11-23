<?php

require_once __DIR__ . '/data/backendpenggajian.php';

function rupiah($angka)
{
    return "Rp " . number_format($angka, 0, ",", ".");
}

$daftarKaryawan = semuaKaryawan();

$daftarKaryawanAktif = array_values(array_filter(
    $daftarKaryawan,
    function ($item) {
        return ($item['status'] ?? '') === 'Aktif';
    }
));

$pesanError = '';
$dihitung = false;
$hasil = null;

$idKaryawan = '';
$namaKaryawan = '';
$jabatan = '';
$gajiPokok = 0;
$tunjangan = 0;
$lembur = 0;
$bonus = 0;
$potongan = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $idKaryawan = $_POST['id_karyawan'] ?? '';

    $lembur = max(0, (float) ($_POST['lembur'] ?? 0));
    $bonus = max(0, (float) ($_POST['bonus'] ?? 0));
    $potongan = max(0, (float) ($_POST['potongan'] ?? 0));

    $karyawanTerpilih = null;

    foreach ($daftarKaryawanAktif as $item) {
        if ((int) $item['id'] === (int) $idKaryawan) {
            $karyawanTerpilih = $item;
            break;
        }
    }

    if ($karyawanTerpilih === null) {
        $pesanError = 'Silakan pilih nama karyawan yang tersedia.';
    } else {

        $tarif = tarifJabatan($karyawanTerpilih['jabatan']);

        if ($tarif === null) {
            $pesanError = 'Tarif untuk jabatan karyawan belum tersedia.';
        } else {

            $namaKaryawan = $karyawanTerpilih['nama'];
            $jabatan = $karyawanTerpilih['jabatan'];

            $gajiPokok = $tarif['gaji_pokok'];
            $tunjangan = $tarif['tunjangan'];

            $data = [
                'id_karyawan' => (int) $idKaryawan,
                'nama' => $namaKaryawan,
                'jabatan' => $jabatan,
                'gaji_pokok' => $gajiPokok,
                'tunjangan' => $tunjangan,
                'lembur' => $lembur,
                'bonus' => $bonus,
                'potongan' => $potongan
            ];

            $berhasil = simpanPenggajian($data);

            if ($berhasil) {

                $hasil = [
                    'nama' => $namaKaryawan,
                    'jabatan' => $jabatan,
                    'gaji_pokok' => $gajiPokok,
                    'tunjangan' => $tunjangan,
                    'lembur' => $lembur,
                    'bonus' => $bonus,
                    'potongan' => $potongan,
                    'gaji_bersih' => hitungGaji(
                        $gajiPokok,
                        $tunjangan,
                        $lembur,
                        $bonus,
                        $potongan
                    )
                ];

                $dihitung = true;

            } else {
                $pesanError = 'Penggajian gagal disimpan. Silakan coba kembali.';
            }
        }
    }
}

$dataKaryawanJS = [];

foreach ($daftarKaryawanAktif as $item) {

    $tarif = tarifJabatan($item['jabatan']);

    if ($tarif !== null) {
        $dataKaryawanJS[] = [
            'id' => $item['id'],
            'nama' => $item['nama'],
            'jabatan' => $item['jabatan'],
            'gaji_pokok' => $tarif['gaji_pokok'],
            'tunjangan' => $tarif['tunjangan']
        ];
    }
}

?>




<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Penggajian | PayRoll</title>

    <link rel="stylesheet" href="css/style.css">

    <style>
        .payroll-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(280px, .8fr);
            gap: 24px;
            align-items: start;
        }

        .payroll-panel {
            background: #fff;
            border: 1px solid #e5e4de;
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .payroll-panel h2 {
            margin: 0 0 8px;
            color: #263633;
            font-size: 19px;
        }

        .panel-description {
            color: #777d76;
            font-size: 13px;
            margin: 0 0 24px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #343a35;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            box-sizing: border-box;
            padding: 12px 13px;
            border: 1px solid #dedfd8;
            border-radius: 8px;
            background: #fff;
            color: #252824;
            font: inherit;
            outline: none;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #526c5e;
        }

        .form-group input[readonly] {
            background: #f3f5f2;
            color: #526c5e;
            cursor: default;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0 16px;
        }

        .payroll-button {
            width: 100%;
            border: none;
            border-radius: 8px;
            padding: 13px;
            background: #263633;
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .payroll-button:hover {
            background: #3b5148;
        }

        .salary-result {
            background: #263633;
            color: white;
            padding: 24px;
            border-radius: 12px;
            margin: 20px 0;
        }

        .salary-result small {
            color: #d2ded5;
        }

        .salary-total {
            font-size: 28px;
            font-weight: 700;
            margin-top: 10px;
            overflow-wrap: anywhere;
        }

        .salary-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 13px 0;
            border-bottom: 1px solid #e9e9e3;
            font-size: 13px;
        }

        .salary-row span:last-child {
            text-align: right;
            font-weight: 600;
        }

        .salary-row.total {
            border-bottom: none;
            color: #263633;
            font-size: 15px;
            font-weight: 700;
        }

        .result-placeholder {
            color: #777d76;
            text-align: center;
            padding: 30px 10px;
            line-height: 1.7;
        }

        .result-placeholder .placeholder-icon {
            font-size: 36px;
            margin-bottom: 10px;
        }

        .notice-error {
            background: #fff1ee;
            color: #9b3427;
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 18px;
        }

        .print-button {
            width: 100%;
            padding: 12px;
            margin-top: 20px;
            border: 1px solid #263633;
            border-radius: 8px;
            background: white;
            color: #263633;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .print-button:hover {
            background: #f1f4f1;
        }

        .signature-area {
            display: none;
        }

        @media (max-width: 900px) {
            .payroll-layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 500px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .payroll-panel {
                padding: 18px;
            }
        }

        /* Tampilan khusus cetak slip gaji */
        @media print {

            @page {
                size: A4;
                margin: 20mm;
            }

            body {
                background: white !important;
            }

            .sidebar,
            .topbar,
            .page-heading,
            .footer,
            .payroll-layout > section:first-child,
            .payroll-layout > section:nth-child(2) > h2,
            .payroll-layout > section:nth-child(2) > .panel-description,
            .print-button {
                display: none !important;
            }

            .app-container,
            .main-content,
            .page-content,
            .payroll-layout {
                display: block !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
            }

            .payroll-panel {
                display: none !important;
                border: none !important;
            }

            .payroll-panel:last-child {
                display: block !important;
                width: 100% !important;
                padding: 20px !important;
                border: 1px solid #ccc !important;
                border-radius: 0 !important;
            }

            .salary-result {
                background: white !important;
                color: black !important;
                border: 1px solid #ccc !important;
            }

            .salary-result small {
                color: black !important;
            }

            .salary-total {
                color: black !important;
            }

            .salary-row {
                border-bottom: 1px solid #ddd !important;
            }

            .salary-row.total {
                border-top: 2px solid #263633 !important;
            }

            .signature-area {
                display: flex !important;
                justify-content: space-between;
                margin-top: 70px;
                text-align: center;
            }
        }
    </style>
</head>

<body>

<div class="app-container">

    <!-- SIDEBAR -->
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

            <a href="karyawan.php" class="menu-item">
                <span>♙</span> Data Karyawan
            </a>

            <a href="penggajian.php" class="menu-item active">
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

    <!-- KONTEN UTAMA -->
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
                <p class="eyebrow">MANAJEMEN PENGGAJIAN</p>

                <h1>Perhitungan Gaji</h1>

                <p class="welcome-description">
                    Masukkan rincian gaji untuk menghitung total gaji karyawan.
                </p>
            </div>
        </section>

        <?php if ($pesanError !== ''): ?>
            <div class="notice-error">
                <?php echo htmlspecialchars($pesanError, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <section class="page-content">

            <div class="payroll-layout">

                <!-- FORM PENGGAJIAN -->
                <section class="payroll-panel">

                    <h2>Data Penggajian</h2>

                    <p class="panel-description">
                        Pilih karyawan dan lengkapi komponen gajinya.
                    </p>

                    <?php if (empty($daftarKaryawanAktif)): ?>

                        <div class="notice-error">
                            Belum ada data karyawan aktif.
                            Silakan tambahkan karyawan terlebih dahulu di halaman Data Karyawan.
                        </div>

                    <?php else: ?>

                        <form method="POST" action="penggajian.php">

                            <div class="form-group">

                                <label for="nama_karyawan">
                                    Nama Karyawan
                                </label>

                                <input
                                    type="text"
                                    id="nama_karyawan"
                                    list="pilihan_karyawan"
                                    placeholder="Ketik nama karyawan..."
                                    autocomplete="off"
                                    value="<?php echo htmlspecialchars($namaKaryawan, ENT_QUOTES, 'UTF-8'); ?>"
                                    required
                                >

                                <datalist id="pilihan_karyawan">

                                    <?php foreach ($daftarKaryawanAktif as $item): ?>

                                        <option value="<?php echo htmlspecialchars($item['nama'], ENT_QUOTES, 'UTF-8'); ?>"></option>

                                    <?php endforeach; ?>

                                </datalist>

                                <input
                                    type="hidden"
                                    name="id_karyawan"
                                    id="id_karyawan"
                                    value="<?php echo htmlspecialchars((string) $idKaryawan, ENT_QUOTES, 'UTF-8'); ?>"
                                >

                            </div>

                            <div class="form-grid">

                                <div class="form-group">
                                    <label for="jabatan">Jabatan</label>

                                    <input
                                        type="text"
                                        id="jabatan"
                                        value="<?php echo htmlspecialchars($jabatan, ENT_QUOTES, 'UTF-8'); ?>"
                                        placeholder="Otomatis"
                                        readonly
                                    >
                                </div>

                                <div class="form-group">
                                    <label for="gaji_pokok">Gaji Pokok (Rp)</label>

                                    <input
                                        type="number"
                                        id="gaji_pokok"
                                        value="<?php echo $gajiPokok; ?>"
                                        placeholder="Otomatis"
                                        readonly
                                    >
                                </div>

                                <div class="form-group">
                                    <label for="tunjangan">Tunjangan (Rp)</label>

                                    <input
                                        type="number"
                                        id="tunjangan"
                                        value="<?php echo $tunjangan; ?>"
                                        placeholder="Otomatis"
                                        readonly
                                    >
                                </div>

                                <div class="form-group">
                                    <label for="lembur">Uang Lembur (Rp)</label>

                                    <input
                                        type="number"
                                        name="lembur"
                                        id="lembur"
                                        min="0"
                                        step="1000"
                                        value="<?php echo $lembur; ?>"
                                    >
                                </div>

                                <div class="form-group">
                                    <label for="bonus">Bonus (Rp)</label>

                                    <input
                                        type="number"
                                        name="bonus"
                                        id="bonus"
                                        min="0"
                                        step="1000"
                                        value="<?php echo $bonus; ?>"
                                    >
                                </div>

                                <div class="form-group">
                                    <label for="potongan">Potongan (Rp)</label>

                                    <input
                                        type="number"
                                        name="potongan"
                                        id="potongan"
                                        min="0"
                                        step="1000"
                                        value="<?php echo $potongan; ?>"
                                    >
                                </div>

                            </div>

                            <button type="submit" class="payroll-button">
                                Hitung dan Simpan Gaji
                            </button>

                        </form>

                    <?php endif; ?>

                </section>

                <!-- HASIL PERHITUNGAN -->
                <section class="payroll-panel">

                    <h2>Hasil Perhitungan</h2>

                    <p class="panel-description">
                        Ringkasan gaji bersih karyawan.
                    </p>

                    <?php if ($dihitung && $hasil !== null): ?>

                        <div class="salary-result">

                            <small>Total Gaji Bersih</small>

                            <div class="salary-total">
                                <?php echo rupiah($hasil['gaji_bersih']); ?>
                            </div>

                            <p>
                                <?php echo htmlspecialchars($hasil['nama'], ENT_QUOTES, 'UTF-8'); ?>
                            </p>

                        </div>

                        <div class="salary-row">
                            <span>Jabatan</span>
                            <span>
                                <?php echo htmlspecialchars($hasil['jabatan'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </div>

                        <div class="salary-row">
                            <span>Gaji Pokok</span>
                            <span><?php echo rupiah($hasil['gaji_pokok']); ?></span>
                        </div>

                        <div class="salary-row">
                            <span>Tunjangan</span>
                            <span><?php echo rupiah($hasil['tunjangan']); ?></span>
                        </div>

                        <div class="salary-row">
                            <span>Uang Lembur</span>
                            <span><?php echo rupiah($hasil['lembur']); ?></span>
                        </div>

                        <div class="salary-row">
                            <span>Bonus</span>
                            <span><?php echo rupiah($hasil['bonus']); ?></span>
                        </div>

                        <div class="salary-row">
                            <span>Potongan</span>
                            <span>- <?php echo rupiah($hasil['potongan']); ?></span>
                        </div>

                        <div class="salary-row total">
                            <span>Total Gaji</span>
                            <span><?php echo rupiah($hasil['gaji_bersih']); ?></span>
                        </div>

                        <!-- TOMBOL CETAK -->
                        <button
                            type="button"
                            class="print-button"
                            onclick="window.print()"
                        >
                            Cetak Slip Gaji
                        </button>

                        <!-- TANDA TANGAN -->
                        <div class="signature-area">

                            <div>
                                <p>Admin,</p>
                                <br><br><br>
                                <strong>(........................)</strong>
                            </div>

                            <div>
                                <p>Karyawan,</p>
                                <br><br><br>
                                <strong>
                                    (<?php echo htmlspecialchars($hasil['nama'], ENT_QUOTES, 'UTF-8'); ?>)
                                </strong>
                            </div>

                        </div>

                    <?php else: ?>

                        <div class="result-placeholder">

                            <div class="placeholder-icon">▤</div>

                            <strong>Belum Ada Hasil</strong>

                            <p>
                                Isi form penggajian di sebelah kiri,
                                lalu klik tombol Hitung dan Simpan Gaji.
                            </p>

                        </div>

                    <?php endif; ?>

                </section>

            </div>

        </section>

        <footer class="footer">
            PAYROLL MANAGEMENT SYSTEM <?php echo date('Y'); ?>
        </footer>

    </main>

</div>

<script>

// Data karyawan dan tarif gaji dari PHP
const daftarKaryawan = <?php
echo json_encode(
    $dataKaryawanJS,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
);
?>;

// Mengambil elemen form
const inputNama = document.getElementById('nama_karyawan');
const inputId = document.getElementById('id_karyawan');
const inputJabatan = document.getElementById('jabatan');
const inputGaji = document.getElementById('gaji_pokok');
const inputTunjangan = document.getElementById('tunjangan');

// Mencari data karyawan berdasarkan nama
function cariKaryawan() {

    const nama = inputNama.value.trim().toLowerCase();

    const karyawan = daftarKaryawan.find(item =>
        item.nama.trim().toLowerCase() === nama
    );

    if (karyawan) {

        inputId.value = karyawan.id;
        inputJabatan.value = karyawan.jabatan;
        inputGaji.value = karyawan.gaji_pokok;
        inputTunjangan.value = karyawan.tunjangan;

    } else {

        inputId.value = '';
        inputJabatan.value = '';
        inputGaji.value = '';
        inputTunjangan.value = '';

    }
}

inputNama.addEventListener('input', cariKaryawan);
inputNama.addEventListener('change', cariKaryawan);

// Validasi sebelum form dikirim
document.querySelector('form[action="penggajian.php"]')
    ?.addEventListener('submit', function(event) {

        cariKaryawan();

        if (inputId.value === '') {
            event.preventDefault();
            alert('Silakan pilih nama karyawan yang tersedia.');
        }

    });

</script>

</body>
</html>
