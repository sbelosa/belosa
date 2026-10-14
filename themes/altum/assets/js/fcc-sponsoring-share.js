/* Custom code: FC-2026-10-14: local sharing controls never send a message automatically. */
(() => {
 const root=document.querySelector('.fs-share');if(!root)return;root.classList.add('js-ready');
 const status=root.querySelector('[data-sponsoring-copy-status]');
 root.querySelectorAll('[data-sponsoring-copy]').forEach(button=>button.addEventListener('click',async()=>{
  const field=document.getElementById(button.dataset.sponsoringCopy);if(!field)return;
  try{await navigator.clipboard.writeText(field.value);status.textContent=status.dataset.copied;}catch{field.focus();field.select();status.textContent=status.dataset.manual;}
 }));
 root.querySelector('[data-sponsoring-language]')?.addEventListener('change',event=>event.currentTarget.form.requestSubmit());
 root.querySelector('[data-sponsoring-message]')?.addEventListener('click',event=>{const field=document.getElementById(event.currentTarget.dataset.sponsoringMessage);if(field)event.currentTarget.href='https://wa.me/?text='+encodeURIComponent(field.value);});
})();
/* /Custom code: FC-2026-10-14 */
