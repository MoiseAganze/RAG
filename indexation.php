<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

$user        = requireRole('admin_full', 'chat.php');
$currentPage = 'indexation';

$pdo  = getPDO();
$docs = $pdo->query(
    "SELECT d.id, d.original_name, d.file_size, d.mime_type, d.status, d.error_msg,
            d.created_at, u.prenom, u.nom
     FROM documents d
     JOIN users u ON u.id = d.user_id
     ORDER BY d.created_at DESC
     LIMIT 100"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>RAG — Indexation</title>
  <link rel="stylesheet" href="assets/app.css" />
  <style>
    html, body { height: 100%; }

    #drop-zone {
      border: 1.5px dashed var(--border-strong);
      border-radius: 14px;
      padding: 38px 24px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 12px;
      cursor: pointer;
      transition: all 0.18s ease;
      text-align: center;
    }
    #drop-zone:hover, #drop-zone.drag-active {
      border-color: var(--accent);
      background: var(--accent-dim);
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
    #drop-zone:hover .drop-icon, #drop-zone.drag-active .drop-icon {
      background: var(--accent-dim); border-color: rgba(201,169,110,0.3); color: var(--accent);
    }
    .drop-text-main { font-size: 14px; font-weight: 500; }
    .drop-text-sub  { font-size: 13px; color: var(--text-muted); }

    .browse-btn {
      display: inline-flex; align-items: center; gap: 6px; margin-top: 4px;
      padding: 7px 16px; border-radius: 8px; border: 1px solid var(--border-strong);
      background: transparent; color: var(--text); font-family: var(--font-sans);
      font-size: 13px; font-weight: 500; cursor: pointer; transition: all 0.15s ease;
    }
    .browse-btn:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-dim); }

    #file-list-wrapper { display: none; flex-direction: column; gap: 10px; }
    #file-list-wrapper.visible { display: flex; }
    .file-list-header { display: flex; align-items: center; justify-content: space-between; }
    .file-list-label { font-size: 11.5px; font-weight: 500; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; }
    .clear-all-btn { font-size: 12.5px; font-weight: 500; color: var(--error); background: none; border: none; cursor: pointer; opacity: 0.7; transition: opacity var(--transition); font-family: var(--font-sans); }
    .clear-all-btn:hover { opacity: 1; }

    #file-list { list-style: none; display: flex; flex-direction: column; gap: 6px; max-height: 220px; overflow-y: auto; }
    .file-item {
      display: flex; align-items: center; justify-content: space-between; gap: 10px;
      padding: 10px 14px; background: var(--surface-2); border: 1px solid var(--border);
      border-radius: 10px; animation: slideIn 0.18s ease-out;
    }
    @keyframes slideIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }
    .file-item-left  { display: flex; align-items: center; gap: 10px; min-width: 0; }
    .file-icon       { color: var(--accent); flex-shrink: 0; }
    .file-name       { font-size: 13.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .file-item-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
    .file-size       { font-size: 11.5px; color: var(--text-muted); background: var(--surface-3); border-radius: 6px; padding: 2px 8px; }
    .remove-file-btn { width: 22px; height: 22px; border-radius: 6px; border: none; background: transparent; color: var(--text-muted); cursor: pointer; font-size: 13px; display: flex; align-items: center; justify-content: center; transition: all 0.14s ease; font-family: var(--font-sans); }
    .remove-file-btn:hover { background: var(--error-bg); color: var(--error); }

    #progress-wrapper { display: none; flex-direction: column; gap: 8px; }
    #progress-wrapper.visible { display: flex; }
    .progress-meta  { display: flex; justify-content: space-between; font-size: 12px; color: var(--text-muted); }
    .progress-track { width: 100%; height: 4px; background: var(--surface-3); border-radius: 99px; overflow: hidden; }
    .progress-fill  { height: 100%; background: var(--accent); border-radius: 99px; transition: width 0.3s ease; width: 0%; }

    #submit-btn {
      display: none; align-items: center; justify-content: center; gap: 8px;
      width: 100%; padding: 13px; border-radius: 12px; border: none;
      background: var(--accent); color: #0f0f0f; font-family: var(--font-sans);
      font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.15s ease;
    }
    #submit-btn.visible { display: flex; }
    #submit-btn:hover:not(:disabled) { filter: brightness(1.08); transform: translateY(-1px); }
    #submit-btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
  </style>
</head>
<body>
<div id="toast-container"></div>
<div class="app-layout">

  <?php require_once 'includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="mobile-header">
      <button class="hamburger-btn" onclick="toggleSidebar()">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
      </button>
      <div class="mobile-header-title">Rag</div>
      <div style="width:24px;"></div>
    </div>
    <div class="page-wrap">
      <div class="page-inner">

        <div class="page-heading">
          <h1>Indexation de documents</h1>
          <p>Envoyez vos fichiers au pipeline RAG pour les indexer.</p>
        </div>

        <div class="card">
          <div class="card-body">

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
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" /></svg>
                Parcourir
              </button>
            </div>

            <div id="file-list-wrapper">
              <div class="file-list-header">
                <span class="file-list-label">Fichiers sélectionnés</span>
                <button class="clear-all-btn" id="clear-btn">Tout supprimer</button>
              </div>
              <ul id="file-list"></ul>
            </div>

            <div id="progress-wrapper">
              <div class="progress-meta">
                <span id="progress-label">Envoi en cours…</span>
                <span id="progress-count">0 / 0</span>
              </div>
              <div class="progress-track"><div class="progress-fill" id="progress-fill"></div></div>
            </div>

            <button id="submit-btn" type="button">
              <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
              Indexer les documents
            </button>

          </div>
        </div>

        <!-- ─── Document history ─── -->
        <div class="page-heading" style="margin-bottom:-20px; display:flex; justify-content:space-between; align-items:center;">
          <h1 style="font-size:22px; margin:0;">Documents indexés</h1>
          <button type="button" id="wipe-memory-btn" style="background:var(--error-bg); color:var(--error); border:1px solid var(--error-border); padding:6px 12px; border-radius:8px; font-size:13px; font-weight:500; cursor:pointer; display:flex; align-items:center; gap:6px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
            Vider la mémoire de l'IA
          </button>
        </div>
        <div class="card">
          <table class="data-table">
            <thead>
              <tr>
                <th>Fichier</th>
                <th>Taille</th>
                <th>Indexé par</th>
                <th>Date</th>
                <th style="text-align:center;">Statut</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($docs)): ?>
              <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:28px;">Aucun document indexé pour l'instant.</td></tr>
              <?php else: ?>
              <?php foreach ($docs as $d): ?>
              <tr>
                <td>
                  <div style="font-size:13.5px;font-weight:500;"><?php echo htmlspecialchars($d['original_name']) ?></div>
                  <div style="font-size:11.5px;color:var(--text-muted);margin-top:2px;"><?php echo htmlspecialchars($d['mime_type']) ?></div>
                </td>
                <td style="color:var(--text-muted);font-size:13px;"><?php echo formatFileSize((int)$d['file_size']) ?></td>
                <td style="font-size:13px;"><?php echo htmlspecialchars($d['prenom'] . ' ' . $d['nom']) ?></td>
                <td style="color:var(--text-muted);font-size:12.5px;white-space:nowrap;"><?php echo date('d/m/Y H:i', strtotime($d['created_at'])) ?></td>
                <td style="text-align:center;">
                  <?php if ($d['status'] === 'success'): ?>
                    <span class="badge badge-full">OK</span>
                  <?php else: ?>
                    <span class="badge badge-chat" style="background:var(--error-bg);color:var(--error);border-color:var(--error-border);" title="<?php echo htmlspecialchars($d['error_msg'] ?? '') ?>">Erreur</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </div>
</div>

<?php
function formatFileSize(int $b): string {
    if ($b < 1024)       return $b . ' o';
    if ($b < 1048576)    return round($b/1024, 1) . ' Ko';
    return round($b/1048576, 1) . ' Mo';
}
?>

<script>
  function applyTheme(t) {
    document.documentElement.setAttribute('data-theme', t);
    localStorage.setItem('rag-theme', t);
  }
  applyTheme(localStorage.getItem('rag-theme') || 'dark');

  const UPLOAD_URL = 'api/doc_upload.php';

  let selectedFiles = [];

  const dropZone        = document.getElementById('drop-zone');
  const fileInput       = document.getElementById('file-input');
  const browseBtn       = document.getElementById('browse-btn');
  const fileList        = document.getElementById('file-list');
  const fileListWrapper = document.getElementById('file-list-wrapper');
  const clearBtn        = document.getElementById('clear-btn');
  const submitBtn       = document.getElementById('submit-btn');
  const progressWrapper = document.getElementById('progress-wrapper');
  const progressFill    = document.getElementById('progress-fill');
  const progressLabel   = document.getElementById('progress-label');
  const progressCount   = document.getElementById('progress-count');
  const toastContainer  = document.getElementById('toast-container');

  function formatSize(b) {
    if (b < 1024)    return b + ' o';
    if (b < 1048576) return (b/1024).toFixed(1) + ' Ko';
    return (b/1048576).toFixed(1) + ' Mo';
  }

  function showToast(msg, type = 'success') {
    const icon = type === 'success'
      ? `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`
      : `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>`;
    const t = document.createElement('div');
    t.className = `toast toast-${type}`;
    t.innerHTML = `${icon}<span>${msg}</span>`;
    toastContainer.appendChild(t);
    setTimeout(() => t.remove(), 4000);
  }

  function renderFileList() {
    fileList.innerHTML = '';
    if (!selectedFiles.length) {
      fileListWrapper.classList.remove('visible');
      submitBtn.classList.remove('visible');
      return;
    }
    fileListWrapper.classList.add('visible');
    submitBtn.classList.add('visible');
    selectedFiles.forEach((f, i) => {
      const li = document.createElement('li');
      li.className = 'file-item';
      li.innerHTML = `
        <div class="file-item-left">
          <svg class="file-icon" xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          <span class="file-name">${f.name}</span>
        </div>
        <div class="file-item-right">
          <span class="file-size">${formatSize(f.size)}</span>
          <button class="remove-file-btn" data-i="${i}" title="Supprimer">✕</button>
        </div>`;
      fileList.appendChild(li);
    });
    fileList.querySelectorAll('.remove-file-btn').forEach(b => {
      b.addEventListener('click', () => { selectedFiles.splice(+b.dataset.i, 1); renderFileList(); });
    });
  }

  function addFiles(files) {
    const existing = new Set(selectedFiles.map(f => f.name + f.size));
    for (const f of files) {
      if (!existing.has(f.name + f.size)) { selectedFiles.push(f); existing.add(f.name + f.size); }
    }
    renderFileList();
  }

  dropZone.addEventListener('dragover',  e => { e.preventDefault(); dropZone.classList.add('drag-active'); });
  dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-active'));
  dropZone.addEventListener('drop', e => {
    e.preventDefault(); dropZone.classList.remove('drag-active');
    if (e.dataTransfer.files.length) addFiles(Array.from(e.dataTransfer.files));
  });
  dropZone.addEventListener('click', e => { if (e.target !== browseBtn) fileInput.click(); });
  browseBtn.addEventListener('click', e => { e.stopPropagation(); fileInput.click(); });
  fileInput.addEventListener('change', () => { addFiles(Array.from(fileInput.files)); fileInput.value = ''; });
  clearBtn.addEventListener('click', () => { selectedFiles = []; renderFileList(); });

  submitBtn.addEventListener('click', async () => {
    if (!selectedFiles.length) return;
    const total = selectedFiles.length;
    let sent = 0, errors = 0;
    submitBtn.disabled = true;
    submitBtn.innerHTML = `<div class="spinner" style="border-color:rgba(0,0,0,0.2);border-top-color:#0f0f0f;"></div> Envoi en cours…`;
    progressWrapper.classList.add('visible');
    progressFill.style.width = '0%';
    progressCount.textContent = `0 / ${total}`;

    for (const file of selectedFiles) {
      const fd = new FormData();
      fd.append('file', file);
      try {
        const res  = await fetch(UPLOAD_URL, { method: 'POST', body: fd });
        const data = await res.json().catch(() => ({}));
        if (data.success) { sent++; }
        else { errors++; showToast(`Échec : ${file.name} — ${data.error || ''}`, 'error'); }
      } catch(e) {
        errors++;
        showToast(`Échec : ${file.name}`, 'error');
      }
      const done = sent + errors;
      progressFill.style.width = Math.round(done/total*100) + '%';
      progressCount.textContent = `${done} / ${total}`;
      progressLabel.textContent = `Envoi en cours… (${done}/${total})`;
    }

    progressWrapper.classList.remove('visible');
    submitBtn.disabled = false;
    submitBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg> Indexer les documents`;

    if (errors === 0) {
      showToast(`${sent} fichier${sent>1?'s':''} indexé${sent>1?'s':''} avec succès !`);
      selectedFiles = [];
      renderFileList();
      setTimeout(() => location.reload(), 1200);
    } else {
      showToast(`${sent} succès · ${errors} échec${errors>1?'s':''}`, 'error');
      setTimeout(() => location.reload(), 2000);
    }
  });

  // Memory wipe action
  const wipeBtn = document.getElementById('wipe-memory-btn');
  if (wipeBtn) {
    wipeBtn.addEventListener('click', async () => {
      if (!confirm('Êtes-vous sûr de vouloir vider la mémoire de l\'IA ? Cette action est irréversible.')) return;
      
      const originalText = wipeBtn.innerHTML;
      wipeBtn.disabled = true;
      wipeBtn.innerHTML = `<div class="spinner" style="border-color:rgba(255,90,90,0.2);border-top-color:var(--error);width:14px;height:14px;border-width:2px;margin-right:6px;"></div> Suppression...`;
      
      try {
        const res = await fetch('http://148.230.120.123:32768/collections/test_rag_v2', {
          method: 'DELETE',
          headers: {
            'Authorization': 'Bearer iGlWfzD7ykHU5VkURuoXyBuh7sNt4FZ9',
            'Content-Type': 'application/json'
          }
        });
        
        if (res.ok) {
          showToast('Mémoire de l\'IA vidée avec succès !');
        } else {
          showToast('Erreur lors de la suppression de la mémoire.', 'error');
        }
      } catch (e) {
        showToast('Erreur de connexion au serveur IA.', 'error');
      } finally {
        wipeBtn.disabled = false;
        wipeBtn.innerHTML = originalText;
      }
    });
  }

  // Sidebar stubs (needed by sidebar include)
  function selectConv(id) { window.location.href = `chat.php?conv=${id}`; }
  function deleteConv(id) { /* no-op on this page */ }
  function renameConv(id, t) { /* no-op on this page */ }
  document.getElementById('new-conv-btn').addEventListener('click', () => { window.location.href = 'chat.php'; });
</script>
</body>
</html>
