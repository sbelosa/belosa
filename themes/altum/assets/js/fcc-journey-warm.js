(() => {
 'use strict';
 const root=document.querySelector('[data-warm-step]'),form=root?.querySelector('[data-warm-form]');if(!form)return;
 const text=form.querySelector('[data-warm-message]'),article=form.querySelector('[data-warm-article]'),status=form.querySelector('[data-warm-status]');
 const choices=JSON.parse(form.querySelector('[data-warm-choices]').textContent);let dirty=false;
 // The shared click handler shows loading before this form can reject a submit.
 const submitLabels=new WeakMap();
 form.querySelectorAll('button[type="submit"]').forEach(button=>submitLabels.set(button,button.innerHTML));
 form.addEventListener('click',e=>{
  const button=e.target.closest('button[type="submit"]');
  if(button&&button.form===form)submitLabels.set(button,button.innerHTML);
 },true);
 const restoreSubmit=button=>{
  if(!button)return;
  button.classList.remove('disabled','container-disabled-simple');
  if(submitLabels.has(button))button.innerHTML=submitLabels.get(button);
 };
 if(article)article.required=root.dataset.practice!=='1';
 const source=()=>choices[article?.value||'0']||{};
 const tell=value=>{if(status)status.textContent=value;};
 function message(){
  if(article&&!article.value)throw Error(window.fccT('Najprije odaberi proizvod.'));
  let value=text.value.trim();const link=source().referral_url||'';
  if(!value)throw Error(window.fccT('Napiši svoju poruku.'));
  if(link){const own=new URL(link);value=value.replace(/https?:\/\/[^\s<>]+/g,raw=>{try{const u=new URL(raw);return u.origin===own.origin&&u.pathname===own.pathname?link:raw;}catch{return raw;}});if(!value.includes(link))value+='\n\n'+link;}
  if(/\[[^\]]+\]|\{(?:title|link|event)\}/.test(value))throw Error(window.fccT('Dodaj svoje riječi na mjesta u uglatim zagradama.'));
  if(value.length>3000)throw Error(window.fccT('Malo skrati poruku kako bi stala i poveznica.'));
  text.value=value;return value;
 }
 let oldSource=source();
 article?.addEventListener('change',()=>{const next=source();if(!text.value.trim()||text.value===oldSource.message)text.value=next.message||'';else if(oldSource.referral_url)text.value=text.value.split(oldSource.referral_url).join(next.referral_url||'');oldSource=next;dirty=true;});
 form.querySelector('[data-warm-copy]')?.addEventListener('click',async()=>{try{const value=message();await navigator.clipboard.writeText(value);tell(window.fccT('Tekst je kopiran.'));}catch(e){tell(e.message);text?.focus();text?.select();}});
 form.querySelector('[data-warm-whatsapp]')?.addEventListener('click',e=>{try{
  const contact=form.querySelector('[data-warm-contact]');const value=message(),rawPhone=e.currentTarget.dataset.phone||contact?.selectedOptions[0]?.dataset.phone||'',phone=/^[1-9][0-9]{6,14}$/.test(rawPhone)?rawPhone:'';const destination='https://api.whatsapp.com/send?'+(phone?'phone='+phone+'&':'')+'text='+encodeURIComponent(value);if(e.currentTarget.dataset.scDirect==='1'){dirty=false;window.location.assign(destination);}else window.open(destination,'_blank','noopener,noreferrer');
  tell(window.fccT('Poruka je spremna u WhatsAppu. Ondje je pregledaj i pošalji.'));
  const data=new FormData(form);data.set('action','pilot_event');data.set('kind','share_intent');data.set('confirmed','1');data.set('detail',window.fccT('Otvoren WhatsApp za poruku uz dnevni korak.'));data.set('request_key',crypto.randomUUID().replaceAll('-',''));
  fetch(location.href,{method:'POST',body:data,credentials:'same-origin',keepalive:true}).catch(()=>{});
 }catch(err){tell(err.message);text?.focus();}});
 root.querySelector('[data-warm-practice]')?.addEventListener('click',()=>{
  form.querySelectorAll('[name=kind]').forEach(e=>{e.disabled=true;});
  const kind=document.createElement('input');kind.type='hidden';kind.name='kind';kind.value='practice';form.append(kind);
  if(article)article.required=false;
  const note=form.querySelector('[name=detail]');note.closest('details').open=true;note.required=true;note.minLength=15;note.placeholder=window.fccT('Napiši što si uvježbao/la i što želiš primijeniti.');
  form.querySelector('[value=pilot_finish]').textContent=window.fccT('Spremi odrađenu vježbu ✓');note.focus();dirty=true;
 });
 form.addEventListener('input',()=>{dirty=true;});
 form.addEventListener('submit',e=>{
  if(e.submitter?.value==='pilot_finish'&&form.querySelector('[name=kind]:not(:disabled)')?.value!=='practice'&&article&&!article.value){e.preventDefault();restoreSubmit(e.submitter);tell(window.fccT('Odaberi proizvod uz koji si odradio/la korak.'));article.focus();return;}
  const detail=form.querySelector('[name=detail]');if(detail){const note=document.createElement('input');note.type='hidden';note.name='note';note.value=detail.value;form.append(note);}dirty=false;
 });
 window.addEventListener('beforeunload',e=>{if(dirty){e.preventDefault();e.returnValue='';}});
})();
