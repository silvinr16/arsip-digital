<?php
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php'; 
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';

// === Ambil data surat masuk dari Firebase ===
 $database = $firebase->createDatabase();
 $ref = $database->getReference('surat_masuk');
 $snapshot = $ref->getSnapshot();
 $items = $snapshot->getValue() ?? [];

// Pastikan $items berupa array asosiatif
if (!is_array($items)) $items = [];

// === Hitung jumlah surat ===
 $suratMasuk = count($items);

// === Ambil 5 surat terbaru (berdasarkan created_at) ===
// Kita perlu mengurutkan berdasarkan timestamp yang benar
usort($items, function($a, $b) {
    $ta = $a['created_at'] ?? 0;
    $tb = $b['created_at'] ?? 0;

    // Jika berupa timestamp numerik (dalam milidetik)
    if (is_numeric($ta)) $ta = intval($ta / 1000);
    else $ta = strtotime($ta); // Jika berupa string tanggal

    if (is_numeric($tb)) $tb = intval($tb / 1000);
    else $tb = strtotime($tb);

    return $tb - $ta; // Urutkan dari yang terbaru
});
 $latest = array_slice($items, 0, 5);

// === Hitung jumlah surat per bulan (12 bulan terakhir) ===
 $chart_bulan = [];
foreach ($items as $it) {
    $tgl = $it['tgl_terima'] ?? $it['created_at'] ?? null;
    if ($tgl) {
        // Pastikan kita mengonversi ke timestamp dulu
        $timestamp = is_numeric($tgl) ? intval($tgl / 1000) : strtotime($tgl);
        $bulan = date('Y-m', $timestamp);
        $chart_bulan[$bulan] = ($chart_bulan[$bulan] ?? 0) + 1;
    }
}
ksort($chart_bulan);
 $chart_bulan_labels = array_keys($chart_bulan);
 $chart_bulan_data = array_values($chart_bulan);

// === Hitung asal surat terbanyak ===
 $chart_asal = [];
foreach ($items as $it) {
    $asal = $it['asal_surat'] ?? 'Tidak diketahui';
    $chart_asal[$asal] = ($chart_asal[$asal] ?? 0) + 1;
}
arsort($chart_asal);
 $chart_asal_labels = array_slice(array_keys($chart_asal), 0, 5);
 $chart_asal_data = array_slice(array_values($chart_asal), 0, 5);

// === Hitung jenis surat terbanyak ===
 $chart_jenis = [];
foreach ($items as $it) {
    $jenis = $it['jenis_surat'] ?? 'Lainnya';
    $chart_jenis[$jenis] = ($chart_jenis[$jenis] ?? 0) + 1;
}
arsort($chart_jenis);
 $chart_jenis_labels = array_slice(array_keys($chart_jenis), 0, 5);
 $chart_jenis_data = array_slice(array_values($chart_jenis), 0, 5);

// === REVISI: Cari update terakhir dengan konversi zona waktu yang eksplisit ===
 $lastUpdateTimestamp = 0;
 $lastUpdateFormatted = '-';

foreach ($items as $it) {
    $rawDate = $it['created_at'] ?? null;
    if (!$rawDate) continue;

    try {
        // Asumsikan input adalah UTC. Bisa string ISO atau timestamp.
        $date = new DateTime($rawDate, new DateTimeZone('UTC'));
        $timestamp = $date->getTimestamp();

        if ($timestamp > $lastUpdateTimestamp) {
            $lastUpdateTimestamp = $timestamp;
        }
    } catch (Exception $e) {
        // Abaikan jika format tanggal tidak valid
        continue;
    }
}

// Jika ditemukan timestamp terbaru, konversi ke WIB untuk ditampilkan
if ($lastUpdateTimestamp > 0) {
    $wibDate = new DateTime();
    $wibDate->setTimestamp($lastUpdateTimestamp);
    $wibDate->setTimezone(new DateTimeZone('Asia/Jakarta')); // Konversi ke WIB
    $lastUpdateFormatted = $wibDate->format('d M Y') . ' pukul ' . $wibDate->format('H:i') . ' WIB';
}
?>

<style>
.flash-floating {
    position: fixed; top: 20px; left: 50%;
    transform: translateX(-50%);
    z-index: 1050; min-width: 300px; max-width: 500px;
    text-align: center; opacity: 0.95;
    animation: fadeInOut 5s forwards;
}
@keyframes fadeInOut {
    0% { opacity: 0; transform: translate(-50%, -20px); }
    10% { opacity: 1; transform: translate(-50%, 0); }
    80% { opacity: 1; }
    100% { opacity: 0; transform: translate(-50%, -20px); }
}
.card-hover:hover { transform: translateY(-3px); transition: .3s; }
.card-title { font-size: 14px; font-weight: 600; margin-bottom: .75rem; }
.card-body { padding: 0.8rem 1rem; }
.table-sm td, .table-sm th { font-size: 12px; }
</style>

<div class="container-fluid">

  <div id="flash-container" class="flash-floating">
    <?php show_flash(); ?>
  </div>

  <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
          <h2 class="h5 mb-0">Dashboard</h2>
          <div class="text-muted small">Ringkasan Sistem Surat Masuk</div>
      </div>
  </div>

  <div class="row g-2 mb-3">
      <div class="col-md-8">
          <div class="card rounded-3 shadow-sm border-0 card-hover">
              <div class="card-body d-flex justify-content-between align-items-center">
                  <div>
                      <div class="small text-muted">Info Sistem</div>
                      <div class="fw-semibold">Monitoring Surat & Disposisi</div>
                  </div>
                  <i class="bi bi-speedometer2 fs-2 text-secondary"></i>
              </div>
          </div>
      </div>
      <div class="col-md-4">
          <a href="../modules/surat_masuk.php" class="text-decoration-none">
              <div class="card rounded-3 shadow-sm card-hover border-0 bg-primary text-white">
                  <div class="card-body d-flex justify-content-between align-items-center">
                      <div>
                          <div class="small">Surat Masuk</div>
                          <div class="fs-5 fw-semibold"><?= $suratMasuk; ?></div>
                      </div>
                      <i class="bi bi-inbox fs-2 opacity-75"></i>
                  </div>
              </div>
          </a>
      </div>
  </div>

  <div class="row g-3">
      <div class="col-lg-8">
          <div class="card rounded-3 shadow-sm mb-3">
              <div class="card-body">
                  <h6 class="card-title">Surat Masuk Terbaru</h6>
                  <table class="table table-sm table-striped align-middle mb-0">
                      <thead class="table-light text-center">
                          <tr>
                              <th style="width:100px;">Tanggal</th>
                              <th style="width:160px;">Nomor Surat</th>
                              <th>Asal Surat</th>
                          </tr>
                      </thead>
                      <tbody>
                          <?php if (!empty($latest)): ?>
                              <?php foreach ($latest as $r): ?>
                              <tr>
                                  <td class="text-center"><?= htmlspecialchars($r['tgl_terima'] ?? '-'); ?></td>
                                  <td><?= htmlspecialchars($r['nomor_surat'] ?? '-'); ?></td>
                                  <td><?= htmlspecialchars($r['asal_surat'] ?? '-'); ?></td>
                              </tr>
                              <?php endforeach; ?>
                          <?php else: ?>
                              <tr><td colspan="3" class="text-center text-muted small">Belum ada data</td></tr>
                          <?php endif; ?>
                      </tbody>
                  </table>
              </div>
          </div>

          <div class="card rounded-3 shadow-sm mb-3">
              <div class="card-body">
                  <h6 class="card-title">Trend Surat Masuk (12 Bulan)</h6>
                  <canvas id="chartBulan" height="90"></canvas>
              </div>
          </div>

          <div class="card rounded-3 shadow-sm">
              <div class="card-body">
                  <h6 class="card-title">Statistik Ringkas</h6>
                  <p class="mb-1 small text-muted">Total surat masuk: <b><?= $suratMasuk; ?></b></p>
                  <p class="mb-1 small text-muted">
                      Update terakhir: 
                      <b><?= $lastUpdateFormatted; ?></b>
                  </p>
              </div>
          </div>
      </div>

      <div class="col-lg-4">
          <div class="card rounded-3 shadow-sm mb-3">
              <div class="card-body">
                  <h6 class="card-title">Asal Surat Terbanyak</h6>
                  <canvas id="chartAsal" height="120"></canvas>
              </div>
          </div>

          <div class="card rounded-3 shadow-sm">
              <div class="card-body">
                  <h6 class="card-title">Jenis Surat Terbanyak</h6>
                  <canvas id="chartJenis" height="160"></canvas>
              </div>
          </div>
      </div>
  </div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
</main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('chartBulan'), {
    type: 'line',
    data: {
        labels: <?= json_encode($chart_bulan_labels); ?>,
        datasets: [{
            label: 'Jumlah Surat',
            data: <?= json_encode($chart_bulan_data); ?>,
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13,110,253,0.2)',
            fill: true, tension: 0.3
        }]
    },
    options: { 
        plugins:{legend:{display:false}}, 
        scales:{y:{beginAtZero:true}, x:{ticks:{font:{size:11}}}} 
    }
});

new Chart(document.getElementById('chartAsal'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_asal_labels); ?>,
        datasets: [{
            label: 'Jumlah Surat',
            data: <?= json_encode($chart_asal_data); ?>,
            backgroundColor: '#20c997'
        }]
    },
    options: { 
        plugins:{legend:{display:false}}, 
        indexAxis:'y', 
        scales:{x:{beginAtZero:true, ticks:{font:{size:11}}}, y:{ticks:{font:{size:11}}}} 
    }
});

new Chart(document.getElementById('chartJenis'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($chart_jenis_labels); ?>,
        datasets: [{
            label: 'Jumlah Surat',
            data: <?= json_encode($chart_jenis_data); ?>,
            backgroundColor: ['#0d6efd','#20c997','#ffc107','#dc3545','#6f42c1']
        }]
    },
    options: { 
        plugins:{legend:{position:'bottom', labels:{font:{size:11}}}}, 
        cutout: '65%' 
    }
});

setTimeout(() => { const flash=document.getElementById('flash-container'); if(flash) flash.remove(); }, 5000);
</script>