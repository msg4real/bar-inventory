<?php
$currentTheme = $settings['theme'] ?? 'dark-gold';
$customVars   = json_decode($settings['theme_custom'] ?? '{}', true) ?: [];

$presets = [
    'dark-gold'     => ['name'=>'Dark Gold',     'emoji'=>'🥃', '--bg'=>'#0f0d09','--surface'=>'#1a1710','--surface-2'=>'#221e14','--surface-3'=>'#2c271a','--accent'=>'#c8862a','--accent-light'=>'#e8a84a','--text'=>'#f0e8d4','--text-muted'=>'#9a8f78'],
    'tiki-classic'  => ['name'=>'Tiki Classic',  'emoji'=>'🌺', '--bg'=>'#1a0f05','--surface'=>'#2a1a08','--surface-2'=>'#3a2510','--surface-3'=>'#4a3018','--accent'=>'#d4621a','--accent-light'=>'#f07830','--text'=>'#fde8c8','--text-muted'=>'#b8966a'],
    'tiki-tropical' => ['name'=>'Tiki Tropical', 'emoji'=>'🌿', '--bg'=>'#051a12','--surface'=>'#082a1c','--surface-2'=>'#0d3a28','--surface-3'=>'#124a34','--accent'=>'#20b87a','--accent-light'=>'#30d890','--text'=>'#e0f8ee','--text-muted'=>'#7ab898'],
    'tiki-hawaiian' => ['name'=>'Tiki Hawaiian', 'emoji'=>'🌸', '--bg'=>'#1a0a00','--surface'=>'#2a1500','--surface-2'=>'#3a2008','--surface-3'=>'#4a2d10','--accent'=>'#e06030','--accent-light'=>'#f07840','--text'=>'#fde8d0','--text-muted'=>'#c09070'],
    'light'         => ['name'=>'Light',         'emoji'=>'☀️', '--bg'=>'#f5f3ef','--surface'=>'#ffffff','--surface-2'=>'#f0ece4','--surface-3'=>'#e8e0d0','--accent'=>'#9a6010','--accent-light'=>'#b87818','--text'=>'#1a1410','--text-muted'=>'#6a5a48'],
];

$colorFields = [
    '--bg'          => 'Background',
    '--surface'     => 'Surface',
    '--surface-2'   => 'Surface 2',
    '--surface-3'   => 'Surface 3',
    '--accent'      => 'Accent',
    '--accent-light'=> 'Accent Light',
    '--text'        => 'Text',
    '--text-muted'  => 'Text Muted',
];
?>

<div class="page-header">
  <h1 class="page-title"><i class="ti ti-palette" style="font-size:1.4rem"></i> Theme</h1>
</div>

<!-- Preset tiles -->
<h3 style="font-size:12px;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin-bottom:.75rem">Built-in Presets</h3>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px;max-width:820px;margin-bottom:2rem">
  <?php foreach ($presets as $key => $p): ?>
  <div class="theme-tile" data-theme="<?= $key ?>" onclick="selectTheme('<?= $key ?>', <?= json_encode($p) ?>)"
       style="cursor:pointer;border-radius:var(--radius-lg);overflow:hidden;border:2px solid <?= $currentTheme===$key?'var(--accent)':'var(--border)' ?>;transition:border-color .15s">
    <div style="height:72px;background:<?= $p['--bg'] ?>;display:flex;align-items:center;justify-content:center;position:relative">
      <span style="font-size:26px"><?= $p['emoji'] ?></span>
      <div style="position:absolute;bottom:7px;left:7px;width:22px;height:7px;border-radius:4px;background:<?= $p['--accent'] ?>"></div>
    </div>
    <div style="padding:8px 10px;background:var(--surface-2);display:flex;align-items:center;justify-content:space-between">
      <span style="font-size:12px;font-weight:500"><?= $p['name'] ?></span>
      <?php if ($currentTheme===$key): ?><i class="ti ti-check" style="color:var(--accent-light);font-size:13px"></i><?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Custom saved themes -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem;max-width:820px">
  <h3 style="font-size:12px;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted)">Custom Themes</h3>
  <button onclick="openEditor(null)" style="font-size:12px;padding:5px 10px"><i class="ti ti-plus"></i> New Theme</button>
</div>

<div id="custom-theme-list" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px;max-width:820px;margin-bottom:2rem">
  <?php foreach ($customThemes as $ct):
    $v = json_decode($ct['vars'], true) ?: [];
  ?>
  <div class="theme-tile" data-theme="custom-<?= $ct['id'] ?>"
       style="cursor:pointer;border-radius:var(--radius-lg);overflow:hidden;border:2px solid <?= $currentTheme==='custom-'.$ct['id']?'var(--accent)':'var(--border)' ?>;transition:border-color .15s">
    <div onclick="selectCustomTheme(<?= $ct['id'] ?>, <?= htmlspecialchars($ct['vars']) ?>)"
         style="height:72px;background:<?= $v['--bg'] ?? '#0f0d09' ?>;display:flex;align-items:center;justify-content:center;position:relative">
      <span style="font-size:26px">🎨</span>
      <div style="position:absolute;bottom:7px;left:7px;width:22px;height:7px;border-radius:4px;background:<?= $v['--accent'] ?? '#c8862a' ?>"></div>
    </div>
    <div style="padding:8px 10px;background:var(--surface-2);display:flex;align-items:center;justify-content:space-between;gap:4px">
      <span style="font-size:12px;font-weight:500;overflow:hidden;white-space:nowrap;text-overflow:ellipsis"><?= htmlspecialchars($ct['name']) ?></span>
      <div style="display:flex;gap:2px;flex-shrink:0">
        <button onclick="openEditor(<?= htmlspecialchars(json_encode($ct)) ?>)" class="ghost" style="padding:2px 4px;font-size:13px" title="Edit"><i class="ti ti-edit"></i></button>
        <button onclick="deleteCustomTheme(<?= $ct['id'] ?>, this)" class="ghost danger" style="padding:2px 4px;font-size:13px" title="Delete"><i class="ti ti-trash"></i></button>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php if (empty($customThemes)): ?>
  <div style="grid-column:1/-1;color:var(--text-faint);font-size:13px;padding:1rem 0">No custom themes yet — click New Theme to create one.</div>
  <?php endif; ?>
</div>

<!-- Theme editor modal -->
<div id="theme-editor-overlay" style="display:none" class="overlay">
  <div class="card" style="width:100%;max-width:540px;padding:1.5rem;position:relative">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem">
      <h2 style="font-family:var(--font-display);font-size:1.1rem" id="editor-title">New Theme</h2>
      <button onclick="closeEditor()" class="ghost" style="padding:6px 8px"><i class="ti ti-x" style="font-size:18px"></i></button>
    </div>

    <div style="margin-bottom:1rem">
      <label class="label">Theme Name</label>
      <input id="theme-name" placeholder="My Dark Theme">
    </div>

    <div style="display:flex;gap:8px;margin-bottom:1rem;flex-wrap:wrap">
      <span style="font-size:12px;color:var(--text-muted);align-self:center">Start from:</span>
      <?php foreach ($presets as $key => $p): ?>
      <button onclick="loadPresetIntoEditor(<?= json_encode($p) ?>)" class="ghost" style="font-size:11px;padding:4px 8px"><?= $p['name'] ?></button>
      <?php endforeach; ?>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:1.25rem" id="editor-pickers">
      <?php foreach ($colorFields as $var => $label): ?>
      <div style="display:flex;align-items:center;gap:10px">
        <input type="color" data-var="<?= $var ?>" value="#000000"
               style="width:38px;height:34px;padding:2px;border-radius:6px;cursor:pointer;background:var(--surface-2);border:1px solid var(--border-md)"
               oninput="livePreview()">
        <label style="font-size:13px;color:var(--text-muted)"><?= $label ?></label>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Live preview strip -->
    <div id="theme-preview" style="border-radius:var(--radius);overflow:hidden;border:1px solid var(--border);margin-bottom:1.25rem">
      <div id="preview-bg" style="padding:12px 14px;display:flex;align-items:center;gap:10px">
        <div id="preview-accent" style="width:28px;height:28px;border-radius:6px"></div>
        <div>
          <div id="preview-text" style="font-size:13px;font-weight:500">Preview Text</div>
          <div id="preview-muted" style="font-size:11px">Muted text sample</div>
        </div>
        <div id="preview-surface" style="margin-left:auto;padding:4px 10px;border-radius:4px;font-size:11px" id="preview-surface-txt">Surface</div>
      </div>
    </div>

    <input type="hidden" id="editing-theme-id" value="">
    <div style="display:flex;gap:8px;justify-content:flex-end">
      <button onclick="closeEditor()">Cancel</button>
      <button onclick="saveCustomTheme()" class="primary"><i class="ti ti-device-floppy"></i> Save Theme</button>
    </div>
  </div>
</div>

<script>
let selectedTheme = '<?= $currentTheme ?>';

// ── Select built-in preset ─────────────────────────────────────────────────
function selectTheme(key, vars) {
  selectedTheme = key;
  updateTiles(key);
  saveThemeSetting({ theme: key });
}

// ── Select saved custom theme ──────────────────────────────────────────────
function selectCustomTheme(id, vars) {
  const key = 'custom-' + id;
  selectedTheme = key;
  updateTiles(key);
  saveThemeSetting({ theme: key, theme_custom: typeof vars === 'string' ? vars : JSON.stringify(vars) });
}

function updateTiles(activeKey) {
  document.querySelectorAll('.theme-tile').forEach(t => {
    t.style.borderColor = t.dataset.theme === activeKey ? 'var(--accent)' : 'var(--border)';
  });
}

async function saveThemeSetting(data) {
  try {
    await api('POST', '/api/admin/settings', data);
    showToast('Theme saved — reloading…');
    setTimeout(() => location.reload(), 700);
  } catch(e) { showToast(e.message, 'err'); }
}

// ── Theme editor ───────────────────────────────────────────────────────────
const defaultVars = {
  '--bg':'#0f0d09','--surface':'#1a1710','--surface-2':'#221e14','--surface-3':'#2c271a',
  '--accent':'#c8862a','--accent-light':'#e8a84a','--text':'#f0e8d4','--text-muted':'#9a8f78'
};

function openEditor(theme) {
  document.getElementById('theme-editor-overlay').style.display = 'flex';
  document.getElementById('editing-theme-id').value = theme?.id ?? '';
  document.getElementById('editor-title').textContent = theme ? 'Edit Theme' : 'New Theme';
  document.getElementById('theme-name').value = theme?.name ?? '';
  const vars = theme ? JSON.parse(theme.vars || '{}') : defaultVars;
  loadVarsIntoPickers(vars);
  livePreview();
}

function closeEditor() {
  document.getElementById('theme-editor-overlay').style.display = 'none';
}

function loadPresetIntoEditor(preset) {
  loadVarsIntoPickers(preset);
  livePreview();
}

function loadVarsIntoPickers(vars) {
  document.querySelectorAll('#editor-pickers input[type=color]').forEach(inp => {
    inp.value = vars[inp.dataset.var] ?? defaultVars[inp.dataset.var] ?? '#000000';
  });
}

function getEditorVars() {
  const vars = {};
  document.querySelectorAll('#editor-pickers input[type=color]').forEach(inp => { vars[inp.dataset.var] = inp.value; });
  return vars;
}

function livePreview() {
  const v = getEditorVars();
  document.getElementById('preview-bg').style.background     = v['--surface'] ?? '#1a1710';
  document.getElementById('preview-accent').style.background = v['--accent']  ?? '#c8862a';
  document.getElementById('preview-text').style.color        = v['--text']    ?? '#f0e8d4';
  document.getElementById('preview-muted').style.color       = v['--text-muted'] ?? '#9a8f78';
  document.getElementById('preview-surface').style.background= v['--surface-2'] ?? '#221e14';
  document.getElementById('preview-surface').style.color     = v['--text-muted'] ?? '#9a8f78';
}

async function saveCustomTheme() {
  const name = document.getElementById('theme-name').value.trim();
  if (!name) { showToast('Enter a theme name', 'err'); return; }
  const vars = getEditorVars();
  const id   = document.getElementById('editing-theme-id').value;
  try {
    await api('POST', '/api/admin/themes', { name, vars: JSON.stringify(vars), id });
    showToast('Theme saved — reloading…');
    setTimeout(() => location.reload(), 700);
  } catch(e) { showToast(e.message, 'err'); }
}

async function deleteCustomTheme(id, btn) {
  if (!confirm('Delete this theme?')) return;
  try {
    await fetch(`/api/admin/themes/${id}`, { method: 'DELETE' });
    btn.closest('.theme-tile').remove();
    showToast('Theme deleted');
  } catch(e) { showToast(e.message, 'err'); }
}

// Close overlay on bg click
document.getElementById('theme-editor-overlay').addEventListener('click', function(e) {
  if (e.target === this) closeEditor();
});
</script>
