/* ============================================================
   G DESIGN — Request details view
   Client/service info, dynamic requirements, attachments with
   preview/download, and controlled status updates.
   ============================================================ */
(function (GD) {
  'use strict';

  var IMAGE_MIMES = {
    'image/png': true, 'image/jpeg': true, 'image/gif': true,
    'image/webp': true, 'image/svg+xml': true, 'image/avif': true,
    'image/bmp': true, 'image/x-icon': true
  };

  function isImage(mime) { return Boolean(IMAGE_MIMES[String(mime || '').toLowerCase()]); }

  function requireNumber(id) {
    return /^\d{1,10}$/.test(String(id));
  }

  /* Render a requirement value (may be a plain string or an array). */
  function presentValue(value) {
    if (value == null) { return '—'; }
    if (Array.isArray(value)) {
      if (!value.length) { return '—'; }
      if (typeof value[0] === 'object' && value[0] !== null) {
        return value.map(function (row) {
          return (row.size || '?') + ' × ' + String(row.quantity != null ? row.quantity : '?');
        }).join(', ');
      }
      return value.join(', ');
    }
    return String(value);
  }

  function kvRow(label, value) {
    var ddText = (value == null || value === '') ? '—' : presentValue(value);
    return GD.el('div', {}, [GD.el('dt', {}, label), GD.el('dd', {}, ddText)]);
  }

  function buildDetails(container, req) {
    container.appendChild(GD.el('div', { class: 'details-head' },
      [
        GD.el('button', {
          class: 'btn btn-sm',
          type: 'button',
          onclick: function () { location.hash = '#/requests'; }
        }, '← All requests'),
        GD.el('span', { class: 'ref' }, req.reference),
        GD.statusBadge(req.status),
        GD.el('span', {}, 'Submitted ' + GD.formatDate(req.created_at))
      ]));

    var grid = GD.el('div', { class: 'details-grid' });

    /* Client card */
    var clientCard = GD.el('div', { class: 'card' },
      GD.el('div', { class: 'card-head' }, GD.el('h2', { text: 'Client' })));
    var dl = GD.el('dl', { class: 'dl' });
    dl.appendChild(kvRow('Name', req.client && req.client.name));
    dl.appendChild(kvRow('Company', req.client && req.client.company));
    dl.appendChild(kvRow('Email', req.client && req.client.email));
    dl.appendChild(kvRow('Phone', req.client && req.client.phone));
    clientCard.appendChild(dl);
    grid.appendChild(clientCard);

    /* Request / service card */
    var reqCard = GD.el('div', { class: 'card' },
      GD.el('div', { class: 'card-head' }, GD.el('h2', { text: 'Service Request' })));
    var dl2 = GD.el('dl', { class: 'dl' });
    dl2.appendChild(kvRow('Service', req.service && req.service.name));
    dl2.appendChild(kvRow('Category', req.service && req.service.category));
    if (req.description) {
      dl2.appendChild(GD.el('div', { class: 'req-row' },
        [GD.el('dt', {}, 'Details'),
         GD.el('dd', { class: 'req-message' }, req.description)]));
    }
    reqCard.appendChild(dl2);
    grid.appendChild(reqCard);

    /* Dynamic requirements card */
    var reqs = req.requirements || [];
    if (reqs.length) {
      var reqsCard = GD.el('div', { class: 'card' },
        GD.el('div', { class: 'card-head' }, GD.el('h2', { text: 'Requirements' })));
      var dl3 = GD.el('dl', { class: 'dl' });
      reqs.forEach(function (r) {
        dl3.appendChild(kvRow(r.label || r.key, r.value));
      });
      reqsCard.appendChild(dl3);
      grid.appendChild(reqsCard);
    }

    /* Status control card */
    var statusCard = GD.el('div', { class: 'card' },
      GD.el('div', { class: 'card-head' }, GD.el('h2', { text: 'Update Status' })));
    statusCard.appendChild(buildStatusControl(req));
    grid.appendChild(statusCard);

    container.appendChild(grid);

    /* Attachments card */
    container.appendChild(buildAttachmentsCard(req));
  }

  function buildStatusControl(req) {
    var select = GD.el('select', { class: 'select', id: 'status-select', 'aria-label': 'New status' });
    ['pending', 'reviewing', 'in_progress', 'completed', 'cancelled'].forEach(function (s) {
      select.appendChild(GD.el('option', { value: s }, GD.statusMeta(s).label));
    });
    select.value = req.status || 'pending';

    var msg = GD.el('div', { class: 'status-msg', text: 'Status follows a set workflow: pending → reviewing → in progress → completed.' });

    var updateBtn = GD.el('button', {
      class: 'btn btn-accent',
      type: 'button',
      disabled: true,
      onclick: function () {
        var next = select.value;
        if (next === (req.status || 'pending')) { return; }
        if (next === 'cancelled' && !window.confirm('Cancel request ' + req.reference + '? This cannot be undone.')) {
          select.value = req.status;
          return;
        }
        applyStatus(req.id, next, select, updateBtn, msg);
      }
    }, 'Save Status');

    select.addEventListener('change', function () {
      updateBtn.disabled = select.value === (req.status || 'pending');
    });

    return GD.el('div', {},
      [
        GD.el('div', { class: 'status-control' },
          [GD.el('div', { class: 'field' },
            [GD.el('label', { 'for': 'status-select', text: 'Current: ' + GD.statusMeta(req.status || 'pending').label }),
             select]),
           updateBtn]),
        msg
      ]);
  }

  function applyStatus(id, status, select, btn, msg) {
    var label = GD.statusMeta(status).label;
    btn.disabled = true;
    btn.textContent = 'Saving…';
    msg.className = 'status-msg';
    msg.textContent = '';

    GD.patch('/admin/requests/' + id + '/status', { status: status })
      .then(function () {
        msg.className = 'status-msg ok';
        msg.textContent = 'Status updated to ' + label + '.';
        btn.textContent = 'Save Status';
        reloadView(id);
      })
      .catch(function (err) {
        btn.textContent = 'Save Status';
        btn.disabled = false;
        msg.className = 'status-msg err';
        msg.textContent = err.message;
      });
  }

  function reloadView(id) {
    var node = document.getElementById('view');
    if (node) { GD.views.requestDetails(node, String(id)); }
  }

  function buildAttachmentsCard(req) {
    var card = GD.el('div', { class: 'card' },
      GD.el('div', { class: 'card-head' },
        [GD.el('h2', { text: 'Attachments (' + String((req.attachments || []).length) + ')' }),
         GD.el('span', { class: 'count' }, 'Only the request owner can access these files')]));

    var atts = req.attachments || [];
    if (!atts.length) {
      card.appendChild(GD.renderEmpty('No attachments', 'This request was submitted without files.'));
      return card;
    }

    var grid = GD.el('div', { class: 'attach-list' });
    atts.forEach(function (a) {
      grid.appendChild(attachCard(req, a));
    });
    card.appendChild(grid);
    return card;
  }

  function attachCard(req, att) {
    var baseUrl = att.url;
    var dlUrl = baseUrl + '?download=1';
    var image = att.is_image || isImage(att.mime_type);

    var thumb;
    if (image) {
      thumb = GD.el('div', {
        class: 'thumb',
        role: 'button',
        tabindex: '0',
        'aria-label': 'Preview ' + att.filename,
        onclick: function () { GD.openPreview(baseUrl, att.filename); },
        onkeydown: function (e) {
          if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); GD.openPreview(baseUrl, att.filename); }
        }
      }, GD.el('img', { src: baseUrl, alt: '', loading: 'lazy' }));
    } else {
      thumb = GD.el('div', { class: 'thumb' },
        GD.el('div', { class: 'doc' },
          ['📄', GD.el('span', { class: 'doc-type' }, extLabel(att.mime_type))]));
    }

    var actions = GD.el('div', { class: 'actions' },
      [
        image
          ? GD.el('button', { class: 'btn btn-sm', type: 'button',
              onclick: function () { GD.openPreview(baseUrl, att.filename); } }, 'Preview')
          : null,
        GD.el('a', { class: 'btn btn-sm', href: dlUrl }, 'Download')
      ]);

    return GD.el('div', { class: 'attach' },
      [
        thumb,
        GD.el('div', { class: 'meta' },
          [GD.el('strong', {}, att.filename),
           GD.el('div', {}, att.mime_type),
           GD.el('div', {}, GD.formatSize(att.size))]),
        actions
      ]);
  }

  function extLabel(mime) {
    var m = String(mime || '').toLowerCase().split('/');
    return (m[1] || 'file').replace('x-', '').replace('ms-', '');
  }

  function load(container, id) {
    if (!requireNumber(id)) {
      container.appendChild(GD.renderError('That request number looks invalid.', function () {
        location.hash = '#/requests';
      }));
      return;
    }

    container.innerHTML = '';
    container.appendChild(GD.renderLoading('Loading request…'));

    GD.get('/admin/requests/' + id)
      .then(function (res) {
        container.innerHTML = '';
        if (!res.body || !res.body.data) {
          throw new Error('Unexpected response from the server.');
        }
        buildDetails(container, res.body.data);
      })
      .catch(function (err) {
        container.innerHTML = '';
        container.appendChild(GD.renderError(err.message, function () { load(container, id); }));
      });
  }

  GD.views.requestDetails = function (container, id) { load(container, id); };
})(window.GDApp);