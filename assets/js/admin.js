document.addEventListener('click', function(event) {
  var tabButton = event.target.closest('[data-ys-tab]');
  if (tabButton) {
    var panel = tabButton.closest('[data-ys-admin-panel]');
    if (!panel) {
      return;
    }

    var target = tabButton.getAttribute('data-ys-tab');
    if (!target) {
      return;
    }

    panel.querySelectorAll('[data-ys-tab]').forEach(function(button) {
      var isActive = button === tabButton;
      button.classList.toggle('is-active', isActive);
      button.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });

    panel.querySelectorAll('[data-ys-panel]').forEach(function(tabPanel) {
      var isActive = tabPanel.getAttribute('data-ys-panel') === target;
      tabPanel.classList.toggle('is-active', isActive);
      tabPanel.hidden = !isActive;
    });

    return;
  }

  var addButton = event.target.closest('[data-ys-repeatable-add]');
  if (addButton) {
    var container = addButton.closest('[data-ys-repeatable]');
    if (!container) {
      return;
    }

    var rows = container.querySelector('[data-ys-repeatable-rows]');
    var template = container.querySelector('[data-ys-repeatable-template]');
    if (!rows || !template) {
      return;
    }

    rows.insertAdjacentHTML('beforeend', template.innerHTML.trim());
    var inputs = rows.querySelectorAll('input');
    if (inputs.length) {
      inputs[inputs.length - 1].focus();
    }

    return;
  }

  var removeButton = event.target.closest('[data-ys-repeatable-remove]');
  if (removeButton) {
    var row = removeButton.closest('.ys-repeatable-row');
    var list = row ? row.parentElement : null;
    if (!row || !list) {
      return;
    }

    if (list.children.length === 1) {
      var input = row.querySelector('input');
      if (input) {
        input.value = '';
        input.focus();
      }
      return;
    }

    row.remove();
  }
});

function syncBulkBookedGrid(scope) {
  var container = scope || document;

  container.querySelectorAll('[data-ys-bulk-booked-action]').forEach(function(select) {
    var fieldset = select.closest('.ys-bulk-edit-fieldset');
    if (!fieldset) {
      return;
    }

    var inputWrap = fieldset.querySelector('[data-ys-bulk-booked-input]');
    if (!inputWrap) {
      return;
    }

    var input = inputWrap.querySelector('textarea[name="ys_bulk_booked_months"]');
    var showInput = select.value === 'replace' || select.value === 'add' || select.value === 'remove';
    inputWrap.hidden = !showInput;

    if (!showInput && input) {
      input.value = '';
    }
  });
}

function initializeQuickEditBookedMonths() {
  if (typeof inlineEditPost === 'undefined' || typeof inlineEditPost.edit !== 'function') {
    return;
  }

  if (inlineEditPost._ysBookedPatched) {
    return;
  }

  inlineEditPost._ysBookedPatched = true;

  var originalInlineEdit = inlineEditPost.edit;

  inlineEditPost.edit = function(id) {
    originalInlineEdit.apply(this, arguments);

    var postId = 0;
    if (typeof id === 'object') {
      postId = parseInt(this.getId(id), 10);
    } else {
      postId = parseInt(id, 10);
    }

    if (!postId) {
      return;
    }

    var postRow = document.getElementById('post-' + postId);
    var editRow = document.getElementById('edit-' + postId);
    if (!postRow || !editRow) {
      return;
    }

    var valueNode = postRow.querySelector('[data-ys-booked-values]');
    var input = editRow.querySelector('textarea[name="ys_quick_booked_months"]');
    if (!input) {
      return;
    }

    input.value = valueNode ? valueNode.getAttribute('data-ys-booked-values') || '' : '';
  };
}

document.addEventListener('change', function(event) {
  if (event.target.matches('[data-ys-bulk-booked-action]')) {
    syncBulkBookedGrid(event.target.closest('.ys-bulk-edit-fieldset') || document);
  }
});

document.addEventListener('DOMContentLoaded', function() {
  syncBulkBookedGrid(document);
  initializeQuickEditBookedMonths();
});

function syncExistingPostTypeField() {
  var toggle = document.querySelector('[data-ys-use-existing-post-type]');
  var row = document.querySelector('[data-ys-existing-post-type-row]');
  var select = row ? row.querySelector('select') : null;

  if (!toggle || !row || !select) {
    return;
  }

  var enabled = !!toggle.checked;
  select.disabled = !enabled;
  row.classList.toggle('is-disabled', !enabled);
}

function syncCrewContactFields(scope) {
  var container = scope || document;

  container.querySelectorAll('[data-ys-crew-is-contact]').forEach(function(toggle) {
    var wrapper = toggle.closest('.ys-crew-meta-box');
    if (!wrapper) {
      return;
    }

    var fields = wrapper.querySelector('[data-ys-crew-contact-availability-fields]');
    if (!fields) {
      return;
    }

    var enabled = !!toggle.checked;
    fields.classList.toggle('is-inactive', !enabled);

    fields.querySelectorAll('input, select, textarea').forEach(function(field) {
      field.disabled = !enabled;
    });

    syncCrewContactUrlFields(wrapper);
  });
}

function syncCrewContactUrlFields(scope) {
  var container = scope || document;

  container.querySelectorAll('.ys-crew-meta-box').forEach(function(wrapper) {
    var toolList = wrapper.querySelector('[data-ys-crew-contact-tool-list]');

    if (!toolList) {
      return;
    }

    var selectedIds = [];

    wrapper.querySelectorAll('input[name="tax_input[ys_contact_tool][]"]:checked').forEach(function(checkbox) {
      var toolKey = checkbox.getAttribute('data-ys-contact-tool-key');
      if (toolKey) {
        selectedIds.push(toolKey);
      }
    });

    toolList.querySelectorAll('[data-ys-contact-tool-url-item]').forEach(function(item) {
      var toolKey = item.getAttribute('data-ys-contact-tool-url-item');
      var visible = selectedIds.indexOf(toolKey) !== -1;
      item.classList.toggle('hidden', !visible);
      item.hidden = !visible;

      item.querySelectorAll('input, select, textarea').forEach(function(field) {
        field.disabled = !visible;
      });
    });
  });
}

function syncContactToolFields(scope) {
  var container = scope || document;

  container.querySelectorAll('[data-ys-contact-tool-fields]').forEach(function(wrapper) {
    var selected = wrapper.querySelector('input[name="ys_contact_tool_icon_type"]:checked');
    var activeType = selected ? selected.value : 'icon';

    wrapper.querySelectorAll('[data-ys-contact-tool-group]').forEach(function(group) {
      var isActive = group.getAttribute('data-ys-contact-tool-group') === activeType;
      group.classList.toggle('hidden', !isActive);

      group.querySelectorAll('input, select, textarea, button').forEach(function(field) {
        if (field.matches('input[type="radio"]')) {
          return;
        }

        field.disabled = !isActive;
      });
    });
  });
}

function applyCrewDayPreset(button) {
  var wrapper = button.closest('.ys-crew-meta-box');
  if (!wrapper) {
    return;
  }

  var fields = wrapper.querySelector('[data-ys-crew-contact-fields]');
  if (!fields || fields.classList.contains('is-inactive')) {
    return;
  }

  var checkboxes = fields.querySelectorAll('input[name="ys_crew_online_days[]"]');
  if (!checkboxes.length) {
    return;
  }

  var mode = button.getAttribute('data-ys-crew-days-select');
  var selectedValues = [];

  if (mode === 'weekdays') {
    selectedValues = ['mon', 'tue', 'wed', 'thu', 'fri'];
  } else if (mode === 'weekend') {
    selectedValues = ['sat', 'sun'];
  } else if (mode === 'all') {
    selectedValues = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
  }

  checkboxes.forEach(function(checkbox) {
    checkbox.checked = selectedValues.indexOf(checkbox.value) !== -1;
  });
}

document.addEventListener('change', function(event) {
  if (event.target.matches('[data-ys-use-existing-post-type]')) {
    syncExistingPostTypeField();
    return;
  }

  if (event.target.matches('[data-ys-crew-is-contact]')) {
    syncCrewContactFields(event.target.closest('.ys-crew-meta-box') || document);
    return;
  }

  if (event.target.matches('input[name="tax_input[ys_contact_tool][]"], [data-ys-contact-tool-key]')) {
    syncCrewContactUrlFields(event.target.closest('.ys-crew-meta-box') || document);
    return;
  }

  if (event.target.matches('input[name="ys_contact_tool_icon_type"]')) {
    syncContactToolFields(document);
  }
});

document.addEventListener('click', function(event) {
  var contactToolCheckbox = event.target.closest('input[name="tax_input[ys_contact_tool][]"], [data-ys-contact-tool-key]');
  if (contactToolCheckbox) {
    window.setTimeout(function() {
      syncCrewContactUrlFields(contactToolCheckbox.closest('.ys-crew-meta-box') || document);
    }, 0);
  }

  var dayPresetButton = event.target.closest('[data-ys-crew-days-select]');
  if (dayPresetButton) {
    event.preventDefault();
    applyCrewDayPreset(dayPresetButton);
    return;
  }

  var iconFillButton = event.target.closest('[data-ys-contact-tool-icon-fill]');
  if (iconFillButton) {
    event.preventDefault();
    var group = iconFillButton.closest('[data-ys-contact-tool-group="icon"]');
    var input = group ? group.querySelector('[data-ys-contact-tool-icon-input]') : null;

    if (!input) {
      return;
    }

    input.value = iconFillButton.getAttribute('data-ys-contact-tool-icon-fill') || '';
    input.focus();
    return;
  }

  var contactUrlHelpToggle = event.target.closest('[data-ys-crew-contact-url-help-toggle]');
  if (contactUrlHelpToggle) {
    event.preventDefault();
    var metaBox = contactUrlHelpToggle.closest('.ys-crew-meta-box');
    var panel = metaBox ? metaBox.querySelector('[data-ys-crew-contact-url-help-panel]') : null;
    if (!panel) {
      return;
    }

    var expanded = contactUrlHelpToggle.getAttribute('aria-expanded') === 'true';
    contactUrlHelpToggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
    panel.hidden = expanded;
    panel.classList.toggle('hidden', expanded);
  }
});

document.addEventListener('DOMContentLoaded', function() {
  syncExistingPostTypeField();
  syncCrewContactFields(document);
  syncCrewContactUrlFields(document);
  syncContactToolFields(document);
});

document.querySelectorAll('[data-ys-admin-panel]').forEach(function(panel) {
  var firstTab = panel.querySelector('[data-ys-tab]');
  if (firstTab && !panel.querySelector('[data-ys-tab].is-active')) {
    firstTab.classList.add('is-active');
    firstTab.setAttribute('aria-selected', 'true');
  }

  panel.querySelectorAll('[data-ys-panel]').forEach(function(tabPanel, index) {
    var isActive = tabPanel.classList.contains('is-active') || index === 0;
    tabPanel.classList.toggle('is-active', isActive);
    tabPanel.hidden = !isActive;
  });
});

document.addEventListener('click', function(event) {
  var selectButton = event.target.closest('[data-ys-media-select]');
  if (selectButton) {
    event.preventDefault();

    var wrapper = selectButton.closest('[data-ys-contact-tool-image-field], [data-ys-media-field], .ys-field');
    if (!wrapper || typeof wp === 'undefined' || !wp.media) {
      return;
    }

    var input = wrapper.querySelector('[data-ys-contact-tool-image-id], [data-ys-image-id]');
    var preview = wrapper.querySelector('.ys-contact-tool-image-preview, [data-ys-image-preview]');
    var placeholder = wrapper.querySelector('[data-ys-image-placeholder]');
    var removeButton = wrapper.querySelector('.ys-contact-tool-image-remove, [data-ys-media-remove]');
    var previewWrap = wrapper.querySelector('.ys-contact-tool-image-preview-wrap, [data-ys-image-preview-wrap]');
    var frame = selectButton._ysMediaFrame || wp.media({
      title: selectButton.getAttribute('data-ys-media-title') || (window.ysAdminI18n && window.ysAdminI18n.mediaTitleFallback) || 'Select Card Image',
      button: {
        text: selectButton.getAttribute('data-ys-media-button') || (window.ysAdminI18n && window.ysAdminI18n.mediaButtonFallback) || 'Use image'
      },
      library: {
        type: 'image'
      },
      multiple: false
    });

    selectButton._ysMediaFrame = frame;

    frame.on('select', function() {
      var attachment = frame.state().get('selection').first().toJSON();
      if (!attachment || !input || !preview) {
        return;
      }

      input.value = attachment.id || '';
      preview.src = (attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url) || '';
      preview.hidden = !preview.src;

      if (placeholder) {
        placeholder.hidden = !!preview.src;
      }

      if (removeButton) {
        removeButton.classList.remove('hidden');
      }

      if (previewWrap) {
        previewWrap.classList.remove('is-empty');
      }

      selectButton.textContent = selectButton.getAttribute('data-ys-media-replace') || (window.ysAdminI18n && window.ysAdminI18n.replaceImageFallback) || 'Replace Image';
    });

    frame.open();
    return;
  }

  var removeButton = event.target.closest('[data-ys-media-remove]');
  if (removeButton) {
    event.preventDefault();

    var field = removeButton.closest('[data-ys-contact-tool-image-field], [data-ys-media-field], .ys-field');
    if (!field) {
      return;
    }

    var input = field.querySelector('[data-ys-contact-tool-image-id], [data-ys-image-id]');
    var preview = field.querySelector('.ys-contact-tool-image-preview, [data-ys-image-preview]');
    var placeholder = field.querySelector('[data-ys-image-placeholder]');
    var selectButton = field.querySelector('[data-ys-media-select]');
    var previewWrap = field.querySelector('.ys-contact-tool-image-preview-wrap, [data-ys-image-preview-wrap]');

    if (input) {
      input.value = '';
    }

    if (preview) {
      preview.src = '';
      preview.hidden = true;
    }

    if (placeholder) {
      placeholder.hidden = false;
    }

    if (previewWrap) {
      previewWrap.classList.add('is-empty');
    }

    if (selectButton) {
      selectButton.textContent = selectButton.getAttribute('data-ys-media-default') || (window.ysAdminI18n && window.ysAdminI18n.selectImageFallback) || 'Select Image';
    }

    removeButton.classList.add('hidden');
  }
});
