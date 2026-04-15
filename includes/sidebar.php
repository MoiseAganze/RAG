<?php
// Requires: $user (array), $currentPage (string)
// Fetches conversations for the current user from DB.
require_once __DIR__ . '/db.php';

$pdo   = getPDO();
$stmt  = $pdo->prepare(
    "SELECT id, title, updated_at
     FROM conversations
     WHERE user_id = ?
     ORDER BY updated_at DESC
     LIMIT 60"
);
$stmt->execute([$user['id']]);
$sidebarConvs = $stmt->fetchAll();

// Group by date label
function convDateLabel(string $dateStr): string {
    $ts    = strtotime($dateStr);
    $today = strtotime('today');
    $diff  = $today - strtotime(date('Y-m-d', $ts));
    if ($diff === 0)       return 'Aujourd\'hui';
    if ($diff === 86400)   return 'Hier';
    if ($diff < 7 * 86400) return 'Cette semaine';
    if ($diff < 30 * 86400) return 'Ce mois-ci';
    return 'Plus ancien';
}

$grouped = [];
foreach ($sidebarConvs as $c) {
    $label = convDateLabel($c['updated_at']);
    $grouped[$label][] = $c;
}

$activeConvId = isset($activeConvId) ? (int)$activeConvId : 0;
?>
<aside class="sidebar" id="sidebar">

  <div class="sidebar-header">
    <span class="logo">Rag</span>
    <button class="new-conv-btn" id="new-conv-btn" title="Nouvelle conversation">
      <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
      </svg>
      Nouveau
    </button>
  </div>

  <div class="conv-section" id="conv-section">
    <?php if (empty($sidebarConvs)): ?>
      <p style="font-size:12.5px;color:var(--text-muted);padding:12px 10px;">Aucune conversation</p>
    <?php else: ?>
      <?php foreach ($grouped as $label => $items): ?>
        <div class="conv-group-label"><?php echo htmlspecialchars($label) ?></div>
        <?php foreach ($items as $c): ?>
          <?php $isActive = ($c['id'] === $activeConvId); ?>
          <div
            class="conv-item <?php echo $isActive ? 'active' : '' ?>"
            data-id="<?php echo $c['id'] ?>"
            data-title="<?php echo htmlspecialchars($c['title']) ?>"
            onclick='selectConv(<?php echo $c['id'] ?>, <?php echo htmlspecialchars(json_encode($c['title']), ENT_QUOTES, 'UTF-8') ?>)'
          >
            <span class="conv-title"><?php echo htmlspecialchars($c['title']) ?></span>
          </div>
        <?php endforeach; ?>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <nav class="sidebar-nav">
    <a href="chat.php" class="sidebar-nav-link <?php echo $currentPage === 'chat' ? 'active' : '' ?>">
      <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/></svg>
      Chat
    </a>

    <?php if ($user['role'] === 'admin_full'): ?>
    <a href="indexation.php" class="sidebar-nav-link <?php echo $currentPage === 'indexation' ? 'active' : '' ?>">
      <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
      Indexation
    </a>

    <a href="admins.php" class="sidebar-nav-link <?php echo $currentPage === 'admins' ? 'active' : '' ?>">
      <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
      Gestion des admins
    </a>
    <?php endif; ?>

    <a href="settings.php" class="sidebar-nav-link <?php echo $currentPage === 'settings' ? 'active' : '' ?>">
      <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      Paramètres
    </a>

    <div class="sidebar-user">
      <div class="user-avatar"><?php echo strtoupper(substr($user['prenom'], 0, 1)) ?></div>
      <div class="user-info">
        <div class="user-name"><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></div>
        <div class="user-role"><?php echo $user['role'] === 'admin_full' ? 'Administrateur' : 'Utilisateur' ?></div>
      </div>
      <button type="button" onclick="toggleTheme()" title="Changer le thème" style="margin-left:auto;flex-shrink:0;width:34px;height:34px;border-radius:9px;border:1px solid var(--border);background:var(--surface);color:var(--text-muted);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all var(--transition);">
        <svg id="sidebar-icon-moon" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        <svg id="sidebar-icon-sun" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
      </button>
      <a href="logout.php" title="Déconnexion" style="margin-left:auto;flex-shrink:0;">
        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" style="color:var(--text-muted)"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
      </a>
    </div>
  </nav>

</aside>
<div class="sidebar-overlay" id="sidebar-overlay"></div>

<script>
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebar-overlay');
  function syncSidebarThemeIcons(theme) {
    const moon = document.getElementById('sidebar-icon-moon');
    const sun = document.getElementById('sidebar-icon-sun');
    if (!moon || !sun) return;
    moon.style.display = theme === 'dark' ? '' : 'none';
    sun.style.display = theme === 'light' ? '' : 'none';
  }
  function setSidebarTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('rag-theme', theme);
    syncSidebarThemeIcons(theme);
  }
  function toggleTheme() {
    const nextTheme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    setSidebarTheme(nextTheme);
  }
  function toggleSidebar() {
    sidebar.classList.toggle('open');
    overlay.classList.toggle('active');
  }
  syncSidebarThemeIcons(localStorage.getItem('rag-theme') || document.documentElement.getAttribute('data-theme') || 'dark');
  if (overlay) overlay.addEventListener('click', () => {
    sidebar.classList.remove('open');
    overlay.classList.remove('active');
  });
</script>
