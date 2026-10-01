/* Connect an approved device on login. Permission requests only follow a user click. */
(() => {
 'use strict';
 const root=document.querySelector('[data-fcc-device]');if(!root)return;
 const prompt=root.querySelector('[data-device-prompt]'),enable=root.querySelector('[data-device-enable]'),install=root.querySelector('[data-device-install]');
 const controls=[enable,...document.querySelectorAll('[data-push-enable]')].filter(Boolean);
 const statuses=[root.querySelector('[data-device-status]'),...document.querySelectorAll('[data-partner-push] [role=status]')].filter(Boolean);
 const report=text=>statuses.forEach(el=>{el.textContent=text;});
 const laterKey='fcc-push-later:'+root.dataset.user;
 let later=false;try{later=Number(localStorage.getItem(laterKey)||0)>Date.now();}catch{}
 const canPrompt=()=>root.dataset.prompt==='1'&&root.dataset.enabled==='1'&&!later;
 root.querySelector('[data-device-later]')?.addEventListener('click',()=>{prompt.hidden=true;later=true;try{localStorage.setItem(laterKey,String(Date.now()+7*86400000));}catch{}});
 const ios=/iPhone|iPad|iPod/.test(navigator.userAgent||'')||(/Macintosh/.test(navigator.userAgent||'')&&navigator.maxTouchPoints>1);
 const standalone=window.matchMedia('(display-mode: standalone)').matches||navigator.standalone===true;
 const supported=window.isSecureContext&&'serviceWorker' in navigator&&'PushManager' in window&&'Notification' in window;
 if(root.dataset.delegated==='1'){controls.forEach(b=>b.disabled=true);report(window.fccT('Za povezivanje svojeg uređaja vrati se na vlastiti račun.'));return;}
 if(root.dataset.ready!=='1'||!root.dataset.publicKey){controls.forEach(b=>b.disabled=true);report(window.fccT('Obavijesti su dostupne ovdje u FCC-u. Povezivanje uređaja na ovoj adresi trenutačno nije dostupno.'));return;}
 if(ios&&!standalone){
  controls.forEach(b=>b.disabled=true);enable.hidden=true;install.hidden=false;
  root.querySelector('[data-device-copy]').textContent=window.fccT('Za obavijesti na iPhoneu dodaj FCC na početni zaslon i otvori njegovu ikonu.');
  report(window.fccT('Na iPhoneu uključi obavijesti iz FCC aplikacije na početnom zaslonu.'));prompt.hidden=!canPrompt();return;
 }
 if(!supported){controls.forEach(b=>b.disabled=true);report(window.fccT('Ovaj preglednik ne podržava obavijesti na uređaju. Sve poruke možeš pročitati u FCC-u.'));return;}
 if(Notification.permission==='denied'){controls.forEach(b=>b.disabled=true);report(window.fccT('Dopuštenje za obavijesti je isključeno u pregledniku. Možeš ga promijeniti u postavkama ove stranice.'));return;}
 const bytes=key=>Uint8Array.from(atob(key.replace(/-/g,'+').replace(/_/g,'/')),c=>c.charCodeAt(0));
 const activated=registration=>new Promise((resolve,reject)=>{
  if(registration.active){resolve(registration);return;}
  const worker=registration.installing||registration.waiting;
  if(!worker){reject(Error(window.fccT('Povezivanje se priprema. Pokušaj ponovno.')));return;}
  const finish=error=>{clearTimeout(timer);worker.removeEventListener('statechange',check);error?reject(error):resolve(registration);};
  const check=()=>{if(worker.state==='activated')finish();else if(worker.state==='redundant')finish(Error(window.fccT('Povezivanje nije uspjelo. Osvježi stranicu.')));};
  const timer=setTimeout(()=>finish(Error(window.fccT('Povezivanje se priprema. Pokušaj ponovno.'))),12000);
  worker.addEventListener('statechange',check);check();
 });
 let busy=false;
 const connect=async automatic=>{
  if(busy)return;busy=true;controls.forEach(b=>b.disabled=true);
  try {
   // This call must precede asynchronous work on a fresh iOS permission request.
   const permission=Notification.permission==='granted'?'granted':automatic?Notification.permission:await Notification.requestPermission();
   if(permission!=='granted')throw Error(permission==='denied'?window.fccT('Obavijesti nisu dopuštene. Dopuštenje možeš promijeniti u postavkama preglednika.'):window.fccT('Obavijesti možeš uključiti kasnije u svojim postavkama.'));
   const base=document.querySelector('base')?.href||location.origin+'/';
   const workerUrl=new URL('fcc-partner-sw.js',base).href,scope=new URL('partner',base).href;
   let registration=await navigator.serviceWorker.getRegistration(scope);
   if(registration&&[registration.active,registration.waiting,registration.installing].filter(Boolean).some(w=>w.scriptURL!==workerUrl))throw Error(window.fccT('Uređaj se nije mogao povezati. Otvori upute za instalaciju FCC-a.'));
   if(!registration)registration=await navigator.serviceWorker.register(workerUrl,{scope,updateViaCache:'none'});
   if(typeof registration.update==='function')registration.update().catch(()=>{});
   await activated(registration);
   const key=bytes(root.dataset.publicKey);
   let subscription=await registration.pushManager.getSubscription();
   if(subscription?.options?.applicationServerKey){
    const oldKey=new Uint8Array(subscription.options.applicationServerKey);
    if(oldKey.length!==key.length||oldKey.some((n,i)=>n!==key[i])){
     if(automatic)throw Error(window.fccT('Za ponovno povezivanje obavijesti klikni Uključi obavijesti.'));
     await subscription.unsubscribe();subscription=null;
    }
   }
   if(!subscription)subscription=await registration.pushManager.subscribe({userVisibleOnly:true,applicationServerKey:key});
   const body=new URLSearchParams({action:'push_connect',user_id:root.dataset.user,token:root.dataset.token,automatic:automatic?'1':'0',subscription:JSON.stringify(subscription.toJSON())});
   const response=await fetch(root.dataset.endpoint,{method:'POST',credentials:'same-origin',headers:{'Accept':'application/json'},body});
   const result=await response.json();if(!response.ok||!result.ok)throw Error(result.message||window.fccT('Povezivanje nije uspjelo. Pokušaj ponovno.'));
   prompt.hidden=true;root.dataset.enabled='1';report(window.fccT('Obavijesti su uključene na ovom uređaju.'));
   controls.forEach(b=>{b.textContent=window.fccT('Obavijesti su uključene');b.disabled=true;});
   document.querySelector('[name=push]')?.setAttribute('checked','checked');
   try{localStorage.removeItem(laterKey);}catch{}
  }catch(error){
   report(error instanceof SyntaxError?window.fccT('Sesija je istekla. Osvježi stranicu i pokušaj ponovno.'):error.message||window.fccT('Povezivanje nije uspjelo. Pokušaj ponovno.'));
   controls.forEach(b=>b.disabled=Notification.permission==='denied');
   if(Notification.permission==='denied')prompt.hidden=true;
   else if(canPrompt())prompt.hidden=false;
  }finally{busy=false;}
 };
 controls.forEach(button=>button.addEventListener('click',()=>connect(false)));
 if(root.dataset.enabled==='1'&&Notification.permission==='granted')connect(true);
 else if(Notification.permission==='default')prompt.hidden=!canPrompt();
})();
