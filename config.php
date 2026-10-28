<?php
// config.php — database connection, encryption helpers, auth helpers

// ── Session ──────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Database credentials (change these for your environment) ─────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'simple_chat');
define('DB_USER', 'root');
define('DB_PASS', '');          // XAMPP default — no password

// ── Encryption key (base64-encoded 32-byte key for AES-256-GCM) ─────
// To regenerate:  php -r "echo base64_encode(random_bytes(32));"
define('ENCRYPTION_KEY_B64', 'g7R3xK9pLmQzYv2wN8sT5uB0jF4hD6aE1cW7iO3kP+M=');

// ── Database singleton ──────────────────────────────────────────────
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

// ── Encryption: AES-256-GCM ─────────────────────────────────────────
// Storage format (before base64): [12-byte IV][16-byte auth tag][ciphertext]

function encrypt_msg(string $plaintext): string {
    $key = base64_decode(ENCRYPTION_KEY_B64, true);
    $iv  = random_bytes(12); // 96-bit IV recommended for GCM
    $tag = '';

    $ciphertext = openssl_encrypt(
        $plaintext,
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag,
        '',    // no AAD
        16     // 128-bit tag
    );

    if ($ciphertext === false) {
        throw new RuntimeException('Encryption failed');
    }

    // Pack: IV (12) + tag (16) + ciphertext
    return base64_encode($iv . $tag . $ciphertext);
}

function decrypt_msg(string $encoded): string|false {
    $key  = base64_decode(ENCRYPTION_KEY_B64, true);
    $blob = base64_decode($encoded, true);

    if ($blob === false || strlen($blob) < 28) {
        // 12 IV + 16 tag = 28 minimum (empty plaintext)
        return false;
    }

    $iv         = substr($blob, 0, 12);
    $tag        = substr($blob, 12, 16);
    $ciphertext = substr($blob, 28);

    $plaintext = openssl_decrypt(
        $ciphertext,
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );

    return $plaintext; // string on success, false on failure
}

// ── Auth helpers ────────────────────────────────────────────────────

function current_user(): ?array {
    if (isset($_SESSION['user_id'], $_SESSION['username'])) {
        return [
            'id'       => (int) $_SESSION['user_id'],
            'username' => $_SESSION['username'],
        ];
    }
    return null;
}

function require_login(): void {
    if (current_user() === null) {
        header('Location: login.php');
        exit;
    }
}

function require_login_api(): void {
    if (current_user() === null) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
}
