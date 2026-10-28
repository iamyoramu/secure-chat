<?php
// login.php — user login form + handler
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

    $pdo  = db();
    $stmt = $pdo->prepare('SELECT id, username, password_hash FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id']  = (int) $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: index.php');
        exit;
    }

    $error = 'Invalid username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Log In — Secure Chat</title>
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
  <h1>🔒 Log In</h1>
  <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form method="POST" autocomplete="off">
    <label for="username">Username</label>
    <input type="text" id="username" name="username" required
           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">

    <label for="password">Password</label>
    <input type="password" id="password" name="password" required>

    <button type="submit">Log In</button>
  </form>
  <div class="link">No account? <a href="register.php">Register</a></div>
</div>
</body>
</html>
