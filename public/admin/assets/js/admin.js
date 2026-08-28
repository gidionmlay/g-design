/* ============================================================
   G DESIGN — Admin dashboard core
   Shared API client, auth/session handling, safe DOM helpers,
   tiny hash router, and shell behaviour.
   ============================================================ */
(function (global) {
  'use strict';

  var API = '/api/v1';
  var LOGIN_URL = '/admin/index.html';
  var TOAST_TIMEOUT = 4200;
  var sessionExpiredHandled = false;

  var STATUS = {
    pending:      { label: 'Pending',    cls: 'status-pending' },
    reviewing:    { label: 'Reviewing',  cls: 'status-reviewing' },
    in_progress:  { label: 'In Progress', cls: 'status-in_progress' },
    completed:    { label: 'Completed',  cls: 'status-completed' },
    cancelled:    { label: 'Cancelled',  cls: 'status-cancelled' }
  };

  /* ---------- Safe string escaping (belt-and-braces for templates) ---------- */
  function esc(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  /* ---------- Safe DOM builder (textContent-based) ---------- */
  function el(tag, attrs, children) {
    var node = document.createElement(tag);
    if (attrs) {
      Object.keys(attrs).forEach(function (key) {
        var value = attrs[key];
        if (key === 'class') {
          node.className = value;
        } else if (key === 'dataset') {
          Object.keys(value).forEach(function (d) { node.dataset[d] = value[d]; });
        } else if (key === 'html') {
          node.innerHTML = value;
        } else if (key === 'text') {
          node.textContent = value;
        } else if (key.indexOf('on') === 0 && typeof value === 'function') {
          node.addEventListener(key.slice(2), value);
        } else if (value != null && value !== false) {
          node.setAttribute(key, value === true ? '' : String(value));
        }
      });
    }
    if (children) {
      if (typeof children === 'string') {
        node.textContent = children;
      } else if (Array.isArray(children)) {
        children.forEach(function (child) {
          if (child == null || child === false) { return; }
          node.appendChild(typeof child === 'string' ? document.createTextNode(child) : child);
        });
      } else {
        node.appendChild(children);
      }
    }
    return node;
  }

  /* ---------- Formatters ---------- */
  function formatDate(value) {
    if (!value) { return '—'; }
    var d = new Date(String(value).replace(' ', 'T') + 'Z');
    if (isNaN(d.getTime())) { return String(value); }
    return d.toLocaleString(undefined, {
      year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
    });
  }

  function formatSize(bytes) {
    var n = Number(bytes) || 0;
    if (n < 1024) { return n + ' B'; }
    if (n < 1024 * 1024) { return (n / 1024).toFixed(1) + ' KB'; }
    return (n / (1024 * 1024)).toFixed(1) + ' MB';
  }

  function statusMeta(status) {
    return STATUS[status] || { label: String(status).replace(/_/g, ' '), cls: 'status-pending' };
  }

  function statusBadge(status) {
    var meta = statusMeta(status);
    return el('span', { class: 'badge ' + meta.cls },
      [el('span', { class: 'dot' }), meta.label]);
  }

  /* ---------- API client ---------- */
  function api(method, path, body) {
    return new Promise(function (resolve, reject) {
      var xhr = new XMLHttpRequest();
      xhr.open(method, API + path, true);
      xhr.timeout = 15000;
      xhr.setRequestHeader('Accept', 'application/json');
      var isForm = body instanceof FormData;
      if (body && !isForm) {
        xhr.setRequestHeader('Content-Type', 'application/json');
      }
      xhr.onload = function () {
        var parsed = null;
        try { parsed = JSON.parse(xhr.responseText); } catch (e) { parsed = null; }

        if (xhr.status === 401) {
          handleSessionExpired(parsed);
          return;
        }
        if (xhr.status >= 200 && xhr.status < 300) {
          resolve({ status: xhr.status, body: parsed });
          return;
        }
        var err = new Error((parsed && parsed.error && parsed.error.message) || 'Request failed.');
        err.status = xhr.status;
        err.body = parsed;
        reject(err);
      };
      xhr.onerror = function () { reject(new Error('Network error. Please check your connection.')); };
      xhr.ontimeout = function () { reject(new Error('The request timed out. Please try again.')); };
      xhr.send(body ? (isForm ? body : JSON.stringify(body)) : null);
    });
  }

  var get  = function (path) { return api('GET', path); };
  var post = function (path, body) { return api('POST', path, body); };
  var patch = function (path, body) { return api('PATCH', path, body); };

  function redirectToLogin(expired) {
    if (sessionExpiredHandled) { return; }
    sessionExpiredHandled = true;
    window.location.replace(LOGIN_URL + (expired ? '?expired=1' : ''));
  }

  function handleSessionExpired() {
    redirectToLogin(true);
  }

  /* ---------- Toast ---------- */
  function toast(message, isError) {
    var existing = document.getElementById('toast');
    if (existing) { existing.remove(); }
    var node = el('div', { class: 'toast' + (isError ? ' err' : ''), id: 'toast', role: 'status' }, message);
    document.body.appendChild(node);
    setTimeout(function () { node.remove(); }, TOAST_TIMEOUT);
  }

  /* ---------- Shell ---------- */
  var currentViewNode = null;

  function router() {
    var hash = location.hash.replace(/^#\/?/, '') || 'overview';
    var parts = hash.split('/');
    var route = parts[0] || 'overview';
    var viewNode = document.getElementById('view');

    if (route === 'requests' && parts[1]) {
      renderView(viewNode, global.GDApp.views.requestDetails, parts[1]);
    } else if (route === 'requests') {
      renderView(viewNode, global.GDApp.views.requests, null);
    } else if (route === 'services' && parts[1]) {
      renderView(viewNode, global.GDApp.views.serviceEditor, { id: parts[1], mode: 'edit' });
    } else if (route === 'services') {
      renderView(viewNode, global.GDApp.views.services, null);
    } else if (route === 'categories') {
      renderView(viewNode, global.GDApp.views.categories, null);
    } else if (route === 'settings') {
      renderView(viewNode, global.GDApp.views.settings, null);
    } else {
      renderView(viewNode, global.GDApp.views.overview, null);
    }

    setActiveNav(route);
  }

  function setActiveNav(route) {
    var group = route;
    if (route === 'services' || route === 'categories') {
      group = 'catalog';
    }
    var buttons = document.querySelectorAll('.nav-link[data-route]');
    buttons.forEach(function (btn) {
      var key = btn.getAttribute('data-route') === 'catalog' ? 'catalog'
        : (btn.getAttribute('data-route') === 'services' || btn.getAttribute('data-route') === 'categories'
           ? 'catalog' : btn.getAttribute('data-route'));
      btn.classList.toggle('active', key === group && btn.getAttribute('data-route') === route);
      if (route === 'services' && btn.getAttribute('data-route') === 'services') {
        btn.classList.add('active');
      }
      if (route === 'categories' && btn.getAttribute('data-route') === 'categories') {
        btn.classList.add('active');
      }
    });
    var titles = {
      overview: 'Overview',
      requests: 'Requests',
      settings: 'Settings',
      categories: 'Service Categories',
      services: 'Services'
    };
    document.getElementById('page-title').textContent = titles[route] || 'Services';
    document.body.classList.toggle('catalog-open', route === 'catalog' || route === 'categories' || route === 'services');
    closeSidebar();
  }

  function renderView(viewNode, renderer, param) {
    if (currentViewNode) { currentViewNode.innerHTML = ''; }
    currentViewNode = viewNode;
    try {
      renderer(viewNode, param);
    } catch (e) {
      viewNode.innerHTML = '';
      viewNode.appendChild(renderError(e));
      console.error(e);
    }
  }

  function renderLoading(text) {
    return el('div', { class: 'loading' }, el('span', { text: text || 'Loading…' }));
  }

  function renderError(message, onRetry) {
    var box = el('div', { class: 'error' },
      [
        el('h3', { text: 'Something went wrong' }),
        el('p', { text: message || 'Something went wrong while loading this information.' })
      ]);
    if (onRetry) {
      box.appendChild(el('button', { class: 'btn btn-accent', type: 'button', onclick: onRetry }, 'Try Again'));
    }
    return box;
  }

  function renderEmpty(title, body) {
    return el('div', { class: 'empty' },
      [el('h3', { text: title || 'Nothing here yet' }),
       el('p', { text: body || '' })]);
  }

  function openPreview(imageUrl, name) {
    var modal = document.getElementById('preview-modal');
    var img = document.getElementById('preview-img');
    img.src = imageUrl;
    img.alt = name || 'Attachment preview';
    document.getElementById('preview-name').textContent = name || '';
    modal.classList.add('open');
  }

  function closePreview() {
    var modal = document.getElementById('preview-modal');
    modal.classList.remove('open');
    document.getElementById('preview-img').removeAttribute('src');
  }

  function openSidebar() {
    document.getElementById('sidebar').classList.add('open');
    document.getElementById('backdrop').classList.add('show');
    document.getElementById('menu-btn').setAttribute('aria-expanded', 'true');
  }

  function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('backdrop').classList.remove('show');
    var btn = document.getElementById('menu-btn');
    if (btn) { btn.setAttribute('aria-expanded', 'false'); }
  }

  function boot() {
    get('/admin/auth/me')
      .then(function (res) {
        if (!res.body || !res.body.ok || !res.body.data || !res.body.data.admin) {
          throw new Error('unexpected');
        }
        var admin = res.body.data.admin;
        global.GDApp.currentAdmin = admin;
        var short = (admin.full_name || admin.username || 'A').split(' ').map(function (w) { return w[0] || ''; }).join('').slice(0, 2).toUpperCase();
        document.getElementById('admin-avatar').textContent = short;
        document.getElementById('admin-name').textContent = admin.username;

        window.addEventListener('hashchange', router);
        if (!location.hash) { location.hash = '#/overview'; }
        router();
      })
      .catch(function () {
        redirectToLogin(false);
      });

    document.getElementById('logout').addEventListener('click', function () {
      post('/admin/auth/logout').finally(function () {
        window.location.replace(LOGIN_URL);
      });
    });

    document.getElementById('menu-btn').addEventListener('click', openSidebar);
    document.getElementById('backdrop').addEventListener('click', closeSidebar);
    document.getElementById('preview-close').addEventListener('click', closePreview);
    var catalogClose = document.getElementById('catalog-modal-close');
    if (catalogClose) {
      catalogClose.addEventListener('click', function () {
        document.getElementById('catalog-modal').classList.remove('open');
      });
    }
    var catalogModal = document.getElementById('catalog-modal');
    if (catalogModal) {
      catalogModal.addEventListener('click', function (e) {
        if (e.target === catalogModal) {
          catalogModal.classList.remove('open');
        }
      });
    }
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { closePreview(); closeSidebar(); }
    });
    document.querySelectorAll('.sidebar .nav-link[data-route]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var route = btn.getAttribute('data-route');
        closeSidebar();
        if (route === 'catalog') {
          document.body.classList.toggle('catalog-open');
          return;
        }
        if (route === 'overview') { location.hash = '#/overview'; }
        else if (route === 'requests') { location.hash = '#/requests'; }
        else { location.hash = '#/' + route; }
      });
    });
  }

  global.GDApp = {
    API: API,
    el: el,
    esc: esc,
    get: get,
    post: post,
    patch: patch,
    toast: toast,
    formatDate: formatDate,
    formatSize: formatSize,
    statusMeta: statusMeta,
    statusBadge: statusBadge,
    renderLoading: renderLoading,
    renderError: renderError,
    renderEmpty: renderEmpty,
    openPreview: openPreview,
    views: {}
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})(window);