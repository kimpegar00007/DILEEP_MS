<script>
// Notification System
let notificationData = [];

function fetchNotifications() {
    fetch('api/get-notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                notificationData = data.notifications;
                updateNotificationUI(data.notifications, data.count);
            } else {
                console.error('Failed to fetch notifications:', data.error);
                showNotificationError();
            }
        })
        .catch(error => {
            console.error('Error fetching notifications:', error);
            showNotificationError();
        });
}

function updateNotificationUI(notifications, count) {
    const notificationCount = document.getElementById('notificationCount');
    const notificationList = document.getElementById('notificationList');
    
    if (count > 0) {
        notificationCount.textContent = count > 99 ? '99+' : count;
        notificationCount.style.display = 'inline-block';
    } else {
        notificationCount.style.display = 'none';
    }
    
    if (notifications.length === 0) {
        notificationList.innerHTML = `
            <div class="text-center py-4 text-muted">
                <i class="bi bi-check-circle fs-1"></i>
                <p class="mb-0 mt-2">No notifications</p>
                <small>You're all caught up!</small>
            </div>
        `;
    } else {
        let html = '';
        notifications.forEach(notification => {
            html += createNotificationHTML(notification);
        });
        notificationList.innerHTML = html;
    }
}

function createNotificationHTML(notification) {
    const severityClass = `severity-${notification.severity}`;
    let severityBadge = 'bg-secondary';
    if (notification.severity === 'danger') {
        severityBadge = 'bg-danger';
    } else if (notification.severity === 'warning') {
        severityBadge = 'bg-warning text-dark';
    } else if (notification.severity === 'info') {
        severityBadge = 'bg-info text-dark';
    }
    
    let actionButtons = '';
    if (notification.type === 'liquidation' || notification.type === 'upcoming_liquidation') {
        actionButtons = `
            <div class="notification-actions">
                <a href="proponent-view.php?id=${notification.proponent_id}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye"></i> View Details
                </a>
            </div>
        `;
    } else if (notification.type === 'fieldwork') {
        actionButtons = `
            <div class="notification-actions">
                <a href="fieldwork-schedule.php" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-calendar-event"></i> View Schedule
                </a>
            </div>
        `;
    } else if (notification.type === 'monitoring' || notification.type === 'upcoming_monitoring') {
        if (notification.beneficiary_id) {
            actionButtons = `
                <div class="notification-actions">
                    <a href="beneficiary-view.php?id=${notification.beneficiary_id}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye"></i> View Details
                    </a>
                </div>
            `;
        } else if (notification.proponent_id) {
            actionButtons = `
                <div class="notification-actions">
                    <a href="proponent-view.php?id=${notification.proponent_id}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye"></i> View Details
                    </a>
                </div>
            `;
        }
    }
    
    return `
        <div class="notification-item ${severityClass}">
            <div class="notification-title">
                <span class="badge ${severityBadge} me-2">${notification.title}</span>
            </div>
            <div class="notification-message">${notification.message}</div>
            <div class="notification-details">
                <i class="bi bi-clock"></i> ${notification.details}
            </div>
            ${actionButtons}
        </div>
    `;
}

function showNotificationError() {
    const notificationList = document.getElementById('notificationList');
    notificationList.innerHTML = `
        <div class="text-center py-4 text-danger">
            <i class="bi bi-exclamation-triangle fs-1"></i>
            <p class="mb-0 mt-2">Failed to load notifications</p>
            <button class="btn btn-sm btn-link" onclick="fetchNotifications()">Retry</button>
        </div>
    `;
}

// Fetch notifications on page load
document.addEventListener('DOMContentLoaded', function() {
    fetchNotifications();
    
    // Refresh notifications every 5 minutes
    setInterval(fetchNotifications, 5 * 60 * 1000);
});

// Refresh notifications when dropdown is opened
const notificationBellElement = document.getElementById('notificationBell');
if (notificationBellElement) {
    notificationBellElement.addEventListener('click', function() {
        fetchNotifications();
    });
}
</script>
