/* ============================================================
   G DESIGN — Settings view
   Update the signed-in admin's username and/or password.
   ============================================================ */
(function (GD) {
  'use strict';

  var MIN_LENGTH = 8;
  var USERNAME_RE = /^[a-z0-9_-]{3,30}$/;

  var FIELD_KEYS = {
    current_password: 0,
    username: 1,
    new_password: 2,
    new_password_confirmation: 3
  };

  function makeField(opts) {
    var msg = GD.el('small', { class: 'field-msg', 'aria-live': 'polite' });

    var attrs = {
      id: opts.id,
      class: 'input',
      type: opts.type || 'text',
      autocomplete: opts.autocomplete,
      value: opts.value || ''
    };
    attrs.oninput = function () { msg.textContent = ''; };

    var input = GD.el('input', attrs);

    var children = [GD.el('label', { 'for': opts.id }, opts.label), input, msg];
    if (opts.hint) {
      children.push(GD.el('small', { class: 'field-hint' }, opts.hint));
    }

    return {
      field: GD.el('div', { class: 'field' }, children),
      input: input,
      msg: msg
    };
  }

  function setErrors(fields, errors) {
    Object.keys(errors || {}).forEach(function (key) {
      if (key in FIELD_KEYS) {
        fields[FIELD_KEYS[key]].msg.textContent = errors[key];
      }
    });
  }

  function validate(fields, payload) {
    fields.forEach(function (f) { f.msg.textContent = ''; });

    if (!payload.current_password) {
      fields[0].msg.textContent = 'Current password is required.';
    }
    if (!payload.username) {
      fields[1].msg.textContent = 'Username is required.';
    } else if (!USERNAME_RE.test(payload.username)) {
      fields[1].msg.textContent = 'Username must be 3\u201330 characters using letters, digits, "_" or "-".';
    }
    if (payload.new_password) {
      if (payload.new_password.length < MIN_LENGTH) {
        fields[2].msg.textContent = 'New password must be at least ' + MIN_LENGTH + ' characters long.';
      } else if (payload.new_password === payload.current_password) {
        fields[2].msg.textContent = 'New password must be different from your current password.';
      }
      if (!payload.new_password_confirmation) {
        fields[3].msg.textContent = 'Please confirm your new password.';
      } else if (payload.new_password !== payload.new_password_confirmation) {
        fields[3].msg.textContent = 'Password confirmation does not match.';
      }
    } else if (payload.new_password_confirmation) {
      fields[2].msg.textContent = 'New password is required.';
    }

    return fields.every(function (f) { return f.msg.textContent === ''; });
  }

  function submit(btn, form, fields) {
    var payload = {
      username: fields[1].input.value.trim(),
      current_password: fields[0].input.value,
      new_password: fields[2].input.value,
      new_password_confirmation: fields[3].input.value
    };

    if (!validate(fields, payload)) { return; }

    var original = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Saving…';

    GD.post('/admin/auth/settings', payload)
      .then(function (res) {
        var data = (res.body && res.body.data) || {};
        if (data.admin) { GD.currentAdmin = data.admin; }
        form.reset();
        fields[1].input.value = (data.admin && data.admin.username) || payload.username;
        GD.toast(data.message || 'Profile updated successfully.');
      })
      .catch(function (err) {
        if (err.body && err.body.error && err.body.error.fields) {
          setErrors(fields, err.body.error.fields);
        } else {
          GD.toast(err.message || 'Unable to update profile.', true);
        }
      })
      .finally(function () {
        btn.disabled = false;
        btn.textContent = original;
      });
  }

  function renderSettings(container) {
    var username = makeField({
      id: 'set-username',
      label: 'Username',
      value: (GD.currentAdmin && GD.currentAdmin.username) || ''
    });
    var current = makeField({
      id: 'set-current-password',
      label: 'Current password',
      type: 'password',
      autocomplete: 'current-password'
    });
    var fresh = makeField({
      id: 'set-new-password',
      label: 'New password',
      type: 'password',
      autocomplete: 'new-password',
      hint: 'Leave blank to keep your current password.'
    });
    var confirm = makeField({
      id: 'set-confirm-password',
      label: 'Confirm new password',
      type: 'password',
      autocomplete: 'new-password'
    });

    var fields = [current, username, fresh, confirm];

    var button = GD.el('button', { class: 'btn btn-accent', type: 'submit' }, 'Save Changes');

    var form = GD.el('form', {
      class: 'settings-form',
      novalidate: true,
      onsubmit: function (e) {
        e.preventDefault();
        submit(button, form, fields);
      }
    }, [
      GD.el('p', { class: 'settings-intro' },
        'Update your login details. Changes apply on your next sign-in.'),
      username.field,
      current.field,
      fresh.field,
      confirm.field,
      button
    ]);

    container.appendChild(GD.el('div', { class: 'card settings-card' },
      [
        GD.el('div', { class: 'card-head' },
          GD.el('h2', { text: 'Account Settings' })),
        form
      ]));
  }

  GD.views.settings = function (container) {
    container.innerHTML = '';
    renderSettings(container);
  };
})(window.GDApp);