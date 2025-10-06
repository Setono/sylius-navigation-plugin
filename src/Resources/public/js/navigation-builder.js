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
        
        // Initialize modal with onHidden callback to reset dropdowns
        $('#add-item-modal').modal({
            onHidden: () => {
                // Reset all dropdown states when modal is closed
                $('.ui.button.dropdown.initialized').each(function() {
                    $(this).removeClass('initialized');
                });
            }
        });
        
        $('#edit-item-modal').modal();
        $('#delete-item-modal').modal();
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
        return items.map((item, index) => this.renderTreeItem(item, level, index, items.length)).join('');
    }

    renderTreeItem(item, level = 0, index = 0, totalSiblings = 1) {
        const children = item.children && item.children.length > 0 
            ? this.renderTreeItems(item.children, level + 1)
            : '';
            
        const enabledIcon = item.enabled 
            ? '<i class="green check circle icon tree-item-enabled-icon"></i>' 
            : '<i class="red times circle icon tree-item-enabled-icon"></i>';
            
        const typeIcon = item.type === 'taxon' 
            ? '<i class="tag icon tree-item-type-icon"></i>' 
            : '<i class="file text icon tree-item-type-icon"></i>';

        return `
            <div class="tree-item" data-item-id="${item.id}" data-level="${level}">
                <div class="tree-item-content">
                    ${typeIcon}
                    ${enabledIcon}
                    <span class="tree-item-label">${this.escapeHtml(item.label)}</span>
                    <div class="tree-item-actions">
                        <div class="ui tiny icon button dropdown" onclick="NavigationBuilder.initDropdown(this, ${item.id})" title="Add child item">
                            <i class="plus icon"></i>
                            <i class="dropdown icon"></i>
                            <div class="menu">
                                <!-- Item types will be loaded dynamically -->
                            </div>
                        </div>
                        <button class="ui tiny icon button" onclick="NavigationBuilder.showEditItemModal(${item.id})" title="Edit item">
                            <i class="edit icon"></i>
                        </button>
                        <button class="ui tiny red icon button" onclick="NavigationBuilder.showDeleteItemModal(${item.id})" title="Delete item">
                            <i class="trash icon"></i>
                        </button>
                    </div>
                </div>
                ${children}
            </div>
        `;
    }

    async initDropdown(dropdownElement, parentId = null) {
        // Load item types if not already loaded
        if (!this.itemTypes) {
            await this.loadItemTypes();
        }
        
        const $dropdown = $(dropdownElement);
        
        // Check if already initialized
        if ($dropdown.hasClass('initialized')) {
            // If already initialized, destroy and reinitialize to ensure proper state
            $dropdown.dropdown('destroy');
            $dropdown.removeClass('initialized');
        }
        
        // Populate dropdown menu
        const menu = dropdownElement.querySelector('.menu');
        menu.innerHTML = '';
        
        Object.entries(this.itemTypes).forEach(([type, label]) => {
            const item = document.createElement('div');
            item.className = 'item';
            item.setAttribute('data-value', type);
            
            // Add icon based on type
            const icon = type === 'taxon' ? '<i class="tag icon"></i>' : '<i class="file text icon"></i>';
            item.innerHTML = `${icon} ${this.escapeHtml(label)}`;
            
            menu.appendChild(item);
        });
        
        // Store parentId as data attribute for later use
        $dropdown.data('parent-id', parentId);
        
        // Initialize Semantic UI dropdown with proper settings
        $dropdown.dropdown({
            action: 'hide',
            onChange: (value) => {
                if (value) {
                    const storedParentId = $dropdown.data('parent-id');
                    this.selectItemTypeFromDropdown(value, storedParentId);
                    // Clear the dropdown value after selection
                    $dropdown.dropdown('clear');
                }
            }
        });
        
        $dropdown.addClass('initialized');
        
        // Show the dropdown immediately after initialization
        $dropdown.dropdown('show');
    }
    
    async selectItemTypeFromDropdown(type, parentId = null) {
        this.currentParentId = parentId;
        this.currentSelectedType = type;
        
        // Load form for selected type
        await this.loadFormFields(type, 'add');
        
        // Set the hidden fields
        document.getElementById('parent-id-field').value = parentId || '';
        document.getElementById('item-type-field').value = type;
        
        // Update modal title based on type
        const titleElement = document.getElementById('add-item-modal-title');
        const typeLabel = this.itemTypes[type] || type;
        titleElement.textContent = `Add ${typeLabel}`;
        
        // Show modal with form
        $('#add-item-modal').modal('show');
    }

    showEditItemModal(itemId) {
        this.currentEditItemId = itemId;

        // Find item data - the data-item-id is on the tree-item div
        const treeItem = document.querySelector(`[data-item-id="${itemId}"]`);
        if (!treeItem) return;

        // Get the label from within the tree-item-content
        const label = treeItem.querySelector('.tree-item-label').textContent;
        const isEnabled = treeItem.querySelector('.green.check.circle.icon') !== null;
        const isTaxonItem = treeItem.querySelector('.tag.icon') !== null;
        
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
        
        // The type is already in the hidden field, no need to append separately
        // The parent_id is already in the hidden field, no need to append separately
        
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
            
            if (result.success) {
                // Reload the tree to ensure consistent rendering
                await this.loadTree();
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
            
            if (result.success) {
                // Reload the tree to ensure consistent rendering
                await this.loadTree();
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
            
            if (result.success) {
                // Reload the tree to ensure consistent rendering  
                await this.loadTree();
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
        // If already loaded, return cached types
        if (this.itemTypes) {
            return this.itemTypes;
        }
        
        try {
            const response = await fetch(this.config.routes.getItemTypes);
            const result = await response.json();
            
            if (result.success && result.itemTypes) {
                this.itemTypes = result.itemTypes;
                return this.itemTypes;
            } else {
                throw new Error(result.error || 'Failed to load item types');
            }
        } catch (error) {
            console.error('Failed to load item types:', error);
            this.showError('Failed to load item types: ' + error.message);
            return {};
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
        initDropdown(element, parentId) {
            NavigationBuilderInstance.initDropdown(element, parentId);
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