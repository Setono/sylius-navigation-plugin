## Why

Channels currently use opt-out semantics: a navigation or item with no channels assigned is visible on **all** channels. This means adding a new channel automatically inherits every existing navigation and item, requiring manual cleanup. The correct default should be explicit opt-in — nothing is visible unless deliberately assigned to a channel.

## What Changes

- **BREAKING**: Empty channel sets on navigations and items now mean "visible nowhere" instead of "visible everywhere"
- Remove the `IS EMPTY` fallback in `NavigationRepository::findOneEnabledByCode` — a navigation must have the requested channel assigned
- Remove the `null === $channel` branch in `NavigationRepository::findOneEnabledByCode` — rendering without a channel context is no longer meaningful
- Simplify channel visibility checks in `GraphBuilder` and `BuildController` to strict `hasChannel()` without empty-set fallback
- Make channels `required` on both `NavigationType` and `ItemType` forms to prevent accidental misconfiguration

## Capabilities

### New Capabilities
- `channel-visibility`: Rules governing when navigations and items are visible on a given channel

### Modified Capabilities

## Impact

- `NavigationRepository::findOneEnabledByCode` — query logic changes, method signature loses optional `$channel` parameter
- `GraphBuilder::build` — item filtering logic simplified
- `BuildController::isItemVisibleOnChannel` — logic simplified
- `NavigationType` / `ItemType` forms — channels field becomes required
- Existing data: any navigation or item with empty channels will become invisible after upgrade
