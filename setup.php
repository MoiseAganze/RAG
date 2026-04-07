<?php
require_once 'includes/db.php';

$pdo   = getPDO();
$count = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$done  = false;
$error = '';

if ($count > 0) {
    $error = 'Un administrateur existe déjà. Supprimez ce fichier setup.php pour des raisons de sécurité.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricule = trim($_POST['matricule'] ?? '');
    $nom       = trim($_POST['nom']       ?? '');
    $prenom    = trim($_POST['prenom']    ?? '');
    $password  = $_POST['password']       ?? '';
    $confirm   = $_POST['confirm']        ?? '';

    if (!$matricule || !$nom || !$prenom || !$password) {
        $error = 'Tous les champs sont obligatoires.';
    } elseif (strlen($password) < 8) {
        $error = 'Le mot de passe doit contenir au moins 8 caractères.';
    } elseif ($password !== $confirm) {
        $error = 'Les mots de passe ne correspondent pas.';
    } else {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $pdo->prepare(
            "INSERT INTO users (matricule, password_hash, nom, prenom, role)
             VALUES (?, ?, ?, ?, 'admin_full')"
        );
        $stmt->execute([$matricule, $hash, $nom, $prenom]);
        $done = true;
    }
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>RAG — Installation</title>
  <link rel="stylesheet" href="assets/app.css" />
  <style>
    body { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
    .setup-box { width: 100%; max-width: 420px; }
    .setup-logo { font-family: var(--font-serif); font-size: 28px; font-style: italic; color: var(--accent); margin-bottom: 28px; text-align: center; }
    .setup-title { font-size: 18px; font-weight: 500; margin-bottom: 6px; }
    .setup-sub   { font-size: 13.5px; color: var(--text-muted); margin-bottom: 24px; }
    .form-row    { display: flex; gap: 12px; }
    .form-row .form-group { flex: 1; }
    .warn-box { background: rgba(255,160,50,0.08); border: 1px solid rgba(255,160,50,0.3); color: #f0a040; padding: 12px 14px; border-radius: 10px; font-size: 13px; margin-top: 18px; }
    .success-icon { text-align:center; font-size: 48px; margin-bottom: 12px; }
  </style>
</head>
<body>
  <div class="setup-box">
    <div class="setup-logo">Rag</div>

    <?php if ($done): ?>
      <div class="card"><div class="card-body" style="align-items:center;text-align:center;gap:14px;">
        <div class="success-icon">✓</div>
        <div>
          <div class="setup-title">Administrateur créé !</div>
          <p style="font-size:13.5px;color:var(--text-muted);margin-top:6px;">Vous pouvez maintenant vous connecter.<br/>⚠️ Supprimez ce fichier <code>setup.php</code> de votre serveur.</p>
        </div>
        <a href="login.php" class="btn btn-primary" style="margin-top:8px;">Aller à la connexion</a>
      </div></div>

    <?php elseif ($error && $count > 0): ?>
      <div class="card"><div class="card-body">
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <a href="login.php" class="btn btn-primary" style="align-self:center;margin-top:8px;">Aller à la connexion</a>
      </div></div>

    <?php else: ?>
      <div class="card"><div class="card-body">
        <div>
          <div class="setup-title">Initialisation</div>
          <div class="setup-sub">Créez le premier compte administrateur.</div>
        </div>
        <?php if ($error): ?>
          <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" style="display:flex;flex-direction:column;gap:14px;">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Prénom</label>
              <input class="form-input" type="text" name="prenom" required value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>" />
            </div>
            <div class="form-group">
              <label class="form-label">Nom</label>
              <input class="form-input" type="text" name="nom" required value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" />
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Matricule</label>
            <input class="form-input" type="text" name="matricule" required value="<?= htmlspecialchars($_POST['matricule'] ?? '') ?>" placeholder="ex : ADMIN001" />
          </div>
          <div class="form-group">
            <label class="form-label">Mot de passe</label>
            <input class="form-input" type="password" name="password" required placeholder="Minimum 8 caractères" />
          </div>
          <div class="form-group">
            <label class="form-label">Confirmer le mot de passe</label>
            <input class="form-input" type="password" name="confirm" required />
          </div>
          <button type="submit" class="btn btn-primary" style="margin-top:4px;">Créer l'administrateur</button>
        </form>
        <div class="warn-box">⚠️ Ce fichier doit être supprimé après utilisation.</div>
      </div></div>
    <?php endif; ?>
  </div>
</body>
</html>
