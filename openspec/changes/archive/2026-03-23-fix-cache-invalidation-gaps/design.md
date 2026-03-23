## Context

The navigation plugin caches rendered navigation HTML keyed by `{code}_{channel}_{locale}` and tagged by `{code}`. Cache invalidation currently relies on Doctrine lifecycle events (`postUpdate`, `postRemove`, `postPersist`) via two listeners:

- `ItemBasedNavigationCacheInvalidatorListener` — watches `ItemInterface` and `NavigationInterface` entity changes
- `TaxonBasedNavigationCacheInvalidatorListener` — watches `TaxonInterface` entity changes

This works for most mutations but fails for two code paths in `BuildController`:

1. **`deleteItemAction()`** calls `ClosureManager::removeTree()` which uses DQL bulk `DELETE` queries. DQL bulk operations bypass Doctrine lifecycle events entirely.
2. **`reorderItemAction()`** calls `ClosureManager::moveItem()`. When an item is moved to a different parent that has no existing children, and the item's position index doesn't change, no `Item` properties are marked dirty by Doctrine. Only `Closure` entities change, but those aren't watched.

## Goals / Non-Goals

**Goals:**
- Ensure cache is invalidated for all tree mutation paths in `BuildController`
- Keep the fix minimal and consistent with existing patterns

**Non-Goals:**
- Refactoring `removeTree()` to use entity-level operations (slower for large trees, and the DQL approach is intentional)
- Adding cache invalidation to `ClosureManager` itself (it shouldn't know about rendering)
- Changing the Doctrine listener approach (listeners remain as safety net for non-controller mutations)

## Decisions

### Explicit invalidation in controller actions

**Decision**: Add `?CachedNavigationRenderer $cachedRenderer = null` as a method parameter to `deleteItemAction()` and `reorderItemAction()`, and call `$cachedRenderer?->invalidate($navigation)` after the mutation.

**Rationale**: This is the same pattern used by `NavigationBuilder::buildFromTaxon()` which already does explicit invalidation. The nullable type handles the case where caching is disabled (the service doesn't exist). Controller-level invalidation makes the cache contract visible rather than relying on implicit Doctrine side-effects.

**Alternative considered**: Injecting the renderer into `ClosureManager` — rejected because `ClosureManager` is a domain service that shouldn't know about rendering concerns. Also, `removeTree()` is called from `NavigationBuilder::buildFromTaxon()` which handles its own invalidation, so double-invalidation would be wasteful.

**Alternative considered**: Refactoring `removeTree()` to use `$manager->remove()` per entity — rejected because the DQL bulk approach is significantly faster for large trees and the performance tradeoff isn't worth it for cache purposes.

## Risks / Trade-offs

- **Double invalidation for some operations**: When Doctrine events DO fire (e.g., same-parent reorder), both the listener AND the explicit call will invalidate. This is harmless — `invalidateTags()` is idempotent and cheap. Better to invalidate twice than zero times.
- **Controller-only coverage**: Mutations made outside the controller (e.g., direct repository calls, other integrations) still rely on Doctrine listeners. The listeners remain registered and functional for these cases.
