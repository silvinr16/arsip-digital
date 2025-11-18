<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $username = trim($_POST['username'] ?? '');
    if ($username === '') {
        set_flash('warning','Username wajib diisi.');
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username=? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($user = $res->fetch_assoc()) {
            $token = bin2hex(random_bytes(16));
            $expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));
            $stmt2 = $conn->prepare("INSERT INTO password_resets(user_id, token, expires_at) VALUES(?,?,?)");
            $stmt2->bind_param('iss', $user['id'], $token, $expires);
            $stmt2->execute();
            set_flash('success',"Token reset password: <code>$token</code><br>Pakai link: reset_password.php?token=$token");
        } else {
            set_flash('danger','Username tidak ditemukan.');
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Lupa Password - <?= APP_NAME; ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <?php show_flash(); ?>
      <div class="card shadow rounded-4">
        <div class="card-body p-4">
          <h1 class="h5 mb-3 text-center">Lupa Password</h1>
          <form method="post">
            <?php csrf_field(); ?>
            <div class="mb-3">
              <label class="form-label">Username</label>
              <input type="text" name="username" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Kirim Token Reset</button>
          </form>
          <hr>
          <p class="text-center small"><a href="login.php">Kembali ke Login</a></p>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
