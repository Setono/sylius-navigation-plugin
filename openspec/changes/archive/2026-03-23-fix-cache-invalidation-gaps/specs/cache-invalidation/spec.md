## ADDED Requirements

### Requirement: Cache invalidation on item deletion
The system SHALL invalidate the navigation rendering cache when items are deleted via `deleteItemAction()`.

#### Scenario: Deleting a single item invalidates cache
- **WHEN** an item is deleted from a navigation via the build controller
- **THEN** the cache for that navigation MUST be invalidated

#### Scenario: Deleting a subtree invalidates cache
- **WHEN** an item with children is deleted (removing the entire subtree) via the build controller
- **THEN** the cache for that navigation MUST be invalidated

#### Scenario: Cache invalidation is skipped when caching is disabled
- **WHEN** an item is deleted and the cache renderer is not available (caching disabled)
- **THEN** the deletion completes successfully without attempting cache invalidation

### Requirement: Cache invalidation on item reorder
The system SHALL invalidate the navigation rendering cache when items are reordered via `reorderItemAction()`.

#### Scenario: Reordering within the same parent invalidates cache
- **WHEN** an item is moved to a different position under the same parent
- **THEN** the cache for that navigation MUST be invalidated

#### Scenario: Moving to a different parent invalidates cache
- **WHEN** an item is moved from one parent to another
- **THEN** the cache for that navigation MUST be invalidated

#### Scenario: Moving to an empty parent invalidates cache
- **WHEN** an item is moved to a parent that has no existing children and the item's position index does not change
- **THEN** the cache for that navigation MUST still be invalidated

#### Scenario: Cache invalidation is skipped when caching is disabled
- **WHEN** an item is reordered and the cache renderer is not available (caching disabled)
- **THEN** the reorder completes successfully without attempting cache invalidation
