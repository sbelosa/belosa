(() => {
 'use strict';
 function init() {
  const root=document.querySelector('[data-fl-library]'); if(!root)return;
  const product= root.dataset.productSlug;
  const appScroller=()=>window.matchMedia('(max-width: 800px)').matches?document.querySelector('.fp-mobile-shell > .app-container'):null;
  const scrollPosition=()=>appScroller()?.scrollTop??window.scrollY;
  const emit=(name,extra={})=>{
   const selectedProduct=product||extra.product_id;
   if(!selectedProduct)return;
   const detail={event:name,product_id:selectedProduct,market:root.dataset.market||'',language:root.dataset.locale||'en',...extra};
   // Public product dimensions only. Never send the referral, phone, URL query or message.
   window.dispatchEvent(new CustomEvent('fcc:product-event',{detail}));
  };
  let viewed=false;
  const productView=()=>{if(!viewed&&window.fccProductAnalyticsAllowed?.()){emit('fcc_product_view');viewed=true;}};
  window.addEventListener('fcc:analytics-ready',productView);productView();
  root.addEventListener('click',event=>{
   const a=event.target.closest('a,button');if(!a)return;
   if(!product && a.closest('[data-fl-product]')){
    const headingLink=a.closest('[data-fl-product]').querySelector('h2 a');
    if(headingLink)emit('fcc_product_list_select',{product_id:new URL(headingLink.href).pathname.split('/').filter(Boolean).pop(),placement:'library'});
   }
   const type=a.dataset.flEvent || (a.matches('[data-fl-offer]')?'fcc_product_order':a.matches('[data-fl-category]')?'fcc_product_alternative':a.matches('[data-fl-contact-whatsapp]')?'fcc_product_whatsapp':a.matches('[data-fl-contact-phone]')?'fcc_product_phone':a.matches('[data-fl-related]')?'fcc_product_related':a.matches('[data-fl-compose]')?'fcc_product_share':null);
   if(type)emit(type,{placement:a.hasAttribute('data-fl-sticky-action')?'sticky':'page',...(a.dataset.flRelated?{target_product:a.dataset.flRelated}:{})});
  });
  root.querySelector('.fl-country-form')?.addEventListener('submit',()=>emit('fcc_product_market',{selected_market:root.querySelector('#fl-market').value}));
  root.querySelectorAll('[data-fl-faq]').forEach(el=>el.addEventListener('toggle',()=>{if(el.open)emit('fcc_product_question',{question:el.dataset.flFaq});}));
  const sticky=root.querySelector('[data-fl-sticky]'),purchase=root.querySelector('.fl-purchase');
  let stickyTick=false;
  function updateSticky(){
   stickyTick=false;if(!sticky||!purchase)return;
   const action=root.querySelector('[data-fl-offer], [data-fl-category]')||root.querySelector('[data-fl-contact-whatsapp], [data-fl-contact-profile]');
   const keyboard=window.visualViewport&&window.visualViewport.height<window.innerHeight*.72;
   const modal=document.querySelector('dialog[open],.fcc-chat-extreme.is-open');
   const show=!!action&&window.innerWidth<=700&&!keyboard&&!modal&&!document.body.classList.contains('fl-dialog-open')&&purchase.getBoundingClientRect().bottom<100;
   sticky.hidden=!show;document.body.classList.toggle('fl-sticky-active',show);
   if(!show)return;
   const nav=document.querySelector('.fp-bottom-nav'),rect=nav?.getBoundingClientRect();
   const bottom=rect&&rect.height>0&&rect.top<innerHeight?Math.max(12,innerHeight-rect.top+8):12;
   root.style.setProperty('--fl-fixed-bottom',bottom+'px');document.body.style.setProperty('--fl-launcher-bottom',bottom+'px');
   const link=sticky.querySelector('[data-fl-sticky-action]');link.href=action.href;link.target=action.target;link.rel=action.rel;
   link.textContent=action.matches('[data-fl-offer]')?(root.dataset.locale==='hr'?window.fccT('Naruči iz Forevera'):window.fccT('Order from Forever')):(action.matches('[data-fl-category]')?(root.dataset.locale==='hr'?window.fccT('Drugi proizvodi'):window.fccT('Other products')):(root.dataset.locale==='hr'?window.fccT('Pitaj suradnika'):window.fccT('Ask your distributor')));
   link.dataset.flEvent=action.matches('[data-fl-offer]')?'fcc_product_order':action.matches('[data-fl-category]')?'fcc_product_alternative':action.matches('[data-fl-contact-whatsapp]')?'fcc_product_whatsapp':'fcc_product_contact';
   sticky.querySelector('[data-fl-sticky-price]').textContent=root.querySelector('[data-fl-price]')?.textContent||root.querySelector('#fl-market option:checked')?.textContent||'';
  }
  const scheduleSticky=()=>{if(!stickyTick){stickyTick=true;requestAnimationFrame(updateSticky);}};
  if(sticky){document.addEventListener('scroll',scheduleSticky,{passive:true,capture:true});window.addEventListener('resize',scheduleSticky);window.visualViewport?.addEventListener('resize',scheduleSticky);root.addEventListener('click',scheduleSticky);document.querySelectorAll('.fcc-chat-extreme').forEach(chat=>new MutationObserver(scheduleSticky).observe(chat,{attributes:true,attributeFilter:['class']}));updateSticky();}
  const priceRefresh=root.querySelector('[data-fl-refresh]');
  if(priceRefresh){
   const controller=new AbortController(),timer=setTimeout(()=>controller.abort(),24000);
   fetch(priceRefresh.dataset.flRefresh,{credentials:'same-origin',signal:controller.signal,cache:'no-store'})
    .then(r=>r.ok?r.json():null).then(data=>{
     if(data?.html){root.querySelector('[data-fl-purchase-result]').innerHTML=data.html;updateSticky();}
    }).catch(()=>{}).finally(()=>{clearTimeout(timer);priceRefresh.remove();});
  }
  const dialog=root.querySelector('#fl-composer'), message=dialog?.querySelector('#fl-message');
  const photoDialog=root.querySelector('[data-fl-photo-dialog]'),photoOpen=root.querySelector('[data-fl-photo-open]');
  if(photoDialog&&photoOpen){
   photoOpen.addEventListener('click',()=>{photoDialog.showModal();scheduleSticky();});
   photoDialog.querySelector('[data-fl-photo-close]').addEventListener('click',()=>photoDialog.close());
   photoDialog.addEventListener('close',()=>{photoOpen.focus();scheduleSticky();});
  }
  const store={get(k){try{return sessionStorage.getItem(k)}catch{return null}},set(k,v){try{sessionStorage.setItem(k,v)}catch{}}};
  let active=null, opener=null;
  function preparedMessage(){
   if(!active)return '';
   const canonical=new URL(active.url,location.href),ref=canonical.searchParams.get('ref');
   let text=message.value.trim();
   // Preserve the authored text; normalize only our own article referral links.
   text=text.replace(/https?:\/\/[^\s<>]+/g,raw=>{
    const tail=raw.match(/[.,;!?)]+$/)?.[0]||'', clean=tail?raw.slice(0,-tail.length):raw;
    try{const u=new URL(clean);if(u.origin===canonical.origin&&/\/(?:[a-z]{2,3}\/)?blog\//.test(u.pathname)&&ref){u.searchParams.set('ref',ref);return u.href+tail}}catch{}
    return raw;
   });
   if(!text.includes(canonical.href))text+=(text?'\n\n':'')+canonical.href;
   return text;
  }
  function refresh(){if(active){dialog.querySelector('[data-fl-whatsapp]').href='https://wa.me/?text='+encodeURIComponent(preparedMessage());store.set(active.key,message.value);}}
  root.querySelectorAll('[data-fl-compose]').forEach(trigger=>trigger.addEventListener('click',event=>{
   if(!dialog||typeof dialog.showModal!=='function')return;
   event.preventDefault();opener=trigger;
   active={url:trigger.dataset.flUrl,initial:trigger.dataset.flMessage,key:'fcc.library.draft.v1:'+trigger.dataset.flId+':'+trigger.dataset.flUrl};
   message.value=store.get(active.key)??active.initial;
   dialog.querySelector('[data-fl-title]').textContent=trigger.dataset.flTitle;
   const img=dialog.querySelector('[data-fl-image]');img.src=trigger.dataset.flImage;img.alt=trigger.dataset.flTitle;
   dialog.querySelector('[data-fl-status]').textContent='';refresh();
   document.body.classList.add('fl-dialog-open');dialog.showModal();dialog.querySelector('[data-fl-close]').focus();
  }));
  if(dialog){
   dialog.querySelector('[data-fl-close]').addEventListener('click',()=>dialog.close());
   dialog.addEventListener('close',()=>{document.body.classList.remove('fl-dialog-open');opener?.focus();scheduleSticky();});
   message.addEventListener('input',refresh);
   message.addEventListener('blur',()=>{if(active){message.value=preparedMessage();refresh();}});
   dialog.querySelector('[data-fl-whatsapp]').addEventListener('click',()=>{
    // Read the latest textarea value in the click handler, including autofill and last edits.
    message.value=preparedMessage();refresh();
   });
   dialog.querySelector('[data-fl-copy]').addEventListener('click',async()=>{
    const text=preparedMessage();message.value=text;refresh();
    try{await navigator.clipboard.writeText(text);dialog.querySelector('[data-fl-status]').textContent=dialog.dataset.copyOk}
    catch{message.focus();message.select();dialog.querySelector('[data-fl-status]').textContent=dialog.dataset.copyError}
   });
   dialog.querySelector('[data-fl-reset]').addEventListener('click',()=>{
    if(active&&confirm(dialog.dataset.resetConfirm)){message.value=active.initial;refresh();dialog.querySelector('[data-fl-status]').textContent='';}
   });
  }
  const search=root.querySelector('#fl-search');if(!search)return;
  const tiles=[...root.querySelectorAll('[data-fl-product]')], filters=[...root.querySelectorAll('[data-fl-filter]')];
  const key='fcc.library.list.v1:'+location.pathname;
  let selected='all',saved=null;
  try{saved=JSON.parse(store.get(key)||'null')}catch{}
  const params=new URLSearchParams(location.search);
  if(params.has('q'))search.value=params.get('q');else if(saved)search.value=saved.q||'';
  if(filters.some(b=>b.dataset.flFilter===(params.get('topic')||saved?.topic)))selected=params.get('topic')||saved.topic;
  const normalize=s=>s.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLocaleLowerCase();
  function filter(){let count=0;const q=normalize(search.value.trim());tiles.forEach(tile=>{const visible=(selected==='all'||selected===tile.dataset.flTopic)&&normalize(tile.dataset.flSearch).includes(q);tile.hidden=!visible;if(visible)count++;});root.querySelector('.fl-empty').hidden=count>0;filters.forEach(b=>b.setAttribute('aria-pressed',String(b.dataset.flFilter===selected)));store.set(key,JSON.stringify({q:search.value,topic:selected,scroll:scrollPosition()}));}
  search.addEventListener('input',filter);
  filters.forEach(b=>b.addEventListener('click',()=>{selected=b.dataset.flFilter;filter();}));
  root.querySelector('[data-fl-clear]').addEventListener('click',()=>{selected='all';search.value='';filter();search.focus();});
  window.addEventListener('pagehide',()=>store.set(key,JSON.stringify({q:search.value,topic:selected,scroll:scrollPosition()})));
  const returnScroll=saved?.scroll;filter();
  if(performance.getEntriesByType('navigation')[0]?.type==='back_forward'&&returnScroll)requestAnimationFrame(()=>(appScroller()||window).scrollTo(0,returnScroll));
 }
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
