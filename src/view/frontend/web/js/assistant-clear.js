/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2026 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Removes stored AI Shopping Assistant conversations, used after logout.
 */
define([], function () {
    'use strict';

    return function () {
        try {
            Object.keys(window.sessionStorage)
                .filter((key) => key.indexOf('tw-assistant-') === 0)
                .forEach((key) => window.sessionStorage.removeItem(key));
        } catch (e) {
            // Storage unavailable, nothing to clear
        }
    };
});
