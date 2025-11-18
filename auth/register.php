<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php'; // berisi koneksi Firebase baru

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['username'] ?? ''); // diubah: username jadi email
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        set_flash('warning', 'Semua field wajib diisi.');
    } elseif ($password !== $confirm) {
        set_flash('warning', 'Konfirmasi password tidak cocok.');
    } else {
        try {
            // Buat user baru di Firebase Auth
            $userProperties = [
                'email' => $email,
                'emailVerified' => false,
                'password' => $password,
                'displayName' => $name,
                'disabled' => false,
            ];

            $createdUser = $auth->createUser($userProperties);

            // Simpan data tambahan ke Firebase Database
            $database->getReference('users/' . $createdUser->uid)
                     ->set([
                         'name' => $name,
                         'email' => $email,
                         'created_at' => date('Y-m-d H:i:s')
                     ]);

            set_flash('success', 'Registrasi berhasil. Silakan login.');
            header('Location: login.php');
            exit;

        } catch (\Kreait\Firebase\Exception\Auth\EmailExists $e) {
            set_flash('danger', 'Email sudah digunakan.');
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
    <title>Registrasi - <?= APP_NAME; ?></title>
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
            padding-right: 50px;
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
        <!-- PERUBAHAN: Modal diperkecil -->
        <div class="col-md-6 col-lg-5">
          <div class="card login-card shadow-sm border-0">
            <div class="card-body p-3">
              <!-- Logo/Brand -->
              <div class="brand-logo">
                <i class="bi bi-person-plus-fill"></i>
              </div>
              
              <h4 class="text-center mb-2 fw-bold">Buat Akun Baru</h4>
              <p class="text-center text-muted mb-2">Isi formulir di bawah untuk mendaftar</p>

              <!-- Tempat asal flash message (akan dipindahkan oleh JS) -->
              <div id="flash-messages" style="display: none;">
                  <?php show_flash(); ?>
              </div>

              <form method="POST" id="registerForm" autocomplete="off">
                <?= csrf_field(); ?>
                
                <div class="mb-2 position-relative">
                  <i class="bi bi-person input-icon"></i>
                  <input type="text" name="name" id="name" class="form-control" placeholder="Nama Lengkap" required>
                </div>

                <div class="mb-2 position-relative">
                  <i class="bi bi-envelope input-icon"></i>
                  <input type="email" name="username" id="username" class="form-control" placeholder="Email Anda" required>
                </div>

                <div class="mb-2 position-relative">
                  <i class="bi bi-lock input-icon"></i>
                  <input type="password" name="password" id="password" class="form-control" placeholder="Password Anda" required>
                  <button type="button" class="btn position-absolute end-0 top-0 h-100" id="togglePassword" style="z-index: 10;">
                    <i class="bi bi-eye-slash" id="toggleIcon"></i>
                  </button>
                </div>

                <div class="mb-3 position-relative">
                  <i class="bi bi-lock-fill input-icon"></i>
                  <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Konfirmasi Password" required>
                  <button type="button" class="btn position-absolute end-0 top-0 h-100" id="toggleConfirmPassword" style="z-index: 10;">
                    <i class="bi bi-eye-slash" id="toggleConfirmIcon"></i>
                  </button>
                </div>

                <button type="submit" class="btn btn-primary w-100" id="registerBtn">
                  <span id="btnText">Daftar</span>
                  <span id="btnSpinner" class="spinner-border spinner-border-sm ms-2" role="status" aria-hidden="true" style="display: none;"></span>
                </button>
              </form>

              <p class="text-center mt-3 mb-0 text-muted">
                Sudah punya akun? <a href="login.php" class="link-primary text-decoration-none">Login di sini</a>
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        // ========== LOGIKA FLASH MESSAGE ==========
        const flashSource = document.getElementById('flash-messages');
        const flashContainer = document.getElementById('floating-flash-container');
        
        const alerts = flashSource.querySelectorAll('.alert');
        alerts.forEach((alert, index) => {
            const clonedAlert = alert.cloneNode(true);
            clonedAlert.classList.add('flash-message');
            flashContainer.appendChild(clonedAlert);

            setTimeout(() => {
                clonedAlert.classList.add('show');
            }, 100 + (index * 100));

            setTimeout(() => {
                clonedAlert.classList.add('hide');
                clonedAlert.addEventListener('transitionend', () => {
                    clonedAlert.remove();
                });
            }, 5000 + (index * 100));
        });
        flashSource.innerHTML = '';

        // ========== LOGIKA TOGGLE PASSWORD ==========
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');
        const toggleIcon = document.querySelector('#toggleIcon');

        togglePassword.addEventListener('click', () => {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            toggleIcon.classList.toggle('bi-eye');
            toggleIcon.classList.toggle('bi-eye-slash');
        });

        const toggleConfirmPassword = document.querySelector('#toggleConfirmPassword');
        const confirmPassword = document.querySelector('#confirm_password');
        const toggleConfirmIcon = document.querySelector('#toggleConfirmIcon');

        toggleConfirmPassword.addEventListener('click', () => {
            const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPassword.setAttribute('type', type);
            toggleConfirmIcon.classList.toggle('bi-eye');
            toggleConfirmIcon.classList.toggle('bi-eye-slash');
        });

        // ========== LOGIKA LOADING STATE ==========
        const registerForm = document.querySelector('#registerForm');
        const registerBtn = document.querySelector('#registerBtn');
        const btnText = document.querySelector('#btnText');
        const btnSpinner = document.querySelector('#btnSpinner');

        registerForm.addEventListener('submit', function() {
            registerBtn.disabled = true;
            btnText.textContent = 'Mendaftar...';
            btnSpinner.style.display = 'inline-block';
        });
      });
    </script>
  </body>
</html>