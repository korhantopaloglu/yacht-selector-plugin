document.addEventListener('DOMContentLoaded', function() {
  var selectors = document.querySelectorAll('.ys-yacht-selector');

  selectors.forEach(function(selector) {
    var payloadNode = selector.querySelector('.ys-selector-data');
    if (!payloadNode) {
      return;
    }

    try {
      selector.ysPayload = JSON.parse(payloadNode.textContent || '{}');
    } catch (error) {
      selector.ysPayload = { items: [], months: [], countries: [] };
    }
  });
});
