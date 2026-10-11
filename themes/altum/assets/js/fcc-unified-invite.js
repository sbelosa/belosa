(() => {
 'use strict';
 const root=document.querySelector('[data-webinar-invite]');
 const form=root?.querySelector('[data-invite-customize]');
 if(!form)return;
 const choice=form.querySelector('[name="video_choice"]'),custom=form.querySelector('[data-invite-video-url]'),input=custom.querySelector('input');
 function update(){custom.hidden=choice.value!=='custom';input.required=choice.value==='custom';input.disabled=choice.value!=='custom';}
 choice.addEventListener('change',update);update();
 form.addEventListener('submit',()=>{
  const message=root.querySelector('[data-invite-message]');
  form.querySelector('[name="message_draft"]').value=message?.value||'';
 });
})();
