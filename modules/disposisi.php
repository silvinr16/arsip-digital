<?php
// FILE: disposisi.php (Telah disesuaikan untuk Firebase Realtime Database)

// --- A. REQUIRE & INITIALISASI FIREBASE ---
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php'; // DIHARAPKAN MENGANDUNG INISIALISASI $database (Firebase)
require_once __DIR__ . '/../middleware/auth.php';

// Pastikan koneksi Firebase tersedia
if (!isset($database)) {
    http_response_code(500);
    die("Inisialisasi Firebase Realtime Database gagal. Cek config/db.php.");
}

// -----------------------------
// B. AMBIL SEMUA DATA DARI FIREBASE
// -----------------------------
 $ref = $database->getReference('surat_masuk');
 $all_data = $ref->getValue();
 $surat_masuk_array = is_array($all_data) ? $all_data : [];

// Ubah struktur array (jika perlu) dan tambahkan ID
 $data_list = [];
foreach ($surat_masuk_array as $key => $val) {
    if (is_array($val)) {
        $val['id'] = $key;
        $data_list[] = $val;
    }
}
unset($surat_masuk_array, $all_data);

// -----------------------------
// C. KONFIGURASI PAGINATION & SEARCH
// -----------------------------
 $allowed_limits = [10, 25, 50, 100];

// Ambil limit
 $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
if (!in_array($limit, $allowed_limits)) $limit = 10;

// Ambil halaman
 $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;

// Search keyword
 $search = $_GET['search'] ?? '';

// -----------------------------
// D. FILTER DATA (Server-side/PHP)
// -----------------------------

 $filtered_data = $data_list;

if (!empty($search)) {
    $searchTerm = strtolower($search);
    $filtered_data = array_filter($data_list, function($item) use ($searchTerm) {
        // Kolom yang dicari (sama seperti di kode MySQLi Anda)
        $fields_to_search = [
            'kendali', 'jenis_surat', 'nomor_surat', 'asal_surat', 
            'perihal', 'tgl_surat', 'tgl_terima'
        ];
        
        foreach ($fields_to_search as $field) {
            $value = strtolower($item[$field] ?? '');
            if (str_contains($value, $searchTerm)) {
                return true;
            }
        }
        return false;
    });
    // Reindex array setelah filter
    $filtered_data = array_values($filtered_data);
}

// Urutkan data berdasarkan ID (atau created_at jika ada) secara DESC
// Karena Firebase tidak menjamin urutan, kita urutkan manual
usort($filtered_data, function($a, $b) {
    // Mengasumsikan ID Firebase (key) adalah string, diurutkan descending
    return $b['id'] <=> $a['id']; 
});

// -----------------------------
// E. APLIKASIKAN PAGINATION
// -----------------------------
 $total_rows = count($filtered_data);
 $total_pages = ceil($total_rows / $limit);
 $offset = ($page - 1) * $limit;

// Pastikan offset tidak melebihi batas
if ($offset >= $total_rows && $total_rows > 0) {
    $page = 1;
    $offset = 0;
}

// Ambil data untuk halaman saat ini
 $data_per_page = array_slice($filtered_data, $offset, $limit);

 $start_data = $total_rows > 0 ? $offset + 1 : 0;
 $end_data = min($offset + $limit, $total_rows);
?>

<?php require_once __DIR__ . '/../partials/header.php'; ?>
<?php require_once __DIR__ . '/../partials/sidebar.php'; ?>

<div class="container-fluid px-0">
  <!-- Card Header -->
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white py-3">
      <div class="row align-items-center">
        <div class="col-md-6">
          <h4 class="mb-0"><i class="bi bi-clipboard-check me-2"></i>Disposisi - Cetak Lembar</h4>
        </div>
        <div class="col-md-6 text-md-end">
          <!-- Total Data dipindahkan dari sini -->
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
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Cari surat...">
                <button class="btn btn-primary" type="submit">
                  <i class="bi bi-search"></i> Cari
                </button>
              </div>
            </form>
            
            <!-- Limit Data Form -->
            <form method="get" class="d-flex align-items-center gap-2">
              <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
              <input type="hidden" name="page" value="1">
              <label class="small text-muted m-0 fw-semibold">Tampilkan</label>
              <select name="limit" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <?php foreach ($allowed_limits as $opt): ?>
                  <option value="<?= $opt ?>" <?= $opt == $limit ? 'selected' : '' ?>><?= $opt ?></option>
                <?php endforeach; ?>
              </select>
              <span class="small text-muted">data per halaman</span>
            </form>
          </div>
        </div>
        <div class="col-md-4 text-md-end mt-2 mt-md-0">
          <!-- Informasi tambahan bisa ditambahkan di sini -->
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-header bg-light py-2">
      <div class="row align-items-center">
        <div class="col-md-6">
          <h5 class="mb-0 text-primary"><i class="bi bi-table me-2"></i>Data Surat Masuk</h5>
        </div>
        <div class="col-md-6 text-md-end">
          <!-- Total Data dipindahkan ke sini -->
          <div class="badge bg-primary p-2">
            <i class="bi bi-database me-1"></i>
            Total Data: <span class="fw-bold"><?= $total_rows ?></span>
          </div>
        </div>
      </div>
    </div>
    <div class="card-body p-2">
      <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle text-sm" style="table-layout: fixed; font-size:14px;">
          <thead class="table-primary text-center fw-semibold">
            <tr>
              <th style="width: 7%;">No Agenda</th>
              <th style="width: 8%;">Jenis Surat</th>
              <th style="width: 7%;">Tgl Terima</th>
              <th style="width: 14%;">Asal Surat</th>
              <th style="width: 14%;">No Surat</th>
              <th style="width: 7%;">Tgl Surat</th>
              <th style="width: 27%;">Perihal</th>
              <th style="width: 10%;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($data_per_page)): foreach ($data_per_page as $row): ?>
              <tr>
                <td class="wrap-text"><?= htmlspecialchars($row['kendali'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td class="wrap-text"><?= htmlspecialchars($row['jenis_surat'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td class="text-center wrap-text"><?= htmlspecialchars($row['tgl_terima'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td class="wrap-text"><?= htmlspecialchars($row['asal_surat'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td class="wrap-text"><?= htmlspecialchars($row['nomor_surat'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td class="text-center wrap-text"><?= htmlspecialchars($row['tgl_surat'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td class="wrap-text"><?= htmlspecialchars($row['perihal'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td class="text-center">
                  <div class="d-inline-flex align-items-center gap-1">
                    <button class="btn btn-sm btn-light p-0 px-1 btn-copy position-relative" 
                            style="width:28px; height:28px;"
                            data-kendali="<?= htmlspecialchars($row['kendali'] ?? '') ?>"
                            data-jenissurat="<?= htmlspecialchars($row['jenis_surat'] ?? '') ?>"
                            data-tglterima="<?= htmlspecialchars($row['tgl_terima'] ?? '') ?>"
                            data-asalsurat="<?= htmlspecialchars($row['asal_surat'] ?? '') ?>"
                            data-nomorsurat="<?= htmlspecialchars($row['nomor_surat'] ?? '') ?>"
                            data-tglsurat="<?= htmlspecialchars($row['tgl_surat'] ?? '') ?>"
                            data-perihal="<?= htmlspecialchars($row['perihal'] ?? '') ?>"
                            title="Salin Data">
                      <i class="bi bi-clipboard fs-6 icon-copy"></i>
                      <span class="tooltip-copy">Disalin!</span>
                    </button>
                    <a href="cetak_disposisi.php?id=<?= $row['id'] ?>" 
                      class="btn btn-primary btn-sm p-0 px-1"
                      style="width:28px; height:28px;"
                      title="Cetak Disposisi">
                      <i class="bi bi-printer fs-6"></i>
                    </a>
                    <a href="cetak_tandaterima.php?id=<?= $row['id'] ?>" 
                      class="btn btn-success btn-sm p-0 px-1"
                      style="width:28px; height:28px;"
                      title="Cetak Tanda Terima">
                      <i class="bi bi-file-earmark-text fs-6"></i>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; else: ?>
              <tr><td colspan="8" class="text-center text-muted">Belum ada surat masuk.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    
    <div class="card-footer bg-light py-2">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
        <div class="small text-muted">
        <i class="bi bi-info-circle me-1"></i>
          Menampilkan <?= $start_data ?> - <?= $end_data ?> dari <?= $total_rows ?> data
        </div>
        <?php if ($total_pages > 1): ?>
        <nav>
          <ul class="pagination pagination-sm mb-0">
            <?php
            // Function to build pagination link
            $buildLink = function($p) use ($limit, $search) {
                return "?page={$p}&limit={$limit}&search=" . urlencode($search);
            };
            ?>
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
              <a class="page-link" href="<?= $buildLink(1) ?>">First</a>
            </li>
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
              <a class="page-link" href="<?= $buildLink($page-1) ?>">«</a>
            </li>
            <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
              <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                <a class="page-link" href="<?= $buildLink($i) ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
              <a class="page-link" href="<?= $buildLink($page+1) ?>">»</a>
            </li>
            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
              <a class="page-link" href="<?= $buildLink($total_pages) ?>">Last</a>
            </li>
          </ul>
        </nav>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-copy').forEach(btn => {
        btn.addEventListener('click', function() {
            let text = [
                this.dataset.kendali,
                this.dataset.jenissurat,
                this.dataset.tglterima,
                this.dataset.asalsurat,
                this.dataset.nomorsurat,
                this.dataset.tglsurat,
                this.dataset.perihal
            ].join("\t");

            navigator.clipboard.writeText(text).then(() => {
                let icon = this.querySelector('.icon-copy');
                let tooltip = this.querySelector('.tooltip-copy');

                icon.classList.remove('bi-clipboard');
                icon.classList.add('bi-clipboard-check', 'text-success', 'bounce');

                tooltip.classList.add('show');

                setTimeout(() => {
                    icon.classList.remove('bi-clipboard-check', 'text-success', 'bounce');
                    icon.classList.add('bi-clipboard');
                    tooltip.classList.remove('show');
                }, 1500);
            });
        });
    });
});
</script>

<style>
@keyframes bounce {
    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
    40% { transform: translateY(-6px); }
    60% { transform: translateY(-3px); }
}
.bounce { animation: bounce 0.6s; }

/* Tooltip */
.tooltip-copy {
    position: absolute;
    bottom: 120%; 
    left: 50%;
    transform: translateX(-50%);
    background: #198754; 
    color: #fff;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 10px;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s ease-in-out;
}
.tooltip-copy.show { opacity: 1; }

.table td.wrap-text, 
.table th.wrap-text {
    white-space: normal !important;
    word-wrap: break-word;
    vertical-align: top;
}
.table { font-size: 14px; }
.btn-sm i { font-size: 14px; }
.pagination .page-link { font-size: 12px; padding: 4px 8px; }
.form-select-sm, .form-control-sm, .btn-sm { font-size: 12px; }
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
    transition:.4s;box-shadow:0 0 4px rgba(0,123,255,.25);
}
.btn-scroll:hover{background:rgba(0,123,255,.85);box-shadow:0 0 6px rgba(0,123,255,.35);}
.btn-scroll.show{opacity:1;visibility:visible;animation:float 6s ease-in-out infinite;}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}
</style>

<script>
const btn=document.getElementById("btnScrollTop");
window.addEventListener("scroll",()=>btn.classList.toggle("show",window.scrollY>200));
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>