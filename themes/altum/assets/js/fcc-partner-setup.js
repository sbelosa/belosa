/* FC-2026-10-13: "Postavi FCC". The bell holds three setup steps (team group, notifications, home screen);
   Danas shows one quiet line while any step is open. The browser decides which device steps apply here. This
   script sends nothing itself: notifications connect through fcc-partner-push.js ([data-push-enable]) and the team
   step posts its own form. Every text comes from the page, already in the member's language. */
(() => {
 'use strict';
 const panel=document.querySelector('[data-fcc-setup]'),strip=document.querySelector('[data-fcc-setup-strip]');
 if(!panel&&!strip)return;
 const device=document.querySelector('[data-fcc-device]');
 const ua=navigator.userAgent||'';
 const ios=/iPhone|iPad|iPod/.test(ua)||(/Macintosh/.test(ua)&&navigator.maxTouchPoints>1);
 const phone=ios||/Android/.test(ua);
 const standalone=()=>window.matchMedia('(display-mode: standalone)').matches||navigator.standalone===true;
 const pushSupported=window.isSecureContext&&'serviceWorker' in navigator&&'PushManager' in window&&'Notification' in window;
 let installed=false,deferred=null;

 // Each step is open, done, after_install (iPhone: notifications need the home screen app first),
 // blocked (the browser refuses notifications) or hidden (the step does not apply on this device).
 const state=()=>{
  const team=(panel||strip).dataset.teamOpen==='1'?'open':'done';
  let push='hidden';
  if(device&&device.dataset.delegated!=='1'&&device.dataset.ready==='1'&&device.dataset.publicKey){
   if(ios&&!standalone())push='after_install';
   else if(pushSupported)push=Notification.permission==='granted'&&device.dataset.enabled==='1'?'done':Notification.permission==='denied'?'blocked':'open';
  }
  const install=standalone()||installed?'done':phone?'open':'hidden';
  return {team,push,install};
 };
 const fill=(template,values)=>Object.entries(values).reduce((text,[key,value])=>text.split('{'+key+'}').join(String(value)),template||'');

 const laterKey='fcc-setup-later:'+(strip?strip.dataset.user:'');
 const snoozed=()=>{try{return Number(localStorage.getItem(laterKey)||0)>Date.now();}catch{return false;}};

 const render=()=>{
  const steps=state();
  const applicable=Object.values(steps).filter(value=>value!=='hidden');
  const total=applicable.length,done=applicable.filter(value=>value==='done').length;
  const open=applicable.filter(value=>value==='open'||value==='after_install').length;
  if(panel){
   for(const [name,value] of Object.entries(steps)){
    const row=panel.querySelector(`[data-setup-step="${name}"]`);if(!row)continue;
    row.hidden=value==='hidden';row.dataset.state=value;
   }
   const copy=panel.querySelector('[data-setup-push-copy]');
   if(copy)copy.textContent=steps.push==='after_install'?copy.dataset.iosCopy:steps.push==='blocked'?copy.dataset.blockedCopy:copy.dataset.copy;
   const progress=panel.querySelector('[data-setup-progress]');
   if(progress)progress.textContent=fill(progress.dataset.template,{done,total});
   panel.hidden=open===0;
  }
  if(strip){
   const count=strip.querySelector('[data-setup-strip-count]');
   if(count)count.textContent=fill(count.dataset.template,{n:open,total});
   strip.hidden=open===0||snoozed();
  }
 };

 strip?.querySelector('[data-setup-strip-later]')?.addEventListener('click',()=>{
  strip.hidden=true;
  try{localStorage.setItem(laterKey,String(Date.now()+7*86400000));}catch{}
 });

 // Android Chrome installs in one tap; other browsers open the install page with the steps for that phone.
 window.addEventListener('beforeinstallprompt',event=>{event.preventDefault();deferred=event;});
 window.addEventListener('appinstalled',()=>{installed=true;render();});
 panel?.querySelector('[data-setup-install]')?.addEventListener('click',async event=>{
  if(!deferred)return;
  event.preventDefault();
  const href=event.currentTarget.href,prompt=deferred;deferred=null;
  try{await prompt.prompt();const choice=await prompt.userChoice;if(choice.outcome==='accepted'){installed=true;render();return;}}catch{}
  location.href=href;
 });

 // fcc-partner-push.js connects the device; follow its result without a second request.
 const pushButton=panel?.querySelector('[data-setup-step="push"] [data-push-enable]');
 if(pushButton)new MutationObserver(render).observe(pushButton,{attributes:true,childList:true,characterData:true,subtree:true});
 if(device)new MutationObserver(render).observe(device,{attributes:true,attributeFilter:['data-enabled']});
 navigator.permissions?.query({name:'notifications'}).then(status=>{status.onchange=render;}).catch(()=>{});

 render();
})();
