/**
 * Navigation Builder JavaScript
 * Provides interactive tree building functionality for Sylius Navigation Plugin
 */
class NavigationBuilder {
    constructor() {
        this.config = window.NavigationBuilderConfig || {};
        this.currentEditItemId = null;
        this.currentParentId = null;
        
        this.init();
    }

    init() {
        this.initializeUI();
        this.loadTree();
        this.setupEventListeners();
    }

    initializeUI() {
        // Initialize Semantic UI components
        $('.ui.dropdown').dropdown();
        $('.ui.checkbox').checkbox();
        $('.ui.modal').modal();
        
        // Setup item type dropdown change handler
        $('select[name="type"]').dropdown({
            onChange: (value) => this.onItemTypeChange(value)
        });
    }

    setupEventListeners() {
        // Make tree items sortable
        this.initializeSortable();
        
        // Modal form submissions
        $('#add-item-form').on('submit', (e) => {
            e.preventDefault();
            this.addItem();
        });
        
        $('#edit-item-form').on('submit', (e) => {
            e.preventDefault();
            this.updateItem();
        });
    }

    initializeSortable() {
        if (typeof Sortable !== 'undefined') {
            const treeContainer = document.getElementById('navigation-tree');
            if (treeContainer) {
                Sortable.create(treeContainer, {
                    group: 'navigation-items',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    dragClass: 'sortable-drag',
                    onEnd: (evt) => this.handleReorder(evt)
                });
            }
        }
    }

    async loadTree() {
        this.showLoading();
        
        try {
            const response = await fetch(this.config.routes.getTree);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const tree = await response.json();
            this.renderTree(tree);
            
        } catch (error) {
            console.error('Failed to load navigation tree:', error);
            this.showError('Failed to load navigation tree');
        }
    }

    renderTree(tree) {
        const container = document.getElementById('navigation-tree');
        const loadingElement = document.getElementById('tree-loading');
        const emptyElement = document.getElementById('empty-tree');
        
        loadingElement.style.display = 'none';
        
        // Handle both empty arrays and null/undefined
        if (!tree || (Array.isArray(tree) && tree.length === 0)) {
            container.style.display = 'none';
            emptyElement.style.display = 'block';
            return;
        }
        
        // If tree is not an array, convert it to one
        const items = Array.isArray(tree) ? tree : [tree];
        
        container.innerHTML = this.renderTreeItems(items);
        container.style.display = 'block';
        emptyElement.style.display = 'none';
        
        // Re-initialize sortable after rendering
        this.initializeSortable();
    }

    renderTreeItems(items, level = 0) {
        return items.map(item => this.renderTreeItem(item, level)).join('');
    }

    renderTreeItem(item, level = 0) {
        const children = item.children && item.children.length > 0 
            ? `<div class="list" style="margin-left: 20px;">${this.renderTreeItems(item.children, level + 1)}</div>`
            : '';
            
        const enabledIcon = item.enabled 
            ? '<i class="green check circle icon"></i>' 
            : '<i class="red times circle icon"></i>';
            
        const typeIcon = item.type === 'taxon' 
            ? '<i class="tag icon"></i>' 
            : '<i class="file text icon"></i>';

        return `
            <div class="item" data-item-id="${item.id}" data-level="${level}">
                <div class="content">
                    <div class="header">
                        ${typeIcon}
                        ${enabledIcon}
                        <span class="item-label">${this.escapeHtml(item.label)}</span>
                        <div class="right floated">
                            <button class="ui tiny icon button" onclick="NavigationBuilder.showAddItemModal(${item.id})" title="Add child item">
                                <i class="plus icon"></i>
                            </button>
                            <button class="ui tiny icon button" onclick="NavigationBuilder.showEditItemModal(${item.id})" title="Edit item">
                                <i class="edit icon"></i>
                            </button>
                            <button class="ui tiny red icon button" onclick="NavigationBuilder.showDeleteItemModal(${item.id})" title="Delete item">
                                <i class="trash icon"></i>
                            </button>
                        </div>
                    </div>
                </div>
                ${children}
            </div>
        `;
    }

    showAddItemModal(parentId = null) {
        this.currentParentId = parentId;
        
        // Reset form
        document.getElementById('add-item-form').reset();
        document.getElementById('parent-id-field').value = parentId || '';
        
        // Show modal
        $('#add-item-modal').modal('show');
    }

    showEditItemModal(itemId) {
        this.currentEditItemId = itemId;
        
        // Find item data
        const itemElement = document.querySelector(`[data-item-id="${itemId}"]`);
        if (!itemElement) return;
        
        const label = itemElement.querySelector('.item-label').textContent;
        const isEnabled = itemElement.querySelector('.green.check.circle.icon') !== null;
        const isTaxonItem = itemElement.querySelector('.tag.icon') !== null;
        
        // Populate form
        const form = document.getElementById('edit-item-form');
        form.querySelector('input[name="label"]').value = label;
        form.querySelector('input[name="enabled"]').checked = isEnabled;
        document.getElementById('edit-item-id').value = itemId;
        
        // Show/hide taxon field based on item type
        const taxonField = document.getElementById('edit-taxon-field');
        taxonField.style.display = isTaxonItem ? 'block' : 'none';
        
        $('#edit-item-modal').modal('show');
    }

    showDeleteItemModal(itemId) {
        document.getElementById('delete-item-id').value = itemId;
        $('#delete-item-modal').modal('show');
    }

    async addItem() {
        const form = document.getElementById('add-item-form');
        const formData = new FormData(form);
        
        const data = {
            type: formData.get('type'),
            label: formData.get('label'),
            enabled: formData.has('enabled'),
            parent_id: this.currentParentId
        };
        
        if (data.type === 'taxon') {
            data.taxon_id = formData.get('taxon_id');
            if (!data.taxon_id) {
                this.showError('Please select a taxon');
                return;
            }
        }

        try {
            const response = await fetch(this.config.routes.addItem, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.error || `HTTP ${response.status}`);
            }

            $('#add-item-modal').modal('hide');
            this.showSuccess('Item added successfully');
            this.loadTree();
            
        } catch (error) {
            console.error('Failed to add item:', error);
            this.showError('Failed to add item: ' + error.message);
        }
    }

    async updateItem() {
        const form = document.getElementById('edit-item-form');
        const formData = new FormData(form);
        const itemId = document.getElementById('edit-item-id').value;
        
        const data = {
            label: formData.get('label'),
            enabled: formData.has('enabled')
        };
        
        if (formData.has('taxon_id') && formData.get('taxon_id')) {
            data.taxon_id = formData.get('taxon_id');
        }

        try {
            const url = this.config.routes.updateItem.replace('__ITEM_ID__', itemId);
            const response = await fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.error || `HTTP ${response.status}`);
            }

            $('#edit-item-modal').modal('hide');
            this.showSuccess('Item updated successfully');
            this.loadTree();
            
        } catch (error) {
            console.error('Failed to update item:', error);
            this.showError('Failed to update item: ' + error.message);
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
                const error = await response.json();
                throw new Error(error.error || `HTTP ${response.status}`);
            }

            $('#delete-item-modal').modal('hide');
            this.showSuccess('Item deleted successfully');
            this.loadTree();
            
        } catch (error) {
            console.error('Failed to delete item:', error);
            this.showError('Failed to delete item: ' + error.message);
        }
    }

    async handleReorder(evt) {
        const itemId = evt.item.getAttribute('data-item-id');
        const newParentElement = evt.to.closest('[data-item-id]');
        const newParentId = newParentElement ? newParentElement.getAttribute('data-item-id') : null;
        const position = evt.newIndex;

        try {
            const response = await fetch(this.config.routes.reorderItem, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    item_id: itemId,
                    new_parent_id: newParentId,
                    position: position
                })
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.error || `HTTP ${response.status}`);
            }

            this.showSuccess('Item reordered successfully');
            
        } catch (error) {
            console.error('Failed to reorder item:', error);
            this.showError('Failed to reorder item: ' + error.message);
            // Reload tree to restore original order
            this.loadTree();
        }
    }

    onItemTypeChange(value) {
        const taxonField = document.getElementById('taxon-field');
        taxonField.style.display = value === 'taxon' ? 'block' : 'none';
        
        // Load taxons if switching to taxon type
        if (value === 'taxon') {
            this.loadTaxons();
        }
    }

    async loadTaxons() {
        try {
            // This would need to be implemented to load available taxons
            // For now, we'll leave it as a placeholder
            console.log('Loading taxons...');
        } catch (error) {
            console.error('Failed to load taxons:', error);
        }
    }

    showLoading() {
        document.getElementById('tree-loading').style.display = 'block';
        document.getElementById('navigation-tree').style.display = 'none';
        document.getElementById('empty-tree').style.display = 'none';
    }

    showSuccess(message) {
        this.showMessage(message, 'success');
    }

    showError(message) {
        this.showMessage(message, 'error');
    }

    showMessage(message, type) {
        // For now, just log to console and show alert
        // TODO: Implement proper Semantic UI toast when available
        console.log(`${type.toUpperCase()}: ${message}`);
        
        // Show browser alert for now
        if (type === 'error') {
            alert(`Error: ${message}`);
        } else {
            console.log(`Success: ${message}`);
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Global instance and functions for onclick handlers
let NavigationBuilderInstance;

window.NavigationBuilder = {
    init() {
        NavigationBuilderInstance = new NavigationBuilder();
    },
    
    showAddItemModal(parentId) {
        NavigationBuilderInstance.showAddItemModal(parentId);
    },
    
    showEditItemModal(itemId) {
        NavigationBuilderInstance.showEditItemModal(itemId);
    },
    
    showDeleteItemModal(itemId) {
        NavigationBuilderInstance.showDeleteItemModal(itemId);
    },
    
    addItem() {
        NavigationBuilderInstance.addItem();
    },
    
    updateItem() {
        NavigationBuilderInstance.updateItem();
    },
    
    deleteItem() {
        NavigationBuilderInstance.deleteItem();
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if (window.NavigationBuilderConfig) {
        window.NavigationBuilder.init();
    }
});