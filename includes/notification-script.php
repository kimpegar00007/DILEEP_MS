<script>
// Notification System
let notificationData = [];
let notificationMeta = { count: 0, totals: { overdue: 0, upcoming: 0 }, truncated: false };
let notificationFilter = 'all';          // all | overdue | upcoming
let notificationLoadedAt = 0;            // ms timestamp of the last successful load
let notificationRenderKey = '';          // skip re-rendering when nothing changed (keeps scroll position)
let notificationFetching = false;
let notificationLastCount = null;

const NOTIFICATION_POLL_MS = 5 * 60 * 1000;
const NOTIFICATION_STALE_MS = 30 * 1000;

const NOTIFICATION_ICONS = {
    liquidation: 'bi-cash-coin',
    monitoring: 'bi-clipboard-check',
    fieldwork: 'bi-calendar-event'
};

// Notification text carries user-entered names (proponents, beneficiaries,
// fieldwork titles), so escape everything before it goes into innerHTML.
function escapeNotificationHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function notificationEl(id) {
    return document.getElementById(id);
}

function fetchNotifications(force) {
    if (notificationFetching) return Promise.resolve();
    if (!force && Date.now() - notificationLoadedAt < NOTIFICATION_STALE_MS) return Promise.resolve();

    notificationFetching = true;
    const refreshBtn = notificationEl('notificationRefresh');
    if (refreshBtn) refreshBtn.classList.add('spinning');

    return fetch('api/get-notifications.php', {
        credentials: 'same-origin',
        cache: 'no-store',
        headers: { 'Accept': 'application/json' }
    })
        .then(response => response.text().then(text => {
            let data = null;
            try { data = JSON.parse(text); } catch (e) { /* not JSON, e.g. a login page */ }
            if (response.status === 401 || (data && data.code === 'session_expired')) {
                throw { code: 'session_expired' };
            }
            if (response.status === 503 || (data && data.code === 'maintenance')) {
                throw { code: 'maintenance' };
            }
            if (!response.ok || !data || !data.success) {
                throw { code: 'failed', message: data && data.error };
            }
            return data;
        }))
        .then(data => {
            notificationData = data.notifications || [];
            notificationMeta = {
                count: data.count ?? notificationData.length,
                totals: data.totals || { overdue: 0, upcoming: 0 },
                truncated: !!data.truncated
            };
            notificationLoadedAt = Date.now();
            hideNotificationBanner();
            updateNotificationUI();
        })
        .catch(error => {
            console.error('Error fetching notifications:', error);
            showNotificationError(error && error.code);
        })
        .finally(() => {
            notificationFetching = false;
            if (refreshBtn) refreshBtn.classList.remove('spinning');
        });
}

function notificationTimeAgo() {
    if (!notificationLoadedAt) return '';
    const secs = Math.round((Date.now() - notificationLoadedAt) / 1000);
    if (secs < 45) return 'Updated just now';
    const mins = Math.round(secs / 60);
    return 'Updated ' + mins + ' min ago';
}

function updateNotificationTime() {
    const el = notificationEl('notificationUpdated');
    if (el) el.textContent = notificationTimeAgo();
}

function updateNotificationBadge() {
    const badge = notificationEl('notificationCount');
    const bell = notificationEl('notificationBell');
    const count = notificationMeta.count;

    if (count > 0) {
        badge.textContent = count > 99 ? '99+' : count;
        badge.style.display = 'inline-block';
        // Red only when something is actually overdue, amber for "due soon" only
        badge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill ' +
            (notificationMeta.totals.overdue > 0 ? 'bg-danger' : 'bg-warning text-dark');
    } else {
        badge.style.display = 'none';
    }

    const label = count > 0
        ? 'Notifications, ' + count + (count === 1 ? ' alert' : ' alerts') +
          (notificationMeta.totals.overdue > 0 ? ', ' + notificationMeta.totals.overdue + ' overdue' : '')
        : 'Notifications, no alerts';
    bell.setAttribute('aria-label', label);

    // Tell screen-reader users when the number changes (not on the first load)
    if (notificationLastCount !== null && notificationLastCount !== count) {
        const live = notificationEl('notificationLive');
        if (live) live.textContent = label;
    }
    notificationLastCount = count;
}

function renderNotificationFilters() {
    const box = notificationEl('notificationFilters');
    if (notificationMeta.count === 0) {
        box.style.display = 'none';
        box.innerHTML = '';
        return;
    }
    const t = notificationMeta.totals;
    const filters = [
        ['all', 'All', notificationMeta.count],
        ['overdue', 'Overdue', t.overdue],
        ['upcoming', 'Due soon', t.upcoming]
    ];
    box.innerHTML = filters.map(([key, label, n]) =>
        `<button type="button" class="notification-filter${notificationFilter === key ? ' active' : ''}" role="tab"
                 aria-selected="${notificationFilter === key}" data-filter="${key}">${label} (${n})</button>`
    ).join('');
    box.style.display = 'flex';
}

function updateNotificationUI() {
    updateNotificationBadge();
    updateNotificationTime();
    renderNotificationFilters();

    const list = notificationEl('notificationList');
    const footer = notificationEl('notificationFooter');
    const visible = notificationData.filter(n => notificationFilter === 'all' || n.urgency === notificationFilter);

    // Rebuild the list only when something changed, so a background refresh does not
    // reset the scroll position of someone who is reading it.
    const key = notificationFilter + '|' + notificationMeta.count + '|' +
        visible.map(n => n.id + ':' + n.chip).join(',');
    if (key !== notificationRenderKey) {
        notificationRenderKey = key;
        const scrollTop = list.scrollTop;

        if (notificationData.length === 0) {
            list.innerHTML = `
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-check-circle fs-1" aria-hidden="true"></i>
                    <p class="mb-0 mt-2">No notifications</p>
                    <small>You're all caught up!</small>
                </div>`;
        } else if (visible.length === 0) {
            list.innerHTML = `<div class="text-center py-4 text-muted small">Nothing in this view.</div>`;
        } else {
            // Show the province on each row only when more than one province is in the list
            const showProvince = new Set(notificationData.map(n => n.province).filter(Boolean)).size > 1;
            list.innerHTML = visible.map(n => createNotificationHTML(n, showProvince)).join('');
        }
        list.scrollTop = scrollTop;
    }

    if (notificationMeta.truncated) {
        footer.innerHTML = `<div class="notification-footer">Showing the most urgent items of each type.
            ${notificationMeta.count} alerts in total.</div>`;
    } else {
        footer.innerHTML = '';
    }
}

function createNotificationHTML(notification, showProvince) {
    const severity = ['danger', 'warning', 'info'].includes(notification.severity) ? notification.severity : 'info';
    const icon = NOTIFICATION_ICONS[notification.category] || 'bi-bell';
    const href = notification.href || '#';

    const metaParts = [notification.title];
    if (showProvince && notification.province) metaParts.push(notification.province);

    return `
        <a href="${escapeNotificationHtml(href)}" class="notification-item severity-${severity}">
            <i class="bi ${icon} notification-icon" aria-hidden="true"></i>
            <div class="notification-body">
                <div class="notification-subject">${escapeNotificationHtml(notification.subject || notification.message)}</div>
                ${notification.project ? `<div class="notification-project">${escapeNotificationHtml(notification.project)}</div>` : ''}
                <div class="notification-meta">${escapeNotificationHtml(metaParts.join(' · '))}</div>
                <div class="notification-meta">${escapeNotificationHtml(notification.details)}</div>
            </div>
            <span class="notification-chip">${escapeNotificationHtml(notification.chip || '')}</span>
        </a>`;
}

function hideNotificationBanner() {
    notificationEl('notificationBanner').innerHTML = '';
}

function showNotificationError(code) {
    const message = code === 'session_expired' ? 'Your session has expired.'
        : code === 'maintenance' ? 'The system is under maintenance.'
        : "Couldn't refresh notifications.";
    const action = code === 'session_expired'
        ? '<a href="login.php" class="alert-link">Sign in again</a>'
        : '<button type="button" class="btn btn-link alert-link" data-notification-retry>Retry</button>';

    // Keep what we already have on screen; only show a full error when there is nothing to show
    if (notificationLoadedAt) {
        notificationEl('notificationBanner').innerHTML =
            `<div class="notification-banner" role="alert">${message} Showing the last results. ${action}</div>`;
        return;
    }
    notificationEl('notificationList').innerHTML = `
        <div class="text-center py-4 text-danger" role="alert">
            <i class="bi bi-exclamation-triangle fs-1" aria-hidden="true"></i>
            <p class="mb-0 mt-2">${message}</p>
            ${action}
        </div>`;
    notificationRenderKey = '';
}

document.addEventListener('DOMContentLoaded', function() {
    const bell = notificationEl('notificationBell');
    if (!bell) return;
    const dropdown = bell.closest('.dropdown');

    fetchNotifications(true);

    // Refresh every 5 minutes, but not while the tab is in the background
    setInterval(function() {
        if (!document.hidden) fetchNotifications(true);
    }, NOTIFICATION_POLL_MS);

    // Catch up when the user comes back to the tab
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden && Date.now() - notificationLoadedAt > 60 * 1000) fetchNotifications(true);
    });

    // Refresh when the panel opens (not on the click that closes it), only if the data is stale
    dropdown.addEventListener('show.bs.dropdown', function() {
        updateNotificationTime();
        fetchNotifications(false);
    });

    notificationEl('notificationRefresh').addEventListener('click', function(e) {
        e.stopPropagation();
        fetchNotifications(true);
    });

    notificationEl('notificationFilters').addEventListener('click', function(e) {
        const btn = e.target.closest('[data-filter]');
        if (!btn) return;
        notificationFilter = btn.dataset.filter;
        updateNotificationUI();
    });

    // Retry button in the error states
    notificationEl('notificationPanel').addEventListener('click', function(e) {
        if (e.target.closest('[data-notification-retry]')) fetchNotifications(true);
    });

    // Keep "Updated N min ago" honest while the panel is open
    setInterval(function() {
        if (notificationEl('notificationPanel').classList.contains('show')) updateNotificationTime();
    }, 30 * 1000);
});
</script>
