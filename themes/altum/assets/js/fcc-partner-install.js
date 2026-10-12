/* Keep Partner offline handling separate from existing public-card PWAs.
   FC-2026-10-12: the install page shows one clear action per device (one tap install, Safari steps,
   open in a real browser, or a QR code on a computer) and a finished state inside the installed app. */
(() => {
 'use strict';
 const page=document.querySelector('[data-install-page]');
 const button=document.querySelector('[data-install]');
 const howto=document.querySelector('[data-install-howto]');
 const status=document.querySelector('[data-install-status]');
 const mode=window.matchMedia('(display-mode: standalone)');
 const standalone=()=>mode.matches||navigator.standalone===true;
 let copy={};try{copy=JSON.parse(page?.dataset.installCopy||'{}');}catch{}
 const t=(key,fallback)=>copy[key]||(window.fccT?window.fccT(fallback):fallback);
 const ua=navigator.userAgent||'';
 const ios=/iPhone|iPad|iPod/.test(ua)||(/Macintosh/.test(ua)&&navigator.maxTouchPoints>1);
 const android=/Android/.test(ua);
 const inApp=/FBAN|FBAV|FB_IAB|Instagram|WhatsApp|Line\/|Snapchat|musical_ly|TikTok|LinkedInApp|Messenger|; wv\)/.test(ua);
 const platform=ios?'ios':android?'android':null;
 let deferred=null;
 const show=(selector,visible)=>{const el=page?.querySelector(selector);if(el)el.hidden=!visible;};
 const showStatus=text=>{if(status){status.hidden=false;status.textContent=text;}};
 const update=()=>{
  const installed=standalone();
  if(button)button.hidden=installed||!deferred;
  if(howto)howto.hidden=installed||!!deferred;
  if(!page)return;
  show('[data-install-done]',installed);
  show('[data-install-guide]',!installed);
  show('[data-install-inapp]',!installed&&inApp);
  show('[data-install-desktop]',!installed&&!platform);
  const open=page.querySelector('[data-install-open]');
  if(open)open.hidden=installed;
 };
 window.addEventListener('beforeinstallprompt',event=>{event.preventDefault();deferred=event;update();});
 button?.addEventListener('click',async()=>{
  if(!deferred||standalone())return;
  const prompt=deferred;deferred=null;button.disabled=true;
  try{await prompt.prompt();const choice=await prompt.userChoice;if(choice.outcome==='accepted')showStatus(t('accepted','Instalacija je potvrđena. Otvori FCC Partner s početnog zaslona.'));}
  catch{showStatus(t('manual','Otvori izbornik preglednika i odaberi dodavanje na početni zaslon.'));}
  finally{button.disabled=false;update();}
 });
 howto?.addEventListener('click',event=>{
  const target=document.getElementById('install-steps');if(!target)return;
  event.preventDefault();target.scrollIntoView({behavior:window.matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth',block:'start'});
 });
 window.addEventListener('appinstalled',()=>{deferred=null;update();showStatus(t('done','FCC Partner je instaliran. Otvori njegovu ikonu na početnom zaslonu.'));});
 mode.addEventListener?.('change',update);
 if(page&&platform){
  // The visitor's own device comes first; the other one stays available under a fold.
  const other=page.querySelector('[data-install-other]');
  const content=page.querySelector('[data-install-other-content]');
  page.querySelectorAll('[data-install-device]').forEach(section=>{if(section.dataset.installDevice!==platform&&content)content.append(section);});
  if(other)other.hidden=false;
 }
 update();
 const register = async () => {
  if (!('serviceWorker' in navigator) || !window.isSecureContext) return;
  // A worker must come from the page's own origin, also when the site is opened under another host name.
  const base = location.origin + (new URL(document.querySelector('base')?.href || location.origin + '/').pathname);
  const workerUrl = new URL('fcc-partner-sw.js', base).href;
  // No trailing slash: /partner itself is the manifest's start URL.
  const scope = new URL('partner', base).href;
  const rootScope = new URL('.', base).href;
  const registrations = await navigator.serviceWorker.getRegistrations();
  const workers = registration => [registration.active, registration.waiting, registration.installing].filter(Boolean);
  const own = registration => workers(registration).length > 0 && workers(registration).every(worker => worker.scriptURL === workerUrl);
  const conflict = registrations.some(registration => registration.scope.startsWith(scope) && !own(registration));
  if (conflict) throw new Error('Existing worker in Partner scope');
  await navigator.serviceWorker.register(workerUrl, {scope, updateViaCache: 'none'});
  // Migrate only our earlier local root worker. Never unregister another FCC PWA.
  for (const registration of registrations) {
   if (registration.scope === rootScope && own(registration)) await registration.unregister();
  }
 };
 // Without a worker the steps above still work, so a failure is only logged.
 register().catch(error => { if (window.console) console.warn('FCC install worker:', error && error.message); });
})();
