## Why

The navigation rendering cache is not invalidated in all mutation paths. Specifically, deleting items via `ClosureManager::removeTree()` uses DQL bulk deletes which bypass Doctrine lifecycle events entirely, so `postRemove` never fires. Additionally, moving an item to a different parent with no existing children can result in no Item property changes, meaning no `postUpdate` fires and the cache stays stale. This was reported in GitHub issue #6.

## What Changes

- Add explicit cache invalidation in `BuildController::deleteItemAction()` after `removeTree()` call
- Add explicit cache invalidation in `BuildController::reorderItemAction()` after `moveItem()` call
- Use nullable `?CachedNavigationRenderer` parameter (same pattern as `NavigationBuilder`) since caching is conditional

## Capabilities

### New Capabilities

- `cache-invalidation`: Defines when and how the navigation rendering cache must be invalidated across all mutation paths

### Modified Capabilities


## Impact

- `src/Controller/BuildController.php` — two actions gain explicit cache invalidation calls
- `src/Resources/config/services/conditional/renderer_cached.xml` — no changes expected (controller actions use method-level DI)
- Existing Doctrine listeners remain unchanged as a safety net for non-controller mutations
