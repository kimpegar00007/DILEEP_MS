/**
 * DILP Form Auto-Save Module
 * Saves form field values to localStorage on input change (debounced).
 * Restores saved values on page load when the form is empty (new record mode).
 * Clears saved data on successful form submission.
 *
 * Usage:
 *   DILPAutoSave.init({ formSelector: 'form[method="POST"]', storageKey: 'beneficiary-form' });
 *   DILPAutoSave.init({ formSelector: 'form[method="POST"]', storageKey: 'proponent-form' });
 */
var DILPAutoSave = (function () {
    'use strict';

    var _debounceTimer = null;
    var _config = {
        formSelector: 'form[method="POST"]',
        storageKey: 'dilp_autosave',
        debounceMs: 500,
        editParam: 'id'
    };

    function _getStorageKey() {
        var params = new URLSearchParams(window.location.search);
        var editId = params.get(_config.editParam);
        return editId
            ? _config.storageKey + '_edit_' + editId
            : _config.storageKey + '_new';
    }

    function _getForm() {
        return document.querySelector(_config.formSelector);
    }

    function _collectFormData(form) {
        var data = {};
        var elements = form.elements;

        for (var i = 0; i < elements.length; i++) {
            var el = elements[i];
            if (!el.name || el.disabled || el.type === 'submit' || el.type === 'button' || el.type === 'hidden') continue;

            var key = el.name;

            if (el.type === 'checkbox') {
                if (!data[key]) data[key] = [];
                if (el.checked) data[key].push(el.value);
            } else if (el.type === 'radio') {
                if (el.checked) data[key] = el.value;
            } else if (el.type === 'select-multiple') {
                data[key] = [];
                for (var j = 0; j < el.options.length; j++) {
                    if (el.options[j].selected) data[key].push(el.options[j].value);
                }
            } else {
                // For array fields like beneficiary_full_names[]
                if (key.endsWith('[]')) {
                    if (!data[key]) data[key] = [];
                    data[key].push(el.value);
                } else {
                    data[key] = el.value;
                }
            }
        }

        return data;
    }

    function _saveToStorage(form) {
        try {
            var data = _collectFormData(form);
            localStorage.setItem(_getStorageKey(), JSON.stringify(data));
        } catch (e) {
            console.warn('[DILPAutoSave] Failed to save:', e);
        }
    }

    function _restoreFromStorage(form) {
        try {
            var raw = localStorage.getItem(_getStorageKey());
            if (!raw) return false;

            var data = JSON.parse(raw);
            if (!data || typeof data !== 'object') return false;

            var restored = false;
            var elements = form.elements;

            for (var i = 0; i < elements.length; i++) {
                var el = elements[i];
                if (!el.name || el.disabled || el.type === 'submit' || el.type === 'button' || el.type === 'hidden') continue;

                var key = el.name;
                var savedValue = data[key];

                if (savedValue === undefined || savedValue === null) continue;

                if (el.type === 'checkbox') {
                    if (Array.isArray(savedValue) && savedValue.indexOf(el.value) !== -1) {
                        el.checked = true;
                        restored = true;
                    }
                } else if (el.type === 'radio') {
                    if (el.value === savedValue) {
                        el.checked = true;
                        restored = true;
                    }
                } else if (key.endsWith('[]')) {
                    // Array fields — skip here, handled by dynamic field generators
                } else {
                    if (savedValue !== '' && el.value === '') {
                        el.value = savedValue;
                        restored = true;
                    }
                }
            }

            return restored;
        } catch (e) {
            console.warn('[DILPAutoSave] Failed to restore:', e);
            return false;
        }
    }

    function _showRestoredToast() {
        if (typeof DILP !== 'undefined' && DILP.toast) {
            DILP.toast.info('Draft Restored', 'Previously unsaved form data has been restored.');
        }
    }

    function _clearStorage() {
        try {
            localStorage.removeItem(_getStorageKey());
        } catch (e) {
            console.warn('[DILPAutoSave] Failed to clear:', e);
        }
    }

    function _isPostReload() {
        // Check if page has server-rendered errors or success (POST was processed)
        return document.querySelector('.alert-danger') !== null ||
               document.querySelector('.alert-success') !== null;
    }

    function init(options) {
        if (options) {
            for (var k in options) {
                if (options.hasOwnProperty(k)) _config[k] = options[k];
            }
        }

        var form = _getForm();
        if (!form) return;

        // Clear storage on successful submission redirect (success message present)
        if (document.querySelector('.alert-success')) {
            _clearStorage();
            return;
        }

        // Only restore if not a POST error reload (POST data already in form via PHP)
        if (!_isPostReload()) {
            var wasRestored = _restoreFromStorage(form);
            if (wasRestored) {
                _showRestoredToast();
            }
        }

        // Attach save listeners
        form.addEventListener('input', function () {
            clearTimeout(_debounceTimer);
            _debounceTimer = setTimeout(function () {
                _saveToStorage(form);
            }, _config.debounceMs);
        });

        form.addEventListener('change', function () {
            clearTimeout(_debounceTimer);
            _debounceTimer = setTimeout(function () {
                _saveToStorage(form);
            }, _config.debounceMs);
        });

        // Clear on successful submit
        form.addEventListener('submit', function () {
            _clearStorage();
        });

        // Wire up Clear Form button (#btn-clear-form)
        _wireClearFormButton(form);
    }

    /**
     * Reset all input/select/textarea fields in the form to empty/default,
     * uncheck all checkboxes/radios, and wipe the localStorage draft.
     * Preserves hidden fields that represent server-supplied IDs (e.g. CSRF tokens).
     */
    function _resetForm(form) {
        var elements = form.elements;
        for (var i = 0; i < elements.length; i++) {
            var el = elements[i];
            if (!el.name || el.disabled) continue;
            if (el.type === 'submit' || el.type === 'button') continue;
            // Skip hidden fields used for security/identification
            if (el.type === 'hidden' && /csrf|token|_method/i.test(el.name)) continue;

            if (el.type === 'checkbox' || el.type === 'radio') {
                el.checked = false;
            } else if (el.tagName === 'SELECT') {
                el.selectedIndex = 0;
            } else {
                el.value = '';
            }
            // Trigger change so dependent JS (e.g. dynamic field generators) updates
            try {
                el.dispatchEvent(new Event('change', { bubbles: true }));
                el.dispatchEvent(new Event('input',  { bubbles: true }));
            } catch (_) { /* ignore IE */ }
        }
    }

    function _wireClearFormButton(form) {
        var btn = document.getElementById('btn-clear-form');
        if (!btn) return;

        btn.addEventListener('click', function () {
            var ok = window.confirm('Clear all form fields? Any unsaved input will be lost.');
            if (!ok) return;

            _resetForm(form);
            _clearStorage();

            // Notify any consumers (e.g. dynamic beneficiary name renderer, proponent combobox)
            try {
                form.dispatchEvent(new CustomEvent('dilp:form-cleared', { bubbles: true }));
            } catch (_) { /* ignore */ }

            if (typeof DILP !== 'undefined' && DILP.toast) {
                DILP.toast.success('Form Cleared', 'All fields have been reset.');
            }
        });
    }

    return {
        init: init,
        clear: _clearStorage
    };
})();
