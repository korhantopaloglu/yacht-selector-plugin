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

// Month Overlay Controller
var ENABLE_MONTH_TRACK_MOTION = false;

function getMonthItems() {
  return Array.prototype.slice.call(document.querySelectorAll('.ys-months-track .ys-month'));
}

function getSelectedMonthItem() {
  return document.querySelector('.ys-months-track .ys-month.active, .ys-months-track .ys-month.selected');
}

function resetMonthTrackTransform() {
  var track = document.querySelector('.ys-months-track');
  if (!track) {
    return;
  }

  track.style.transform = '';
}

function updateMonthOverlayFromItem(monthItem) {
  if (!monthItem) {
    return;
  }

  var overlay = document.querySelector('.ys-month-overlay');
  if (!overlay) {
    return;
  }

  var fullLabel = monthItem.getAttribute('data-month-full') || monthItem.querySelector('.ys-month-label').textContent;
  var density = monthItem.getAttribute('data-density') || monthItem.querySelector('.ys-month-density').textContent.replace('%', '');
  var barFill = monthItem.querySelector('.ys-month-bar-fill').style.width;

  overlay.querySelector('.ys-month-overlay-label').textContent = fullLabel;
  overlay.querySelector('.ys-month-overlay-density').textContent = density + '%';
  overlay.querySelector('.ys-month-overlay-bar-fill').style.width = barFill;
}

function getMonthItemCenter(monthItem) {
  if (!monthItem) {
    return 0;
  }

  var track = monthItem.closest('.ys-months-track');
  var trackRect = track.getBoundingClientRect();
  var itemRect = monthItem.getBoundingClientRect();

  return itemRect.left + (itemRect.width / 2) - trackRect.left;
}

function getOverlayCenter() {
  var overlay = document.querySelector('.ys-month-overlay');
  if (!overlay) {
    return 0;
  }

  var wrapper = overlay.closest('.ys-months-header-wrapper');
  var wrapperRect = wrapper.getBoundingClientRect();
  var overlayRect = overlay.getBoundingClientRect();

  return overlayRect.left + (overlayRect.width / 2) - wrapperRect.left;
}

function centerMonthItemUnderOverlay(monthItem) {
  if (!ENABLE_MONTH_TRACK_MOTION) {
    return;
  }

  if (!monthItem) {
    return;
  }

  var track = monthItem.closest('.ys-months-track');
  if (!track) {
    return;
  }

  var itemCenter = getMonthItemCenter(monthItem);
  var overlayCenter = getOverlayCenter();
  var currentTransform = track.style.transform ? parseFloat(track.style.transform.replace('translateX(', '').replace('px)', '')) : 0;
  
  // Determine direction based on item position relative to overlay
  var distance = itemCenter - overlayCenter;
  var targetTransform;
  
  if (distance < 0) {
    // Item is on the LEFT side of overlay - move track RIGHT (left-to-right)
    targetTransform = currentTransform + Math.abs(distance);
  } else if (distance > 0) {
    // Item is on the RIGHT side of overlay - move track LEFT (right-to-left)  
    targetTransform = currentTransform - Math.abs(distance);
  } else {
    // Item is already centered
    targetTransform = currentTransform;
  }

  track.style.transform = 'translateX(' + targetTransform + 'px)';
  
  // Mark the item as under overlay
  getMonthItems().forEach(function(item) {
    item.classList.remove('is-under-overlay');
  });
  monthItem.classList.add('is-under-overlay');
}

function setUnderlyingMonthState(monthItem) {
  if (!monthItem) {
    return;
  }

  getMonthItems().forEach(function(item) {
    item.classList.remove('is-under-overlay');
  });
  monthItem.classList.add('is-under-overlay');
}

function updateMonthState(selectedMonthLink) {
  if (!selectedMonthLink) {
    return;
  }

  var selectedMonth = selectedMonthLink.getAttribute('data-month') || '';
  var monthContainer = selectedMonthLink.closest('.ys-months-container');

  if (monthContainer) {
    monthContainer.querySelectorAll('.ys-month').forEach(function(link) {
      var isSelected = link === selectedMonthLink;
      link.classList.toggle('selected', isSelected);
      link.classList.toggle('active', isSelected);
    });
  }

  document.querySelectorAll('.ys-card').forEach(function(card) {
    var monthValues = (card.getAttribute('data-months') || '').split(',');
    var isBooked = monthValues.indexOf(selectedMonth) !== -1;

    card.classList.toggle('booked', isBooked);
  });

  // Update overlay immediately
  updateMonthOverlayFromItem(selectedMonthLink);

  // Keep visual marker state without moving the month track.
  setUnderlyingMonthState(selectedMonthLink);
  resetMonthTrackTransform();
  
  // Then animate track to center the month
  if (ENABLE_MONTH_TRACK_MOTION) {
    centerMonthItemUnderOverlay(selectedMonthLink);
  }
}

function getVisibleCards() {
  return Array.prototype.slice.call(document.querySelectorAll('.ys-card')).filter(function(card) {
    return !card.classList.contains('location-hide');
  });
}

function getWrappedIndex(index, length) {
  if (!length) {
    return 0;
  }

  return ((index % length) + length) % length;
}

function setSelectedCard(card) {
  if (!card) {
    return;
  }

  document.querySelectorAll('.ys-card.selected').forEach(function(selectedCard) {
    selectedCard.classList.remove('selected');
  });

  card.classList.add('selected');
}

function getActiveCardIndex(cards) {
  if (!cards.length) {
    return -1;
  }

  var activeIndex = cards.findIndex(function(card) {
    return card.classList.contains('selected');
  });

  if (activeIndex === -1) {
    setSelectedCard(cards[0]);
    return 0;
  }

  return activeIndex;
}

function applySliderWindowState() {
  var cards = getVisibleCards();
  var sliderStateClasses = ['is-active', 'is-pos-1', 'is-pos-2', 'is-pos-3', 'is-neg-1', 'is-neg-2', 'is-neg-3', 'is-bg-card', 'slider-outside-window'];

  document.querySelectorAll('.ys-card').forEach(function(card) {
    sliderStateClasses.forEach(function(className) {
      card.classList.remove(className);
    });
  });

  if (!cards.length) {
    return;
  }

  var activeIndex = getActiveCardIndex(cards);
  var total = cards.length;

  cards.forEach(function(card, index) {
    var forwardDistance = getWrappedIndex(index - activeIndex, total);
    var backwardDistance = getWrappedIndex(activeIndex - index, total);

    if (index === activeIndex) {
      card.classList.add('is-active');
      return;
    }

    if (forwardDistance <= 3 && (forwardDistance < backwardDistance || backwardDistance > 3)) {
      card.classList.add('is-pos-' + forwardDistance);
      card.classList.add('is-bg-card');
      return;
    }

    if (backwardDistance <= 3) {
      card.classList.add('is-neg-' + backwardDistance);
      card.classList.add('is-bg-card');
      return;
    }

    card.classList.add('slider-outside-window');
  });
}

function goToRelativeCard(step) {
  var cards = getVisibleCards();

  if (!cards.length) {
    return;
  }

  var activeIndex = getActiveCardIndex(cards);
  var nextIndex = getWrappedIndex(activeIndex + step, cards.length);

  setSelectedCard(cards[nextIndex]);
  applySliderWindowState();
}

function syncCrewPanelOpenState() {
  var hasOpenCrewPanel = false;

  document.querySelectorAll('.ys-card').forEach(function(card) {
    var panel = card.querySelector('.ys-card-contact-tools-group');
    var isOpen = panel ? panel.classList.contains('show') : false;
    var isSelectedCard = card.classList.contains('selected');
    card.classList.toggle('has-open-crew', isOpen);

    if (isSelectedCard && isOpen) {
      card.style.height = card.offsetHeight + 'px';
    } else {
      card.style.height = 'auto';
    }

    if (isOpen) {
      hasOpenCrewPanel = true;
    }
  });

  document.body.classList.toggle('crew-card-open', hasOpenCrewPanel);
}

function closeAllCrewPanels(exceptPanel) {
  document.querySelectorAll('.ys-card-contact-tools-group.show').forEach(function(panel) {
    if (exceptPanel && panel === exceptPanel) {
      return;
    }

    panel.classList.remove('show');
  });

  syncCrewPanelOpenState();
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
        var crewPanel = card.querySelector('.ys-card-contact-tools-group');

        if (crewPanel) {
          crewPanel.classList.remove('show');
        }
      }
    });

    syncCrewPanelOpenState();
    applySliderWindowState();

    event.preventDefault();
    return;
  }

  var monthLink = event.target.closest('.ys-months-container a[data-month]');
  if (monthLink) {
    updateMonthState(monthLink);
    applySliderWindowState();
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
    var triggerPanel = triggerCard ? triggerCard.querySelector('.ys-card-contact-tools-group') : null;

    if (triggerCard) {
      setSelectedCard(triggerCard);
      applySliderWindowState();
    }

    if (triggerPanel) {
      var shouldOpenPanel = !triggerPanel.classList.contains('show');
      closeAllCrewPanels(triggerPanel);
      triggerPanel.classList.toggle('show', shouldOpenPanel);
      syncCrewPanelOpenState();
    }

    event.preventDefault();
    return;
  }

  var closeButton = event.target.closest('.ys-card-crew-close');
  if (closeButton) {
    var crewPanel = closeButton.closest('.ys-card-contact-tools-group');

    if (crewPanel) {
      crewPanel.classList.remove('show');
    }

    syncCrewPanelOpenState();
    event.preventDefault();
    return;
  }

  var crewCloseButton = event.target.closest('.ys-card-crew-close-button');
  if (crewCloseButton) {
    var crewPanel = crewCloseButton.closest('.ys-card-contact-tools-group');

    if (crewPanel) {
      crewPanel.classList.toggle('show');
    }

    syncCrewPanelOpenState();
    event.preventDefault();
    return;
  }

  var prevButton = event.target.closest('.ys-card-nav-container .ys-prev');
  if (prevButton) {
    goToRelativeCard(-1);
    event.preventDefault();
    return;
  }

  var nextButton = event.target.closest('.ys-card-nav-container .ys-next');
  if (nextButton) {
    goToRelativeCard(1);
    event.preventDefault();
    return;
  }

  var selectedCard = event.target.closest('.ys-card');
  if (selectedCard && !selectedCard.classList.contains('location-hide')) {
    setSelectedCard(selectedCard);
    applySliderWindowState();
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
  resetMonthTrackTransform();
  closeAllCrewPanels();

  document.querySelectorAll('.ys-card').forEach(function(card) {
    syncCardAvailabilityState(card);
  });

  // Initialize month overlay
  var activeMonthLink = document.querySelector('.ys-months-container .ys-month.active, .ys-months-container .ys-month.selected');
  if (activeMonthLink) {
    updateMonthState(activeMonthLink);
  } else {
    // If no active month, find current month and set it
    var currentMonth = new Date().getMonth() + 1; // 1-12
    var monthItems = getMonthItems();
    var currentMonthItem = monthItems.find(function(item) {
      var itemMonth = parseInt(item.getAttribute('data-month'));
      return itemMonth === currentMonth;
    });
    
    if (currentMonthItem) {
      updateMonthState(currentMonthItem);
    } else if (monthItems.length > 0) {
      updateMonthState(monthItems[0]);
    }
  }

  applySliderWindowState();
});

window.addEventListener('resize', function() {
  resetMonthTrackTransform();

  if (ENABLE_MONTH_TRACK_MOTION) {
    var activeMonthLink = document.querySelector('.ys-months-container .ys-month.active, .ys-months-container .ys-month.selected');
    if (activeMonthLink) {
      centerMonthItemUnderOverlay(activeMonthLink);
    }
  }
});
