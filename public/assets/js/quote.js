/* G DESIGN — Quotation Wizard (quote.html)
   Frontend-only for now: collects a service-specific request and shows a success
   state. Backend (mailer/storage) will be wired later. */
(function () {
  'use strict';

  /* ============================ CONFIG ============================ */

  function briefField(key, label) {
    return [
      { key: key + '_goal', type: 'textarea', label: 'Project overview / goal', placeholder: 'Tell us about the project', required: true },
      { key: key + '_audience', type: 'textarea', label: 'Target audience', placeholder: 'Who is this for?', required: false },
      { key: key + '_deliverables', type: 'textarea', label: 'Expected deliverables', placeholder: 'e.g. brand guidelines, campaign plan…', required: false },
      { key: key + '_channels', type: 'text', label: 'Channels (if applicable)', placeholder: 'e.g. Instagram, website, print…', required: false }
    ];
  }

  var QUOTE_CONFIG = [
    {
      id: 'branding', name: 'Branding', tag: 'BRANDING',
      image: '../assets/images/service/01.webp',
      desc: 'Identities that are recognizable and consistent across every touchpoint.',
      items: [
        {
          id: 'logo-design', name: 'Logo Design & Logo Animation',
          desc: 'New logo, brand packages and logo animation.',
          fields: [
            { key: 'package', type: 'radio', label: 'Design Package', required: true, options: [
              'New Logo Design & Brand Package', 'New Design & Animation', 'Animation Only'
            ] },
            { key: 'colour_style', type: 'text', label: 'Preferred Colour / Style', placeholder: 'e.g. Modern, minimal, orange accent', required: true },
            { key: 'business', type: 'textarea', label: 'Company / Business Activities', placeholder: 'What does your business do?', required: true },
            { key: 'budget', type: 'radio', label: 'Estimated budget', required: true, options: [
              'TZS 50,000/= to TZS 200,000/=', 'TZS 200,000/= to TZS 500,000/=', 'TZS 500,000/= to TZS 700,000/='
            ] }
          ]
        },
        {
          id: 'brand-identity', name: 'Brand Identity',
          desc: 'Full identity systems and refresh work.',
          fields: [
            { key: 'scope', type: 'radio', label: 'Scope', required: true, options: [
              'New identity', 'Refresh existing', 'Logo + stationery package'
            ] },
            { key: 'brand_name', type: 'text', label: 'Brand / business name', placeholder: 'Your brand name', required: true },
            { key: 'style', type: 'text', label: 'Preferred style direction', placeholder: 'e.g. bold, elegant, playful…', required: false }
          ]
        },
        {
          id: 'brand-guidelines', name: 'Brand Guidelines',
          desc: 'Rules that keep your brand consistent.',
          fields: [
            { key: 'status', type: 'radio', label: 'Status', required: true, options: [
              'Brand already exists', 'Starting fresh'
            ] },
            { key: 'includes', type: 'checkbox', label: 'Include', required: true, options: [
              'Logo usage', 'Colour system', 'Typography', 'Stationery templates'
            ] },
            { key: 'notes', type: 'textarea', label: 'Notes / scope', placeholder: 'Anything specific the guidelines should cover…', required: false }
          ]
        },
        {
          id: 'brand-strategy', name: 'Brand Strategy',
          desc: 'Positioning, messaging and roadmap.',
          fields: briefField('bs', '')
        }
      ]
    },
    {
      id: 'graphic-design', name: 'Graphic Design', tag: 'GRAPHIC DESIGN',
      image: '../assets/images/service/02.webp',
      desc: 'Visual communication for businesses, campaigns and individuals.',
      items: [
        {
          id: 'flyer-brochures', name: 'Flyer & Brochures',
          desc: 'Flyers and brochures for events, promotions and more.',
          fields: [
            { key: 'type', type: 'radio', label: 'Type', required: true, options: ['Flyer', 'Brochures'] },
            { key: 'quantity', type: 'radio', label: 'Quantity', required: true, options: ['Below 100', '100 – 500', 'Above 500'] },
            { key: 'size', type: 'text', label: 'Print size', placeholder: 'e.g. A5, A4, A3', required: true },
            { key: 'service', type: 'radio', label: 'Service type', required: true, options: ['Print only', 'Print & design'] },
            { key: 'sides', type: 'radio', label: 'Sides', required: true, options: ['One side', 'Double side'] },
            { key: 'budget', type: 'radio', label: 'Estimated budget', required: true, options: [
              'Below TZS 50,000/=', 'TZS 50,000/= to TZS 200,000/=', 'TZS 200,000/= to TZS 500,000/=', 'Above TZS 500,000/='
            ] }
          ]
        },
        {
          id: 'poster-design', name: 'Poster Design',
          desc: 'Poster design and graphics animation.',
          fields: [
            { key: 'type', type: 'radio', label: 'Type', required: true, options: ['Poster design', 'Graphics animation'] },
            { key: 'quantity', type: 'radio', label: 'Quantity', required: true, options: ['Below 100', '100 – 500', 'Above 500'] },
            { key: 'size', type: 'text', label: 'Print size', placeholder: 'e.g. A5, A4, A3', required: true },
            { key: 'service', type: 'radio', label: 'Service type', required: true, options: ['Print only', 'Print & design'] },
            { key: 'budget', type: 'radio', label: 'Estimated budget', required: true, options: [
              'Below TZS 50,000/=', 'TZS 50,000/= to TZS 200,000/=', 'TZS 200,000/= to TZS 500,000/=', 'Above TZS 500,000/='
            ] }
          ]
        },
        {
          id: 'brochure', name: 'Brochure Design',
          desc: 'Multi-page print or digital brochures.',
          fields: [
            { key: 'type', type: 'radio', label: 'Brochure type', required: true, options: ['Bi-fold', 'Tri-fold', 'Multi-page'] },
            { key: 'pages', type: 'number', label: 'Number of pages', required: false },
            { key: 'quantity', type: 'radio', label: 'Quantity', required: true, options: ['Below 100', '100 – 500', 'Above 500'] },
            { key: 'size', type: 'text', label: 'Size', placeholder: 'e.g. A4, A5, custom', required: false },
            { key: 'service', type: 'radio', label: 'Service', required: true, options: ['Design only', 'Design & print', 'Print only'] },
            { key: 'budget', type: 'radio', label: 'Estimated budget', required: true, options: [
              'Below TZS 50,000/=', 'TZS 50,000/= to TZS 200,000/=', 'TZS 200,000/= to TZS 500,000/=', 'Above TZS 500,000/='
            ] }
          ]
        },
        {
          id: 'social-media', name: 'Social Media Designs',
          desc: 'Posts, stories and covers for your channels.',
          fields: [
            { key: 'platforms', type: 'checkbox', label: 'Platforms', required: true, options: ['Instagram', 'Facebook', 'LinkedIn', 'X', 'TikTok', 'WhatsApp'] },
            { key: 'formats', type: 'checkbox', label: 'Formats', required: true, options: ['Posts', 'Stories', 'Reels covers', 'Profile & cover'] },
            { key: 'count', type: 'number', label: 'Number of designs', required: true },
            { key: 'style', type: 'radio', label: 'Style', required: true, options: ['Template-based', 'Custom from scratch'] }
          ]
        },
        {
          id: 'business-cards', name: 'Business Cards',
          desc: 'Standard and premium finish cards.',
          fields: [
            { key: 'service', type: 'radio', label: 'Service type', required: true, options: ['Print only', 'Print & design'] },
            { key: 'quantity', type: 'radio', label: 'Quantity', required: true, options: ['100', '200', '300', 'Above 300'] },
            { key: 'material', type: 'radio', label: 'Material type', required: true, options: [
              'Soft touch Gloss matte Lamination Card', 'PVC card'
            ] },
            { key: 'sides', type: 'radio', label: 'Print side', required: true, options: ['One side', 'Both side (front and back)'] },
            { key: 'budget', type: 'radio', label: 'Estimated budget', required: true, options: [
              'Below TZS 50,000/=', 'TZS 50,000/= to TZS 100,000/=', 'TZS 100,000/= to TZS 300,000/=', 'Above TZS 300,000/='
            ] }
          ]
        }
      ]
    },
    {
      id: 'printing', name: 'Printing', tag: 'PRINTING',
      image: '../assets/images/service/03.webp',
      desc: 'From digital designs to physical products — banners, apparel and more.',
      items: [
        {
          id: 'banner', name: 'Banner Printing',
          desc: 'Flex, roll-up, mesh and tear drop banner printing.',
          fields: [
            { key: 'product', type: 'radio', label: 'Banner type', required: true, options: [
              'Flex Banner', 'Roll-up Banner', 'Mesh Banner (wind resistant)', 'Tear Drop / Flying Banner'
            ] },
            { key: 'quantity', type: 'number', label: 'Quantity', placeholder: 'Number of banners', required: true },
            { key: 'width', type: 'number', label: 'Width', placeholder: 'metres / cm', required: false },
            { key: 'height', type: 'number', label: 'Height', placeholder: 'metres / cm', required: false },
            { key: 'service', type: 'radio', label: 'Service needed', required: true, options: [
              'Print only', 'Print & design', 'Printing & installation', 'Print, design & installation'
            ] },
            { key: 'environment', type: 'radio', label: 'Usage environment', required: true, options: ['Outdoor', 'Indoor'] },
            { key: 'budget', type: 'radio', label: 'Estimated budget', required: true, options: [
              'Below TZS 50,000/=', 'TZS 50,000/= to TZS 200,000/=', 'TZS 200,000/= to TZS 500,000/=', 'Above TZS 500,000/='
            ] }
          ]
        },
        {
          id: 'sticker-labels', name: 'Sticker & Labels',
          desc: 'Custom diecut, circle and square stickers in various materials.',
          fields: [
            { key: 'shape', type: 'radio', label: 'Sticker shape', required: true, options: [
              'Custom Diecut (custom contour)', 'Circle / round', 'Square / rectangle'
            ] },
            { key: 'quantity', type: 'number', label: 'Quantity', required: true },
            { key: 'width', type: 'number', label: 'Width (cm)', placeholder: 'e.g. 10', required: false },
            { key: 'height', type: 'number', label: 'Height (cm)', placeholder: 'e.g. 20', required: false },
            { key: 'service', type: 'radio', label: 'Service need', required: true, options: [
              'Print only', 'Print & design', 'Print & installation'
            ] },
            { key: 'material', type: 'radio', label: 'Sticker material', required: true, options: [
              'Waterproof vinyl (white)', 'Transparent / clear vinyl', 'Paper label sticker'
            ] },
            { key: 'budget', type: 'radio', label: 'Estimated budget', required: true, options: [
              'Below TZS 50,000/=', 'TZS 50,000/= to TZS 200,000/=', 'TZS 200,000/= to TZS 500,000/=', 'Above TZS 500,000/='
            ] }
          ]
        },
        {
          id: 'tshirt-cap', name: 'T-Shirt & Kofia Printing',
          desc: 'DTF, embroidery and vinyl on apparel.',
          fields: [
            { key: 'garment', type: 'radio', label: 'Garment type', required: true, options: [
              'Round Neck', 'Polo', 'Sport Jersey', 'Reflective Vest', 'Long Sleeve', 'Pullover', 'Cap (Kofia)'
            ] },
            { key: 'printing', type: 'radio', label: 'Printing type', required: true, options: [
              'DTF Full Colour', 'Embroidery (stitched logo)', 'Vinyl Heat Transfer'
            ] },
            { key: 'colour', type: 'text', label: 'Colour', placeholder: 'e.g. Black, White, Royal Blue', required: true },
            { key: 'size_breakdown', type: 'sizegrid', label: 'Size breakdown', required: true,
              sizes: ['S', 'M', 'L', 'XL', 'XXL'], oneSizeWhen: { key: 'garment', value: 'Cap (Kofia)', label: 'One Size' } },
            { key: 'quantity', type: 'number', label: 'Quantity', required: true },
            { key: 'location', type: 'radio', label: 'Print location', required: true, options: ['Front', 'Back', 'Front & Back'] },
            { key: 'budget', type: 'radio', label: 'Estimated budget', required: true, options: [
              'Below TZS 150,000/=', 'TZS 150,000/= to TZS 500,000/=', 'TZS 500,000/= to TZS 1,000,000/=', 'Above TZS 1,000,000/='
            ] }
          ]
        },
        {
          id: 'mug-bottle', name: 'Mug & Bottle Printing',
          desc: 'Full wrap or logo printing on drinkware.',
          fields: [
            { key: 'product', type: 'radio', label: 'Product type', required: true, options: [
              'Ceramic mug', 'Magic Mug / Color changing Mug', 'Aluminum water bottle', 'Plastic water bottle'
            ] },
            { key: 'printing_method', type: 'radio', label: 'Printing method', required: true, options: ['Sublimation', 'UV Printing'] },
            { key: 'quantity', type: 'number', label: 'Quantity', required: true },
            { key: 'budget', type: 'radio', label: 'Estimated budget', required: true, options: [
              'Below TZS 100,000/=', 'TZS 100,000/= to TZS 500,000/=', 'TZS 500,000/= to TZS 500,000/=', 'Above TZS 500,000/='
            ] }
          ]
        },
        {
          id: 'photo-printing', name: 'Photo Printing',
          desc: 'High quality prints in multiple sizes.',
          fields: [
            { key: 'print_type', type: 'radio', label: 'Print type', required: true, options: [
              'Wooden photoframe (picha mbao)', 'Canvas photo print', 'Photo clock'
            ] },
            { key: 'sizes', type: 'sizegrid', label: 'Sizes & quantities', required: true,
              sizes: ['A5', 'A4', 'A3', 'A2', 'A1'] },
            { key: 'more_explanation', type: 'textarea', label: 'More explanation', placeholder: 'Any special requirements, frame preferences, orientation, etc.', required: false },
            { key: 'budget', type: 'radio', label: 'Estimated budget', required: true, options: [
              'Below TZS 50,000/=', 'TZS 50,000/= to TZS 100,000/=', 'TZS 100,000/= to TZS 300,000/=', 'Above TZS 300,000/='
            ] }
          ]
        }
      ]
    },
    {
      id: 'content-creation', name: 'Content Creation', tag: 'CONTENT CREATION',
      image: '../assets/images/service/04.webp',
      desc: 'Photography, video and motion content that tells your story.',
      items: [
        {
          id: 'photography', name: 'Photography',
          desc: 'Product, event, corporate and portrait shoots.',
          fields: [
            { key: 'type', type: 'radio', label: 'Session type', required: true, options: ['Product', 'Event', 'Corporate', 'Portrait', 'Real estate'] },
            { key: 'duration', type: 'text', label: 'Duration', placeholder: 'e.g. 3 hours, half day', required: true },
            { key: 'location', type: 'text', label: 'Location', placeholder: 'Where is the shoot?', required: true },
            { key: 'edited', type: 'number', label: 'Number of edited photos needed', required: true },
            { key: 'extras', type: 'checkbox', label: 'Extras', required: false, options: ['Prints', 'Video reel'] }
          ]
        },
        {
          id: 'videography', name: 'Videography',
          desc: 'Event, corporate and promo videos.',
          fields: [
            { key: 'type', type: 'radio', label: 'Video type', required: true, options: ['Event', 'Corporate', 'Promo', 'Documentary', 'Interview'] },
            { key: 'duration', type: 'text', label: 'Duration estimate', placeholder: 'e.g. 5 minute video', required: true },
            { key: 'location', type: 'text', label: 'Filming location', required: true },
            { key: 'editing', type: 'radio', label: 'Editing', required: true, options: ['Full edit included', 'Raw footage only'] }
          ]
        },
        {
          id: 'video-editing', name: 'Video Editing',
          desc: 'Edit your footage for any platform.',
          fields: [
            { key: 'footage', type: 'radio', label: 'Source material', required: true, options: ['Footage provided', 'Need filming too'] },
            { key: 'length', type: 'text', label: 'Target length', placeholder: 'e.g. 60 seconds', required: true },
            { key: 'platforms', type: 'checkbox', label: 'Platforms', required: false, options: ['YouTube', 'Instagram', 'TikTok', 'TV'] }
          ]
        },
        {
          id: 'motion-graphics', name: 'Motion Graphics',
          desc: 'Logo animation, explainers and social motion.',
          fields: [
            { key: 'type', type: 'radio', label: 'Type', required: true, options: ['Logo animation', 'Explainer video', 'Intro & outro', 'Social animations'] },
            { key: 'duration', type: 'text', label: 'Duration', placeholder: 'e.g. 15 seconds', required: true },
            { key: 'style', type: 'textarea', label: 'Style reference', placeholder: 'Describe or link to references', required: false }
          ]
        }
      ]
    },
    {
      id: 'creative-strategy', name: 'Creative Strategy', tag: 'CREATIVE STRATEGY',
      image: '../assets/images/service/03.webp',
      desc: 'Determining what to communicate and how.',
      items: [
        { id: 'brand-strategy', name: 'Brand Strategy', desc: 'Positioning, messaging and roadmap.', fields: briefField('cs_brand', '') },
        { id: 'content-strategy', name: 'Content Strategy', desc: 'Content calendars, plans and systems.', fields: briefField('cs_content', '') },
        { id: 'campaign-strategy', name: 'Campaign Strategy', desc: 'Campaign planning from goal to execution.', fields: briefField('cs_campaign', '') },
        { id: 'communication-direction', name: 'Communication Direction', desc: 'How your brand speaks to the world.', fields: briefField('cs_comm', '') }
      ]
    },
    {
      id: 'art-direction', name: 'Art Direction', tag: 'ART DIRECTION',
      image: '../assets/images/service/01.webp',
      desc: 'Consistent, professionally executed visual communication.',
      items: [
        { id: 'campaign-art-direction', name: 'Campaign Art Direction', desc: 'Look and feel for campaigns.', fields: briefField('ad_campaign', '') },
        { id: 'brand-visual-direction', name: 'Brand Visual Direction', desc: 'Visual consistency across outputs.', fields: briefField('ad_brand', '') },
        { id: 'photography-direction', name: 'Photography Direction', desc: 'Direction for photo projects.', fields: briefField('ad_photo', '') },
        { id: 'creative-direction', name: 'Creative Direction', desc: 'Overall creative leadership.', fields: briefField('ad_creative', '') }
      ]
    }
  ];

  var COMMON_FIELDS = [
    { key: 'name', type: 'text', label: 'Your name', placeholder: 'Full name', required: true },
    { key: 'email', type: 'email', label: 'Your e-mail', placeholder: 'you@example.com', required: true },
    { key: 'phone', type: 'tel', label: 'Phone (optional)', placeholder: '+255 …', required: false },
    { key: 'completion_date', type: 'date', label: 'Requested completion date', required: true },
    { key: 'notes', type: 'textarea', label: 'Additional project notes', placeholder: 'Anything else we should know?', required: false },
    { key: 'files', type: 'upload', label: 'Attach artwork / reference files', hint: 'PDF, PNG, JPEG, AI, PSD, ZIP', required: false }
  ];

  /* ============================ STATE ============================ */

  var state = {
    step: 1,
    serviceId: null,
    itemId: null,
    answers: {},
    files: []
  };

  var MAX_FILES = 5;
  var ACCEPT = '.pdf,.png,.jpg,.jpeg,.ai,.psd,.zip';

  var qs = function (name) {
    return new URLSearchParams(window.location.search).get(name);
  };

  var findService = function (id) {
    for (var i = 0; i < QUOTE_CONFIG.length; i++) if (QUOTE_CONFIG[i].id === id) return QUOTE_CONFIG[i];
    return null;
  };
  var findItem = function (svc, id) {
    if (!svc) return null;
    for (var i = 0; i < svc.items.length; i++) if (svc.items[i].id === id) return svc.items[i];
    return null;
  };
  var currentService = function () { return findService(state.serviceId); };
  var currentItem = function () { return findItem(currentService(), state.itemId); };

  /* ============================ HELPERS ============================ */

  var $ = function (sel) { return document.querySelector(sel); };
  var $$ = function (sel) { return Array.prototype.slice.call(document.querySelectorAll(sel)); };
  var esc = function (s) {
    return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  };
  var attrVal = function (s) {
    return String(s == null ? '' : s).replace(/"/g, '&quot;');
  };

  function getAnswer(key) {
    return state.answers[key];
  }
  function setAnswer(key, value) {
    state.answers[key] = value;
  }

  /* conditional visibility: field.showWhen = { key, equals|notIn|value } */
  function isFieldVisible(field) {
    var w = field.showWhen;
    if (!w) return true;
    var val = getAnswer(w.key);
    if (w.equals !== undefined) return val === w.equals;
    if (w.notIn) return w.notIn.indexOf(val) === -1;
    if (w.value !== undefined) return val === w.value;
    return true;
  }

  function optionHTML(field, option) {
    var val = getAnswer(field.key);
    var checked = false;
    if (field.type === 'checkbox') {
      var arr = val || [];
      checked = arr.indexOf(option) !== -1;
    } else {
      checked = val === option;
    }
    var id = 'q-' + field.key + '-' + option.toLowerCase().replace(/[^a-z0-9]+/g, '-');
    return '<label class="q-option">' +
      '<input type="' + field.type + '" name="' + esc(field.key) + '" data-field="' + esc(field.key) + '" value="' + attrVal(option) + '" id="' + esc(id) + '"' + (checked ? ' checked' : '') + '>' +
      '<span>' + esc(option) + '</span></label>';
  }

  function requiredAsterisk(field) {
    return field.required ? '<span class="q-req">*</span>' : '';
  }

  function fieldWrapper(field, inner) {
    var hidden = isFieldVisible(field) ? '' : ' hidden';
    return '<div class="q-field" data-key="' + esc(field.key) + '"' + hidden + '>' + inner + '</div>';
  }

  function renderField(field) {
    var inner = '';
    var label = '<label>' + esc(field.label) + ' ' + requiredAsterisk(field) + '</label>';

    switch (field.type) {
      case 'radio':
      case 'checkbox':
        inner = label + '<div class="q-options">' + (field.options || []).map(function (o) { return optionHTML(field, o); }).join('') + '</div>';
        break;
      case 'text':
      case 'email':
      case 'tel':
        inner = label + '<input type="' + field.type + '" data-field="' + esc(field.key) + '" value="' + esc(getAnswer(field.key)) + '" placeholder="' + esc(field.placeholder || '') + '">';
        break;
      case 'number':
        inner = label + '<input type="number" min="0" data-field="' + esc(field.key) + '" value="' + esc(getAnswer(field.key)) + '" placeholder="' + esc(field.placeholder || '') + '">';
        break;
      case 'date':
        inner = label + '<input type="date" data-field="' + esc(field.key) + '" value="' + esc(getAnswer(field.key)) + '" min="' + new Date().toISOString().split('T')[0] + '">';
        break;
      case 'textarea':
        inner = label + '<textarea data-field="' + esc(field.key) + '" placeholder="' + esc(field.placeholder || '') + '">' + esc(getAnswer(field.key)) + '</textarea>';
        break;
      case 'sizegrid':
        inner = label + '<div class="q-sizegrid" data-sizegrid="' + esc(field.key) + '"></div>';
        break;
      case 'upload':
        inner = '<div class="q-upload">' +
          '<div class="q-upload-drop"><i class="fa-sharp-duotone fa-light fa-paperclip"></i> ' + esc(field.label) +
          ' <span style="opacity:.6">(' + esc(field.hint || '') + ')</span></div>' +
          '<input type="file" multiple accept="' + ACCEPT + '" data-field="' + esc(field.key) + '">' +
          '</div><ul class="q-file-list" id="q-file-list"></ul>';
        break;
    }

    return fieldWrapper(field, inner + '<p class="q-error"></p>');
  }

  function renderSizeGrid(field) {
    var wrap = document.querySelector('.q-field[data-key="' + field.key + '"] .q-sizegrid');
    if (!wrap) return;
    var oneSize = field.oneSizeWhen && getAnswer(field.oneSizeWhen.key) === field.oneSizeWhen.value;
    var sizes = oneSize ? [field.oneSizeWhen.label] : field.sizes;
    var answers = getAnswer(field.key) || {};
    wrap.innerHTML = sizes.map(function (size) {
      var v = answers[size] || '';
      return '<div class="q-size"><label>' + esc(size) + '</label>' +
        '<input type="number" min="0" data-sizegrid="' + esc(field.key) + '" data-size="' + esc(size) + '" value="' + esc(v) + '" placeholder="0"></div>';
    }).join('');
  }

  /* ============================ RENDERING ============================ */

  function renderProgress() {
    var steps = $$('.q-progress li');
    steps.forEach(function (li, i) {
      var n = i + 1;
      li.classList.toggle('active', n === state.step);
      li.classList.toggle('done', n < state.step);
    });
  }

  function renderStep1() {
    var service = currentService();
    var servicesPanel = $('#q-services-panel');
    var itemsPanel = $('#q-items-panel');

    if (service) {
      servicesPanel.hidden = true;
      itemsPanel.hidden = false;
      $('#q-items-back').style.display = 'inline-flex';
      $('#q-items-heading').textContent = service.name + ' services';
      $('#q-items-sub').textContent = service.desc;
      $('#q-items-grid').innerHTML = service.items.map(function (it) {
        return cardHTML(it, 'item', it.id === state.itemId);
      }).join('');
    } else {
      servicesPanel.hidden = false;
      itemsPanel.hidden = true;
      $('#q-services-grid').innerHTML = QUOTE_CONFIG.map(function (svc) {
        return cardHTML(svc, 'service', svc.id === state.serviceId);
      }).join('');
    }
    updateContinue();
  }

  function cardHTML(entity, kind, selected) {
    return '<button type="button" class="q-card' + (selected ? ' selected' : '') + '" data-' + kind + '="' + esc(entity.id) + '">' +
      '<img src="' + esc(entity.image) + '" alt="' + esc(entity.name) + '">' +
      '<div class="q-card-body">' +
      (kind === 'service' ? '<p class="q-tag">' + esc(entity.tag) + '</p>' : '') +
      '<h3>' + esc(entity.name) + '</h3>' +
      '<p>' + esc(entity.desc) + '</p>' +
      '</div></button>';
  }

  function renderStep2() {
    var item = currentItem();
    var heading = $('#q-step-2-heading');
    heading.innerHTML = 'Step 2 — Requirements <span class="q-selected">/ ' + esc(item.name) + '</span>';
    $('#q-fields').innerHTML = item.fields.map(renderField).join('');
    item.fields.forEach(function (field) {
      if (field.type === 'sizegrid') renderSizeGrid(field);
    });
  }

  function renderStep3() {
    $('#q-common').innerHTML = COMMON_FIELDS.map(renderField).join('');
    renderFileList();
  }

  function renderStep4() {
    var item = currentItem();
    var svc = currentService();
    $('#q-review-service').textContent = svc.name + ' — ' + item.name;
    var dl = $('#q-review-list');
    var rows = [];

    rows.push(['Contact', getAnswer('name') || '-', getAnswer('email') || '-', getAnswer('phone') || '-']);

    item.fields.forEach(function (field) {
      if (!isFieldVisible(field)) return;
      var label = field.label;
      var val = getAnswer(field.key);
      if (field.type === 'sizegrid') {
        var entries = Object.keys(val || {});
        if (field.oneSizeWhen && getAnswer(field.oneSizeWhen.key) === field.oneSizeWhen.value) {
          var oneLabel = field.oneSizeWhen.label;
          entries = val && val[oneLabel] ? [oneLabel] : [];
          rows.push([label, val ? val[oneLabel] : '-']);
        } else {
          var parts = [];
          for (var i = 0; i < (field.sizes || []).length; i++) {
            var s = field.sizes[i];
            if (val && val[s]) parts.push(s + ': ' + val[s]);
          }
          rows.push([label, parts.length ? parts.join(', ') : '-']);
        }
      } else if (field.type === 'checkbox') {
        var arr = val || [];
        rows.push([label, arr.length ? arr.join(', ') : '-']);
      } else if (field.type === 'radio' || field.type === 'text' || field.type === 'email' || field.type === 'tel' || field.type === 'number' || field.type === 'date' || field.type === 'textarea') {
        var v = (val === undefined || val === null) ? '' : String(val).trim();
        if (v || field.required) rows.push([label, v || '-']);
      }
    });

    rows.push(['Requested completion date', getAnswer('completion_date') || '-']);
    rows.push(['Additional notes', getAnswer('notes') || '-']);
    rows.push(['Attachments', state.files.length ? state.files.length + ' file(s)' : 'None']);

    dl.innerHTML = rows.map(function (r) {
      var label = r[0];
      var values = r.slice(1).join(' · ');
      return '<div class="q-review-item"><dt>' + esc(label) + '</dt><dd>' + esc(values) + '</dd></div>';
    }).join('');
  }

  /* ============================ VALIDATION ============================ */

  function collectFields(panel) {
    $$('input[type="text"][data-field], input[type="email"][data-field], input[type="tel"][data-field], input[type="number"][data-field], input[type="date"][data-field], textarea[data-field]', panel).forEach(function (el) {
      var key = el.getAttribute('data-field');
      if (el.type === 'number') {
        setAnswer(key, el.value !== '' ? el.value : '');
      } else {
        setAnswer(key, el.value);
      }
    });

    var radios = $$('input[type="radio"]', panel);
    radios.forEach(function (r) {
      var key = r.name;
      if (r.checked) setAnswer(key, r.value);
    });

    $$('input[type="checkbox"]', panel).forEach(function (c) {
      var key = c.name;
      var arr = state.answers[key] || [];
      if (c.checked && arr.indexOf(c.value) === -1) arr.push(c.value);
      if (!c.checked) arr = arr.filter(function (v) { return v !== c.value; });
      setAnswer(key, arr);
    });

    $$('[data-sizegrid][data-size]', panel).forEach(function (el) {
      var key = el.getAttribute('data-sizegrid');
      var size = el.getAttribute('data-size');
      if (key) {
        var obj = getAnswer(key) || {};
        if (el.value && Number(el.value) > 0) obj[size] = el.value;
        else delete obj[size];
        setAnswer(key, obj);
      }
    });
  }

  function validateFields(fields) {
    var ok = true;
    fields.forEach(function (field) {
      var el = document.querySelector('.q-step.active .q-field[data-key="' + field.key + '"]');
      if (!el || el.hidden || !isFieldVisible(field)) return;
      var invalid = false;

      if (field.type === 'radio') {
        invalid = field.required && !getAnswer(field.key);
      } else if (field.type === 'checkbox') {
        invalid = field.required && !(getAnswer(field.key) || []).length;
      } else if (field.type === 'sizegrid') {
        var obj = getAnswer(field.key) || {};
        var hasOne = field.oneSizeWhen && getAnswer(field.oneSizeWhen.key) === field.oneSizeWhen.value;
        if (hasOne) {
          invalid = field.required && !(obj[field.oneSizeWhen.label]);
        } else {
          var filled = (field.sizes || []).filter(function (s) { return obj[s]; }).length;
          invalid = field.required && filled === 0;
        }
      } else if (field.type === 'upload') {
        invalid = field.required && state.files.length === 0;
      } else if (field.type === 'email') {
        var em = getAnswer(field.key);
        invalid = field.required ? (!em || !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(em)) : false;
      } else {
        var v = getAnswer(field.key);
        invalid = field.required && (v === undefined || v === '' || v === null);
      }

      el.classList.toggle('invalid', invalid);
      if (invalid) {
        el.querySelector('.q-error').textContent = 'Please complete this field.';
        ok = false;
      }
    });
    return ok;
  }

  /* ============================ NAVIGATION ============================ */

  function showStep(n, scroll) {
    state.step = n;
    renderProgress();
    updateContinue();
    $$('.q-step').forEach(function (s) { s.classList.remove('active'); });
    $('#q-step-' + n).classList.add('active');
    $('#q-back').style.visibility = n === 1 ? 'hidden' : 'visible';
    $('#q-continue').style.display = n === 4 ? 'none' : '';
    $('#q-submit').style.display = n === 4 ? '' : 'none';
    $('#q-nav').style.display = n === 5 ? 'none' : '';
    if (scroll !== false) scrollToWizard();
  }

  function scrollToWizard() {
    var el = $('#q-wizard');
    if (el) {
      var top = el.getBoundingClientRect().top + window.pageYOffset - 40;
      window.scrollTo({ top: top, behavior: 'smooth' });
    }
  }

  function updateContinue() {
    var btn = $('#q-continue');
    if (!btn) return;
    if (state.step === 1) {
      btn.disabled = !currentItem();
      btn.style.opacity = currentItem() ? '1' : '.4';
    } else {
      btn.disabled = false;
      btn.style.opacity = '1';
    }
  }

  function goNext() {
    var panel = $('#q-step-' + state.step);
    collectFields(panel);

    if (state.step === 1) {
      showStep(2);
      renderStep2();
      return;
    }
    if (state.step === 2) {
      if (validateFields(currentItem().fields)) { showStep(3); renderStep3(); }
      return;
    }
    if (state.step === 3) {
      if (validateFields(COMMON_FIELDS)) { showStep(4); renderStep4(); }
      return;
    }
    if (state.step === 4) {
      submitRequest();
    }
  }

  function goBack() {
    if (state.step > 1) {
      showStep(state.step - 1);
      if (state.step === 2) renderStep2();
      if (state.step === 3) renderStep3();
      if (state.step === 4) renderStep4();
    }
  }

  /* ============================ FILES ============================ */

  function renderFileList() {
    var list = $('#q-file-list');
    if (!list) return;
    list.innerHTML = state.files.map(function (f, i) {
      return '<li><span>' + esc(f.name) + ' <em style="opacity:.55;font-style:normal">(' + Math.round(f.size / 1024) + ' KB)</em></span>' +
        '<button type="button" data-remove-file="' + i + '"><i class="fa-light fa-xmark"></i></button></li>';
    }).join('');
  }

  /* ============================ SUBMIT ============================ */

  function submitRequest() {
    var svc = currentService();
    var item = currentItem();
    var payload = {
      service: svc.name,
      item: item.name,
      contact: {
        name: getAnswer('name'),
        email: getAnswer('email'),
        phone: getAnswer('phone') || ''
      },
      requirements: {},
      project: {
        completion_date: getAnswer('completion_date'),
        budget: getAnswer('budget'),
        notes: getAnswer('notes') || ''
      },
      attachments: state.files.map(function (f) { return f.name; }),
      submitted_at: new Date().toISOString()
    };

    item.fields.forEach(function (field) {
      if (!isFieldVisible(field)) return;
      payload.requirements[field.label] = getAnswer(field.key);
    });

    console.log('QUOTE REQUEST (frontend only):', payload);

    showStep(5);
    $('#q-success-text').textContent = 'We received your ' + item.name + ' request. Our team will review it and get back to you with a quotation shortly.';
  }

  /* ============================ EVENTS ============================ */

  function bindEvents() {
    $('#q-wizard').addEventListener('click', function (e) {
      var svcBtn = e.target.closest('[data-service]');
      if (svcBtn) {
        state.serviceId = svcBtn.getAttribute('data-service');
        state.itemId = null;
        renderStep1();
        return;
      }
      var itemBtn = e.target.closest('[data-item]');
      if (itemBtn) {
        state.itemId = itemBtn.getAttribute('data-item');
        renderStep1();
        return;
      }
      var removeBtn = e.target.closest('[data-remove-file]');
      if (removeBtn) {
        state.files.splice(Number(removeBtn.getAttribute('data-remove-file')), 1);
        renderFileList();
        return;
      }
    });

    $('#q-items-back').addEventListener('click', function () {
      state.serviceId = null;
      state.itemId = null;
      renderStep1();
    });

    $('#q-wizard').addEventListener('change', function (e) {
      var field = e.target.getAttribute('data-field');
      if (!field) return;

      if (e.target.type === 'file') {
        var files = Array.prototype.slice.call(e.target.files || []);
        files.forEach(function (f) {
          var okType = ['pdf', 'png', 'jpg', 'jpeg', 'ai', 'psd', 'zip'].indexOf((f.name.split('.').pop() || '').toLowerCase()) !== -1;
          if (!okType) { alert('Only PDF, PNG, JPEG, AI, PSD and ZIP files are allowed.'); return; }
          if (state.files.length >= MAX_FILES) { alert('You can attach up to ' + MAX_FILES + ' files.'); return; }
          state.files.push(f);
        });
        e.target.value = '';
        renderFileList();
        return;
      }

      if (e.target.type === 'radio') {
        setAnswer(e.target.name, e.target.value);
        reRenderAffected();
      } else if (e.target.type === 'checkbox') {
        collectFields($('#q-step-' + state.step));
        reRenderAffected();
      }
    });

    $('#q-wizard').addEventListener('input', function (e) {
      var field = e.target.getAttribute('data-field');
      if (field) {
        setAnswer(field, e.target.type === 'number' ? e.target.value : e.target.value);
      }
      var sgrid = e.target.getAttribute('data-sizegrid');
      if (sgrid) {
        var obj = getAnswer(sgrid) || {};
        var size = e.target.getAttribute('data-size');
        if (e.target.value && Number(e.target.value) > 0) obj[size] = e.target.value;
        else delete obj[size];
        setAnswer(sgrid, obj);
      }
    });

    $('#q-continue').addEventListener('click', goNext);
    $('#q-back').addEventListener('click', goBack);
    $('#q-submit').addEventListener('click', goNext);
  }

  function reRenderAffected() {
    var item = currentItem();
    if (state.step === 2 && item) {
      item.fields.forEach(function (field) {
        var wrap = document.querySelector('.q-step.active .q-field[data-key="' + field.key + '"]');
        if (!wrap) return;
        wrap.hidden = !isFieldVisible(field);
        if (field.type === 'sizegrid') renderSizeGrid(field);
      });
    }
  }

  /* ============================ INIT ============================ */

  function init() {
    var svcParam = qs('service');
    var itemParam = qs('item');
    if (svcParam && findService(svcParam)) {
      state.serviceId = svcParam;
      if (itemParam && findItem(currentService(), itemParam)) state.itemId = itemParam;
    }
    showStep(1, false);
    renderStep1();
    bindEvents();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
