/* Drops & Stock (resources/portal/admin-products.html): the section tabs, moving ticked products
   to a category, the Categories tab and the Home showcase tab. Uses psaApi / psaEsc from store.js,
   showRBACToast / hasPermission from rbac.js, and `selected` / `shownIds` / renderProductsTable()
   from the page itself. The server checks every permission again. */

const PSA_SHOWCASE_MAX = 6;
const byName = (a, b) => a.name.localeCompare(b.name);

// Top-level categories A to Z, each with its sub-categories.
function categoryTree() {
  const all = Array.isArray(PSA.categories) ? PSA.categories : [];
  return all.filter(c => !c.parentId).sort(byName).map(t => ({ ...t, children: all.filter(c => c.parentId === t.id).sort(byName) }));
}

function categoryOptions({ topOnly = false } = {}) {
  const label = c => `${psaEsc(c.name)}${c.isActive ? '' : ' (hidden)'}`;
  return categoryTree().map(t => `<option value="${t.id}">${label(t)}</option>` +
    (topOnly ? '' : t.children.map(c => `<option value="${c.id}">&nbsp;&nbsp;&nbsp;↳ ${label(c)}</option>`).join(''))).join('');
}

function categoryName(id) {
  const c = (PSA.categories || []).find(x => x.id === Number(id));
  return c ? c.name : 'that category';
}

function refreshCatalogViews() {
  fillCategoryFilter();
  fillBulkCategory();
  renderProductsTable();
  renderCategoryPanel();
  renderShowcasePanel();
  renderSortPanel();
}

/* ---------- Tabs ---------- */

function initCatalogTabs() {
  const tabs = [...document.querySelectorAll('[role="tab"][data-tab]')];
  function show(name, focus) {
    tabs.forEach(t => {
      const on = t.dataset.tab === name;
      t.setAttribute('aria-selected', String(on));
      t.tabIndex = on ? 0 : -1;
      t.classList.toggle('border-orange-600', on);
      t.classList.toggle('text-orange-700', on);
      t.classList.toggle('border-transparent', !on);
      t.classList.toggle('text-gray-500', !on);
      document.getElementById('panel-' + t.dataset.tab).classList.toggle('hidden', !on);
      if (on && focus) t.focus();
    });
    try { history.replaceState(history.state, '', '#' + name); } catch (e) { /* ignore */ }
    if (name === 'showcase') showcasePreview();
    if (name === 'sorting') renderSortPanel();
  }
  tabs.forEach((t, i) => {
    t.addEventListener('click', () => show(t.dataset.tab));
    t.addEventListener('keydown', e => {
      const step = e.key === 'ArrowRight' ? 1 : e.key === 'ArrowLeft' ? -1 : 0;
      if (!step) return;
      e.preventDefault();
      show(tabs[(i + step + tabs.length) % tabs.length].dataset.tab, true);
    });
  });
  window.psaShowCatalogTab = show;
  const start = location.hash.slice(1);
  show(tabs.some(t => t.dataset.tab === start) ? start : 'products');
}

/* ---------- Products tab: move ticked products ---------- */

function fillBulkCategory() {
  const sel = document.getElementById('bulkCategory');
  if (!sel) return;
  const keep = sel.value;
  sel.innerHTML = '<option value="">Choose a category…</option>' + categoryOptions();
  if ([...sel.options].some(o => o.value === keep)) sel.value = keep;
}

function updateBulkBar() {
  const bar = document.getElementById('bulkBar');
  if (!bar) return;
  const known = new Set(PSA.products.map(p => p.id));
  [...selected].forEach(id => { if (!known.has(id)) selected.delete(id); });
  bar.classList.toggle('hidden', selected.size === 0);
  document.getElementById('bulkCount').textContent = selected.size;
  const all = document.getElementById('selectAll');
  if (all) {
    const n = shownIds.filter(id => selected.has(id)).length;
    all.checked = n > 0 && n === shownIds.length;
    all.indeterminate = n > 0 && n < shownIds.length;
  }
  if (!document.getElementById('bulkCategory').options.length) fillBulkCategory();
}

document.addEventListener('change', e => {
  if (e.target.classList && e.target.classList.contains('row-check')) {
    if (e.target.checked) selected.add(e.target.value); else selected.delete(e.target.value);
    updateBulkBar();
  }
  if (e.target.id === 'selectAll') {
    shownIds.forEach(id => { if (e.target.checked) selected.add(id); else selected.delete(id); });
    renderProductsTable();
  }
});

async function moveSelected() {
  const sel = document.getElementById('bulkCategory');
  if (!sel.value) {
    showRBACToast('Choose the category to move them to.', 'danger');
    sel.focus();
    return;
  }
  const btn = document.getElementById('bulkMoveBtn');
  psaSetButtonLoading(btn, true, 'Moving…');
  try {
    const res = await psaApi('POST', '/api/admin/products/move', { products: [...selected], categoryId: Number(sel.value) });
    res.products.forEach(np => {
      const i = PSA.products.findIndex(p => p.id === np.id);
      if (i > -1) PSA.products[i] = np;
    });
    PSA.categories = res.categories;
    selected.clear();
    refreshCatalogViews();
    showRBACToast(`Moved ${res.moved} product${res.moved === 1 ? '' : 's'} to ${psaEsc(categoryName(sel.value))}.`, 'success');
  } catch (err) {
    showRBACToast(psaEsc(err.message), 'danger');
  } finally {
    psaSetButtonLoading(btn, false);
  }
}

document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('bulkMoveBtn')?.addEventListener('click', moveSelected);
  document.getElementById('bulkClearBtn')?.addEventListener('click', () => { selected.clear(); renderProductsTable(); });
});

/* ---------- Categories tab ---------- */

const catBtn = 'h-9 px-3 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 text-xs font-bold text-gray-700 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed';

function categoryRow(c, isSub, canDelete) {
  const count = c.productCount + (c.children || []).reduce((n, x) => n + x.productCount, 0);
  const subs = (c.children || []).length;
  const empty = count === 0 && subs === 0;
  const extra = [`${count} product${count === 1 ? '' : 's'}`];
  if (subs) extra.push(`${subs} sub-categor${subs === 1 ? 'y' : 'ies'}`);
  return `
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 px-4 py-3 ${isSub ? 'sm:pl-12 bg-gray-50/60' : ''}" data-cat-row="${c.id}">
      <div class="flex-1 min-w-0" data-cat-main>
        <div class="flex items-center gap-2">
          ${isSub ? '<span class="text-gray-400" aria-hidden="true">↳</span>' : ''}
          <span class="text-sm font-bold text-gray-900">${psaEsc(c.name)}</span>
          ${c.isActive ? '' : '<span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-gray-100 text-gray-600 border border-gray-200">Hidden from shop</span>'}
        </div>
        <div class="text-xs text-gray-500">${extra.join(' · ')}</div>
      </div>
      <div class="flex flex-wrap gap-2">
        <button type="button" class="${catBtn}" data-cat-action="view">View products</button>
        <button type="button" class="${catBtn}" data-cat-action="rename" aria-label="Rename ${psaEsc(c.name)}">Rename</button>
        <button type="button" class="${catBtn}" data-cat-action="toggle">${c.isActive ? 'Hide' : 'Show'}</button>
        ${canDelete ? `<button type="button" class="${catBtn} hover:text-red-700" data-cat-action="delete" ${empty ? '' : 'disabled title="Move its products and sub-categories out first"'} aria-label="Delete ${psaEsc(c.name)}">Delete</button>` : ''}
      </div>
    </div>`;
}

function renderCategoryPanel() {
  const list = document.getElementById('categoryList');
  if (!list) return;
  const parentSel = document.getElementById('newCategoryParent');
  const keep = parentSel.value;
  parentSel.innerHTML = '<option value="">Nothing (new top-level category)</option>' + categoryOptions({ topOnly: true });
  if ([...parentSel.options].some(o => o.value === keep)) parentSel.value = keep;

  if (!PSA.online) {
    list.innerHTML = '<p class="p-6 text-sm text-gray-500 text-center">Categories can be managed when the server is running.</p>';
    return;
  }
  const canDelete = typeof hasPermission === 'function' && hasPermission('canDeleteProducts');
  const tree = categoryTree();
  list.innerHTML = tree.length
    ? tree.map(t => categoryRow(t, false, canDelete) + t.children.map(c => categoryRow(c, true, canDelete)).join('')).join('')
    : '<p class="p-6 text-sm text-gray-500 text-center">No categories yet. Add the first one above.</p>';
}

async function categoryRequest(method, url, body, okMessage) {
  try {
    const res = await psaApi(method, url, body);
    if (res && res.categories) PSA.categories = res.categories;
    refreshCatalogViews();
    if (okMessage) showRBACToast(okMessage, 'success');
    return res;
  } catch (err) {
    showRBACToast(psaEsc(err.message), 'danger');
    return null;
  }
}

function startRename(row, c) {
  const main = row.querySelector('[data-cat-main]');
  main.innerHTML = `
    <form class="flex flex-wrap items-center gap-2" data-cat-rename>
      <label class="sr-only" for="rename-${c.id}">New name for ${psaEsc(c.name)}</label>
      <input id="rename-${c.id}" type="text" maxlength="60" value="${psaEsc(c.name)}" class="h-9 px-3 text-sm rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-orange-500 min-w-[12rem]" />
      <button type="submit" class="h-9 px-3 rounded-lg bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold cursor-pointer">Save</button>
      <button type="button" class="${catBtn}" data-cat-action="cancel">Cancel</button>
    </form>`;
  const input = main.querySelector('input');
  input.focus();
  input.select();
  main.querySelector('form').addEventListener('submit', async e => {
    e.preventDefault();
    const name = input.value.trim();
    if (name.length < 2) { showRBACToast('Category names need at least 2 characters.', 'danger'); input.focus(); return; }
    if (name === c.name) { renderCategoryPanel(); return; }
    await categoryRequest('PATCH', `/api/admin/categories/${c.id}`, { name }, `Renamed to ${psaEsc(name)}.`);
  });
}

document.addEventListener('DOMContentLoaded', () => {
  const list = document.getElementById('categoryList');
  if (!list) return;

  list.addEventListener('click', async e => {
    const btn = e.target.closest('[data-cat-action]');
    if (!btn) return;
    const row = btn.closest('[data-cat-row]');
    const c = (PSA.categories || []).find(x => x.id === Number(row.dataset.catRow));
    if (!c) return;
    const action = btn.dataset.catAction;
    if (action === 'view') {
      document.getElementById('categoryFilter').value = String(c.id);
      renderProductsTable();
      window.psaShowCatalogTab('products', true);
    } else if (action === 'rename') {
      startRename(row, c);
    } else if (action === 'cancel') {
      renderCategoryPanel();
    } else if (action === 'toggle') {
      await categoryRequest('PATCH', `/api/admin/categories/${c.id}`, { isActive: !c.isActive },
        c.isActive ? `${psaEsc(c.name)} is hidden from the shop.` : `${psaEsc(c.name)} is back in the shop.`);
    } else if (action === 'delete') {
      if (!confirm(`Delete the empty category "${c.name}"?`)) return;
      await categoryRequest('DELETE', `/api/admin/categories/${c.id}`, undefined, `Deleted ${psaEsc(c.name)}.`);
    }
  });

  document.getElementById('newCategoryForm').addEventListener('submit', async e => {
    e.preventDefault();
    const nameEl = document.getElementById('newCategoryName');
    const errorEl = document.getElementById('newCategoryError');
    const name = nameEl.value.trim();
    errorEl.classList.add('hidden');
    if (name.length < 2) {
      errorEl.textContent = 'Give the category a name (at least 2 characters).';
      errorEl.classList.remove('hidden');
      nameEl.focus();
      return;
    }
    const parent = document.getElementById('newCategoryParent').value;
    const btn = document.getElementById('newCategoryBtn');
    psaSetButtonLoading(btn, true, 'Adding…');
    try {
      const res = await psaApi('POST', '/api/admin/categories', { name, parentId: parent ? Number(parent) : null });
      PSA.categories = res.categories;
      nameEl.value = '';
      refreshCatalogViews();
      document.getElementById('bulkCategory').value = String(res.category.id);
      showRBACToast(`Added ${psaEsc(res.category.name)}. Tick products on the Products tab and use Move to fill it.`, 'success');
    } catch (err) {
      errorEl.textContent = err.message;
      errorEl.classList.remove('hidden');
    } finally {
      psaSetButtonLoading(btn, false);
    }
  });
});

/* ---------- Home showcase tab ---------- */

let showcasePicks = [];
let savedPicks = [];
const productById = id => PSA.products.find(p => p.id === id);
const showcaseDirty = () => JSON.stringify(showcasePicks) !== JSON.stringify(savedPicks);

// Why a picked product would not appear on the home page.
function showcaseProblem(p) {
  if (!p) return 'This product no longer exists.';
  if (p.status !== 'active') return `Not shown: the product is ${p.status === 'draft' ? 'a draft' : 'archived'}.`;
  const cat = (PSA.categories || []).find(c => c.id === p.categoryId);
  const parent = cat && cat.parentId ? (PSA.categories || []).find(c => c.id === cat.parentId) : null;
  if ((cat && !cat.isActive) || (parent && !parent.isActive)) return 'Not shown: its category is hidden.';
  return '';
}

function initShowcasePanel() {
  savedPicks = (PSA.showcase || []).slice();
  showcasePicks = savedPicks.slice();

  document.getElementById('showcaseSearch').addEventListener('input', renderShowcaseResults);
  document.getElementById('showcaseResults').addEventListener('click', e => {
    const btn = e.target.closest('[data-add-pick]');
    if (!btn || showcasePicks.length >= PSA_SHOWCASE_MAX) return;
    showcasePicks.push(btn.dataset.addPick);
    renderShowcasePanel();
    document.getElementById('showcaseSearch').focus();
  });
  document.getElementById('showcasePicks').addEventListener('click', e => {
    const btn = e.target.closest('[data-pick-action]');
    if (!btn) return;
    const id = btn.closest('[data-pick]').dataset.pick;
    const i = showcasePicks.indexOf(id);
    const action = btn.dataset.pickAction;
    if (action === 'up' && i > 0) [showcasePicks[i - 1], showcasePicks[i]] = [showcasePicks[i], showcasePicks[i - 1]];
    if (action === 'down' && i < showcasePicks.length - 1) [showcasePicks[i + 1], showcasePicks[i]] = [showcasePicks[i], showcasePicks[i + 1]];
    if (action === 'remove') showcasePicks.splice(i, 1);
    renderShowcasePanel();
    const again = document.querySelector(`[data-pick="${CSS.escape(id)}"] [data-pick-action="${action}"]:not(:disabled)`);
    (again || document.querySelector('#showcasePicks [data-pick-action]') || document.getElementById('showcaseSearch')).focus();
  });
  document.getElementById('showcaseSaveBtn').addEventListener('click', saveShowcase);
  window.addEventListener('beforeunload', e => {
    if (showcaseDirty()) { e.preventDefault(); e.returnValue = ''; }
  });
  renderShowcasePanel();
}

function renderShowcasePanel() {
  const box = document.getElementById('showcasePicks');
  if (!box) return;
  const arrow = 'w-9 h-9 grid place-items-center rounded-lg border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed';
  box.innerHTML = showcasePicks.length ? showcasePicks.map((id, i) => {
    const p = productById(id);
    const problem = showcaseProblem(p);
    const title = p ? p.title : id;
    return `
      <li class="flex items-center gap-3 rounded-xl border ${i === 0 ? 'border-orange-300 bg-orange-50/60' : 'border-gray-200'} p-2" data-pick="${psaEsc(id)}">
        <span class="w-6 text-center text-sm font-black text-gray-400" aria-hidden="true">${i + 1}</span>
        <div class="w-12 h-12 rounded-lg bg-gray-100 overflow-hidden shrink-0">${p && p.image ? `<img src="${psaEsc(p.image)}" alt="" class="w-full h-full object-cover" />` : ''}</div>
        <div class="flex-1 min-w-0">
          <div class="text-sm font-bold text-gray-900 truncate">${psaEsc(title)}</div>
          <div class="text-xs ${problem ? 'text-amber-700 font-semibold' : 'text-gray-500'}">${problem ? psaEsc(problem) : `${i === 0 ? '<strong class="text-orange-700">Front card</strong> · ' : ''}${p ? `$${Number(p.priceUSD).toFixed(2)} · ${psaEsc(p.categoryLabel || '')}` : ''}`}</div>
        </div>
        <div class="flex gap-1">
          <button type="button" class="${arrow}" data-pick-action="up" ${i === 0 ? 'disabled' : ''} aria-label="Move ${psaEsc(title)} up">↑</button>
          <button type="button" class="${arrow}" data-pick-action="down" ${i === showcasePicks.length - 1 ? 'disabled' : ''} aria-label="Move ${psaEsc(title)} down">↓</button>
          <button type="button" class="${arrow} hover:text-red-700" data-pick-action="remove" aria-label="Remove ${psaEsc(title)} from the showcase">✕</button>
        </div>
      </li>`;
  }).join('') : '<li class="rounded-xl border border-dashed border-gray-300 p-5 text-center text-sm text-gray-500">Nothing picked. Until you add some, the home page shows the first products in the catalog.</li>';

  const dirty = showcaseDirty();
  document.getElementById('showcaseDirty').classList.toggle('hidden', !dirty);
  document.getElementById('showcaseSaveBtn').disabled = !dirty;
  renderShowcaseResults();
  showcasePreview();
}

function renderShowcaseResults() {
  const box = document.getElementById('showcaseResults');
  if (!box) return;
  const q = document.getElementById('showcaseSearch').value.trim().toLowerCase();
  const full = showcasePicks.length >= PSA_SHOWCASE_MAX;
  const matches = PSA.products
    .filter(p => p.status === 'active' && !showcasePicks.includes(p.id))
    .filter(p => !q || `${p.title} ${p.categoryLabel || ''} ${p.id}`.toLowerCase().includes(q));
  const head = full ? `<li class="py-2 text-xs font-bold text-amber-700">The showcase is full (${PSA_SHOWCASE_MAX}). Remove one to add another.</li>` : '';
  box.innerHTML = head + (matches.length ? matches.slice(0, 40).map(p => `
    <li class="flex items-center gap-3 py-2">
      <div class="w-10 h-10 rounded-lg bg-gray-100 overflow-hidden shrink-0">${p.image ? `<img src="${psaEsc(p.image)}" alt="" class="w-full h-full object-cover" loading="lazy" />` : ''}</div>
      <div class="flex-1 min-w-0">
        <div class="text-sm font-semibold text-gray-900 truncate">${psaEsc(p.title)}</div>
        <div class="text-xs text-gray-500">${psaEsc(p.categoryLabel || '')} · $${Number(p.priceUSD).toFixed(2)}</div>
      </div>
      <button type="button" class="h-9 px-3 rounded-lg border border-gray-300 bg-white hover:bg-orange-50 hover:border-orange-300 text-xs font-bold text-gray-700 hover:text-orange-800 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed" data-add-pick="${psaEsc(p.id)}" ${full ? 'disabled' : ''} aria-label="Add ${psaEsc(p.title)} to the showcase">Add</button>
    </li>`).join('') : '<li class="py-3 text-sm text-gray-500">No active products match.</li>');
}

function showcasePreview() {
  const el = document.getElementById('showcasePreview');
  if (!el || el.closest('.hidden')) return;
  const picks = showcasePicks.map(productById).filter(p => p && !showcaseProblem(p));
  if (!picks.length) {
    if (el._psaShowcase) el._psaShowcase.destroy();
    el.innerHTML = '<p class="text-sm text-gray-500 text-center py-16">Add products to see the preview.</p>';
    return;
  }
  psaRenderShowcase(el, picks, { preview: true });
}

async function saveShowcase() {
  const btn = document.getElementById('showcaseSaveBtn');
  psaSetButtonLoading(btn, true, 'Saving…');
  try {
    const res = await psaApi('PUT', '/api/admin/showcase', { products: showcasePicks });
    savedPicks = res.showcase.slice();
    showcasePicks = savedPicks.slice();
    PSA.showcase = savedPicks.slice();
    showRBACToast('Showcase saved. The home page shows it now.', 'success');
  } catch (err) {
    showRBACToast(psaEsc(err.message), 'danger');
  } finally {
    psaSetButtonLoading(btn, false);
    renderShowcasePanel();
  }
}

/* ---------- Sort By tab ---------- */

const SORT_KEY_LABELS = {
  featured: 'Trending (shop order)',
  price_asc: 'Price: low to high',
  price_desc: 'Price: high to low',
  rating: 'Top rated',
  newest: 'Newest first',
  name: 'Name: A to Z'
};
let sortEditing = null; // the option being edited ({} for a new one), or null

function sortOptionsAll() {
  return Array.isArray(PSA.sortOptions) ? PSA.sortOptions : [];
}

function describeSortOption(o) {
  if (o.type === 'group') {
    const n = (o.products || []).length;
    return `Hand-picked · ${n} product${n === 1 ? '' : 's'}${o.freeDelivery ? ' · <strong class="text-emerald-700">Free delivery</strong>' : ''}`;
  }
  return `Sorts all products: ${psaEsc(SORT_KEY_LABELS[o.sortKey] || o.sortKey)}`;
}

function renderSortPanel() {
  const box = document.getElementById('sortList');
  if (!box) return;
  if (!PSA.online) {
    box.innerHTML = '<li class="p-6 text-sm text-gray-500 text-center">The Sort By menu can be edited when the server is running.</li>';
    return;
  }
  const canDelete = typeof hasPermission === 'function' && hasPermission('canDeleteProducts');
  const list = sortOptionsAll();
  const arrow = 'w-9 h-9 grid place-items-center rounded-lg border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed';
  box.innerHTML = list.length ? list.map((o, i) => `
    <li class="flex flex-col sm:flex-row sm:items-center gap-3 rounded-xl border ${sortEditing && sortEditing.id === o.id ? 'border-orange-400 bg-orange-50/60' : 'border-gray-200'} p-3" data-sort-row="${o.id}">
      <span class="hidden sm:block w-6 text-center text-sm font-black text-gray-400" aria-hidden="true">${i + 1}</span>
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2">
          <span class="text-sm font-bold text-gray-900">${psaEsc(o.label)}</span>
          ${i === 0 && o.isActive ? '<span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-orange-100 text-orange-800">Default</span>' : ''}
          ${o.isActive ? '' : '<span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-gray-100 text-gray-600 border border-gray-200">Hidden</span>'}
        </div>
        <div class="text-xs text-gray-500">${describeSortOption(o)}</div>
      </div>
      <div class="flex flex-wrap gap-1">
        <button type="button" class="${arrow}" data-sort-action="up" ${i === 0 ? 'disabled' : ''} aria-label="Move ${psaEsc(o.label)} up">↑</button>
        <button type="button" class="${arrow}" data-sort-action="down" ${i === list.length - 1 ? 'disabled' : ''} aria-label="Move ${psaEsc(o.label)} down">↓</button>
        <button type="button" class="${catBtn}" data-sort-action="edit">Edit</button>
        <button type="button" class="${catBtn}" data-sort-action="toggle">${o.isActive ? 'Hide' : 'Show'}</button>
        ${canDelete ? `<button type="button" class="${catBtn} hover:text-red-700" data-sort-action="delete" aria-label="Delete ${psaEsc(o.label)}">Delete</button>` : ''}
      </div>
    </li>`).join('') : '<li class="rounded-xl border border-dashed border-gray-300 p-5 text-center text-sm text-gray-500">No options yet. The shop falls back to its own order.</li>';
  renderSortEditor();
}

function renderSortEditor() {
  const form = document.getElementById('sortEditor');
  if (!form) return;
  if (!sortEditing) {
    form.innerHTML = '<p class="text-sm text-gray-500 text-center py-10">Choose <strong>Edit</strong> on an option, or <strong>+ Add option</strong>.</p>';
    return;
  }
  const o = sortEditing;
  const isGroup = o.type === 'group';
  const picks = o.products || [];
  form.innerHTML = `
    <h2 class="text-base font-black text-gray-900">${o.id ? 'Edit option' : 'New option'}</h2>
    <div class="space-y-1">
      <label for="sortLabel" class="text-xs font-bold text-gray-700">Name customers see</label>
      <input id="sortLabel" type="text" maxlength="60" value="${psaEsc(o.label || '')}" placeholder="e.g. New Drop" class="w-full h-11 px-3 text-sm rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-orange-500" />
    </div>
    <fieldset class="space-y-2">
      <legend class="text-xs font-bold text-gray-700 mb-1">What it does</legend>
      <label class="flex items-start gap-2 text-sm cursor-pointer"><input type="radio" name="sortType" value="sort" ${isGroup ? '' : 'checked'} class="mt-1 accent-orange-600" /> <span><strong>Sort all products</strong><span class="block text-xs text-gray-500">Shows everything, in the order you pick.</span></span></label>
      <label class="flex items-start gap-2 text-sm cursor-pointer"><input type="radio" name="sortType" value="group" ${isGroup ? 'checked' : ''} class="mt-1 accent-orange-600" /> <span><strong>Hand-picked products</strong><span class="block text-xs text-gray-500">Shows only the products you choose below.</span></span></label>
    </fieldset>
    ${isGroup ? `
      <label class="flex items-start gap-2 rounded-lg border border-emerald-200 bg-emerald-50/60 p-3 text-sm cursor-pointer">
        <input type="checkbox" id="sortFree" ${o.freeDelivery ? 'checked' : ''} class="mt-1 w-4 h-4 accent-emerald-700" />
        <span><strong class="text-emerald-900">Free delivery for these products</strong><span class="block text-xs text-emerald-900/80">Any order with one of them ships free, even with other items in the bag. They get a "Free delivery" badge.</span></span>
      </label>
      <div class="space-y-2">
        <div class="text-xs font-bold text-gray-700">Products in it (${picks.length})</div>
        <ul class="max-h-56 overflow-y-auto divide-y divide-gray-100 rounded-lg border border-gray-200" id="sortPicks">
          ${picks.length ? picks.map(id => {
            const p = productById(id);
            return `<li class="flex items-center gap-2 px-2 py-1.5">
              <div class="w-8 h-8 rounded bg-gray-100 overflow-hidden shrink-0">${p && p.image ? `<img src="${psaEsc(p.image)}" alt="" class="w-full h-full object-cover" />` : ''}</div>
              <span class="flex-1 min-w-0 text-sm truncate">${psaEsc(p ? p.title : id)}</span>
              <button type="button" class="w-8 h-8 rounded-lg hover:bg-red-50 hover:text-red-700 text-gray-500 cursor-pointer" data-sort-remove="${psaEsc(id)}" aria-label="Remove ${psaEsc(p ? p.title : id)}">✕</button>
            </li>`;
          }).join('') : '<li class="px-3 py-4 text-sm text-gray-500 text-center">No products yet. Add some below.</li>'}
        </ul>
        <label for="sortSearch" class="sr-only">Find products to add</label>
        <input id="sortSearch" type="search" placeholder="Find products to add" class="w-full h-10 px-3 text-sm rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-orange-500" />
        <ul class="max-h-56 overflow-y-auto divide-y divide-gray-100" id="sortResults"></ul>
      </div>` : `
      <div class="space-y-1">
        <label for="sortKey" class="text-xs font-bold text-gray-700">Order</label>
        <select id="sortKey" class="w-full h-11 px-3 text-sm rounded-lg border border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-orange-500">
          ${Object.entries(SORT_KEY_LABELS).map(([k, v]) => `<option value="${k}" ${o.sortKey === k ? 'selected' : ''}>${v}</option>`).join('')}
        </select>
      </div>`}
    <p id="sortError" class="hidden text-xs font-bold text-red-600" role="alert"></p>
    <div class="flex gap-2">
      <button type="submit" id="sortSaveBtn" class="h-10 px-4 rounded-lg bg-orange-600 hover:bg-orange-700 text-white text-sm font-bold cursor-pointer">${o.id ? 'Save changes' : 'Add to the menu'}</button>
      <button type="button" id="sortCancelBtn" class="${catBtn} h-10">Cancel</button>
    </div>`;
  if (isGroup) renderSortResults();
}

function renderSortResults() {
  const box = document.getElementById('sortResults');
  if (!box || !sortEditing) return;
  const q = (document.getElementById('sortSearch').value || '').trim().toLowerCase();
  const picks = sortEditing.products || [];
  const matches = PSA.products.filter(p => p.status === 'active' && !picks.includes(p.id) && (!q || `${p.title} ${p.categoryLabel || ''} ${p.id}`.toLowerCase().includes(q)));
  box.innerHTML = matches.length ? matches.slice(0, 30).map(p => `
    <li class="flex items-center gap-2 py-1.5">
      <div class="w-8 h-8 rounded bg-gray-100 overflow-hidden shrink-0">${p.image ? `<img src="${psaEsc(p.image)}" alt="" class="w-full h-full object-cover" loading="lazy" />` : ''}</div>
      <span class="flex-1 min-w-0 text-sm truncate">${psaEsc(p.title)} <span class="text-xs text-gray-500">$${Number(p.priceUSD).toFixed(2)}</span></span>
      <button type="button" class="h-8 px-3 rounded-lg border border-gray-300 bg-white hover:bg-orange-50 hover:border-orange-300 hover:text-orange-800 text-xs font-bold text-gray-700 cursor-pointer" data-sort-add="${psaEsc(p.id)}" aria-label="Add ${psaEsc(p.title)}">Add</button>
    </li>`).join('') : '<li class="py-3 text-sm text-gray-500">No more products match.</li>';
}

// Keep what was typed when the editor re-renders.
function readSortEditor() {
  if (!sortEditing) return;
  const label = document.getElementById('sortLabel');
  if (label) sortEditing.label = label.value;
  const key = document.getElementById('sortKey');
  if (key) sortEditing.sortKey = key.value;
  const free = document.getElementById('sortFree');
  if (free) sortEditing.freeDelivery = free.checked;
}

async function sortRequest(method, url, body, okMessage) {
  try {
    const res = await psaApi(method, url, body);
    if (res && res.sortOptions) PSA.sortOptions = res.sortOptions;
    if (okMessage) showRBACToast(okMessage, 'success');
    return res;
  } catch (err) {
    showRBACToast(psaEsc(err.message), 'danger');
    return null;
  } finally {
    renderSortPanel();
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const list = document.getElementById('sortList');
  const form = document.getElementById('sortEditor');
  if (!list || !form) return;

  document.getElementById('sortAddBtn').addEventListener('click', () => {
    sortEditing = { label: '', type: 'group', freeDelivery: false, products: [], sortKey: 'featured' };
    renderSortPanel();
    document.getElementById('sortLabel').focus();
  });

  list.addEventListener('click', async e => {
    const btn = e.target.closest('[data-sort-action]');
    if (!btn) return;
    const id = Number(btn.closest('[data-sort-row]').dataset.sortRow);
    const options = sortOptionsAll();
    const i = options.findIndex(o => o.id === id);
    const o = options[i];
    const action = btn.dataset.sortAction;
    if (action === 'edit') {
      sortEditing = JSON.parse(JSON.stringify(o));
      renderSortPanel();
      document.getElementById('sortLabel').focus();
    } else if (action === 'toggle') {
      await sortRequest('PATCH', `/api/admin/sort-options/${id}`, { isActive: !o.isActive }, o.isActive ? `${psaEsc(o.label)} is hidden from the shop.` : `${psaEsc(o.label)} is back in the shop.`);
    } else if (action === 'delete') {
      if (!confirm(`Delete "${o.label}" from the Sort By menu?`)) return;
      if (sortEditing && sortEditing.id === id) sortEditing = null;
      await sortRequest('DELETE', `/api/admin/sort-options/${id}`, undefined, `Deleted ${psaEsc(o.label)}.`);
    } else if (action === 'up' || action === 'down') {
      const ids = options.map(x => x.id);
      const j = action === 'up' ? i - 1 : i + 1;
      [ids[i], ids[j]] = [ids[j], ids[i]];
      await sortRequest('PUT', '/api/admin/sort-options/order', { ids });
      document.querySelector(`[data-sort-row="${id}"] [data-sort-action="${action}"]:not(:disabled)`)?.focus();
    }
  });

  form.addEventListener('change', e => {
    if (e.target.name === 'sortType') {
      readSortEditor();
      sortEditing.type = e.target.value;
      renderSortEditor();
      form.querySelector(`input[name="sortType"][value="${sortEditing.type}"]`).focus();
    }
  });
  form.addEventListener('input', e => { if (e.target.id === 'sortSearch') renderSortResults(); });
  form.addEventListener('click', e => {
    const add = e.target.closest('[data-sort-add]');
    const remove = e.target.closest('[data-sort-remove]');
    if (!add && !remove && e.target.id !== 'sortCancelBtn') return;
    readSortEditor();
    if (e.target.id === 'sortCancelBtn') { sortEditing = null; renderSortPanel(); return; }
    const search = document.getElementById('sortSearch').value;
    if (add) sortEditing.products = [...(sortEditing.products || []), add.dataset.sortAdd];
    if (remove) sortEditing.products = (sortEditing.products || []).filter(id => id !== remove.dataset.sortRemove);
    renderSortEditor();
    const box = document.getElementById('sortSearch');
    box.value = search;
    renderSortResults();
    box.focus();
  });
  form.addEventListener('submit', async e => {
    e.preventDefault();
    readSortEditor();
    const o = sortEditing;
    const errorEl = document.getElementById('sortError');
    const label = (o.label || '').trim();
    if (label.length < 2) {
      errorEl.textContent = 'Give the option a name (at least 2 characters).';
      errorEl.classList.remove('hidden');
      document.getElementById('sortLabel').focus();
      return;
    }
    const body = o.type === 'group'
      ? { label, type: 'group', freeDelivery: !!o.freeDelivery, products: o.products || [] }
      : { label, type: 'sort', sortKey: o.sortKey || 'featured' };
    const btn = document.getElementById('sortSaveBtn');
    psaSetButtonLoading(btn, true, 'Saving…');
    try {
      const res = o.id
        ? await psaApi('PATCH', `/api/admin/sort-options/${o.id}`, body)
        : await psaApi('POST', '/api/admin/sort-options', body);
      PSA.sortOptions = res.sortOptions;
      sortEditing = null;
      renderSortPanel();
      showRBACToast(`${psaEsc(label)} saved. The shop's Sort By menu shows it now.`, 'success');
    } catch (err) {
      errorEl.textContent = err.message;
      errorEl.classList.remove('hidden');
      psaSetButtonLoading(btn, false);
    }
  });
});

/* ---------- Home headline (Home showcase tab) ---------- */

const HOME_TEXT_DEFAULT = {
  headline: 'Cute gifts & *little treats,* delivered in Phnom Penh.',
  subtitle: 'Plush bag charms, flower claw clips, comfy socks, graphic tees and watches. Real prices in US dollars, paid your way.'
};
let savedHomeText = null;

function homeTextValues() {
  return { headline: document.getElementById('homeHeadline').value.trim(), subtitle: document.getElementById('homeSubtitle').value.trim() };
}

function renderHomeTextPreview() {
  const v = homeTextValues();
  document.getElementById('homeTextPreview').innerHTML = psaHeadlineHtml(v.headline || HOME_TEXT_DEFAULT.headline);
  document.getElementById('homeSubtitlePreview').textContent = v.subtitle || HOME_TEXT_DEFAULT.subtitle;
  document.getElementById('homeHeadlineCount').textContent = `${v.headline.length}/90`;
  document.getElementById('homeSubtitleCount').textContent = `${v.subtitle.length}/220`;
  const dirty = JSON.stringify(v) !== JSON.stringify(savedHomeText);
  document.getElementById('homeTextDirty').classList.toggle('hidden', !dirty);
  document.getElementById('homeTextSaveBtn').disabled = !dirty;
}

function fillHomeText(content) {
  const c = content || {};
  savedHomeText = { headline: c.homeHeadline || HOME_TEXT_DEFAULT.headline, subtitle: c.homeSubtitle || HOME_TEXT_DEFAULT.subtitle };
  document.getElementById('homeHeadline').value = savedHomeText.headline;
  document.getElementById('homeSubtitle').value = savedHomeText.subtitle;
  renderHomeTextPreview();
}

async function saveHomeText(values) {
  const errorEl = document.getElementById('homeTextError');
  errorEl.classList.add('hidden');
  const btn = document.getElementById('homeTextSaveBtn');
  psaSetButtonLoading(btn, true, 'Saving…');
  try {
    // The original text is stored as "nothing", so the page keeps its built-in text.
    const res = await psaApi('PUT', '/api/admin/site-content', {
      homeHeadline: values.headline === HOME_TEXT_DEFAULT.headline ? null : values.headline || null,
      homeSubtitle: values.subtitle === HOME_TEXT_DEFAULT.subtitle ? null : values.subtitle || null
    });
    PSA.siteContent = res.siteContent;
    psaSetButtonLoading(btn, false);
    fillHomeText(res.siteContent);
    showRBACToast('Headline saved. The home page shows it now.', 'success');
  } catch (err) {
    psaSetButtonLoading(btn, false);
    errorEl.textContent = err.message;
    errorEl.classList.remove('hidden');
    renderHomeTextPreview();
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('homeTextForm');
  if (!form) return;
  if (!PSA.online) {
    form.innerHTML = '<p class="text-sm text-gray-500">The headline can be changed when the server is running.</p>';
    return;
  }
  fillHomeText(PSA.siteContent);
  form.addEventListener('input', renderHomeTextPreview);
  form.addEventListener('submit', e => {
    e.preventDefault();
    const v = homeTextValues();
    if (v.headline.replace(/\*/g, '').trim().length < 3) {
      const errorEl = document.getElementById('homeTextError');
      errorEl.textContent = 'The headline needs at least 3 characters.';
      errorEl.classList.remove('hidden');
      document.getElementById('homeHeadline').focus();
      return;
    }
    saveHomeText(v);
  });
  document.getElementById('homeTextResetBtn').addEventListener('click', () => {
    document.getElementById('homeHeadline').value = HOME_TEXT_DEFAULT.headline;
    document.getElementById('homeSubtitle').value = HOME_TEXT_DEFAULT.subtitle;
    renderHomeTextPreview();
  });
});

/* ---------- Social media tab ---------- */

const SOCIAL_ORDER = ['facebook', 'instagram', 'tiktok', 'x', 'youtube', 'telegram'];
const SOCIAL_EXAMPLES = {
  facebook: 'https://www.facebook.com/yourpage',
  instagram: 'https://www.instagram.com/yourname',
  tiktok: 'https://www.tiktok.com/@yourname',
  x: 'https://x.com/yourname',
  youtube: 'https://www.youtube.com/@yourchannel',
  telegram: 'https://t.me/yourname'
};
let savedSocials = null;

function socialRowsFrom(list) {
  return SOCIAL_ORDER.map(platform => {
    const s = (list || []).find(x => x.platform === platform);
    return { platform, handle: s ? s.handle : '', url: s ? s.url : '' };
  });
}

function readSocialRows() {
  return SOCIAL_ORDER.map(platform => ({
    platform,
    handle: document.getElementById(`social-handle-${platform}`).value.trim(),
    url: document.getElementById(`social-url-${platform}`).value.trim()
  }));
}

function renderSocialRows(rows) {
  document.getElementById('socialRows').innerHTML = rows.map(r => `
    <div class="grid gap-2 sm:grid-cols-[9rem_1fr_1.4fr] sm:items-end py-3">
      <div class="flex items-center gap-2 text-sm font-bold text-gray-900 sm:pb-2.5">${psaSocialIcon(r.platform, 'w-5 h-5')} ${PSA_SOCIAL_NAMES[r.platform]}</div>
      <div class="space-y-1">
        <label for="social-handle-${r.platform}" class="text-xs font-bold text-gray-700">Username</label>
        <input id="social-handle-${r.platform}" type="text" maxlength="60" value="${psaEsc(r.handle)}" placeholder="@yourname" class="w-full h-10 px-3 text-sm rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-orange-500" />
      </div>
      <div class="space-y-1">
        <label for="social-url-${r.platform}" class="text-xs font-bold text-gray-700">Link</label>
        <input id="social-url-${r.platform}" type="url" maxlength="255" value="${psaEsc(r.url)}" placeholder="${SOCIAL_EXAMPLES[r.platform]}" class="w-full h-10 px-3 text-sm rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-orange-500" />
      </div>
    </div>`).join('');
}

function renderSocialsPreview() {
  const rows = readSocialRows().filter(r => r.handle && r.url);
  const preview = document.getElementById('socialsPreview');
  preview.dataset.psaSocials = 'list';
  psaRenderSocials(preview.parentElement, rows);
  if (!preview.innerHTML) preview.innerHTML = '<li class="text-sm text-gray-500">No accounts: the footer section stays empty.</li>';
  const dirty = JSON.stringify(readSocialRows()) !== JSON.stringify(savedSocials);
  document.getElementById('socialsDirty').classList.toggle('hidden', !dirty);
  document.getElementById('socialsSaveBtn').disabled = !dirty;
}

function fillSocials(content) {
  const saved = content && Array.isArray(content.socials) ? content.socials : PSA_SOCIAL_DEFAULTS;
  const rows = socialRowsFrom(saved);
  savedSocials = rows.map(r => ({ ...r }));
  renderSocialRows(rows);
  renderSocialsPreview();
}

async function saveSocials(links) {
  const errorEl = document.getElementById('socialsError');
  errorEl.classList.add('hidden');
  const btn = document.getElementById('socialsSaveBtn');
  psaSetButtonLoading(btn, true, 'Saving…');
  try {
    const res = await psaApi('PUT', '/api/admin/socials', { links });
    PSA.siteContent = res.siteContent;
    psaSetButtonLoading(btn, false);
    fillSocials(res.siteContent);
    showRBACToast('Social accounts saved. Every footer shows them now.', 'success');
  } catch (err) {
    psaSetButtonLoading(btn, false);
    errorEl.textContent = err.message;
    errorEl.classList.remove('hidden');
    renderSocialsPreview();
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('socialsForm');
  if (!form) return;
  if (!PSA.online) {
    form.innerHTML = '<p class="text-sm text-gray-500">Social accounts can be changed when the server is running.</p>';
    return;
  }
  fillSocials(PSA.siteContent);
  form.addEventListener('input', renderSocialsPreview);
  form.addEventListener('submit', e => {
    e.preventDefault();
    const errorEl = document.getElementById('socialsError');
    const rows = readSocialRows();
    const half = rows.find(r => (r.handle && !r.url) || (!r.handle && r.url));
    const bad = rows.find(r => r.url && !/^https:\/\/[^\s]+\.[^\s]+/i.test(r.url));
    if (half || bad) {
      const r = half || bad;
      errorEl.textContent = half
        ? `${PSA_SOCIAL_NAMES[r.platform]}: fill in both the username and the link, or leave both empty to hide it.`
        : `${PSA_SOCIAL_NAMES[r.platform]}: the link must be a full address starting with https://`;
      errorEl.classList.remove('hidden');
      document.getElementById(`social-${half && !r.url ? 'url' : (half ? 'handle' : 'url')}-${r.platform}`).focus();
      return;
    }
    saveSocials(rows.filter(r => r.handle && r.url));
  });
  document.getElementById('socialsResetBtn').addEventListener('click', () => {
    if (!confirm('Put the original social accounts back?')) return;
    saveSocials(null);
  });
});
