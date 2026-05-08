document.addEventListener('click', function(event) {
  var link = event.target.closest('.ys-selector-block-container a[href="#"]');
  if (!link) {
    return;
  }

  // Month/country/tools use delegated handlers below; do not intercept those clicks here.
  if (link.matches('a.ys-month[data-month]')) {
    return;
  }

  if (link.closest('.ys-countries-container') && link.hasAttribute('data-country')) {
    return;
  }

  if (link.classList.contains('ys-tool-card')) {
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
var MONTH_SCROLL_THRESHOLD = 60;
var MONTH_DRAG_THRESHOLD = 6;
var MONTH_SNAP_DURATION_MS = 260;
var suppressMonthClickOnce = false;

// Shared smooth-center animation — used by drag-snap and direct click.
// Eases the mask scroll so targetItem's centre aligns with the mask centre.
// Returns a cancel function (or null if no animation was needed).
// Calls onComplete (if provided) once the animation finishes.
function animateScrollToCenter(mask, targetItem, onComplete) {
  if (!mask || !targetItem) {
    if (onComplete) { onComplete(); }
    return null;
  }

  var maskRect   = mask.getBoundingClientRect();
  var itemRect   = targetItem.getBoundingClientRect();
  var offset     = (itemRect.left + itemRect.width / 2) - (maskRect.left + maskRect.width / 2);
  var fromScroll = mask.scrollLeft;
  var toScroll   = fromScroll + offset;
  var maxScroll  = Math.max(0, mask.scrollWidth - mask.clientWidth);
  toScroll       = Math.max(0, Math.min(toScroll, maxScroll));

  if (Math.abs(toScroll - fromScroll) < 1) {
    if (onComplete) { onComplete(); }
    return null;
  }

  var dist    = toScroll - fromScroll;
  var startTs = null;
  var rafId   = null;

  function step(ts) {
    if (startTs === null) { startTs = ts; }
    var t = Math.min((ts - startTs) / MONTH_SNAP_DURATION_MS, 1);
    mask.scrollLeft = fromScroll + dist * (1 - Math.pow(1 - t, 3)); // easeOutCubic

    if (t < 1) {
      rafId = requestAnimationFrame(step);
    } else {
      rafId = null;
      mask.scrollLeft = toScroll;
      if (onComplete) { onComplete(); }
    }
  }

  rafId = requestAnimationFrame(step);

  return function cancel() {
    if (rafId !== null) { cancelAnimationFrame(rafId); rafId = null; }
  };
}

function getMonthTrack(scope) {
  if (!scope) {
    return document.querySelector('.ys-months-track');
  }

  if (scope.classList && scope.classList.contains('ys-months-track')) {
    return scope;
  }

  if (scope.classList && scope.classList.contains('ys-months-mask')) {
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

function getMonthMask(scope) {
  var container = getMonthContainer(scope) || scope;
  if (!container || !container.querySelector) {
    return null;
  }

  if (container.classList && container.classList.contains('ys-months-mask')) {
    return container;
  }

  return container.querySelector('.ys-months-mask');
}

function getSingleMonthTrackWidth(monthContainer) {
  if (!monthContainer || !monthContainer.querySelector) {
    return 0;
  }

  var originalTrack = monthContainer.querySelector('.ys-months-track[data-track-role="original"]');
  if (!originalTrack) {
    return 0;
  }

  return originalTrack.offsetWidth;
}


function getAllMonthItemsByKey(monthContainer, monthKey) {
  if (!monthContainer || !monthKey) {
    return [];
  }
  return monthContainer.querySelectorAll('.ys-month[data-month-key="' + monthKey + '"]');
}

function syncSelectedMonthAcrossClones(monthContainer, monthKey, sourceItem) {
  var allItems = getAllMonthItemsByKey(monthContainer, monthKey);
  if (!allItems.length) {
    return;
  }

  // Remove selected/active from all month items first
  monthContainer.querySelectorAll('.ys-month.active, .ys-month.selected').forEach(function(item) {
    item.classList.remove('active', 'selected', 'is-under-overlay');
  });

  // Add selected/active to all items with same month key
  allItems.forEach(function(item) {
    item.classList.add('active', 'selected');
  });

  // Add is-under-overlay only to the closest visible item
  var bestVisible = findBestVisibleMonthItem(monthContainer, monthKey);
  if (bestVisible) {
    bestVisible.classList.add('is-under-overlay');
  }
}

function findBestVisibleMonthItem(monthContainer, monthKey) {
  var allItems = getAllMonthItemsByKey(monthContainer, monthKey);
  if (!allItems.length) {
    return null;
  }

  var mask = getMonthMask(monthContainer);
  if (!mask) {
    return allItems[0];
  }

  var maskRect = mask.getBoundingClientRect();
  var maskCenter = maskRect.left + (maskRect.width / 2);
  var closestItem = null;
  var closestDistance = Infinity;

  allItems.forEach(function(item) {
    var itemRect = item.getBoundingClientRect();
    var itemCenter = itemRect.left + (itemRect.width / 2);
    var distance = Math.abs(itemCenter - maskCenter);
    
    if (distance < closestDistance) {
      closestDistance = distance;
      closestItem = item;
    }
  });

  return closestItem;
}

function normalizeMonthScrollPosition(monthContainer) {
  var mask = getMonthMask(monthContainer);
  if (!mask) {
    return;
  }

  var singleTrackWidth = getSingleMonthTrackWidth(monthContainer);
  if (!singleTrackWidth) {
    return;
  }

  var currentScrollLeft = mask.scrollLeft;
  var leftThreshold = MONTH_SCROLL_THRESHOLD;
  var rightThreshold = MONTH_SCROLL_THRESHOLD;

  // Normalize left edge
  if (currentScrollLeft <= leftThreshold) {
    mask.scrollLeft = currentScrollLeft + singleTrackWidth;
    return;
  }

  // Normalize right edge (2 tracks = singleTrackWidth * 2)
  var rightEdge = singleTrackWidth * 2;
  if (currentScrollLeft >= rightEdge - rightThreshold) {
    mask.scrollLeft = currentScrollLeft - singleTrackWidth;
  }
}

function bindInfiniteMonthScroll(monthContainer) {
  var mask = getMonthMask(monthContainer);
  if (!mask) {
    return;
  }

  var isNormalizingMonthScroll = false;
  var lastScrollLeft = 0;

  mask.addEventListener('scroll', function() {
    if (isNormalizingMonthScroll) {
      return;
    }

    // Prevent recursive scroll events during normalization
    if (Math.abs(mask.scrollLeft - lastScrollLeft) > 100) {
      isNormalizingMonthScroll = true;
      requestAnimationFrame(function() {
        normalizeMonthScrollPosition(monthContainer);
        lastScrollLeft = mask.scrollLeft;
        isNormalizingMonthScroll = false;
      });
    } else {
      lastScrollLeft = mask.scrollLeft;
    }
  });
}

function bindMonthDragScroll(monthContainer) {
  var mask     = getMonthMask(monthContainer);
  var maskInner = monthContainer && monthContainer.querySelector
    ? monthContainer.querySelector('.ys-months-mask-inner')
    : null;

  if (!mask || !maskInner || mask.getAttribute('data-month-drag-ready') === '1') {
    return;
  }

  mask.setAttribute('data-month-drag-ready', '1');
  mask.classList.add('is-drag-scroll-ready');

  // Momentum tuning constants
  var FRICTION         = 0.94;   // velocity multiplier per 60 fps frame (time-normalised below)
  var MIN_VELOCITY     = 0.25;   // px/ms — stop momentum below this
  var SAMPLE_WINDOW_MS = 100;    // rolling window used to compute release velocity

  var isPointerDown    = false;
  var isDragging       = false;
  var activePointerId  = null;
  var startX           = 0;
  var startY           = 0;
  var startScrollLeft  = 0;
  var dragCaptureActive = false;
  var velSamples       = [];     // { t, x } recent pointer positions
  var momentumRafId    = null;
  var cancelSnapFn     = null;   // cancel handle returned by animateScrollToCenter

  function cancelAnimations() {
    if (momentumRafId !== null) { cancelAnimationFrame(momentumRafId); momentumRafId = null; }
    if (cancelSnapFn  !== null) { cancelSnapFn(); cancelSnapFn = null; }
  }

  // Find the a.ys-month whose visual centre is closest to the mask centre.
  function findNearestMonthItem() {
    var maskRect    = mask.getBoundingClientRect();
    var maskCenterX = maskRect.left + maskRect.width / 2;
    var items       = maskInner.querySelectorAll('a.ys-month[data-month]');
    var closest     = null;
    var closestDist = Infinity;

    for (var i = 0; i < items.length; i++) {
      var r    = items[i].getBoundingClientRect();
      var cx   = r.left + r.width / 2;
      var dist = Math.abs(cx - maskCenterX);
      if (dist < closestDist) { closestDist = dist; closest = items[i]; }
    }
    return closest;
  }

  // Trigger existing month-selection flow for a programmatically centred item.
  function selectSnappedMonth(monthItem) {
    if (!monthItem) { return; }
    var scope = getBlockScope(monthItem);
    updateMonthState(monthItem, false);
    applySliderWindowState(scope);
  }

  // Snap to a month item using the shared animation, then select it.
  function snapToItem(targetItem) {
    if (!targetItem) { return; }
    cancelSnapFn = animateScrollToCenter(mask, targetItem, function() {
      cancelSnapFn = null;
      selectSnappedMonth(targetItem);
    });
  }

  // Derive release velocity (px/ms) from recent pointer samples.
  function calcReleaseVelocity() {
    if (velSamples.length < 2) { return 0; }
    var now    = Date.now();
    var recent = velSamples.filter(function(s) { return now - s.t <= SAMPLE_WINDOW_MS; });
    if (recent.length < 2) { return 0; }
    var first = recent[0];
    var last  = recent[recent.length - 1];
    var dt    = last.t - first.t;
    if (dt <= 0) { return 0; }
    // Dragging right (+x) decreases scrollLeft, so velocity sign is negated.
    return -(last.x - first.x) / dt;
  }

  // Friction-decay loop; snaps to nearest month when velocity falls below MIN_VELOCITY.
  function startMomentum(velocity) {
    var lastTs = null;

    function momentumStep(ts) {
      if (lastTs === null) { lastTs = ts; momentumRafId = requestAnimationFrame(momentumStep); return; }

      var dt       = ts - lastTs;
      lastTs       = ts;
      velocity    *= Math.pow(FRICTION, dt / 16.667); // normalise friction to 60 fps

      var nextLeft = mask.scrollLeft + velocity * dt;
      var maxScroll = Math.max(0, mask.scrollWidth - mask.clientWidth);

      if (nextLeft <= 0 || nextLeft >= maxScroll) {
        // Bounced into scroll boundary — snap immediately from here.
        mask.scrollLeft = Math.max(0, Math.min(nextLeft, maxScroll));
        momentumRafId   = null;
        snapToItem(findNearestMonthItem());
        return;
      }

      mask.scrollLeft = nextLeft;

      if (Math.abs(velocity) > MIN_VELOCITY) {
        momentumRafId = requestAnimationFrame(momentumStep);
      } else {
        momentumRafId = null;
        snapToItem(findNearestMonthItem());
      }
    }

    momentumRafId = requestAnimationFrame(momentumStep);
  }

  // Central end-of-drag handler; starts momentum or bare snap as appropriate.
  function handleDragEnd(wasDragging) {
    if (!isPointerDown) { return; }

    if (wasDragging) {
      // Suppress the synthetic click that fires immediately after pointerup.
      suppressMonthClickOnce = true;
      setTimeout(function() { suppressMonthClickOnce = false; }, 0);
    }

    var velocity      = wasDragging ? calcReleaseVelocity() : 0;
    isPointerDown     = false;
    isDragging        = false;
    activePointerId   = null;
    dragCaptureActive = false;
    velSamples        = [];
    mask.classList.remove('is-month-dragging');

    if (!wasDragging) { return; } // plain tap → let the click handler take it

    if (Math.abs(velocity) > MIN_VELOCITY) {
      startMomentum(velocity);
    } else {
      snapToItem(findNearestMonthItem());
    }
  }

  maskInner.addEventListener('pointerdown', function(event) {
    if (event.pointerType === 'touch' || event.button !== 0) { return; }

    cancelAnimations(); // interrupt any ongoing momentum/snap on new press
    isPointerDown     = true;
    isDragging        = false;
    dragCaptureActive = false;
    activePointerId   = event.pointerId;
    startX            = event.clientX;
    startY            = event.clientY;
    startScrollLeft   = mask.scrollLeft;
    velSamples        = [{ t: Date.now(), x: event.clientX }];
  });

  maskInner.addEventListener('pointermove', function(event) {
    if (!isPointerDown || (activePointerId !== null && event.pointerId !== activePointerId)) { return; }

    var now = Date.now();
    velSamples.push({ t: now, x: event.clientX });
    // Trim samples outside the velocity window.
    while (velSamples.length > 1 && now - velSamples[0].t > SAMPLE_WINDOW_MS) {
      velSamples.shift();
    }

    var deltaX = event.clientX - startX;
    var deltaY = event.clientY - startY;

    if (!isDragging) {
      if (Math.abs(deltaX) < MONTH_DRAG_THRESHOLD || Math.abs(deltaX) < Math.abs(deltaY)) { return; }

      isDragging      = true;
      mask.classList.add('is-month-dragging');
      // Re-anchor so the first scroll jump is zero.
      startX          = event.clientX;
      startY          = event.clientY;
      startScrollLeft = mask.scrollLeft;
      deltaX          = 0;

      if (maskInner.setPointerCapture) {
        try { maskInner.setPointerCapture(activePointerId); dragCaptureActive = true; }
        catch (e) { dragCaptureActive = false; }
      }
    }

    mask.scrollLeft = startScrollLeft - deltaX;
    event.preventDefault();
  });

  maskInner.addEventListener('pointerup', function(event) {
    if (activePointerId !== null && event.pointerId !== activePointerId) { return; }

    if (dragCaptureActive && maskInner.releasePointerCapture && activePointerId !== null) {
      try { maskInner.releasePointerCapture(activePointerId); } catch (e) {}
    }

    handleDragEnd(isDragging);
  });

  maskInner.addEventListener('pointercancel', function() {
    handleDragEnd(isDragging);
  });

  maskInner.addEventListener('lostpointercapture', function() {
    if (isPointerDown) { handleDragEnd(isDragging); }
  });

  maskInner.addEventListener('dragstart', function(event) {
    event.preventDefault();
  });
}

function centerMonthItemInMask(monthItem, monthContainer, behavior) {
  var mask = getMonthMask(monthContainer);
  if (!mask || !monthItem) {
    return;
  }

  var targetScrollLeft = monthItem.offsetLeft - ((mask.clientWidth - monthItem.offsetWidth) / 2);
  var maxScrollLeft = Math.max(0, mask.scrollWidth - mask.clientWidth);
  targetScrollLeft = Math.max(0, Math.min(targetScrollLeft, maxScrollLeft));

  mask.scrollTo({
    left: targetScrollLeft,
    behavior: behavior || 'smooth'
  });
}

function scrollMonthItemIntoComfortZone(monthItem, monthContainer, forceCenter) {
  var mask = getMonthMask(monthContainer);
  if (!mask || !monthItem) {
    return;
  }

  if (forceCenter) {
    centerMonthItemInMask(monthItem, monthContainer, 'auto');
    return;
  }

  var itemRect = monthItem.getBoundingClientRect();
  var maskRect = mask.getBoundingClientRect();
  var itemLeft = itemRect.left - maskRect.left;
  var itemRight = itemLeft + itemRect.width;
  var maskWidth = maskRect.width;

  // If it's not comfortably visible, center it.
  if (itemLeft < 50 || itemRight > maskWidth - 50) {
    centerMonthItemInMask(monthItem, monthContainer, 'smooth');
  }
}

function updateMonthState(selectedMonthLink, forceCenter) {
  if (!selectedMonthLink) {
    return;
  }

  var selectedMonth = selectedMonthLink.getAttribute('data-month') || '';
  var monthKey = selectedMonthLink.getAttribute('data-month-key') || selectedMonth;
  var monthContainer = selectedMonthLink.closest('.ys-months-container');
  var blockContainer = monthContainer ? monthContainer.closest('.ys-selector-block-container') : null;

  if (!blockContainer) {
    return;
  }

  // Sync selected state across all clones
  syncSelectedMonthAcrossClones(monthContainer, monthKey, selectedMonthLink);

  // Update overlay from the best visible item
  var bestVisibleItem = findBestVisibleMonthItem(monthContainer, monthKey);
  if (bestVisibleItem) {
    updateMonthOverlayFromItem(bestVisibleItem);
  }

  // Update card availability states
  var selectedMonthNum = parseInt(selectedMonth, 10);
  if (!isNaN(selectedMonthNum)) {
    blockContainer.querySelectorAll('.ys-card').forEach(function(card) {
      var monthValues = (card.getAttribute('data-months') || '').split(',');
      var isBooked = monthValues.indexOf(selectedMonth) !== -1;

      card.classList.toggle('booked', isBooked);
      card.classList.toggle('booked-hide', isBooked);
    });
  }

  // Scroll the selected month into comfort zone
  scrollMonthItemIntoComfortZone(selectedMonthLink, monthContainer, !!forceCenter);
}

function updateMonthOverlayFromItem(monthItem) {
  if (!monthItem) {
    return;
  }

  var overlay = monthItem.closest('.ys-months-header-wrapper').querySelector('.ys-month-overlay');
  if (!overlay) {
    return;
  }

  var monthLabel = monthItem.getAttribute('data-month-full') || '';
  var density = monthItem.getAttribute('data-density') || '';
  var barFill = density + '%';

  overlay.querySelector('.ys-month-overlay-label').textContent = monthLabel;
  overlay.querySelector('.ys-month-overlay-density').textContent = density + '%';
  overlay.querySelector('.ys-month-overlay-bar-fill').style.width = barFill;
}

// Month Infinite Scroll Initialization
function initializeMonthInfiniteScroll(monthContainer) {
  if (!monthContainer) {
    return;
  }

  // Bind infinite scroll behavior
  bindInfiniteMonthScroll(monthContainer);
  bindMonthDragScroll(monthContainer);

  // Get single track width for calculations
  var singleTrackWidth = getSingleMonthTrackWidth(monthContainer);
  if (!singleTrackWidth) {
    return;
  }

  // Start with middle track (original) visible
  var mask = getMonthMask(monthContainer);
  if (mask) {
    mask.scrollLeft = singleTrackWidth;
  }

  // Find and set initial selected month from original track
  var activeMonthLink = monthContainer.querySelector('.ys-months-track[data-track-role="original"] .ys-month.active, .ys-months-track[data-track-role="original"] .ys-month.selected');
  if (!activeMonthLink) {
    // If no active month, find current month
    var currentMonth = new Date().getMonth() + 1;
    var monthItems = monthContainer.querySelectorAll('.ys-months-track[data-track-role="original"] .ys-month');
    for (var i = 0; i < monthItems.length; i++) {
      var itemMonth = parseInt(monthItems[i].getAttribute('data-month-key'));
      if (itemMonth === currentMonth) {
        activeMonthLink = monthItems[i];
        break;
      }
    }
  }

  if (activeMonthLink) {
    updateMonthState(activeMonthLink, true);
  }
}

// Event Handlers
document.addEventListener('DOMContentLoaded', function() {
  // Initialize month infinite scroll for each selector block
  document.querySelectorAll('.ys-selector-block-container').forEach(function(blockContainer) {
    var monthContainer = blockContainer.querySelector('.ys-months-container');
    if (monthContainer) {
      initializeMonthInfiniteScroll(monthContainer);
    }
  });
});

window.addEventListener('resize', function() {
  // Re-initialize month scroll for each container
  document.querySelectorAll('.ys-months-container').forEach(function(monthContainer) {
    var singleTrackWidth = getSingleMonthTrackWidth(monthContainer);
    if (singleTrackWidth) {
      var mask = getMonthMask(monthContainer);
      if (mask) {
        // Normalize scroll position to middle track
        mask.scrollLeft = singleTrackWidth;
      }
      
      // Update selected month state
      var activeMonthLink = monthContainer.querySelector('.ys-months-track[data-track-role="original"] .ys-month.active, .ys-months-track[data-track-role="original"] .ys-month.selected') ||
        monthContainer.querySelector('.ys-month.active, .ys-month.selected');
      if (activeMonthLink) {
        updateMonthState(activeMonthLink, true);
      }
    }
  });
});


function getScopeRoot(scope) {
  return scope && scope.querySelectorAll ? scope : document;
}

function getBlockScope(node) {
  if (node && node.closest) {
    var localScope = node.closest('.ys-selector-block-container');
    if (localScope) {
      return localScope;
    }
  }

  var allScopes = document.querySelectorAll('.ys-selector-block-container');
  if (allScopes.length === 1) {
    return allScopes[0];
  }

  return document;
}

function getCards(scope) {
  return Array.prototype.slice.call(getScopeRoot(scope).querySelectorAll('.ys-card'));
}

function getVisibleCards(scope) {
  return getCards(scope).filter(function(card) {
    return !card.classList.contains('location-hide') && !card.classList.contains('booked-hide');
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

function updateSliderCounter(scope) {
  var root    = getScopeRoot(scope);
  var counter = root.querySelector ? root.querySelector('.ys-slider-counter') : null;
  if (!counter) { return; }

  var cards = getVisibleCards(root);
  var total = cards.length;
  var currentIndex = 1;

  for (var i = 0; i < cards.length; i++) {
    if (cards[i].classList.contains('selected') || cards[i].classList.contains('is-active')) {
      currentIndex = i + 1;
      break;
    }
  }

  function pad(n) { return n < 10 ? '0' + n : String(n); }

  var currentEl = counter.querySelector('.ys-slider-counter-current');
  var totalEl   = counter.querySelector('.ys-slider-counter-total');
  if (currentEl) { currentEl.textContent = pad(currentIndex); }
  if (totalEl)   { totalEl.textContent   = pad(total); }
}

function applySliderWindowState(scope) {
  var root = getScopeRoot(scope);
  var cards = getVisibleCards(root);
  var sliderStateClasses = ['is-active', 'is-pos-1', 'is-pos-2', 'is-pos-3', 'is-pos-4', 'is-pos-5', 'is-neg-1', 'is-neg-2', 'is-neg-3', 'is-neg-4', 'is-neg-5', 'is-bg-card', 'slider-outside-window'];

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

  // Bias the extra visible slot to the right side when total is even.
  var maxSide          = 5;
  var visibleSideSlots = Math.min(total - 1, maxSide * 2);
  var leftCount        = Math.floor(visibleSideSlots / 2);
  var rightCount       = Math.ceil(visibleSideSlots / 2);

  cards.forEach(function(card, index) {
    if (index === activeIndex) {
      card.classList.add('is-active');
      return;
    }

    var forwardDistance  = getWrappedIndex(index - activeIndex, total);
    var backwardDistance = getWrappedIndex(activeIndex - index, total);

    if (forwardDistance <= rightCount && forwardDistance <= backwardDistance) {
      card.classList.add('is-pos-' + forwardDistance);
      card.classList.add('is-bg-card');
      return;
    }

    if (backwardDistance <= leftCount) {
      card.classList.add('is-neg-' + backwardDistance);
      card.classList.add('is-bg-card');
      return;
    }

    card.classList.add('slider-outside-window');
  });

  syncThumbnailRailState(root);
  updateSliderCounter(root);
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

function initCardSliderGestures(scope) {
  var root = getScopeRoot(scope);
  var cardsContainer = root.querySelector('.ys-cards-container');
  var dragThreshold = 42;
  var wheelThreshold = 18;
  var wheelCooldownMs = 260;
  var pointerState = null;
  var wheelLockedUntil = 0;

  if (!cardsContainer || cardsContainer.getAttribute('data-swipe-ready') === '1') {
    return;
  }

  cardsContainer.setAttribute('data-swipe-ready', '1');

  function shouldIgnorePointerStart(target) {
    if (!target || !target.closest) {
      return false;
    }

    return !!target.closest('a, button, input, select, textarea, .ys-tool-card');
  }

  function applySwipe(deltaX, deltaY) {
    if (Math.abs(deltaX) < dragThreshold || Math.abs(deltaX) <= Math.abs(deltaY)) {
      return;
    }

    if (deltaX < 0) {
      goToRelativeCard(1, root);
      return;
    }

    goToRelativeCard(-1, root);
  }

  cardsContainer.addEventListener('touchstart', function(event) {
    if (!event.touches || event.touches.length !== 1) {
      pointerState = null;
      return;
    }

    pointerState = {
      x: event.touches[0].clientX,
      y: event.touches[0].clientY
    };
  }, { passive: true });

  cardsContainer.addEventListener('touchend', function(event) {
    if (!pointerState || !event.changedTouches || !event.changedTouches.length) {
      pointerState = null;
      return;
    }

    var endX = event.changedTouches[0].clientX;
    var endY = event.changedTouches[0].clientY;
    applySwipe(endX - pointerState.x, endY - pointerState.y);
    pointerState = null;
  }, { passive: true });

  cardsContainer.addEventListener('pointerdown', function(event) {
    if (event.pointerType !== 'mouse') {
      return;
    }

    if (shouldIgnorePointerStart(event.target)) {
      pointerState = null;
      return;
    }

    pointerState = {
      x: event.clientX,
      y: event.clientY
    };
  });

  cardsContainer.addEventListener('pointerup', function(event) {
    if (!pointerState || event.pointerType !== 'mouse') {
      pointerState = null;
      return;
    }

    applySwipe(event.clientX - pointerState.x, event.clientY - pointerState.y);
    pointerState = null;
  });

  cardsContainer.addEventListener('pointercancel', function() {
    pointerState = null;
  });

  cardsContainer.addEventListener('wheel', function(event) {
    var now = Date.now();
    var deltaX = event.deltaX;

    if (Math.abs(deltaX) < wheelThreshold && event.shiftKey) {
      deltaX = event.deltaY;
    }

    if (Math.abs(deltaX) < wheelThreshold || now < wheelLockedUntil) {
      return;
    }

    wheelLockedUntil = now + wheelCooldownMs;
    event.preventDefault();
    goToRelativeCard(deltaX > 0 ? 1 : -1, root);
  }, { passive: false });
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
    var blockContainer = getBlockScope(countryLink);

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
    if (suppressMonthClickOnce) {
      suppressMonthClickOnce = false;
      event.preventDefault();
      return;
    }

    var monthScope     = getBlockScope(monthLink);
    var clickContainer = monthLink.closest('.ys-months-container');
    var clickMask      = getMonthMask(clickContainer);

    updateMonthState(monthLink);
    applySliderWindowState(monthScope);

    // Smoothly center the clicked month under the overlay using the shared helper.
    if (clickMask) {
      animateScrollToCenter(clickMask, monthLink, null);
    }

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
    var triggerScope = getBlockScope(panelTrigger);
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
    var closeScope = getBlockScope(closeButton);

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
    var crewCloseScope = getBlockScope(crewCloseButton);

    if (crewPanel) {
      crewPanel.classList.toggle('show');
    }

    syncCrewPanelOpenState(crewCloseScope);
    event.preventDefault();
    return;
  }

  var prevButton = event.target.closest('.ys-card-nav-container .ys-prev');
  if (prevButton) {
    var prevScope = getBlockScope(prevButton);
    goToRelativeCard(-1, prevScope);
    event.preventDefault();
    return;
  }

  var nextButton = event.target.closest('.ys-card-nav-container .ys-next');
  if (nextButton) {
    var nextScope = getBlockScope(nextButton);
    goToRelativeCard(1, nextScope);
    event.preventDefault();
    return;
  }

  var thumbnailButton = event.target.closest('.ys-card-thumbnail');
  if (thumbnailButton) {
    var thumbnailScope = getBlockScope(thumbnailButton);
    var targetCardId = thumbnailButton.getAttribute('data-card-id') || '';
    var targetCard = findCardByIdentifier(thumbnailScope, targetCardId);

    if (targetCard && !targetCard.classList.contains('location-hide') && !targetCard.classList.contains('booked-hide')) {
      setSelectedCard(targetCard, thumbnailScope);
      applySliderWindowState(thumbnailScope);
    }

    event.preventDefault();
    return;
  }

  var clickedCard = event.target.closest('.ys-card');
  if (clickedCard && !clickedCard.classList.contains('location-hide') && !clickedCard.classList.contains('booked-hide')) {
    var cardScope = getBlockScope(clickedCard);

    // Active card clicked — nothing to do.
    if (clickedCard.classList.contains('is-active') || clickedCard.classList.contains('selected')) {
      return;
    }

    // Side card clicked — navigate one step in the card's direction.
    var navStep = 0;
    var cl = clickedCard.classList;
    for (var n = 1; n <= 5; n++) {
      if (cl.contains('is-pos-' + n)) { navStep =  1; break; }
      if (cl.contains('is-neg-' + n)) { navStep = -1; break; }
    }

    if (navStep !== 0) {
      goToRelativeCard(navStep, cardScope);
      return;
    }

    // Fallback: direct selection (e.g. card has no window class yet).
    setSelectedCard(clickedCard, cardScope);
    applySliderWindowState(cardScope);
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
  document.querySelectorAll('.ys-selector-block-container').forEach(function(blockContainer) {
    closeAllCrewPanels(null, blockContainer);
    initCardSliderGestures(blockContainer);

    blockContainer.querySelectorAll('.ys-card').forEach(function(card) {
      syncCardAvailabilityState(card);
    });
  });
  
  applySliderWindowState();
});
