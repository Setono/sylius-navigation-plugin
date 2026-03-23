### Requirement: Navigation visibility requires explicit channel assignment
A navigation SHALL only be visible on channels it is explicitly assigned to. A navigation with no channels assigned SHALL NOT be visible on any channel.

#### Scenario: Navigation with matching channel
- **WHEN** a navigation has channel A assigned and is queried for channel A
- **THEN** the navigation is returned

#### Scenario: Navigation with non-matching channel
- **WHEN** a navigation has channel A assigned and is queried for channel B
- **THEN** the navigation is not returned

#### Scenario: Navigation with no channels assigned
- **WHEN** a navigation has no channels assigned and is queried for any channel
- **THEN** the navigation is not returned

### Requirement: Item visibility requires explicit channel assignment
An item SHALL only be visible on channels it is explicitly assigned to. An item with no channels assigned SHALL NOT be visible on any channel.

#### Scenario: Item with matching channel
- **WHEN** an item has channel A assigned and the graph is built for channel A
- **THEN** the item is included in the graph

#### Scenario: Item with non-matching channel
- **WHEN** an item has channel A assigned and the graph is built for channel B
- **THEN** the item is excluded from the graph

#### Scenario: Item with no channels assigned
- **WHEN** an item has no channels assigned and the graph is built for any channel
- **THEN** the item is excluded from the graph

### Requirement: Repository requires channel context
`findOneEnabledByCode` SHALL require a `ChannelInterface` parameter. The method SHALL NOT accept a null channel.

#### Scenario: Query with channel
- **WHEN** `findOneEnabledByCode` is called with a code and a channel
- **THEN** it returns the navigation only if it is enabled and assigned to that channel

### Requirement: Navigation form requires channel selection
The navigation admin form SHALL require at least one channel to be selected.

#### Scenario: Navigation form without channels
- **WHEN** an admin submits a navigation form with no channels selected
- **THEN** the form is not valid

### Requirement: Item form allows empty channel selection
The item admin form SHALL NOT require channels to be selected, allowing items to be drafted without channel assignment.

#### Scenario: Item form without channels
- **WHEN** an admin submits an item form with no channels selected
- **THEN** the form is valid

### Requirement: Build controller uses strict channel check
The tree builder admin UI SHALL use strict opt-in visibility when filtering items by channel.

#### Scenario: Item visible in builder for matching channel
- **WHEN** the build controller checks item visibility for a channel the item is assigned to
- **THEN** the item is considered visible

#### Scenario: Item invisible in builder for non-matching channel
- **WHEN** the build controller checks item visibility for a channel the item is NOT assigned to
- **THEN** the item is considered not visible

#### Scenario: Item with no channels in builder
- **WHEN** the build controller checks item visibility and the item has no channels
- **THEN** the item is considered not visible
