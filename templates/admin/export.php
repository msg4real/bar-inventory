<div class="page-header">
  <h1 class="page-title"><i class="ti ti-file-spreadsheet" style="font-size:1.4rem"></i> Export</h1>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;max-width:800px">

  <!-- Export options -->
  <div class="card" style="padding:1.5rem">
    <h2 style="font-family:var(--font-display);font-size:1.1rem;margin-bottom:1rem">Export Inventory</h2>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:1.25rem">
      Download your <strong style="color:var(--text)"><?= $count ?></strong> bottle<?= $count!=1?'s':'' ?> as a spreadsheet.
    </p>

    <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:1.5rem">
      <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
        <input type="checkbox" id="include-empty" <?= ($settings['export_include_empty']??'1')==='1'?'checked':'' ?>>
        <span style="font-size:14px">Include empty bottles</span>
      </label>
      <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
        <input type="checkbox" id="include-custom" checked>
        <span style="font-size:14px">Include custom fields</span>
      </label>
    </div>

    <div style="display:flex;flex-direction:column;gap:8px">
      <button onclick="exportXlsx()" class="primary" id="export-xlsx-btn" style="justify-content:center">
        <i class="ti ti-file-spreadsheet"></i> Download .xlsx (Excel)
      </button>
      <button onclick="exportCsv()" id="export-csv-btn" style="justify-content:center">
        <i class="ti ti-file-text"></i> Download .csv
      </button>
    </div>
  </div>

  <!-- Export recipes -->
  <div class="card" style="padding:1.5rem">
    <h2 style="font-family:var(--font-display);font-size:1.1rem;margin-bottom:1rem">Export Recipes</h2>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:1.25rem">Download your recipe collection as a spreadsheet.</p>
    <button onclick="exportRecipes()" style="justify-content:center;width:100%">
      <i class="ti ti-file-spreadsheet"></i> Download Recipes .xlsx
    </button>

    <div style="margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid var(--border)">
      <h2 style="font-size:1rem;margin-bottom:.75rem">Export preferences</h2>
      <form id="pref-form" style="display:flex;flex-direction:column;gap:8px">
        <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
          <input type="checkbox" name="export_include_empty" <?= ($settings['export_include_empty']??'1')==='1'?'checked':'' ?>>
          <span style="font-size:13px;color:var(--text-muted)">Default: include empty bottles</span>
        </label>
        <button type="submit" style="font-size:13px;margin-top:4px">Save preference</button>
      </form>
    </div>
  </div>

</div>

<script>
async function loadXLSX() {
  if (window.XLSX) return;
  await new Promise((res,rej) => {
    const s = document.createElement('script');
    s.src = 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js';
    s.onload = res; s.onerror = rej; document.head.appendChild(s);
  });
}

async function exportXlsx() {
  const btn = document.getElementById('export-xlsx-btn');
  btn.disabled = true; btn.innerHTML = '<span class="spin">↻</span> Loading…';
  try {
    await loadXLSX();
    const bottles = await api('GET', '/api/bottles');
    const includeEmpty   = document.getElementById('include-empty').checked;
    const includeCustom  = document.getElementById('include-custom').checked;
    const filtered = includeEmpty ? bottles : bottles.filter(b => (b.fill??100) > 0);
    const fillLabel = v => v===100?'Full':v===0?'Empty':`${v}%`;

    const headers = ['Name','Brand','Category','Vintage','ABV (%)','Country','Fill Level','Fill %','Barcode','Notes','Added'];
    if (includeCustom) {
      // Get unique custom keys
      const keys = new Set();
      filtered.forEach(b => { Object.keys(typeof b.custom_data==='string'?JSON.parse(b.custom_data||'{}'):b.custom_data||{}).forEach(k=>keys.add(k)); });
      keys.forEach(k => headers.push(k));
    }

    const rows = [headers, ...filtered.sort((a,b)=>(a.name||'').localeCompare(b.name||'')).map(b => {
      const cd = typeof b.custom_data==='string' ? JSON.parse(b.custom_data||'{}') : (b.custom_data||{});
      const row = [b.name||'',b.brand||'',b.category||'',b.vintage||'',
        b.abv!==null&&b.abv!==''?Number(b.abv):'',b.country||'',
        fillLabel(b.fill??100),b.fill??100,b.barcode||'',b.notes||'',(b.created_at||'').slice(0,10)];
      if (includeCustom) Object.values(cd).forEach(v => row.push(v));
      return row;
    })];

    // Summary sheet
    const cats = {};
    filtered.forEach(b => { cats[b.category||'Other'] = (cats[b.category||'Other']||0)+1; });
    const summary = [
      ['Bar Inventory — Summary'],['Exported', new Date().toLocaleString()],[],
      ['Category','Count'],
      ...Object.entries(cats).sort().map(([c,n])=>[c,n]),[],['Total',filtered.length]
    ];

    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet(rows);
    ws['!cols'] = [{wch:32},{wch:24},{wch:14},{wch:10},{wch:9},{wch:16},{wch:10},{wch:8},{wch:16},{wch:40},{wch:14}];
    XLSX.utils.book_append_sheet(wb, ws, 'Inventory');
    const ws2 = XLSX.utils.aoa_to_sheet(summary);
    XLSX.utils.book_append_sheet(wb, ws2, 'Summary');
    XLSX.writeFile(wb, `bar-inventory-${new Date().toISOString().slice(0,10)}.xlsx`);
    showToast(`Exported ${filtered.length} bottles`);
  } catch(err) { showToast(err.message,'err'); }
  btn.disabled = false; btn.innerHTML = '<i class="ti ti-file-spreadsheet"></i> Download .xlsx (Excel)';
}

async function exportCsv() {
  window.location.href = '/api/export/xlsx'; // server returns CSV
}

async function exportRecipes() {
  await loadXLSX();
  const recipes = await api('GET', '/api/recipes');
  const rows = [['Name','Category','Glass','Garnish','Description','Ingredients','Instructions']];
  recipes.forEach(r => {
    const ings = (r.ingredients||[]).map(i => `${i.amount||''} ${i.unit||''} ${i.name}`.trim()).join('; ');
    rows.push([r.name,r.category,r.glass||'',r.garnish||'',r.description||'',ings,r.instructions||'']);
  });
  const wb = XLSX.utils.book_new();
  const ws = XLSX.utils.aoa_to_sheet(rows);
  ws['!cols'] = [{wch:28},{wch:14},{wch:14},{wch:18},{wch:30},{wch:50},{wch:60}];
  XLSX.utils.book_append_sheet(wb, ws, 'Recipes');
  XLSX.writeFile(wb, `bar-recipes-${new Date().toISOString().slice(0,10)}.xlsx`);
  showToast(`Exported ${recipes.length} recipes`);
}

document.getElementById('pref-form').addEventListener('submit', async e => {
  e.preventDefault();
  const fd = new FormData(e.target);
  await api('POST', '/api/admin/settings', { export_include_empty: fd.get('export_include_empty')?'1':'0' });
  showToast('Preference saved');
});
</script>
