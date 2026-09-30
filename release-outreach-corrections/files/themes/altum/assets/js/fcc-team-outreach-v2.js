(() => {
 'use strict';
 const root = document.querySelector('[data-team-outreach]');
 if (!root) return;
 const one = selector => root.querySelector(selector);
 const config = JSON.parse(one('[data-team-config]').textContent);
 const t = text => window.fccT ? window.fccT(text) : text;
 const c = key => config.copy[key] || key;
 const message = one('[data-team-message]'), situation = one('[data-team-situation]');
 const status = one('[data-team-status]'), due = one('[data-team-due]');
 const rows = new Map(config.history.map(row => [Number(row.id), row]));
 const key = () => Array.from(crypto.getRandomValues(new Uint8Array(16)), n => n.toString(16).padStart(2, '0')).join('');
 let purpose = 'checkin', draft = null, requestKey = key(), busy = false;
 const deferred = value => `fcc-outreach-later:${root.dataset.member}:${value.id}:${value.version}`;
 const isDeferred = value => { try { return sessionStorage.getItem(deferred(value)) === '1'; } catch { return false; } };
 function say(text, error = false) { status.textContent = text; status.classList.toggle('is-error', error); }
 function sync() {
  root.querySelectorAll('[data-purpose]').forEach(b => b.setAttribute('aria-pressed', String(b.dataset.purpose === purpose)));
  const locked = !!draft?.sent_self_reported_at || !!draft?.closed_at || !!draft?.deleted_at;
  one('[data-team-open]').disabled = busy || root.dataset.phoneAvailable !== '1' || !message.value.trim() || locked;
  one('[data-team-copy]').disabled = busy || !message.value.trim() || locked;
  one('[data-team-save]').disabled = busy || locked;
  const confirmable = draft?.state === 'pending' && !draft.closed_at && draft.message === message.value.trim() && !isDeferred(draft);
  one('[data-team-confirm-panel]').hidden = !confirmable;
  one('[data-team-draft-status]').textContent = draft?.status_label || '';
  one('[data-team-confirm-text]').textContent = draft?.message || '';
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
   message: message.value.trim(), situation: situation.value, due_date: due.value, ...extra});
  const response = await fetch(root.dataset.endpoint, {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'}, body});
  if (response.redirected) throw new Error(t('Sesija je istekla. Tekst je ostao u obrascu. Prijavi se pa osvježi stranicu.'));
  const result = await response.json();
  if (!response.ok || !result.ok) throw new Error(result.message || t('Spremanje nije uspjelo. Pokušaj ponovno.'));
  return result;
 }
 function resume(value, showConfirmation = false) {
  draft = value; requestKey = value.request_key; purpose = value.purpose;
  message.value = value.message; due.value = value.due_date || '';
  one('[data-team-guidance]').textContent = value.guidance || '';
  one('[data-team-guidance]').hidden = !value.guidance;
  one('[data-team-continue]').hidden = true;
  if (showConfirmation) { try { sessionStorage.removeItem(deferred(value)); } catch {} }
  sync();
 }
 function reset() {
  draft = null; requestKey = key(); message.value = config.templates[purpose]; due.value = '';
  one('[data-team-guidance]').hidden = true; one('[data-team-continue]').hidden = true; sync();
 }
 function renderHistory(value) {
  rows.set(Number(value.id), value);
  let item = root.querySelector(`[data-outreach-id="${Number(value.id)}"]`);
  if (!item) { item = document.createElement('article'); item.className = 'ft-outreach-item'; item.tabIndex = -1; }
  item.dataset.outreachId = value.id; item.replaceChildren();
  const add = (tag, text, parent = item) => { const el = document.createElement(tag); el.textContent = text; parent.append(el); return el; };
  const heading = add('div', ''); heading.className = 'fp-section-title';
  add('strong', root.querySelector(`[data-purpose="${value.purpose}"]`).textContent, heading);
  add('span', value.status_label, heading).className = `ft-status ft-status-${value.state}`;
  add('small', `${value.created_at} UTC${value.source === 'ai' ? t(' · Pripremljeno s Coachom') : ''}`);
  const detail = add('details', ''); add('summary', t('Pogledaj tekst'), detail); add('p', value.message, detail).className = 'ft-history-text';
  if (value.guidance) add('p', value.guidance, detail).className = 'fp-muted';
  if (value.outcome) add('p', value.outcome);
  if (value.closed_at) add('p', c('closed')).className = 'fp-muted';
  const actions = add('div', ''); actions.className = 'ft-outreach-actions';
  const button = (label, attribute, operation = '', parent = actions) => {
   const b = add('button', label, parent); b.type = 'button'; b.className = 'fp-button fp-button-outline'; b.setAttribute(attribute, operation); return b;
  };
  if (value.deleted_at) button(c('restore'), 'data-history-operation', 'restore');
  else {
   if (!value.sent_self_reported_at && !value.closed_at) button(t('Uredi i nastavi'), 'data-team-resume');
   if (value.state === 'pending' && !value.closed_at) button(c('check'), 'data-team-check');
   if (value.sent_self_reported_at) button(c('unconfirm'), 'data-history-operation', 'unconfirm');
   button(c('remove'), 'data-history-operation', 'remove');
   if (!value.closed_at) {
    const next = add('details', ''); add('summary', value.due_date ? `${value.reminder_label}: ${value.due_date}` : t('Dogovori podsjetnik ili završi praćenje'), next);
    const controls = add('div', '', next); controls.className = 'ft-history-followup';
    const dateLabel = add('label', t('Sljedeća provjera'), controls), date = add('input', '', dateLabel); date.type = 'date'; date.dataset.historyDue = ''; date.value = value.due_date || '';
    button(t('Spremi podsjetnik'), 'data-history-operation', 'followup', controls);
    const noteLabel = add('label', t('Ishod razgovora, ako ga znaš'), controls), note = add('textarea', '', noteLabel); note.rows = 2; note.maxLength = 1000; note.dataset.historyOutcome = '';
    button(t('Završi praćenje'), 'data-history-operation', 'close', controls);
   }
  }
  const group = one(`[data-team-group="${value.deleted_at ? 'removed_group' : value.sent_self_reported_at ? 'confirmed' : 'preparations'}"]`);
  const list = group.querySelector('[data-team-history]');
  const before = Array.from(list.children).find(el => Number(el.dataset.outreachId) < Number(value.id));
  list.insertBefore(item, before || null); group.open = true;
  root.querySelectorAll('[data-team-group]').forEach(g => {
   const count = g.querySelector('[data-team-history]').children.length;
   g.querySelector('[data-team-count]').textContent = `(${count})`; g.querySelector('[data-team-empty]').hidden = !!count;
  });
  return item;
 }
 async function act(operation) {
  if (busy) return;
  if (['open','save','copy'].includes(operation) && !message.value.trim()) { say(t('Najprije upiši poruku.'), true); message.focus(); return; }
  if (operation === 'ai' && !situation.value.trim()) { say(t('Opiši kakvu podršku želiš ponuditi.'), true); situation.focus(); return; }
  // Only this explicit user action reserves a WhatsApp tab. Returning never confirms sending.
  let tab = operation === 'open' ? window.open('about:blank', '_blank') : null;
  if (tab) tab.opener = null;
  setBusy(true); say(operation === 'ai' ? t('Coach priprema osobni prijedlog…') : c('saving'));
  try {
   if (operation === 'copy') {
    try { await navigator.clipboard.writeText(message.value.trim()); }
    catch { message.focus(); message.select(); throw new Error(c('copy_error')); }
   }
   const result = await request(operation, operation === 'ai' ? {request_key: key(), draft_id: '0'} : {});
   resume(result.draft); renderHistory(result.draft); say(result.notice);
   if (result.whatsapp_url) {
    const link = one('[data-team-continue]'); link.href = result.whatsapp_url; link.hidden = false;
    if (tab && !tab.closed) tab.location.replace(result.whatsapp_url);
    else say(t('Preglednik je zaustavio novi prozor. Klikni Nastavi u WhatsApp. Slanje još nije potvrđeno.'));
   }
  } catch (error) { if (tab && !tab.closed) tab.close(); say(error.message, true); }
  finally { setBusy(false); }
 }
 root.addEventListener('click', async event => {
  const button = event.target.closest('button'); if (!button || busy) return;
  if (button.hasAttribute('data-purpose')) {
   if (message.value !== config.templates[purpose] && message.value.trim() && !draft?.sent_self_reported_at && !window.confirm(t('Zamijeniti trenutačni tekst novim predloškom? Spremljene poruke ostaju u povijesti.'))) return;
   purpose = button.dataset.purpose; reset(); say(t('Prilagodi poruku osobi kojoj se javljaš.'));
  } else if (button.hasAttribute('data-team-webinar')) {
   if (!config.webinar) return;
   if (draft?.sent_self_reported_at || draft?.closed_at) { draft = null; requestKey = key(); }
   const w = config.webinar;
   if (!message.value.includes(w.url)) message.value = `${message.value.trim()}\n\n${w.title}, ${w.when}.\nPristup webinaru: ${w.url}`;
   sync();
  } else if (button.hasAttribute('data-team-resume') || button.hasAttribute('data-team-check')) {
   const value = rows.get(Number(button.closest('[data-outreach-id]').dataset.outreachId));
   resume(value, true);
   const target = button.hasAttribute('data-team-check') ? one('[data-team-confirm]') : message;
   target.focus(); target.scrollIntoView({behavior: 'smooth', block: 'center'});
  } else if (button.hasAttribute('data-team-later')) {
   if (draft) { try { sessionStorage.setItem(deferred(draft), '1'); } catch {} }
   one('[data-team-confirm-panel]').hidden = true; say(c('later_done'));
  } else if (button.hasAttribute('data-history-cancel')) {
   const item = button.closest('[data-outreach-id]');
   item.querySelector('[data-record-confirm]')?.remove(); item.focus();
  } else if (button.hasAttribute('data-history-operation') || button.hasAttribute('data-history-approve')) {
   const item = button.closest('[data-outreach-id]'), value = rows.get(Number(item.dataset.outreachId)), operation = button.dataset.historyOperation || button.dataset.historyApprove;
   if (['remove','unconfirm'].includes(operation) && !button.hasAttribute('data-history-approve')) {
    item.querySelector('[data-record-confirm]')?.remove();
    const panel = document.createElement('div'); panel.dataset.recordConfirm = ''; panel.className = 'ft-record-confirm'; panel.setAttribute('role','region'); panel.setAttribute('aria-label', c(operation));
    const text = document.createElement('p'); text.textContent = c(`${operation}_question`); panel.append(text);
    const yes = document.createElement('button'); yes.type = 'button'; yes.className = 'fp-button'; yes.dataset.historyApprove = operation; yes.textContent = c(operation); panel.append(yes);
    const cancel = document.createElement('button'); cancel.type = 'button'; cancel.className = 'fp-button fp-button-outline'; cancel.dataset.historyCancel = ''; cancel.textContent = c('cancel'); panel.append(cancel);
    item.append(panel); cancel.focus(); return;
   }
   setBusy(true);
   try {
    const result = await request(operation, {draft_id: value.id, version: value.version, request_key: value.request_key, message: value.message,
     due_date: item.querySelector('[data-history-due]')?.value || '', outcome: item.querySelector('[data-history-outcome]')?.value || ''});
    const updated = renderHistory(result.draft);
    if (draft && Number(draft.id) === Number(result.draft.id)) { if (result.draft.deleted_at) reset(); else draft = result.draft; }
    say(result.notice || c('saved')); updated.focus({preventScroll:true});
   } catch (error) { say(error.message, true); } finally { setBusy(false); }
  } else {
   for (const operation of ['open','save','copy','ai','confirm','not_sent']) if (button.hasAttribute(`data-team-${operation}`)) { await act(operation); break; }
  }
 });
 message.addEventListener('input', () => { if (draft?.sent_self_reported_at || draft?.closed_at) { draft = null; requestKey = key(); } sync(); });
 due.addEventListener('change', () => say(t('Datum će se spremiti uz sljedeće spremanje nacrta ili otvaranje WhatsAppa. Za već poslanu poruku uredi podsjetnik u povijesti.')));
 // Persisted pending records survive reloads. A focus/visibility event is never evidence of sending.
 const pending = config.history.find(row => row.state === 'pending' && !row.closed_at && !isDeferred(row));
 if (pending) resume(pending);
 window.addEventListener('focus', sync);
 document.addEventListener('visibilitychange', () => { if (!document.hidden) sync(); });
 sync();
})();
