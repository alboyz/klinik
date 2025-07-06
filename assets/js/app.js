/**
 * Pet Clinic Management System - JavaScript Functions
 * Enhanced user interactions and client-side functionality
 */

// Global application object
const PetClinic = {
    // Initialize application
    init: function() {
        this.setupEventListeners();
        this.initializeComponents();
        this.setupFormValidation();
    },

    // Setup event listeners
    setupEventListeners: function() {
        // Mobile menu toggle
        const menuToggle = document.querySelector('.menu-toggle');
        const sidebar = document.querySelector('.sidebar');
        
        if (menuToggle && sidebar) {
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('mobile-open');
            });
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            if (sidebar && !sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                sidebar.classList.remove('mobile-open');
            }
        });

        // Auto-hide alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.remove();
                }, 300);
            }, 5000);
        });

        // Confirm delete actions
        const deleteButtons = document.querySelectorAll('[onclick*="delete"]');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                    e.preventDefault();
                    return false;
                }
            });
        });
    },

    // Initialize components
    initializeComponents: function() {
        this.initializeDataTables();
        this.initializeDatePickers();
        this.initializeTooltips();
        this.initializeModals();
    },

    // Initialize data tables with search and pagination
    initializeDataTables: function() {
        const tables = document.querySelectorAll('.data-table');
        tables.forEach(table => {
            this.addTableSearch(table);
            this.addTableSorting(table);
        });
    },

    // Add search functionality to tables
    addTableSearch: function(table) {
        const searchContainer = document.createElement('div');
        searchContainer.className = 'table-search';
        searchContainer.innerHTML = `
            <input type="text" placeholder="Search..." class="search-input">
        `;
        
        table.parentNode.insertBefore(searchContainer, table);
        
        const searchInput = searchContainer.querySelector('.search-input');
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    },

    // Add sorting functionality to tables
    addTableSorting: function(table) {
        const headers = table.querySelectorAll('thead th');
        headers.forEach((header, index) => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                this.sortTable(table, index);
            });
        });
    },

    // Sort table by column
    sortTable: function(table, columnIndex) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const isAscending = table.dataset.sortDirection !== 'asc';
        
        rows.sort((a, b) => {
            const aText = a.cells[columnIndex].textContent.trim();
            const bText = b.cells[columnIndex].textContent.trim();
            
            // Try to parse as numbers
            const aNum = parseFloat(aText);
            const bNum = parseFloat(bText);
            
            if (!isNaN(aNum) && !isNaN(bNum)) {
                return isAscending ? aNum - bNum : bNum - aNum;
            }
            
            // Sort as strings
            return isAscending ? aText.localeCompare(bText) : bText.localeCompare(aText);
        });
        
        // Clear tbody and append sorted rows
        tbody.innerHTML = '';
        rows.forEach(row => tbody.appendChild(row));
        
        // Update sort direction
        table.dataset.sortDirection = isAscending ? 'asc' : 'desc';
        
        // Update header indicators
        const headers = table.querySelectorAll('thead th');
        headers.forEach(h => h.classList.remove('sort-asc', 'sort-desc'));
        headers[columnIndex].classList.add(isAscending ? 'sort-asc' : 'sort-desc');
    },

    // Initialize date pickers
    initializeDatePickers: function() {
        const dateInputs = document.querySelectorAll('input[type="date"]');
        dateInputs.forEach(input => {
            // Set max date to today for past dates
            if (input.name.includes('birth') || input.name.includes('examination_date')) {
                input.max = new Date().toISOString().split('T')[0];
            }
            
            // Set min date to today for future dates
            if (input.name.includes('expiry') || input.name.includes('appointment')) {
                input.min = new Date().toISOString().split('T')[0];
            }
        });
    },

    // Initialize tooltips
    initializeTooltips: function() {
        const tooltipElements = document.querySelectorAll('[data-tooltip]');
        tooltipElements.forEach(element => {
            element.addEventListener('mouseenter', function() {
                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip';
                tooltip.textContent = this.dataset.tooltip;
                document.body.appendChild(tooltip);
                
                const rect = this.getBoundingClientRect();
                tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
                tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
            });
            
            element.addEventListener('mouseleave', function() {
                const tooltip = document.querySelector('.tooltip');
                if (tooltip) {
                    tooltip.remove();
                }
            });
        });
    },

    // Initialize modals
    initializeModals: function() {
        const modalTriggers = document.querySelectorAll('[data-modal]');
        modalTriggers.forEach(trigger => {
            trigger.addEventListener('click', function(e) {
                e.preventDefault();
                const modalId = this.dataset.modal;
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.style.display = 'block';
                }
            });
        });

        // Close modals
        const closeButtons = document.querySelectorAll('.modal .close');
        closeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const modal = this.closest('.modal');
                if (modal) {
                    modal.style.display = 'none';
                }
            });
        });

        // Close modal when clicking outside
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.style.display = 'none';
            }
        });
    },

    // Setup form validation
    setupFormValidation: function() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!PetClinic.validateForm(this)) {
                    e.preventDefault();
                }
            });
        });
    },

    // Validate form
    validateForm: function(form) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                this.showFieldError(field, 'This field is required');
                isValid = false;
            } else {
                this.clearFieldError(field);
            }
        });

        // Email validation
        const emailFields = form.querySelectorAll('input[type="email"]');
        emailFields.forEach(field => {
            if (field.value && !this.isValidEmail(field.value)) {
                this.showFieldError(field, 'Please enter a valid email address');
                isValid = false;
            }
        });

        // Phone validation
        const phoneFields = form.querySelectorAll('input[name*="phone"], input[name*="telephone"]');
        phoneFields.forEach(field => {
            if (field.value && !this.isValidPhone(field.value)) {
                this.showFieldError(field, 'Please enter a valid phone number');
                isValid = false;
            }
        });

        // Password validation
        const passwordFields = form.querySelectorAll('input[type="password"]');
        passwordFields.forEach(field => {
            if (field.value && field.value.length < 6) {
                this.showFieldError(field, 'Password must be at least 6 characters long');
                isValid = false;
            }
        });

        return isValid;
    },

    // Show field error
    showFieldError: function(field, message) {
        this.clearFieldError(field);
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.textContent = message;
        
        field.parentNode.appendChild(errorDiv);
        field.classList.add('error');
    },

    // Clear field error
    clearFieldError: function(field) {
        const existingError = field.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
        field.classList.remove('error');
    },

    // Email validation
    isValidEmail: function(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },

    // Phone validation
    isValidPhone: function(phone) {
        const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
        return phoneRegex.test(phone.replace(/[\s\-\(\)]/g, ''));
    },

    // Utility functions
    utils: {
        // Format currency
        formatCurrency: function(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(amount);
        },

        // Format date
        formatDate: function(date) {
            return new Intl.DateTimeFormat('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            }).format(new Date(date));
        },

        // Show notification
        showNotification: function(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('show');
            }, 100);
            
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 3000);
        },

        // Debounce function
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    PetClinic.init();
});

// Export for use in other scripts
window.PetClinic = PetClinic;

