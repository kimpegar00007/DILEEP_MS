<link rel="icon" href="assets/dileep-logo.ico" type="image/x-icon">
<style>
    :root {
        --dole-primary: #1B7A3D;
        --dole-secondary: #145A2C;
        --dole-accent: #D4A017;
        --dole-accent-light: #F0B429;
        --dole-success: #28a745;
        --dole-warning: #ffc107;
        --dole-danger: #dc3545;
        --dole-info: #1E6BB8;
        --dole-light: #f8f9fa;
        --dole-dark: #1a2e1a;
        --dole-body-bg: #f5f7f5;
        --dole-font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        --dole-border-radius: 10px;
        --dole-box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        --dole-transition: all 0.2s ease;
    }

    body {
        background-color: var(--dole-body-bg);
        font-family: var(--dole-font-family);
        font-size: 1rem;
        line-height: 1.6;
        color: #212529;
    }

    /* Navbar – Glass Morphism Header */
    .navbar-glass {
        background: linear-gradient(135deg, rgba(21, 90, 44, 0.92) 0%, rgba(27, 122, 61, 0.88) 50%, rgba(30, 107, 55, 0.92) 100%);
        backdrop-filter: blur(20px) saturate(180%);
        -webkit-backdrop-filter: blur(20px) saturate(180%);
        box-shadow:
            0 4px 20px rgba(21, 90, 44, 0.15),
            inset 0 -1px 0 rgba(255, 255, 255, 0.10);
        border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        position: relative;
        z-index: 1030;
        padding: 0.5rem 1rem;
    }

    .navbar-logo {
        height: 40px;
        margin-right: 0.75rem;
        filter: drop-shadow(0 2px 6px rgba(0, 0, 0, 0.15));
        transition: transform 0.25s ease;
    }
    .navbar-logo:hover { transform: scale(1.05); }

    .navbar-brand-text {
        font-weight: 700;
        font-size: 1.15rem;
        color: #fff;
        letter-spacing: -0.01em;
        line-height: 1.2;
    }

    .navbar-brand-sub {
        display: block;
        font-size: 0.72rem;
        font-weight: 400;
        color: rgba(255, 255, 255, 0.70);
        letter-spacing: 0.02em;
    }

    .navbar-glass .navbar-brand { font-weight: 600; font-size: 1.25rem; }

    .navbar-glass .nav-link {
        color: rgba(255, 255, 255, 0.85) !important;
        font-size: 0.9rem;
        font-weight: 450;
        padding: 0.5rem 0.75rem;
        border-radius: 0.5rem;
        transition: all 0.25s ease;
    }

    .navbar-glass .nav-link:hover {
        color: #fff !important;
        background: rgba(255, 255, 255, 0.12);
    }

    .navbar-user-link {
        display: flex !important;
        align-items: center;
        gap: 0.5rem;
    }

    .navbar-avatar {
        font-size: 1.2rem;
        opacity: 0.9;
    }

    .navbar-username {
        font-weight: 500;
    }

    .navbar-dropdown {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(27, 122, 61, 0.12);
        border-radius: 0.75rem;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        padding: 0.5rem;
        margin-top: 0.5rem;
    }

    .navbar-dropdown .dropdown-item {
        border-radius: 0.4rem;
        padding: 0.5rem 0.75rem;
        font-size: 0.88rem;
        color: #374151;
        transition: all 0.2s ease;
    }

    .navbar-dropdown .dropdown-item:hover {
        background: rgba(27, 122, 61, 0.08);
        color: var(--dole-primary);
    }

    .navbar-dropdown .dropdown-item i {
        margin-right: 0.5rem;
        width: 1rem;
        text-align: center;
    }

    .navbar-dropdown .dropdown-item-logout:hover {
        background: rgba(220, 53, 69, 0.08);
        color: var(--dole-danger);
    }

    .navbar-dropdown .dropdown-divider {
        border-color: rgba(0, 0, 0, 0.06);
        margin: 0.25rem 0;
    }

    .navbar-accent-bar {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--dole-accent) 0%, var(--dole-accent-light) 40%, rgba(30, 107, 184, 0.50) 70%, var(--dole-primary) 100%);
    }

    .navbar-glass .navbar-toggler {
        border-color: rgba(255, 255, 255, 0.25);
        padding: 0.35rem 0.6rem;
    }

    .navbar-glass .navbar-toggler-icon {
        filter: brightness(2);
    }

    /* Sidebar – Glass Morphism */
    .sidebar {
        background: linear-gradient(180deg, rgba(235, 245, 238, 0.92) 0%, rgba(228, 242, 232, 0.88) 50%, rgba(232, 240, 235, 0.92) 100%);
        backdrop-filter: blur(20px) saturate(160%);
        -webkit-backdrop-filter: blur(20px) saturate(160%);
        min-height: calc(100vh - 56px);
        box-shadow: 2px 0 12px rgba(27, 122, 61, 0.06);
        border-right: 1px solid rgba(255, 255, 255, 0.45);
        position: relative;
        overflow: hidden;
    }

    .sidebar-inner {
        top: 1rem;
    }

    .sidebar-brand {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.25rem 0.5rem 1rem;
        margin-bottom: 0.25rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.35);
    }

    .sidebar-brand-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 0.5rem;
        background: rgba(27, 122, 61, 0.10);
        color: var(--dole-primary);
        font-size: 0.95rem;
    }

    .sidebar-brand-text {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: rgba(55, 65, 81, 0.60);
    }

    .sidebar-divider {
        height: 1px;
        background: rgba(255, 255, 255, 0.35);
        margin: 0.5rem 0;
        list-style: none;
    }

    .sidebar-section-label {
        font-size: 0.68rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: rgba(55, 65, 81, 0.50);
        padding: 0.5rem 1rem 0.25rem;
        list-style: none;
    }

    .sidebar .nav-link {
        color: #374151;
        padding: 0.65rem 1rem;
        border-radius: 0.6rem;
        margin-bottom: 0.15rem;
        font-size: 0.9rem;
        font-weight: 450;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        text-decoration: none;
    }

    .sidebar .nav-link:hover {
        background: rgba(255, 255, 255, 0.45);
        color: var(--dole-primary);
        box-shadow: 0 2px 8px rgba(27, 122, 61, 0.08);
        transform: translateX(2px);
    }

    .sidebar .nav-link.active {
        background: rgba(27, 122, 61, 0.12);
        color: var(--dole-primary);
        font-weight: 600;
        box-shadow:
            0 2px 12px rgba(27, 122, 61, 0.12),
            inset 0 1px 0 rgba(255, 255, 255, 0.50);
    }

    .sidebar .nav-link.active::before {
        content: '';
        position: absolute;
        left: -12px;
        top: 50%;
        transform: translateY(-50%);
        width: 4px;
        height: 60%;
        border-radius: 0 4px 4px 0;
        background: linear-gradient(180deg, var(--dole-secondary), var(--dole-primary));
    }

    .sidebar .nav-link i {
        font-size: 1.05rem;
        width: 1.25rem;
        text-align: center;
        flex-shrink: 0;
    }

    .sidebar-accent {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, rgba(27, 122, 61, 0.35), rgba(212, 160, 23, 0.30), rgba(30, 107, 184, 0.30));
        border-radius: 3px 3px 0 0;
    }

    /* Cards */
    .card {
        border-radius: var(--dole-border-radius);
        box-shadow: var(--dole-box-shadow);
        border: none;
    }
    .stat-card { transition: transform 0.2s; }
    .stat-card:hover { transform: translateY(-5px); }
    .stat-card .card-body { padding: 1.25rem; }
    .stat-text-wrap {
        min-width: 0;
        overflow: hidden;
        flex: 1;
        margin-right: 0.5rem;
    }
    .stat-text-wrap .card-title {
        font-size: 0.7rem;
        letter-spacing: 0.5px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .stat-number {
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1.2;
        word-break: break-word;
    }
    .stat-detail {
        font-size: 0.75rem;
        opacity: 0.85;
        display: block;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .stat-icon { font-size: 2rem; opacity: 0.7; }

    /* Buttons - Consistent Styling */
    .btn {
        border-radius: 6px;
        font-weight: 500;
        transition: var(--dole-transition);
        letter-spacing: 0.25px;
    }
    .btn:focus-visible {
        outline: 3px solid var(--dole-primary);
        outline-offset: 2px;
    }
    .btn-primary {
        background: linear-gradient(135deg, var(--dole-secondary), var(--dole-primary));
        border: none;
    }
    .btn-primary:hover {
        background: linear-gradient(135deg, #0e4420, #167035);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(27, 122, 61, 0.25);
    }
    .action-btn {
        padding: 0.25rem 0.5rem;
        margin: 0 0.125rem;
    }
    
    .action-buttons-container {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        white-space: nowrap;
    }

    /* Badge Consistency */
    .badge-status {
        padding: 0.5rem 0.75rem;
        font-weight: 500;
        font-size: 0.8rem;
    }

    /* Form Sections */
    .form-section {
        background-color: var(--dole-light);
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }
    .form-section h5 {
        color: var(--dole-primary);
        margin-bottom: 1rem;
    }

    /* Form Controls - Accessibility */
    .form-control:focus, .form-select:focus {
        border-color: var(--dole-primary);
        box-shadow: 0 0 0 0.2rem rgba(27, 122, 61, 0.20);
    }
    .form-label {
        font-weight: 500;
        font-size: 0.9rem;
        margin-bottom: 0.35rem;
    }

    /* Filter Cards */
    .filters-card { background-color: var(--dole-light); }

    /* Alerts */
    .alert { border-radius: var(--dole-border-radius); }

    /* Footer */
    .site-footer {
        text-align: center;
        color: #6c757d;
        background-color: var(--dole-light);
        padding: 0.75rem;
        border-radius: 0 0 15px 15px;
        font-size: 0.85rem;
    }

    /* Tooltips - Part 1 */
    .help-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background-color: var(--dole-primary);
        color: white;
        font-size: 0.65rem;
        cursor: help;
        margin-left: 4px;
        vertical-align: middle;
        transition: var(--dole-transition);
    }
    .help-icon:hover {
        background-color: var(--dole-secondary);
        transform: scale(1.15);
    }

    /* Toast Notifications - Part 3 */
    .toast-container {
        position: fixed;
        top: 70px;
        right: 20px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .dilp-toast {
        min-width: 320px;
        max-width: 420px;
        padding: 1rem 1.25rem;
        border-radius: 8px;
        color: white;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        animation: slideInRight 0.35s ease-out;
        opacity: 1;
        transition: opacity 0.3s ease, transform 0.3s ease;
    }
    .dilp-toast.toast-hiding {
        opacity: 0;
        transform: translateX(100%);
    }
    .dilp-toast.toast-success { background: linear-gradient(135deg, #1e7e34, #28a745); }
    .dilp-toast.toast-error { background: linear-gradient(135deg, #bd2130, #dc3545); }
    .dilp-toast.toast-warning { background: linear-gradient(135deg, #d39e00, #ffc107); color: #212529; }
    .dilp-toast.toast-info { background: linear-gradient(135deg, #145A2C, #1B7A3D); }
    .dilp-toast-icon { font-size: 1.25rem; flex-shrink: 0; margin-top: 1px; }
    .dilp-toast-body { flex: 1; }
    .dilp-toast-title { font-weight: 600; font-size: 0.95rem; margin-bottom: 2px; }
    .dilp-toast-message { font-size: 0.85rem; opacity: 0.9; }
    .dilp-toast-close {
        background: none; border: none; color: inherit; opacity: 0.7;
        cursor: pointer; font-size: 1.1rem; padding: 0; line-height: 1;
        flex-shrink: 0;
    }
    .dilp-toast-close:hover { opacity: 1; }
    .dilp-toast-undo {
        background: rgba(255,255,255,0.2);
        border: 1px solid rgba(255,255,255,0.4);
        color: inherit;
        padding: 0.2rem 0.6rem;
        border-radius: 4px;
        font-size: 0.8rem;
        cursor: pointer;
        margin-top: 0.35rem;
        display: inline-block;
        transition: var(--dole-transition);
    }
    .dilp-toast-undo:hover { background: rgba(255,255,255,0.35); }

    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }

    /* Loading Overlay - Part 5 */
    .loading-overlay {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(255,255,255,0.8);
        z-index: 10000;
        display: none;
        align-items: center;
        justify-content: center;
        flex-direction: column;
    }
    .loading-overlay.active { display: flex; }
    .loading-spinner {
        width: 48px; height: 48px;
        border: 4px solid #e9ecef;
        border-top: 4px solid var(--dole-primary);
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }
    .loading-text {
        margin-top: 1rem;
        color: var(--dole-secondary);
        font-weight: 500;
        font-size: 0.95rem;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* Inline Loading for Tables/Cards */
    .inline-loader {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        color: #6c757d;
        gap: 0.5rem;
    }

    /* Validation Styles - Part 2 */
    .form-control.is-validating {
        border-color: var(--dole-warning);
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23ffc107'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linecap='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23ffc107' stroke='none'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right calc(0.375em + 0.1875rem) center;
        background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
    }
    .validation-feedback {
        font-size: 0.8rem;
        margin-top: 0.25rem;
        display: none;
    }
    .validation-feedback.show { display: block; }
    .validation-feedback.valid { color: var(--dole-success); }
    .validation-feedback.invalid { color: var(--dole-danger); }

    /* Password Strength - Part 8 */
    .password-strength { margin-top: 0.5rem; }
    .strength-bar {
        height: 5px;
        background-color: #e9ecef;
        border-radius: 3px;
        overflow: hidden;
    }
    .strength-fill {
        height: 100%;
        width: 0%;
        transition: width 0.3s ease, background-color 0.3s ease;
    }
    .strength-fill.weak { width: 25%; background-color: var(--dole-danger); }
    .strength-fill.fair { width: 50%; background-color: #fd7e14; }
    .strength-fill.good { width: 75%; background-color: var(--dole-warning); }
    .strength-fill.strong { width: 100%; background-color: var(--dole-success); }
    .strength-text { font-size: 0.8rem; margin-top: 0.25rem; }
    .strength-requirements {
        font-size: 0.8rem;
        margin-top: 0.5rem;
        padding: 0.5rem;
        background: var(--dole-light);
        border-radius: 4px;
    }
    .strength-requirements li {
        margin-bottom: 0.15rem;
        list-style: none;
        padding-left: 1.25rem;
        position: relative;
    }
    .strength-requirements li::before {
        content: '\2717';
        position: absolute;
        left: 0;
        color: var(--dole-danger);
        font-weight: bold;
    }
    .strength-requirements li.met::before {
        content: '\2713';
        color: var(--dole-success);
    }

    /* Session Timeout Warning - Part 8 */
    .session-warning-modal .modal-header {
        background: linear-gradient(135deg, var(--dole-warning), #e0a800);
        color: #212529;
    }

    /* Onboarding Tour - Part 1 */
    .tour-overlay {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 10001;
        display: none;
        pointer-events: none;
    }
    .tour-overlay.active { display: block; }
    .tour-highlight {
        position: fixed;
        box-shadow: 0 0 0 4px var(--dole-primary), 0 0 0 9999px rgba(0,0,0,0.4);
        border-radius: 8px;
        z-index: 10002;
        transition: all 0.4s ease;
        pointer-events: none;
    }
    .tour-tooltip {
        position: fixed;
        background: white;
        border-radius: 10px;
        padding: 1.25rem;
        min-width: 280px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.3);
        z-index: 10003;
        pointer-events: auto;
    }
    .tour-tooltip .btn { 
        position: relative; 
        z-index: 10004;
        pointer-events: auto;
        cursor: pointer;
    }
    .tour-tooltip h6 { color: var(--dole-primary); margin-bottom: 0.5rem; }
    .tour-tooltip p { font-size: 0.9rem; color: #495057; margin-bottom: 1rem; }
    .tour-progress {
        display: flex;
        gap: 4px;
        margin-bottom: 0.75rem;
    }
    .tour-progress-dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        background: #dee2e6;
    }
    .tour-progress-dot.active { background: var(--dole-primary); }
    .tour-progress-dot.completed { background: var(--dole-success); }
    .tour-nav { 
        display: flex; 
        justify-content: space-between; 
        gap: 0.5rem;
        pointer-events: auto;
    }

    /* Skip to Content - Part 4 Accessibility */
    .skip-to-content {
        position: absolute;
        top: -100%;
        left: 0;
        background: var(--dole-primary);
        color: white;
        padding: 0.75rem 1.5rem;
        z-index: 10010;
        font-weight: 600;
        text-decoration: none;
        border-radius: 0 0 8px 0;
        transition: top 0.2s;
    }
    .skip-to-content:focus {
        top: 0;
        color: white;
    }

    /* Focus Visible for Accessibility */
    a:focus-visible, button:focus-visible, input:focus-visible,
    select:focus-visible, textarea:focus-visible, [tabindex]:focus-visible {
        outline: 3px solid var(--dole-primary);
        outline-offset: 2px;
    }

    /* High Contrast Adjustments - Part 4 */
    .text-muted { color: #5a6268 !important; }

    /* Print Styles */
    @media print {
        .no-print, .sidebar, .navbar, .toast-container,
        .loading-overlay, .tour-overlay { display: none !important; }
        main { margin: 0 !important; padding: 1rem !important; }
    }

    /* Responsive */
    @media (max-width: 1200px) {
        .stat-number { font-size: 1.25rem; }
        .stat-icon { font-size: 1.75rem; }
    }
    @media (max-width: 768px) {
        .sidebar { min-height: auto; }
        .dilp-toast { min-width: 280px; max-width: 95vw; }
        .stat-number { font-size: 1.5rem; }
        .stat-icon { font-size: 2rem; }
    }
    @media (max-width: 576px) {
        .stat-number { font-size: 1.25rem; }
        .stat-icon { font-size: 1.5rem; }
        .stat-card .card-body { padding: 1rem; }
    }
</style>
