(() => {
 'use strict';
 const editor=document.querySelector('.fp-editor');
 if(!editor)return;
 const form=editor.querySelector('#update_biolink');
 const designIds=new Set(['fcc_biolink_theme_button','theme_container','fcc_biolink_settings_customizations_button','customizations_container']);
 if(form){
  for(const child of form.children){
   if(child.matches('input[type=hidden],.notification-container')||child.querySelector('button[type=submit]'))continue;
   child.dataset.editorGroup=designIds.has(child.id)?'design':'settings';
   if(child.matches('small')&&child.previousElementSibling?.dataset.editorGroup==='design')child.dataset.editorGroup='design';
  }
  const save=form.querySelector('button[type=submit]');if(save)save.textContent=window.fccT('Spremi promjene');
 }
 const descriptions={blocks:window.fccT('Dodirni blok za uređivanje. Povuci ručicu za promjenu redoslijeda.'),design:window.fccT('Odaberi temu ili prilagodi boje, pozadinu i font.'),settings:window.fccT('Uredi poveznicu i postavke kartice. Nakon izmjene spremi promjene.'),preview:window.fccT('Ovako izgleda tvoja kartica. Pregled uređivanja ne zbraja klikove.')};
 let current='blocks';
 const show=(mode)=>{
  if(!Object.hasOwn(descriptions,mode))mode='blocks';current=mode;editor.dataset.editorMode=mode;
  editor.querySelectorAll('[data-editor-tab]').forEach(button=>button.setAttribute('aria-pressed',String(button.dataset.editorTab===mode)));
  editor.querySelector('[data-editor-description]').textContent=descriptions[mode];editor.querySelector('[data-editor-add]').hidden=mode!=='blocks';
  if(window.jQuery)window.jQuery(mode==='blocks'?'#blocks-tab':'#settings-tab').tab('show');
  const appearance=editor.querySelector('#customizations_container');
  if(mode==='design'&&appearance&&window.jQuery)window.jQuery(appearance).collapse('show');
  const url=new URL(location.href);url.searchParams.set('tab',mode==='blocks'?'blocks':'settings');url.searchParams.set('editor',mode);
  const plain=value=>value!==null&&typeof value==='object'&&!Array.isArray(value);
  const state=plain(history.state)?{...history.state}:history.state;
  if(plain(state)&&plain(state.fccMobileNav))state.fccMobileNav={...state.fccMobileNav,url:url.href};
  history.replaceState(state,'',url);
  window.dispatchEvent(new CustomEvent('fcc:editor-view-changed'));
 };
 editor.querySelectorAll('[data-editor-tab]').forEach(button=>button.addEventListener('click',()=>show(button.dataset.editorTab)));
 const params=new URLSearchParams(location.search);show(params.get('editor')||(params.get('tab')==='settings'?'settings':'blocks'));
 // Existing saves and cache invalidation remain the single write path.
 if(window.jQuery)window.jQuery(document).ajaxSuccess(()=>{
  const link=editor.querySelector('#link_full_url');const share=editor.querySelector('[data-share-url]');
  if(link&&share)share.dataset.shareUrl=link.href;
 });
})();
