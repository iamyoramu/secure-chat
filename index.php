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
/* ── Reset & base ────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html, body { height: 100%; }
body {
  font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text",
               "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  background: #FFFFFF;
  background-image:
    radial-gradient(ellipse at 80% 10%, rgba(10,132,255,0.04) 0%, transparent 60%),
    radial-gradient(ellipse at 90% 25%, rgba(94,92,230,0.04) 0%, transparent 50%);
  color: #000000;
  display: flex; height: 100dvh; overflow: hidden;
  animation: pageIn 200ms ease;
}
@keyframes pageIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes bubbleIn {
  from { opacity: 0; transform: translateY(8px) scale(0.98); }
  to   { opacity: 1; transform: none; }
}
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    animation-duration: 0s !important;
    transition-duration: 0s !important;
  }
}

/* ── Sidebar ─────────────────────────────────────────────── */
.sidebar {
  width: 320px; flex-shrink: 0; display: flex; flex-direction: column;
  background: rgba(255,255,255,0.72);
  backdrop-filter: blur(24px) saturate(180%);
  -webkit-backdrop-filter: blur(24px) saturate(180%);
  border-right: 1px solid rgba(60,60,67,0.12);
  overflow: hidden;
}
.sidebar-top {
  padding: 20px 20px 0; display: flex; align-items: center;
  justify-content: space-between; flex-shrink: 0;
}
.sidebar-title {
  font-size: 22px; font-weight: 700; letter-spacing: -0.3px;
}
.my-avatar {
  width: 32px; height: 32px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-size: 14px; font-weight: 600;
  flex-shrink: 0; cursor: default;
}
.search-wrap {
  padding: 12px 16px; flex-shrink: 0;
}
.search-box {
  display: flex; align-items: center; gap: 8px;
  background: rgba(118,118,128,0.12); border-radius: 20px;
  padding: 10px 14px;
}
.search-box svg { flex-shrink: 0; color: #8E8E93; }
.search-box input {
  flex: 1; border: none; background: transparent; outline: none;
  font-size: 15px; color: #000; font-family: inherit;
}
.search-box input::placeholder { color: #8E8E93; }
.user-list {
  flex: 1; overflow-y: auto; padding: 0 8px 8px;
}
.user-item {
  display: flex; align-items: center; gap: 12px;
  padding: 12px 12px; border-radius: 14px; cursor: pointer;
  margin: 2px 0; transition: background 150ms;
  position: relative; border: none; background: transparent;
  width: 100%; text-align: left; font-family: inherit;
}
.user-item:hover { background: rgba(118,118,128,0.08); }
.user-item:active { transform: scale(0.98); }
.user-item.active {
  background: rgba(10,132,255,0.12);
  border-left: 3px solid #0A84FF;
  padding-left: 9px;
}
.user-item .avatar {
  width: 48px; height: 48px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-size: 18px; font-weight: 600; flex-shrink: 0;
}
.user-item .info { flex: 1; min-width: 0; }
.user-item .info .name {
  font-size: 15px; font-weight: 600; color: #000;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.user-item.active .info .name { font-weight: 700; }
.user-item .info .preview {
  font-size: 13px; color: #8E8E93; font-weight: 400;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  margin-top: 2px;
}
.user-item .meta {
  display: flex; flex-direction: column; align-items: flex-end;
  gap: 4px; flex-shrink: 0;
}
.user-item .meta .time {
  font-size: 12px; color: #8E8E93; font-weight: 500;
  white-space: nowrap;
}
.badge {
  min-width: 20px; height: 20px; border-radius: 10px;
  background: #FF3B30; color: #fff; font-size: 11px; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  padding: 0 6px;
}
.badge.hidden { display: none; }

/* ── Chat area ───────────────────────────────────────────── */
.chat {
  flex: 1; display: flex; flex-direction: column; min-width: 0;
}
.chat-header {
  height: 56px; display: flex; align-items: center; gap: 12px;
  padding: 0 20px; flex-shrink: 0;
  background: rgba(255,255,255,0.72);
  backdrop-filter: blur(24px) saturate(180%);
  -webkit-backdrop-filter: blur(24px) saturate(180%);
  border-bottom: 1px solid rgba(60,60,67,0.12);
}
.chat-header .avatar {
  width: 36px; height: 36px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-size: 15px; font-weight: 600; flex-shrink: 0;
}
.chat-header .name { font-size: 17px; font-weight: 600; flex: 1; }
.chat-header .back-btn {
  display: none; background: none; border: none; cursor: pointer;
  padding: 4px; border-radius: 8px; color: #0A84FF;
  transition: background 150ms;
}
.chat-header .back-btn:hover { background: rgba(118,118,128,0.12); }
.chat-header .menu-btn {
  width: 32px; height: 32px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  background: none; border: none; cursor: pointer; color: #8E8E93;
  transition: background 150ms;
}
.chat-header .menu-btn:hover { background: rgba(118,118,128,0.12); }

/* ── Empty state ─────────────────────────────────────────── */
.empty-state {
  flex: 1; display: flex; flex-direction: column;
  align-items: center; justify-content: center;
}
.empty-state svg { width: 72px; height: 72px; }
.empty-state .title {
  font-size: 17px; font-weight: 600; margin-top: 16px;
}
.empty-state .sub {
  font-size: 14px; color: #8E8E93; margin-top: 8px;
}

/* ── Messages ────────────────────────────────────────────── */
.messages {
  flex: 1; overflow-y: auto; padding: 24px 20px; position: relative;
  display: flex; flex-direction: column;
}
.day-divider {
  align-self: center; font-size: 12px; color: #8E8E93; font-weight: 500;
  background: rgba(118,118,128,0.08); padding: 4px 12px;
  border-radius: 12px; margin: 12px 0;
}
.msg-row {
  display: flex; gap: 8px; max-width: 68%;
  animation: bubbleIn 320ms cubic-bezier(0.34, 1.56, 0.64, 1) both;
}
.msg-row.sent { align-self: flex-end; flex-direction: row-reverse; }
.msg-row.received { align-self: flex-start; }
.msg-row .row-avatar {
  width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-size: 11px; font-weight: 600;
  align-self: flex-end;
}
.msg-row .row-avatar.hidden-av { visibility: hidden; }
.bubble {
  padding: 10px 14px; font-size: 15px; line-height: 1.4;
  word-break: break-word; white-space: pre-wrap;
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}
.bubble.sent {
  background: linear-gradient(135deg, #0A84FF 0%, #5E5CE6 100%);
  color: #FFFFFF;
  border-radius: 20px 20px 6px 20px;
}
.bubble.received {
  background: #E9E9EB; color: #000000;
  border-radius: 20px 20px 20px 6px;
}
.bubble .status {
  font-size: 11px; opacity: 0.7; margin-left: 4px;
}
.bubble.sending { opacity: 0.6; }
.bubble.failed {
  background: linear-gradient(135deg, #FF3B30 0%, #D32F2F 100%) !important;
}
.bubble .retry-link {
  font-size: 11px; color: #FFD0CF; cursor: pointer;
  text-decoration: underline; margin-left: 6px;
}
.group-ts {
  font-size: 11px; color: #8E8E93; font-weight: 500;
  margin-top: 2px; padding: 0 4px;
}
.group-ts.sent-ts { text-align: right; align-self: flex-end; }
.group-ts.recv-ts { text-align: left; margin-left: 36px; }
.msg-gap-sm { height: 4px; }
.msg-gap-lg { height: 12px; }

/* ── New message pill ────────────────────────────────────── */
.new-msg-pill {
  position: absolute; bottom: 80px; left: 50%; transform: translateX(-50%);
  background: #0A84FF; color: #fff; padding: 6px 16px;
  border-radius: 20px; font-size: 13px; font-weight: 600;
  cursor: pointer; box-shadow: 0 2px 12px rgba(10,132,255,0.3);
  z-index: 5; display: none;
  transition: opacity 200ms;
}

/* ── Input bar ───────────────────────────────────────────── */
.input-wrap {
  padding: 12px 20px 20px; flex-shrink: 0;
}
.input-pill {
  display: flex; align-items: flex-end; gap: 0;
  background: rgba(118,118,128,0.12);
  border-radius: 24px; padding: 6px 6px 6px 18px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
  transition: box-shadow 200ms;
}
.input-pill.focused {
  box-shadow: 0 0 0 3px rgba(10,132,255,0.25), 0 4px 20px rgba(0,0,0,0.08);
}
.input-pill textarea {
  flex: 1; border: none; background: transparent; outline: none;
  font-size: 16px; font-family: inherit; color: #000;
  resize: none; line-height: 1.4; padding: 6px 0;
  min-height: 24px; max-height: 120px; height: 24px;
}
.input-pill textarea::placeholder { color: #8E8E93; }
.send-btn {
  width: 36px; height: 36px; border-radius: 50%; border: none;
  background: linear-gradient(135deg, #0A84FF 0%, #5E5CE6 100%);
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; flex-shrink: 0;
  transition: transform 120ms, opacity 120ms;
}
.send-btn:hover { transform: scale(1.05); }
.send-btn:active { transform: scale(0.92); }
.send-btn:disabled { opacity: 0.4; cursor: default; transform: none; }
.send-btn svg { fill: #fff; width: 16px; height: 16px; }

/* ── Chat area state toggling ────────────────────────────── */
.chat[data-state="empty"] .chat-header,
.chat[data-state="empty"] .messages,
.chat[data-state="empty"] .input-wrap,
.chat[data-state="empty"] .new-msg-pill { display: none; }
.chat[data-state="active"] .empty-state { display: none; }

/* ── Dark mode ───────────────────────────────────────────── */
@media (prefers-color-scheme: dark) {
  body {
    background: #000000;
    background-image:
      radial-gradient(ellipse at 80% 10%, rgba(10,132,255,0.06) 0%, transparent 60%),
      radial-gradient(ellipse at 90% 25%, rgba(94,92,230,0.05) 0%, transparent 50%);
    color: #F2F2F7;
  }
  .sidebar {
    background: rgba(28,28,30,0.72);
    border-right-color: rgba(84,84,88,0.65);
  }
  .sidebar-title, .user-item .info .name,
  .chat-header .name, .empty-state .title { color: #F2F2F7; }
  .search-box { background: rgba(118,118,128,0.24); }
  .search-box input { color: #F2F2F7; }
  .chat-header {
    background: rgba(28,28,30,0.72);
    border-bottom-color: rgba(84,84,88,0.65);
  }
  .bubble.received { background: #26262A; color: #F2F2F7; }
  .input-pill { background: rgba(118,118,128,0.24); }
  .input-pill textarea { color: #F2F2F7; }
  .day-divider { background: rgba(118,118,128,0.16); }
  .user-item.active { background: rgba(10,132,255,0.2); }
  .card-shadow { box-shadow: 0 1px 2px rgba(0,0,0,0.12), 0 4px 14px rgba(0,0,0,0.18); }
}

/* ── Responsive ──────────────────────────────────────────── */
@media (max-width: 767px) {
  .sidebar { width: 100%; }
  .chat { display: none; }
  body.chat-open .sidebar { display: none; }
  body.chat-open .chat { display: flex; width: 100%; }
  .chat-header .back-btn { display: flex; }
}
</style>
</head>
<body>

<!-- ═══ Sidebar ═══ -->
<div class="sidebar">
  <div class="sidebar-top">
    <span class="sidebar-title">Messages</span>
    <div class="my-avatar" id="my-avatar"></div>
  </div>
  <div class="search-wrap">
    <div class="search-box">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
        <circle cx="7" cy="7" r="5.2"/><line x1="10.8" y1="10.8" x2="14" y2="14"/>
      </svg>
      <input type="text" id="search-input" placeholder="Search" autocomplete="off">
    </div>
  </div>
  <div class="user-list" id="user-list"></div>
</div>

<!-- ═══ Chat area ═══ -->
<div class="chat" id="chat" data-state="empty">
  <!-- Header -->
  <div class="chat-header" id="chat-header">
    <button class="back-btn" id="back-btn" aria-label="Back to conversations">
      <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="13,3 6,10 13,17"/>
      </svg>
    </button>
    <div class="avatar" id="peer-avatar"></div>
    <span class="name" id="peer-name"></span>
    <button class="menu-btn" aria-label="More options">
      <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
        <circle cx="10" cy="4" r="1.6"/><circle cx="10" cy="10" r="1.6"/><circle cx="10" cy="16" r="1.6"/>
      </svg>
    </button>
  </div>

  <!-- Empty state -->
  <div class="empty-state">
    <svg viewBox="0 0 72 72" fill="none">
      <defs>
        <linearGradient id="esg" x1="0" y1="0" x2="72" y2="72" gradientUnits="userSpaceOnUse">
          <stop offset="0%" stop-color="#0A84FF"/><stop offset="100%" stop-color="#5E5CE6"/>
        </linearGradient>
      </defs>
      <rect x="8" y="14" width="56" height="40" rx="16" fill="url(#esg)" opacity="0.15"/>
      <rect x="12" y="18" width="48" height="32" rx="12" fill="url(#esg)" opacity="0.3"/>
      <path d="M20 32h20M20 38h12" stroke="url(#esg)" stroke-width="3" stroke-linecap="round"/>
    </svg>
    <span class="title">Select a conversation</span>
    <span class="sub">Pick someone from the sidebar to start chatting</span>
  </div>

  <!-- Messages -->
  <div class="messages" id="messages" role="log" aria-live="polite"></div>

  <!-- New message pill -->
  <div class="new-msg-pill" id="new-pill">↓ New message</div>

  <!-- Input -->
  <div class="input-wrap">
    <div class="input-pill" id="input-pill">
      <textarea id="msg-input" placeholder="Message..." rows="1" maxlength="5000" autocomplete="off"></textarea>
      <button class="send-btn" id="send-btn" aria-label="Send message" disabled>
        <svg viewBox="0 0 16 16"><path d="M1.5 8.5l5-5.5v4h8v3h-8v4z" transform="rotate(-45 8 8)" fill="#fff"/></svg>
      </button>
    </div>
  </div>
</div>

<script>
(function () {
  'use strict';

  /* ── Constants & state ───────────────────────────────── */
  var myId       = <?= (int) $me['id'] ?>;
  var myName     = <?= json_encode($me['username']) ?>;
  var activeUser = null;
  var seenIds    = new Set();
  var lastSeenId = 0;
  var pollTimer  = null;
  var userTimer  = null;
  var convToken  = 0;
  var tempIdCounter = 0;
  var allUsers   = [];
  var prevMsgs   = [];

  var GRADIENTS = [
    'linear-gradient(135deg, #FF6B6B, #FF8E53)',
    'linear-gradient(135deg, #4ECDC4, #2ECC71)',
    'linear-gradient(135deg, #0A84FF, #5E5CE6)',
    'linear-gradient(135deg, #A78BFA, #EC4899)',
    'linear-gradient(135deg, #F59E0B, #EF4444)',
    'linear-gradient(135deg, #06B6D4, #3B82F6)'
  ];

  /* ── DOM refs ────────────────────────────────────────── */
  var userListEl   = document.getElementById('user-list');
  var chatEl       = document.getElementById('chat');
  var peerAvatarEl = document.getElementById('peer-avatar');
  var peerNameEl   = document.getElementById('peer-name');
  var messagesEl   = document.getElementById('messages');
  var msgInput     = document.getElementById('msg-input');
  var sendBtn      = document.getElementById('send-btn');
  var inputPill    = document.getElementById('input-pill');
  var searchInput  = document.getElementById('search-input');
  var newPill      = document.getElementById('new-pill');
  var backBtn      = document.getElementById('back-btn');
  var myAvatarEl   = document.getElementById('my-avatar');

  /* ── Helpers ─────────────────────────────────────────── */
  function hashName(s) {
    var h = 0;
    for (var i = 0; i < s.length; i++) h += s.charCodeAt(i);
    return h % GRADIENTS.length;
  }
  function gradient(name) { return GRADIENTS[hashName(name)]; }
  function initial(name)  { return name.charAt(0).toUpperCase(); }

  function formatTime(ts) {
    var d = new Date(ts.replace(' ', 'T'));
    if (isNaN(d.getTime())) return '';
    return pad(d.getHours()) + ':' + pad(d.getMinutes());
  }
  function pad(n) { return n < 10 ? '0' + n : '' + n; }

  function formatSidebarTime(ts) {
    if (!ts) return '';
    var d = new Date(ts.replace(' ', 'T'));
    if (isNaN(d.getTime())) return '';
    var now = new Date();
    var today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    var msgDay = new Date(d.getFullYear(), d.getMonth(), d.getDate());
    var diff = (today - msgDay) / 86400000;
    if (diff < 1) return pad(d.getHours()) + ':' + pad(d.getMinutes());
    if (diff < 2) return 'Yesterday';
    if (diff < 7) return ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][d.getDay()];
    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return months[d.getMonth()] + ' ' + d.getDate();
  }

  function dayLabel(ts) {
    var d = new Date(ts.replace(' ', 'T'));
    if (isNaN(d.getTime())) return '';
    var now = new Date();
    var today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    var msgDay = new Date(d.getFullYear(), d.getMonth(), d.getDate());
    var diff = (today - msgDay) / 86400000;
    if (diff < 1) return 'Today';
    if (diff < 2) return 'Yesterday';
    var days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return days[d.getDay()] + ', ' + months[d.getMonth()] + ' ' + d.getDate();
  }

  function dateKey(ts) {
    var d = new Date(ts.replace(' ', 'T'));
    return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate());
  }

  function isNearBottom() {
    return messagesEl.scrollHeight - messagesEl.scrollTop - messagesEl.clientHeight < 60;
  }
  function scrollToBottom(smooth) {
    messagesEl.scrollTo({ top: messagesEl.scrollHeight, behavior: smooth ? 'smooth' : 'auto' });
  }

  /* ── My avatar ───────────────────────────────────────── */
  myAvatarEl.style.background = gradient(myName);
  myAvatarEl.textContent = initial(myName);

  /* ── Load users (sidebar) ────────────────────────────── */
  function loadUsers() {
    fetch('api.php?action=users')
      .then(function (r) { return r.json(); })
      .then(function (users) {
        allUsers = users;
        renderUserList();
      })
      .catch(function () {});
  }

  function renderUserList() {
    var filter = searchInput.value.trim().toLowerCase();
    userListEl.innerHTML = '';
    allUsers.forEach(function (u) {
      if (filter && u.username.toLowerCase().indexOf(filter) === -1) return;

      var btn = document.createElement('button');
      btn.className = 'user-item';
      if (activeUser && activeUser.id === u.id) btn.classList.add('active');

      // Avatar
      var av = document.createElement('div');
      av.className = 'avatar';
      av.style.background = gradient(u.username);
      av.textContent = initial(u.username);
      btn.appendChild(av);

      // Info
      var info = document.createElement('div');
      info.className = 'info';
      var nameSpan = document.createElement('div');
      nameSpan.className = 'name';
      nameSpan.textContent = u.username;
      info.appendChild(nameSpan);
      if (u.last_message) {
        var prev = document.createElement('div');
        prev.className = 'preview';
        prev.textContent = u.last_message;
        info.appendChild(prev);
      }
      btn.appendChild(info);

      // Meta (time + badge)
      var meta = document.createElement('div');
      meta.className = 'meta';
      if (u.last_message_time) {
        var ts = document.createElement('span');
        ts.className = 'time';
        ts.textContent = formatSidebarTime(u.last_message_time);
        meta.appendChild(ts);
      }
      if (u.unread_count > 0) {
        var badge = document.createElement('span');
        badge.className = 'badge';
        badge.textContent = u.unread_count > 99 ? '99+' : u.unread_count;
        meta.appendChild(badge);
      }
      btn.appendChild(meta);

      btn.addEventListener('click', function () {
        openConversation(u.id, u.username);
      });
      userListEl.appendChild(btn);
    });
  }

  searchInput.addEventListener('input', renderUserList);

  /* ── Open conversation ───────────────────────────────── */
  function openConversation(userId, username) {
    activeUser = { id: userId, username: username };
    seenIds.clear();
    lastSeenId = 0;
    prevMsgs = [];
    convToken++;

    chatEl.dataset.state = 'active';
    peerAvatarEl.style.background = gradient(username);
    peerAvatarEl.textContent = initial(username);
    peerNameEl.textContent = username;
    messagesEl.innerHTML = '';
    msgInput.value = '';
    msgInput.style.height = '24px';
    newPill.style.display = 'none';
    msgInput.focus();

    renderUserList();

    if (pollTimer) clearInterval(pollTimer);
    loadMessages();
    pollTimer = setInterval(loadMessages, 700);
    markRead(userId);

    document.body.classList.add('chat-open');
  }

  /* ── Load messages (delta) ───────────────────────────── */
  function loadMessages() {
    if (!activeUser) return;
    var token = convToken;

    fetch('api.php?action=messages&with=' + activeUser.id + '&since=' + lastSeenId)
      .then(function (r) { return r.json(); })
      .then(function (msgs) {
        if (token !== convToken) return;
        if (!msgs.length) return;

        var wasBottom = isNearBottom();
        var hasNewReceived = false;

        msgs.forEach(function (m) {
          if (seenIds.has(m.id)) return;
          seenIds.add(m.id);
          if (m.id > lastSeenId) lastSeenId = m.id;

          // Check if this message was optimistically added
          var tempEl = document.querySelector('[data-temp-text="' + CSS.escape(m.text) + '"][data-temp-to="' + m.receiver_id + '"]');
          if (m.sender_id === myId && tempEl) {
            tempEl.classList.remove('sending');
            var st = tempEl.querySelector('.status');
            if (st) st.textContent = ' ✓✓';
            tempEl.removeAttribute('data-temp-text');
            tempEl.removeAttribute('data-temp-to');
            return;
          }

          if (m.sender_id !== myId) hasNewReceived = true;
          appendMessage(m, true);
        });

        if (wasBottom) {
          scrollToBottom(true);
        } else if (hasNewReceived) {
          newPill.style.display = 'block';
        }

        if (hasNewReceived) markRead(activeUser.id);
      })
      .catch(function () {});
  }

  /* ── Append a message ────────────────────────────────── */
  function appendMessage(m, animate) {
    var isSent = m.sender_id === myId;
    var peerName = activeUser ? activeUser.username : '';

    // Day divider
    var mDate = dateKey(m.created_at);
    var lastMsg = prevMsgs.length > 0 ? prevMsgs[prevMsgs.length - 1] : null;
    var lastDate = lastMsg ? dateKey(lastMsg.created_at) : null;

    if (mDate !== lastDate) {
      var divider = document.createElement('div');
      divider.className = 'day-divider';
      divider.textContent = dayLabel(m.created_at);
      messagesEl.appendChild(divider);
    }

    // Grouping logic
    var sameGroup = false;
    if (lastMsg && lastMsg.sender_id === m.sender_id) {
      var t1 = new Date(lastMsg.created_at.replace(' ', 'T')).getTime();
      var t2 = new Date(m.created_at.replace(' ', 'T')).getTime();
      if (Math.abs(t2 - t1) < 60000) sameGroup = true;
    }

    // Gap
    if (lastMsg) {
      var gap = document.createElement('div');
      gap.className = sameGroup ? 'msg-gap-sm' : 'msg-gap-lg';
      messagesEl.appendChild(gap);
    }

    // If starting a new group and there was a previous group, add timestamp to last group
    if (!sameGroup && prevMsgs.length > 0) {
      addGroupTimestamp(lastMsg);
    }

    // Message row
    var row = document.createElement('div');
    row.className = 'msg-row ' + (isSent ? 'sent' : 'received');
    if (animate) row.style.animationDuration = '320ms';
    else row.style.animation = 'none';

    // Avatar for received
    if (!isSent) {
      var av = document.createElement('div');
      av.className = 'row-avatar';
      if (sameGroup) {
        av.classList.add('hidden-av');
      } else {
        av.style.background = gradient(peerName);
        av.textContent = initial(peerName);
      }
      row.appendChild(av);
    }

    // Bubble
    var bubble = document.createElement('div');
    bubble.className = 'bubble ' + (isSent ? 'sent' : 'received');
    if (m._sending) bubble.classList.add('sending');
    if (m._failed) bubble.classList.add('failed');

    if (m._tempText !== undefined) {
      bubble.setAttribute('data-temp-text', m._tempText);
      bubble.setAttribute('data-temp-to', m._tempTo);
    }

    var textNode = document.createElement('span');
    textNode.textContent = m.text;
    bubble.appendChild(textNode);

    if (isSent) {
      var status = document.createElement('span');
      status.className = 'status';
      if (m._sending) status.textContent = ' ✓';
      else if (m._failed) {
        status.textContent = '';
        var retry = document.createElement('span');
        retry.className = 'retry-link';
        retry.textContent = 'Retry';
        retry.addEventListener('click', function () {
          bubble.classList.remove('failed');
          bubble.classList.add('sending');
          status.textContent = ' ✓';
          if (retry.parentNode) retry.parentNode.removeChild(retry);
          doSend(m.text, bubble);
        });
        bubble.appendChild(retry);
      } else {
        status.textContent = ' ✓✓';
      }
      bubble.appendChild(status);
    }

    row.appendChild(bubble);
    messagesEl.appendChild(row);
    row.dataset.msgId = m.id || '';

    prevMsgs.push(m);
  }

  function addGroupTimestamp(m) {
    if (!m) return;
    var ts = document.createElement('div');
    ts.className = 'group-ts ' + (m.sender_id === myId ? 'sent-ts' : 'recv-ts');
    ts.textContent = formatTime(m.created_at);
    messagesEl.appendChild(ts);
  }

  /* ── Send message ────────────────────────────────────── */
  function sendMessage() {
    var text = msgInput.value.trim();
    if (!text || !activeUser) return;

    msgInput.value = '';
    msgInput.style.height = '24px';
    sendBtn.disabled = true;

    // Optimistic append
    tempIdCounter--;
    var now = new Date();
    var nowStr = now.getFullYear() + '-' + pad(now.getMonth()+1) + '-' + pad(now.getDate()) +
                ' ' + pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
    var tempMsg = {
      id: tempIdCounter,
      sender_id: myId,
      receiver_id: activeUser.id,
      text: text,
      created_at: nowStr,
      _sending: true,
      _tempText: text,
      _tempTo: activeUser.id
    };

    var wasBottom = isNearBottom();
    appendMessage(tempMsg, true);
    if (wasBottom) scrollToBottom(true);

    var bubble = messagesEl.querySelector('[data-temp-text="' + CSS.escape(text) + '"][data-temp-to="' + activeUser.id + '"]');
    doSend(text, bubble);
  }

  function doSend(text, bubble) {
    fetch('api.php?action=send', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ to: activeUser.id, text: text })
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (data.ok && data.id) {
        seenIds.add(data.id);
        if (data.id > lastSeenId) lastSeenId = data.id;
        if (bubble) {
          bubble.classList.remove('sending');
          var st = bubble.querySelector('.status');
          if (st) st.textContent = ' ✓✓';
          bubble.removeAttribute('data-temp-text');
          bubble.removeAttribute('data-temp-to');
        }
      }
      sendBtn.disabled = !msgInput.value.trim();
    })
    .catch(function () {
      if (bubble) {
        bubble.classList.remove('sending');
        bubble.classList.add('failed');
        var st = bubble.querySelector('.status');
        if (st) st.textContent = '';
        var retry = document.createElement('span');
        retry.className = 'retry-link';
        retry.textContent = 'Retry';
        retry.addEventListener('click', function () {
          bubble.classList.remove('failed');
          bubble.classList.add('sending');
          if (st) st.textContent = ' ✓';
          if (retry.parentNode) retry.parentNode.removeChild(retry);
          doSend(text, bubble);
        });
        bubble.appendChild(retry);
      }
      sendBtn.disabled = !msgInput.value.trim();
    });
  }

  /* ── Mark read ───────────────────────────────────────── */
  function markRead(userId) {
    fetch('api.php?action=mark_read', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ with: userId })
    }).catch(function () {});
  }

  /* ── Event listeners ─────────────────────────────────── */
  sendBtn.addEventListener('click', sendMessage);
  msgInput.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  });
  msgInput.addEventListener('input', function () {
    this.style.height = '24px';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    sendBtn.disabled = !this.value.trim();
  });
  msgInput.addEventListener('focus', function () { inputPill.classList.add('focused'); });
  msgInput.addEventListener('blur', function () { inputPill.classList.remove('focused'); });
  newPill.addEventListener('click', function () {
    scrollToBottom(true);
    newPill.style.display = 'none';
  });
  messagesEl.addEventListener('scroll', function () {
    if (isNearBottom()) newPill.style.display = 'none';
  });
  backBtn.addEventListener('click', function () {
    document.body.classList.remove('chat-open');
  });

  /* ── Init ────────────────────────────────────────────── */
  loadUsers();
  userTimer = setInterval(loadUsers, 5000);

  // Flush last group timestamp when conversation ends/switches
  var origOpen = openConversation;
  // Already handled in appendMessage logic
})();
</script>
</body>
</html>
