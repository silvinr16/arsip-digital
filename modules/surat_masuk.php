<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/auth.php'; 

if (!isset($database)) {
    http_response_code(500);
    die("Inisialisasi Firebase Realtime Database gagal. Cek config/db.php.");
}

// -----------------------------
// 2. FUNGSI UTILITY: Konversi File ke Base64 (MAX 500MB)
// -----------------------------
function handle_file_to_base64($file) {
    $max_size = 500 * 1024 * 1024; 
    
    if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['base64' => null, 'mime' => null, 'name' => null, 'error' => 'Tidak ada file diunggah atau error upload.'];
    }

    $tmp = $file['tmp_name'];
    $file_name = preg_replace('/[^A-Za-z0-9_\.-]/', '_', basename($file['name']));
    
    // Validasi Ukuran 
    if ($file['size'] > $max_size) {
        return ['base64' => null, 'mime' => null, 'name' => null, 'error' => 'Ukuran file melebihi batas 500MB.'];
    }
    
    // Ambil Konten dan Konversi ke Base64
    $file_mime = @mime_content_type($tmp); 
    $file_content = file_get_contents($tmp);

    if ($file_content === false) {
        return ['base64' => null, 'mime' => null, 'name' => null, 'error' => 'Gagal membaca isi file temporary.'];
    }

    $base64_data = base64_encode($file_content);
    
    return [
        'base64' => $base64_data, 
        'mime' => $file_mime, 
        'name' => $file_name,
        'error' => null
    ];
}

// -----------------------------
// 3. PENGATURAN DASAR & FETCH DATA
// -----------------------------
 $allowed_limits = [10, 25, 50, 100];
 $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
if (!in_array($limit, $allowed_limits, true)) $limit = 10;
 $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
 $search = trim($_GET['search'] ?? '');

 $ref = $database->getReference('surat_masuk');

// -----------------------------
// 4. LOGIKA CRUD (CREATE, DELETE, UPDATE)
// -----------------------------
// 4.1. CREATE (TAMBAH DATA BARU - DINAMIS)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
    
    $no_agenda    = trim($_POST['kendali'] ?? '');
    $jenis_surat  = trim($_POST['jenis_surat'] ?? '');
    $tgl_terima   = trim($_POST['tgl_terima'] ?? '');
    $asal_surat   = trim($_POST['asal_surat'] ?? '');
    $nomor_surat  = trim($_POST['nomor_surat'] ?? '');
    $tgl_surat    = trim($_POST['tgl_surat'] ?? '');
    $perihal      = mb_substr(trim($_POST['perihal'] ?? ''), 0, 10000); 
    $dispo_kadis  = trim($_POST['dispo_kadis'] ?? '') !== '' ? mb_substr(trim($_POST['dispo_kadis']),0,1000) : "-";
    $dispo_sekdin = trim($_POST['dispo_sekdin'] ?? '') !== '' ? mb_substr(trim($_POST['dispo_sekdin']),0,1000) : "-";
    $dispo_kabid  = trim($_POST['dispo_kabid'] ?? '') !== '' ? mb_substr(trim($_POST['dispo_kabid']),0,1000) : "-";
    
    if (empty($no_agenda) || empty($tgl_terima) || empty($asal_surat) || empty($nomor_surat)) {
        header("Location: surat_masuk.php?msg=error&detail=Input tidak lengkap&limit={$limit}");
        exit;
    }

    $file_data = handle_file_to_base64($_FILES['file'] ?? []);
    
    if ($file_data['error'] && !str_contains($file_data['error'], 'Tidak ada file')) {
        header("Location: surat_masuk.php?msg=error&detail=".urlencode($file_data['error'])."&limit={$limit}");
        exit;
    }

    $data = [
        'kendali'     => $no_agenda,
        'jenis_surat' => $jenis_surat,
        'tgl_terima'  => $tgl_terima,
        'asal_surat'  => $asal_surat,
        'nomor_surat' => $nomor_surat,
        'tgl_surat'   => $tgl_surat,
        'perihal'     => $perihal,
        'dispo_kadis' => $dispo_kadis,
        'dispo_sekdin' => $dispo_sekdin,
        'dispo_kabid' => $dispo_kabid,
        'file_base64' => $file_data['base64'],
        'file_mime'   => $file_data['mime'],
        'file_name'   => $file_data['name'], 
        'created_at'  => date('Y-m-d H:i:s'),
    ];

    try {
        // PUSH DATA BARU KE FIREBASE (INILAH YANG MEMBUATNYA DINAMIS)
        $database->getReference('surat_masuk')->push($data);
        header("Location: surat_masuk.php?msg=added&limit={$limit}&page={$page}");
    } catch (Exception $e) {
        error_log("Gagal simpan ke database: " . $e->getMessage());
        header("Location: surat_masuk.php?msg=error&detail=Gagal simpan ke database (Cek ukuran data!)&limit={$limit}");
    }
    exit;
}

  
// 4.2. DELETE
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $ref->getChild($id)->remove();
    $msg_status = 'deleted';
    header("Location: surat_masuk.php?msg={$msg_status}&limit={$limit}&page={$page}");
    exit;
}

// 4.3. UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id           = trim($_POST['id'] ?? null);
    if ($id === null) {
        header("Location: surat_masuk.php?msg=error&detail=ID tidak ditemukan");
        exit;
    }

    $no_agenda    = trim($_POST['kendali'] ?? '');
    $jenis_surat  = trim($_POST['jenis_surat'] ?? '');
    $tgl_terima   = trim($_POST['tgl_terima'] ?? '');
    $asal_surat   = trim($_POST['asal_surat'] ?? '');
    $nomor_surat  = trim($_POST['nomor_surat'] ?? '');
    $tgl_surat    = trim($_POST['tgl_surat'] ?? '');
    $perihal      = mb_substr(trim($_POST['perihal'] ?? ''), 0, 10000);
    $dispo_kadis  = trim($_POST['dispo_kadis'] ?? '') !== '' ? mb_substr(trim($_POST['dispo_kadis']),0,1000) : "-";
    $dispo_sekdin = trim($_POST['dispo_sekdin'] ?? '') !== '' ? mb_substr(trim($_POST['dispo_sekdin']),0,1000) : "-";
    $dispo_kabid  = trim($_POST['dispo_kabid'] ?? '') !== '' ? mb_substr(trim($_POST['dispo_kabid']),0,1000) : "-";

    $updateData = [
        'kendali' => $no_agenda,
        'jenis_surat' => $jenis_surat,
        'tgl_terima' => $tgl_terima,
        'asal_surat' => $asal_surat,
        'nomor_surat' => $nomor_surat,
        'tgl_surat' => $tgl_surat,
        'perihal' => $perihal,
        'dispo_kadis' => $dispo_kadis,
        'dispo_sekdin' => $dispo_sekdin,
        'dispo_kabid' => $dispo_kabid,
        'updated_at' => date('Y-m-d H:i:s')
    ];

    if (!empty($_FILES['file']['name']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $file_data = handle_file_to_base64($_FILES['file']);

        if ($file_data['error'] && !str_contains($file_data['error'], 'Tidak ada file')) {
            header("Location: surat_masuk.php?msg=error&detail=".urlencode($file_data['error'])."&limit={$limit}&page={$page}");
            exit;
        }

        if ($file_data['base64']) {
            $updateData['file_base64'] = $file_data['base64'];
            $updateData['file_mime']   = $file_data['mime'];
            $updateData['file_name']   = $file_data['name']; 
        }
    }
  
    try {
        $ref->getChild($id)->update($updateData);
        header("Location: surat_masuk.php?msg=updated&limit={$limit}&page={$page}");
    } catch (Exception $e) {
        error_log(" Gagal update ke database: " . $e->getMessage());
        header("Location: surat_masuk.php?msg=error&detail=Gagal update ke database (Cek ukuran data!)&limit={$limit}&page={$page}");
    }
    exit;
}

// -----------------------------
// 5. PEMROSESAN DATA UNTUK TAMPILAN (READ)
// -----------------------------
 $all = $ref->getValue(); 
 $items = [];

if (is_array($all)) {
    foreach ($all as $key => $val) {
        if (!is_array($val)) continue;
        $item = $val;
        $item['id'] = $key;
        // Normalisasi
        $item['kendali'] = $item['kendali'] ?? '';
        $item['jenis_surat'] = $item['jenis_surat'] ?? '';
        $item['asal_surat'] = $item['asal_surat'] ?? '';
        $item['nomor_surat'] = $item['nomor_surat'] ?? '';
        $item['perihal'] = $item['perihal'] ?? '';
        $item['dispo_kadis'] = $item['dispo_kadis'] ?? '-';
        $item['dispo_sekdin'] = $item['dispo_sekdin'] ?? '-';
        $item['dispo_kabid'] = $item['dispo_kabid'] ?? '-';
        $item['file_base64'] = $item['file_base64'] ?? null;
        $item['file_mime'] = $item['file_mime'] ?? null;
        $item['file_name'] = $item['file_name'] ?? 'File Dokumen'; 
        $item['created_at'] = $item['created_at'] ?? null;
        $items[] = $item;
    }
}

// Sort (Terbaru ke Terlama)
usort($items, function($a, $b){
    $ta = strtotime($a['created_at'] ?? '1970-01-01 00:00:00');
    $tb = strtotime($b['created_at'] ?? '1970-01-01 00:00:00');
    return $tb <=> $ta;
});

// Search
if ($search !== '') {
    $filtered = [];
    $search_lower = strtolower($search);
    foreach ($items as $it) {
        $haystack = strtolower(implode(' ', [
            $it['kendali'], $it['jenis_surat'], $it['asal_surat'], $it['nomor_surat'],
            $it['perihal'], $it['dispo_kadis'], $it['dispo_sekdin'], $it['dispo_kabid'],
            $it['tgl_terima'] ?? '', $it['tgl_surat'] ?? ''
        ]));
        if (strpos($haystack, $search_lower) !== false) {
            $filtered[] = $it;
        }
    }
    $items = $filtered;
}

 $total_rows = count($items);
 $total_pages = max(1, (int)ceil($total_rows / $limit));
if ($page > $total_pages) $page = $total_pages;
 $offset = ($page - 1) * $limit;
 $surat_page = array_slice($items, $offset, $limit);


// -------------------------------------------------
// 6. TAMPILAN: HTML
// -------------------------------------------------
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
?>

<div class="container-fluid px-0">
  <!-- Card Header -->
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white py-3">
      <div class="row align-items-center">
        <div class="col-md-6">
          <h4 class="mb-0"><i class="bi bi-envelope-fill me-2"></i>Surat Masuk</h4>
        </div>
        <div class="col-md-6 text-md-end">
          <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="bi bi-plus-circle me-1"></i>Tambah Data
          </button>
        </div>
      </div>
    </div>
    <div class="card-body bg-light">
      <div class="row align-items-center">
        <div class="col-md-8">
          <div class="d-flex align-items-center gap-3 flex-wrap">
            <!-- Search Form -->
            <form method="get" class="d-flex" role="search">
              <input type="hidden" name="limit" value="<?= htmlspecialchars($limit) ?>">
              <div class="input-group input-group-sm">
                <input type="text" name="search" value="<?= htmlspecialchars($search ?? '', ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="Cari...">
                <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
              </div>
            </form>
            
            <!-- Limit Data Form -->
            <form method="get" class="d-flex align-items-center gap-2">
              <input type="hidden" name="search" value="<?= htmlspecialchars($search ?? '', ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="page" value="1">
              <label class="small text-muted m-0 fw-semibold">Tampilkan</label>
              <select name="limit" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <?php foreach ($allowed_limits as $opt): ?>
                  <option value="<?= htmlspecialchars($opt) ?>" <?= ($limit == $opt) ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
                <?php endforeach; ?>
              </select>
              <span class="small text-muted">data per halaman</span>
            </form>
          </div>
        </div>
        <div class="col-md-4 text-md-end mt-2 mt-md-0">
          <!-- Total Data dipindahkan ke sini -->
        </div>
      </div>
    </div>
  </div>

  <?php if (isset($_GET['msg'])):
    $msg = $_GET['msg'];
    $class = ($msg === 'deleted') ? 'danger' : (($msg === 'error') ? 'danger' : 'success');
    $text  = ($msg === 'added') ? 'Data berhasil ditambahkan' : (($msg === 'updated') ? 'Data berhasil diperbarui' : (($msg === 'deleted') ? 'Data berhasil dihapus' : (($msg === 'error') ? ('Gagal! ' . htmlspecialchars($_GET['detail'] ?? 'Terjadi kesalahan.') ) : '')));
  ?>
    <div id="flash-message" class="alert alert-<?= $class ?> text-center small px-3 py-2 shadow-sm">
      <?= $text ?>
    </div>
  <?php endif; ?>

  <style>
  #flash-message {
    position: fixed; top: 18%; left: 50%; transform: translateX(-50%); min-width: 280px; z-index: 1055; border-radius: 8px; opacity: 0.95; animation: fadeOut 4s forwards;
  }
  @keyframes fadeOut { 0% { opacity: 1; } 70% { opacity: 1; } 100% { opacity: 0; display: none; } }
  </style>

  <div class="card shadow-sm">
    <div class="card-header bg-light py-2">
      <div class="row align-items-center">
        <div class="col-md-6">
          <h5 class="mb-0 text-primary"><i class="bi bi-table me-2"></i>Data Surat Masuk</h5>
        </div>
        <div class="col-md-6 text-md-end">
          <div class="badge bg-primary p-2">
            <i class="bi bi-database me-1"></i>
            Total Data: <span class="fw-bold"><?= $total_rows ?></span>
          </div>
        </div>
      </div>
    </div>
    <div class="card-body p-2">
      <div class="table-responsive">
        <table class="table table-sm table-striped table-bordered align-middle" style="font-size:13px; table-layout:fixed; width:100%;">
          <thead class="table-primary text-center fw-semibold">
            <tr>
              <th style="width:30px;">No</th>
              <th style="width:70px;">Kendali</th>
              <th style="width:90px;">Jenis Surat</th>
              <th style="width:70px;">Tgl Terima</th>
              <th style="width:110px;">Asal Surat</th>
              <th style="width:140px;">No Surat</th>
              <th style="width:70px;">Tgl Surat</th>
              <th style="width:220px;">Perihal</th>
              <th style="width:80px;">Kadis</th>
              <th style="width:80px;">Sekdin</th>
              <th style="width:80px;">Kabid</th>
              <th style="width:40px;">File</th>
              <th style="width:70px;">Aksi</th>
            </tr>
          </thead>
          <tbody class="fw-semibold">
            <?php $no = $offset+1; if (!empty($surat_page)): ?>
              <?php foreach ($surat_page as $row): ?>
                <tr>
                  <td class="text-center"><?= htmlspecialchars($no++); ?></td>
                  <td><?= htmlspecialchars($row['kendali'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?= htmlspecialchars($row['jenis_surat'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                  <td class="text-center"><?= htmlspecialchars($row['tgl_terima'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                  <td class="text-start"><?= htmlspecialchars($row['asal_surat'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                  <td class="text-start"><?= htmlspecialchars($row['nomor_surat'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                  <td class="text-center"><?= htmlspecialchars($row['tgl_surat'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                  <td class="text-start"><?= htmlspecialchars($row['perihal'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                  <td class="text-start"><?= htmlspecialchars($row['dispo_kadis'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                  <td class="text-start"><?= htmlspecialchars($row['dispo_sekdin'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                  <td class="text-start"><?= htmlspecialchars($row['dispo_kabid'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                  <td class="text-center">
                    <?php if (!empty($row['file_base64'])): 
                      $data_uri = 'data:' . htmlspecialchars($row['file_mime'] ?? 'application/octet-stream') . ';base64,' . $row['file_base64'];
                    ?>
                      <a href="#" class="btn btn-sm btn-primary preview-btn" 
                         data-file="<?= htmlspecialchars($data_uri); ?>" 
                         data-mime="<?= htmlspecialchars($row['file_mime'] ?? 'application/octet-stream'); ?>"
                         data-name="<?= htmlspecialchars($row['file_name'] ?? 'dokumen', ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="bi bi-eye"></i>
                      </a>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <div class="btn-group" role="group">
                      <button class="btn btn-sm btn-warning me-1" data-bs-toggle="modal" data-bs-target="#modalEdit<?= htmlspecialchars($row['id']) ?>" title="Edit">
                        <i class="bi bi-pencil"></i>
                      </button>
                      <a href="?delete=<?= htmlspecialchars($row['id']) ?>&page=<?= htmlspecialchars($page) ?>&limit=<?= htmlspecialchars($limit) ?>&search=<?= urlencode($search) ?>" 
                         onclick="return confirm('Hapus data?')" class="btn btn-sm btn-danger" title="Hapus">
                        <i class="bi bi-trash"></i>
                      </a>
                    </div>
                  </td>
                </tr>

            <!-- Modal Edit Surat Masuk -->
            <div class="modal fade" id="modalEdit<?= htmlspecialchars($row['id']) ?>" tabindex="-1" aria-labelledby="modalEditLabel<?= htmlspecialchars($row['id']) ?>" aria-hidden="true">
              <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content shadow border-0 rounded-3">

                  <!-- HEADER -->
                  <div class="modal-header py-2 bg-primary text-white rounded-top">
                    <h6 class="modal-title fw-semibold d-flex align-items-center mb-0">
                      <i class="bi bi-envelope-paper me-2"></i> Edit Surat Masuk
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                  </div>

                  <!-- FORM -->
                  <form method="post" enctype="multipart/form-data">
                    <div class="modal-body bg-light">
                      <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">

                      <!-- CARD: INFORMASI SURAT -->
                      <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body">
                          <h6 class="fw-bold text-primary mb-3"><i class="bi bi-info-circle me-1"></i> Informasi Surat</h6>
                          <div class="row g-3">
                            <div class="col-md-6">
                              <label class="form-label small">Kendali</label>
                              <input type="text" name="kendali" value="<?= htmlspecialchars($row['kendali'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-6">
                              <label class="form-label small">Jenis Surat</label>
                              <input type="text" name="jenis_surat" value="<?= htmlspecialchars($row['jenis_surat'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-6">
                              <label class="form-label small">Tanggal Terima</label>
                              <input type="text" name="tgl_terima" value="<?= htmlspecialchars($row['tgl_terima'] ?? '') ?>" class="form-control form-control-sm" placeholder="Contoh: 14 Oktober 2025" required>
                            </div>
                            <div class="col-md-6">
                              <label class="form-label small">Tanggal Surat</label>
                              <input type="text" name="tgl_surat" value="<?= htmlspecialchars($row['tgl_surat'] ?? '') ?>" class="form-control form-control-sm" placeholder="Contoh: 10 Oktober 2025" required>
                            </div>
                            <div class="col-md-6">
                              <label class="form-label small">Asal Surat</label>
                              <input type="text" name="asal_surat" value="<?= htmlspecialchars($row['asal_surat'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-6">
                              <label class="form-label small">Nomor Surat</label>
                              <input type="text" name="nomor_surat" value="<?= htmlspecialchars($row['nomor_surat'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-12">
                              <label class="form-label small">Perihal</label>
                              <textarea name="perihal" class="form-control form-control-sm" rows="2" required><?= htmlspecialchars($row['perihal'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- CARD: DISPOSISI -->
                      <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body">
                          <h6 class="fw-bold text-primary mb-3"><i class="bi bi-person-lines-fill me-1"></i> Disposisi</h6>
                          <div class="row g-3">
                            <div class="col-md-4">
                              <label class="form-label small">Dispo Kadis</label>
                              <input type="text" name="dispo_kadis" value="<?= htmlspecialchars($row['dispo_kadis'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-4">
                              <label class="form-label small">Dispo Sekdin</label>
                              <input type="text" name="dispo_sekdin" value="<?= htmlspecialchars($row['dispo_sekdin'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-4">
                              <label class="form-label small">Dispo Kabid</label>
                              <input type="text" name="dispo_kabid" value="<?= htmlspecialchars($row['dispo_kabid'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>" class="form-control form-control-sm">
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- CARD: FILE LAMPIRAN -->
                      <div class="card border-0 shadow-sm">
                        <div class="card-body">
                          <h6 class="fw-bold text-primary mb-3"><i class="bi bi-paperclip me-1"></i> File Lampiran</h6>
                          <div class="row g-3">
                            <div class="col-12">
                              <label class="form-label small">File (max 500 MB)</label>
                              <input type="file" name="file" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                              <?php if (!empty($row['file_base64'])): 
                                $data_uri = 'data:' . htmlspecialchars($row['file_mime'] ?? 'application/octet-stream') . ';base64,' . $row['file_base64'];
                                $unique_id = 'file_' . uniqid();
                              ?>
                                <div class="mt-2 p-2 border rounded bg-light">
                                    <small class="text-muted d-block mb-2">
                                        <i class="bi bi-file-earmark-text me-1"></i>
                                        File saat ini: <strong><?= htmlspecialchars($row['file_name'] ?? 'dokumen', ENT_QUOTES, 'UTF-8'); ?></strong>
                                    </small>
                                    <button type="button" class="btn btn-outline-primary btn-sm" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalPreviewEdit<?= htmlspecialchars($unique_id) ?>">
                                        <i class="bi bi-eye"></i> Lihat File
                                    </button>
                                </div>
                              <?php endif; ?>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- FOOTER -->
                    <div class="modal-footer py-2 bg-white border-0 rounded-bottom">
                      <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i> Batal
                      </button>
                      <button type="submit" name="update" class="btn btn-success btn-sm px-3">
                        <i class="bi bi-save me-1"></i> Simpan Perubahan
                      </button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
            
            <!-- Modal Preview File di dalam Modal Edit -->
            <?php if (!empty($row['file_base64'])): ?>
              <div class="modal fade" id="modalPreviewEdit<?= htmlspecialchars($unique_id) ?>" tabindex="-1">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                  <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
                    
                    <div class="modal-header py-2 px-3 bg-primary text-white d-flex justify-content-between align-items-center rounded-top-3">
                      <h6 class="modal-title d-flex align-items-center gap-2 m-0">
                        <i class="bi bi-file-earmark"></i>
                        <span><?= htmlspecialchars($row['file_name'] ?? 'dokumen', ENT_QUOTES, 'UTF-8'); ?></span>
                      </h6>
                      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body p-0 bg-dark d-flex justify-content-center align-items-center rounded-bottom-3" style="height:80vh; overflow:hidden;">
                      <div class="w-100 h-100 d-flex justify-content-center align-items-center text-center text-white">
                        <?php 
                          $file_mime = $row['file_mime'] ?? 'application/octet-stream';
                          
                          if ($file_mime == 'application/pdf') {
                              echo '<iframe src="' . htmlspecialchars($data_uri) . '" width="100%" height="100%" style="border:none;"></iframe>';
                          } else if (strpos($file_mime, 'image/') === 0) {
                              echo '<img src="' . htmlspecialchars($data_uri) . '" style="max-width:100%; max-height:100%; object-fit:contain;">';
                          } else {
                              echo '<div class="text-center p-4">';
                              echo '<i class="bi bi-file-earmark-code" style="font-size: 3rem;"></i>';
                              echo '<p class="mt-3">Tipe file ini tidak dapat ditampilkan di browser.</p>';
                              echo '</div>';
                          }
                        ?>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            <?php endif; ?>
            
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="13" class="text-center text-muted">Tidak ada data.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="card-footer bg-light py-2">
      <div class="row align-items-center">
        <div class="col-md-6">
          <p class="small text-muted mb-0">
          <i class="bi bi-info-circle me-1"></i>
            Menampilkan <?= ($total_rows==0) ? 0 : ($offset+1) ?> - <?= min($offset + $limit, $total_rows) ?> dari <?= $total_rows ?> data
          </p>
        </div>
        <div class="col-md-6">
          <nav>
            <ul class="pagination pagination-sm justify-content-md-end mb-0">
              <?php 
              $search_url = urlencode($search);
              if ($page > 1): ?>
                <li class="page-item"><a class="page-link" href="?page=1&limit=<?= htmlspecialchars($limit) ?>&search=<?= $search_url ?>">« First</a></li>
                <li class="page-item"><a class="page-link" href="?page=<?= htmlspecialchars($page-1) ?>&limit=<?= htmlspecialchars($limit) ?>&search=<?= $search_url ?>">‹ Prev</a></li>
              <?php endif; ?>

              <?php
              $range = 2;
              $start = max(1, $page - $range);
              $end   = min($total_pages, $page + $range);

              if ($start > 1) {
                  echo '<li class="page-item"><a class="page-link" href="?page=1&limit='.htmlspecialchars($limit).'&search='.$search_url.'">1</a></li>';
                  if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
              }

              for ($i=$start; $i<=$end; $i++): ?>
                <li class="page-item <?= ($i==$page)?'active':'' ?>">
                  <a class="page-link" href="?page=<?= htmlspecialchars($i) ?>&limit=<?= htmlspecialchars($limit) ?>&search=<?= $search_url ?>"><?= htmlspecialchars($i) ?></a>
                </li>
              <?php endfor;

              if ($end < $total_pages) {
                  if ($end < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                  echo '<li class="page-item"><a class="page-link" href="?page='.htmlspecialchars($total_pages).'&limit='.htmlspecialchars($limit).'&search='.$search_url.'">'.htmlspecialchars($total_pages).'</a></li>';
              }
              ?>

              <?php if ($page < $total_pages): ?>
                <li class="page-item"><a class="page-link" href="?page=<?= htmlspecialchars($page+1) ?>&limit=<?= htmlspecialchars($limit) ?>&search=<?= $search_url ?>">Next ›</a></li>
                <li class="page-item"><a class="page-link" href="?page=<?= htmlspecialchars($total_pages) ?>&limit=<?= htmlspecialchars($limit) ?>&search=<?= $search_url ?>">Last »</a></li>
              <?php endif; ?>
            </ul>
          </nav>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="previewModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
      
      <div class="modal-header py-2 px-3 bg-primary text-white d-flex justify-content-between align-items-center rounded-top-3">
        <h6 class="modal-title d-flex align-items-center gap-2 m-0" id="previewModalLabel">
          <i id="fileIcon" class="bi bi-file-earmark"></i>
          <span id="fileTitle">Preview File</span>
        </h6>
        <div class="d-flex align-items-center gap-2">
          <a id="btnDownload" class="btn btn-sm btn-light rounded-circle shadow-sm" title="Download File" download>
            <i class="bi bi-download"></i>
          </a>
          <button class="btn btn-sm btn-light rounded-circle shadow-sm" id="btnZoomIn" title="Zoom In" style="display:none;">
            <i class="bi bi-zoom-in"></i>
          </button>
          <button class="btn btn-sm btn-light rounded-circle shadow-sm" id="btnZoomOut" title="Zoom Out" style="display:none;">
            <i class="bi bi-zoom-out"></i>
          </button>
          <button class="btn btn-sm btn-light rounded-circle shadow-sm" id="btnFullscreen" title="Fullscreen" style="display:none;">
            <i class="bi bi-arrows-fullscreen"></i>
          </button>
          <button type="button" class="btn-close btn-close-white ms-1" data-bs-dismiss="modal"></button>
        </div>
      </div>

      <div class="modal-body p-0 bg-dark d-flex justify-content-center align-items-center rounded-bottom-3" style="height:90vh; overflow:hidden;">
        <div id="previewArea" class="w-100 h-100 d-flex justify-content-center align-items-center text-center text-white">
          <p class="text-muted">Loading...</p>
        </div>

        <div id="unsupportedCard" class="card text-center shadow-lg position-absolute top-50 start-50 translate-middle" style="width: 300px; display:none;">
          <div class="card-body">
            <i class="bi bi-exclamation-octagon-fill text-danger mb-3" style="font-size: 3rem;"></i>
            <h5 class="card-title">File Tidak Dapat Ditampilkan</h5>
            <p class="card-text small text-muted">Tipe file ini (<span id="unsupportedMime"></span>) tidak didukung oleh browser. Silakan unduh untuk melihat isinya.</p>
            <a id="btnDownloadCard" class="btn btn-primary mt-2" download>
              <i class="bi bi-download me-1"></i> Download File
            </a>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener("click", function (e) {
  const btn = e.target.closest(".preview-btn");
  if (!btn) return;
  e.preventDefault();

  const dataUri = btn.getAttribute("data-file");
  const fileMime = btn.getAttribute("data-mime");
  const fileName = btn.getAttribute("data-name");
  if (!dataUri) return alert("File (Base64) tidak ditemukan.");

  const previewModalEl = document.getElementById("previewModal");
  const previewModal = new bootstrap.Modal(previewModalEl);
  const previewArea = document.getElementById("previewArea");
  const unsupportedCard = document.getElementById("unsupportedCard");
  const unsupportedMime = document.getElementById("unsupportedMime");
  const fileIcon = document.getElementById("fileIcon");
  const fileTitle = document.getElementById("fileTitle");
  const btnDownload = document.getElementById("btnDownload");
  const btnDownloadCard = document.getElementById("btnDownloadCard");
  const btnZoomIn = document.getElementById("btnZoomIn");
  const btnZoomOut = document.getElementById("btnZoomOut");
  const btnFullscreen = document.getElementById("btnFullscreen");
  
  // Reset visibility
  btnZoomIn.style.display = 'none';
  btnZoomOut.style.display = 'none';
  btnFullscreen.style.display = 'none';
  unsupportedCard.style.display = 'none';
  previewArea.style.display = 'flex';
  
  let zoomLevel = 1;
  let content = "";
  let iconClass = "bi bi-file-earmark";
  let titleText = fileName;

  // Set umum untuk download
  btnDownload.href = dataUri; 
  btnDownload.setAttribute('download', fileName);
  btnDownloadCard.href = dataUri; 
  btnDownloadCard.setAttribute('download', fileName);

  const supportedMimes = {
    pdf: 'application/pdf',
    jpg: 'image/jpeg',
    png: 'image/png',
    gif: 'image/gif',
    webp: 'image/webp',
    bmp: 'image/bmp'
  };

  if (fileMime.startsWith("image/")) {
    content = `<img src="${dataUri}" id="previewContent" style="max-width:100%; max-height:100%; object-fit:contain; transition: transform 0.3s ease;">`;
    iconClass = "bi bi-file-image";
    btnZoomIn.style.display = '';
    btnZoomOut.style.display = '';
    btnFullscreen.style.display = '';

  } else if (fileMime === supportedMimes.pdf) {
    content = `<iframe id="previewContent" src="${dataUri}" width="100%" height="100%" style="border:none;"></iframe>`;
    iconClass = "bi bi-file-pdf";
    btnFullscreen.style.display = '';
  
  } else {
    // File Tidak Didukung
    content = `<p class="text-white text-center">Tipe file tidak didukung oleh preview.</p>`;
    previewArea.style.display = 'none';
    unsupportedCard.style.display = 'flex';
    unsupportedMime.textContent = fileMime;
    iconClass = "bi bi-file-earmark-code";
  }
  
  previewArea.innerHTML = content;
  fileIcon.className = iconClass;
  fileTitle.textContent = titleText;


  // Fungsionalitas Zoom (Hanya untuk Gambar)
  const resetZoom = () => {
      zoomLevel = 1;
      const el = document.getElementById("previewContent");
      if (el && el.tagName === 'IMG') {
          el.style.transform = `scale(${zoomLevel})`;
          el.style.transformOrigin = "center center";
      }
  };
  // Reset zoom saat modal ditutup
  previewModalEl.addEventListener('hidden.bs.modal', resetZoom);
  
  btnZoomIn.onclick = () => {
    const el = document.getElementById("previewContent");
    if (el && el.tagName === 'IMG') {
      zoomLevel += 0.1;
      el.style.transform = `scale(${zoomLevel})`;
    }
  };
  btnZoomOut.onclick = () => {
    const el = document.getElementById("previewContent");
    if (el && el.tagName === 'IMG') {
      zoomLevel = Math.max(0.5, zoomLevel - 0.1);
      el.style.transform = `scale(${zoomLevel})`;
    }
  };
  btnFullscreen.onclick = () => document.getElementById("previewContent")?.requestFullscreen();

  previewModal.show();
});
</script>


<!-- Modal Tambah Surat Masuk -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-3">
      <div class="modal-header py-3 bg-success text-white rounded-top">
        <h5 class="modal-title fw-semibold d-flex align-items-center mb-0" id="modalTambahLabel">
          <i class="bi bi-envelope-paper me-2"></i> Tambah Surat Masuk
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form method="post" enctype="multipart/form-data" class="h-100">
        <div class="modal-body bg-light" style="max-height: calc(100vh - 180px); overflow-y: auto;">
          <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <div class="border rounded-3 p-3 bg-white h-100">
                    <h6 class="fw-bold mb-3 text-success"><i class="bi bi-info-circle me-1"></i> Informasi Surat</h6>
                    <div class="mb-2">
                      <label class="form-label small mb-1">Kendali</label>
                      <input type="text" name="kendali" class="form-control form-control-sm" placeholder="Nomor kendali..." required>
                    </div>
                    <div class="mb-2">
                      <label class="form-label small mb-1">Jenis Surat</label>
                      <input type="text" name="jenis_surat" class="form-control form-control-sm" placeholder="Contoh: Undangan, Pemberitahuan..." required>
                    </div>
                    <div class="mb-2">
                      <label class="form-label small mb-1">Tanggal Terima</label>
                      <input type="text" name="tgl_terima" class="form-control form-control-sm" placeholder="Contoh: 14 Oktober 2025" required>
                    </div>
                    <div class="mb-2">
                      <label class="form-label small mb-1">Tanggal Surat</label>
                      <input type="text" name="tgl_surat" class="form-control form-control-sm" placeholder="Contoh: 10 Oktober 2025" required>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="border rounded-3 p-3 bg-white h-100">
                    <h6 class="fw-bold mb-3 text-success"><i class="bi bi-building me-1"></i> Asal & Nomor Surat</h6>
                    <div class="mb-2">
                      <label class="form-label small mb-1">Asal Surat</label>
                      <input type="text" name="asal_surat" class="form-control form-control-sm" placeholder="Contoh: DLHK Prov Jateng" required>
                    </div>
                    <div class="mb-2">
                      <label class="form-label small mb-1">Nomor Surat</label>
                      <input type="text" name="nomor_surat" class="form-control form-control-sm" placeholder="Contoh: 456/EDR/IX/2025" required>
                    </div>
                    <div class="mb-2">
                      <label class="form-label small mb-1">Perihal</label>
                      <textarea name="perihal" class="form-control form-control-sm" rows="2" placeholder="Isi perihal surat..." required></textarea>
                    </div>
                  </div>
                </div>
                <div class="col-12">
                  <div class="border rounded-3 p-3 bg-white">
                    <h6 class="fw-bold mb-3 text-success"><i class="bi bi-person-lines-fill me-1"></i> Disposisi</h6>
                    <div class="row g-3">
                      <div class="col-md-4">
                        <label class="form-label small mb-1">Dispo Kadis</label>
                        <input type="text" name="dispo_kadis" value="-" class="form-control form-control-sm">
                      </div>
                      <div class="col-md-4">
                        <label class="form-label small mb-1">Dispo Sekdin</label>
                        <input type="text" name="dispo_sekdin" value="-" class="form-control form-control-sm">
                      </div>
                      <div class="col-md-4">
                        <label class="form-label small mb-1">Dispo Kabid</label>
                        <input type="text" name="dispo_kabid" value="-" class="form-control form-control-sm">
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-12">
                  <div class="border rounded-3 p-3 bg-white">
                    <h6 class="fw-bold mb-3 text-success"><i class="bi bi-paperclip me-1"></i> Lampiran File</h6>
                    <input type="file" name="file" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                    <small class="text-muted d-block mt-1">Ukuran maksimal 500 MB. Format: PDF, JPG, PNG, DOC, DOCX.</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer sticky-bottom py-3 bg-white border-0 shadow-sm" style="position: sticky; bottom: 0; z-index: 10;">
          <button type="button" class="btn btn-outline-secondary btn-sm px-4" data-bs-dismiss="modal">
            <i class="bi bi-x-circle me-1"></i> Batal
          </button>
          <button type="submit" name="tambah" class="btn btn-success btn-sm px-4">
            <i class="bi bi-save me-1"></i> Simpan Data
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<style>
.table th, 
.table td {
  vertical-align: middle;
  white-space: normal !important;
  word-wrap: break-word;
  word-break: break-word;
}
.table {
  table-layout: fixed;
  width: 100%;
}
/* Kustomisasi lebar modal-xl agar seragam 90vw untuk semua modal (Create, Update, Preview) */
.modal-xl {
    --bs-modal-width: 90vw; 
}
</style>

<button id="btnScrollTop" onclick="window.scrollTo({top:0,behavior:'smooth'})" class="btn-scroll">
  <i class="bi bi-arrow-up"></i>
</button>
<style>
  .btn-scroll{
    position:fixed;bottom:20px;right:20px;z-index:999;
    width:32px;height:32px;border:none;border-radius:6px;
    background:rgba(0,123,255,.55);color:#fff;font-size:15px;
    display:flex;align-items:center;justify-content:center;
    opacity:0;visibility:hidden;cursor:pointer;
    transition:.4s;box-shadow:0 0 4px rgba(0,123,255, 0.5);
  }
  .btn-scroll:hover { background:rgba(0,123,255,1); }
</style>
<script>
window.onscroll = function() {
  const btn = document.getElementById("btnScrollTop");
  if (document.body.scrollTop > 100 || document.documentElement.scrollTop > 100) {
    btn.style.opacity = "1";
    btn.style.visibility = "visible";
  } else {
    btn.style.opacity = "0";
    btn.style.visibility = "hidden";
  }
};
</script>

<?php 
require_once __DIR__ . '/../partials/footer.php'; 
?>