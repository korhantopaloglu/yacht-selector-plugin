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
