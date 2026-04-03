<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>RAG — Indexation</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet" />
  <style>
    /* ─── Tokens ─────────────────────────────────────────────────── */
    :root {
      --font-serif: 'Instrument Serif', serif;
      --font-sans:  'DM Sans', sans-serif;
      --transition: 0.2s ease;
    }

    [data-theme="dark"] {
      --bg:           #0f0f0f;
      --surface:      #161616;
      --surface-2:    #1e1e1e;
      --surface-3:    #252525;
      --border:       rgba(255,255,255,0.07);
      --border-strong:rgba(255,255,255,0.12);
      --text:         #e8e8e6;
      --text-muted:   rgba(232,232,230,0.38);
      --accent:       #c9a96e;
      --accent-dim:   rgba(201,169,110,0.12);
      --accent-hover: rgba(201,169,110,0.2);
      --error:        #ff7070;
      --error-bg:     rgba(255,90,90,0.08);
      --error-border: rgba(255,90,90,0.25);
      --success:      #6ec98a;
      --success-bg:   rgba(110,201,138,0.08);
      --success-border:rgba(110,201,138,0.25);
      --scroll-thumb: rgba(255,255,255,0.1);
    }

    [data-theme="light"] {
      --bg:           #faf9f7;
      --surface:      #ffffff;
      --surface-2:    #f4f3f0;
      --surface-3:    #ece9e4;
      --border:       rgba(0,0,0,0.07);
      --border-strong:rgba(0,0,0,0.12);
      --text:         #1a1a18;
      --text-muted:   rgba(26,26,24,0.4);
      --accent:       #9b7a3e;
      --accent-dim:   rgba(155,122,62,0.1);
      --accent-hover: rgba(155,122,62,0.18);
      --error:        #c03030;
      --error-bg:     rgba(200,50,50,0.06);
      --error-border: rgba(200,50,50,0.2);
      --success:      #2d8a4e;
      --success-bg:   rgba(45,138,78,0.07);
      --success-border:rgba(45,138,78,0.2);
      --scroll-thumb: rgba(0,0,0,0.12);
    }

    /* ─── Reset & Base ────────────────────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    html {
      font-family: var(--font-sans);
      font-size: 15px;
      font-weight: 400;
      background: var(--bg);
      color: var(--text);
      transition: background var(--transition), color var(--transition);
    }

    body { min-height: 100vh; }

    /* ─── Navbar ─────────────────────────────────────────────────── */
    .navbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 28px;
      height: 58px;
      background: var(--surface);
      border-bottom: 1px solid var(--border);
      position: sticky;
      top: 0;
      z-index: 50;
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

    /* ─── Toast ──────────────────────────────────────────────────── */
    #toast-container {
      position: fixed;
      top: 70px;
      right: 20px;
      display: flex;
      flex-direction: column;
      gap: 8px;
      z-index: 100;
      pointer-events: none;
    }

    .toast {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 12px 16px;
      border-radius: 12px;
      font-size: 13.5px;
      font-weight: 500;
      pointer-events: auto;
      animation: toastIn 0.22s ease-out;
      max-width: 300px;
      border: 1px solid;
    }
    @keyframes toastIn {
      from { opacity: 0; transform: translateX(12px); }
      to   { opacity: 1; transform: translateX(0); }
    }
    .toast-success {
      background: var(--success-bg);
      border-color: var(--success-border);
      color: var(--success);
    }
    .toast-error {
      background: var(--error-bg);
      border-color: var(--error-border);
      color: var(--error);
    }
    .toast svg { width: 16px; height: 16px; flex-shrink: 0; }

    /* ─── Main ────────────────────────────────────────────────────── */
    main {
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 64px 20px 80px;
      min-height: calc(100vh - 58px);
    }

    .content { width: 100%; max-width: 600px; display: flex; flex-direction: column; gap: 36px; }

    /* ─── Header ─────────────────────────────────────────────────── */
    .page-header { text-align: center; }
    .page-header h1 {
      font-family: var(--font-serif);
      font-size: 32px;
      font-style: italic;
      font-weight: 400;
      letter-spacing: -0.03em;
      color: var(--text);
      line-height: 1.2;
    }
    .page-header p {
      margin-top: 8px;
      font-size: 14px;
      color: var(--text-muted);
      line-height: 1.5;
    }

    /* ─── Card ────────────────────────────────────────────────────── */
    .card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 20px;
      overflow: hidden;
      transition: background var(--transition), border-color var(--transition);
    }
    .card-body { padding: 28px; display: flex; flex-direction: column; gap: 20px; }

    /* ─── Drop Zone ───────────────────────────────────────────────── */
    #drop-zone {
      border: 1.5px dashed var(--border-strong);
      border-radius: 14px;
      padding: 40px 24px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 12px;
      cursor: pointer;
      transition: all 0.18s ease;
      text-align: center;
    }
    #drop-zone:hover {
      border-color: var(--accent);
      background: var(--accent-dim);
    }
    #drop-zone.drag-active {
      border-color: var(--accent);
      background: var(--accent-hover);
    }

    .drop-icon {
      width: 44px; height: 44px;
      border-radius: 12px;
      background: var(--surface-2);
      border: 1px solid var(--border);
      display: flex; align-items: center; justify-content: center;
      color: var(--text-muted);
      transition: all var(--transition);
    }
    #drop-zone:hover .drop-icon,
    #drop-zone.drag-active .drop-icon {
      background: var(--accent-dim);
      border-color: rgba(201,169,110,0.3);
      color: var(--accent);
    }

    .drop-text-main { font-size: 14px; font-weight: 500; color: var(--text); }
    .drop-text-sub  { font-size: 13px; color: var(--text-muted); }

    .browse-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      margin-top: 4px;
      padding: 7px 16px;
      border-radius: 8px;
      border: 1px solid var(--border-strong);
      background: transparent;
      color: var(--text);
      font-family: var(--font-sans);
      font-size: 13px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.15s ease;
    }
    .browse-btn:hover {
      border-color: var(--accent);
      color: var(--accent);
      background: var(--accent-dim);
    }

    /* ─── File list ───────────────────────────────────────────────── */
    #file-list-wrapper { display: none; flex-direction: column; gap: 10px; }
    #file-list-wrapper.visible { display: flex; }

    .file-list-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .file-list-label { font-size: 12px; font-weight: 500; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; }

    .clear-btn {
      font-size: 12.5px;
      font-weight: 500;
      color: var(--error);
      background: none;
      border: none;
      cursor: pointer;
      opacity: 0.7;
      transition: opacity var(--transition);
      font-family: var(--font-sans);
    }
    .clear-btn:hover { opacity: 1; }

    #file-list {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 6px;
      max-height: 240px;
      overflow-y: auto;
      padding-right: 2px;
    }
    #file-list::-webkit-scrollbar { width: 4px; }
    #file-list::-webkit-scrollbar-track { background: transparent; }
    #file-list::-webkit-scrollbar-thumb { background: var(--scroll-thumb); border-radius: 99px; }

    .file-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      padding: 10px 14px;
      background: var(--surface-2);
      border: 1px solid var(--border);
      border-radius: 10px;
      animation: slideIn 0.18s ease-out;
      transition: background var(--transition), border-color var(--transition);
    }
    @keyframes slideIn {
      from { opacity: 0; transform: translateY(-5px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .file-item-left { display: flex; align-items: center; gap: 10px; min-width: 0; }
    .file-icon { color: var(--accent); flex-shrink: 0; }
    .file-name { font-size: 13.5px; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    .file-item-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
    .file-size {
      font-size: 11.5px;
      color: var(--text-muted);
      background: var(--surface-3);
      border-radius: 6px;
      padding: 2px 8px;
    }
    .remove-btn {
      width: 22px; height: 22px;
      border-radius: 6px;
      border: none;
      background: transparent;
      color: var(--text-muted);
      cursor: pointer;
      font-size: 14px;
      display: flex; align-items: center; justify-content: center;
      transition: all 0.15s ease;
      font-family: var(--font-sans);
    }
    .remove-btn:hover { background: var(--error-bg); color: var(--error); }

    /* ─── Progress ────────────────────────────────────────────────── */
    #progress-wrapper { display: none; flex-direction: column; gap: 8px; }
    #progress-wrapper.visible { display: flex; }

    .progress-meta { display: flex; justify-content: space-between; font-size: 12px; color: var(--text-muted); }

    .progress-track {
      width: 100%;
      height: 4px;
      background: var(--surface-3);
      border-radius: 99px;
      overflow: hidden;
    }
    .progress-fill {
      height: 100%;
      background: var(--accent);
      border-radius: 99px;
      transition: width 0.3s ease;
      width: 0%;
    }

    /* ─── Submit button ───────────────────────────────────────────── */
    #submit-btn {
      display: none;
      align-items: center;
      justify-content: center;
      gap: 8px;
      width: 100%;
      padding: 13px;
      border-radius: 12px;
      border: none;
      background: var(--accent);
      color: #0f0f0f;
      font-family: var(--font-sans);
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.15s ease;
    }
    #submit-btn.visible { display: flex; }
    #submit-btn:hover:not(:disabled) { filter: brightness(1.08); transform: translateY(-1px); box-shadow: 0 4px 20px rgba(201,169,110,0.3); }
    #submit-btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: none; }

    .spinner {
      width: 15px; height: 15px;
      border: 2px solid rgba(0,0,0,0.2);
      border-top-color: #0f0f0f;
      border-radius: 50%;
      animation: spin 0.7s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
  </style>
</head>
<body>

  <!-- NAVBAR -->
  <nav class="navbar">
    <span class="logo">Rag</span>
    <div class="nav-actions">
      <a href="indexation.php" class="nav-link active">Indexation</a>
      <a href="chat.php" class="nav-link">Chat</a>
      <button class="theme-btn" id="theme-btn" title="Basculer le thème" onclick="toggleTheme()">
        <svg id="icon-moon" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        <svg id="icon-sun" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
      </button>
    </div>
  </nav>

  <!-- TOAST CONTAINER -->
  <div id="toast-container"></div>

  <!-- MAIN -->
  <main>
    <div class="content">

      <!-- Header -->
      <div class="page-header">
        <h1>Indexation de documents</h1>
        <p>Déposez vos fichiers pour les envoyer au pipeline RAG.</p>
      </div>

      <!-- Card -->
      <div class="card">
        <div class="card-body">

          <!-- Drop zone -->
          <div id="drop-zone">
            <div class="drop-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
              </svg>
            </div>
            <p class="drop-text-main">Glissez-déposez vos fichiers ici</p>
            <p class="drop-text-sub">PDF, Word, texte et plus encore</p>
            <input id="file-input" type="file" multiple style="display:none" />
            <button type="button" class="browse-btn" id="browse-btn">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
              </svg>
              Parcourir
            </button>
          </div>

          <!-- File list -->
          <div id="file-list-wrapper">
            <div class="file-list-header">
              <span class="file-list-label">Fichiers sélectionnés</span>
              <button class="clear-btn" id="clear-btn">Tout supprimer</button>
            </div>
            <ul id="file-list"></ul>
          </div>

          <!-- Progress -->
          <div id="progress-wrapper">
            <div class="progress-meta">
              <span id="progress-label">Envoi en cours…</span>
              <span id="progress-count">0 / 0</span>
            </div>
            <div class="progress-track">
              <div class="progress-fill" id="progress-fill"></div>
            </div>
          </div>

          <!-- Submit -->
          <button id="submit-btn" type="button">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
            </svg>
            Indexer les documents
          </button>

        </div>
      </div>

    </div>
  </main>

  <script>
    // ─── Configuration ─────────────────────────────────────────────────────────
    const WEBHOOK_INDEXATION_URL = "https://n8n.srv859196.hstgr.cloud/webhook/f5953563-088a-4381-8445-1f95bc906bb1";

    // ─── Theme ─────────────────────────────────────────────────────────────────
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

    // ─── State & refs ───────────────────────────────────────────────────────────
    let selectedFiles = [];

    const dropZone        = document.getElementById("drop-zone");
    const fileInput       = document.getElementById("file-input");
    const browseBtn       = document.getElementById("browse-btn");
    const fileList        = document.getElementById("file-list");
    const fileListWrapper = document.getElementById("file-list-wrapper");
    const clearBtn        = document.getElementById("clear-btn");
    const submitBtn       = document.getElementById("submit-btn");
    const progressWrapper = document.getElementById("progress-wrapper");
    const progressFill    = document.getElementById("progress-fill");
    const progressLabel   = document.getElementById("progress-label");
    const progressCount   = document.getElementById("progress-count");
    const toastContainer  = document.getElementById("toast-container");

    // ─── Utils ──────────────────────────────────────────────────────────────────
    function formatSize(bytes) {
      if (bytes < 1024)    return bytes + " o";
      if (bytes < 1048576) return (bytes / 1024).toFixed(1) + " Ko";
      return (bytes / 1048576).toFixed(1) + " Mo";
    }

    function showToast(message, type = "success") {
      const icon = type === "success"
        ? `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`
        : `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`;
      const toast = document.createElement("div");
      toast.className = `toast toast-${type}`;
      toast.innerHTML = `${icon}<span>${message}</span>`;
      toastContainer.appendChild(toast);
      setTimeout(() => toast.remove(), 4000);
    }

    // ─── Render file list ───────────────────────────────────────────────────────
    function renderFileList() {
      fileList.innerHTML = "";
      if (selectedFiles.length === 0) {
        fileListWrapper.classList.remove("visible");
        submitBtn.classList.remove("visible");
        return;
      }
      fileListWrapper.classList.add("visible");
      submitBtn.classList.add("visible");

      selectedFiles.forEach((file, index) => {
        const li = document.createElement("li");
        li.className = "file-item";
        li.innerHTML = `
          <div class="file-item-left">
            <svg class="file-icon" xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="file-name">${file.name}</span>
          </div>
          <div class="file-item-right">
            <span class="file-size">${formatSize(file.size)}</span>
            <button class="remove-btn" data-index="${index}" title="Supprimer">✕</button>
          </div>`;
        fileList.appendChild(li);
      });

      document.querySelectorAll(".remove-btn").forEach(btn => {
        btn.addEventListener("click", () => {
          selectedFiles.splice(parseInt(btn.dataset.index, 10), 1);
          renderFileList();
        });
      });
    }

    function addFiles(newFiles) {
      const existing = new Set(selectedFiles.map(f => f.name + f.size));
      for (const file of newFiles) {
        if (!existing.has(file.name + file.size)) {
          selectedFiles.push(file);
          existing.add(file.name + file.size);
        }
      }
      renderFileList();
    }

    // ─── Events ─────────────────────────────────────────────────────────────────
    dropZone.addEventListener("dragover", e => { e.preventDefault(); dropZone.classList.add("drag-active"); });
    dropZone.addEventListener("dragleave", () => dropZone.classList.remove("drag-active"));
    dropZone.addEventListener("drop", e => {
      e.preventDefault();
      dropZone.classList.remove("drag-active");
      if (e.dataTransfer.files.length) addFiles(Array.from(e.dataTransfer.files));
    });
    dropZone.addEventListener("click", e => { if (e.target !== browseBtn) fileInput.click(); });
    browseBtn.addEventListener("click", e => { e.stopPropagation(); fileInput.click(); });
    fileInput.addEventListener("change", () => {
      if (fileInput.files.length) addFiles(Array.from(fileInput.files));
      fileInput.value = "";
    });
    clearBtn.addEventListener("click", () => { selectedFiles = []; renderFileList(); });

    // ─── Submit ──────────────────────────────────────────────────────────────────
    submitBtn.addEventListener("click", async () => {
      if (selectedFiles.length === 0) return;
      const total = selectedFiles.length;
      let sent = 0, errors = 0;

      submitBtn.disabled = true;
      submitBtn.innerHTML = `<div class="spinner"></div> Envoi en cours…`;
      progressWrapper.classList.add("visible");
      progressFill.style.width = "0%";
      progressCount.textContent = `0 / ${total}`;

      for (const file of selectedFiles) {
        const formData = new FormData();
        formData.append("file", file);
        formData.append("filename", file.name);
        try {
          const res = await fetch(WEBHOOK_INDEXATION_URL, { method: "POST", body: formData });
          if (!res.ok) throw new Error(`HTTP ${res.status}`);
          sent++;
        } catch (err) {
          errors++;
          showToast(`Échec : ${file.name}`, "error");
        }
        const done = sent + errors;
        const pct = Math.round((done / total) * 100);
        progressFill.style.width = pct + "%";
        progressCount.textContent = `${done} / ${total}`;
        progressLabel.textContent = `Envoi en cours… (${done}/${total})`;
      }

      progressWrapper.classList.remove("visible");
      submitBtn.disabled = false;
      submitBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg> Indexer les documents`;

      if (errors === 0) {
        showToast(`${sent} fichier${sent > 1 ? "s" : ""} indexé${sent > 1 ? "s" : ""} avec succès !`, "success");
        selectedFiles = [];
        renderFileList();
      } else {
        showToast(`${sent} succès, ${errors} échec${errors > 1 ? "s" : ""}`, "error");
      }
    });
  </script>

</body>
</html>
