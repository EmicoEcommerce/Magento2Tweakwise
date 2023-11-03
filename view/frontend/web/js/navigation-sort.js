/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

define([
    'jquery'
], function ($) {
    $.widget('tweakwise.navigationSort', {

        options: {
            hasAlternateSort: null,
        },

        _create: function () {
            this._hookEvents();
            return this._superApply(arguments);
        },

        /**
         * Bind more and less items click handlers
         *
         * @private
         */
        _hookEvents: function () {
            this.element.on('click', '.more-items', this._handleMoreItemsLink.bind(this));
            this.element.on('click', '.less-items', this._handleLessItemsLink.bind(this));
            this.element.on('keyup', '.tw_filtersearch', this._handleFilterSearch.bind(this));
        },

        /**
         * Sort items depending on alternate sort (this comes from tweakwise api) and expand filter list
         *
         * @returns {boolean}
         * @private
         */
        _handleMoreItemsLink: function () {
            this._sortItems('alternate-sort');
            this.element.find('.default-hidden').show();
            this.element.find('.more-items').hide();

            return false;
        },

        /**
         * Sort items depending on alternate sort (this comes from tweakwise api) and abbreviate filter list
         *
         * @returns {boolean}
         * @private
         */
        _handleLessItemsLink: function () {
            this._sortItems('original-sort');
            this.element.find('.default-hidden').hide();
            this.element.find('.more-items').show();

            return false;
        },

        /**
         * Sort items based on alternate sort (if available)
         *
         * @param type
         * @private
         */
        _sortItems: function (type) {
            if (!this.options.hasAlternateSort) {
                return;
            }

            var list = this.element.find('.items');
            list.children('.item').sort(function (a, b) {
                return $(a).data(type) - $(b).data(type);
            }).appendTo(list);
        },

        _handleFilterSearch: function () {
            var filterInput = this.element.find('.tw_filtersearch');
            var value = filterInput.val().toLowerCase().trim();
            var items = filterInput.parent('div').find('ol');
            var noItems = filterInput.parent('div').find('.tw_search_no_results');
            var defaultVisibleItems = filterInput.data('max-visible');
            var counter = -1;
            var filterElement = 'li'

            if (items.length == 0) {
                //swatch
                items = filterInput.parent('div');
                filterElement = 'a';
                defaultVisibleItems = 100;
            }


            items.find(filterElement).show().filter(function () {
                //dont hide selected values
                if ($(this).find('input').is(":checked")) {
                    counter++;
                    return false;
                }
                console.log($(this).find('input').val());
                return $(this).find('input').val().toLowerCase().trim().indexOf(value) == -1;
            }).hide();

            if(defaultVisibleItems < items.find(filterElement +':visible').length) {
                //more items visible then max visible items set on filter
                items.find(filterElement + ':visible').show().filter(function () {
                    if ((counter) > defaultVisibleItems) {
                        return true;
                    }
                    counter++;
                    return false;
                }).hide();
            }

            //no items found
            if (items.find(filterElement + ':visible').length < 1) {
                noItems.show();
            } else {
                noItems.hide();
            }


            //hide show more/less button
            if (value.length == 0) {
                filterInput.parent('div').find('.more-items').show();
            } else {
                filterInput.parent('div').find('.more-items').hide();
                filterInput.parent('div').find('.less-items').hide();
            }
        },
    });

    return $.tweakwise.navigationSort;
});
