/**
 * Navigation Builder with jsTree
 * Provides interactive tree building functionality for Sylius Navigation Plugin
 */

class NavigationBuilder {
    constructor() {
        this.config = window.NavigationBuilderConfig || {};
        this.currentEditItemId = null;
        this.currentParentId = null;
        this.itemTypes = null;
        this.tree = null; // jsTree instance

        this.init();
    }

    init() {
        // Wait for jQuery and jsTree to be available
        if (typeof jQuery === 'undefined' || typeof jQuery.jstree === 'undefined') {
            console.error('jQuery or jsTree not loaded');
            return;
        }

        this.initializeUI();
        this.initializeTree();
        this.loadItemTypes();
    }

    initializeUI() {
        // Initialize Semantic UI components
        jQuery('.ui.dropdown').dropdown();
        jQuery('.ui.checkbox').checkbox();

        // Initialize modals
        jQuery('#add-item-modal').modal({
            onHidden: () => {
                jQuery('.ui.button.dropdown.initialized').each(function() {
                    jQuery(this).removeClass('initialized');
                });
            }
        });

        jQuery('#edit-item-modal').modal();
        jQuery('#delete-item-modal').modal();

        // Initialize search
        this.initializeSearch();
    }

    initializeSearch() {
        const self = this;
        let searchTimeout = null;

        jQuery('#tree-search').on('keyup', function() {
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }

            searchTimeout = setTimeout(function() {
                const searchString = jQuery('#tree-search').val();
                if (self.tree) {
                    self.tree.search(searchString);
                }
            }, 250); // Debounce search by 250ms
        });
    }

    initializeTree() {
        const self = this;

        jQuery('#navigation-tree').jstree({
            'core': {
                'data': {
                    'url': this.config.routes.getTree,
                    'dataType': 'json',
                    'data': function(node) {
                        return { 'id': node.id };
                    }
                },
                'check_callback': true, // Allow modifications
                'themes': {
                    'name': 'default',
                    'responsive': true,
                    'dots': true,
                    'icons': true
                }
            },
            'plugins': ['dnd', 'contextmenu', 'types', 'state', 'search'],
            'state': {
                'key': 'navigation-tree-' + this.config.navigationId, // Unique key per navigation
                'preserve_loaded': false // Don't preserve loaded nodes, let lazy loading handle it
            },
            'search': {
                'show_only_matches': true,
                'show_only_matches_children': true,
                'case_sensitive': false,
                'ajax': {
                    'url': this.config.routes.searchItems,
                    'data': function(searchString) {
                        return { 'q': searchString };
                    },
                    'dataType': 'json'
                }
            },
            'dnd': {
                'is_draggable': function() {
                    return true;
                },
                'copy': false
            },
            'contextmenu': {
                'items': function(node) {
                    return self.getContextMenuItems(node);
                }
            },
            'types': {
                'default': {
                    'icon': 'file text icon'
                },
                'taxon': {
                    'icon': 'tag icon'
                }
            }
        })
        .on('ready.jstree', function() {
            self.tree = jQuery.jstree.reference('#navigation-tree');
            self.hideLoading();
        })
        .on('load_node.jstree', function() {
            self.hideLoading();
        })
        .on('move_node.jstree', function(e, data) {
            self.handleMove(data);
        })
        .on('select_node.jstree', function(e, data) {
            // Can be used to show item details
        });
    }

    getContextMenuItems(node) {
        const self = this;
        const items = {};

        // Add child item submenu
        items.add = {
            'label': 'Add Child',
            'icon': 'plus icon',
            'submenu': {}
        };

        // Load item types dynamically
        if (this.itemTypes) {
            Object.entries(this.itemTypes).forEach(([type, label]) => {
                items.add.submenu[type] = {
                    'label': label,
                    'icon': type === 'taxon' ? 'tag icon' : 'file text icon',
                    'action': function() {
                        self.showAddItemModal(type, node.id);
                    }
                };
            });
        }

        // Edit item
        items.edit = {
            'label': 'Edit',
            'icon': 'edit icon',
            'action': function() {
                self.showEditItemModal(node.id);
            }
        };

        // Delete item
        items.delete = {
            'label': 'Delete',
            'icon': 'trash icon',
            'action': function() {
                self.showDeleteItemModal(node.id);
            }
        };

        return items;
    }

    async loadItemTypes() {
        try {
            const response = await fetch(this.config.routes.getItemTypes);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const result = await response.json();
            // Extract itemTypes from wrapped response
            this.itemTypes = result.itemTypes || result;
            this.populateMainAddDropdown();
        } catch (error) {
            console.error('Failed to load item types:', error);
        }
    }

    populateMainAddDropdown() {
        // Populate both the main "Add item" dropdown and the empty state dropdown
        const dropdowns = ['main-add-dropdown', 'empty-add-dropdown'];

        dropdowns.forEach(dropdownId => {
            const dropdown = document.getElementById(dropdownId);
            if (!dropdown) return;

            const menu = dropdown.querySelector('.menu');
            if (!menu) return;

            menu.innerHTML = '';

            Object.entries(this.itemTypes).forEach(([type, label]) => {
                const item = document.createElement('div');
                item.className = 'item';
                item.setAttribute('data-value', type);

                const icon = type === 'taxon' ? '<i class="tag icon"></i>' : '<i class="file text icon"></i>';
                item.innerHTML = `${icon} ${this.escapeHtml(label)}`;

                menu.appendChild(item);
            });

            // Initialize dropdown
            const self = this;
            jQuery(dropdown).dropdown({
                action: 'hide',
                onChange: (value) => {
                    if (value) {
                        self.showAddItemModal(value, null);
                        jQuery(dropdown).dropdown('clear');
                    }
                }
            });
        });
    }

    async showAddItemModal(type, parentId = null) {
        this.currentParentId = parentId;
        this.currentSelectedType = type;

        // Load form for selected type
        await this.loadFormFields(type, 'add');

        // Update modal title based on type
        const titleElement = document.getElementById('add-item-modal-title');
        const typeLabel = this.itemTypes[type] || type;
        titleElement.textContent = `Add ${typeLabel}`;

        // Show modal
        jQuery('#add-item-modal').modal('show');
    }

    async showEditItemModal(itemId) {
        this.currentEditItemId = itemId;

        // Get node data from jsTree
        const node = this.tree.get_node(itemId);
        if (!node) {
            console.error('Node not found:', itemId);
            return;
        }

        // Get the actual item type from data attribute
        const type = node.data?.item_type || node.original?.data?.item_type || 'text';

        // Load form for the item type
        await this.loadFormFields(type, 'edit', itemId);

        // Show modal
        jQuery('#edit-item-modal').modal('show');
    }

    showDeleteItemModal(itemId) {
        this.currentEditItemId = itemId;
        document.getElementById('delete-item-id').value = itemId;

        jQuery('#delete-item-modal').modal('show');
    }

    async loadFormFields(type, mode = 'add', itemId = null) {
        try {
            const url = this.config.routes.getForm.replace('__TYPE__', type);
            const params = new URLSearchParams();
            if (mode === 'edit' && itemId) {
                params.append('itemId', itemId);
            }

            const response = await fetch(`${url}?${params}`);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const result = await response.json();
            const containerId = mode === 'add' ? 'add-item-form-fields' : 'edit-item-form-fields';
            document.getElementById(containerId).innerHTML = result.html;

            // Reinitialize Semantic UI components
            jQuery(`#${containerId} .ui.dropdown`).dropdown();
            jQuery(`#${containerId} .ui.checkbox`).checkbox();

        } catch (error) {
            console.error('Failed to load form fields:', error);
            this.showError('Failed to load form');
        }
    }

    async addItem() {
        const container = document.getElementById('add-item-form-fields');
        const form = container.querySelector('form');
        if (!form) {
            this.showError('Form not found');
            return;
        }
        const formData = new FormData(form);

        // Add additional fields
        if (this.currentParentId) {
            formData.append('parent_id', this.currentParentId);
        }
        formData.append('type', this.currentSelectedType);

        try {
            const response = await fetch(this.config.routes.addItem, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const result = await response.json();

            if (result.success) {
                // Close modal
                jQuery('#add-item-modal').modal('hide');

                // Refresh the tree
                this.tree.refresh();

                this.showSuccess('Item added successfully');
            } else {
                this.showError(result.message || 'Failed to add item');
            }

        } catch (error) {
            console.error('Failed to add item:', error);
            this.showError('Failed to add item');
        }
    }

    async updateItem() {
        const itemId = this.currentEditItemId;
        const container = document.getElementById('edit-item-form-fields');
        const form = container.querySelector('form');
        if (!form) {
            this.showError('Form not found');
            return;
        }
        const formData = new FormData(form);

        try {
            const url = this.config.routes.updateItem.replace('__ITEM_ID__', itemId);
            const response = await fetch(url, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const result = await response.json();

            if (result.success) {
                // Close modal
                jQuery('#edit-item-modal').modal('hide');

                // Refresh the tree
                this.tree.refresh();

                this.showSuccess('Item updated successfully');
            } else {
                this.showError(result.message || 'Failed to update item');
            }

        } catch (error) {
            console.error('Failed to update item:', error);
            this.showError('Failed to update item');
        }
    }

    async deleteItem() {
        const itemId = document.getElementById('delete-item-id').value;

        try {
            const url = this.config.routes.deleteItem.replace('__ITEM_ID__', itemId);
            const response = await fetch(url, {
                method: 'DELETE'
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const result = await response.json();

            if (result.success) {
                // Close modal
                jQuery('#delete-item-modal').modal('hide');

                // Refresh the tree
                this.tree.refresh();

                this.showSuccess('Item deleted successfully');
            } else {
                this.showError(result.message || 'Failed to delete item');
            }

        } catch (error) {
            console.error('Failed to delete item:', error);
            this.showError('Failed to delete item');
        }
    }

    async handleMove(data) {
        const itemId = data.node.id;
        const parentId = data.parent === '#' ? null : data.parent;
        const position = data.position;

        try {
            const response = await fetch(this.config.routes.reorderItem, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    item_id: itemId,
                    new_parent_id: parentId,
                    position: position
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const result = await response.json();

            if (result.success) {
                this.showSuccess('Item moved successfully');
            } else {
                // Revert the move
                this.tree.refresh();
                this.showError(result.message || 'Failed to move item');
            }

        } catch (error) {
            console.error('Failed to move item:', error);
            // Revert the move
            this.tree.refresh();
            this.showError('Failed to move item');
        }
    }

    async toggleItemStatus(itemId, enabled) {
        // This would need a server endpoint to toggle the enabled status
        // For now, we can refresh after updating
        this.showInfo('Toggle functionality to be implemented');
    }

    showLoading() {
        document.getElementById('tree-loading').style.display = 'block';
        document.getElementById('navigation-tree').style.display = 'none';
        document.getElementById('empty-tree').style.display = 'none';
    }

    hideLoading() {
        document.getElementById('tree-loading').style.display = 'none';
        document.getElementById('navigation-tree').style.display = 'block';
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showError(message) {
        this.showNotification(message, 'error');
    }

    showInfo(message) {
        this.showNotification(message, 'info');
    }

    showNotification(message, type = 'info') {
        // Use Semantic UI toast or create a simple notification
        console.log(`[${type.toUpperCase()}] ${message}`);

        // Simple alert for now - can be replaced with better UI
        if (type === 'error') {
            alert(message);
        }
    }

    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    // Static methods for onclick handlers
    static initDropdown(dropdownElement, parentId = null) {
        NavigationBuilderInstance.initDropdown(dropdownElement, parentId);
    }

    static showEditItemModal(itemId) {
        NavigationBuilderInstance.showEditItemModal(itemId);
    }

    static showDeleteItemModal(itemId) {
        NavigationBuilderInstance.showDeleteItemModal(itemId);
    }

    static addItem() {
        NavigationBuilderInstance.addItem();
    }

    static updateItem() {
        NavigationBuilderInstance.updateItem();
    }

    static deleteItem() {
        NavigationBuilderInstance.deleteItem();
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.NavigationBuilderInstance = new NavigationBuilder();
        window.NavigationBuilder = NavigationBuilder;
    });
} else {
    window.NavigationBuilderInstance = new NavigationBuilder();
    window.NavigationBuilder = NavigationBuilder;
}

console.log('NavigationBuilder initialized as jsTree implementation');
