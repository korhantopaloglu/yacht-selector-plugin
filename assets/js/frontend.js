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

  // Suspend infinite-rail normalisation for the duration of the animation.
  // If the snap target is in a clone track (e.g. track-3's January when
  // swiping past December), each animation frame would otherwise be yanked
  // back into track-2 by the scroll handler, causing visible ping-pong.
  // Callers should explicitly call normalizeMonthRailScroll() once when the
  // animation completes — the resulting jump is invisible because the clones
  // are pixel-identical.
  mask.classList.add('is-snap-animating');

  function step(ts) {
    if (startTs === null) { startTs = ts; }
    var t = Math.min((ts - startTs) / MONTH_SNAP_DURATION_MS, 1);
    mask.scrollLeft = fromScroll + dist * (1 - Math.pow(1 - t, 3)); // easeOutCubic

    if (t < 1) {
      rafId = requestAnimationFrame(step);
    } else {
      rafId = null;
      mask.scrollLeft = toScroll;
      mask.classList.remove('is-snap-animating');
      if (onComplete) { onComplete(); }
    }
  }

  rafId = requestAnimationFrame(step);

  return function cancel() {
    if (rafId !== null) { cancelAnimationFrame(rafId); rafId = null; }
    mask.classList.remove('is-snap-animating');
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

// Silently re-centre scrollLeft back into the track-2 (original) zone.
// Uses getBoundingClientRect so the result is correct regardless of layout gaps.
// threshold = max(15 % of trackWidth, full mask width) — wide enough to cover
// legitimate centering positions for the first and last months of track-2.
//
// The jump is made invisible by:
//   1. Adding .is-normalizing-scroll (scroll-behavior: auto !important) before the
//      direct scrollLeft assignment — cancels any in-flight CSS smooth scroll.
//   2. Removing the class in the next RAF so subsequent intentional smooth
//      animations (snap, click-center) are unaffected.
//
// Returns true if a jump was made.
function normalizeMonthRailScroll(mask, monthContainer) {
  if (!mask || !monthContainer) { return false; }

  var track2 = monthContainer.querySelector('.ys-months-track[data-track-role="original"]');
  if (!track2) { return false; }

  var trackWidth = track2.offsetWidth;
  if (!trackWidth) { return false; }

  // track2Start: left edge of track-2 in scroll-container coordinates.
  var maskRect    = mask.getBoundingClientRect();
  var track2Rect  = track2.getBoundingClientRect();
  var track2Start = (track2Rect.left - maskRect.left) + mask.scrollLeft;

  // Threshold must cover any legitimate centering position (first/last month).
  var threshold = Math.max(trackWidth * 0.15, mask.clientWidth);
  var sl        = mask.scrollLeft;

  if (sl >= track2Start - threshold && sl <= track2Start + trackWidth + threshold) {
    return false; // already in the safe zone
  }

  // Disable CSS smooth scroll for the instant jump so any in-flight smooth
  // animation (e.g. from centerMonthItemInMask) cannot animate the transition.
  mask.classList.add('is-normalizing-scroll');

  // Wrap to the equivalent offset within track-2.
  var offset      = ((sl - track2Start) % trackWidth + trackWidth) % trackWidth;
  mask.scrollLeft = track2Start + offset;

  // Restore normal scroll behaviour once the browser has processed the assignment.
  requestAnimationFrame(function() {
    mask.classList.remove('is-normalizing-scroll');
  });

  return true;
}

function bindInfiniteMonthScroll(monthContainer) {
  var mask = getMonthMask(monthContainer);
  if (!mask) { return; }

  // isNormalizing prevents re-entry: the scroll event fired by our own scrollLeft
  // assignment inside normalizeMonthRailScroll must not trigger another cycle.
  var isNormalizing = false;

  mask.addEventListener('scroll', function() {
    if (isNormalizing) { return; }
    // Skip during a snap animation: targets may legitimately live in a clone
    // track. The snap caller normalises once when the animation completes.
    if (mask.classList.contains('is-snap-animating')) { return; }
    isNormalizing = true;

    // Normalise SYNCHRONOUSLY — fires before the browser's next paint so the
    // clone position is never rendered even for a single frame.
    normalizeMonthRailScroll(mask, monthContainer);

    // Reset the guard after one RAF so legitimate post-normalisation scroll
    // events (e.g. from the snap animation) are processed normally.
    requestAnimationFrame(function() { isNormalizing = false; });
  });
}

function bindMonthDragScroll(monthContainer) {
  var mask      = getMonthMask(monthContainer);
  var maskInner = monthContainer && monthContainer.querySelector
    ? monthContainer.querySelector('.ys-months-mask-inner')
    : null;

  if (!mask || !maskInner || mask.getAttribute('data-month-drag-ready') === '1') {
    return;
  }

  mask.setAttribute('data-month-drag-ready', '1');
  mask.classList.add('is-drag-scroll-ready');

  var FRICTION         = 0.94;
  var MIN_VELOCITY     = 0.25;  // px/ms
  var SAMPLE_WINDOW_MS = 100;   // ms — rolling velocity window

  var isPointerDown     = false;
  var isDragging        = false;
  var activePointerId   = null;
  var startX            = 0;    // captured at pointerdown; used for threshold check only
  var startY            = 0;
  var lastX             = 0;    // updated each pointermove; used for per-frame delta
  var dragCaptureActive = false;
  var velSamples        = [];   // { t, x }
  var momentumRafId     = null;
  var cancelSnapFn      = null;

  function cancelAnimations() {
    if (momentumRafId !== null) { cancelAnimationFrame(momentumRafId); momentumRafId = null; }
    if (cancelSnapFn  !== null) { cancelSnapFn(); cancelSnapFn = null; }
  }

  // Find the best snap target in the given scroll direction.
  // Searches ALL three track clones — at year boundaries the next forward
  // month exists in track-3 (e.g. January after December) and the next
  // backward month exists in track-1 (e.g. December before January). Picking
  // is purely visual (DOM centre vs. mask centre) — never compares calendar
  // month numbers, so 12 → 1 across the boundary is treated as a normal step.
  //
  // direction > 0 : scrollLeft was increasing (content moved left).
  //   The "next" uncentered month is to the RIGHT of mask centre.
  //   Pick the item with the smallest non-negative diff.
  //
  // direction < 0 : scrollLeft was decreasing (content moved right).
  //   The "next" uncentered month is to the LEFT of mask centre.
  //   Pick the item with the smallest non-positive diff (abs).
  //
  // direction === 0 : no clear direction — pick the nearest item by |diff|.
  //
  // ±1 px slack around mask centre lets items that land virtually on-centre
  // qualify for either direction, avoiding "wrong side" snaps for tiny offsets.
  //
  // The returned element may live in track-1, track-2 or track-3. The snap
  // caller is responsible for resolving it to the track-2 canonical copy
  // AFTER the snap animation completes (so state stays anchored to track-2
  // while the visible scroll travel remains continuous across year boundaries).
  function findSnapTarget(direction) {
    var maskRect    = mask.getBoundingClientRect();
    var maskCenterX = maskRect.left + maskRect.width / 2;
    var items       = maskInner.querySelectorAll('a.ys-month[data-month]');

    var best     = null;
    var bestDist = Infinity;

    for (var i = 0; i < items.length; i++) {
      var r    = items[i].getBoundingClientRect();
      var cx   = r.left + r.width / 2;
      var diff = cx - maskCenterX; // positive = item is right of mask centre

      var isCandidate, dist;
      if (direction > 0) {
        isCandidate = diff >= -1;
        dist        = Math.max(0, diff);
      } else if (direction < 0) {
        isCandidate = diff <= 1;
        dist        = Math.max(0, -diff);
      } else {
        isCandidate = true;
        dist        = Math.abs(diff);
      }

      if (isCandidate && dist < bestDist) { bestDist = dist; best = items[i]; }
    }

    // Defensive fallback: should never trigger for a non-empty rail because
    // direction-aware filters always have at least one matching clone.
    if (!best) {
      bestDist = Infinity;
      for (var j = 0; j < items.length; j++) {
        var rj  = items[j].getBoundingClientRect();
        var cxj = rj.left + rj.width / 2;
        var dj  = Math.abs(cxj - maskCenterX);
        if (dj < bestDist) { bestDist = dj; best = items[j]; }
      }
    }

    return best || null;
  }

  // Resolve any month item (track-1/2/3) to its canonical track-2 copy via
  // data-month-key. Returns the original if no track-2 match exists.
  function resolveToTrack2(item) {
    if (!item) { return null; }
    var key = item.getAttribute('data-month-key') || item.getAttribute('data-month') || '';
    if (!key) { return item; }
    var copy = monthContainer.querySelector(
      '.ys-months-track[data-track-role="original"] .ys-month[data-month-key="' + key + '"]'
    );
    return copy || item;
  }

  function selectSnappedMonth(monthItem) {
    if (!monthItem) { return; }
    var scope = getBlockScope(monthItem);
    updateMonthState(monthItem, false);
    applySliderWindowState(scope);
  }

  function snapToItem(targetItem) {
    if (!targetItem) { return; }
    cancelSnapFn = animateScrollToCenter(mask, targetItem, function() {
      cancelSnapFn = null;

      // If the snap landed in a clone track (track-1 "pre" or track-3 "post"),
      // perform an instant, invisible same-offset teleport back into track-2.
      //
      // Why not use normalizeMonthRailScroll() here?
      // Because `toScroll` for a nearby clone item (e.g. track-3's January
      // when December was centred) is still within normalise's safe-zone
      // threshold, so that function would be a no-op. We need an exact,
      // targeted correction based on which track the snap element actually
      // lives in.
      //
      // The delta is computed from getBoundingClientRect so it works
      // regardless of flex gaps between tracks.
      var trackRole   = targetItem.getAttribute('data-track-role');
      if (trackRole && trackRole !== 'original') {
        var track2El    = monthContainer.querySelector('.ys-months-track[data-track-role="original"]');
        var targetTrack = targetItem.closest('.ys-months-track');
        if (track2El && targetTrack) {
          // Visual distance between the two tracks in the current viewport.
          // Subtracting it from scrollLeft re-anchors the view to the
          // equivalent position inside track-2.
          var delta = targetTrack.getBoundingClientRect().left -
                      track2El.getBoundingClientRect().left;
          if (Math.abs(delta) > 1) {
            mask.classList.add('is-normalizing-scroll');
            mask.scrollLeft -= delta;
            requestAnimationFrame(function() {
              mask.classList.remove('is-normalizing-scroll');
            });
          }
        }
      }

      // After the teleport the track-2 canonical copy is now visually
      // centred. Pass it to selectSnappedMonth so updateMonthState's
      // comfort-zone check finds the item in view and fires no extra scroll.
      selectSnappedMonth(resolveToTrack2(targetItem));
    });
  }

  function calcReleaseVelocity() {
    if (velSamples.length < 2) { return 0; }
    var now    = Date.now();
    var recent = velSamples.filter(function(s) { return now - s.t <= SAMPLE_WINDOW_MS; });
    if (recent.length < 2) { return 0; }
    var first = recent[0];
    var last  = recent[recent.length - 1];
    var dt    = last.t - first.t;
    if (dt <= 0) { return 0; }
    // Dragging right (+x) decreases scrollLeft — negate.
    return -(last.x - first.x) / dt;
  }

  function startMomentum(velocity) {
    var lastTs = null;

    function momentumStep(ts) {
      if (lastTs === null) { lastTs = ts; momentumRafId = requestAnimationFrame(momentumStep); return; }

      var dt        = ts - lastTs;
      lastTs        = ts;
      velocity     *= Math.pow(FRICTION, dt / 16.667);

      var nextLeft  = mask.scrollLeft + velocity * dt;
      var maxScroll = Math.max(0, mask.scrollWidth - mask.clientWidth);

      if (nextLeft <= 0 || nextLeft >= maxScroll) {
        // Hard boundary — clamp, normalise, snap (direction from final velocity).
        mask.scrollLeft = Math.max(0, Math.min(nextLeft, maxScroll));
        momentumRafId   = null;
        normalizeMonthRailScroll(mask, monthContainer);
        snapToItem(findSnapTarget(velocity));
        return;
      }

      mask.scrollLeft = nextLeft;
      // scroll event fires here and schedules normalisation via bindInfiniteMonthScroll.

      if (Math.abs(velocity) > MIN_VELOCITY) {
        momentumRafId = requestAnimationFrame(momentumStep);
      } else {
        momentumRafId = null;
        normalizeMonthRailScroll(mask, monthContainer);
        snapToItem(findSnapTarget(velocity)); // velocity still carries its sign
      }
    }

    momentumRafId = requestAnimationFrame(momentumStep);
  }

  function handleDragEnd(wasDragging) {
    if (!isPointerDown) { return; }

    if (wasDragging) {
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

    if (!wasDragging) { return; }

    if (Math.abs(velocity) > MIN_VELOCITY) {
      startMomentum(velocity);
    } else {
      normalizeMonthRailScroll(mask, monthContainer);
      snapToItem(findSnapTarget(velocity)); // velocity carries drag-release direction
    }
  }

  maskInner.addEventListener('pointerdown', function(event) {
    if (event.pointerType === 'touch' || event.button !== 0) { return; }

    cancelAnimations();
    isPointerDown     = true;
    isDragging        = false;
    dragCaptureActive = false;
    activePointerId   = event.pointerId;
    startX            = event.clientX;
    startY            = event.clientY;
    lastX             = event.clientX;  // per-frame delta anchor
    velSamples        = [{ t: Date.now(), x: event.clientX }];
  });

  maskInner.addEventListener('pointermove', function(event) {
    if (!isPointerDown || (activePointerId !== null && event.pointerId !== activePointerId)) { return; }

    var now = Date.now();
    velSamples.push({ t: now, x: event.clientX });
    while (velSamples.length > 1 && now - velSamples[0].t > SAMPLE_WINDOW_MS) {
      velSamples.shift();
    }

    var deltaX = event.clientX - startX;
    var deltaY = event.clientY - startY;

    if (!isDragging) {
      if (Math.abs(deltaX) < MONTH_DRAG_THRESHOLD || Math.abs(deltaX) < Math.abs(deltaY)) { return; }

      isDragging = true;
      mask.classList.add('is-month-dragging');
      lastX = event.clientX; // anchor for per-frame deltas from this point

      if (maskInner.setPointerCapture) {
        try { maskInner.setPointerCapture(activePointerId); dragCaptureActive = true; }
        catch (e) { dragCaptureActive = false; }
      }
      return; // zero scroll on the frame drag is confirmed
    }

    // Per-frame relative delta — immune to normalisation jumps mid-drag.
    var dx = event.clientX - lastX;
    lastX  = event.clientX;
    mask.scrollLeft -= dx;
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

/**
 * Compare resolved img[src] with a target URL (handles absolute vs relative).
 *
 * @param {HTMLImageElement} img Image element.
 * @param {string} url Target URL.
 * @returns {boolean}
 */
function imageSrcMatches(img, url) {
  if (!img || !url) {
    return false;
  }

  var current = img.getAttribute('src') || '';

  if (current === url) {
    return true;
  }

  try {
    var absCurrent = new URL(current, window.location.href).href;
    var absTarget = new URL(url, window.location.href).href;
    return absCurrent === absTarget;
  } catch (err) {
    return false;
  }
}

/**
 * Active cards load full-size images asynchronously; side cards use thumbnails (with blur via CSS).
 *
 * @param {ParentNode|Document|null} scope Scope root or document.
 * @returns {void}
 */
function updateCardImages(scope) {
  getCards(scope).forEach(function(card) {
    var images = card.querySelectorAll('.ys-card-image');

    if (!images.length) {
      return;
    }

    images.forEach(function(image) {
      var fullSrc = image.getAttribute('data-full-src') || '';
      var thumbSrc = image.getAttribute('data-thumb-src') || '';

      if (!thumbSrc && fullSrc) {
        thumbSrc = fullSrc;
      }

      if (!fullSrc) {
        fullSrc = thumbSrc;
      }

      if (card.classList.contains('is-active')) {
        if (!fullSrc) {
          return;
        }

        if (imageSrcMatches(image, fullSrc)) {
          card.classList.add('is-full-image-loaded');
          return;
        }

        if (!thumbSrc || thumbSrc === fullSrc) {
          image.src = fullSrc;
          card.classList.add('is-full-image-loaded');
          return;
        }

        var gen = String(parseInt(image.getAttribute('data-ys-img-gen') || '0', 10) + 1);
        image.setAttribute('data-ys-img-gen', gen);

        var preload = new Image();
        preload.decoding = 'async';

        preload.onload = function() {
          if (image.getAttribute('data-ys-img-gen') !== gen || !card.classList.contains('is-active')) {
            return;
          }

          image.src = fullSrc;
          card.classList.add('is-full-image-loaded');
        };

        preload.onerror = function() {
          if (image.getAttribute('data-ys-img-gen') !== gen || !card.classList.contains('is-active')) {
            return;
          }

          image.src = fullSrc;
          card.classList.remove('is-full-image-loaded');
        };

        preload.src = fullSrc;
      } else if (thumbSrc && !imageSrcMatches(image, thumbSrc)) {
        image.src = thumbSrc;
        card.classList.remove('is-full-image-loaded');
      }
    });
  });
}

/**
 * Reveal the selector after initial JS layout (slider, months, images).
 *
 * @param {Element|null} scope Block container.
 * @returns {void}
 */
function markSelectorReady(scope) {
  var root = scope && scope.classList && scope.classList.contains('ys-selector-block-container')
    ? scope
    : null;

  if (!root) {
    return;
  }

  root.classList.remove('is-loading');
  root.classList.add('is-ready');

  var loader = root.querySelector('.ys-selector-loader');

  if (loader) {
    loader.setAttribute('aria-hidden', 'true');
    loader.removeAttribute('aria-busy');
  }
}

/**
 * Initialise one selector block (cards, months, images, ready state).
 *
 * @param {Element} blockContainer Root `.ys-selector-block-container`.
 * @returns {void}
 */
function initSelectorBlock(blockContainer) {
  if (!blockContainer) {
    return;
  }

  var monthContainer = blockContainer.querySelector('.ys-months-container');

  if (monthContainer) {
    initializeMonthInfiniteScroll(monthContainer);
  }

  closeAllCrewPanels(null, blockContainer);
  initCardSliderGestures(blockContainer);

  blockContainer.querySelectorAll('.ys-card').forEach(function(card) {
    syncCardAvailabilityState(card);
  });

  applySliderWindowState(blockContainer);
  updateCardImages(blockContainer);

  window.requestAnimationFrame(function() {
    window.requestAnimationFrame(function() {
      markSelectorReady(blockContainer);
    });
  });
}

window.ysYachtSelectorInitBlock = initSelectorBlock;

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
    updateSliderCounter(root);
    updateCardImages(root);
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
  updateCardImages(root);
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

    var clickContainer = monthLink.closest('.ys-months-container');
    var clickMask      = getMonthMask(clickContainer);

    // Normalise to track-2 zone first so the canonical item is in view.
    if (clickMask) { normalizeMonthRailScroll(clickMask, clickContainer); }

    // Resolve the clicked month to the track-2 canonical copy for consistent centering.
    var clickMonthKey = monthLink.getAttribute('data-month-key') || monthLink.getAttribute('data-month') || '';
    var track2Target  = clickMonthKey && clickContainer
      ? clickContainer.querySelector('.ys-months-track[data-track-role="original"] .ys-month[data-month-key="' + clickMonthKey + '"]')
      : null;
    var targetItem = track2Target || monthLink;

    var monthScope = getBlockScope(monthLink);
    updateMonthState(targetItem);
    applySliderWindowState(monthScope);

    if (clickMask) {
      animateScrollToCenter(clickMask, targetItem, null);
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
      crewPanel.classList.remove('show');
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
  document.querySelectorAll('.ys-selector-block-container').forEach(initSelectorBlock);
});
