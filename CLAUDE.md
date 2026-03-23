# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Sylius plugin providing navigation functionality independent of the default taxonomy system.
Allows creating flexible navigation structures with custom menu items, built manually or generated from existing taxon structures.

## Code Standards

- Follow SOLID principles and clean code practices
- Favor composition over inheritance
- Keep methods and classes focused on a single responsibility

### Testing Requirements
- Write unit tests for new functionality when it makes sense
- BDD-style test method names (e.g., `it_should_do_something_when_condition_is_met`)
- **MUST use Prophecy for mocking** — use `ProphecyTrait` and `$this->prophesize()`, NOT `$this->createMock()`
- **Form testing** — extend `Symfony\Component\Form\Test\TypeTestCase`, use `$this->factory->create()`
- Test both happy path and edge cases

## Development Commands

- `composer analyse` — PHPStan static analysis (level 8)
- `composer check-style` — Check code style (ECS)
- `composer fix-style` — Auto-fix code style
- `composer phpunit` — Run PHPUnit tests
- `vendor/bin/infection` — Mutation testing
- `vendor/bin/rector process` — Automated refactoring
- PHPStan baseline: `composer analyse -- --generate-baseline`

### Test Application
- Located in `tests/Application/`
- Build assets: `cd tests/Application && yarn install && yarn build`
- Sylius backend credentials: `sylius` / `sylius`

## Bash Tools

- **Files** → `fd` | **Text** → `rg` | **Code structure** → `ast-grep`
- **Interactive selection** → `fzf` | **JSON** → `jq` | **YAML/XML** → `yq`

## Architecture

### Core Models
- **Navigation** — menu with code, description, root item, and build state (`idle`/`building`/`completed`/`failed`)
- **Item** — base navigation item with label, hierarchical structure (closure table pattern)
- **TaxonItem** — item linked to a Sylius taxon
- **LinkItem** — item with a custom URL link
- **Closure** — closure table entries for hierarchical relationships

### Directory Structure
- `src/ArgumentResolver/` — Controller argument value resolvers
- `src/Attribute/` — PHP attributes (e.g., `#[ItemType]`)
- `src/Builder/` — Navigation builder service (build-from-taxon logic)
- `src/Controller/` — `BuildController` (tree AJAX CRUD), `BuildFromTaxonController` (async taxon import)
- `src/DependencyInjection/` — Bundle configuration, compiler passes
- `src/Event/` — Custom events
- `src/EventListener/` — Doctrine listeners (cache invalidation, discriminator map)
- `src/EventSubscriber/` — Admin menu subscriber
- `src/Factory/` — Entity factories (`ItemFactory`, `TaxonItemFactory`, `LinkItemFactory`, `ClosureFactory`)
- `src/Form/` — Symfony form types
- `src/Graph/` — Graph building for navigation structure
- `src/Manager/` — `ClosureManager` for hierarchical operations
- `src/Menu/` — Admin menu builders
- `src/Message/` — Symfony Messenger commands and handlers (async build-from-taxon)
- `src/Model/` — Entities and interfaces
- `src/Registry/` — Item type registry (tracks available item types)
- `src/Renderer/` — Navigation and item rendering (`CachedNavigationRenderer`, `CompositeItemRenderer`)
- `src/Repository/` — Doctrine repositories
- `src/Resources/` — Config (services, routes, doctrine, validation), views, translations
- `src/Twig/` — Extensions, runtime, `Attributes` helper class

### Key Patterns
- **Closure table** for hierarchical item relationships
- **Composite renderer** pattern for different item types
- **Symfony Messenger** for async build-from-taxon (prevents timeouts with large taxonomies)
- **Item type registry** with PHP attributes for extensible item types
- Admin CRUD auto-generated via Sylius Resource Bundle + Grid Bundle
- Twig function `ssn_item()` renders individual items

### Plugin Integration
- Extends `AbstractResourceBundle` for Sylius resource management
- Uses `SyliusPluginTrait` for plugin functionality
- Custom compiler passes: `RegisterItemTypesPass`, `ResolveTargetEntitiesPass`
