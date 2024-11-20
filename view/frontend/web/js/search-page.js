define('Tweakwise_Magento2Tweakwise/js/search-page', ['jquery'], function($) {
    'use strict';

    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
    }

    function setCookie(name, value, days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        const expires = `expires=${date.toUTCString()}`;
        document.cookie = `${name}=${value}; ${expires}; path=/`;
    }

    return function(config) {
        $(document).ready(function() {
            var apiUrl = config.apiUrl;
            var searchQuery = config.searchQuery;
            var instanceKey = config.instanceKey;
            var cookieName = config.tweakwiseCookieName;

            //check cookie for profile key
            var profileKey = getCookie(cookieName);
            //if cookie not set, generate an random profile key
            if (!profileKey) {
                profileKey = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
                setCookie(cookieName, profileKey, { expires: 365, path: '/' });
            }

            $.ajax({
                url: apiUrl,
                method: 'POST',
                headers: {
                    'Instance-Key': instanceKey
                },
                data: {
                    profileKey: profileKey,
                    searchTerm: searchQuery
                },
                error: function(error) {
                    console.error('Tweakwise API call failed:', error);
                }
            });
        });
    };
});
