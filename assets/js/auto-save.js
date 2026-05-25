/**
 * Auto-save Form Data to localStorage
 * Automatically saves form data every 30 seconds and restores on page load
 */
(function() {
    'use strict';

    const DILP_AutoSave = {
        interval: null,
        saveIntervalMs: 30000, // 30 seconds
        storagePrefix: 'dilp_autosave_',
        
        init: function(formSelector, options = {}) {
            const form = document.querySelector(formSelector);
            if (!form) return;

            this.formSelector = formSelector;
            this.storageKey = this.storagePrefix + window.location.pathname;
            this.options = {
                excludeFields: options.excludeFields || [],
                onSave: options.onSave || null,
                onRestore: options.onRestore || null,
                showNotification: options.showNotification !== false
            };

            // Check for existing saved data
            this.checkForSavedData();

            // Start auto-save interval
            this.startAutoSave();

            // Clear saved data on successful form submission
            form.addEventListener('submit', () => {
                this.clearSavedData();
            });

            // Save on page unload (backup)
            window.addEventListener('beforeunload', () => {
                this.saveFormData();
            });
        },

        checkForSavedData: function() {
            const savedData = localStorage.getItem(this.storageKey);
            if (!savedData) return;

            const data = JSON.parse(savedData);
            const savedTime = new Date(data.timestamp);
            const now = new Date();
            const hoursDiff = (now - savedTime) / (1000 * 60 * 60);

            // Only restore if saved within last 24 hours
            if (hoursDiff > 24) {
                this.clearSavedData();
                return;
            }

            // Show restore prompt
            this.showRestorePrompt(savedTime);
        },

        showRestorePrompt: function(savedTime) {
            const timeStr = savedTime.toLocaleString();
            const message = `Found auto-saved draft from ${timeStr}. Would you like to restore it?`;
            
            if (confirm(message)) {
                this.restoreFormData();
            } else {
                this.clearSavedData();
            }
        },

        saveFormData: function() {
            const form = document.querySelector(this.formSelector);
            if (!form) return;

            const formData = {};
            const elements = form.elements;

            for (let i = 0; i < elements.length; i++) {
                const element = elements[i];
                const name = element.name;
                
                if (!name || this.options.excludeFields.includes(name)) continue;

                if (element.type === 'checkbox') {
                    if (!formData[name]) formData[name] = [];
                    if (element.checked) {
                        formData[name].push(element.value);
                    }
                } else if (element.type === 'radio') {
                    if (element.checked) {
                        formData[name] = element.value;
                    }
                } else if (element.tagName === 'SELECT' && element.multiple) {
                    formData[name] = Array.from(element.selectedOptions).map(opt => opt.value);
                } else {
                    formData[name] = element.value;
                }
            }

            const saveData = {
                timestamp: new Date().toISOString(),
                data: formData
            };

            localStorage.setItem(this.storageKey, JSON.stringify(saveData));

            if (this.options.showNotification) {
                this.showSaveNotification();
            }

            if (this.options.onSave) {
                this.options.onSave(formData);
            }
        },

        restoreFormData: function() {
            const savedData = localStorage.getItem(this.storageKey);
            if (!savedData) return;

            const data = JSON.parse(savedData);
            const formData = data.data;
            const form = document.querySelector(this.formSelector);
            if (!form) return;

            Object.keys(formData).forEach(name => {
                const elements = form.elements[name];
                if (!elements) return;

                if (elements.length > 1) {
                    // Multiple elements with same name (checkboxes/radios)
                    Array.from(elements).forEach(element => {
                        if (element.type === 'checkbox') {
                            element.checked = Array.isArray(formData[name]) && formData[name].includes(element.value);
                        } else if (element.type === 'radio') {
                            element.checked = element.value === formData[name];
                        }
                    });
                } else {
                    const element = elements;
                    if (element.type === 'checkbox') {
                        element.checked = Array.isArray(formData[name]) && formData[name].includes(element.value);
                    } else if (element.tagName === 'SELECT' && element.multiple) {
                        Array.from(element.options).forEach(opt => {
                            opt.selected = Array.isArray(formData[name]) && formData[name].includes(opt.value);
                        });
                    } else {
                        element.value = formData[name] || '';
                    }
                }
            });

            // Trigger change events to update any dependent fields
            const changeEvent = new Event('change', { bubbles: true });
            form.querySelectorAll('input, select, textarea').forEach(el => {
                el.dispatchEvent(changeEvent);
            });

            if (this.options.onRestore) {
                this.options.onRestore(formData);
            }

            this.showRestoreNotification();
        },

        clearSavedData: function() {
            localStorage.removeItem(this.storageKey);
        },

        startAutoSave: function() {
            this.interval = setInterval(() => {
                this.saveFormData();
            }, this.saveIntervalMs);
        },

        stopAutoSave: function() {
            if (this.interval) {
                clearInterval(this.interval);
                this.interval = null;
            }
        },

        showSaveNotification: function() {
            this.showNotificationToast('Auto-saved', 'Draft saved automatically', 'success');
        },

        showRestoreNotification: function() {
            this.showNotificationToast('Draft Restored', 'Your previous work has been restored', 'info');
        },

        showNotificationToast: function(title, message, type) {
            // Use DILP toast if available
            if (typeof DILP !== 'undefined' && DILP.toast) {
                DILP.toast[type](title, message);
                return;
            }

            // Fallback to simple notification
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} auto-save-notification`;
            notification.style.cssText = 'position: fixed; top: 80px; right: 20px; z-index: 9999; min-width: 250px; animation: slideInRight 0.3s ease;';
            notification.innerHTML = `<strong>${title}:</strong> ${message}`;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    };

    // Expose to global scope
    window.DILP_AutoSave = DILP_AutoSave;
})();
