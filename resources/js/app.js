/**
 * PsaOnline - Gen-Z Street & Aesthetic Accessories Marketplace Engine
 * Sorbet Orange (#FFA552), Cotton Beige (#F9F3EA), Deep Espresso (#2B1D1D) & Clean White (#FFFFFF)
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
    image: "https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?auto=format&fit=crop&w=600&q=80",
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
    image: "https://images.unsplash.com/photo-1605100804763-247f67b3557e?auto=format&fit=crop&w=600&q=80",
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
    image: "https://images.unsplash.com/photo-1511499767150-a48a237f0083?auto=format&fit=crop&w=600&q=80",
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
    image: "https://images.unsplash.com/photo-1572635196237-14b3f281503f?auto=format&fit=crop&w=600&q=80",
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
    image: "https://images.unsplash.com/photo-1584917865442-de89df76afd3?auto=format&fit=crop&w=600&q=80",
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
    image: "https://images.unsplash.com/photo-1548036328-c9fa89d128fa?auto=format&fit=crop&w=600&q=80",
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
    image: "https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=600&q=80",
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
    image: "https://images.unsplash.com/photo-1535295972055-1c762f4483e5?auto=format&fit=crop&w=600&q=80",
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
    image: "https://images.unsplash.com/photo-1599643477877-530eb83abc8e?auto=format&fit=crop&w=600&q=80",
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
    image: "https://images.unsplash.com/photo-1582142839970-2b9da1978253?auto=format&fit=crop&w=600&q=80",
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
    image: "https://images.unsplash.com/photo-1588850561407-ed78c282e89b?auto=format&fit=crop&w=600&q=80",
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
    image: "https://images.unsplash.com/photo-1630019852942-f89202989a59?auto=format&fit=crop&w=600&q=80",
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
