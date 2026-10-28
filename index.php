<?php
// index.php — main chat UI
require __DIR__ . '/config.php';
require_login();
$me = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Secure Chat</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  html, body { height: 100%; }
  body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    background: #f0f2f5;
    display: flex; flex-direction: column;
  }

  /* ── Header ─────────────────────────────────────────────── */
  .header {
    background: #4a90d9; color: #fff; padding: 12px 20px;
    display: flex; justify-content: space-between; align-items: center;
    flex-shrink: 0;
  }
  .header h1 { font-size: 1.1rem; font-weight: 600; }
  .header a { color: #e0ecf8; text-decoration: none; font-size: .9rem; }
  .header a:hover { text-decoration: underline; color: #fff; }

  /* ── Layout ─────────────────────────────────────────────── */
  .layout {
    display: flex; flex: 1; overflow: hidden;
  }

  /* ── Sidebar ────────────────────────────────────────────── */
  .sidebar {
    width: 220px; background: #fff; border-right: 1px solid #ddd;
    overflow-y: auto; flex-shrink: 0;
  }
  .sidebar h2 {
    font-size: .8rem; text-transform: uppercase; color: #888;
    padding: 14px 16px 6px; letter-spacing: .5px;
  }
  .sidebar .user-item {
    padding: 10px 16px; cursor: pointer; font-size: .95rem;
    border-bottom: 1px solid #f0f0f0; transition: background .15s;
  }
  .sidebar .user-item:hover { background: #e8f0fe; }
  .sidebar .user-item.active { background: #d2e3fc; font-weight: 600; }

  /* ── Chat area ──────────────────────────────────────────── */
  .chat {
    flex: 1; display: flex; flex-direction: column; background: #f0f2f5;
  }
  .chat-header {
    padding: 12px 20px; background: #fff; border-bottom: 1px solid #ddd;
    font-weight: 600; font-size: 1rem; flex-shrink: 0;
  }
  .chat-placeholder {
    flex: 1; display: flex; justify-content: center; align-items: center;
    color: #999; font-size: 1.1rem;
  }
  .messages {
    flex: 1; overflow-y: auto; padding: 16px 20px;
    display: flex; flex-direction: column; gap: 6px;
  }

  /* ── Bubbles ────────────────────────────────────────────── */
  .bubble {
    max-width: 65%; padding: 9px 14px; border-radius: 16px;
    font-size: .93rem; line-height: 1.4; word-wrap: break-word;
    position: relative;
  }
  .bubble .time {
    font-size: .7rem; color: #999; margin-top: 3px; display: block;
  }
  .bubble.sent {
    align-self: flex-end; background: #4a90d9; color: #fff;
    border-bottom-right-radius: 4px;
  }
  .bubble.sent .time { color: #c8ddf4; text-align: right; }
  .bubble.received {
    align-self: flex-start; background: #fff; color: #222;
    border-bottom-left-radius: 4px;
    box-shadow: 0 1px 2px rgba(0,0,0,.08);
  }

  /* ── Input row ──────────────────────────────────────────── */
  .input-row {
    display: flex; padding: 10px 16px; background: #fff;
    border-top: 1px solid #ddd; flex-shrink: 0; gap: 8px;
  }
  .input-row input {
    flex: 1; padding: 10px 14px; border: 1px solid #ccc;
    border-radius: 20px; font-size: .95rem; outline: none;
  }
  .input-row input:focus { border-color: #4a90d9; }
  .input-row button {
    padding: 10px 20px; background: #4a90d9; color: #fff;
    border: none; border-radius: 20px; font-size: .95rem; cursor: pointer;
  }
  .input-row button:hover { background: #3b7cc9; }
  .input-row button:disabled { background: #a0c4e8; cursor: default; }
</style>
</head>
<body>

<!-- Header -->
<div class="header">
  <h1>🔒 Secure Chat &mdash; <?= htmlspecialchars($me['username']) ?></h1>
  <a href="logout.php">Logout</a>
</div>

<!-- Main layout -->
<div class="layout">
  <!-- Sidebar -->
  <div class="sidebar">
    <h2>Users</h2>
    <div id="user-list"></div>
  </div>

  <!-- Chat area -->
  <div class="chat">
    <div class="chat-header" id="chat-header" style="display:none;"></div>
    <div class="chat-placeholder" id="chat-placeholder">Select a user to start chatting</div>
    <div class="messages" id="messages" style="display:none;"></div>
    <div class="input-row" id="input-row" style="display:none;">
      <input type="text" id="msg-input" placeholder="Type a message…" maxlength="5000" autocomplete="off">
      <button id="send-btn">Send</button>
    </div>
  </div>
</div>

<script>
(function () {
  'use strict';

  var myId        = <?= (int) $me['id'] ?>;
  var activeUser  = null;   // {id, username}
  var seenIds     = new Set();
  var pollTimer   = null;
  var convToken   = 0;      // incremented on every conversation switch

  // ── DOM refs ────────────────────────────────────────────
  var userListEl    = document.getElementById('user-list');
  var chatHeaderEl  = document.getElementById('chat-header');
  var placeholderEl = document.getElementById('chat-placeholder');
  var messagesEl    = document.getElementById('messages');
  var inputRowEl    = document.getElementById('input-row');
  var msgInput      = document.getElementById('msg-input');
  var sendBtn       = document.getElementById('send-btn');

  // ── Load user list ──────────────────────────────────────
  function loadUsers() {
    fetch('api.php?action=users')
      .then(function (r) { return r.json(); })
      .then(function (users) {
        userListEl.innerHTML = '';
        users.forEach(function (u) {
          var div = document.createElement('div');
          div.className = 'user-item';
          div.textContent = u.username;
          div.dataset.id = u.id;
          div.dataset.name = u.username;
          if (activeUser && activeUser.id === u.id) {
            div.classList.add('active');
          }
          div.addEventListener('click', function () {
            openConversation(u.id, u.username);
          });
          userListEl.appendChild(div);
        });
      });
  }

  // ── Open conversation ───────────────────────────────────
  function openConversation(userId, username) {
    activeUser = { id: userId, username: username };
    seenIds.clear();
    convToken++;

    // Highlight active user in sidebar
    var items = userListEl.querySelectorAll('.user-item');
    items.forEach(function (el) {
      el.classList.toggle('active', parseInt(el.dataset.id) === userId);
    });

    // Show chat UI
    chatHeaderEl.textContent = username;
    chatHeaderEl.style.display = '';
    placeholderEl.style.display = 'none';
    messagesEl.style.display = '';
    inputRowEl.style.display = '';
    messagesEl.innerHTML = '';
    msgInput.value = '';
    msgInput.focus();

    // Clear existing timer and start fresh
    if (pollTimer) clearInterval(pollTimer);
    loadMessages();
    pollTimer = setInterval(loadMessages, 2000);
  }

  // ── Load messages ───────────────────────────────────────
  function loadMessages() {
    if (!activeUser) return;

    var token  = convToken;
    var afterId = 0;
    if (seenIds.size > 0) {
      afterId = Math.max.apply(null, Array.from(seenIds));
    }

    fetch('api.php?action=messages&with=' + activeUser.id + '&after=' + afterId)
      .then(function (r) { return r.json(); })
      .then(function (msgs) {
        // Stale response — user switched conversations
        if (token !== convToken) return;

        var shouldScroll = false;

        msgs.forEach(function (m) {
          if (seenIds.has(m.id)) return;
          seenIds.add(m.id);
          shouldScroll = true;
          appendBubble(m);
        });

        if (shouldScroll) {
          messagesEl.scrollTop = messagesEl.scrollHeight;
        }
      });
  }

  // ── Append a message bubble ─────────────────────────────
  function appendBubble(m) {
    var bubble = document.createElement('div');
    bubble.className = 'bubble ' + (m.sender_id === myId ? 'sent' : 'received');

    var textSpan = document.createElement('span');
    textSpan.textContent = m.text;       // safe: textContent, not innerHTML
    bubble.appendChild(textSpan);

    var timeSpan = document.createElement('span');
    timeSpan.className = 'time';
    timeSpan.textContent = formatTime(m.created_at);
    bubble.appendChild(timeSpan);

    messagesEl.appendChild(bubble);
  }

  // ── Format timestamp to HH:MM ──────────────────────────
  function formatTime(ts) {
    var d = new Date(ts.replace(' ', 'T'));
    var h = d.getHours().toString().padStart(2, '0');
    var m = d.getMinutes().toString().padStart(2, '0');
    return h + ':' + m;
  }

  // ── Send message ────────────────────────────────────────
  function sendMessage() {
    var text = msgInput.value.trim();
    if (!text || !activeUser) return;

    sendBtn.disabled = true;
    msgInput.value = '';

    fetch('api.php?action=send', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ to: activeUser.id, text: text })
    })
    .then(function (r) { return r.json(); })
    .then(function () {
      sendBtn.disabled = false;
      msgInput.focus();
      loadMessages();
    })
    .catch(function () {
      sendBtn.disabled = false;
    });
  }

  // ── Event listeners ─────────────────────────────────────
  sendBtn.addEventListener('click', sendMessage);
  msgInput.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  });

  // ── Init ────────────────────────────────────────────────
  loadUsers();
})();
</script>

</body>
</html>
