(() => {
 'use strict';
 const config=document.querySelector('[data-webinar-config]');
 if(config){
  const picker=config.querySelector('[data-webinar-choice]'),mode=config.querySelector('[data-webinar-mode]'),own=config.querySelector('[data-personal-event]');
  const setup=config.querySelector('[data-webinar-setup]'),page=config.closest('[data-webinar-workspace]');
  const radios=[...config.querySelectorAll('[name=video_choice]')];
  const selectedVideo=()=>radios.find(input=>input.checked)?.value||'none';
  const videoByContext={shared:selectedVideo(),personal:'none'};
  let previousPersonal=null;
  const ownValue=name=>config.elements.namedItem('custom_'+name)?.value.trim()||'';
  function updateVideo(){
   const field=config.querySelector('[data-webinar-custom]'),custom=selectedVideo()==='custom',input=field.querySelector('input');
   field.hidden=!custom;input.required=custom;input.disabled=!custom;
  }
  function eventPreview(){
   if(!picker)return;
   const creating=mode?.value==='custom',option=picker.selectedOptions[0],personal=creating||option?.dataset.personal==='1'||option?.dataset.eventChannel==='events';
   if(previousPersonal!==null&&previousPersonal!==personal){
    videoByContext[previousPersonal?'personal':'shared']=selectedVideo();
    radios.forEach(input=>{input.checked=input.value===videoByContext[personal?'personal':'shared'];});
   }
   // An old saved FCC choice must never carry across to an individual event.
   if(personal&&!['custom','none'].includes(selectedVideo()))radios.forEach(input=>{input.checked=input.value==='none';});
   previousPersonal=personal;
   config.querySelectorAll('[data-official-video]').forEach(label=>{label.hidden=personal;label.querySelector('input').disabled=personal;});
   const note=config.querySelector('[data-official-video-note]');if(note)note.hidden=personal;
   config.querySelector('[data-video-help]').textContent=personal?window.fccT('Dodaj svoj video ili pripremi pozivnicu samo s opisom i prijavom.'):window.fccT('Odaberi uvod za odabrani webinar.');
   const manage=config.querySelector('[data-personal-actions]');
   if(manage){
    const manageUrl=!creating&&personal?option?.dataset.manageUrl:'';
    manage.hidden=!manageUrl;
    if(manageUrl){manage.querySelector('[data-personal-edit-link]').href=manageUrl;manage.querySelector('[data-personal-remove-link]').href=manageUrl+'&remove=1#fv-remove-webinar';}
   }
   config.querySelector('[data-existing-event]').hidden=creating;
   config.querySelector('[data-own-heading]').hidden=!creating;
   config.querySelector('[data-cancel-webinar]').hidden=!creating;
   config.querySelector('[data-create-webinar]')?.setAttribute('aria-expanded',String(creating));
   setup.classList.toggle('is-personal',personal);
   if(own){own.hidden=!creating;own.querySelectorAll('input,textarea,select').forEach(field=>{field.disabled=!creating;field.required=creating&&field.hasAttribute('data-own-required');});}
   let title=option?.dataset.title||window.fccT('Tvoj webinar'),date=option?.dataset.date||'',description=option?.dataset.description||'';
   if(creating){
    title=ownValue('title')||window.fccT('Tvoj webinar');description=ownValue('description');
    const parts=ownValue('date').split('-');
    const language=(document.documentElement.lang||'en').split('-')[0],at={hr:'u',sl:'ob',en:'at',de:'um',sr:'u',sq:'në',cnr:'u',fr:'à',es:'a las',sv:'kl.',hu:'ekkor:',pl:'o',ro:'la'}[language]||'at';
    date=parts.length===3?`${parts[2]}. ${parts[1]}. ${parts[0]}. ${at} ${ownValue('time')} · ${ownValue('timezone')}`:window.fccT('Odaberi svoj datum i vrijeme.');
   }
   page.querySelector('[data-event-preview-title]').textContent=title;
   page.querySelector('[data-event-preview-date]').textContent=date;
   page.querySelector('[data-event-preview-description]').textContent=description;
   const summary=config.querySelector('[data-event-summary]');summary.textContent=date;
   config.querySelector('[data-webinar-prepare]').disabled=!creating&&option?.dataset.available!=='1';
   config.querySelector('[data-prepare-label]').textContent=creating?window.fccT('Kreiraj webinar i pozivnicu'):window.fccT('Pripremi moju pozivnicu');
   updateVideo();
  }
  config.querySelector('[data-create-webinar]')?.addEventListener('click',()=>{
   mode.value='custom';eventPreview();config.querySelector('[data-own-title]').focus({preventScroll:true});setup.scrollIntoView({block:'start'});
  });
  config.querySelectorAll('[data-existing-webinar]').forEach(button=>button.addEventListener('click',()=>{
   mode.value='existing';eventPreview();picker.focus({preventScroll:true});config.querySelector('[data-existing-event]').scrollIntoView({block:'start'});
  }));
  radios.forEach(input=>input.addEventListener('change',updateVideo));
  picker?.addEventListener('change',eventPreview);
  own?.addEventListener('input',eventPreview);own?.addEventListener('change',eventPreview);eventPreview();
 }
 const root=document.querySelector('[data-video-composer]');
 if(root){
  const input=root.querySelector('[data-webinar-message]'),wa=root.querySelector('[data-webinar-whatsapp]'),status=root.querySelector('[data-webinar-status]');
  const url=root.dataset.url,key='fcc.webinar.video.v2:'+new URL(url,location.href).origin+':'+(document.documentElement.lang||'en')+':'+root.dataset.owner+':'+root.dataset.id+':'+(root.dataset.revision||'0');
  try{const saved=sessionStorage.getItem(key);if(saved!==null)input.value=saved;}catch{}
  const record=(kind)=>fetch(root.dataset.endpoint,{method:'POST',credentials:'same-origin',keepalive:true,headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({token:root.dataset.csrf,action:'event',id:root.dataset.id,kind})}).catch(()=>{});
  // The editable textarea itself is the final payload. Link repair is visible before navigation.
  function prepared(){
   let text=input.value.trim();
   const pattern=/https?:\/\/[^\s<>]+\/webinar-invite\/[a-f0-9]{64}(?:[?#][^\s<>]*)?/gi;
   let found=false;
   text=text.replace(pattern,()=>{if(found)return '';found=true;return url;});
   if(!found)text+=(text?'\n\n':'')+url;
   input.value=text;
   return text;
  }
  const whatsappUrl=text=>'https://api.whatsapp.com/send?'+(root.dataset.phone?'phone='+encodeURIComponent(root.dataset.phone)+'&':'')+'text='+encodeURIComponent(text);
  function refresh(){
   const text=prepared();wa.href=whatsappUrl(text);
   try{sessionStorage.setItem(key,text);}catch{}
   return text;
  }
  input.addEventListener('input',()=>{wa.href=whatsappUrl(input.value.trim());try{sessionStorage.setItem(key,input.value);}catch{}});
  input.addEventListener('blur',refresh);
  async function currentEvent(){
   if(!root.dataset.eventCheck)return true;
   try{const response=await fetch(root.dataset.eventCheck,{credentials:'same-origin',cache:'no-store'});const result=await response.json();if(response.ok&&result.ok)return true;status.textContent=result.message||window.fccT('Termin nije dostupan. Osvježi prikaz.');}
   catch{status.textContent=window.fccT('Ne mogu provjeriti aktualni termin. Pokušaj ponovno prije slanja.');}
   return false;
  }
  wa.addEventListener('click',async event=>{
   refresh();const destination=wa.href;
   if(!root.dataset.eventCheck){record('whatsapp');return;}
   event.preventDefault();
   if(!await currentEvent())return;
   record('whatsapp');window.location.assign(destination);
  });
  async function copy(text,kind='copy'){
   try{await navigator.clipboard.writeText(text);record(kind);status.textContent=window.fccT('Kopirano. Zalijepi u svoju poruku.');}
   catch{input.focus();input.select();status.textContent=window.fccT('Označi i kopiraj tekst poruke. Automatsko kopiranje nije dostupno.');}
  }
  root.querySelector('[data-webinar-copy]').addEventListener('click',async()=>{if(await currentEvent())await copy(refresh());});
  root.querySelector('[data-webinar-copy-link]').addEventListener('click',async()=>{if(await currentEvent())await copy(url);});
  root.querySelector('[data-webinar-share]').addEventListener('click',async()=>{
   if(!await currentEvent())return;const text=refresh();
   if(navigator.share){try{await navigator.share({title:window.fccT('Poziv na webinar'),text});record('share');return;}catch(e){if(e.name==='AbortError')return;}}
   await copy(text);
  });
  refresh();
 }
 document.querySelectorAll('[data-webinar-player]').forEach(player=>{
  player.querySelector('[data-webinar-play]')?.addEventListener('click',()=>{
   if(player.dataset.embed){
    // Provider URLs are normalized on the server. Never forward the private invitation URL.
    let embed;
    try {
     embed=new URL(player.dataset.embed);
     if(embed.protocol!=='https:' || embed.username || embed.password || embed.port) return;
     const youtube=embed.hostname==='www.youtube-nocookie.com' && /^\/embed\/[A-Za-z0-9_-]{11}$/.test(embed.pathname);
     const vimeo=embed.hostname==='player.vimeo.com' && /^\/video\/[0-9]{5,12}$/.test(embed.pathname);
     if(!youtube&&!vimeo) return;
     if(youtube){embed.searchParams.set('origin',window.location.origin);embed.searchParams.set('playsinline','1');}
    } catch {return;}
    const frame=document.createElement('iframe');
    frame.title=window.fccT('Video pozivnica');frame.width='560';frame.height='315';
    frame.allow='autoplay; encrypted-media; fullscreen; picture-in-picture';frame.allowFullscreen=true;
    frame.referrerPolicy='strict-origin-when-cross-origin';
    frame.src=embed.href;player.replaceChildren(frame);return;
   }
   const slides={
    short:[window.fccT('Dobro došao/la.'),window.fccT('Upoznaj temu i način rada.'),window.fccT('Ako želiš saznati više, prijavi se za webinar.')],
    agenda:[window.fccT('Što te očekuje?'),window.fccT('Predstavljanje, pitanja i konkretni prvi koraci.'),window.fccT('Termin i prijava nalaze se uz ovaj prikaz.')],
    work:[window.fccT('Kako izgleda početak?'),window.fccT('Podrška, edukacija i razgovori s ljudima.'),window.fccT('Na webinaru pitaj ono što ti je važno.')]
   }[player.dataset.demo]||[window.fccT('Probni prikaz')];
   const panel=document.createElement('div');panel.className='fv-demo-slide';
   const title=document.createElement('strong'),note=document.createElement('small'),progress=document.createElement('div'),bar=document.createElement('span'),button=document.createElement('button');
   note.textContent=window.fccT('Probni prikaz, nije službena snimka');progress.className='fv-demo-progress';progress.append(bar);panel.append(title,note,progress);player.replaceChildren(panel);
   const start=Date.now(),duration=12000;const timer=setInterval(()=>{const elapsed=Date.now()-start,index=Math.min(slides.length-1,Math.floor(elapsed/4000));title.textContent=slides[index];bar.style.width=Math.min(100,elapsed/duration*100)+'%';if(elapsed>=duration)clearInterval(timer);},100);title.textContent=slides[0];
  });
 });
 document.querySelectorAll('[data-webinar-followup]').forEach(root=>{
  const input=root.querySelector('textarea'),link=root.querySelector('[data-followup-whatsapp]');
  const refresh=()=>{link.href='https://wa.me/'+root.dataset.phone+'?text='+encodeURIComponent(input.value.trim());};
  input.addEventListener('input',refresh);link.addEventListener('click',refresh);refresh();
 });
})();
