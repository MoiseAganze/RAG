<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

$user        = requireAuth('login.php');
$currentPage = 'chat';

// Active conversation from URL
$pdo         = getPDO();
$activeConvId = 0;
if (!empty($_GET['conv'])) {
    $c = $pdo->prepare("SELECT id FROM conversations WHERE id = ? AND user_id = ?");
    $c->execute([(int)$_GET['conv'], $user['id']]);
    if ($row = $c->fetch()) $activeConvId = (int)$row['id'];
}
$startDraft = $activeConvId === 0 && (($_GET['new'] ?? '') === '1');
?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>RAG — Chat</title>
  <link rel="stylesheet" href="assets/app.css" />
  
  <!-- ─── Markdown Parsers ────────────────────────────────────────── -->
  <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.0.6/purify.min.js"></script>
  <script>
    if (typeof marked !== 'undefined') {
      marked.setOptions({
        breaks: true,
        gfm: true
      });
    }
  </script>

  <style>
    html, body { height: 100%; overflow: hidden; }

    /* ─── Chat area ───────────────────────────────────────────────── */
    #chat-messages {
      flex: 1;
      overflow-y: auto;
      padding: 32px 16px 20px;
      scroll-behavior: smooth;
    }

    #messages-inner {
      max-width: 700px;
      margin: 0 auto;
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    /* ─── Empty state ─────────────────────────────────────────────── */
    .empty-state {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 12px;
      color: var(--text-muted);
      text-align: center;
      padding: 60px 24px;
    }
    .empty-logo {
      font-family: var(--font-serif);
      font-size: 52px;
      font-style: italic;
      color: var(--accent);
      opacity: 0.5;
      line-height: 1;
    }
    .empty-sub { font-size: 14px; color: var(--text-muted); }

    /* ─── Message bubbles ─────────────────────────────────────────── */
    .msg-enter { animation: msgIn 0.22s ease-out; }
    @keyframes msgIn {
      from { opacity: 0; transform: translateY(8px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .msg-ai   { display: flex; align-items: flex-start; gap: 12px; }
    .msg-user { display: flex; align-items: flex-end; justify-content: flex-end; gap: 12px; }

    .ai-avatar {
      width: 28px; height: 28px;
      border-radius: 50%;
      background: var(--accent-dim);
      border: 1px solid rgba(201,169,110,0.3);
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
      font-family: var(--font-serif);
      font-style: italic;
      font-size: 12px;
      color: var(--accent);
    }

    .msg-ai-body   { display: flex; flex-direction: column; gap: 4px; max-width: 82%; }
    .msg-user-body { display: flex; flex-direction: column; gap: 4px; align-items: flex-end; max-width: 76%; }
    .msg-label     { font-size: 11px; color: var(--text-muted); padding-left: 2px; }

    .bubble-ai {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 18px;
      border-top-left-radius: 5px;
      padding: 13px 17px;
      font-size: 14.5px;
      line-height: 1.65;
      color: var(--text);
      white-space: pre-wrap;
      word-break: break-word;
      transition: background var(--transition), border-color var(--transition);
    }

    .bubble-user {
      background: var(--surface-2);
      border: 1px solid var(--border-strong);
      border-radius: 18px;
      border-bottom-right-radius: 5px;
      padding: 13px 17px;
      font-size: 14.5px;
      line-height: 1.65;
      color: var(--text);
      white-space: pre-wrap;
      word-break: break-word;
      transition: background var(--transition), border-color var(--transition);
    }

    .bubble-error {
      background: var(--error-bg);
      border: 1px solid var(--error-border);
      color: var(--error);
      border-radius: 18px;
      border-top-left-radius: 5px;
      padding: 13px 17px;
      font-size: 14px;
      line-height: 1.6;
    }

    .typing-dots { display: flex; align-items: center; gap: 4px; padding: 2px 0; }
    .dot {
      width: 6px; height: 6px; border-radius: 50%;
      background: var(--text-muted);
      animation: dotPulse 1.4s infinite ease-in-out;
    }
    .dot:nth-child(2) { animation-delay: 0.2s; }
    .dot:nth-child(3) { animation-delay: 0.4s; }
    @keyframes dotPulse {
      0%, 80%, 100% { opacity: 0.3; transform: scale(0.8); }
      40%            { opacity: 1;   transform: scale(1); }
    }

    /* ─── Input bar ───────────────────────────────────────────────── */
    .input-bar {
      flex-shrink: 0;
      padding: 14px 20px 18px;
      background: var(--bg);
      transition: background var(--transition);
    }

    .input-inner {
      max-width: 700px;
      margin: 0 auto;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 16px;
      overflow: hidden;
      transition: border-color 0.15s ease, box-shadow 0.15s ease, background var(--transition);
      box-shadow: 0 2px 10px rgba(0,0,0,0.07);
    }
    .input-inner:focus-within {
      border-color: rgba(201,169,110,0.4);
      box-shadow: 0 0 0 3px rgba(201,169,110,0.07);
    }

    #user-input {
      width: 100%;
      background: transparent;
      border: none;
      outline: none;
      padding: 14px 18px 4px;
      font-family: var(--font-sans);
      font-size: 14.5px;
      color: var(--text);
      resize: none;
      line-height: 1.6;
      max-height: 160px;
      field-sizing: content;
    }
    #user-input::placeholder { color: var(--text-muted); }

    .input-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 6px 12px 10px 18px;
    }
    .input-hint { font-size: 11.5px; color: var(--text-muted); }

    .send-btn {
      width: 34px; height: 34px;
      border-radius: 10px;
      border: none;
      background: var(--accent);
      color: #0f0f0f;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer;
      transition: all 0.15s ease;
      flex-shrink: 0;
    }
    .send-btn:hover:not(:disabled) { background: #d4b47a; transform: scale(1.04); }
    .send-btn:disabled { opacity: 0.4; cursor: not-allowed; transform: none; }
    .send-btn svg { width: 15px; height: 15px; }

    @media (max-width: 768px) {
      .input-bar {
        padding: 10px;
      }
      .input-inner {
        border-radius: 14px;
      }
      #user-input {
        padding: 10px 14px 4px;
      }
      .input-footer {
        padding: 4px 8px 8px 14px;
      }
      .input-hint {
        display: none;
      }
    }
  </style>
</head>
<body>
<div id="toast-container"></div>
<div class="app-layout">

  <?php require_once 'includes/sidebar.php'; ?>

  <!-- ─── Main chat area ─────────────────────────────────────── -->
  <div class="main-content" id="chat-col">

    <div class="mobile-header">
      <button class="hamburger-btn" onclick="toggleSidebar()">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
      </button>
      <div class="mobile-header-title" style="font-size: 14px;">Retrieval-Augmented Generation</div>
      <div style="width:24px;"></div>
    </div>

    <?php if (!$activeConvId && !$startDraft): ?>
      <!-- No conversation selected -->
      <div class="empty-state" id="empty-state">
        <div class="empty-logo" style="font-size: 26px;">Assistant IA d’analyse de texte juridique DGR/KC</div>
        <p class="empty-sub">Créez une nouvelle conversation ou sélectionnez-en une dans la liste.</p>
      </div>
    <?php else: ?>
      <!-- Active conversation -->
      <div id="chat-messages">
        <div id="messages-inner"></div>
      </div>
      <div class="input-bar">
        <div class="input-inner">
          <textarea id="user-input" rows="1" placeholder="Posez votre question…"></textarea>
          <div class="input-footer">
            <span class="input-hint">Maj+Entrée pour un saut de ligne</span>
            <button class="send-btn" id="send-btn" title="Envoyer">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                <path d="M3.478 2.405a.75.75 0 00-.926.94l2.432 7.905H13.5a.75.75 0 010 1.5H4.984l-2.432 7.905a.75.75 0 00.926.94 60.519 60.519 0 0018.445-8.986.75.75 0 000-1.218A60.517 60.517 0 003.478 2.405z"/>
              </svg>
            </button>
          </div>
        </div>
      </div>
    <?php endif; ?>

  </div>
</div>

<script>
  // ─── Theme ──────────────────────────────────────────────────────────────
  function applyTheme(t) {
    document.documentElement.setAttribute('data-theme', t);
    localStorage.setItem('rag-theme', t);
  }
  applyTheme(localStorage.getItem('rag-theme') || 'dark');

  // ─── State ──────────────────────────────────────────────────────────────
  let currentConvId = <?php echo $activeConvId ?>;
  let isDraftConversation = <?php echo $startDraft ? 'true' : 'false' ?>;

  // ─── Utils ──────────────────────────────────────────────────────────────
  function escapeHtml(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
  }

  function showToast(msg, type = 'success') {
    const icon = type === 'success'
      ? `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`
      : `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>`;
    const t = document.createElement('div');
    t.className = `toast toast-${type}`;
    t.innerHTML = `${icon}<span>${escapeHtml(msg)}</span>`;
    document.getElementById('toast-container').appendChild(t);
    setTimeout(() => t.remove(), 4000);
  }

  // ─── Chat helpers ────────────────────────────────────────────────────────
  function getMessages() { return document.getElementById('messages-inner'); }
  function getChatArea() { return document.getElementById('chat-messages'); }

  function renderConversationShell() {
    const chatCol = document.getElementById('chat-col');
    chatCol.innerHTML = `
      <div class="mobile-header">
        <button class="hamburger-btn" onclick="toggleSidebar()">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
        </button>
        <div class="mobile-header-title">Rag</div>
        <div style="width:24px;"></div>
      </div>
      <div id="chat-messages"><div id="messages-inner"></div></div>
      <div class="input-bar">
        <div class="input-inner">
          <textarea id="user-input" rows="1" placeholder="Posez votre question…"></textarea>
          <div class="input-footer">
            <span class="input-hint">Maj+Entrée pour un saut de ligne</span>
            <button class="send-btn" id="send-btn" title="Envoyer">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3.478 2.405a.75.75 0 00-.926.94l2.432 7.905H13.5a.75.75 0 010 1.5H4.984l-2.432 7.905a.75.75 0 00.926.94 60.519 60.519 0 0018.445-8.986.75.75 0 000-1.218A60.517 60.517 0 003.478 2.405z"/></svg>
            </button>
          </div>
        </div>
      </div>`;
    attachInputEvents();
  }

  function scrollBottom() {
    const c = getChatArea();
    if (c) c.scrollTo({ top: c.scrollHeight, behavior: 'smooth' });
  }

  function appendUserMsg(text) {
    const el = document.createElement('div');
    el.className = 'msg-user msg-enter';
    const parsed = typeof marked !== 'undefined' ? DOMPurify.sanitize(marked.parse(text)) : escapeHtml(text);
    el.innerHTML = `<div class="msg-user-body"><span class="msg-label">Vous</span><div class="bubble-user markdown-body">${parsed}</div></div>`;
    getMessages().appendChild(el);
    scrollBottom();
  }

  function appendAiMsg(text) {
    const el = document.createElement('div');
    el.className = 'msg-ai msg-enter';
    const parsed = typeof marked !== 'undefined' ? DOMPurify.sanitize(marked.parse(text)) : escapeHtml(text);
    el.innerHTML = `
      <div class="ai-avatar"><img src="/assets/images/logo.png" alt="" style="width:42px;height:42px;" /></div>
      <div class="msg-ai-body"><span class="msg-label" style="font-size:16px;">IA</span><div class="bubble-ai markdown-body">${parsed}</div></div>`;
    getMessages().appendChild(el);
    scrollBottom();
  }

  function appendWelcomeMsg() {
    const inner = getMessages();
    if (!inner) return;
    const txt = "Bonjour ! Je suis votre Assistant IA d’analyse de texte juridique DGR/KC.";
    const parsed = typeof marked !== 'undefined' ? DOMPurify.sanitize(marked.parse(txt)) : escapeHtml(txt);
    const el = document.createElement('div');
    el.className = 'msg-ai msg-enter';
    el.innerHTML = `<div class="ai-avatar"><img src="/assets/images/logo.png" alt="" style="width:42px;height:42px;" /></div><div class="msg-ai-body"><span class="msg-label" style="font-size:16px;">IA</span><div class="bubble-ai markdown-body">${parsed}</div></div>`;
    inner.appendChild(el);
    scrollBottom();
  }

  function appendTyping() {
    const el = document.createElement('div');
    el.id = 'typing';
    el.className = 'msg-ai msg-enter';
    el.innerHTML = `
      <div class="ai-avatar">R</div>
      <div class="msg-ai-body"><span class="msg-label">RAG</span>
        <div class="bubble-ai" style="padding:14px 17px">
          <div class="typing-dots"><div class="dot"></div><div class="dot"></div><div class="dot"></div></div>
        </div>
      </div>`;
    getMessages().appendChild(el);
    scrollBottom();
  }
  function removeTyping() { const el = document.getElementById('typing'); if (el) el.remove(); }

  function appendErrMsg(msg) {
    const el = document.createElement('div');
    el.className = 'msg-ai msg-enter';
    el.innerHTML = `
      <div class="ai-avatar" style="background:var(--error-bg);border-color:var(--error-border);color:var(--error);">!</div>
      <div class="msg-ai-body"><span class="msg-label">Erreur</span><div class="bubble-error">${escapeHtml(msg)}</div></div>`;
    getMessages().appendChild(el);
    scrollBottom();
  }

  function setLoading(on) {
    const btn = document.getElementById('send-btn');
    const inp = document.getElementById('user-input');
    if (!btn) return;
    btn.disabled = on;
    inp.disabled = on;
    btn.innerHTML = on
      ? `<div class="spinner" style="border-color:rgba(0,0,0,0.2);border-top-color:#0f0f0f;"></div>`
      : `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3.478 2.405a.75.75 0 00-.926.94l2.432 7.905H13.5a.75.75 0 010 1.5H4.984l-2.432 7.905a.75.75 0 00.926.94 60.519 60.519 0 0018.445-8.986.75.75 0 000-1.218A60.517 60.517 0 003.478 2.405z"/></svg>`;
    if (!on) inp.focus();
  }

  // ─── Load messages for a conversation ───────────────────────────────────
  async function loadMessages(convId) {
    const inner = getMessages();
    if (!inner) return;
    inner.innerHTML = `<div style="display:flex;justify-content:center;padding:40px 0;"><div class="spinner spinner-light" style="width:20px;height:20px;border-width:2px;"></div></div>`;
    try {
      const res  = await fetch(`api/conv_messages.php?conv_id=${convId}`);
      const data = await res.json();
      inner.innerHTML = '';
      if (!data.success) { appendErrMsg('Impossible de charger les messages.'); return; }
      if (data.messages.length === 0) {
        appendWelcomeMsg();
      } else {
        data.messages.forEach(m => {
          if (m.role === 'user') appendUserMsg(m.content);
          else appendAiMsg(m.content);
        });
      }
      scrollBottom();
    } catch(e) {
      appendErrMsg('Erreur de chargement.');
    }
  }

  // ─── Send message ────────────────────────────────────────────────────────
  async function sendMessage() {
    const inp = document.getElementById('user-input');
    const question = inp.value.trim();
    if (!question) return;
    const draftMode = currentConvId === 0;
    inp.value = '';
    inp.style.height = 'auto';
    appendUserMsg(question);
    setLoading(true);
    appendTyping();
    try {
      const payload = { question };
      if (currentConvId) payload.conv_id = currentConvId;
      const res  = await fetch('api/message_send.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      const data = await res.json();
      removeTyping();
      if (draftMode && data.conv_id) {
        currentConvId = data.conv_id;
        isDraftConversation = false;
        addConvToSidebar(data.conv_id, data.new_title || 'Nouvelle conversation');
        setActiveConv(data.conv_id);
        history.replaceState(null, '', `chat.php?conv=${data.conv_id}`);
      }
      if (!data.success) { appendErrMsg(data.error || 'Erreur inconnue'); return; }
      appendAiMsg(data.answer);
      if (data.new_title) {
        updateSidebarTitle(currentConvId, data.new_title);
      }
    } catch(e) {
      removeTyping();
      appendErrMsg(e.message || 'Erreur de connexion.');
    } finally {
      setLoading(false);
    }
  }

  // ─── Sidebar conversation actions ────────────────────────────────────────
  function updateSidebarTitle(id, title) {
    const el = document.querySelector(`.conv-item[data-id="${id}"] .conv-title`);
    if (el) { el.textContent = title; document.querySelector(`.conv-item[data-id="${id}"]`).dataset.title = title; }
  }

  function setActiveConv(id) {
    document.querySelectorAll('.conv-item').forEach(el => el.classList.remove('active'));
    const el = document.querySelector(`.conv-item[data-id="${id}"]`);
    if (el) el.classList.add('active');
  }

  async function selectConv(id, title) {
    if (id === currentConvId) return;
    renderConversationShell();
    currentConvId = id;
    isDraftConversation = false;
    setActiveConv(id);
    history.pushState(null, '', `chat.php?conv=${id}`);
    await loadMessages(id);
  }

  function renderDraftConversation(updateHistory = true) {
    renderConversationShell();
    currentConvId = 0;
    isDraftConversation = true;
    setActiveConv(0);
    if (updateHistory) {
      history.pushState(null, '', 'chat.php?new=1');
    }
    appendWelcomeMsg();
  }

  function newConversation() {
    if (typeof closeSidebar === 'function') closeSidebar();
    if (currentConvId === 0 && isDraftConversation) {
      document.getElementById('user-input')?.focus();
      return;
    }
    renderDraftConversation();
  }

  function addConvToSidebar(id, title) {
    const section = document.getElementById('conv-section');
    const existing = section.querySelector(`.conv-item[data-id="${id}"]`);
    if (existing) {
      updateSidebarTitle(id, title);
      return;
    }
    section.querySelector('p')?.remove();
    const today   = section.querySelector('.conv-group-label');

    const item = document.createElement('div');
    item.className = 'conv-item';
    item.dataset.id    = id;
    item.dataset.title = title;
    item.onclick = () => selectConv(id, item.dataset.title || title);
    item.innerHTML = `<span class="conv-title">${escapeHtml(title)}</span>`;

    if (!today) {
      const label = document.createElement('div');
      label.className = 'conv-group-label';
      label.textContent = "Aujourd'hui";
      section.prepend(label);
      label.after(item);
    } else {
      today.after(item);
    }
  }

  async function deleteConv(id) {
    if (!confirm('Supprimer cette conversation ?')) return;
    try {
      const res  = await fetch('api/conv_delete.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ conv_id: id }),
      });
      const data = await res.json();
      if (!data.success) { showToast(data.error || 'Erreur', 'error'); return; }
      document.querySelector(`.conv-item[data-id="${id}"]`)?.remove();
      if (currentConvId === id) {
        currentConvId = 0;
        history.pushState(null, '', 'chat.php');
        const chatCol = document.getElementById('chat-col');
        chatCol.innerHTML = `
          <div class="empty-state" id="empty-state">
            <div class="empty-logo">Rag</div>
            <p class="empty-sub">Créez une nouvelle conversation ou sélectionnez-en une dans la liste.</p>
          </div>`;
      }
    } catch(e) { showToast('Erreur de connexion', 'error'); }
  }

  async function renameConv(id, currentTitle) {
    const title = prompt('Renommer la conversation :', currentTitle);
    if (!title || title.trim() === '' || title.trim() === currentTitle) return;
    try {
      const res  = await fetch('api/conv_rename.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ conv_id: id, title: title.trim() }),
      });
      const data = await res.json();
      if (!data.success) { showToast(data.error || 'Erreur', 'error'); return; }
      updateSidebarTitle(id, data.title);
    } catch(e) { showToast('Erreur de connexion', 'error'); }
  }

  // ─── Input events ────────────────────────────────────────────────────────
  function attachInputEvents() {
    const inp = document.getElementById('user-input');
    const btn = document.getElementById('send-btn');
    if (!inp || !btn) return;
    btn.addEventListener('click', sendMessage);
    inp.addEventListener('keydown', e => {
      if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
    });
    inp.addEventListener('input', () => {
      inp.style.height = 'auto';
      inp.style.height = Math.min(inp.scrollHeight, 160) + 'px';
    });
    inp.focus();
  }

  // ─── Init ────────────────────────────────────────────────────────────────
  document.getElementById('new-conv-btn').addEventListener('click', newConversation);

  if (currentConvId) {
    attachInputEvents();
    loadMessages(currentConvId);
  } else if (isDraftConversation) {
    attachInputEvents();
    appendWelcomeMsg();
  } else {
    attachInputEvents();
  }
</script>
</body>
</html>
