<?php
// register.php — user registration form + handler
require __DIR__ . '/config.php';

// Already logged in? Go to chat.
if (current_user() !== null) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (strlen($password) < 4) {
        $error = 'Password must be at least 4 characters.';
    } else {
        $pdo = db();

        // Check uniqueness
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);

        if ($stmt->fetch()) {
            $error = 'Username is already taken.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)');
            $stmt->execute([$username, $hash]);

            header('Location: login.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register — Secure Chat</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    background: #f0f2f5;
    display: flex; justify-content: center; align-items: center;
    min-height: 100vh;
  }
  .card {
    background: #fff; border-radius: 12px; padding: 40px 36px;
    box-shadow: 0 2px 16px rgba(0,0,0,.1); width: 100%; max-width: 380px;
  }
  h1 { font-size: 1.5rem; text-align: center; margin-bottom: 24px; }
  label { display: block; font-size: .85rem; font-weight: 600; margin-bottom: 4px; }
  input[type=text], input[type=password] {
    width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 6px;
    font-size: .95rem; margin-bottom: 16px; outline: none;
  }
  input:focus { border-color: #4a90d9; }
  button {
    width: 100%; padding: 10px; background: #4a90d9; color: #fff;
    border: none; border-radius: 6px; font-size: 1rem; cursor: pointer;
  }
  button:hover { background: #3b7cc9; }
  .error { color: #d32f2f; font-size: .85rem; margin-bottom: 14px; text-align: center; }
  .link { text-align: center; margin-top: 16px; font-size: .85rem; }
  .link a { color: #4a90d9; text-decoration: none; }
  .link a:hover { text-decoration: underline; }
</style>
</head>
<body>
<div class="card">
  <h1>🔒 Register</h1>
  <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form method="POST" autocomplete="off">
    <label for="username">Username</label>
    <input type="text" id="username" name="username" required
           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">

    <label for="password">Password</label>
    <input type="password" id="password" name="password" required>

    <button type="submit">Create Account</button>
  </form>
  <div class="link">Already have an account? <a href="login.php">Log in</a></div>
</div>
</body>
</html>
