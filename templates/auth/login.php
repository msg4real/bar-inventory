<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($settings['app_name'] ?? 'Bar Inventory') ?> — Login</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root{--bg:#0f0d09;--surface:#1a1710;--surface-2:#221e14;--accent:#c8862a;--accent-light:#e8a84a;--text:#f0e8d4;--text-muted:#9a8f78;--border:rgba(255,220,150,0.14);--danger:#e05252;--radius:8px}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem}
.card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:2.5rem;width:100%;max-width:400px}
h1{font-family:'Playfair Display',serif;font-size:1.6rem;margin-bottom:.25rem;text-align:center}
.subtitle{color:var(--text-muted);text-align:center;font-size:14px;margin-bottom:2rem}
label{font-size:12px;color:var(--text-muted);display:block;margin-bottom:5px;text-transform:uppercase;letter-spacing:.06em}
input{font-family:'DM Sans',sans-serif;font-size:14px;background:var(--surface-2);color:var(--text);border:1px solid var(--border);border-radius:var(--radius);padding:10px 13px;width:100%;outline:none;transition:border-color .15s;margin-bottom:1rem}
input:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(200,134,42,.2)}
button{width:100%;padding:12px;font-family:'DM Sans',sans-serif;font-size:15px;font-weight:600;background:var(--accent);border:1px solid var(--accent-light);color:#1a0e00;border-radius:var(--radius);cursor:pointer;margin-top:.5rem;display:inline-flex;align-items:center;justify-content:center}
button:hover{background:var(--accent-light)}
.error{background:rgba(224,82,82,.12);border:1px solid rgba(224,82,82,.25);color:var(--danger);border-radius:var(--radius);padding:10px 14px;font-size:13px;margin-bottom:1rem}
.logo{text-align:center;font-size:48px;margin-bottom:1rem}
</style>
</head>
<body>
<div class="card">
  <?php if (!empty($settings['app_logo'])): ?>
  <div class="logo"><img src="<?= htmlspecialchars($settings['app_logo']) ?>" style="height:56px;object-fit:contain"></div>
  <?php else: ?>
  <div class="logo">🍾</div>
  <?php endif; ?>
  <h1><?= htmlspecialchars($settings['app_name'] ?? 'Bar Inventory') ?></h1>
  <p class="subtitle">Sign in to continue</p>

  <?php if ($error): ?>
  <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="/login">
    <input type="hidden" name="next" value="<?= htmlspecialchars($next ?? '/') ?>">
    <label>Username</label>
    <input type="text" name="username" required autocomplete="username" autofocus>
    <label>Password</label>
    <input type="password" name="password" required autocomplete="current-password">
    <button type="submit">Sign in →</button>
  </form>
  <p style="text-align:center;margin-top:1rem;font-size:13px;color:var(--text-muted)">
    <a href="/forgot-password" style="color:var(--accent-light)">Forgot password?</a>
  </p>
</div>
</body>
</html>
