# Manual Navigation Tree Builder - Requirements

This document outlines the requirements for implementing a manual navigation tree builder feature for the Sylius Navigation Plugin.

## Overview

Create a "build" action that allows users to manually construct navigation trees through an interactive web interface, complementing the existing "Build from taxon" functionality.

## User Requirements

### UI/UX Design
- **Page Type**: Separate page (similar to existing "Build from taxon" action)
- **Tree Interaction**: 
  - Add items on each node (root and children)
  - Remove items with confirmation
  - Reorder items using drag and drop functionality
  - All operations should use AJAX for real-time updates

### Item Types
- **Phase 1**: Support for existing item types:
  - Simple text items (current `Item` model)
  - Taxon-linked items (current `TaxonItem` model)
- **Future**: Extensible architecture for additional item types (custom URLs, external links, etc.)

### Item Management
- **Property Editing**: Modal dialog loaded via AJAX for editing item properties
- **Operations**: Add, remove, reorder, and edit items in real-time
- **Save Strategy**: Immediate save (no draft functionality required initially)
- **Localization**: Skip for initial implementation due to complexity

### User Experience
- **Preview**: The editing interface serves as a live preview of the navigation structure
- **No Undo/Redo**: Not required for initial implementation
- **Integration**: Standalone feature that complements (not replaces) the existing edit form

## Technical Specification

### Page Structure
- **Route**: `/admin/navigations/{id}/build`
- **Controller**: New action in existing controller or dedicated controller
- **Template**: New template with interactive tree interface
- **Navigation**: Add "Build manually" button alongside existing "Build from taxon" button

### Frontend Components

#### Tree Display
- Hierarchical list showing current navigation structure
- Visual indicators for item types (simple vs taxon items)
- Clear parent-child relationships

#### Interactive Controls
- **Add Item**: Button on each node with dropdown for item type selection
- **Remove Item**: Delete button with confirmation dialog
- **Drag & Drop**: Visual feedback during reordering operations
- **Edit Item**: Click to open modal for property editing

#### Modal System
- **Dynamic Loading**: Modal content loaded based on item type
- **Form Fields**:
  - Simple Items: Label, enabled status, channel restrictions
  - Taxon Items: Taxon selector, label override, enabled status, channel restrictions

### Backend API Endpoints

#### RESTful AJAX Endpoints
1. **GET** `/admin/navigations/{id}/build/items` - Load current tree structure as JSON
2. **POST** `/admin/navigations/{id}/build/items` - Add new item to navigation
3. **PUT** `/admin/navigations/{id}/build/items/{itemId}` - Update existing item properties
4. **DELETE** `/admin/navigations/{id}/build/items/{itemId}` - Remove item from navigation
5. **POST** `/admin/navigations/{id}/build/items/reorder` - Handle drag & drop reordering

#### Existing Admin Routes (for reference)
- **Items Index**: `/admin/navigation/items/` - Manage all navigation items
- **Items Create**: `/admin/navigation/items/new` - Create new navigation item
- **Items Edit**: `/admin/navigation/items/{id}/edit` - Edit navigation item
- **Items Show**: `/admin/navigation/items/{id}` - View navigation item
- **Items Delete**: `/admin/navigation/items/{id}` - Delete navigation item

#### Request/Response Format
- **JSON API**: All endpoints should accept and return JSON
- **Error Handling**: Consistent error response format
- **Validation**: Server-side validation for all operations

### Backend Integration

#### Existing Components Usage
- **ClosureManager**: Utilize for all tree operations (create, move, remove)
- **ItemFactory & TaxonItemFactory**: Use for creating new items
- **Closure Table Structure**: Maintain existing hierarchical data structure
- **Repositories**: Leverage existing repository methods where possible

#### Data Persistence
- **Immediate Save**: All changes saved immediately to database
- **Transaction Management**: Ensure data consistency during operations
- **Validation**: Apply existing validation rules for navigation items

### Technical Implementation Details

#### Frontend Technology Stack
- **JavaScript Library**: Choose between jsTree, Sortable.js, or custom implementation
- **AJAX Handling**: Vanilla JavaScript for API communication
- **UI Framework**: Integrate with existing Sylius admin theme

#### Security Considerations
- **CSRF Protection**: Implement CSRF tokens for all AJAX requests
- **Permission Checks**: Verify user permissions for each operation
- **Input Validation**: Both client-side and server-side validation
- **XSS Prevention**: Proper output escaping in templates

#### Error Handling
- **User Feedback**: Clear success/error messages for all operations
- **Graceful Degradation**: Handle network failures appropriately
- **Validation Messages**: Display meaningful error messages for form validation

## Implementation Phases

### Phase 1: Core Functionality
- Basic tree display and navigation
- Add/remove simple items and taxon items
- Basic drag & drop reordering
- Modal editing for item properties

### Phase 2: Enhanced Features
- Advanced drag & drop with visual feedback
- Bulk operations support
- Performance optimizations for large trees
- Enhanced validation and error handling

### Phase 3: Future Enhancements
- Support for additional item types
- Localization support for item labels
- Undo/redo functionality
- Advanced tree operations (copy, duplicate, bulk edit)

## Success Criteria

1. **Functionality**: Users can build complete navigation trees manually through the web interface
2. **Usability**: Intuitive drag & drop interface with immediate feedback
3. **Performance**: Responsive interface even with large navigation structures
4. **Integration**: Seamless integration with existing Sylius admin interface
5. **Reliability**: Robust error handling and data consistency
6. **Extensibility**: Architecture supports future item types and features

## Dependencies

- Existing Sylius Navigation Plugin architecture
- Modern JavaScript framework for frontend interactions (or just Vanilla JS)
- Existing Symfony/Sylius permissions and security systems
- Current closure table implementation for hierarchical data
