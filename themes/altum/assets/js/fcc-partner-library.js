/* Secondary library navigation stays available without pushing articles below long lists. */
(() => {
 'use strict';
 if (!document.body.classList.contains('fcc-library-mode')) return;
 const mobile = window.matchMedia('(max-width: 991.98px)');
 document.querySelectorAll('.fcc-blog-sidebar-col .fcc-widget-card .card-body').forEach(body => {
  const heading = body.querySelector(':scope > .fcc-widget-title');
  const list = body.querySelector(':scope > .fcc-widget-list');
  if (!heading || !list) return;
  const details = document.createElement('details'); details.className = 'fcc-library-disclosure';
  const summary = document.createElement('summary'); summary.textContent = heading.textContent;
  details.append(summary, list); heading.replaceWith(details);
  details.open = !mobile.matches;
  mobile.addEventListener('change', () => { details.open = !mobile.matches; });
 });
})();
