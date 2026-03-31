document.addEventListener('click', function(event) {
  var link = event.target.closest('.ys-selector-block-container a[href="#"]');
  if (!link) {
    return;
  }

  event.preventDefault();
});

function parseTimeToMinutes(value) {
  if (!value || typeof value !== 'string') {
    return null;
  }

  var match = value.match(/^(\d{2}):(\d{2})$/);
  if (!match) {
    return null;
  }

  var hours = parseInt(match[1], 10);
  var minutes = parseInt(match[2], 10);

  if (hours < 0 || hours > 23 || minutes < 0 || minutes > 59) {
    return null;
  }

  return (hours * 60) + minutes;
}

function getCurrentMinutes() {
  var now = new Date();
  return (now.getHours() * 60) + now.getMinutes();
}

function getActiveCrewState(card) {
  var single = card.querySelector('.ys-card-crew-member-single');
  if (single) {
    return {
      source: single,
      name: single.getAttribute('data-crew-name') || single.textContent || '',
      start: single.getAttribute('data-online-start') || '',
      end: single.getAttribute('data-online-end') || ''
    };
  }

  var select = card.querySelector('.ys-card-crew-member-select');
  if (!select || select.selectedIndex < 0) {
    return null;
  }

  var option = select.options[select.selectedIndex];
  if (!option) {
    return null;
  }

  return {
    source: option,
    name: option.getAttribute('data-crew-name') || option.textContent || '',
    start: option.getAttribute('data-online-start') || '',
    end: option.getAttribute('data-online-end') || ''
  };
}

function getActiveCrewToolUrls(card) {
  var crew = getActiveCrewState(card);
  var source = crew && crew.source ? crew.source : null;
  var raw = source ? source.getAttribute('data-tool-urls') : '';

  if (!raw) {
    return {};
  }

  try {
    var parsed = JSON.parse(raw);
    return parsed && typeof parsed === 'object' ? parsed : {};
  } catch (error) {
    return {};
  }
}

function getActiveCrewSelectedTools(card) {
  var crew = getActiveCrewState(card);
  var source = crew && crew.source ? crew.source : null;
  var raw = source ? source.getAttribute('data-selected-tools') : '';

  if (!raw) {
    return [];
  }

  try {
    var parsed = JSON.parse(raw);
    return Array.isArray(parsed) ? parsed : [];
  } catch (error) {
    return [];
  }
}

function isCrewOnline(card) {
  var crew = getActiveCrewState(card);
  if (!crew) {
    return false;
  }

  var start = parseTimeToMinutes(crew.start);
  var end = parseTimeToMinutes(crew.end);
  if (start === null || end === null) {
    return false;
  }

  var current = getCurrentMinutes();

  if (start === end) {
    return true;
  }

  if (start < end) {
    return current >= start && current <= end;
  }

  return current >= start || current <= end;
}

function applyCrewStatusState(card, hasCrew, isOnline) {
  var statusGroup = card.querySelector('.ys-card-crew-status-group');
  if (!statusGroup) {
    return;
  }

  var textNode = statusGroup.querySelector('.ys-card-crew-status-text');
  var crew = getActiveCrewState(card);
  var crewName = crew && crew.name ? crew.name.trim() : '';
  var onlineTemplate = statusGroup.getAttribute('data-online-text-template') || 'Currently on board: {crew_member}';
  var offlineTemplate = statusGroup.getAttribute('data-offline-text-template') || 'Currently offline: {crew_member}';
  var activeTemplate = isOnline ? onlineTemplate : offlineTemplate;
  var nextText = activeTemplate.replace('{crew_member}', crewName);

  statusGroup.classList.toggle('crew-online', !!hasCrew && !!isOnline);
  statusGroup.setAttribute('data-crew-online', hasCrew && isOnline ? '1' : '0');

  if (textNode) {
    textNode.textContent = nextText;
  }
}

function syncCardAvailabilityState(card) {
  if (!card) {
    return;
  }

  var hasCrew = card.getAttribute('data-has-crew') === '1';
  var allTools = card.querySelectorAll('.ys-tool-card');
  var toolUrls = getActiveCrewToolUrls(card);
  var selectedTools = getActiveCrewSelectedTools(card);
  var crewOnline = hasCrew ? isCrewOnline(card) : false;

  applyCrewStatusState(card, hasCrew, crewOnline);

  allTools.forEach(function(tool) {
    var toolKey = tool.getAttribute('data-tool-key') || '';
    var defaultUrl = tool.getAttribute('data-default-url') || '';
    var crewUrl = toolKey && toolUrls[toolKey] ? toolUrls[toolKey] : '';
    var resolvedUrl = crewUrl || defaultUrl;
    var isSensitive = tool.getAttribute('data-online-sensitive') === '1';
    var shouldDisable = !resolvedUrl;
    var isSelectedForCrew = toolKey && selectedTools.indexOf(toolKey) !== -1;

    if (isSensitive && hasCrew && !isSelectedForCrew) {
      shouldDisable = true;
    }

    if (isSensitive && (!hasCrew || !crewOnline)) {
      shouldDisable = true;
    }

    tool.setAttribute('data-tool-url', resolvedUrl);
    tool.setAttribute('href', resolvedUrl || '#');
    tool.classList.toggle('disabled', shouldDisable);
  });
}

document.addEventListener('change', function(event) {
  if (event.target.matches('.ys-card-crew-member-select')) {
    syncCardAvailabilityState(event.target.closest('.ys-card'));
  }
});

document.addEventListener('click', function(event) {
  var countryLink = event.target.closest('.ys-countries-container a[data-country]');
  if (countryLink) {
    var selectedCountry = countryLink.getAttribute('data-country') || 'all';
    var countryContainer = countryLink.closest('.ys-countries-container');

    if (countryContainer) {
      countryContainer.querySelectorAll('a[data-country]').forEach(function(link) {
        var isSelected = link === countryLink;
        link.classList.toggle('selected', isSelected);
        link.classList.toggle('active', isSelected);
      });
    }

    document.querySelectorAll('.ys-card').forEach(function(card) {
      var cardCountry = card.getAttribute('data-location') || '';
      var shouldShow = selectedCountry === 'all' || cardCountry === selectedCountry;

      card.classList.toggle('location-hide', !shouldShow);

      if (!shouldShow) {
        var crewPanel = card.querySelector('.ys-card-crew-group');

        if (crewPanel) {
          crewPanel.classList.remove('show');
        }
      }
    });

    event.preventDefault();
    return;
  }

  var watchButton = event.target.closest('.ys-watch-button, .ys-watch-video-button');
  if (watchButton) {
    var watchUrl = watchButton.getAttribute('data-url') || '';

    if (watchUrl) {
      window.location.assign(watchUrl);
    }

    return;
  }

  var panelTrigger = event.target.closest('.ys-call-crew-button, .ys-book-now-button');
  if (panelTrigger) {
    var triggerCard = panelTrigger.closest('.ys-card');
    var triggerPanel = triggerCard ? triggerCard.querySelector('.ys-card-crew-group') : null;

    if (triggerPanel) {
      triggerPanel.classList.toggle('show');
    }

    event.preventDefault();
    return;
  }

  var closeButton = event.target.closest('.ys-card-crew-close');
  if (closeButton) {
    var crewPanel = closeButton.closest('.ys-card-crew-group');

    if (crewPanel) {
      crewPanel.classList.remove('show');
    }

    event.preventDefault();
    return;
  }

  var toolCard = event.target.closest('.ys-tool-card');
  if (!toolCard) {
    return;
  }

  if (toolCard.classList.contains('disabled') || !toolCard.getAttribute('data-tool-url')) {
    event.preventDefault();
  }
});

document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.ys-card').forEach(function(card) {
    syncCardAvailabilityState(card);
  });
});
