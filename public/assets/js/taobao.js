/**
 * PsaOnline — Taobao-style UI behaviors
 * Requires store.js (ACCESSORIES_PRODUCTS, formatPrice) to be loaded first.
 */

/* Estimated "sold" label. NOTE: derived from reviewsCount until real order data is wired in. */
function tbSoldLabel(item) {
  const n = Math.max(10, Math.round((item.reviewsCount || 0) * 5));
  if (n >= 1000) return (Math.floor(n / 100) / 10) + 'k+ sold';
  return (Math.floor(n / 10) * 10) + '+ sold';
}

/* Shared tag row for product cards */
function tbTagsHtml(item) {
  const tags = ['<span class="tb-tag solid">Free delivery $15+</span>', '<span class="tb-tag">KHQR</span>'];
  if (item.badge) tags.push('<span class="tb-tag gray">' + String(item.badge).replace(/[<>]/g, '') + '</span>');
  return '<div class="tb-tags">' + tags.join('') + '</div>';
}

/* Header search → Taobao pill search with orange button */
function initTbSearch() {
  // Pages without a header search: inject one right after the logo (Taobao keeps search on every page)
  document.querySelectorAll('header.sticky').forEach(h => {
    if (h.querySelector('form[action="products.html"]')) return;
    const bar = h.firstElementChild;
    const logo = bar && bar.firstElementChild;
    if (!bar || !logo) return;
    const wrap = document.createElement('div');
    wrap.className = 'hidden sm:block';
    wrap.innerHTML = '<form action="products.html" method="GET"><input type="text" name="q" placeholder="Search Y2K rings, cloud bags, claw clips, shades..." /></form>';
    logo.insertAdjacentElement('afterend', wrap);
  });

  document.querySelectorAll('header form[action="products.html"]').forEach(form => {
    if (form.classList.contains('tb-search')) return;
    const input = form.querySelector('input[name="q"]');
    const placeholder = input ? input.getAttribute('placeholder') : 'Search accessories';
    const params = new URLSearchParams(location.search);
    form.className = 'tb-search';
    form.innerHTML =
      '<input type="text" name="q" autocomplete="off" placeholder="' + placeholder + '" value="' + (params.get('q') || '').replace(/"/g, '&quot;') + '" />' +
      '<button type="submit">Search</button>';
    if (form.parentElement) {
      form.parentElement.classList.remove('hidden', 'lg:block', 'max-w-md');
      form.parentElement.classList.add('tb-search-wrap');
    }
  });
}

/* Home hero carousel */
function initTbHero() {
  const hero = document.getElementById('tbBanner');
  if (!hero || typeof ACCESSORIES_PRODUCTS === 'undefined') return;
  const slides = hero.querySelectorAll('.tb-slide');
  const dotsBox = hero.querySelector('.tb-dots');
  const picks = ['jewelry', 'eyewear', 'bags'].map((c, i) =>
    ACCESSORIES_PRODUCTS.find(p => p.category === c) || ACCESSORIES_PRODUCTS[i % ACCESSORIES_PRODUCTS.length]);
  slides.forEach((s, i) => {
    const img = s.querySelector('img');
    if (img && picks[i]) { img.src = picks[i].image; img.alt = picks[i].title; }
    const a = s.querySelector('.tb-cta');
    if (a && picks[i]) a.href = 'product-detail.html?id=' + picks[i].id;
  });
  let cur = 0, timer;
  const go = n => {
    cur = (n + slides.length) % slides.length;
    slides.forEach((s, i) => s.classList.toggle('active', i === cur));
    dotsBox.querySelectorAll('button').forEach((d, i) => d.classList.toggle('active', i === cur));
  };
  dotsBox.innerHTML = Array.from(slides, (_, i) => '<button type="button" aria-label="Slide ' + (i + 1) + '"></button>').join('');
  dotsBox.querySelectorAll('button').forEach((d, i) => d.addEventListener('click', () => { go(i); restart(); }));
  const restart = () => { clearInterval(timer); timer = setInterval(() => go(cur + 1), 4500); };
  hero.addEventListener('mouseenter', () => clearInterval(timer));
  hero.addEventListener('mouseleave', restart);
  go(0); restart();
}

/* "Hot right now" strip: top-rated products */
function initTbHot() {
  const box = document.getElementById('tbHot');
  if (!box || typeof ACCESSORIES_PRODUCTS === 'undefined') return;
  const top = [...ACCESSORIES_PRODUCTS].sort((a, b) => (b.reviewsCount || 0) - (a.reviewsCount || 0)).slice(0, 5);
  box.innerHTML = top.map(p =>
    '<a href="product-detail.html?id=' + p.id + '">' +
      '<img src="' + p.image + '" alt="' + p.title.replace(/"/g, '&quot;') + '" loading="lazy" />' +
      '<div class="t">' + p.title + '</div>' +
      '<div><span class="tb-price">' + formatPrice(p.priceUSD, p.priceKHR) + '</span> <span class="tb-sold">' + tbSoldLabel(p) + '</span></div>' +
    '</a>'
  ).join('');
}

document.addEventListener('DOMContentLoaded', () => {
  initTbSearch();
  initTbHero();
  initTbHot();
});
