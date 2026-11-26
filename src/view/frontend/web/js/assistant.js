/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2026 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

define([
    'jquery',
    'mage/cookies'
], function ($) {
    'use strict';

    const WORD_DELAY = 60;
    const SYSTEM_INPUT = /^(__tw_|product_list_)/;

    $.widget('tweakwise.assistant', {
        options: {
            endpoint: '',
            storageKey: 'tw-assistant',
            context: {},
            selectedFacets: [],
            searchTerm: null,
            unavailableReason: null,
            filterBlockSelector: '#layered-filter-block',
            filterFormSelector: '#facet-filter-form',
            searchInputSelector: '#search',
            labels: {}
        },

        state: null,
        controller: null,
        requestId: 0,
        revealTimer: null,

        _create: function () {
            this.ui = {
                open: this.element.find('[data-role="tw-assistant-open"]'),
                panel: this.element.find('[data-role="tw-assistant-panel"]'),
                messages: this.element.find('[data-role="tw-assistant-messages"]'),
                empty: this.element.find('[data-role="tw-assistant-empty"]'),
                thread: this.element.find('[data-role="tw-assistant-thread"]'),
                error: this.element.find('[data-role="tw-assistant-error"]'),
                quota: this.element.find('[data-role="tw-assistant-quota"]'),
                status: this.element.find('[data-role="tw-assistant-status"]'),
                composer: this.element.find('[data-role="tw-assistant-composer"]'),
                input: this.element.find('[data-role="tw-assistant-input"]'),
                submit: this.element.find('[data-role="tw-assistant-submit"]'),
                reset: this.element.find('[data-role="tw-assistant-reset"]'),
                close: this.element.find('[data-role="tw-assistant-close"]')
            };
            this.state = this._loadState();
            this._bind();
            this._renderMessages();

            if (this.options.unavailableReason) {
                this._showQuota(this.options.unavailableReason);
            }

            if (this.state.open) {
                this._open(false);
            }

            if (this.state.revealPending) {
                this.state.revealPending = false;
                this._saveState();
                this._revealLastMessage();
                if (this.state.open && !this.ui.input.prop('disabled')) {
                    this.ui.input.trigger('focus');
                }
            }

            this._updateSubmit();
        },

        _destroy: function () {
            this._abort();
            this._stopReveal();
            this.element.off('.twAssistant');
        },

        _bind: function () {
            this.ui.open.on('click.twAssistant', () => this._open(true));
            this.ui.close.on('click.twAssistant', () => this._close());
            this.ui.reset.on('click.twAssistant', () => this._reset());
            this.ui.input.on('input.twAssistant', () => this._updateSubmit());
            this.ui.input.on('keydown.twAssistant', (event) => {
                if (event.key === 'Enter' && !event.shiftKey && !event.isComposing && event.keyCode !== 229) {
                    event.preventDefault();
                    this._submit();
                }
            });
            this.ui.composer.on('submit.twAssistant', (event) => {
                event.preventDefault();
                if (this.controller) {
                    this._cancel();
                    return;
                }
                this._submit();
            });
        },

        // ------- State

        _emptyState: function () {
            return {messages: [], history: [], open: false, startUrl: null, revealPending: false};
        },

        _loadState: function () {
            try {
                const stored = JSON.parse(window.sessionStorage.getItem(this.options.storageKey) || 'null');
                if (stored && Array.isArray(stored.messages) && Array.isArray(stored.history)) {
                    return $.extend(this._emptyState(), stored);
                }
            } catch (e) {
                // Ignore unavailable or corrupt storage
            }

            return this._emptyState();
        },

        _saveState: function () {
            try {
                window.sessionStorage.setItem(this.options.storageKey, JSON.stringify(this.state));
            } catch (e) {
                // Storage can be unavailable (private mode, quota), the conversation then lasts for this page only
            }
        },

        // ------- Panel

        _open: function (focus) {
            $(this.options.filterBlockSelector).addClass('tw-assistant-open');
            this.ui.open.attr('aria-expanded', 'true');
            this.ui.panel.prop('hidden', false);
            this.state.open = true;
            this._saveState();
            this._scrollToBottom();
            if (focus) {
                this.ui.input.trigger('focus');
            }
        },

        _close: function () {
            $(this.options.filterBlockSelector).removeClass('tw-assistant-open');
            this.ui.open.attr('aria-expanded', 'false');
            this.ui.panel.prop('hidden', true);
            this.state.open = false;
            this._saveState();
            this.ui.open.trigger('focus');
        },

        _reset: function () {
            const startUrl = this.state.startUrl;
            this._abort();
            this._stopReveal();
            this.state = $.extend(this._emptyState(), {open: true});
            this._saveState();
            this._hideError();
            this._renderMessages();
            this.ui.input.val('');
            this._setPending(false);

            if (startUrl && startUrl !== window.location.href) {
                this._navigate(startUrl);
                return;
            }

            this.ui.input.trigger('focus');
        },

        // ------- Messages

        _submit: function () {
            const content = $.trim(this.ui.input.val() || '');
            if (!content || this.controller || this.ui.input.prop('disabled')) {
                return;
            }

            const previousMessages = this.state.messages.slice();
            const messages = previousMessages.concat([{role: 'user', content: content}]);

            this._stopReveal();
            this._hideError();
            this.state.messages = messages;
            this.state.startUrl = this.state.startUrl || window.location.href;
            this._saveState();
            this._renderMessages();
            this.ui.input.val('');
            this._request(messages, previousMessages, content);
            this._setPending(true);
        },

        _cancel: function () {
            const pending = this.pending;
            this._abort();
            this._setPending(false);
            if (pending) {
                this.state.messages = pending.previousMessages;
                this._saveState();
                this._renderMessages();
                this.ui.input.val(pending.content);
            }
            this._updateSubmit();
            this.ui.input.trigger('focus');
        },

        _abort: function () {
            this.requestId++;
            if (this.controller) {
                this.controller.abort();
            }
            this.controller = null;
            this.pending = null;
        },

        _request: function (messages, previousMessages, content) {
            const requestId = ++this.requestId;
            const controller = new AbortController();
            const body = new URLSearchParams();

            this.controller = controller;
            this.pending = {previousMessages: previousMessages, content: content};

            body.append('form_key', $.mage.cookies.get('form_key') || '');
            body.append('payload', JSON.stringify({
                messages: messages,
                history: this.state.history,
                selectedFacets: this.options.selectedFacets,
                searchTerm: this.options.searchTerm,
                context: this.options.context,
                toolbar: this._getToolbarParameters()
            }));

            fetch(this.options.endpoint, {
                method: 'POST',
                body: body,
                credentials: 'same-origin',
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'},
                signal: controller.signal
            }).then((response) => response.json().catch(() => null).then((data) => {
                if (!response.ok || !data || !Array.isArray(data.messages)) {
                    throw data || {};
                }

                return data;
            })).then((data) => {
                if (requestId !== this.requestId) {
                    return;
                }

                this.controller = null;
                this.pending = null;
                this._handleSuccess(data);
            }).catch((error) => {
                if (requestId !== this.requestId || (error && error.name === 'AbortError')) {
                    return;
                }

                this.controller = null;
                this.pending = null;
                this._handleError(error || {});
            });
        },

        _handleSuccess: function (data) {
            this.state.messages = data.messages.filter(
                (message) => message && typeof message.content === 'string'
            );
            this.state.history = Array.isArray(data.history) ? data.history : [];
            this.state.revealPending = true;
            this.state.open = true;
            this._saveState();

            if (!this._loadResultWithAjax(data)) {
                this._navigate(data.url);
            }
        },

        _handleError: function (error) {
            this._setPending(false);
            if (error.reason && this.options.labels.quota && this.options.labels.quota[error.reason]) {
                this._showQuota(error.reason);
            } else {
                this.ui.error.prop('hidden', false);
            }
            this._scrollToBottom();
            this._updateSubmit();
            this.ui.input.trigger('focus');
        },

        // ------- Results

        /**
         * Loads the result through the existing ajax navigation, this re-renders the layered navigation and
         * thereby this widget. Returns false when the result requires a full page load.
         */
        _loadResultWithAjax: function (data) {
            const form = $(this.options.filterFormSelector);
            const navigation = form.data('tweakwiseNavigationForm');
            if (!data.ajax || !navigation || !navigation.options.ajaxFilters
                || !form.find('input[name="__tw_hash"]').length
            ) {
                return false;
            }

            const params = new URLSearchParams();
            form.find('input[type="hidden"]').each((index, input) => {
                if (input.name && SYSTEM_INPUT.test(input.name)) {
                    params.append(input.name, input.value);
                }
            });
            $.each(data.filters || {}, (key, value) => {
                params.delete(key);
                (Array.isArray(value) ? value : [value]).forEach((item) => {
                    params.append(Array.isArray(value) ? key + '[]' : key, item);
                });
            });

            navigation._startLoader();
            $.ajax({
                url: navigation.options.ajaxEndpoint,
                data: params.toString(),
                cache: navigation.options.ajaxCache
            }).done((response) => {
                if (!response || typeof response.html !== 'string') {
                    this._navigate(data.url);
                    return;
                }

                window.history.pushState({html: response.html, canonical: response.canonical}, '', data.url);
                $(this.options.searchInputSelector).val(data.searchTerm || '');
                navigation._updateBlocks(response.html);
                navigation._updateCanonical(response.canonical);
                this._finishWhenNotRendered(data);
            }).fail(() => {
                this._navigate(data.url);
            }).always(() => {
                navigation._stopLoader();
            });

            return true;
        },

        /**
         * The result normally re-renders this widget, which shows the answer. Show it here when it was not
         * re-rendered, so the widget does not stay pending.
         */
        _finishWhenNotRendered: function (data) {
            if (!$.contains(document.documentElement, this.element.get(0))) {
                return;
            }

            this.options.searchTerm = data.searchTerm || null;
            this.state.revealPending = false;
            this._saveState();
            this._setPending(false);
            this._renderMessages();
            this._revealLastMessage();
        },

        _navigate: function (url) {
            window.location.assign(url);
        },

        _getToolbarParameters: function () {
            const result = {};
            new URLSearchParams(window.location.search).forEach((value, key) => {
                if (['product_list_order', 'product_list_dir', 'product_list_limit', 'product_list_mode'].includes(key)) {
                    result[key] = value;
                }
            });

            return result;
        },

        // ------- Rendering

        _renderMessages: function () {
            this.ui.thread.empty();
            this.state.messages.forEach((message) => this.ui.thread.append(this._createMessage(message)));
            this.ui.empty.prop('hidden', this.state.messages.length > 0);
            this._scrollToBottom();
        },

        _createMessage: function (message) {
            const role = message.role === 'user' ? 'user' : 'assistant';
            const item = $('<div>').addClass('tw-assistant__message tw-assistant__message--' + role);
            $('<span>').addClass('tw-assistant__author').text(this.options.labels[role] || role).appendTo(item);
            $('<p>').addClass('tw-assistant__bubble').text(message.content).appendTo(item);

            return item;
        },

        _setPending: function (pending) {
            this.ui.thread.find('.tw-assistant__message--pending').remove();
            this.ui.input.prop('disabled', pending || !!this.quotaReason);
            this.ui.messages.attr('aria-busy', pending ? 'true' : 'false');
            this.ui.submit.text(pending ? this.options.labels.cancel : this.options.labels.search);
            this.element.toggleClass('tw-assistant--pending', pending);

            if (pending) {
                const item = $('<div>')
                    .addClass('tw-assistant__message tw-assistant__message--assistant tw-assistant__message--pending')
                    .attr({role: 'status', 'aria-busy': 'true'});
                $('<span>').addClass('tw-assistant__author').text(this.options.labels.assistant).appendTo(item);
                const bubble = $('<p>').addClass('tw-assistant__bubble tw-assistant__typing').appendTo(item);
                $('<span>').addClass('tw-assistant__sr-only').text(this.options.labels.responding).appendTo(bubble);
                for (let i = 0; i < 3; i++) {
                    $('<span>').addClass('tw-assistant__dot').attr('aria-hidden', 'true').appendTo(bubble);
                }
                this.ui.thread.append(item);
                this._scrollToBottom();
            }

            this._updateSubmit();
        },

        _updateSubmit: function () {
            const pending = !!this.controller;
            const hasInput = $.trim(this.ui.input.val() || '') !== '';
            this.ui.submit.prop('disabled', !pending && (!hasInput || !!this.quotaReason));
        },

        _showQuota: function (reason) {
            this.quotaReason = reason;
            this.ui.quota.text(this.options.labels.quota[reason]).prop('hidden', false);
            this.ui.error.prop('hidden', true);
            this.ui.status.addClass('tw-assistant__status--unavailable');
            this.ui.input.prop('disabled', true);
            this._updateSubmit();
        },

        _hideError: function () {
            this.ui.error.prop('hidden', true);
        },

        _revealLastMessage: function () {
            const bubble = this.ui.thread.find('.tw-assistant__message--assistant .tw-assistant__bubble').last();
            const text = bubble.text();
            const reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (!bubble.length || reduceMotion) {
                return;
            }

            const words = text.split(/(\s+)/);
            let index = 0;
            bubble.text('');
            const step = () => {
                bubble.text(bubble.text() + words.slice(index, index + 2).join(''));
                index += 2;
                this._scrollToBottom();
                if (index < words.length) {
                    this.revealTimer = window.setTimeout(step, WORD_DELAY);
                } else {
                    this.revealTimer = null;
                }
            };
            step();
        },

        _stopReveal: function () {
            if (this.revealTimer) {
                window.clearTimeout(this.revealTimer);
                this.revealTimer = null;
                this._renderMessages();
            }
        },

        _scrollToBottom: function () {
            const element = this.ui.messages.get(0);
            if (element) {
                element.scrollTop = element.scrollHeight;
            }
        }
    });

    return $.tweakwise.assistant;
});
