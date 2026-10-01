/**
 * PsaOnline - Gen-Z Street & Aesthetic Accessories Marketplace Engine
 * Sorbet Orange (#FF5000), Cotton Beige (#FFF3EC), Deep Espresso (#333333) & Clean White (#FFFFFF)
 */

const EXCHANGE_RATE = 4100; // 1 USD = 4,100 KHR

// Gen-Z Trendy Accessories Catalog (Affordable, Aesthetic, TikTok & Streetwear Trending)
const ACCESSORIES_PRODUCTS = [
  {
    id: "genz-01",
    title: "Silver Chrome Star Pendant Necklace",
    titleKhmer: "ខ្សែកបន្តោងផ្កាយប្រាក់ Y2K",
    category: "jewelry",
    categoryLabel: "Y2K Jewelry",
    priceUSD: 6.50,
    priceKHR: Math.round(6.50 * EXCHANGE_RATE),
    inStock: true,
    rating: 4.9,
    reviewsCount: 142,
    badge: "Trending ⚡",
    image: "https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?auto=format&fit=crop&w=800&q=80",
    gallery: [
      "https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?auto=format&fit=crop&w=800&q=80",
      "https://images.unsplash.com/photo-1611591475819-797de2338ec8?auto=format&fit=crop&w=800&q=80",
      "https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?auto=format&fit=crop&w=800&q=80"
    ],
    description: "Layered stainless steel chain with a chrome Cyber Y2K star motif. Tarnish-free, hypoallergenic, and perfect for streetwear daily fits.",
    specifications: {
      "Material": "316L Stainless Steel (Tarnish-free)",
      "Chain Length": "45cm + 5cm adjustable extension",
      "Pendant Size": "2.2cm x 2.2cm",
      "Gender": "Unisex (Streetwear / Gen-Z)"
    }
  },
  {
    id: "genz-02",
    title: "Chunky Cyberpunk Silver Ring Set (4 Pcs)",
    titleKhmer: "ឈុតចិញ្ចៀនប្រាក់ Cyberpunk (៤ វង់)",
    category: "jewelry",
    categoryLabel: "Y2K Jewelry",
    priceUSD: 4.50,
    priceKHR: Math.round(4.50 * EXCHANGE_RATE),
    inStock: true,
    rating: 4.8,
    reviewsCount: 98,
    badge: "Best Value",
    image: "https://images.unsplash.com/photo-1605100804763-247f67b3557e?auto=format&fit=crop&w=800&q=80",
    gallery: [
      "https://images.unsplash.com/photo-1605100804763-247f67b3557e?auto=format&fit=crop&w=800&q=80",
      "https://images.unsplash.com/photo-1603561591411-07134e71a2a9?auto=format&fit=crop&w=800&q=80",
      "https://images.unsplash.com/photo-1602751584552-8ba73aad10e1?auto=format&fit=crop&w=800&q=80"
    ],
    description: "4-piece grunge & cyber aesthetic open-stacking rings. Adjustable sizing so they comfortably fit any finger.",
    specifications: {
      "Pack Quantity": "4 Unique Stacking Rings",
      "Sizing": "Adjustable Open Band (US 6-10)",
      "Finish": "Polished Silver Chrome",
      "Weight": "Ultra-lightweight alloy"
    }
  },
  {
    id: "genz-03",
    title: "Retro 90s Tinted Oval Sunglasses",
    titleKhmer: "វ៉ែនតាការពារកម្ដៅថ្ងៃម៉ូដ 90s Retro",
    category: "eyewear",
    categoryLabel: "Shades & Eyewear",
    priceUSD: 7.50,
    priceKHR: Math.round(7.50 * EXCHANGE_RATE),
    inStock: true,
    rating: 4.9,
    reviewsCount: 215,
    badge: "TikTok Viral ✨",
    image: "https://images.unsplash.com/photo-1511499767150-a48a237f0083?auto=format&fit=crop&w=800&q=80",
    gallery: [
      "https://images.unsplash.com/photo-1511499767150-a48a237f0083?auto=format&fit=crop&w=800&q=80",
      "https://images.unsplash.com/photo-1508296695146-257a814070b4?auto=format&fit=crop&w=800&q=80",
      "https://images.unsplash.com/photo-1473496169904-658ba7c44d8a?auto=format&fit=crop&w=800&q=80"
    ],
    description: "Aesthetic oval narrow sunglasses with UV400 protective lenses. Lightweight frame suited for café runs, concerts, and sunny Phnom Penh days.",
    specifications: {
      "Protection": "UV400 Total Block",
      "Frame Material": "High-grade acetate",
      "Lens Tint": "Subtle Dark Olive / Smoke",
      "Included": "Microfiber pouch & cleaning cloth"
    }
  },
  {
    id: "genz-04",
    title: "Cyber Rimless Gradient Shield Sunglasses",
    titleKhmer: "វ៉ែនតាគ្មានគែម Y2K Cyber Shield",
    category: "eyewear",
    categoryLabel: "Shades & Eyewear",
    priceUSD: 8.00,
    priceKHR: Math.round(8.00 * EXCHANGE_RATE),
    inStock: true,
    rating: 4.7,
    reviewsCount: 76,
    badge: "New Drop 🔥",
    image: "https://images.unsplash.com/photo-1572635196237-14b3f281503f?auto=format&fit=crop&w=800&q=80",
    gallery: [
      "https://images.unsplash.com/photo-1572635196237-14b3f281503f?auto=format&fit=crop&w=800&q=80",
      "https://images.unsplash.com/photo-1509695507497-903c140c43b0?auto=format&fit=crop&w=800&q=80",
      "https://images.unsplash.com/photo-1577803645773-f96470509666?auto=format&fit=crop&w=800&q=80"
    ],
    description: "Frameless one-piece gradient shield shades inspired by early 2000s street fashion. Ultra sleek metallic temples.",
    specifications: {
      "Style": "Rimless Y2K Wrap",
      "Lens": "Polycarbonate Anti-Scratch",
      "Temple Material": "Alloy Chrome",
      "Fit": "Medium to Wide Face"
    }
  },
  {
    id: "genz-05",
    title: "Puffy Cloud Quilted Nylon Shoulder Bag",
    titleKhmer: "កាបូបស្ពាយសាច់ប៉ោង Puffy Cloud Bag",
    category: "bags",
    categoryLabel: "Bags & Totes",
    priceUSD: 14.50,
    priceKHR: Math.round(14.50 * EXCHANGE_RATE),
    inStock: true,
    rating: 5.0,
    reviewsCount: 198,
    badge: "Bestseller ☁️",
    image: "https://images.unsplash.com/photo-1584917865442-de89df76afd3?auto=format&fit=crop&w=800&q=80",
    gallery: [
      "https://images.unsplash.com/photo-1584917865442-de89df76afd3?auto=format&fit=crop&w=800&q=80",
      "https://images.unsplash.com/photo-1544816155-12df9643f363?auto=format&fit=crop&w=800&q=80",
      "https://images.unsplash.com/photo-1590874103328-eac38a683ce7?auto=format&fit=crop&w=800&q=80"
    ],
    description: "Ultra soft puffy dumpling silhouette with cloud padding. Roomy interior fits iPad Mini, lip gloss, wallet, and sunglasses.",
    specifications: {
      "Material": "Waterproof Padded Nylon",
      "Closure": "Smooth YKK Zipper",
      "Pockets": "1 Inner zipper pocket + slip pocket",
      "Strap": "Comfortable ruched puffy strap"
    }
  },
  {
    id: "genz-06",
    title: "Silver Metallic Mini Crossbody Dumpling",
    titleKhmer: "កាបូបស្ពាយតូចពណ៍ប្រាក់ Y2K",
    category: "bags",
    categoryLabel: "Bags & Totes",
    priceUSD: 12.00,
    priceKHR: Math.round(12.00 * EXCHANGE_RATE),
    inStock: true,
    rating: 4.8,
    reviewsCount: 64,
    badge: "Chrome Edition",
    image: "https://images.unsplash.com/photo-1548036328-c9fa89d128fa?auto=format&fit=crop&w=800&q=80",
    gallery: [
      "https://images.unsplash.com/photo-1548036328-c9fa89d128fa?auto=format&fit=crop&w=800&q=80",
      "https://images.unsplash.com/photo-1566150905458-1bf1fc113f0d?auto=format&fit=crop&w=800&q=80",
      "https://images.unsplash.com/photo-1591561954557-26941169b49e?auto=format&fit=crop&w=800&q=80"
    ],
    description: "Futuristic foil metallic finish with reflective sheen. Lightweight everyday compact bag for night-outs and casual streetwear.",
    specifications: {
      "Dimensions": "20cm x 13cm x 7cm",
      "Material": "High-durability metallic PU",
      "Weight": "190 grams ultra-light"
    }
  },
  {
    id: "genz-07",
    title: "French Matte Pastel Claw Clip Trio (3 Pcs)",
    titleKhmer: "ឈុតដង្កៀបសក់ Pastel (៣ ដុំ)",
    category: "hair",
    categoryLabel: "Hair Accessories",
    priceUSD: 3.50,
    priceKHR: Math.round(3.50 * EXCHANGE_RATE),
    inStock: true,
    rating: 4.9,
    reviewsCount: 312,
    badge: "Must-Have 🌸",
    image: "https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=800&q=80",
    gallery: [
      "https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=800&q=80",
      "https://images.unsplash.com/photo-1596462502278-27bfdc403348?auto=format&fit=crop&w=800&q=80",
      "https://images.unsplash.com/photo-1607083206869-4c7672e72a8a?auto=format&fit=crop&w=800&q=80"
    ],
    description: "Tough flexible acetate claw clips with strong steel springs and a velvety matte rubberized coating that won't pull hair.",
    specifications: {
      "Set Includes": "Cotton Cream, Sorbet Orange, Muted Espresso",
      "Length": "10.5cm large clip",
      "Hold Type": "All-day firm grip for thick & fine hair"
    }
  },
  {
    id: "genz-08",
    title: "Silver Chrome Y2K Star Hair Pins (Set of 6)",
    titleKhmer: "កូនខ្ទាស់សក់ផ្កាយប្រាក់ Y2K (៦ គ្រាប់)",
    category: "hair",
    categoryLabel: "Hair Accessories",
    priceUSD: 3.00,
    priceKHR: Math.round(3.00 * EXCHANGE_RATE),
    inStock: true,
    rating: 4.9,
    reviewsCount: 184,
    badge: "TikTok Trend",
    image: "https://images.unsplash.com/photo-1535295972055-1c762f4483e5?auto=format&fit=crop&w=800&q=80",
    gallery: [
      "https://images.unsplash.com/photo-1535295972055-1c762f4483e5?auto=format&fit=crop&w=800&q=80",
      "https://images.unsplash.com/photo-1606760227091-3dd870d97f1d?auto=format&fit=crop&w=800&q=80",
      "https://images.unsplash.com/photo-1519699047748-de8e457a634e?auto=format&fit=crop&w=800&q=80"
    ],
    description: "Six mini silver cyber stars to snap onto braids, bangs, and buns. Instant aesthetic upgrade for effortless street style.",
    specifications: {
      "Pack": "6 Stainless Star Snap Pins",
      "Finish": "Chrome Polish",
      "Grip": "Snag-free steel snap clip"
    }
  },
  {
    id: "genz-09",
    title: "Iridescent Butterfly Beaded Phone Lanyard",
    titleKhmer: "ខ្សែពាក់ទូរស័ព្ទអង្កាំមេអំបៅ Cute Charm",
    category: "charms",
    categoryLabel: "Tech Charms",
    priceUSD: 4.00,
    priceKHR: Math.round(4.00 * EXCHANGE_RATE),
    inStock: true,
    rating: 4.9,
    reviewsCount: 167,
    badge: "Hand-Strung 💖",
    image: "https://images.unsplash.com/photo-1599643477877-530eb83abc8e?auto=format&fit=crop&w=800&q=80",
    gallery: [
      "https://images.unsplash.com/photo-1599643477877-530eb83abc8e?auto=format&fit=crop&w=800&q=80",
      "https://images.unsplash.com/photo-1582142839970-2b9da1978253?auto=format&fit=crop&w=800&q=80",
      "https://images.unsplash.com/photo-1563245372-f21724e3856d?auto=format&fit=crop&w=800&q=80"
    ],
    description: "Cute phone wrist lanyard with iridescent beads, glass pearls, and acrylic butterfly accents. Attaches to any phone case loophole.",
    specifications: {
      "Loop Length": "22cm wrist loop",
      "Cord": "Heavy-duty nylon braided tether",
      "Compatibility": "Universal phone cases & cameras"
    }
  },
  {
    id: "genz-10",
    title: "Silver Chunky Heart Locket Keychain",
    titleKhmer: "បន្តោងសោររូបបេះដូងប្រាក់ Y2K Locket",
    category: "charms",
    categoryLabel: "Tech Charms",
    priceUSD: 4.50,
    priceKHR: Math.round(4.50 * EXCHANGE_RATE),
    inStock: true,
    rating: 4.7,
    reviewsCount: 52,
    badge: "Trending ⚡",
    image: "https://images.unsplash.com/photo-1582142839970-2b9da1978253?auto=format&fit=crop&w=800&q=80",
    gallery: [
      "https://images.unsplash.com/photo-1582142839970-2b9da1978253?auto=format&fit=crop&w=800&q=80",
      "https://images.unsplash.com/photo-1611591475819-797de2338ec8?auto=format&fit=crop&w=800&q=80",
      "https://images.unsplash.com/photo-1508296695146-257a814070b4?auto=format&fit=crop&w=800&q=80"
    ],
    description: "Functional photo locket keychain with heavy metallic carabiner clip. Clip it onto your belt loop, backpack, or tote bag.",
    specifications: {
      "Function": "Opens to insert mini photo",
      "Clip": "Quick-release spring carabiner",
      "Material": "Solid metal alloy"
    }
  },
  {
    id: "genz-11",
    title: "Washed Vintage Cotton Streetwear Cap",
    titleKhmer: "មួកកាតឹប Streetwear បែប Vintage",
    category: "hair",
    categoryLabel: "Streetwear Gear",
    priceUSD: 8.50,
    priceKHR: Math.round(8.50 * EXCHANGE_RATE),
    inStock: true,
    rating: 4.9,
    reviewsCount: 112,
    badge: "Street Essential",
    image: "https://images.unsplash.com/photo-1588850561407-ed78c282e89b?auto=format&fit=crop&w=800&q=80",
    gallery: [
      "https://images.unsplash.com/photo-1588850561407-ed78c282e89b?auto=format&fit=crop&w=800&q=80",
      "https://images.unsplash.com/photo-1521369909029-2afed882baee?auto=format&fit=crop&w=800&q=80",
      "https://images.unsplash.com/photo-1534215754734-18e55d13e346?auto=format&fit=crop&w=800&q=80"
    ],
    description: "Low-profile distressed washed cotton dad hat with embroidered minimalist typography. Adjustable metal buckle back.",
    specifications: {
      "Material": "100% Washed Vintage Cotton",
      "Crown": "Unstructured 6-Panel Low Profile",
      "Strap": "Antique brass buckle adjustment"
    }
  },
  {
    id: "genz-12",
    title: "Liquid Metal Abstract Ear Cuff & Huggie Set",
    titleKhmer: "ក្រវិលប្រាក់ទាន់សម័យ Liquid Metal (២ ដុំ)",
    category: "jewelry",
    categoryLabel: "Y2K Jewelry",
    priceUSD: 5.00,
    priceKHR: Math.round(5.00 * EXCHANGE_RATE),
    inStock: true,
    rating: 4.8,
    reviewsCount: 88,
    badge: "No Piercing Needed",
    image: "https://images.unsplash.com/photo-1630019852942-f89202989a59?auto=format&fit=crop&w=800&q=80",
    gallery: [
      "https://images.unsplash.com/photo-1630019852942-f89202989a59?auto=format&fit=crop&w=800&q=80",
      "https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?auto=format&fit=crop&w=800&q=80",
      "https://images.unsplash.com/photo-1602751584552-8ba73aad10e1?auto=format&fit=crop&w=800&q=80"
    ],
    description: "Futuristic molten silver ear cuff that clips onto upper ear cartilage without piercing, paired with a chunky mini hoop.",
    specifications: {
      "Pieces": "1 Ear Cuff (No Piercing) + 1 Mini Huggie",
      "Material": "S925 Sterling Silver Plated",
      "Hypoallergenic": "Nickel & Lead Free"
    }
  }
];

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

function removeFromCart(productId) {
  let cart = getCart();
  cart = cart.filter(item => item.id !== productId);
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
      btn.classList.add('bg-[#333333]', 'text-white', 'shadow-xs');
      btn.classList.remove('text-[#333333]');
    } else {
      btn.classList.remove('bg-[#333333]', 'text-white', 'shadow-xs');
      btn.classList.add('text-[#333333]');
    }
  });

  // Re-render any dynamic prices
  if (typeof initTbHot === 'function') initTbHot();
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
    <div class="bg-white border-2 border-[#FF5000] shadow-2xl rounded-2xl p-4 max-w-sm flex items-center gap-3.5 pointer-events-auto">
      <div class="w-14 h-14 rounded-xl bg-white border border-[#EDEDED] p-1 overflow-hidden shrink-0 flex items-center justify-center">
        <img src="${product.image}" alt="${product.title}" class="w-full h-full object-cover rounded-lg" />
      </div>
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-1.5 text-[11px] font-bold text-[#FF5000]">
          <span class="w-2 h-2 rounded-full bg-[#FF5000] animate-ping"></span>
          Added to Bag!
        </div>
        <div class="text-xs font-black text-[#333333] truncate mt-0.5">${product.title}</div>
        <div class="text-[11px] font-semibold text-[#666666] mt-0.5">${formatPrice(product.priceUSD, product.priceKHR)} &bull; Qty: ${qty}</div>
      </div>
      <a href="cart.html" class="px-3.5 py-2 rounded-xl bg-[#FF5000] hover:bg-[#E64500] text-white font-extrabold text-xs shrink-0 transition-colors shadow-sm">
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
        <div class="text-[11px] font-black uppercase tracking-wider text-[#333333] dark:text-white">Product Details & Specs</div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
          ${specsEntries.map(([k, v]) => `
            <div class="bg-[#FFF3EC] dark:bg-[#23232C] border border-[#EDEDED] dark:border-[#32323D] rounded-xl p-2.5 flex flex-col justify-center">
              <span class="text-[10px] uppercase font-bold text-[#FF5000]">${k}</span>
              <span class="font-extrabold text-[#333333] dark:text-white truncate mt-0.5">${v}</span>
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
    <div class="relative w-full max-w-3xl bg-white dark:bg-[#1A1A22] rounded-3xl border-2 border-[#FF5000] shadow-2xl overflow-hidden transform transition-all duration-300 scale-95 opacity-0 my-auto text-[#333333] dark:text-white" id="quickViewCard">
      
      <!-- Close Button (Top-Right) -->
      <button 
        type="button" 
        onclick="closeQuickView()" 
        class="absolute top-4 right-4 z-20 w-9 h-9 rounded-full bg-[#FFF3EC] dark:bg-[#2A2A34] hover:bg-[#E64500] hover:text-white text-[#333333] dark:text-white flex items-center justify-center border border-[#EDEDED] dark:border-[#3A3A46] transition-all duration-200 hover:scale-105 shadow-sm cursor-pointer"
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
          <div class="aspect-square w-full rounded-2xl bg-[#F5F5F5] dark:bg-[#141419] border border-[#EDEDED] dark:border-[#2D2D38] p-3 flex items-center justify-center relative overflow-hidden group shadow-inner">
            <img 
              id="quickViewMainImage" 
              src="${galleryList[0]}" 
              alt="${product.title}" 
              class="w-full h-full object-cover rounded-xl transition-all duration-300 group-hover:scale-105"
            />
            
            <!-- Badges -->
            <div class="absolute top-3 left-3 flex flex-col gap-1.5 z-10 pointer-events-none">
              <span class="bg-[#FF5000] text-white px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider shadow-sm">
                ${product.badge}
              </span>
              <span class="bg-[#333333] dark:bg-[#4D4D4D] text-[#FFF3EC] px-2.5 py-0.5 rounded-full text-[9px] font-extrabold tracking-wide shadow-sm">
                ${product.categoryLabel}
              </span>
            </div>

            <!-- Zoom Indicator -->
            <div class="absolute bottom-3 right-3 bg-white/95 dark:bg-[#1E1E26]/90 backdrop-blur-sm text-[#333333] dark:text-white px-2.5 py-1 rounded-full text-[10px] font-bold border border-[#EDEDED] dark:border-[#2D2D38] shadow-xs flex items-center gap-1 pointer-events-none">
              <svg class="w-3.5 h-3.5 text-[#FF5000]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/></svg>
              <span>Hover for Zoom</span>
            </div>
          </div>

          <!-- Gallery Thumbnails (Interactive View Switcher) -->
          <div class="flex items-center gap-2 overflow-x-auto pb-1 select-none" id="quickViewGalleryThumbs">
            ${galleryList.map((img, idx) => `
              <button 
                type="button" 
                onclick="switchQuickViewImage('${img}', this)"
                class="gallery-thumb-btn w-14 h-14 rounded-xl border-2 overflow-hidden shrink-0 cursor-pointer ${idx === 0 ? 'active-thumb border-[#FF5000]' : 'border-[#EDEDED] dark:border-[#2D2D38] opacity-75 hover:opacity-100'}"
                title="View Gallery Angle ${idx + 1}"
              >
                <img src="${img}" alt="${product.title} angle ${idx + 1}" class="w-full h-full object-cover" />
              </button>
            `).join('')}
          </div>

          <!-- Studio Trust Perks -->
          <div class="bg-[#FFF3EC] dark:bg-[#20202A] rounded-xl p-3 border border-[#EDEDED] dark:border-[#30303E] space-y-1.5 text-[11px] text-[#666666] dark:text-[#E4E4E7] font-semibold">
            <div class="flex items-center gap-2">
              <span class="w-4 h-4 rounded-full bg-[#FF5000] text-white flex items-center justify-center text-[10px] font-black shrink-0">✓</span>
              <span>Phnom Penh 1-2hr Express Courier Available</span>
            </div>
            <div class="flex items-center gap-2">
              <span class="w-4 h-4 rounded-full bg-[#FF5000] text-white flex items-center justify-center text-[10px] font-black shrink-0">✓</span>
              <span>Universal KHQR Scan & Pay (ABA, ACLEDA, Wing)</span>
            </div>
          </div>
        </div>

        <!-- Right Column: Product Details & Cart Actions -->
        <div class="md:col-span-6 flex flex-col justify-between space-y-4">
          
          <div class="space-y-3">
            <!-- Category and Stock Indicator -->
            <div class="flex items-center justify-between gap-2 pr-8">
              <span class="text-xs font-black text-[#FF5000] uppercase tracking-wider">${product.categoryLabel}</span>
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 text-[10px] font-extrabold">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                In Stock & Ready to Dispatch
              </span>
            </div>

            <!-- Title & Subtitle -->
            <div>
              <h2 id="quickViewTitle" class="text-lg sm:text-xl font-black text-[#333333] dark:text-white tracking-tight leading-snug">
                ${product.title}
              </h2>
              <p class="text-xs text-stone-500 dark:text-stone-300 font-khmer font-semibold mt-1">
                ${product.titleKhmer}
              </p>
            </div>

            <!-- Ratings -->
            <div class="flex items-center gap-2 text-xs">
              <div class="flex items-center text-amber-500 font-black">
                <span>★ ★ ★ ★ ★</span>
                <span class="ml-1 text-[#333333] dark:text-white font-bold">${product.rating}</span>
              </div>
              <span class="text-stone-300 dark:text-stone-600">&bull;</span>
              <span class="text-stone-500 dark:text-stone-300 font-semibold">${product.reviewsCount} verified reviews</span>
            </div>

            <!-- Pricing Box -->
            <div class="p-3 bg-[#F5F5F5] dark:bg-[#16161D] rounded-2xl border border-[#EDEDED] dark:border-[#2D2D38] flex items-baseline justify-between">
              <div>
                <span class="text-2xl font-black text-[#333333] dark:text-white" id="quickViewPricePrimary">
                  ${formatPrice(product.priceUSD, product.priceKHR)}
                </span>
                <span class="text-xs font-bold text-[#FF5000] ml-2" id="quickViewPriceSecondary">
                  (${altCurrency})
                </span>
              </div>
              <span class="text-[10px] font-black uppercase text-[#333333] dark:text-white px-2 py-0.5 rounded-md bg-[#FF5000]/20 border border-[#FF5000]/40">
                1 USD = 4,100 ៛
              </span>
            </div>

            <!-- Description -->
            <p class="text-xs text-[#666666] dark:text-[#E4E4E7] leading-relaxed font-medium">
              ${product.description}
            </p>

            <!-- Specifications Table -->
            ${specsHtml}
          </div>

          <!-- Bottom Actions & Quantity Selector -->
          <div class="pt-3 border-t border-[#EDEDED] dark:border-[#2D2D38] space-y-3">
            
            <div class="flex items-center gap-3">
              <!-- Quantity Stepper -->
              <div class="flex items-center border border-[#EDEDED] dark:border-[#32323D] bg-[#F5F5F5] dark:bg-[#16161D] rounded-xl overflow-hidden shrink-0 shadow-xs">
                <button 
                  type="button" 
                  onclick="changeQuickViewQty(-1)" 
                  class="w-9 h-10 flex items-center justify-center font-black text-sm text-[#333333] dark:text-white hover:bg-[#E64500] hover:text-white transition-colors cursor-pointer"
                  aria-label="Decrease quantity"
                >-</button>
                <span id="quickViewQtyVal" class="w-10 text-center text-xs font-black text-[#333333] dark:text-white">1</span>
                <button 
                  type="button" 
                  onclick="changeQuickViewQty(1)" 
                  class="w-9 h-10 flex items-center justify-center font-black text-sm text-[#333333] dark:text-white hover:bg-[#E64500] hover:text-white transition-colors cursor-pointer"
                  aria-label="Increase quantity"
                >+</button>
              </div>

              <!-- Add to Bag Button -->
              <button 
                type="button" 
                id="quickViewAddBtn"
                onclick="addQuickViewToBag()" 
                class="btn-press flex-1 py-3 px-4 rounded-xl bg-gradient-to-r from-[#FF5000] to-[#E64500] hover:from-[#E64500] hover:to-[#FF5000] text-white font-black text-xs shadow-md flex items-center justify-center gap-2 cursor-pointer transition-all"
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
                class="wishlist-btn p-3 rounded-xl border border-[#EDEDED] dark:border-[#32323D] bg-[#F5F5F5] dark:bg-[#16161D] hover:bg-rose-50 dark:hover:bg-rose-950/40 text-stone-400 hover:text-rose-500 transition-all flex items-center justify-center shadow-xs cursor-pointer ${isInWishlist(product.id) ? 'active-wishlist text-rose-500' : ''}"
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
                class="text-xs font-extrabold text-[#FF5000] hover:text-[#333333] dark:hover:text-white flex items-center gap-1 transition-colors underline"
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
    b.classList.remove('active-thumb', 'border-[#FF5000]');
    b.classList.add('border-[#EDEDED]', 'dark:border-[#2D2D38]', 'opacity-75');
  });
  if (btn) {
    btn.classList.add('active-thumb', 'border-[#FF5000]');
    btn.classList.remove('border-[#EDEDED]', 'dark:border-[#2D2D38]', 'opacity-75');
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
    feedback.className = 'text-xs font-semibold text-[#FF5000] min-h-[1.25rem] text-left sm:text-center transition-all';
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
        colors: ['#FF5000', '#FFF3EC', '#333333', '#E64500']
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
    <div class="bg-white dark:bg-[#1E1E26] border-2 ${isAdded ? 'border-rose-400 shadow-[0_10px_25px_-5px_rgba(244,63,94,0.35)]' : 'border-[#EDEDED] dark:border-[#32323D]'} rounded-2xl p-3.5 max-w-sm flex items-center gap-3 pointer-events-auto">
      <div class="w-10 h-10 rounded-xl ${isAdded ? 'bg-rose-50 text-rose-500 dark:bg-rose-950/60 dark:text-rose-400' : 'bg-stone-100 text-stone-500 dark:bg-stone-800 dark:text-stone-400'} flex items-center justify-center shrink-0">
        <svg class="w-5 h-5 ${isAdded ? 'animate-bounce' : ''}" fill="${isAdded ? 'currentColor' : 'none'}" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
      </div>
      <div class="flex-1 min-w-0 text-xs">
        <div class="font-black text-[#333333] dark:text-white truncate">${productTitle}</div>
        <div class="text-[11px] font-semibold ${isAdded ? 'text-rose-600 dark:text-rose-400' : 'text-stone-500'}">
          ${isAdded ? 'Saved to your Wishlist! 💖' : 'Removed from Wishlist'}
        </div>
      </div>
      <button onclick="openWishlistModal()" class="px-2.5 py-1.5 rounded-lg bg-[#FF5000] hover:bg-[#E64500] text-white text-[11px] font-bold shrink-0 transition-colors shadow-xs cursor-pointer">
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
    <div class="relative w-full max-w-2xl bg-white dark:bg-[#1A1A22] rounded-3xl border-2 border-rose-300 dark:border-rose-900/60 shadow-2xl overflow-hidden transform transition-all duration-300 scale-95 opacity-0 my-auto text-[#333333] dark:text-white flex flex-col max-h-[90vh]" id="wishlistModalCard">
      
      <!-- Header -->
      <div class="px-6 py-5 border-b border-[#EDEDED] dark:border-[#2D2D38] flex items-center justify-between bg-[#F5F5F5] dark:bg-[#15151B]">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-rose-50 dark:bg-rose-950/60 text-rose-500 flex items-center justify-center text-lg shadow-xs">
            💖
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h2 id="wishlistModalTitle" class="text-lg sm:text-xl font-black text-[#333333] dark:text-white tracking-tight">Your Wishlist</h2>
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
          class="w-9 h-9 rounded-full bg-[#FFF3EC] dark:bg-[#252530] hover:bg-[#E64500] hover:text-white text-[#333333] dark:text-white flex items-center justify-center border border-[#EDEDED] dark:border-[#32323D] transition-all hover:scale-105 cursor-pointer shadow-xs"
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
              <h3 class="text-base sm:text-lg font-black text-[#333333] dark:text-white">Your Wishlist is Empty</h3>
              <p class="text-xs text-stone-500 dark:text-stone-400 max-w-sm mx-auto leading-relaxed">
                Click the heart icon on any drop in the catalog to bookmark your favorite Y2K jewelry, shades, and cloud bags!
              </p>
            </div>
            <div class="pt-2">
              <a href="products.html" onclick="closeWishlistModal()" class="btn-press inline-flex items-center gap-2 px-6 py-2.5 rounded-full bg-[#FF5000] hover:bg-[#E64500] text-white font-black text-xs shadow-md transition-all">
                <span>Explore Drops &bull; All Catalog</span>
                <span>&rarr;</span>
              </a>
            </div>
          </div>
        ` : `
          <div class="space-y-3">
            ${products.map(item => `
              <div class="p-3 sm:p-4 rounded-2xl bg-[#F5F5F5] dark:bg-[#15151B] border border-[#EDEDED] dark:border-[#2D2D38] flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4 transition-all hover:border-[#FF5000]">
                <div class="flex items-center gap-3 min-w-0">
                  <a href="product-detail.html?id=${item.id}" onclick="closeWishlistModal()" class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl bg-white dark:bg-[#1C1C24] border border-[#EDEDED] dark:border-[#2D2D38] p-1 overflow-hidden shrink-0 block">
                    <img src="${item.image}" alt="${item.title}" class="w-full h-full object-cover rounded-lg hover:scale-110 transition-transform" />
                  </a>
                  <div class="min-w-0 flex-1">
                    <div class="text-[10px] font-black uppercase text-[#FF5000] tracking-wider">${item.categoryLabel}</div>
                    <a href="product-detail.html?id=${item.id}" onclick="closeWishlistModal()" class="text-xs sm:text-sm font-extrabold text-[#333333] dark:text-white hover:text-[#FF5000] transition-colors truncate block">
                      ${item.title}
                    </a>
                    <div class="text-[11px] text-stone-400 font-khmer truncate mt-0.5">${item.titleKhmer}</div>
                    <div class="text-xs font-black text-[#333333] dark:text-white mt-1">
                      ${formatPrice(item.priceUSD, item.priceKHR)}
                      <span class="text-[10px] font-bold text-[#FF5000] ml-1">(${CURRENT_CURRENCY === 'USD' ? item.priceKHR.toLocaleString() + ' ៛' : '$' + item.priceUSD.toFixed(2)})</span>
                    </div>
                  </div>
                </div>

                <div class="flex items-center gap-2 self-end sm:self-center shrink-0">
                  <button
                    type="button"
                    onclick="moveWishlistItemToCart('${item.id}', event)"
                    class="btn-press px-3.5 py-2 rounded-xl bg-[#FF5000] hover:bg-[#E64500] text-white text-xs font-black shadow-xs flex items-center gap-1.5 transition-all cursor-pointer"
                    title="Add to Bag"
                  >
                    <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    <span>Add to Bag</span>
                  </button>

                  <button
                    type="button"
                    onclick="openQuickView('${item.id}', event); closeWishlistModal();"
                    class="p-2 rounded-xl bg-white dark:bg-[#20202A] hover:bg-[#FFF3EC] dark:hover:bg-[#2D2D38] border border-[#EDEDED] dark:border-[#32323D] text-[#333333] dark:text-white transition-all cursor-pointer"
                    title="Quick View"
                  >
                    <svg class="w-4 h-4 text-stone-600 dark:text-stone-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                  </button>

                  <button
                    type="button"
                    onclick="removeWishlistItem('${item.id}', event)"
                    class="p-2 rounded-xl bg-white dark:bg-[#20202A] hover:bg-rose-50 dark:hover:bg-rose-950/40 border border-[#EDEDED] dark:border-[#32323D] text-stone-400 hover:text-rose-500 transition-all cursor-pointer"
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
        <div class="px-6 py-4 bg-[#F5F5F5] dark:bg-[#15151B] border-t border-[#EDEDED] dark:border-[#2D2D38] flex flex-col sm:flex-row items-center justify-between gap-3">
          <div class="flex items-center gap-3 text-xs">
            <button 
              type="button" 
              onclick="clearWishlist()" 
              class="text-xs text-stone-400 hover:text-rose-600 font-bold transition-colors cursor-pointer"
            >
              Clear All (${products.length})
            </button>
            <span class="text-stone-300 dark:text-stone-600">&bull;</span>
            <a href="wishlist.html" onclick="closeWishlistModal()" class="text-xs text-[#FF5000] hover:underline font-bold">
              Open Full Wishlist Page &rarr;
            </a>
          </div>

          <button 
            type="button" 
            onclick="moveAllWishlistToBag()" 
            class="btn-press w-full sm:w-auto px-5 py-2.5 rounded-xl bg-gradient-to-r from-[#FF5000] to-[#E64500] hover:from-[#E64500] hover:to-[#FF5000] text-white text-xs font-black shadow-md flex items-center justify-center gap-2 cursor-pointer transition-all"
          >
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            <span>Move All to Bag</span>
          </button>
        </div>
      ` : ''}

    </div>
  `;
}
