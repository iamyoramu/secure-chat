<?php
// api.php — JSON API for chat (users, messages, send, mark_read)
require __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    require_login_api();

    $me     = current_user();
    $action = $_GET['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];

    // ── GET ?action=users ───────────────────────────────────────────
    // Returns [{id, username, last_message, last_message_time, unread_count}]
    if ($action === 'users' && $method === 'GET') {
        $pdo = db();

        // Fetch all other users
        $stmt = $pdo->prepare('SELECT id, username FROM users WHERE id != ?');
        $stmt->execute([$me['id']]);
        $users = $stmt->fetchAll();

        $result = [];
        foreach ($users as $u) {
            $uid = (int) $u['id'];

            // Last message between me and this user (by highest id)
            $stmt2 = $pdo->prepare(
                'SELECT id, sender_id, ciphertext, created_at FROM messages
                 WHERE (sender_id = ? AND receiver_id = ?)
                    OR (sender_id = ? AND receiver_id = ?)
                 ORDER BY id DESC LIMIT 1'
            );
            $stmt2->execute([$me['id'], $uid, $uid, $me['id']]);
            $lastMsg = $stmt2->fetch();

            $lastMessage     = null;
            $lastMessageTime = null;
            if ($lastMsg) {
                $plain = decrypt_msg($lastMsg['ciphertext']);
                $lastMessage     = ($plain !== false) ? $plain : '[decryption failed]';
                $lastMessageTime = $lastMsg['created_at'];
            }

            // Unread count: messages FROM this user TO me with id > read cursor
            $stmt3 = $pdo->prepare(
                'SELECT COALESCE(r.last_read_message_id, 0) AS cursor_id
                 FROM (SELECT 1) AS dummy
                 LEFT JOIN `reads` r ON r.reader_id = ? AND r.sender_id = ?'
            );
            $stmt3->execute([$me['id'], $uid]);
            $cursorRow = $stmt3->fetch();
            $cursorId  = (int) ($cursorRow['cursor_id'] ?? 0);

            $stmt4 = $pdo->prepare(
                'SELECT COUNT(*) AS cnt FROM messages
                 WHERE sender_id = ? AND receiver_id = ? AND id > ?'
            );
            $stmt4->execute([$uid, $me['id'], $cursorId]);
            $unread = (int) $stmt4->fetchColumn();

            $result[] = [
                'id'                => $uid,
                'username'          => $u['username'],
                'last_message'      => $lastMessage,
                'last_message_time' => $lastMessageTime,
                'unread_count'      => $unread,
            ];
        }

        // Sort: users with recent messages first, then alphabetical
        usort($result, function ($a, $b) {
            if ($a['last_message_time'] && $b['last_message_time']) {
                return strcmp($b['last_message_time'], $a['last_message_time']);
            }
            if ($a['last_message_time']) return -1;
            if ($b['last_message_time']) return 1;
            return strcasecmp($a['username'], $b['username']);
        });

        echo json_encode($result);
        exit;
    }

    // ── GET ?action=messages&with=ID ────────────────────────────────
    // Supports &since=LAST_ID (preferred) or &after=LAST_ID (compat)
    if ($action === 'messages' && $method === 'GET') {
        $withId = filter_input(INPUT_GET, 'with', FILTER_VALIDATE_INT);
        if (!$withId) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing or invalid "with" parameter']);
            exit;
        }

        // "since" takes priority; fall back to "after"; default 0
        $sinceId = filter_input(INPUT_GET, 'since', FILTER_VALIDATE_INT);
        $afterId = filter_input(INPUT_GET, 'after', FILTER_VALIDATE_INT);
        $minId   = $sinceId ?: ($afterId ?: 0);

        $stmt = db()->prepare(
            'SELECT id, sender_id, receiver_id, ciphertext, created_at
             FROM messages
             WHERE ((sender_id = ? AND receiver_id = ?)
                 OR (sender_id = ? AND receiver_id = ?))
               AND id > ?
             ORDER BY id ASC'
        );
        $stmt->execute([$me['id'], $withId, $withId, $me['id'], $minId]);

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

    // ── POST ?action=mark_read ──────────────────────────────────────
    if ($action === 'mark_read' && $method === 'POST') {
        $body   = json_decode(file_get_contents('php://input'), true);
        $withId = (int) ($body['with'] ?? 0);

        if ($withId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid user id']);
            exit;
        }

        $pdo = db();

        // Find max message id from this sender to me
        $stmt = $pdo->prepare(
            'SELECT MAX(id) AS max_id FROM messages
             WHERE sender_id = ? AND receiver_id = ?'
        );
        $stmt->execute([$withId, $me['id']]);
        $maxId = (int) ($stmt->fetchColumn() ?: 0);

        if ($maxId > 0) {
            $stmt = $pdo->prepare(
                'INSERT INTO `reads` (reader_id, sender_id, last_read_message_id)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE last_read_message_id = GREATEST(last_read_message_id, VALUES(last_read_message_id))'
            );
            $stmt->execute([$me['id'], $withId, $maxId]);
        }

        echo json_encode(['ok' => true]);
        exit;
    }

    // ── Unknown action ──────────────────────────────────────────────
    http_response_code(400);
    echo json_encode(['error' => 'Unknown action']);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
