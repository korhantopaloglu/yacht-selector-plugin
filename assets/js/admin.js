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

    var grid = fieldset.querySelector('[data-ys-bulk-booked-grid]');
    if (!grid) {
      return;
    }

    var showGrid = select.value === 'replace';
    grid.hidden = !showGrid;

    if (select.value === 'clear') {
      grid.querySelectorAll('input[type="checkbox"]').forEach(function(checkbox) {
        checkbox.checked = false;
      });
    }
  });
}

document.addEventListener('change', function(event) {
  if (event.target.matches('[data-ys-bulk-booked-action]')) {
    syncBulkBookedGrid(event.target.closest('.ys-bulk-edit-fieldset') || document);
  }
});

document.addEventListener('DOMContentLoaded', function() {
  syncBulkBookedGrid(document);
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

    var fields = wrapper.querySelector('[data-ys-crew-contact-fields]');
    if (!fields) {
      return;
    }

    var enabled = !!toggle.checked;
    fields.classList.toggle('is-inactive', !enabled);

    fields.querySelectorAll('input, select, textarea').forEach(function(field) {
      field.disabled = !enabled;
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

  if (event.target.matches('input[name="ys_contact_tool_icon_type"]')) {
    syncContactToolFields(event.target.closest('[data-ys-contact-tool-fields]') || document);
  }
});

document.addEventListener('click', function(event) {
  var dayPresetButton = event.target.closest('[data-ys-crew-days-select]');
  if (dayPresetButton) {
    applyCrewDayPreset(dayPresetButton);
    return;
  }

  var iconFillButton = event.target.closest('[data-ys-contact-tool-icon-fill]');
  if (iconFillButton) {
    var group = iconFillButton.closest('[data-ys-contact-tool-group="icon"]');
    var input = group ? group.querySelector('[data-ys-contact-tool-icon-input]') : null;

    if (!input) {
      return;
    }

    input.value = iconFillButton.getAttribute('data-ys-contact-tool-icon-fill') || '';
    input.focus();
  }
});

document.addEventListener('DOMContentLoaded', function() {
  syncExistingPostTypeField();
  syncCrewContactFields(document);
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
    var wrapper = selectButton.closest('[data-ys-media-field], .ys-field');
    if (!wrapper || typeof wp === 'undefined' || !wp.media) {
      return;
    }

    var input = wrapper.querySelector('[data-ys-image-id]');
    var preview = wrapper.querySelector('[data-ys-image-preview]');
    var placeholder = wrapper.querySelector('[data-ys-image-placeholder]');
    var removeButton = wrapper.querySelector('[data-ys-media-remove]');
    var previewWrap = wrapper.querySelector('[data-ys-image-preview-wrap]');

    var frame = wp.media({
      title: selectButton.getAttribute('data-ys-media-title') || 'Select Card Image',
      button: {
        text: selectButton.getAttribute('data-ys-media-button') || 'Use image'
      },
      library: {
        type: 'image'
      },
      multiple: false
    });

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

      selectButton.textContent = selectButton.getAttribute('data-ys-media-replace') || 'Replace Image';
    });

    frame.open();
    return;
  }

  var removeButton = event.target.closest('[data-ys-media-remove]');
  if (removeButton) {
    var field = removeButton.closest('[data-ys-media-field], .ys-field');
    if (!field) {
      return;
    }

    var input = field.querySelector('[data-ys-image-id]');
    var preview = field.querySelector('[data-ys-image-preview]');
    var placeholder = field.querySelector('[data-ys-image-placeholder]');
    var selectButton = field.querySelector('[data-ys-media-select]');
    var previewWrap = field.querySelector('[data-ys-image-preview-wrap]');

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
      selectButton.textContent = selectButton.getAttribute('data-ys-media-default') || 'Select Image';
    }

    removeButton.classList.add('hidden');
  }
});
