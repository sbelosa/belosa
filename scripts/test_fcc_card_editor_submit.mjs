/* Custom code: FC-2026-10-15: AJAX editor saves must stay outside the navigation submit guard. */
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {runInNewContext} from 'node:vm';

const read = path => readFileSync(new URL('../' + path, import.meta.url), 'utf8');
const views = ['card-editor.php', 'card-editor-steps.php'];
const ajaxButtons = views.flatMap(view => {
    const html = read('themes/altum/views/partner/' + view);
    return [...html.matchAll(/<button\b[^>]*\btype="submit"[^>]*>/g)].map(match => ({view, tag: match[0]}));
});
assert.equal(ajaxButtons.length, 3, 'Exercise block, profile and design save buttons');

function button(tag, valid = true) {
    const classes = new Set();
    const listeners = new Map();
    return {
        ajax: /\bdata-is-ajax(?:\s|=|>)/.test(tag),
        classList: {add: name => classes.add(name), contains: name => classes.has(name)},
        innerHTML: 'Spremi',
        form: {checkValidity: () => valid},
        addEventListener: (event, callback) => listeners.set(event, callback),
        click() { listeners.get('click')?.({currentTarget: this}); },
    };
}

const editor = ajaxButtons.map(({tag}) => button(tag));
const navigation = button('<button type="submit">');
const invalid = button('<button type="submit">', false);
const all = [...editor, navigation, invalid];
runInNewContext(read('themes/altum/assets/js/custom.js'), {
    document: {
        querySelectorAll: selector => selector === 'button[type="submit"]:not([data-is-ajax])'
            ? all.filter(element => !element.ajax) : [],
        addEventListener() {},
    },
    window: {addEventListener() {}},
}, {filename: 'custom.js'});

for (const [index, element] of editor.entries()) {
    for (let save = 0; save < 3; save++) {
        element.click();
        assert.equal(element.classList.contains('container-disabled-simple'), false,
            ajaxButtons[index].view + ' remains clickable on save ' + (save + 1));
        assert.equal(element.innerHTML, 'Spremi', 'Global guard leaves AJAX labels to the editor');
    }
}
navigation.click();
assert.equal(navigation.classList.contains('container-disabled-simple'), true,
    'Standard forms retain duplicate submission protection');
assert.match(navigation.innerHTML, /spinner-border/);
invalid.click();
assert.equal(invalid.classList.contains('container-disabled-simple'), false,
    'Invalid standard forms remain editable');
console.log('FCC editor submit integration checks passed');
/* /Custom code: FC-2026-10-15 */
