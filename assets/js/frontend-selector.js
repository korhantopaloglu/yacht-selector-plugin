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

function getMonthTrack(scope) {
  if (!scope) {
    return document.querySelector('.ys-months-track');
  }

  if (scope.classList && scope.classList.contains('ys-months-track')) {
    return scope;
  }

  if (scope.classList && scope.classList.contains('ys-months-header-wrapper')) {
    return scope.querySelector('.ys-months-track');
  }

  if (scope.classList && scope.classList.contains('ys-months-container')) {
    return scope.querySelector('.ys-months-track');
  }

  return scope.querySelector ? scope.querySelector('.ys-months-track') : document.querySelector('.ys-months-track');
}

function getMonthContainer(scope) {
  if (!scope) {
    return null;
  }

  if (scope.classList && scope.classList.contains('ys-months-container')) {
    return scope;
  }

  return scope.closest ? scope.closest('.ys-months-container') : null;
}

function getMonthItems(scope) {
  var track = getMonthTrack(scope || document);

  if (!track) {
    return [];
  }

  return Array.prototype.slice.call(track.querySelectorAll('.ys-month'));
}

function getSelectedMonthItem(scope) {
  var track = getMonthTrack(scope || document);

  if (!track) {
    return null;
  }

  return track.querySelector('.ys-month.active, .ys-month.selected');
}

function resetMonthTrackTransform(scope) {
  if (scope) {
    var scopedTrack = getMonthTrack(scope);
    if (scopedTrack) {
      scopedTrack.style.transform = '';
    }
    return;
  }

  document.querySelectorAll('.ys-months-track').forEach(function(track) {
    track.style.transform = '';
  });
}

function updateMonthOverlayFromItem(monthItem, monthContainer) {
  if (!monthItem) {
    return;
  }

  var resolvedContainer = monthContainer || getMonthContainer(monthItem);
  if (!resolvedContainer) {
    return;
  }

  var overlay = resolvedContainer.querySelector('.ys-month-overlay');
  if (!overlay) {
    return;
  }

  var labelNode = monthItem.querySelector('.ys-month-label');
  var densityNode = monthItem.querySelector('.ys-month-density');
  var barNode = monthItem.querySelector('.ys-month-bar-fill');
  var fullLabel = monthItem.getAttribute('data-month-full') || (labelNode ? labelNode.textContent : '');
  var density = monthItem.getAttribute('data-density') || (densityNode ? densityNode.textContent.replace('%', '') : '0');
  var barFill = barNode ? barNode.style.width : '0%';

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

function getOverlayCenter(monthContainer) {
  if (!monthContainer) {
    return 0;
  }

  var overlay = monthContainer.querySelector('.ys-month-overlay');
  var wrapper = monthContainer.querySelector('.ys-months-header-wrapper');
  if (!overlay || !wrapper) {
    return 0;
  }

  var wrapperRect = wrapper.getBoundingClientRect();
  var overlayRect = overlay.getBoundingClientRect();

  return overlayRect.left + (overlayRect.width / 2) - wrapperRect.left;
}

function centerMonthItemUnderOverlay(monthItem, monthContainer) {
  if (!ENABLE_MONTH_TRACK_MOTION) {
    return;
  }

  if (!monthItem) {
    return;
  }

  var resolvedContainer = monthContainer || getMonthContainer(monthItem);
  if (!resolvedContainer) {
    return;
  }

  var track = resolvedContainer.querySelector('.ys-months-track');
  if (!track) {
    return;
  }

  var itemCenter = getMonthItemCenter(monthItem);
  var overlayCenter = getOverlayCenter(resolvedContainer);
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
  getMonthItems(resolvedContainer).forEach(function(item) {
    item.classList.remove('is-under-overlay');
  });
  monthItem.classList.add('is-under-overlay');
}

function setUnderlyingMonthState(monthItem, monthContainer) {
  if (!monthItem) {
    return;
  }

  var resolvedContainer = monthContainer || getMonthContainer(monthItem);
  if (!resolvedContainer) {
    return;
  }

  getMonthItems(resolvedContainer).forEach(function(item) {
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
  var blockContainer = monthContainer ? monthContainer.closest('.ys-selector-block-container') : null;
  var cardScope = blockContainer || document;

  if (monthContainer) {
    monthContainer.querySelectorAll('.ys-month').forEach(function(link) {
      var isSelected = link === selectedMonthLink;
      link.classList.toggle('selected', isSelected);
      link.classList.toggle('active', isSelected);
    });
  }

  cardScope.querySelectorAll('.ys-card').forEach(function(card) {
    var monthValues = (card.getAttribute('data-months') || '').split(',');
    var isBooked = monthValues.indexOf(selectedMonth) !== -1;

    card.classList.toggle('booked', isBooked);
  });

  // Update overlay immediately
  updateMonthOverlayFromItem(selectedMonthLink, monthContainer);

  // Keep visual marker state without moving the month track.
  setUnderlyingMonthState(selectedMonthLink, monthContainer);
  resetMonthTrackTransform(monthContainer);
  
  // Then animate track to center the month
  if (ENABLE_MONTH_TRACK_MOTION) {
    centerMonthItemUnderOverlay(selectedMonthLink, monthContainer);
  }
}


function getScopeRoot(scope) {
  return scope && scope.querySelectorAll ? scope : document;
}

function getCards(scope) {
  return Array.prototype.slice.call(getScopeRoot(scope).querySelectorAll('.ys-card'));
}

function getVisibleCards(scope) {
  return getCards(scope).filter(function(card) {
    return !card.classList.contains('location-hide');
  });
}

function getCardIdentifier(card) {
  if (!card) {
    return '';
  }

  return (card.getAttribute('data-card-id') || card.getAttribute('data-id') || card.getAttribute('data-card-index') || '').toString();
}

function findCardByIdentifier(scope, cardId) {
  if (!cardId) {
    return null;
  }

  var cards = getCards(scope);
  for (var i = 0; i < cards.length; i += 1) {
    if (getCardIdentifier(cards[i]) === cardId) {
      return cards[i];
    }
  }

  return null;
}

function getCardThumbnailImageSource(card) {
  if (!card) {
    return '';
  }

  var image = card.querySelector('.ys-card-image-container img');
  if (image && image.getAttribute('src')) {
    return image.getAttribute('src');
  }

  return 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="160" height="90" viewBox="0 0 160 90"><rect width="160" height="90" fill="%23e2e8f0"/></svg>';
}

function buildThumbnailRail(scope) {
  var root = getScopeRoot(scope);
  var track = root.querySelector('.ys-card-thumbnail-track');

  if (!track) {
    return;
  }

  track.innerHTML = '';

  getCards(root).forEach(function(card, index) {
    var cardId = getCardIdentifier(card);
    var titleNode = card.querySelector('.ys-card-content-container h3');
    var title = titleNode ? titleNode.textContent.trim() : '';
    var thumbnail = document.createElement('button');
    var image = document.createElement('img');

    thumbnail.type = 'button';
    thumbnail.className = 'ys-card-thumbnail';
    thumbnail.setAttribute('data-card-id', cardId);
    thumbnail.setAttribute('aria-label', title ? 'Select ' + title : 'Select yacht ' + (index + 1));

    image.className = 'ys-card-thumbnail-image';
    image.src = getCardThumbnailImageSource(card);
    image.alt = title || ('Yacht ' + (index + 1));

    thumbnail.appendChild(image);
    track.appendChild(thumbnail);
  });
}

function syncThumbnailRailState(scope) {
  var root = getScopeRoot(scope);
  var track = root.querySelector('.ys-card-thumbnail-track');

  if (!track) {
    return;
  }

  var visibleIds = getVisibleCards(root).map(function(card) {
    return getCardIdentifier(card);
  });
  var selectedCard = root.querySelector('.ys-card.selected');
  var selectedId = selectedCard ? getCardIdentifier(selectedCard) : '';

  track.querySelectorAll('.ys-card-thumbnail').forEach(function(thumbnail) {
    var thumbnailId = thumbnail.getAttribute('data-card-id') || '';
    var isVisible = visibleIds.indexOf(thumbnailId) !== -1;
    var isActive = !!selectedId && selectedId === thumbnailId;

    thumbnail.classList.toggle('is-hidden', !isVisible);
    thumbnail.classList.toggle('active', isActive);
    thumbnail.classList.toggle('is-selected', isActive);
    thumbnail.hidden = !isVisible;
    thumbnail.setAttribute('aria-current', isActive ? 'true' : 'false');
  });

  var activeThumbnail = track.querySelector('.ys-card-thumbnail.active');
  if (activeThumbnail && activeThumbnail.scrollIntoView) {
    activeThumbnail.scrollIntoView({ block: 'nearest', inline: 'center' });
  }
}

function getWrappedIndex(index, length) {
  if (!length) {
    return 0;
  }

  return ((index % length) + length) % length;
}

function setSelectedCard(card, scope) {
  if (!card) {
    return;
  }

  var root = getScopeRoot(scope || card.closest('.ys-selector-block-container'));

  root.querySelectorAll('.ys-card.selected').forEach(function(selectedCard) {
    selectedCard.classList.remove('selected');
  });

  card.classList.add('selected');
  syncThumbnailRailState(root);
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

function applySliderWindowState(scope) {
  var root = getScopeRoot(scope);
  var cards = getVisibleCards(root);
  var sliderStateClasses = ['is-active', 'is-pos-1', 'is-pos-2', 'is-pos-3', 'is-neg-1', 'is-neg-2', 'is-neg-3', 'is-bg-card', 'slider-outside-window'];

  root.querySelectorAll('.ys-card').forEach(function(card) {
    sliderStateClasses.forEach(function(className) {
      card.classList.remove(className);
    });
  });

  if (!cards.length) {
    syncThumbnailRailState(root);
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

  syncThumbnailRailState(root);
}

function goToRelativeCard(step, scope) {
  var root = getScopeRoot(scope);
  var cards = getVisibleCards(root);
  var cardsContainer = root.querySelector('.ys-cards-container');

  if (!cards.length) {
    return;
  }

  if (cardsContainer) {
    cardsContainer.classList.add('ys-no-active-slide');
  }

  var activeIndex = getActiveCardIndex(cards);
  var nextIndex = getWrappedIndex(activeIndex + step, cards.length);

  setSelectedCard(cards[nextIndex], root);
  applySliderWindowState(root);

  if (cardsContainer) {
    window.requestAnimationFrame(function() {
      window.requestAnimationFrame(function() {
        cardsContainer.classList.remove('ys-no-active-slide');
      });
    });
  }
}

function syncCrewPanelOpenState(scope) {
  var root = getScopeRoot(scope);

  root.querySelectorAll('.ys-card').forEach(function(card) {
    var panel = card.querySelector('.ys-card-contact-tools-group');
    var isOpen = panel ? panel.classList.contains('show') : false;
    var isSelectedCard = card.classList.contains('selected');
    card.classList.toggle('has-open-crew', isOpen);

    if (isSelectedCard && isOpen) {
      card.style.height = card.offsetHeight + 'px';
    } else {
      card.style.height = 'auto';
    }

  });

  document.body.classList.toggle('crew-card-open', !!document.querySelector('.ys-card-contact-tools-group.show'));
}

function closeAllCrewPanels(exceptPanel, scope) {
  var root = getScopeRoot(scope);

  root.querySelectorAll('.ys-card-contact-tools-group.show').forEach(function(panel) {
    if (exceptPanel && panel === exceptPanel) {
      return;
    }

    panel.classList.remove('show');
  });

  syncCrewPanelOpenState(root);
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
    var blockContainer = countryLink.closest('.ys-selector-block-container') || document;

    if (countryContainer) {
      countryContainer.querySelectorAll('a[data-country]').forEach(function(link) {
        var isSelected = link === countryLink;
        link.classList.toggle('selected', isSelected);
        link.classList.toggle('active', isSelected);
      });
    }

    blockContainer.querySelectorAll('.ys-card').forEach(function(card) {
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

    syncCrewPanelOpenState(blockContainer);
    applySliderWindowState(blockContainer);

    event.preventDefault();
    return;
  }

  var monthLink = event.target.closest('.ys-months-container a[data-month]');
  if (monthLink) {
    var monthScope = monthLink.closest('.ys-selector-block-container') || document;
    updateMonthState(monthLink);
    applySliderWindowState(monthScope);
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
    var triggerScope = panelTrigger.closest('.ys-selector-block-container') || document;
    var triggerPanel = triggerCard ? triggerCard.querySelector('.ys-card-contact-tools-group') : null;

    if (triggerCard) {
      setSelectedCard(triggerCard, triggerScope);
      applySliderWindowState(triggerScope);
    }

    if (triggerPanel) {
      var shouldOpenPanel = !triggerPanel.classList.contains('show');
      closeAllCrewPanels(triggerPanel, triggerScope);
      triggerPanel.classList.toggle('show', shouldOpenPanel);
      syncCrewPanelOpenState(triggerScope);
    }

    event.preventDefault();
    return;
  }

  var closeButton = event.target.closest('.ys-card-crew-close');
  if (closeButton) {
    var crewPanel = closeButton.closest('.ys-card-contact-tools-group');
    var closeScope = closeButton.closest('.ys-selector-block-container') || document;

    if (crewPanel) {
      crewPanel.classList.remove('show');
    }

    syncCrewPanelOpenState(closeScope);
    event.preventDefault();
    return;
  }

  var crewCloseButton = event.target.closest('.ys-card-crew-close-button');
  if (crewCloseButton) {
    var crewPanel = crewCloseButton.closest('.ys-card-contact-tools-group');
    var crewCloseScope = crewCloseButton.closest('.ys-selector-block-container') || document;

    if (crewPanel) {
      crewPanel.classList.toggle('show');
    }

    syncCrewPanelOpenState(crewCloseScope);
    event.preventDefault();
    return;
  }

  var prevButton = event.target.closest('.ys-card-nav-container .ys-prev');
  if (prevButton) {
    var prevScope = prevButton.closest('.ys-selector-block-container') || document;
    goToRelativeCard(-1, prevScope);
    event.preventDefault();
    return;
  }

  var nextButton = event.target.closest('.ys-card-nav-container .ys-next');
  if (nextButton) {
    var nextScope = nextButton.closest('.ys-selector-block-container') || document;
    goToRelativeCard(1, nextScope);
    event.preventDefault();
    return;
  }

  var thumbnailButton = event.target.closest('.ys-card-thumbnail');
  if (thumbnailButton) {
    var thumbnailScope = thumbnailButton.closest('.ys-selector-block-container') || document;
    var targetCardId = thumbnailButton.getAttribute('data-card-id') || '';
    var targetCard = findCardByIdentifier(thumbnailScope, targetCardId);

    if (targetCard && !targetCard.classList.contains('location-hide')) {
      setSelectedCard(targetCard, thumbnailScope);
      applySliderWindowState(thumbnailScope);
    }

    event.preventDefault();
    return;
  }

  var selectedCard = event.target.closest('.ys-card');
  if (selectedCard && !selectedCard.classList.contains('location-hide')) {
    var selectedScope = selectedCard.closest('.ys-selector-block-container') || document;
    setSelectedCard(selectedCard, selectedScope);
    applySliderWindowState(selectedScope);
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

  document.querySelectorAll('.ys-selector-block-container').forEach(function(blockContainer) {
    closeAllCrewPanels(null, blockContainer);

    blockContainer.querySelectorAll('.ys-card').forEach(function(card) {
      syncCardAvailabilityState(card);
    });

    buildThumbnailRail(blockContainer);

    // Initialize month overlay per instance.
    blockContainer.querySelectorAll('.ys-months-container').forEach(function(monthContainer) {
      var activeMonthLink = getSelectedMonthItem(monthContainer);

      if (activeMonthLink) {
        updateMonthState(activeMonthLink);
        return;
      }

      var currentMonth = new Date().getMonth() + 1;
      var monthItems = getMonthItems(monthContainer);
      var currentMonthItem = monthItems.find(function(item) {
        return parseInt(item.getAttribute('data-month'), 10) === currentMonth;
      });

      if (currentMonthItem) {
        updateMonthState(currentMonthItem);
      } else if (monthItems.length > 0) {
        updateMonthState(monthItems[0]);
      }
    });

    applySliderWindowState(blockContainer);
  });
});

window.addEventListener('resize', function() {
  resetMonthTrackTransform();

  if (ENABLE_MONTH_TRACK_MOTION) {
    document.querySelectorAll('.ys-months-container').forEach(function(monthContainer) {
      var activeMonthLink = getSelectedMonthItem(monthContainer);
      if (activeMonthLink) {
        centerMonthItemUnderOverlay(activeMonthLink, monthContainer);
      }
    });
  }
});
