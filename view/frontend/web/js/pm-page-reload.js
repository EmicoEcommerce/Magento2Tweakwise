/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2023 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

define([
    'jquery',
    'mage/cookies',
    'tweakwiseNavigationForm'
], function ($) {
    $.widget('tweakwise.pmPageReload', {

        options: {
            cookieName: '',
            reloadList: false
        },

        _create: function () {
            this._hookEvents();
            return this._superApply(arguments);
        },

        _hookEvents: function () {
            var reload = this.options.reloadList
                && this.options.cookieName
                && ($.mage.cookies.get(this.options.cookieName) !== null);
            if (reload) {
                const urlParams = new URLSearchParams(window.location.search);
                const pageParam = urlParams.get('p');
                if (pageParam) {
                    $(this.element.append('<input type="hidden" name="p" value="'+ parseInt(pageParam) +'" />'))
                }
                this.element.trigger('change');
            }
        }
    });

    return $.tweakwise.pmPageReload;
});
