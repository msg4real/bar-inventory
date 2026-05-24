<?php
$allIn = true;
foreach ($recipe['ingredients'] as $ing) {
    if ($ing['bottle_id'] && ($ing['bottle_fill']??100) == 0) { $allIn = false; break; }
}
?>
<div style="max-width:700px">
  <div style="margin-bottom:1.5rem">
    <a href="/recipes" style="font-size:13px;color:var(--text-muted)"><i class="ti ti-arrow-left" style="font-size:13px"></i> Back to Recipes</a>
  </div>

  <div class="page-header">
    <div>
      <h1 class="page-title"><?= htmlspecialchars($recipe['name']) ?></h1>
      <div style="display:flex;gap:8px;margin-top:.5rem;flex-wrap:wrap">
        <span class="badge badge-cat"><?= htmlspecialchars($recipe['category']) ?></span>
        <?php if ($recipe['glass']): ?><span class="badge badge-country"><i class="ti ti-glass" style="font-size:10px"></i><?= htmlspecialchars($recipe['glass']) ?></span><?php endif; ?>
        <?php if ($allIn && count($recipe['ingredients'])>0): ?>
        <span class="badge" style="background:rgba(76,175,130,.12);color:var(--success);border:1px solid rgba(76,175,130,.2)"><i class="ti ti-circle-check" style="font-size:10px"></i> All in stock</span>
        <?php endif; ?>
      </div>
    </div>
    <?php if ($canEdit): ?>
    <a href="/recipes" class="button ghost" style="font-size:13px;padding:6px 12px;display:inline-flex;align-items:center;gap:6px;border:1px solid var(--border-md);border-radius:var(--radius);color:var(--text-muted);text-decoration:none"><i class="ti ti-edit"></i> Edit</a>
    <?php endif; ?>
  </div>

  <?php if ($recipe['description']): ?>
  <p style="color:var(--text-muted);margin-bottom:1.5rem"><?= htmlspecialchars($recipe['description']) ?></p>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">

    <!-- Ingredients -->
    <div class="card" style="padding:1.25rem">
      <h2 style="font-family:var(--font-display);margin-bottom:1rem;font-size:1.1rem">Ingredients</h2>
      <div style="display:flex;flex-direction:column;gap:10px">
        <?php foreach ($recipe['ingredients'] as $ing):
          $low    = $ing['bottle_id'] && ($ing['bottle_fill']??100)==0;
          $linked = !empty($ing['bottle_name']);
        ?>
        <div style="display:flex;align-items:center;gap:10px;padding:8px 10px;background:var(--surface-2);border-radius:var(--radius)">
          <span style="color:<?= $low?'var(--danger)':($linked?'var(--success)':'var(--text-faint)') ?>;font-size:14px">
            <?= $low ? '✕' : ($linked ? '✓' : '·') ?>
          </span>
          <div style="flex:1">
            <span style="font-weight:500"><?= htmlspecialchars(trim(($ing['amount']??'').' '.($ing['unit']??''))) ?></span>
            <span style="color:var(--text-muted)"> <?= htmlspecialchars($ing['name']) ?></span>
          </div>
          <?php if ($linked): ?>
          <span style="font-size:11px;color:<?= $low?'var(--danger)':'var(--text-faint)' ?>"><?= $low?'empty':$ing['bottle_fill'].'%' ?></span>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php if (empty($recipe['ingredients'])): ?>
        <p style="color:var(--text-faint);font-size:13px">No ingredients listed.</p>
        <?php endif; ?>
      </div>
      <?php if ($recipe['garnish']): ?>
      <p style="margin-top:1rem;font-size:13px;color:var(--text-muted)"><strong style="color:var(--text)">Garnish:</strong> <?= htmlspecialchars($recipe['garnish']) ?></p>
      <?php endif; ?>
    </div>

    <!-- Instructions -->
    <div class="card" style="padding:1.25rem">
      <h2 style="font-family:var(--font-display);margin-bottom:1rem;font-size:1.1rem">Instructions</h2>
      <?php if ($recipe['instructions']): ?>
      <div style="font-size:14px;color:var(--text-muted);line-height:1.8;white-space:pre-wrap"><?= htmlspecialchars($recipe['instructions']) ?></div>
      <?php else: ?>
      <p style="color:var(--text-faint);font-size:13px">No instructions added.</p>
      <?php endif; ?>
    </div>

  </div>
</div>
