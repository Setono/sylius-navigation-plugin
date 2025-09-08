/**
 * Navigation Builder JavaScript ES6 Module
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

    updateTreeHTML(html) {
        const container = document.getElementById('navigation-tree');
        const loadingElement = document.getElementById('tree-loading');
        const emptyElement = document.getElementById('empty-tree');
        
        loadingElement.style.display = 'none';
        
        if (!html || html.trim() === '') {
            container.style.display = 'none';
            emptyElement.style.display = 'block';
            return;
        }
        
        container.innerHTML = html;
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
                        <span class="item-actions" style="margin-left: 8px;">
                            <button class="ui tiny icon button" onclick="NavigationBuilder.showAddItemModal(${item.id})" title="Add child item">
                                <i class="plus icon"></i>
                            </button>
                            <button class="ui tiny icon button" onclick="NavigationBuilder.showEditItemModal(${item.id})" title="Edit item">
                                <i class="edit icon"></i>
                            </button>
                            <button class="ui tiny red icon button" onclick="NavigationBuilder.showDeleteItemModal(${item.id})" title="Delete item">
                                <i class="trash icon"></i>
                            </button>
                        </span>
                    </div>
                </div>
                ${children}
            </div>
        `;
    }

    async showAddItemModal(parentId = null) {
        this.currentParentId = parentId;
        this.currentSelectedType = null;
        
        // Reset modal to step 1 (type selection)
        this.resetModalToTypeSelection();
        
        // Load available item types
        await this.loadItemTypes();
        
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
        
        // Determine item type
        const itemType = isTaxonItem ? 'taxon' : 'text';
        
        // Store current values to populate after form loads
        this.pendingEditValues = {
            label: label,
            enabled: isEnabled,
            itemId: itemId
        };
        
        // Load the appropriate form type
        this.loadFormFields(itemType, 'edit').then(() => {
            // Populate form after it's loaded
            const formContainer = document.getElementById('edit-item-form-fields');
            if (formContainer) {
                const labelField = formContainer.querySelector('input[name="label"]');
                if (labelField) labelField.value = this.pendingEditValues.label;
                
                const enabledField = formContainer.querySelector('input[name="enabled"]');
                if (enabledField) enabledField.checked = this.pendingEditValues.enabled;
                
                const itemIdField = formContainer.querySelector('input[name="item_id"]');
                if (itemIdField) itemIdField.value = this.pendingEditValues.itemId;
            }
            
            document.getElementById('edit-item-id').value = itemId;
            
            // Show modal after form is loaded and populated
            $('#edit-item-modal').modal('show');
        });
    }

    showDeleteItemModal(itemId) {
        document.getElementById('delete-item-id').value = itemId;
        $('#delete-item-modal').modal('show');
    }

    async addItem() {
        const form = document.getElementById('add-item-form');
        const formData = new FormData(form);
        
        // Add the selected item type
        if (this.currentSelectedType) {
            formData.append('type', this.currentSelectedType);
        }
        
        // Add parent_id to the form data
        if (this.currentParentId) {
            formData.append('parent_id', this.currentParentId);
        }
        
        // Validate taxon selection if needed
        if (this.currentSelectedType === 'taxon' && !formData.get('taxon_id')) {
            this.showError('Please select a taxon');
            return;
        }

        try {
            const response = await fetch(this.config.routes.addItem, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.error || `HTTP ${response.status}`);
            }

            const result = await response.json();
            
            if (result.success && result.html) {
                // Update the tree with rendered HTML
                this.updateTreeHTML(result.html);
                $('#add-item-modal').modal('hide');
                this.showSuccess('Item added successfully');
            } else {
                throw new Error(result.error || 'Failed to add item');
            }
            
        } catch (error) {
            console.error('Failed to add item:', error);
            this.showError('Failed to add item: ' + error.message);
        }
    }

    async updateItem() {
        const form = document.getElementById('edit-item-form');
        const formData = new FormData(form);
        const itemId = document.getElementById('edit-item-id').value;

        try {
            const url = this.config.routes.updateItem.replace('__ITEM_ID__', itemId);
            const response = await fetch(url, {
                method: 'POST', // Use POST with form data instead of PUT with JSON
                body: formData
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.error || `HTTP ${response.status}`);
            }

            const result = await response.json();
            
            if (result.success && result.html) {
                // Update the tree with rendered HTML
                this.updateTreeHTML(result.html);
                $('#edit-item-modal').modal('hide');
                this.showSuccess('Item updated successfully');
            } else {
                throw new Error(result.error || 'Failed to update item');
            }
            
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

            const result = await response.json();
            
            if (result.success && result.html) {
                // Update the tree with rendered HTML
                this.updateTreeHTML(result.html);
                $('#delete-item-modal').modal('hide');
                this.showSuccess('Item deleted successfully');
            } else {
                throw new Error(result.error || 'Failed to delete item');
            }
            
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

    onItemTypeChange(value, modalType) {
        // Load the appropriate form fields via AJAX
        this.loadFormFields(value, modalType);
    }
    
    async loadFormFields(itemType, modalType) {
        const formContainer = modalType === 'add' 
            ? document.getElementById('add-item-form-fields')
            : document.getElementById('edit-item-form-fields');
            
        if (!formContainer) {
            console.error(`Form container not found for ${modalType} modal`);
            return;
        }
        
        // Show loading state
        formContainer.innerHTML = '<div class="ui active inline loader">Loading form...</div>';
        
        try {
            const response = await fetch(this.config.routes.getForm.replace('__TYPE__', itemType));
            
            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.error || `HTTP ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success && result.html) {
                formContainer.innerHTML = result.html;
                
                // Re-initialize Semantic UI components in the new form
                $(formContainer).find('.ui.dropdown').dropdown();
                $(formContainer).find('.ui.checkbox').checkbox();
                
                // Store the current type in a hidden field
                const typeField = document.createElement('input');
                typeField.type = 'hidden';
                typeField.name = 'type';
                typeField.value = itemType;
                formContainer.appendChild(typeField);
                
                // If edit modal, restore the parent_id and item_id
                if (modalType === 'edit' && this.currentEditItemId) {
                    const itemIdField = formContainer.querySelector('input[name="item_id"]');
                    if (itemIdField) {
                        itemIdField.value = this.currentEditItemId;
                    }
                }
                
                // If add modal, restore the parent_id
                if (modalType === 'add' && this.currentParentId) {
                    const parentIdField = formContainer.querySelector('input[name="parent_id"]');
                    if (parentIdField) {
                        parentIdField.value = this.currentParentId;
                    }
                }
            } else {
                throw new Error(result.error || 'Failed to load form');
            }
        } catch (error) {
            console.error('Failed to load form fields:', error);
            formContainer.innerHTML = `
                <div class="ui negative message">
                    <div class="header">Error loading form</div>
                    <p>${error.message}</p>
                </div>
            `;
        }
    }
    
    async loadItemTypes() {
        const container = document.getElementById('item-type-buttons');
        container.innerHTML = '<div class="ui active inline loader">Loading item types...</div>';
        
        try {
            const response = await fetch(this.config.routes.getItemTypes);
            const result = await response.json();
            
            if (result.success && result.itemTypes) {
                const buttons = Object.entries(result.itemTypes).map(([type, label]) => {
                    return `
                        <button class="ui button" onclick="NavigationBuilder.selectItemType('${type}')">
                            <i class="${this.getIconForType(type)} icon"></i>
                            ${this.escapeHtml(label)}
                        </button>
                    `;
                }).join('');
                
                container.innerHTML = buttons;
            } else {
                throw new Error(result.error || 'Failed to load item types');
            }
        } catch (error) {
            console.error('Failed to load item types:', error);
            container.innerHTML = `
                <div class="ui negative message">
                    <div class="header">Error loading item types</div>
                    <p>${error.message}</p>
                </div>
            `;
        }
    }
    
    getIconForType(type) {
        switch (type) {
            case 'taxon':
                return 'tag';
            case 'text':
                return 'file text';
            default:
                return 'plus';
        }
    }
    
    async selectItemType(type) {
        this.currentSelectedType = type;
        
        // Show form container and hide type selection
        document.getElementById('item-type-selection').style.display = 'none';
        document.getElementById('item-form-container').style.display = 'block';
        document.getElementById('back-button').style.display = 'inline-block';
        document.getElementById('create-button').style.display = 'inline-block';
        
        // Reset and set up form
        const form = document.getElementById('add-item-form');
        if (form) {
            form.reset();
        }
        
        const parentIdField = document.getElementById('parent-id-field');
        if (parentIdField) {
            parentIdField.value = this.currentParentId || '';
        }
        
        // Load form fields for the selected type
        await this.loadFormFields(type, 'add');
    }
    
    goBackToTypeSelection() {
        this.resetModalToTypeSelection();
    }
    
    resetModalToTypeSelection() {
        // Show type selection and hide form container
        document.getElementById('item-type-selection').style.display = 'block';
        document.getElementById('item-form-container').style.display = 'none';
        document.getElementById('back-button').style.display = 'none';
        document.getElementById('create-button').style.display = 'none';
        
        // Reset form
        const form = document.getElementById('add-item-form');
        if (form) {
            form.reset();
        }
        
        // Clear form fields container
        const formFields = document.getElementById('add-item-form-fields');
        if (formFields) {
            formFields.innerHTML = '';
        }
        
        this.currentSelectedType = null;
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

// Module initialization - automatically runs after DOM is loaded
if (window.NavigationBuilderConfig) {
    const NavigationBuilderInstance = new NavigationBuilder();
    
    // Export global functions for onclick handlers
    window.NavigationBuilder = {
        showAddItemModal(parentId) {
            NavigationBuilderInstance.showAddItemModal(parentId);
        },
        
        showEditItemModal(itemId) {
            NavigationBuilderInstance.showEditItemModal(itemId);
        },
        
        showDeleteItemModal(itemId) {
            NavigationBuilderInstance.showDeleteItemModal(itemId);
        },
        
        selectItemType(type) {
            NavigationBuilderInstance.selectItemType(type);
        },
        
        goBackToTypeSelection() {
            NavigationBuilderInstance.goBackToTypeSelection();
        },
        
        addItem() {
            NavigationBuilderInstance.addItem();
        },
        
        updateItem() {
            NavigationBuilderInstance.updateItem();
        },
        
        deleteItem() {
            NavigationBuilderInstance.deleteItem();
        },
        
        // Expose instance for debugging
        getInstance() {
            return NavigationBuilderInstance;
        }
    };
    
    console.log('NavigationBuilder initialized as ES6 module');
} else {
    console.warn('NavigationBuilderConfig not found, NavigationBuilder not initialized');
}