<?php
$requireLogin = ($settings['require_login'] ?? '0') === '1';
$categories   = json_decode($settings['categories'] ?? '[]', true) ?: ['Bourbon','Scotch','Whiskey','Vodka','Gin','Rum','Tequila','Mezcal','Cognac','Brandy','Champagne','Rosé','Red Wine','White Wine','Wine','Beer','Liqueur','Other'];
$defCat       = $settings['default_category'] ?? 'Other';
$defFill      = $settings['default_fill']     ?? '100';
$defCurrency  = $settings['currency']         ?? 'USD';
?>

<div class="page-header">
  <h1 class="page-title"><i class="ti ti-settings" style="font-size:1.4rem"></i> Admin Settings</h1>
</div>

<?php if ($needsPin): ?>
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

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;max-width:900px">

  <!-- General -->
  <div class="card" style="padding:1.5rem">
    <h2 style="font-family:var(--font-display);margin-bottom:1rem;font-size:1.1rem">
      <i class="ti ti-adjustments-horizontal" style="color:var(--accent-light)"></i> General
    </h2>
    <div style="display:flex;flex-direction:column;gap:12px">

      <div>
        <label class="label">Default Category</label>
        <div class="cs-wrap" data-name="default_category" data-value="<?= htmlspecialchars($defCat) ?>" data-searchable="true">
          <?php foreach ($categories as $c): ?>
          <div class="cs-options" data-value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></div>
          <?php endforeach; ?>
        </div>
      </div>

      <div>
        <label class="label">Default Fill Level</label>
        <div class="cs-wrap" data-name="default_fill" data-value="<?= htmlspecialchars($defFill) ?>">
          <div class="cs-options" data-value="100">Full (100%)</div>
          <div class="cs-options" data-value="75">¾ (75%)</div>
          <div class="cs-options" data-value="50">Half (50%)</div>
          <div class="cs-options" data-value="25">¼ (25%)</div>
          <div class="cs-options" data-value="0">Empty</div>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 80px;gap:8px">
        <div>
          <label class="label">Currency</label>
          <div class="cs-wrap" data-name="currency" data-value="<?= htmlspecialchars($defCurrency) ?>">
            <div class="cs-options" data-value="USD">USD</div>
            <div class="cs-options" data-value="GBP">GBP</div>
            <div class="cs-options" data-value="EUR">EUR</div>
            <div class="cs-options" data-value="AUD">AUD</div>
            <div class="cs-options" data-value="CAD">CAD</div>
            <div class="cs-options" data-value="JPY">JPY</div>
          </div>
        </div>
        <div>
          <label class="label">Symbol</label>
          <input name="currency_symbol" id="currency_symbol" value="<?= htmlspecialchars($settings['currency_symbol'] ?? '$') ?>" style="text-align:center">
        </div>
      </div>

      <button onclick="saveGeneral()" class="primary" style="justify-content:center">
        <i class="ti ti-check"></i> Save
      </button>
    </div>
  </div>

  <!-- Security -->
  <div class="card" style="padding:1.5rem">
    <h2 style="font-family:var(--font-display);margin-bottom:1rem;font-size:1.1rem">
      <i class="ti ti-shield-lock" style="color:var(--accent-light)"></i> Security
    </h2>
    <div style="display:flex;flex-direction:column;gap:12px">
      <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 12px;background:var(--surface-2);border-radius:var(--radius);border:1px solid var(--border)">
        <div>
          <p style="font-weight:500;font-size:14px">Require login</p>
          <p style="font-size:12px;color:var(--text-muted)">All users must sign in to view inventory</p>
        </div>
        <label style="position:relative;display:inline-block;width:44px;height:24px;flex-shrink:0">
          <input type="checkbox" id="require_login" value="1" <?= $requireLogin ? 'checked' : '' ?> style="opacity:0;width:0;height:0;position:absolute">
          <span id="login-slider" style="position:absolute;cursor:pointer;inset:0;background:<?= $requireLogin ? 'var(--accent)' : 'var(--surface-3)' ?>;border-radius:24px;transition:.2s">
            <span id="login-knob" style="position:absolute;height:18px;width:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.2s;transform:<?= $requireLogin ? 'translateX(20px)' : '' ?>"></span>
          </span>
        </label>
      </div>
      <div>
        <label class="label">Change Admin PIN</label>
        <input type="password" id="admin_pin" placeholder="Leave blank to keep current" inputmode="numeric" maxlength="6">
        <p style="font-size:11px;color:var(--text-faint);margin-top:4px">4–6 digits. Protects admin settings even when logged in.</p>
      </div>
      <button onclick="saveSecurity()" class="primary" style="justify-content:center">
        <i class="ti ti-check"></i> Save
      </button>
    </div>
  </div>

  <!-- Quick links -->
  <div class="card" style="padding:1.5rem">
    <h2 style="font-family:var(--font-display);margin-bottom:1rem;font-size:1.1rem">
      <i class="ti ti-layout-grid" style="color:var(--accent-light)"></i> Quick Links
    </h2>
    <div style="display:flex;flex-direction:column;gap:8px">
      <?php foreach ([
        ['/admin/fields',  'ti-layout-list',     'Manage Fields',  'Rename, hide, or add custom fields'],
        ['/admin/users',   'ti-users',            'Users',          'Create accounts, set roles'],
        ['/admin/theme',   'ti-palette',          'Theme',          'Pick preset or custom colours'],
        ['/admin/branding','ti-brand-abstract',   'Branding',       'Upload logo, set bar name'],
        ['/import',        'ti-file-import',      'Import',         'Import bottles or recipes'],
        ['/export',        'ti-file-export',      'Export',         'Download your inventory data'],
      ] as [$url,$icon,$title,$desc]): ?>
      <a href="<?= $url ?>" style="display:flex;align-items:center;gap:12px;padding:10px 12px;background:var(--surface-2);border-radius:var(--radius);border:1px solid var(--border);text-decoration:none;transition:border-color .15s"
         onmouseover="this.style.borderColor='var(--border-strong)'" onmouseout="this.style.borderColor='var(--border)'">
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
// initCustomSelects called by app.js

async function saveGeneral() {
  const data = {
    default_category: document.querySelector('[data-name="default_category"] input[type=hidden]')?.value,
    default_fill:     document.querySelector('[data-name="default_fill"] input[type=hidden]')?.value,
    currency:         document.querySelector('[data-name="currency"] input[type=hidden]')?.value,
    currency_symbol:  document.getElementById('currency_symbol').value,
  };
  try { await api('POST', '/api/admin/settings', data); showToast('Settings saved'); }
  catch(err) { showToast(err.message, 'err'); }
}

async function saveSecurity() {
  const data = { require_login: document.getElementById('require_login').checked ? '1' : '0' };
  const pin = document.getElementById('admin_pin').value;
  if (pin) data.admin_pin = pin;
  try {
    await api('POST', '/api/admin/settings', data);
    showToast('Security settings saved');
    setTimeout(() => location.reload(), 800);
  } catch(err) { showToast(err.message, 'err'); }
}

document.getElementById('require_login').addEventListener('change', function() {
  document.getElementById('login-slider').style.background = this.checked ? 'var(--accent)' : 'var(--surface-3)';
  document.getElementById('login-knob').style.transform    = this.checked ? 'translateX(20px)' : '';
});
</script>

<?php endif; ?>
