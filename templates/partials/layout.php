<?php
// Theme variables
$theme = $settings['theme'] ?? 'dark-gold';
$custom = json_decode($settings['theme_custom'] ?? '{}', true);

$themes = [
    'dark-gold'   => ['--bg'=>'#0f0d09','--surface'=>'#1a1710','--surface-2'=>'#221e14','--surface-3'=>'#2c271a','--accent'=>'#c8862a','--accent-light'=>'#e8a84a','--text'=>'#f0e8d4','--text-muted'=>'#9a8f78','--name'=>'Dark Gold'],
    'tiki-classic'=> ['--bg'=>'#1a0f05','--surface'=>'#2a1a08','--surface-2'=>'#3a2510','--surface-3'=>'#4a3018','--accent'=>'#d4621a','--accent-light'=>'#f07830','--text'=>'#fde8c8','--text-muted'=>'#b8966a','--name'=>'Tiki Classic'],
    'tiki-tropical'=>['--bg'=>'#051a12','--surface'=>'#082a1c','--surface-2'=>'#0d3a28','--surface-3'=>'#124a34','--accent'=>'#20b87a','--accent-light'=>'#30d890','--text'=>'#e0f8ee','--text-muted'=>'#7ab898','--name'=>'Tiki Tropical'],
    'tiki-hawaiian'=>['--bg'=>'#1a0a00','--surface'=>'#2a1500','--surface-2'=>'#3a2008','--surface-3'=>'#4a2d10','--accent'=>'#e06030','--accent-light'=>'#f07840','--text'=>'#fde8d0','--text-muted'=>'#c09070','--name'=>'Tiki Hawaiian'],
    'light'       => ['--bg'=>'#f5f3ef','--surface'=>'#ffffff','--surface-2'=>'#f0ece4','--surface-3'=>'#e8e0d0','--accent'=>'#9a6010','--accent-light'=>'#b87818','--text'=>'#1a1410','--text-muted'=>'#6a5a48','--name'=>'Light'],
    'custom'      => array_merge(['--bg'=>'#0f0d09','--surface'=>'#1a1710','--surface-2'=>'#221e14','--surface-3'=>'#2c271a','--accent'=>'#c8862a','--accent-light'=>'#e8a84a','--text'=>'#f0e8d4','--text-muted'=>'#9a8f78','--name'=>'Custom'], $custom),
];

$vars = $themes[$theme] ?? $themes["dark-gold"];
if ($theme === "custom") $vars = array_merge($themes["dark-gold"], $custom);
if (str_starts_with($theme, "custom-")) {
    $themeId = (int)substr($theme, 7);
    $ctVars  = json_decode($settings["custom_theme_{$themeId}"] ?? "{}", true) ?: [];
    $vars    = array_merge($themes["dark-gold"], $ctVars);
}

$cssVars = '';
foreach ($vars as $k => $v) {
    if (str_starts_with($k, '--')) $cssVars .= "$k:$v;";
}

$appName  = htmlspecialchars($settings['app_name'] ?? 'Bar Inventory');
$appLogo  = $settings['app_logo'] ?? '';
$userRole = $user['role'] ?? 'viewer';
$isAdmin  = $userRole === 'admin';
$canEdit  = in_array($userRole, ['admin','editor']);
$isGuest  = empty($user['id']) || $user['username'] === 'guest';
$uri      = $_SERVER['REQUEST_URI'] ?? '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $appName ?></title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🍾</text></svg>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.44.0/tabler-icons.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<script src="/js/app.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root { <?= $cssVars ?>
  --success:#4caf82; --warning:#e8a84a; --danger:#e05252; --info:#5b9bd5;
  --border:rgba(255,220,150,0.08); --border-md:rgba(255,220,150,0.14); --border-strong:rgba(255,220,150,0.22);
  --accent-dim:rgba(200,134,42,0.12); --accent-dim2:rgba(200,134,42,0.2);
  --text-faint:rgba(255,220,150,0.2);
  --radius:8px; --radius-lg:12px;
  --font-display:'Playfair Display',Georgia,serif;
  --font-ui:'DM Sans',-apple-system,sans-serif;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:var(--font-ui);background:var(--bg);color:var(--text);font-size:15px;line-height:1.6;-webkit-font-smoothing:antialiased;min-height:100vh}
::-webkit-scrollbar{width:5px}::-webkit-scrollbar-track{background:var(--surface)}::-webkit-scrollbar-thumb{background:var(--surface-3);border-radius:3px}
a{color:var(--accent-light);text-decoration:none}a:hover{text-decoration:underline}
input,textarea{font-family:var(--font-ui);font-size:14px;background:var(--surface);color:var(--text);border:1px solid var(--border-md);border-radius:var(--radius);padding:9px 12px;width:100%;transition:border-color .15s,box-shadow .15s;outline:none;-webkit-appearance:none}
input::placeholder,textarea::placeholder{color:var(--text-faint)}
input:focus,textarea:focus{border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-dim2)}
/* Native select hidden — use custom-select instead */
select{display:none}
textarea{resize:vertical;min-height:80px}
button{font-family:var(--font-ui);font-size:14px;font-weight:500;cursor:pointer;border-radius:var(--radius);border:1px solid var(--border-md);padding:8px 16px;background:var(--surface-2);color:var(--text);transition:all .15s;display:inline-flex;align-items:center;gap:6px;white-space:nowrap}
button:hover{border-color:var(--border-strong);background:var(--surface-3)}
button:active{transform:scale(.98)}
button:disabled{opacity:.4;cursor:not-allowed}
button.primary{background:var(--accent);border-color:var(--accent-light);color:#1a0e00;font-weight:600}
button.primary:hover{background:var(--accent-light)}
button.ghost{background:transparent;border-color:transparent;color:var(--text-muted);padding:6px 10px}
button.ghost:hover{background:var(--surface-2);border-color:var(--border);color:var(--text)}
button.danger{background:rgba(224,82,82,.12);border-color:rgba(224,82,82,.25);color:var(--danger)}
button.danger:hover{background:rgba(224,82,82,.2)}
.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg)}
.badge{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:500;padding:3px 9px;border-radius:99px;letter-spacing:.02em}
.badge-cat{background:rgba(91,155,213,.12);color:var(--info);border:1px solid rgba(91,155,213,.2)}
.badge-country{background:var(--surface-3);color:var(--text-muted);border:1px solid var(--border)}
.badge-abv{background:var(--accent-dim);color:var(--accent-light);border:1px solid rgba(200,134,42,.2)}
.overlay{position:fixed;inset:0;background:rgba(0,0,0,.75);backdrop-filter:blur(4px);display:flex;align-items:flex-start;justify-content:center;padding:2rem 1rem;z-index:100;overflow-y:auto}
.toast{position:fixed;bottom:24px;right:24px;background:var(--surface-3);border:1px solid var(--border-md);border-radius:var(--radius-lg);padding:12px 18px;font-size:14px;z-index:200;display:flex;align-items:center;gap:8px;box-shadow:0 8px 32px rgba(0,0,0,.5);animation:slideUp .2s ease}
@keyframes slideUp{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
.spin{display:inline-block;animation:spin .8s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.scanner-overlay{position:fixed;inset:0;background:#000;z-index:300;display:flex;flex-direction:column}
.scanner-video{width:100%;height:100%;object-fit:cover}
.scanner-frame{position:absolute;top:50%;left:50%;transform:translate(-50%,-60%);width:min(280px,70vw);height:140px;border:2px solid var(--accent);border-radius:8px;box-shadow:0 0 0 9999px rgba(0,0,0,.55);overflow:hidden}
.scan-line{position:absolute;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,var(--accent-light),transparent);animation:scanLine 1.8s ease-in-out infinite}
@keyframes scanLine{0%,100%{top:0;opacity:.8}50%{top:calc(100% - 2px);opacity:1}}
.fill-bar{flex:1;height:5px;background:var(--surface-3);border-radius:3px;overflow:hidden}
.fill-bar-inner{height:100%;border-radius:3px;transition:width .4s ease}
nav.sidebar{width:220px;background:var(--surface);border-right:1px solid var(--border);height:100vh;display:flex;flex-direction:column;position:fixed;left:0;top:0;z-index:20;overflow:hidden}
.sidebar-logo{padding:1.25rem 1rem;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px}
.sidebar-logo img{height:32px;width:32px;object-fit:contain;border-radius:4px}
.sidebar-logo .name{font-family:var(--font-display);font-size:1.05rem;font-weight:600;overflow:hidden;white-space:nowrap;text-overflow:ellipsis}
.sidebar-nav{flex:1;padding:.75rem .5rem;overflow-y:auto;-webkit-overflow-scrolling:touch}
.nav-item{display:flex;align-items:center;gap:9px;padding:9px 12px;border-radius:var(--radius);color:var(--text-muted);font-size:14px;font-weight:500;cursor:pointer;transition:all .15s;border:none;background:none;width:100%;text-decoration:none}
.nav-item:hover{background:var(--surface-2);color:var(--text);text-decoration:none}
.nav-item.active{background:var(--accent-dim);color:var(--accent-light);border:1px solid rgba(200,134,42,.15)}
.nav-item i{font-size:18px;flex-shrink:0}
.nav-section{font-size:10px;font-weight:600;letter-spacing:.08em;color:var(--text-faint);padding:12px 12px 4px;text-transform:uppercase}
.sidebar-footer{padding:.75rem;border-top:1px solid var(--border)}
main.content{margin-left:220px;padding:2rem;min-height:100vh}
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.75rem;flex-wrap:wrap;gap:12px}
.page-title{font-family:var(--font-display);font-size:1.6rem;font-weight:600}
.stat-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:16px;text-align:center}
.stat-label{font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px}
.stat-value{font-size:28px;font-weight:600;font-family:var(--font-display)}
.grid-4{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
.grid-auto{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:14px}
.label{font-size:12px;color:var(--text-muted);display:block;margin-bottom:5px;text-transform:uppercase;letter-spacing:.06em}

/* ── Custom Select ──────────────────────────────────────────────────────── */
.cs-wrap{position:relative;width:100%}
.cs-trigger{display:flex;align-items:center;justify-content:space-between;gap:8px;width:100%;padding:9px 12px;background:var(--surface);color:var(--text);border:1px solid var(--border-md);border-radius:var(--radius);font-size:14px;font-family:var(--font-ui);cursor:pointer;transition:border-color .15s,box-shadow .15s;text-align:left}
.cs-trigger:hover{border-color:var(--border-strong)}
.cs-trigger.open{border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-dim2)}
.cs-trigger i.arrow{font-size:14px;color:var(--text-muted);transition:transform .2s;flex-shrink:0}
.cs-trigger.open i.arrow{transform:rotate(180deg)}
.cs-dropdown{position:absolute;top:calc(100% + 4px);left:0;right:0;background:var(--surface-2);border:1px solid var(--border-md);border-radius:var(--radius);box-shadow:0 8px 24px rgba(0,0,0,.4);z-index:50;max-height:240px;overflow-y:auto;display:none}
.cs-dropdown.open{display:block}
.cs-search{padding:8px;border-bottom:1px solid var(--border)}
.cs-search input{padding:6px 10px;font-size:13px}
.cs-option{padding:9px 12px;font-size:14px;cursor:pointer;transition:background .1s;display:flex;align-items:center;gap:8px}
.cs-option:hover{background:var(--surface-3)}
.cs-option.selected{color:var(--accent-light);background:var(--accent-dim)}
.cs-option.selected::after{content:'✓';margin-left:auto;font-size:12px}
.cs-empty{padding:10px 12px;font-size:13px;color:var(--text-muted);text-align:center}

.hamburger{display:none;position:fixed;top:12px;left:12px;z-index:200;background:var(--surface-2);border:1px solid var(--border-md);border-radius:var(--radius);padding:8px 10px;cursor:pointer;align-items:center;justify-content:center}
.hamburger i{font-size:22px;color:var(--text)}
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:29;backdrop-filter:blur(2px)}
@media(max-width:768px){
  .hamburger{display:flex}
  nav.sidebar{transform:translateX(-100%);transition:transform .25s ease;z-index:30;width:240px;height:100vh}
  nav.sidebar.open{transform:translateX(0)}
  .sidebar-overlay.open{display:block}
  main.content{margin-left:0;padding:1rem;padding-top:60px}
  .grid-4{grid-template-columns:repeat(2,1fr)}
  .page-header{flex-direction:column;align-items:flex-start}
}
</style>
</head>
<body>

<button class="hamburger" id="hamburger" aria-label="Menu" onclick="toggleSidebar()">
  <i class="ti ti-menu-2"></i>
</button>
<div class="sidebar-overlay" id="sidebar-overlay" onclick="closeSidebar()"></div>

<nav class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <?php if ($appLogo): ?>
    <img src="<?= htmlspecialchars($appLogo) ?>" alt="Logo">
    <?php else: ?>
    <span style="font-size:24px">🍾</span>
    <?php endif; ?>
    <span class="name"><?= $appName ?></span>
  </div>
  <div class="sidebar-nav">
    <div class="nav-section">Inventory</div>
    <a href="/" class="nav-item <?= $uri==='/'?'active':'' ?>">
      <i class="ti ti-bottles"></i> All Bottles
    </a>
    <?php if ($canEdit): ?>
    <a href="/bottles/scan" class="nav-item <?= str_starts_with($uri,'/bottles/scan')?'active':'' ?>">
      <i class="ti ti-barcode"></i> Scan Bottles
    </a>
    <?php endif; ?>
    <a href="/recipes" class="nav-item <?= str_starts_with($uri,'/recipes')?'active':'' ?>">
      <i class="ti ti-cocktail"></i> Recipes
    </a>

    <?php if ($canEdit): ?>
    <div class="nav-section">Data</div>
    <a href="/import" class="nav-item <?= str_starts_with($uri,'/import')?'active':'' ?>">
      <i class="ti ti-file-import"></i> Import
    </a>
    <a href="/export" class="nav-item <?= str_starts_with($uri,'/export')?'active':'' ?>">
      <i class="ti ti-file-export"></i> Export
    </a>
    <?php endif; ?>

    <?php if ($isAdmin): ?>
    <div class="nav-section">Admin</div>
    <a href="/admin" class="nav-item <?= $uri==='/admin'?'active':'' ?>">
      <i class="ti ti-settings"></i> Settings
    </a>
    <a href="/admin/fields" class="nav-item <?= str_starts_with($uri,'/admin/fields')?'active':'' ?>">
      <i class="ti ti-layout-list"></i> Fields
    </a>
    <a href="/admin/users" class="nav-item <?= str_starts_with($uri,'/admin/users')?'active':'' ?>">
      <i class="ti ti-users"></i> Users
    </a>
    <a href="/admin/theme" class="nav-item <?= str_starts_with($uri,'/admin/theme')?'active':'' ?>">
      <i class="ti ti-palette"></i> Theme
    </a>
    <a href="/admin/branding" class="nav-item <?= str_starts_with($uri,'/admin/branding')?'active':'' ?>">
      <i class="ti ti-brand-abstract"></i> Branding
    </a>
    <a href="/admin/email" class="nav-item <?= str_starts_with($uri,'/admin/email')?'active':'' ?>">
      <i class="ti ti-mail"></i> Email
    </a>
    <?php endif; ?>
  </div>
  <div class="sidebar-footer">
    <?php if ($isGuest): ?>
    <a href="/login" class="nav-item" style="justify-content:center;background:var(--accent-dim);border:1px solid rgba(200,134,42,.2);color:var(--accent-light)">
      <i class="ti ti-login"></i> Sign In
    </a>
    <?php else: ?>
    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px">
      <div style="font-size:13px;color:var(--text-muted);overflow:hidden;white-space:nowrap;text-overflow:ellipsis">
        <i class="ti ti-user" style="font-size:14px"></i>
        <?= htmlspecialchars($user['username']) ?>
        <span style="font-size:11px;opacity:.6">(<?= $user['role'] ?>)</span>
      </div>
      <a href="/logout" style="color:var(--text-faint);font-size:13px" title="Logout"><i class="ti ti-logout" style="font-size:16px"></i></a>
    </div>
    <?php endif; ?>
  </div>
</nav>

<main class="content">
<?= $content ?>
</main>

<div id="toast-container"></div>

</body>