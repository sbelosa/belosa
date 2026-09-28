(() => {
 'use strict';
 const root = document.querySelector('[data-team-outreach]');
 if (!root) return;
 const one = selector => root.querySelector(selector);
 const config = JSON.parse(one('[data-team-config]').textContent);
 const message = one('[data-team-message]'), situation = one('[data-team-situation]');
 const status = one('[data-team-status]'), due = one('[data-team-due]');
 const key = () => Array.from(crypto.getRandomValues(new Uint8Array(16)), n => n.toString(16).padStart(2, '0')).join('');
 let purpose = 'checkin', draft = null, requestKey = key(), busy = false;
 function say(text, error = false) { status.textContent = text; status.classList.toggle('is-error', error); }
 function sync() {
  root.querySelectorAll('[data-purpose]').forEach(b => b.setAttribute('aria-pressed', String(b.dataset.purpose === purpose)));
  one('[data-team-open]').disabled = busy || root.dataset.phoneAvailable !== '1' || !message.value.trim() || !!draft?.sent_self_reported_at;
  const confirmable = draft?.opened_at && !draft.sent_self_reported_at && draft.message === message.value.trim();
  one('[data-team-confirm-panel]').hidden = !confirmable;
  one('[data-team-draft-status]').textContent = draft?.status_label || '';
 }
 function setBusy(value) {
  busy = value;
  root.querySelectorAll('button').forEach(b => { b.disabled = value; });
  root.querySelectorAll('textarea,input[type="date"]').forEach(el => { el.readOnly = value; });
  root.setAttribute('aria-busy', String(value)); sync();
 }
 async function request(operation, extra = {}) {
  const body = new URLSearchParams({action: 'team_outreach', token: one('[name="token"]').value,
   member_id: root.dataset.member, operation, request_key: requestKey, draft_id: draft?.id || '0',
   version: draft?.version || '0', contact_stamp: root.dataset.contactStamp, purpose,
   message: message.value, situation: situation.value, due_date: due.value, ...extra});
  const response = await fetch(root.dataset.endpoint, {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'}, body});
  if (response.redirected) throw new Error('Sesija je istekla. Tekst je ostao u obrascu. Prijavi se pa osvježi stranicu.');
  const result = await response.json();
  if (!response.ok || !result.ok) throw new Error(result.message || 'Spremanje nije uspjelo. Pokušaj ponovno.');
  return result;
 }
 function resume(value) {
  draft = value; requestKey = value.request_key; purpose = value.purpose;
  message.value = value.message; due.value = value.due_date || '';
  one('[data-team-guidance]').textContent = value.guidance || '';
  one('[data-team-guidance]').hidden = !value.guidance;
  sync();
 }
 function renderHistory(value) {
  let item = root.querySelector(`[data-outreach-id="${Number(value.id)}"]`);
  if (!item) { item = document.createElement('article'); item.className = 'ft-outreach-item'; one('[data-team-history]').prepend(item); }
  item.dataset.outreachId = value.id; item.dataset.outreachVersion = value.version; item.dataset.requestKey = value.request_key;
  item.replaceChildren();
  const add = (tag, text, parent = item) => { const el = document.createElement(tag); el.textContent = text; parent.append(el); return el; };
  const heading = add('div', ''); heading.className = 'fp-section-title';
  add('strong', root.querySelector(`[data-purpose="${value.purpose}"]`).textContent, heading);
  add('span', value.status_label, heading).className = 'ft-status';
  add('small', `${value.created_at} UTC${value.source === 'ai' ? ' · Pripremljeno s Coachom' : ''}`);
  const detail = add('details', ''); add('summary', 'Pogledaj tekst', detail); add('p', value.message, detail).className = 'ft-history-text';
  if (value.guidance) add('p', value.guidance, detail).className = 'fp-muted';
  if (value.outcome) add('p', value.outcome);
  if (value.closed_at) return;
  const actions = add('div', ''); actions.className = 'ft-outreach-actions';
  const button = (label, parent = actions) => { const b = add('button', label, parent); b.type = 'button'; b.className = 'fp-button fp-button-outline'; return b; };
  if (!value.sent_self_reported_at) button('Uredi i nastavi').dataset.teamResume = JSON.stringify(value);
  if (value.opened_at && !value.sent_self_reported_at) button('Potvrđujem slanje ove poruke').dataset.historyOperation = 'confirm';
  const next = add('details', ''); add('summary', value.due_date ? `Provjeri ponovno: ${value.due_date}` : 'Dogovori podsjetnik ili završi praćenje', next);
  const controls = add('div', '', next); controls.className = 'ft-history-followup';
  const dateLabel = add('label', 'Sljedeća provjera', controls), date = add('input', '', dateLabel); date.type = 'date'; date.dataset.historyDue = ''; date.value = value.due_date || '';
  button('Spremi podsjetnik', controls).dataset.historyOperation = 'followup';
  const noteLabel = add('label', 'Ishod razgovora, ako ga znaš', controls), note = add('textarea', '', noteLabel); note.rows = 2; note.maxLength = 1000; note.dataset.historyOutcome = '';
  button('Završi praćenje', controls).dataset.historyOperation = 'close';
  const history = one('.ft-outreach-history'); history.open = true;
  history.querySelector('summary span').textContent = `(${one('[data-team-history]').children.length})`;
 }
 async function act(operation) {
  if (busy) return;
  if ((operation === 'open' || operation === 'save') && !message.value.trim()) { say('Najprije upiši poruku.', true); message.focus(); return; }
  if (operation === 'ai' && !situation.value.trim()) { say('Opiši kakvu podršku želiš ponuditi.', true); situation.focus(); return; }
  // Reserve a tab during the user gesture; navigate only after server authorization and saving.
  let tab = operation === 'open' ? window.open('about:blank', '_blank') : null;
  if (tab) tab.opener = null;
  setBusy(true); say(operation === 'ai' ? 'Coach priprema osobni prijedlog…' : 'Spremam…');
  try {
   const result = await request(operation, operation === 'ai' ? {request_key: key(), draft_id: '0'} : {});
   resume(result.draft); renderHistory(result.draft); say(result.notice);
   if (result.whatsapp_url) {
    const link = one('[data-team-continue]'); link.href = result.whatsapp_url; link.hidden = false;
    if (tab && !tab.closed) tab.location.replace(result.whatsapp_url);
    else say('Preglednik je zaustavio novi prozor. Klikni Nastavi u WhatsApp. Slanje još nije potvrđeno.');
   }
   if (operation === 'confirm') say('Osobno si potvrdio/la slanje. Coach će taj podatak koristiti pri sljedećem razgovoru. Za novi tekst odaberi razlog javljanja.');
  } catch (error) { if (tab && !tab.closed) tab.close(); say(error.message, true); }
  finally { setBusy(false); }
 }
 root.addEventListener('click', async event => {
  const button = event.target.closest('button'); if (!button || busy) return;
  if (button.hasAttribute('data-purpose')) {
   if (message.value !== config.templates[purpose] && message.value.trim() && !draft?.sent_self_reported_at && !window.confirm('Zamijeniti trenutačni tekst novim predloškom? Spremljene poruke ostaju u povijesti.')) return;
   purpose = button.dataset.purpose; draft = null; requestKey = key(); message.value = config.templates[purpose]; due.value = '';
   one('[data-team-guidance]').hidden = true; say('Prilagodi poruku osobi kojoj se javljaš.'); sync();
  } else if (button.hasAttribute('data-team-webinar')) {
   if (!config.webinar) return;
   if (draft?.sent_self_reported_at) { draft = null; requestKey = key(); }
   const w = config.webinar;
   if (!message.value.includes(w.url)) message.value = `${message.value.trim()}\n\n${w.title}, ${w.when}.\nPristup webinaru: ${w.url}`;
   sync();
  } else if (button.hasAttribute('data-team-resume')) { resume(JSON.parse(button.dataset.teamResume)); message.focus(); message.scrollIntoView({behavior: 'smooth', block: 'center'}); }
  else if (button.hasAttribute('data-history-operation')) {
   const item = button.closest('[data-outreach-id]'); setBusy(true);
   try {
    const result = await request(button.dataset.historyOperation, {draft_id: item.dataset.outreachId, version: item.dataset.outreachVersion, request_key: item.dataset.requestKey, due_date: item.querySelector('[data-history-due]')?.value || '', outcome: item.querySelector('[data-history-outcome]')?.value || ''});
    renderHistory(result.draft); if (draft && Number(draft.id) === Number(result.draft.id)) draft = result.draft;
    say('Spremljeno.');
   } catch (error) { say(error.message, true); } finally { setBusy(false); }
  } else if (button.hasAttribute('data-team-copy')) {
   try { await navigator.clipboard.writeText(message.value); say('Cijela poruka je kopirana. Kopiranje nije potvrda slanja.'); }
   catch { message.focus(); message.select(); say('Označen je cijeli tekst. Kopiraj ga osobno.'); }
  } else {
   for (const operation of ['open','save','ai','confirm']) if (button.hasAttribute(`data-team-${operation}`)) { await act(operation); break; }
  }
 });
 message.addEventListener('input', () => { if (draft?.sent_self_reported_at) { draft = null; requestKey = key(); } sync(); });
 due.addEventListener('change', () => say('Datum će se spremiti uz sljedeće spremanje nacrta ili otvaranje WhatsAppa. Za već poslanu poruku uredi podsjetnik u povijesti.'));
 sync();
})();
