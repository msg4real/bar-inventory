// ── Toast ──────────────────────────────────────────────────────────────────
function showToast(msg, type='ok') {
  const t = document.createElement('div');
  t.className = 'toast';
  t.style.borderColor = type==='err' ? 'rgba(224,82,82,.3)' : 'var(--border-md)';
  t.innerHTML = `<i class="ti ${type==='err'?'ti-alert-circle':'ti-circle-check'}" style="color:${type==='err'?'var(--danger)':'var(--success)'};font-size:16px"></i><span>${msg}</span>`;
  document.getElementById('toast-container').appendChild(t);
  setTimeout(() => t.remove(), 3500);
}

// ── API helper ─────────────────────────────────────────────────────────────
async function api(method, url, data) {
  const opts = { method, headers: {'Content-Type':'application/json'} };
  if (data) opts.body = JSON.stringify(data);
  const r = await fetch(url, opts);
  const json = await r.json();
  if (!r.ok) throw new Error(json.error || 'Request failed');
  return json;
}

// ── Custom Select ──────────────────────────────────────────────────────────
function initCustomSelects(root, force) {
  root = root || document;
  // If called with a specific container (not document), reset init flags so selects reinitialize
  if (root !== document && root.querySelectorAll) {
    root.querySelectorAll('.cs-wrap[data-cs-init]').forEach(function(w) {
      w.removeAttribute('data-cs-init');
    });
  }
  root.querySelectorAll('.cs-wrap:not([data-cs-init])').forEach(function(wrap) {
    wrap.setAttribute('data-cs-init', '1');
    var name        = wrap.dataset.name || '';
    var initVal     = wrap.dataset.value || '';
    var searchable  = wrap.dataset.searchable === 'true';
    var placeholder = wrap.dataset.placeholder || 'Select…';

    var optEls  = Array.from(wrap.querySelectorAll('.cs-options'));
    var options = optEls.map(function(el) {
      return { value: el.dataset.value !== undefined ? el.dataset.value : el.textContent.trim(), label: el.textContent.trim() };
    });
    optEls.forEach(function(el) { el.remove(); });

    var current = initVal !== '' ? initVal : (options[0] ? options[0].value : '');

    function escH(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
    function getLabel(val) { var o = options.find(function(o){return o.value===val;}); return escH(o ? o.label : (val || placeholder)); }

    wrap.innerHTML =
      '<button type="button" class="cs-trigger" aria-haspopup="listbox">' +
        '<span class="cs-label">' + getLabel(current) + '</span>' +
        '<i class="ti ti-chevron-down arrow"></i>' +
      '</button>' +
      '<div class="cs-dropdown" role="listbox">' +
        (searchable ? '<div class="cs-search"><input type="text" placeholder="Search…" autocomplete="off"></div>' : '') +
        '<div class="cs-list"></div>' +
      '</div>' +
      '<input type="hidden" name="' + name + '" value="' + escH(current) + '">';

    var trigger  = wrap.querySelector('.cs-trigger');
    var dropdown = wrap.querySelector('.cs-dropdown');
    var listEl   = wrap.querySelector('.cs-list');
    var searchEl = wrap.querySelector('.cs-search input');
    var hidden   = wrap.querySelector('input[type=hidden]');

    function renderList(filter) {
      filter = filter || '';
      var f = filter.toLowerCase();
      var filtered = f ? options.filter(function(o){ return o.label.toLowerCase().indexOf(f) !== -1; }) : options;
      if (!filtered.length) { listEl.innerHTML = '<div class="cs-empty">No results</div>'; return; }
      listEl.innerHTML = filtered.map(function(o) {
        return '<div class="cs-option' + (o.value===current?' selected':'') + '" data-value="' + escH(o.value) + '">' + escH(o.label) + '</div>';
      }).join('');
      listEl.querySelectorAll('.cs-option').forEach(function(opt) {
        opt.addEventListener('click', function() { select(opt.dataset.value); });
      });
    }

    function select(val) {
      current = val;
      hidden.value = val;
      trigger.querySelector('.cs-label').innerHTML = getLabel(val);
      close();
      wrap.dispatchEvent(new Event('change', {bubbles: true}));
    }

    function open() {
      trigger.classList.add('open');
      dropdown.classList.add('open');
      renderList();
      if (searchEl) { searchEl.value = ''; searchEl.focus(); }
      setTimeout(function(){ var sel = dropdown.querySelector('.selected'); if(sel) sel.scrollIntoView({block:'nearest'}); }, 50);
    }

    function close() {
      trigger.classList.remove('open');
      dropdown.classList.remove('open');
    }

    trigger.addEventListener('click', function(e) {
      e.stopPropagation();
      dropdown.classList.contains('open') ? close() : open();
    });
    if (searchEl) searchEl.addEventListener('input', function() { renderList(searchEl.value); });
    document.addEventListener('click', function(e) { if (!wrap.contains(e.target)) close(); });

    wrap.csSelect = function(val) {
      var found = options.find(function(o){ return o.value === val; });
      if (found) select(val);
    };
  });
}

// ── Mobile sidebar ─────────────────────────────────────────────────────────
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebar-overlay').classList.toggle('open');
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebar-overlay').classList.remove('open');
}

// ── Init on load ───────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
  initCustomSelects();

  // Close sidebar on nav link tap (mobile)
  document.querySelectorAll('.nav-item').forEach(function(el) {
    el.addEventListener('click', function() { if (window.innerWidth <= 768) closeSidebar(); });
  });
});
