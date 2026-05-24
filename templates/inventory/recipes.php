<?php
$categories = array_unique(array_column($recipes, 'category'));
sort($categories);
?>

<div class="page-header">
  <h1 class="page-title">🍹 Recipes</h1>
  <?php if ($canEdit): ?>
  <button class="primary" onclick="openRecipeModal()"><i class="ti ti-plus"></i> Add Recipe</button>
  <?php endif; ?>
</div>

<?php if (empty($recipes)): ?>
<div style="text-align:center;padding:6rem 2rem">
  <div style="font-size:56px;margin-bottom:1rem">🍹</div>
  <h2 style="font-family:var(--font-display);font-size:1.5rem;margin-bottom:.5rem">No recipes yet</h2>
  <p style="color:var(--text-muted);margin-bottom:2rem">Add your favourite tiki drinks and cocktail recipes.</p>
  <?php if ($canEdit): ?>
  <button class="primary" onclick="openRecipeModal()" style="padding:12px 28px;font-size:15px"><i class="ti ti-plus"></i> Add your first recipe</button>
  <?php endif; ?>
</div>
<?php else: ?>

<!-- Category filter -->
<div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:1.5rem">
  <button class="cat-chip active" data-cat="All" onclick="filterCat(this)"
    style="padding:5px 12px;font-size:13px;border-radius:99px;background:var(--accent-dim2);border:1px solid var(--accent);color:var(--accent-light)">
    All (<?= count($recipes) ?>)
  </button>
  <?php foreach ($categories as $cat): ?>
  <?php $n = count(array_filter($recipes, fn($r) => $r['category']===$cat)); ?>
  <button class="cat-chip" data-cat="<?= htmlspecialchars($cat) ?>" onclick="filterCat(this)"
    style="padding:5px 12px;font-size:13px;border-radius:99px;background:transparent;border:1px solid var(--border-md);color:var(--text-muted)">
    <?= htmlspecialchars($cat) ?> (<?= $n ?>)
  </button>
  <?php endforeach; ?>
</div>

<div class="grid-auto" id="recipe-grid">
<?php foreach ($recipes as $r):
  // Check which ingredients are in stock
  $missing = 0;
  $total_ing = count($r['ingredients'] ?? []);
  foreach (($r['ingredients'] ?? []) as $ing) {
    if ($ing['bottle_id'] && ($ing['bottle_fill'] ?? 100) == 0) $missing++;
  }
?>
<div class="card recipe-card" data-cat="<?= htmlspecialchars($r['category']) ?>"
     style="padding:1.25rem;display:flex;flex-direction:column;gap:10px">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px">
    <div style="flex:1;min-width:0">
      <p style="font-weight:600;font-size:15px"><?= htmlspecialchars($r['name']) ?></p>
      <p style="font-size:12px;color:var(--text-muted);margin-top:2px"><?= htmlspecialchars($r['category']) ?><?= $r['glass'] ? ' · '.$r['glass'] : '' ?></p>
    </div>
    <?php if ($canEdit): ?>
    <div style="display:flex;gap:2px;flex-shrink:0">
      <button class="ghost" onclick='editRecipe(<?= htmlspecialchars(json_encode($r),ENT_QUOTES) ?>)' title="Edit"><i class="ti ti-edit" style="font-size:15px"></i></button>
      <button class="ghost" onclick="deleteRecipe(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['name'])) ?>')" style="color:var(--danger)" title="Delete"><i class="ti ti-trash" style="font-size:15px"></i></button>
    </div>
    <?php endif; ?>
  </div>
  <?php if ($r['description']): ?>
  <p style="font-size:13px;color:var(--text-muted);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden"><?= htmlspecialchars($r['description']) ?></p>
  <?php endif; ?>
  <?php if (!empty($r['ingredients'])): ?>
  <div style="display:flex;flex-direction:column;gap:4px">
    <?php foreach ($r['ingredients'] as $ing):
      $low = $ing['bottle_id'] && ($ing['bottle_fill'] ?? 100) == 0;
      $linked = !empty($ing['bottle_name']);
    ?>
    <div style="display:flex;align-items:center;gap:6px;font-size:13px">
      <span style="color:<?= $low?'var(--danger)':($linked?'var(--success)':'var(--text-muted)') ?>;font-size:11px">
        <?= $low ? '✕' : ($linked ? '✓' : '·') ?>
      </span>
      <span style="color:var(--text)"><?= htmlspecialchars(trim(($ing['amount']??'').' '.($ing['unit']??''))) ?></span>
      <span style="color:<?= $low?'var(--danger)':'var(--text-muted)' ?>"><?= htmlspecialchars($ing['name']) ?><?= $low ? ' (empty)' : '' ?></span>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?php if ($missing > 0): ?>
  <p style="font-size:12px;color:var(--danger)"><i class="ti ti-alert-triangle" style="font-size:12px"></i> <?= $missing ?> ingredient<?= $missing!==1?'s':'' ?> empty</p>
  <?php elseif ($total_ing > 0): ?>
  <p style="font-size:12px;color:var(--success)"><i class="ti ti-circle-check" style="font-size:12px"></i> All in stock</p>
  <?php endif; ?>
  <a href="/recipes/<?= $r['id'] ?>" style="font-size:13px;color:var(--accent-light)">View recipe →</a>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($canEdit): ?>
<!-- Recipe Modal -->
<div id="recipe-modal" class="overlay" style="display:none" onclick="if(event.target===this)closeRecipeModal()">
  <div class="card" style="width:100%;max-width:600px;padding:1.75rem">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem">
      <h2 id="recipe-modal-title" style="font-family:var(--font-display);font-size:1.3rem">Add Recipe</h2>
      <button class="ghost" onclick="closeRecipeModal()"><i class="ti ti-x"></i></button>
    </div>
    <form id="recipe-form">
      <input type="hidden" id="r-id">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div style="grid-column:1/-1">
          <label class="label">Recipe Name *</label>
          <input id="r-name" placeholder="e.g. Mai Tai" required>
        </div>
        <div>
          <label class="label">Category</label>
          <select id="r-category">
            <?php foreach (['Tiki','Classic','Modern','Shot','Non-alcoholic','Other'] as $c): ?>
            <option value="<?= $c ?>"><?= $c ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="label">Glass</label>
          <input id="r-glass" placeholder="e.g. Tiki mug, Rocks glass">
        </div>
        <div style="grid-column:1/-1">
          <label class="label">Description</label>
          <input id="r-description" placeholder="Short description">
        </div>
        <div style="grid-column:1/-1">
          <label class="label">Garnish</label>
          <input id="r-garnish" placeholder="e.g. Mint sprig, Lime wheel">
        </div>
      </div>

      <!-- Ingredients -->
      <div style="margin-top:1rem">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem">
          <label class="label" style="margin-bottom:0">Ingredients</label>
          <button type="button" onclick="addIngredientRow()" class="ghost" style="font-size:12px"><i class="ti ti-plus" style="font-size:13px"></i> Add</button>
        </div>
        <div id="ingredient-list" style="display:flex;flex-direction:column;gap:8px"></div>
      </div>

      <!-- Instructions -->
      <div style="margin-top:12px">
        <label class="label">Instructions</label>
        <textarea id="r-instructions" rows="4" placeholder="1. Combine rum and lime juice over ice..."></textarea>
      </div>

      <div style="display:flex;gap:8px;margin-top:1.5rem;justify-content:flex-end">
        <button type="button" onclick="closeRecipeModal()">Cancel</button>
        <button type="submit" class="primary" id="recipe-save-btn">Add Recipe</button>
      </div>
    </form>
  </div>
</div>

<!-- Delete confirm -->
<div id="delete-recipe-modal" class="overlay" style="display:none" onclick="if(event.target===this)this.style.display='none'">
  <div class="card" style="width:100%;max-width:400px;padding:1.75rem;margin-top:8rem">
    <h2 style="margin-bottom:.5rem">Delete recipe?</h2>
    <p style="color:var(--text-muted);margin-bottom:1.5rem;font-size:14px"><strong id="delete-recipe-name" style="color:var(--text)"></strong> will be permanently deleted.</p>
    <div style="display:flex;gap:8px;justify-content:flex-end">
      <button onclick="document.getElementById('delete-recipe-modal').style.display='none'">Cancel</button>
      <button class="danger" onclick="confirmDeleteRecipe()">Delete</button>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
const allBottles = <?= json_encode(array_map(fn($b)=>['id'=>$b['id'],'name'=>$b['name']], $bottles)) ?>;
let deleteRecipeId = null;
let editRecipeId   = null;

function filterCat(btn) {
  const cat = btn.dataset.cat;
  document.querySelectorAll('.cat-chip').forEach(b => {
    const a = b===btn;
    b.style.background  = a ? 'var(--accent-dim2)' : 'transparent';
    b.style.borderColor = a ? 'var(--accent)' : 'var(--border-md)';
    b.style.color       = a ? 'var(--accent-light)' : 'var(--text-muted)';
  });
  document.querySelectorAll('.recipe-card').forEach(c => {
    c.style.display = cat==='All' || c.dataset.cat===cat ? '' : 'none';
  });
}

function openRecipeModal() {
  editRecipeId = null;
  document.getElementById('recipe-modal-title').textContent = 'Add Recipe';
  document.getElementById('recipe-save-btn').textContent    = 'Add Recipe';
  document.getElementById('recipe-form').reset();
  document.getElementById('r-id').value = '';
  document.getElementById('ingredient-list').innerHTML = '';
  addIngredientRow();
  document.getElementById('recipe-modal').style.display = 'flex';
}

function editRecipe(r) {
  editRecipeId = r.id;
  document.getElementById('recipe-modal-title').textContent = 'Edit Recipe';
  document.getElementById('recipe-save-btn').textContent    = 'Save Changes';
  document.getElementById('r-id').value          = r.id;
  document.getElementById('r-name').value        = r.name         || '';
  document.getElementById('r-category').value    = r.category     || 'Tiki';
  document.getElementById('r-glass').value       = r.glass        || '';
  document.getElementById('r-description').value = r.description  || '';
  document.getElementById('r-garnish').value     = r.garnish      || '';
  document.getElementById('r-instructions').value= r.instructions || '';
  document.getElementById('ingredient-list').innerHTML = '';
  (r.ingredients||[]).forEach(ing => addIngredientRow(ing));
  if (!r.ingredients?.length) addIngredientRow();
  document.getElementById('recipe-modal').style.display = 'flex';
}

function closeRecipeModal() { document.getElementById('recipe-modal').style.display = 'none'; }

function addIngredientRow(ing={}) {
  const div = document.createElement('div');
  div.style.cssText = 'display:grid;grid-template-columns:80px 80px 1fr auto;gap:6px;align-items:center';
  const bottleOpts = allBottles.map(b =>
    `<option value="${b.id}" ${ing.bottle_id==b.id?'selected':''}>${b.name}</option>`
  ).join('');
  div.innerHTML = `
    <input class="ing-amount"   value="${ing.amount||''}"  placeholder="Amount" style="font-size:13px">
    <input class="ing-unit"     value="${ing.unit||''}"    placeholder="Unit"   style="font-size:13px">
    <input class="ing-name"     value="${ing.name||''}"    placeholder="Ingredient name" style="font-size:13px">
    <select class="ing-bottle" style="font-size:12px;min-width:0">
      <option value="">— link bottle</option>${bottleOpts}
    </select>
    <button type="button" onclick="this.closest('div').remove()" style="padding:6px 8px;color:var(--danger);background:transparent;border-color:transparent;font-size:16px;min-width:auto">✕</button>`;
  // Fix grid to include remove button
  div.style.gridTemplateColumns = '70px 70px 1fr 120px 32px';
  document.getElementById('ingredient-list').appendChild(div);
}

document.getElementById('recipe-form')?.addEventListener('submit', async e => {
  e.preventDefault();
  const btn = document.getElementById('recipe-save-btn');
  btn.disabled = true; btn.textContent = 'Saving…';
  const ingredients = [...document.querySelectorAll('#ingredient-list > div')].map(row => ({
    amount:    row.querySelector('.ing-amount').value,
    unit:      row.querySelector('.ing-unit').value,
    name:      row.querySelector('.ing-name').value,
    bottle_id: row.querySelector('.ing-bottle').value || null,
  })).filter(i => i.name.trim());
  const data = {
    name:         document.getElementById('r-name').value,
    category:     document.getElementById('r-category').value,
    glass:        document.getElementById('r-glass').value,
    description:  document.getElementById('r-description').value,
    garnish:      document.getElementById('r-garnish').value,
    instructions: document.getElementById('r-instructions').value,
    ingredients,
  };
  try {
    if (editRecipeId) {
      await api('PUT', `/api/recipes/${editRecipeId}`, data);
      showToast('Recipe updated');
    } else {
      await api('POST', '/api/recipes', data);
      showToast(`${data.name} added`);
    }
    closeRecipeModal();
    setTimeout(() => location.reload(), 500);
  } catch(err) {
    showToast(err.message, 'err');
    btn.disabled = false;
    btn.textContent = editRecipeId ? 'Save Changes' : 'Add Recipe';
  }
});

function deleteRecipe(id, name) {
  deleteRecipeId = id;
  document.getElementById('delete-recipe-name').textContent = name;
  document.getElementById('delete-recipe-modal').style.display = 'flex';
}
async function confirmDeleteRecipe() {
  try {
    await api('DELETE', `/api/recipes/${deleteRecipeId}`);
    showToast('Recipe deleted');
    document.getElementById('delete-recipe-modal').style.display = 'none';
    setTimeout(() => location.reload(), 500);
  } catch(err) { showToast(err.message, 'err'); }
}
</script>
