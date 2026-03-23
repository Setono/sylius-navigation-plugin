## Context

The plugin has two admin surfaces with submit actions:

1. **Build from taxon form** (`build_from_taxon.html.twig`) — a standard HTML form with `<button type="submit">` that dispatches a Symfony Messenger message. Double-clicking queues duplicate build jobs.
2. **Tree builder modals** (`build.html.twig` + `navigation-builder.js`) — three modals (Add Item, Edit Item, Delete Item) with `<div>` elements acting as buttons. Their `onclick` handlers call async `fetch()` methods. Double-clicking fires duplicate AJAX requests.

The modal buttons are `<div class="ui ... button">` instead of real `<button>` elements, which means they lack native `disabled` attribute support and are inaccessible to keyboard/screen reader users.

## Goals / Non-Goals

**Goals:**
- Prevent double-submission on all submit buttons
- Show visual loading feedback (Semantic UI `.loading` class) during submission
- Use semantic `<button>` elements for all action buttons

**Non-Goals:**
- Server-side idempotency (out of scope — the client-side guard is sufficient)
- Changing cancel button behavior (cancel buttons in modals can remain `<div>` since they have no async side effects, but will be converted to `<button>` for consistency)
- Adding loading states to non-submit actions (e.g., drag-and-drop reorder)

## Decisions

### 1. Convert modal `<div>` buttons to `<button type="button">`

All six modal buttons (3 action + 3 cancel) become `<button type="button">`. The `type="button"` prevents accidental form submission. Semantic UI CSS classes work identically on `<button>` elements.

**Alternative considered:** Keep `<div>` and use CSS `.disabled` class only. Rejected because it doesn't prevent programmatic clicks, adds no accessibility benefit, and the `<div>` pattern is a known anti-pattern.

### 2. Build from taxon: inline `<script>` with form submit listener

A small `<script>` block in `build_from_taxon.html.twig` listens for the `submit` event on the form, disables the button, and adds `.loading`. Since this is a full-page POST that redirects on success or re-renders on error, the button state resets naturally via page reload.

**Alternative considered:** Separate JS file. Rejected — ~5 lines of code doesn't warrant a new asset.

### 3. Tree builder: disable/loading logic inside `addItem()`, `updateItem()`, `deleteItem()`

Each method finds its action button via the modal's `.actions .primary` or `.actions .ok` selector, adds `disabled` attribute and `.loading` class before the fetch, and removes them in a `finally` block. This ensures re-enabling on both success and failure.

**Alternative considered:** Passing `this` via `onclick="NavigationBuilder.addItem(this)"`. Rejected in favor of querying the DOM — keeps the HTML cleaner and the button reference is unambiguous within each modal.

## Risks / Trade-offs

- [Risk] Semantic UI modal `.approve`/`.deny` callbacks may interact with `disabled` buttons → Mitigation: We use `.cancel` class for cancel buttons, not `.deny`, and action buttons don't use `.approve`. No conflict expected.
- [Risk] Button stays in loading state if JS error occurs before `finally` → Mitigation: The `finally` block handles this. If the error is truly catastrophic (page crash), a reload resets everything.
