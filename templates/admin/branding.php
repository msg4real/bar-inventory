<div class="page-header">
  <h1 class="page-title"><i class="ti ti-brand-abstract" style="font-size:1.4rem"></i> Branding</h1>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;max-width:800px">

  <!-- Bar Name -->
  <div class="card" style="padding:1.5rem">
    <h2 style="font-family:var(--font-display);font-size:1.1rem;margin-bottom:1rem">Bar Name</h2>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:1rem">Shown in the sidebar header and browser tab.</p>
    <form id="name-form" style="display:flex;flex-direction:column;gap:12px">
      <input id="app-name" value="<?= htmlspecialchars($settings['app_name'] ?? 'Bar Inventory') ?>" placeholder="e.g. The Tiki Shack">
      <button type="submit" class="primary" style="justify-content:center"><i class="ti ti-check"></i> Save Name</button>
    </form>
  </div>

  <!-- Logo Upload -->
  <div class="card" style="padding:1.5rem">
    <h2 style="font-family:var(--font-display);font-size:1.1rem;margin-bottom:1rem">Logo</h2>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:1rem">PNG, JPG, SVG or WebP. Displays next to your bar name.</p>

    <!-- Current logo -->
    <div id="current-logo" style="margin-bottom:1rem;<?= empty($settings['app_logo'])?'display:none':'' ?>">
      <img id="logo-preview" src="<?= htmlspecialchars($settings['app_logo'] ?? '') ?>"
           style="height:64px;object-fit:contain;border-radius:8px;background:var(--surface-2);padding:8px;border:1px solid var(--border)">
      <p style="font-size:12px;color:var(--text-faint);margin-top:6px">Current logo</p>
    </div>

    <!-- Upload -->
    <div id="drop-zone"
         style="border:2px dashed var(--border-md);border-radius:var(--radius-lg);padding:1.75rem;text-align:center;cursor:pointer;transition:border-color .15s"
         onclick="document.getElementById('logo-file').click()"
         ondragover="event.preventDefault();this.style.borderColor='var(--accent)'"
         ondragleave="this.style.borderColor='var(--border-md)'"
         ondrop="handleDrop(event)">
      <i class="ti ti-upload" style="font-size:28px;color:var(--text-faint);display:block;margin-bottom:.5rem"></i>
      <p style="font-size:14px;color:var(--text-muted)">Click or drag &amp; drop</p>
      <p style="font-size:12px;color:var(--text-faint);margin-top:4px">PNG · JPG · SVG · WebP · max 10MB</p>
    </div>
    <input type="file" id="logo-file" accept="image/*" style="display:none" onchange="uploadLogo(this.files[0])">

    <?php if (!empty($settings['app_logo'])): ?>
    <button onclick="removeLogo()" class="danger" style="width:100%;justify-content:center;margin-top:10px">
      <i class="ti ti-trash"></i> Remove logo
    </button>
    <?php endif; ?>
  </div>

</div>

<!-- Preview -->
<div class="card" style="max-width:800px;padding:1.5rem;margin-top:16px">
  <h2 style="font-size:1rem;margin-bottom:1rem;color:var(--text-muted)">Preview</h2>
  <div style="display:flex;align-items:center;gap:10px;padding:1rem;background:var(--surface-2);border-radius:var(--radius);border:1px solid var(--border);max-width:280px">
    <div id="preview-logo-wrap">
      <?php if (!empty($settings['app_logo'])): ?>
      <img id="preview-logo" src="<?= htmlspecialchars($settings['app_logo']) ?>" style="height:32px;width:32px;object-fit:contain;border-radius:4px">
      <?php else: ?>
      <span id="preview-emoji" style="font-size:24px">🍾</span>
      <?php endif; ?>
    </div>
    <span id="preview-name" style="font-family:var(--font-display);font-size:1.05rem;font-weight:600"><?= htmlspecialchars($settings['app_name'] ?? 'Bar Inventory') ?></span>
  </div>
</div>

<script>
document.getElementById('name-form').addEventListener('submit', async e => {
  e.preventDefault();
  const name = document.getElementById('app-name').value.trim();
  if (!name) return;
  try {
    await api('POST', '/api/admin/settings', { app_name: name });
    document.getElementById('preview-name').textContent = name;
    showToast('Bar name saved');
  } catch(err) { showToast(err.message, 'err'); }
});

function handleDrop(e) {
  e.preventDefault();
  document.getElementById('drop-zone').style.borderColor = 'var(--border-md)';
  const file = e.dataTransfer.files[0];
  if (file) uploadLogo(file);
}

async function uploadLogo(file) {
  if (!file) return;
  const fd = new FormData();
  fd.append('logo', file);
  try {
    const r = await fetch('/api/admin/logo', { method:'POST', body:fd });
    const d = await r.json();
    if (!r.ok) throw new Error(d.error);
    document.getElementById('logo-preview').src = d.url;
    document.getElementById('current-logo').style.display = '';
    document.getElementById('preview-logo-wrap').innerHTML = `<img src="${d.url}" style="height:32px;width:32px;object-fit:contain;border-radius:4px">`;
    showToast('Logo uploaded');
  } catch(err) { showToast(err.message, 'err'); }
}

async function removeLogo() {
  try {
    await api('POST', '/api/admin/settings', { app_logo: '' });
    document.getElementById('current-logo').style.display = 'none';
    document.getElementById('preview-logo-wrap').innerHTML = '<span style="font-size:24px">🍾</span>';
    showToast('Logo removed');
  } catch(err) { showToast(err.message, 'err'); }
}
</script>
