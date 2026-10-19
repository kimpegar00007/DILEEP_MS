<style>
/* ---- Bell + panel ---- */
.navbar-bell .dropdown-menu.notification-dropdown {
    position: absolute;
    width: min(420px, calc(100vw - 1rem));
    max-height: min(560px, calc(100vh - 5rem));
    padding: 0;
    overflow: hidden;
    background: rgba(255, 255, 255, 0.97);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(27, 122, 61, 0.12);
    border-radius: 0.75rem;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.navbar-bell .dropdown-menu.notification-dropdown.show {
    display: flex;
    flex-direction: column;
}

/* Below lg the bell is next to the menu toggler: anchor the panel to the navbar, not the bell,
   so it can use the full phone width */
@media (max-width: 991.98px) {
    .navbar-bell .nav-item.dropdown { position: static; }
    .navbar-bell .dropdown-menu.notification-dropdown { right: 0.5rem; left: auto; }
}

#notificationBell .badge { font-size: 0.65rem; }

.notification-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1rem 0.5rem;
}

.notification-header h6 { font-weight: 700; }
.notification-refresh { color: #1B7A3D; padding: 0.25rem 0.5rem; }
.notification-refresh.spinning i { animation: notification-spin 0.8s linear infinite; }
@keyframes notification-spin { to { transform: rotate(360deg); } }
@media (prefers-reduced-motion: reduce) { .notification-refresh.spinning i { animation: none; } }

/* ---- Filters ---- */
.notification-filters {
    display: flex;
    gap: 0.4rem;
    padding: 0 1rem 0.6rem;
    border-bottom: 1px solid #e9ecef;
    flex-wrap: wrap;
}

.notification-filter {
    border: 1px solid #ced4da;
    background: #fff;
    color: #495057;
    border-radius: 999px;
    font-size: 0.78rem;
    padding: 0.15rem 0.7rem;
    cursor: pointer;
}

.notification-filter:hover { background: rgba(27, 122, 61, 0.06); }
.notification-filter.active { background: #1B7A3D; border-color: #1B7A3D; color: #fff; }
.notification-filter:focus-visible { outline: 2px solid #1B7A3D; outline-offset: 2px; }

/* ---- List ---- */
.notification-list { overflow-y: auto; flex: 1 1 auto; overscroll-behavior: contain; }

.notification-banner {
    font-size: 0.8rem;
    padding: 0.5rem 1rem;
    background: #fff3cd;
    color: #664d03;
    border-bottom: 1px solid #ffecb5;
}

.notification-banner button { font-size: 0.8rem; padding: 0; vertical-align: baseline; }

.notification-item {
    display: flex;
    gap: 0.75rem;
    align-items: flex-start;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #e9ecef;
    border-left: 4px solid transparent;
    color: inherit;
    text-decoration: none;
    transition: background-color 0.2s;
}

.notification-item:hover,
.notification-item:focus-visible {
    background-color: rgba(27, 122, 61, 0.06);
    color: inherit;
}

.notification-item:focus-visible { outline: 2px solid #1B7A3D; outline-offset: -2px; }
.notification-item:last-child { border-bottom: none; }

.notification-item.severity-danger  { border-left-color: #dc3545; }
.notification-item.severity-warning { border-left-color: #f0a500; }
.notification-item.severity-info    { border-left-color: #0aa2c0; }

.notification-icon { font-size: 1.1rem; line-height: 1.4; flex: 0 0 auto; }
.severity-danger .notification-icon  { color: #dc3545; }
.severity-warning .notification-icon { color: #b97f00; }
.severity-info .notification-icon    { color: #0a7f96; }

.notification-body { min-width: 0; flex: 1 1 auto; }

.notification-subject {
    font-weight: 600;
    font-size: 0.9rem;
    color: #212529;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.notification-project {
    font-size: 0.8rem;
    color: #495057;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.notification-meta { font-size: 0.75rem; color: #6c757d; margin-top: 0.1rem; }

.notification-chip {
    flex: 0 0 auto;
    font-size: 0.72rem;
    font-weight: 600;
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
    white-space: nowrap;
}

.severity-danger .notification-chip  { background: #f8d7da; color: #842029; }
.severity-warning .notification-chip { background: #fff3cd; color: #664d03; }
.severity-info .notification-chip    { background: #cff4fc; color: #055160; }

.notification-footer {
    padding: 0.5rem 1rem;
    font-size: 0.75rem;
    color: #6c757d;
    border-top: 1px solid #e9ecef;
    background: rgba(248, 249, 250, 0.9);
}

.notification-footer a { color: #1B7A3D; font-weight: 600; }
</style>
