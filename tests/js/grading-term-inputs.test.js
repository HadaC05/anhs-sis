import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const view = readFileSync(new URL('../../resources/views/users/admin/grading-term-config.blade.php', import.meta.url), 'utf8');
const script = view.slice(view.indexOf('    function validateGradingNumber('), view.indexOf('    function closeGradingTermSettingsMenu('));

function field() {
    const handlers = {};
    const input = {
        value: '', dataset: { min: '2', max: '4' },
        addEventListener(name, callback) { handlers[name] = callback; },
        setCustomValidity(message) { this.error = message; },
    };
    vm.runInNewContext(script, { document: { querySelectorAll: () => [input] } });
    return { input, handlers };
}

test('numeric fields reject letters and punctuation while preserving navigation and shortcuts', () => {
    const { handlers } = field();
    for (const key of ['a', 'Z', 'e', '+', '-', '.', ' ']) {
        let prevented = false;
        handlers.keydown({ key, preventDefault() { prevented = true; } });
        assert.equal(prevented, true, key);
    }
    for (const key of ['2', 'Backspace', 'Tab', 'ArrowLeft']) {
        handlers.keydown({ key, preventDefault() { assert.fail(key); } });
    }
    handlers.keydown({ key: 'a', ctrlKey: true, preventDefault() { assert.fail('Select all'); } });
    for (const text of ['abc', '2e0', '2.5', '3a']) {
        let prevented = false;
        handlers.paste({ clipboardData: { getData: () => text }, preventDefault() { prevented = true; } });
        assert.equal(prevented, true);
        prevented = false;
        handlers.beforeinput({ data: text, preventDefault() { prevented = true; } });
        assert.equal(prevented, true);
    }
    handlers.paste({ clipboardData: { getData: () => '3' }, preventDefault() { assert.fail('Digits'); } });
});

test('input fallback removes nondigits and validates the current school level limits', () => {
    const { input, handlers } = field();
    input.value = 'abc3';
    handlers.input();
    assert.equal(input.value, '3');
    assert.equal(input.error, '');
    for (const value of ['1', '5']) {
        input.value = value;
        handlers.input();
        assert.match(input.error, /from 2 to 4/);
    }
    input.dataset.min = '1';
    input.dataset.max = '5';
    handlers.input();
    assert.equal(input.error, '');
    input.value = '';
    handlers.input();
    assert.equal(input.error, '');
});
