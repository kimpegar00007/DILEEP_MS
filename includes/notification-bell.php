<!-- Notification Bell Component -->
<li class="nav-item dropdown">
    <a class="nav-link position-relative" href="#" role="button" data-bs-toggle="dropdown" data-bs-auto-close="outside"
       aria-expanded="false" aria-haspopup="true" aria-controls="notificationPanel" id="notificationBell" aria-label="Notifications">
        <i class="bi bi-bell-fill fs-5" aria-hidden="true"></i>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationCount" style="display: none;" aria-hidden="true">
            0
        </span>
    </a>
    <div class="dropdown-menu dropdown-menu-end notification-dropdown" id="notificationPanel" aria-labelledby="notificationBell">
        <div class="notification-header">
            <div>
                <h6 class="mb-0">Notifications</h6>
                <small class="text-muted" id="notificationUpdated"></small>
            </div>
            <button type="button" class="btn btn-sm btn-link notification-refresh" id="notificationRefresh" title="Refresh" aria-label="Refresh notifications">
                <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
            </button>
        </div>
        <div class="notification-filters" id="notificationFilters" role="tablist" aria-label="Filter notifications" style="display: none;"></div>
        <div id="notificationBanner"></div>
        <div id="notificationList" class="notification-list">
            <div class="text-center py-4 text-muted">
                <div class="spinner-border spinner-border-sm" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mb-0 mt-2">Loading notifications...</p>
            </div>
        </div>
        <div id="notificationFooter"></div>
    </div>
    <span class="visually-hidden" id="notificationLive" role="status" aria-live="polite"></span>
</li>
