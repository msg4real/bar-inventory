<div class="page-header">
  <h1 class="page-title"><i class="ti ti-layout-list" style="font-size:1.4rem"></i> Fields</h1>
  <button class="primary" onclick="saveAll()"><i class="ti ti-device-floppy"></i> Save All</button>
</div>

<div style="display:grid;gap:16px;max-width:860px">

  <!-- Built-in fields -->
  <div class="card" style="padding:1.5rem">
    <h2 style="font-size:1rem;margin-bottom:1rem">Built-in Fields</h2>
    <div style="display:flex;flex-direction:column;gap:8px" id="builtin-list">
      <?php foreach ($builtin as $f): ?>
      <div style="display:flex;align-items:center;gap:12px;padding:10px;background:var(--surface-2);border-radius:var(--radius);border:1px solid var(--border)" data-field="<?= htmlspecialchars($f['field_name']) ?>">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;flex-shrink:0">
          <input type="checkbox" class="field-visible" <?= $f['visible']?'checked':'' ?>>
          <span style="font-size:12px;color:var(--text-muted)">Visible</span>
        </label>
        <input type="text" class="field-label" value="<?= htmlspecialchars($f['label']) ?>" style="flex:1;max-width:220px" placeholder="Label">
        <span style="font-size:12px;color:var(--text-faint);font-family:monospace"><?= htmlspecialchars($f['field_name']) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Category options -->
  <div class="card" style="padding:1.5rem">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
      <h2 style="font-size:1rem">Category Options</h2>
      <button onclick="addCategory()" style="font-size:13px"><i class="ti ti-plus"></i> Add</button>
    </div>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:1rem">These appear in all category dropdowns.</p>
    <div id="cat-list" style="display:flex;flex-direction:column;gap:6px">
      <?php
        $cats = json_decode($settings['categories'] ?? '[]', true) ?: ['Bourbon','Scotch','Whiskey','Vodka','Gin','Rum','Tequila','Mezcal','Cognac','Brandy','Champagne','Rosé','Red Wine','White Wine','Wine','Beer','Liqueur','Other'];
        foreach ($cats as $cat):
      ?>
      <div class="cat-row" style="display:flex;align-items:center;gap:8px">
        <i class="ti ti-grip-vertical" style="color:var(--text-faint);cursor:grab;font-size:16px"></i>
        <input type="text" class="cat-val" value="<?= htmlspecialchars($cat) ?>" style="flex:1">
        <button onclick="this.closest('.cat-row').remove()" class="ghost danger" style="padding:6px 8px;flex-shrink:0"><i class="ti ti-trash" style="font-size:14px"></i></button>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Custom fields -->
  <div class="card" style="padding:1.5rem">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
      <h2 style="font-size:1rem">Custom Fields</h2>
      <button onclick="addCustomField()" style="font-size:13px"><i class="ti ti-plus"></i> Add Field</button>
    </div>
    <div id="custom-list" style="display:flex;flex-direction:column;gap:8px">
      <?php foreach ($custom as $f): ?>
      <div class="custom-row" style="display:flex;align-items:center;gap:10px;padding:10px;background:var(--surface-2);border-radius:var(--radius);border:1px solid var(--border)" data-id="<?= $f['id'] ?>">
        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;flex-shrink:0">
          <input type="checkbox" class="cf-enabled" <?= $f['enabled']?'checked':'' ?>>
          <span style="font-size:12px;color:var(--text-muted)">On</span>
        </label>
        <input type="text" class="cf-label" value="<?= htmlspecialchars($f['label']) ?>" style="flex:1" placeholder="Field name">
        <!-- Custom type dropdown -->
        <div class="cs-wrap" data-name="cf-type" data-value="<?= htmlspecialchars($f['type']) ?>" style="width:130px">
          <div class="cs-options" data-value="text">Text</div>
          <div class="cs-options" data-value="number">Number</div>
          <div class="cs-options" data-value="date">Date</div>
          <div class="cs-options" data-value="boolean">Yes / No</div>
        </div>
        <button onclick="deleteCustomField(this, <?= $f['id'] ?>)" class="ghost danger" style="padding:6px 8px;flex-shrink:0"><i class="ti ti-trash" style="font-size:14px"></i></button>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

</div>

<script>
function addCategory() {
  const row = document.createElement('div');
  row.className = 'cat-row';
  row.style.cssText = 'display:flex;align-items:center;gap:8px';
  row.innerHTML = `
    <i class="ti ti-grip-vertical" style="color:var(--text-faint);cursor:grab;font-size:16px"></i>
    <input type="text" class="cat-val" value="" style="flex:1" placeholder="Category name">
    <button onclick="this.closest('.cat-row').remove()" class="ghost danger" style="padding:6px 8px;flex-shrink:0"><i class="ti ti-trash" style="font-size:14px"></i></button>
  `;
  document.getElementById('cat-list').appendChild(row);
  row.querySelector('input').focus();
}

function addCustomField() {
  const row = document.createElement('div');
  row.className = 'custom-row';
  row.dataset.id = '0';
  row.style.cssText = 'display:flex;align-items:center;gap:10px;padding:10px;background:var(--surface-2);border-radius:var(--radius);border:1px solid var(--border)';
  row.innerHTML = `
    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;flex-shrink:0">
      <input type="checkbox" class="cf-enabled" checked>
      <span style="font-size:12px;color:var(--text-muted)">On</span>
    </label>
    <input type="text" class="cf-label" style="flex:1" placeholder="Field name">
    <div class="cs-wrap" data-name="cf-type" data-value="text" style="width:130px">
      <div class="cs-options" data-value="text">Text</div>
      <div class="cs-options" data-value="number">Number</div>
      <div class="cs-options" data-value="date">Date</div>
      <div class="cs-options" data-value="boolean">Yes / No</div>
    </div>
    <button onclick="this.closest('.custom-row').remove()" class="ghost danger" style="padding:6px 8px;flex-shrink:0"><i class="ti ti-trash" style="font-size:14px"></i></button>
  `;
  document.getElementById('custom-list').appendChild(row);
  initCustomSelects(row);
  row.querySelector('.cf-label').focus();
}

const toDelete = [];
function deleteCustomField(btn, id) {
  if (id) toDelete.push(id);
  btn.closest('.custom-row').remove();
}

async function saveAll() {
  // Built-in fields
  const builtin = [...document.querySelectorAll('#builtin-list [data-field]')].map(el => ({
    field_name: el.dataset.field,
    label:      el.querySelector('.field-label').value,
    visible:    el.querySelector('.field-visible').checked ? 1 : 0,
  }));

  // Categories
  const categories = [...document.querySelectorAll('.cat-val')]
    .map(el => el.value.trim()).filter(Boolean);

  // Custom fields
  const custom = [...document.querySelectorAll('.custom-row')].map(el => {
    const typeWrap = el.querySelector('.cs-wrap');
    return {
      id:      el.dataset.id || '0',
      label:   el.querySelector('.cf-label').value,
      type:    typeWrap?.querySelector('input[type=hidden]')?.value || 'text',
      enabled: el.querySelector('.cf-enabled').checked ? 1 : 0,
    };
  }).filter(f => f.label);

  try {
    await Promise.all([
      api('POST', '/api/admin/fields',      { builtin, custom, delete_custom: toDelete }),
      api('POST', '/api/admin/categories',  { categories }),
    ]);
    toDelete.length = 0;
    showToast('Fields saved');
  } catch(e) { showToast(e.message, 'err'); }
}

document.addEventListener('DOMContentLoaded', () => initCustomSelects());
</script>
