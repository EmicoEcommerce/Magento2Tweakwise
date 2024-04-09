# [6.0.0-beta.2](https://github.com/EmicoEcommerce/Magento2Tweakwise/compare/v6.0.0-beta.1...v6.0.0-beta.2) (2024-04-09)


### Features

* searchable filters ([#166](https://github.com/EmicoEcommerce/Magento2Tweakwise/issues/166)) ([bebd6c6](https://github.com/EmicoEcommerce/Magento2Tweakwise/commit/bebd6c67b101878cc7086da9df6341eb76caa07d))

# [6.0.0-beta.1](https://github.com/EmicoEcommerce/Magento2Tweakwise/compare/v5.8.3...v6.0.0-beta.1) (2024-03-29)


### Performance Improvements

* Applied phpcs and phpmd rules ([9a499fd](https://github.com/EmicoEcommerce/Magento2Tweakwise/commit/9a499fdb5a1fb420a6dd4e24eb985d7d87029172))


### BREAKING CHANGES

* Code is refactored based on phpcs and phpmd rules.

<h1>Changelog</h1>

## 5.0.0
Backward incompatible changes
- Change repository to tweakwise repo. You need to uninstall the old tweakwise package en reinstall this one

## 5.0.1
Update module with changes from emico/tweawise v4.3.0

## 5.0.2
Bug fix for 500 error in autocomplete when no products are found

## 5.0.3
Bug fixes for autocomplete
- Old autocomplete api is still called when new suggestion api is enabled.
- When using the suggetions api, if the result contains an category you get an 500 error.

## 5.0.4
Bug fix for areacode in config
Updated readme

## 5.0.5
Bug fix for personal merchandising hyva theme

## 5.0.6
Bug fix adobe commerce

## 5.0.7
Bug fix show parent in autocomplete settings
Bug fix for commerce, searching is slow and wrong item count in hyva theme

## 5.0.8
Bug fix for installer when using an db prefix 

## 5.1.0
- Added support for searchbanners. Show searchbanners in search results. Configure this in tweakwise. Only setting in magento is to enable/disable showing searchbanners
- Bug fix upsell/crossell templates

## 5.1.1
- Bug fix deprecated ctype function
- Bug fix swatch order
- Fix float and 0 prices

## 5.1.2
- Escape clear url

## 5.2.0
- Feature shoppingcart recommendations from tweakwise
- Bug fix ajax query url

## 5.2.1
- Bug fix upselll plugin not triggered

## 5.3.0
- Limit group code recommendations by @ah-net in #40
- Rename recommendations by @ah-net in #32
- add category to featured request by @ah-net in #42
- Feature shoppingcart crosssell featured by @ah-net in #35
- php8.2 by @ah-net in #43
- Added php 8.2 support, and droppend php 7.4 support

## 5.3.1
- Bug fix for slash in category url #45

## 5.3.2
- Fixed bug with undifined function

## 5.3.3
- Updated composer.json, removed wrong conflict value

## 5.4.0
- Fixed php 8.2 bug #53
- Fixed bug when search was enabled #46
- Speed improvement. Removed multiple spaces fram html response #50
- Remove container css class from searchbanners #49
- Improved XSS protection #47

## 5.4.1
- Fixed bug in observer #57
- Fixed items typo #59
- Hide categorie in url #55
- Bugfix category rewrite #56
- Improve query param url generation #37
- Add tn profile key to suggestions #58

## 5.4.2
- Fixed php 8.2 bug #61
- Use salt from db instead of session for xss hash

## 5.4.3
- Removed undefined index #64
- Revert changes from pull request #56. That pull request causes issues with the category url. The original issue from #56 will be fixed later

## v5.4.4
- Fix category url if url is rewrite #67

## 5.4.5
- Bugfix search query parameters #69
- Delete p=1 indicator for first page #68
- Fix pagination/sorting url #72

## 5.5.0
- Add fallback for category recommendations #71

## 5.5.1
- Bump version number #73

## 5.5.2
- Return last added item back in around plugin #75

## 5.6.0
- Fix bug with multiple filters selected #84
- Rewrite fallbck #70

## 5.6.1
- Bump version nr

## 5.6.2
- Bugfix query parameters with multiple filters selected #87
- Bugfix check hash value on page load #86

## 5.6.3
- Revert fix fallback #70

## 5.6.4
- Bump version nr

## 5.6.5
- Fix store code in url #90
- Bugfix guzzle library #93

## 5.7.0
- Merge query parameters back after tweakwise personal merchandise refresh #83
- Bugfix fallback prduct collection #91
- Fix empty search result #96
- Feature facets requests #97
- Feature server timing #102

## 5.7.1
- Fix undefined array key #106

## 5.7.2
- Fix wrong variable check causing active filters to not be shown #110

## 5.7.3 
- Remove category filter for facets requests #111
- Fix bug in tncid in recommendations #109

## 5.7.4
- Fix bug resulting in active filters not being shown
- Added ci pipeline to run unit tests
- Added support for multiple stores for ALP facets requests
- Rename personal merchandising
- Fix bug when searchbanners response is empty
- Fix bug in last item added to shoppingcart, should be removed after reading
- Disable searchbanners when search is disabled
- Items misspelled causing a lookup to Tweakwise not to be used

## 5.7.5
- Resolved an XSS security vulnerability linked to product sorting.
- Fixed an issue causing JavaScript errors with the hyva theme and ajax navigation.
- Enhanced SEO by removing 'p1' from URLs; this issue specifically affected the query parameter strategy for URLs.
- Rectified a bug causing an undefined index in active filters while using the ALP module.
- Addressed a problem where 404 errors were logged in Magento when_ a product, no longer available in Tweakwise (e.g., out of stock), still had recommendations called. This change prevents such 404 errors from being logged.
- Fixed an issue causing double filter values in URLs. This occurred when activating a filter while personal merchandising was active, resulting in incorrect filter URLs with doubled values.
- Improved handling of double values in URL filters. Now, only consecutive double values, such as '/category/category, are removed. This also removes unwanted double slashes in the url.
- Cleaned up unused imports
- Prevented category ID from being added to the URL during certain category searches, ensuring URL consistency

## 5.8.0
The category view in Magento differs from the way it is presented in Tweakwise Demoshop, which we considered to be the desired behavior. The difference is related to the way how (sub)category levels are shown. 

We've added a setting in which you can define whether to use the old/existing way of presenting, or whether you want to use the desired way of presenting. The setting 'category view' which you can find under "Stores->Catalog->Tweakwise->Layered Navigation->Category View" is set default to 'simple'. This represents the old way of presenting and nothing will change if you do not touch this setting. If the setting is changed to 'extended', the category view is changed to the same view as the Tweakise demoshop

## 5.8.1
- Resolved an issue where the category filter was not considering the current applied filter, specifically when the query parameter strategy was enabled.
- Fixed a bug where sending the root category in Tweakwise requests resulted in 500 errors.
- Addressed an issue where a page reload caused the URL to be incorrect, preventing filters from deactivating.
- Implemented a check to verify if an item is a Tweakwise filter, preventing 500 errors when the Tweakwise API doesn't respond.


## 5.8.2
- Resolved an issue where the incorrect parent category was utilized while navigating a category tree that is three levels deep.
- Resolved an issue where the filter parameter remained in the URL after deactivating the filter. 
- Resolved an issue where, with the URL pathslug strategy enabled, selecting a filter on a category page would lead to an incorrect URL upon refreshing the page and selecting another filter. 
- Bugfix implemented to address the duplication of category names in the default Magento renderer.
- Implemented query length limitation for search to prevent a 500 error. 
- Implemented a preventive measure to avoid errors when no attribute values are present for ALP facet requests. 
- Resolved the issue with personal merchandising and pagination not functioning correctly when utilising the filter form.
- Implemented measures to prevent a 500 error in the search when Tweakwise is inaccessible or down.

## 5.8.3
- Implemented prevention of duplicate attribute slug values. Previously, attribute values with variations in their representations, such as Black and Black", resulted in duplicate entries in the tweakwise_attribute_slug table. This duplication could disrupt filter functionality if the incorrect value was retrieved. This pull request addresses the issue by appending a unique identifier ("-") followed by a number to each slug, ensuring their uniqueness. Note that attributes with previously duplicated slug values will require re-saving to activate this fix.
- Resolved a notice issue pertaining to missing variables when the shopping cart is empty. Previously, certain sections of the application would trigger notices due to uninitialized variables when the cart was empty.
