<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Bar Inventory — Setup</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root{--bg:#0f0d09;--surface:#1a1710;--surface-2:#221e14;--accent:#c8862a;--accent-light:#e8a84a;--text:#f0e8d4;--text-muted:#9a8f78;--border:rgba(255,220,150,0.14);--danger:#e05252;--radius:8px}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'DM Sans',-apple-system,sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem}
.card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:2.5rem;width:100%;max-width:460px}
h1{font-family:'Playfair Display',serif;font-size:1.8rem;margin-bottom:.5rem;text-align:center}
.subtitle{color:var(--text-muted);text-align:center;font-size:14px;margin-bottom:2rem}
.emoji{font-size:48px;text-align:center;display:block;margin-bottom:1rem}
label{font-size:12px;color:var(--text-muted);display:block;margin-bottom:5px;text-transform:uppercase;letter-spacing:.06em}
input{font-family:'DM Sans',sans-serif;font-size:14px;background:var(--surface-2);color:var(--text);border:1px solid var(--border);border-radius:var(--radius);padding:10px 13px;width:100%;outline:none;transition:border-color .15s}
input:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(200,134,42,.2)}
.field{margin-bottom:1rem}
button{width:100%;padding:12px;font-family:'DM Sans',sans-serif;font-size:15px;font-weight:600;background:var(--accent);border:1px solid var(--accent-light);color:#1a0e00;border-radius:var(--radius);cursor:pointer;margin-top:.5rem}
button:hover{background:var(--accent-light)}
.error{background:rgba(224,82,82,.12);border:1px solid rgba(224,82,82,.25);color:var(--danger);border-radius:var(--radius);padding:10px 14px;font-size:13px;margin-bottom:1rem}
.divider{border:none;border-top:1px solid var(--border);margin:1.5rem 0}
.hint{font-size:12px;color:var(--text-muted);margin-top:4px}
</style>
</head>
<body>
<div class="card">
  <span class="emoji">🍾</span>
  <h1>Welcome</h1>
  <p class="subtitle">Set up your bar inventory — takes 30 seconds</p>

  <?php if ($error): ?>
  <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="/setup">
    <div class="field">
      <label>Bar Name</label>
      <input type="text" name="app_name" placeholder="My Bar" value="Bar Inventory">
    </div>
    <hr class="divider">
    <div class="field">
      <label>Admin Username</label>
      <input type="text" name="username" placeholder="admin" required autocomplete="username">
    </div>
    <div class="field">
      <label>Password <span style="color:var(--danger)">*</span></label>
      <input type="password" name="password" placeholder="Min 6 characters" required autocomplete="new-password">
    </div>
    <div class="field">
      <label>Confirm Password</label>
      <input type="password" name="confirm" required autocomplete="new-password">
    </div>
    <hr class="divider">
    <div class="field">
      <label>Admin Panel PIN <span style="color:var(--text-muted);font-size:11px">(optional)</span></label>
      <input type="password" name="pin" placeholder="4–6 digit PIN to protect admin settings" inputmode="numeric" maxlength="6">
      <p class="hint">If set, you'll need this PIN to access admin settings even when logged in.</p>
    </div>
    <button type="submit">Create Account &amp; Get Started →</button>
  </form>
</div>
</body>
</html>
