/* Custom code: FC-2026-10-12: copy a prepared message in the Moj put sponsor view. */
(()=>{'use strict';
 document.querySelectorAll('[data-copy-value]').forEach(button=>button.addEventListener('click',async()=>{const value=button.dataset.copyValue||'',done=button.dataset.copyDone||'';try{await navigator.clipboard.writeText(value);if(done){const label=button.textContent;button.textContent=done;setTimeout(()=>{button.textContent=label;},2000);}}catch{window.prompt('',value);}}));
})();
/* /Custom code: FC-2026-10-12 */
