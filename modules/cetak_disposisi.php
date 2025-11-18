<?php
// FILE: cetak_disposisi.php (Telah disesuaikan untuk Firebase Realtime Database)
require __DIR__ . '/../vendor/autoload.php';
// Ganti include config/db.php dengan file koneksi Firebase Anda
require __DIR__ . '/../config/db.php'; 

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Mpdf\Mpdf;

// Pastikan koneksi Firebase tersedia
if (!isset($database)) {
    die("Koneksi database Firebase belum diinisialisasi. Cek config/db.php.");
}

// --------------------
// 1. Ambil ID surat (ID Firebase adalah string)
// --------------------
 $id = $_GET['id'] ?? null;
if (empty($id)) {
    die("ID surat tidak valid.");
}

// --------------------
// 2. Ambil data dari Firebase
// --------------------
try {
    // Ambil data spesifik berdasarkan key ($id)
    $ref = $database->getReference('surat_masuk/' . $id);
    $data = $ref->getValue();
} catch (\Exception $e) {
    die("Gagal mengambil data dari Firebase: " . $e->getMessage());
}

if ($data === null || !is_array($data)) {
    die("Data surat dengan ID '{$id}' tidak ditemukan.");
}

// Tambahkan ID ke array data untuk konsistensi
 $data['id'] = $id;

// --------------------
// 3. Tentukan aksi
// --------------------
 $action = $_GET['action'] ?? 'preview';

// --------------------
// 4. Load template Excel
// --------------------
 $templatePath = __DIR__ . "/templates/SM_Dispo.xlsx";
if (!file_exists($templatePath)) {
    die("Template tidak ditemukan: $templatePath");
}

 $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
 $spreadsheet = $reader->load($templatePath);
 $sheet = $spreadsheet->getActiveSheet();

// ==================================================
// 5. MAPPING DATA DAN PENYESUAIAN UKURAN DAN FONT
// ==================================================

// --- Pengaturan Ukuran Cetak untuk Excel ---
 $sheet->getPageSetup()->setPrintArea('A1:L40');
 $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT);
 $sheet->getPageSetup()->setFitToWidth(1);
 $sheet->getPageSetup()->setFitToHeight(0);

// --- Mapping Data ke Cell ---
// Pastikan menggunakan operator null coalescing (??) jika data di Firebase bisa null/kosong
 $sheet->setCellValue('B4', "LEMBAR DISPOSISI\n" . strtoupper($data['jenis_surat'] ?? ''));
 $sheet->setCellValue('L4', $data['tgl_terima'] ?? '');
 $sheet->setCellValue('L5', $data['kendali'] ?? '');
 $sheet->setCellValue('B9', ''); // Indek (Kosong di template)
 $sheet->setCellValue('K9', ''); // Kode (Kosong di template)
 $sheet->setCellValue('B10', $data['nomor_surat'] ?? '');  // No.Surat
 $sheet->setCellValue('K10', $data['tgl_surat'] ?? '');    // Tgl Surat
 $sheet->setCellValue('B14', $data['perihal'] ?? ''); 
 $sheet->setCellValue('J18', $data['asal_surat'] ?? ''); 
 $sheet->setCellValue('B18', ''); // Lampiran (Kosong di template)
 $sheet->setCellValue('L7', ''); // Batas Waktu (Kosong di template)

// --- Styling Excel ---
 $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(9);

// KUNCI: Pengaturan Font pada PhpSpreadsheet
 $sheet->getStyle('L5')->getFont()->setSize(12)->setBold(true); // No Agenda 
 $sheet->getStyle('B4')->getFont()->setSize(11)->setBold(true);  // JENIS SURAT 
 $sheet->getStyle('B7:L7')->getFont()->setBold(true); // SIFAT <--- PERBAIKAN DI SINI
 $sheet->getStyle('L4')->getFont()->setSize(10); // Tgl Terima 
 $sheet->getStyle('B10')->getFont()->setSize(10); // No.Surat 
 $sheet->getStyle('K10')->getFont()->setSize(10); // Tgl Surat 
 $sheet->getStyle('B14')->getFont()->setSize(10); // Isi Perihal 
 $sheet->getStyle('J18')->getFont()->setSize(10); // Asal Surat

// ==================================================
// 6. ROUTES
// ==================================================

// --------------------
// 6.1. Export Excel
// --------------------
if ($action === 'excel') {
    $filename = "SM_Dispo_" . $data['id'] . ".xlsx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header('Cache-Control: max-age=0');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// --------------------
// 6.2. Export PDF
// --------------------
if ($action === 'pdf') {
    $sheet->calculateColumnWidths(); 
    
    $writer = IOFactory::createWriter($spreadsheet, 'Html');
    ob_start();
    $writer->save('php://output');
    $html = ob_get_clean();

    $mpdf = new Mpdf([
        'format' => 'A5', 
        'default_font_size' => 9,
        'default_font' => 'arial'
    ]);
    
    $style = '
        <style>
            body { font-family: Arial, sans-serif; font-size: 9pt; margin: 0; }
            table { width: 100% !important; border-collapse: collapse; table-layout: fixed; }
            td, th { padding: 3px; border: 1px solid black; }
        </style>
    ';
    
    $mpdf->WriteHTML($style . $html);
    $mpdf->Output("SM_Dispo_{$data['id']}.pdf", "D");
    exit;
}

// --------------------
// 6.3. Preview HTML (Kode tetap)
// --------------------
if ($action === 'preview') {
    header("Content-Type: text/html; charset=UTF-8");
    ?>
    <style>
     body { font-family: Arial, sans-serif; font-size: 9pt; margin:0; padding:15px; }
     table { border-collapse: collapse; width: 100%; table-layout: fixed; font-size:9pt; }
     td, th {
       border: 1px solid #000;
       padding: 3px;
       vertical-align: top;
       word-wrap: break-word;
       white-space: normal;
       font-family: Arial, sans-serif;
       font-size: 9pt;
     }
     .center { text-align: center; }
     .bold { font-weight: bold; }
     .checkbox { width:14px; height:14px; border:1px solid #000; display:inline-block; }
     .agenda { font-size:12pt; font-weight:bold; } 
     .tanggal { font-size:12pt; font-weight:normal; } 
     .surat-info { font-size:10pt; font-weight:normal; }
     .jenis-surat { font-size:10pt; font-weight:bold; } 
     .pengolah-nama { font-size:10pt; font-weight:normal; } 

     
     @media print {
       body * { visibility: hidden !important; }
       #print-area, #print-area * { visibility: visible !important; }
       #print-area { 
         position: absolute; 
         top: 0; 
         left: 0; 
         width: 100%; 
         @page { margin: 0; size: A5; } 
       }
       .no-print { display: none !important; }
     }

     .no-print { margin: 10px 0; text-align: right; }
     .btn-print, .btn-back {
       display: inline-flex;
       align-items: center;
       gap: 6px;
       border: none;
       padding: 6px 12px;
       font-size: 14px;
       font-family: Arial, sans-serif;
       border-radius: 4px;
       cursor: pointer;
       transition: background 0.25s ease;
       margin-right: 8px;
     }
     .btn-print {
       background: #007bff; 
       color: #fff;
     }
     .btn-print:hover {
       background: #0056b3;
     }
     .btn-back {
       background: #6c757d; 
       color: #fff;
     }
     .btn-back:hover {
       background: #5a6268;
     }
     .btn-print svg, .btn-back svg {
       width: 16px;
       height: 16px;
       fill: currentColor;
     }

     /* Pengolah dan Simulasi A5 */
     .pengolah-table { border: 1px solid #000; border-collapse: collapse; width: 100%; table-layout: fixed; font-size:9pt; }
     .pengolah-table td { border: none; vertical-align: top; }
     .pengolah-header td { border-top: 1px solid #000; border-bottom: 1px solid #000; font-weight: bold; text-align: center; }
     .pengolah-col { border-left: 1px solid #000; padding: 6px; }
     .pengolah-col:first-child { border-left: none; border-right: 1px solid #000; }
     .pengolah-item { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
     .keterangan-item { display: flex; align-items: center; gap: 4px; margin-bottom: 6px; }
     
     #print-area { 
       width: 100%; 
       max-width: 680px; 
       margin: auto; 
       font-family: Arial, sans-serif;
     }
     
     </style>

     <div class="no-print">
       <button class="btn-back" onclick="window.history.back()">
         <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
           <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
         </svg>
         Kembali
       </button>
       <button class="btn-print" onclick="window.print()">
         <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
           <path d="M19 7h-1V3H6v4H5c-1.1 0-2 .9-2 2v6h4v4h10v-4h4v-6c0-1.1-.9-2-2-2zm-11-2h8v2H8V5zm8 12H8v-4h8v4z"/>
         </svg>
         Print
       </button>
     </div>

     <div id="print-area">
       <div class="center bold" style="font-size:11pt;">
         PEMERINTAH PROVINSI JAWA TENGAH <br>
         DINAS LINGKUNGAN HIDUP DAN KEHUTANAN
       </div>
       <br>

     <table>
       <tr>
         <td rowspan="3" class="bold center jenis-surat" style="width:38%; line-height:1.5;">
           LEMBAR DISPOSISI <br> <?=strtoupper($data['jenis_surat'] ?? '-')?>
         </td>
         <td rowspan="3" class="center bold" style="width:37%; font-size:9pt; line-height:1.5; vertical-align:middle;">
           DINAS LINGKUNGAN HIDUP DAN <br>
           KEHUTANAN PROVINSI JAWA <br>
           TENGAH
         </td>
         <td class="tanggal" style="width:30%">Tanggal : <?=$data['tgl_terima'] ?? '-'?></td>
       </tr>
       <tr>
         <td class="agenda">No.Ag : <?=$data['kendali'] ?? '-'?></td>
       </tr>
       <tr>
         <td>Batas Waktu Penyelesaian :</td>
       </tr>
     </table>

     <table>
       <tr>
         <td class="bold center" style="width:15%">SIFAT</td>
         <td class="center" style="width:21%">PENTING</td>
         <td class="center" style="width:21%">RAHASIA</td>
         <td class="center" style="width:21%">SEGERA</td>
         <td class="center" style="width:22%">Amat Segera</td>
       </tr>
     </table>

     <table>
       <tr>
         <td style="width:50%">Indek :</td>
         <td></td>
         <td style="width:50%"class="surat-info">Kode :</td>
       </tr>
       <tr>
         <td style="width:50%" class="surat-info">No.Surat : <?=$data['nomor_surat'] ?? '-'?></td>
         <td></td>
         <td style="width:30%" class="surat-info">Tgl Surat : <?=$data['tgl_surat'] ?? '-'?></td>
       </tr>
       <tr>
         <td colspan="3" style="height:100px;" class="surat-info">Isi : <br><br><?=$data['perihal'] ?? '-'?></td>
       </tr>
       <tr>
         <td style="width:40%">Lampiran :</td>
         <td colspan="2" style="width:60%" class="surat-info">Asal Surat : <?=$data['asal_surat'] ?? '-'?></td>
       </tr>
     </table>

     <table class="pengolah-table" style="margin-top:-1px;">
       <tr class="pengolah-header">
         <td colspan="2" class="center bold">PENGOLAH</td>
       </tr>
       <tr>
         <td class="pengolah-col" style="width:50%">
           <?php 
             $pengolah = [
               "1. Sdr. Sekretaris" => "",
               "2. Sdr. Kabid I (P2DPKLH)" => "untuk diselesaikan",
               "3. Sdr. Kabid II (PSLB3P2KLHK)" => "",
               "4. Sdr. Kabid III (PPH)" => "Harap saran / Pertimbangan",
               "5. Sdr. Kabid IV (PDAS KSDA)" => "",
               "6. Sdr. Kabid V (P2HLHK)" => "Untuk diketahui / dipergunakan seperlunya",
               "7. Sdr. Ka CDK …………." => "",
               "8. Sdr. Ka BPL2H" => "Bahas dengan saya",
               "9. Sdr. KaBalai Tahura Mangkunagoro I" => "",
               "10. Sdr. Ka BSPTH" => "",
               "11. Sdr. Ka BKR Baturraden" => ""
             ];
           ?>
           <?php 
           $count = 0;
           foreach($pengolah as $nama => $ket): 
             $count++;
             $style_kabid = ($count >= 2 && $count <= 6) ? 'font-size: 10pt;' : '';
           ?>
             <div class="pengolah-item pengolah-nama" style="height:15px; <?=$style_kabid?>">
               <span><?=$nama?></span>
               <span class="checkbox"></span>
             </div>
           <?php endforeach; ?>
         </td>
         <td class="pengolah-col" style="width:50%">
           <?php 
           $keterangan_map = [
               2 => "untuk diselesaikan",
               4 => "Harap saran / Pertimbangan",
               6 => "Untuk diketahui / dipergunakan seperlunya",
               8 => "Bahas dengan saya",
           ];
           
           for ($i = 1; $i <= 11; $i++) {
               $content = $keterangan_map[$i] ?? "";
               if (!empty($content)): ?>
                   <div class="keterangan-item" style="height:15px; margin-top:0;">
                       <span class="checkbox"></span><span><?=$content?></span>
                   </div>
               <?php else: ?>
                   <div class="keterangan-item" style="height:15px;">&nbsp;</div>
               <?php endif;
           }
           ?>
         </td>
       </tr>
     </table>

     <table style="margin-top:-1px;">
       <tr><td class="bold">Konsultasi dengan</td></tr>
       <tr><td>1. Sdr. …………………</td></tr>
       <tr><td>2. Sdr. …………………</td></tr>
     </table>

     <table style="margin-top:-1px;">
       <tr><td style="height:200px;">Catatan :</td></tr>
     </table>
     </div>
    <?php
    exit;
}

// --------------------
// 6.4. Default
// --------------------
echo "Action tidak dikenal!";