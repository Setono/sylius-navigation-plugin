# Sylius Navigation Plugin

[![Latest Version][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-github-actions]][link-github-actions]
[![Code Coverage][ico-code-coverage]][link-code-coverage]
[![Mutation testing][ico-infection]][link-infection]

Create flexible, independent navigation menus in your Sylius store without being tied to taxonomies or other entities.

## Features

- 🎯 **Independent Navigation**: Create navigation menus completely independent of taxonomies
- 🔗 **Multiple Item Types**: Support for text items, taxon-linked items, and custom link items
- 🌐 **Multi-channel & Multi-locale**: Full support for Sylius channels and locales
- 🏗️ **Hierarchical Structure**: Build nested navigation menus with unlimited depth
- 🎨 **Flexible Rendering**: Twig templates with customizable rendering per item type
- ⚡ **Smart Caching**: Built-in cache with automatic invalidation on content changes
- 🔧 **Easy Integration**: Visual tree builder in Sylius admin panel
- 📦 **Build from Taxon**: Quickly generate navigation structures from existing taxonomies

## Installation

### 1. Install the plugin

```bash
composer require setono/sylius-navigation-plugin
```

### 2. Enable the plugin

Add the plugin to your `config/bundles.php`:

```php
<?php
return [
    // ...
    Setono\SyliusNavigationPlugin\SetonoSyliusNavigationPlugin::class => ['all' => true],
];
```

### 3. Import configuration

Create or update `config/packages/setono_sylius_navigation.yaml`:

```yaml
imports:
    - { resource: "@SetonoSyliusNavigationPlugin/config/config.yaml" }
```

### 4. Import routing

Create `config/routes/setono_sylius_navigation.yaml`:

```yaml
setono_sylius_navigation:
    resource: "@SetonoSyliusNavigationPlugin/config/routes.yaml"
```

### 5. Update database schema

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

### 6. Install assets

```bash
php bin/console assets:install
```

## Usage

### Creating a Navigation

1. Log in to the Sylius admin panel (`/admin`)
2. Navigate to **Navigation** → **Navigations**
3. Click **Create**
4. Enter a unique code (e.g., `main-menu`) and description
5. Click **Create**

### Building Navigation Structure

After creating a navigation, you can build its structure using the visual tree builder:

1. Click **Build** on your navigation
2. Add items using the **Add item** button:
   - **Text Item**: Simple text label (can be styled/linked via templates)
   - **Taxon Item**: Links to a Sylius taxon/category
   - **Link Item**: Custom URL with full control over attributes
3. Drag and drop items to reorder or nest them
4. Edit items by right-clicking and selecting **Edit**
5. Delete items by right-clicking and selecting **Delete**

### Build from Taxon

Quickly create a navigation from an existing taxonomy:

1. Click **Build from Taxon** on your navigation
2. Select the root taxon to import
3. Click **Submit**
4. The entire taxon tree will be imported as navigation items

### Rendering Navigation in Templates

Render a navigation anywhere in your Twig templates:

```twig
{# Render by navigation code #}
{{ ssn_navigation('main-menu') }}

{# Render with custom attributes #}
{{ ssn_navigation('main-menu', {'class': 'navbar-nav'}) }}

{# Render specific channel/locale #}
{{ ssn_navigation('footer-menu', {}, channel, 'en_US') }}
```

### Available Twig Functions

```twig
{# Render complete navigation #}
{{ ssn_navigation(code, attributes, channel, locale) }}

{# Render single navigation item #}
{{ ssn_item(item, attributes) }}

{# Get item type name #}
{{ ssn_item_type(item) }}
```

## Item Types

### Text Item

A basic navigation item with just a label. Use this for:
- Simple text labels
- Items styled/linked via custom templates
- Placeholder items in the navigation hierarchy

### Taxon Item

Links to a Sylius taxon (category). Features:
- Automatically uses taxon name if no custom label is set
- Links to product listing page for the taxon
- Updates automatically when taxon changes

### Link Item

Custom link with full control. Features:
- Custom URL (internal or external)
- **Open in new tab**: Target attribute control
- **SEO attributes**:
  - `nofollow`: Tell search engines not to follow the link
  - `noopener`: Security for external links
  - `noreferrer`: Privacy for external links

Example link item in admin:
```
URL: https://example.com
☑ Open in new tab
☑ nofollow
☑ noopener
☑ noreferrer
```

## Configuration

### Cache Configuration

By default, caching is enabled in production mode:

```yaml
# config/packages/setono_sylius_navigation.yaml
setono_sylius_navigation:
    cache:
        enabled: true  # null = auto (enabled when kernel.debug is false)
        pool: cache.app  # Use custom cache pool
```

### Custom Templates

Override default templates by creating your own:

```
templates/
  bundles/
    SetonoSyliusNavigationPlugin/
      navigation/
        navigation.html.twig       # Main navigation wrapper
        item/
          default.html.twig        # Default item renderer
          taxon_item.html.twig     # Taxon item renderer
          link_item.html.twig      # Link item renderer
```

### Custom Item Types

Create your own navigation item types:

1. **Create the model**:
```php
use Setono\SyliusNavigationPlugin\Attribute\ItemType;
use Setono\SyliusNavigationPlugin\Model\Item;

#[ItemType(
    name: 'custom',
    formType: CustomItemType::class,
    template: '@App/navigation/form/_custom_item.html.twig',
    label: 'Custom Item'
)]
class CustomItem extends Item
{
    private ?string $customField = null;

    public function getCustomField(): ?string
    {
        return $this->customField;
    }

    public function setCustomField(?string $customField): void
    {
        $this->customField = $customField;
    }
}
```

2. **Create the form type**:
```php
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class CustomItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('customField', TextType::class, [
            'label' => 'Custom Field',
        ]);
    }
}
```

3. **Register in Sylius resources**:
```yaml
# config/packages/_sylius.yaml
sylius_resource:
    resources:
        setono_sylius_navigation.custom_item:
            classes:
                model: App\Entity\CustomItem
                factory: Sylius\Resource\Factory\TranslatableFactory
```

4. **Create a template** for rendering (optional):
```twig
{# templates/bundles/SetonoSyliusNavigationPlugin/navigation/item/custom_item.html.twig #}
<div class="custom-item">
    <span>{{ item.label }}</span>
    <small>{{ item.customField }}</small>
</div>
```

## Architecture

### Core Components

- **Navigation**: Top-level container with code and description
- **Item**: Base class for all navigation items (supports inheritance)
- **TaxonItem**: Links to Sylius taxons
- **LinkItem**: Custom URLs with SEO attributes
- **Closure**: Manages hierarchical relationships using closure table pattern

### Caching System

The plugin includes intelligent caching:

- **Automatic**: Caches rendered navigation per channel/locale
- **Tag-based**: Uses cache tags for efficient invalidation
- **Smart Invalidation**:
  - Invalidates when navigation or items change
  - Invalidates when linked taxons change
  - Batched invalidation for performance

### Repositories

- **NavigationRepository**: Find navigations by code
- **TaxonItemRepository**: Find items by taxon (for cache invalidation)
- **ClosureRepository**: Manage hierarchical queries

## Development

### Running Tests

```bash
# Unit and integration tests
composer phpunit

# Static analysis
composer analyse

# Code style check
composer check-style

# Fix code style
composer fix-style

# Mutation testing
vendor/bin/infection
```

### Test Application

The plugin includes a full test Sylius application:

```bash
cd tests/Application

# Install dependencies
composer install
yarn install && yarn build

# Set up database
php bin/console doctrine:database:create
php bin/console doctrine:schema:create

# Load fixtures (optional)
php bin/console sylius:fixtures:load

# Start server
symfony server:start

# Access admin panel
# URL: https://127.0.0.1:8000/admin
# Username: sylius
# Password: sylius
```

## Requirements

- PHP 8.1 or higher
- Symfony 6.4 or 7.0+
- Sylius 1.11+

## Contributing

Contributions are welcome! Please read the contribution guidelines before submitting a pull request.

## License

This plugin is under the MIT license. See the [LICENSE](LICENSE) file for details.

## Credits

Developed by [Setono](https://setono.com/).

[ico-version]: https://poser.pugx.org/setono/sylius-navigation-plugin/v/stable
[ico-license]: https://poser.pugx.org/setono/sylius-navigation-plugin/license
[ico-github-actions]: https://github.com/Setono/sylius-navigation-plugin/workflows/build/badge.svg
[ico-code-coverage]: https://codecov.io/gh/Setono/sylius-navigation-plugin/branch/master/graph/badge.svg
[ico-infection]: https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2FSetono%2Fsylius-navigation-plugin%2Fmaster

[link-packagist]: https://packagist.org/packages/setono/sylius-navigation-plugin
[link-github-actions]: https://github.com/Setono/sylius-navigation-plugin/actions
[link-code-coverage]: https://codecov.io/gh/Setono/sylius-navigation-plugin
[link-infection]: https://dashboard.stryker-mutator.io/reports/github.com/Setono/sylius-navigation-plugin/master
