const test = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const path = require('node:path');

function setup(quantity, maximum = '8') {
    const events = {};
    const requests = [];
    class Element {
        constructor(isAdd = false) { this.isAdd = isAdd; }
        closest(selector) { return this.isAdd && selector === '[data-add-cart]' ? this : null; }
        getAttribute(key) { return key === 'data-add-cart' ? '42' : key === 'data-quantity-input' ? 'pdp-quantity' : null; }
    }
    const document = {
        getElementById: (id) => id === 'pdp-quantity' ? { value: quantity, max: maximum } : null,
        addEventListener: (name, fn) => { events[name] = fn; },
    };
    const context = {
        document, Element, FormData, console,
        window: { kleerAjax: {}, alert: (message) => { throw new Error(message); } },
        fetch: async (_, options) => {
            requests.push(Object.fromEntries(options.body.entries()));
            return { ok: true, json: async () => ({ success: true, data: { items: [] } }) };
        },
    };
    vm.runInNewContext(fs.readFileSync(path.resolve(__dirname, '../../wp-content/themes/kleer-theme/assets/js/routine-cart.js'), 'utf8'), context);
    return {
        requests,
        click: async (isAdd) => {
            let prevented = false;
            events.click({ target: new Element(isAdd), preventDefault: () => { prevented = true; } });
            await new Promise(setImmediate);
            return prevented;
        },
    };
}

test('PDP cart sends the selected quantity', async () => {
    const page = setup('3');
    assert.equal(await page.click(true), true);
    assert.equal(page.requests[0].product_id, '42');
    assert.equal(page.requests[0].qty, '3');
});
test('PDP quantity respects the purchase maximum', async () => {
    const page = setup('99', '4');
    await page.click(true);
    assert.equal(page.requests[0].qty, '4');
});
test('Invalid quantity falls back to one', async () => {
    const page = setup('invalid');
    await page.click(true);
    assert.equal(page.requests[0].qty, '1');
});
test('Normal navigation links pass through the cart click handler', async () => {
    const page = setup('1');
    assert.equal(await page.click(false), false);
    assert.equal(page.requests.length, 0);
});
