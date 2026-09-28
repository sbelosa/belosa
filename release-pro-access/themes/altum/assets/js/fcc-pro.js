(()=>{
 'use strict';
 const source=document.getElementById('fcc-pro-config');if(!source)return;
 let config;try{config=JSON.parse(source.textContent);}catch{return;}
 if(config.active)return;
 const modal=document.getElementById('fcc-pro-dialog'),base=new URL(config.base);
 let opener=null;
 const featureFor=anchor=>{
  if(anchor.dataset.fccProFeature)return {key:anchor.dataset.fccProFeature,lock:true};
  let url;try{url=new URL(anchor.getAttribute('href'),base);}catch{return null;}
  if(url.origin!==base.origin||!url.pathname.startsWith(base.pathname))return null;
  const path=url.pathname.slice(base.pathname.length).replace(/\/$/,'');
  if(path==='partner/team')return {key:'team',lock:true};
  if((path==='partner'&&url.searchParams.get('view')==='coach')||path==='ai-plan')return {key:'coach',lock:true};
  if(path==='vip-funnel-studio')return {key:'funnel',lock:true};
  if(path==='partner-webinar'){
   if(!url.searchParams.has('view')&&!url.searchParams.has('id'))return {key:'webinar',lock:true};
   if(['events','edit'].includes(url.searchParams.get('view')))return {key:'webinar',lock:false};
  }
  if(path==='partner-marketing'&&!url.searchParams.has('share'))return {key:'marketing',lock:!url.searchParams.has('id')};
  return null;
 };
 const enhance=root=>root.querySelectorAll('a[href]:not([data-pro-enhanced])').forEach(a=>{
  if(a.closest('#fcc-pro-dialog'))return;
  const f=featureFor(a);if(!f||!config.features[f.key])return;
  a.dataset.proEnhanced='1';a.dataset.fccProFeature=f.key;
  if(!a.querySelector('.fcc-pro-badge,.fv-pro')&&!/\bPRO\b/.test(a.textContent)){
   const badge=document.createElement('span');badge.className='fcc-pro-badge';badge.textContent='PRO';(a.querySelector('strong,h2')||a).append(badge);
  }
  if(!config.active&&f.lock){a.href=new URL('account-plan?feature='+f.key,base).href;a.dataset.fccProLocked='1';a.setAttribute('aria-haspopup','dialog');}
 });
 enhance(document);
 document.addEventListener('click',event=>{
  const a=event.target.closest('a[data-fcc-pro-locked="1"]');
  if(!a||event.button!==0||event.ctrlKey||event.metaKey||event.shiftKey||event.altKey||!modal||typeof modal.showModal!=='function')return;
  const f=config.features[a.dataset.fccProFeature];if(!f)return;
  event.preventDefault();event.stopImmediatePropagation();opener=a;
  modal.querySelector('#fcc-pro-title').textContent=f.title;
  modal.querySelector('#fcc-pro-description').textContent=f.description;
  modal.querySelector('[data-pro-activate]').href=a.href;
  if(!modal.open)modal.showModal();
 },true);
 modal?.querySelectorAll('[data-pro-close]').forEach(b=>b.addEventListener('click',()=>modal.close()));
 modal?.addEventListener('close',()=>opener?.focus());
 modal?.addEventListener('click',e=>{if(e.target===modal){const r=modal.getBoundingClientRect();if(e.clientX<r.left||e.clientX>r.right||e.clientY<r.top||e.clientY>r.bottom)modal.close();}});
})();
