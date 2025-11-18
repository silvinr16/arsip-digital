<?php
// FILE: modules/cetak_tandaterima.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php'; // berisi inisialisasi $database (Firebase)
require_once __DIR__ . '/../middleware/auth.php';

// Pastikan koneksi Firebase aktif
if (!isset($database)) {
    die("Koneksi Firebase gagal. Periksa config/db.php");
}

// Ambil ID dari query string
$id = $_GET['id'] ?? '';
if (empty($id)) {
    die("ID surat tidak ditemukan.");
}

// --- Ambil data surat dari Firebase ---
$surat_ref = $database->getReference("surat_masuk/{$id}");
$surat_data = $surat_ref->getValue();

if (!$surat_data) {
    die("Data surat tidak ditemukan di Firebase.");
}

// --- Ambil data disposisi terkait surat ---
$disposisi_ref = $database->getReference("disposisi/{$id}");
$disposisi_data = $disposisi_ref->getValue();
?>

<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Kartu Surat Masuk - <?= htmlspecialchars($surat_data['kendali'] ?? '-') ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<style>
  @page { size: A4 portrait; margin: 10mm 12mm; }
  body { margin:0; padding:0; font-family:"Times New Roman", serif; font-size:11pt; -webkit-print-color-adjust:exact; }

  .sheet { width: calc(250mm - 70mm); margin: 8mm auto; }

  table { width:100%; border-collapse: collapse; table-layout: fixed; }
  td, th {
    border:1px solid #000;
    padding:5px;
    vertical-align: top;
    box-sizing:border-box;
    word-wrap: break-word;
    white-space: normal;
  }

  .center { text-align:center; }
  .bold { font-weight:700; }
  .indent { padding-left:10px; text-align:justify; }
  .vertical-text {
    writing-mode: vertical-rl;
    transform: rotate(180deg);
    text-align: center;
    font-weight: bold;
    font-size: 18pt;
  }

  .h-15mm { height:15mm; }
  .h-18mm { height:18mm; }
  .h-22mm { height:22mm; }

  .w-30 { width:25%; }
  .w-34 { width:20%; }

  .agenda-font { font-size: 16pt; }

  @media print { .no-print{display:none;} body { margin:0; } }
</style>
</head>

<body>
  <div class="sheet">
    <div class="no-print mb-3">
      <a href="../modules/disposisi.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Kembali
      </a>
      <button onclick="window.print()" class="btn btn-primary">
        <i class="bi bi-printer-fill"></i> Cetak
      </button>
    </div>

    <table>
      <colgroup>
        <col style="width:6%">
        <col class="w-30">
        <col class="w-30">
        <col class="w-34">
      </colgroup>

      <!-- Header atas -->
      <tr class="h-10mm">
        <td class="vertical-text" rowspan="11">KARTU SURAT MASUK</td>
        <td>Index:</td>
        <td>Kode: <?= htmlspecialchars($surat_data['jenis_surat'] ?? '-') ?></td>
        <td>No Urut Ag.: <span class="agenda-font"><?= htmlspecialchars($surat_data['kendali'] ?? '-') ?></span></td>
      </tr>

      <!-- Isi -->
      <tr class="h-22mm">
        <td colspan="3" class="indent">
          Isi: <?= htmlspecialchars($surat_data['perihal'] ?? '-') ?>
        </td>
      </tr>

      <!-- Dari -->
      <tr class="h-18mm">
        <td colspan="3">Dari: <?= htmlspecialchars($surat_data['asal_surat'] ?? '-') ?></td>
      </tr>

      <!-- Tanggal, Nomor, Lampiran -->
      <tr class="h-18mm center">
        <td>Tanggal Surat: <?= htmlspecialchars($surat_data['tgl_surat'] ?? '-') ?></td>
        <td>Nomor Surat: <?= htmlspecialchars($surat_data['nomor_surat'] ?? '-') ?></td>
        <td>Lampiran: </td>
      </tr>

      <!-- Pengolah -->
      <tr class="h-18mm">
        <td>Pengolah: <?= htmlspecialchars($surat_data['tgl_terima'] ?? '-') ?></td>
        <td class="center">Tgl diteruskan:</td>
        <td class="center">Tanda Terima:</td>
      </tr>

      <!-- Disposisi Kadis -->
      <tr class="h-15mm">
        <td colspan="3">Disposisi Kadis:</td>
      </tr>

      <!-- Disposisi Sekretaris -->
      <tr class="h-15mm">
        <td colspan="3">Disposisi Sek:</td>
      </tr>
    </table>
  </div>
</body>
</html>
