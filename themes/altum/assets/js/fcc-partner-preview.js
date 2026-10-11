/* Local interactive viewer. It frames the real application; it does not clone data. */
(() => {
 'use strict';
 const screen=document.querySelector('#pv-screen'), frame=document.querySelector('#pv-frame');
 const stage=document.querySelector('#pv-stage'), space=document.querySelector('#pv-space');
 const select=document.querySelector('#pv-device'), current=document.querySelector('#pv-current');
 const title=document.querySelector('#pv-mode-title'), description=document.querySelector('#pv-mode-description');
 const devices={iphone:[390,844,'phone'],android:[360,800,'android'],large:[430,932,'phone'],desktop:[1280,820,'desktop']};
 const copy={
  app:[window.fccT('Aplikacija za svakodnevni rad'),window.fccT('Kartica, kontakti, dijeljenje i dnevni zadaci. Ostale alate pronađi pod Više, u istom radnom prostoru.')],
  web:[window.fccT('Ovako izgleda FCC nakon prijave'),window.fccT('Na računalu imaš bočni izbornik i više prostora. Podaci i radnje isti su kao u aplikaciji; napredni web alati uključuju Funnel.')],
  public:[window.fccT('Javni FCC ostaje tvoja izložba'),window.fccT('Ovdje je lokalna kopija naslovnice, bloga i javnih kartica. Novi radni prostor uređuje dio nakon prijave; javni sadržaj i preporuke ostaju povezani.')]
 };
 let mode='app',dirty=false,fullSize=false;
 function size(){
  const [w,h,shape]=devices[select.value];
  screen.style.width=w+'px';screen.style.height=h+'px';frame.dataset.shape=shape;
  const outerW=w+(shape==='desktop'?10:24),outerH=h+(shape==='desktop'?22:76);
  frame.style.width=outerW+'px';
  const pad=parseFloat(getComputedStyle(stage).paddingLeft)*2;
  const scale=fullSize?1:Math.min(1,(stage.clientWidth-pad)/outerW,(stage.clientHeight-pad)/outerH);
  space.style.width=outerW*scale+'px';space.style.height=outerH*scale+'px';frame.style.transform=`scale(${scale})`;
  stage.style.justifyContent=fullSize?'flex-start':'center';stage.style.alignItems=fullSize?'flex-start':'center';
  document.querySelector('#pv-size').textContent=`${w} × ${h} · ${shape==='desktop'?window.fccT('računalo'):'mobilni prikaz'}${scale<1?window.fccT(' · prilagođeno prozoru'):fullSize?window.fccT(' · 100 % · pomakni prikaz za ostali sadržaj'):''}`;
 }
 function resetZoom(){
  fullSize=false;const button=document.querySelector('#pv-zoom');button.setAttribute('aria-pressed','false');button.textContent=window.fccT('Izvorna veličina');button.title=window.fccT('Povećaj na 100 % (pomicanje unutar okvira)');
 }
 function applyMode(){
  screen.dataset.fccPreviewMode=mode==='app'?'app':'web';
  try{screen.contentWindow.dispatchEvent(new Event('fcc-preview-mode'));}catch{}
  document.querySelectorAll('[data-mode]').forEach(button=>button.setAttribute('aria-pressed',String(button.dataset.mode===mode)));
  document.querySelectorAll('[data-audience]').forEach(button=>button.hidden=button.dataset.audience!==(mode==='public'?'public':'member'));
  title.textContent=copy[mode][0];description.textContent=copy[mode][1];
  screen.title=mode==='app'?window.fccT('FCC mobilna aplikacija, interaktivni lokalni pregled'):mode==='web'?window.fccT('FCC web nakon prijave — lokalni pregled'):window.fccT('Javni FCC web — lokalna kopija');
  size();
 }
 function canLeave(){return !dirty||window.confirm(window.fccT('U pregledu imaš unos koji možda nije spremljen. Želiš li napustiti taj ekran?'));}
 function navigate(path){if(!canLeave())return false;dirty=false;screen.src=path;return true;}
 document.querySelectorAll('[data-mode]').forEach(button=>button.addEventListener('click',()=>{
  const next=button.dataset.mode;if(next===mode)return;
  if((next==='public')!==(mode==='public')&&!navigate(next==='public'?'/':'/partner'))return;
  mode=next;resetZoom();select.value=mode==='app'?'iphone':'desktop';applyMode();
 }));
 document.querySelectorAll('[data-route]').forEach(button=>button.addEventListener('click',()=>navigate(button.dataset.route)));
 select.addEventListener('change',()=>{resetZoom();size();});
 document.querySelector('#pv-reload').addEventListener('click',()=>{if(!canLeave())return;try{screen.contentWindow.location.reload();}catch{screen.src=screen.src;}});
 document.querySelector('#pv-account').addEventListener('click',()=>navigate('/logout'));
 document.querySelector('#pv-zoom').addEventListener('click',event=>{fullSize=!fullSize;event.currentTarget.setAttribute('aria-pressed',String(fullSize));event.currentTarget.textContent=fullSize?window.fccT('Prilagodi okviru'):window.fccT('Izvorna veličina');size();});
 screen.addEventListener('load',()=>{
  dirty=false;
  try{
   const child=screen.contentWindow, doc=child.document, url=new URL(child.location.href);
   if(url.origin!==location.origin)return;
   current.href=url.href;document.querySelector('#pv-open').href=url.href;current.textContent=url.pathname+url.search+' ↗';
   doc.addEventListener('input',()=>dirty=true);doc.addEventListener('change',()=>dirty=true);
   doc.addEventListener('submit',()=>dirty=false);
   // Successful existing AJAX saves clear only the viewer's navigation reminder.
   child.jQuery?.(doc).on('ajaxSuccess.fccDevicePreview',(_event,xhr)=>{if(xhr.responseJSON?.status==='success')dirty=false;});
   applyMode();
  }catch{current.textContent=window.fccT('Vanjska stranica · otvori u pregledniku');}
 });
 new ResizeObserver(size).observe(stage);
 applyMode();
})();
