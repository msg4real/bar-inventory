<div class="page-header">
  <h1 class="page-title"><i class="ti ti-users" style="font-size:1.4rem"></i> Users</h1>
  <button class="primary" onclick="openUserModal()"><i class="ti ti-plus"></i> Add User</button>
</div>

<div class="card" style="max-width:800px;overflow:hidden">
  <table style="width:100%;border-collapse:collapse">
    <thead>
      <tr style="border-bottom:1px solid var(--border)">
        <?php foreach (['Username','Role','Last Login','Added','Actions'] as $h): ?>
        <th style="text-align:left;padding:12px 16px;font-size:12px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em"><?= $h ?></th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($users as $u):
      $isMe = $u['id'] === ($user['id'] ?? 0);
      $roleColor = ['admin'=>'var(--accent-light)','editor'=>'var(--success)','viewer'=>'var(--info)'][$u['role']] ?? 'var(--text-muted)';
    ?>
    <tr style="border-bottom:1px solid var(--border)">
      <td style="padding:12px 16px;font-weight:500">
        <?= htmlspecialchars($u['username']) ?>
        <?php if ($isMe): ?><span style="font-size:11px;color:var(--text-faint);margin-left:4px">(you)</span><?php endif; ?>
      </td>
      <td style="padding:12px 16px">
        <span class="badge" style="background:<?= $roleColor ?>22;color:<?= $roleColor ?>;border:1px solid <?= $roleColor ?>44">
          <?= ucfirst($u['role']) ?>
        </span>
      </td>
      <td style="padding:12px 16px;font-size:13px;color:var(--text-muted)"><?= $u['last_login'] ? substr($u['last_login'],0,16) : 'Never' ?></td>
      <td style="padding:12px 16px;font-size:13px;color:var(--text-muted)"><?= substr($u['created_at'],0,10) ?></td>
      <td style="padding:8px 16px">
        <div style="display:flex;gap:4px">
          <button class="ghost" onclick='openEditUser(<?= htmlspecialchars(json_encode($u),ENT_QUOTES) ?>)' title="Edit"><i class="ti ti-edit" style="font-size:15px"></i></button>
          <?php if (!$isMe): ?>
          <button class="ghost" onclick="confirmDeleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>')" style="color:var(--danger)" title="Delete"><i class="ti ti-trash" style="font-size:15px"></i></button>
          <?php endif; ?>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="card" style="max-width:800px;padding:1.25rem;margin-top:16px">
  <h2 style="font-size:1rem;margin-bottom:.5rem"><i class="ti ti-info-circle" style="color:var(--accent-light)"></i> Role permissions</h2>
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:.75rem">
    <?php foreach ([
      ['Admin','var(--accent-light)','Full access — inventory, recipes, admin settings, user management'],
      ['Editor','var(--success)','Can add, edit and delete bottles and recipes — no admin access'],
      ['Viewer','var(--info)','Read-only — can browse inventory and recipes but cannot make changes'],
    ] as [$role,$color,$desc]): ?>
    <div style="padding:10px 12px;background:var(--surface-2);border-radius:var(--radius);border:1px solid var(--border)">
      <p style="font-weight:600;color:<?= $color ?>;margin-bottom:4px"><?= $role ?></p>
      <p style="font-size:12px;color:var(--text-muted)"><?= $desc ?></p>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Add/Edit modal -->
<div id="user-modal" class="overlay" style="display:none" onclick="if(event.target===this)closeUserModal()">
  <div class="card" style="width:100%;max-width:400px;padding:1.75rem;margin-top:6rem">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem">
      <h2 id="user-modal-title" style="font-family:var(--font-display);font-size:1.2rem">Add User</h2>
      <button class="ghost" onclick="closeUserModal()"><i class="ti ti-x"></i></button>
    </div>
    <form id="user-form" style="display:flex;flex-direction:column;gap:12px">
      <input type="hidden" id="u-id">
      <div><label class="label">Username *</label><input id="u-username" required autocomplete="off"></div>
      <div><label class="label">Role</label>
        <select id="u-role">
          <option value="viewer">Viewer — read only</option>
          <option value="editor">Editor — add/edit/delete</option>
          <option value="admin">Admin — full access</option>
        </select>
      </div>
      <div><label class="label">Password *</label><input type="password" id="u-password" autocomplete="new-password"><p id="pw-hint" style="font-size:11px;color:var(--text-faint);margin-top:3px"></p></div>
      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:.5rem">
        <button type="button" onclick="closeUserModal()">Cancel</button>
        <button type="submit" class="primary" id="user-save-btn">Add User</button>
      </div>
    </form>
  </div>
</div>

<!-- Delete confirm -->
<div id="delete-user-modal" class="overlay" style="display:none" onclick="if(event.target===this)this.style.display='none'">
  <div class="card" style="width:100%;max-width:380px;padding:1.75rem;margin-top:8rem">
    <h2 style="margin-bottom:.5rem">Delete user?</h2>
    <p style="color:var(--text-muted);font-size:14px;margin-bottom:1.5rem"><strong id="del-username" style="color:var(--text)"></strong> will lose all access.</p>
    <div style="display:flex;gap:8px;justify-content:flex-end">
      <button onclick="document.getElementById('delete-user-modal').style.display='none'">Cancel</button>
      <button class="danger" onclick="doDeleteUser()">Delete user</button>
    </div>
  </div>
</div>

<script>
let editUserId = null, deleteUserId = null;

function openUserModal() {
  editUserId = null;
  document.getElementById('user-modal-title').textContent = 'Add User';
  document.getElementById('user-save-btn').textContent    = 'Add User';
  document.getElementById('user-form').reset();
  document.getElementById('u-id').value = '';
  document.getElementById('pw-hint').textContent = '';
  document.getElementById('user-modal').style.display = 'flex';
}

function openEditUser(u) {
  editUserId = u.id;
  document.getElementById('user-modal-title').textContent = 'Edit User';
  document.getElementById('user-save-btn').textContent    = 'Save Changes';
  document.getElementById('u-id').value       = u.id;
  document.getElementById('u-username').value = u.username;
  document.getElementById('u-role').value     = u.role;
  document.getElementById('u-password').value = '';
  document.getElementById('pw-hint').textContent = 'Leave blank to keep current password';
  document.getElementById('user-modal').style.display = 'flex';
}

function closeUserModal() { document.getElementById('user-modal').style.display = 'none'; }

document.getElementById('user-form').addEventListener('submit', async e => {
  e.preventDefault();
  const btn = document.getElementById('user-save-btn');
  btn.disabled = true; btn.textContent = 'Saving…';
  const data = {
    username: document.getElementById('u-username').value,
    role:     document.getElementById('u-role').value,
    password: document.getElementById('u-password').value,
  };
  try {
    if (editUserId) {
      await api('PUT', `/api/admin/users/${editUserId}`, data);
      showToast('User updated');
    } else {
      if (!data.password) { showToast('Password is required','err'); btn.disabled=false; btn.textContent='Add User'; return; }
      await api('POST', '/api/admin/users', data);
      showToast(`${data.username} created`);
    }
    closeUserModal();
    setTimeout(() => location.reload(), 500);
  } catch(err) {
    showToast(err.message, 'err');
    btn.disabled = false; btn.textContent = editUserId ? 'Save Changes' : 'Add User';
  }
});

function confirmDeleteUser(id, name) {
  deleteUserId = id;
  document.getElementById('del-username').textContent = name;
  document.getElementById('delete-user-modal').style.display = 'flex';
}
async function doDeleteUser() {
  try {
    await api('DELETE', `/api/admin/users/${deleteUserId}`);
    showToast('User deleted');
    document.getElementById('delete-user-modal').style.display = 'none';
    setTimeout(() => location.reload(), 500);
  } catch(err) { showToast(err.message, 'err'); }
}
</script>
