/**
 * PsaOnline - Role-Based Access Control (RBAC) Engine
 * Roles:
 * - Admin (Studio Lead): Full administrative access (Reports, CSV Exports, Payment Gateways, Product Catalog, Staff & Roles Editor)
 * - Staff (Courier & Dispatch): Operations & fulfillment access (Verify Slips, Dispatch Orders, Adjust Stock) - Restricted from Financial Exports, User Roles, and Payment Settings
 * - Buyer (Customer): Storefront customer only (Cart, Wishlist, Order History) - Restricted from Admin Portal with 403 Security Screen
 */

const RBAC_ROLES = {
  ADMIN: 'Admin',
  STAFF: 'Staff',
  BUYER: 'Buyer'
};

const RBAC_PERMISSIONS = {
  Admin: {
    canAccessAdminPortal: true,
    canViewReports: true,
    canExportReports: true,
    canVerifyOrders: true,
    canCancelOrders: true,
    canManageProducts: true,
    canDeleteProducts: true,
    canManageInventory: true,
    canManageUsers: true,
    canEditRoles: true,
    canManagePaymentGateways: true
  },
  Staff: {
    canAccessAdminPortal: true,
    canViewReports: true,
    canExportReports: false,
    canVerifyOrders: true,
    canCancelOrders: false,
    canManageProducts: true,
    canDeleteProducts: false,
    canManageInventory: true,
    canManageUsers: false,
    canEditRoles: false,
    canManagePaymentGateways: false
  },
  Buyer: {
    canAccessAdminPortal: false,
    canViewReports: false,
    canExportReports: false,
    canVerifyOrders: false,
    canCancelOrders: false,
    canManageProducts: false,
    canDeleteProducts: false,
    canManageInventory: false,
    canManageUsers: false,
    canEditRoles: false,
    canManagePaymentGateways: false
  }
};

const DEFAULT_USERS = [
  {
    id: 'usr-01',
    name: 'Sokha Ouk',
    email: 'admin@psaonline.kh',
    passwordHint: 'admin1234',
    role: 'Admin',
    avatar: 'SO',
    status: 'Active',
    created: '15 Jan 2026',
    department: 'Studio Management & Finance',
    description: 'Executive Lead with full financial exports and system configuration authority.'
  },
  {
    id: 'usr-02',
    name: 'Kosal Meng',
    email: 'kosal.dispatch@psaonline.kh',
    passwordHint: 'staff1234',
    role: 'Staff',
    avatar: 'KM',
    status: 'Active',
    created: '03 Feb 2026',
    department: 'Fulfillment & Courier Dispatch',
    description: 'Verifies Bakong KHQR transfer slips and dispatches city couriers.'
  },
  {
    id: 'usr-03',
    name: 'Rathana Keo',
    email: 'rathana.catalog@psaonline.kh',
    passwordHint: 'staff1234',
    role: 'Staff',
    avatar: 'RK',
    status: 'Active',
    created: '18 Feb 2026',
    department: 'Catalog & Inventory Operations',
    description: 'Monitors Y2K drop stock levels and coordinates restocks.'
  },
  {
    id: 'usr-04',
    name: 'Chhum Sophea',
    email: 'sophea.accessories@gmail.com',
    passwordHint: 'demo1234',
    role: 'Buyer',
    avatar: 'CS',
    status: 'Active',
    created: '14 Mar 2026',
    department: 'VIP Gen-Z Customer',
    description: 'Verified buyer shopping aesthetic streetwear jewelry and cloud bags.'
  },
  {
    id: 'usr-05',
    name: 'Bona Seng',
    email: 'chhum.sophea@gmail.com',
    passwordHint: 'demo1234',
    role: 'Buyer',
    avatar: 'BS',
    status: 'Active',
    created: '20 May 2026',
    department: 'Customer',
    description: 'Verified buyer with active order tracking.'
  }
];

const RBAC_GUEST = { id: null, name: 'Guest', email: '', role: 'Buyer', avatar: 'G', status: 'Active', guest: true };

function getRBACUsers() {
  // Live mode: the server only sends the user list to admins.
  if (PSA.online) return PSA.users;
  try {
    const saved = localStorage.getItem('psa_rbac_users');
    if (saved) {
      const parsed = JSON.parse(saved);
      if (Array.isArray(parsed) && parsed.length > 0) return parsed;
    }
  } catch (e) {
    // fallback
  }
  localStorage.setItem('psa_rbac_users', JSON.stringify(DEFAULT_USERS));
  return DEFAULT_USERS;
}

function saveRBACUsers(users) {
  localStorage.setItem('psa_rbac_users', JSON.stringify(users));
}

function getCurrentUser() {
  // Live mode: who you are comes from your login session, not from the browser.
  if (PSA.online) return PSA.user || RBAC_GUEST;
  try {
    const saved = localStorage.getItem('psa_current_user');
    if (saved) {
      const user = JSON.parse(saved);
      if (user && user.role) return user;
    }
  } catch (e) {
    // fallback
  }
  // Demo mode (no server): default to Admin so the admin portal can be previewed
  const defaultAdmin = DEFAULT_USERS[0];
  localStorage.setItem('psa_current_user', JSON.stringify(defaultAdmin));
  return defaultAdmin;
}

function setCurrentUser(user) {
  localStorage.setItem('psa_current_user', JSON.stringify(user));
  updateRBACUI();
}

function switchUserRole(newRole) {
  // Live mode: roles are real, so "switching" means signing in with another account.
  if (PSA.online) {
    showRBACToast(`Sign in with a ${newRole} account to continue.`, 'info');
    setTimeout(() => psaLogout(`login.html?next=${encodeURIComponent(psaCurrentPage())}`), 600);
    return;
  }
  const users = getRBACUsers();
  let targetUser = users.find(u => u.role === newRole);
  if (!targetUser) {
    targetUser = {
      id: `usr-${Date.now()}`,
      name: newRole === 'Admin' ? 'Sokha Ouk' : (newRole === 'Staff' ? 'Kosal Meng' : 'Chhum Sophea'),
      email: newRole === 'Admin' ? 'admin@psaonline.kh' : (newRole === 'Staff' ? 'kosal.dispatch@psaonline.kh' : 'sophea.accessories@gmail.com'),
      role: newRole,
      avatar: newRole === 'Admin' ? 'SO' : (newRole === 'Staff' ? 'KM' : 'CS'),
      status: 'Active',
      department: newRole === 'Admin' ? 'Studio Management & Finance' : (newRole === 'Staff' ? 'Fulfillment Team' : 'VIP Customer')
    };
  }
  setCurrentUser(targetUser);
  showRBACToast(`Role switched to ${newRole.toUpperCase()}! UI & permissions updated.`, 'success');
  setTimeout(() => {
    window.location.reload();
  }, 350);
}

function hasPermission(permissionName) {
  const user = getCurrentUser();
  const perms = RBAC_PERMISSIONS[user.role] || RBAC_PERMISSIONS.Buyer;
  return !!perms[permissionName];
}

function showRBACToast(msg, type = 'info') {
  let toast = document.getElementById('psaRBACToast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'psaRBACToast';
    toast.className = 'fixed bottom-6 right-6 z-50 transition-all duration-300 transform translate-y-10 opacity-0 pointer-events-none max-w-sm';
    document.body.appendChild(toast);
  }

  const isSuccess = type === 'success';
  const isDanger = type === 'danger';

  toast.innerHTML = `
    <div class="bg-white dark:bg-[#1A1A22] border-2 ${isDanger ? 'border-red-500 shadow-[0_10px_25px_-5px_rgba(239,68,68,0.3)]' : (isSuccess ? 'border-emerald-500 shadow-[0_10px_25px_-5px_rgba(16,185,129,0.3)]' : 'border-[#FFA552] shadow-[0_10px_25px_-5px_rgba(255,165,82,0.3)]')} rounded-2xl p-4 flex items-center gap-3 text-xs pointer-events-auto">
      <div class="w-9 h-9 rounded-xl ${isDanger ? 'bg-red-50 text-red-600 dark:bg-red-950/60' : (isSuccess ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60' : 'bg-orange-50 text-[#FFA552] dark:bg-orange-950/60')} flex items-center justify-center font-black text-sm shrink-0">
        ${isDanger ? '⚠️' : (isSuccess ? '✓' : '🛡️')}
      </div>
      <div class="min-w-0">
        <div class="font-black text-[#2B1D1D] dark:text-white flex items-center gap-1.5">
          <span>RBAC Guard</span>
          <span class="text-[9px] px-1.5 py-0.2 rounded font-mono font-bold uppercase ${isDanger ? 'bg-red-100 text-red-700' : 'bg-stone-100 text-stone-600'}">Role: ${getCurrentUser().role}</span>
        </div>
        <div class="text-[11px] text-stone-500 dark:text-stone-400 font-medium leading-tight mt-0.5">${msg}</div>
      </div>
    </div>
  `;

  toast.classList.remove('translate-y-10', 'opacity-0', 'pointer-events-none');
  toast.classList.add('translate-y-0', 'opacity-100');

  setTimeout(() => {
    toast.classList.add('translate-y-10', 'opacity-0', 'pointer-events-none');
    toast.classList.remove('translate-y-0', 'opacity-100');
  }, 3400);
}

function enforcePageRBAC(requiredPermission = 'canAccessAdminPortal') {
  const user = getCurrentUser();
  if (PSA.online && user.guest) {
    return psaRequireLogin();
  }
  const allowed = hasPermission(requiredPermission);

  if (!allowed) {
    let blocker = document.getElementById('rbacAccessDeniedModal');
    if (!blocker) {
      blocker = document.createElement('div');
      blocker.id = 'rbacAccessDeniedModal';
      blocker.className = 'fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md transition-opacity duration-300';
      blocker.innerHTML = `
        <div class="w-full max-w-md bg-white dark:bg-[#1A1A22] rounded-3xl border-2 border-red-500 p-6 sm:p-8 text-center space-y-5 shadow-2xl animate-fade-in-up">
          <div class="w-16 h-16 rounded-3xl bg-red-100 dark:bg-red-950/60 text-red-600 dark:text-red-400 flex items-center justify-center text-3xl mx-auto shadow-inner">
            🛡️
          </div>
          <div class="space-y-1.5">
            <span class="px-2.5 py-0.5 rounded-full bg-red-100 text-red-700 text-[10px] font-black uppercase tracking-wider">
              Access Restricted (RBAC 403)
            </span>
            <h2 class="text-xl font-black text-[#2B1D1D] dark:text-white tracking-tight">Insufficient Permissions</h2>
            <p class="text-xs text-stone-500 dark:text-stone-400 leading-relaxed">
              Your logged in role <strong class="text-red-600 uppercase font-black px-1.5 py-0.5 rounded bg-red-50 border border-red-200">${user.role}</strong> does not have permission for <code>${requiredPermission}</code>.
            </p>
          </div>

          <!-- Quick Role Switcher for Demo & Testing -->
          <div class="p-3.5 bg-stone-50 dark:bg-[#15151B] rounded-2xl border border-stone-200 dark:border-stone-800 text-left space-y-2">
            <div class="text-[10px] uppercase font-black text-stone-400 tracking-wider">Switch Role to Continue (RBAC Demo):</div>
            <div class="grid grid-cols-2 gap-2 text-xs">
              <button 
                type="button" 
                onclick="switchUserRole('Admin')" 
                class="btn-press p-2 rounded-xl bg-purple-50 dark:bg-purple-950/50 border border-purple-200 dark:border-purple-800 text-purple-700 dark:text-purple-300 font-bold flex items-center justify-center gap-1.5 hover:bg-purple-100 cursor-pointer"
              >
                <span>👑</span> Switch to Admin
              </button>
              <button 
                type="button" 
                onclick="switchUserRole('Staff')" 
                class="btn-press p-2 rounded-xl bg-blue-50 dark:bg-blue-950/50 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300 font-bold flex items-center justify-center gap-1.5 hover:bg-blue-100 cursor-pointer"
              >
                <span>⚡</span> Switch to Staff
              </button>
            </div>
          </div>

          <div class="pt-2 flex items-center justify-center gap-3">
            <a href="home.html" class="px-5 py-2.5 rounded-xl bg-stone-200 dark:bg-stone-800 hover:bg-stone-300 text-stone-700 dark:text-stone-300 text-xs font-bold transition">
              &larr; Return to Storefront
            </a>
          </div>
        </div>
      `;
      document.body.appendChild(blocker);
      document.body.style.overflow = 'hidden';
    }
    return false;
  }
  return true;
}

function updateRBACUI() {
  const user = getCurrentUser();

  // Update current user name / role across DOM
  document.querySelectorAll('.rbac-current-name').forEach(el => el.textContent = user.name);
  document.querySelectorAll('.rbac-current-role').forEach(el => el.textContent = user.role);
  document.querySelectorAll('.rbac-current-email').forEach(el => el.textContent = user.email);
  document.querySelectorAll('.rbac-current-avatar').forEach(el => psaFillAvatar(el, user));

  // Badge styling
  document.querySelectorAll('.rbac-role-badge').forEach(badge => {
    badge.textContent = user.role;
    badge.className = 'rbac-role-badge px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider inline-flex items-center gap-1 ' +
      (user.role === 'Admin' 
        ? 'bg-purple-100 text-purple-700 border border-purple-300 dark:bg-purple-950/60 dark:text-purple-300 dark:border-purple-800' 
        : (user.role === 'Staff' 
          ? 'bg-blue-100 text-blue-700 border border-blue-300 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800' 
          : 'bg-emerald-100 text-emerald-700 border border-emerald-300 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800'));
  });

  // Admin-only elements handling
  if (user.role === 'Staff') {
    document.querySelectorAll('.rbac-admin-only').forEach(el => {
      el.classList.add('opacity-50', 'pointer-events-none');
      el.setAttribute('title', 'Admin Role Required (Restricted for Staff)');
    });
    document.querySelectorAll('.rbac-admin-only-hide').forEach(el => {
      el.classList.add('hidden');
    });
    document.querySelectorAll('.rbac-staff-banner').forEach(el => {
      el.classList.remove('hidden');
    });
  } else if (user.role === 'Admin') {
    document.querySelectorAll('.rbac-admin-only').forEach(el => {
      el.classList.remove('opacity-50', 'pointer-events-none');
      el.removeAttribute('title');
    });
    document.querySelectorAll('.rbac-admin-only-hide').forEach(el => {
      el.classList.remove('hidden');
    });
    document.querySelectorAll('.rbac-staff-banner').forEach(el => {
      el.classList.add('hidden');
    });
  }
}

// Global RBAC Matrix Modal injection
function openRBACMatrixModal() {
  let modal = document.getElementById('rbacMatrixModal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'rbacMatrixModal';
    modal.className = 'fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-3 sm:p-6 bg-black/75 backdrop-blur-md transition-opacity duration-300';
    modal.innerHTML = `
      <div class="relative w-full max-w-2xl bg-white dark:bg-[#1A1A22] rounded-3xl border-2 border-[#FFA552]/40 shadow-2xl p-6 sm:p-8 space-y-6 text-[#2B1D1D] dark:text-white animate-fade-in-up">
        
        <div class="flex items-center justify-between border-b border-[#EFE4D6] dark:border-[#2D2D38] pb-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-[#FFA552]/10 text-[#FFA552] flex items-center justify-center text-xl font-bold">
              🛡️
            </div>
            <div>
              <h2 class="text-lg font-black text-[#2B1D1D] dark:text-white">Role-Based Access Control (RBAC) Matrix</h2>
              <p class="text-xs text-stone-500 dark:text-stone-400">Security permissions mapped across PsaOnline operations</p>
            </div>
          </div>
          <button type="button" onclick="closeRBACMatrixModal()" class="w-8 h-8 rounded-full bg-stone-100 dark:bg-stone-800 hover:bg-stone-200 text-stone-600 dark:text-stone-300 flex items-center justify-center cursor-pointer">
            &times;
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead>
              <tr class="border-b border-[#EFE4D6] dark:border-[#2D2D38] text-stone-400">
                <th class="py-2.5 px-3">Capability / Permission</th>
                <th class="py-2.5 px-3 text-center">👑 Admin</th>
                <th class="py-2.5 px-3 text-center">⚡ Staff</th>
                <th class="py-2.5 px-3 text-center">🛍️ Buyer</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#F9F3EA] dark:divide-[#252530]">
              <tr>
                <td class="py-2.5 px-3 font-bold">Access Admin Operations Hub</td>
                <td class="py-2.5 px-3 text-center text-emerald-600 font-bold">✓ Granted</td>
                <td class="py-2.5 px-3 text-center text-emerald-600 font-bold">✓ Granted</td>
                <td class="py-2.5 px-3 text-center text-red-500 font-bold">✕ 403 Block</td>
              </tr>
              <tr>
                <td class="py-2.5 px-3 font-bold">View Financial Reports & KPIs</td>
                <td class="py-2.5 px-3 text-center text-emerald-600 font-bold">✓ Granted</td>
                <td class="py-2.5 px-3 text-center text-emerald-600 font-bold">✓ Granted</td>
                <td class="py-2.5 px-3 text-center text-red-500 font-bold">✕ Restricted</td>
              </tr>
              <tr>
                <td class="py-2.5 px-3 font-bold">Export Financial Records (CSV / Ledger)</td>
                <td class="py-2.5 px-3 text-center text-emerald-600 font-bold">✓ Granted</td>
                <td class="py-2.5 px-3 text-center text-red-500 font-bold">✕ Restricted</td>
                <td class="py-2.5 px-3 text-center text-red-500 font-bold">✕ Restricted</td>
              </tr>
              <tr>
                <td class="py-2.5 px-3 font-bold">Verify Bakong KHQR Slips & Dispatch</td>
                <td class="py-2.5 px-3 text-center text-emerald-600 font-bold">✓ Granted</td>
                <td class="py-2.5 px-3 text-center text-emerald-600 font-bold">✓ Granted</td>
                <td class="py-2.5 px-3 text-center text-red-500 font-bold">✕ Restricted</td>
              </tr>
              <tr>
                <td class="py-2.5 px-3 font-bold">Cancel Orders & Void Slips</td>
                <td class="py-2.5 px-3 text-center text-emerald-600 font-bold">✓ Granted</td>
                <td class="py-2.5 px-3 text-center text-red-500 font-bold">✕ Restricted</td>
                <td class="py-2.5 px-3 text-center text-red-500 font-bold">✕ Restricted</td>
              </tr>
              <tr>
                <td class="py-2.5 px-3 font-bold">Product Stock & Inventory Adjustment</td>
                <td class="py-2.5 px-3 text-center text-emerald-600 font-bold">✓ Granted</td>
                <td class="py-2.5 px-3 text-center text-emerald-600 font-bold">✓ Granted</td>
                <td class="py-2.5 px-3 text-center text-red-500 font-bold">✕ Restricted</td>
              </tr>
              <tr>
                <td class="py-2.5 px-3 font-bold">Manage Users & Edit Roles</td>
                <td class="py-2.5 px-3 text-center text-emerald-600 font-bold">✓ Granted</td>
                <td class="py-2.5 px-3 text-center text-red-500 font-bold">✕ Restricted</td>
                <td class="py-2.5 px-3 text-center text-red-500 font-bold">✕ Restricted</td>
              </tr>
              <tr>
                <td class="py-2.5 px-3 font-bold">Manage KHQR Gateways & Bank Merchant</td>
                <td class="py-2.5 px-3 text-center text-emerald-600 font-bold">✓ Granted</td>
                <td class="py-2.5 px-3 text-center text-red-500 font-bold">✕ Restricted</td>
                <td class="py-2.5 px-3 text-center text-red-500 font-bold">✕ Restricted</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex items-center justify-between pt-2 border-t border-[#EFE4D6] dark:border-[#2D2D38] text-xs">
          <div class="text-stone-500">Active Session: <strong class="text-[#FFA552] rbac-current-name">User</strong> (<span class="rbac-role-badge">Role</span>)</div>
          <button type="button" onclick="closeRBACMatrixModal()" class="btn-press px-4 py-2 rounded-xl bg-[#FFA552] text-white font-bold cursor-pointer">
            Done
          </button>
        </div>

      </div>
    `;
    document.body.appendChild(modal);
    modal.addEventListener('click', (e) => {
      if (e.target === modal) closeRBACMatrixModal();
    });
  }
  modal.classList.remove('hidden');
}

function closeRBACMatrixModal() {
  const modal = document.getElementById('rbacMatrixModal');
  if (modal) modal.classList.add('hidden');
}

function toggleRbacDropdown(event) {
  if (event) {
    event.stopPropagation();
  }
  const wrapper = event && event.currentTarget
    ? event.currentTarget.closest('.rbac-dropdown-container, .relative.group')
    : document.querySelector('.rbac-dropdown-container, .relative.group');
  if (!wrapper) return;

  const menu = wrapper.querySelector('.rbac-dropdown-menu') || wrapper.querySelector('div.absolute');
  if (!menu) return;

  const isOpen = wrapper.classList.toggle('rbac-dropdown-open');
  if (isOpen) {
    menu.classList.remove('hidden');
    menu.classList.add('block');
  } else {
    menu.classList.remove('block');
    menu.classList.add('hidden');
  }
}

function closeAllRbacDropdowns() {
  document.querySelectorAll('.rbac-dropdown-open').forEach(wrapper => {
    wrapper.classList.remove('rbac-dropdown-open');
    const menu = wrapper.querySelector('.rbac-dropdown-menu') || wrapper.querySelector('div.absolute');
    if (menu) {
      menu.classList.remove('block');
      menu.classList.add('hidden');
    }
  });
}

function logoutSession() {
  // Live mode: end the server session too, not just the browser copy.
  if (PSA.online) {
    psaLogout('login.html');
    return;
  }
  localStorage.removeItem('psa_current_user');
  showRBACToast('Signed out of session. Redirecting to login...', 'info');
  setTimeout(() => {
    window.location.href = 'login.html';
  }, 300);
}

function setupRbacDropdownInteractions() {
  if (!document.getElementById('rbac-dropdown-bridge-style')) {
    const styleEl = document.createElement('style');
    styleEl.id = 'rbac-dropdown-bridge-style';
    styleEl.textContent = `
      .rbac-dropdown-container > .rbac-dropdown-menu {
        top: calc(100% + 6px) !important;
        margin-top: 0 !important;
      }
      .rbac-dropdown-container > .rbac-dropdown-menu::before {
        content: '';
        position: absolute;
        top: -14px;
        left: 0;
        right: 0;
        height: 14px;
        background: transparent;
      }
      .rbac-dropdown-container:hover > .rbac-dropdown-menu,
      .rbac-dropdown-container.rbac-dropdown-open > .rbac-dropdown-menu {
        display: block !important;
      }
    `;
    document.head.appendChild(styleEl);
  }

  document.querySelectorAll('#rbacRoleSelectorBtn').forEach(btn => {
    const wrapper = btn.closest('.relative');
    if (!wrapper) return;
    wrapper.classList.add('rbac-dropdown-container');

    const menu = wrapper.querySelector('div.absolute');
    if (menu) {
      menu.classList.add('rbac-dropdown-menu');
      menu.classList.remove('mt-2');

      if (!menu.querySelector('[data-rbac-logout]')) {
        const footerDiv = document.createElement('div');
        footerDiv.className = 'pt-2 mt-1 border-t border-[#EFE4D6] dark:border-[#2D2D38] flex items-center justify-between gap-2 px-2 pb-1';
        footerDiv.innerHTML = `
          <a href="home.html" class="flex-1 px-2.5 py-2 rounded-xl text-[11px] font-bold text-stone-700 dark:text-stone-200 bg-stone-100/80 dark:bg-[#252530] hover:bg-stone-200/80 dark:hover:bg-[#30303E] flex items-center justify-center gap-1.5 transition">
            <svg class="w-3.5 h-3.5 text-stone-500 dark:text-stone-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            <span>Exit to Store</span>
          </a>
          <button type="button" data-rbac-logout onclick="logoutSession()" class="flex-1 px-2.5 py-2 rounded-xl text-[11px] font-bold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950/40 hover:bg-red-100 dark:hover:bg-red-950/70 border border-red-200/70 dark:border-red-900/50 flex items-center justify-center gap-1.5 transition cursor-pointer">
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            <span>Log Out</span>
          </button>
        `;
        menu.appendChild(footerDiv);
      }
    }

    if (!btn.dataset.dropdownBound) {
      btn.dataset.dropdownBound = 'true';
      btn.addEventListener('click', toggleRbacDropdown);
    }
  });
}

document.addEventListener('click', (e) => {
  if (!e.target.closest('.rbac-dropdown-container')) {
    closeAllRbacDropdowns();
  }
});

document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    closeAllRbacDropdowns();
  }
});

document.addEventListener('DOMContentLoaded', () => {
  updateRBACUI();
  setupRbacDropdownInteractions();
});

