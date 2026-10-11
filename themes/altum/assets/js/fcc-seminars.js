(() => {
 'use strict';
 const ticket = document.querySelector('[data-ticket-redirect]');
 if (ticket) {
  const url = new URL(ticket.href);
  if (url.protocol === 'https:') window.setTimeout(() => window.location.assign(url.href), 6500);
 }
})();
