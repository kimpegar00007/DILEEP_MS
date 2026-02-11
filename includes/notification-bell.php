<!-- Notification Bell Component -->
<li class="nav-item dropdown me-3">
    <a class="nav-link position-relative" href="#" role="button" data-bs-toggle="dropdown" id="notificationBell">
        <i class="bi bi-bell-fill fs-5"></i>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationCount" style="display: none;">
            0
        </span>
    </a>
    <div class="dropdown-menu dropdown-menu-end notification-dropdown" style="width: 400px; max-height: 500px; overflow-y: auto;">
        <div class="dropdown-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Notifications</h6>
            <button class="btn btn-sm btn-link text-decoration-none" id="markAllRead" style="display: none;">Mark all as read</button>
        </div>
        <div class="dropdown-divider"></div>
        <div id="notificationList">
            <div class="text-center py-4 text-muted">
                <div class="spinner-border spinner-border-sm" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mb-0 mt-2">Loading notifications...</p>
            </div>
        </div>
    </div>
</li>
