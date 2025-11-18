<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';

use Kreait\Firebase\Exception\Auth\UserNotFound;
use Kreait\Firebase\Exception\Auth\InvalidPassword;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $email = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        set_flash('warning', 'Email dan password wajib diisi.');
    } else {
        try {
            // Login menggunakan Firebase Auth
            $signInResult = $auth->signInWithEmailAndPassword($email, $password);
            $firebaseUser = $signInResult->data();

            $uid  = $firebaseUser['localId'] ?? null;
            $name = $firebaseUser['displayName'] ?? 'Pengguna';
            $emailUser = $firebaseUser['email'] ?? $email;

            // Simpan session user
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'uid'   => $uid,
                'email' => $emailUser,
                'name'  => $name
            ];

            //  Simpan atau update data user di Firestore
            if (isset($firestore) && $uid) {
                $firestore->collection('users')->document($uid)->set([
                    'uid'       => $uid,
                    'email'     => $emailUser,
                    'name'      => $name,
                    'lastLogin' => date('Y-m-d H:i:s'),
                ], ['merge' => true]);
            }

            // 🔹 Redirect ke dashboard
            set_flash('success', 'Login berhasil. Selamat datang, ' . htmlspecialchars($name) . '!');
            header('Location: ../dashboard/index.php');
            exit;

        } catch (InvalidPassword $e) {
            set_flash('danger', 'Password salah.');
        } catch (UserNotFound $e) {
            set_flash('danger', 'Akun tidak ditemukan.');
        } catch (Exception $e) {
            set_flash('danger', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - <?= APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- ========== Custom CSS untuk UI Modern ========== -->
    <style>
        body {
            /* PERUBAHAN: Gradasi biru yang lebih cerah dan modern */
            background: linear-gradient(135deg, #4A90E2 0%, #357ABD 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            border-radius: 20px;
        }

        .brand-logo {
            width: 80px;
            height: 80px;
            /* PERUBAHAN: Gradasi biru yang sama */
            background: linear-gradient(135deg, #4A90E2 0%, #357ABD 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px auto;
            color: white;
            font-size: 2rem;
        }

        .form-control {
            border-radius: 10px;
            padding-left: 45px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            /* PERUBAHAN: Warna fokus biru yang lebih cerah */
            border-color: #357ABD;
            box-shadow: 0 0 0 0.2rem rgba(53, 122, 189, 0.25);
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            z-index: 10;
        }
        
        .btn-primary {
            /* PERUBAHAN: Gradasi biru yang sama */
            background: linear-gradient(135deg, #4A90E2 0%, #357ABD 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            /* PERUBAHAN: Warna shadow biru yang lebih cerah */
            box-shadow: 0 4px 15px rgba(53, 122, 189, 0.4);
        }

        .btn-primary:disabled {
            transform: none;
            box-shadow: none;
        }

        .link-primary {
            /* PERUBAHAN: Warna biru yang lebih cerah */
            color: #357ABD;
            text-decoration: none;
            font-weight: 600;
        }

        .link-primary:hover {
            text-decoration: underline;
        }

        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }

        /* ========== CSS untuk Flash Message Mengambang ========== */
        .flash-message-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            max-width: 350px;
        }

        .flash-message {
            transform: translateX(100%);
            opacity: 0;
            transition: all 0.5s cubic-bezier(0.25, 0.8, 0.25, 1);
            margin-bottom: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-radius: 8px;
        }

        .flash-message.show {
            transform: translateX(0);
            opacity: 1;
        }

        .flash-message.hide {
            transform: translateX(100%);
            opacity: 0;
        }
    </style>
  </head>
  <body class="d-flex align-items-center">
    <!-- Container untuk notifikasi mengambang -->
    <div id="floating-flash-container" class="flash-message-container"></div>

    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
          <div class="card login-card shadow-sm border-0">
            <div class="card-body p-4">
              <!-- Logo/Brand -->
              <div class="brand-logo">
                <i class="bi bi-shield-lock-fill"></i>
              </div>
              
              <h4 class="text-center mb-3 fw-bold">Selamat Datang</h4>
              <p class="text-center text-muted mb-3">Masuk ke akun Anda untuk melanjutkan</p>

              <!-- Tempat asal flash message (akan dipindahkan oleh JS) -->
              <div id="flash-messages" style="display: none;">
                  <?php show_flash(); ?>
              </div>

              <form method="POST" id="loginForm" autocomplete="off">
                <?= csrf_field(); ?>
                
                <!-- Input Email -->
                <div class="mb-3 position-relative">
                  <i class="bi bi-envelope input-icon"></i>
                  <input type="email" name="username" id="username" class="form-control" placeholder="Email Anda" required autofocus>
                </div>

                <!-- Input Password -->
                <div class="mb-4 position-relative">
                  <i class="bi bi-lock input-icon"></i>
                  <input type="password" name="password" id="password" class="form-control pe-5" placeholder="Password Anda" required>
                  <button type="button" class="btn position-absolute end-0 top-0 h-100" id="togglePassword" style="z-index: 10;">
                    <i class="bi bi-eye-slash" id="toggleIcon"></i>
                  </button>
                </div>

                <!-- Tombol Login -->
                <button type="submit" class="btn btn-primary w-100" id="loginBtn">
                  <span id="btnText">Login</span>
                  <span id="btnSpinner" class="spinner-border spinner-border-sm ms-2" role="status" aria-hidden="true" style="display: none;"></span>
                </button>
              </form>

              <p class="text-center mt-4 mb-0 text-muted">
                Belum punya akun? <a href="register.php" class="link-primary text-decoration-none">Daftar di sini</a>
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const flashSource = document.getElementById('flash-messages');
        const flashContainer = document.getElementById('floating-flash-container');
        
        // Pindahkan semua notifikasi dari sumbernya ke container mengambang
        const alerts = flashSource.querySelectorAll('.alert');
        alerts.forEach((alert, index) => {
            const clonedAlert = alert.cloneNode(true);
            clonedAlert.classList.add('flash-message');
            flashContainer.appendChild(clonedAlert);

            // Tampilkan notifikasi dengan animasi
            setTimeout(() => {
                clonedAlert.classList.add('show');
            }, 100 + (index * 100));

            // Sembunyikan dan hapus notifikasi setelah 5 detik
            setTimeout(() => {
                clonedAlert.classList.add('hide');
                clonedAlert.addEventListener('transitionend', () => {
                    clonedAlert.remove();
                });
            }, 5000 + (index * 100));
        });

        // Kosongkan sumber notifikasi untuk mencegah duplikat
        flashSource.innerHTML = '';


        // ========== Toggle Password Visibility ==========
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');
        const toggleIcon = document.querySelector('#toggleIcon');

        togglePassword.addEventListener('click', () => {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            toggleIcon.classList.toggle('bi-eye');
            toggleIcon.classList.toggle('bi-eye-slash');
        });

        // ========== Loading State on Form Submit ==========
        const loginForm = document.querySelector('#loginForm');
        const loginBtn = document.querySelector('#loginBtn');
        const btnText = document.querySelector('#btnText');
        const btnSpinner = document.querySelector('#btnSpinner');

        loginForm.addEventListener('submit', function() {
            loginBtn.disabled = true;
            btnText.textContent = 'Memproses...';
            btnSpinner.style.display = 'inline-block';
        });
      });
    </script>
  </body>
</html>