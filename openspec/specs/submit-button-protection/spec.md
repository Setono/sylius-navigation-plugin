## ADDED Requirements

### Requirement: Semantic button elements in tree builder modals
All action and cancel buttons in the tree builder modals (Add Item, Edit Item, Delete Item) SHALL use `<button type="button">` elements instead of `<div>` elements.

#### Scenario: Modal buttons are real button elements
- **WHEN** a tree builder modal is rendered
- **THEN** all action buttons (Create, Save, Yes) and cancel buttons (Cancel, No) SHALL be `<button type="button">` elements with the same Semantic UI CSS classes

### Requirement: Build from taxon button disables on submit
The "Build from taxon" submit button SHALL be disabled and show a loading indicator when the form is submitted, preventing duplicate submissions.

#### Scenario: User submits the build from taxon form
- **WHEN** the user clicks the "Build from taxon" submit button
- **THEN** the button SHALL become disabled and display a Semantic UI loading spinner
- **AND** the form SHALL submit normally

#### Scenario: Form re-renders on validation error
- **WHEN** the form submission fails server-side validation and the page re-renders
- **THEN** the button SHALL be enabled and not in a loading state (reset by page reload)

### Requirement: Tree builder Add Item button disables during request
The Add Item modal's action button SHALL be disabled and show a loading indicator while the AJAX request is in flight.

#### Scenario: User clicks Create in Add Item modal
- **WHEN** the user clicks the Create button in the Add Item modal
- **THEN** the button SHALL become disabled and display a loading spinner
- **AND** the button SHALL remain disabled until the server responds

#### Scenario: Add Item request completes successfully
- **WHEN** the Add Item AJAX request returns a success response
- **THEN** the modal SHALL close and the button state SHALL be reset

#### Scenario: Add Item request fails
- **WHEN** the Add Item AJAX request returns an error
- **THEN** the button SHALL be re-enabled and the loading spinner SHALL be removed

### Requirement: Tree builder Update Item button disables during request
The Edit Item modal's action button SHALL be disabled and show a loading indicator while the AJAX request is in flight.

#### Scenario: User clicks Save in Edit Item modal
- **WHEN** the user clicks the Save button in the Edit Item modal
- **THEN** the button SHALL become disabled and display a loading spinner
- **AND** the button SHALL remain disabled until the server responds

#### Scenario: Update Item request completes
- **WHEN** the Update Item AJAX request completes (success or failure)
- **THEN** the button SHALL be re-enabled and the loading spinner SHALL be removed

### Requirement: Tree builder Delete Item button disables during request
The Delete Item modal's action button SHALL be disabled and show a loading indicator while the AJAX request is in flight.

#### Scenario: User clicks Yes in Delete Item modal
- **WHEN** the user clicks the Yes button in the Delete Item modal
- **THEN** the button SHALL become disabled and display a loading spinner
- **AND** the button SHALL remain disabled until the server responds

#### Scenario: Delete Item request completes
- **WHEN** the Delete Item AJAX request completes (success or failure)
- **THEN** the button SHALL be re-enabled and the loading spinner SHALL be removed
