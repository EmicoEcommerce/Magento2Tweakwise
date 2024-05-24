/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

define([
    'jquery',
    'Magento_Search/form-mini'
], function ($, quickSearch) {
    $.widget('tweakwise.quickSearch', quickSearch, {

        // Declare the variable to hold the current AJAX request
        currentRequest: null,

        getSelectedProductUrl: function () {
            if (!this.responseList.selected) {
                return null;
            }

            return this.responseList.selected.data('url');
        },

        _create: function () {
            $(this.options.formSelector).on('submit', function (event) {
                if (this.getSelectedProductUrl()) {
                    event.preventDefault();
                }
            }.bind(this));

            // Use jQuery's ajaxSend event to abort the previous AJAX request before a new one is sent
            $(document).ajaxSend(function (event, jqXHR, ajaxOptions) {

                // Get the request URL and type from ajaxOptions
                var requestUrl = ajaxOptions.url;
                // If there's an ongoing AJAX request, abort it if it is an search request and if the new request is an search request
                if (this.currentRequest && requestUrl.indexOf('search/ajax') !== -1) {

                    this.currentRequest.abort();
                    // Store the jqXHR object of the new AJAX request
                    this.currentRequest = jqXHR;
                } else if (requestUrl.indexOf('search/ajax') !== -1) {
                    this.currentRequest = jqXHR;
                }
            }.bind(this));

            var templateId = '#autocomplete-item-template';
            this.options.template = templateId;
            this.options.url = $(templateId).data('url');

            return this._superApply(arguments);
        },

        _onSubmit: function () {
            var url = this.getSelectedProductUrl();
            if (!url) {
                return this._superApply(arguments);
            }

            window.location.href = url;
        },

        _onPropertyChange: function () {
            if (this.searchDelayTimeout) {
                clearTimeout(this.searchDelayTimeout);
            }

            this.searchDelayTimeout = setTimeout(function () {
                quickSearch.prototype._onPropertyChange.apply(this);
            }.bind(this), 200);
        }
    });

    return $.tweakwise.quickSearch;
});
