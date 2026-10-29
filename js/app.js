(function () {
  // CSRF: attach token to all POST fetch requests
  (function () {
    var token = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!token) return;
    var originalFetch = window.fetch;
    window.fetch = function (url, options) {
      options = options || {};
      var method = (options.method || 'GET').toUpperCase();
      if (method === 'POST') {
        options.headers = options.headers || {};
        if (!('X-CSRF-Token' in options.headers)) {
          options.headers['X-CSRF-Token'] = token;
        }
      }
      return originalFetch(url, options);
    };
  })();

  const qs = (s) => document.querySelector(s);
  const qsa = (s) => Array.from(document.querySelectorAll(s));

  const yearEl = qs('#year');
  if (yearEl) yearEl.textContent = new Date().getFullYear();

  const body = document.body;
  const hamburger = qs('.hamburger');
  function setNavOpen(open) {
    body.classList.toggle('nav-open', !!open);
    if (hamburger) hamburger.setAttribute('aria-expanded', open ? 'true' : 'false');
  }
  hamburger?.addEventListener('click', () => {
    const isOpen = body.classList.contains('nav-open');
    setNavOpen(!isOpen);
  });

  // Close sidebar on link click
  qsa('.sidebar a').forEach((a) => a.addEventListener('click', () => setNavOpen(false)));

  // Close sidebar when clicking outside the sidebar area (overlay/outside click)
  document.addEventListener('click', (e) => {
    if (!body.classList.contains('nav-open')) return;

    const sidebar = qs('#sidebar');
    const sidebarToggleBtn = qs('#sidebarToggle');
    if (!sidebar) return;

    const clickedInsideSidebar = sidebar.contains(e.target);
    const clickedToggleBtn = sidebarToggleBtn ? sidebarToggleBtn.contains(e.target) : false;
    const clickedHamburger = hamburger ? hamburger.contains(e.target) : false;

    if (!clickedInsideSidebar && !clickedToggleBtn && !clickedHamburger) {
      setNavOpen(false);
    }
  });


  // Sidebar button (desktop): left toggle below header
  const sidebarToggle = qs('#sidebarToggle');
  sidebarToggle?.addEventListener('click', () => {
    const isOpen = body.classList.contains('nav-open');
    setNavOpen(!isOpen);
  });

  // When sidebar is open we hide the desktop toggle via CSS; clicking the hamburger closes it.


  // Also support existing inlined sidebar toggle scripts by ensuring click always toggles.
  // (If other scripts stop propagation, fallback still works because we listen on button itself.)
  sidebarToggle?.addEventListener('mousedown', (e) => {
    // prevent focus flicker
    e.preventDefault();
  });



  // Ensure open/close works even when scripts load late
  // (Some pages may render sidebar/hamburger after DOMContentLoaded)
  if (!hamburger) {
    // no-op
  }


  // ------------------------------
  // Shared helpers
  // ------------------------------
  const escapeMap = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
  };
  function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, (m) => escapeMap[m] ?? m);
  }

  function normalizeQuery(s) {
    return (s || '').trim().toLowerCase();
  }

  function mapShortKeywords(q) {
    if (!q) return q;
    const x = q.trim().toLowerCase();

    const waterLike = ['wa', 'wat', 'wate', 'watr', 'water'];
    if (waterLike.includes(x) || x.startsWith('wa')) return 'water';

    const vaccineLike = ['vac', 'vacc', 'vaccin', 'vax', 'vaccine'];
    if (vaccineLike.includes(x) || x.startsWith('vac')) return 'vaccine';

    return q;
  }

  // ------------------------------
  // Inventory page: product dropdown search + DB upsert
  // ------------------------------
  const categorySelect = qs('#categorySelect');
  const productSearch = qs('#productSearch');
  const productSearchDropdown = qs('#productSearchDropdown');
  const selectedProductInfo = qs('#selectedProductInfo');
  const selectedProductIdInput = qs('#selectedProductId');
  const newProductNameInput = qs('#newProductName');
  const newProductDescriptionInput = qs('#newProductDescription');

  const qtyInput = qs('#qtyInput');
  const upsertBtn = qs('#upsertBtn');
  const upsertResult = qs('#upsertResult');
  const newProductImageInput = qs('#newProductImage');
  const newProductImagePreview = qs('#newProductImagePreview');
  const newProductImagePreviewImg = qs('#newProductImagePreviewImg');

  // Image preview on file selection
  if (newProductImageInput && newProductImagePreview && newProductImagePreviewImg) {
    newProductImageInput.addEventListener('change', function() {
      const file = this.files && this.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          newProductImagePreviewImg.src = e.target.result;
          newProductImagePreview.style.display = 'block';
        };
        reader.readAsDataURL(file);
      } else {
        newProductImagePreview.style.display = 'none';
        newProductImagePreviewImg.src = '';
      }
    });
  }

  // Create hidden input if missing
  let selectedPidInput = selectedProductIdInput;
  if (!selectedPidInput) {
    selectedPidInput = document.createElement('input');
    selectedPidInput.type = 'hidden';
    selectedPidInput.id = 'selectedProductId';
    selectedPidInput.value = '0';
    qtyInput?.parentElement?.appendChild(selectedPidInput);
  }

  const CATEGORY_VALUES = ['vaccines', 'water', 'layer_meds', 'feed_meds'];
  let lastInventoryDropdownProducts = [];

  function setSelectedProduct(pid, product) {
    if (selectedPidInput) selectedPidInput.value = String(pid ?? 0);

    if (selectedProductInfo) {
      if (!product) {
        selectedProductInfo.textContent = '';
      } else {
        selectedProductInfo.innerHTML = `Selected: <b>${escapeHtml(product.name || '')}</b> <span class="muted">(${escapeHtml(product.sku || '')})</span>`;
      }
    }

    // Clear “new product” field when a known product is chosen
    if (newProductNameInput) newProductNameInput.value = '';
  }

  function renderDropdown(items) {
    if (!productSearchDropdown) return;

    if (!items || !items.length) {
      productSearchDropdown.hidden = true;
      productSearchDropdown.innerHTML = '';
      return;
    }

    productSearchDropdown.hidden = false;
    productSearchDropdown.innerHTML = items
      .map((p) => {
        return `
          <button type="button" class="dropdown-item" data-inv-pid="${escapeHtml(p.id)}" style="width:100%;text-align:left;">
            <div class="dropdown-title">${escapeHtml(p.name || '')}</div>
            <div class="dropdown-meta">${escapeHtml(p.sku || '')} · ${escapeHtml(p.category || '')}</div>
          </button>
        `;
      })
      .join('');
  }

  async function loadProductsForCategory(category) {
    if (!productSearch || !productSearchDropdown || !categorySelect) return;

    // Reset
    lastInventoryDropdownProducts = [];
    setSelectedProduct(0, null);
    if (selectedPidInput) selectedPidInput.value = '0';
    if (selectedProductInfo) selectedProductInfo.textContent = '';
    if (productSearch) {
      productSearch.value = '';
      productSearch.disabled = true;
    }
    if (productSearchDropdown) {
      productSearchDropdown.hidden = true;
      productSearchDropdown.innerHTML = '';
    }

    if (!category || category === 'all' || !CATEGORY_VALUES.includes(category)) return;

    productSearch.disabled = false;

    try {
      const res = await fetch('api/products.php?category=' + encodeURIComponent(category));
      const data = await res.json();
      if (!data.ok) throw new Error(data.error || data.message || 'Failed to load products');

      lastInventoryDropdownProducts = data.products || [];
      renderDropdown(lastInventoryDropdownProducts.slice(0, 200));
    } catch (e) {
      console.error(e);
      lastInventoryDropdownProducts = [];
      renderDropdown([]);
    }
  }

  function filterProductsForDropdown(query) {
    const q = mapShortKeywords(normalizeQuery(query));
    if (!q) return lastInventoryDropdownProducts.slice(0, 200);

    return (lastInventoryDropdownProducts || [])
      .filter((p) => {
        const name = (p.name || '').toLowerCase();
        const desc = (p.description || '').toLowerCase();
        const cat = (p.category || '').toLowerCase();
        return name.includes(q) || desc.includes(q) || cat.includes(q);
      })
      .slice(0, 200);
  }

  function bindInventoryUI() {
    if (!categorySelect || !productSearch || !productSearchDropdown) return;

    // Category change -> load dropdown items
    categorySelect.addEventListener('change', async () => {
      const cat = categorySelect.value || '';
      if (upsertResult) upsertResult.innerHTML = '';
      await loadProductsForCategory(cat);
    });

    // Typing filters dropdown
    productSearch.addEventListener('input', () => {
      if (productSearch.disabled) return;
      const list = filterProductsForDropdown(productSearch.value);
      renderDropdown(list);
    });

    // Focus shows list again
    productSearch.addEventListener('focus', () => {
      if (productSearch.disabled) return;
      renderDropdown(filterProductsForDropdown(productSearch.value));
    });

    // Click product in dropdown: open product card like products page (via products modal)
    // Also keep inventory selection in sync.
    productSearchDropdown.addEventListener('click', (e) => {
      const btn = e.target.closest('button[data-inv-pid]');
      if (!btn) return;
      const pid = Number(btn.getAttribute('data-inv-pid'));
      if (!pid) return;

      const product = (lastInventoryDropdownProducts || []).find((x) => Number(x.id) === pid);
      if (!product) return;

      // Update input values for inventory upsert
      setSelectedProduct(pid, product);
      productSearch.value = product.name || '';

      // Hide dropdown after selection
      productSearchDropdown.hidden = true;
      productSearchDropdown.innerHTML = '';

      // Fetch and display current stock for this product
      fetchCurrentStock(pid);
    });

    // Fetch current stock quantity when product is selected
    async function fetchCurrentStock(productId) {
      const container = document.getElementById('currentStockContainer');
      const valueEl = document.getElementById('currentStockValue');
      const updatedEl = document.getElementById('currentStockUpdated');
      const priceDisplay = document.getElementById('currentPriceDisplay');
      const sizeDisplay = document.getElementById('currentSizeDisplay');
      if (!container || !valueEl) return;

      container.style.display = 'block';
      valueEl.textContent = '…';
      updatedEl.textContent = 'Loading…';

      try {
        const res = await fetch('api/inventory.php?product_id=' + encodeURIComponent(productId));
        const data = await res.json();
        if (!data.ok || !data.inventory) throw new Error(data.error || 'No inventory found');

        const inv = data.inventory;
        valueEl.textContent = inv.qty ?? '0';
        updatedEl.textContent = inv.updated_at ? 'Last updated: ' + inv.updated_at : '';
        if (priceDisplay) priceDisplay.textContent = inv.price ? '₹' + Number(inv.price).toFixed(2) : '—';
        if (sizeDisplay) sizeDisplay.textContent = inv.size || '—';

        // Update price adjuster UI with current price
        updatePriceAdjusterUI(inv.price || 0);
      } catch (e) {
        console.warn('Failed to load stock:', e);
        valueEl.textContent = '0';
        updatedEl.textContent = 'Unable to load';
      }
    }

    // ---- Price Adjuster Logic ----
    let priceAdjusterCurrentPrice = 0;

    function updatePriceAdjusterUI(currentPrice) {
      priceAdjusterCurrentPrice = Number(currentPrice) || 0;
      const container = document.getElementById('priceAdjusterContainer');
      const currentPriceEl = document.getElementById('priceAdjusterCurrentPrice');
      const newPriceEl = document.getElementById('priceAdjusterNewPrice');
      if (!container || !currentPriceEl || !newPriceEl) return;

      container.style.display = 'block';
      currentPriceEl.textContent = '₹' + priceAdjusterCurrentPrice.toFixed(2);
      recalcPricePreview();
    }

    function recalcPricePreview() {
      const newPriceEl = document.getElementById('priceAdjusterNewPrice');
      if (!newPriceEl) return;

      const priceAction = (document.getElementById('selectedPriceAction')?.value) || 'increase';
      const priceType = (document.getElementById('selectedPriceType')?.value) || 'flat';
      const rawValue = parseFloat(document.getElementById('priceValueInput')?.value) || 0;

      let newPrice = priceAdjusterCurrentPrice;
      if (rawValue > 0) {
        if (priceType === 'percent') {
          const pct = rawValue / 100;
          if (priceAction === 'increase') newPrice = priceAdjusterCurrentPrice * (1 + pct);
          else if (priceAction === 'decrease') newPrice = priceAdjusterCurrentPrice * (1 - pct);
          else newPrice = rawValue;
        } else {
          if (priceAction === 'increase') newPrice = priceAdjusterCurrentPrice + rawValue;
          else if (priceAction === 'decrease') newPrice = Math.max(0, priceAdjusterCurrentPrice - rawValue);
          else newPrice = rawValue;
        }
      }
      newPrice = Math.max(0, newPrice);
      newPriceEl.textContent = '₹' + newPrice.toFixed(2);
    }

    // Price action buttons (increase / decrease / set)
    const priceActionBtns = document.querySelectorAll('.price-action-btn');
    const selectedPriceActionInput = document.getElementById('selectedPriceAction');
    priceActionBtns.forEach((btn) => {
      btn.addEventListener('click', () => {
        const action = btn.getAttribute('data-price-action');
        if (!action) return;
        if (selectedPriceActionInput) selectedPriceActionInput.value = action;

        priceActionBtns.forEach((b) => {
          const isActive = b.getAttribute('data-price-action') === action;
          b.classList.toggle('is-active', isActive);
          b.style.borderColor = isActive ? 'rgba(212,175,55,0.6)' : 'var(--border)';
          b.style.boxShadow = isActive ? '0 0 0 3px rgba(212,175,55,0.15)' : 'none';
        });

        // Update quick action button labels based on operation
        updatePriceQuickActions();
        recalcPricePreview();
      });
    });

    // Price type buttons (flat / percent)
    const priceTypeBtns = document.querySelectorAll('.price-type-btn');
    const selectedPriceTypeInput = document.getElementById('selectedPriceType');
    priceTypeBtns.forEach((btn) => {
      btn.addEventListener('click', () => {
        const type = btn.getAttribute('data-price-type');
        if (!type) return;
        if (selectedPriceTypeInput) selectedPriceTypeInput.value = type;

        priceTypeBtns.forEach((b) => {
          const isActive = b.getAttribute('data-price-type') === type;
          b.classList.toggle('is-active', isActive);
          b.style.borderColor = isActive ? 'rgba(212,175,55,0.6)' : 'var(--border)';
          b.style.boxShadow = isActive ? '0 0 0 3px rgba(212,175,55,0.15)' : 'none';
        });

        updatePriceQuickActions();
        recalcPricePreview();
      });
    });

    function updatePriceQuickActions() {
      const container = document.getElementById('priceQuickActions');
      if (!container) return;
      const priceType = selectedPriceTypeInput?.value || 'flat';
      const priceAction = selectedPriceActionInput?.value || 'increase';

      if (priceType === 'percent') {
        container.innerHTML = `
          <button type="button" class="price-quick-btn" data-value="5" style="padding:8px 14px;border-radius:14px;border:1px solid rgba(255,255,255,0.10);background:rgba(255,255,255,0.04);color:rgba(229,231,235,0.9);font-weight:1000;cursor:pointer;">+5%</button>
          <button type="button" class="price-quick-btn" data-value="10" style="padding:8px 14px;border-radius:14px;border:1px solid rgba(255,255,255,0.10);background:rgba(255,255,255,0.04);color:rgba(229,231,235,0.9);font-weight:1000;cursor:pointer;">+10%</button>
          <button type="button" class="price-quick-btn" data-value="15" style="padding:8px 14px;border-radius:14px;border:1px solid rgba(255,255,255,0.10);background:rgba(255,255,255,0.04);color:rgba(229,231,235,0.9);font-weight:1000;cursor:pointer;">+15%</button>
          <button type="button" class="price-quick-btn" data-value="25" style="padding:8px 14px;border-radius:14px;border:1px solid rgba(255,255,255,0.10);background:rgba(255,255,255,0.04);color:rgba(229,231,235,0.9);font-weight:1000;cursor:pointer;">+25%</button>
        `;
      } else if (priceAction === 'set') {
        container.innerHTML = `
          <button type="button" class="price-quick-btn" data-value="100" style="padding:8px 14px;border-radius:14px;border:1px solid rgba(255,255,255,0.10);background:rgba(255,255,255,0.04);color:rgba(229,231,235,0.9);font-weight:1000;cursor:pointer;">₹100</button>
          <button type="button" class="price-quick-btn" data-value="500" style="padding:8px 14px;border-radius:14px;border:1px solid rgba(255,255,255,0.10);background:rgba(255,255,255,0.04);color:rgba(229,231,235,0.9);font-weight:1000;cursor:pointer;">₹500</button>
          <button type="button" class="price-quick-btn" data-value="1000" style="padding:8px 14px;border-radius:14px;border:1px solid rgba(255,255,255,0.10);background:rgba(255,255,255,0.04);color:rgba(229,231,235,0.9);font-weight:1000;cursor:pointer;">₹1000</button>
          <button type="button" class="price-quick-btn" data-value="2500" style="padding:8px 14px;border-radius:14px;border:1px solid rgba(255,255,255,0.10);background:rgba(255,255,255,0.04);color:rgba(229,231,235,0.9);font-weight:1000;cursor:pointer;">₹2500</button>
        `;
      } else {
        container.innerHTML = `
          <button type="button" class="price-quick-btn" data-value="10" style="padding:8px 14px;border-radius:14px;border:1px solid rgba(255,255,255,0.10);background:rgba(255,255,255,0.04);color:rgba(229,231,235,0.9);font-weight:1000;cursor:pointer;">+₹10</button>
          <button type="button" class="price-quick-btn" data-value="50" style="padding:8px 14px;border-radius:14px;border:1px solid rgba(255,255,255,0.10);background:rgba(255,255,255,0.04);color:rgba(229,231,235,0.9);font-weight:1000;cursor:pointer;">+₹50</button>
          <button type="button" class="price-quick-btn" data-value="100" style="padding:8px 14px;border-radius:14px;border:1px solid rgba(255,255,255,0.10);background:rgba(255,255,255,0.04);color:rgba(229,231,235,0.9);font-weight:1000;cursor:pointer;">+₹100</button>
          <button type="button" class="price-quick-btn" data-value="500" style="padding:8px 14px;border-radius:14px;border:1px solid rgba(255,255,255,0.10);background:rgba(255,255,255,0.04);color:rgba(229,231,235,0.9);font-weight:1000;cursor:pointer;">+₹500</button>
        `;
      }

      // Re-bind quick button clicks
      container.querySelectorAll('.price-quick-btn').forEach((qb) => {
        qb.addEventListener('click', () => {
          const val = qb.getAttribute('data-value');
          if (val) {
            const input = document.getElementById('priceValueInput');
            if (input) input.value = val;
            recalcPricePreview();
          }
        });
      });
    }

    // Price value input change recalculates preview
    const priceValueInput = document.getElementById('priceValueInput');
    if (priceValueInput) {
      priceValueInput.addEventListener('input', recalcPricePreview);
      priceValueInput.addEventListener('change', recalcPricePreview);
    }

    // "Update Price" button handler
    const updatePriceBtn = document.getElementById('updatePriceBtn');
    const priceUpdateResult = document.getElementById('priceUpdateResult');

    updatePriceBtn?.addEventListener('click', async () => {
      if (!priceUpdateResult) return;

      const pid = selectedPidInput ? Number(selectedPidInput.value) : 0;
      if (!pid || pid <= 0) {
        priceUpdateResult.innerHTML = '<b>Error:</b> Select a product first.';
        return;
      }

      const priceAction = selectedPriceActionInput?.value || 'increase';
      const priceType = selectedPriceTypeInput?.value || 'flat';
      const rawValue = parseFloat(priceValueInput?.value) || 0;

      if (rawValue <= 0) {
        priceUpdateResult.innerHTML = '<b>Error:</b> Enter a valid price adjustment value greater than 0.';
        return;
      }

      const form = new FormData();
      form.append('product_id', String(pid));
      form.append('qty', '0');
      form.append('inv_action', 'set');
      form.append('price_action', priceAction);
      form.append('price_type', priceType);
      form.append('price_value', String(rawValue));

      priceUpdateResult.innerHTML = 'Updating price...';

      try {
        const res = await fetch('api/inventory_upsert.php', { method: 'POST', body: form });
        const data = await res.json();
        if (!data.ok) throw new Error(data.error || data.message || 'Failed');

        const inv = data.inventory || {};
        const updatedPrice = inv.price || '—';
        priceUpdateResult.innerHTML = `
          <div style="font-weight:900; margin-bottom:8px;">✅ Price updated successfully!</div>
          <div class="muted" style="font-weight:900;">Product: <b>${escapeHtml(inv.product || '')}</b></div>
          <div class="muted" style="font-weight:900;">New Price: ₹${Number(updatedPrice).toFixed(2)}</div>
        `;

        // Update price displays
        const priceDisplay = document.getElementById('currentPriceDisplay');
        if (priceDisplay && updatedPrice) {
          priceDisplay.textContent = '₹' + Number(updatedPrice).toFixed(2);
        }

        // Update price adjuster UI
        updatePriceAdjusterUI(updatedPrice || 0);

        // Refresh stock board
        if (typeof refreshInventoryBoard === 'function') {
          refreshInventoryBoard();
        }
      } catch (e) {
        priceUpdateResult.innerHTML = '<b>Error:</b> ' + escapeHtml(e.message || 'Failed');
        console.error(e);
      }
    });

    // Initialize price quick actions on load
    setTimeout(() => { updatePriceQuickActions(); }, 100);

    // ---- End Price Adjuster Logic ----

    // Outside click closes dropdown
    document.addEventListener('click', (e) => {
      if (!productSearchDropdown || productSearchDropdown.hidden) return;
      const clickedInside = productSearchDropdown.contains(e.target) || productSearch.contains(e.target);
      if (!clickedInside) {
        productSearchDropdown.hidden = true;
        productSearchDropdown.innerHTML = '';
      }
    });

    // Action toggle buttons (Add / Deduct / Set)
    const actionBtns = document.querySelectorAll('.inv-action-btn');
    const selectedActionInput = document.getElementById('selectedAction');
    const qtyLabel = document.getElementById('qtyLabel');
    const quickActions = document.getElementById('quickActions');

    function updateActionUI(action) {
      // Update hidden input
      if (selectedActionInput) selectedActionInput.value = action;

      // Update active state on buttons
      actionBtns.forEach((btn) => {
        const isActive = btn.getAttribute('data-action') === action;
        btn.classList.toggle('is-active', isActive);
        // Adjust opacity/border for active state
        btn.style.borderColor = isActive ? 'rgba(212,175,55,0.6)' : 'var(--border)';
        btn.style.boxShadow = isActive ? '0 0 0 3px rgba(212,175,55,0.15)' : 'none';
      });

      // Update label text
      if (qtyLabel) {
        if (action === 'add') qtyLabel.textContent = 'Quantity to Add';
        else if (action === 'deduct') qtyLabel.textContent = 'Quantity to Deduct';
        else qtyLabel.textContent = 'Set Quantity To';
      }

      // Update quick action buttons
      if (quickActions) {
        if (action === 'add') {
          quickActions.innerHTML = `
            <button type="button" onclick="invSetQty(10)">+10</button>
            <button type="button" onclick="invSetQty(50)">+50</button>
            <button type="button" onclick="invSetQty(100)">+100</button>
          `;
        } else if (action === 'deduct') {
          quickActions.innerHTML = `
            <button type="button" onclick="invSetQty(10)">-10</button>
            <button type="button" onclick="invSetQty(50)">-50</button>
            <button type="button" onclick="invSetQty(100)">-100</button>
          `;
        } else {
          quickActions.innerHTML = `
            <button type="button" onclick="invSetQty(10)">Set 10</button>
            <button type="button" onclick="invSetQty(50)">Set 50</button>
            <button type="button" onclick="invSetQty(100)">Set 100</button>
          `;
        }
      }
    }

    actionBtns.forEach((btn) => {
      btn.addEventListener('click', () => {
        const action = btn.getAttribute('data-action');
        if (action) updateActionUI(action);
      });
    });

    // Initialize default action
    updateActionUI('add');

  // Override invAdjustQty for the stepper to work with our modified HTML
    window.invAdjustQtyStepper = function(delta) {
      const el = document.getElementById('qtyInput');
      if (!el) return;
      const cur = Number(el.value) || 0;
      el.value = String(Math.max(0, cur + (Number(delta) || 0)));
    };

    // ---- New Product Tab Handler ----
    const upsertBtnNew = document.getElementById('upsertBtnNew');
    const categorySelectNew = document.getElementById('categorySelectNew');
    const qtyInputNew = document.getElementById('qtyInputNew');
    const upsertResultNew = document.getElementById('upsertResultNew');

    upsertBtnNew?.addEventListener('click', async () => {
      if (!qtyInputNew || !upsertResultNew || !categorySelectNew) return;

      const qty = Number(qtyInputNew.value);
      const cat = categorySelectNew.value || '';
      const typedNewName = (newProductNameInput?.value || '').trim();
      const typedNewDesc = (newProductDescriptionInput?.value || '').trim();
      const typedNewPrice = parseFloat(document.getElementById('newProductPrice')?.value || '0') || 0;
      const typedNewSize = document.getElementById('newProductSize')?.value || '';

      if (!cat || !CATEGORY_VALUES.includes(cat)) {
        upsertResultNew.innerHTML = '<b>Error:</b> Select a category first.';
        return;
      }
      if (Number.isNaN(qty) || qty < 0) {
        upsertResultNew.innerHTML = '<b>Error:</b> Quantity must be 0 or more.';
        return;
      }
      if (!typedNewName) {
        upsertResultNew.innerHTML = '<b>Error:</b> Enter a product name.';
        return;
      }
      if (!typedNewDesc) {
        upsertResultNew.innerHTML = '<b>Error:</b> Enter a product description.';
        return;
      }
      if (!typedNewPrice || typedNewPrice <= 0) {
        upsertResultNew.innerHTML = '<b>Error:</b> Enter a valid price greater than 0.';
        return;
      }
      if (!typedNewSize) {
        upsertResultNew.innerHTML = '<b>Error:</b> Select a size.';
        return;
      }

      const form = new FormData();
      form.append('qty', String(qty));
      form.append('inv_action', 'set');
      form.append('product_id', '0');
      form.append('new_product_name', typedNewName);
      form.append('new_product_description', typedNewDesc);
      form.append('new_category', cat);
      form.append('price', String(typedNewPrice));
      form.append('size', typedNewSize);

      // Attach product image if selected
      if (newProductImageInput && newProductImageInput.files && newProductImageInput.files[0]) {
        form.append('product_image', newProductImageInput.files[0]);
      }

      upsertResultNew.innerHTML = 'Registering new product...';

      try {
        const res = await fetch('api/inventory_upsert.php', { method: 'POST', body: form });
        const data = await res.json();
        if (!data.ok) throw new Error(data.error || data.message || 'Failed');

        const inv = data.inventory || {};
        upsertResultNew.innerHTML = `
          <div style="font-weight:900; margin-bottom:8px;">✅ Product registered & inventory updated</div>
          <div class="muted" style="font-weight:900;">Product: <b>${escapeHtml(inv.product || inv.name || '')}</b></div>
          <div class="muted" style="font-weight:900;">Qty: <span class="mono">${escapeHtml(inv.qty)}</span></div>
          <div class="muted" style="font-weight:900;">Updated: ${escapeHtml(inv.updated_at || '')}</div>
        `;

        // Refresh the stock board
        if (typeof refreshInventoryBoard === 'function') {
          refreshInventoryBoard();
        }
      } catch (e) {
        upsertResultNew.innerHTML = '<b>Error:</b> ' + escapeHtml(e.message || 'Failed');
        console.error(e);
      }
    });

    if (!upsertBtn) return;
    upsertBtn.addEventListener('click', async () => {
      if (!qtyInput || !upsertResult) return;

      const qty = Number(qtyInput.value);
      const cat = categorySelect.value || '';

      if (!cat || !CATEGORY_VALUES.includes(cat)) {
        upsertResult.innerHTML = '<b>Error:</b> Select product category first.';
        return;
      }
      if (Number.isNaN(qty) || qty < 0) {
        upsertResult.innerHTML = '<b>Error:</b> Quantity must be 0 or more.';
        return;
      }

      const selectedProductId = selectedPidInput ? Number(selectedPidInput.value) : 0;
      const typedNewName = (newProductNameInput?.value || '').trim();

      const form = new FormData();
      form.append('qty', String(qty));

      // Add the selected action (add/deduct/set)
      const currentAction = selectedActionInput ? selectedActionInput.value : 'set';
      form.append('inv_action', currentAction);

      if (selectedProductId > 0) {
        form.append('product_id', String(selectedProductId));
      } else {
        if (!typedNewName) {
          upsertResult.innerHTML = '<b>Error:</b> Select a product OR type a new product name.';
          return;
        }
        form.append('product_id', '0');
        const typedNewDesc = (newProductDescriptionInput?.value || '').trim();
        if (!typedNewDesc) {
          upsertResult.innerHTML = '<b>Error:</b> Enter NEW product description.';
          return;
        }
        form.append('new_product_name', typedNewName);
        form.append('new_product_description', typedNewDesc);
        form.append('new_category', cat);

        // Attach product image if selected
        if (newProductImageInput && newProductImageInput.files && newProductImageInput.files[0]) {
          form.append('product_image', newProductImageInput.files[0]);
        }
      }


      upsertResult.innerHTML = 'Updating...';

      try {
        const res = await fetch('api/inventory_upsert.php', { method: 'POST', body: form });
        const data = await res.json();
        if (!data.ok) throw new Error(data.error || data.message || 'Failed');

        const inv = data.inventory || {};
        upsertResult.innerHTML = `
          <div style="font-weight:900; margin-bottom:8px;">✅ Inventory updated</div>
          <div class="muted" style="font-weight:900;">Product: <b>${escapeHtml(inv.product || inv.name || '')}</b></div>
          <div class="muted" style="font-weight:900;">Qty: <span class="mono">${escapeHtml(inv.qty)}</span></div>
          <div class="muted" style="font-weight:900;">Updated: ${escapeHtml(inv.updated_at || '')}</div>
        `;

        if (inv.product && inv.sku && !typedNewName) {
          // keep UI as-is; selection already exists for product_id > 0
        }

        // Update the current stock display with the new value
        const currentStockVal = document.getElementById('currentStockValue');
        const currentStockUpdated = document.getElementById('currentStockUpdated');
        if (currentStockVal && inv.qty !== undefined) {
          currentStockVal.textContent = inv.qty;
        }
        if (currentStockUpdated && inv.updated_at) {
          currentStockUpdated.textContent = 'Last updated: ' + inv.updated_at;
        }

        // Refresh the stock board
        if (typeof refreshInventoryBoard === 'function') {
          refreshInventoryBoard();
        }

      } catch (e) {
        upsertResult.innerHTML = '<b>Error:</b> ' + escapeHtml(e.message || 'Failed');
        console.error(e);
      }
    });
  }

  bindInventoryUI();

  // ------------------------------
  // Product catalog / QR page code is intentionally not touched.
  // If you want, we can integrate it back later; for now this file focuses on fixing the inventory page UI.
  // ------------------------------
})();

