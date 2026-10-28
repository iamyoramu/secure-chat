<?php
// register.php — user registration form + handler
require __DIR__ . '/config.php';

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
    font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text",
                 "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    min-height: 100dvh;
    display: flex; justify-content: center; align-items: center;
    background: #FFFFFF;
    background-image:
      radial-gradient(ellipse at 75% 20%, rgba(10,132,255,0.04) 0%, transparent 60%),
      radial-gradient(ellipse at 85% 30%, rgba(94,92,230,0.04) 0%, transparent 50%);
    animation: fadeIn 200ms ease;
  }
  @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
  @keyframes slideDown {
    from { opacity: 0; transform: translateY(-8px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  @media (prefers-reduced-motion: reduce) {
    *, *::before, *::after { animation-duration: 0s !important; transition-duration: 0s !important; }
  }
  @media (prefers-color-scheme: dark) {
    body {
      background: #000000;
      background-image:
        radial-gradient(ellipse at 75% 20%, rgba(10,132,255,0.06) 0%, transparent 60%),
        radial-gradient(ellipse at 85% 30%, rgba(94,92,230,0.05) 0%, transparent 50%);
    }
    .card {
      background: rgba(28,28,30,0.72) !important;
      box-shadow: 0 1px 2px rgba(0,0,0,0.1), 0 4px 14px rgba(0,0,0,0.15) !important;
    }
    .card h1, .card label { color: #F2F2F7 !important; }
    .card .subtitle { color: #8E8E93 !important; }
    input[type=text], input[type=password] {
      background: rgba(118,118,128,0.24) !important;
      color: #F2F2F7 !important;
    }
    input::placeholder { color: #636366 !important; }
    .link a { color: #8E8E93 !important; }
  }

  .card {
    width: 100%; max-width: 400px; padding: 32px;
    background: rgba(255,255,255,0.72);
    backdrop-filter: blur(24px) saturate(180%);
    -webkit-backdrop-filter: blur(24px) saturate(180%);
    border-radius: 24px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04), 0 4px 14px rgba(0,0,0,0.06);
    margin: 16px;
  }
  .icon-wrap { text-align: center; margin-bottom: 16px; }
  .icon-wrap svg { width: 48px; height: 48px; }
  h1 {
    font-size: 26px; font-weight: 700; letter-spacing: -0.5px;
    text-align: center; color: #000000; margin-bottom: 6px;
  }
  .subtitle {
    text-align: center; font-size: 14px; color: #8E8E93;
    margin-bottom: 24px; font-weight: 400;
  }
  label {
    display: block; font-size: 13px; font-weight: 600;
    color: #000000; margin-bottom: 6px;
  }
  input[type=text], input[type=password] {
    width: 100%; height: 44px; padding: 0 14px;
    background: rgba(118,118,128,0.12); border: none;
    border-radius: 12px; font-size: 16px; color: #000;
    outline: none; margin-bottom: 16px;
    transition: box-shadow 200ms;
  }
  input::placeholder { color: #8E8E93; }
  input:focus { box-shadow: 0 0 0 3px rgba(10,132,255,0.25); }
  button[type=submit] {
    width: 100%; height: 44px; border: none; border-radius: 12px;
    background: linear-gradient(135deg, #0A84FF 0%, #5E5CE6 100%);
    color: #FFFFFF; font-size: 15px; font-weight: 600;
    cursor: pointer; transition: filter 150ms, transform 100ms;
  }
  button[type=submit]:hover { filter: brightness(1.05); }
  button[type=submit]:active { transform: scale(0.98); }
  .error {
    color: #FF3B30; font-size: 13px; text-align: center;
    margin-bottom: 14px; animation: slideDown 250ms ease;
  }
  .link {
    text-align: center; margin-top: 20px; font-size: 14px;
  }
  .link a {
    color: #8E8E93; text-decoration: none; font-weight: 500;
  }
  .link a:hover { text-decoration: underline; }
</style>
</head>
<body>
<div class="card">
  <div class="icon-wrap">
    <svg viewBox="0 0 48 48" fill="none">
      <defs>
        <linearGradient id="g" x1="0" y1="0" x2="48" y2="48" gradientUnits="userSpaceOnUse">
          <stop offset="0%" stop-color="#0A84FF"/>
          <stop offset="100%" stop-color="#5E5CE6"/>
        </linearGradient>
      </defs>
      <rect x="10" y="22" width="28" height="20" rx="4" fill="url(#g)"/>
      <path d="M16 22v-6a8 8 0 1 1 16 0v6" stroke="url(#g)" stroke-width="3" fill="none" stroke-linecap="round"/>
      <circle cx="24" cy="33" r="2.5" fill="#fff"/>
    </svg>
  </div>
  <h1>Create your account</h1>
  <p class="subtitle">Join Secure Chat to start messaging</p>
  <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form method="POST" autocomplete="off">
    <label for="username">Username</label>
    <input type="text" id="username" name="username" placeholder="Choose a username" required
           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
    <label for="password">Password</label>
    <input type="password" id="password" name="password" placeholder="Choose a password" required>
    <button type="submit">Create Account</button>
  </form>
  <div class="link">Already have an account? <a href="login.php">Log in</a></div>
</div>
</body>
</html>
