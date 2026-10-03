/**
 * Psa Bunny: the chat mascot. A floating bunny button on shopper pages that opens a mini chat
 * with the AI shopping assistant (/api/chat/assistant, answered by Gemini on the server).
 * Loaded by store.js on the pages listed in PSA_MASCOT_PAGES.
 */
(function () {
  if (window.__psaMascotLoaded) return;
  window.__psaMascotLoaded = true;

  const STORE_KEY = 'psa_bunny_chat';
  const GREETED_KEY = 'psa_bunny_greeted';
  const WELCOME = "Hi! I'm Psa Bunny 🐰 I can help you find a cute gift, check prices and delivery, or explain how to pay. What are you looking for?";
  const CHIPS = ['Gift ideas under $10', 'How much is delivery?', 'How do I pay?', 'Where is my order?'];

  // The bunny, drawn from a few soft shapes so it stays readable at 64px.
  const BUNNY_SVG = `
    <svg class="psa-bunny" viewBox="0 0 120 120" aria-hidden="true" focusable="false">
      <g class="psa-bunny__body">
        <g class="psa-bunny__ear psa-bunny__ear--l">
          <rect x="31" y="4" width="22" height="56" rx="11" fill="#FFF6EA"/>
          <rect x="37" y="13" width="10" height="38" rx="5" fill="#FFA552"/>
        </g>
        <g class="psa-bunny__ear psa-bunny__ear--r">
          <rect x="67" y="4" width="22" height="56" rx="11" fill="#FFF6EA"/>
          <rect x="73" y="13" width="10" height="38" rx="5" fill="#FFA552"/>
        </g>
        <ellipse cx="60" cy="80" rx="42" ry="37" fill="#FFF6EA"/>
        <g class="psa-bunny__eyes">
          <ellipse cx="45" cy="77" rx="5" ry="6.5" fill="#2B1D1D"/>
          <ellipse cx="75" cy="77" rx="5" ry="6.5" fill="#2B1D1D"/>
        </g>
        <circle cx="35" cy="90" r="6.5" fill="#FFA552" opacity=".55"/>
        <circle cx="85" cy="90" r="6.5" fill="#FFA552" opacity=".55"/>
        <path d="M54 90 q6 5 12 0" fill="none" stroke="#2B1D1D" stroke-width="3.2" stroke-linecap="round"/>
      </g>
    </svg>`;

  let history = [];
  let sending = false;
  let assistantDown = false;

  function load() {
    try { history = JSON.parse(sessionStorage.getItem(STORE_KEY) || '[]'); } catch (e) { history = []; }
    if (!Array.isArray(history)) history = [];
  }
  function save() {
    try { sessionStorage.setItem(STORE_KEY, JSON.stringify(history.slice(-30))); } catch (e) { /* private mode */ }
  }

  function esc(s) { return typeof psaEsc === 'function' ? psaEsc(s) : String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])); }

  // Links in replies (product pages, the shop, web links) become tappable.
  function linkify(text) {
    return esc(text)
      .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
      .replace(/(https?:\/\/[^\s<]+[^\s<.,!?)])/g, '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>')
      .replace(/(^|[\s(|])(product-detail\.html\?id=[\w-]+)/g, '$1<a href="$2">View product</a>')
      .replace(/(^|[\s(|])(products\.html(?:\?[\w=&;%-]+)?)/g, '$1<a href="$2">Browse the shop</a>');
  }

  function render() {
    const list = document.getElementById('psaBunnyMessages');
    if (!list) return;
    const items = [{ role: 'assistant', text: WELCOME }, ...history];
    list.innerHTML = items.map(m => `
      <div class="psa-bunny-msg ${m.role === 'user' ? 'is-user' : 'is-bot'}">
        <div class="psa-bunny-bubble">${m.role === 'user' ? esc(m.text) : linkify(m.text)}</div>
      </div>`).join('') + (sending ? '<div class="psa-bunny-msg is-bot"><div class="psa-bunny-bubble psa-bunny-typing" role="status">Psa Bunny is typing…</div></div>' : '');
    document.getElementById('psaBunnyChips').hidden = history.length > 0;
    list.scrollTop = list.scrollHeight;
  }

  async function ask(text) {
    text = String(text || '').trim();
    if (!text || sending) return;
    history.push({ role: 'user', text: text.slice(0, 1500) });
    sending = true;
    save();
    render();
    talk();

    let reply;
    let busy = false;
    if (typeof PSA !== 'undefined' && PSA.online && !assistantDown) {
      try {
        const res = await psaApi('POST', '/api/chat/assistant', { messages: history.slice(-12) });
        reply = res.reply;
      } catch (err) {
        if (err.status === 503) assistantDown = true; // AI not set up: stop asking for this visit
        busy = err.status !== 503;
      }
    }
    if (!reply) {
      const basics = 'Delivery in Phnom Penh is $1.50, free on orders of $15 or more, and you can pay by KHQR or cash on delivery. You can browse gift ideas here: products.html?max=10 For anything else, message our team on Telegram: https://t.me/psaonline_support';
      reply = (busy ? "I'm getting lots of questions right now 🐰 Please ask me again in a minute! Quick answers: " : "I can only answer the basics right now. ") + basics;
    }
    history.push({ role: 'assistant', text: reply });
    sending = false;
    save();
    render();
    talk();
  }

  function talk() {
    const btn = document.getElementById('psaBunnyBtn');
    if (!btn) return;
    btn.classList.remove('is-talking');
    void btn.offsetWidth; // restart the hop animation
    btn.classList.add('is-talking');
  }

  function setOpen(open) {
    const panel = document.getElementById('psaBunnyPanel');
    const btn = document.getElementById('psaBunnyBtn');
    if (!panel || !btn) return;
    panel.hidden = !open;
    btn.setAttribute('aria-expanded', String(open));
    btn.classList.toggle('is-open', open);
    hideGreeting();
    if (open) {
      render();
      setTimeout(() => document.getElementById('psaBunnyInput')?.focus(), 60);
    } else {
      btn.focus();
    }
  }

  function hideGreeting() {
    document.getElementById('psaBunnyGreet')?.remove();
  }

  function mount() {
    if (document.getElementById('psaBunny')) return;
    load();
    const root = document.createElement('div');
    root.id = 'psaBunny';
    root.className = 'psa-bunny-root';
    root.innerHTML = `
      <section id="psaBunnyPanel" class="psa-bunny-panel" hidden role="dialog" aria-modal="false" aria-labelledby="psaBunnyTitle">
        <header class="psa-bunny-panel__head">
          <span class="psa-bunny-panel__avatar">${BUNNY_SVG}</span>
          <div class="psa-bunny-panel__title">
            <h2 id="psaBunnyTitle">Psa Bunny</h2>
            <p>AI shopping helper &middot; can make mistakes</p>
          </div>
          <a href="chat.html" class="psa-bunny-panel__link">Full chat</a>
          <button type="button" class="psa-bunny-panel__close" data-bunny-close aria-label="Close chat">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
          </button>
        </header>
        <div id="psaBunnyMessages" class="psa-bunny-panel__messages" aria-live="polite"></div>
        <div id="psaBunnyChips" class="psa-bunny-panel__chips">
          ${CHIPS.map(c => `<button type="button" data-bunny-chip>${esc(c)}</button>`).join('')}
        </div>
        <form id="psaBunnyForm" class="psa-bunny-panel__form">
          <label for="psaBunnyInput" class="sr-only">Ask Psa Bunny</label>
          <input id="psaBunnyInput" type="text" maxlength="1500" autocomplete="off" placeholder="Ask about gifts, prices, delivery…" />
          <button type="submit" aria-label="Send">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
          </button>
        </form>
      </section>
      <button type="button" id="psaBunnyBtn" class="psa-bunny-btn" aria-expanded="false" aria-controls="psaBunnyPanel" aria-label="Chat with Psa Bunny, our AI shopping helper">
        ${BUNNY_SVG}
      </button>`;
    document.body.appendChild(root);

    const btn = document.getElementById('psaBunnyBtn');
    btn.addEventListener('click', () => { talk(); setOpen(document.getElementById('psaBunnyPanel').hidden); });
    root.querySelector('[data-bunny-close]').addEventListener('click', () => setOpen(false));
    root.querySelectorAll('[data-bunny-chip]').forEach(chip => chip.addEventListener('click', () => ask(chip.textContent)));
    document.getElementById('psaBunnyForm').addEventListener('submit', e => {
      e.preventDefault();
      const input = document.getElementById('psaBunnyInput');
      const text = input.value;
      input.value = '';
      ask(text);
    });
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape' && !document.getElementById('psaBunnyPanel').hidden) setOpen(false);
    });

    // A one-time hello per visit, so people notice the helper without being nagged.
    let greeted = false;
    try { greeted = sessionStorage.getItem(GREETED_KEY) === '1'; sessionStorage.setItem(GREETED_KEY, '1'); } catch (e) { greeted = true; }
    if (!greeted) {
      setTimeout(() => {
        if (!document.getElementById('psaBunnyPanel').hidden) return;
        const greet = document.createElement('button');
        greet.type = 'button';
        greet.id = 'psaBunnyGreet';
        greet.className = 'psa-bunny-greet';
        greet.innerHTML = 'Need a gift idea? <strong>Ask me!</strong>';
        greet.addEventListener('click', () => setOpen(true));
        root.appendChild(greet);
        talk();
        setTimeout(hideGreeting, 7000);
      }, 3500);
    }
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mount);
  else mount();
})();
