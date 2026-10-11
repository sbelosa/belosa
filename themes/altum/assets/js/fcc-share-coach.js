(function(){'use strict';
 document.querySelectorAll('[data-share-coach]').forEach(function(root){
  if(root.dataset.scReady)return;root.dataset.scReady='1';
  const generate=root.querySelector('[data-sc-generate]');if(!generate)return;
  const copy=JSON.parse(root.dataset.scCopy),situation=root.querySelector('[data-sc-situation]'),status=root.querySelector('[data-sc-status]'),history=root.querySelector('[data-sc-history]'),result=root.querySelector('[data-sc-result]'),message=root.querySelector('[data-sc-message]');
  const composer=root.closest('[data-video-composer],[data-business-composer],[data-webinar-invite],[data-warm-step]');if(!composer)return;
  const main=composer.querySelector('[data-webinar-message],[data-business-message],[data-invite-message],[data-warm-message]'),wa=composer.querySelector('[data-webinar-whatsapp],[data-business-whatsapp],[data-invite-whatsapp],[data-warm-whatsapp]');
  const journey=root.dataset.kind==='journey';
  const selection=()=>journey?{article_id:composer.querySelector('[data-warm-article]')?.value||'0',contact_id:composer.querySelector('[data-warm-contact]')?.value||'0',network:root.querySelector('[data-sc-network]')?.value||''}:{};
  let busy=false,hasDraft=false,selectionVersion=0;
  composer.querySelectorAll('[data-warm-article],[data-warm-contact],[data-sc-network]').forEach(el=>el.addEventListener('change',()=>{selectionVersion++;hasDraft=false;result.hidden=true;message.value='';history.replaceChildren();root.dataset.thread=crypto.randomUUID().replaceAll('-','');status.textContent=copy.changed||copy.stale;}));
  function turn(label,text,isUser){if(!text)return;const box=document.createElement('div'),strong=document.createElement('strong');box.className='fp-share-coach-turn'+(isUser?' is-user':'');strong.textContent=label;box.append(strong,document.createTextNode(text));history.append(box);while(history.children.length>6)history.firstElementChild.remove();}
  async function request(operation,extra){
   const body=new URLSearchParams(Object.assign({action:'share_coach',operation,kind:root.dataset.kind,id:root.dataset.id,fingerprint:root.dataset.fingerprint,token:root.dataset.token,thread:root.dataset.thread},selection(),extra||{}));
   const controller=new AbortController(),timeout=setTimeout(()=>controller.abort(),70000);
   try{const response=await fetch(root.dataset.endpoint,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},body,signal:controller.signal});const data=await response.json();if(!response.ok||!data.ok)throw new Error(data.message||copy.error);return data;}finally{clearTimeout(timeout);}
  }
  function lock(value){busy=value;generate.disabled=value;root.querySelectorAll('[data-sc-send],[data-sc-apply],[data-sc-copy-result]').forEach(b=>b.disabled=value);root.setAttribute('aria-busy',String(value));}
  generate.addEventListener('click',async function(){
   if(busy)return;const text=situation.value.trim();if(text.length<3){status.textContent=copy.input_error;situation.focus();return;}
   const version=selectionVersion;lock(true);status.textContent=copy.working;
   try{if(journey){const context=await request('context');if(version!==selectionVersion)throw new Error(copy.changed);root.dataset.fingerprint=context.fingerprint;}const response=await request('generate',{situation:text,draft:hasDraft?message.value:main.value,request_id:crypto.randomUUID()});if(version!==selectionVersion)throw new Error(copy.changed);turn(copy.you,text,true);turn(copy.coach,response.guidance,false);hasDraft=!!response.recipient_message;result.hidden=!hasDraft;if(hasDraft)message.value=response.recipient_message;if(situation.value.trim()===text)situation.value='';situation.placeholder=copy.followup;status.textContent='';}
   catch(error){status.textContent=error.name==='AbortError'?copy.error:(error.message||copy.error);}
   finally{lock(false);}
  });
  async function use(send){if(busy||!message.value.trim())return;const version=selectionVersion;lock(true);status.textContent='';try{await request('check');if(version!==selectionVersion)throw new Error(copy.changed);main.value=message.value.trim();main.dispatchEvent(new Event('input',{bubbles:true}));main.dispatchEvent(new Event('blur',{bubbles:true}));if(send&&wa){wa.dataset.scDirect='1';const target=wa.getAttribute('target');wa.setAttribute('target','_self');wa.click();delete wa.dataset.scDirect;if(target===null)wa.removeAttribute('target');else wa.setAttribute('target',target);}else status.textContent=copy.applied;}catch(error){status.textContent=error.message||copy.error;}finally{lock(false);}}
  root.querySelector('[data-sc-copy-result]')?.addEventListener('click',async()=>{try{await navigator.clipboard.writeText(message.value.trim());status.textContent=copy.copied;}catch(error){message.focus();message.select();status.textContent=copy.error;}});
  root.querySelector('[data-sc-apply]').addEventListener('click',()=>use(false));root.querySelector('[data-sc-send]')?.addEventListener('click',()=>use(true));
 });
})();
