<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// === APP CONFIG ===
define('APP_NAME', 'Sistem Arsip Digital');
define('BASE_URL', '/dlhk_surat_arsip'); 

// === CSRF ===
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
function csrf_token() {
    return $_SESSION['csrf_token'] ?? '';
}
function csrf_field() {
    $t = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    echo '<input type="hidden" name="csrf_token" value="'.$t.'">';
}
function csrf_verify() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(419);
            exit('CSRF token mismatch.');
        }
    }
}

// === FLASH MESSAGE ===
function set_flash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}
function show_flash() {
    if (!empty($_SESSION['flash'])) {
        $type = $_SESSION['flash']['type'];
        $msg = htmlspecialchars($_SESSION['flash']['message'], ENT_QUOTES, 'UTF-8');
        echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">'
            . $msg .
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
        unset($_SESSION['flash']);
    }
}
?>
