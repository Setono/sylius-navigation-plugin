# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Sylius plugin that provides navigation functionality independent of the default taxonomy system.
The plugin allows creating flexible navigation structures with custom menu items that can be built manually or generated from existing taxon structures.

## Code Standards

Follow clean code principles and SOLID design patterns when working with this codebase:
- Write clean, readable, and maintainable code
- Apply SOLID principles (Single Responsibility, Open/Closed, Liskov Substitution, Interface Segregation, Dependency Inversion)
- Use meaningful variable and method names
- Keep methods and classes focused on a single responsibility
- Favor composition over inheritance
- Write code that is easy to test and extend

### Testing Requirements
- Write unit tests for all new functionality (if it makes sense)
- Follow the BDD-style naming convention for test methods (e.g., `it_should_do_something_when_condition_is_met`)
- Use the ProphecyTrait for mocking when needed
- Ensure tests are isolated and don't depend on external state
- Test both happy path and edge cases

## Development Commands

Based on the `composer.json` scripts section:

### Code Quality & Testing
- `composer analyse` - Run PHPStan static analysis (level 8)
- `composer check-style` - Check code style with ECS (Easy Coding Standard)
- `composer fix-style` - Fix code style issues automatically with ECS
- `composer phpunit` - Run PHPUnit tests

### Additional Quality Tools
- `vendor/bin/infection` - Run mutation testing (configured in infection.json.dist)
- `vendor/bin/rector process` - Run automated refactoring

### Static Analysis

#### PHPStan Configuration
PHPStan is configured in `phpstan.neon` with:
- **Analysis Level**: 8 (strictest)
- **Extensions**: Auto-loaded via `phpstan/extension-installer`
  - `phpstan/phpstan-symfony` - Symfony framework integration
  - `phpstan/phpstan-doctrine` - Doctrine ORM integration
  - `phpstan/phpstan-phpunit` - PHPUnit test integration
  - `jangregor/phpstan-prophecy` - Prophecy mocking integration
- **Symfony Integration**: Uses console application loader (`tests/console_application.php`)
- **Doctrine Integration**: Uses object manager loader (`tests/object_manager.php`)
- **Exclusions**: Test application directory and Configuration.php
- **Baseline**: Generate with `composer analyse -- --generate-baseline` to track improvements

### Test Application
The plugin includes a test Symfony application in `tests/Application/` for development and testing:
- Navigate to `tests/Application/` directory
- Run `yarn install && yarn build` to build assets
- Use standard Symfony commands for the test app

## Bash Tools Recommendations

Use the right tool for the right job when executing bash commands:

- **Finding FILES?** → Use `fd` (fast file finder)
- **Finding TEXT/strings?** → Use `rg` (ripgrep for text search)
- **Finding CODE STRUCTURE?** → Use `ast-grep` (syntax-aware code search)
- **SELECTING from multiple results?** → Pipe to `fzf` (interactive fuzzy finder)
- **Interacting with JSON?** → Use `jq` (JSON processor)
- **Interacting with YAML or XML?** → Use `yq` (YAML/XML processor)

Examples:
- `fd "*.php" | fzf` - Find PHP files and interactively select one
- `rg "function.*validate" | fzf` - Search for validation functions and select
- `ast-grep --lang php -p 'class $name extends $parent'` - Find class inheritance patterns

## Architecture Overview

### Core Models
- **Navigation**: Main entity representing a navigation menu with code, description, and root item
- **Item**: Base navigation item with label and hierarchical structure  
- **TaxonItem**: Specialized item linked to Sylius taxon entities
- **Closure**: Manages hierarchical relationships between items using closure table pattern

### Key Components

#### Graph System
- **GraphBuilder**: Converts navigation structure into a graph representation
- **Node**: Represents nodes in the navigation graph

#### Rendering System  
- **NavigationRenderer**: Main service for rendering navigation HTML
- **CompositeItemRenderer**: Composite pattern for rendering different item types
- **ItemRendererInterface**: Contract for individual item renderers

#### Factory System
- **ItemFactory**: Creates basic navigation items
- **TaxonItemFactory**: Creates taxon-linked navigation items  
- **ClosureFactory**: Creates closure table entries

#### Management
- **ClosureManager**: Handles hierarchical operations (create, move, remove items from tree)

### Key Features
- **Build from Taxon**: Controller and form to automatically generate navigation from existing Sylius taxon tree
- **Channel & Locale Support**: Navigation items support multiple channels and locales
- **Twig Integration**: Runtime and extensions for rendering in templates
- **Admin Integration**: Sylius admin panel integration with CRUD operations

### Directory Structure
- `src/Model/` - Core entities and interfaces
- `src/Factory/` - Entity factories
- `src/Repository/` - Doctrine repositories  
- `src/Renderer/` - Navigation rendering logic
- `src/Graph/` - Graph building for navigation structure
- `src/Manager/` - Business logic managers
- `src/Controller/` - Symfony controllers
- `src/Form/` - Symfony form types
- `src/Resources/config/` - Symfony services, routes, validation
- `src/Resources/views/` - Twig templates
- `src/Twig/` - Twig extensions and runtime

### Plugin Integration
- Extends `AbstractResourceBundle` for Sylius resource management
- Uses `SyliusPluginTrait` for plugin functionality  
- Registers custom compiler passes for dependency injection
- Implements Doctrine ORM entity resolution

### CRUD Pages Generation
The admin CRUD pages are automatically generated using the Sylius Resource Bundle in combination with the Sylius Grid Bundle:

#### Resource Configuration
- **Resource Definition**: Resources are configured in `src/DependencyInjection/Configuration.php`
- **Model Classes**: Navigation, Item, ItemTranslation, TaxonItem, and Closure entities
- **Controllers**: Uses Sylius `ResourceController` for standard CRUD operations
- **Forms**: Custom `NavigationType` for navigation, `DefaultResourceType` for other entities
- **Repositories**: Custom repositories where needed, otherwise uses standard Doctrine repositories

#### Grid Configuration
- **Grid Definition**: Admin grid is configured in `\Setono\SyliusNavigationPlugin\DependencyInjection\SetonoSyliusNavigationExtension::prepend()` method
- **Configuration Method**: Uses `$container->prependExtensionConfig('sylius_grid', [...])` to add grid configuration
- **Grid Name**: `setono_sylius_navigation_admin_navigation`
- **Fields**: Shows `code` and `description` columns
- **Actions**: Standard create, update, delete operations with bulk delete support
- **Limits**: Configurable pagination (100, 250, 500, 1000 items per page)

#### Route Configuration
- **Admin Routes**: Defined in `src/Resources/config/routes/admin.yaml`
- **Route Type**: Uses `sylius.resource` type for automatic CRUD route generation
- **Base Path**: `/admin/navigations/` with standard RESTful endpoints
- **Templates**: Uses `@SyliusAdmin\Crud` templates with custom overrides
- **Custom Routes**: Additional route for "build from taxon" functionality

#### Admin Integration
- **Menu Integration**: Navigation menu item added via `AddMenuSubscriber`
- **Permissions**: Permission-based access control enabled
- **Templates**: Custom form and toolbar templates in plugin's views directory

### Translations
The plugin provides multilingual support through translation files in `src/Resources/translations/`:

- **Translation Files**: Available in 10 languages (en, da, de, es, fr, it, nl, no, pl, sv)
- **Translation Domains**:
  - `messages.*` - General UI translations (form labels, navigation terms)
  - `flashes.*` - Flash message translations (success/error messages)

Key translation keys:
- `setono_sylius_navigation.ui.*` - UI labels and navigation terms
- `setono_sylius_navigation.form.*` - Form field labels
- `setono_sylius_navigation.navigation_built` - Success message for building navigation
- `setono_sylius_navigation.navigation_not_found` - Error message for missing navigation

### Templates
Navigation rendering templates in `src/Resources/views/navigation/`:

#### Core Navigation Templates
- **`navigation.html.twig`** - Main navigation rendering template with recursive macro for hierarchical structure
- **`_form.html.twig`** - Form components for navigation CRUD operations
- **`_toolbar.html.twig`** - Admin toolbar for navigation management
- **`build_from_taxon.html.twig`** - Admin form for building navigation from taxon tree

#### Item Rendering Templates (`item/` directory)
- **`default.html.twig`** - Default item renderer (renders as `<span>` with label)
- **`taxon_item.html.twig`** - Specialized renderer for taxon items (renders as `<a>` link to product listing)

#### Template Features
- Uses Twig macros for recursive navigation rendering
- Supports CSS classes with depth levels (`level-{depth}`)
- Integrates with Sylius admin theme (`@SyliusAdmin/layout.html.twig`)
- Uses custom Twig function `ssn_item()` for item rendering
- Supports item attributes through `ItemAttributes` helper class

## Navigation Builder Forms Implementation

### AJAX Form Types for Tree Builder Interface
The navigation builder interface uses specialized form types for AJAX-based CRUD operations:

#### Form Types (`src/Form/Type/`)
- **`BuilderTextItemType`** - Form for creating/editing simple navigation items via AJAX
- **`BuilderTaxonItemType`** - Form for creating/editing taxon-linked navigation items via AJAX

#### Key Implementation Details
- **Extends AbstractType**: Unlike other forms, these extend `AbstractType` instead of `AbstractResourceType` for simplified AJAX handling
- **CSRF Protection Disabled**: Both forms have `'csrf_protection' => false` in `configureOptions()` for AJAX compatibility
- **Unmapped Fields**: Contains several unmapped fields for JavaScript integration:
  - `label` - Item name (unmapped, handled manually in controller)
  - `parent_id` - Parent item ID for tree structure
  - `item_id` - Current item ID for updates
  - `taxon_id` - Taxon reference for TaxonItem (TaxonItemType only)
  - `type` - Item type identifier for form selection

#### Controller Integration (`src/Controller/BuildController.php`)
- **Form Handling**: Uses `$form->handleRequest($request)` for proper Symfony form processing
- **Server-side HTML Rendering**: Returns rendered HTML via Twig instead of JSON for tree updates
- **Manual Field Processing**: Handles unmapped fields manually after form validation:
  ```php
  // Handle label (unmapped field)
  if ($request->request->get('label')) {
      $item->setLabel($request->request->get('label'));
  }
  
  // Handle taxon_id for TaxonItem (since it's unmapped)
  if ($item instanceof TaxonItemInterface && $request->request->get('taxon_id')) {
      $taxon = $this->taxonRepository->find($request->request->get('taxon_id'));
      if ($taxon instanceof TaxonInterface) {
          $item->setTaxon($taxon);
      }
  }
  ```

#### Frontend Integration
- **FormData Submission**: JavaScript uses `FormData` instead of JSON for form submissions
- **Modal Interface**: Add/edit operations use modal dialogs with real-time validation
- **Tree Updates**: Server returns rendered HTML that replaces the entire tree structure

#### Service Configuration (`src/Resources/config/services/form.xml`)
Both form types are registered as Symfony services with the `form.type` tag for automatic discovery.

### Testing the Builder Interface
- **Test URL**: `https://127.0.0.1:8000/admin/navigation/navigations/2/build`
- **Functionality**: All CRUD operations (Create, Read, Update, Delete) work via AJAX
- **Validation**: Proper Symfony form validation with error handling
- **User Experience**: Modals, confirmation dialogs, and success feedback messages

## Testing Notes
- Tests are in `tests/` directory
- Test application in `tests/Application/` provides full Sylius environment
- Use `composer phpunit` to run tests
- Psalm configuration excludes test application from analysis
- **Manual Testing**: Navigation builder can be tested at `/admin/navigation/navigations/{id}/build`
