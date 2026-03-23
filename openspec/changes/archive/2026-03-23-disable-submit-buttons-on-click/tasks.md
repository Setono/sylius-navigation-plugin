## 1. Convert modal buttons to semantic elements

- [x] 1.1 In `build.html.twig`, convert all six modal `<div>` buttons (3 action + 3 cancel) to `<button type="button">` elements, preserving existing Semantic UI classes and onclick handlers

## 2. Build from taxon submit protection

- [x] 2.1 In `build_from_taxon.html.twig`, add an inline `<script>` block that listens for the form `submit` event, disables the submit button, and adds the Semantic UI `.loading` class

## 3. Tree builder submit protection

- [x] 3.1 In `navigation-builder.js`, add disable + loading logic to `addItem()`: query the Add Item modal's action button, set `disabled` and add `.loading` before fetch, remove both in `finally`
- [x] 3.2 In `navigation-builder.js`, add disable + loading logic to `updateItem()`: same pattern as addItem
- [x] 3.3 In `navigation-builder.js`, add disable + loading logic to `deleteItem()`: query the Delete Item modal's action button, same pattern

## 4. Verification

- [x] 4.1 Manually verify build-from-taxon button disables and shows spinner on click
- [x] 4.2 Manually verify tree builder Add/Edit/Delete modal buttons disable and show spinner during requests
- [x] 4.3 Run `composer analyse` and `composer check-style` to ensure no regressions
