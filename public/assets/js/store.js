/**
 * PsaOnline - Gen-Z Street & Aesthetic Accessories Marketplace Engine
 * Sorbet Orange (#FFA552), Cotton Beige (#F9F3EA), Deep Espresso (#2B1D1D) & Clean White (#FFFFFF)
 */

const EXCHANGE_RATE = 4100; // 1 USD = 4,100 KHR

// Show clean addresses (/products instead of /products.html, / for the home page) without reloading.
// The server answers both forms, so old .html links and bookmarks keep working.
(function psaCleanAddress() {
  if (location.port === '3000') return; // the Vite dev server only knows the .html files
  const m = location.pathname.match(/^(.*\/)([a-z0-9-]+)\.html$/i);
  if (!m) return;
  const clean = m[2] === 'home' || m[2] === 'index' ? m[1] : m[1] + m[2];
  try { history.replaceState(history.state, '', clean + location.search + location.hash); } catch (e) { /* ignore */ }
})();

// This page's file name (products.html), whether the address shows /products or /products.html.
function psaPageName() {
  const last = location.pathname.split('/').pop();
  return last ? (/\.html$/i.test(last) ? last : last + '.html') : 'home.html';
}

// Live data from the Laravel API (Supabase database). Loaded synchronously on purpose:
// every page script reads the catalog / user / orders as soon as it runs.
// When the API is unreachable (e.g. `npm run dev` without `php artisan serve`),
// PSA.online is false and the pages fall back to the built-in demo data below.
const PSA = (function loadPsaBootstrap() {
  try {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', '/api/bootstrap', false);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.send();
    if (xhr.status === 200) {
      const data = JSON.parse(xhr.responseText);
      data.online = true;
      return data;
    }
  } catch (e) {
    // offline: fall through to demo mode
  }
  return { online: false, csrf: '', user: null, products: [], paymentMethods: [], orders: [], users: [] };
})();

// JSON call to the Laravel API. Resolves with the response body or throws an Error with a readable message.
async function psaApi(method, url, body) {
  if (!PSA.online) {
    throw new Error('The server is offline. Start it with "php artisan serve" and open http://127.0.0.1:8000');
  }
  const res = await fetch(url, {
    method,
    credentials: 'same-origin',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': PSA.csrf,
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: body === undefined ? undefined : JSON.stringify(body)
  });
  let data = null;
  try { data = await res.json(); } catch (e) { /* empty body */ }
  if (!res.ok) {
    const firstError = data && data.errors ? Object.values(data.errors)[0] : null;
    const message = (firstError && firstError[0]) || (data && data.message) || `Request failed (${res.status})`;
    const err = new Error(res.status === 419 ? 'Your session expired. Refresh the page and try again.' : message);
    err.status = res.status;
    err.errors = data && data.errors ? data.errors : null;
    throw err;
  }
  // Signing in/out starts a new session with a new CSRF token; keep using the fresh one.
  if (data && typeof data.csrf === 'string') PSA.csrf = data.csrf;
  return data;
}

// =========================================================================
// SHARED FORM VALIDATION & FEEDBACK HELPERS
// =========================================================================

function psaValidateEmail(email) {
  const v = (email || '').trim();
  if (!v) return 'Email address is required.';
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!re.test(v)) return 'Please enter a valid email address (e.g. name@example.com).';
  return null;
}

function psaValidatePhone(phone, required = true) {
  const v = (phone || '').trim();
  if (!v) return required ? 'Phone number is required.' : null;
  const digits = v.replace(/\D/g, '');
  if (digits.length < 8) return 'Phone number must be at least 8 digits.';
  if (!/^[+0-9\s\-()]+$/.test(v)) return 'Please enter a valid phone number (digits, spaces, or +).';
  return null;
}

function psaValidatePassword(password, minLength = 6, label = 'Password') {
  const v = password || '';
  if (!v) return `${label} is required.`;
  if (v.length < minLength) return `${label} must be at least ${minLength} characters.`;
  return null;
}

function psaValidateRequired(value, label = 'This field') {
  if (value === null || value === undefined) return `${label} is required.`;
  if (typeof value === 'string' && !value.trim()) return `${label} is required.`;
  if (Array.isArray(value) && value.length === 0) return `${label} is required.`;
  return null;
}

function psaValidateMinLength(value, min, label = 'This field') {
  const v = (value || '').trim();
  if (v.length < min) return `${label} must be at least ${min} characters.`;
  return null;
}

function psaValidateNumber(value, min = 0, label = 'This field') {
  const num = Number(value);
  if (isNaN(num)) return `${label} must be a valid number.`;
  if (num < min) return `${label} must be at least ${min}.`;
  return null;
}

function psaGetInputElement(inputOrId, root = document) {
  if (!inputOrId) return null;
  if (typeof inputOrId !== 'string') return inputOrId;
  return (root.getElementById ? root.getElementById(inputOrId) : null) ||
         document.getElementById(inputOrId) ||
         (root.querySelector ? root.querySelector(`[name="${inputOrId}"]`) : null) ||
         (root.querySelector ? root.querySelector(`#${inputOrId}`) : null);
}

const PSA_FIELD_BORDERS = ['border-[#EFE4D6]', 'border-stone-200', 'border-gray-200', 'border-gray-300'];

// The message <p> sits right after the input, or right after its wrapper when the input has an icon.
function psaFindFieldError(el) {
  const key = el.id || el.name;
  const parent = el.parentElement;
  if (key && parent) {
    const sel = `.field-error[data-for="${CSS.escape(key)}"]`;
    const next = parent.nextElementSibling;
    const found = parent.querySelector(sel) || (next && next.matches(sel) ? next : null);
    if (found) return found;
  }
  return el.id ? (document.getElementById(`${el.id}_error`) || document.getElementById(`${el.id}Error`)) : null;
}

// Put back the field's own border colour (saved by psaShowFieldError).
function psaRestoreFieldBorder(el) {
  el.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-200');
  if (el.dataset.psaBorder) {
    el.classList.add(...el.dataset.psaBorder.split(' '));
    delete el.dataset.psaBorder;
  }
}

function psaShowFieldError(inputOrId, message, root = document) {
  const el = psaGetInputElement(inputOrId, root);
  if (!el) return;

  const borders = PSA_FIELD_BORDERS.filter(c => el.classList.contains(c));
  if (borders.length) el.dataset.psaBorder = borders.join(' ');
  el.classList.remove(...PSA_FIELD_BORDERS);
  el.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-200');

  let errEl = psaFindFieldError(el);
  if (!errEl) {
    errEl = document.createElement('p');
    errEl.className = 'field-error text-[11px] font-bold text-red-600 dark:text-red-400 mt-1';
    errEl.setAttribute('role', 'alert');
    if (el.id || el.name) errEl.setAttribute('data-for', el.id || el.name);
    const parent = el.parentElement;
    if (parent && (parent.classList.contains('relative') || parent.classList.contains('flex'))) {
      parent.insertAdjacentElement('afterend', errEl);
    } else if (parent) {
      el.insertAdjacentElement('afterend', errEl);
    }
  }

  errEl.textContent = message;
  errEl.classList.remove('hidden');
}

function psaClearFieldError(inputOrId, root = document) {
  const el = psaGetInputElement(inputOrId, root);
  if (!el) return;

  psaRestoreFieldBorder(el);

  const errEl = psaFindFieldError(el);
  if (errEl) {
    errEl.textContent = '';
    errEl.classList.add('hidden');
  }
}

function psaClearAllFieldErrors(container = document) {
  if (!container) return;
  const errors = container.querySelectorAll ? container.querySelectorAll('.field-error') : [];
  errors.forEach(err => {
    err.textContent = '';
    err.classList.add('hidden');
  });

  const inputs = container.querySelectorAll ? container.querySelectorAll('.border-red-500') : [];
  inputs.forEach(psaRestoreFieldBorder);
}

function psaSetButtonLoading(buttonOrId, isLoading, loadingText = 'Processing...') {
  const btn = typeof buttonOrId === 'string' ? document.getElementById(buttonOrId) : buttonOrId;
  if (!btn) return;

  if (isLoading) {
    btn.disabled = true;
    if (!btn.dataset.originalHtml) {
      btn.dataset.originalHtml = btn.innerHTML;
    }
    btn.classList.add('opacity-75', 'cursor-not-allowed');
    btn.innerHTML = `<svg class="animate-spin -ml-1 mr-2 h-4 w-4 inline-block" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span>${loadingText}</span>`;
  } else {
    btn.disabled = false;
    btn.classList.remove('opacity-75', 'cursor-not-allowed');
    if (btn.dataset.originalHtml) {
      btn.innerHTML = btn.dataset.originalHtml;
    }
  }
}

function psaApplyServerErrors(container, errors) {
  if (!errors || typeof errors !== 'object') return;
  const root = container || document;
  const aliasMap = {
    title: ['productName', 'title'],
    titleKhmer: ['khmerName', 'titleKhmer'],
    category: ['categorySelect', 'category'],
    badge: ['stallLocation', 'badge'],
    description: ['productDescription', 'description', 'instructions'],
    priceUSD: ['priceUSD', 'price'],
    stock: ['stockQuantity', 'stock'],
    name: ['userName', 'methodName', 'fName', 'aName', 'name', 'reg_name', 'shipping_name', 'customerName'],
    customerName: ['shipping_name', 'customerName', 'name'],
    email: ['userEmail', 'fEmail', 'email', 'reg_email', 'login'],
    phone: ['userPhone', 'fPhone', 'aPhone', 'shipping_phone', 'phone', 'reg_phone'],
    address: ['shipping_address', 'aLine', 'address'],
    role: ['userRole', 'role'],
    password: ['newPassword', 'pNew', 'reg_password', 'password'],
    current: ['pOld', 'current'],
    type: ['methodType', 'type'],
    accountNumber: ['accountNumber'],
    accountName: ['accountName'],
    paymentMethod: ['paymentMethodContainer', 'payment_method'],
    login: ['email']
  };

  for (const [field, msgs] of Object.entries(errors)) {
    const message = Array.isArray(msgs) ? msgs[0] : msgs;
    const aliases = aliasMap[field] || [];
    const possibleIds = [
      ...aliases,
      field,
      `reg_${field}`,
      `shipping_${field}`,
      `user${field.charAt(0).toUpperCase() + field.slice(1)}`,
      `product${field.charAt(0).toUpperCase() + field.slice(1)}`,
      `method${field.charAt(0).toUpperCase() + field.slice(1)}`,
      field.replace(/([A-Z])/g, '_$1').toLowerCase(),
      `f${field.charAt(0).toUpperCase() + field.slice(1)}`,
      `p${field.charAt(0).toUpperCase() + field.slice(1)}`,
      `a${field.charAt(0).toUpperCase() + field.slice(1)}`
    ];
    let matched = false;
    for (const id of possibleIds) {
      const el = psaGetInputElement(id, root);
      if (el) {
        psaShowFieldError(el, message, root);
        matched = true;
        break;
      }
    }
    if (!matched) {
      const alerts = root.querySelectorAll ? root.querySelectorAll('[role="alert"]') : [];
      let shownInAlert = false;
      for (const alertEl of alerts) {
        if (!alertEl.classList.contains('field-error') && alertEl.offsetParent !== null) {
          alertEl.textContent = message;
          alertEl.classList.remove('hidden');
          shownInAlert = true;
          break;
        }
      }
      if (!shownInAlert) {
        if (typeof showRBACToast === 'function') {
          showRBACToast(message, 'error');
        } else {
          alert(message);
        }
      }
    }
  }
}

// Demo mode (no server) is only for previewing pages on your own computer. On the real site a
// failed connection must show an error, never a pretend login.
function psaIsLocalPreview() {
  return location.protocol === 'file:' || ['localhost', '127.0.0.1', '0.0.0.0'].includes(location.hostname);
}
const PSA_OFFLINE_MESSAGE = 'Could not reach the server. Check your connection and refresh the page, then try again.';

async function psaLogout(redirectTo = 'login.html') {
  try { await psaApi('POST', '/api/auth/logout'); } catch (e) { /* already logged out */ }
  localStorage.removeItem('psa_current_user');
  window.location.replace(redirectTo);
}

// ---------- Navigation helpers ----------
// Redirects use location.replace() so the page you were sent away from is not left in the
// history. Otherwise the browser's Back button returns to it and it redirects again (a loop).

function psaCurrentPage() {
  return psaPageName() + location.search;
}

// Guests are sent to the login page and come back here after signing in.
// Returns true when the visitor may stay on the page.
function psaRequireLogin() {
  if (!PSA.online || PSA.user) return true;
  window.location.replace(`login.html?next=${encodeURIComponent(psaCurrentPage())}`);
  return false;
}

// Go back to the previous page of this site. When there is none (opened from a link,
// bookmark or new tab), go to `fallback` instead, without adding a history entry.
function psaGoBack(fallback = 'home.html') {
  let cameFromThisSite = false;
  try {
    const ref = document.referrer ? new URL(document.referrer) : null;
    cameFromThisSite = !!ref && ref.origin === location.origin && ref.href !== location.href;
  } catch (e) {
    cameFromThisSite = false;
  }
  if (cameFromThisSite && history.length > 1) {
    history.back();
  } else {
    window.location.replace(fallback);
  }
}

// Pages that get a back arrow in the header, and where it goes when there is no previous page.
const PSA_BACK_FALLBACKS = {
  'product-detail.html': 'products.html',
  'cart.html': 'products.html',
  'checkout.html': 'cart.html',
  'wishlist.html': 'home.html',
  'order-detail.html': 'dashboard-buyer.html',
  'order-detail--buyer-pending.html': 'dashboard-buyer.html',
  'login.html': 'home.html',
  'register.html': 'login.html',
  'admin-orders.html': 'dashboard-admin.html',
  'admin-products.html': 'dashboard-admin.html',
  'admin-users.html': 'dashboard-admin.html',
  'admin-payment-methods.html': 'dashboard-admin.html',
  'product-form.html': 'admin-products.html',
  'user-form.html': 'admin-users.html',
  'payment-method-form.html': 'admin-payment-methods.html'
};

function psaAddHeaderBackButton() {
  const fallback = PSA_BACK_FALLBACKS[psaPageName()];
  const logo = document.querySelector('header a');
  if (!fallback || !logo || document.getElementById('psaBackBtn')) return;
  const btn = document.createElement('button');
  btn.type = 'button';
  btn.id = 'psaBackBtn';
  btn.className = 'psa-back-btn';
  btn.setAttribute('aria-label', 'Go back');
  btn.title = 'Back';
  btn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>';
  btn.addEventListener('click', () => psaGoBack(fallback));
  logo.parentNode.insertBefore(btn, logo);
}

// Put a user's profile photo into an avatar circle, or their initials when they have no photo.
function psaFillAvatar(el, user) {
  el.replaceChildren();
  if (typeof user.avatarUrl === 'string' && user.avatarUrl.startsWith('data:image/')) {
    const img = new Image();
    img.src = user.avatarUrl;
    img.alt = '';
    img.className = 'w-full h-full object-cover rounded-full';
    el.classList.add('overflow-hidden');
    el.appendChild(img);
  } else {
    el.textContent = user.avatar || '?';
  }
}

// The header avatar was hard-coded ("SC"): show the signed-in user's photo or initials, or a sign-in icon for guests.
function psaUpdateHeaderAccount() {
  if (!PSA.online) return;
  document.querySelectorAll('header a[href="dashboard-buyer.html"] > div.rounded-full').forEach(avatar => {
    const link = avatar.parentElement;
    if (PSA.user) {
      psaFillAvatar(avatar, PSA.user);
      if (PSA.user.role !== 'Buyer') link.href = 'dashboard-admin.html';
      link.setAttribute('aria-label', `My account (${PSA.user.name})`);
    } else {
      avatar.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>';
      link.href = 'login.html';
      link.setAttribute('aria-label', 'Sign in');
    }
  });
}

document.addEventListener('DOMContentLoaded', () => {
  psaAddHeaderBackButton();
  psaUpdateHeaderAccount();
});

// Escape text before putting it into innerHTML (names, addresses and titles come from other users).
function psaEsc(value) {
  return String(value == null ? '' : value).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

// Shrink a picked image file to a JPEG data URL so it can be stored in the database.
function psaImageToDataUrl(file, maxSize = 900, quality = 0.82) {
  return new Promise((resolve, reject) => {
    if (!file || !/^image\//.test(file.type)) { reject(new Error('Please choose an image file.')); return; }
    const reader = new FileReader();
    reader.onerror = () => reject(new Error('Could not read that file.'));
    reader.onload = () => {
      const img = new Image();
      img.onerror = () => reject(new Error('That image could not be opened.'));
      img.onload = () => {
        const scale = Math.min(1, maxSize / Math.max(img.width, img.height));
        const canvas = document.createElement('canvas');
        canvas.width = Math.round(img.width * scale);
        canvas.height = Math.round(img.height * scale);
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
        resolve(canvas.toDataURL('image/jpeg', quality));
      };
      img.src = reader.result;
    };
    reader.readAsDataURL(file);
  });
}

// Demo catalog for previews without the server. Same data as database/data/catalog*.json.
const ACCESSORIES_PRODUCTS = [
  {
    "id": "genz-01",
    "title": "Oversized Retro Fan Graphic Tee",
    "titleKhmer": "អាវយឺតទ្រង់ធំ ម៉ូដក្រាហ្វិកកង្ហារ Retro",
    "category": "apparel",
    "categoryLabel": "Graphic Tees",
    "priceUSD": 9,
    "priceKHR": 36900,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Trending ⚡",
    "status": "active",
    "image": "/assets/images/products/oversized-retro-fan-graphic-tee.jpg",
    "gallery": [
      "/assets/images/products/oversized-retro-fan-graphic-tee.jpg"
    ],
    "description": "Boxy white cotton tee printed with a playful collection of retro electric fans. Relaxed drop-shoulder fit that works on its own or layered.",
    "specifications": {
      "Material": "100% Combed Cotton",
      "Fit": "Oversized / Unisex",
      "Print": "Retro fan collage",
      "Colour": "White"
    }
  },
  {
    "id": "genz-02",
    "title": "Starry Night Art Print Oversized Tee",
    "titleKhmer": "អាវយឺតទ្រង់ធំ បោះពុម្ពគំនូរ Starry Night",
    "category": "apparel",
    "categoryLabel": "Graphic Tees",
    "priceUSD": 10,
    "priceKHR": 41000,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Art Club 🎨",
    "status": "active",
    "image": "/assets/images/products/starry-night-art-print-oversized-tee.jpg",
    "gallery": [
      "/assets/images/products/starry-night-art-print-oversized-tee.jpg"
    ],
    "description": "Cream oversized tee with a Starry Night museum-poster print on the back. A quiet, gallery-core look for everyday fits.",
    "specifications": {
      "Material": "Heavyweight Cotton",
      "Fit": "Oversized / Unisex",
      "Print": "Back museum-poster graphic",
      "Colour": "Cream"
    }
  },
  {
    "id": "genz-03",
    "title": "Matcha Girl Washed Green Oversized Tee",
    "titleKhmer": "អាវយឺតបៃតងលាងស្អាត Matcha Girl",
    "category": "apparel",
    "categoryLabel": "Graphic Tees",
    "priceUSD": 9.5,
    "priceKHR": 38950,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Matcha Core 🍵",
    "status": "active",
    "image": "/assets/images/products/matcha-girl-washed-green-oversized-tee.jpg",
    "gallery": [
      "/assets/images/products/matcha-girl-washed-green-oversized-tee.jpg"
    ],
    "description": "Vintage-washed forest green tee with a Matcha Girl front graphic. Soft, broken-in feel and a slightly cropped boxy cut.",
    "specifications": {
      "Material": "Washed Cotton",
      "Fit": "Oversized / Boxy",
      "Print": "Matcha cocktail graphic",
      "Colour": "Forest Green"
    }
  },
  {
    "id": "genz-04",
    "title": "Pink Hair Care Essentials Set",
    "titleKhmer": "ឈុតថែរក្សាសក់ពណ៌ផ្កាឈូក",
    "category": "hair",
    "categoryLabel": "Hair Essentials",
    "priceUSD": 8,
    "priceKHR": 32800,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Self-Care 💗",
    "status": "active",
    "image": "/assets/images/products/pink-hair-care-essentials-set.jpg",
    "gallery": [
      "/assets/images/products/pink-hair-care-essentials-set.jpg"
    ],
    "description": "A pastel-pink hair-care flat lay in one set: wide-tooth comb, scalp massager brush and a cream claw clip for your wash-day routine.",
    "specifications": {
      "Includes": "Comb, scalp massager, claw clip",
      "Material": "Acetate and soft silicone",
      "Colour": "Blush Pink and Cream",
      "Use": "Wet and dry hair"
    }
  },
  {
    "id": "genz-05",
    "title": "Rainbow Body Mist Gift Set (6 Pcs)",
    "titleKhmer": "ឈុតទឹកអប់ខ្លួន ឥន្ទធនូ (៦ ដប)",
    "category": "beauty",
    "categoryLabel": "Body Mist",
    "priceUSD": 14,
    "priceKHR": 57400,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Gift Idea 🎁",
    "status": "active",
    "image": "/assets/images/products/rainbow-body-mist-gift-set-6-pcs.jpg",
    "gallery": [
      "/assets/images/products/rainbow-body-mist-gift-set-6-pcs.jpg"
    ],
    "description": "Six travel-size fragrance mists in a rainbow of colours, each with its own sweet scent. Perfect gifting set and easy to toss in a bag.",
    "specifications": {
      "Pack Quantity": "6 mini bottles",
      "Format": "Fine-mist spray",
      "Scent Family": "Fruity / Sweet / Floral",
      "Packaging": "Gift-ready"
    }
  },
  {
    "id": "genz-06",
    "title": "Pink Cherry Scrunchie and Flower Clip Set",
    "titleKhmer": "ឈុតខ្សែចងសក់ Cherry និងដង្កៀបផ្កា",
    "category": "hair",
    "categoryLabel": "Scrunchies and Clips",
    "priceUSD": 5.5,
    "priceKHR": 22550,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Coquette 🎀",
    "status": "active",
    "image": "/assets/images/products/pink-cherry-scrunchie-and-flower-clip-set.jpg",
    "gallery": [
      "/assets/images/products/pink-cherry-scrunchie-and-flower-clip-set.jpg"
    ],
    "description": "Soft pink velvet and cherry-print gingham scrunchies with a pastel flower claw clip. Gentle on hair and very coquette.",
    "specifications": {
      "Includes": "3 scrunchies + 1 flower clip",
      "Material": "Cotton gauze and velvet",
      "Colour": "Pink and White",
      "Vibe": "Soft girl / Coquette"
    }
  },
  {
    "id": "genz-07",
    "title": "Floral Flower Claw Clip Set (5 Pcs)",
    "titleKhmer": "ឈុតដង្កៀបសក់រាងផ្កា បោះពុម្ពលម្អ (៥ គ្រឿង)",
    "category": "hair",
    "categoryLabel": "Claw Clips",
    "priceUSD": 6,
    "priceKHR": 24600,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Best Value",
    "status": "active",
    "image": "/assets/images/products/floral-flower-claw-clip-set-5-pcs.jpg",
    "gallery": [
      "/assets/images/products/floral-flower-claw-clip-set-5-pcs.jpg"
    ],
    "description": "Five glossy flower-shaped claw clips hand-painted with cherries, bows and tiny blossoms. Strong spring grip for thick or fine hair.",
    "specifications": {
      "Pack Quantity": "5 clips",
      "Material": "Acetate",
      "Pattern": "Cherry, bow and floral prints",
      "Grip": "Non-slip spring"
    }
  },
  {
    "id": "genz-08",
    "title": "Pink Plumeria Flower Claw Clip",
    "titleKhmer": "ដង្កៀបសក់ផ្កាចំប៉ីពណ៌ផ្កាឈូក",
    "category": "hair",
    "categoryLabel": "Claw Clips",
    "priceUSD": 2.5,
    "priceKHR": 10250,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Hot Pick 🔥",
    "status": "active",
    "image": "/assets/images/products/pink-plumeria-flower-claw-clip.jpg",
    "gallery": [
      "/assets/images/products/pink-plumeria-flower-claw-clip.jpg"
    ],
    "description": "Translucent pink plumeria claw clip with a deep magenta centre. A fast way to dress up a messy bun.",
    "specifications": {
      "Style": "Plumeria flower",
      "Material": "Acetate",
      "Colour": "Pink and Magenta",
      "Size": "Approx. 8cm"
    }
  },
  {
    "id": "genz-09",
    "title": "Floral Quilted Mini Zip Pouch with Keyring",
    "titleKhmer": "កាបូបតូចដេរក្រណាត់ផ្កា មានខ្សែសោ",
    "category": "bags",
    "categoryLabel": "Mini Pouches",
    "priceUSD": 5.5,
    "priceKHR": 22550,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Cute Find",
    "status": "active",
    "image": "/assets/images/products/floral-quilted-mini-zip-pouch-with-keyring.jpg",
    "gallery": [
      "/assets/images/products/floral-quilted-mini-zip-pouch-with-keyring.jpg"
    ],
    "description": "Pocket-size quilted pouch in a ditsy pink floral print, with a pink zipper and keyring clip. Holds lip balm, earbuds and cards.",
    "specifications": {
      "Material": "Quilted cotton",
      "Closure": "Zip",
      "Attachment": "Metal keyring and chain",
      "Size": "Approx. 12cm x 9cm"
    }
  },
  {
    "id": "genz-10",
    "title": "Pink Bow Print Phone Case",
    "titleKhmer": "ស្រោមទូរស័ព្ទបោះពុម្ពរាងរ៉ូប៉ូពណ៌ផ្កាឈូក",
    "category": "accessories",
    "categoryLabel": "Phone Cases",
    "priceUSD": 6,
    "priceKHR": 24600,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Girly Pick 🎀",
    "status": "active",
    "image": "/assets/images/products/pink-bow-print-phone-case.jpg",
    "gallery": [
      "/assets/images/products/pink-bow-print-phone-case.jpg"
    ],
    "description": "Glossy translucent pink phone case with an all-over bow pattern and a sparkle camera ring. Slim fit with raised edges for protection.",
    "specifications": {
      "Pattern": "Allover bow print",
      "Material": "TPU + PC",
      "Protection": "Raised camera and screen edges",
      "Colour": "Pink"
    }
  },
  {
    "id": "genz-11",
    "title": "Rose Gold Green Dial Chronograph Watch",
    "titleKhmer": "នាឡិកាក្រូណូក្រាហ្វ មុខពណ៌បៃតង ស្ពាន់ផ្កាឈូក",
    "category": "watches",
    "categoryLabel": "Statement Watches",
    "priceUSD": 28,
    "priceKHR": 114800,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Premium ✨",
    "status": "active",
    "image": "/assets/images/products/rose-gold-green-dial-chronograph-watch.jpg",
    "gallery": [
      "/assets/images/products/rose-gold-green-dial-chronograph-watch.jpg"
    ],
    "description": "Rose-gold case and bracelet with a rich green three-subdial face. A dressy statement watch for gifts and special days.",
    "specifications": {
      "Case Material": "Stainless steel, rose-gold tone",
      "Dial": "Green chronograph",
      "Strap": "Link bracelet",
      "Water Resistance": "3 ATM"
    }
  },
  {
    "id": "genz-12",
    "title": "Two-Tone Chronograph Bracelet Watch",
    "titleKhmer": "នាឡិកាក្រូណូក្រាហ្វ ពណ៌ពីរ សង្វាក់ដៃ",
    "category": "watches",
    "categoryLabel": "Statement Watches",
    "priceUSD": 26,
    "priceKHR": 106600,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Classic",
    "status": "active",
    "image": "/assets/images/products/two-tone-chronograph-bracelet-watch.jpg",
    "gallery": [
      "/assets/images/products/two-tone-chronograph-bracelet-watch.jpg"
    ],
    "description": "Silver and rose-gold two-tone bracelet with a dark multi-dial face. Sharp, polished and easy to wear from desk to dinner.",
    "specifications": {
      "Case Material": "Stainless steel",
      "Dial": "Dark grey chronograph",
      "Strap": "Two-tone link bracelet",
      "Water Resistance": "3 ATM"
    }
  },
  {
    "id": "genz-13",
    "title": "Classic Silver Link Bracelet Watch",
    "titleKhmer": "នាឡិកាសង្វាក់ប្រាក់ បែបបុរាណ",
    "category": "watches",
    "categoryLabel": "Everyday Watches",
    "priceUSD": 22,
    "priceKHR": 90200,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Timeless",
    "status": "active",
    "image": "/assets/images/products/classic-silver-link-bracelet-watch.jpg",
    "gallery": [
      "/assets/images/products/classic-silver-link-bracelet-watch.jpg"
    ],
    "description": "Brushed silver bracelet watch with a pale dial and fluted bezel. Clean enough for the office, stylish enough for the weekend.",
    "specifications": {
      "Case Material": "Stainless steel",
      "Dial": "Silver-white",
      "Strap": "Jubilee-style link bracelet",
      "Clasp": "Folding"
    }
  },
  {
    "id": "genz-14",
    "title": "Brown Leather Strap Chronograph Watch",
    "titleKhmer": "នាឡិកាក្រូណូក្រាហ្វ ខ្សែស្បែកពណ៌ត្នោត",
    "category": "watches",
    "categoryLabel": "Everyday Watches",
    "priceUSD": 25,
    "priceKHR": 102500,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Men's Pick",
    "status": "active",
    "image": "/assets/images/products/brown-leather-strap-chronograph-watch.jpg",
    "gallery": [
      "/assets/images/products/brown-leather-strap-chronograph-watch.jpg"
    ],
    "description": "Black dial with roman numerals and three subdials, set in a bronze case on a brown leather strap. Presented in a gift tin.",
    "specifications": {
      "Case Material": "Bronze-tone alloy",
      "Dial": "Black, Roman numerals",
      "Strap": "Genuine leather",
      "Packaging": "Gift tin"
    }
  },
  {
    "id": "genz-15",
    "title": "Sage Green Oxford Button-Down Shirt",
    "titleKhmer": "អាវដៃវែង Oxford ពណ៌បៃតងស្រាល",
    "category": "apparel",
    "categoryLabel": "Men's Shirts",
    "priceUSD": 14,
    "priceKHR": 57400,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Fresh Drop",
    "status": "active",
    "image": "/assets/images/products/sage-green-oxford-button-down-shirt.jpg",
    "gallery": [
      "/assets/images/products/sage-green-oxford-button-down-shirt.jpg"
    ],
    "description": "Soft sage green Oxford shirt with button-down collar. Wear it tucked in with white trousers or open over a tee.",
    "specifications": {
      "Material": "Cotton Oxford",
      "Fit": "Regular",
      "Collar": "Button-down",
      "Colour": "Sage Green"
    }
  },
  {
    "id": "genz-16",
    "title": "Blue Striped Slim-Fit Button-Down Shirt",
    "titleKhmer": "អាវដៃវែងឆ្នូតខៀវ ទម្រង់ Slim",
    "category": "apparel",
    "categoryLabel": "Men's Shirts",
    "priceUSD": 14,
    "priceKHR": 57400,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Smart Casual",
    "status": "active",
    "image": "/assets/images/products/blue-striped-slim-fit-button-down-shirt.jpg",
    "gallery": [
      "/assets/images/products/blue-striped-slim-fit-button-down-shirt.jpg"
    ],
    "description": "Crisp blue-and-white striped shirt with a slim, tailored cut. Roll up the sleeves for easy smart-casual style.",
    "specifications": {
      "Material": "Cotton poplin",
      "Fit": "Slim",
      "Collar": "Button-down",
      "Pattern": "Blue and white stripe"
    }
  },
  {
    "id": "genz-17",
    "title": "Ribbed Knit Contrast Collar Polo",
    "titleKhmer": "អាវពូឡូ ក្រណាត់ត្បាញ ក និងបំពង់ដៃពណ៌ផ្ទុយ",
    "category": "apparel",
    "categoryLabel": "Polos",
    "priceUSD": 16,
    "priceKHR": 65600,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Old Money",
    "status": "active",
    "image": "/assets/images/products/ribbed-knit-contrast-collar-polo.jpg",
    "gallery": [
      "/assets/images/products/ribbed-knit-contrast-collar-polo.jpg"
    ],
    "description": "Cream ribbed knit polo with navy contrast trim on the collar and sleeves. Dressed-up and easy to wear.",
    "specifications": {
      "Material": "Cotton-blend rib knit",
      "Fit": "Regular",
      "Detail": "Navy contrast collar and cuffs",
      "Colour": "Cream"
    }
  },
  {
    "id": "genz-18",
    "title": "Olive Plaid Relaxed Overshirt",
    "titleKhmer": "អាវដៃវែងឆ្នូតការ៉ូ ពណ៌អូលីវ ទ្រង់ធំ",
    "category": "apparel",
    "categoryLabel": "Men's Shirts",
    "priceUSD": 15,
    "priceKHR": 61500,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Streetwear",
    "status": "active",
    "image": "/assets/images/products/olive-plaid-relaxed-overshirt.jpg",
    "gallery": [
      "/assets/images/products/olive-plaid-relaxed-overshirt.jpg"
    ],
    "description": "Relaxed olive and cream plaid shirt with a casual drape. Wear it open as an overshirt or buttoned up.",
    "specifications": {
      "Material": "Cotton blend",
      "Fit": "Relaxed",
      "Pattern": "Olive plaid",
      "Colour": "Olive and Cream"
    }
  },
  {
    "id": "genz-19",
    "title": "Grey Check Oversized Long-Sleeve Shirt",
    "titleKhmer": "អាវដៃវែងការ៉ូប្រផេះ ទ្រង់ធំ",
    "category": "apparel",
    "categoryLabel": "Men's Shirts",
    "priceUSD": 15,
    "priceKHR": 61500,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Gen-Z Fit",
    "status": "active",
    "image": "/assets/images/products/grey-check-oversized-long-sleeve-shirt.jpg",
    "gallery": [
      "/assets/images/products/grey-check-oversized-long-sleeve-shirt.jpg"
    ],
    "description": "Lightweight grey checkered shirt with a chest pocket and an oversized cut. Soft, airy and made for layering.",
    "specifications": {
      "Material": "Textured cotton blend",
      "Fit": "Oversized",
      "Pocket": "Chest patch pocket",
      "Colour": "Grey and White"
    }
  },
  {
    "id": "genz-20",
    "title": "Gingham and Lace Scrunchie Set (4 Pcs)",
    "titleKhmer": "ឈុតខ្សែចងសក់ Gingham និង Lace (៤ ខ្សែ)",
    "category": "hair",
    "categoryLabel": "Scrunchies and Clips",
    "priceUSD": 5,
    "priceKHR": 20500,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Cottagecore 🌼",
    "status": "active",
    "image": "/assets/images/products/gingham-and-lace-scrunchie-set-4-pcs.jpg",
    "gallery": [
      "/assets/images/products/gingham-and-lace-scrunchie-set-4-pcs.jpg"
    ],
    "description": "Four oversized scrunchies in red gingham, blue check, cream knit and floral with lace trim. Gentle hold with no creases.",
    "specifications": {
      "Pack Quantity": "4 scrunchies",
      "Material": "Cotton and lace",
      "Colours": "Red, Blue, Cream, Floral",
      "Hold": "Soft elastic"
    }
  },
  {
    "id": "genz-21",
    "title": "Strawberry Beaded Charm Watch Bracelet",
    "titleKhmer": "កងដៃនាឡិកាអង្កាំ Strawberry",
    "category": "jewelry",
    "categoryLabel": "Charm Jewelry",
    "priceUSD": 9,
    "priceKHR": 36900,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Handmade 🍓",
    "status": "active",
    "image": "/assets/images/products/strawberry-beaded-charm-watch-bracelet.jpg",
    "gallery": [
      "/assets/images/products/strawberry-beaded-charm-watch-bracelet.jpg"
    ],
    "description": "Handmade beaded bracelet with a tiny watch face, glass strawberry, hearts and flowers. Part jewellery, part tiny accessory.",
    "specifications": {
      "Material": "Glass beads and alloy",
      "Closure": "Lobster clasp + extender",
      "Charms": "Strawberry, hearts, flowers",
      "Style": "Cottagecore / Kawaii"
    }
  },
  {
    "id": "genz-22",
    "title": "Strawberry Bow Beaded Bag Charm",
    "titleKhmer": "គ្រឿងលម្អកាបូប អង្កាំ Strawberry រ៉ូប៉ូ",
    "category": "charms",
    "categoryLabel": "Bag Charms",
    "priceUSD": 4.5,
    "priceKHR": 18450,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Kawaii 🍓",
    "status": "active",
    "image": "/assets/images/products/strawberry-bow-beaded-bag-charm.jpg",
    "gallery": [
      "/assets/images/products/strawberry-bow-beaded-bag-charm.jpg"
    ],
    "description": "Dangling bag charm with a ribbon bow, pearl heart, strawberry and a tiny critter figure. Clips on to bags, keys or lip balm.",
    "specifications": {
      "Charms": "Bow, heart, strawberry, mini figure",
      "Attachment": "Swivel clip + ball chain",
      "Material": "Resin and acrylic",
      "Length": "Approx. 14cm"
    }
  },
  {
    "id": "genz-23",
    "title": "White Floppy-Ear Plush Bag Charm",
    "titleKhmer": "គ្រឿងលម្អកាបូប តុក្កតាឆ្កែត្រចៀកធ្លាក់ ពណ៌ស",
    "category": "charms",
    "categoryLabel": "Plush Charms",
    "priceUSD": 5.5,
    "priceKHR": 22550,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Bestseller",
    "status": "active",
    "image": "/assets/images/products/white-floppy-ear-plush-bag-charm.jpg",
    "gallery": [
      "/assets/images/products/white-floppy-ear-plush-bag-charm.jpg"
    ],
    "description": "Soft white plush with long floppy ears, paired with a heart carabiner, star and 8-ball charm. Clip it on a bag zipper or backpack.",
    "specifications": {
      "Material": "Plush and metal",
      "Attachment": "Heart carabiner + key ring",
      "Extras": "Star and 8-ball charm",
      "Height": "Approx. 12cm"
    }
  },
  {
    "id": "genz-24",
    "title": "Kawaii Bunny Plush Bag Charm Duo",
    "titleKhmer": "គ្រឿងលម្អកាបូប តុក្កតាទន្សាយ (២ ក្បាល)",
    "category": "charms",
    "categoryLabel": "Plush Charms",
    "priceUSD": 6,
    "priceKHR": 24600,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Viral 🐰",
    "status": "active",
    "image": "/assets/images/products/kawaii-bunny-plush-bag-charm-duo.jpg",
    "gallery": [
      "/assets/images/products/kawaii-bunny-plush-bag-charm-duo.jpg"
    ],
    "description": "Two squishy bunny plush charms with blushing cheeks on ball chains. Hang them on a handbag, tote or backpack.",
    "specifications": {
      "Pack Quantity": "2 plush charms",
      "Material": "Plush and PP cotton",
      "Attachment": "Ball chain",
      "Height": "Approx. 10cm"
    }
  },
  {
    "id": "genz-25",
    "title": "Plush Bunny Blue Rose Bouquet Gift Set",
    "titleKhmer": "ឈុតកាដូ ផ្កាកុលាបខៀវ និងតុក្កតាទន្សាយ",
    "category": "gifts",
    "categoryLabel": "Gift Sets",
    "priceUSD": 22,
    "priceKHR": 90200,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Gift Idea 🎁",
    "status": "active",
    "image": "/assets/images/products/plush-bunny-blue-rose-bouquet-gift-set.jpg",
    "gallery": [
      "/assets/images/products/plush-bunny-blue-rose-bouquet-gift-set.jpg"
    ],
    "description": "Gift bouquet with a plush bunny nested in blue and white blooms, wrapped in light blue paper and ribbon. Ideal for birthdays and graduations.",
    "specifications": {
      "Includes": "Plush bunny, flowers, wrapping",
      "Colour Theme": "Blue and White",
      "Wrapping": "Paper and satin ribbon",
      "Occasion": "Birthday, graduation, anniversary"
    }
  },
  {
    "id": "genz-26",
    "title": "Vintage Floral Ribbon Newsboy Cap",
    "titleKhmer": "មួក Newsboy បែបវីនធេច ជាប់ខ្សែរ៉ូប៉ូផ្កា",
    "category": "hats",
    "categoryLabel": "Caps and Hats",
    "priceUSD": 11,
    "priceKHR": 45100,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Vintage Vibes",
    "status": "active",
    "image": "/assets/images/products/vintage-floral-ribbon-newsboy-cap.jpg",
    "gallery": [
      "/assets/images/products/vintage-floral-ribbon-newsboy-cap.jpg"
    ],
    "description": "Soft olive-brown newsboy cap with a floral ribbon band and a short peak. A relaxed vintage finish for any outfit.",
    "specifications": {
      "Material": "Cotton twill",
      "Style": "Newsboy / Baker boy",
      "Detail": "Floral ribbon band",
      "Fit": "Adjustable inner band"
    }
  },
  {
    "id": "genz-27",
    "title": "Embroidered Bow Crew Socks (2 Pairs)",
    "titleKhmer": "ស្រោមជើងកវែង ប៉ាក់រ៉ូប៉ូ (២ គូរ)",
    "category": "socks",
    "categoryLabel": "Socks",
    "priceUSD": 4,
    "priceKHR": 16400,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Coquette 🎀",
    "status": "active",
    "image": "/assets/images/products/embroidered-bow-crew-socks-2-pairs.jpg",
    "gallery": [
      "/assets/images/products/embroidered-bow-crew-socks-2-pairs.jpg"
    ],
    "description": "Cream and burgundy ribbed crew socks with a tiny embroidered bow. Soft, stretchy and made for loafers and sneakers.",
    "specifications": {
      "Pack Quantity": "2 pairs",
      "Material": "Cotton blend",
      "Length": "Crew",
      "Colours": "Cream and Burgundy"
    }
  },
  {
    "id": "genz-28",
    "title": "Cartoon Face Crew Socks Set (5 Pairs)",
    "titleKhmer": "ឈុតស្រោមជើងមុខតុក្កតា (៥ គូរ)",
    "category": "socks",
    "categoryLabel": "Socks",
    "priceUSD": 8,
    "priceKHR": 32800,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Fun Pick",
    "status": "active",
    "image": "/assets/images/products/cartoon-face-crew-socks-set-5-pairs.jpg",
    "gallery": [
      "/assets/images/products/cartoon-face-crew-socks-set-5-pairs.jpg"
    ],
    "description": "Five bold crew socks in pastel colours, each with a goofy cartoon face. A fun gift for friends.",
    "specifications": {
      "Pack Quantity": "5 pairs",
      "Material": "Cotton blend",
      "Length": "Crew",
      "Colours": "Purple, Mint, Orange, Lime, Yellow"
    }
  },
  {
    "id": "genz-29",
    "title": "Glossy Brown Leather Shoulder Bag",
    "titleKhmer": "កាបូបស្ពាយស្បែកពណ៌ត្នោតភ្លឺ",
    "category": "bags",
    "categoryLabel": "Shoulder Bags",
    "priceUSD": 24,
    "priceKHR": 98400,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Y2K Classic",
    "status": "active",
    "image": "/assets/images/products/glossy-brown-leather-shoulder-bag.jpg",
    "gallery": [
      "/assets/images/products/glossy-brown-leather-shoulder-bag.jpg"
    ],
    "description": "Rich glossy brown shoulder bag with double top handles and a roomy interior. A true Y2K silhouette for your bag charms.",
    "specifications": {
      "Material": "Glossy PU leather",
      "Handles": "Double top handle",
      "Closure": "Zip",
      "Colour": "Chocolate Brown"
    }
  },
  {
    "id": "genz-30",
    "title": "Mini Vintage Brown Crossbody Bag",
    "titleKhmer": "កាបូបស្ពាយឆៀងតូច ពណ៌ត្នោតបែបវីនធេច",
    "category": "bags",
    "categoryLabel": "Crossbody Bags",
    "priceUSD": 20,
    "priceKHR": 82000,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Trending ⚡",
    "status": "active",
    "image": "/assets/images/products/mini-vintage-brown-crossbody-bag.jpg",
    "gallery": [
      "/assets/images/products/mini-vintage-brown-crossbody-bag.jpg"
    ],
    "description": "Compact brown crossbody with a curved flap and adjustable strap. It fits phone, wallet and earbuds, and looks great with a clip on the strap.",
    "specifications": {
      "Material": "PU leather",
      "Strap": "Adjustable crossbody",
      "Closure": "Magnetic flap",
      "Colour": "Brown"
    }
  },
  {
    "id": "genz-31",
    "title": "Brown Leather Shoulder Bag with Lace Ribbon",
    "titleKhmer": "កាបូបស្ពាយស្បែកពណ៌ត្នោត ជាប់ខ្សែរបាំង",
    "category": "bags",
    "categoryLabel": "Shoulder Bags",
    "priceUSD": 26,
    "priceKHR": 106600,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Cottagecore 🌼",
    "status": "active",
    "image": "/assets/images/products/brown-leather-shoulder-bag-with-lace-ribbon.jpg",
    "gallery": [
      "/assets/images/products/brown-leather-shoulder-bag-with-lace-ribbon.jpg"
    ],
    "description": "Structured brown shoulder bag with buckle detail, tied with a lace ribbon on the strap. Pair with a plush charm to finish the look.",
    "specifications": {
      "Material": "PU leather",
      "Details": "Buckle front, lace ribbon",
      "Closure": "Zip",
      "Colour": "Cognac Brown"
    }
  },
  {
    "id": "genz-32",
    "title": "Beaded Bow Phone Charm Strap",
    "titleKhmer": "ខ្សែអង្កាំទូរស័ព្ទ រាងរ៉ូប៉ូ",
    "category": "charms",
    "categoryLabel": "Phone Charms",
    "priceUSD": 4.5,
    "priceKHR": 18450,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Gen-Z Pick",
    "status": "active",
    "image": "/assets/images/products/beaded-bow-phone-charm-strap.jpg",
    "gallery": [
      "/assets/images/products/beaded-bow-phone-charm-strap.jpg"
    ],
    "description": "Pastel beaded phone strap tied into a bow, with pearl and heart beads. Also works on tumblers, keys and bags.",
    "specifications": {
      "Material": "Acrylic and glass beads",
      "Attachment": "Phone lanyard loop",
      "Style": "Pastel / Coquette",
      "Length": "Approx. 18cm"
    }
  },
  {
    "id": "genz-33",
    "title": "Crochet Strawberry Hat Bunny Plush",
    "titleKhmer": "តុក្កតាទន្សាយក្រូសេពាក់មួកស្ត្របឺរី",
    "category": "plush",
    "categoryLabel": "Plush & Crochet",
    "priceUSD": 12,
    "priceKHR": 49200,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Crochet Cutie",
    "status": "active",
    "image": "/assets/images/products/crochet-strawberry-hat-bunny-plush.jpg",
    "gallery": [
      "/assets/images/products/crochet-strawberry-hat-bunny-plush.jpg"
    ],
    "description": "A floppy-eared crochet bunny in pink overalls and a little strawberry hat. Soft, squishy and made to be hugged.",
    "specifications": {
      "Material": "Soft chenille yarn",
      "Size": "About 25 cm",
      "Colour": "Cream & pink"
    }
  },
  {
    "id": "genz-34",
    "title": "Strawberry Ear Bunny Plush with Berry Friend",
    "titleKhmer": "តុក្កតាទន្សាយត្រចៀកស្ត្របឺរី",
    "category": "plush",
    "categoryLabel": "Plush & Crochet",
    "priceUSD": 15,
    "priceKHR": 61500,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Gift Idea 🎁",
    "status": "active",
    "image": "/assets/images/products/strawberry-ear-bunny-plush.jpg",
    "gallery": [
      "/assets/images/products/strawberry-ear-bunny-plush.jpg"
    ],
    "description": "A fluffy white bunny with strawberry-print ears and feet, plus a tiny strawberry plush buddy. A sweet birthday surprise.",
    "specifications": {
      "Material": "Super-soft plush",
      "Size": "About 30 cm",
      "Includes": "Bunny + strawberry plush"
    }
  },
  {
    "id": "genz-35",
    "title": "Pink Tulip Bunny Bouquet",
    "titleKhmer": "ភួងផ្កាទុយលីបនិងទន្សាយពណ៌ផ្កាឈូក",
    "category": "gifts",
    "categoryLabel": "Gift Sets",
    "priceUSD": 25,
    "priceKHR": 102500,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Gift Idea 🎁",
    "status": "active",
    "image": "/assets/images/products/pink-tulip-bunny-bouquet.jpg",
    "gallery": [
      "/assets/images/products/pink-tulip-bunny-bouquet.jpg"
    ],
    "description": "Pink tulips and baby's breath tucked around little bunny figures, wrapped in soft pink paper with a satin bow.",
    "specifications": {
      "Flowers": "Tulips & baby's breath",
      "Wrap": "Pink paper, satin bow",
      "Occasion": "Birthdays, anniversaries"
    }
  },
  {
    "id": "genz-36",
    "title": "Fawn & Roses Garden Bouquet",
    "titleKhmer": "ភួងផ្កាកុលាបនិងកូនក្តាន់",
    "category": "gifts",
    "categoryLabel": "Gift Sets",
    "priceUSD": 25,
    "priceKHR": 102500,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Cottagecore 🌼",
    "status": "active",
    "image": "/assets/images/products/fawn-and-roses-garden-bouquet.jpg",
    "gallery": [
      "/assets/images/products/fawn-and-roses-garden-bouquet.jpg"
    ],
    "description": "Cream roses and fresh greenery with tiny fawn figures peeking out, wrapped in striped pink paper.",
    "specifications": {
      "Flowers": "Roses & mixed greens",
      "Wrap": "Striped pink paper",
      "Occasion": "Any sweet surprise"
    }
  },
  {
    "id": "genz-37",
    "title": "Pink Floral Digital Camera with Charm Strap",
    "titleKhmer": "កាមេរ៉ាឌីជីថលពណ៌ផ្កាឈូកជាមួយខ្សែអង្កាំ",
    "category": "accessories",
    "categoryLabel": "Phone & Tech",
    "priceUSD": 45,
    "priceKHR": 184500,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Y2K Classic",
    "status": "active",
    "image": "/assets/images/products/pink-floral-digital-camera-with-charm-strap.jpg",
    "gallery": [
      "/assets/images/products/pink-floral-digital-camera-with-charm-strap.jpg"
    ],
    "description": "A compact pink digital camera decorated with flower stickers and a beaded charm strap, for dreamy Y2K-style photos.",
    "specifications": {
      "Type": "Compact digital camera",
      "Includes": "Beaded wrist strap",
      "Colour": "Pink"
    }
  },
  {
    "id": "genz-38",
    "title": "Bunny & Swiss Roll Bag Charm Set",
    "titleKhmer": "ឈុតព្យួរកាបូបទន្សាយនិងនំរមូរ",
    "category": "charms",
    "categoryLabel": "Bag & Phone Charms",
    "priceUSD": 7,
    "priceKHR": 28700,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Kawaii 🍓",
    "status": "active",
    "image": "/assets/images/products/bunny-and-swiss-roll-bag-charm-set.jpg",
    "gallery": [
      "/assets/images/products/bunny-and-swiss-roll-bag-charm-set.jpg"
    ],
    "description": "A cluster of bag charms: two little bunnies, a crochet Swiss roll, pearls and a cream ribbon bow.",
    "specifications": {
      "Includes": "2 bunnies, cake roll, bead strands",
      "Clip": "Lobster clasp",
      "Length": "About 15 cm"
    }
  },
  {
    "id": "genz-39",
    "title": "Strawberry Pound Cake Body Mist Trio",
    "titleKhmer": "ឈុតទឹកអប់ខ្លួនក្លិនស្ត្របឺរី (៣ ដប)",
    "category": "beauty",
    "categoryLabel": "Beauty",
    "priceUSD": 16,
    "priceKHR": 65600,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Sweet Treat",
    "status": "active",
    "image": "/assets/images/products/strawberry-pound-cake-body-mist-trio.jpg",
    "gallery": [
      "/assets/images/products/strawberry-pound-cake-body-mist-trio.jpg"
    ],
    "description": "Three sweet strawberry-and-vanilla scents: a body mist, a mini hand gel and a candy-striped spray.",
    "specifications": {
      "Scent": "Strawberry pound cake",
      "Includes": "3 pieces",
      "Size": "Full-size mist + minis"
    }
  },
  {
    "id": "genz-40",
    "title": "Burgundy Patent Bow Ballet Flats",
    "titleKhmer": "ស្បែកជើងបាឡេពណ៌ក្រហមចាស់មានបូ",
    "category": "shoes",
    "categoryLabel": "Shoes",
    "priceUSD": 18,
    "priceKHR": 73800,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Coquette 🎀",
    "status": "active",
    "image": "/assets/images/products/burgundy-patent-bow-ballet-flats.jpg",
    "gallery": [
      "/assets/images/products/burgundy-patent-bow-ballet-flats.jpg"
    ],
    "description": "Glossy burgundy ballet flats with a tiny bow on the toe. Easy to dress up or wear every day.",
    "specifications": {
      "Material": "Patent faux leather",
      "Sizes": "35–40",
      "Colour": "Burgundy"
    }
  },
  {
    "id": "genz-41",
    "title": "Clay Heart & Flower Phone Straps",
    "titleKhmer": "ខ្សែព្យួរទូរស័ព្ទអង្កាំដីឥដ្ឋបេះដូងនិងផ្កា",
    "category": "charms",
    "categoryLabel": "Bag & Phone Charms",
    "priceUSD": 5,
    "priceKHR": 20500,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Gen-Z Pick",
    "status": "active",
    "image": "/assets/images/products/clay-heart-and-flower-phone-straps.jpg",
    "gallery": [
      "/assets/images/products/clay-heart-and-flower-phone-straps.jpg"
    ],
    "description": "Pink cord phone straps strung with chunky clay hearts, flowers and beads. Pick your favourite style.",
    "specifications": {
      "Material": "Polymer clay beads, cord",
      "Length": "About 18 cm",
      "Styles": "Assorted"
    }
  },
  {
    "id": "genz-42",
    "title": "Brown Suede Shoulder Bag with Bunny Charm",
    "titleKhmer": "កាបូបស្ពាយស្មាពណ៌ត្នោតជាមួយទន្សាយ",
    "category": "bags",
    "categoryLabel": "Shoulder Bags",
    "priceUSD": 26,
    "priceKHR": 106600,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Cottagecore 🌼",
    "status": "active",
    "image": "/assets/images/products/brown-suede-shoulder-bag-with-bunny-charm.jpg",
    "gallery": [
      "/assets/images/products/brown-suede-shoulder-bag-with-bunny-charm.jpg"
    ],
    "description": "A roomy brown suede-look shoulder bag, styled with a fluffy bunny charm, chains and a pink ribbon.",
    "specifications": {
      "Material": "Faux suede",
      "Size": "About 38 × 26 cm",
      "Includes": "Bunny charm"
    }
  },
  {
    "id": "genz-43",
    "title": "NIVEA MEN Maximum Hydration Face Wash",
    "titleKhmer": "ជែលលាងមុខបុរស NIVEA MEN",
    "category": "beauty",
    "categoryLabel": "Beauty",
    "priceUSD": 5,
    "priceKHR": 20500,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Self-Care 💗",
    "status": "active",
    "image": "/assets/images/products/nivea-men-maximum-hydration-face-wash.jpg",
    "gallery": [
      "/assets/images/products/nivea-men-maximum-hydration-face-wash.jpg"
    ],
    "description": "A refreshing daily face wash with aloe vera that cleans without drying your skin.",
    "specifications": {
      "Size": "150 ml",
      "Key": "Aloe vera",
      "Skin": "All skin types"
    }
  },
  {
    "id": "genz-44",
    "title": "Versace Eros Eau de Toilette",
    "titleKhmer": "ទឹកអប់បុរស Versace Eros",
    "category": "beauty",
    "categoryLabel": "Beauty",
    "priceUSD": 78,
    "priceKHR": 319800,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Premium ✨",
    "status": "active",
    "image": "/assets/images/products/versace-eros-eau-de-toilette.jpg",
    "gallery": [
      "/assets/images/products/versace-eros-eau-de-toilette.jpg"
    ],
    "description": "A fresh, sweet men's fragrance with mint, green apple and vanilla notes in the iconic blue bottle.",
    "specifications": {
      "Size": "100 ml",
      "Type": "Eau de Toilette",
      "Notes": "Mint, apple, vanilla"
    }
  },
  {
    "id": "genz-45",
    "title": "Les Desserts Strawberry Graphic Tee",
    "titleKhmer": "អាវយឺតបោះពុម្ពស្ត្របឺរី Les Desserts",
    "category": "apparel",
    "categoryLabel": "Graphic Tees",
    "priceUSD": 10,
    "priceKHR": 41000,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Matcha Core 🍵",
    "status": "active",
    "image": "/assets/images/products/les-desserts-strawberry-graphic-tee.jpg",
    "gallery": [
      "/assets/images/products/les-desserts-strawberry-graphic-tee.jpg"
    ],
    "description": "A soft grey tee printed with a bear, strawberries and \"Les Desserts\" lettering. Cute with jeans or a skirt.",
    "specifications": {
      "Material": "Cotton blend",
      "Fit": "Relaxed",
      "Colour": "Heather grey"
    }
  },
  {
    "id": "genz-46",
    "title": "Pastel Pink Two-Piece Suit",
    "titleKhmer": "ឈុតអាវធំពណ៌ផ្កាឈូក",
    "category": "apparel",
    "categoryLabel": "Suits",
    "priceUSD": 48,
    "priceKHR": 196800,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Statement Fit",
    "status": "active",
    "image": "/assets/images/products/pastel-pink-two-piece-suit.jpg",
    "gallery": [
      "/assets/images/products/pastel-pink-two-piece-suit.jpg"
    ],
    "description": "A bold pastel pink blazer and trousers set for parties, weddings and anyone who loves to stand out.",
    "specifications": {
      "Includes": "Blazer + trousers",
      "Fit": "Regular",
      "Colour": "Pastel pink"
    }
  },
  {
    "id": "genz-47",
    "title": "Grey Striped Relaxed Shirt",
    "titleKhmer": "អាវឆ្នូតដៃខ្លី",
    "category": "apparel",
    "categoryLabel": "Men's Shirts",
    "priceUSD": 14,
    "priceKHR": 57400,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Streetwear",
    "status": "active",
    "image": "/assets/images/products/grey-striped-relaxed-shirt.jpg",
    "gallery": [
      "/assets/images/products/grey-striped-relaxed-shirt.jpg"
    ],
    "description": "An easy short-sleeve shirt in grey and white stripes. Wear it open over a tee or buttoned with wide trousers.",
    "specifications": {
      "Material": "Cotton blend",
      "Fit": "Relaxed",
      "Colour": "Grey & white"
    }
  },
  {
    "id": "genz-48",
    "title": "Spider Hero Knit Balaclava Beanie",
    "titleKhmer": "មួកក្រណាត់ Spider-Man",
    "category": "hats",
    "categoryLabel": "Caps & Hats",
    "priceUSD": 9,
    "priceKHR": 36900,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Spidey Fan",
    "status": "active",
    "image": "/assets/images/products/spider-hero-knit-balaclava-beanie.jpg",
    "gallery": [
      "/assets/images/products/spider-hero-knit-balaclava-beanie.jpg"
    ],
    "description": "A warm red knit beanie with the classic spider-hero eyes. Fun for fans and chilly nights.",
    "specifications": {
      "Material": "Acrylic knit",
      "Size": "One size",
      "Colour": "Red"
    }
  },
  {
    "id": "genz-49",
    "title": "LEGO Marvel Spider-Verse Keychain Duo",
    "titleKhmer": "ខ្សែសោ LEGO Marvel (២)",
    "category": "charms",
    "categoryLabel": "Bag & Phone Charms",
    "priceUSD": 12,
    "priceKHR": 49200,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Spidey Fan",
    "status": "active",
    "image": "/assets/images/products/lego-marvel-spider-verse-keychain-duo.jpg",
    "gallery": [
      "/assets/images/products/lego-marvel-spider-verse-keychain-duo.jpg"
    ],
    "description": "Two minifigure keychains: Ghost-Spider and Miles Morales. Clip them on your keys or bag.",
    "specifications": {
      "Includes": "2 keychains",
      "Ages": "6+",
      "Material": "Plastic, metal ring"
    }
  },
  {
    "id": "genz-50",
    "title": "Navy Canvas Backpack with Spidey Patches",
    "titleKhmer": "កាបូបស្ពាយក្រោយពណ៌ខៀវចាស់",
    "category": "bags",
    "categoryLabel": "Backpacks",
    "priceUSD": 28,
    "priceKHR": 114800,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Spidey Fan",
    "status": "active",
    "image": "/assets/images/products/navy-canvas-backpack-with-spidey-patches.jpg",
    "gallery": [
      "/assets/images/products/navy-canvas-backpack-with-spidey-patches.jpg"
    ],
    "description": "A sturdy navy backpack with tan straps, decorated with spider-hero patches and pins. Fits a laptop and books.",
    "specifications": {
      "Material": "Canvas",
      "Fits": "15-inch laptop",
      "Colour": "Navy & tan"
    }
  },
  {
    "id": "genz-51",
    "title": "Spider & Ghost-Spider Couple Necklace Set",
    "titleKhmer": "ខ្សែកគូស្នេហ៍ Spider",
    "category": "jewelry",
    "categoryLabel": "Charm Jewelry",
    "priceUSD": 8,
    "priceKHR": 32800,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Couple Pick 💞",
    "status": "active",
    "image": "/assets/images/products/spider-couple-necklace-set.jpg",
    "gallery": [
      "/assets/images/products/spider-couple-necklace-set.jpg"
    ],
    "description": "Two silver-tone chain necklaces with red and white spider-hero pendants: one for you, one for your person.",
    "specifications": {
      "Includes": "2 necklaces",
      "Chain": "About 45 cm",
      "Material": "Alloy"
    }
  },
  {
    "id": "genz-52",
    "title": "Spider Hero Beaded Couple Bracelets",
    "titleKhmer": "ខ្សែដៃអង្កាំគូស្នេហ៍ Spider",
    "category": "jewelry",
    "categoryLabel": "Charm Jewelry",
    "priceUSD": 6,
    "priceKHR": 24600,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Couple Pick 💞",
    "status": "active",
    "image": "/assets/images/products/spider-hero-beaded-couple-bracelets.jpg",
    "gallery": [
      "/assets/images/products/spider-hero-beaded-couple-bracelets.jpg"
    ],
    "description": "A matching pair of stretchy bead bracelets in red-black and pink-white with spider-hero charms.",
    "specifications": {
      "Includes": "2 bracelets",
      "Fit": "Stretch, one size",
      "Material": "Glass beads"
    }
  },
  {
    "id": "genz-53",
    "title": "Spider-Man Mask AirPods Case",
    "titleKhmer": "ស្រោម AirPods Spider-Man",
    "category": "accessories",
    "categoryLabel": "Phone & Tech",
    "priceUSD": 7,
    "priceKHR": 28700,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Spidey Fan",
    "status": "active",
    "image": "/assets/images/products/spider-man-mask-airpods-case.jpg",
    "gallery": [
      "/assets/images/products/spider-man-mask-airpods-case.jpg"
    ],
    "description": "A glossy red AirPods case shaped like the spider-hero mask, with a keyring loop.",
    "specifications": {
      "Fits": "AirPods 3 / Pro",
      "Material": "Hard shell",
      "Colour": "Red"
    }
  },
  {
    "id": "genz-54",
    "title": "Washed Red Spider Web Baseball Cap",
    "titleKhmer": "មួកបេស្បលពណ៌ក្រហមលាងសាប៊ូ",
    "category": "hats",
    "categoryLabel": "Caps and Hats",
    "priceUSD": 11,
    "priceKHR": 45100,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Vintage Vibes",
    "status": "active",
    "image": "/assets/images/products/washed-red-spider-web-baseball-cap.jpg",
    "gallery": [
      "/assets/images/products/washed-red-spider-web-baseball-cap.jpg"
    ],
    "description": "A faded red cap with a stitched spider web, an embroidered spider and a worn-in vintage look.",
    "specifications": {
      "Material": "Washed cotton",
      "Fit": "Adjustable strap",
      "Colour": "Washed red"
    }
  },
  {
    "id": "genz-55",
    "title": "Spider-Man Racing Bomber Jacket",
    "titleKhmer": "អាវក្រៅប្រណាំង Spider-Man",
    "category": "apparel",
    "categoryLabel": "Jackets",
    "priceUSD": 35,
    "priceKHR": 143500,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Streetwear",
    "status": "active",
    "image": "/assets/images/products/spider-man-racing-bomber-jacket.jpg",
    "gallery": [
      "/assets/images/products/spider-man-racing-bomber-jacket.jpg"
    ],
    "description": "A navy and red racing-style jacket covered in spider-hero patches and checkered stripes.",
    "specifications": {
      "Material": "Polyester shell",
      "Fit": "Oversized",
      "Colour": "Navy & red"
    }
  },
  {
    "id": "genz-56",
    "title": "Crochet Ghost-Spider Eyes Beanie",
    "titleKhmer": "មួកក្រូសេភ្នែក Ghost-Spider",
    "category": "hats",
    "categoryLabel": "Caps & Hats",
    "priceUSD": 13,
    "priceKHR": 53300,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Crochet Cutie",
    "status": "active",
    "image": "/assets/images/products/crochet-ghost-spider-eyes-beanie.jpg",
    "gallery": [
      "/assets/images/products/crochet-ghost-spider-eyes-beanie.jpg"
    ],
    "description": "A chunky cream crochet beanie with pink Ghost-Spider eyes. Cosy and cute.",
    "specifications": {
      "Material": "Acrylic yarn",
      "Size": "One size",
      "Colour": "Cream & pink"
    }
  },
  {
    "id": "genz-57",
    "title": "Crochet Spider-Verse Doll Duo",
    "titleKhmer": "តុក្កតាក្រូសេ Spider-Verse (២)",
    "category": "plush",
    "categoryLabel": "Plush & Crochet",
    "priceUSD": 18,
    "priceKHR": 73800,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Spidey Fan",
    "status": "active",
    "image": "/assets/images/products/crochet-spider-verse-doll-duo.jpg",
    "gallery": [
      "/assets/images/products/crochet-spider-verse-doll-duo.jpg"
    ],
    "description": "Two little crochet dolls: Ghost-Spider in white and pink, and Miles Morales in black and red.",
    "specifications": {
      "Includes": "2 dolls",
      "Size": "About 15 cm each",
      "Material": "Cotton yarn"
    }
  },
  {
    "id": "genz-58",
    "title": "Crochet Bow Bunny Holding a Heart",
    "titleKhmer": "តុក្កតាទន្សាយក្រូសេកាន់បេះដូង",
    "category": "plush",
    "categoryLabel": "Plush & Crochet",
    "priceUSD": 12,
    "priceKHR": 49200,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Gift Idea 🎁",
    "status": "active",
    "image": "/assets/images/products/crochet-bow-bunny-holding-a-heart.jpg",
    "gallery": [
      "/assets/images/products/crochet-bow-bunny-holding-a-heart.jpg"
    ],
    "description": "A cream crochet bunny with pink bows on its ears, hugging a little pink heart. Made for saying \"I love you\".",
    "specifications": {
      "Material": "Soft yarn",
      "Size": "About 20 cm",
      "Colour": "Cream & pink"
    }
  },
  {
    "id": "genz-59",
    "title": "Crochet Penguin in Blue Beanie & Scarf",
    "titleKhmer": "តុក្កតាភេនឃ្វីនក្រូសេពាក់មួក",
    "category": "plush",
    "categoryLabel": "Plush & Crochet",
    "priceUSD": 11,
    "priceKHR": 45100,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Crochet Cutie",
    "status": "active",
    "image": "/assets/images/products/crochet-penguin-in-blue-beanie.jpg",
    "gallery": [
      "/assets/images/products/crochet-penguin-in-blue-beanie.jpg"
    ],
    "description": "A chubby crochet penguin bundled up in a blue beanie and scarf, with rosy cheeks.",
    "specifications": {
      "Material": "Cotton yarn",
      "Size": "About 12 cm",
      "Colour": "Grey, white & blue"
    }
  },
  {
    "id": "genz-60",
    "title": "Cherry Red Patent Platform Mary Janes",
    "titleKhmer": "ស្បែកជើងកែងក្រាស់ពណ៌ក្រហមភ្លឺ",
    "category": "shoes",
    "categoryLabel": "Shoes",
    "priceUSD": 28,
    "priceKHR": 114800,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Y2K Classic",
    "status": "active",
    "image": "/assets/images/products/cherry-red-patent-platform-mary-janes.jpg",
    "gallery": [
      "/assets/images/products/cherry-red-patent-platform-mary-janes.jpg"
    ],
    "description": "Shiny cherry-red Mary Janes on a chunky platform with a buckle strap. A bold, retro statement.",
    "specifications": {
      "Material": "Patent faux leather",
      "Heel": "About 8 cm platform",
      "Sizes": "35–40"
    }
  },
  {
    "id": "genz-61",
    "title": "Brown Faux-Leather Slouch Boots",
    "titleKhmer": "ស្បែកជើងកវែងពណ៌ត្នោត",
    "category": "shoes",
    "categoryLabel": "Shoes",
    "priceUSD": 32,
    "priceKHR": 131200,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Vintage Vibes",
    "status": "active",
    "image": "/assets/images/products/brown-faux-leather-slouch-boots.jpg",
    "gallery": [
      "/assets/images/products/brown-faux-leather-slouch-boots.jpg"
    ],
    "description": "Knee-high slouchy boots in rich brown faux leather with buckle details. Great with skirts or jeans.",
    "specifications": {
      "Material": "Faux leather",
      "Height": "Knee-high",
      "Sizes": "35–40"
    }
  },
  {
    "id": "genz-62",
    "title": "Cream Bow Block-Heel Pumps",
    "titleKhmer": "ស្បែកជើងកែងពណ៌ក្រែមមានបូ",
    "category": "shoes",
    "categoryLabel": "Shoes",
    "priceUSD": 22,
    "priceKHR": 90200,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Coquette 🎀",
    "status": "active",
    "image": "/assets/images/products/cream-bow-block-heel-pumps.jpg",
    "gallery": [
      "/assets/images/products/cream-bow-block-heel-pumps.jpg"
    ],
    "description": "Soft cream pumps with a low block heel and a delicate bow. Comfortable and elegant.",
    "specifications": {
      "Material": "Faux leather",
      "Heel": "About 4 cm block",
      "Sizes": "35–40"
    }
  },
  {
    "id": "genz-63",
    "title": "Pastel Star & Snap Hair Clip Set",
    "titleKhmer": "ឈុតដង្កៀបសក់ផ្កាយ",
    "category": "hair",
    "categoryLabel": "Scrunchies and Clips",
    "priceUSD": 5,
    "priceKHR": 20500,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Gen-Z Pick",
    "status": "active",
    "image": "/assets/images/products/pastel-star-and-snap-hair-clip-set.jpg",
    "gallery": [
      "/assets/images/products/pastel-star-and-snap-hair-clip-set.jpg"
    ],
    "description": "A playful mix of pastel snap clips, pink star clips and a silver star barrette.",
    "specifications": {
      "Includes": "8 clips",
      "Material": "Acrylic & metal",
      "Colours": "Pink, lilac, silver"
    }
  },
  {
    "id": "genz-64",
    "title": "Crochet Puppy with Flower Bouquet",
    "titleKhmer": "តុក្កតាកូនឆ្កែក្រូសេកាន់ផ្កា",
    "category": "plush",
    "categoryLabel": "Plush & Crochet",
    "priceUSD": 12,
    "priceKHR": 49200,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Gift Idea 🎁",
    "status": "active",
    "image": "/assets/images/products/crochet-puppy-with-flower-bouquet.jpg",
    "gallery": [
      "/assets/images/products/crochet-puppy-with-flower-bouquet.jpg"
    ],
    "description": "A cream crochet puppy with floppy black ears, holding a tiny bouquet of crochet flowers.",
    "specifications": {
      "Material": "Soft yarn",
      "Size": "About 18 cm",
      "Colour": "Cream & black"
    }
  },
  {
    "id": "genz-65",
    "title": "Brown & White Striped Long-Sleeve Tee",
    "titleKhmer": "អាវដៃវែងឆ្នូតត្នោតសរ",
    "category": "apparel",
    "categoryLabel": "Tees & Tops",
    "priceUSD": 12,
    "priceKHR": 49200,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Fresh Drop",
    "status": "active",
    "image": "/assets/images/products/brown-and-white-striped-long-sleeve-tee.jpg",
    "gallery": [
      "/assets/images/products/brown-and-white-striped-long-sleeve-tee.jpg"
    ],
    "description": "A relaxed long-sleeve tee in brown and white stripes with a ribbed brown collar.",
    "specifications": {
      "Material": "Cotton",
      "Fit": "Relaxed",
      "Colour": "Brown & white"
    }
  },
  {
    "id": "genz-66",
    "title": "Red Gingham Puff-Sleeve Blouse",
    "titleKhmer": "អាវក្រឡាក្រហមដៃប៉ោង",
    "category": "apparel",
    "categoryLabel": "Tees & Tops",
    "priceUSD": 13,
    "priceKHR": 53300,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Coquette 🎀",
    "status": "active",
    "image": "/assets/images/products/red-gingham-puff-sleeve-blouse.jpg",
    "gallery": [
      "/assets/images/products/red-gingham-puff-sleeve-blouse.jpg"
    ],
    "description": "A fitted red gingham blouse with puff sleeves and a pointed collar. Sweet and summery.",
    "specifications": {
      "Material": "Cotton blend",
      "Fit": "Fitted",
      "Colour": "Red & white check"
    }
  },
  {
    "id": "genz-67",
    "title": "Red Gingham Cropped Button Top",
    "titleKhmer": "អាវក្រឡាក្រហមខ្លី",
    "category": "apparel",
    "categoryLabel": "Tees & Tops",
    "priceUSD": 13,
    "priceKHR": 53300,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Cottagecore 🌼",
    "status": "active",
    "image": "/assets/images/products/red-gingham-cropped-button-top.jpg",
    "gallery": [
      "/assets/images/products/red-gingham-cropped-button-top.jpg"
    ],
    "description": "A short-sleeve red gingham button top, shown styled with khaki shorts, a red bag and black flats.",
    "specifications": {
      "Material": "Cotton blend",
      "Fit": "Cropped",
      "Colour": "Red & white check"
    }
  },
  {
    "id": "genz-68",
    "title": "White Polka Dot Collared Blouse",
    "titleKhmer": "អាវសចំណុចខ្មៅមានក",
    "category": "apparel",
    "categoryLabel": "Tees & Tops",
    "priceUSD": 14,
    "priceKHR": 57400,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Vintage Vibes",
    "status": "active",
    "image": "/assets/images/products/white-polka-dot-collared-blouse.jpg",
    "gallery": [
      "/assets/images/products/white-polka-dot-collared-blouse.jpg"
    ],
    "description": "A floaty white blouse with tiny black polka dots, a wide collar and a cinched waist.",
    "specifications": {
      "Material": "Light chiffon",
      "Fit": "Relaxed",
      "Colour": "White & black dots"
    }
  },
  {
    "id": "genz-69",
    "title": "Embroidered Flare Jeans",
    "titleKhmer": "ខោខូវប៊យប៉ាក់លំនាំ",
    "category": "apparel",
    "categoryLabel": "Jeans",
    "priceUSD": 24,
    "priceKHR": 98400,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Y2K Classic",
    "status": "active",
    "image": "/assets/images/products/embroidered-flare-jeans.jpg",
    "gallery": [
      "/assets/images/products/embroidered-flare-jeans.jpg"
    ],
    "description": "Washed blue flare jeans with swirling red and white embroidery down the legs. Very Y2K.",
    "specifications": {
      "Material": "Denim",
      "Fit": "Low-rise flare",
      "Sizes": "S–L"
    }
  },
  {
    "id": "genz-70",
    "title": "Olive Square Optical Frames",
    "titleKhmer": "ស៊ុមវ៉ែនតាការ៉េពណ៌បៃតងអូលីវ",
    "category": "eyewear",
    "categoryLabel": "Eyewear",
    "priceUSD": 9,
    "priceKHR": 36900,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Fresh Drop",
    "status": "active",
    "image": "/assets/images/products/olive-square-optical-frames.jpg",
    "gallery": [
      "/assets/images/products/olive-square-optical-frames.jpg"
    ],
    "description": "Lightweight square frames in a soft olive green. Add your own lenses or wear them as a style piece.",
    "specifications": {
      "Material": "Acetate-look frame",
      "Lenses": "Clear (non-prescription)",
      "Colour": "Olive green"
    }
  },
  {
    "id": "genz-71",
    "title": "Fluffy Monkey House Slippers",
    "titleKhmer": "ស្បែកជើងផ្ទះស្វារោមទន់",
    "category": "shoes",
    "categoryLabel": "Slippers",
    "priceUSD": 9,
    "priceKHR": 36900,
    "inStock": true,
    "rating": 0,
    "reviewsCount": 0,
    "badge": "Kawaii 🍓",
    "status": "active",
    "image": "/assets/images/products/fluffy-monkey-house-slippers.jpg",
    "gallery": [
      "/assets/images/products/fluffy-monkey-house-slippers.jpg"
    ],
    "description": "Super-soft plush slippers with smiling monkey faces. Cosy, silly and a guaranteed laugh as a gift.",
    "specifications": {
      "Material": "Plush, soft sole",
      "Sizes": "One size (36–40)",
      "Colour": "Beige & brown"
    }
  }
];

// Replace the demo data with the database copy, and mirror the server's user/orders into the
// localStorage keys the pages already read (psa_current_user, psa_orders, psa_rbac_users).
if (PSA.online) {
  ACCESSORIES_PRODUCTS.splice(0, ACCESSORIES_PRODUCTS.length, ...PSA.products.filter(p => p.status === 'active'));
  try {
    localStorage.setItem('psa_orders', JSON.stringify(PSA.orders));
    if (PSA.user) localStorage.setItem('psa_current_user', JSON.stringify(PSA.user));
    else localStorage.removeItem('psa_current_user');
    localStorage.setItem('psa_rbac_users', JSON.stringify(PSA.users));

    // Refresh bag lines with current prices; drop products that were removed.
    const bag = JSON.parse(localStorage.getItem('psa_cart') || '[]');
    const fresh = bag.flatMap(item => {
      const p = ACCESSORIES_PRODUCTS.find(x => x.id === item.id);
      return p ? [{ ...item, title: p.title, priceUSD: p.priceUSD, priceKHR: p.priceKHR, image: p.image, categoryLabel: p.categoryLabel }] : [];
    });
    localStorage.setItem('psa_cart', JSON.stringify(fresh));
  } catch (e) {
    // storage unavailable: pages still render from PSA
  }
}

// Active Currency State
let CURRENT_CURRENCY = localStorage.getItem('psa_currency') || 'USD';

// Cart Helper Functions
function getCart() {
  try {
    return JSON.parse(localStorage.getItem('psa_cart') || '[]');
  } catch (e) {
    return [];
  }
}

function saveCart(cart) {
  localStorage.setItem('psa_cart', JSON.stringify(cart));
  updateCartBadge();
}

function addToCart(productId, qty = 1) {
  const product = ACCESSORIES_PRODUCTS.find(p => p.id === productId);
  if (!product) return;

  const cart = getCart();
  const existingIndex = cart.findIndex(item => item.id === productId);

  if (existingIndex > -1) {
    cart[existingIndex].quantity += qty;
  } else {
    cart.push({
      id: product.id,
      title: product.title,
      priceUSD: product.priceUSD,
      priceKHR: product.priceKHR,
      image: product.image,
      categoryLabel: product.categoryLabel,
      quantity: qty
    });
  }

  saveCart(cart);
  showToastNotification(product, qty);
}

// Asks before taking an item out of the bag (Remove button, or lowering the quantity below 1).
function removeFromCart(productId, { confirmFirst = true } = {}) {
  let cart = getCart();
  const item = cart.find(i => i.id === productId);
  if (confirmFirst && item && !confirm(`Remove "${item.title || 'this item'}" from your bag?`)) {
    if (typeof renderCartPage === 'function') renderCartPage();
    return false;
  }
  cart = cart.filter(i => i.id !== productId);
  saveCart(cart);
  if (typeof renderCartPage === 'function') {
    renderCartPage();
  }
}

function updateCartQuantity(productId, delta) {
  const cart = getCart();
  const item = cart.find(i => i.id === productId);
  if (item) {
    item.quantity += delta;
    if (item.quantity <= 0) {
      removeFromCart(productId);
      return;
    }
    saveCart(cart);
    if (typeof renderCartPage === 'function') {
      renderCartPage();
    }
  }
}

function clearCart() {
  localStorage.removeItem('psa_cart');
  updateCartBadge();
  if (typeof renderCartPage === 'function') {
    renderCartPage();
  }
}

function updateCartBadge() {
  const cart = getCart();
  const totalCount = cart.reduce((sum, item) => sum + item.quantity, 0);

  // Desktop badges
  document.querySelectorAll('.cart-count-badge').forEach(el => {
    el.textContent = totalCount;
    if (totalCount > 0) {
      el.classList.remove('hidden');
      el.classList.add('cart-bounce');
      setTimeout(() => el.classList.remove('cart-bounce'), 450);
    } else {
      el.classList.add('hidden');
    }
  });

  // Mobile badges
  document.querySelectorAll('.mobile-cart-badge').forEach(el => {
    el.textContent = totalCount;
    if (totalCount > 0) {
      el.classList.remove('hidden');
    } else {
      el.classList.add('hidden');
    }
  });
}

// Currency Switcher
function setCurrency(currency) {
  CURRENT_CURRENCY = currency;
  localStorage.setItem('psa_currency', currency);

  document.querySelectorAll('.currency-toggle-btn').forEach(btn => {
    if (btn.dataset.currency === currency) {
      btn.classList.add('bg-[#2B1D1D]', 'text-white', 'shadow-xs');
      btn.classList.remove('text-[#2B1D1D]');
    } else {
      btn.classList.remove('bg-[#2B1D1D]', 'text-white', 'shadow-xs');
      btn.classList.add('text-[#2B1D1D]');
    }
  });

  // Re-render any dynamic prices
  if (typeof renderCatalog === 'function') renderCatalog();
  if (typeof renderCartPage === 'function') renderCartPage();
  if (typeof updateCheckoutSummary === 'function') updateCheckoutSummary();
  if (typeof renderHomeProducts === 'function' && typeof activeFilter !== 'undefined') {
    renderHomeProducts(activeFilter);
  }
  if (currentQuickViewProduct) {
    const p1 = document.getElementById('quickViewPricePrimary');
    const p2 = document.getElementById('quickViewPriceSecondary');
    if (p1 && p2) {
      p1.textContent = formatPrice(currentQuickViewProduct.priceUSD, currentQuickViewProduct.priceKHR);
      const alt = CURRENT_CURRENCY === 'USD' 
        ? `${currentQuickViewProduct.priceKHR.toLocaleString()} ៛` 
        : `$${currentQuickViewProduct.priceUSD.toFixed(2)}`;
      p2.textContent = `(${alt})`;
    }
  }
}

function formatPrice(usd, khr) {
  if (CURRENT_CURRENCY === 'KHR') {
    return `${khr.toLocaleString()} ៛`;
  }
  return `$${usd.toFixed(2)}`;
}

// Toast Notification with Sorbet Orange & Cotton Beige Frame
function showToastNotification(product, qty) {
  let toast = document.getElementById('psaGlobalToast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'psaGlobalToast';
    toast.className = 'fixed bottom-20 md:bottom-8 right-4 md:right-8 z-50 transition-all duration-300 transform translate-y-20 opacity-0 pointer-events-none';
    document.body.appendChild(toast);
  }

  toast.innerHTML = `
    <div class="bg-white border-2 border-[#FFA552] shadow-2xl rounded-2xl p-4 max-w-sm flex items-center gap-3.5 pointer-events-auto">
      <div class="w-14 h-14 rounded-xl bg-white border border-[#EFE4D6] p-1 overflow-hidden shrink-0 flex items-center justify-center">
        <img src="${product.image}" alt="${product.title}" class="w-full h-full object-cover rounded-lg" />
      </div>
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-1.5 text-[11px] font-bold text-[#FFA552]">
          <span class="w-2 h-2 rounded-full bg-[#FFA552] animate-ping"></span>
          Added to Bag!
        </div>
        <div class="text-xs font-black text-[#2B1D1D] truncate mt-0.5">${product.title}</div>
        <div class="text-[11px] font-semibold text-[#4A3333] mt-0.5">${formatPrice(product.priceUSD, product.priceKHR)} &bull; Qty: ${qty}</div>
      </div>
      <a href="cart.html" class="px-3.5 py-2 rounded-xl bg-[#FFA552] hover:bg-[#E88C35] text-white font-extrabold text-xs shrink-0 transition-colors shadow-sm">
        View Bag &rarr;
      </a>
    </div>
  `;

  // Animate in
  setTimeout(() => {
    toast.classList.remove('translate-y-20', 'opacity-0', 'pointer-events-none');
    toast.classList.add('translate-y-0', 'opacity-100');
  }, 10);

  // Auto hide after 3.8s
  setTimeout(() => {
    toast.classList.add('translate-y-20', 'opacity-0', 'pointer-events-none');
    toast.classList.remove('translate-y-0', 'opacity-100');
  }, 3800);
}

// Scroll Intersection Observer for Smooth Shopify-Style Entrance
document.addEventListener('DOMContentLoaded', () => {
  updateCartBadge();
  updateWishlistUI();
  initEarlyAccessState();
  updateThemeToggleButtons();

  const reveals = document.querySelectorAll('.reveal-init');
  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries, obs) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('revealed');
          obs.unobserve(entry.target);
        }
      });
    }, { threshold: 0.08 });

    reveals.forEach(el => observer.observe(el));
  } else {
    reveals.forEach(el => el.classList.add('revealed'));
  }
});

/* ==========================================================================
   DARK THEME SYSTEM (CRYSTAL-CLEAR, NON-BLURRY TYPOGRAPHY)
   ========================================================================== */
function initTheme() {
  const saved = localStorage.getItem('psa_theme');
  const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  const isDark = saved === 'dark' || (!saved && prefersDark);
  applyTheme(isDark ? 'dark' : 'light');
}

function toggleTheme() {
  const isDark = document.documentElement.classList.contains('dark');
  applyTheme(isDark ? 'light' : 'dark');
}

function applyTheme(theme) {
  if (theme === 'dark') {
    document.documentElement.classList.add('dark');
    document.body.classList.add('dark');
    localStorage.setItem('psa_theme', 'dark');
  } else {
    document.documentElement.classList.remove('dark');
    document.body.classList.remove('dark');
    localStorage.setItem('psa_theme', 'light');
  }
  updateThemeToggleButtons();
}

function updateThemeToggleButtons() {
  const isDark = document.documentElement.classList.contains('dark');
  document.querySelectorAll('.theme-toggle-btn').forEach(btn => {
    btn.innerHTML = isDark
      ? `<svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg><span class="hidden sm:inline font-bold">Light</span>`
      : `<svg class="w-4 h-4 text-stone-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg><span class="hidden sm:inline font-bold">Dark</span>`;
    btn.setAttribute('title', isDark ? 'Switch to Light Theme' : 'Switch to Dark Theme');
  });
}

// Immediate execution to prevent white flash
initTheme();

/* ==========================================================================
   QUICK VIEW MODAL SYSTEM (LARGE GALLERY VIEW + DETAILED SPECS + ADD TO BAG)
   ========================================================================== */
let currentQuickViewProduct = null;
let quickViewQuantity = 1;

function openQuickView(productId, event) {
  if (event) {
    if (typeof event.preventDefault === 'function') event.preventDefault();
    if (typeof event.stopPropagation === 'function') event.stopPropagation();
  }

  const product = ACCESSORIES_PRODUCTS.find(p => p.id === productId);
  if (!product) return;

  currentQuickViewProduct = product;
  quickViewQuantity = 1;

  let modal = document.getElementById('psaQuickViewModal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'psaQuickViewModal';
    modal.className = 'fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-3 sm:p-6 bg-black/75 backdrop-blur-md transition-opacity duration-300 opacity-0 pointer-events-none';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-labelledby', 'quickViewTitle');
    document.body.appendChild(modal);

    modal.addEventListener('click', (e) => {
      if (e.target === modal) {
        closeQuickView();
      }
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && !modal.classList.contains('pointer-events-none')) {
        closeQuickView();
      }
    });
  }

  const specsEntries = Object.entries(product.specifications || {});
  const specsHtml = specsEntries.length > 0 
    ? `
      <div class="space-y-1.5 pt-2">
        <div class="text-[11px] font-black uppercase tracking-wider text-[#2B1D1D] dark:text-white">Product Details & Specs</div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
          ${specsEntries.map(([k, v]) => `
            <div class="bg-[#F9F3EA] dark:bg-[#23232C] border border-[#EFE4D6] dark:border-[#32323D] rounded-xl p-2.5 flex flex-col justify-center">
              <span class="text-[10px] uppercase font-bold text-[#FFA552]">${k}</span>
              <span class="font-extrabold text-[#2B1D1D] dark:text-white truncate mt-0.5">${v}</span>
            </div>
          `).join('')}
        </div>
      </div>
    `
    : '';

  const altCurrency = CURRENT_CURRENCY === 'USD' 
    ? `${product.priceKHR.toLocaleString()} ៛` 
    : `$${product.priceUSD.toFixed(2)}`;

  const galleryList = product.gallery && product.gallery.length > 0 ? product.gallery : [product.image];

  modal.innerHTML = `
    <div class="relative w-full max-w-3xl bg-white dark:bg-[#1A1A22] rounded-3xl border-2 border-[#FFA552] shadow-2xl overflow-hidden transform transition-all duration-300 scale-95 opacity-0 my-auto text-[#2B1D1D] dark:text-white" id="quickViewCard">
      
      <!-- Close Button (Top-Right) -->
      <button 
        type="button" 
        onclick="closeQuickView()" 
        class="absolute top-4 right-4 z-20 w-9 h-9 rounded-full bg-[#F9F3EA] dark:bg-[#2A2A34] hover:bg-[#FFA552] hover:text-white text-[#2B1D1D] dark:text-white flex items-center justify-center border border-[#EFE4D6] dark:border-[#3A3A46] transition-all duration-200 hover:scale-105 shadow-sm cursor-pointer"
        aria-label="Close Quick View"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      </button>

      <div class="grid grid-cols-1 md:grid-cols-12 gap-6 p-5 sm:p-8">
        
        <!-- Left Column: Large Gallery View + Thumbnail Strip -->
        <div class="md:col-span-6 flex flex-col space-y-3">
          
          <!-- Large High-Res Viewer -->
          <div class="aspect-square w-full rounded-2xl bg-[#FDFBF7] dark:bg-[#141419] border border-[#EFE4D6] dark:border-[#2D2D38] p-3 flex items-center justify-center relative overflow-hidden group shadow-inner">
            <img 
              id="quickViewMainImage" 
              src="${galleryList[0]}" 
              alt="${product.title}" 
              class="w-full h-full object-cover rounded-xl transition-all duration-300 group-hover:scale-105"
            />
            
            <!-- Badges -->
            <div class="absolute top-3 left-3 flex flex-col gap-1.5 z-10 pointer-events-none">
              <span class="bg-[#FFA552] text-white px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider shadow-sm">
                ${product.badge}
              </span>
              <span class="bg-[#2B1D1D] dark:bg-[#3D2929] text-[#F9F3EA] px-2.5 py-0.5 rounded-full text-[9px] font-extrabold tracking-wide shadow-sm">
                ${product.categoryLabel}
              </span>
            </div>

            <!-- Zoom Indicator -->
            <div class="absolute bottom-3 right-3 bg-white/95 dark:bg-[#1E1E26]/90 backdrop-blur-sm text-[#2B1D1D] dark:text-white px-2.5 py-1 rounded-full text-[10px] font-bold border border-[#EFE4D6] dark:border-[#2D2D38] shadow-xs flex items-center gap-1 pointer-events-none">
              <svg class="w-3.5 h-3.5 text-[#FFA552]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/></svg>
              <span>Hover for Zoom</span>
            </div>
          </div>

          <!-- Gallery Thumbnails (Interactive View Switcher) -->
          <div class="flex items-center gap-2 overflow-x-auto pb-1 select-none" id="quickViewGalleryThumbs">
            ${galleryList.map((img, idx) => `
              <button 
                type="button" 
                onclick="switchQuickViewImage('${img}', this)"
                class="gallery-thumb-btn w-14 h-14 rounded-xl border-2 overflow-hidden shrink-0 cursor-pointer ${idx === 0 ? 'active-thumb border-[#FFA552]' : 'border-[#EFE4D6] dark:border-[#2D2D38] opacity-75 hover:opacity-100'}"
                title="View Gallery Angle ${idx + 1}"
              >
                <img src="${img}" alt="${product.title} angle ${idx + 1}" class="w-full h-full object-cover" />
              </button>
            `).join('')}
          </div>

          <!-- Studio Trust Perks -->
          <div class="bg-[#F9F3EA] dark:bg-[#20202A] rounded-xl p-3 border border-[#EFE4D6] dark:border-[#30303E] space-y-1.5 text-[11px] text-[#4A3333] dark:text-[#E4E4E7] font-semibold">
            <div class="flex items-center gap-2">
              <span class="w-4 h-4 rounded-full bg-[#FFA552] text-white flex items-center justify-center text-[10px] font-black shrink-0">✓</span>
              <span>Phnom Penh 1-2hr Express Courier Available</span>
            </div>
            <div class="flex items-center gap-2">
              <span class="w-4 h-4 rounded-full bg-[#FFA552] text-white flex items-center justify-center text-[10px] font-black shrink-0">✓</span>
              <span>Universal KHQR Scan & Pay (ABA, ACLEDA, Wing)</span>
            </div>
          </div>
        </div>

        <!-- Right Column: Product Details & Cart Actions -->
        <div class="md:col-span-6 flex flex-col justify-between space-y-4">
          
          <div class="space-y-3">
            <!-- Category and Stock Indicator -->
            <div class="flex items-center justify-between gap-2 pr-8">
              <span class="text-xs font-black text-[#FFA552] uppercase tracking-wider">${product.categoryLabel}</span>
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 text-[10px] font-extrabold">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                In Stock & Ready to Dispatch
              </span>
            </div>

            <!-- Title & Subtitle -->
            <div>
              <h2 id="quickViewTitle" class="text-lg sm:text-xl font-black text-[#2B1D1D] dark:text-white tracking-tight leading-snug">
                ${product.title}
              </h2>
              <p class="text-xs text-stone-500 dark:text-stone-300 font-khmer font-semibold mt-1">
                ${product.titleKhmer}
              </p>
            </div>
            ${product.reviewsCount > 0 ? `<!-- Ratings -->
            <div class="flex items-center gap-2 text-xs">
              <div class="flex items-center text-amber-500 font-black">
                <span>★</span>
                <span class="ml-1 text-[#2B1D1D] dark:text-white font-bold">${Number(product.rating).toFixed(1)}</span>
              </div>
              <span class="text-stone-300 dark:text-stone-600">&bull;</span>
              <span class="text-stone-500 dark:text-stone-300 font-semibold">${product.reviewsCount} ${product.reviewsCount === 1 ? 'review' : 'reviews'} from customers</span>
            </div>` : `<div class="text-xs font-semibold text-stone-500">No reviews yet</div>`}

            <!-- Pricing Box -->
            <div class="p-3 bg-[#FDFBF7] dark:bg-[#16161D] rounded-2xl border border-[#EFE4D6] dark:border-[#2D2D38] flex items-baseline justify-between">
              <div>
                <span class="text-2xl font-black text-[#2B1D1D] dark:text-white" id="quickViewPricePrimary">
                  ${formatPrice(product.priceUSD, product.priceKHR)}
                </span>
                <span class="text-xs font-bold text-[#FFA552] ml-2" id="quickViewPriceSecondary">
                  (${altCurrency})
                </span>
              </div>
              <span class="text-[10px] font-black uppercase text-[#2B1D1D] dark:text-white px-2 py-0.5 rounded-md bg-[#FFA552]/20 border border-[#FFA552]/40">
                1 USD = 4,100 ៛
              </span>
            </div>

            <!-- Description -->
            <p class="text-xs text-[#4A3333] dark:text-[#E4E4E7] leading-relaxed font-medium">
              ${product.description}
            </p>

            <!-- Specifications Table -->
            ${specsHtml}
          </div>

          <!-- Bottom Actions & Quantity Selector -->
          <div class="pt-3 border-t border-[#EFE4D6] dark:border-[#2D2D38] space-y-3">
            
            <div class="flex items-center gap-3">
              <!-- Quantity Stepper -->
              <div class="flex items-center border border-[#EFE4D6] dark:border-[#32323D] bg-[#FDFBF7] dark:bg-[#16161D] rounded-xl overflow-hidden shrink-0 shadow-xs">
                <button 
                  type="button" 
                  onclick="changeQuickViewQty(-1)" 
                  class="w-9 h-10 flex items-center justify-center font-black text-sm text-[#2B1D1D] dark:text-white hover:bg-[#FFA552] hover:text-white transition-colors cursor-pointer"
                  aria-label="Decrease quantity"
                >-</button>
                <span id="quickViewQtyVal" class="w-10 text-center text-xs font-black text-[#2B1D1D] dark:text-white">1</span>
                <button 
                  type="button" 
                  onclick="changeQuickViewQty(1)" 
                  class="w-9 h-10 flex items-center justify-center font-black text-sm text-[#2B1D1D] dark:text-white hover:bg-[#FFA552] hover:text-white transition-colors cursor-pointer"
                  aria-label="Increase quantity"
                >+</button>
              </div>

              <!-- Add to Bag Button -->
              <button 
                type="button" 
                id="quickViewAddBtn"
                onclick="addQuickViewToBag()" 
                class="btn-press flex-1 py-3 px-4 rounded-xl bg-gradient-to-r from-[#FFA552] to-[#E88C35] hover:from-[#E88C35] hover:to-[#FFA552] text-white font-black text-xs shadow-md flex items-center justify-center gap-2 cursor-pointer transition-all"
              >
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                </svg>
                <span id="quickViewAddText">Add to Bag &bull; ${formatPrice(product.priceUSD, product.priceKHR)}</span>
              </button>

              <!-- Save to Wishlist Button in Quick View -->
              <button
                type="button"
                data-wishlist-id="${product.id}"
                onclick="toggleWishlist('${product.id}', event)"
                class="wishlist-btn p-3 rounded-xl border border-[#EFE4D6] dark:border-[#32323D] bg-[#FDFBF7] dark:bg-[#16161D] hover:bg-rose-50 dark:hover:bg-rose-950/40 text-[#4A3333] hover:text-rose-600 transition-all flex items-center justify-center shadow-xs cursor-pointer ${isInWishlist(product.id) ? 'active-wishlist text-rose-500' : ''}"
                title="${isInWishlist(product.id) ? 'Remove from Wishlist' : 'Save to Wishlist'}"
                aria-label="Save to Wishlist"
              >
                <svg class="w-5 h-5 transition-transform active:scale-125" fill="${isInWishlist(product.id) ? 'currentColor' : 'none'}" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
              </button>
            </div>

            <!-- Full Details Link & SKU -->
            <div class="flex items-center justify-between text-xs pt-1">
              <a 
                href="product-detail.html?id=${product.id}" 
                class="text-xs font-extrabold text-[#FFA552] hover:text-[#2B1D1D] dark:hover:text-white flex items-center gap-1 transition-colors underline"
              >
                <span>View Full Product Details Page</span>
                <span>&rarr;</span>
              </a>
              <span class="text-[10px] font-mono text-stone-400 dark:text-stone-400">SKU: ${product.id.toUpperCase()}</span>
            </div>

          </div>

        </div>

      </div>
    </div>
  `;

  document.body.style.overflow = 'hidden';
  modal.classList.remove('opacity-0', 'pointer-events-none');
  modal.classList.add('opacity-100', 'pointer-events-auto');

  setTimeout(() => {
    const card = document.getElementById('quickViewCard');
    if (card) {
      card.classList.remove('scale-95', 'opacity-0');
      card.classList.add('scale-100', 'opacity-100');
    }
  }, 10);
}

function switchQuickViewImage(newUrl, btn) {
  const mainImg = document.getElementById('quickViewMainImage');
  if (mainImg) {
    mainImg.style.opacity = '0.4';
    mainImg.src = newUrl;
    setTimeout(() => {
      mainImg.style.opacity = '1';
    }, 120);
  }
  document.querySelectorAll('.gallery-thumb-btn').forEach(b => {
    b.classList.remove('active-thumb', 'border-[#FFA552]');
    b.classList.add('border-[#EFE4D6]', 'dark:border-[#2D2D38]', 'opacity-75');
  });
  if (btn) {
    btn.classList.add('active-thumb', 'border-[#FFA552]');
    btn.classList.remove('border-[#EFE4D6]', 'dark:border-[#2D2D38]', 'opacity-75');
  }
}

function closeQuickView() {
  const modal = document.getElementById('psaQuickViewModal');
  const card = document.getElementById('quickViewCard');
  if (!modal) return;

  if (card) {
    card.classList.remove('scale-100', 'opacity-100');
    card.classList.add('scale-95', 'opacity-0');
  }

  modal.classList.remove('opacity-100');
  modal.classList.add('opacity-0', 'pointer-events-none');

  setTimeout(() => {
    document.body.style.overflow = '';
  }, 250);
}

function changeQuickViewQty(delta) {
  quickViewQuantity = Math.max(1, quickViewQuantity + delta);
  const qtyEl = document.getElementById('quickViewQtyVal');
  if (qtyEl) {
    qtyEl.textContent = quickViewQuantity;
  }
  updateQuickViewPrice();
}

function updateQuickViewPrice() {
  if (!currentQuickViewProduct) return;
  const totalUSD = currentQuickViewProduct.priceUSD * quickViewQuantity;
  const totalKHR = currentQuickViewProduct.priceKHR * quickViewQuantity;
  const btnText = document.getElementById('quickViewAddText');
  if (btnText) {
    btnText.textContent = `Add to Bag • ${formatPrice(totalUSD, totalKHR)}`;
  }
}

function addQuickViewToBag() {
  if (!currentQuickViewProduct) return;
  addToCart(currentQuickViewProduct.id, quickViewQuantity);

  const btn = document.getElementById('quickViewAddBtn');
  const text = document.getElementById('quickViewAddText');
  if (btn && text) {
    const origText = text.textContent;
    text.textContent = 'Added to Bag! ✓';
    btn.classList.add('bg-emerald-600');
    setTimeout(() => {
      text.textContent = origText;
      btn.classList.remove('bg-emerald-600');
    }, 1500);
  }
}

/* ==========================================================================
   GET EARLY ACCESS NEWSLETTER (MOCK API & SUBSCRIPTION ENGINE)
   ========================================================================== */

/**
 * Mock API endpoint simulating asynchronous newsletter registration
 * @param {string} email
 * @returns {Promise<object>}
 */
async function mockNewsletterApi(email) {
  // Simulate network request latency (700ms)
  await new Promise(resolve => setTimeout(resolve, 700));

  const trimmed = email ? email.trim().toLowerCase() : '';
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  if (!trimmed || !emailRegex.test(trimmed)) {
    throw new Error('Please enter a valid email address (e.g. name@domain.com)');
  }

  const randomVip = Math.floor(100000 + Math.random() * 900000);
  const vipId = `VIP-${randomVip}`;

  return {
    success: true,
    status: 200,
    email: trimmed,
    vipId: vipId,
    discountCode: 'EARLY10',
    message: 'Welcome to the PsaOnline Early Access Inner Circle! Your VIP radar pass is active.',
    timestamp: new Date().toISOString()
  };
}

async function handleEarlyAccessSubmit(e) {
  if (e && typeof e.preventDefault === 'function') {
    e.preventDefault();
  }

  const input = document.getElementById('earlyAccessEmail');
  const btn = document.getElementById('earlyAccessSubmitBtn');
  const btnText = document.getElementById('earlyAccessBtnText');
  const btnArrow = document.getElementById('earlyAccessBtnArrow');
  const spinner = document.getElementById('earlyAccessSpinner');
  const feedback = document.getElementById('earlyAccessFeedback');
  const form = document.getElementById('earlyAccessForm');
  const successCard = document.getElementById('earlyAccessSuccessCard');

  if (!input) return;
  const email = input.value.trim();

  if (feedback) {
    feedback.textContent = '';
    feedback.className = 'text-xs font-semibold text-[#FFA552] min-h-[1.25rem] text-left sm:text-center transition-all';
  }

  if (btn) {
    btn.disabled = true;
    if (btnText) btnText.textContent = 'Reserving Spot...';
    if (btnArrow) btnArrow.classList.add('hidden');
    if (spinner) spinner.classList.remove('hidden');
  }

  try {
    const res = await mockNewsletterApi(email);

    localStorage.setItem('psa_early_access_user', JSON.stringify(res));

    if (form) form.classList.add('hidden');
    if (successCard) {
      successCard.classList.remove('hidden');
      const badge = document.getElementById('vipBadgeId');
      if (badge) badge.textContent = res.vipId;
      const emailDisp = document.getElementById('subscribedEmailDisplay');
      if (emailDisp) emailDisp.textContent = res.email;
    }

    // Trigger celebration confetti
    if (typeof confetti === 'function') {
      confetti({
        particleCount: 50,
        spread: 60,
        origin: { y: 0.8 },
        colors: ['#FFA552', '#F9F3EA', '#2B1D1D', '#E88C35']
      });
    }

    if (typeof showToastNotification === 'function') {
      // Notification
    }
  } catch (err) {
    if (feedback) {
      feedback.textContent = err.message || 'Something went wrong. Please check your email and try again.';
      feedback.className = 'text-xs font-semibold text-rose-300 min-h-[1.25rem] text-left sm:text-center transition-all';
    }
  } finally {
    if (btn) {
      btn.disabled = false;
      if (btnText) btnText.textContent = 'Get Early Access';
      if (btnArrow) btnArrow.classList.remove('hidden');
      if (spinner) spinner.classList.add('hidden');
    }
  }
}

function copyEarlyAccessCode(btn) {
  const code = 'EARLY10';
  if (navigator && navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(code).then(() => {
      const orig = btn.innerHTML;
      btn.innerHTML = '<span class="text-emerald-300 font-extrabold">Copied! ✓</span>';
      setTimeout(() => {
        btn.innerHTML = orig;
      }, 2000);
    }).catch(() => {
      btn.innerHTML = '<span>Code: EARLY10</span>';
    });
  } else {
    btn.innerHTML = '<span>Code: EARLY10</span>';
  }
}

function resetEarlyAccessForm() {
  localStorage.removeItem('psa_early_access_user');
  const form = document.getElementById('earlyAccessForm');
  const successCard = document.getElementById('earlyAccessSuccessCard');
  const input = document.getElementById('earlyAccessEmail');
  const feedback = document.getElementById('earlyAccessFeedback');
  if (form) form.classList.remove('hidden');
  if (successCard) successCard.classList.add('hidden');
  if (feedback) feedback.textContent = '';
  if (input) {
    input.value = '';
    input.focus();
  }
}

function initEarlyAccessState() {
  const saved = localStorage.getItem('psa_early_access_user');
  if (!saved) return;
  try {
    const data = JSON.parse(saved);
    const form = document.getElementById('earlyAccessForm');
    const successCard = document.getElementById('earlyAccessSuccessCard');
    if (form && successCard && data && data.email) {
      form.classList.add('hidden');
      successCard.classList.remove('hidden');
      const badge = document.getElementById('vipBadgeId');
      if (badge) badge.textContent = data.vipId || 'VIP-MEMBER';
      const emailDisp = document.getElementById('subscribedEmailDisplay');
      if (emailDisp) emailDisp.textContent = data.email;
    }
  } catch (e) {
    // ignore parse error
  }
}

/* ==========================================================================
   WISHLIST SYSTEM & INTERACTIVE VIEWER DRAWER
   ========================================================================== */
function getWishlist() {
  try {
    const list = JSON.parse(localStorage.getItem('psa_wishlist'));
    return Array.isArray(list) ? list : [];
  } catch (e) {
    return [];
  }
}

function isInWishlist(productId) {
  return getWishlist().includes(productId);
}

// Viewed history is stored under 'psa_viewed' as [{ id, ts }], newest first.
// ts is milliseconds since the epoch. Entries for products no longer in the catalog are dropped.
function getViewed() {
  try {
    const list = JSON.parse(localStorage.getItem('psa_viewed'));
    if (!Array.isArray(list)) return [];
    return list.filter(v => v && typeof v.id === 'string' && ACCESSORIES_PRODUCTS.some(p => p.id === v.id));
  } catch (e) {
    return [];
  }
}

// Record a product view (newest first, one entry per product, last 50) for viewed.html
function trackView(productId) {
  try {
    const list = JSON.parse(localStorage.getItem('psa_viewed') || '[]');
    const rest = Array.isArray(list) ? list.filter(v => v && v.id !== productId) : [];
    localStorage.setItem('psa_viewed', JSON.stringify([{ id: productId, ts: Date.now() }, ...rest].slice(0, 50)));
  } catch (e) {
    // storage unavailable
  }
}

function toggleWishlist(productId, event) {
  if (event) {
    if (typeof event.preventDefault === 'function') event.preventDefault();
    if (typeof event.stopPropagation === 'function') event.stopPropagation();
  }

  let list = getWishlist();
  const product = ACCESSORIES_PRODUCTS.find(p => p.id === productId);
  const exists = list.includes(productId);

  if (exists) {
    list = list.filter(id => id !== productId);
    localStorage.setItem('psa_wishlist', JSON.stringify(list));
    showWishlistToast(product ? product.title : 'Item', false);
  } else {
    list.push(productId);
    localStorage.setItem('psa_wishlist', JSON.stringify(list));
    showWishlistToast(product ? product.title : 'Item', true);
  }

  updateWishlistUI();
  
  if (typeof renderWishlistDrawer === 'function') renderWishlistDrawer();
  if (typeof renderWishlistPage === 'function') renderWishlistPage();
}

function removeWishlistItem(productId, event) {
  if (event) {
    if (typeof event.preventDefault === 'function') event.preventDefault();
    if (typeof event.stopPropagation === 'function') event.stopPropagation();
  }
  let list = getWishlist().filter(id => id !== productId);
  localStorage.setItem('psa_wishlist', JSON.stringify(list));
  updateWishlistUI();
  if (typeof renderWishlistDrawer === 'function') renderWishlistDrawer();
  if (typeof renderWishlistPage === 'function') renderWishlistPage();
}

function moveWishlistItemToCart(productId, event) {
  if (event) {
    if (typeof event.preventDefault === 'function') event.preventDefault();
    if (typeof event.stopPropagation === 'function') event.stopPropagation();
  }
  addToCart(productId, 1);
}

function moveAllWishlistToBag() {
  const list = getWishlist();
  if (list.length === 0) return;
  list.forEach(id => {
    addToCart(id, 1);
  });
  showToastNotification({
    title: `${list.length} Wishlist Items`,
    image: ACCESSORIES_PRODUCTS.find(p => p.id === list[0])?.image || '',
    priceUSD: 0,
    priceKHR: 0
  }, list.length);
  closeWishlistModal();
}

function clearWishlist() {
  if (confirm('Clear all saved items from your wishlist?')) {
    localStorage.setItem('psa_wishlist', JSON.stringify([]));
    updateWishlistUI();
    if (typeof renderWishlistDrawer === 'function') renderWishlistDrawer();
    if (typeof renderWishlistPage === 'function') renderWishlistPage();
  }
}

function updateWishlistUI() {
  const list = getWishlist();

  // Badges in header and nav
  document.querySelectorAll('.wishlist-count-badge').forEach(badge => {
    badge.textContent = list.length;
    if (list.length > 0) {
      badge.classList.remove('hidden');
    } else {
      badge.classList.add('hidden');
    }
  });

  // Toggle button icons on cards
  document.querySelectorAll('[data-wishlist-id]').forEach(btn => {
    const pId = btn.getAttribute('data-wishlist-id');
    const isSaved = list.includes(pId);
    const svg = btn.querySelector('svg');

    if (isSaved) {
      btn.classList.add('active-wishlist', 'text-rose-500');
      btn.classList.remove('text-stone-400');
      if (svg) {
        svg.setAttribute('fill', 'currentColor');
        svg.classList.add('text-rose-500');
      }
      btn.setAttribute('title', 'Remove from Wishlist');
      btn.setAttribute('aria-label', 'Remove from Wishlist');
    } else {
      btn.classList.remove('active-wishlist', 'text-rose-500');
      btn.classList.add('text-stone-400');
      if (svg) {
        svg.setAttribute('fill', 'none');
        svg.classList.remove('text-rose-500');
      }
      btn.setAttribute('title', 'Save to Wishlist');
      btn.setAttribute('aria-label', 'Save to Wishlist');
    }
  });
}

function showWishlistToast(productTitle, isAdded) {
  let toast = document.getElementById('psaWishlistToast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'psaWishlistToast';
    toast.className = 'fixed top-20 right-4 sm:right-8 z-50 transition-all duration-300 transform -translate-y-10 opacity-0 pointer-events-none';
    document.body.appendChild(toast);
  }

  toast.innerHTML = `
    <div class="bg-white dark:bg-[#1E1E26] border-2 ${isAdded ? 'border-rose-400 shadow-[0_10px_25px_-5px_rgba(244,63,94,0.35)]' : 'border-[#EFE4D6] dark:border-[#32323D]'} rounded-2xl p-3.5 max-w-sm flex items-center gap-3 pointer-events-auto">
      <div class="w-10 h-10 rounded-xl ${isAdded ? 'bg-rose-50 text-rose-500 dark:bg-rose-950/60 dark:text-rose-400' : 'bg-[#F9F3EA] text-[#4A3333] dark:bg-[#2A2A34] dark:text-[#F9F3EA]'} flex items-center justify-center shrink-0">
        <svg class="w-5 h-5" fill="${isAdded ? 'currentColor' : 'none'}" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
      </div>
      <div class="flex-1 min-w-0 text-xs">
        <div class="font-black text-[#2B1D1D] dark:text-white truncate">${productTitle}</div>
        <div class="text-[11px] font-semibold ${isAdded ? 'text-rose-600 dark:text-rose-400' : 'text-stone-500'}">
          ${isAdded ? 'Saved to your Wishlist! 💖' : 'Removed from Wishlist'}
        </div>
      </div>
      <button onclick="openWishlistModal()" class="px-2.5 py-1.5 rounded-lg bg-[#FFA552] hover:bg-[#E88C35] text-white text-[11px] font-bold shrink-0 transition-colors shadow-xs cursor-pointer">
        View
      </button>
    </div>
  `;

  setTimeout(() => {
    toast.classList.remove('-translate-y-10', 'opacity-0', 'pointer-events-none');
    toast.classList.add('translate-y-0', 'opacity-100');
  }, 10);

  setTimeout(() => {
    toast.classList.add('-translate-y-10', 'opacity-0', 'pointer-events-none');
    toast.classList.remove('translate-y-0', 'opacity-100');
  }, 2600);
}

function openWishlistModal() {
  let modal = document.getElementById('psaWishlistModal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'psaWishlistModal';
    modal.className = 'fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-3 sm:p-6 bg-black/75 backdrop-blur-md transition-opacity duration-300 opacity-0 pointer-events-none';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-labelledby', 'wishlistModalTitle');
    document.body.appendChild(modal);

    modal.addEventListener('click', (e) => {
      if (e.target === modal) closeWishlistModal();
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && !modal.classList.contains('pointer-events-none')) {
        closeWishlistModal();
      }
    });
  }

  renderWishlistDrawer();

  document.body.style.overflow = 'hidden';
  modal.classList.remove('opacity-0', 'pointer-events-none');
  modal.classList.add('opacity-100', 'pointer-events-auto');

  setTimeout(() => {
    const card = document.getElementById('wishlistModalCard');
    if (card) {
      card.classList.remove('scale-95', 'opacity-0');
      card.classList.add('scale-100', 'opacity-100');
    }
  }, 10);
}

function closeWishlistModal() {
  const modal = document.getElementById('psaWishlistModal');
  const card = document.getElementById('wishlistModalCard');
  if (!modal) return;

  if (card) {
    card.classList.remove('scale-100', 'opacity-100');
    card.classList.add('scale-95', 'opacity-0');
  }

  modal.classList.remove('opacity-100');
  modal.classList.add('opacity-0', 'pointer-events-none');
  document.body.style.overflow = '';
}

function renderWishlistDrawer() {
  const modal = document.getElementById('psaWishlistModal');
  if (!modal) return;

  const list = getWishlist();
  const products = list.map(id => ACCESSORIES_PRODUCTS.find(p => p.id === id)).filter(Boolean);

  modal.innerHTML = `
    <div class="relative w-full max-w-2xl bg-white dark:bg-[#1A1A22] rounded-3xl border-2 border-rose-300 dark:border-rose-900/60 shadow-2xl overflow-hidden transform transition-all duration-300 scale-95 opacity-0 my-auto text-[#2B1D1D] dark:text-white flex flex-col max-h-[90vh]" id="wishlistModalCard">
      
      <!-- Header -->
      <div class="px-6 py-5 border-b border-[#EFE4D6] dark:border-[#2D2D38] flex items-center justify-between bg-[#FDFBF7] dark:bg-[#15151B]">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-rose-50 dark:bg-rose-950/60 text-rose-500 flex items-center justify-center text-lg shadow-xs">
            💖
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h2 id="wishlistModalTitle" class="text-lg sm:text-xl font-black text-[#2B1D1D] dark:text-white tracking-tight">Your Wishlist</h2>
              <span class="px-2 py-0.5 rounded-full bg-rose-100 dark:bg-rose-950/80 text-rose-600 dark:text-rose-400 text-xs font-black">
                ${products.length} saved
              </span>
            </div>
            <p class="text-[11px] text-stone-500 dark:text-stone-400 font-medium">Saved accessories stored in your local storage</p>
          </div>
        </div>

        <button 
          type="button" 
          onclick="closeWishlistModal()" 
          class="w-9 h-9 rounded-full bg-[#F9F3EA] dark:bg-[#252530] hover:bg-[#FFA552] hover:text-white text-[#2B1D1D] dark:text-white flex items-center justify-center border border-[#EFE4D6] dark:border-[#32323D] transition-all hover:scale-105 cursor-pointer shadow-xs"
          aria-label="Close Wishlist"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
      </div>

      <!-- Content Area -->
      <div class="p-6 overflow-y-auto flex-1 space-y-3">
        ${products.length === 0 ? `
          <div class="text-center py-12 sm:py-16 space-y-4">
            <div class="w-16 h-16 rounded-3xl bg-rose-50 dark:bg-rose-950/40 text-rose-400 flex items-center justify-center text-3xl mx-auto shadow-inner">
              🤍
            </div>
            <div class="space-y-1">
              <h3 class="text-base sm:text-lg font-black text-[#2B1D1D] dark:text-white">Your Wishlist is Empty</h3>
              <p class="text-xs text-stone-500 dark:text-stone-400 max-w-sm mx-auto leading-relaxed">
                Click the heart icon on any drop in the catalog to bookmark your favorite Y2K jewelry, shades, and cloud bags!
              </p>
            </div>
            <div class="pt-2">
              <a href="products.html" onclick="closeWishlistModal()" class="btn-press inline-flex items-center gap-2 px-6 py-2.5 rounded-full bg-[#FFA552] hover:bg-[#E88C35] text-white font-black text-xs shadow-md transition-all">
                <span>Explore Drops &bull; All Catalog</span>
                <span>&rarr;</span>
              </a>
            </div>
          </div>
        ` : `
          <div class="space-y-3">
            ${products.map(item => `
              <div class="p-3 sm:p-4 rounded-2xl bg-[#FDFBF7] dark:bg-[#15151B] border border-[#EFE4D6] dark:border-[#2D2D38] flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4 transition-all hover:border-[#FFA552]">
                <div class="flex items-center gap-3 min-w-0">
                  <a href="product-detail.html?id=${item.id}" onclick="closeWishlistModal()" class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl bg-white dark:bg-[#1C1C24] border border-[#EFE4D6] dark:border-[#2D2D38] p-1 overflow-hidden shrink-0 block">
                    <img src="${item.image}" alt="${item.title}" class="w-full h-full object-cover rounded-lg hover:scale-110 transition-transform" />
                  </a>
                  <div class="min-w-0 flex-1">
                    <div class="text-[10px] font-black uppercase text-[#FFA552] tracking-wider">${item.categoryLabel}</div>
                    <a href="product-detail.html?id=${item.id}" onclick="closeWishlistModal()" class="text-xs sm:text-sm font-extrabold text-[#2B1D1D] dark:text-white hover:text-[#FFA552] transition-colors truncate block">
                      ${item.title}
                    </a>
                    <div class="text-[11px] text-stone-400 font-khmer truncate mt-0.5">${item.titleKhmer}</div>
                    <div class="text-xs font-black text-[#2B1D1D] dark:text-white mt-1">
                      ${formatPrice(item.priceUSD, item.priceKHR)}
                      <span class="text-[10px] font-bold text-[#FFA552] ml-1">(${CURRENT_CURRENCY === 'USD' ? item.priceKHR.toLocaleString() + ' ៛' : '$' + item.priceUSD.toFixed(2)})</span>
                    </div>
                  </div>
                </div>

                <div class="flex items-center gap-2 self-end sm:self-center shrink-0">
                  <button
                    type="button"
                    onclick="moveWishlistItemToCart('${item.id}', event)"
                    class="btn-press px-3.5 py-2 rounded-xl bg-[#FFA552] hover:bg-[#E88C35] text-white text-xs font-black shadow-xs flex items-center gap-1.5 transition-all cursor-pointer"
                    title="Add to Bag"
                  >
                    <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    <span>Add to Bag</span>
                  </button>

                  <button
                    type="button"
                    onclick="openQuickView('${item.id}', event); closeWishlistModal();"
                    class="p-2 rounded-xl bg-white dark:bg-[#20202A] hover:bg-[#F9F3EA] dark:hover:bg-[#2D2D38] border border-[#EFE4D6] dark:border-[#32323D] text-[#2B1D1D] dark:text-white transition-all cursor-pointer"
                    title="Quick View"
                  >
                    <svg class="w-4 h-4 text-stone-600 dark:text-stone-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                  </button>

                  <button
                    type="button"
                    onclick="removeWishlistItem('${item.id}', event)"
                    class="p-2 rounded-xl bg-white dark:bg-[#20202A] hover:bg-rose-50 dark:hover:bg-rose-950/40 border border-[#EFE4D6] dark:border-[#32323D] text-[#4A3333] hover:text-rose-600 transition-all cursor-pointer"
                    title="Remove from Wishlist"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                  </button>
                </div>
              </div>
            `).join('')}
          </div>
        `}
      </div>

      <!-- Footer Bar -->
      ${products.length > 0 ? `
        <div class="px-6 py-4 bg-[#FDFBF7] dark:bg-[#15151B] border-t border-[#EFE4D6] dark:border-[#2D2D38] flex flex-col sm:flex-row items-center justify-between gap-3">
          <div class="flex items-center gap-3 text-xs">
            <button 
              type="button" 
              onclick="clearWishlist()" 
              class="text-xs text-stone-400 hover:text-rose-600 font-bold transition-colors cursor-pointer"
            >
              Clear All (${products.length})
            </button>
            <span class="text-stone-300 dark:text-stone-600">&bull;</span>
            <a href="wishlist.html" onclick="closeWishlistModal()" class="text-xs text-[#FFA552] hover:underline font-bold">
              Open Full Wishlist Page &rarr;
            </a>
          </div>

          <button 
            type="button" 
            onclick="moveAllWishlistToBag()" 
            class="btn-press w-full sm:w-auto px-5 py-2.5 rounded-xl bg-gradient-to-r from-[#FFA552] to-[#E88C35] hover:from-[#E88C35] hover:to-[#FFA552] text-white text-xs font-black shadow-md flex items-center justify-center gap-2 cursor-pointer transition-all"
          >
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            <span>Move All to Bag</span>
          </button>
        </div>
      ` : ''}

    </div>
  `;
}

/* ==========================================================================
   NOTIFICATION BELL (signed-in customers, staff and admins)
   Customers: order updates and replies from the shop.
   Staff: new orders, payment slips and customer messages (+ a link to the Messages inbox).
   ========================================================================== */
const PSA_BELL_ICON = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>';
let psaBellState = { unread: 0, unreadMessages: 0, items: [] };

function psaTimeAgo(iso) {
  const s = Math.max(0, (Date.now() - new Date(iso).getTime()) / 1000);
  if (s < 60) return 'just now';
  if (s < 3600) return `${Math.floor(s / 60)} min ago`;
  if (s < 86400) return `${Math.floor(s / 3600)} h ago`;
  return new Date(iso).toLocaleDateString([], { day: 'numeric', month: 'short' });
}

// Where the bell goes: next to the account or bag link, else at the start of the header's right-hand group.
function psaBellHost() {
  const header = document.querySelector('header');
  if (!header) return null;
  const anchor = header.querySelector('a[href="dashboard-buyer.html"], a[href="cart.html"]');
  if (anchor && anchor.parentElement) return { parent: anchor.parentElement, before: anchor };
  const groups = [...header.querySelectorAll('div')].filter(d =>
    d.querySelector(':scope > a, :scope > button') && getComputedStyle(d).display.includes('flex'));
  // Otherwise go at the far right of the header's main row
  const row = [...header.children].find(d => d.tagName === 'DIV' && d.offsetParent !== null && d.querySelector('a, img') && getComputedStyle(d).display.includes('flex'))
    || [...header.querySelectorAll(':scope > div > div')].find(d => d.offsetParent !== null && d.querySelector('a, img') && getComputedStyle(d).display.includes('flex'));
  if (row) return { parent: row, before: null };
  const group = groups[groups.length - 1];
  return group ? { parent: group, before: null } : null;
}

function psaRenderBell() {
  const total = psaBellState.unread;
  document.querySelectorAll('.psa-bell-badge').forEach(b => {
    b.textContent = total > 9 ? '9+' : String(total);
    b.classList.toggle('hidden', total === 0);
  });
  document.querySelectorAll('.psa-bell-btn').forEach(b => b.setAttribute('aria-label', total ? `Notifications, ${total} unread` : 'Notifications'));
  document.querySelectorAll('.psa-inbox-count').forEach(b => {
    b.textContent = psaBellState.unreadMessages;
    b.classList.toggle('hidden', !psaBellState.unreadMessages);
  });

  const panel = document.getElementById('psaBellPanel');
  if (!panel || panel.hidden) return;
  const isStaff = PSA.user && PSA.user.role !== 'Buyer';
  const items = psaBellState.items || [];
  panel.innerHTML = `
    <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-[#EFE4D6] dark:border-[#2D2D38]">
      <h2 class="text-base font-black text-[#2B1D1D]">Notifications</h2>
      ${total ? '<button type="button" data-bell-readall class="h-9 px-2 text-sm font-bold text-[#A3520F] dark:text-[#FFA552] hover:underline cursor-pointer">Mark all read</button>' : ''}
    </div>
    ${isStaff ? `<a href="admin-messages.html" class="flex items-center justify-between gap-3 px-4 py-3 border-b border-[#EFE4D6] dark:border-[#2D2D38] hover:bg-[#F9F3EA] dark:hover:bg-[#20202A]">
      <span class="text-sm font-black text-[#2B1D1D]">Messages inbox</span>
      <span class="text-sm font-bold ${psaBellState.unreadMessages ? 'text-rose-700' : 'text-stone-600'}">${psaBellState.unreadMessages ? psaBellState.unreadMessages + ' unread' : 'Open'}</span>
    </a>` : ''}
    <ul class="max-h-[60vh] overflow-y-auto divide-y divide-[#F3EAD9] dark:divide-[#2D2D38]">
      ${items.length ? items.map(n => `
        <li>
          <a href="${psaEsc(n.link || '#')}" data-bell-item="${Number(n.id)}" class="flex gap-3 px-4 py-3 hover:bg-[#F9F3EA] dark:hover:bg-[#20202A] ${n.read ? '' : 'bg-[#FFF6EC] dark:bg-[#231E1A]'}">
            <span class="mt-1.5 w-2 h-2 rounded-full shrink-0 ${n.read ? 'bg-transparent' : 'bg-[#E88C35]'}" aria-hidden="true"></span>
            <span class="min-w-0">
              <span class="block text-sm ${n.read ? 'font-semibold' : 'font-black'} text-[#2B1D1D]">${psaEsc(n.title)}${n.read ? '' : '<span class="sr-only"> (unread)</span>'}</span>
              ${n.body ? `<span class="block text-sm text-stone-600 line-clamp-2">${psaEsc(n.body)}</span>` : ''}
              <span class="block mt-0.5 text-xs text-stone-500">${psaEsc(psaTimeAgo(n.createdAt))}</span>
            </span>
          </a>
        </li>`).join('') : '<li class="px-4 py-8 text-center text-sm text-stone-600">Nothing new yet. Order updates and replies show up here.</li>'}
    </ul>`;
}

async function psaLoadNotifications() {
  if (!PSA.online || !PSA.user) return;
  try {
    psaBellState = await psaApi('GET', '/api/notifications');
    psaRenderBell();
  } catch (e) { /* keep the last known state */ }
}

function psaToggleBellPanel(force) {
  const panel = document.getElementById('psaBellPanel');
  const btn = document.querySelector('.psa-bell-btn');
  if (!panel || !btn) return;
  const open = typeof force === 'boolean' ? force : panel.hidden;
  if (open === !panel.hidden) return;
  panel.hidden = !open;
  btn.setAttribute('aria-expanded', String(open));
  if (open) {
    psaRenderBell();
    psaLoadNotifications();
  }
}

function psaInitNotifications() {
  if (!PSA.online || !PSA.user || document.querySelector('.psa-bell-btn')) return;
  const host = psaBellHost();
  if (!host) return;

  const wrap = document.createElement('div');
  wrap.className = 'relative shrink-0';
  wrap.innerHTML = `
    <button type="button" class="psa-bell-btn relative w-11 h-11 rounded-full flex items-center justify-center text-[#2B1D1D] dark:text-white bg-white dark:bg-[#1E1E26] border border-[#EFE4D6] dark:border-[#32323D] hover:bg-[#F9F3EA] cursor-pointer" aria-haspopup="true" aria-expanded="false" aria-controls="psaBellPanel" aria-label="Notifications">
      ${PSA_BELL_ICON}
      <span class="psa-bell-badge hidden absolute -top-1 -right-1 min-w-5 h-5 px-1 rounded-full bg-rose-600 text-white text-xs font-black flex items-center justify-center ring-2 ring-white dark:ring-[#1E1E26]"></span>
    </button>
    <div id="psaBellPanel" hidden class="fixed sm:absolute left-2 right-2 sm:left-auto sm:right-0 top-16 sm:top-12 sm:w-96 z-[60] rounded-2xl bg-white dark:bg-[#1A1A22] border border-[#EFE4D6] dark:border-[#2D2D38] shadow-[0_18px_40px_-12px_rgba(0,0,0,0.3)] overflow-hidden text-left" role="region" aria-label="Notifications"></div>`;
  host.parent.insertBefore(wrap, host.before || null);

  wrap.querySelector('.psa-bell-btn').addEventListener('click', e => { e.stopPropagation(); psaToggleBellPanel(); });
  wrap.querySelector('#psaBellPanel').addEventListener('click', async e => {
    e.stopPropagation();
    if (e.target.closest('[data-bell-readall]')) {
      await psaApi('POST', '/api/notifications/read').catch(() => null);
      (psaBellState.items || []).forEach(n => { n.read = true; });
      psaBellState.unread = 0;
      psaRenderBell();
      return;
    }
    const item = e.target.closest('[data-bell-item]');
    if (item) {
      e.preventDefault();
      await psaApi('POST', '/api/notifications/read', { id: Number(item.dataset.bellItem) }).catch(() => null);
      window.location.href = item.getAttribute('href');
    }
  });
  document.addEventListener('click', () => psaToggleBellPanel(false));
  document.addEventListener('keydown', e => { if (e.key === 'Escape') psaToggleBellPanel(false); });

  psaLoadNotifications();
  setInterval(() => { if (!document.hidden) psaLoadNotifications(); }, 30000);
}

document.addEventListener('DOMContentLoaded', psaInitNotifications);


// Stacked tables on phones: copy each column heading onto its cells as data-label (rows are rendered by page scripts).
function psaLabelStackTables(root = document) {
  root.querySelectorAll('table.psa-stack-table').forEach(table => {
    const heads = [...table.querySelectorAll('thead th')].map(th => th.textContent.replace(/\s+/g, ' ').trim());
    table.querySelectorAll('tbody tr').forEach(tr => {
      [...tr.children].forEach((td, i) => { if (heads[i] && !td.hasAttribute('data-label')) td.setAttribute('data-label', heads[i]); });
    });
  });
}
document.addEventListener('DOMContentLoaded', () => {
  if (!document.querySelector('table.psa-stack-table')) return;
  psaLabelStackTables();
  new MutationObserver(() => psaLabelStackTables()).observe(document.body, { childList: true, subtree: true });
});

// Psa Bunny chat mascot on shopper pages (not on chat itself, checkout, settings, sign-in or staff pages).
const PSA_MASCOT_PAGES = ['', 'home.html', 'index.html', 'products.html', 'product-detail.html', 'cart.html', 'wishlist.html', 'viewed.html', 'dashboard-buyer.html', 'order-detail.html', 'order-detail--buyer-pending.html', 'about.html', 'privacy.html', 'terms.html'];
document.addEventListener('DOMContentLoaded', () => {
  const page = psaPageName();
  if (!PSA_MASCOT_PAGES.includes(page) && !(page === 'home.html' && PSA_MASCOT_PAGES.includes(''))) return;
  const s = document.createElement('script');
  s.src = 'assets/js/mascot-chat.js';
  s.defer = true;
  document.body.appendChild(s);
});
