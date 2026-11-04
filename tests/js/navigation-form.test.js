const fs = require('node:fs');
const path = require('node:path');
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
    expect(url.search).toBe('');
});

test('removing one facet keeps the other facet and tracking parameters', () => {
    const url = buildUrl('?brand[0]=Acme&size[0]=M&utm_source=newsletter&gclid=123', [
        {name: 'brand[]', value: 'Acme', checked: false},
        {name: 'size[]', value: 'M', checked: true}
    ], '/category/?size[0]=M');
    expect(url.searchParams.has('brand[0]')).toBe(false);
    expect(url.searchParams.get('size[0]')).toBe('M');
    expect(url.searchParams.get('utm_source')).toBe('newsletter');
    expect(url.searchParams.get('gclid')).toBe('123');
});

test('removing one value does not resurrect an old array index', () => {
    const url = buildUrl('?brand[0]=Acme&brand[1]=Other', [
        {name: 'brand[]', value: 'Acme', checked: false},
        {name: 'brand[]', value: 'Other', checked: true}
    ], '/category/?brand[0]=Other');
    expect([...url.searchParams]).toEqual([['brand[0]', 'Other']]);
});

test('clearing through a disabled input also removes the old URL parameter', () => {
    const url = buildUrl('?brand[0]=Acme&utm_source=email', [
        {name: 'brand[]', value: 'Acme', checked: true, disabled: true}
    ], '/category/');
    expect(url.searchParams.has('brand[0]')).toBe(false);
    expect(url.searchParams.get('utm_source')).toBe('email');
});

test('clearing an empty scalar filter works when the response has no query', () => {
    const form = createForm('?price=10-20', [{name: 'price', value: ''}]);
    expect(form._getFilterParameters()).toBe('');
    expect(new URL(form._buildUrlWithQueryString({url: '/category/'}, true)).search).toBe('');
});

test('history initialization uses response values for form-owned parameters', () => {
    const url = buildUrl('?brand[]=Acme&utm_source=email', [
        {name: 'brand[]', value: 'Acme', checked: true}
    ], '/category/?brand[0]=Acme', false);
    expect(url.searchParams.has('brand[]')).toBe(false);
    expect(url.searchParams.get('brand[0]')).toBe('Acme');
    expect(url.searchParams.get('utm_source')).toBe('email');
});

test('unrelated repeated parameters and sorting survive filter removal', () => {
    const url = buildUrl('?brand[0]=Acme&tag=a&tag=b&product_list_order=price', [
        {name: 'brand[]', value: 'Acme', checked: false}
    ], '/category/');
    expect(url.searchParams.has('brand[0]')).toBe(false);
    expect(url.searchParams.getAll('tag')).toEqual(['a', 'b']);
    expect(url.searchParams.get('product_list_order')).toBe('price');
});

test('repeated response values are not collapsed by the query merge', () => {
    const url = buildUrl('?utm_source=email', [], '/category/?tag=a&tag=b');
    expect(url.searchParams.getAll('tag')).toEqual(['a', 'b']);
    expect(url.searchParams.get('utm_source')).toBe('email');
});

test('pagination is removed on page one and updated on subsequent pages', () => {
    expect(buildUrl('?p=3&utm_source=email', [], '/category/').searchParams.has('p')).toBe(false);
    expect(buildUrl('?p=3', [], '/category/?p=2').searchParams.get('p')).toBe('2');
});

test('path URLs and unrelated query parameters are preserved', () => {
    const url = buildUrl('?utm_source=email&flag', [
        {name: 'brand[]', value: 'Acme', checked: false}
    ], '/category/brand/other/');
    expect(url.pathname).toBe('/category/brand/other/');
    expect(url.searchParams.get('utm_source')).toBe('email');
    expect(url.toString().endsWith('&flag')).toBe(true);
});

test('inputs excluded from submission do not claim external parameters', () => {
    const url = buildUrl('?tracking=123', [
        {name: 'tracking', value: '', skipSubmit: true}
    ], '/category/');
    expect(url.searchParams.get('tracking')).toBe('123');
});
