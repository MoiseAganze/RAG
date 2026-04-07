<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

startSession();
if (getSessionUser()) {
    header('Location: chat.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricule = trim($_POST['matricule'] ?? '');
    $password  = $_POST['password'] ?? '';

    if (!$matricule || !$password) {
        $error = 'Veuillez renseigner votre matricule et votre mot de passe.';
    } else {
        $pdo  = getPDO();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE matricule = ? LIMIT 1");
        $stmt->execute([$matricule]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            loginUser($user);
            header('Location: chat.php');
            exit;
        } else {
            $error = 'Matricule ou mot de passe incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>RAG — Connexion</title>
  <link rel="stylesheet" href="assets/app.css" />
  <style>
    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
      background: var(--bg);
    }

    .login-box { width: 100%; max-width: 380px; display: flex; flex-direction: column; gap: 28px; }

    .login-header { text-align: center; }
    .login-logo {
      font-family: var(--font-serif);
      font-size: 42px;
      font-style: italic;
      color: var(--accent);
      letter-spacing: -0.03em;
      line-height: 1;
      display: block;
      margin-bottom: 10px;
    }
    .login-sub { font-size: 14px; color: var(--text-muted); }

    .login-card { background: var(--surface); border: 1px solid var(--border); border-radius: 20px; }
    .login-card-body { padding: 28px; display: flex; flex-direction: column; gap: 18px; }

    .login-btn {
      width: 100%;
      padding: 12px;
      border-radius: 11px;
      border: none;
      background: var(--accent);
      color: #0f0f0f;
      font-family: var(--font-sans);
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.15s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      margin-top: 4px;
    }
    .login-btn:hover:not(:disabled) { filter: brightness(1.08); transform: translateY(-1px); }
    .login-btn:disabled { opacity: 0.5; cursor: not-allowed; }

    .theme-toggle {
      position: fixed;
      top: 16px; right: 16px;
      width: 34px; height: 34px;
      border-radius: 9px;
      border: 1px solid var(--border);
      background: var(--surface);
      color: var(--text-muted);
      cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      transition: all var(--transition);
    }
    .theme-toggle:hover { color: var(--text); border-color: var(--accent); }
  </style>
</head>
<body>

  <button class="theme-toggle" onclick="toggleTheme()" title="Changer le thème">
    <svg id="icon-moon" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
    <svg id="icon-sun"  xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
  </button>

  <div class="login-box">

    <div class="login-header">
      <span class="login-logo">Rag</span>
      <p class="login-sub">Assistant de recherche documentaire</p>
    </div>

    <div class="login-card">
      <div class="login-card-body">

        <?php if ($error): ?>
          <div class="alert alert-error">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <form method="POST" id="login-form" style="display:flex;flex-direction:column;gap:14px;">
          <div class="form-group">
            <label class="form-label" for="matricule">Matricule</label>
            <input
              class="form-input"
              type="text"
              id="matricule"
              name="matricule"
              placeholder="ex : ADMIN001"
              value="<?= htmlspecialchars($_POST['matricule'] ?? '') ?>"
              autocomplete="username"
              required
              autofocus
            />
          </div>
          <div class="form-group">
            <label class="form-label" for="password">Mot de passe</label>
            <div style="position:relative;">
              <input
                class="form-input"
                type="password"
                id="password"
                name="password"
                placeholder="••••••••"
                autocomplete="current-password"
                required
                style="padding-right:42px;"
              />
              <button
                type="button"
                id="toggle-pw"
                style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;padding:4px;"
                title="Afficher / masquer"
              >
                <svg id="eye-off" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                <svg id="eye-on" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" style="display:none"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              </button>
            </div>
          </div>
          <button type="submit" class="login-btn" id="submit-btn">
            Se connecter
          </button>
        </form>

      </div>
    </div>

    <p style="text-align:center;font-size:12px;color:var(--text-muted);">
      Accès réservé aux administrateurs autorisés
    </p>

  </div>

  <script>
    function applyTheme(t) {
      document.documentElement.setAttribute('data-theme', t);
      localStorage.setItem('rag-theme', t);
      document.getElementById('icon-moon').style.display = t === 'dark'  ? '' : 'none';
      document.getElementById('icon-sun').style.display  = t === 'light' ? '' : 'none';
    }
    function toggleTheme() {
      applyTheme(document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
    }
    applyTheme(localStorage.getItem('rag-theme') || 'dark');

    const pwInput   = document.getElementById('password');
    const toggleBtn = document.getElementById('toggle-pw');
    const eyeOff    = document.getElementById('eye-off');
    const eyeOn     = document.getElementById('eye-on');
    toggleBtn.addEventListener('click', () => {
      const visible = pwInput.type === 'text';
      pwInput.type   = visible ? 'password' : 'text';
      eyeOff.style.display = visible ? '' : 'none';
      eyeOn.style.display  = visible ? 'none' : '';
    });

    document.getElementById('login-form').addEventListener('submit', () => {
      const btn = document.getElementById('submit-btn');
      btn.disabled = true;
      btn.innerHTML = '<div class="spinner"></div>';
    });
  </script>

</body>
</html>
