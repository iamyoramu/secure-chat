<?php
// api.php — JSON API for chat (users, messages, send)
require __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    require_login_api();

    $me     = current_user();
    $action = $_GET['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];

    // ── GET ?action=users ───────────────────────────────────────────
    if ($action === 'users' && $method === 'GET') {
        $stmt = db()->prepare(
            'SELECT id, username FROM users WHERE id != ? ORDER BY username'
        );
        $stmt->execute([$me['id']]);
        echo json_encode($stmt->fetchAll());
        exit;
    }

    // ── GET ?action=messages&with=ID ────────────────────────────────
    if ($action === 'messages' && $method === 'GET') {
        $withId = filter_input(INPUT_GET, 'with', FILTER_VALIDATE_INT);
        if (!$withId) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing or invalid "with" parameter']);
            exit;
        }

        $afterId = filter_input(INPUT_GET, 'after', FILTER_VALIDATE_INT) ?: 0;

        $stmt = db()->prepare(
            'SELECT id, sender_id, receiver_id, ciphertext, created_at
             FROM messages
             WHERE ((sender_id = ? AND receiver_id = ?)
                 OR (sender_id = ? AND receiver_id = ?))
               AND id > ?
             ORDER BY id ASC'
        );
        $stmt->execute([$me['id'], $withId, $withId, $me['id'], $afterId]);

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $plain = decrypt_msg($row['ciphertext']);
            $rows[] = [
                'id'          => (int) $row['id'],
                'sender_id'   => (int) $row['sender_id'],
                'receiver_id' => (int) $row['receiver_id'],
                'text'        => $plain !== false ? $plain : '[decryption failed]',
                'created_at'  => $row['created_at'],
            ];
        }

        echo json_encode($rows);
        exit;
    }

    // ── POST ?action=send ───────────────────────────────────────────
    if ($action === 'send' && $method === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true);

        $to   = (int) ($body['to']   ?? 0);
        $text = trim($body['text']   ?? '');

        if ($to <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid recipient']);
            exit;
        }
        if ($text === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Message cannot be empty']);
            exit;
        }
        if (mb_strlen($text) > 5000) {
            http_response_code(400);
            echo json_encode(['error' => 'Message too long (max 5000 characters)']);
            exit;
        }

        // Verify recipient exists
        $stmt = db()->prepare('SELECT id FROM users WHERE id = ?');
        $stmt->execute([$to]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Recipient not found']);
            exit;
        }

        // Encrypt and store
        $cipher = encrypt_msg($text);
        $stmt   = db()->prepare(
            'INSERT INTO messages (sender_id, receiver_id, ciphertext) VALUES (?, ?, ?)'
        );
        $stmt->execute([$me['id'], $to, $cipher]);

        echo json_encode(['ok' => true, 'id' => (int) db()->lastInsertId()]);
        exit;
    }

    // ── Unknown action ──────────────────────────────────────────────
    http_response_code(400);
    echo json_encode(['error' => 'Unknown action']);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
