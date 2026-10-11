(() => {
 'use strict';
 const nav = document.querySelector('body.fcc-partner-mode .fp-bottom-nav');
 const content = document.querySelector('body.fcc-partner-mode > .app-container');
 if (!nav || !content || document.querySelector('.fp-mobile-shell')) return;

 // The footer occupies a layout row outside the scrollable content. In particular,
 // iOS must not position a fixed footer against the scrolling document viewport.
 const shell = document.createElement('div');
 shell.className = 'fp-mobile-shell';
 content.before(shell);
 shell.append(content, nav);
 document.documentElement.classList.add('fp-mobile-layout');

 const mobile = window.matchMedia('(max-width: 800px)');
 const scroller = content.querySelector('.admin-content') || content;
 const key = 'fcc.navigation.scroll:' + location.pathname + location.search;
 const savePosition = () => {
  if (!mobile.matches) return;
  try { sessionStorage.setItem(key, String(scroller.scrollTop)); } catch {}
 };
 const restorePosition = () => {
  if (!mobile.matches) return;
  if (location.hash) {
   try { document.getElementById(decodeURIComponent(location.hash.slice(1)))?.scrollIntoView(); } catch {}
  } else if (performance.getEntriesByType('navigation')[0]?.type === 'back_forward') {
   try { scroller.scrollTop = Number(sessionStorage.getItem(key)) || 0; } catch {}
  }
 };
 window.addEventListener('pagehide', savePosition);
 window.addEventListener('pageshow', event => { if (!event.persisted) restorePosition(); });
 // Match the normal document scroll position when rotating across the breakpoint.
 let lastMobileTop = 0;
 scroller.addEventListener('scroll', () => { lastMobileTop = scroller.scrollTop; }, { passive: true });
 mobile.addEventListener('change', () => {
  if (mobile.matches) scroller.scrollTop = window.scrollY || lastMobileTop;
  else window.scrollTo(0, lastMobileTop);
 });
})();
