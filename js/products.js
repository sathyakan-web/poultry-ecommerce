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

  const grid = qs('#productGrid');
  const mobileGrid = qs('#productGridMobile');
  const searchInput = qs('#search');
  const searchDropdown = qs('#searchDropdown');
  const clearBtn = qs('#clearBtn');

  function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, (m) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m]));
  }

  function mulberry32(a) {
    return function () {
      let t = (a += 0x6D2B79F5);
      t = Math.imul(t ^ (t >>> 15), t | 1);
      t ^= t + Math.imul(t ^ (t >>> 7), t | 61);
      return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };
  }

  function formatPrice(value) {
    return `₹${Number(value || 0).toFixed(2)}`;
  }

  const RUPEE_SYMBOL = '₹';



  function params() {
    const u = new URL(location.href);
    return {
      search: (u.searchParams.get('search') || '').trim().toLowerCase(),
      category: (u.searchParams.get('category') || 'all').trim(),
    };
  }

  async function fetchProducts(category) {
    const url = category && category !== 'all'
      ? `api/products.php?category=${encodeURIComponent(category)}`
      : 'api/products.php?category=all';

    const res = await fetch(url, { method: 'GET', credentials: 'same-origin' });
    const text = await res.text();

    let data;
    try {
      data = JSON.parse(text);
    } catch {
      throw new Error('Products API returned non-JSON. Response: ' + text.slice(0, 300));
    }

    if (!data.ok) throw new Error(data.error || data.message || 'Failed to load products');
    return data.products || [];
  }

  function matchesSearch(p, q) {
    if (!q) return true;
    const name = (p.name || '').toLowerCase();
    const desc = (p.description || '').toLowerCase();
    const cat = (p.category || '').toLowerCase();
    return name.includes(q) || desc.includes(q) || cat.includes(q);
  }

  function render(products) {
    if (!grid && !mobileGrid) return;
    const { search } = params();

    const filtered = search ? products.filter((p) => matchesSearch(p, search)) : products;

    const html = filtered
      .slice(0, 60)
      .map((p) => {
        const price = Number(p.price || 0);
        const size = p.size || '—';

        return `
          <button type="button" class="doodle-card product-open" data-product-id="${escapeHtml(p.id)}">
            <div class="product-image-container">
              <img class="product-image" alt="" src="${escapeHtml(p.logo_path ? p.logo_path : '')}" onerror="this.style.display='none'" />
            </div>
            <h3 class="product-title">${escapeHtml(p.name)}</h3>
            <div class="info-row">
              <span class="info-value category">${escapeHtml(p.category || '')}</span>
              <span class="info-value price">₹${price.toFixed(2)}</span>
            </div>
            <div class="info-row">
              <span class="info-value">Size:</span>
              <span class="info-value">${escapeHtml(size)}</span>
            </div>
            <p class="desc-text">${escapeHtml((p.description || '').slice(0, 90))}${(p.description || '').length > 90 ? '…' : ''}</p>
            <span class="qty-action">Tap to view qty left</span>
          </button>
        `;
      })
      .join('');

    if (filtered.length === 0) {
      const emptyMsg = '<div class="doodle-card">No products found.</div>';
      if (grid) grid.innerHTML = emptyMsg;
      if (mobileGrid) mobileGrid.innerHTML = emptyMsg;
      return;
    }

    if (grid) grid.innerHTML = html;
    if (mobileGrid) mobileGrid.innerHTML = html;

    const allButtons = document.querySelectorAll('.product-open');
    allButtons.forEach((btn) => {
      btn.addEventListener('click', async () => {
        const productId = Number(btn.getAttribute('data-product-id'));
        const product = filtered.find((x) => Number(x.id) === productId);
        if (!productId || !product) return;
        await openProductDetails(product);
      });
    });
  }


  // Product details modal
  const modal = qs('#productDetailsModal');
  const modalQty = qs('#productDetailsQty');
  const modalUpdated = qs('#productDetailsUpdated');
  const modalCost = qs('#productDetailsCost');
  const modalSku = qs('#productDetailsSku');
  const modalImage = qs('#productDetailsImage');

  // Cooling / temperature fields (match IoT "latest" values)
  const modalColdRoomValue = qs('#productColdRoomValue');
  const modalShelfTempValue = qs('#productShelfTempValue');
  const modalCoolingUpdated = qs('#productCoolingUpdated');


  const modalTitle = qs('#productDetailsTitle');
  const modalCategory = qs('#productDetailsCategory');
  const modalInventoryLink = qs('#productDetailsInventoryLink');
  const addQtyInput = qs('#productAddQty');
  const addToCartBtn = qs('#productAddToCart');
  const addToCartStatus = qs('#productAddToCartStatus');

  // Mobile modal elements
  const mobileModal = qs('#productDetailsModalMobile');
  const mobileModalQty = qs('#productDetailsQtyMobile');
  const mobileModalUpdated = qs('#productDetailsUpdatedMobile');
  const mobileModalCost = qs('#productDetailsCostMobile');
  const mobileModalSku = qs('#productDetailsSkuMobile');
  const mobileModalImage = qs('#productDetailsImageMobile');
  const mobileModalColdRoomValue = qs('#productColdRoomValueMobile');
  const mobileModalShelfTempValue = qs('#productShelfTempValueMobile');
  const mobileModalCoolingUpdated = qs('#productCoolingUpdatedMobile');
  const mobileModalTitle = qs('#productDetailsTitleMobile');
  const mobileModalCategory = qs('#productDetailsCategoryMobile');
  const mobileAddQtyInput = qs('#productAddQtyMobile');
  const mobileAddToCartBtn = qs('#productAddToCartMobile');
  const mobileAddToCartStatus = qs('#productAddToCartStatusMobile');

  function setModalOpen(open) {
    const target = modal;
    if (!target) return;
    if (open) {
      target.hidden = false;
      target.classList.add('active');
    } else {
      target.hidden = true;
      target.classList.remove('active');
    }
    document.body.style.overflow = open ? 'hidden' : '';
  }

  async function fetchInventoryQty(productId) {
    const res = await fetch(`api/inventory.php?product_id=${encodeURIComponent(productId)}`, { credentials: 'same-origin' });
    const data = await res.json();
    if (!data.ok) throw new Error(data.error || 'Failed to load inventory');
    return data.inventory || null;
  }

  async function openProductDetails(product) {
    try {
      setModalOpen(true);

      const price = Number(product.price || 0);
      const size = product.size || '—';
      const pid = Number(product.id);

      modalTitle && (modalTitle.textContent = product.name || 'Product');
      modalCategory && (modalCategory.textContent = product.category || '');
      modalSku && (modalSku.textContent = `SKU: ${product.sku || product.category || ''}`);
      modalCost && (modalCost.textContent = `₹${price.toFixed(2)}`);

      if (modalImage) {
        const img = product.logo_path || product.image_path || product.image || '';
        if (img) {
          modalImage.src = img;
          modalImage.alt = product.name ? String(product.name) : 'Product image';
          modalImage.style.display = '';
        } else {
          modalImage.src = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 120 120"><rect width="120" height="120" fill="rgba(0,0,0,0.18)" rx="14"/><text x="60" y="68" text-anchor="middle" fill="rgba(255,255,255,0.85)" font-size="14" font-family="sans-serif">No image</text></svg>';
          modalImage.alt = 'No product image';
          modalImage.style.display = '';
        }
      }

      modalQty && (modalQty.textContent = '—');
      modalUpdated && (modalUpdated.textContent = 'Updated: —');

      if (addQtyInput) {
        addQtyInput.value = 1;
        addQtyInput.min = 1;
      }
      if (addToCartBtn) addToCartBtn.setAttribute('data-product-id', String(pid));
      if (addToCartStatus) addToCartStatus.textContent = '—';

      // Populate mobile modal if present
      if (mobileModalTitle) mobileModalTitle.textContent = product.name || 'Product';
      if (mobileModalCategory) mobileModalCategory.textContent = product.category || '';
      if (mobileModalSku) mobileModalSku.textContent = `SKU: ${product.sku || product.category || ''}`;
      if (mobileModalCost) mobileModalCost.textContent = `₹${price.toFixed(2)}`;

      if (mobileModalImage) {
        const img = product.logo_path || product.image_path || product.image || '';
        if (img) {
          mobileModalImage.src = img;
          mobileModalImage.alt = product.name ? String(product.name) : 'Product image';
          mobileModalImage.style.display = '';
        } else {
          mobileModalImage.src = '';
          mobileModalImage.alt = '';
          mobileModalImage.style.display = 'none';
        }
      }

      mobileModalQty && (mobileModalQty.textContent = '—');
      mobileModalUpdated && (mobileModalUpdated.textContent = 'Updated: —');

      if (mobileAddQtyInput) {
        mobileAddQtyInput.value = 1;
        mobileAddQtyInput.min = 1;
      }
      if (mobileAddToCartBtn) mobileAddToCartBtn.setAttribute('data-product-id', String(pid));
      if (mobileAddToCartStatus) mobileAddToCartStatus.textContent = '—';
      addToCartBtn && addToCartBtn.setAttribute('data-product-id', String(pid));

      const inv = await fetchInventoryQty(pid);
      const qty = inv && typeof inv.qty !== 'undefined' ? inv.qty : 0;
      const updatedAt = inv && inv.updated_at ? inv.updated_at : '';

      modalQty && (modalQty.textContent = qty);
      modalUpdated && (modalUpdated.textContent = `Updated: ${updatedAt || '—'}`);

      // Cooling temps (cold_room + shelf_temp) - latest per reading_type for this product
      if (modalColdRoomValue || modalShelfTempValue) {
        if (modalColdRoomValue) modalColdRoomValue.textContent = '—';
        if (modalShelfTempValue) modalShelfTempValue.textContent = '—';
        if (modalCoolingUpdated) modalCoolingUpdated.textContent = 'Updated: —';

        try {
          const iotRes = await fetch(`api/iot_readings.php?product_id=${encodeURIComponent(pid)}`, { credentials: 'same-origin' });
          const iotData = await iotRes.json();
          if (iotData.ok) {
            const rows = iotData.readings || [];
            // latest per type based on recorded_at
            let coldLatest = null;
            let shelfLatest = null;
            for (const r of rows) {
              const type = r.reading_type;
              const ts = r.recorded_at ? new Date(r.recorded_at).getTime() : 0;
              if (type === 'cold_room') {
                if (!coldLatest || ts >= coldLatest._ts) coldLatest = { ...r, _ts: ts };
              }
              if (type === 'shelf_temp') {
                if (!shelfLatest || ts >= shelfLatest._ts) shelfLatest = { ...r, _ts: ts };
              }
            }

            if (modalColdRoomValue) modalColdRoomValue.textContent = (coldLatest && coldLatest.value !== undefined) ? String(coldLatest.value) : '—';
            if (modalShelfTempValue) modalShelfTempValue.textContent = (shelfLatest && shelfLatest.value !== undefined) ? String(shelfLatest.value) : '—';

            const coldTs = coldLatest && coldLatest.recorded_at ? new Date(coldLatest.recorded_at).toLocaleString() : '';
            const shelfTs = shelfLatest && shelfLatest.recorded_at ? new Date(shelfLatest.recorded_at).toLocaleString() : '';
            const latestTs = coldTs && shelfTs ? (new Date(coldLatest.recorded_at).getTime() >= new Date(shelfLatest.recorded_at).getTime() ? coldTs : shelfTs) : (coldTs || shelfTs || '—');
            if (modalCoolingUpdated) modalCoolingUpdated.textContent = `Updated: ${latestTs || '—'}`;
          }
        } catch (e) {
          console.warn('Failed loading IoT cooling for modal', e);
        }
      }


      if (addQtyInput && Number(qty) > 0) {
        addQtyInput.max = String(qty);
      }

      // Replace modal button text with cart symbol (keep qty badge intact)
      if (addToCartBtn) {
        addToCartBtn.textContent = '🛒 Add';
      }

      if (modalInventoryLink) {
        modalInventoryLink.setAttribute('href', `inventory.php?product_id=${encodeURIComponent(pid)}`);
      }
    } catch (e) {
      console.error(e);
      modalQty && (modalQty.textContent = '0');
      modalUpdated && (modalUpdated.textContent = 'Updated: —');
      if (addToCartStatus) addToCartStatus.textContent = '—';
      setModalOpen(true);
    }
  }

  modal?.addEventListener('click', (e) => {
    const target = e.target;
    if (!target) return;
    if (target.getAttribute && target.getAttribute('data-close') === '1') {
      setModalOpen(false);
    }
  });

  const modalPanel = modal?.querySelector('.doodle-modal');
  modalPanel?.addEventListener('click', (e) => {
    e.stopPropagation();
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') setModalOpen(false);
  });

  async function addToCart(productId, qty) {
    const form = new URLSearchParams();
    form.set('action', 'add');
    form.set('product_id', String(productId));
    form.set('qty', String(qty));

    console.log('[products] addToCart request body:', form.toString());

    const res = await fetch('api/cart.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: form.toString(),
    });

    const text = await res.text();
    console.log('[products] addToCart raw response:', text);
    let data;
    try {
      data = JSON.parse(text);
    } catch {
      data = { ok: false, error: text };
    }

    if (!data.ok) {
      const msg = data.error || data.message || 'Failed to add to cart';
      throw new Error(msg);
    }
    return data;
  }

  async function addToCartMobile() {
    const pid = Number(mobileAddToCartBtn?.getAttribute('data-product-id') || 0);
    const qty = Math.max(1, Number(mobileAddQtyInput?.value || 1));
    if (!pid || pid <= 0) return;
    try {
      if (mobileAddToCartStatus) mobileAddToCartStatus.textContent = 'Adding…';
      mobileAddToCartBtn && (mobileAddToCartBtn.disabled = true);
      await addToCart(pid, qty);
      if (mobileAddToCartStatus) mobileAddToCartStatus.textContent = 'Added to cart ✅';
      await refreshCartBadge();
      setTimeout(() => setModalOpen(false), 600);
    } catch (e) {
      console.error(e);
      if (mobileAddToCartStatus) mobileAddToCartStatus.textContent = 'Failed: ' + (e?.message || '');
      mobileAddToCartBtn && (mobileAddToCartBtn.disabled = false);
    }
  }

  async function fetchCartCount() {
    try {
      const res = await fetch('api/cart.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: new URLSearchParams({ action: 'get' }).toString(),
      });
      const data = await res.json();
      if (!data.ok) return 0;
      const cart = data.cart || [];
      return cart.reduce((sum, x) => sum + Number(x.qty || 0), 0);
    } catch {
      return 0;
    }
  }

  function updateCartBadge(count) {
    const badge = document.getElementById('cartCountBadge');
    if (!badge) return;
    badge.textContent = String(Number(count || 0));
  }

  async function refreshCartBadge() {
    const count = await fetchCartCount();
    updateCartBadge(count);
  }

  function flashCartNav() {
    const link = document.getElementById('cartNavLink');
    if (!link) return;
    link.style.boxShadow = '0 0 0 3px rgba(34,197,94,0.25)';
    setTimeout(() => (link.style.boxShadow = ''), 350);
  }

  refreshCartBadge();

  addToCartBtn?.addEventListener('click', async () => {
    try {
      const pid = Number(addToCartBtn.getAttribute('data-product-id'));
      const qtyLeftText = modalQty ? modalQty.textContent : '0';
      const qtyLeft = Number(qtyLeftText || 0);
      const qty = Math.max(1, Number(addQtyInput?.value || 1));

      if (!pid || pid <= 0) return;
      if (qtyLeft <= 0) {
        if (addToCartStatus) addToCartStatus.textContent = 'No stock available.';
        return;
      }
      if (qty > qtyLeft) {
        if (addToCartStatus) addToCartStatus.textContent = `Only ${qtyLeft} left.`;
        return;
      }

      if (addToCartStatus) addToCartStatus.textContent = 'Adding…';
      addToCartBtn.disabled = true;

      await addToCart(pid, qty);
      if (addToCartStatus) addToCartStatus.textContent = 'Added to cart ✅';

      await refreshCartBadge();
      flashCartNav();

      location.href = 'cart.php';
    } catch (e) {
      console.error(e);
      if (addToCartStatus) addToCartStatus.textContent = 'Failed to add to cart: ' + (e?.message || '');
      addToCartBtn && (addToCartBtn.disabled = false);
    }
  });

  mobileAddToCartBtn?.addEventListener('click', async () => {
    try {
      const pid = Number(mobileAddToCartBtn.getAttribute('data-product-id'));
      const qty = Math.max(1, Number(mobileAddQtyInput?.value || 1));

      if (!pid || pid <= 0) return;

      if (mobileAddToCartStatus) mobileAddToCartStatus.textContent = 'Adding…';
      mobileAddToCartBtn.disabled = true;

      await addToCart(pid, qty);
      if (mobileAddToCartStatus) mobileAddToCartStatus.textContent = 'Added to cart ✅';

      await refreshCartBadge();
      flashCartNav();

      location.href = 'cart.php';
    } catch (e) {
      console.error(e);
      if (mobileAddToCartStatus) mobileAddToCartStatus.textContent = 'Failed: ' + (e?.message || '');
      mobileAddToCartBtn && (mobileAddToCartBtn.disabled = false);
    }
  });

  // Search dropdown autocomplete
  let allProductsForDropdown = [];
  let searchActiveIndex = -1;

  function renderSearchDropdown(items) {
    if (!searchDropdown) return;

    if (!items.length) {
      searchDropdown.hidden = true;
      searchDropdown.innerHTML = `
        <div class="no-results">
          <span style="font-size: 36px; transform: rotate(10deg);">🧐</span>
          <div>No chicks found</div>
          <div style="font-size: 14px; font-family: 'Comic Neue', sans-serif;">Try a different keyword</div>
        </div>
      `;
      searchDropdown.classList.add('active');
      return;
    }

    searchDropdown.hidden = false;
    searchDropdown.innerHTML = items
      .slice(0, 12)
      .map((p, idx) => {
        const safeName = escapeHtml(p.name || 'Product');
        const safeCat = escapeHtml(p.category || '');
        const safeId = escapeHtml(p.id);
        const safePrice = escapeHtml(String(p.price || ''));
        const rawImg = p.logo_path || p.image_path || p.image || '';
        const safeImg = escapeHtml(rawImg);
        const delay = idx * 0.04;
        return `
          <div class="result-item" role="option" tabindex="-1" data-idx="${idx}" data-product-id="${safeId}" data-product-name="${safeName}" style="animation-delay:${delay}s">
            <div class="result-icon-box">
              ${safeImg ? `<img src="${safeImg}" alt="" onerror="this.parentElement.textContent='📦'" />` : '📦'}
            </div>
            <div class="result-details">
              <div class="result-name">${safeName}</div>
              <div class="result-meta">
                ${safeCat ? `<span class="category-pill">${safeCat}</span>` : ''}
                <span>${escapeHtml(p.size || '')}</span>
              </div>
            </div>
            <div class="result-price">₹${safePrice}</div>
          </div>
        `;
      })
      .join('');

    searchDropdown.classList.add('active');
  }

  function closeSearchDropdown() {
    if (!searchDropdown) return;
    searchDropdown.classList.remove('active');
    searchDropdown.innerHTML = '';
    searchActiveIndex = -1;
  }

  function setSearchActiveIndex(i) {
    if (!searchDropdown) return;
    const items = Array.from(searchDropdown.querySelectorAll('.result-item'));
    items.forEach((el) => (el.style.background = 'transparent'));
    const active = items[i];
    if (active) {
      active.style.background = 'rgba(22,163,74,0.12)';
      active.scrollIntoView({ block: 'nearest' });
    }
  }

  function selectSuggestion(text) {
    if (!searchInput) return;
    searchInput.value = text;
    closeSearchDropdown();

    // Prefer matching by product name first
    const matchByName = (allProductsForDropdown || []).find(
      (p) => (p?.name || '').trim().toLowerCase() === String(text).trim().toLowerCase()
    );
    if (matchByName) {
      openProductDetails(matchByName);
      return;
    }

    // Fallback: if not found, keep old behavior.
    location.href = `products.php?search=${encodeURIComponent(text.trim())}`;
  }

  searchInput?.addEventListener('input', () => {
    if (!searchInput) return;
    const q = (searchInput.value || '').trim().toLowerCase();
    if (clearBtn) {
      if (q) clearBtn.classList.add('visible');
      else clearBtn.classList.remove('visible');
    }
    if (!q) {
      closeSearchDropdown();
      return;
    }

    const items = allProductsForDropdown
      .filter((p) => (p.name || '').toLowerCase().includes(q))
      .slice(0, 30);

    searchActiveIndex = -1;
    renderSearchDropdown(items);

    if (items.length) {
      setSearchActiveIndex(0);
      searchActiveIndex = 0;
    }
  });

  searchInput?.addEventListener('keydown', (e) => {
    if (!searchDropdown || !searchDropdown.classList.contains('active')) {
      if (e.key === 'Enter') {
        const q = (searchInput.value || '').trim();
        location.href = `products.php?search=${encodeURIComponent(q)}`;
      }
      return;
    }

    const items = Array.from(searchDropdown.querySelectorAll('.result-item'));
    if (!items.length) return;

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      searchActiveIndex = Math.min(items.length - 1, searchActiveIndex + 1);
      setSearchActiveIndex(searchActiveIndex);
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      searchActiveIndex = Math.max(0, searchActiveIndex - 1);
      setSearchActiveIndex(searchActiveIndex);
    } else if (e.key === 'Enter') {
      e.preventDefault();
      const active = items[searchActiveIndex] || items[0];
      const name = active?.getAttribute('data-product-name');
      if (name) selectSuggestion(name);
    } else if (e.key === 'Escape') {
      e.preventDefault();
      closeSearchDropdown();
    }
  });

  // Use mousedown instead of click so modal opens before blur/outside handlers run
  searchDropdown?.addEventListener('mousedown', (e) => {
    const item = e.target && e.target.closest ? e.target.closest('.result-item') : null;
    if (!item) return;

    // Prevent focus/blur side effects
    e.preventDefault();

    const pidAttr = item.getAttribute('data-product-id');
    const pid = pidAttr ? Number(pidAttr) : 0;

    if (pid) {
      const product = (allProductsForDropdown || []).find((p) => Number(p?.id) === pid);
      if (product) {
        closeSearchDropdown();
        if (searchInput) searchInput.value = product.name || '';
        openProductDetails(product);
      }
      return;
    }

    const name = item.getAttribute('data-product-name');
    if (name) selectSuggestion(name);
  });

  searchInput?.addEventListener('blur', () => {
    setTimeout(() => closeSearchDropdown(), 120);
  });

  clearBtn?.addEventListener('click', () => {
    if (!searchInput) return;
    searchInput.value = '';
    searchInput.focus();
    clearBtn.classList.remove('visible');
    closeSearchDropdown();
  });

  async function init() {
    try {
      const { category } = params();
      const products = await fetchProducts(category);
      allProductsForDropdown = products || [];
      render(products);

      // Auto-open product modal after QR scan
      // QR provides product id/code in format: "PRODUCT_CODE|BATCH..."
      // qr.html navigates here as: products.php?open_product=PRODUCT_CODE
      const u = new URL(location.href);
      const openProduct = (u.searchParams.get('open_product') || '').trim();

      if (openProduct) {
        // Auto-open modal after QR scan.
        // Depending on the scanner/backend, openProduct may be:
        //  - a SKU (e.g. "FEED-CALCIUM-001")
        //  - or a QR token/payload that starts with SKU before a "|" (e.g. "FEED-CALCIUM-001|BATCH-..." )
        // Match robustly by SKU, id, or by prefix before '|'.
        const open = String(openProduct).trim();
        const openSku = open.includes('|') ? open.split('|')[0].trim() : open;

        // ALSO handle case: open_product arrives URL-decoded but still contains whitespace.
        const openNorm = openSku.replace(/\s+/g, '');

        const match = (products || []).find((p) => {
          const id = Number(p?.id);
          const sku = String(p?.sku || '').trim();
          const skuNorm = sku.replace(/\s+/g, '');
          return (
            (sku && sku === open) ||
            (skuNorm && skuNorm === openNorm) ||
            (sku && sku === openSku) ||
            (id && String(id) === open)
          );
        }) || null;


        if (match) {
          setTimeout(() => openProductDetails(match), 80);
        } else {
          console.warn('[QR] open_product not found. openProduct=', openProduct);
        }
      }

    } catch (e) {
      const msg = (e && e.message) ? e.message : String(e);
      const errorHtml = '<div class="doodle-card">Error loading products: ' + escapeHtml(msg) + '</div>';
      if (grid) grid.innerHTML = errorHtml;
      if (mobileGrid) mobileGrid.innerHTML = errorHtml;
      console.error(e);
    }
  }

  init();


  qsa('.sidebar a').forEach((a) => a.addEventListener('click', () => document.body.classList.remove('nav-open')));
})();

