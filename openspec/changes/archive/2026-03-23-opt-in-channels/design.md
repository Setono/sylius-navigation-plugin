## Context

The plugin currently treats channel associations with opt-out semantics: an empty channel set on a navigation or item means "visible on all channels." This is implemented via `IS EMPTY OR :channel MEMBER OF` in the repository query and `!isEmpty() && !hasChannel()` guards in `GraphBuilder` and `BuildController`.

The channel association exists on two levels:
1. **Navigation level** — determines if a navigation is found for rendering on a given channel
2. **Item level** — determines if individual items within a navigation are visible on a given channel

Both levels currently share the same opt-out pattern.

## Goals / Non-Goals

**Goals:**
- Switch to opt-in channel semantics: entities must be explicitly assigned to channels to be visible
- Make channels a required field in admin forms to prevent misconfiguration
- Simplify the visibility checking logic (remove empty-set special cases)
- Make `NavigationRepository::findOneEnabledByCode` require a channel argument (remove the `null` branch)

**Non-Goals:**
- Adding a data migration for existing installations (this is a pre-release plugin)
- Changing the channel model itself or introducing channel inheritance between navigation and items
- Adding validation constraints beyond form-level `required`

## Decisions

### 1. Strict opt-in at both navigation and item level

Both navigations and items require explicit channel assignment. Empty channels = invisible.

**Alternatives considered:**
- Items inherit navigation channels (less explicit, creates implicit coupling)
- Only change navigation level, keep items opt-out (inconsistent)

**Rationale:** Consistency. Both entities have the same `ChannelsAwareInterface` contract and should behave the same way.

### 2. Make `$channel` parameter required on `findOneEnabledByCode`

Remove the `ChannelInterface $channel = null` optional parameter and make it `ChannelInterface $channel`.

**Alternatives considered:**
- Keep nullable and return `null` when no channel is provided (preserves signature but adds dead logic)

**Rationale:** The `null` branch queries for navigations with empty channels, which under opt-in semantics means "invisible." Returning nothing for a null channel is the only correct behavior, so requiring the parameter makes the contract explicit.

### 3. Navigation form requires channels, item form does not

Navigation form has `required: true` on channels. Item form keeps `required: false`.

**Rationale:** Navigations are the top-level entry point — a navigation without channels is never visible, so requiring channels prevents misconfiguration. Items, however, may be drafted without channels while building a navigation tree "off the radar," then assigned to channels once the tree is ready to go live.

## Risks / Trade-offs

- **[Breaking change]** Existing navigations/items with empty channels become invisible. → Acceptable for pre-release plugin. Document in upgrade notes.
- **[Interface change]** `NavigationRepositoryInterface::findOneEnabledByCode` signature changes. → Any code calling with `null` will get a type error. Clean break, easy to find.
- **[UX friction]** Every item now needs channels assigned manually in the tree builder. → This is the intended behavior per the issue.
