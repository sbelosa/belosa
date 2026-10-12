/* Custom code: FC-2026-10-12: the simple card editor.
 * Every change is one small POST to partner/card/{id}. The answer carries the fresh steps, the page swaps
 * them in and reloads the live preview, so the member always sees the real card. */
(() => {
  'use strict';
  const root = document.querySelector('[data-fe]');
  if (!root) return;

  // Paths are resolved against the address in the browser, not the page's <base>, so the preview and the
  // saves always stay on the same origin as the editor.
  const endpoint = new URL(root.dataset.feEndpoint, location.origin).href;
  const token = root.dataset.feToken;
  const data = JSON.parse(root.querySelector('[data-fe-data]').textContent || '{}');
  const copy = data.copy || {};
  let blocks = data.blocks || {};
  let profileIds = (data.profile_ids || []).map(Number);
  const editable = new Set(data.editable || []);

  const panel = root.querySelector('[data-fe-panel]');
  const steps = root.querySelector('[data-fe-steps]');
  const sheet = root.querySelector('[data-fe-sheet]');
  const sheetForm = root.querySelector('[data-fe-sheet-form]');
  const sheetBody = root.querySelector('[data-fe-sheet-body]');
  const sheetTitle = root.querySelector('[data-fe-sheet-title]');
  const sheetSave = root.querySelector('[data-fe-sheet-save]');
  const sheetDelete = root.querySelector('[data-fe-sheet-delete]');
  const confirmBox = root.querySelector('[data-fe-confirm]');
  const toast = root.querySelector('[data-fe-toast]');
  const frame = root.querySelector('[data-fe-preview]');
  const frameLoading = root.querySelector('[data-fe-preview-loading]');
  const phone = window.matchMedia('(max-width: 991px)');
  let current = { id: 0, type: '' };
  let activeStep = root.dataset.feStepActive || 'profile';
  let busy = false;
  let toastTimer = 0;

  const t = (key, replace = {}) => {
    let text = copy[key] || key;
    for (const [from, to] of Object.entries(replace)) text = text.split(from).join(String(to));
    return text;
  };

  /* Steps and the phone panel */
  const showStep = (step, { open = true } = {}) => {
    if (activeStep === 'design' && step !== 'design' && designDirty) revertDesign();
    activeStep = step;
    panel.classList.toggle('is-compact', step === 'design');
    root.dataset.feStepActive = step;
    root.querySelectorAll('[data-fe-nav]').forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.feNav === step)));
    steps.querySelectorAll('[data-fe-step]').forEach((section) => { section.hidden = section.dataset.feStep !== step; });
    if (phone.matches && open) openPanel();
    const url = new URL(location.href);
    url.searchParams.set('step', step);
    history.replaceState(history.state, '', url);
  };
  // On phones the style step leaves the card visible above a shorter panel, so choices can be seen at once.
  const peek = () => activeStep === 'design' && phone.matches;
  const openPanel = () => {
    panel.classList.add('is-open');
    // The app menu steps aside while the panel is open, so nothing covers the panel's buttons.
    document.documentElement.classList.add('fe-sheet-open');
    root.classList.toggle('fe-peek', peek());
    document.documentElement.classList.toggle('fe-lock', !peek());
    if (peek()) window.scrollTo({ top: Math.max(0, frame.getBoundingClientRect().top + window.scrollY - 8), behavior: 'smooth' });
  };
  const closePanel = () => { panel.classList.remove('is-open'); root.classList.remove('fe-peek'); document.documentElement.classList.remove('fe-lock', 'fe-sheet-open'); };

  root.querySelectorAll('[data-fe-nav]').forEach((button) => button.addEventListener('click', () => {
    if (phone.matches && panel.classList.contains('is-open') && activeStep === button.dataset.feNav) { closePanel(); return; }
    showStep(button.dataset.feNav);
  }));
  root.querySelector('[data-fe-panel-close]').addEventListener('click', closePanel);

  /* Messages */
  const say = (message, isError = false) => {
    if (!message) return;
    toast.textContent = message;
    toast.classList.toggle('is-error', isError);
    toast.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { toast.hidden = true; }, isError ? 6000 : 3200);
  };
  say(root.dataset.feNotice);
  const formError = (form, message) => {
    const box = form && form.querySelector('[data-fe-error]');
    if (!box) { say(message, true); return; }
    box.textContent = message || '';
    box.hidden = !message;
  };

  /* Server */
  // Every save answers with a clear message: saved, a reason it was not saved, an expired session or a slow connection.
  const post = async (body, form) => {
    if (busy) { say(t('busy'), true); return null; }
    busy = true;
    const submit = form && form.querySelector('[type=submit]');
    const label = submit && submit.textContent;
    const controller = typeof AbortController === 'function' ? new AbortController() : null;
    const timer = controller ? setTimeout(() => controller.abort(), 25000) : 0;
    try {
      root.classList.add('is-busy');
      if (submit) { submit.disabled = true; submit.textContent = t('saving'); }
      formError(form, '');
      body.append('token', token);
      const response = await fetch(endpoint, { method: 'POST', body, credentials: 'same-origin', headers: { Accept: 'application/json' }, signal: controller ? controller.signal : undefined });
      const isJson = (response.headers.get('content-type') || '').includes('application/json');
      let result = null;
      if (isJson) { try { result = await response.json(); } catch (error) { result = null; } }
      if (!isJson || response.redirected) { formError(form, t('session')); return null; }
      if (!response.ok || !result || !result.ok) {
        formError(form, (result && result.message) || t('error'));
        return null;
      }
      applyPayload(result);
      say(result.message);
      return result;
    } catch (error) {
      formError(form, error && error.name === 'AbortError' ? t('slow') : t('error'));
      return null;
    } finally {
      clearTimeout(timer);
      busy = false;
      root.classList.remove('is-busy');
      if (submit) { submit.disabled = false; submit.textContent = label; }
    }
  };
  const action = (name, fields = {}, form = null) => {
    const body = form ? new FormData(form) : new FormData();
    body.set('action', name);
    for (const [key, value] of Object.entries(fields)) body.set(key, value);
    return post(body, form);
  };

  const applyPayload = (result) => {
    if (result.steps !== undefined) {
      const libraryOpen = !!steps.querySelector('[data-fe-library]:not([hidden])');
      steps.innerHTML = result.steps;
      if (libraryOpen) toggleLibrary(true);
      showStep(activeStep, { open: false });
    }
    if (result.blocks) blocks = result.blocks;
    if (result.is_v2 !== undefined) root.dataset.feV2 = result.is_v2 ? '1' : '0';
    if (result.profile_ids) profileIds = result.profile_ids.map(Number);
    reloadPreview();
  };

  /* Live preview */
  let scrollMemory = 0;
  let designDirty = false;
  const baseSrc = () => new URL(frame.dataset.feSrc, location.origin);
  const reloadPreview = () => {
    try { scrollMemory = frame.contentWindow.scrollY || 0; } catch (error) { scrollMemory = 0; }
    frameLoading.hidden = false;
    let showsChoice = false;
    try { showsChoice = new URL(frame.contentWindow.location.href).searchParams.has('fcc_card'); } catch (error) { showsChoice = true; }
    if (showsChoice) { frame.src = baseSrc().href; return; }
    try { frame.contentWindow.location.reload(); } catch (error) { frame.src = baseSrc().href; }
  };

  /* Style choices show in the preview at once; "Spremi izgled" makes them visible to visitors. */
  let designTimer = 0;
  const designForm = () => steps.querySelector('[data-fe-design]');
  const previewDesign = () => {
    const form = designForm();
    if (!form) return;
    const value = (name) => { const input = form.querySelector(`[name="${name}"]:checked`) || form.querySelector(`[name="${name}"]:not([type=radio]):not([type=checkbox])`); return input ? input.value : ''; };
    const style = {};
    ['buttons', 'button_look', 'font', 'text', 'avatar', 'glow'].forEach((key) => { const choice = value(`style_${key}`); if (choice) style[key] = choice; });
    ['accent', 'background'].forEach((key) => { const toggle = form.querySelector(`[name="${key}_on"]`); if (toggle && toggle.checked && !toggle.disabled) style[key] = value(key); });
    const url = baseSrc();
    url.searchParams.set('fcc_card', value('card_version') || 'v2');
    if (value('card_theme')) url.searchParams.set('fcc_theme', value('card_theme'));
    url.searchParams.set('fcc_style', JSON.stringify(style));
    try { scrollMemory = frame.contentWindow.scrollY || 0; } catch (error) { scrollMemory = 0; }
    frameLoading.hidden = false;
    frame.src = url.href;
    designDirty = true;
    const note = form.parentElement.querySelector('[data-fe-design-note]');
    if (note) note.hidden = false;
  };
  const revertDesign = () => {
    const form = designForm();
    if (form) form.reset();
    designDirty = false;
    const note = steps.querySelector('[data-fe-design-note]');
    if (note) note.hidden = true;
    frameLoading.hidden = false;
    frame.src = baseSrc().href;
  };
  const frameStyle = `
    [data-biolink-block-id]{cursor:pointer;transition:outline-color .15s ease;outline:2px solid transparent;outline-offset:4px;border-radius:18px}
    [data-biolink-block-id]:hover{outline-color:rgba(18,119,91,.55)}
    [data-biolink-block-id].fe-picked{outline-color:#12775B}
    .fcc-chat-extreme__launcher-stack{display:none!important}`;
  const setupFrame = () => {
    frameLoading.hidden = true;
    let doc;
    try { doc = frame.contentDocument; } catch (error) { return; }
    if (!doc || !doc.body || doc.feBound) return;
    doc.feBound = true;
    const style = doc.createElement('style');
    style.textContent = frameStyle;
    doc.head.appendChild(style);
    if (scrollMemory) frame.contentWindow.scrollTo(0, scrollMemory);
    else if (designDirty && peek()) { const first = doc.querySelector('.link-btn'); if (first) frame.contentWindow.scrollTo(0, Math.max(0, first.getBoundingClientRect().top - 120)); }
    doc.addEventListener('click', (event) => {
      const target = event.target.closest('[data-biolink-block-id]');
      event.preventDefault();
      event.stopPropagation();
      if (!target) return;
      const id = Number(target.dataset.biolinkBlockId);
      doc.querySelectorAll('.fe-picked').forEach((node) => node.classList.remove('fe-picked'));
      target.classList.add('fe-picked');
      if (profileIds.includes(id)) { closeSheet(); showStep('profile'); const name = steps.querySelector('[data-fe-profile] [name=name]'); if (name && !phone.matches) name.focus(); return; }
      openBlock(id);
    }, true);
  };
  frame.addEventListener('load', setupFrame);
  frameLoading.hidden = false;
  frame.src = new URL(frame.dataset.feSrc, location.origin).href;

  /* One block in the sheet */
  const fill = (form, fields) => {
    form.querySelectorAll('input[name], textarea[name], select[name]').forEach((input) => {
      if (input.type === 'file' || input.type === 'hidden') return;
      let value;
      const social = input.name.match(/^socials\[(.+)\]$/);
      const faq = input.name.match(/^item_(title|content)\[(\d+)\]$/);
      if (social) value = (fields.socials || {})[social[1]];
      else if (faq) value = ((fields.items || [])[Number(faq[2])] || {})[faq[1]];
      else value = fields[input.name];
      if (input.type === 'radio') { input.checked = value !== undefined && value !== null ? input.value === String(value) : input.defaultChecked; return; }
      if (input.type === 'checkbox') { input.checked = !!value; return; }
      // A choice made earlier in the classic editor stays selectable even when it is not in the short list.
      if (input.tagName === 'SELECT' && value && !Array.from(input.options).some((option) => option.value === String(value))) {
        const option = document.createElement('option');
        option.value = String(value);
        option.textContent = t('current_icon');
        input.appendChild(option);
      }
      if (value !== undefined && value !== null) input.value = value;
    });
    const items = (fields.items || []).length;
    form.querySelectorAll('[data-fe-faq-item]').forEach((item) => { item.hidden = Number(item.dataset.feFaqItem) > Math.max(0, items - 1); });
    const rich = form.querySelector('[data-fe-rich]');
    if (rich) rich.hidden = !fields.rich_text;
    const preview = form.querySelector('[data-fe-photo-preview]');
    if (preview) preview.innerHTML = fields.photo ? `<img src="${encodeURI(fields.photo)}" alt="">` : '<i class="fas fa-image" aria-hidden="true"></i>';
    const productId = form.querySelector('[data-fe-product-id]');
    if (productId) {
      productId.value = fields.product_id || '';
      const currentProduct = form.querySelector('[data-fe-product-current]');
      if (fields.product_name) { currentProduct.textContent = t('selected', { '{name}': fields.product_name }); currentProduct.hidden = false; }
      loadProducts(form, '');
    }
  };

  const openSheet = (type, id) => {
    current = { id, type };
    const template = root.querySelector(`template[data-fe-form="${editable.has(type) ? type : 'advanced'}"]`);
    sheetBody.innerHTML = '';
    sheetBody.appendChild(template.content.cloneNode(true));
    const advancedLink = sheetBody.querySelector('[data-fe-advanced-link]');
    if (advancedLink && id) advancedLink.href += `#biolink_block_id_${id}`;
    sheetTitle.textContent = (copy.labels && copy.labels[type]) || t('new');
    const hasFields = !!sheetBody.querySelector('input[name], textarea[name], select[name]');
    sheetSave.hidden = !hasFields;
    sheetSave.textContent = id ? t('save') : t('add');
    sheetDelete.hidden = !id;
    confirmBox.hidden = true;
    formError(sheetForm, '');
    sheetBody.querySelectorAll('.fe-chips').forEach((group) => { const first = group.querySelector('input[type=radio]'); if (first) first.defaultChecked = true; });
    if (id && blocks[id]) fill(sheetForm, blocks[id].fields || {});
    else fill(sheetForm, (data.defaults || {})[type] || {});
    closePanel();
    if (typeof sheet.showModal === 'function') sheet.showModal(); else sheet.setAttribute('open', '');
    const first = sheetBody.querySelector('input:not([type=hidden]):not([type=file]), textarea');
    if (first && !phone.matches) first.focus();
  };
  const openBlock = (id) => {
    const block = blocks[id];
    if (!block) return;
    openSheet(block.type, id);
  };
  const closeSheet = () => { if (sheet.open) sheet.close(); };

  root.querySelector('[data-fe-sheet-close]').addEventListener('click', closeSheet);
  sheet.addEventListener('click', (event) => { if (event.target === sheet) closeSheet(); });
  sheetForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const result = await action('save', { block_id: String(current.id || 0), type: current.type }, sheetForm);
    if (result) closeSheet();
  });
  sheetDelete.addEventListener('click', () => { confirmBox.hidden = false; sheetDelete.hidden = true; });
  root.querySelector('[data-fe-confirm-no]').addEventListener('click', () => { confirmBox.hidden = true; sheetDelete.hidden = false; });
  root.querySelector('[data-fe-confirm-yes]').addEventListener('click', async () => {
    const result = await action('delete', { block_id: String(current.id), confirm: '1' });
    if (result) closeSheet();
  });
  sheetBody.addEventListener('click', (event) => {
    if (event.target.closest('[data-fe-faq-more]')) {
      const next = sheetBody.querySelector('[data-fe-faq-item][hidden]');
      if (next) { next.hidden = false; next.querySelector('input').focus(); }
    }
    const product = event.target.closest('[data-fe-product]');
    if (product) {
      sheetBody.querySelector('[data-fe-product-id]').value = product.dataset.feProduct;
      sheetBody.querySelectorAll('[data-fe-product]').forEach((node) => node.setAttribute('aria-selected', String(node === product)));
      const currentProduct = sheetBody.querySelector('[data-fe-product-current]');
      currentProduct.textContent = t('selected', { '{name}': product.dataset.feProductName });
      currentProduct.hidden = false;
    }
  });

  /* Product picker */
  let productTimer = 0;
  let productRequest = 0;
  const loadProducts = async (form, query) => {
    const list = form.querySelector('[data-fe-products]');
    if (!list) return;
    const request = ++productRequest;
    list.textContent = t('loading');
    try {
      const response = await fetch(`${endpoint}?products=1&q=${encodeURIComponent(query)}`, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
      const result = await response.json();
      if (request !== productRequest) return;
      const selected = form.querySelector('[data-fe-product-id]').value;
      list.innerHTML = '';
      if (!result.products || !result.products.length) { list.textContent = t('no_products'); return; }
      for (const product of result.products) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'fe-product';
        button.setAttribute('role', 'option');
        button.dataset.feProduct = product.id;
        button.dataset.feProductName = product.title;
        button.setAttribute('aria-selected', String(String(product.id) === String(selected)));
        if (product.image) { const img = document.createElement('img'); img.src = product.image; img.alt = ''; img.loading = 'lazy'; button.appendChild(img); }
        const name = document.createElement('span'); name.textContent = product.title; button.appendChild(name);
        list.appendChild(button);
      }
    } catch (error) {
      if (request === productRequest) list.textContent = t('error');
    }
  };
  sheetBody.addEventListener('input', (event) => {
    const search = event.target.closest('[data-fe-product-search]');
    if (!search) return;
    clearTimeout(productTimer);
    productTimer = setTimeout(() => loadProducts(sheetForm, search.value), 250);
  });

  /* Photo previews before saving */
  root.addEventListener('change', (event) => {
    const input = event.target.closest('[data-fe-photo-input]');
    if (!input || !input.files || !input.files[0]) return;
    const preview = input.closest('form').querySelector('[data-fe-photo-preview]');
    if (preview) { const img = document.createElement('img'); img.alt = ''; img.src = URL.createObjectURL(input.files[0]); preview.innerHTML = ''; preview.appendChild(img); }
  });

  /* The steps panel */
  const toggleLibrary = (open) => {
    const library = steps.querySelector('[data-fe-library]');
    const opener = steps.querySelector('[data-fe-library-open]');
    if (!library) return;
    library.hidden = !open;
    if (opener) opener.setAttribute('aria-expanded', String(open));
  };
  steps.addEventListener('click', async (event) => {
    const edit = event.target.closest('[data-fe-edit]');
    if (edit) { openBlock(Number(edit.dataset.feEdit)); return; }
    const advanced = event.target.closest('[data-fe-advanced]');
    if (advanced) { openBlock(Number(advanced.dataset.feAdvanced)); return; }
    const move = event.target.closest('[data-fe-move]');
    if (move) { await action('move', { block_id: move.dataset.feId, direction: move.dataset.feMove }); return; }
    if (event.target.closest('[data-fe-library-open]')) {
      const library = steps.querySelector('[data-fe-library]');
      toggleLibrary(library.hidden);
      if (!library.hidden) library.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      return;
    }
    const add = event.target.closest('[data-fe-add]');
    if (add) {
      if (add.dataset.feNeedsFields === '1') { openSheet(add.dataset.feAdd, 0); return; }
      const result = await action('add', { type: add.dataset.feAdd });
      if (result) toggleLibrary(false);
      return;
    }
    if (event.target.closest('[data-fe-action="recommended"]')) await action('recommended');
  });
  steps.addEventListener('input', (event) => {
    if (!event.target.closest('[data-fe-design]')) return;
    if (event.target.matches('[type=color]')) { const toggle = event.target.closest('.fe-color').querySelector('[type=checkbox]'); if (toggle && !toggle.disabled) toggle.checked = true; }
    clearTimeout(designTimer);
    designTimer = setTimeout(previewDesign, event.target.type === 'color' ? 300 : 0);
  });
  steps.addEventListener('click', (event) => {
    if (event.target.closest('[data-fe-design-revert]')) { revertDesign(); return; }
    if (event.target.closest('[data-fe-style-reset]')) {
      const form = designForm();
      form.querySelectorAll('.fe-chips').forEach((group) => { const first = group.querySelector('input[type=radio]'); if (first) first.checked = true; });
      form.querySelectorAll('[name$="_on"]').forEach((toggle) => { toggle.checked = false; });
      previewDesign();
    }
  });
  steps.addEventListener('change', async (event) => {
    if (event.target.closest('[data-fe-design]')) {
      if (event.target.matches('[type=color]')) { const toggle = event.target.closest('.fe-color').querySelector('[type=checkbox]'); if (toggle && !toggle.disabled) toggle.checked = true; }
      clearTimeout(designTimer);
      previewDesign();
      return;
    }
    const toggle = event.target.closest('[data-fe-toggle]');
    if (!toggle) return;
    const result = await action('toggle', { block_id: toggle.dataset.feToggle, enabled: toggle.checked ? '1' : '0' });
    if (!result) toggle.checked = !toggle.checked;
  });
  steps.addEventListener('submit', async (event) => {
    const form = event.target;
    event.preventDefault();
    if (form.matches('[data-fe-profile]')) {
      const result = await action('profile', {}, form);
      if (result && phone.matches) closePanel();
    } else if (form.matches('[data-fe-design]')) {
      const result = await action('design', {}, form);
      if (result) designDirty = false;
      if (result && phone.matches) closePanel();
    }
  });

  /* Phone layout: the card fills the space between the header and the step bar. */
  const fit = () => {
    if (!phone.matches) { root.style.removeProperty('--fe-canvas'); return; }
    const top = frame.getBoundingClientRect().top + window.scrollY;
    const nav = root.querySelector('.fe-nav');
    const bottomBar = document.querySelector('.fp-bottom-nav, .fp-mobile-nav, nav[data-fcc-mobile-nav]');
    const reserved = (nav ? nav.offsetHeight : 0) + (bottomBar ? bottomBar.offsetHeight : 0) + 24;
    root.style.setProperty('--fe-canvas', `${Math.max(360, window.innerHeight - (top - window.scrollY) - reserved)}px`);
  };
  window.addEventListener('resize', fit);
  phone.addEventListener('change', () => { closePanel(); fit(); showStep(activeStep, { open: false }); });

  showStep(activeStep, { open: false });
  fit();
})();
/* /Custom code: FC-2026-10-12 */
