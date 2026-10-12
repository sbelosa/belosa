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
    cutout(img);
    if (host.classList.contains('fl-product-stage')) {
      placeFloor(img, host);
      var page = document.getElementById('fl-library');
      if (page && colour) page.style.setProperty('--fl-glow', colour);
    }
  }
  /* Remove the photo's white studio background: flood fill from the border through near-white pixels only,
     so white parts of the pack itself stay. Works in every browser; multiply blending stays as a fallback. */
  function cutout(img) {
    if (img.dataset.zsCut || !img.naturalWidth) return;
    img.dataset.zsCut = 'pending';
    try {
      var W = Math.min(img.naturalWidth, 640), H = Math.round(img.naturalHeight * W / img.naturalWidth);
      var c = document.createElement('canvas'); c.width = W; c.height = H;
      var x = c.getContext('2d', { willReadFrequently: true }); x.drawImage(img, 0, 0, W, H);
      var data = x.getImageData(0, 0, W, H), d = data.data, seen = new Uint8Array(W * H), stack = [];
      var white = function (p) { var i = p * 4, mn = Math.min(d[i], d[i + 1], d[i + 2]), mx = Math.max(d[i], d[i + 1], d[i + 2]); return d[i + 3] > 0 && mn >= 232 && mx - mn <= 16; };
      for (var px = 0; px < W; px++) { stack.push(px, (H - 1) * W + px); }
      for (var py = 0; py < H; py++) { stack.push(py * W, py * W + W - 1); }
      var cleared = 0;
      while (stack.length) {
        var p = stack.pop(); if (seen[p] || !white(p)) continue;
        seen[p] = 1; cleared++;
        var i = p * 4, mn = Math.min(d[i], d[i + 1], d[i + 2]);
        d[i + 3] = mn >= 246 ? 0 : Math.round(255 * (246 - mn) / 14);
        var col = p % W;
        if (col > 0) stack.push(p - 1); if (col < W - 1) stack.push(p + 1);
        if (p >= W) stack.push(p - W); if (p < W * (H - 1)) stack.push(p + W);
      }
      if (cleared < W * H * 0.08) { img.dataset.zsCut = 'skip'; return; }
      x.putImageData(data, 0, 0);
      c.toBlob(function (blob) {
        if (!blob) { img.dataset.zsCut = 'skip'; return; }
        img.dataset.zsOriginal = img.currentSrc || img.src;
        img.removeAttribute('srcset');
        img.src = URL.createObjectURL(blob);
        img.dataset.zsCut = '1';
      }, 'image/png');
    } catch (e) { img.dataset.zsCut = 'skip'; }
  }
  function scan() {
    document.querySelectorAll(SELECTOR).forEach(function (img) {
      if (img.complete && img.naturalWidth) apply(img);
      else img.addEventListener('load', function () { apply(img); }, { once: true });
    });
  }
  /* Share dialog networks. The referral link comes from the share trigger; the text from the message box. */
  function networks() {
    var box = document.querySelector('[data-fl-networks]'); if (!box) return;
    var dialog = box.closest('dialog'), message = dialog && dialog.querySelector('#fl-message'), status = dialog && dialog.querySelector('[data-fl-status]');
    var current = { url: '', title: '' };
    var mobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
    document.querySelectorAll('[data-fl-compose]').forEach(function (t) {
      t.addEventListener('click', function () { current.url = new URL(t.dataset.flUrl, location.href).href; current.title = t.dataset.flTitle || ''; }, true);
    });
    var more = box.querySelector('[data-fl-net="more"]');
    if (more && navigator.share) more.hidden = false;
    function text() { return (message && message.value.trim()) || current.url; }
    function textWithoutLink() { return text().split(current.url).join('').trim(); }
    function say(msg) { if (status) status.textContent = msg; }
    function copy(value, okMsg) {
      var legacy = function () {
        var t = document.createElement('textarea'); t.value = value; t.setAttribute('readonly', ''); t.style.cssText = 'position:fixed;opacity:0;left:-9999px';
        (dialog || document.body).appendChild(t); t.select(); var ok = false; try { ok = document.execCommand('copy'); } catch (e) {} t.remove();
        say(ok ? okMsg : box.dataset.copyError); return ok;
      };
      if (navigator.clipboard && navigator.clipboard.writeText) return navigator.clipboard.writeText(value).then(function () { say(okMsg); return true; }, function () { return legacy(); });
      return Promise.resolve(legacy());
    }
    box.addEventListener('click', function (e) {
      var a = e.target.closest('[data-fl-net]'); if (!a || !current.url) return;
      var net = a.dataset.flNet, u = encodeURIComponent(current.url);
      if (net === 'facebook') { a.href = 'https://www.facebook.com/sharer/sharer.php?u=' + u; copy(text(), box.dataset.facebookHint); return; }
      if (net === 'telegram') { a.href = 'https://t.me/share/url?url=' + u + '&text=' + encodeURIComponent(textWithoutLink()); return; }
      if (net === 'viber') { a.href = 'viber://forward?text=' + encodeURIComponent(text()); return; }
      if (net === 'email') { a.href = 'mailto:?subject=' + encodeURIComponent(current.title) + '&body=' + encodeURIComponent(text()); return; }
      if (net === 'messenger') {
        if (mobile) { a.href = 'fb-messenger://share/?link=' + u; return; }
        e.preventDefault(); copy(current.url, box.dataset.messengerCopied).then(function () { window.open('https://www.messenger.com/', '_blank', 'noopener'); });
        return;
      }
      if (net === 'link') { e.preventDefault(); copy(current.url, box.dataset.linkCopied); return; }
      if (net === 'more') { e.preventDefault(); navigator.share({ title: current.title, text: textWithoutLink(), url: current.url }).catch(function () {}); }
    });
  }
  function init() { scan(); networks(); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
