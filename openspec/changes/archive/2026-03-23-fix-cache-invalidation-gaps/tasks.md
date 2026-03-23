## 1. Add cache invalidation to deleteItemAction

- [x] 1.1 Add nullable `?CachedNavigationRenderer $cachedRenderer = null` parameter to `deleteItemAction()` in `BuildController`
- [x] 1.2 Call `$cachedRenderer?->invalidate($navigation)` after `$closureManager->removeTree($item)` succeeds

## 2. Add cache invalidation to reorderItemAction

- [x] 2.1 Add nullable `?CachedNavigationRenderer $cachedRenderer = null` parameter to `reorderItemAction()` in `BuildController`
- [x] 2.2 Call `$cachedRenderer?->invalidate($navigation)` after `$closureManager->moveItem()` succeeds

## 3. Tests

- [x] 3.1 Add test for `deleteItemAction` cache invalidation — verify `invalidate()` is called after deletion
- [x] 3.2 Add test for `reorderItemAction` cache invalidation — verify `invalidate()` is called after reorder
- [x] 3.3 Add tests verifying graceful behavior when `$cachedRenderer` is null (caching disabled)

## 4. Cleanup

- [x] 4.1 Close GitHub issue #6
