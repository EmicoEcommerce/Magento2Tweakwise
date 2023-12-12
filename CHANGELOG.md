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
