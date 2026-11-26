const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const $ = require('jquery');

const source = fs.readFileSync(
    path.join(__dirname, '../../src/view/frontend/web/js/assistant.js'),
    'utf8'
);

let definition;
$.tweakwise = {};
$.mage = {cookies: {get: () => 'form-key'}};
$.widget = (name, widgetDefinition) => {
    definition = widgetDefinition;
};
vm.runInNewContext(source, {
    define(dependencies, factory) {
        factory($);
    },
    document,
    window,
    URLSearchParams,
    AbortController,
    fetch: (...args) => global.fetchMock(...args)
});

const STORAGE_KEY = 'tw-assistant-default';
const labels = {
    user: 'You',
    assistant: 'Assistant',
    responding: 'Assistant is responding.',
    search: 'Search',
    cancel: 'Cancel',
    quota: {
        'quota-reached': 'Usage limit reached',
        'quota-unavailable': 'Quota could not be verified'
    }
};

function render() {
    document.body.innerHTML = `
        <div id="layered-filter-block">
            <div class="block-content">
                <section class="tw-assistant-facet">
                    <span data-role="tw-assistant-status"></span>
                    <button type="button" data-role="tw-assistant-open" aria-expanded="false"></button>
                    <div data-role="tw-assistant-panel" hidden>
                        <section data-role="tw-assistant-messages">
                            <div data-role="tw-assistant-empty"></div>
                            <div data-role="tw-assistant-thread"></div>
                            <p data-role="tw-assistant-error" hidden></p>
                            <p data-role="tw-assistant-quota" hidden></p>
                        </section>
                        <form data-role="tw-assistant-composer">
                            <textarea data-role="tw-assistant-input"></textarea>
                            <button type="submit" data-role="tw-assistant-submit" disabled>Search</button>
                        </form>
                        <button type="button" data-role="tw-assistant-reset"></button>
                        <button type="button" data-role="tw-assistant-close"></button>
                    </div>
                </section>
                <form id="facet-filter-form"></form>
            </div>
        </div>
    `;
}

function createWidget(options = {}) {
    render();
    const widget = Object.create(definition);
    widget.options = $.extend(true, {}, definition.options, {
        endpoint: '/tweakwise/ajax/assistant',
        storageKey: STORAGE_KEY,
        context: {type: 'category', categoryId: 12, baseUrl: 'https://magento.test/category/', hash: 'hash'},
        selectedFacets: [{attribute: 'kleur', value: 'Rood'}],
        searchTerm: null,
        labels: labels
    }, options);
    widget.element = $('.tw-assistant-facet');
    widget.navigated = [];
    widget._navigate = (url) => widget.navigated.push(url);
    widget._create();

    return widget;
}

function deferred() {
    let resolve;
    let reject;
    const promise = new Promise((res, rej) => {
        resolve = res;
        reject = rej;
    });

    return {promise, resolve, reject};
}

function jsonResponse(status, data) {
    return Promise.resolve({ok: status >= 200 && status < 300, status, json: () => Promise.resolve(data)});
}

function type(widget, text) {
    widget.ui.input.val(text).trigger('input');
}

function pressEnter(widget, extra = {}) {
    const event = $.Event('keydown', {key: 'Enter', keyCode: 13, shiftKey: false, ...extra});
    widget.ui.input.trigger(event);

    return event;
}

function threadTexts(widget) {
    return widget.ui.thread.find('.tw-assistant__message:not(.tw-assistant__message--pending) .tw-assistant__bubble')
        .map((index, element) => $(element).text()).get();
}

const flush = () => new Promise((resolve) => setTimeout(resolve, 0));

beforeEach(() => {
    window.sessionStorage.clear();
    window.history.replaceState({}, '', '/category/?product_list_order=price&utm_source=mail');
    global.fetchMock = jest.fn();
});

test('opening the card hides the filters and focuses the input', () => {
    const widget = createWidget();

    widget.ui.open.trigger('click');

    expect($('#layered-filter-block').hasClass('tw-assistant-open')).toBe(true);
    expect(widget.ui.panel.prop('hidden')).toBe(false);
    expect(widget.ui.open.attr('aria-expanded')).toBe('true');
    expect(document.activeElement).toBe(widget.ui.input.get(0));
});

test('search is disabled while the input is empty', () => {
    const widget = createWidget();
    expect(widget.ui.submit.prop('disabled')).toBe(true);

    type(widget, 'red shoes');
    expect(widget.ui.submit.prop('disabled')).toBe(false);

    type(widget, '   ');
    expect(widget.ui.submit.prop('disabled')).toBe(true);
    pressEnter(widget);
    expect(global.fetchMock).not.toHaveBeenCalled();
});

test('shift+enter and IME composition do not submit', () => {
    const widget = createWidget();
    type(widget, 'red shoes');

    expect(pressEnter(widget, {shiftKey: true}).isDefaultPrevented()).toBe(false);
    pressEnter(widget, {isComposing: true});
    pressEnter(widget, {keyCode: 229});

    expect(global.fetchMock).not.toHaveBeenCalled();
});

test('enter posts the conversation with context, facets and toolbar', async () => {
    const pending = deferred();
    global.fetchMock.mockReturnValue(pending.promise);
    const widget = createWidget();
    type(widget, ' red shoes ');

    expect(pressEnter(widget).isDefaultPrevented()).toBe(true);

    const [url, request] = global.fetchMock.mock.calls[0];
    const payload = JSON.parse(request.body.get('payload'));
    expect(url).toBe('/tweakwise/ajax/assistant');
    expect(request.method).toBe('POST');
    expect(request.body.get('form_key')).toBe('form-key');
    expect(payload.messages).toEqual([{role: 'user', content: 'red shoes'}]);
    expect(payload.history).toEqual([]);
    expect(payload.selectedFacets).toEqual([{attribute: 'kleur', value: 'Rood'}]);
    expect(payload.context.hash).toBe('hash');
    expect(payload.toolbar).toEqual({product_list_order: 'price'});

    expect(widget.ui.input.val()).toBe('');
    expect(widget.ui.input.prop('disabled')).toBe(true);
    expect(widget.ui.submit.text()).toBe('Cancel');
    expect(widget.ui.submit.prop('disabled')).toBe(false);
    expect(widget.ui.thread.find('.tw-assistant__message--pending[role="status"]').text())
        .toContain('Assistant is responding.');
    expect(threadTexts(widget)).toEqual(['red shoes']);
});

test('cancel restores the transcript and the draft and ignores the late response', async () => {
    const pending = deferred();
    global.fetchMock.mockReturnValue(pending.promise);
    const widget = createWidget();
    type(widget, 'red shoes');
    pressEnter(widget);
    const signal = global.fetchMock.mock.calls[0][1].signal;

    widget.ui.composer.trigger('submit');

    expect(signal.aborted).toBe(true);
    expect(threadTexts(widget)).toEqual([]);
    expect(widget.ui.input.val()).toBe('red shoes');
    expect(widget.ui.submit.text()).toBe('Search');
    expect(JSON.parse(window.sessionStorage.getItem(STORAGE_KEY)).messages).toEqual([]);

    pending.resolve(jsonResponse(200, {messages: [{role: 'assistant', content: 'late'}], history: [], url: '/x'}));
    await flush();
    await flush();
    expect(widget.navigated).toEqual([]);
});

test('success replaces the transcript, stores the state and loads the result', async () => {
    global.fetchMock.mockReturnValue(jsonResponse(200, {
        messages: [{role: 'user', content: 'red shoes'}, {role: 'assistant', content: 'Here are red shoes'}],
        history: [{id: 'h1'}],
        url: 'https://magento.test/category/?kleur[]=Rood',
        filters: {kleur: ['Rood']},
        ajax: false
    }));
    const widget = createWidget();
    widget.ui.open.trigger('click');
    type(widget, 'red shoes');
    pressEnter(widget);
    await flush();
    await flush();

    expect(widget.navigated).toEqual(['https://magento.test/category/?kleur[]=Rood']);
    const state = JSON.parse(window.sessionStorage.getItem(STORAGE_KEY));
    expect(state.messages.map((message) => message.content)).toEqual(['red shoes', 'Here are red shoes']);
    expect(state.history).toEqual([{id: 'h1'}]);
    expect(state.open).toBe(true);
    expect(state.revealPending).toBe(true);
    expect(state.startUrl).toBe('https://magento.test/category/?product_list_order=price&utm_source=mail');
});

test('the widget restores the open conversation after a result load', () => {
    window.sessionStorage.setItem(STORAGE_KEY, JSON.stringify({
        messages: [{role: 'user', content: 'red shoes'}, {role: 'assistant', content: 'Here are red shoes'}],
        history: [{id: 'h1'}],
        open: true,
        startUrl: '/category/',
        revealPending: false
    }));

    const widget = createWidget();

    expect($('#layered-filter-block').hasClass('tw-assistant-open')).toBe(true);
    expect(threadTexts(widget)).toEqual(['red shoes', 'Here are red shoes']);
    expect(widget.ui.empty.prop('hidden')).toBe(true);
});

test('follow-up messages send the previous history', () => {
    window.sessionStorage.setItem(STORAGE_KEY, JSON.stringify({
        messages: [{role: 'user', content: 'red shoes'}, {role: 'assistant', content: 'Here are red shoes'}],
        history: [{id: 'h1'}],
        open: true
    }));
    global.fetchMock.mockReturnValue(deferred().promise);
    const widget = createWidget();
    type(widget, 'cheaper');
    pressEnter(widget);

    const payload = JSON.parse(global.fetchMock.mock.calls[0][1].body.get('payload'));
    expect(payload.history).toEqual([{id: 'h1'}]);
    expect(payload.messages.map((message) => message.content)).toEqual(['red shoes', 'Here are red shoes', 'cheaper']);
});

test('errors keep the failed message, clear the input and keep the history', async () => {
    window.sessionStorage.setItem(STORAGE_KEY, JSON.stringify({messages: [], history: [{id: 'h0'}], open: true}));
    global.fetchMock.mockReturnValue(jsonResponse(502, {error: 'assistant_error', reason: null, reference: 'abc'}));
    const widget = createWidget();
    type(widget, 'red shoes');
    pressEnter(widget);
    await flush();
    await flush();

    expect(widget.ui.error.prop('hidden')).toBe(false);
    expect(threadTexts(widget)).toEqual(['red shoes']);
    expect(widget.ui.input.val()).toBe('');
    expect(widget.ui.input.prop('disabled')).toBe(false);
    expect(widget.ui.submit.text()).toBe('Search');
    expect(JSON.parse(window.sessionStorage.getItem(STORAGE_KEY)).history).toEqual([{id: 'h0'}]);
    expect(widget.navigated).toEqual([]);
});

test('network errors and invalid json show the friendly error', async () => {
    global.fetchMock.mockReturnValue(Promise.resolve({ok: true, status: 200, json: () => Promise.reject(new Error())}));
    const widget = createWidget();
    type(widget, 'red shoes');
    pressEnter(widget);
    await flush();
    await flush();

    expect(widget.ui.error.prop('hidden')).toBe(false);
});

test('a quota error disables the composer', async () => {
    global.fetchMock.mockReturnValue(jsonResponse(502, {error: 'assistant_error', reason: 'quota-reached'}));
    const widget = createWidget();
    type(widget, 'red shoes');
    pressEnter(widget);
    await flush();
    await flush();

    expect(widget.ui.quota.prop('hidden')).toBe(false);
    expect(widget.ui.quota.text()).toBe('Usage limit reached');
    expect(widget.ui.error.prop('hidden')).toBe(true);
    expect(widget.ui.input.prop('disabled')).toBe(true);
    expect(widget.ui.submit.prop('disabled')).toBe(true);
    expect(widget.ui.status.hasClass('tw-assistant__status--unavailable')).toBe(true);
});

test('quota reason from the navigation response disables the composer on load', () => {
    const widget = createWidget({unavailableReason: 'quota-unavailable'});

    expect(widget.ui.quota.text()).toBe('Quota could not be verified');
    expect(widget.ui.input.prop('disabled')).toBe(true);
});

test('back to filters keeps the conversation', () => {
    window.sessionStorage.setItem(STORAGE_KEY, JSON.stringify({
        messages: [{role: 'user', content: 'red shoes'}],
        history: [],
        open: true
    }));
    const widget = createWidget();

    widget.ui.close.trigger('click');

    expect($('#layered-filter-block').hasClass('tw-assistant-open')).toBe(false);
    expect(widget.ui.panel.prop('hidden')).toBe(true);
    const state = JSON.parse(window.sessionStorage.getItem(STORAGE_KEY));
    expect(state.open).toBe(false);
    expect(state.messages).toEqual([{role: 'user', content: 'red shoes'}]);
});

test('reset clears the conversation and restores the results from before it', () => {
    window.sessionStorage.setItem(STORAGE_KEY, JSON.stringify({
        messages: [{role: 'user', content: 'red shoes'}],
        history: [{id: 'h1'}],
        open: true,
        startUrl: 'https://magento.test/category/?product_list_order=name'
    }));
    const widget = createWidget();

    widget.ui.reset.trigger('click');

    const state = JSON.parse(window.sessionStorage.getItem(STORAGE_KEY));
    expect(state.messages).toEqual([]);
    expect(state.history).toEqual([]);
    expect(state.open).toBe(true);
    expect(threadTexts(widget)).toEqual([]);
    expect(widget.navigated).toEqual(['https://magento.test/category/?product_list_order=name']);
});

test('reset aborts a pending request', () => {
    global.fetchMock.mockReturnValue(deferred().promise);
    const widget = createWidget();
    type(widget, 'red shoes');
    pressEnter(widget);

    widget.ui.reset.trigger('click');

    expect(global.fetchMock.mock.calls[0][1].signal.aborted).toBe(true);
    expect(widget.ui.thread.find('.tw-assistant__message--pending').length).toBe(0);
});

test('messages are rendered as text', () => {
    window.sessionStorage.setItem(STORAGE_KEY, JSON.stringify({
        messages: [{role: 'assistant', content: '<img src=x onerror=alert(1)>'}],
        history: []
    }));
    const widget = createWidget();

    expect(widget.ui.thread.find('img').length).toBe(0);
    expect(threadTexts(widget)).toEqual(['<img src=x onerror=alert(1)>']);
});

test('search results are loaded through ajax navigation with the existing hash', async () => {
    global.fetchMock.mockReturnValue(jsonResponse(200, {
        messages: [{role: 'assistant', content: 'Found it'}],
        history: [],
        searchTerm: 'red shoes',
        url: 'https://magento.test/catalogsearch/result/?q=red%20shoes&kleur%5B%5D=Rood',
        filters: {q: 'red shoes', kleur: ['Rood']},
        ajax: true
    }));
    const widget = createWidget();
    $('#facet-filter-form').html(`
        <input type="hidden" name="__tw_ajax_type" value="search">
        <input type="hidden" name="__tw_object_id" value="0">
        <input type="hidden" name="__tw_original_url" value="catalogsearch/result/index">
        <input type="hidden" name="__tw_hash" value="hash">
        <input type="hidden" name="product_list_order" value="price">
        <input type="hidden" name="q" value="shoes">
        <input type="checkbox" name="maat[]" value="M" checked>
    `);
    $('body').append('<input id="search" value="shoes">');
    const navigation = {
        options: {ajaxFilters: true, ajaxEndpoint: '/tweakwise/ajax/navigation', ajaxCache: true},
        _startLoader: jest.fn(),
        _stopLoader: jest.fn(),
        _updateBlocks: jest.fn(),
        _updateCanonical: jest.fn()
    };
    $('#facet-filter-form').data('tweakwiseNavigationForm', navigation);
    const ajax = jest.spyOn($, 'ajax').mockImplementation(() => $.Deferred().resolve({html: '<div></div>', canonical: 'c'}));
    type(widget, 'red shoes');
    pressEnter(widget);
    await flush();
    await flush();

    const params = new URLSearchParams(ajax.mock.calls[0][0].data);
    expect(ajax.mock.calls[0][0].url).toBe('/tweakwise/ajax/navigation');
    expect(params.get('__tw_hash')).toBe('hash');
    expect(params.get('__tw_original_url')).toBe('catalogsearch/result/index');
    expect(params.get('product_list_order')).toBe('price');
    expect(params.get('q')).toBe('red shoes');
    expect(params.getAll('kleur[]')).toEqual(['Rood']);
    expect(params.has('maat[]')).toBe(false);
    expect(navigation._updateBlocks).toHaveBeenCalledWith('<div></div>');
    expect(navigation._updateCanonical).toHaveBeenCalledWith('c');
    expect(window.location.search).toBe('?q=red%20shoes&kleur%5B%5D=Rood');
    expect($('#search').val()).toBe('red shoes');
    expect(widget.navigated).toEqual([]);
    ajax.mockRestore();
});

test('the latest assistant message is revealed word by word', () => {
    jest.useFakeTimers();
    window.matchMedia = () => ({matches: false});
    window.sessionStorage.setItem(STORAGE_KEY, JSON.stringify({
        messages: [{role: 'assistant', content: 'one two three four five'}],
        history: [],
        open: true,
        revealPending: true
    }));

    const widget = createWidget();
    expect(threadTexts(widget)[0].trim()).toBe('one');
    jest.advanceTimersByTime(60);
    expect(threadTexts(widget)[0].trim()).toBe('one two');
    jest.runAllTimers();
    expect(threadTexts(widget)[0]).toBe('one two three four five');
    expect(JSON.parse(window.sessionStorage.getItem(STORAGE_KEY)).revealPending).toBe(false);
    jest.useRealTimers();
});

test('reduced motion shows the message immediately', () => {
    window.matchMedia = () => ({matches: true});
    window.sessionStorage.setItem(STORAGE_KEY, JSON.stringify({
        messages: [{role: 'assistant', content: 'one two three'}],
        history: [],
        revealPending: true
    }));

    expect(threadTexts(createWidget())[0]).toBe('one two three');
});
