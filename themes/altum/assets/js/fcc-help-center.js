(()=>{'use strict';
 const page=document.querySelector('[data-fcc-help-page]');if(!page)return;
 const status=page.querySelector('[data-fcc-help-status]');
 const notify=text=>{if(status)status.textContent=text;};
 page.addEventListener('click',async event=>{
  const copy=event.target.closest('[data-fcc-help-copy]');
  if(copy){try{await navigator.clipboard.writeText(copy.dataset.fccHelpCopy);notify(status.dataset.copied);}catch(_){notify(status.dataset.copyError);}return;}
  const coach=event.target.closest('[data-fcc-help-coach]');
  if(coach){
   const api=window.FccCoachShare;
   if(api&&typeof api.prefillHelp==='function'){
    const accepted=api.prefillHelp({articleId:coach.dataset.fccHelpCoach,section:coach.dataset.fccHelpSection,prompt:coach.dataset.fccHelpPrompt});
    if(!accepted)notify(window.fccHelpCoachLastStatus==='draft'?status.dataset.coachDraft:status.dataset.coachUnavailable);
   }else notify(status.dataset.coachUnavailable);
   return;
  }
  if(event.target.closest('[data-fcc-help-print]')){
   const details=[...page.querySelectorAll('details')];const open=details.map(d=>d.open);details.forEach(d=>d.open=true);
   const restore=()=>{details.forEach((d,i)=>d.open=open[i]);window.removeEventListener('afterprint',restore);};window.addEventListener('afterprint',restore);window.print();
  }
 });
 const anchor=page.dataset.fccHelpAnchor;
 if(anchor&&!location.hash){const section=document.getElementById(anchor);if(section)requestAnimationFrame(()=>section.scrollIntoView({block:'start'}));}
})();
