(() => {
 'use strict';
 // Old saved invitations and inbox notifications used the catalogue's bottom anchor.
 const redirectWebinar=()=>{
  if(location.hash==='#webinar'&&/\/partner\/share\/?$/.test(location.pathname)&&new URLSearchParams(location.search).get('type')!=='webinar'){
   const target=new URL(location.href);target.search='?type=webinar';target.hash='';location.replace(target.href);return true;
  }
  return false;
 };
 window.addEventListener('hashchange',redirectWebinar);
 if(redirectWebinar())return;
 const appMode=window.matchMedia('(display-mode: standalone)');
 const localPreview=()=>{try{return window.frameElement?.dataset.fccPreviewMode==='app';}catch{return false;}};
 const updateMode=()=>document.documentElement.classList.toggle('fp-standalone',appMode.matches||navigator.standalone===true||localPreview());
 updateMode();appMode.addEventListener?.('change',updateMode);
 window.addEventListener('fcc-preview-mode',updateMode);
 const toast=(message)=>{let el=document.querySelector('.fp-toast');if(!el){el=document.createElement('div');el.className='fp-toast';el.role='status';document.body.append(el);}el.textContent=message;el.hidden=false;setTimeout(()=>el.hidden=true,4000);};
 const copy=async(text)=>{try{await navigator.clipboard.writeText(text);toast(window.fccT('Kopirano.'));}catch{toast(window.fccT('Kopiranje nije dostupno. Otvori poveznicu i kopiraj adresu iz preglednika.'));}};
 document.addEventListener('click',async(event)=>{const c=event.target.closest('[data-copy]:not([data-clipboard-text])');if(c){event.preventDefault();await copy(c.dataset.copy);return;}const s=event.target.closest('[data-share-url]');if(s){event.preventDefault();if(navigator.share){try{await navigator.share({title:s.dataset.shareTitle||'FCC',url:s.dataset.shareUrl});}catch(e){if(e.name!=='AbortError')await copy(s.dataset.shareUrl);}}else await copy(s.dataset.shareUrl);}});
 // WhatsApp opens a draft only. The partner selects the recipient and confirms sending.
 document.querySelectorAll('.fp-section-share [data-share-url]').forEach(button=>{
  const link=document.createElement('a');link.className='fp-button fp-button-outline fp-whatsapp-share';link.target='_blank';link.rel='noopener noreferrer';
  const title=button.dataset.shareTitle||window.fccT('moja FCC kartica');
  const message=button.dataset.shareMessage||(window.fccT('Bok! Šaljem ti ')+title+'. Ovdje možeš pogledati više informacija i javiti mi se ako imaš pitanja.');
  link.href='https://wa.me/?text='+encodeURIComponent(message+'\n'+button.dataset.shareUrl);link.textContent=window.fccT('Pošalji na WhatsApp');button.insertAdjacentElement('afterend',link);
 });
 document.querySelectorAll('[data-ai-form]').forEach(form=>form.addEventListener('submit',()=>{if(!navigator.onLine)return;form.querySelector('[type=submit],button').disabled=true;form.querySelector('.fp-ai-wait').hidden=false;}));
 const connection=()=>{document.querySelectorAll('.fp-offline').forEach(el=>el.hidden=navigator.onLine);};window.addEventListener('online',connection);window.addEventListener('offline',connection);connection();
 // Never cache a submitted form or private customer data. All writes need a live server.
 document.addEventListener('submit',event=>{if(!navigator.onLine){event.preventDefault();toast(window.fccT('Poveži se na internet prije spremanja.'));}});


 // Put assistance in the app header so it cannot cover forms or bottom navigation.
 const integrateHelp=()=>{
  const menu=document.querySelector('.fp-help-menu');if(!menu)return;
  const actions=menu.querySelector('[data-help-actions]');
  const sources=[['.fcc-help-button',window.fccT('Video upute')]];
  sources.forEach(([selector,label])=>{const source=document.querySelector(selector);if(!source)return;const button=document.createElement('button');button.type='button';button.textContent=label;button.addEventListener('click',()=>{menu.open=false;source.click();});actions.append(button);source.setAttribute('tabindex','-1');});
  if(actions.children.length){menu.hidden=false;document.body.classList.add('fp-integrated-help');}
  const helpPanel=document.querySelector('.fcc-help-panel');const helpButton=document.querySelector('.fcc-help-button');
  if(helpPanel&&helpButton){const close=document.createElement('button');close.type='button';close.className='fp-help-close';close.textContent='Zatvori';close.addEventListener('click',()=>{helpButton.click();menu.querySelector('summary').focus();});helpPanel.querySelector('.fcc-help-panel-header')?.append(close);}
  document.addEventListener('click',event=>{if(!menu.contains(event.target))menu.open=false;});
  menu.addEventListener('keydown',event=>{if(event.key==='Escape'){menu.open=false;menu.querySelector('summary').focus();}});
 };
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',integrateHelp);else integrateHelp();
 const channel='BroadcastChannel' in window?new BroadcastChannel('fcc-partner'):null;
 if(channel){document.querySelectorAll('form[method=post]').forEach(form=>form.addEventListener('submit',()=>channel.postMessage('change')));channel.onmessage=()=>{document.body.dataset.fccRefresh='1';};}
 // Refresh server truth on return to an idle screen; never discard an edited form.
 let dirty=false;const busy=()=>dirty||!!document.querySelector('.modal.show')||document.activeElement?.matches('input,textarea,select,[contenteditable=true]');document.addEventListener('input',()=>dirty=true);document.addEventListener('change',()=>dirty=true);
 document.addEventListener('visibilitychange',()=>{if(!document.hidden&&!busy()&&document.body.dataset.fccRefresh==='1')location.reload();});
 if(document.body.classList.contains('fcc-partner-mode')){
  const base=document.querySelector('base')?.href||location.origin+'/';let revision=null;
  let checking=false;
  const check=async()=>{
   if(checking||document.hidden||!navigator.onLine)return;
   checking=true;
   const controller=new AbortController();
   const timeout=setTimeout(()=>controller.abort(),10000);
   try{
    const response=await fetch(new URL('partner/state?module='+encodeURIComponent(document.querySelector('.fp-page')?.className.match(/fp-section-([a-z]+)/)?.[1]||'other'),base),{credentials:'same-origin',cache:'no-store',signal:controller.signal});
    if(!response.ok||!response.headers.get('content-type')?.includes('application/json'))return;
    const state=await response.json();
    window.fccNoticeBadge?.(state.unread);
    if(revision&&state.revision!==revision){if(!busy())location.reload();else toast(window.fccT('Podaci su ažurirani u drugom prikazu. Dovrši unos pa osvježi stranicu.'));}
    revision=state.revision;
   }catch{}finally{clearTimeout(timeout);checking=false;}
  };
  setTimeout(check,3000);setInterval(check,60000);document.addEventListener('visibilitychange',check);window.addEventListener('pageshow',check);
 }
})();

// Presentation-only navigation. No writes, duplicated state or alternate save paths.
(() => {
 document.querySelectorAll('[data-fp-panels]').forEach(root => {
  const panels=[...root.querySelectorAll('[data-fp-panel-content]')];
  const buttons=[...root.querySelectorAll('[data-fp-panel]')];
  const show=id=>{
   if(!panels.some(p=>p.id===id))return;
   panels.forEach(p=>p.hidden=p.id!==id);
   buttons.forEach(b=>{if(b.hasAttribute('aria-pressed'))b.setAttribute('aria-pressed',String(b.dataset.fpPanel===id));});
  };
  buttons.forEach(b=>b.addEventListener('click',()=>show(b.dataset.fpPanel)));
  if(panels.length)show(panels[0].id);
 });
})();
document.querySelectorAll('.fp-los-analysis').forEach(details => details.addEventListener('toggle', () => {
 if(details.open)details.querySelectorAll('canvas').forEach(canvas => window.Chart?.getChart?.(canvas)?.resize());
}));

// Progressive disclosure keeps the LOS overview usable without removing a report.
(() => {
 const root=document.querySelector('.leader-os-page');
 if(!root||!root.querySelector('.leader-os-primary-grid'))return;
 const group=(nodes,label)=>{
  nodes=nodes.filter(Boolean);if(!nodes.length)return;
  const details=document.createElement('details');details.className='fp-disclosure fp-los-extra';
  const summary=document.createElement('summary');summary.textContent=label;details.append(summary);
  nodes[0].before(details);nodes.forEach(node=>details.append(node));return details;
 };
 group([...root.querySelectorAll('.leader-os-summary-signal-grid,.leader-os-summary-detail-grid')],window.fccT('Signali, objašnjenja i preporučeni koraci'));
 const trend=root.querySelector('#leader-os-team-trend-root');
 if(trend)group([trend.closest('.row')],window.fccT('Promet, konverzije i raspored tima'));
 const overview=root.querySelector('.leader-os-primary-strip').parentElement;
 group([...overview.children].filter(node=>node.classList.contains('leader-os-grid-3')),window.fccT('Usporedba suradnika'));
 group([...overview.children].filter(node=>node.classList.contains('row')),window.fccT('Blog, AI i razvoj tržišta'));
 const filters=root.querySelector('input[type="search"]')?.closest('.leader-os-shell');
 if(filters){
  const disclosure=group([filters],window.fccT('Pretraži i filtriraj suradnike'));
  const query=new URLSearchParams(location.search);
  disclosure.open=!!query.get('search')||['status','ai_status','anomaly_status','fraud_status'].some(key=>query.has(key)&&query.get(key)!=='all');
 }

 root.querySelectorAll('.leader-os-queue-meta').forEach(node=>group([node],window.fccT('Detalji suradnika i preporuka')));
 // Guidance repeats across metrics; its full text remains a click away.
 root.querySelectorAll('.leader-os-summary-guide').forEach(node=>group([node],window.fccT('Kako čitati ovaj pokazatelj')));
 root.querySelectorAll('.leader-os-primary-card').forEach(card=>{
  const hint=card.querySelector('.leader-os-primary-hint');
  if(hint){
   const help=card.querySelector('.leader-os-primary-help');
   if(help){const explanation=[help.getAttribute('data-original-title')||help.title,hint.textContent].filter(Boolean).join(' ');help.title=explanation;help.setAttribute('data-original-title',explanation);hint.hidden=true;}
  }
 });
})();

// Put the member's result before the full rules, keeping direct explanation links valid.
(() => {
 const root=document.querySelector('.fcc-results-page');
 const explanation=root?.querySelector('#objasnjenje-klikova');
 const results=root?.querySelector(':scope>.row');
 if(!explanation||!results)return;
 const body=explanation.querySelector('.card-body');
 const title=body?.querySelector('h2');
 if(!body||!title)return;
 const details=document.createElement('details');details.className='fp-disclosure fp-result-rules';details.id=explanation.id;
 const summary=document.createElement('summary');summary.textContent=title.textContent;details.append(summary);title.remove();
 root.querySelectorAll('.fcc-hero-note,.fcc-hero-card .fcc-metric-note').forEach(note=>body.append(note));
 details.append(body);explanation.remove();results.before(details);
 const reveal=()=>{if(location.hash==='#objasnjenje-klikova'){details.open=true;details.scrollIntoView({block:'start'});}};
 window.addEventListener('hashchange',reveal);reveal();
})();

// Contact export submits the current form. WhatsApp uses only the saved number.
(() => {
 const form=document.querySelector('[data-contact-edit]');
 const link=document.querySelector('[data-contact-whatsapp]');
 if(!form||!link)return;
 const fields=['phone','contact_permission'];
 const initial=Object.fromEntries(fields.map(k=>[k,form.elements[k].value]));
 const update=()=>{link.hidden=fields.some(k=>form.elements[k].value!==initial[k]);};
 form.addEventListener('input',update);form.addEventListener('change',update);
})();

(() => {
 window.fccNoticeBadge=n=>{if(!Number.isFinite(n))return;document.querySelectorAll('[data-fcc-bell]').forEach(b=>{let v=b.querySelector('.fp-unread');if(n===0){v?.remove();return;}if(!v){v=document.createElement('span');v.className='fp-unread';b.append(v);}v.textContent=Math.min(99,n);});};
 document.querySelectorAll('[data-notice-open]').forEach(f=>f.addEventListener('submit',()=>{const row=f.closest('.fp-notice');if(row?.classList.contains('is-unread')){row.classList.remove('is-unread');const n=Number(document.querySelector('[data-fcc-bell] .fp-unread')?.textContent||0);window.fccNoticeBadge(Math.max(0,n-1));}}));
})();
