(() => {
 'use strict';
 // Keep the edited text; attach the server-generated owner link before save/share.
 const withReferral=(text,link)=>{
  if(!link)return text;
  const source=new URL(link);
  text=text.replace(/https?:\/\/[^\s<>"']+/gu,raw=>{
   const clean=raw.replace(/[.,;!?) \]}]+$/u,'');
   try{const url=new URL(clean);if(url.hostname===source.hostname&&url.port===source.port&&url.pathname===source.pathname)return link+raw.slice(clean.length);}catch{}
   return raw;
  });
  return text.includes(link)?text:text.trimEnd()+'\n'+link;
 };
 document.querySelectorAll('[data-j90-composer]').forEach(form => {
  const message=form.querySelector('textarea'),button=form.querySelector('[data-j90-whatsapp]'),status=form.querySelector('[data-j90-message-status]');
  const select=form.querySelector('[data-j90-article]'),fields=form.querySelector('[data-j90-message-fields]');
  if(select){
   const img=form.querySelector('[data-j90-image]'),drafts=new Map();let previous=select.value;
   const image=()=>{const url=select.selectedOptions[0]?.dataset.image;img.hidden=!url;if(url)img.src=url;else img.removeAttribute('src');};
   select.addEventListener('change',()=>{
    drafts.set(previous,message.value);const option=select.selectedOptions[0];previous=select.value;
    form.dataset.j90Referral=option?.dataset.referral||'';
    message.value=withReferral(drafts.get(previous)??option?.dataset.message??'',form.dataset.j90Referral);
    if(fields)fields.hidden=!select.value;button.disabled=!select.value;image();
    status.textContent=window.fccT('Poruka i tvoja osobna poveznica su spremne. Slobodno prilagodi tekst prije otvaranja WhatsAppa.');
   });image();
  }
  const currentMessage=()=>{
   const text=withReferral(message.value.trim(),form.dataset.j90Referral||'');
   if(!text||/\[[^\]]+\]|\{(?:title|link)\}/u.test(text)){status.textContent=window.fccT('Najprije dopuni označena mjesta u poruci.');message.focus();return null;}
   if([...text].length>3000){status.textContent=window.fccT('Skrati poruku kako bi u nju stala i tvoja osobna poveznica.');message.focus();return null;}
   message.value=text;return text;
  };
  button.addEventListener('click',()=>{
   const text=currentMessage();if(text===null)return;
   // Read the current textarea, never the original template or an older saved draft.
   // Opening a chooser never writes an activity or marks a step complete.
   window.open('https://wa.me/?text='+encodeURIComponent(text),'_blank','noopener,noreferrer');
   status.textContent=window.fccT('U WhatsApp je prenesena tvoja poruka. Odaberi osobu i potvrdi slanje.');
  });
  form.addEventListener('submit',event=>{if(currentMessage()===null)event.preventDefault();});
 });
 document.querySelectorAll('[data-j90-completion]').forEach(form => {
  const mode=form.querySelector('[data-j90-mode]'), output=form.querySelector('[data-j90-output]'), evidence=form.querySelector('[data-j90-evidence]');
  const update=()=>{
   const practice=mode.value==='practice';
   Array.from(output.options).forEach(o=>{o.disabled=practice?o.value!=='practised':o.value==='practised';o.hidden=o.disabled;});
   if(output.selectedOptions[0]?.disabled)output.value=Array.from(output.options).find(o=>!o.disabled).value;
   const external=!practice&&['sent','performed'].includes(output.value);
   evidence.hidden=!external;evidence.querySelectorAll('input,select').forEach(e=>{e.disabled=!external;});
   evidence.querySelector('[name=contact_permission]').required=external;
  };
  mode.addEventListener('change',update);output.addEventListener('change',update);update();
 });
})();
