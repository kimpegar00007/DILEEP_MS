<!-- Toast Container - Part 3 -->
<div class="toast-container" id="toastContainer" aria-live="polite" aria-atomic="true"></div>

<!-- Loading Overlay - Part 5 -->
<div class="loading-overlay" id="loadingOverlay" role="alert" aria-busy="true" aria-label="Loading content">
    <div class="loading-spinner"></div>
    <div class="loading-text" id="loadingText">Loading...</div>
</div>

<!-- Session Timeout Warning Modal - Part 8 -->
<div class="modal fade session-warning-modal" id="sessionTimeoutModal" tabindex="-1" aria-labelledby="sessionTimeoutLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sessionTimeoutLabel"><i class="bi bi-clock-history"></i> Session Expiring Soon</h5>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="bi bi-shield-exclamation text-warning" style="font-size: 3rem;"></i>
                </div>
                <p class="text-center">Your session will expire in <strong id="sessionCountdown">60</strong> seconds due to inactivity.</p>
                <p class="text-center text-muted">Click "Stay Logged In" to continue your session.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" onclick="window.location.href='logout.php'">
                    <i class="bi bi-box-arrow-right"></i> Logout Now
                </button>
                <button type="button" class="btn btn-primary" id="sessionExtendBtn">
                    <i class="bi bi-arrow-clockwise"></i> Stay Logged In
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Onboarding Tour Container - Part 1 -->
<div class="tour-overlay" id="tourOverlay"></div>
<div class="tour-highlight" id="tourHighlight" style="display:none;"></div>
<div class="tour-tooltip" id="tourTooltip" style="display:none;" role="dialog" aria-label="Guided tour">
    <div class="tour-progress" id="tourProgress"></div>
    <h6 id="tourTitle"></h6>
    <p id="tourDescription"></p>
    <div class="tour-nav">
        <button class="btn btn-sm btn-outline-secondary" id="tourSkip">Skip Tour</button>
        <div>
            <button class="btn btn-sm btn-outline-primary me-1" id="tourPrev" style="display:none;">
                <i class="bi bi-chevron-left"></i> Back
            </button>
            <button class="btn btn-sm btn-primary" id="tourNext">
                Next <i class="bi bi-chevron-right"></i>
            </button>
        </div>
    </div>
</div>

<script>
// ============================================================
// DILP UX Utilities - Centralized UX Enhancement System
// ============================================================

const DILP = {
    // --------------------------------------------------------
    // Part 3: Toast Notification System
    // --------------------------------------------------------
    toast: {
        show(type, title, message, options = {}) {
            const container = document.getElementById('toastContainer');
            if (!container) return;

            const icons = {
                success: 'bi-check-circle-fill',
                error: 'bi-exclamation-triangle-fill',
                warning: 'bi-exclamation-circle-fill',
                info: 'bi-info-circle-fill'
            };

            const toast = document.createElement('div');
            toast.className = `dilp-toast toast-${type}`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');

            let undoHtml = '';
            if (options.undoCallback) {
                undoHtml = `<button class="dilp-toast-undo" onclick="DILP.toast._handleUndo(this, '${options.undoId || ''}')">Undo</button>`;
            }

            toast.innerHTML = `
                <i class="bi ${icons[type] || icons.info} dilp-toast-icon" aria-hidden="true"></i>
                <div class="dilp-toast-body">
                    <div class="dilp-toast-title">${this._escapeHtml(title)}</div>
                    <div class="dilp-toast-message">${this._escapeHtml(message)}</div>
                    ${undoHtml}
                </div>
                <button class="dilp-toast-close" aria-label="Close notification" onclick="DILP.toast._dismiss(this.parentElement)">&times;</button>
            `;

            if (options.undoCallback) {
                toast._undoCallback = options.undoCallback;
            }

            container.appendChild(toast);

            const duration = options.duration || (type === 'error' ? 8000 : 5000);
            toast._timeout = setTimeout(() => this._dismiss(toast), duration);

            return toast;
        },

        success(title, message, options) { return this.show('success', title, message, options); },
        error(title, message, options) { return this.show('error', title, message, options); },
        warning(title, message, options) { return this.show('warning', title, message, options); },
        info(title, message, options) { return this.show('info', title, message, options); },

        _dismiss(toast) {
            if (!toast || toast._dismissed) return;
            toast._dismissed = true;
            clearTimeout(toast._timeout);
            toast.classList.add('toast-hiding');
            setTimeout(() => toast.remove(), 300);
        },

        _handleUndo(btn, undoId) {
            const toast = btn.closest('.dilp-toast');
            if (toast && toast._undoCallback) {
                toast._undoCallback(undoId);
                this._dismiss(toast);
                if (undoId !== 'start-tour') {
                    this.info('Undone', 'The action has been reversed.');
                }
            }
        },

        _escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    },

    // --------------------------------------------------------
    // Part 5: Loading State Management
    // --------------------------------------------------------
    loading: {
        show(text = 'Loading...') {
            const overlay = document.getElementById('loadingOverlay');
            const loadingText = document.getElementById('loadingText');
            if (overlay) {
                if (loadingText) loadingText.textContent = text;
                overlay.classList.add('active');
            }
        },

        hide() {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) overlay.classList.remove('active');
        },

        inline(container, show = true) {
            if (typeof container === 'string') {
                container = document.querySelector(container);
            }
            if (!container) return;

            const existing = container.querySelector('.inline-loader');
            if (show && !existing) {
                const loader = document.createElement('div');
                loader.className = 'inline-loader';
                loader.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div><span>Loading data...</span>';
                container.appendChild(loader);
            } else if (!show && existing) {
                existing.remove();
            }
        }
    },

    // --------------------------------------------------------
    // Part 2: Form Validation System
    // --------------------------------------------------------
    validation: {
        init(formSelector) {
            const form = document.querySelector(formSelector);
            if (!form) return;

            form.setAttribute('novalidate', '');

            const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
            inputs.forEach(input => {
                input.addEventListener('blur', () => this._validateField(input));
                input.addEventListener('input', () => {
                    if (input.classList.contains('is-invalid')) {
                        this._validateField(input);
                    }
                });
            });

            // Real-time email validation
            form.querySelectorAll('input[type="email"]').forEach(input => {
                input.addEventListener('input', () => this._validateEmail(input));
            });

            // Real-time phone validation
            form.querySelectorAll('input[name="contact_number"]').forEach(input => {
                input.addEventListener('input', () => this._validatePhone(input));
            });

            // Password match validation
            const confirmPwd = form.querySelector('input[name="confirm_password"]');
            if (confirmPwd) {
                confirmPwd.addEventListener('input', () => this._validatePasswordMatch(form));
            }

            form.addEventListener('submit', (e) => {
                let isValid = true;
                inputs.forEach(input => {
                    if (!this._validateField(input)) isValid = false;
                });
                if (!isValid) {
                    e.preventDefault();
                    DILP.toast.error('Validation Error', 'Please fix the highlighted fields before submitting.');
                    const firstInvalid = form.querySelector('.is-invalid');
                    if (firstInvalid) firstInvalid.focus();
                }
            });
        },

        _validateField(input) {
            const value = input.value.trim();
            let isValid = true;
            let message = '';

            if (input.hasAttribute('required') && !value) {
                isValid = false;
                const label = this._getFieldLabel(input);
                message = `${label} is required`;
            } else if (input.type === 'email' && value && !this._isValidEmail(value)) {
                isValid = false;
                message = 'Please enter a valid email address';
            } else if (input.minLength > 0 && value.length < input.minLength) {
                isValid = false;
                message = `Must be at least ${input.minLength} characters`;
            } else if (input.type === 'number' && value) {
                const num = parseFloat(value);
                if (input.min && num < parseFloat(input.min)) {
                    isValid = false;
                    message = `Value must be at least ${input.min}`;
                }
            }

            this._setFieldState(input, isValid, message);
            return isValid;
        },

        _validateEmail(input) {
            const value = input.value.trim();
            if (!value) return;
            const isValid = this._isValidEmail(value);
            this._setFieldState(input, isValid, isValid ? '' : 'Please enter a valid email address');
        },

        _validatePhone(input) {
            const value = input.value.trim();
            if (!value) return;
            const isValid = /^[\d\s\-\+\(\)]{7,15}$/.test(value);
            this._setFieldState(input, isValid, isValid ? '' : 'Please enter a valid phone number');
        },

        _validatePasswordMatch(form) {
            const pwd = form.querySelector('input[name="new_password"], input[name="password"]');
            const confirm = form.querySelector('input[name="confirm_password"]');
            if (!pwd || !confirm || !confirm.value) return;
            const match = pwd.value === confirm.value;
            this._setFieldState(confirm, match, match ? '' : 'Passwords do not match');
        },

        _setFieldState(input, isValid, message) {
            input.classList.remove('is-valid', 'is-invalid');
            input.classList.add(isValid ? 'is-valid' : 'is-invalid');

            let feedback = input.parentElement.querySelector('.invalid-feedback');
            if (!isValid && message) {
                if (!feedback) {
                    feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    input.parentElement.appendChild(feedback);
                }
                feedback.textContent = message;
            }
        },

        _getFieldLabel(input) {
            const label = input.closest('.mb-3, .col-md-3, .col-md-4, .col-md-6, .col-md-12')?.querySelector('.form-label');
            if (label) {
                return label.textContent.replace('*', '').trim();
            }
            return input.name ? input.name.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'This field';
        },

        _isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }
    },

    // --------------------------------------------------------
    // Part 8: Password Strength Indicator
    // --------------------------------------------------------
    passwordStrength: {
        init(inputSelector, options = {}) {
            const input = document.querySelector(inputSelector);
            if (!input) return;

            const container = input.parentElement;
            let strengthEl = container.querySelector('.password-strength');
            if (!strengthEl) {
                strengthEl = document.createElement('div');
                strengthEl.className = 'password-strength';
                strengthEl.innerHTML = `
                    <div class="strength-bar"><div class="strength-fill" id="pwdStrengthFill"></div></div>
                    <div class="strength-text" id="pwdStrengthText"></div>
                    <ul class="strength-requirements" id="pwdRequirements">
                        <li data-req="length">At least 8 characters</li>
                        <li data-req="upper">Contains uppercase letter</li>
                        <li data-req="lower">Contains lowercase letter</li>
                        <li data-req="number">Contains a number</li>
                        <li data-req="special">Contains special character</li>
                    </ul>
                `;
                container.appendChild(strengthEl);
            }

            input.addEventListener('input', () => this._evaluate(input));
        },

        _evaluate(input) {
            const pwd = input.value;
            const fill = input.parentElement.querySelector('.strength-fill') || document.getElementById('pwdStrengthFill');
            const text = input.parentElement.querySelector('.strength-text') || document.getElementById('pwdStrengthText');
            const reqs = input.parentElement.querySelectorAll('.strength-requirements li');

            if (!fill || !text) return;

            const checks = {
                length: pwd.length >= 8,
                upper: /[A-Z]/.test(pwd),
                lower: /[a-z]/.test(pwd),
                number: /[0-9]/.test(pwd),
                special: /[^a-zA-Z0-9]/.test(pwd)
            };

            reqs.forEach(li => {
                const req = li.dataset.req;
                if (checks[req]) {
                    li.classList.add('met');
                } else {
                    li.classList.remove('met');
                }
            });

            const score = Object.values(checks).filter(Boolean).length;
            fill.className = 'strength-fill';

            if (pwd.length === 0) {
                fill.style.width = '0%';
                text.textContent = '';
                return;
            }

            if (score <= 1) {
                fill.classList.add('weak');
                text.textContent = 'Weak password';
                text.style.color = '#dc3545';
            } else if (score <= 2) {
                fill.classList.add('fair');
                text.textContent = 'Fair password';
                text.style.color = '#fd7e14';
            } else if (score <= 3) {
                fill.classList.add('good');
                text.textContent = 'Good password';
                text.style.color = '#ffc107';
            } else {
                fill.classList.add('strong');
                text.textContent = 'Strong password';
                text.style.color = '#28a745';
            }
        }
    },

    // --------------------------------------------------------
    // Part 8: Session Timeout Manager
    // --------------------------------------------------------
    session: {
        _timeout: null,
        _warningTimeout: null,
        _countdownInterval: null,
        _sessionDuration: 30 * 60 * 1000,   // 30 minutes
        _warningBefore: 60 * 1000,           // warn 60s before

        init() {
            this._resetTimers();
            ['click', 'keypress', 'mousemove', 'scroll'].forEach(event => {
                document.addEventListener(event, () => this._resetTimers(), { passive: true });
            });

            const extendBtn = document.getElementById('sessionExtendBtn');
            if (extendBtn) {
                extendBtn.addEventListener('click', () => this._extendSession());
            }
        },

        _resetTimers() {
            clearTimeout(this._timeout);
            clearTimeout(this._warningTimeout);
            clearInterval(this._countdownInterval);

            this._warningTimeout = setTimeout(() => {
                this._showWarning();
            }, this._sessionDuration - this._warningBefore);

            this._timeout = setTimeout(() => {
                window.location.href = 'logout.php?timeout=1';
            }, this._sessionDuration);
        },

        _showWarning() {
            const modal = document.getElementById('sessionTimeoutModal');
            if (!modal) return;
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();

            let seconds = 60;
            const countdown = document.getElementById('sessionCountdown');
            this._countdownInterval = setInterval(() => {
                seconds--;
                if (countdown) countdown.textContent = seconds;
                if (seconds <= 0) {
                    clearInterval(this._countdownInterval);
                    window.location.href = 'logout.php?timeout=1';
                }
            }, 1000);
        },

        _extendSession() {
            clearInterval(this._countdownInterval);
            const modal = bootstrap.Modal.getInstance(document.getElementById('sessionTimeoutModal'));
            if (modal) modal.hide();
            this._resetTimers();

            // Ping server to extend session
            fetch('index.php', { method: 'HEAD', credentials: 'same-origin' })
                .catch(() => {});

            DILP.toast.success('Session Extended', 'Your session has been renewed.');
        }
    },

    // --------------------------------------------------------
    // Part 1: Onboarding Tour System
    // --------------------------------------------------------
    tour: {
        _steps: [],
        _currentStep: 0,
        _active: false,

        init(steps) {
            this._steps = steps;
            this._currentStep = 0;

            // Check if user has seen the tour
            const tourSeen = localStorage.getItem('dilp_tour_completed');
            if (!tourSeen) {
                setTimeout(() => this._promptTour(), 1500);
            }
        },

        _promptTour() {
            DILP.toast.show('info', 'Welcome!', 'Would you like a quick tour of the system?', {
                duration: 15000,
                undoCallback: () => this.start(),
                undoId: 'start-tour'
            });
            // Override the undo button text
            setTimeout(() => {
                const undoBtn = document.querySelector('.dilp-toast-undo');
                if (undoBtn) undoBtn.textContent = 'Start Tour';
            }, 50);
        },

        start() {
            if (this._steps.length === 0) return;
            this._active = true;
            this._currentStep = 0;
            const overlay = document.getElementById('tourOverlay');
            overlay.classList.add('active');
            // Allow dismissing tour by clicking the overlay backdrop
            overlay.onclick = (e) => {
                if (e.target === overlay) this.end();
            };
            this._showStep();
        },

        _showStep() {
            try {
                const step = this._steps[this._currentStep];
                if (!step) return this.end();

                const target = document.querySelector(step.target);
                const highlight = document.getElementById('tourHighlight');
                const tooltip = document.getElementById('tourTooltip');
                const title = document.getElementById('tourTitle');
                const desc = document.getElementById('tourDescription');
                const progress = document.getElementById('tourProgress');
                const prevBtn = document.getElementById('tourPrev');
                const nextBtn = document.getElementById('tourNext');

                if (!target || !tooltip) return this.end();

                // Scroll target into view first, then position after scroll settles
                target.scrollIntoView({ behavior: 'smooth', block: 'center' });

                setTimeout(() => {
                    // Update progress dots
                    progress.innerHTML = this._steps.map((_, i) => {
                        let cls = 'tour-progress-dot';
                        if (i < this._currentStep) cls += ' completed';
                        if (i === this._currentStep) cls += ' active';
                        return `<div class="${cls}"></div>`;
                    }).join('');

                    title.textContent = step.title;
                    desc.textContent = step.description;

                    // Use viewport-relative coords (position: fixed)
                    const rect = target.getBoundingClientRect();

                    // Position highlight
                    highlight.style.display = 'block';
                    highlight.style.top = (rect.top - 4) + 'px';
                    highlight.style.left = (rect.left - 4) + 'px';
                    highlight.style.width = (rect.width + 8) + 'px';
                    highlight.style.height = (rect.height + 8) + 'px';

                    // Position tooltip below target
                    tooltip.style.display = 'block';
                    let tooltipTop = rect.bottom + 12;
                    const tooltipLeft = Math.max(10, Math.min(rect.left, window.innerWidth - 360));
                    tooltip.style.left = tooltipLeft + 'px';

                    // If tooltip goes off screen bottom, place above target
                    if (tooltipTop + 200 > window.innerHeight) {
                        tooltipTop = rect.top - 220;
                        if (tooltipTop < 10) tooltipTop = 10;
                    }
                    tooltip.style.top = tooltipTop + 'px';

                    prevBtn.style.display = this._currentStep > 0 ? 'inline-block' : 'none';
                    if (this._currentStep < this._steps.length - 1) {
                        nextBtn.innerHTML = 'Next <i class="bi bi-chevron-right"></i>';
                    } else {
                        nextBtn.innerHTML = '<i class="bi bi-check-lg"></i> Finish';
                    }
                }, 400);
            } catch (e) {
                console.error('[DILP Tour] Error in _showStep:', e);
                this.end();
            }
        },

        next() {
            this._currentStep++;
            if (this._currentStep >= this._steps.length) {
                this.end();
            } else {
                this._showStep();
            }
        },

        prev() {
            if (this._currentStep > 0) {
                this._currentStep--;
                this._showStep();
            }
        },

        end() {
            this._active = false;
            document.getElementById('tourOverlay').classList.remove('active');
            document.getElementById('tourHighlight').style.display = 'none';
            document.getElementById('tourTooltip').style.display = 'none';
            localStorage.setItem('dilp_tour_completed', 'true');
            DILP.toast.success('Tour Complete', 'You can restart the tour from your profile settings anytime.');
        }
    },

    // --------------------------------------------------------
    // Part 1: Tooltip Helper
    // --------------------------------------------------------
    tooltip: {
        create(text) {
            return `<span class="help-icon" data-bs-toggle="tooltip" data-bs-placement="top" title="${text}" role="img" aria-label="${text}">?</span>`;
        },

        initAll() {
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));
        }
    },

    // --------------------------------------------------------
    // Part 10: Export Utilities
    // --------------------------------------------------------
    export: {
        toCSV(tableSelector, filename = 'report') {
            const table = document.querySelector(tableSelector);
            if (!table) {
                DILP.toast.error('Export Error', 'No data table found to export.');
                return;
            }

            DILP.loading.show('Preparing CSV export...');

            const rows = [];
            const headers = [];
            table.querySelectorAll('thead th').forEach(th => {
                headers.push('"' + th.textContent.trim().replace(/"/g, '""') + '"');
            });
            rows.push(headers.join(','));

            table.querySelectorAll('tbody tr').forEach(tr => {
                const cols = [];
                tr.querySelectorAll('td').forEach(td => {
                    cols.push('"' + td.textContent.trim().replace(/"/g, '""') + '"');
                });
                rows.push(cols.join(','));
            });

            const csvContent = '\uFEFF' + rows.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `${filename}_${new Date().toISOString().slice(0,10)}.csv`;
            link.click();
            URL.revokeObjectURL(link.href);

            DILP.loading.hide();
            DILP.toast.success('Export Complete', 'CSV file has been downloaded.');
        },

        toExcel(tableSelector, filename = 'report') {
            const table = document.querySelector(tableSelector);
            if (!table) {
                DILP.toast.error('Export Error', 'No data table found to export.');
                return;
            }

            DILP.loading.show('Preparing Excel export...');

            const html = `
                <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
                <head><meta charset="UTF-8">
                <!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>
                <x:Name>Report</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>
                </x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->
                <style>td,th{border:1px solid #ccc;padding:5px;font-family:Arial;font-size:12px;}th{background:#1B7A3D;color:white;font-weight:bold;}</style>
                </head><body>${table.outerHTML}</body></html>`;

            const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `${filename}_${new Date().toISOString().slice(0,10)}.xls`;
            link.click();
            URL.revokeObjectURL(link.href);

            DILP.loading.hide();
            DILP.toast.success('Export Complete', 'Excel file has been downloaded.');
        },

        toPDF(tableSelector, title = 'Report') {
            DILP.loading.show('Preparing PDF export...');

            const table = document.querySelector(tableSelector);
            if (!table) {
                DILP.loading.hide();
                DILP.toast.error('Export Error', 'No data table found to export.');
                return;
            }

            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html><head><title>${title}</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    h1 { color: #145A2C; font-size: 18px; margin-bottom: 5px; }
                    .meta { color: #666; font-size: 12px; margin-bottom: 15px; }
                    table { width: 100%; border-collapse: collapse; font-size: 11px; }
                    th { background: #145A2C; color: white; padding: 8px 6px; text-align: left; }
                    td { padding: 6px; border-bottom: 1px solid #ddd; }
                    tr:nth-child(even) { background: #f8f9fa; }
                    .footer { margin-top: 20px; text-align: center; font-size: 10px; color: #999; }
                </style></head><body>
                <h1>DOLE DILEEP Monitoring System - ${title}</h1>
                <div class="meta">Generated: ${new Date().toLocaleString()} | Total Records: ${table.querySelectorAll('tbody tr').length}</div>
                ${table.outerHTML}
                <div class="footer">&copy; 2026 Department of Labor and Employment</div>
                </body></html>
            `);
            printWindow.document.close();
            printWindow.onload = function() {
                printWindow.print();
                DILP.loading.hide();
                DILP.toast.success('PDF Ready', 'Print dialog opened. Save as PDF from your browser.');
            };
        }
    },

    // --------------------------------------------------------
    // Part 6: Error Handler
    // --------------------------------------------------------
    error: {
        handle(error, context = '') {
            console.error(`[DILP Error] ${context}:`, error);
            const message = error.message || 'An unexpected error occurred. Please try again.';
            DILP.toast.error('Error', `${context ? context + ': ' : ''}${message}`);
        },

        handleFetch(response) {
            if (!response.ok) {
                throw new Error(`Server returned ${response.status}: ${response.statusText}`);
            }
            return response;
        }
    },

    // --------------------------------------------------------
    // Part 4: Accessibility Enhancements
    // --------------------------------------------------------
    accessibility: {
        init() {
            // Add aria-labels to icon-only buttons
            document.querySelectorAll('.action-btn').forEach(btn => {
                if (!btn.getAttribute('aria-label') && btn.title) {
                    btn.setAttribute('aria-label', btn.title);
                }
            });

            // Ensure all images have alt text
            document.querySelectorAll('img:not([alt])').forEach(img => {
                img.setAttribute('alt', 'Image');
            });

            // Add role="status" to badge elements for screen readers
            document.querySelectorAll('.badge-status').forEach(badge => {
                badge.setAttribute('role', 'status');
            });

            // Keyboard navigation for table rows
            document.querySelectorAll('.table-hover tbody tr').forEach(row => {
                row.setAttribute('tabindex', '0');
                row.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        const viewBtn = row.querySelector('.btn-info');
                        if (viewBtn) viewBtn.click();
                    }
                });
            });
        }
    },

    // --------------------------------------------------------
    // Initialization
    // --------------------------------------------------------
    init() {
        const self = this;
        function _run() {
            // Each init step is isolated so one failure doesn't block others
            try { self.tooltip.initAll(); } catch (e) { console.warn('[DILP] Tooltip init failed:', e); }
            try { self.accessibility.init(); } catch (e) { console.warn('[DILP] Accessibility init failed:', e); }
            try { self.session.init(); } catch (e) { console.warn('[DILP] Session init failed:', e); }

            // Wire up tour buttons with comprehensive debugging
            try {
                const tourNext = document.getElementById('tourNext');
                const tourPrev = document.getElementById('tourPrev');
                const tourSkip = document.getElementById('tourSkip');
                
                console.log('[DILP Tour] Button elements found:', {
                    tourNext: !!tourNext,
                    tourPrev: !!tourPrev,
                    tourSkip: !!tourSkip
                });
                
                if (tourNext) {
                    tourNext.addEventListener('click', function(e) {
                        console.log('[DILP Tour] Next button clicked');
                        e.preventDefault();
                        e.stopPropagation();
                        self.tour.next();
                    });
                    console.log('[DILP Tour] Next button listener attached');
                }
                if (tourPrev) {
                    tourPrev.addEventListener('click', function(e) {
                        console.log('[DILP Tour] Prev button clicked');
                        e.preventDefault();
                        e.stopPropagation();
                        self.tour.prev();
                    });
                    console.log('[DILP Tour] Prev button listener attached');
                }
                if (tourSkip) {
                    tourSkip.addEventListener('click', function(e) {
                        console.log('[DILP Tour] Skip button clicked');
                        e.preventDefault();
                        e.stopPropagation();
                        self.tour.end();
                    });
                    console.log('[DILP Tour] Skip button listener attached');
                }
            } catch (e) { console.warn('[DILP] Tour button wiring failed:', e); }

            // Handle URL success/error params for toast notifications
            try {
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('success') === 'created') {
                    self.toast.success('Created', 'Record has been created successfully.');
                }
                if (urlParams.get('deleted') === '1') {
                    self.toast.success('Deleted', 'Record has been deleted successfully.');
                }
                if (urlParams.get('timeout') === '1') {
                    self.toast.warning('Session Expired', 'You were logged out due to inactivity.');
                }
            } catch (e) { console.warn('[DILP] URL params handling failed:', e); }

            // Show loading on form submissions
            try {
                document.querySelectorAll('form').forEach(function(form) {
                    form.addEventListener('submit', function() {
                        if (form.checkValidity()) {
                            DILP.loading.show('Processing...');
                        }
                    });
                });
            } catch (e) { console.warn('[DILP] Form submit handler failed:', e); }
        }

        // Run immediately if DOM is already parsed, otherwise wait
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', _run);
        } else {
            _run();
        }
    }
};

// Auto-initialize
DILP.init();
</script>
