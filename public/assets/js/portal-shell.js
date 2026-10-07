/**
 * Staff portal layout: the sidebar, the top bar and "you can't open this page" handling,
 * shared by every staff page. Load it after store.js and rbac.js, and mark the page:
 *   <body class="pt" data-portal="orders" data-portal-title="Order PSA-123" data-portal-parent="orders">
 *   <div class="pt-frame"><main class="pt-main">...</main></div>
 * The sidebar only lists pages the signed-in role may use (the server checks again on every action).
 */
(function () {
  const ICONS = {
    dashboard: '<path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 6-6"/>',
    orders: '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>',
    messages: '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
    promos: '<path d="M20.59 13.41 13.42 20.6a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><circle cx="7" cy="7" r="1.5"/>',
    products: '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="M3.3 7 12 12l8.7-5"/><path d="M12 22V12"/>',
    payments: '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
    website: '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18"/><path d="M7 6.5h.01M10 6.5h.01"/>',
    users: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    store: '<path d="M3 9l1.5-5h15L21 9"/><path d="M4 9v11h16V9"/><path d="M9 20v-6h6v6"/>',
    moon: '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>',
    sun: '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>',
    logout: '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>',
    menu: '<path d="M4 6h16M4 12h16M4 18h16"/>',
    back: '<path d="M15 18l-6-6 6-6"/>',
    lock: '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>'
  };
  const icon = (name, extra = '') => `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" ${extra}>${ICONS[name] || ''}</svg>`;
  window.ptIcon = icon;

  // Each page and the permissions that let you use it (any one of them is enough).
  const NAV = [
    { label: '', items: [
      { id: 'dashboard', label: 'Dashboard', href: 'dashboard-admin.html', need: ['view_reports'] }
    ] },
    { label: 'Sales', items: [
      { id: 'orders', label: 'Orders', href: 'admin-orders.html', need: ['verify_payments', 'manage_orders', 'cancel_orders'] },
      { id: 'messages', label: 'Messages', href: 'admin-messages.html', need: ['manage_messages'], count: true },
      { id: 'promos', label: 'Promo codes', href: 'admin-promo-codes.html', need: ['manage_promotions'] }
    ] },
    { label: 'Catalog', items: [
      { id: 'products', label: 'Products & stock', href: 'admin-products.html', need: ['manage_products', 'manage_stock', 'manage_suppliers', 'delete_products'] }
    ] },
    { label: 'Settings', items: [
      { id: 'website', label: 'Website', href: 'admin-website.html', need: ['manage_products'] },
      { id: 'payments', label: 'Payment methods', href: 'admin-payment-methods.html', need: ['manage_payment_methods'] },
      { id: 'users', label: 'Staff & roles', href: 'admin-users.html', need: ['manage_users'] }
    ] }
  ];
  const ALL = NAV.flatMap(g => g.items);

  const user = () => (typeof getCurrentUser === 'function' ? getCurrentUser() : (typeof PSA !== 'undefined' && PSA.user)) || {};
  const may = item => item.need.some(p => typeof hasPermission === 'function' && hasPermission(p));
  const esc = s => (typeof psaEsc === 'function' ? psaEsc(s) : String(s ?? ''));

  window.ptCan = permission => typeof hasPermission === 'function' && hasPermission(permission);
  window.ptFirstPage = () => (ALL.find(may) || {}).href || 'home.html';

  function initials(name) {
    return String(name || '?').trim().split(/\s+/).slice(0, 2).map(w => w[0]).join('').toUpperCase();
  }

  function render() {
    const body = document.body;
    if (!body || body.dataset.ptReady) return;
    body.dataset.ptReady = '1';
    body.classList.add('pt');

    const page = body.dataset.portal || '';
    const me = user();
    const current = ALL.find(i => i.id === page);
    const isDark = document.documentElement.classList.contains('dark');

    const nav = NAV.map(group => {
      const items = group.items.filter(may);
      if (!items.length) return '';
      return `<div class="pt-nav__group">${group.label ? `<div class="pt-nav__label">${group.label}</div>` : ''}${items.map(i => `
        <a href="${i.href}"${i.id === page ? ' aria-current="page"' : ''}>${icon(i.id)}<span>${i.label}</span>${i.count ? '<span class="pt-nav__count psa-inbox-count hidden"></span>' : ''}</a>`).join('')}</div>`;
    }).join('');

    const avatar = me.avatarUrl
      ? `<span class="pt-avatar"><img src="${esc(me.avatarUrl)}" alt=""></span>`
      : `<span class="pt-avatar" aria-hidden="true">${esc(initials(me.name))}</span>`;

    const aside = document.createElement('aside');
    aside.className = 'pt-side';
    aside.id = 'ptSide';
    aside.setAttribute('aria-label', 'Staff portal');
    aside.innerHTML = `
      <a class="pt-brand" href="${window.ptFirstPage()}">
        <img src="assets/images/psa-accessories-online-logo.svg" alt="">
        <span><b>Psa<span>Online</span></b><small>Staff portal</small></span>
      </a>
      <nav class="pt-nav" aria-label="Portal pages">${nav}</nav>
      <div class="pt-me">
        <div class="pt-me__row">${avatar}<div style="min-width:0"><div class="pt-me__name">${esc(me.name || 'Signed out')}</div><div class="pt-me__role">${esc(me.role || '')}</div></div></div>
        <div class="pt-me__actions">
          <a href="home.html" title="Open the shop">${icon('store')}<span>Shop</span></a>
          <button type="button" data-pt-theme title="Switch light / dark">${icon(isDark ? 'sun' : 'moon')}<span>${isDark ? 'Light' : 'Dark'}</span></button>
          <button type="button" class="is-out" data-pt-logout title="Log out">${icon('logout')}<span>Log out</span></button>
        </div>
      </div>`;

    const scrim = document.createElement('div');
    scrim.className = 'pt-scrim';
    scrim.setAttribute('data-pt-close', '');

    const parent = ALL.find(i => i.id === body.dataset.portalParent);
    const title = body.dataset.portalTitle || (current && current.label) || document.title.split('—')[0].trim();
    const crumb = parent
      ? `<a href="${parent.href}">${parent.label}</a> <span aria-hidden="true">/</span> <b>${esc(title)}</b>`
      : `<b>${esc(title)}</b>`;

    const top = document.createElement('header');
    top.className = 'pt-top no-print';
    top.innerHTML = `
      <button type="button" class="pt-btn pt-btn--ghost pt-btn--icon pt-top__menu" data-pt-menu aria-controls="ptSide" aria-expanded="false" aria-label="Open menu">${icon('menu')}</button>
      <a class="pt-top__brand" href="${window.ptFirstPage()}"><img src="assets/images/psa-accessories-online-logo.svg" alt=""><span>Staff portal</span></a>
      <div class="pt-top__crumb">${crumb}</div>
      <div class="pt-top__end" data-bell-host></div>`;

    body.prepend(scrim);
    body.prepend(aside);
    const frame = body.querySelector('.pt-frame');
    (frame || body).prepend(top);

    body.addEventListener('click', e => {
      if (e.target.closest('[data-pt-menu]')) setMenu(!body.classList.contains('pt-nav-open'));
      else if (e.target.closest('[data-pt-close]')) setMenu(false);
      if (e.target.closest('[data-pt-theme]')) {
        if (typeof toggleTheme === 'function') toggleTheme();
        const dark = document.documentElement.classList.contains('dark');
        const b = aside.querySelector('[data-pt-theme]');
        b.innerHTML = `${icon(dark ? 'sun' : 'moon')}<span>${dark ? 'Light' : 'Dark'}</span>`;
      }
      if (e.target.closest('[data-pt-logout]')) {
        if (typeof logoutSession === 'function') logoutSession();
      }
    });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') setMenu(false); });

    guard(current);
  }

  function setMenu(open) {
    document.body.classList.toggle('pt-nav-open', open);
    const b = document.querySelector('[data-pt-menu]');
    if (b) b.setAttribute('aria-expanded', String(open));
  }

  // Signed-out visitors go to login; a role without this page gets a clear message instead of broken buttons.
  function guard(current) {
    const me = user();
    if (typeof PSA !== 'undefined' && PSA.online && (!PSA.user || me.guest)) {
      if (typeof psaRequireLogin === 'function') psaRequireLogin();
      return;
    }
    const pageItem = current || ALL.find(i => i.id === document.body.dataset.portalParent);
    if (!pageItem || may(pageItem)) return;
    if (pageItem.id === 'dashboard' && !document.body.dataset.portalParent) {
      const first = ALL.find(may);
      if (first) { location.replace(first.href); return; }
    }
    const main = document.querySelector('.pt-main');
    if (!main) return;
    document.body.dataset.ptDenied = '1';
    main.innerHTML = `
      <div class="pt-denied pt-panel pt-empty">
        ${icon('lock')}
        <h3>Your role can't open ${esc(pageItem.label)}</h3>
        <p>You're signed in as ${esc(me.role || 'a team member')}. If you need this page, ask an Admin to tick it for your role under Staff &amp; roles.</p>
        <a class="pt-btn pt-btn--primary" href="${window.ptFirstPage()}">Go to ${esc((ALL.find(may) || { label: 'the shop' }).label)}</a>
      </div>`;
  }

  window.ptDenied = () => document.body.dataset.ptDenied === '1';

  if (document.body) render();
  else document.addEventListener('DOMContentLoaded', render);
  // The bell mounts into the top bar (store.js looks for [data-bell-host]).
  document.addEventListener('DOMContentLoaded', () => { if (typeof psaInitNotifications === 'function') psaInitNotifications(); });
})();
