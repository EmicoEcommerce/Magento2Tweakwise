const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const $ = require('jquery');

const source = fs.readFileSync(
    path.join(__dirname, '../../src/view/frontend/web/js/navigation-form.js'),
    'utf8'
);

let widget;
$.tweakwise = {};
$.widget = (name, definition) => {
    widget = definition;
};
vm.runInNewContext(source, {
    define(dependencies, factory) {
        factory($);
    },
    document,
    window,
    URL,
    URLSearchParams
});

function createForm(search, inputs) {
    window.history.replaceState({}, '', `/category/${search}`);
    document.body.innerHTML = `
        <div id="layered-filter-block">
            <form>${inputs}</form>
        </div>
    `;

    return {...widget, element: $('#layered-filter-block')};
}

function buildUrl(search, inputs, responseUrl, flip = true) {
    const form = createForm(search, inputs);
    const filterData = form._getFilterData();
    return new URL(form._buildUrlWithQueryString(
        {url: responseUrl},
        filterData.formParameterNames,
        flip
    ));
}

test('removing the last checkbox removes its indexed URL parameter', () => {
    const url = buildUrl('?brand[0]=Acme', `
        <input type="checkbox" name="brand[]" value="Acme">
    `, '/category/');
    expect(url.search).toBe('');
});

test('removing one facet keeps the other facet and tracking parameters', () => {
    const url = buildUrl('?brand[0]=Acme&size[0]=M&utm_source=newsletter&gclid=123', `
        <input type="checkbox" name="brand[]" value="Acme">
        <input type="checkbox" name="size[]" value="M" checked>
    `, '/category/?size[0]=M');
    expect(url.searchParams.has('brand[0]')).toBe(false);
    expect(url.searchParams.get('size[0]')).toBe('M');
    expect(url.searchParams.get('utm_source')).toBe('newsletter');
    expect(url.searchParams.get('gclid')).toBe('123');
});

test('removing one value does not resurrect an old array index', () => {
    const url = buildUrl('?brand[0]=Acme&brand[1]=Other', `
        <input type="checkbox" name="brand[]" value="Acme">
        <input type="checkbox" name="brand[]" value="Other" checked>
    `, '/category/?brand[0]=Other');
    expect([...url.searchParams]).toEqual([['brand[0]', 'Other']]);
});

test('clearing through a disabled input also removes the old URL parameter', () => {
    const url = buildUrl('?brand[0]=Acme&utm_source=email', `
        <input type="checkbox" name="brand[]" value="Acme" checked disabled>
    `, '/category/');
    expect(url.searchParams.has('brand[0]')).toBe(false);
    expect(url.searchParams.get('utm_source')).toBe('email');
});

test('clearing an empty scalar filter works when the response has no query', () => {
    const form = createForm('?price=10-20', '<input name="price" value="">');
    const filterData = form._getFilterData();
    expect(filterData.parameters).toBe('');
    expect(new URL(form._buildUrlWithQueryString(
        {url: '/category/'},
        filterData.formParameterNames,
        true
    )).search).toBe('');
});

test('a later count request cannot replace the submitted form parameter snapshot', () => {
    const form = createForm('?brand[0]=Acme', `
        <input type="checkbox" name="brand[]" value="Acme" checked>
    `);
    const submittedFormData = form._getFilterData();

    form.element.html('<form><input type="checkbox" name="size[]" value="M" checked></form>');
    form._getFilterData();

    const url = new URL(form._buildUrlWithQueryString(
        {url: '/category/'},
        submittedFormData.formParameterNames
    ));
    expect(url.search).toBe('');
});

test('real form serialization excludes unchecked and disabled inputs', () => {
    const form = createForm('', `
        <input type="checkbox" name="brand[]" value="Acme" checked>
        <input type="checkbox" name="brand[]" value="Other">
        <input name="price" value="10-20" disabled>
        <input name="tracking" value="123" class="js-skip-submit">
    `);
    const filterData = form._getFilterData();

    expect(filterData.parameters).toBe('brand%5B%5D=Acme');
    expect([...filterData.formParameterNames]).toEqual(['brand', 'price']);
});

test('history initialization uses response values for form-owned parameters', () => {
    const url = buildUrl('?brand[]=Acme&utm_source=email', `
        <input type="checkbox" name="brand[]" value="Acme" checked>
    `, '/category/?brand[0]=Acme', false);
    expect(url.searchParams.has('brand[]')).toBe(false);
    expect(url.searchParams.get('brand[0]')).toBe('Acme');
    expect(url.searchParams.get('utm_source')).toBe('email');
});

test('unrelated repeated parameters and sorting survive filter removal', () => {
    const url = buildUrl('?brand[0]=Acme&tag=a&tag=b&product_list_order=price', `
        <input type="checkbox" name="brand[]" value="Acme">
    `, '/category/');
    expect(url.searchParams.has('brand[0]')).toBe(false);
    expect(url.searchParams.getAll('tag')).toEqual(['a', 'b']);
    expect(url.searchParams.get('product_list_order')).toBe('price');
});

test('repeated response values are not collapsed by the query merge', () => {
    const url = buildUrl('?utm_source=email', '', '/category/?tag=a&tag=b');
    expect(url.searchParams.getAll('tag')).toEqual(['a', 'b']);
    expect(url.searchParams.get('utm_source')).toBe('email');
});

test('pagination is removed on page one and updated on subsequent pages', () => {
    expect(buildUrl('?p=3&utm_source=email', '', '/category/').searchParams.has('p')).toBe(false);
    expect(buildUrl('?p=3', '', '/category/?p=2').searchParams.get('p')).toBe('2');
});

test('path URLs and unrelated query parameters are preserved', () => {
    const url = buildUrl('?utm_source=email&flag', `
        <input type="checkbox" name="brand[]" value="Acme">
    `, '/category/brand/other/');
    expect(url.pathname).toBe('/category/brand/other/');
    expect(url.searchParams.get('utm_source')).toBe('email');
    expect(url.toString().endsWith('&flag')).toBe(true);
});

test('inputs excluded from submission do not claim external parameters', () => {
    const url = buildUrl('?tracking=123', `
        <input name="tracking" value="" class="js-skip-submit">
    `, '/category/');
    expect(url.searchParams.get('tracking')).toBe('123');
});
