(function () {
  'use strict';

  var cfg = window.BXM_APP || {};
  var base = cfg.base || '';
  var currency = cfg.currency || '\u20B9';

  var state = {
    user: cfg.loggedIn ? { uid: cfg.userId || '', role: cfg.role || 'user' } : null,
    profile: cfg.loggedIn ? { uid: cfg.userId || '', role: cfg.role || 'user' } : null,
    cart: {},
    wishlist: {},
    notifications: {},
    unread: 0
  };

  function url(path) {
    return base + '/' + String(path || '').replace(/^\//, '');
  }

  function api(path) {
    return url('api/' + String(path || '').replace(/^\//, ''));
  }

  function escapeHtml(value) {
    return String(value === null || value === undefined ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function toNumber(value) {
    var n = parseFloat(value);
    return isNaN(n) ? 0 : n;
  }

  function money(value) {
    return currency + toNumber(value).toLocaleString('en-IN', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }

  function plainNumber(value) {
    return toNumber(value).toLocaleString('en-IN', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }

  function toast(message, type, timeout) {
    var stack = document.getElementById('bxmToastStack');
    if (!stack) {
      return;
    }
    var icons = {
      success: 'bi-check-circle-fill',
      danger: 'bi-x-circle-fill',
      warning: 'bi-exclamation-triangle-fill',
      info: 'bi-info-circle-fill'
    };
    var el = document.createElement('div');
    el.className = 'bxm-toast toast-' + (type || 'info');
    el.innerHTML = '<i class="bi ' + (icons[type] || icons.info) + '"></i><div>' + escapeHtml(message) + '</div>';
    stack.appendChild(el);
    setTimeout(function () {
      el.style.opacity = '0';
      el.style.transform = 'translateX(20px)';
      el.style.transition = 'all .25s ease';
      setTimeout(function () {
        if (el.parentNode) {
          el.parentNode.removeChild(el);
        }
      }, 250);
    }, timeout || 4000);
  }

  function loader(show) {
    var el = document.getElementById('bxmLoader');
    if (el) {
      el.classList.toggle('show', !!show);
    }
  }

  function copyText(text) {
    var value = String(text || '');
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(value).then(function () {
        toast('Copied to clipboard', 'success');
      });
    }
    var ta = document.createElement('textarea');
    ta.value = value;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    try {
      document.execCommand('copy');
      toast('Copied to clipboard', 'success');
    } catch (e) {
      toast('Copy failed', 'danger');
    }
    document.body.removeChild(ta);
    return Promise.resolve();
  }

  function confirmDialog(message, options) {
    options = options || {};
    return new Promise(function (resolve) {
      var id = 'bxmConfirm' + Date.now();
      var wrap = document.createElement('div');
      wrap.className = 'modal fade';
      wrap.id = id;
      wrap.tabIndex = -1;
      wrap.innerHTML =
        '<div class="modal-dialog modal-dialog-centered">' +
        '<div class="modal-content bxm-modal-content">' +
        '<div class="modal-header"><h5 class="modal-title">' + escapeHtml(options.title || 'Please confirm') + '</h5>' +
        '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>' +
        '<div class="modal-body"><p class="mb-0 text-secondary">' + escapeHtml(message) + '</p></div>' +
        '<div class="modal-footer">' +
        '<button type="button" class="bxm-btn bxm-btn-outline" data-bs-dismiss="modal">' + escapeHtml(options.cancelText || 'Cancel') + '</button>' +
        '<button type="button" class="bxm-btn ' + (options.danger ? 'bxm-btn-danger' : 'bxm-btn-primary') + '" data-confirm>' + escapeHtml(options.confirmText || 'Confirm') + '</button>' +
        '</div></div></div>';
      document.body.appendChild(wrap);
      var modal = new bootstrap.Modal(wrap);
      var decided = false;
      wrap.querySelector('[data-confirm]').addEventListener('click', function () {
        decided = true;
        modal.hide();
        resolve(true);
      });
      wrap.addEventListener('hidden.bs.modal', function () {
        if (!decided) {
          resolve(false);
        }
        wrap.remove();
      });
      modal.show();
    });
  }

  function statusBadge(status) {
    var map = {
      pending: ['bxm-badge-pending', 'Pending'],
      payment_submitted: ['bxm-badge-pending', 'Payment Submitted'],
      under_review: ['bxm-badge-pending', 'Under Review'],
      approved: ['bxm-badge-success', 'Approved'],
      completed: ['bxm-badge-success', 'Completed'],
      rejected: ['bxm-badge-danger', 'Rejected'],
      cancelled: ['bxm-badge-info', 'Cancelled'],
      active: ['bxm-badge-success', 'Active'],
      inactive: ['bxm-badge-info', 'Inactive'],
      out_of_stock: ['bxm-badge-danger', 'Out of Stock'],
      open: ['bxm-badge-pending', 'Open'],
      replied: ['bxm-badge-success', 'Replied'],
      closed: ['bxm-badge-info', 'Closed']
    };
    var item = map[String(status)] || ['bxm-badge-info', String(status || 'Unknown')];
    return '<span class="bxm-badge ' + item[0] + '">' + escapeHtml(item[1]) + '</span>';
  }

  function discountPercent(product) {
    if (product.discountPercent !== undefined && product.discountPercent !== null && product.discountPercent !== '') {
      return toNumber(product.discountPercent);
    }
    var original = toNumber(product.originalPrice);
    var discounted = toNumber(product.discountedPrice);
    if (original <= 0) {
      return 0;
    }
    return Math.round(((original - discounted) / original) * 100);
  }

  function productCardHTML(product) {
    var id = product.id;
    var percent = discountPercent(product);
    var wished = !!state.wishlist[id];
    var status = product.status || 'active';
    var outOfStock = status === 'out_of_stock';
    var href = url('product-details.php?id=' + encodeURIComponent(id));
    var html = '';
    html += '<div class="bxm-product-card bxm-fade-in">';
    html += '<div class="bxm-product-thumb">';
    html += '<a href="' + href + '"><img src="' + escapeHtml(product.thumbnailUrl || url('assets/images/logo/logo.jpeg')) + '" alt="' + escapeHtml(product.title || 'Product') + '" loading="lazy"></a>';
    if (percent > 0) {
      html += '<span class="bxm-discount-badge">-' + percent + '%</span>';
    }
    html += '<button type="button" class="bxm-icon-btn bxm-wish-btn' + (wished ? ' active' : '') + '" data-bxm-wish="' + escapeHtml(id) + '" aria-label="Wishlist"><i class="bi ' + (wished ? 'bi-heart-fill' : 'bi-heart') + '"></i></button>';
    html += '</div>';
    html += '<div class="bxm-product-body">';
    html += '<a href="' + href + '" class="bxm-product-title">' + escapeHtml(product.title || 'Untitled') + '</a>';
    html += '<div class="bxm-product-cat">' + escapeHtml(product.category || 'Digital') + '</div>';
    html += '<div class="mb-3 d-flex align-items-center">';
    html += '<span class="bxm-price-current">' + money(product.discountedPrice || product.originalPrice) + '</span>';
    if (toNumber(product.originalPrice) > toNumber(product.discountedPrice)) {
      html += '<span class="bxm-price-original">' + money(product.originalPrice) + '</span>';
    }
    html += '</div>';
    html += '<div class="mt-auto d-flex gap-2">';
    html += '<button type="button" class="bxm-btn bxm-btn-primary flex-grow-1' + (outOfStock ? ' disabled' : '') + '" data-bxm-buy-now="' + escapeHtml(id) + '"' + (outOfStock ? ' disabled' : '') + '>';
    html += '<i class="bi bi-lightning-charge"></i> ' + (outOfStock ? 'Out of Stock' : 'Buy Now');
    html += '</button>';
    html += '<a href="' + href + '" class="bxm-btn bxm-btn-outline" aria-label="View details"><i class="bi bi-arrow-right"></i></a>';
    html += '</div></div></div>';
    return html;
  }

  function renderProducts(container, products) {
    if (!container) {
      return;
    }
    if (!products || !products.length) {
      container.innerHTML = '<div class="col-12"><div class="bxm-state-box text-center"><i class="bi bi-search bxm-state-icon"></i><h5>No products found</h5><p class="text-secondary mb-0">Try another search or browse all products.</p></div></div>';
      return;
    }
    var html = '';
    products.forEach(function (p) {
      html += '<div class="col-6 col-md-4 col-lg-3">' + productCardHTML(p) + '</div>';
    });
    container.innerHTML = html;
  }

  function requireLogin(redirectPath) {
    if (state.user) {
      return true;
    }
    toast('Please login to continue', 'warning');
    var target = redirectPath || (window.location.pathname + window.location.search);
    setTimeout(function () {
      window.location.href = url('auth/login.php?redirect=' + encodeURIComponent(target));
    }, 700);
    return false;
  }

  function csrfToken() {
    return (window.BXM_APP && window.BXM_APP.csrf) || '';
  }

  function csrfHeaders(extra) {
    var headers = extra || {};
    var token = csrfToken();
    if (token) {
      headers['X-CSRF-TOKEN'] = token;
    }
    return headers;
  }

  function apiFetch(path, options) {
    options = options || {};
    var headers = csrfHeaders(options.headers || {});
    if (options.json) {
      headers['Content-Type'] = 'application/json';
    }
    return fetch(api(path), {
      method: options.method || 'GET',
      credentials: 'same-origin',
      headers: headers,
      body: options.json ? JSON.stringify(options.json) : options.body
    }).then(function (res) {
      return res.json().catch(function () {
        return { ok: false, error: 'Invalid server response' };
      });
    }).catch(function () {
      return { ok: false, error: 'Network error. Please try again.' };
    });
  }

  function uploadImage(file, folder) {
    var fd = new FormData();
    fd.append('image', file);
    fd.append('folder', folder || 'misc');
    return fetch(api('upload-image.php'), {
      method: 'POST',
      credentials: 'same-origin',
      headers: csrfHeaders({}),
      body: fd
    }).then(function (res) {
      return res.json().catch(function () {
        return { ok: false, error: 'Upload failed' };
      });
    }).catch(function () {
      return { ok: false, error: 'Upload failed' };
    });
  }

  function addToCart(productId, options) {
    options = options || {};
    if (!requireLogin()) {
      return Promise.resolve(false);
    }
    if (!productId) {
      return Promise.resolve(false);
    }
    return apiFetch('cart.php', { method: 'POST', json: { action: 'add', productId: productId } }).then(function (res) {
      if (!res.ok) {
        toast(options.errorMessage || res.error || 'Could not add to cart', 'danger');
        return false;
      }
      loadCart().then(function () {
        if (!options.silent) {
          toast('Added to cart', 'success');
        }
      });
      return true;
    });
  }

  function buyNow(productId) {
    if (!requireLogin()) {
      return;
    }
    if (!productId) {
      return;
    }
    apiFetch('cart.php', { method: 'POST', json: { action: 'buy-now', productId: productId } }).then(function (res) {
      if (!res.ok) {
        toast(res.error || 'Could not start payment', 'danger');
        return;
      }
      window.location.href = url('payment.php');
    });
  }

  function setCartQuantity(productId, quantity) {
    return apiFetch('cart.php', { method: 'POST', json: { action: 'set', productId: productId, quantity: quantity } }).then(function (res) {
      return loadCart();
    });
  }

  function removeFromCart(productId) {
    return apiFetch('cart.php', { method: 'POST', json: { action: 'remove', productId: productId } }).then(function () {
      return loadCart().then(function () {
        toast('Removed from cart', 'info');
      });
    });
  }

  function toggleWishlist(productId) {
    if (!requireLogin()) {
      return Promise.resolve();
    }
    return apiFetch('wishlist.php', { method: 'POST', json: { action: 'toggle', productId: productId } }).then(function (res) {
      if (!res.ok) {
        toast(res.error || 'Could not update wishlist', 'danger');
        return;
      }
      return loadWishlist().then(function () {
        toast(res.wished ? 'Added to wishlist' : 'Removed from wishlist', res.wished ? 'success' : 'info');
      });
    });
  }

  function moveToCart(productId) {
    if (!requireLogin()) {
      return;
    }
    apiFetch('wishlist.php', { method: 'POST', json: { action: 'move-to-cart', productId: productId } }).then(function (res) {
      if (!res.ok) {
        toast(res.error || 'Could not start payment', 'danger');
        return;
      }
      window.location.href = url('payment.php');
    });
  }

  function updateCartCount() {
    var count = 0;
    Object.keys(state.cart || {}).forEach(function (key) {
      count += toNumber(state.cart[key] && state.cart[key].quantity) || 1;
    });
    document.querySelectorAll('[data-bxm-cart-count]').forEach(function (el) {
      el.textContent = count;
      el.hidden = count === 0;
    });
    return count;
  }

  function updateWishlistCount() {
    var count = Object.keys(state.wishlist || {}).length;
    document.querySelectorAll('[data-bxm-wishlist-count]').forEach(function (el) {
      el.textContent = count;
      el.hidden = count === 0;
    });
    return count;
  }

  function loadCart() {
    if (!state.user) {
      state.cart = {};
      updateCartCount();
      document.dispatchEvent(new CustomEvent('bxm:cart', { detail: state.cart }));
      return Promise.resolve(state.cart);
    }
    return apiFetch('cart.php?action=list').then(function (res) {
      state.cart = (res && res.items) ? res.items : {};
      updateCartCount();
      document.dispatchEvent(new CustomEvent('bxm:cart', { detail: state.cart }));
      return state.cart;
    });
  }

  function loadWishlist() {
    if (!state.user) {
      state.wishlist = {};
      updateWishlistCount();
      document.dispatchEvent(new CustomEvent('bxm:wishlist', { detail: state.wishlist }));
      return Promise.resolve(state.wishlist);
    }
    return apiFetch('wishlist.php?action=list').then(function (res) {
      var map = {};
      (res && res.ids ? res.ids : []).forEach(function (id) { map[id] = true; });
      state.wishlist = map;
      updateWishlistCount();
      document.dispatchEvent(new CustomEvent('bxm:wishlist', { detail: state.wishlist }));
      return state.wishlist;
    });
  }

  function notifIcon(type) {
    var map = {
      success: 'bi-check-circle-fill text-success',
      danger: 'bi-x-circle-fill text-danger',
      warning: 'bi-exclamation-triangle-fill text-warning',
      info: 'bi-info-circle-fill text-info'
    };
    return map[type] || map.info;
  }

  function updateNotifCount() {
    document.querySelectorAll('[data-bxm-notif-count]').forEach(function (el) {
      el.textContent = state.unread > 99 ? '99+' : state.unread;
      el.hidden = state.unread === 0;
    });
  }

  function timeAgo(ts) {
    var diff = Date.now() - Number(ts || 0);
    if (!ts || diff < 0) {
      return 'just now';
    }
    var mins = Math.floor(diff / 60000);
    if (mins < 1) return 'just now';
    if (mins < 60) return mins + 'm ago';
    var hours = Math.floor(mins / 60);
    if (hours < 24) return hours + 'h ago';
    var days = Math.floor(hours / 24);
    if (days < 30) return days + 'd ago';
    return new Date(Number(ts)).toLocaleDateString();
  }

  function renderNotifications() {
    var list = Object.keys(state.notifications).map(function (id) {
      var n = state.notifications[id] || {};
      n.id = id;
      return n;
    }).sort(function (a, b) { return Number(b.createdAt || 0) - Number(a.createdAt || 0); });

    state.unread = list.filter(function (n) { return !n.read; }).length;
    updateNotifCount();

    document.querySelectorAll('[data-bxm-notif-list]').forEach(function (host) {
      if (!list.length) {
        host.innerHTML = '<div class="bxm-notif-empty"><i class="bi bi-bell-slash"></i><span>No notifications yet.</span></div>';
        return;
      }
      host.innerHTML = list.slice(0, 10).map(function (n) {
        var unread = !n.read;
        return '<a class="bxm-notif-item' + (unread ? ' unread' : '') + '" href="' + escapeHtml(n.link || '#') + '" data-bxm-notif-read="' + escapeHtml(n.id) + '">' +
          '<span class="bxm-notif-icon"><i class="bi ' + notifIcon(n.type) + '"></i></span>' +
          '<span class="flex-grow-1 min-w-0"><span class="bxm-notif-title">' + escapeHtml(n.title || 'Notification') + '</span>' +
          '<span class="bxm-notif-msg">' + escapeHtml(n.message || '') + '</span>' +
          '<span class="bxm-notif-time">' + escapeHtml(timeAgo(n.createdAt)) + '</span></span>' +
          (unread ? '<span class="bxm-notif-dot"></span>' : '') +
          '</a>';
      }).join('');
    });
  }

  function markNotificationRead(id) {
    if (!state.user || !id) {
      return;
    }
    var n = state.notifications[id];
    if (!n || n.read) {
      return;
    }
    n.read = true;
    renderNotifications();
    apiFetch('notifications.php', { method: 'POST', json: { action: 'read', id: id } });
  }

  function markAllNotificationsRead() {
    if (!state.user) {
      return;
    }
    var changed = false;
    Object.keys(state.notifications).forEach(function (id) {
      if (!state.notifications[id].read) {
        state.notifications[id].read = true;
        changed = true;
      }
    });
    renderNotifications();
    if (changed) {
      apiFetch('notifications.php', { method: 'POST', json: { action: 'read-all' } });
      toast('All notifications marked as read', 'success');
    }
  }

  function loadNotifications() {
    if (!state.user) {
      state.notifications = {};
      renderNotifications();
      return Promise.resolve(state.notifications);
    }
    return apiFetch('notifications.php?action=list').then(function (res) {
      var map = {};
      (res && res.notifications ? res.notifications : []).forEach(function (n) {
        map[n.id] = n;
      });
      state.notifications = map;
      renderNotifications();
      document.dispatchEvent(new CustomEvent('bxm:notifications', { detail: state.notifications }));
      return state.notifications;
    });
  }

  function initGlobalEvents() {
    document.addEventListener('click', function (e) {
      var buy = e.target.closest('[data-bxm-buy-now]');
      if (buy) {
        e.preventDefault();
        if (buy.disabled || buy.classList.contains('disabled')) {
          return;
        }
        buyNow(buy.getAttribute('data-bxm-buy-now'));
        return;
      }
      var wish = e.target.closest('[data-bxm-wish]');
      if (wish) {
        e.preventDefault();
        toggleWishlist(wish.getAttribute('data-bxm-wish'));
        return;
      }
      var copy = e.target.closest('[data-bxm-copy]');
      if (copy) {
        e.preventDefault();
        copyText(copy.getAttribute('data-bxm-copy'));
        return;
      }
      var notifRead = e.target.closest('[data-bxm-notif-read]');
      if (notifRead) {
        markNotificationRead(notifRead.getAttribute('data-bxm-notif-read'));
        return;
      }
      var notifAll = e.target.closest('[data-bxm-notif-all]');
      if (notifAll) {
        e.preventDefault();
        markAllNotificationsRead();
        return;
      }
      var logout = e.target.closest('[data-bxm-logout]');
      if (logout) {
        if (logout.getAttribute('href') && logout.getAttribute('href') !== '#') {
          return;
        }
        e.preventDefault();
        confirmDialog('Are you sure you want to logout?', { confirmText: 'Logout' }).then(function (ok) {
          if (!ok) {
            return;
          }
          window.location.href = url('auth/logout.php');
        });
      }
    });
  }

  window.BXM = {
    url: url,
    api: api,
    escapeHtml: escapeHtml,
    money: money,
    plainNumber: plainNumber,
    toNumber: toNumber,
    toast: toast,
    loader: loader,
    copyText: copyText,
    confirmDialog: confirmDialog,
    statusBadge: statusBadge,
    discountPercent: discountPercent,
    productCardHTML: productCardHTML,
    renderProducts: renderProducts,
    requireLogin: requireLogin,
    apiFetch: apiFetch,
    uploadImage: uploadImage,
    addToCart: addToCart,
    buyNow: buyNow,
    setCartQuantity: setCartQuantity,
    removeFromCart: removeFromCart,
    toggleWishlist: toggleWishlist,
    moveToCart: moveToCart,
    markNotificationRead: markNotificationRead,
    markAllNotificationsRead: markAllNotificationsRead,
    renderNotifications: renderNotifications,
    timeAgo: timeAgo,
    loadCart: loadCart,
    loadWishlist: loadWishlist,
    loadNotifications: loadNotifications,
    state: state,
    get user() { return state.user; },
    get profile() { return state.profile; },
    get isAdmin() { return !!(state.user && state.user.role === 'admin'); }
  };

  function boot() {
    initGlobalEvents();
    if (state.user) {
      loadCart();
      loadWishlist();
      loadNotifications();
    }
    document.querySelectorAll('[data-bxm-year]').forEach(function (el) {
      el.textContent = new Date().getFullYear();
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
