<div class="page-header">
  <h1 class="page-title"><i class="ti ti-file-export" style="font-size:1.4rem"></i> Export</h1>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;max-width:860px">

  <div class="card" style="padding:1.5rem">
    <h2 style="font-family:var(--font-display);font-size:1.1rem;margin-bottom:.5rem">Export Inventory</h2>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:1.25rem">
      <strong style="color:var(--text)"><?= $count ?></strong> bottle<?= $count!=1?'s':'' ?> in your inventory.
    </p>
    <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:1.25rem">
      <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
        <input type="checkbox" id="inc-empty" checked>
        <span style="font-size:14px">Include empty bottles</span>
      </label>
      <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
        <input type="checkbox" id="inc-custom" checked>
        <span style="font-size:14px">Include custom fields</span>
      </label>
    </div>
    <div style="display:flex;flex-direction:column;gap:8px">
      <button onclick="exportBottlesXlsx()" class="primary" id="btn-bxlsx" style="justify-content:center">
        <i class="ti ti-file-spreadsheet"></i> Download .xlsx
      </button>
      <button onclick="exportBottlesCsv()" id="btn-bcsv" style="justify-content:center">
        <i class="ti ti-file-text"></i> Download .csv
      </button>
    </div>
  </div>

  <div class="card" style="padding:1.5rem">
    <h2 style="font-family:var(--font-display);font-size:1.1rem;margin-bottom:.5rem">Export Recipes</h2>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:1.25rem">
      <strong style="color:var(--text)"><?= $rcount ?></strong> recipe<?= $rcount!=1?'s':'' ?> in your collection.
    </p>
    <div style="display:flex;flex-direction:column;gap:8px">
      <button onclick="exportRecipesXlsx()" class="primary" id="btn-rxlsx" style="justify-content:center">
        <i class="ti ti-file-spreadsheet"></i> Download .xlsx
      </button>
      <button onclick="exportRecipesCsv()" id="btn-rcsv" style="justify-content:center">
        <i class="ti ti-file-text"></i> Download .csv
      </button>
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

function csvEsc(v) { v = String(v??''); return v.includes(',') || v.includes('"') || v.includes('\n') ? `"${v.replace(/"/g,'""')}"` : v; }

async function exportBottlesXlsx() {
  const btn = document.getElementById('btn-bxlsx');
  btn.disabled = true; btn.innerHTML = '<span class="spin">↻</span> Exporting…';
  try {
    await loadXLSX();
    let bottles = await api('GET', '/api/bottles');
    if (!document.getElementById('inc-empty').checked) bottles = bottles.filter(b => (b.fill??100) > 0);
    const incCustom = document.getElementById('inc-custom').checked;
    const hdrs = ['Name','Brand','Category','Vintage','ABV (%)','Country','Fill %','Barcode','Notes','Added'];
    if (incCustom) { const keys = new Set(); bottles.forEach(b => Object.keys(b.custom_data||{}).forEach(k=>keys.add(k))); keys.forEach(k=>hdrs.push(k)); }
    const rows = [hdrs, ...bottles.map(b => {
      const r = [b.name,b.brand,b.category,b.vintage,b.abv??'',b.country,b.fill??100,b.barcode,b.notes,(b.created_at||'').slice(0,10)];
      if (incCustom) Object.values(b.custom_data||{}).forEach(v=>r.push(v));
      return r;
    })];
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet(rows);
    ws['!cols'] = [{wch:32},{wch:24},{wch:14},{wch:10},{wch:9},{wch:16},{wch:8},{wch:16},{wch:40},{wch:14}];
    XLSX.utils.book_append_sheet(wb, ws, 'Inventory');
    XLSX.writeFile(wb, `bar-inventory-${new Date().toISOString().slice(0,10)}.xlsx`);
    showToast(`Exported ${bottles.length} bottles`);
  } catch(e) { showToast(e.message,'err'); }
  btn.disabled=false; btn.innerHTML='<i class="ti ti-file-spreadsheet"></i> Download .xlsx';
}

async function exportBottlesCsv() {
  window.location.href = '/api/export/csv';
}

async function exportRecipesXlsx() {
  const btn = document.getElementById('btn-rxlsx');
  btn.disabled = true; btn.innerHTML = '<span class="spin">↻</span> Exporting…';
  try {
    await loadXLSX();
    const recipes = await api('GET', '/api/recipes');
    const rows = [['Name','Category','Glass','Garnish','Description','Ingredients','Instructions']];
    recipes.forEach(r => {
      const ings = (r.ingredients||[]).map(i=>`${i.amount||''} ${i.unit||''} ${i.name}`.trim()).join('; ');
      rows.push([r.name,r.category,r.glass||'',r.garnish||'',r.description||'',ings,r.instructions||'']);
    });
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet(rows);
    ws['!cols'] = [{wch:28},{wch:14},{wch:14},{wch:18},{wch:30},{wch:50},{wch:60}];
    XLSX.utils.book_append_sheet(wb, ws, 'Recipes');
    XLSX.writeFile(wb, `bar-recipes-${new Date().toISOString().slice(0,10)}.xlsx`);
    showToast(`Exported ${recipes.length} recipes`);
  } catch(e) { showToast(e.message,'err'); }
  btn.disabled=false; btn.innerHTML='<i class="ti ti-file-spreadsheet"></i> Download .xlsx';
}

async function exportRecipesCsv() {
  await loadXLSX();
  const recipes = await api('GET', '/api/recipes');
  const hdrs = ['Name','Category','Glass','Garnish','Description','Ingredients','Instructions'];
  const lines = [hdrs.map(csvEsc).join(',')];
  recipes.forEach(r => {
    const ings = (r.ingredients||[]).map(i=>`${i.amount||''} ${i.unit||''} ${i.name}`.trim()).join('; ');
    lines.push([r.name,r.category,r.glass||'',r.garnish||'',r.description||'',ings,r.instructions||''].map(csvEsc).join(','));
  });
  const blob = new Blob([lines.join('\n')], {type:'text/csv'});
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = `bar-recipes-${new Date().toISOString().slice(0,10)}.csv`;
  a.click();
  showToast(`Exported ${recipes.length} recipes`);
}
</script>
