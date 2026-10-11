(()=>{'use strict';
 document.querySelectorAll('[data-learning-topic] select').forEach(select=>select.addEventListener('change',()=>{const form=select.form;form.action=form.action.split('#')[0]+'#business-learning';form.requestSubmit();}));
 document.addEventListener('click',event=>{
  const link=event.target.closest('[data-education-coach]');if(!link)return;
  const chat=document.querySelector('[data-fcc-chat-extreme].is-coach');
  const launcher=chat?.querySelector('.fcc-chat-extreme__launcher');
  const input=chat?.querySelector('[data-chat-extreme-form] [name="message"]');
  if(!launcher||!input)return;
  event.preventDefault();launcher.click();
  if(!input.value.trim()){input.value=link.dataset.educationCoach;input.dispatchEvent(new Event('input',{bubbles:true}));}
  input.focus();
 });
})();
