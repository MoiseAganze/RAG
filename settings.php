<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

$user        = requireAuth('login.php');
$currentPage = 'settings';
$pdo         = getPDO();

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ─── Update profile ───────────────────────────────────────────────────────
    if ($action === 'profile') {
        $nom    = trim($_POST['nom']    ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        if (!$nom || !$prenom) {
            $error = 'Le nom et le prénom sont obligatoires.';
        } else {
            $pdo->prepare("UPDATE users SET nom = ?, prenom = ? WHERE id = ?")
                ->execute([$nom, $prenom, $user['id']]);
            // Refresh session
            $row = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $row->execute([$user['id']]);
            loginUser($row->fetch());
            $user    = getSessionUser();
            $success = 'Profil mis à jour.';
        }
    }

    // ─── Change password ──────────────────────────────────────────────────────
    if ($action === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $row = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $row->execute([$user['id']]);
        $hash = $row->fetchColumn();

        if (!password_verify($current, $hash)) {
            $error = 'Le mot de passe actuel est incorrect.';
        } elseif (strlen($new) < 8) {
            $error = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
        } elseif ($new !== $confirm) {
            $error = 'Les mots de passe ne correspondent pas.';
        } else {
            $newHash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")
                ->execute([$newHash, $user['id']]);
            $success = 'Mot de passe mis à jour.';
        }
    }
}

// Fresh user data for display
$dbUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$dbUser->execute([$user['id']]);
$dbUser = $dbUser->fetch();
?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>RAG — Paramètres</title>
  <link rel="stylesheet" href="assets/app.css" />
  <style>
    html, body { height: 100%; }
    .divider { height: 1px; background: var(--border); margin: 4px 0; }
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
          <h1>Paramètres</h1>
          <p>Gérez votre profil et votre mot de passe.</p>
        </div>

        <?php if ($success): ?>
          <div class="alert alert-success">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <?php echo htmlspecialchars($success) ?>
          </div>
        <?php endif; ?>
        <?php if ($error): ?>
          <div class="alert alert-error">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
            <?php echo htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <!-- ─── Profile ─── -->
        <div class="card">
          <div class="card-body">
            <div>
              <div class="card-title">Informations personnelles</div>
              <p style="font-size:13px;color:var(--text-muted);margin-top:4px;">Votre nom affiché dans l'interface.</p>
            </div>

            <!-- Avatar display -->
            <div style="display:flex;align-items:center;gap:14px;">
              <div style="width:44px;height:44px;border-radius:50%;background:var(--accent-dim);border:1px solid rgba(201,169,110,0.3);display:flex;align-items:center;justify-content:center;font-family:var(--font-serif);font-style:italic;font-size:18px;color:var(--accent);flex-shrink:0;">
                <?php echo strtoupper(substr($dbUser['prenom'], 0, 1)) ?>
              </div>
              <div>
                <div style="font-size:15px;font-weight:500;"><?php echo htmlspecialchars($dbUser['prenom'] . ' ' . $dbUser['nom']) ?></div>
                <div style="font-size:12.5px;color:var(--text-muted);margin-top:2px;">
                  <?php echo $dbUser['role'] === 'admin_full' ? 'Administrateur complet' : 'Administrateur chat' ?>
                  &nbsp;·&nbsp; <span style="font-family:monospace;"><?php echo htmlspecialchars($dbUser['matricule']) ?></span>
                </div>
              </div>
            </div>

            <div class="divider"></div>

            <form method="POST" style="display:flex;flex-direction:column;gap:14px;">
              <input type="hidden" name="action" value="profile" />
              <div style="display:flex;gap:12px;">
                <div class="form-group" style="flex:1">
                  <label class="form-label">Prénom</label>
                  <input class="form-input" type="text" name="prenom" required value="<?php echo htmlspecialchars($dbUser['prenom']) ?>" />
                </div>
                <div class="form-group" style="flex:1">
                  <label class="form-label">Nom</label>
                  <input class="form-input" type="text" name="nom" required value="<?php echo htmlspecialchars($dbUser['nom']) ?>" />
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Matricule</label>
                <input class="form-input" type="text" value="<?php echo htmlspecialchars($dbUser['matricule']) ?>" readonly />
                <span class="form-hint">Le matricule ne peut pas être modifié.</span>
              </div>
              <div>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
              </div>
            </form>
          </div>
        </div>

        <!-- ─── Password ─── -->
        <div class="card">
          <div class="card-body">
            <div>
              <div class="card-title">Changer le mot de passe</div>
              <p style="font-size:13px;color:var(--text-muted);margin-top:4px;">Choisissez un mot de passe d'au moins 8 caractères.</p>
            </div>

            <form method="POST" style="display:flex;flex-direction:column;gap:14px;" id="pw-form">
              <input type="hidden" name="action" value="password" />
              <div class="form-group">
                <label class="form-label">Mot de passe actuel</label>
                <input class="form-input" type="password" name="current_password" required autocomplete="current-password" />
              </div>
              <div class="form-group">
                <label class="form-label">Nouveau mot de passe</label>
                <input class="form-input" type="password" name="new_password" required autocomplete="new-password" id="new-pw" />
              </div>
              <div class="form-group">
                <label class="form-label">Confirmer le nouveau mot de passe</label>
                <input class="form-input" type="password" name="confirm_password" required autocomplete="new-password" id="confirm-pw" />
                <span class="form-error" id="pw-match-err" style="display:none;">Les mots de passe ne correspondent pas.</span>
              </div>
              <div>
                <button type="submit" class="btn btn-primary" id="pw-submit">Changer le mot de passe</button>
              </div>
            </form>
          </div>
        </div>

        <!-- ─── Danger zone ─── -->
        <div class="card" style="border-color:var(--error-border);">
          <div class="card-body">
            <div>
              <div class="card-title" style="color:var(--error);">Zone dangereuse</div>
              <p style="font-size:13px;color:var(--text-muted);margin-top:4px;">Actions irréversibles sur votre compte.</p>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;">
              <div>
                <div style="font-size:13.5px;font-weight:500;">Se déconnecter</div>
                <div style="font-size:12.5px;color:var(--text-muted);">Mettre fin à la session en cours.</div>
              </div>
              <a href="logout.php" class="btn btn-danger btn-sm">Déconnexion</a>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
  function applyTheme(t) {
    document.documentElement.setAttribute('data-theme', t);
    localStorage.setItem('rag-theme', t);
  }
  applyTheme(localStorage.getItem('rag-theme') || 'dark');

  // Password match validation
  const newPw     = document.getElementById('new-pw');
  const confirmPw = document.getElementById('confirm-pw');
  const matchErr  = document.getElementById('pw-match-err');
  const pwSubmit  = document.getElementById('pw-submit');

  function checkMatch() {
    if (confirmPw.value && newPw.value !== confirmPw.value) {
      matchErr.style.display = '';
      pwSubmit.disabled = true;
    } else {
      matchErr.style.display = 'none';
      pwSubmit.disabled = false;
    }
  }
  newPw.addEventListener('input', checkMatch);
  confirmPw.addEventListener('input', checkMatch);

  function selectConv(id) { window.location.href = `chat.php?conv=${id}`; }
  function deleteConv(id) {}
  function renameConv(id, t) {}
  document.getElementById('new-conv-btn').addEventListener('click', () => { window.location.href = 'chat.php'; });
</script>
</body>
</html>
