/* FCC Živi sloj: tint each product stage with the packaging's own colour.
   Reads a 48×48 sample of the already loaded photo; on any failure the topic colour from CSS stays. */
(function () {
  var SELECTOR = '.fl-product-stage img, .fl-product-image img, .fl-related-card img';
  function glowOf(img) {
    try {
      var n = 48, c = document.createElement('canvas'); c.width = c.height = n;
      var x = c.getContext('2d', { willReadFrequently: true }); x.drawImage(img, 0, 0, n, n);
      var d = x.getImageData(0, 0, n, n).data, r = 0, g = 0, b = 0, w = 0;
      for (var i = 0; i < d.length; i += 4) {
        var R = d[i], G = d[i + 1], B = d[i + 2], max = Math.max(R, G, B), min = Math.min(R, G, B);
        var sat = max ? (max - min) / max : 0;
        if (d[i + 3] < 128 || max > 248 && sat < .08 || sat < .18) continue;
        var weight = sat * sat; r += R * weight; g += G * weight; b += B * weight; w += weight;
      }
      if (w < .6) return null;
      return 'rgb(' + Math.round(r / w) + ',' + Math.round(g / w) + ',' + Math.round(b / w) + ')';
    } catch (e) { return null; }
  }
  /* Lowest non-white row of the photo, as a fraction of its height, so the floor shadow touches the pack. */
  function floorOf(img) {
    try {
      var n = 64, c = document.createElement('canvas'); c.width = c.height = n;
      var x = c.getContext('2d', { willReadFrequently: true }); x.drawImage(img, 0, 0, n, n);
      var d = x.getImageData(0, 0, n, n).data;
      for (var y = n - 1; y >= 0; y--) for (var col = 0; col < n; col++) {
        var i = (y * n + col) * 4;
        if (d[i + 3] > 128 && Math.min(d[i], d[i + 1], d[i + 2]) < 236) return (y + 1) / n;
      }
    } catch (e) {}
    return null;
  }
  function placeFloor(img, host) {
    var frame = img.closest('.fl-hero-image'), frac = floorOf(img);
    if (!frame || frac === null) return;
    var set = function () {
      var top = img.offsetTop + img.offsetHeight * frac;
      if (img.offsetHeight) frame.style.setProperty('--zs-floor', Math.round(top - 11) + 'px');
    };
    set(); window.addEventListener('resize', set, { passive: true });
  }
  function apply(img) {
    var host = img.closest('.fl-product-stage, .fl-product-image, .fl-related-card');
    if (!host || host.dataset.zsGlow) return;
    var colour = glowOf(img);
    host.dataset.zsGlow = colour ? '1' : '0';
    if (colour) host.style.setProperty('--fl-glow', colour);
    if (host.classList.contains('fl-product-stage')) {
      placeFloor(img, host);
      var page = document.getElementById('fl-library');
      if (page && colour) page.style.setProperty('--fl-glow', colour);
    }
  }
  function scan() {
    document.querySelectorAll(SELECTOR).forEach(function (img) {
      if (img.complete && img.naturalWidth) apply(img);
      else img.addEventListener('load', function () { apply(img); }, { once: true });
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', scan); else scan();
})();
