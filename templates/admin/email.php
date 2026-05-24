<div class="page-header">
  <h1 class="page-title"><i class="ti ti-mail" style="font-size:1.4rem"></i> Email Settings</h1>
</div>

<div style="max-width:560px;display:flex;flex-direction:column;gap:16px">

  <div class="card" style="padding:1.5rem">
    <h2 style="font-family:var(--font-display);font-size:1.1rem;margin-bottom:.5rem">Gmail SMTP</h2>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:1.25rem">
      Used for forgot-password emails. Requires a Gmail
      <a href="https://myaccount.google.com/apppasswords" target="_blank" style="color:var(--accent-light)">App Password</a>
      (not your regular Gmail password).
    </p>

    <div style="display:flex;flex-direction:column;gap:12px">
      <div style="display:grid;grid-template-columns:1fr 100px;gap:8px">
        <div>
          <label class="label">SMTP Host</label>
          <input id="smtp_host" value="<?= htmlspecialchars($settings['smtp_host'] ?? 'smtp.gmail.com') ?>">
        </div>
        <div>
          <label class="label">Port</label>
          <input id="smtp_port" type="number" value="<?= htmlspecialchars($settings['smtp_port'] ?? '587') ?>">
        </div>
      </div>
      <div>
        <label class="label">Gmail Address</label>
        <input id="smtp_user" type="email" value="<?= htmlspecialchars($settings['smtp_user'] ?? '') ?>" placeholder="you@gmail.com">
      </div>
      <div>
        <label class="label">App Password</label>
        <input id="smtp_pass" type="password" value="<?= htmlspecialchars($settings['smtp_pass'] ?? '') ?>" placeholder="16-character app password" autocomplete="new-password">
        <p style="font-size:11px;color:var(--text-faint);margin-top:4px">
          Generate at Google Account → Security → 2-Step Verification → App passwords
        </p>
      </div>
      <div>
        <label class="label">From Name</label>
        <input id="smtp_from_name" value="<?= htmlspecialchars($settings['smtp_from_name'] ?? 'Bar Inventory') ?>">
      </div>

      <div style="display:flex;gap:8px;padding-top:4px">
        <button onclick="saveSmtp()" class="primary"><i class="ti ti-device-floppy"></i> Save</button>
        <button onclick="testSmtp()" id="btn-test"><i class="ti ti-send"></i> Send Test Email</button>
      </div>
      <p id="smtp-result" style="font-size:13px;min-height:18px"></p>
    </div>
  </div>

  <div class="card" style="padding:1.25rem;background:var(--surface-2)">
    <p style="font-size:13px;color:var(--text-muted);line-height:1.7">
      <strong style="color:var(--text)">Setup steps:</strong><br>
      1. Enable 2-Step Verification on your Google account<br>
      2. Go to <a href="https://myaccount.google.com/apppasswords" target="_blank" style="color:var(--accent-light)">myaccount.google.com/apppasswords</a><br>
      3. Create an app password for "Mail"<br>
      4. Paste the 16-character code above (spaces are fine)<br>
      5. Save and send a test email
    </p>
  </div>

</div>

<script>
async function saveSmtp() {
  const data = {
    smtp_host:      document.getElementById('smtp_host').value,
    smtp_port:      document.getElementById('smtp_port').value,
    smtp_user:      document.getElementById('smtp_user').value,
    smtp_pass:      document.getElementById('smtp_pass').value.replace(/\s/g,''),
    smtp_from:      document.getElementById('smtp_user').value,
    smtp_from_name: document.getElementById('smtp_from_name').value,
  };
  try { await api('POST', '/api/admin/settings', data); showToast('SMTP settings saved'); }
  catch(e) { showToast(e.message, 'err'); }
}

async function testSmtp() {
  const btn = document.getElementById('btn-test');
  const res = document.getElementById('smtp-result');
  btn.disabled = true; btn.innerHTML = '<span class="spin">↻</span> Sending…';
  res.textContent = '';
  try {
    await saveSmtp();
    await api('POST', '/api/admin/smtp/test', {});
    res.style.color = 'var(--success)';
    res.textContent = '✅ Test email sent to ' + document.getElementById('smtp_user').value;
  } catch(e) {
    res.style.color = 'var(--danger)';
    res.textContent = '❌ ' + e.message;
  }
  btn.disabled = false; btn.innerHTML = '<i class="ti ti-send"></i> Send Test Email';
}
</script>
