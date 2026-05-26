<?php
$total = count($bottles);
$full  = count(array_filter($bottles, fn($b) => ($b['fill']??100)==100));
$open  = count(array_filter($bottles, fn($b) => ($b['fill']??100)>0 && ($b['fill']??100)<100));
$empty = count(array_filter($bottles, fn($b) => ($b['fill']??100)==0));
$cats  = array_unique(array_column($bottles, 'category'));
sort($cats);

$defaultCat  = $settings['default_category'] ?? 'Whiskey';
$defaultFill = (int)($settings['default_fill'] ?? 100);
$currency    = $settings['currency_symbol'] ?? '$';

$visibleFields = [];
foreach ($fields as $f) { if ($f['visible']) $visibleFields[$f['field_name']] = $f['label']; }
?>

<!-- Stats -->
<?php if ($total > 0): ?>
<div class="grid-4" style="margin-bottom:1.5rem">
  <?php foreach ([['Total',$total,'var(--accent-light)'],['Full',$full,'var(--success)'],['Open',$open,'var(--warning)'],['Empty',$empty,'var(--text-faint)']] as [$l,$v,$c]): ?>
  <div class="stat-card"><div class="stat-label"><?= $l ?></div><div class="stat-value" style="color:<?= $c ?>"><?= $v ?></div></div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="page-header">
  <h1 class="page-title">🍾 All Bottles</h1>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <?php if ($total > 0): ?>
    <button onclick="exportData()" id="export-btn"><i class="ti ti-file-spreadsheet"></i> Export</button>
    <?php endif; ?>
    <?php if ($canEdit): ?>
    <button onclick="openAddModal()" class="primary"><i class="ti ti-plus"></i> Add Bottle</button>
    <?php endif; ?>
  </div>
</div>

<?php if ($total > 0): ?>
<!-- Filters -->
<div style="display:flex;gap:8px;margin-bottom:1rem;flex-wrap:wrap">
  <div style="flex:1;min-width:200px;position:relative">
    <i class="ti ti-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-faint);font-size:15px;pointer-events:none"></i>
    <input id="search" placeholder="Search bottles…" style="padding-left:36px" oninput="filterBottles()">
  </div>
  <div class="cs-wrap" id="sort-wrap" data-name="sort-by" data-value="name" style="width:175px">
    <div class="cs-options" data-value="name">Name A–Z</div>
    <div class="cs-options" data-value="category">Category</div>
    <div class="cs-options" data-value="fill-desc">Fullest first</div>
    <div class="cs-options" data-value="fill-asc">Almost empty</div>
    <div class="cs-options" data-value="newest">Recently added</div>
  </div>
</div>
<!-- Category chips -->
<div id="cat-chips" style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:1.5rem">
  <button class="cat-chip active" data-cat="All" onclick="setCat(this)" style="padding:5px 12px;font-size:13px;border-radius:99px;background:var(--accent-dim2);border:1px solid var(--accent);color:var(--accent-light)">All (<?= $total ?>)</button>
  <?php foreach ($cats as $cat): ?>
  <?php $n = count(array_filter($bottles, fn($b) => $b['category']===$cat)); ?>
  <button class="cat-chip" data-cat="<?= htmlspecialchars($cat) ?>" onclick="setCat(this)" style="padding:5px 12px;font-size:13px;border-radius:99px;background:transparent;border:1px solid var(--border-md);color:var(--text-muted)"><?= htmlspecialchars($cat) ?> (<?= $n ?>)</button>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Bottle grid -->
<div id="bottle-grid" class="grid-auto">
<?php foreach ($bottles as $b):
  $fill = $b['fill'] ?? 100;
  $fillColor = $fill>=75 ? 'var(--success)' : ($fill>=50 ? 'var(--warning)' : ($fill>0 ? 'var(--danger)' : 'var(--text-faint)'));
  $fillLabel = $fill===100 ? 'Full' : ($fill===0 ? 'Empty' : "{$fill}%");
  $cd = json_decode($b['custom_data'] ?: '{}', true);
?>
<div class="card bottle-card" data-name="<?= htmlspecialchars(strtolower($b['name'])) ?>"
     data-brand="<?= htmlspecialchars(strtolower($b['brand']??'')) ?>"
     data-cat="<?= htmlspecialchars($b['category']??'') ?>"
     data-fill="<?= $fill ?>"
     data-created="<?= $b['created_at'] ?>"
     style="padding:1.1rem;display:flex;flex-direction:column;gap:10px;opacity:<?= $fill===0?'0.5':'1' ?>">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px">
    <div style="flex:1;min-width:0">
      <p style="font-weight:600;font-size:15px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($b['name']) ?></p>
      <p style="font-size:13px;color:var(--text-muted);margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
        <?= htmlspecialchars(implode(' · ', array_filter([$b['brand']??'', $b['vintage']??'']))) ?: '—' ?>
      </p>
    </div>
    <?php if ($canEdit): ?>
    <div style="display:flex;gap:2px;flex-shrink:0">
      <button class="ghost" onclick='editBottle(<?= htmlspecialchars(json_encode($b), ENT_QUOTES) ?>)' title="Edit"><i class="ti ti-edit" style="font-size:15px"></i></button>
      <button class="ghost" onclick="deleteBottle(<?= $b['id'] ?>, '<?= htmlspecialchars(addslashes($b['name'])) ?>')" style="color:var(--danger)" title="Delete"><i class="ti ti-trash" style="font-size:15px"></i></button>
    </div>
    <?php endif; ?>
  </div>
  <div style="display:flex;flex-wrap:wrap;gap:5px">
    <?php if (!empty($b['category'])): ?><span class="badge badge-cat"><?= htmlspecialchars($b['category']) ?></span><?php endif; ?>
    <?php if (!empty($b['country'])  && isset($visibleFields['country'])): ?><span class="badge badge-country"><i class="ti ti-map-pin" style="font-size:10px"></i><?= htmlspecialchars($b['country']) ?></span><?php endif; ?>
    <?php if (!empty($b['abv'])      && isset($visibleFields['abv'])): ?><span class="badge badge-abv"><?= $b['abv'] ?>% ABV</span><?php endif; ?>
  </div>
  <!-- Fill bar -->
  <div style="display:flex;align-items:center;gap:10px">
    <div class="fill-bar"><div class="fill-bar-inner" style="width:<?= $fill ?>%;background:<?= $fillColor ?>"></div></div>
    <span style="font-size:12px;font-weight:500;min-width:36px;text-align:right;color:<?= $fillColor ?>"><?= $fillLabel ?></span>
  </div>
  <?php if (!empty($b['notes']) && isset($visibleFields['notes'])): ?>
  <p style="font-size:12px;color:var(--text-muted);font-style:italic;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden"><?= htmlspecialchars($b['notes']) ?></p>
  <?php endif; ?>
  <?php foreach ($custom as $cf):
    $val = $cd[$cf['name']] ?? '';
    if ($val === '' || $val === null) continue; ?>
  <p style="font-size:12px;color:var(--text-muted)"><strong><?= htmlspecialchars($cf['label']) ?>:</strong> <?= htmlspecialchars((string)$val) ?></p>
  <?php endforeach; ?>
  <?php if (!empty($b['barcode']) && isset($visibleFields['barcode'])): ?>
  <p style="font-size:11px;color:var(--text-faint)"><i class="ti ti-barcode" style="font-size:12px"></i> <?= htmlspecialchars($b['barcode']) ?></p>
  <?php endif; ?>
</div>
<?php endforeach; ?>
</div>

<?php if ($total === 0): ?>
<div style="text-align:center;padding:6rem 2rem">
  <div style="font-size:56px;margin-bottom:1rem">🍾</div>
  <h2 style="font-family:var(--font-display);font-size:1.5rem;margin-bottom:.5rem">Your bar is empty</h2>
  <p style="color:var(--text-muted);margin-bottom:2rem">Add bottles manually or scan a barcode to get started.</p>
  <?php if ($canEdit): ?>
  <button class="primary" onclick="openAddModal()" style="padding:12px 28px;font-size:15px"><i class="ti ti-plus"></i> Add your first bottle</button>
  <?php endif; ?>
</div>
<?php endif; ?>

<div id="no-results" style="display:none;text-align:center;padding:4rem 2rem;color:var(--text-muted)">
  <p style="font-size:32px;margin-bottom:1rem">🔍</p>
  <p>No bottles match your search.</p>
  <button onclick="clearFilters()" style="margin-top:12px">Clear filters</button>
</div>

<p id="result-count" style="margin-top:1rem;font-size:12px;color:var(--text-faint);text-align:center"></p>

<!-- Add/Edit Modal -->
<?php if ($canEdit): ?>
<div id="bottle-modal" class="overlay" style="display:none" onclick="if(event.target===this)closeModal()">
  <div class="card" style="width:100%;max-width:520px;padding:1.75rem">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem">
      <h2 id="modal-title" style="font-family:var(--font-display);font-size:1.3rem">Add bottle</h2>
      <button class="ghost" onclick="closeModal()"><i class="ti ti-x"></i></button>
    </div>

    <!-- Barcode scan -->
    <div style="margin-bottom:1.5rem">
      <button type="button" onclick="openScanner('single')" id="scan-btn" style="width:100%;padding:13px;background:var(--accent-dim);border-style:dashed;border-color:var(--accent);color:var(--accent-light);font-size:14px;font-weight:600;justify-content:center">
        <i class="ti ti-barcode" style="font-size:18px"></i> Scan barcode to auto-fill
      </button>
      <p id="scan-status" style="margin-top:6px;font-size:12px;text-align:center;color:var(--text-faint)">Camera scan → auto-fills details from online database</p>
    </div>

    <form id="bottle-form">
      <input type="hidden" id="f-id">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div style="grid-column:1/-1">
          <label class="label">Name *</label>
          <input id="f-name" placeholder="e.g. Highland Park 12 Year" required>
        </div>
        <?php if (isset($visibleFields['brand'])): ?>
        <div>
          <label class="label"><?= htmlspecialchars($visibleFields['brand']) ?></label>
          <input id="f-brand" placeholder="e.g. Highland Park">
        </div>
        <?php endif; ?>
        <div>
          <label class="label">Category</label>
          <div class="cs-wrap" id="f-category-wrap" data-name="f-category" data-value="<?= htmlspecialchars($defaultCat) ?>" data-searchable="true">
            <?php foreach ($categories as $c): ?>
            <div class="cs-options" data-value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php if (isset($visibleFields['vintage'])): ?>
        <div>
          <label class="label"><?= htmlspecialchars($visibleFields['vintage']) ?></label>
          <input id="f-vintage" placeholder="e.g. 2019">
        </div>
        <?php endif; ?>
        <?php if (isset($visibleFields['abv'])): ?>
        <div>
          <label class="label"><?= htmlspecialchars($visibleFields['abv']) ?></label>
          <input id="f-abv" type="number" step="0.1" min="0" max="100" placeholder="46.0">
        </div>
        <?php endif; ?>
        <?php if (isset($visibleFields['country'])): ?>
        <div>
          <label class="label"><?= htmlspecialchars($visibleFields['country']) ?></label>
          <input id="f-country" placeholder="e.g. Scotland">
        </div>
        <?php endif; ?>
        <?php foreach ($custom as $cf): ?>
        <div <?= $cf['type']==='text' || $cf['name']==='notes' ? 'style="grid-column:1/-1"':'' ?>>
          <label class="label"><?= htmlspecialchars($cf['label']) ?></label>
          <?php if ($cf['type']==='boolean'): ?>
          <div class="cs-wrap custom-field-wrap" data-name="cf_<?= $cf['name'] ?>" data-value="" data-cf="<?= $cf['name'] ?>">
            <div class="cs-options" data-value="">—</div>
            <div class="cs-options" data-value="1">Yes</div>
            <div class="cs-options" data-value="0">No</div>
          </div>
          <?php elseif ($cf['type']==='date'): ?>
          <input type="date" class="custom-field" data-name="<?= $cf['name'] ?>">
          <?php else: ?>
          <input type="<?= $cf['type']==='number'?'number':'text' ?>" class="custom-field" data-name="<?= $cf['name'] ?>" placeholder="<?= htmlspecialchars($cf['label']) ?>">
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php if (isset($visibleFields['fill'])): ?>
      <div style="margin-top:12px">
        <label class="label">Fill Level</label>
        <div style="display:flex;gap:6px">
          <?php foreach ([100=>'Full',75=>'¾',50=>'½',25=>'¼',0=>'Empty'] as $v=>$l): ?>
          <button type="button" class="fill-btn" data-val="<?= $v ?>" onclick="setFill(<?= $v ?>)"
            style="flex:1;padding:7px 4px;font-size:12px;border-radius:var(--radius);background:transparent;border:1px solid var(--border-md);color:var(--text-muted)"><?= $l ?></button>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
      <?php if (isset($visibleFields['notes'])): ?>
      <div style="margin-top:12px">
        <label class="label"><?= htmlspecialchars($visibleFields['notes']) ?></label>
        <textarea id="f-notes" rows="2" placeholder="Cask finish, tasting notes…"></textarea>
      </div>
      <?php endif; ?>
      <?php if (isset($visibleFields['barcode'])): ?>
      <div id="barcode-field" style="margin-top:12px;display:none">
        <label class="label"><?= htmlspecialchars($visibleFields['barcode']) ?></label>
        <input id="f-barcode" placeholder="e.g. 5010509258408">
      </div>
      <?php endif; ?>
      <div style="display:flex;gap:8px;margin-top:1.5rem;justify-content:flex-end">
        <button type="button" onclick="closeModal()">Cancel</button>
        <button type="submit" class="primary" id="save-btn">Add bottle</button>
      </div>
    </form>
  </div>
</div>

<!-- Delete confirm -->
<div id="delete-modal" class="overlay" style="display:none" onclick="if(event.target===this)closeDeleteModal()">
  <div class="card" style="width:100%;max-width:400px;padding:1.75rem;margin-top:8rem">
    <h2 style="margin-bottom:.5rem">Remove bottle?</h2>
    <p style="color:var(--text-muted);margin-bottom:1.5rem;font-size:14px"><strong id="delete-name" style="color:var(--text)"></strong> will be permanently removed.</p>
    <div style="display:flex;gap:8px;justify-content:flex-end">
      <button onclick="closeDeleteModal()">Cancel</button>
      <button class="danger" onclick="confirmDelete()">Remove bottle</button>
    </div>
  </div>
</div>

<!-- Camera scanner -->
<div id="scanner-overlay" class="scanner-overlay" style="display:none">
  <video id="scan-video" class="scanner-video" autoplay playsinline muted></video>
  <div id="scan-frame" class="scanner-frame"><div class="scan-line"></div></div>
  <div style="position:absolute;bottom:0;left:0;right:0;background:linear-gradient(transparent,rgba(0,0,0,.92));padding:2rem 1.5rem;display:flex;flex-direction:column;gap:14px">
    <p id="scan-msg" style="color:var(--text-muted);text-align:center;font-size:13px">Point camera at the barcode on the bottle</p>
    <!-- Bulk mode queue -->
    <div id="scan-queue" style="display:none;max-height:180px;overflow-y:auto;background:rgba(255,255,255,.05);border-radius:8px;padding:10px">
      <p style="font-size:12px;color:var(--text-muted);margin-bottom:8px">Scanned bottles:</p>
      <div id="queue-list" style="display:flex;flex-direction:column;gap:6px"></div>
    </div>
    <div style="display:flex;gap:8px">
      <input id="manual-code" placeholder="Or type barcode…" style="flex:1;background:rgba(255,255,255,.07);border-color:rgba(255,255,255,.15);color:#fff">
      <button class="primary" onclick="lookupManual()">Look up</button>
    </div>
    <div style="display:flex;gap:8px">
      <button onclick="closeScanner()" style="flex:1;background:rgba(255,255,255,.07);border-color:rgba(255,255,255,.15);color:#ddd;justify-content:center">✕ Cancel</button>
      <button id="save-queue-btn" onclick="saveBulkQueue()" style="display:none;flex:1;" class="primary">Save all (<span id="queue-count">0</span>)</button>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
// ── Data ──────────────────────────────────────────────────────────────────────
let allBottles = <?= json_encode($bottles) ?>;
let currentCat = 'All';
let deleteId   = null;
let editId     = null;
let currentFill = <?= $defaultFill ?>;
let scanMode   = 'single'; // single|quick|review
let scanQueue  = [];
let zxingReader = null;
let scanStream  = null;
let lastCode    = '';
let lastCodeTime = 0;

// ── Filtering ─────────────────────────────────────────────────────────────────
function filterBottles() {
  const q    = document.getElementById('search')?.value.toLowerCase() ?? '';
  const sort = document.querySelector('#sort-wrap input[type=hidden]')?.value ?? 'name';
  const cards = [...document.querySelectorAll('.bottle-card')];
  let visible = 0;

  cards.forEach(card => {
    const cat   = card.dataset.cat;
    const match = (currentCat === 'All' || cat === currentCat) &&
      (!q || card.dataset.name.includes(q) || card.dataset.brand.includes(q) || cat.toLowerCase().includes(q));
    card.style.display = match ? '' : 'none';
    if (match) visible++;
  });

  // Sort
  const grid = document.getElementById('bottle-grid');
  const sorted = cards.filter(c => c.style.display !== 'none').sort((a,b) => {
    if (sort==='name')      return a.dataset.name.localeCompare(b.dataset.name);
    if (sort==='category')  return a.dataset.cat.localeCompare(b.dataset.cat);
    if (sort==='fill-desc') return Number(b.dataset.fill) - Number(a.dataset.fill);
    if (sort==='fill-asc')  return Number(a.dataset.fill) - Number(b.dataset.fill);
    if (sort==='newest')    return b.dataset.created.localeCompare(a.dataset.created);
    return 0;
  });
  sorted.forEach(c => grid.appendChild(c));

  document.getElementById('no-results').style.display = visible===0 && allBottles.length>0 ? '' : 'none';
  const rc = document.getElementById('result-count');
  if (rc) rc.textContent = visible < allBottles.length ? `${visible} of ${allBottles.length} bottles (filtered)` : '';
}

function setCat(btn) {
  currentCat = btn.dataset.cat;
  document.querySelectorAll('.cat-chip').forEach(b => {
    const active = b===btn;
    b.style.background = active ? 'var(--accent-dim2)' : 'transparent';
    b.style.borderColor = active ? 'var(--accent)' : 'var(--border-md)';
    b.style.color = active ? 'var(--accent-light)' : 'var(--text-muted)';
  });
  filterBottles();
}

function clearFilters() {
  document.getElementById('search').value = '';
  setCat(document.querySelector('.cat-chip[data-cat="All"]'));
}

// ── Modal ─────────────────────────────────────────────────────────────────────
function openAddModal() {
  editId = null;
  document.getElementById('modal-title').textContent = 'Add bottle';
  document.getElementById('save-btn').textContent = 'Add bottle';
  document.getElementById('bottle-form').reset();
  setFill(<?= $defaultFill ?>);
  document.getElementById('f-id').value = '';
  document.getElementById('barcode-field')?.style && (document.getElementById('barcode-field').style.display = 'none');
  document.getElementById('scan-status').textContent = 'Camera scan → auto-fills details from online database';
  document.getElementById('bottle-modal').style.display = 'flex';
  initCustomSelects();
}

function editBottle(b) {
  editId = b.id;
  document.getElementById('modal-title').textContent = 'Edit bottle';
  document.getElementById('save-btn').textContent = 'Save changes';
  document.getElementById('f-id').value = b.id;
  document.getElementById('f-name').value     = b.name     || '';
  document.getElementById('f-brand') && (document.getElementById('f-brand').value = b.brand || '');
  document.getElementById('f-category-wrap')?.csSelect?.(b.category || '');
  document.getElementById('f-vintage') && (document.getElementById('f-vintage').value = b.vintage || '');
  document.getElementById('f-abv') && (document.getElementById('f-abv').value = b.abv || '');
  document.getElementById('f-country') && (document.getElementById('f-country').value = b.country || '');
  document.getElementById('f-notes') && (document.getElementById('f-notes').value = b.notes || '');
  document.getElementById('f-barcode') && (document.getElementById('f-barcode').value = b.barcode || '');
  if (b.barcode) document.getElementById('barcode-field')?.style && (document.getElementById('barcode-field').style.display = '');
  const cd = typeof b.custom_data === 'string' ? JSON.parse(b.custom_data||'{}') : (b.custom_data||{});
  document.querySelectorAll('.custom-field').forEach(el => { el.value = cd[el.dataset.name] ?? ''; });
  document.querySelectorAll('.custom-field-wrap').forEach(el => { el.csSelect?.(cd[el.dataset.cf] ?? ''); });
  setFill(b.fill ?? 100);
  document.getElementById('scan-btn').style.display = 'none';
  document.getElementById('scan-status').style.display = 'none';
  document.getElementById('bottle-modal').style.display = 'flex';
  initCustomSelects();
}

function closeModal() { document.getElementById('bottle-modal').style.display = 'none'; }

function setFill(v) {
  currentFill = v;
  document.querySelectorAll('.fill-btn').forEach(b => {
    const active = Number(b.dataset.val) === v;
    const color = v>=75 ? 'var(--success)' : v>=50 ? 'var(--warning)' : v>0 ? 'var(--danger)' : 'var(--text-faint)';
    b.style.background   = active ? 'var(--surface-3)' : 'transparent';
    b.style.borderColor  = active ? color : 'var(--border-md)';
    b.style.color        = active ? color : 'var(--text-muted)';
    b.style.fontWeight   = active ? '600' : '400';
  });
}

document.getElementById('bottle-form')?.addEventListener('submit', async e => {
  e.preventDefault();
  const btn = document.getElementById('save-btn');
  btn.disabled = true; btn.textContent = 'Saving…';
  const cd = {};
  document.querySelectorAll('.custom-field').forEach(el => { cd[el.dataset.name] = el.value; });
  document.querySelectorAll('.custom-field-wrap').forEach(el => { cd[el.dataset.cf] = el.querySelector('input[type=hidden]')?.value || ''; });
  const data = {
    name:     document.getElementById('f-name').value,
    brand:    document.getElementById('f-brand')?.value    || '',
    category: document.querySelector('#f-category-wrap input[type=hidden]')?.value || '',
    vintage:  document.getElementById('f-vintage')?.value  || '',
    abv:      document.getElementById('f-abv')?.value      || '',
    country:  document.getElementById('f-country')?.value  || '',
    notes:    document.getElementById('f-notes')?.value    || '',
    barcode:  document.getElementById('f-barcode')?.value  || '',
    fill:     currentFill,
    custom_data: cd,
  };
  try {
    if (editId) {
      await api('PUT', `/api/bottles/${editId}`, data);
      showToast('Bottle updated');
    } else {
      await api('POST', '/api/bottles', data);
      showToast(`${data.name} added`);
    }
    closeModal();
    setTimeout(() => location.reload(), 500);
  } catch(err) {
    showToast(err.message, 'err');
    btn.disabled = false;
    btn.textContent = editId ? 'Save changes' : 'Add bottle';
  }
});

// ── Delete ────────────────────────────────────────────────────────────────────
function deleteBottle(id, name) {
  deleteId = id;
  document.getElementById('delete-name').textContent = name;
  document.getElementById('delete-modal').style.display = 'flex';
}
function closeDeleteModal() { document.getElementById('delete-modal').style.display = 'none'; }
async function confirmDelete() {
  try {
    await api('DELETE', `/api/bottles/${deleteId}`);
    showToast('Bottle removed');
    closeDeleteModal();
    setTimeout(() => location.reload(), 500);
  } catch(err) { showToast(err.message, 'err'); }
}

// ── Barcode Scanner ───────────────────────────────────────────────────────────
function openScanner(mode='single') {
  scanMode  = mode;
  scanQueue = [];
  lastCode  = '';
  document.getElementById('scanner-overlay').style.display = 'flex';
  document.getElementById('scan-queue').style.display = mode==='review' ? '' : 'none';
  document.getElementById('save-queue-btn').style.display = mode==='review' ? '' : 'none';
  document.getElementById('scan-msg').textContent = mode==='quick' ? '🔴 Quick scan — auto-saving each bottle' :
    mode==='review' ? 'Scan bottles — they queue up for review' : 'Point camera at the barcode on the bottle';
  startCamera();
}

async function startCamera() {
  let stream;
  try {
    stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 } }, audio: false,
    });
  } catch(err) {
    const msgs = { NotAllowedError:'Camera permission denied.', NotFoundError:'No camera found.', NotReadableError:'Camera in use.' };
    document.getElementById('scan-msg').textContent = (msgs[err.name] || err.message) + ' Use manual entry below.';
    document.getElementById('scan-msg').style.color = 'var(--danger)';
    document.getElementById('manual-code').focus();
    return;
  }
  scanStream = stream;
  const video = document.getElementById('scan-video');
  video.srcObject = stream;
  await video.play();

  if (!window.ZXing) {
    await new Promise((res,rej) => {
      const s = document.createElement('script');
      s.src = 'https://cdnjs.cloudflare.com/ajax/libs/zxing-js/0.19.1/zxing.min.js';
      s.onload = res; s.onerror = rej;
      document.head.appendChild(s);
    });
  }

  const hints = new Map();
  hints.set(ZXing.DecodeHintType.POSSIBLE_FORMATS, [
    ZXing.BarcodeFormat.EAN_13, ZXing.BarcodeFormat.EAN_8,
    ZXing.BarcodeFormat.UPC_A,  ZXing.BarcodeFormat.UPC_E,
    ZXing.BarcodeFormat.CODE_128, ZXing.BarcodeFormat.CODE_39,
  ]);
  hints.set(ZXing.DecodeHintType.TRY_HARDER, true);
  zxingReader = new ZXing.BrowserMultiFormatReader(hints, 400);
  zxingReader.decodeFromStream(stream, video, (result) => {
    if (result) onBarcodeDetected(result.getText());
  });
}

async function onBarcodeDetected(code) {
  const now = Date.now();
  if (code === lastCode && now - lastCodeTime < 3000) return; // debounce
  lastCode = code; lastCodeTime = now;

  if (scanMode === 'single') {
    closeScanner();
    await fillFormFromBarcode(code);
  } else if (scanMode === 'quick') {
    await quickSaveBarcode(code);
  } else if (scanMode === 'review') {
    await addToQueue(code);
  }
}

async function fillFormFromBarcode(code) {
  document.getElementById('scan-btn').innerHTML = '<span class="spin">↻</span> Looking up…';
  document.getElementById('scan-btn').disabled = true;
  try {
    const r = await api('GET', `/api/barcode/${code}`);
    document.getElementById('f-name').value     = r.name    || '';
    document.getElementById('f-brand') && (document.getElementById('f-brand').value = r.brand || '');
    document.getElementById('f-category-wrap')?.csSelect?.(r.category || '');
    document.getElementById('f-vintage') && (document.getElementById('f-vintage').value = r.vintage || '');
    document.getElementById('f-abv') && (document.getElementById('f-abv').value = r.abv || '');
    document.getElementById('f-country') && (document.getElementById('f-country').value = r.country || '');
    document.getElementById('f-notes') && (document.getElementById('f-notes').value = r.notes || '');
    if (r.barcode) {
      document.getElementById('f-barcode') && (document.getElementById('f-barcode').value = r.barcode);
      document.getElementById('barcode-field') && (document.getElementById('barcode-field').style.display = '');
    }
    document.getElementById('scan-status').textContent = `✓ Found: ${r.name}`;
    document.getElementById('scan-status').style.color = 'var(--success)';
  } catch {
    document.getElementById('f-barcode') && (document.getElementById('f-barcode').value = code);
    document.getElementById('barcode-field') && (document.getElementById('barcode-field').style.display = '');
    document.getElementById('scan-status').textContent = 'Not found in database — fill in manually';
    document.getElementById('scan-status').style.color = 'var(--warning)';
  }
  document.getElementById('scan-btn').innerHTML = '<i class="ti ti-barcode" style="font-size:18px"></i> Scan barcode to auto-fill';
  document.getElementById('scan-btn').disabled = false;
}

async function quickSaveBarcode(code) {
  document.getElementById('scan-msg').textContent = `⏳ Looking up ${code}…`;
  try {
    const r = await api('GET', `/api/barcode/${code}`).catch(() => ({ name:'Unknown', barcode:code, category:'Other', fill:100 }));
    await api('POST', '/api/bottles', { ...r, fill: <?= $defaultFill ?> });
    document.getElementById('scan-msg').textContent = `✓ Saved: ${r.name}`;
    setTimeout(() => { document.getElementById('scan-msg').textContent = '🔴 Quick scan — auto-saving each bottle'; }, 2000);
  } catch(err) {
    document.getElementById('scan-msg').textContent = `⚠ Error: ${err.message}`;
  }
}

async function addToQueue(code) {
  if (scanQueue.find(q => q.barcode===code)) return; // no dupes
  document.getElementById('scan-msg').textContent = `⏳ Looking up ${code}…`;
  const r = await api('GET', `/api/barcode/${code}`).catch(() => ({ name:'Unknown', barcode:code, category:'Other' }));
  scanQueue.push({ ...r, fill: <?= $defaultFill ?> });
  renderQueue();
  document.getElementById('scan-msg').textContent = `✓ Added to queue: ${r.name}`;
}

function renderQueue() {
  const list = document.getElementById('queue-list');
  list.innerHTML = '';
  document.getElementById('queue-count').textContent = scanQueue.length;
  document.getElementById('save-queue-btn').style.display = scanQueue.length ? '' : 'none';
  scanQueue.forEach((b, i) => {
    const div = document.createElement('div');
    div.style.cssText = 'display:flex;align-items:center;gap:8px;background:rgba(255,255,255,.05);padding:6px 10px;border-radius:6px';
    div.innerHTML = `<span style="flex:1;font-size:13px;color:#fff;overflow:hidden;white-space:nowrap;text-overflow:ellipsis">${b.name}</span>
      <span style="font-size:11px;color:rgba(255,255,255,.4)">${b.category}</span>
      <button onclick="removeFromQueue(${i})" style="background:none;border:none;color:var(--danger);padding:2px 6px;font-size:16px;cursor:pointer;min-width:auto">✕</button>`;
    list.appendChild(div);
  });
}

function removeFromQueue(i) { scanQueue.splice(i,1); renderQueue(); }

async function saveBulkQueue() {
  if (!scanQueue.length) return;
  const btn = document.getElementById('save-queue-btn');
  btn.disabled = true; btn.textContent = 'Saving…';
  try {
    const r = await api('POST', '/api/bottles/bulk', { bottles: scanQueue });
    showToast(`${r.created} bottle${r.created!==1?'s':''} added`);
    closeScanner();
    setTimeout(() => location.reload(), 600);
  } catch(err) {
    showToast(err.message, 'err');
    btn.disabled = false;
    btn.textContent = `Save all (${scanQueue.length})`;
  }
}

function closeScanner() {
  document.getElementById('scanner-overlay').style.display = 'none';
  zxingReader?.reset();
  scanStream?.getTracks().forEach(t => t.stop());
  zxingReader = null; scanStream = null;
}

async function lookupManual() {
  const code = document.getElementById('manual-code').value.trim();
  if (!code) return;
  onBarcodeDetected(code);
  document.getElementById('manual-code').value = '';
}

document.getElementById('manual-code')?.addEventListener('keydown', e => { if(e.key==='Enter') lookupManual(); });

// ── Export ────────────────────────────────────────────────────────────────────
async function exportData() {
  const btn = document.getElementById('export-btn');
  btn.disabled = true; btn.innerHTML = '<span class="spin">↻</span> Exporting…';
  // Load SheetJS
  if (!window.XLSX) {
    await new Promise((res,rej) => {
      const s = document.createElement('script');
      s.src = 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js';
      s.onload = res; s.onerror = rej;
      document.head.appendChild(s);
    });
  }
  const fillLabel = v => v===100?'Full':v===0?'Empty':`${v}%`;
  const rows = [['Name','Brand','Category','Vintage','ABV (%)','Country','Fill Level','Fill %','Barcode','Notes','Added'],
    ...allBottles.sort((a,b)=>(a.name||'').localeCompare(b.name||'')).map(b=>[
      b.name||'',b.brand||'',b.category||'',b.vintage||'',
      b.abv!==null&&b.abv!==''?Number(b.abv):'',b.country||'',
      fillLabel(b.fill??100),b.fill??100,b.barcode||'',b.notes||'',(b.created_at||'').slice(0,10)
    ])];
  const wb = XLSX.utils.book_new();
  const ws = XLSX.utils.aoa_to_sheet(rows);
  ws['!cols'] = [{wch:32},{wch:24},{wch:14},{wch:10},{wch:9},{wch:16},{wch:10},{wch:8},{wch:16},{wch:40},{wch:14}];
  XLSX.utils.book_append_sheet(wb, ws, 'Inventory');
  XLSX.writeFile(wb, `bar-inventory-${new Date().toISOString().slice(0,10)}.xlsx`);
  showToast(`Exported ${allBottles.length} bottles`);
  btn.disabled = false; btn.innerHTML = '<i class="ti ti-file-spreadsheet"></i> Export';
}
</script>
