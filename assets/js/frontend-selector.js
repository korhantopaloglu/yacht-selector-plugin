document.addEventListener('click', function(event) {
  var link = event.target.closest('.ys-selector-block-container a[href="#"]');
  if (!link) {
    return;
  }

  event.preventDefault();
});
