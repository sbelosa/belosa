(() => {
 'use strict';
 const workspace=document.querySelector('[data-pilot-workspace]');if(!workspace)return;
 const $=s=>workspace.querySelector(s), form=$('[data-pilot-draft]');
 const choices=JSON.parse($('[data-pilot-choices]').textContent),text=$('[data-pilot-message]'),article=$('[data-pilot-article]');
 const preview=$('[data-pilot-preview]'),editor=$('#pilot-editor'),edit=$('[data-pilot-edit]'),status=$('[data-pilot-status]');
 const dialog=$('[data-pilot-dialog]'),fields=$('[data-pilot-confirm-fields]'),detail=$('[data-pilot-detail]');
 let dirty=false,returnFocus=null;
 const tell=m=>{status.textContent=m;};
 const source=()=>choices[article?article.value:'0']||{};
 const hasWords=value=>/[\p{L}\p{N}]/u.test(value.replace(/https?:\/\/[^\s<>]+/g,''));
 const repaint=()=>{preview.textContent=text.value.trim()||window.fccT('Odaberi proizvod pa će se ovdje pojaviti prijedlog teksta.');};
 function editing(open){editor.hidden=!open;preview.hidden=open;edit.setAttribute('aria-expanded',String(open));edit.textContent=open?window.fccT('Zatvori uređivanje'):window.fccT('Uredi tekst');if(open)text.focus();else repaint();}
 edit.addEventListener('click',()=>editing(editor.hidden));
 $('[data-pilot-edit-done]').addEventListener('click',()=>{editing(false);edit.focus();});
 function message(){
  const link=source().referral_url||'';let value=text.value.trim();
  if(article&&!article.value)throw Error(window.fccT('Najprije odaberi proizvod.'));
  if(!hasWords(value))value=source().message||value;
  if(!hasWords(value))throw Error(window.fccT('Napiši svoju poruku.'));
  if(link){const own=new URL(link);value=value.replace(/https?:\/\/[^\s<>]+/g,raw=>{try{const u=new URL(raw);return u.origin===own.origin&&u.pathname===own.pathname?link:raw;}catch{return raw;}});if(!value.includes(link))value+='\n'+link;}
  if(/\[[^\]]+\]|\{(?:title|link)\}/.test(value))throw Error(window.fccT('Zamijeni oznake u uglatim zagradama svojim riječima prije slanja.'));
  if(value.length>3000)throw Error(window.fccT('Skrati poruku kako bi stala i tvoja poveznica.'));text.value=value;repaint();return value;
 }
 async function copy(){const value=message();try{await navigator.clipboard.writeText(value);tell(workspace.dataset.pilotMode==='planning'?window.fccT('Priprema je kopirana.'):window.fccT('Tekst je kopiran. Dodaj ga svojoj objavi ili poruci.'));}catch{editing(true);text.select();tell(window.fccT('Tekst je označen. Kopiraj ga pomoću izbornika svojeg uređaja.'));}}
 function product(){const box=$('[data-pilot-product]');if(!box)return;box.replaceChildren();const s=source();box.hidden=!s.title;if(!s.title)return;if(s.image){const img=document.createElement('img');img.src=s.image;img.alt=s.title;box.append(img);}const info=document.createElement('div'),label=document.createElement('strong');label.textContent=s.title;info.append(label);if(s.referral_url){const link=document.createElement('a');link.href=s.referral_url;link.target='_blank';link.rel='noopener';link.textContent=window.fccT('Pogledaj članak i uputu ↗');info.append(link);}box.append(info);}
 article?.addEventListener('change',()=>{const previous=text.dataset.source||'',next=source();if(!hasWords(text.value)||text.value===previous){text.value=next.message||'';}else{const oldLink=text.dataset.link||'';if(oldLink)text.value=text.value.split(oldLink).join(next.referral_url||'');tell(window.fccT('Tvoj tekst je sačuvan. Provjeri odgovara li novom proizvodu.'));}text.dataset.source=next.message||'';text.dataset.link=next.referral_url||'';product();repaint();});
 text.dataset.source=source().message||'';text.dataset.link=source().referral_url||'';product();
 $('[data-pilot-copy]')?.addEventListener('click',()=>copy().catch(e=>{tell(e.message);if(!article||article.value)editing(true);else article.focus();}));
 $('[data-pilot-whatsapp]')?.addEventListener('click',e=>{try{const value=message(),phone=e.currentTarget.dataset.phone||'';window.open('https://wa.me/'+phone+'?text='+encodeURIComponent(value),'_blank','noopener,noreferrer');tell(window.fccT('Poruka je pripremljena u WhatsAppu. Pregledaj je i sam/a potvrdi slanje.'));}catch(err){tell(err.message);if(!article||article.value)editing(true);else article.focus();}});
 $('[data-pilot-mentor]')?.addEventListener('click',e=>{const b=e.currentTarget;window.open('https://wa.me/'+b.dataset.pilotMentor+'?text='+encodeURIComponent(b.dataset.mentorMessage),'_blank','noopener,noreferrer');tell(window.fccT('Pregledaj poruku mentoru i potvrdi slanje u WhatsAppu.'));});
 function contactVisibility(){const c=$('[data-pilot-contact]');if(c)c.hidden=!['message_sent','conversation_reported'].includes(fields.querySelector('[name="kind"]:checked')?.value);}
 fields.querySelectorAll('[name="kind"]').forEach(r=>r.addEventListener('change',contactVisibility));
 function confirm(practice,trigger){
  returnFocus=trigger;fields.disabled=false;
  $('[data-pilot-real-options]').hidden=practice;
  $('[data-pilot-practice-option]').hidden=!practice;
  $('[data-pilot-practice-tip]').hidden=!practice;
  $('[data-pilot-detail-label]').hidden=!practice;
  $('[data-pilot-criterion]').hidden=practice;
  detail.required=practice;detail.minLength=practice?15:0;detail.disabled=!practice;
  const contact=$('[data-pilot-contact]');if(contact)contact.hidden=practice;
  const radios=[...fields.querySelectorAll('[name="kind"]')];
  radios.forEach(r=>{r.disabled=practice?r.value!=='practice':r.value==='practice';r.checked=false;});
  radios.find(r=>!r.disabled).checked=true;contactVisibility();
  $('#pilot-confirm-title').textContent=practice?window.fccT('Spremi svoju vježbu'):window.fccT('Potvrdi svoj korak');
  $('[data-pilot-confirm]').textContent=practice?window.fccT('Spremi vježbu i nastavi →'):window.fccT('Spremi dovršeni korak →');
  dialog.showModal();
 }
 $('[data-pilot-finish]').addEventListener('click',e=>confirm(false,e.currentTarget));
 $('[data-pilot-practice]').addEventListener('click',e=>confirm(true,e.currentTarget));
 $('[data-pilot-close]').addEventListener('click',()=>dialog.close());
 dialog.addEventListener('close',()=>{fields.disabled=true;returnFocus?.focus();});
 document.querySelectorAll('[data-pilot-coach], [data-webinar-coach]').forEach(link=>link.addEventListener('click',e=>{
  const launcher=document.querySelector('#fcc-coach-chat-extreme .fcc-chat-extreme__launcher');
  if(launcher){e.preventDefault();launcher.click();}
 }));
 function saveAndNavigate(destination){
  const target=document.createElement('input');target.type='hidden';target.name='navigate';target.value=destination;form.append(target);
  const submit=document.createElement('button');submit.type='submit';submit.name='action';submit.value='pilot_draft';submit.hidden=true;form.append(submit);form.requestSubmit(submit);
 }
 workspace.querySelectorAll('[data-pilot-support]').forEach(link=>link.addEventListener('click',e=>{
  e.preventDefault();saveAndNavigate('support');
 }));
 // The floating Coach receives the selected product from an owned, saved draft.
 document.addEventListener('click',e=>{
  if(dirty&&e.target.closest('#fcc-coach-chat-extreme .fcc-chat-extreme__launcher')){
   e.preventDefault();e.stopImmediatePropagation();saveAndNavigate('coach');
  }
 },true);
 const openCoach=()=>{if(workspace.hasAttribute('data-open-coach'))document.querySelector('#fcc-coach-chat-extreme .fcc-chat-extreme__launcher')?.click();};
 if(document.readyState==='complete')openCoach();else window.addEventListener('load',openCoach,{once:true});
 const draftChanged=e=>{if(e.target===text||e.target===article)dirty=true;};
 form.addEventListener('input',draftChanged);form.addEventListener('change',draftChanged);
 form.addEventListener('submit',e=>{
  const finish=e.submitter?.value==='pilot_finish',practice=form.querySelector('[name="kind"]:checked')?.value==='practice';
  if(finish&&!practice&&article&&!article.value){e.preventDefault();dialog.close();tell(window.fccT('Odaberi proizvod uz koji si odradio/la ovaj korak.'));article.focus();return;}
  // Saving a draft or a completed exercise must not require a finished message.
  if(text.value.trim()&&(!article||article.value)){try{message();}catch(err){if(!finish)tell(window.fccT('Nacrt se sprema; poruku možeš doraditi kasnije.'));}}
  dirty=false;
 });
 window.addEventListener('beforeunload',e=>{if(dirty){e.preventDefault();e.returnValue='';}});
})();
