/* Keep Partner offline handling separate from existing public-card PWAs. */
(() => {
 'use strict';
 const page=document.querySelector('[data-install-page]');
 const button=document.querySelector('[data-install]');
 const status=document.querySelector('[data-install-status]');
 const mode=window.matchMedia('(display-mode: standalone)');
 const standalone=()=>mode.matches||navigator.standalone===true;
 let deferred=null;
 const showStatus=text=>{if(status){status.hidden=false;status.textContent=text;}};
 const update=()=>{
  if(button)button.hidden=standalone()||!deferred;
  if(!page)return;
  const guide=page.querySelector('[data-install-guide]');
  if(guide)guide.hidden=standalone();
  const open=page.querySelector('[data-install-open]');
  if(open)open.textContent=standalone()?window.fccT('Otvori moj FCC'):window.fccT('Nastavi u pregledniku');
  if(standalone())showStatus(window.fccT('FCC je otvoren kao aplikacija. Sve je spremno za rad.'));
 };
 window.addEventListener('beforeinstallprompt',event=>{event.preventDefault();deferred=event;update();});
 button?.addEventListener('click',async()=>{
  if(!deferred||standalone())return;
  const prompt=deferred;deferred=null;button.disabled=true;
  try{await prompt.prompt();const choice=await prompt.userChoice;if(choice.outcome==='accepted')showStatus(window.fccT('Instalacija je potvrđena. Otvori FCC Partner s početnog zaslona.'));}
  catch{showStatus(window.fccT('Otvori izbornik preglednika i odaberi dodavanje na početni zaslon.'));}
  finally{button.disabled=false;update();}
 });
 window.addEventListener('appinstalled',()=>{deferred=null;update();showStatus(window.fccT('FCC Partner je instaliran. Otvori njegovu ikonu na početnom zaslonu.'));});
 mode.addEventListener?.('change',update);
 if(page){
  const ua=navigator.userAgent||'';
  const ios=/iPhone|iPad|iPod/.test(ua)||(/Macintosh/.test(ua)&&navigator.maxTouchPoints>1);
  const platform=ios?'ios':/Android/.test(ua)?'android':null;
  if(platform){
   const other=page.querySelector('[data-install-other]');
   const content=page.querySelector('[data-install-other-content]');
   page.querySelectorAll('[data-install-device]').forEach(section=>{if(section.dataset.installDevice!==platform&&content)content.append(section);});
   if(other)other.hidden=false;
  }
 }
 update();
 const register = async () => {
  if (!('serviceWorker' in navigator) || !window.isSecureContext) return;
  const base = document.querySelector('base')?.href || location.origin + '/';
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
 register().catch(() => {
  const status = document.querySelector('[data-install-status]');
  if (status) {
   status.hidden = false;
   status.textContent = window.fccT('Instalaciju trenutačno nije moguće pripremiti. Nastavi u pregledniku; za pomoć se javi podršci.');
  }
 });
})();
