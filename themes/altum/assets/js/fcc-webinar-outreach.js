(() => {
 'use strict';
 const root=document.querySelector('[data-webinar-invite]'),box=root?.querySelector('[data-invite-outreach]');
 if(!box)return;
 const copy=JSON.parse(box.dataset.outreachCopy),panel=box.querySelector('[data-outreach-panel]'),form=box.querySelector('[data-outreach-form]');
 const contact=box.querySelector('[data-outreach-contact]'),name=box.querySelector('[data-outreach-name]'),nameWrap=box.querySelector('[data-outreach-name-wrap]'),status=box.querySelector('[data-outreach-status]');
 const key='fcc.webinar.pending.v1:'+box.dataset.user+':'+box.dataset.session+':'+root.dataset.inviteId;
 let pending=null,busy=false;
 try{pending=JSON.parse(sessionStorage.getItem(key));if(!pending||Date.now()-pending.at>86400000||!/^[a-f0-9]{32}$/.test(pending.request))pending=null;}catch{}
 const requestKey=()=>Array.from(crypto.getRandomValues(new Uint8Array(16)),b=>b.toString(16).padStart(2,'0')).join('');
 function persist(){try{if(pending)sessionStorage.setItem(key,JSON.stringify(pending));else sessionStorage.removeItem(key);}catch{}}
 function fields(){nameWrap.hidden=contact.value!=='0';name.required=contact.value==='0';if(pending){pending.contact=contact.value;pending.name=name.value;persist();}}
 function show(move=false){if(!pending)return;panel.hidden=false;if(move&&!form.contains?.(document.activeElement)){panel.scrollIntoView({block:'center',behavior:'smooth'});panel.focus({preventScroll:true});}}
 function start(channel){
  // Keep the same request identity on repeat taps and retry after a lost response.
  if(!pending)pending={request:requestKey(),at:Date.now(),channel,contact:contact.value,name:name.value};
  persist();show(false);
 }
 function clearDraft(){
  try{for(const draft of [root.dataset.draftKey,box.dataset.baseKey]){sessionStorage.removeItem('fcc.webinar.draft.v1:'+draft);sessionStorage.removeItem('fcc.webinar.draft.v1:'+draft+':origin');}
   if(Number(root.dataset.inviteId)>0){const identity=root.dataset.draftKey.split(':');sessionStorage.removeItem('fcc.webinar.video.v2:'+new URL(root.dataset.inviteSourceUrl||root.dataset.inviteUrl).origin+':'+(document.documentElement?.lang||'en')+':'+identity[0]+':'+root.dataset.inviteId+':'+identity[identity.length-2]);}
  }catch{}
 }
 function next(saved=false){if(busy)return;pending=null;persist();clearDraft();window.location.assign(box.dataset.newUrl+(saved?'&saved=outreach#invite-outreach':''));}
 root.addEventListener('fcc:invite-handoff',event=>start(event.detail.channel));
 box.querySelector('[data-outreach-manual]').addEventListener('click',()=>{start('manual');show(true);});
 box.querySelector('[data-outreach-new]').addEventListener('click',()=>next());
 box.querySelector('[data-outreach-cancel]').addEventListener('click',()=>{if(busy)return;pending=null;persist();panel.hidden=true;status.textContent='';});
 contact.addEventListener('change',fields);name.addEventListener('input',fields);
 ['pageshow','focus'].forEach(type=>window.addEventListener(type,()=>show(true)));
 document.addEventListener('visibilitychange',()=>{if(document.visibilityState==='visible')show(true);});
 form.addEventListener('submit',async event=>{
  event.preventDefault();if(busy||!pending)return;
  if(contact.value==='0'&&name.value.trim().length<2){status.textContent=copy.name_required;name.focus();return;}
  busy=true;status.textContent='';form.setAttribute('aria-busy','true');form.querySelectorAll('button,input,select').forEach(el=>el.disabled=true);
  const abort=new AbortController(),timeout=setTimeout(()=>abort.abort(),15000);
  try{
   const response=await fetch(root.dataset.inviteEndpoint,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},signal:abort.signal,body:new URLSearchParams({action:'outreach_confirm',token:root.dataset.inviteCsrf,session_id:box.dataset.session,attempt_id:root.dataset.inviteId,request_key:pending.request,channel:pending.channel,contact_id:contact.value,recipient_name:name.value.trim(),confirmed:'1'})});
   const data=await response.json();if(!response.ok||!data.ok)throw new Error(data.error||copy.error);
   busy=false;next(true);
  }catch(error){status.textContent=error.name==='AbortError'?copy.error:error.message||copy.error;}
  finally{clearTimeout(timeout);busy=false;form.setAttribute('aria-busy','false');form.querySelectorAll('button,input,select').forEach(el=>el.disabled=false);}
 });
 if(pending){contact.value=pending.contact||'0';if(!contact.value)contact.value='0';name.value=pending.name||'';}
 fields();show(!!pending);
})();
