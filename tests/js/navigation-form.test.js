const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const source = fs.readFileSync(
    path.join(__dirname, '../../src/view/frontend/web/js/navigation-form.js'),
    'utf8'
);

function createForm(search, inputs) {
    let definition;
    const $ = {
        widget(name, widget) {
            definition = widget;
        },
        tweakwise: {}
    };
    const window = {
        location: {origin: 'https://magento.test', search}
    };
    vm.runInNewContext(source, {
        define(dependencies, factory) {
            factory($);
        },
        window,
        URL,
        URLSearchParams
    });

    const controls = inputs.filter(input => !input.skipSubmit);
    const element = {
        find() {
            return {
                serialize() {
                    const params = new URLSearchParams();
                    controls.forEach(input => {
                        if (!input.disabled && input.checked !== false) {
                            params.append(input.name, input.value);
                        }
                    });
                    return params.toString();
                },
                each(callback) {
                    controls.forEach((input, index) => callback(index, input));
                }
            };
        }
    };
    return {...definition, element};
}

function buildUrl(search, inputs, responseUrl, flip = true) {
    const form = createForm(search, inputs);
    form._getFilterParameters();
    return new URL(form._buildUrlWithQueryString({url: responseUrl}, flip));
}

test('removing the last checkbox removes its indexed URL parameter', () => {
    const url = buildUrl('?brand[0]=Acme', [
        {name: 'brand[]', value: 'Acme', checked: false}
    ], '/category/');
    assert.equal(url.search, '');
});

test('removing one facet keeps the other facet and tracking parameters', () => {
    const url = buildUrl('?brand[0]=Acme&size[0]=M&utm_source=newsletter&gclid=123', [
        {name: 'brand[]', value: 'Acme', checked: false},
        {name: 'size[]', value: 'M', checked: true}
    ], '/category/?size[0]=M');
    assert.equal(url.searchParams.has('brand[0]'), false);
    assert.equal(url.searchParams.get('size[0]'), 'M');
    assert.equal(url.searchParams.get('utm_source'), 'newsletter');
    assert.equal(url.searchParams.get('gclid'), '123');
});

test('removing one value does not resurrect an old array index', () => {
    const url = buildUrl('?brand[0]=Acme&brand[1]=Other', [
        {name: 'brand[]', value: 'Acme', checked: false},
        {name: 'brand[]', value: 'Other', checked: true}
    ], '/category/?brand[0]=Other');
    assert.deepEqual([...url.searchParams], [['brand[0]', 'Other']]);
});

test('clearing through a disabled input also removes the old URL parameter', () => {
    const url = buildUrl('?brand[0]=Acme&utm_source=email', [
        {name: 'brand[]', value: 'Acme', checked: true, disabled: true}
    ], '/category/');
    assert.equal(url.searchParams.has('brand[0]'), false);
    assert.equal(url.searchParams.get('utm_source'), 'email');
});

test('clearing an empty scalar filter works when the response has no query', () => {
    const form = createForm('?price=10-20', [{name: 'price', value: ''}]);
    assert.equal(form._getFilterParameters(), '');
    assert.equal(new URL(form._buildUrlWithQueryString({url: '/category/'}, true)).search, '');
});

test('history initialization uses response values for form-owned parameters', () => {
    const url = buildUrl('?brand[]=Acme&utm_source=email', [
        {name: 'brand[]', value: 'Acme', checked: true}
    ], '/category/?brand[0]=Acme', false);
    assert.equal(url.searchParams.has('brand[]'), false);
    assert.equal(url.searchParams.get('brand[0]'), 'Acme');
    assert.equal(url.searchParams.get('utm_source'), 'email');
});

test('unrelated repeated parameters and sorting survive filter removal', () => {
    const url = buildUrl('?brand[0]=Acme&tag=a&tag=b&product_list_order=price', [
        {name: 'brand[]', value: 'Acme', checked: false}
    ], '/category/');
    assert.equal(url.searchParams.has('brand[0]'), false);
    assert.deepEqual(url.searchParams.getAll('tag'), ['a', 'b']);
    assert.equal(url.searchParams.get('product_list_order'), 'price');
});

test('repeated response values are not collapsed by the query merge', () => {
    const url = buildUrl('?utm_source=email', [], '/category/?tag=a&tag=b');
    assert.deepEqual(url.searchParams.getAll('tag'), ['a', 'b']);
    assert.equal(url.searchParams.get('utm_source'), 'email');
});

test('pagination is removed on page one and updated on subsequent pages', () => {
    assert.equal(buildUrl('?p=3&utm_source=email', [], '/category/').searchParams.has('p'), false);
    assert.equal(buildUrl('?p=3', [], '/category/?p=2').searchParams.get('p'), '2');
});

test('path URLs and unrelated query parameters are preserved', () => {
    const url = buildUrl('?utm_source=email&flag', [
        {name: 'brand[]', value: 'Acme', checked: false}
    ], '/category/brand/other/');
    assert.equal(url.pathname, '/category/brand/other/');
    assert.equal(url.searchParams.get('utm_source'), 'email');
    assert.equal(url.toString().endsWith('&flag'), true);
});

test('inputs excluded from submission do not claim external parameters', () => {
    const url = buildUrl('?tracking=123', [
        {name: 'tracking', value: '', skipSubmit: true}
    ], '/category/');
    assert.equal(url.searchParams.get('tracking'), '123');
});
