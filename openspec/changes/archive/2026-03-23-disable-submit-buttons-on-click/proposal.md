## Why

Submit buttons across the admin UI can be clicked multiple times before the action completes. For the "Build from taxon" form, this dispatches duplicate Messenger messages that race to rebuild the navigation tree. For the tree builder modals (add/edit/delete), this fires duplicate AJAX requests. Both cases can corrupt navigation data.

## What Changes

- Convert all modal action `<div>` elements to real `<button type="button">` elements in the tree builder view for proper semantics and native `disabled` support
- Disable submit buttons on click and show a Semantic UI loading spinner across all submit surfaces:
  - **Build from taxon form** — listen to form `submit` event, disable the button, add `.loading` class
  - **Tree builder modals** (Add Item, Update Item, Delete Item) — disable button and add `.loading` class before the async operation, re-enable in `finally`

## Capabilities

### New Capabilities
- `submit-button-protection`: Prevent double-submission by disabling buttons on click and showing a loading indicator across all admin submit surfaces

### Modified Capabilities

## Impact

- `src/Resources/views/navigation/build_from_taxon.html.twig` — new inline `<script>` for form submit handling
- `src/Resources/views/navigation/build.html.twig` — modal `<div>` buttons become `<button type="button">` elements
- `src/Resources/public/js/navigation-builder.js` — `addItem()`, `updateItem()`, `deleteItem()` methods gain button disable/loading logic
