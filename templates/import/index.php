<div class="page-header">
  <h1 class="page-title"><i class="ti ti-file-import" style="font-size:1.4rem"></i> Import</h1>
</div>

<div style="max-width:900px">

  <!-- Tab switcher -->
  <div style="display:flex;gap:8px;margin-bottom:1.5rem">
    <button class="primary" id="tab-bottles" onclick="switchTab('bottles')">
      <i class="ti ti-bottles"></i> Bottles
    </button>
    <button id="tab-recipes" onclick="switchTab('recipes')">
      <i class="ti ti-cocktail"></i> Recipes
    </button>
  </div>

  <!-- ── Bottles import ── -->
  <div id="pane-bottles">
    <div class="card" style="padding:1.5rem;margin-bottom:1rem">
      <h2 style="font-size:1rem;margin-bottom:.75rem">Upload File</h2>
      <p style="font-size:13px;color:var(--text-muted);margin-bottom:1rem">
        Accepts <strong>.csv</strong> or <strong>.xlsx</strong>. Required column: <code>name</code>. Optional: <code>brand, category, vintage, abv, country, fill, barcode, notes</code>.
      </p>
      <div style="display:flex;gap:10px;align-items:flex-end">
        <div style="flex:1">
          <label class="label">File</label>
          <input type="file" id="bottle-file" accept=".csv,.xlsx" style="display:block">
        </div>
        <button onclick="parseBottleFile()" class="primary">
          <i class="ti ti-upload"></i> Parse
        </button>
      </div>
      <div style="margin-top:1rem;font-size:13px;color:var(--text-muted)">
        <strong>Tip:</strong> Export your existing inventory first to use as a template.
      </div>
    </div>

    <div id="bottle-review" style="display:none">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem;flex-wrap:wrap;gap:8px">
        <span id="bottle-summary" style="font-size:14px;color:var(--text-muted)"></span>
        <div style="display:flex;gap:8px">
          <button onclick="resolveAll('bottles','skip')"><i class="ti ti-x"></i> Skip all dupes</button>
          <button onclick="resolveAll('bottles','overwrite')"><i class="ti ti-refresh"></i> Overwrite all dupes</button>
          <button onclick="doImport('bottles')" class="primary" id="btn-import-bottles">
            <i class="ti ti-check"></i> Import
          </button>
        </div>
      </div>
      <div style="overflow-x:auto">
        <table id="bottle-table" style="width:100%;border-collapse:collapse;font-size:13px">
          <thead>
            <tr style="background:var(--surface-2);color:var(--text-muted)">
              <th style="padding:8px 10px;text-align:left;font-weight:500">Name</th>
              <th style="padding:8px 10px;text-align:left;font-weight:500">Brand</th>
              <th style="padding:8px 10px;text-align:left;font-weight:500">Category</th>
              <th style="padding:8px 10px;text-align:left;font-weight:500">ABV</th>
              <th style="padding:8px 10px;text-align:left;font-weight:500">Fill</th>
              <th style="padding:8px 10px;text-align:left;font-weight:500">Status</th>
              <th style="padding:8px 10px;text-align:left;font-weight:500">Action</th>
            </tr>
          </thead>
          <tbody id="bottle-tbody"></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ── Recipes import ── -->
  <div id="pane-recipes" style="display:none">
    <div class="card" style="padding:1.5rem;margin-bottom:1rem">
      <h2 style="font-size:1rem;margin-bottom:.75rem">Upload File</h2>
      <p style="font-size:13px;color:var(--text-muted);margin-bottom:1rem">
        Accepts <strong>.csv</strong> or <strong>.xlsx</strong>. Required column: <code>name</code>. Optional: <code>category, glass, garnish, description, ingredients, instructions</code>.<br>
        Ingredients format: <code>2 oz Rum; 1 oz Lime Juice; 0.5 oz Sugar Syrup</code>
      </p>
      <div style="display:flex;gap:10px;align-items:flex-end">
        <div style="flex:1">
          <label class="label">File</label>
          <input type="file" id="recipe-file" accept=".csv,.xlsx" style="display:block">
        </div>
        <button onclick="parseRecipeFile()" class="primary">
          <i class="ti ti-upload"></i> Parse
        </button>
      </div>
    </div>

    <div id="recipe-review" style="display:none">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem;flex-wrap:wrap;gap:8px">
        <span id="recipe-summary" style="font-size:14px;color:var(--text-muted)"></span>
        <div style="display:flex;gap:8px">
          <button onclick="resolveAll('recipes','skip')"><i class="ti ti-x"></i> Skip all dupes</button>
          <button onclick="resolveAll('recipes','overwrite')"><i class="ti ti-refresh"></i> Overwrite all dupes</button>
          <button onclick="doImport('recipes')" class="primary" id="btn-import-recipes">
            <i class="ti ti-check"></i> Import
          </button>
        </div>
      </div>
      <div style="overflow-x:auto">
        <table id="recipe-table" style="width:100%;border-collapse:collapse;font-size:13px">
          <thead>
            <tr style="background:var(--surface-2);color:var(--text-muted)">
              <th style="padding:8px 10px;text-align:left;font-weight:500">Name</th>
              <th style="padding:8px 10px;text-align:left;font-weight:500">Category</th>
              <th style="padding:8px 10px;text-align:left;font-weight:500">Glass</th>
              <th style="padding:8px 10px;text-align:left;font-weight:500">Ingredients</th>
              <th style="padding:8px 10px;text-align:left;font-weight:500">Status</th>
              <th style="padding:8px 10px;text-align:left;font-weight:500">Action</th>
            </tr>
          </thead>
          <tbody id="recipe-tbody"></tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
let bottleRows = [], recipeRows = [];
let existingBottles = [], existingRecipes = [];

// Load existing data for dupe detection
async function loadExisting() {
  [existingBottles, existingRecipes] = await Promise.all([
    api('GET', '/api/bottles'),
    api('GET', '/api/recipes'),
  ]);
}
loadExisting();

function switchTab(tab) {
  ['bottles','recipes'].forEach(t => {
    document.getElementById(`pane-${t}`).style.display = t===tab ? '' : 'none';
    document.getElementById(`tab-${t}`).className = t===tab ? 'primary' : '';
  });
}

// ── Parse helpers ──────────────────────────────────────────────────────────
function normalise(headers) {
  return headers.map(h => String(h||'').toLowerCase().replace(/[^a-z0-9]+/g,'_').replace(/^_+|_+$/g,''));
}

function fileToRows(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = e => {
      try {
        const wb   = XLSX.read(e.target.result, {type:'binary'});
        const ws   = wb.Sheets[wb.SheetNames[0]];
        const data = XLSX.utils.sheet_to_json(ws, {header:1, defval:''});
        if (data.length < 2) { resolve([]); return; }
        const headers = normalise(data[0]);
        const rows = data.slice(1).map(row => {
          const obj = {};
          headers.forEach((h, i) => { obj[h] = String(row[i] ?? '').trim(); });
          return obj;
        }).filter(r => Object.values(r).some(v => v !== ''));
        resolve(rows);
      } catch(err) { reject(err); }
    };
    reader.onerror = reject;
    reader.readAsBinaryString(file);
  });
}

// ── Bottles ────────────────────────────────────────────────────────────────
async function parseBottleFile() {
  const file = document.getElementById('bottle-file').files[0];
  if (!file) { showToast('Pick a file first','err'); return; }
  try {
    const raw = await fileToRows(file);
    // Map flexible column names
    bottleRows = raw.map(r => ({
      name:     r.name     || r.bottle || r.product || '',
      brand:    r.brand    || '',
      category: r.category || r.type   || 'Other',
      vintage:  r.vintage  || r.year   || '',
      abv:      r.abv      || r.abv_   || r['abv_%'] || '',
      country:  r.country  || r.origin || '',
      fill:     r.fill     || r['fill_%'] || r.fill_level || '100',
      barcode:  r.barcode  || r.upc    || r.ean   || '',
      notes:    r.notes    || r.description || '',
    })).filter(r => r.name);

    // Dupe detection (by name or barcode)
    bottleRows = bottleRows.map(r => {
      const dupe = existingBottles.find(b =>
        b.name.toLowerCase() === r.name.toLowerCase() ||
        (r.barcode && b.barcode && b.barcode === r.barcode)
      );
      return { ...r, _existing_id: dupe?.id ?? null, _is_dupe: !!dupe, _action: dupe ? 'ask' : 'new' };
    });

    renderBottleTable();
  } catch(e) { showToast(e.message,'err'); }
}

function renderBottleTable() {
  const tbody = document.getElementById('bottle-tbody');
  const dupes = bottleRows.filter(r => r._is_dupe).length;
  document.getElementById('bottle-summary').textContent =
    `${bottleRows.length} rows parsed — ${dupes} duplicate${dupes!==1?'s':''} found`;
  document.getElementById('bottle-review').style.display = '';

  tbody.innerHTML = bottleRows.map((r, i) => {
    const fillNum = parseInt(r.fill) || 100;
    const rowBg   = r._is_dupe ? 'background:rgba(232,168,74,.06)' : '';
    return `<tr style="border-bottom:1px solid var(--border);${rowBg}">
      <td style="padding:8px 10px">${escH(r.name)}</td>
      <td style="padding:8px 10px;color:var(--text-muted)">${escH(r.brand)}</td>
      <td style="padding:8px 10px">${escH(r.category)}</td>
      <td style="padding:8px 10px">${escH(r.abv)}</td>
      <td style="padding:8px 10px">${fillNum}%</td>
      <td style="padding:8px 10px">${r._is_dupe
        ? '<span style="color:var(--warning);font-size:12px"><i class="ti ti-alert-triangle"></i> Duplicate</span>'
        : '<span style="color:var(--success);font-size:12px"><i class="ti ti-plus"></i> New</span>'}</td>
      <td style="padding:8px 10px" id="baction-${i}">
        ${r._is_dupe ? dupeActions('bottles', i, r._action) : '<span style="color:var(--text-muted);font-size:12px">—</span>'}
      </td>
    </tr>`;
  }).join('');
}

function dupeActions(type, i, current) {
  const rows = type === 'bottles' ? bottleRows : recipeRows;
  return `
    <div style="display:flex;gap:4px">
      <button onclick="setAction('${type}',${i},'skip')" class="${current==='skip'?'danger':'ghost'}" style="font-size:12px;padding:4px 8px">Skip</button>
      <button onclick="setAction('${type}',${i},'overwrite')" class="${current==='overwrite'?'primary':'ghost'}" style="font-size:12px;padding:4px 8px">Overwrite</button>
      <button onclick="setAction('${type}',${i},'new')" class="${current==='new'?'':'ghost'}" style="font-size:12px;padding:4px 8px">Add new</button>
    </div>`;
}

function setAction(type, i, action) {
  if (type === 'bottles') { bottleRows[i]._action = action; renderBottleTable(); }
  else { recipeRows[i]._action = action; renderRecipeTable(); }
}

function resolveAll(type, action) {
  const rows = type === 'bottles' ? bottleRows : recipeRows;
  rows.forEach(r => { if (r._is_dupe) r._action = action; });
  if (type === 'bottles') renderBottleTable(); else renderRecipeTable();
}

// ── Recipes ────────────────────────────────────────────────────────────────
async function parseRecipeFile() {
  const file = document.getElementById('recipe-file').files[0];
  if (!file) { showToast('Pick a file first','err'); return; }
  try {
    const raw = await fileToRows(file);
    recipeRows = raw.map(r => ({
      name:         r.name         || r.recipe  || '',
      category:     r.category     || 'Tiki',
      glass:        r.glass        || '',
      garnish:      r.garnish      || '',
      description:  r.description  || r.desc    || '',
      ingredients:  r.ingredients  || r.ingredient || '',
      instructions: r.instructions || r.method  || r.steps || '',
    })).filter(r => r.name);

    recipeRows = recipeRows.map(r => {
      const dupe = existingRecipes.find(x => x.name.toLowerCase() === r.name.toLowerCase());
      return { ...r, _existing_id: dupe?.id ?? null, _is_dupe: !!dupe, _action: dupe ? 'ask' : 'new' };
    });

    renderRecipeTable();
  } catch(e) { showToast(e.message,'err'); }
}

function renderRecipeTable() {
  const tbody = document.getElementById('recipe-tbody');
  const dupes = recipeRows.filter(r => r._is_dupe).length;
  document.getElementById('recipe-summary').textContent =
    `${recipeRows.length} rows parsed — ${dupes} duplicate${dupes!==1?'s':''} found`;
  document.getElementById('recipe-review').style.display = '';

  tbody.innerHTML = recipeRows.map((r, i) => {
    const ings = r.ingredients ? r.ingredients.split(';').slice(0,3).map(s=>s.trim()).join(', ') + (r.ingredients.split(';').length > 3 ? '…' : '') : '—';
    const rowBg = r._is_dupe ? 'background:rgba(232,168,74,.06)' : '';
    return `<tr style="border-bottom:1px solid var(--border);${rowBg}">
      <td style="padding:8px 10px">${escH(r.name)}</td>
      <td style="padding:8px 10px;color:var(--text-muted)">${escH(r.category)}</td>
      <td style="padding:8px 10px;color:var(--text-muted)">${escH(r.glass)||'—'}</td>
      <td style="padding:8px 10px;font-size:12px;color:var(--text-muted);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${escH(ings)}</td>
      <td style="padding:8px 10px">${r._is_dupe
        ? '<span style="color:var(--warning);font-size:12px"><i class="ti ti-alert-triangle"></i> Duplicate</span>'
        : '<span style="color:var(--success);font-size:12px"><i class="ti ti-plus"></i> New</span>'}</td>
      <td style="padding:8px 10px" id="raction-${i}">
        ${r._is_dupe ? dupeActions('recipes', i, r._action) : '<span style="color:var(--text-muted);font-size:12px">—</span>'}
      </td>
    </tr>`;
  }).join('');
}

// ── Submit import ──────────────────────────────────────────────────────────
async function doImport(type) {
  const rows    = type === 'bottles' ? bottleRows : recipeRows;
  const btn     = document.getElementById(`btn-import-${type}`);
  const unresolved = rows.filter(r => r._is_dupe && r._action === 'ask');
  if (unresolved.length) {
    showToast(`Please resolve ${unresolved.length} duplicate${unresolved.length!==1?'s':''} first`, 'err');
    return;
  }

  btn.disabled = true; btn.innerHTML = '<span class="spin">↻</span> Importing…';
  try {
    const actions = rows.reduce((acc, r, i) => {
      acc[i] = r._action === 'ask' ? 'skip' : r._action;
      return acc;
    }, {});
    const result = await api('POST', `/api/import/${type}`, { rows, actions });
    showToast(`Imported ${result.imported}, skipped ${result.skipped}`);
    await loadExisting();
    if (type === 'bottles') { bottleRows = []; document.getElementById('bottle-review').style.display = 'none'; document.getElementById('bottle-file').value = ''; }
    else { recipeRows = []; document.getElementById('recipe-review').style.display = 'none'; document.getElementById('recipe-file').value = ''; }
  } catch(e) { showToast(e.message,'err'); }
  btn.disabled = false; btn.innerHTML = '<i class="ti ti-check"></i> Import';
}

function escH(s) { return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
</script>
