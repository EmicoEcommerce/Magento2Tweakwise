# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Module identity

Magento 2 module name: `Tweakwise_Magento2Tweakwise`
Composer package: `tweakwise/magento2-tweakwise`
PSR-4 root: `Tweakwise\Magento2Tweakwise\` → `src/`

Depends on `tweakwise/magento2-tweakwise-export` (the data-export counterpart) and replaces Magento's native layered navigation, search, autocomplete and product recommendations with Tweakwise API results.

## Commands

```sh
# Run all unit tests
vendor/bin/codecept run Unit

# Run a single test file
vendor/bin/codecept run Unit tests/Unit/Model/Catalog/Layer/Url/Strategy/FilterSlugManagerTest.php

# Run the full GrumPHP quality suite (phpcs, phpmd, phpstan)
vendor/bin/grumphp run

# Regenerate SEO filter URL slugs (only relevant when using PathSlug URL strategy)
php bin/magento tweakwise:regenerate-filter-urls
```

Tests use Codeception with a PHPUnit backend. The test namespace is `Tweakwise\Test`, suite config in `codeception.yml` and `tests/Unit.suite.yml`.

## Architecture

### API Client (`src/Model/Client.php`, `src/Model/Client/`)

The `Client` class makes HTTP requests to the Tweakwise API using Guzzle. The API returns XML; `Client` parses it into arrays and passes it to `ResponseFactory` which hydrates typed `Response` objects. All request types (navigation, search, autocomplete, recommendations, analytics) extend `Request` and are created via `RequestFactory` virtual types defined in `di.xml`.

`Client::request($request, async: true)` returns a Guzzle promise instead of blocking. `Client\RequestPool` builds on that: it queues requests (deduplicated on their URL), sends every queued request concurrently with `Promise\Utils::settle()` the moment the first response is resolved, and applies the same error policy as a direct request (`Client::handleApiException()`). Recommendations resolve through the pool (`Recommendation\Context::getResponse()`); `Recommendation\RequestPrefetcher` queues the other enabled recommendation types of a product when a block configures its request (`AbstractRecommendationPlugin::configureRequest()`), so all recommendation calls of a product page go out in one batch (config `tweakwise/recommendations/batch_requests`).

`EndpointManager` handles primary/fallback endpoint failover: if the primary gateway (`gateway.tweakwisenavigator.net`) times out it stores a down-until timestamp in a Magento custom variable and redirects all subsequent requests to the fallback (`gateway.tweakwisenavigator.com`) for 5 minutes.

When any request fails, `Config::setTweakwiseExceptionThrown(true)` is called and subsequent requests in the same page load are skipped, falling back to native Magento behaviour.

### NavigationContext — the per-page request hub (`src/Model/Catalog/Layer/NavigationContext.php`)

`NavigationContext` is the central object for a category or search page. It holds the single Tweakwise `Request` and lazily fetches the single `ProductNavigationResponse` for the page. Everything that needs navigation data (filter list, product collection, toolbar, pagination) injects this context rather than making independent API calls.

Two virtual type instances are declared in `di.xml`:
- `NavigationContext\Category` — uses `ProductNavigationRequest` + `Category\FilterableAttributeList`
- `NavigationContext\Search` — uses `ProductSearchRequest` + `Search\FilterableAttributeList`

`CurrentContext` is a shared singleton that holds whichever context is active for the current request.

### Hooking into Magento's catalog layer

`ItemCollectionProvider` (virtual types `Category` and `Search`) replaces Magento's Elasticsearch collection providers. It receives Tweakwise product IDs from the `NavigationContext` response and passes them to the original Elasticsearch provider so that Magento loads product data in the normal way.

This is wired in `di.xml` by overriding the `collectionProvider` argument of the Magento Elasticsearch virtual types for both category and search contexts.

`FilterList\Plugin` intercepts `Magento\Catalog\Model\Layer\FilterList::getFilters()` and returns Tweakwise facets (from `FilterList\Tweakwise`) instead of native Magento attribute filters.

### URL strategies (`src/Model/Catalog/Layer/Url/Strategy/`)

Two concrete strategies implement the `UrlInterface` / `FilterApplierInterface` / `RouteMatchingInterface` interfaces:

- **`QueryParameterStrategy`** (default): filter URLs as query parameters — `?color=red`
- **`PathSlugStrategy`**: SEO-friendly URLs — `/category/color/red`. Requires a custom Magento router (`Controller/Router.php`) registered at sort order 61 to intercept these paths and apply filters before dispatching the underlying category page.

`FilterSlugManager` is the slug persistence layer used by `PathSlugStrategy`. It maintains a `tweakwise_attribute_slug` DB table (keyed by `attribute` + `store_id`) and a Magento cache entry (`tweakwise.slug.lookup`). Slugs are auto-created when attributes are saved (`Observer/CreateTweakwiseSlugsAfterSaveAttribute`) and can be regenerated via the CLI command.

Active strategy is selected via `UrlStrategyFactory` based on store configuration.

### Plugins

The module is heavily plugin-based. Key plugins declared in `etc/di.xml`:

| Target | Plugin | Purpose |
|---|---|---|
| `Catalog\Model\Layer\FilterList` | `FilterList\Plugin` | Swap in Tweakwise filters |
| `Catalog\Block\Product\ListProduct` | `ProductList\Plugin` | Inject Tweakwise product ordering |
| `Catalog\Block\Product\ProductList\Toolbar` | `Toolbar\Plugin` | Override sorting/pagination from TW response |
| `Theme\Block\Html\Pager` | `Pager\Plugin` | Correct pagination URLs |
| `LayeredNavigation\Block\Navigation\FilterRenderer` | `FilterRenderer\Plugin` | Render TW-specific filter templates |
| `Swatches\Block\LayeredNavigation\RenderLayered` | `RenderLayered\Plugin` | Render TW swatch filters |
| `Framework\View\Page\Config` | `Seo\Robots\Plugin` | Set robots meta based on TW SEO config |

Commerce-only plugins for `TargetRule` upsell/related/crosssell blocks follow the same pattern.

### Recommendations

Three recommendation types (upsell, related, featured) each have their own `Context` virtual type (extending `Recommendation\Context`) with the appropriate `RequestFactory`. Block plugins on `ProductList\Related`, `ProductList\Upsell`, and `Checkout\Cart\Crosssell` intercept native Magento blocks and replace product collections with Tweakwise recommendation results when enabled.

### Analytics observers (`src/Observer/`)

`TweakwiseCheckout` — fires a purchase event to the Tweakwise analytics API on `sales_order_place_after`.
`Event\SendAddToCartEvent` / `SendAddToWishlistEvent` — fire add-to-cart / add-to-wishlist events.

### Configuration (`src/Model/Config.php`)

Single class for all module configuration. Config paths live under `tweakwise/` in `etc/config.xml` and `etc/adminhtml/system.xml`. Check this class for available feature flags before adding new config reads.

## Commit format

Follow semantic-release conventions (used by automated releases):
- `feat:` — new feature
- `fix:` — bug fix
- `refactor:` — code change without feature/fix
- `chore:` / `docs:` / `test:` — maintenance
