/* ============================================================
   G DESIGN — Requests list view
   Search, status/service filters, pagination, refresh,
   loading / error / empty states.
   ============================================================ */
(function (GD) {
  'use strict';

  var STATUS_OPTIONS = [
    ['pending', 'Pending'],
    ['reviewing', 'Reviewing'],
    ['in_progress', 'In Progress'],
    ['completed', 'Completed'],
    ['cancelled', 'Cancelled']
  ];

  var state = {
    page: 1,
    limit: 20,
    search: '',
    status: '',
    service: '',
    catalogLoaded: false
  };

  var debounceTimer = null;

  function buildRow(req) {
    var cell = function (children) { return GD.el('td', {}, children); };
    var viewBtn = GD.el('a', {
      class: 'btn btn-sm',
      href: '#/requests/' + req.id,
      onclick: function (e) { e.preventDefault(); location.hash = '#/requests/' + req.id; }
    }, 'View');

    return GD.el('tr', {
      tabindex: '0',
      'aria-label': 'Open request ' + req.reference,
      onclick: function () { location.hash = '#/requests/' + req.id; },
      onkeydown: function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); location.hash = '#/requests/' + req.id; }
      }
    }, [
      cell(GD.el('a', { class: 'row-link', href: '#/requests/' + req.id, onclick: function (e) { e.preventDefault(); location.hash = '#/requests/' + req.id; } }, req.reference)),
      cell([GD.el('div', { class: 'cell-main' }, req.client.name),
            GD.el('div', { class: 'cell-sub' }, req.client.email)]),
      cell([GD.el('div', { class: 'cell-main' }, req.service.name),
            req.service.category ? GD.el('div', { class: 'cell-sub' }, req.service.category) : null]),
      cell(GD.statusBadge(req.status)),
      cell(GD.el('span', { class: 'count' }, '📎 ' + String(req.attachments_count))),
      cell(GD.el('span', {}, GD.formatDate(req.created_at))),
      cell(GD.el('div', { class: 'row-actions' }, viewBtn))
    ]);
  }

  function renderTable(container, data) {
    var items = data.items || [];
    if (!items.length) {
      var hasFilters = Boolean(state.search || state.status || state.service);
      container.appendChild(GD.renderEmpty(
        hasFilters ? 'No matching requests' : 'No requests yet',
        hasFilters
          ? 'Try adjusting your search or filters.'
          : 'Service requests submitted through the website will appear here.'
      ));
      return;
    }

    var table = GD.el('table', { class: 'list' },
      [
        GD.el('thead', {}, GD.el('tr', {},
          ['Reference', 'Client', 'Service', 'Status', 'Files', 'Date', ''].map(function (h) {
            return GD.el('th', { scope: 'col' }, h);
          }))),
        GD.el('tbody', {}, items.map(buildRow))
      ]);

    container.appendChild(GD.el('div', { class: 'table-wrap' }, table));
  }

  function renderPager(container, pagination) {
    var total = pagination.total;
    var pages = Math.max(1, pagination.pages);
    var page = pagination.page;

    var prev = GD.el('button', {
      class: 'btn btn-sm',
      type: 'button',
      disabled: page <= 1,
      onclick: function () { state.page = page - 1; loadList(container); }
    }, '← Prev');

    var next = GD.el('button', {
      class: 'btn btn-sm',
      type: 'button',
      disabled: page >= pages,
      onclick: function () { state.page = page + 1; loadList(container); }
    }, 'Next →');

    container.appendChild(GD.el('div', { class: 'pager' },
      [prev, GD.el('span', {},
          'Page ' + page + ' of ' + pages + ' · ' + total + ' request' + (total === 1 ? '' : 's')),
       GD.el('span', { class: 'spacer' }), next]));
  }

  function makeStatusSelect() {
    var select = GD.el('select', {
      class: 'select',
      'aria-label': 'Filter by status',
      onchange: function (e) { state.status = e.target.value; state.page = 1; loadList(container_ref()); }
    });
    select.appendChild(GD.el('option', { value: '' }, 'All statuses'));
    STATUS_OPTIONS.forEach(function (pair) {
      select.appendChild(GD.el('option', { value: pair[0] }, pair[1]));
    });
    select.value = state.status;
    return select;
  }

  function makeServiceSelect(items) {
    var select = GD.el('select', {
      class: 'select',
      'aria-label': 'Filter by service',
      onchange: function (e) { state.service = e.target.value; state.page = 1; loadList(container_ref()); }
    });
    select.appendChild(GD.el('option', { value: '' }, 'All services'));
    items.forEach(function (item) {
      select.appendChild(GD.el('option', { value: item.slug },
        (item.category ? item.category + ' — ' : '') + item.name));
    });
    select.value = state.service;

    if (items.length === 0) {
      select.hidden = true;
    }
    return select;
  }

  function renderToolbar(container, items) {
    var searchInput = GD.el('input', {
      class: 'input search-grow',
      type: 'search',
      placeholder: 'Search by reference, name, email, phone, company or service…',
      'aria-label': 'Search requests',
      value: state.search,
      oninput: function (e) {
        state.search = e.target.value;
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () { state.page = 1; loadList(container); }, 350);
      },
      onkeydown: function (e) {
        if (e.key === 'Enter') { clearTimeout(debounceTimer); state.page = 1; loadList(container); }
      }
    });

    var refresh = GD.el('button', {
      class: 'btn',
      type: 'button',
      'aria-label': 'Refresh requests',
      title: 'Refresh',
      onclick: function () { loadList(container, true); }
    }, '⟳');

    container.appendChild(GD.el('div', { class: 'toolbar' },
      [searchInput, makeStatusSelect(), makeServiceSelect(items), refresh]));
  }

  function query() {
    var qs = '?page=' + state.page + '&limit=' + state.limit;
    if (state.search) { qs += '&search=' + encodeURIComponent(state.search); }
    if (state.status) { qs += '&status=' + encodeURIComponent(state.status); }
    if (state.service) { qs += '&service=' + encodeURIComponent(state.service); }
    return qs;
  }

  function loadList(container, fresh) {
    if (fresh) { state.page = 1; }
    container.innerHTML = '';
    container.appendChild(GD.renderLoading('Loading requests…'));

    var servicesPromise = state.catalogLoaded ? Promise.resolve(null) : GD.get('/services');
    servicesPromise
      .then(function (serviceRes) {
        var items = [];
        if (serviceRes && serviceRes.body && serviceRes.body.data) {
          (serviceRes.body.data.categories || []).forEach(function (cat) {
            (cat.items || []).forEach(function (item) {
              items.push({ slug: item.slug, name: item.name, category: cat.name });
            });
          });
          state.catalogLoaded = true;
        }
        return items;
      })
      .then(function (items) {
        return GD.get('/admin/requests' + query()).then(function (res) {
          return { items: items, res: res };
        });
      })
      .then(function (combined) {
        container.innerHTML = '';
        renderToolbar(container, combined.items);
        renderTable(container, combined.res.body.data);
        renderPager(container, combined.res.body.data.pagination);
      })
      .catch(function (err) {
        container.innerHTML = '';
        container.appendChild(GD.renderError(err.message, function () { loadList(container); }));
      });
  }

  /* The filter selects need the container on change; resolve lazily. */
  var container_ref = function () { return currentContainer; };
  var currentContainer = null;

  GD.views.requests = function (container) {
    currentContainer = container;
    loadList(container);
  };
})(window.GDApp);