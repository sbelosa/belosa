/* FC-2026-10-12: invite collaborators who use FCC in a phone browser to add it to the home screen.
   Android Chrome installs in one tap; other browsers open the install page. "Later" waits 14 days. */
(() => {
 'use strict';
 const nudge=document.querySelector('[data-install-nudge]');if(!nudge)return;
 const ua=navigator.userAgent||'';
 const ios=/iPhone|iPad|iPod/.test(ua)||(/Macintosh/.test(ua)&&navigator.maxTouchPoints>1);
 const phone=ios||/Android/.test(ua);
 const standalone=window.matchMedia('(display-mode: standalone)').matches||navigator.standalone===true;
 const key='fcc-install-later:'+nudge.dataset.user;
 let later=false;try{later=Number(localStorage.getItem(key)||0)>Date.now();}catch{}
 if(!phone||standalone||later)return;
 let deferred=null;
 window.addEventListener('beforeinstallprompt',event=>{event.preventDefault();deferred=event;});
 window.addEventListener('appinstalled',()=>{nudge.hidden=true;});
 // The notification prompt on iPhone already offers the same step; one invitation at a time is enough.
 const show=()=>{const push=document.querySelector('[data-device-prompt]');nudge.hidden=!!(push&&!push.hidden);};
 setTimeout(show,600);
 nudge.querySelector('[data-install-nudge-go]')?.addEventListener('click',async event=>{
  if(!deferred)return;
  event.preventDefault();
  const prompt=deferred;deferred=null;
  try{await prompt.prompt();const choice=await prompt.userChoice;if(choice.outcome==='accepted'){nudge.hidden=true;return;}}catch{}
  location.href=nudge.dataset.installUrl;
 });
 nudge.querySelector('[data-install-nudge-later]')?.addEventListener('click',()=>{nudge.hidden=true;try{localStorage.setItem(key,String(Date.now()+14*86400000));}catch{}});
})();
