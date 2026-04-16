<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

$user        = requireRole('admin_full', 'chat.php');
$currentPage = 'admins';
$pdo         = getPDO();

$success = '';
$error   = '';

// ─── Handle POST actions ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $matricule = trim($_POST['matricule'] ?? '');
        $nom       = trim($_POST['nom']       ?? '');
        $prenom    = trim($_POST['prenom']    ?? '');
        $role      = $_POST['role']           ?? '';
        $password  = $_POST['password']       ?? '';

        if (!$matricule || !$nom || !$prenom || !$password) {
            $error = 'Tous les champs sont obligatoires.';
        } elseif (!in_array($role, ['admin_full','admin_chat'], true)) {
            $error = 'Rôle invalide.';
        } elseif (strlen($password) < 8) {
            $error = 'Le mot de passe doit contenir au moins 8 caractères.';
        } else {
            $chk = $pdo->prepare("SELECT id FROM users WHERE matricule = ?");
            $chk->execute([$matricule]);
            if ($chk->fetch()) {
                $error = 'Ce matricule est déjà utilisé.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $ins  = $pdo->prepare(
                    "INSERT INTO users (matricule, password_hash, nom, prenom, role) VALUES (?,?,?,?,?)"
                );
                $ins->execute([$matricule, $hash, $nom, $prenom, $role]);
                $success = "Administrateur « {$prenom} {$nom} » créé avec succès.";
            }
        }
    }

    if ($action === 'delete') {
        $targetId = (int)($_POST['target_id'] ?? 0);
        if ($targetId === $user['id']) {
            $error = 'Vous ne pouvez pas supprimer votre propre compte.';
        } elseif ($targetId) {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$targetId]);
            $success = 'Administrateur supprimé.';
        }
    }

    if ($action === 'change_role') {
        $targetId  = (int)($_POST['target_id'] ?? 0);
        $newRole   = $_POST['new_role'] ?? '';
        if ($targetId === $user['id']) {
            $error = 'Vous ne pouvez pas modifier votre propre rôle.';
        } elseif ($targetId && in_array($newRole, ['admin_full','admin_chat'], true)) {
            $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$newRole, $targetId]);
            $success = 'Rôle mis à jour.';
        }
    }
}

// ─── Fetch all users ──────────────────────────────────────────────────────
$admins = $pdo->query("SELECT id, matricule, nom, prenom, role, created_at FROM users ORDER BY created_at ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>RAG — Gestion des admins</title>
  <link rel="stylesheet" href="assets/app.css" />
  <style>
    html, body { height: 100%; }
    .matricule-tag { font-size: 12px; font-family: monospace; background: var(--surface-3); padding: 2px 8px; border-radius: 6px; color: var(--text-muted); }
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
          <h1>Gestion des administrateurs</h1>
          <p>Ajoutez, modifiez ou supprimez des comptes administrateurs.</p>
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

        <!-- ─── Add admin ─── -->
        <div class="card">
          <div class="card-body">
            <div class="card-title">Ajouter un administrateur</div>
            <form method="POST" style="display:flex;flex-direction:column;gap:14px;">
              <input type="hidden" name="action" value="add" />
              <div style="display:flex;gap:12px;">
                <div class="form-group" style="flex:1">
                  <label class="form-label">Prénom</label>
                  <input class="form-input" type="text" name="prenom" required value="<?php echo htmlspecialchars($_POST['prenom'] ?? '') ?>" />
                </div>
                <div class="form-group" style="flex:1">
                  <label class="form-label">Nom</label>
                  <input class="form-input" type="text" name="nom" required value="<?php echo htmlspecialchars($_POST['nom'] ?? '') ?>" />
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Matricule</label>
                <input class="form-input" type="text" name="matricule" required placeholder="ex : USR002" value="<?php echo htmlspecialchars($_POST['matricule'] ?? '') ?>" />
              </div>
              <div class="form-group">
                <label class="form-label">Mot de passe temporaire</label>
                <input class="form-input" type="password" name="password" required placeholder="Minimum 8 caractères" />
              </div>
              <div class="form-group">
                <label class="form-label">Rôle</label>
                <select class="form-select" name="role">
                  <option value="admin_chat" <?php echo (($_POST['role'] ?? '') === 'admin_chat') ? 'selected' : '' ?>>Chat uniquement</option>
                  <option value="admin_full" <?php echo (($_POST['role'] ?? '') === 'admin_full') ? 'selected' : '' ?>>Accès complet (chat + indexation)</option>
                </select>
              </div>
              <div>
                <button type="submit" class="btn btn-primary">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                  Ajouter
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- ─── Admins list ─── -->
        <div class="card">
          <table class="data-table">
            <thead>
              <tr>
                <th>Nom</th>
                <th>Matricule</th>
                <th>Rôle</th>
                <th style="text-align:right;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($admins as $a): ?>
              <tr>
                <td>
                  <div style="font-weight:500;"><?php echo htmlspecialchars($a['prenom'] . ' ' . $a['nom']) ?></div>
                  <?php if ($a['id'] === $user['id']): ?>
                    <div style="font-size:11px;color:var(--text-muted);margin-top:2px;">Vous</div>
                  <?php endif; ?>
                </td>
                <td><span class="matricule-tag"><?php echo htmlspecialchars($a['matricule']) ?></span></td>
                <td>
                  <?php if ($a['id'] !== $user['id']): ?>
                    <form method="POST" style="display:inline;">
                      <input type="hidden" name="action"    value="change_role" />
                      <input type="hidden" name="target_id" value="<?php echo $a['id'] ?>" />
                      <select class="form-select" name="new_role" onchange="this.form.submit()" style="width:auto;padding:4px 28px 4px 10px;font-size:12.5px;">
                        <option value="admin_chat" <?php echo $a['role'] === 'admin_chat' ? 'selected' : '' ?>>Chat</option>
                        <option value="admin_full" <?php echo $a['role'] === 'admin_full' ? 'selected' : '' ?>>Complet</option>
                      </select>
                    </form>
                  <?php else: ?>
                    <span class="badge <?php echo $a['role'] === 'admin_full' ? 'badge-full' : 'badge-chat' ?>">
                      <?php echo $a['role'] === 'admin_full' ? 'Complet' : 'Chat' ?>
                    </span>
                  <?php endif; ?>
                </td>
                <td style="text-align:right;">
                  <?php if ($a['id'] !== $user['id']): ?>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer « <?php echo htmlspecialchars($a['prenom'] . ' ' . $a['nom']) ?> » ?');">
                      <input type="hidden" name="action"    value="delete" />
                      <input type="hidden" name="target_id" value="<?php echo $a['id'] ?>" />
                      <button type="submit" class="btn btn-ghost btn-sm btn-danger">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m2 0a1 1 0 00-1-1h-4a1 1 0 00-1 1H5"/></svg>
                        Supprimer
                      </button>
                    </form>
                  <?php else: ?>
                    <span style="font-size:12px;color:var(--text-muted);">—</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
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

  function selectConv(id) { window.location.href = `chat.php?conv=${id}`; }
  function deleteConv(id) {}
  function renameConv(id, t) {}
  document.getElementById('new-conv-btn').addEventListener('click', () => { window.location.href = 'chat.php?new=1'; });
</script>
</body>
</html>
