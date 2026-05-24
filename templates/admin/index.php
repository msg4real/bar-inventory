<?php $requireLogin = ($settings['require_login'] ?? '0') === '1'; ?>

<div class="page-header">
  <h1 class="page-title"><i class="ti ti-settings" style="font-size:1.4rem"></i> Admin Settings</h1>
</div>

<?php if ($needsPin): ?>
<!-- PIN gate -->
<div class="card" style="max-width:360px;padding:2rem;margin:0 auto;text-align:center">
  <div style="font-size:40px;margin-bottom:1rem">🔒</div>
  <h2 style="font-family:var(--font-display);margin-bottom:.5rem">Admin PIN required</h2>
  <p style="color:var(--text-muted);font-size:14px;margin-bottom:1.5rem">Enter your admin PIN to access settings</p>
  <form id="pin-form" style="display:flex;flex-direction:column;gap:10px">
    <input type="password" id="pin-input" placeholder="PIN" inputmode="numeric" maxlength="6" autofocus style="text-align:center;font-size:1.5rem;letter-spacing:.3em">
    <p id="pin-error" style="color:var(--danger);font-size:13px;min-height:20px"></p>
    <button type="submit" class="primary" style="justify-content:center">Unlock</button>
  </form>
</div>
<script>
document.getElementById('pin-form').addEventListener('submit', async e => {
  e.preventDefault();
  try {
    await api('POST', '/admin/pin/verify', { pin: document.getElementById('pin-input').value });
    location.reload();
  } catch {
    document.getElementById('pin-error').textContent = 'Incorrect PIN';
    document.getElementById('pin-input').value = '';
  }
});
</script>

<?php else: ?>

<!-- Settings cards -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;max-width:900px">

  <!-- General -->
  <div class="card" style="padding:1.5rem">
    <h2 style="font-family:var(--font-display);margin-bottom:1rem;font-size:1.1rem"><i class="ti ti-adjustments-horizontal" style="color:var(--accent-light)"></i> General</h2>
    <form id="general-form" style="display:flex;flex-direction:column;gap:12px">
      <div><label class="label">Default Category</label>
        <select name="default_category">
          <?php foreach (['Whiskey','Bourbon','Scotch','Wine','Red Wine','White Wine','Rosé','Vodka','Rum','Gin','Tequila','Mezcal','Cognac','Brandy','Champagne','Beer','Other'] as $c): ?>
          <option value="<?= $c ?>" <?= ($settings['default_category']??'')===$c?'selected':'' ?>><?= $c ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div><label class="label">Default Fill Level</label>
        <select name="default_fill">
          <?php foreach ([100=>'Full',75=>'¾ (75%)',50=>'Half (50%)',25=>'¼ (25%)',0=>'Empty'] as $v=>$l): ?>
          <option value="<?= $v ?>" <?= ($settings['default_fill']??'100')==(string)$v?'selected':'' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="display:grid;grid-template-columns:1fr 80px;gap:8px">
        <div><label class="label">Currency</label>
          <select name="currency">
            <?php foreach (['USD'=>'USD','GBP'=>'GBP','EUR'=>'EUR','AUD'=>'AUD','CAD'=>'CAD','JPY'=>'JPY'] as $code=>$label): ?>
            <option value="<?= $code ?>" <?= ($settings['currency']??'USD')===$code?'selected':'' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div><label class="label">Symbol</label>
          <input name="currency_symbol" value="<?= htmlspecialchars($settings['currency_symbol']??'$') ?>" style="text-align:center">
        </div>
      </div>
      <button type="submit" class="primary" style="justify-content:center"><i class="ti ti-check"></i> Save</button>
    </form>
  </div>

  <!-- Security -->
  <div class="card" style="padding:1.5rem">
    <h2 style="font-family:var(--font-display);margin-bottom:1rem;font-size:1.1rem"><i class="ti ti-shield-lock" style="color:var(--accent-light)"></i> Security</h2>
    <form id="security-form" style="display:flex;flex-direction:column;gap:12px">
      <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 12px;background:var(--surface-2);border-radius:var(--radius);border:1px solid var(--border)">
        <div>
          <p style="font-weight:500;font-size:14px">Require login</p>
          <p style="font-size:12px;color:var(--text-muted)">All users must sign in to view inventory</p>
        </div>
        <label style="position:relative;display:inline-block;width:44px;height:24px;flex-shrink:0">
          <input type="checkbox" name="require_login" value="1" <?= $requireLogin?'checked':'' ?> style="opacity:0;width:0;height:0">
          <span class="slider" style="position:absolute;cursor:pointer;inset:0;background:<?= $requireLogin?'var(--accent)':'var(--surface-3)' ?>;border-radius:24px;transition:.2s">
            <span style="position:absolute;height:18px;width:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.2s;transform:<?= $requireLogin?'translateX(20px)':'' ?>"></span>
          </span>
        </label>
      </div>
      <div><label class="label">Change Admin PIN</label>
        <input type="password" name="admin_pin" placeholder="Leave blank to keep current" inputmode="numeric" maxlength="6">
        <p style="font-size:11px;color:var(--text-faint);margin-top:4px">4–6 digits. Protects admin settings even when logged in.</p>
      </div>
      <button type="submit" class="primary" style="justify-content:center"><i class="ti ti-check"></i> Save</button>
    </form>
  </div>

  <!-- Quick links -->
  <div class="card" style="padding:1.5rem">
    <h2 style="font-family:var(--font-display);margin-bottom:1rem;font-size:1.1rem"><i class="ti ti-layout-grid" style="color:var(--accent-light)"></i> Quick Links</h2>
    <div style="display:flex;flex-direction:column;gap:8px">
      <?php foreach ([
        ['/admin/fields',  'ti-layout-list',       'Manage Fields',  'Rename, hide, or add custom fields'],
        ['/admin/users',   'ti-users',              'Users',          'Create accounts, set roles'],
        ['/admin/theme',   'ti-palette',            'Theme',          'Pick preset or custom colours'],
        ['/admin/branding','ti-brand-abstract',     'Branding',       'Upload logo, set bar name'],
        ['/admin/email',   'ti-mail',             'Email',          'Gmail SMTP for password resets'],
        ['/import',        'ti-file-import',      'Import',         'Import bottles or recipes'],
        ['/export',        'ti-file-export',      'Export',         'Download your inventory data'],
      ] as [$url,$icon,$title,$desc]): ?>
      <a href="<?= $url ?>" style="display:flex;align-items:center;gap:12px;padding:10px 12px;background:var(--surface-2);border-radius:var(--radius);border:1px solid var(--border);text-decoration:none;transition:border-color .15s" onmouseover="this.style.borderColor='var(--border-strong)'" onmouseout="this.style.borderColor='var(--border)'">
        <i class="ti <?= $icon ?>" style="font-size:20px;color:var(--accent-light);flex-shrink:0"></i>
        <div>
          <p style="font-weight:500;font-size:14px;color:var(--text)"><?= $title ?></p>
          <p style="font-size:12px;color:var(--text-muted)"><?= $desc ?></p>
        </div>
        <i class="ti ti-chevron-right" style="color:var(--text-faint);margin-left:auto"></i>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

</div>

<script>
document.getElementById('general-form').addEventListener('submit', async e => {
  e.preventDefault();
  const data = Object.fromEntries(new FormData(e.target));
  try { await api('POST','/api/admin/settings', data); showToast('Settings saved'); }
  catch(err) { showToast(err.message,'err'); }
});

document.getElementById('security-form').addEventListener('submit', async e => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const data = { require_login: fd.get('require_login')?'1':'0' };
  if (fd.get('admin_pin')) data.admin_pin = fd.get('admin_pin');
  try { await api('POST','/api/admin/settings', data); showToast('Security settings saved'); setTimeout(()=>location.reload(),800); }
  catch(err) { showToast(err.message,'err'); }
});

// Toggle visual
document.querySelector('input[name="require_login"]').addEventListener('change', function() {
  const slider = this.nextElementSibling;
  const knob   = slider.querySelector('span');
  slider.style.background   = this.checked ? 'var(--accent)' : 'var(--surface-3)';
  knob.style.transform      = this.checked ? 'translateX(20px)' : '';
});
</script>

<?php endif; ?>
