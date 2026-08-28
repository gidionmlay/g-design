/* ============================================================
   G DESIGN — Service Catalog CMS views (M5)
   Categories list, Services list, and the Service editor with
   pricing + dynamic requirements builder. All async operations
   surface loading / success / error feedback per M5 UX rules.
   ============================================================ */
(function (GD) {
  'use strict';

  var PRICING_TYPES = [
    ['fixed', 'Fixed'],
    ['starting_from', 'Starting From'],
    ['range', 'Range'],
    ['quote', 'Request a Quote']
  ];

  var FIELD_TYPES = [
    ['text', 'Text'],
    ['textarea', 'Textarea'],
    ['email', 'Email'],
    ['tel', 'Phone'],
    ['number', 'Number'],
    ['date', 'Date'],
    ['select', 'Select'],
    ['radio', 'Radio'],
    ['checkbox', 'Checkbox']
  ];

  /* ---------- Pricing formatters ---------- */

  function formatCurrency(amount, currency) {
    if (amount == null) { return null; }
    var n = Number(amount);
    return new Intl.NumberFormat('en-TZ', { style: 'currency', currency: currency || 'TZS', maximumFractionDigits: 0 }).format(n);
  }

  function pricingLabel(pricing) {
    if (!pricing) { return '—'; }
    var c = pricing.currency || 'TZS';
    if (pricing.type === 'fixed') {
      return pricing.value != null ? formatCurrency(pricing.value, c) : 'Fixed';
    }
    if (pricing.type === 'starting_from') {
      return pricing.value != null ? 'From ' + formatCurrency(pricing.value, c) : 'From —';
    }
    if (pricing.type === 'range') {
      return pricing.min != null && pricing.max != null
        ? formatCurrency(pricing.min, c) + ' – ' + formatCurrency(pricing.max, c)
        : 'Range';
    }
    return 'On Request';
  }

  function activeBadge(isActive) {
    return GD.el('span', { class: 'badge' + (isActive ? ' status-completed' : ' status-cancelled') },
      [GD.el('span', { class: 'dot' }), isActive ? 'Active' : 'Inactive']);
  }

  function imageCell(image) {
    if (!image) {
      return GD.el('div', { class: 'svc-thumb empty-thumb' }, '—');
    }
    var url = typeof image === 'string' ? image : image.url;
    return GD.el('div', { class: 'svc-thumb' }, GD.el('img', { src: url, alt: 'Service image', loading: 'lazy' }));
  }

  /* ============================================================
     CATEGORIES VIEW
     ============================================================ */

  function categoryRow(cat) {
    return GD.el('tr', {}, [
      GD.el('td', {}, GD.esc(cat.name || '—')),
      GD.el('td', {}, GD.esc(cat.slug || '—')),
      GD.el('td', {}, String(cat.service_count || 0) + (cat.service_count === 1 ? ' service' : ' services')),
      GD.el('td', {}, activeBadge(cat.is_active)),
      GD.el('td', {}, String(cat.sort_order)),
      GD.el('td', { class: 'row-actions' }, GD.el('button', {
        class: 'btn btn-sm', type: 'button',
        onclick: function () { openCategoryEditor(cat); }
      }, 'Edit'))
    ]);
  }

  function renderCategoriesTable(container, cats) {
    if (!cats.length) {
      container.appendChild(GD.renderEmpty('No categories yet',
        'Create a category to start organising your services.'));
      return;
    }
    var table = GD.el('table', { class: 'list' },
      [
        GD.el('thead', {}, GD.el('tr', {},
          ['Name', 'Slug', 'Services', 'Status', 'Order', ''].map(function (h) {
            return GD.el('th', { scope: 'col' }, h);
          }))),
        GD.el('tbody', {}, cats.map(categoryRow))
      ]);
    container.appendChild(GD.el('div', { class: 'table-wrap' }, table));
  }

  function loadCategories(container) {
    container.appendChild(GD.renderLoading('Loading categories…'));
    GD.get('/admin/service-categories')
      .then(function (res) {
        container.innerHTML = '';
        var cats = (res.body && res.body.data && res.body.data.categories) || [];
        var head = GD.el('div', { class: 'card-head' },
          [
            GD.el('h2', { text: 'Categories' }),
            GD.el('button', { class: 'btn btn-accent btn-sm', type: 'button', onclick: function () { openCategoryEditor(null); } }, '+ New Category')
          ]);
        var card = GD.el('div', { class: 'card' }, head);
        renderCategoriesTable(card, cats);
        container.appendChild(card);
      })
      .catch(function (err) {
        container.innerHTML = '';
        container.appendChild(GD.renderError(err.message, function () { loadCategories(container); }));
      });
  }

  function openCategoryEditor(cat) {
    var isNew = !cat;
    var fName = GD.el('input', { class: 'input', value: cat ? cat.name : '', placeholder: 'e.g. Branding' });
    var fSlug = GD.el('input', { class: 'input', value: cat ? cat.slug : '', placeholder: 'e.g. branding' });
    var fTag = GD.el('input', { class: 'input', value: cat && cat.tag ? cat.tag : '', placeholder: 'e.g. BRANDING' });
    var fDesc = GD.el('textarea', { class: 'input', rows: 3, placeholder: 'Short description' });
    if (cat && cat.description) { fDesc.value = cat.description; }
    var fOrder = GD.el('input', { class: 'input', type: 'number', min: '0', value: cat ? String(cat.sort_order) : '0' });
    var fActive = GD.el('input', { class: 'input', type: 'checkbox' });
    fActive.checked = cat ? !!cat.is_active : true;

    var field = function (label, input) {
      return GD.el('div', { class: 'field' },
        [GD.el('label', {}, label), input]);
    };

    var msg = GD.el('div', { class: 'form-msg' });
    var save = function () {
      var payload = {
        name: fName.value.trim(),
        slug: fSlug.value.trim().toLowerCase().replace(/[^a-z0-9-]+/g, '-').replace(/^-+|-+$/g, ''),
        tag: fTag.value.trim() || null,
        description: fDesc.value.trim() || null,
        sort_order: parseInt(fOrder.value, 10) || 0,
        is_active: fActive.checked
      };
      if (!payload.name) { msg.textContent = 'Name is required.'; return; }

      var btn = editorSaveBtn;
      btn.disabled = true; btn.textContent = isNew ? 'Creating…' : 'Saving…';

      var request = isNew
        ? GD.post('/admin/service-categories', payload)
        : GD.patch('/admin/service-categories/' + cat.id, payload);

      request
        .then(function () {
          GD.toast(isNew ? 'Category created successfully.' : 'Category updated successfully.');
          document.getElementById('catalog-modal').classList.remove('open');
          GD.views.categories(viewNode());
        })
        .catch(function (err) {
          msg.textContent = err.body && err.body.error && err.body.error.fields
            ? (Object.values(err.body.error.fields)[0] || 'Unable to save category.')
            : (err.message || 'Unable to save category.');
          btn.disabled = false; btn.textContent = isNew ? 'Create' : 'Save';
        });
    };
    var editorSaveBtn = GD.el('button', { class: 'btn btn-accent', type: 'button', onclick: save }, isNew ? 'Create' : 'Save');

    var form = GD.el('div', { class: 'editor-form' },
      [
        GD.el('div', { class: 'form-grid' },
          [field('Name', fName), field('Slug', fSlug)]),
        field('Tag (short label)', fTag),
        field('Description', fDesc),
        GD.el('div', { class: 'form-grid' },
          [field('Display Order', fOrder), field('Active', fActive)]),
        msg,
        GD.el('div', { class: 'editor-actions' },
          [editorSaveBtn,
           GD.el('button', { class: 'btn btn-ghost', type: 'button', onclick: closeModal }, 'Cancel')])
      ]);

    openModal((isNew ? 'New Category' : 'Edit Category'), form);
  }

  /* ============================================================
     SERVICES VIEW (list)
     ============================================================ */

  var svcState = { page: 1, limit: 20, search: '', category_id: '', status: '' };
  var svcDebounce = null;
  var svcViewNode = null;

  function serviceRow(s) {
    return GD.el('tr', {}, [
      GD.el('td', {}, imageCell(s.image)),
      GD.el('td', {},
        [GD.el('div', { class: 'cell-main' }, GD.esc(s.name)),
         GD.el('div', { class: 'cell-sub' }, GD.esc(s.slug || ''))]),
      GD.el('td', {}, GD.esc(s.category || '—')),
      GD.el('td', {}, GD.esc(pricingLabel(s.pricing))),
      GD.el('td', {}, activeBadge(s.is_active)),
      GD.el('td', {}, String(s.sort_order)),
      GD.el('td', { class: 'row-actions' },
        [GD.el('button', { class: 'btn btn-sm', type: 'button', onclick: function () { location.hash = '#/services/' + s.id; } }, 'Edit')])
    ]);
  }

  function servicesTable(container, data) {
    var items = data.items || [];
    if (!items.length) {
      container.appendChild(GD.renderEmpty('No services found',
        'Try adjusting your search or filters, or create a new service.'));
      return;
    }
    var table = GD.el('table', { class: 'list' },
      [
        GD.el('thead', {}, GD.el('tr', {},
          ['Image', 'Service', 'Category', 'Pricing', 'Status', 'Order', ''].map(function (h) {
            return GD.el('th', { scope: 'col' }, h);
          }))),
        GD.el('tbody', {}, items.map(serviceRow))
      ]);
    container.appendChild(GD.el('div', { class: 'table-wrap' }, table));
  }

  function servicesPager(container, pag) {
    var pages = Math.max(1, pag.pages);
    var prev = GD.el('button', { class: 'btn btn-sm', type: 'button', disabled: pag.page <= 1,
      onclick: function () { svcState.page = pag.page - 1; loadServices(container); } }, '← Prev');
    var next = GD.el('button', { class: 'btn btn-sm', type: 'button', disabled: pag.page >= pages,
      onclick: function () { svcState.page = pag.page + 1; loadServices(container); } }, 'Next →');
    container.appendChild(GD.el('div', { class: 'pager' },
      [prev, GD.el('span', {}, 'Page ' + pag.page + ' of ' + pages + ' · ' + pag.total + ' service' + (pag.total === 1 ? '' : 's')),
       GD.el('span', { class: 'spacer' }), next]));
  }

  function loadServices(container, fresh) {
    if (fresh) { svcState.page = 1; }
    container.appendChild(GD.renderLoading('Loading services…'));

    var categoriesPromise = GD.get('/admin/service-categories');
    var listPromise = GD.get('/admin/services' + svcQuery()).catch(function () { return null; });

    Promise.all([categoriesPromise, listPromise])
      .then(function (results) {
        var cats = (results[0].body && results[0].body.data && results[0].body.data.categories) || [];
        var data = (results[1] && results[1].body && results[1].body.data) || { items: [], pagination: { page: 1, total: 0, pages: 1 } };

        container.innerHTML = '';
        renderServicesToolbar(container, cats);

        var card = GD.el('div', { class: 'card' },
          GD.el('div', { class: 'card-head' },
            [GD.el('h2', { text: 'Services' }),
             GD.el('button', { class: 'btn btn-accent btn-sm', type: 'button', onclick: function () { location.hash = '#/services/new'; } }, '+ New Service')]));
        servicesTable(card, data);
        servicesPager(card, data.pagination);
        container.appendChild(card);
      })
      .catch(function (err) {
        container.innerHTML = '';
        container.appendChild(GD.renderError(err.message, function () { loadServices(container); }));
      });
  }

  function svcQuery() {
    var q = '?page=' + svcState.page + '&limit=' + svcState.limit;
    if (svcState.search) { q += '&search=' + encodeURIComponent(svcState.search); }
    if (svcState.category_id) { q += '&category_id=' + svcState.category_id; }
    if (svcState.status) { q += '&status=' + encodeURIComponent(svcState.status); }
    return q;
  }

  function renderServicesToolbar(container, cats) {
    var search = GD.el('input', { class: 'input search-grow', type: 'search', placeholder: 'Search services…', value: svcState.search,
      oninput: function (e) {
        svcState.search = e.target.value;
        clearTimeout(svcDebounce);
        svcDebounce = setTimeout(function () { svcState.page = 1; loadServices(svcViewNode); }, 350);
      } });

    var catSelect = GD.el('select', { class: 'select', 'aria-label': 'Filter by category', onchange: function (e) { svcState.category_id = e.target.value; svcState.page = 1; loadServices(svcViewNode); } });
    catSelect.appendChild(GD.el('option', { value: '' }, 'All categories'));
    cats.forEach(function (c) { catSelect.appendChild(GD.el('option', { value: String(c.id) }, GD.esc(c.name))); });
    catSelect.value = svcState.category_id;

    var statusSelect = GD.el('select', { class: 'select', 'aria-label': 'Filter by status', onchange: function (e) { svcState.status = e.target.value; svcState.page = 1; loadServices(svcViewNode); } });
    statusSelect.appendChild(GD.el('option', { value: '' }, 'All statuses'));
    statusSelect.appendChild(GD.el('option', { value: 'active' }, 'Active'));
    statusSelect.appendChild(GD.el('option', { value: 'inactive' }, 'Inactive'));
    statusSelect.value = svcState.status;

    container.appendChild(GD.el('div', { class: 'toolbar' },
      [search, catSelect, statusSelect,
       GD.el('button', { class: 'btn', type: 'button', title: 'Refresh', onclick: function () { loadServices(svcViewNode, true); } }, '⟳')]));
  }

  GD.views.services = function (container) {
    svcViewNode = container;
    loadServices(container);
  };

  /* ============================================================
     SERVICE EDITOR
     ============================================================ */

  var MODAL_FIELD_TYPES = ['select', 'radio', 'checkbox'];

  function newServiceRecord() {
    return {
      id: null,
      category_id: '',
      name: '',
      slug: '',
      short_description: '',
      description: '',
      sort_order: 0,
      is_active: true,
      pricing: { type: 'quote', currency: 'TZS', value: null, min: null, max: null },
      fields: []
    };
  }

  GD.views.serviceEditor = function (container, param) {
    container.innerHTML = '';
    container.appendChild(GD.renderLoading('Loading service…'));

    var isNew = !param || param.id === 'new' || param.mode === 'new';
    var lists = [GD.get('/admin/service-categories')];
    if (!isNew) { lists.push(GD.get('/admin/services/' + param.id)); }
    if (isNew) { lists.push(Promise.resolve({ body: { data: { fields: [] } } })); }

    Promise.all(lists)
      .then(function (results) {
        var cats = (results[0].body && results[0].body.data && results[0].body.data.categories) || [];
        var svc = isNew ? newServiceRecord() : results[1].body.data.service;
        var fields = isNew ? [] : (results[1].body.data.fields || []);
        if (svc && svc.pricing && !svc.pricing.type) { svc.pricing.type = 'quote'; }
        container.innerHTML = '';
        renderServiceEditor(container, { isNew: isNew, cats: cats, svc: svc, fields: fields });
      })
      .catch(function (err) {
        container.innerHTML = '';
        container.appendChild(GD.renderError(err.message, function () { GD.views.serviceEditor(container, param); }));
      });
  };

  function renderServiceEditor(container, ctx) {
    var svc = ctx.svc || newServiceRecord();
    var fName = GD.el('input', { class: 'input', value: svc.name, placeholder: 'Service name' });
    var fSlug = GD.el('input', { class: 'input', value: svc.slug, placeholder: 'service-slug' });
    var fShort = GD.el('input', { class: 'input', value: svc.short_description || '', placeholder: 'One-line summary' });
    var fDesc = GD.el('textarea', { class: 'input', rows: 5, placeholder: 'Full description' });
    if (svc.description) { fDesc.value = svc.description; }
    var fOrder = GD.el('input', { class: 'input', type: 'number', min: '0', value: String(svc.sort_order || 0) });
    var fActive = GD.el('input', { class: 'input', type: 'checkbox' });
    fActive.checked = !!svc.is_active;

    var catSelect = GD.el('select', { class: 'select' });
    ctx.cats.forEach(function (c) {
      catSelect.appendChild(GD.el('option', { value: String(c.id) }, GD.esc(c.name)));
    });
    catSelect.value = svc.category_id ? String(svc.category_id) : (ctx.cats[0] ? String(ctx.cats[0].id) : '');

    var pricing = svc.pricing || { type: 'quote', currency: 'TZS' };
    var pType = GD.el('select', { class: 'select', onchange: function () { updatePricingFields(); } });
    pType.appendChild(GD.el('option', { value: 'quote' }, 'Request a Quote'));
    PRICING_TYPES.forEach(function (pair) {
      if (pair[0] !== 'quote') { pType.appendChild(GD.el('option', { value: pair[0] }, pair[1])); }
    });
    pType.value = pricing.type || 'quote';

    var valueInput = GD.el('input', { class: 'input', type: 'number', min: '0', step: 'any', value: pricing.value != null ? String(pricing.value) : '' });
    var minInput = GD.el('input', { class: 'input', type: 'number', min: '0', step: 'any', value: pricing.min != null ? String(pricing.min) : '' });
    var maxInput = GD.el('input', { class: 'input', type: 'number', min: '0', step: 'any', value: pricing.max != null ? String(pricing.max) : '' });
    var currInput = GD.el('input', { class: 'input', value: pricing.currency || 'TZS', maxlength: '3' });
    var field = function (label, input, hint) {
      return GD.el('div', { class: 'field' }, [GD.el('label', {}, label), input].concat(hint ? [GD.el('small', { class: 'field-hint' }, hint)] : []));
    };

    // Pricing dynamic region
    var valueWrap = GD.el('div', { class: 'field', style: 'display:none' },
      [GD.el('label', {}, pricing.type === 'starting_from' ? 'Starting From' : 'Price'), valueInput]);
    var rangeWrap = GD.el('div', { class: 'form-grid', style: 'display:none' },
      [field('Min Price', minInput), field('Max Price', maxInput)]);

    function updatePricingFields() {
      var t = pType.value;
      valueWrap.style.display = (t === 'fixed' || t === 'starting_from') ? '' : 'none';
      valueWrap.querySelector('label').textContent = t === 'starting_from' ? 'Starting From' : 'Price';
      rangeWrap.style.display = (t === 'range') ? '' : 'none';
    }

    var priceSection = GD.el('div', { class: 'editor-section' },
      [
        GD.el('h3', { text: 'Pricing' }),
        field('Pricing Type', pType),
        valueWrap,
        rangeWrap,
        field('Currency', currInput, '3-letter code (default TZS)')
      ]);

    // ---- Dynamic requirements builder ----
    var fieldRows = (ctx.fields || []).map(function (f) { return rebuildFieldRow(f); });

    function rebuildFieldRow(f) {
      var fRow = { data: f };

      var fLabel = GD.el('input', { class: 'input', value: f.label, placeholder: 'Label' });
      var fKey = GD.el('input', { class: 'input', value: f.key, placeholder: 'field_key', disabled: !!f.id });
      var fType = GD.el('select', { class: 'select' });
      FIELD_TYPES.forEach(function (pair) { fType.appendChild(GD.el('option', { value: pair[0] }, pair[1])); });
      fType.value = f.type || 'text';
      var fReq = GD.el('input', { class: 'input', type: 'checkbox' });
      fReq.checked = !!f.required;
      var fOrder = GD.el('input', { class: 'input', type: 'number', min: '0', value: String(f.sort_order || 0) });
      var fOptions = GD.el('div', { class: 'field-options' });

      function renderOptions() {
        fOptions.innerHTML = '';
        (fRow.data.options || []).forEach(function (opt) {
          var oInput = GD.el('input', { class: 'input opt-input', value: opt, placeholder: 'Option' });
          oInput.oninput = function () { fRow.data.options[fRow.data.options.indexOf(opt)] = oInput.value; syncOptionsToData(); };
          var rm = GD.el('button', { class: 'btn btn-ghost btn-sm', type: 'button', onclick: function () { fRow.data.options = fRow.data.options.filter(function (x) { return x !== opt; }); renderOptions(); } }, '✕');
          fOptions.appendChild(GD.el('div', { class: 'opt-row' }, [oInput, rm]));
        });
        syncOptionsToData();
      }
      function syncOptionsToData() {
        if (!Array.isArray(fRow.data.options)) { fRow.data.options = []; }
      }
      var addOpt = GD.el('button', { class: 'btn btn-sm', type: 'button', onclick: function () { fRow.data.options = (fRow.data.options || []).concat(['']); renderOptions(); } }, '+ Option');

      function isOptionType() { return MODAL_FIELD_TYPES.indexOf(fType.value) !== -1; }
      fType.onchange = function () {
        var opt = isOptionType();
        fOptions.style.display = opt ? '' : 'none';
        addOpt.style.display = opt ? '' : 'none';
      };
      renderOptions();
      fType.onchange();

      fRow.node = GD.el('div', { class: 'field-row' },
        [
          GD.el('div', { class: 'form-grid' }, [field('Label', fLabel), field('Field Key', fKey)]),
          GD.el('div', { class: 'form-grid' },
            [field('Type', fType),
             GD.el('div', { class: 'field inline-field' }, [GD.el('label', {}, 'Required'), fReq]),
             field('Display Order', fOrder)]),
          GD.el('div', { class: 'field' }, [GD.el('label', {}, 'Options'), fOptions, addOpt])
        ]);
      fRow.label = fLabel; fRow.key = fKey; fRow.type = fType; fRow.req = fReq; fRow.order = fOrder;
      return fRow;
    }

    // Add requirement button + container
    var fieldList = GD.el('div', { class: 'field-list' });
    fieldRows.forEach(function (r) { fieldList.appendChild(r.node); });
    var addFieldBtn = GD.el('button', { class: 'btn', type: 'button', onclick: addField }, '+ Add Requirement');

    function addField() {
      var f = { label: '', key: '', type: 'text', required: false, sort_order: fieldRows.length, options: [] };
      var row = rebuildFieldRow(f);
      fieldRows.push(row);
      fieldList.appendChild(row.node);
    }

    var fieldsSection = GD.el('div', { class: 'editor-section' },
      [GD.el('h3', { text: 'Client Requirements' }), fieldList, addFieldBtn,
       GD.el('p', { class: 'field-hint' }, 'Field keys are permanent once saved and protect historical request data.')]);

    var msg = GD.el('div', { class: 'form-msg' });

    var saveBtn = GD.el('button', { class: 'btn btn-accent', type: 'button', onclick: save }, ctx.isNew ? 'Create Service' : 'Save Changes');

    function save() {
      var payload = {
        category_id: parseInt(catSelect.value, 10) || null,
        name: fName.value.trim(),
        slug: fSlug.value.trim().toLowerCase().replace(/[^a-z0-9-]+/g, '-').replace(/^-+|-+$/g, ''),
        short_description: fShort.value.trim() || null,
        description: fDesc.value.trim() || null,
        sort_order: parseInt(fOrder.value, 10) || 0,
        is_active: fActive.checked,
        pricing: {
          type: pType.value,
          currency: (currInput.value || 'TZS').toUpperCase(),
          value: valueInput.value === '' ? null : parseFloat(valueInput.value),
          min: minInput.value === '' ? null : parseFloat(minInput.value),
          max: maxInput.value === '' ? null : parseFloat(maxInput.value)
        }
      };

      if (!payload.name) { msg.textContent = 'Service name is required.'; return; }
      if (!payload.category_id) { msg.textContent = 'Please select a category.'; return; }

      saveBtn.disabled = true;
      saveBtn.textContent = 'Saving…';

      var serviceRequest = ctx.isNew
        ? GD.post('/admin/services', payload)
        : GD.patch('/admin/services/' + svc.id, payload);

      serviceRequest
        .then(function (res) {
          var savedId = ctx.isNew ? res.body.data.service.id : svc.id;
          // Save fields for existing services
          var fieldOps = ctx.isNew
            ? Promise.resolve()
            : saveFields(savedId, fieldRows);
          return fieldOps.then(function () { return savedId; });
        })
        .then(function (savedId) {
          GD.toast(ctx.isNew ? 'Service created successfully.' : 'Service updated successfully.');
          location.hash = '#/services/' + savedId;
        })
        .catch(function (err) {
          if (err.body && err.body.error && err.body.error.fields) {
            msg.textContent = Object.values(err.body.error.fields)[0];
          } else {
            msg.textContent = err.message || 'Unable to save service.';
          }
          saveBtn.disabled = false;
          saveBtn.textContent = ctx.isNew ? 'Create Service' : 'Save Changes';
        });
    }

    function saveFields(serviceId, rows) {
      // For a saved service, fields are persisted via the field endpoints.
      // New fields (no id) are created; existing are updated.
      var ops = [];
      rows.forEach(function (row, index) {
        var data = {
          label: row.label.value.trim(),
          type: row.type.value,
          required: !!row.req.checked,
          sort_order: parseInt(row.order.value, 10) || index
        };
        if (MODAL_FIELD_TYPES.indexOf(row.type.value) !== -1) {
          var opts = (row.data.options || []).map(function (o) { return String(o || '').trim(); }).filter(Boolean);
          data.options = opts;
        }
        if (row.data.id) {
          ops.push(GD.patch('/admin/services/' + serviceId + '/fields/' + row.data.id, data));
        } else {
          var key = row.key.value.trim().toLowerCase().replace(/[^a-z0-9_]+/g, '_').replace(/^_+|_+$/g, '');
          data.field_key = key;
          if (key) { ops.push(GD.post('/admin/services/' + serviceId + '/fields', data)); }
        }
      });
      return ops.length ? Promise.all(ops) : Promise.resolve();
    }

    // Assemble the page
    var basicSection = GD.el('div', { class: 'editor-section' },
      [
        GD.el('h3', { text: 'Basic Information' }),
        GD.el('div', { class: 'form-grid' }, [field('Name', fName), field('Slug', fSlug)]),
        field('Category', catSelect),
        field('Short Description', fShort),
        field('Full Description', fDesc),
        GD.el('div', { class: 'form-grid' },
          [field('Display Order', fOrder), GD.el('div', { class: 'field inline-field' }, [GD.el('label', {}, 'Active'), fActive])])
      ]);

    var buttons = GD.el('div', { class: 'editor-actions' },
      [saveBtn,
       GD.el('button', { class: 'btn btn-ghost', type: 'button', onclick: function () { location.hash = '#/services'; } }, 'Back to Services')]);

    var form = GD.el('form', { class: 'editor', novalidate: true, onsubmit: function (e) { e.preventDefault(); save(); } },
      [basicSection, priceSection, fieldsSection, msg, buttons]);

    container.appendChild(form);
    updatePricingFields();
  }

  /* ============================================================
     MODAL + util helpers
     ============================================================ */

  var viewNode = function () { return document.getElementById('view'); };

  function openModal(title, body) {
    var modal = document.getElementById('catalog-modal');
    document.getElementById('catalog-modal-title').textContent = title;
    var content = document.getElementById('catalog-modal-body');
    content.innerHTML = '';
    content.appendChild(body);
    modal.classList.add('open');
  }

  function closeModal() {
    var modal = document.getElementById('catalog-modal');
    if (modal) { modal.classList.remove('open'); }
  }

  GD.views.categories = function (container) {
    loadCategories(container);
  };

  // Expose formatters for reuse elsewhere
  GD.formatCurrency = formatCurrency;
  GD.pricingLabel = pricingLabel;
})(window.GDApp);
