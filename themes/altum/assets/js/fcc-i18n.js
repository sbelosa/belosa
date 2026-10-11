/* Application-owned copy only. Never transform customer content or identifiers. */
window.fccCopy=window.fccCopy||{};
window.fccT=(source,replacements={})=>{let value=window.fccCopy[source];if(value===undefined){const trimmed=source.trim();value=window.fccCopy[trimmed]!==undefined?source.slice(0,source.indexOf(trimmed))+window.fccCopy[trimmed]+source.slice(source.indexOf(trimmed)+trimmed.length):source;}for(const [key,text] of Object.entries(replacements))value=value.split(key).join(String(text));return value;};
/* Native submit buttons retain keyboard support and work without JavaScript. */
document.addEventListener('click',event=>{document.querySelectorAll('.fcc-language-picker[open]').forEach(picker=>{if(!picker.contains(event.target))picker.open=false;});});
document.addEventListener('keydown',event=>{if(event.key!=='Escape')return;document.querySelectorAll('.fcc-language-picker[open]').forEach(picker=>{picker.open=false;picker.querySelector('summary')?.focus();});});
document.addEventListener('toggle',event=>{if(!event.target.matches?.('.fcc-language-picker[open]'))return;document.querySelectorAll('.fcc-language-picker[open]').forEach(picker=>{if(picker!==event.target)picker.open=false;});},true);
