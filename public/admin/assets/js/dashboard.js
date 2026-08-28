/* ============================================================
   G DESIGN — Dashboard overview view
   Stat cards + recent requests. Clicking a request opens details.
   ============================================================ */
(function (GD) {
  'use strict';

  function statCard(key, label, value) {
    var icon = key === 'total_requests' ? 'Σ' : '●';
    return GD.el('div', { class: 'stat-card s-' + key },
      [
        GD.el('span', { class: 'k' }, [GD.el('span', { 'aria-hidden': 'true' }, icon), GD.esc(label)]),
        GD.el('span', { class: 'v' }, String(value))
      ]);
  }

  function recentRow(req) {
    var cell = function (children) { return GD.el('td', {}, children); };
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
      cell(GD.el('span', {}, GD.formatDate(req.created_at)))
    ]);
  }

  var renderRecent = function (container, requests) {
    if (!requests.length) {
      container.appendChild(GD.renderEmpty('No requests yet',
        'Service requests submitted through the website will appear here.'));
      return;
    }

    var table = GD.el('table', { class: 'list' },
      [
        GD.el('thead', {}, GD.el('tr', {},
          ['Reference', 'Client', 'Service', 'Status', 'Files', 'Submitted'].map(function (h) {
            return GD.el('th', { scope: 'col' }, h);
          }))),
        GD.el('tbody', {}, requests.map(recentRow))
      ]);

    container.appendChild(GD.el('div', { class: 'table-wrap' }, table));
  };

  function renderOverview(container, data) {
    var stats = data.statistics || {};
    container.appendChild(GD.el('div', { class: 'stat-grid' },
      [
        statCard('total_requests', 'Total Requests', stats.total_requests),
        statCard('pending', 'Pending', stats.pending),
        statCard('reviewing', 'Reviewing', stats.reviewing),
        statCard('in_progress', 'In Progress', stats.in_progress),
        statCard('completed', 'Completed', stats.completed),
        statCard('cancelled', 'Cancelled', stats.cancelled)
      ]));

    var card = GD.el('div', { class: 'card' },
      GD.el('div', { class: 'card-head' },
        [GD.el('h2', { text: 'Recent Requests' }),
         GD.el('a', { class: 'btn btn-sm', href: '#/requests' }, 'View all')]));
    renderRecent(card, data.recent_requests || []);
    container.appendChild(card);
  }

  function load(container) {
    container.appendChild(GD.renderLoading('Loading dashboard…'));
    GD.get('/admin/dashboard/overview')
      .then(function (res) {
        container.innerHTML = '';
        renderOverview(container, res.body.data);
      })
      .catch(function (err) {
        container.innerHTML = '';
        container.appendChild(GD.renderError(err.message, function () { load(container); }));
      });
  }

  GD.views.overview = load;
})(window.GDApp);