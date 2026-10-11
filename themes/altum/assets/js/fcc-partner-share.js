(() => {
 'use strict';
 const root=document.querySelector('[data-webinar-invite]');
 // Submit the existing GET form so the server refreshes the date, message,
 // referral link and revision together. The button remains a no-JS fallback.
 const eventForm=root?.querySelector('[data-invite-event-form]');
 const eventSelect=eventForm?.querySelector('select[name="session_id"]');
 if(eventSelect){
  eventSelect.addEventListener('change',()=>{
   if(eventSelect.value)eventForm.requestSubmit();
  });
  const submit=eventForm.querySelector('[data-invite-event-submit]');
  if(submit)submit.hidden=true;
 }
 const message=root?.querySelector('[data-invite-message]');
 if(!message||!root.dataset.inviteUrl)return;
 const link=new URL(root.dataset.inviteUrl),whatsapp=root.querySelector('[data-invite-whatsapp]');
 const key='fcc.webinar.draft.v1:'+root.dataset.draftKey;
 const status=root.querySelector('[data-invite-status]');
 const linkOrigins=new Set([link.origin]);
 if(root.dataset.inviteSourceUrl)linkOrigins.add(new URL(root.dataset.inviteSourceUrl).origin);
 try{
  let draft=sessionStorage.getItem(key);const origin=sessionStorage.getItem(key+':origin');
  // Import an existing video editor draft once when its old link opens this editor.
  if(draft===null&&Number(root.dataset.inviteId)>0){
   const identity=root.dataset.draftKey.split(':');
   const legacy='fcc.webinar.video.v2:'+new URL(root.dataset.inviteSourceUrl||root.dataset.inviteUrl).origin+':'+(document.documentElement?.lang||'en')+':'+identity[0]+':'+root.dataset.inviteId+':'+identity[identity.length-2];
   draft=sessionStorage.getItem(legacy);
  }
  if(draft!==null&&root.dataset.draftReplace!=='1'){message.value=draft;if(origin)linkOrigins.add(new URL(origin).origin);}
 }catch{}
 function prepared(){
  // Normalize only the webinar URL, retaining authored text and the account's referral.
  const eventPath=path=>path.replace(/^\/(?:(?:hr|sl|en|de|sr|sq|cnr|fr|es|sv|hu|pl|ro)\/)+(?=webinar-event\/)/,'/').replace(/\/$/,'');
  let found=false;
  let text=message.value.trim().replace(/https?:\/\/[^\s<>]+/g,raw=>{
   const tail=raw.match(/[.,;!?)]+$/)?.[0]||'';
   try{const url=new URL(tail?raw.slice(0,-tail.length):raw);if(linkOrigins.has(url.origin)&&eventPath(url.pathname)===eventPath(link.pathname)){if(found)return tail;found=true;return link.href+tail;}}catch{}
   return raw;
  });
  if(!text.includes(link.href))text+=(text?'\n\n':'')+link.href;
  return text;
 }
 function refresh(showPrepared=false){
  const text=prepared();
  if(showPrepared)message.value=text;
  whatsapp.href='https://api.whatsapp.com/send?'+(root.dataset.phone?'phone='+encodeURIComponent(root.dataset.phone)+'&':'')+'text='+encodeURIComponent(text);
  try{sessionStorage.setItem(key,message.value);sessionStorage.setItem(key+':origin',link.origin);}catch{}
 }
 message.addEventListener('input',()=>refresh());
 message.addEventListener('blur',()=>refresh(true));
 async function record(kind){
  if(!Number(root.dataset.inviteId))return;
  const controller=new AbortController(),timeout=setTimeout(()=>controller.abort(),4000);
  try{await fetch(root.dataset.inviteEndpoint,{method:'POST',credentials:'same-origin',signal:controller.signal,headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},body:new URLSearchParams({action:'event',token:root.dataset.inviteCsrf,id:root.dataset.inviteId,kind})});}catch{}finally{clearTimeout(timeout);}
 }
 async function currentEvent(){
  if(Number(root.dataset.inviteExpires)>0&&Number(root.dataset.inviteExpires)<=Date.now()){status.textContent=window.fccT('Poveznica više nije aktivna. Osvježi pozivnicu.');return false;}
  if(!root.dataset.eventCheck)return true;
  try{const response=await fetch(root.dataset.eventCheck,{credentials:'same-origin',cache:'no-store'});const result=await response.json();if(response.ok&&result.ok)return true;status.textContent=result.message||window.fccT('Termin nije dostupan. Osvježi prikaz.');}
  catch{status.textContent=window.fccT('Ne mogu provjeriti aktualni termin. Pokušaj ponovno prije slanja.');}
  return false;
 }
 whatsapp.addEventListener('click',async event=>{
  refresh(true);
  const destination=whatsapp.href;
  if(!root.dataset.eventCheck&&!Number(root.dataset.inviteExpires)){root.dispatchEvent?.(new CustomEvent('fcc:invite-handoff',{detail:{channel:'whatsapp'}}));return;}
  event.preventDefault();
  if(!await currentEvent())return;
  await record('whatsapp');
  root.dispatchEvent?.(new CustomEvent('fcc:invite-handoff',{detail:{channel:'whatsapp'}}));
  window.location.assign(destination);
 });
 root.querySelector('[data-invite-share]').addEventListener('click',async()=>{
  if(!await currentEvent())return;refresh(true);const text=prepared();status.textContent='';
  if(navigator.share){try{await navigator.share({title:root.dataset.title,text});await record('share');root.dispatchEvent?.(new CustomEvent('fcc:invite-handoff',{detail:{channel:'share'}}));return;}catch(error){if(error.name==='AbortError')return;}}
  try{await navigator.clipboard.writeText(text);await record('copy');root.dispatchEvent?.(new CustomEvent('fcc:invite-handoff',{detail:{channel:'copy'}}));status.textContent=root.dataset.copyOk;}
  catch{const fallback=root.querySelector('[data-invite-fallback]');fallback.value=text;fallback.hidden=false;fallback.focus();fallback.select();status.textContent=root.dataset.copyError;}
 });
 refresh(true);
})();
