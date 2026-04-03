<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>RAG — Chat</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet" />
  <style>
    /* ─── Tokens ─────────────────────────────────────────────────── */
    :root {
      --font-serif: 'Instrument Serif', serif;
      --font-sans:  'DM Sans', sans-serif;
      --radius-bubble: 18px;
      --transition: 0.2s ease;
    }

    [data-theme="dark"] {
      --bg:          #0f0f0f;
      --surface:     #161616;
      --surface-2:   #1e1e1e;
      --border:      rgba(255,255,255,0.07);
      --text:        #e8e8e6;
      --text-muted:  rgba(232,232,230,0.38);
      --accent:      #c9a96e;
      --accent-dim:  rgba(201,169,110,0.12);
      --user-bg:     #1e1e1e;
      --user-border: rgba(255,255,255,0.1);
      --ai-bg:       #161616;
      --ai-border:   rgba(255,255,255,0.06);
      --error-bg:    rgba(255,90,90,0.08);
      --error-border:rgba(255,90,90,0.25);
      --error-text:  #ff7070;
      --scroll-thumb:rgba(255,255,255,0.1);
      --shadow:      0 8px 32px rgba(0,0,0,0.5);
    }

    [data-theme="light"] {
      --bg:          #faf9f7;
      --surface:     #ffffff;
      --surface-2:   #f4f3f0;
      --border:      rgba(0,0,0,0.07);
      --text:        #1a1a18;
      --text-muted:  rgba(26,26,24,0.4);
      --accent:      #9b7a3e;
      --accent-dim:  rgba(155,122,62,0.1);
      --user-bg:     #f0ede8;
      --user-border: rgba(0,0,0,0.08);
      --ai-bg:       #ffffff;
      --ai-border:   rgba(0,0,0,0.06);
      --error-bg:    rgba(200,50,50,0.06);
      --error-border:rgba(200,50,50,0.2);
      --error-text:  #c03030;
      --scroll-thumb:rgba(0,0,0,0.12);
      --shadow:      0 8px 32px rgba(0,0,0,0.1);
    }

    /* ─── Reset & Base ────────────────────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    html, body {
      height: 100%;
      font-family: var(--font-sans);
      font-size: 15px;
      font-weight: 400;
      background: var(--bg);
      color: var(--text);
      overflow: hidden;
      transition: background var(--transition), color var(--transition);
    }

    /* ─── Layout ─────────────────────────────────────────────────── */
    .app { display: flex; flex-direction: column; height: 100vh; }

    /* ─── Navbar ─────────────────────────────────────────────────── */
    .navbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 28px;
      height: 58px;
      background: var(--surface);
      border-bottom: 1px solid var(--border);
      flex-shrink: 0;
      z-index: 10;
      transition: background var(--transition), border-color var(--transition);
    }

    .logo {
      font-family: var(--font-serif);
      font-size: 20px;
      font-style: italic;
      color: var(--accent);
      letter-spacing: -0.02em;
      user-select: none;
    }

    .nav-actions { display: flex; align-items: center; gap: 6px; }

    .nav-link {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 14px;
      border-radius: 8px;
      font-size: 13.5px;
      font-weight: 500;
      text-decoration: none;
      color: var(--text-muted);
      transition: all var(--transition);
    }
    .nav-link:hover { color: var(--text); background: var(--surface-2); }
    .nav-link.active { color: var(--accent); background: var(--accent-dim); }

    .theme-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 34px; height: 34px;
      border-radius: 8px;
      border: 1px solid var(--border);
      background: var(--surface-2);
      color: var(--text-muted);
      cursor: pointer;
      transition: all var(--transition);
      margin-left: 4px;
    }
    .theme-btn:hover { color: var(--text); border-color: var(--accent); }

    /* ─── Chat Area ──────────────────────────────────────────────── */
    #chat-messages {
      flex: 1;
      overflow-y: auto;
      padding: 32px 16px 20px;
      scroll-behavior: smooth;
    }
    #chat-messages::-webkit-scrollbar { width: 4px; }
    #chat-messages::-webkit-scrollbar-track { background: transparent; }
    #chat-messages::-webkit-scrollbar-thumb { background: var(--scroll-thumb); border-radius: 99px; }

    #messages-inner {
      max-width: 720px;
      margin: 0 auto;
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    /* ─── Message Bubbles ────────────────────────────────────────── */
    .msg-enter { animation: msgIn 0.24s ease-out; }
    @keyframes msgIn {
      from { opacity: 0; transform: translateY(10px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* AI message */
    .msg-ai {
      display: flex;
      align-items: flex-start;
      gap: 12px;
    }
    .ai-avatar {
      width: 30px; height: 30px;
      border-radius: 50%;
      background: var(--accent-dim);
      border: 1px solid rgba(201,169,110,0.3);
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      font-family: var(--font-serif);
      font-style: italic;
      font-size: 13px;
      color: var(--accent);
    }
    .msg-ai-body { display: flex; flex-direction: column; gap: 4px; max-width: 82%; }
    .msg-label { font-size: 11px; color: var(--text-muted); padding-left: 2px; letter-spacing: 0.03em; }
    .bubble-ai {
      background: var(--ai-bg);
      border: 1px solid var(--ai-border);
      border-radius: var(--radius-bubble);
      border-top-left-radius: 5px;
      padding: 13px 18px;
      font-size: 14.5px;
      line-height: 1.65;
      color: var(--text);
      white-space: pre-wrap;
      word-break: break-word;
      transition: background var(--transition), border-color var(--transition);
    }

    /* User message */
    .msg-user {
      display: flex;
      align-items: flex-end;
      justify-content: flex-end;
      gap: 12px;
    }
    .msg-user-body { display: flex; flex-direction: column; gap: 4px; align-items: flex-end; max-width: 76%; }
    .bubble-user {
      background: var(--user-bg);
      border: 1px solid var(--user-border);
      border-radius: var(--radius-bubble);
      border-bottom-right-radius: 5px;
      padding: 13px 18px;
      font-size: 14.5px;
      line-height: 1.65;
      color: var(--text);
      white-space: pre-wrap;
      word-break: break-word;
      transition: background var(--transition), border-color var(--transition);
    }

    /* Error */
    .bubble-error {
      background: var(--error-bg);
      border: 1px solid var(--error-border);
      color: var(--error-text);
      border-radius: var(--radius-bubble);
      border-top-left-radius: 5px;
      padding: 13px 18px;
      font-size: 14px;
      line-height: 1.6;
    }
    .avatar-error {
      width: 30px; height: 30px;
      border-radius: 50%;
      background: var(--error-bg);
      border: 1px solid var(--error-border);
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0; font-size: 13px; color: var(--error-text);
    }

    /* ─── Typing indicator ───────────────────────────────────────── */
    .typing-dots { display: flex; align-items: center; gap: 4px; padding: 4px 0; }
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

    /* ─── Input Bar ───────────────────────────────────────────────── */
    .input-bar {
      flex-shrink: 0;
      padding: 16px 20px 20px;
      background: var(--bg);
      transition: background var(--transition);
    }

    .input-inner {
      max-width: 720px;
      margin: 0 auto;
      display: flex;
      flex-direction: column;
      gap: 0;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 16px;
      overflow: hidden;
      transition: border-color 0.15s ease, box-shadow 0.15s ease, background var(--transition);
      box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    }
    .input-inner:focus-within {
      border-color: rgba(201,169,110,0.4);
      box-shadow: 0 0 0 3px rgba(201,169,110,0.08), 0 2px 12px rgba(0,0,0,0.1);
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
      padding: 8px 12px 10px 18px;
    }
    .input-hint { font-size: 12px; color: var(--text-muted); }

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
    .send-btn:hover:not(:disabled) {
      background: #d4b47a;
      transform: scale(1.04);
    }
    .send-btn:disabled { opacity: 0.4; cursor: not-allowed; transform: none; }
    .send-btn svg { width: 16px; height: 16px; }

    .spinner {
      width: 15px; height: 15px;
      border: 2px solid rgba(0,0,0,0.3);
      border-top-color: #0f0f0f;
      border-radius: 50%;
      animation: spin 0.7s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    .input-disclaimer {
      text-align: center;
      font-size: 11.5px;
      color: var(--text-muted);
      margin-top: 10px;
      letter-spacing: 0.01em;
    }
  </style>
</head>
<body>
<div class="app">

  <!-- NAVBAR -->
  <nav class="navbar">
    <span class="logo">Rag</span>
    <div class="nav-actions">
      <a href="indexation.php" class="nav-link">Indexation</a>
      <a href="chat.php" class="nav-link active">Chat</a>
      <button class="theme-btn" id="theme-btn" title="Basculer le thème" onclick="toggleTheme()">
        <svg id="icon-moon" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        <svg id="icon-sun" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
      </button>
    </div>
  </nav>

  <!-- CHAT AREA -->
  <div id="chat-messages">
    <div id="messages-inner">

      <!-- Welcome message -->
      <div class="msg-ai msg-enter">
        <div class="ai-avatar">R</div>
        <div class="msg-ai-body">
          <span class="msg-label">RAG</span>
          <div class="bubble-ai">Bonjour ! Je suis votre assistant RAG. Posez-moi une question sur vos documents indexés.</div>
        </div>
      </div>

    </div>
  </div>

  <!-- INPUT BAR -->
  <div class="input-bar">
    <div class="input-inner">
      <textarea
        id="user-input"
        rows="1"
        placeholder="Posez votre question…"
      ></textarea>
      <div class="input-footer">
        <span class="input-hint">Maj+Entrée pour un saut de ligne</span>
        <button class="send-btn" id="send-btn" title="Envoyer">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
            <path d="M3.478 2.405a.75.75 0 00-.926.94l2.432 7.905H13.5a.75.75 0 010 1.5H4.984l-2.432 7.905a.75.75 0 00.926.94 60.519 60.519 0 0018.445-8.986.75.75 0 000-1.218A60.517 60.517 0 003.478 2.405z" />
          </svg>
        </button>
      </div>
    </div>
    <p class="input-disclaimer">Les réponses sont générées à partir de vos documents indexés.</p>
  </div>

</div>

<script>
  // ─── Configuration ────────────────────────────────────────────────────────
  const WEBHOOK_QA_URL = "https://n8n.srv859196.hstgr.cloud/webhook/f4fc8034-45c7-4f4a-a117-af3f651e3e8d";

  // ─── Theme ────────────────────────────────────────────────────────────────
  function applyTheme(theme) {
    document.documentElement.setAttribute("data-theme", theme);
    localStorage.setItem("rag-theme", theme);
    document.getElementById("icon-moon").style.display = theme === "dark" ? "" : "none";
    document.getElementById("icon-sun").style.display  = theme === "light" ? "" : "none";
  }
  function toggleTheme() {
    const current = document.documentElement.getAttribute("data-theme");
    applyTheme(current === "dark" ? "light" : "dark");
  }
  applyTheme(localStorage.getItem("rag-theme") || "dark");

  // ─── DOM refs ─────────────────────────────────────────────────────────────
  const userInput     = document.getElementById("user-input");
  const sendBtn       = document.getElementById("send-btn");
  const messagesInner = document.getElementById("messages-inner");
  const chatMessages  = document.getElementById("chat-messages");

  function scrollToBottom() {
    chatMessages.scrollTo({ top: chatMessages.scrollHeight, behavior: "smooth" });
  }

  function escapeHtml(str) {
    return str
      .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;").replace(/'/g, "&#039;");
  }

  function appendUserMessage(text) {
    const el = document.createElement("div");
    el.className = "msg-user msg-enter";
    el.innerHTML = `
      <div class="msg-user-body">
        <span class="msg-label">Vous</span>
        <div class="bubble-user">${escapeHtml(text)}</div>
      </div>`;
    messagesInner.appendChild(el);
    scrollToBottom();
  }

  function appendAiMessage(text) {
    const el = document.createElement("div");
    el.className = "msg-ai msg-enter";
    el.innerHTML = `
      <div class="ai-avatar">R</div>
      <div class="msg-ai-body">
        <span class="msg-label">RAG</span>
        <div class="bubble-ai">${escapeHtml(text)}</div>
      </div>`;
    messagesInner.appendChild(el);
    scrollToBottom();
  }

  function appendTypingIndicator() {
    const el = document.createElement("div");
    el.id = "typing-indicator";
    el.className = "msg-ai msg-enter";
    el.innerHTML = `
      <div class="ai-avatar">R</div>
      <div class="msg-ai-body">
        <span class="msg-label">RAG</span>
        <div class="bubble-ai" style="padding: 14px 18px">
          <div class="typing-dots">
            <div class="dot"></div><div class="dot"></div><div class="dot"></div>
          </div>
        </div>
      </div>`;
    messagesInner.appendChild(el);
    scrollToBottom();
  }

  function removeTypingIndicator() {
    const el = document.getElementById("typing-indicator");
    if (el) el.remove();
  }

  function appendErrorMessage(msg) {
    const el = document.createElement("div");
    el.className = "msg-ai msg-enter";
    el.innerHTML = `
      <div class="avatar-error">!</div>
      <div class="msg-ai-body">
        <span class="msg-label">Erreur</span>
        <div class="bubble-error">${escapeHtml(msg)}</div>
      </div>`;
    messagesInner.appendChild(el);
    scrollToBottom();
  }

  function setLoading(loading) {
    sendBtn.disabled = loading;
    userInput.disabled = loading;
    sendBtn.innerHTML = loading
      ? `<div class="spinner"></div>`
      : `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3.478 2.405a.75.75 0 00-.926.94l2.432 7.905H13.5a.75.75 0 010 1.5H4.984l-2.432 7.905a.75.75 0 00.926.94 60.519 60.519 0 0018.445-8.986.75.75 0 000-1.218A60.517 60.517 0 003.478 2.405z"/></svg>`;
    if (!loading) userInput.focus();
  }

  async function sendMessage() {
    const question = userInput.value.trim();
    if (!question) return;
    userInput.value = "";
    userInput.style.height = "auto";
    appendUserMessage(question);
    setLoading(true);
    appendTypingIndicator();
    try {
      const res = await fetch(WEBHOOK_QA_URL, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ question }),
      });
      if (!res.ok) throw new Error(`Erreur serveur : HTTP ${res.status}`);
      const data = await res.json();
      const answer =
        (typeof data === "string" ? data : null) ||
        data.answer || data.response || data.output || data.text || data.message ||
        JSON.stringify(data);
      removeTypingIndicator();
      appendAiMessage(answer);
    } catch (err) {
      removeTypingIndicator();
      appendErrorMessage(err.message || "Une erreur est survenue lors de la connexion au serveur.");
    } finally {
      setLoading(false);
    }
  }

  sendBtn.addEventListener("click", sendMessage);

  userInput.addEventListener("keydown", e => {
    if (e.key === "Enter" && !e.shiftKey) { e.preventDefault(); sendMessage(); }
  });

  userInput.addEventListener("input", () => {
    userInput.style.height = "auto";
    userInput.style.height = Math.min(userInput.scrollHeight, 160) + "px";
  });

  userInput.focus();
</script>
</body>
</html>
