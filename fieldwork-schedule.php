<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';
require_once 'models/FieldworkSchedule.php';

$auth = new Auth();
$auth->requireLogin();

$model = new FieldworkSchedule();
$model->autoUpdateStatuses();

$stats = $model->getStatistics();
$users = $model->getAllUsers();
$overdueActivities = $model->getOverdueActivities();
$userRole = $_SESSION['role'] ?? 'user';
$canEdit = in_array($userRole, ['admin', 'encoder']);

// Philippine holidays (static list for calendar display)
$philippineHolidays = json_encode([
    ['title' => 'New Year\'s Day',             'date' => date('Y') . '-01-01'],
    ['title' => 'EDSA People Power Anniversary','date' => date('Y') . '-02-25'],
    ['title' => 'Araw ng Kagitingan',          'date' => date('Y') . '-04-09'],
    ['title' => 'Maundy Thursday',             'date' => date('Y') . '-04-17'],
    ['title' => 'Good Friday',                 'date' => date('Y') . '-04-18'],
    ['title' => 'Black Saturday',              'date' => date('Y') . '-04-19'],
    ['title' => 'Labor Day',                   'date' => date('Y') . '-05-01'],
    ['title' => 'Independence Day',            'date' => date('Y') . '-06-12'],
    ['title' => 'Ninoy Aquino Day',            'date' => date('Y') . '-08-21'],
    ['title' => 'National Heroes Day',         'date' => date('Y') . '-08-25'],
    ['title' => 'Bonifacio Day',               'date' => date('Y') . '-11-30'],
    ['title' => 'Christmas Day',               'date' => date('Y') . '-12-25'],
    ['title' => 'Rizal Day',                   'date' => date('Y') . '-12-30'],
    ['title' => 'Last Day of the Year',        'date' => date('Y') . '-12-31'],
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule of Activities / Fieldwork - DILEEP Monitoring System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <?php include 'includes/shared-styles.php'; ?>
    <?php include 'includes/notification-styles.php'; ?>
    <style>
        /* Calendar Styles */
        .fc {
            font-family: var(--dole-font-family);
        }
        .fc .fc-toolbar-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dole-dark);
        }
        .fc .fc-button-primary {
            background: linear-gradient(135deg, var(--dole-secondary), var(--dole-primary));
            border: none;
            font-size: 0.85rem;
            font-weight: 500;
            border-radius: 6px;
            padding: 0.4rem 0.75rem;
            transition: var(--dole-transition);
        }
        .fc .fc-button-primary:hover {
            background: linear-gradient(135deg, #0e4420, #167035);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(27, 122, 61, 0.25);
        }
        .fc .fc-button-primary:disabled {
            opacity: 0.65;
        }
        .fc .fc-button-primary:not(:disabled).fc-button-active {
            background: var(--dole-secondary);
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.2);
        }
        .fc .fc-daygrid-day.fc-day-today {
            background-color: rgba(27, 122, 61, 0.06);
        }
        .fc .fc-daygrid-day-number {
            font-weight: 500;
            color: #374151;
            padding: 0.4rem;
        }
        .fc .fc-event {
            border-radius: 4px;
            padding: 2px 6px;
            font-size: 0.78rem;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .fc .fc-event:hover {
            transform: scale(1.02);
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .fc .fc-col-header-cell-cushion {
            font-weight: 600;
            color: var(--dole-secondary);
            font-size: 0.85rem;
        }
        .fc .fc-scrollgrid {
            border-radius: var(--dole-border-radius);
            overflow: hidden;
        }
        .holiday-event,
        .fc .fc-event.holiday-event,
        .fc .fc-daygrid-event.holiday-event {
            background-color: rgba(220, 53, 69, 0.12) !important;
            border-left: 3px solid #dc3545 !important;
            font-style: italic;
            font-size: 0.72rem !important;
        }
        .holiday-event .fc-event-title,
        .holiday-event .fc-event-main,
        .fc .fc-event.holiday-event .fc-event-title,
        .fc .fc-event.holiday-event .fc-event-main {
            color: #b02a37 !important;
        }

        /* Status Legend */
        .status-legend {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            padding: 0.75rem 1rem;
        }
        .status-legend-item {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.82rem;
            font-weight: 500;
        }
        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .status-dot.pending   { background-color: #ffc107; }
        .status-dot.ongoing   { background-color: #0d6efd; }
        .status-dot.completed { background-color: #198754; }
        .status-dot.missed    { background-color: #dc3545; }
        .status-dot.holiday   { background-color: #dc3545; opacity: 0.3; }

        /* Table View */
        .table-fieldwork th {
            font-size: 0.82rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: var(--dole-secondary);
            border-bottom-width: 2px;
        }
        .table-fieldwork td {
            font-size: 0.88rem;
            vertical-align: middle;
        }
        .badge-fieldwork {
            padding: 0.35rem 0.65rem;
            font-weight: 500;
            font-size: 0.76rem;
            border-radius: 20px;
        }

        /* View Toggle */
        .view-toggle .btn {
            padding: 0.4rem 0.85rem;
            font-size: 0.85rem;
        }
        .view-toggle .btn.active {
            background: linear-gradient(135deg, var(--dole-secondary), var(--dole-primary));
            color: #fff;
            border-color: var(--dole-primary);
        }

        /* Smart Assistant Modal */
        .assistant-card {
            border-left: 4px solid #dc3545;
            background: #fff;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }
        .assistant-card .activity-title {
            font-weight: 600;
            color: #212529;
        }
        .assistant-card .activity-date {
            font-size: 0.82rem;
            color: #6c757d;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .fc .fc-toolbar {
                flex-direction: column;
                gap: 0.5rem;
            }
            .fc .fc-toolbar-title {
                font-size: 1rem;
            }
            .status-legend {
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <?php $currentPage = 'fieldwork-schedule'; ?>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <main class="col-md-10 ms-sm-auto px-md-4 py-4" id="mainContent" role="main">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <div>
                        <h2 class="mb-1"><i class="bi bi-calendar-event"></i> Schedule of Activities / Fieldwork</h2>
                        <p class="text-muted mb-0">Plan, monitor, and manage fieldwork activities</p>
                    </div>
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <!-- View Toggle -->
                        <div class="btn-group view-toggle" role="group" aria-label="View toggle">
                            <button type="button" class="btn btn-outline-secondary active" id="btnCalendarView" onclick="switchView('calendar')">
                                <i class="bi bi-calendar3"></i> Calendar
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="btnTableView" onclick="switchView('table')">
                                <i class="bi bi-table"></i> Table
                            </button>
                        </div>
                        <?php if ($canEdit): ?>
                        <button class="btn btn-primary" onclick="openActivityModal()">
                            <i class="bi bi-plus-lg"></i> Add Activity
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-lg-3">
                        <div class="card stat-card h-100" style="border-left: 4px solid #ffc107;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="stat-text-wrap">
                                        <h6 class="card-title text-uppercase mb-1">Pending</h6>
                                        <h2 class="stat-number mb-0" id="statPending"><?php echo (int)($stats['pending'] ?? 0); ?></h2>
                                    </div>
                                    <i class="bi bi-hourglass-split stat-icon text-warning flex-shrink-0"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card stat-card h-100" style="border-left: 4px solid #0d6efd;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="stat-text-wrap">
                                        <h6 class="card-title text-uppercase mb-1">Ongoing</h6>
                                        <h2 class="stat-number mb-0" id="statOngoing"><?php echo (int)($stats['ongoing'] ?? 0); ?></h2>
                                    </div>
                                    <i class="bi bi-play-circle stat-icon text-primary flex-shrink-0"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card stat-card h-100" style="border-left: 4px solid #198754;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="stat-text-wrap">
                                        <h6 class="card-title text-uppercase mb-1">Completed</h6>
                                        <h2 class="stat-number mb-0" id="statCompleted"><?php echo (int)($stats['completed'] ?? 0); ?></h2>
                                    </div>
                                    <i class="bi bi-check-circle stat-icon text-success flex-shrink-0"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card stat-card h-100" style="border-left: 4px solid #dc3545;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="stat-text-wrap">
                                        <h6 class="card-title text-uppercase mb-1">Missed</h6>
                                        <h2 class="stat-number mb-0" id="statMissed"><?php echo (int)($stats['missed'] ?? 0); ?></h2>
                                    </div>
                                    <i class="bi bi-x-circle stat-icon text-danger flex-shrink-0"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Calendar View -->
                <div id="calendarViewSection">
                    <div class="card stat-card mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h5 class="mb-0"><i class="bi bi-calendar3"></i> Activity Calendar</h5>
                            <div class="status-legend">
                                <span class="status-legend-item"><span class="status-dot pending"></span> Pending</span>
                                <span class="status-legend-item"><span class="status-dot ongoing"></span> Ongoing</span>
                                <span class="status-legend-item"><span class="status-dot completed"></span> Completed</span>
                                <span class="status-legend-item"><span class="status-dot missed"></span> Missed</span>
                                <span class="status-legend-item"><span class="status-dot holiday"></span> PH Holiday</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="fieldworkCalendar"></div>
                        </div>
                    </div>
                </div>

                <!-- Table View -->
                <div id="tableViewSection" style="display: none;">
                    <div class="card stat-card mb-4">
                        <div class="card-header bg-white">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <h5 class="mb-0"><i class="bi bi-list-ul"></i> Activity List</h5>
                                <div class="d-flex gap-2 flex-wrap">
                                    <select class="form-select form-select-sm" id="filterStatus" style="width: auto;" onchange="loadTableData()">
                                        <option value="">All Statuses</option>
                                        <option value="pending">Pending</option>
                                        <option value="ongoing">Ongoing</option>
                                        <option value="completed">Completed</option>
                                        <option value="missed">Missed</option>
                                    </select>
                                    <select class="form-select form-select-sm" id="filterUser" style="width: auto;" onchange="loadTableData()">
                                        <option value="">All Users</option>
                                        <?php foreach ($users as $u): ?>
                                        <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['full_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" class="form-control form-control-sm" id="filterSearch" placeholder="Search..." style="width: 200px;" oninput="debounceSearch()">
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-fieldwork mb-0">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Location</th>
                                            <th>Assigned To</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Status</th>
                                            <?php if ($canEdit): ?>
                                            <th class="text-center">Actions</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody id="activityTableBody">
                                        <tr>
                                            <td colspan="<?php echo $canEdit ? 7 : 6; ?>" class="text-center py-4">
                                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                                <span class="ms-2 text-muted">Loading activities...</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <!-- Add/Edit Activity Modal -->
    <div class="modal fade" id="activityModal" tabindex="-1" aria-labelledby="activityModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--dole-secondary), var(--dole-primary)); color: #fff;">
                    <h5 class="modal-title" id="activityModalLabel"><i class="bi bi-calendar-plus"></i> Add New Activity</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="activityForm" onsubmit="return saveActivity(event)">
                    <div class="modal-body">
                        <input type="hidden" id="activityId" name="id" value="">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="activityTitle" class="form-label">Activity Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="activityTitle" name="title" required maxlength="255" placeholder="e.g., Plantation Inspection">
                            </div>
                            <div class="col-12">
                                <label for="activityDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="activityDescription" name="description" rows="3" placeholder="Describe the fieldwork activity..."></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="activityLocation" class="form-label">Fieldwork Location</label>
                                <input type="text" class="form-control" id="activityLocation" name="location" maxlength="500" placeholder="e.g., Brgy. San Jose, Silay City">
                            </div>
                            <div class="col-md-6">
                                <label for="activityAssignedUser" class="form-label">Assigned User <span class="text-danger">*</span></label>
                                <select class="form-select" id="activityAssignedUser" name="assigned_user_id" required>
                                    <option value="">Select user...</option>
                                    <?php foreach ($users as $u): ?>
                                    <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['full_name']); ?> (<?php echo ucfirst($u['role']); ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="activityStartDate" class="form-label">Start Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="activityStartDate" name="start_date" required>
                            </div>
                            <div class="col-md-6">
                                <label for="activityEndDate" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="activityEndDate" name="end_date">
                            </div>
                            <div class="col-md-6" id="statusFieldContainer" style="display: none;">
                                <label for="activityStatus" class="form-label">Status</label>
                                <select class="form-select" id="activityStatus" name="status">
                                    <option value="">Auto-detect</option>
                                    <option value="pending">Pending</option>
                                    <option value="ongoing">Ongoing</option>
                                    <option value="completed">Completed</option>
                                    <option value="missed">Missed</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="saveActivityBtn">
                            <i class="bi bi-check-lg"></i> Save Activity
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Activity Detail Modal -->
    <div class="modal fade" id="viewActivityModal" tabindex="-1" aria-labelledby="viewActivityModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--dole-secondary), var(--dole-primary)); color: #fff;">
                    <h5 class="modal-title" id="viewActivityModalLabel"><i class="bi bi-calendar-event"></i> Activity Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="viewActivityBody">
                </div>
                <div class="modal-footer" id="viewActivityFooter">
                </div>
            </div>
        </div>
    </div>

    <!-- Smart Assistant Reminder Modal -->
    <div class="modal fade" id="assistantModal" tabindex="-1" aria-labelledby="assistantModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="assistantModalLabel"><i class="bi bi-robot"></i> Smart Activity Reminder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="assistantBody">
                    <p class="text-muted mb-3">The following activities are past their scheduled date and haven't been marked as completed:</p>
                    <div id="assistantActivitiesList"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Dismiss</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js"></script>
    <?php include 'includes/notification-script.php'; ?>
    <?php include 'includes/ux-utilities.php'; ?>

    <script>
    // Global state
    const CAN_EDIT = <?php echo $canEdit ? 'true' : 'false'; ?>;
    const PH_HOLIDAYS = <?php echo $philippineHolidays; ?>;
    let calendar = null;
    let searchTimeout = null;

    document.addEventListener('DOMContentLoaded', function() {
        initCalendar();
        loadTableData();
        checkOverdueActivities();
    });

    // ===================== CALENDAR =====================
    function initCalendar() {
        const calendarEl = document.getElementById('fieldworkCalendar');
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth'
            },
            height: 'auto',
            firstDay: 0,
            fixedWeekCount: false,
            dayMaxEvents: 3,
            eventDisplay: 'block',
            loading: function(isLoading) {
                // FullCalendar's built-in loading state callback
                // This helps track when calendar is fetching
                if (isLoading) {
                    console.log('[Calendar] Loading events...');
                } else {
                    console.log('[Calendar] Loading complete');
                }
            },
            events: function(fetchInfo, successCallback, failureCallback) {
                // Add timeout to prevent infinite loading
                const timeoutId = setTimeout(() => {
                    console.error('[Calendar] Fetch timeout - forcing empty result');
                    successCallback([]);
                }, 10000); // 10 second timeout

                fetch(`api/fieldwork-schedule-crud.php?action=calendar&start=${fetchInfo.startStr}&end=${fetchInfo.endStr}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        clearTimeout(timeoutId);
                        
                        // Validate response structure
                        // API should return array, but might return {success: false, error: "..."} on error
                        if (data && typeof data === 'object' && data.success === false) {
                            console.error('[Calendar] API returned error:', data.error || 'Unknown error');
                            showToast('error', 'Calendar Error', data.error || 'Failed to load calendar events');
                            successCallback([]); // Return empty array instead of failing
                            return;
                        }
                        
                        // Ensure data is an array
                        const events = Array.isArray(data) ? data : [];
                        
                        // Add PH holidays as background events
                        const holidayEvents = PH_HOLIDAYS.map(h => ({
                            title: h.title,
                            start: h.date,
                            display: 'block',
                            backgroundColor: 'rgba(220, 53, 69, 0.12)',
                            borderColor: 'transparent',
                            textColor: '#b02a37',
                            classNames: ['holiday-event'],
                            editable: false,
                            extendedProps: { isHoliday: true }
                        }));
                        
                        // Safely combine events
                        try {
                            successCallback([...events, ...holidayEvents]);
                        } catch (spreadError) {
                            console.error('[Calendar] Error spreading events:', spreadError);
                            successCallback(holidayEvents); // At least show holidays
                        }
                    })
                    .catch(err => {
                        clearTimeout(timeoutId);
                        console.error('[Calendar] Failed to load calendar events:', err);
                        // Don't call failureCallback as it might cause issues
                        // Instead, call successCallback with empty array to clear loading state
                        successCallback([]);
                        showToast('error', 'Calendar Error', 'Failed to load events. Please refresh the page.');
                    });
            },
            dateClick: function(info) {
                if (CAN_EDIT) {
                    openActivityModal(null, info.dateStr);
                }
            },
            eventClick: function(info) {
                if (info.event.extendedProps.isHoliday) return;
                viewActivity(info.event.id);
            },
            eventDidMount: function(info) {
                if (info.event.extendedProps.isHoliday) return;
                // Tooltip on hover
                const status = info.event.extendedProps.status || '';
                const assignee = info.event.extendedProps.assigned_user || '';
                const location = info.event.extendedProps.location || '';
                let tip = `Status: ${capitalize(status)}`;
                if (assignee) tip += `\nAssigned: ${assignee}`;
                if (location) tip += `\nLocation: ${location}`;
                info.el.setAttribute('title', tip);
            }
        });
        calendar.render();
    }

    // ===================== TABLE VIEW =====================
    function loadTableData() {
        const status = document.getElementById('filterStatus')?.value || '';
        const userId = document.getElementById('filterUser')?.value || '';
        const search = document.getElementById('filterSearch')?.value || '';

        let url = `api/fieldwork-schedule-crud.php?action=list`;
        if (status) url += `&status=${encodeURIComponent(status)}`;
        if (userId) url += `&assigned_user_id=${encodeURIComponent(userId)}`;
        if (search) url += `&search=${encodeURIComponent(search)}`;

        const tbody = document.getElementById('activityTableBody');
        tbody.innerHTML = `<tr><td colspan="${CAN_EDIT ? 7 : 6}" class="text-center py-4">
            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
            <span class="ms-2 text-muted">Loading activities...</span>
        </td></tr>`;

        fetch(url)
            .then(r => r.json())
            .then(data => {
                if (!data.success || !data.data || data.data.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="${CAN_EDIT ? 7 : 6}" class="text-center py-4 text-muted">
                        <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                        No activities found
                    </td></tr>`;
                    return;
                }
                let html = '';
                data.data.forEach(a => {
                    html += buildTableRow(a);
                });
                tbody.innerHTML = html;
            })
            .catch(err => {
                console.error('Failed to load table data:', err);
                tbody.innerHTML = `<tr><td colspan="${CAN_EDIT ? 7 : 6}" class="text-center py-4 text-danger">
                    <i class="bi bi-exclamation-triangle"></i> Failed to load data. <a href="#" onclick="loadTableData(); return false;">Retry</a>
                </td></tr>`;
            });
    }

    function buildTableRow(a) {
        const badge = getStatusBadge(a.status);
        const startDate = formatDate(a.start_date);
        const endDate = a.end_date ? formatDate(a.end_date) : '<span class="text-muted">—</span>';

        let actions = '';
        if (CAN_EDIT) {
            actions = `<td class="text-center">
                <div class="action-buttons-container justify-content-center">
                    <button class="btn btn-sm btn-outline-primary action-btn" onclick="viewActivity(${a.id})" title="View"><i class="bi bi-eye"></i></button>
                    <button class="btn btn-sm btn-outline-secondary action-btn" onclick="openActivityModal(${a.id})" title="Edit"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-outline-danger action-btn" onclick="deleteActivity(${a.id}, '${escapeHtml(a.title)}')" title="Delete"><i class="bi bi-trash"></i></button>
                </div>
            </td>`;
        }

        return `<tr>
            <td><strong>${escapeHtml(a.title)}</strong>${a.description ? '<br><small class="text-muted">' + escapeHtml(truncate(a.description, 60)) + '</small>' : ''}</td>
            <td>${a.location ? escapeHtml(a.location) : '<span class="text-muted">—</span>'}</td>
            <td>${escapeHtml(a.assigned_user_name || 'N/A')}</td>
            <td>${startDate}</td>
            <td>${endDate}</td>
            <td>${badge}</td>
            ${actions}
        </tr>`;
    }

    function debounceSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(loadTableData, 350);
    }

    // ===================== VIEW TOGGLE =====================
    function switchView(view) {
        const calSection = document.getElementById('calendarViewSection');
        const tblSection = document.getElementById('tableViewSection');
        const btnCal = document.getElementById('btnCalendarView');
        const btnTbl = document.getElementById('btnTableView');

        if (view === 'calendar') {
            calSection.style.display = '';
            tblSection.style.display = 'none';
            btnCal.classList.add('active');
            btnTbl.classList.remove('active');
            if (calendar) calendar.updateSize();
        } else {
            calSection.style.display = 'none';
            tblSection.style.display = '';
            btnCal.classList.remove('active');
            btnTbl.classList.add('active');
            loadTableData();
        }
    }

    // ===================== CRUD =====================
    function openActivityModal(id = null, dateStr = null) {
        const form = document.getElementById('activityForm');
        form.reset();
        document.getElementById('activityId').value = '';
        document.getElementById('statusFieldContainer').style.display = 'none';

        if (id) {
            // Edit mode
            document.getElementById('activityModalLabel').innerHTML = '<i class="bi bi-pencil"></i> Edit Activity';
            document.getElementById('saveActivityBtn').innerHTML = '<i class="bi bi-check-lg"></i> Update Activity';

            fetch(`api/fieldwork-schedule-crud.php?action=get&id=${id}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.data) {
                        const a = data.data;
                        document.getElementById('activityId').value = a.id;
                        document.getElementById('activityTitle').value = a.title;
                        document.getElementById('activityDescription').value = a.description || '';
                        document.getElementById('activityLocation').value = a.location || '';
                        document.getElementById('activityAssignedUser').value = a.assigned_user_id;
                        document.getElementById('activityStartDate').value = a.start_date;
                        document.getElementById('activityEndDate').value = a.end_date || '';
                        document.getElementById('activityStatus').value = a.status;
                        document.getElementById('statusFieldContainer').style.display = '';
                    }
                })
                .catch(err => console.error('Failed to load activity:', err));
        } else {
            // Create mode
            document.getElementById('activityModalLabel').innerHTML = '<i class="bi bi-calendar-plus"></i> Add New Activity';
            document.getElementById('saveActivityBtn').innerHTML = '<i class="bi bi-check-lg"></i> Save Activity';
            if (dateStr) {
                document.getElementById('activityStartDate').value = dateStr;
            }
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById('activityModal')).show();
    }

    function saveActivity(e) {
        e.preventDefault();
        const id = document.getElementById('activityId').value;
        const action = id ? 'update' : 'create';
        const formData = new FormData(document.getElementById('activityForm'));
        formData.append('action', action);

        const btn = document.getElementById('saveActivityBtn');
        const origText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
        btn.disabled = true;

        fetch('api/fieldwork-schedule-crud.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const modalEl = document.getElementById('activityModal');
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) {
                    modalInstance.hide();
                } else {
                    modalEl.classList.remove('show');
                    document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
                    document.body.classList.remove('modal-open');
                    document.body.style.removeProperty('padding-right');
                    document.body.style.removeProperty('overflow');
                }
                showToast('success', action === 'create' ? 'Activity Created' : 'Activity Updated', data.message);
                refreshAll();
            } else {
                const errMsg = data.errors ? data.errors.join('<br>') : (data.error || 'Unknown error');
                showToast('error', 'Save Failed', errMsg);
            }
        })
        .catch(err => {
            console.error('Save error:', err);
            showToast('error', 'Error', 'Failed to save activity');
        })
        .finally(() => {
            btn.innerHTML = origText;
            btn.disabled = false;
        });

        return false;
    }

    function deleteActivity(id, title) {
        if (!confirm(`Are you sure you want to delete "${title}"?\nThis action cannot be undone.`)) return;

        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);

        fetch('api/fieldwork-schedule-crud.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('success', 'Deleted', data.message);
                refreshAll();
            } else {
                showToast('error', 'Delete Failed', data.error || 'Unknown error');
            }
        })
        .catch(err => {
            console.error('Delete error:', err);
            showToast('error', 'Error', 'Failed to delete activity');
        });
    }

    function viewActivity(id) {
        fetch(`api/fieldwork-schedule-crud.php?action=get&id=${id}`)
            .then(r => r.json())
            .then(data => {
                if (!data.success || !data.data) {
                    showToast('error', 'Error', 'Activity not found');
                    return;
                }
                const a = data.data;
                const badge = getStatusBadge(a.status);
                const body = document.getElementById('viewActivityBody');
                body.innerHTML = `
                    <div class="mb-3">
                        <h5 class="fw-bold">${escapeHtml(a.title)}</h5>
                        <div>${badge}</div>
                    </div>
                    ${a.description ? `<div class="mb-3"><label class="text-muted small d-block">Description</label><p class="mb-0">${escapeHtml(a.description)}</p></div>` : ''}
                    ${a.location ? `<div class="mb-3"><label class="text-muted small d-block"><i class="bi bi-geo-alt"></i> Location</label><p class="mb-0">${escapeHtml(a.location)}</p></div>` : ''}
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="text-muted small d-block"><i class="bi bi-person"></i> Assigned To</label>
                            <p class="mb-0 fw-medium">${escapeHtml(a.assigned_user_name || 'N/A')}</p>
                        </div>
                        <div class="col-6">
                            <label class="text-muted small d-block"><i class="bi bi-person-check"></i> Created By</label>
                            <p class="mb-0 fw-medium">${escapeHtml(a.created_by_name || 'N/A')}</p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="text-muted small d-block"><i class="bi bi-calendar"></i> Start Date</label>
                            <p class="mb-0">${formatDate(a.start_date)}</p>
                        </div>
                        <div class="col-6">
                            <label class="text-muted small d-block"><i class="bi bi-calendar-check"></i> End Date</label>
                            <p class="mb-0">${a.end_date ? formatDate(a.end_date) : '—'}</p>
                        </div>
                    </div>
                    <div class="text-muted small">
                        <i class="bi bi-clock"></i> Created: ${formatDateTime(a.created_at)} | Updated: ${formatDateTime(a.updated_at)}
                    </div>
                `;

                const footer = document.getElementById('viewActivityFooter');
                let footerHtml = '<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>';
                if (CAN_EDIT) {
                    footerHtml = `
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-outline-primary" onclick="bootstrap.Modal.getInstance(document.getElementById('viewActivityModal')).hide(); openActivityModal(${a.id});">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                    `;
                }
                footer.innerHTML = footerHtml;

                new bootstrap.Modal(document.getElementById('viewActivityModal')).show();
            })
            .catch(err => {
                console.error('View error:', err);
                showToast('error', 'Error', 'Failed to load activity details');
            });
    }

    function updateActivityStatus(id, status) {
        const formData = new FormData();
        formData.append('action', 'update_status');
        formData.append('id', id);
        formData.append('status', status);

        fetch('api/fieldwork-schedule-crud.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('success', 'Status Updated', data.message);
                refreshAll();
                // Close assistant modal item if applicable
                const card = document.getElementById(`assistant-card-${id}`);
                if (card) card.remove();
                // Check if assistant list is empty
                const list = document.getElementById('assistantActivitiesList');
                if (list && list.children.length === 0) {
                    bootstrap.Modal.getInstance(document.getElementById('assistantModal'))?.hide();
                }
            } else {
                showToast('error', 'Update Failed', data.error || 'Unknown error');
            }
        })
        .catch(err => {
            console.error('Status update error:', err);
            showToast('error', 'Error', 'Failed to update status');
        });
    }

    function rescheduleActivity(id) {
        // Close the assistant modal then open edit modal
        const assistantModal = bootstrap.Modal.getInstance(document.getElementById('assistantModal'));
        if (assistantModal) assistantModal.hide();
        setTimeout(() => openActivityModal(id), 400);
    }

    // ===================== SMART ASSISTANT =====================
    function checkOverdueActivities() {
        fetch('api/fieldwork-schedule-crud.php?action=overdue')
            .then(r => r.json())
            .then(data => {
                if (data.success && data.data && data.data.length > 0) {
                    showAssistantReminder(data.data);
                }
            })
            .catch(err => console.error('Overdue check failed:', err));
    }

    function showAssistantReminder(activities) {
        const list = document.getElementById('assistantActivitiesList');
        let html = '';
        activities.forEach(a => {
            const dueDate = formatDate(a.start_date);
            html += `
                <div class="assistant-card" id="assistant-card-${a.id}">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="activity-title">${escapeHtml(a.title)}</div>
                            <div class="activity-date">Due: ${dueDate}${a.assigned_user_name ? ' | Assigned: ' + escapeHtml(a.assigned_user_name) : ''}</div>
                        </div>
                    </div>
                    <p class="mb-2 small text-muted">Have you completed this activity?</p>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-sm btn-success" onclick="updateActivityStatus(${a.id}, 'completed')">
                            <i class="bi bi-check-lg"></i> Completed
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="updateActivityStatus(${a.id}, 'missed')">
                            <i class="bi bi-x-lg"></i> Missed
                        </button>
                        <button class="btn btn-sm btn-outline-primary" onclick="rescheduleActivity(${a.id})">
                            <i class="bi bi-calendar-plus"></i> Reschedule
                        </button>
                    </div>
                </div>
            `;
        });
        list.innerHTML = html;

        // Show the modal
        new bootstrap.Modal(document.getElementById('assistantModal')).show();
    }

    // ===================== HELPERS =====================
    function refreshAll() {
        // Don't refetch calendar to avoid loading spinner issues
        // Instead, reload table and stats, then do a soft page reload after a delay
        try {
            loadTableData();
            refreshStats();
            
            // Reload page after a short delay to refresh calendar without spinner
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } catch (err) {
            console.error('[refreshAll] Error:', err);
            // Fallback: just reload the page
            setTimeout(() => window.location.reload(), 1000);
        }
    }

    function refreshStats() {
        fetch('api/fieldwork-schedule-crud.php?action=statistics')
            .then(r => r.json())
            .then(data => {
                if (data.success && data.data) {
                    document.getElementById('statPending').textContent = data.data.pending || 0;
                    document.getElementById('statOngoing').textContent = data.data.ongoing || 0;
                    document.getElementById('statCompleted').textContent = data.data.completed || 0;
                    document.getElementById('statMissed').textContent = data.data.missed || 0;
                }
            })
            .catch(err => console.error('Stats refresh failed:', err));
    }

    function getStatusBadge(status) {
        const map = {
            'pending':   '<span class="badge badge-fieldwork bg-warning text-dark">Pending</span>',
            'ongoing':   '<span class="badge badge-fieldwork bg-primary">Ongoing</span>',
            'completed': '<span class="badge badge-fieldwork bg-success">Completed</span>',
            'missed':    '<span class="badge badge-fieldwork bg-danger">Missed</span>'
        };
        return map[status] || '<span class="badge badge-fieldwork bg-secondary">Unknown</span>';
    }

    function formatDate(dateStr) {
        if (!dateStr) return '—';
        const d = new Date(dateStr + 'T00:00:00');
        return d.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
    }

    function formatDateTime(dtStr) {
        if (!dtStr) return '—';
        const d = new Date(dtStr);
        return d.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    }

    function capitalize(str) {
        return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
    }

    function truncate(str, len) {
        return str.length > len ? str.substring(0, len) + '...' : str;
    }

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function showToast(type, title, message) {
        // Use DILP toast system if available
        if (typeof DILP !== 'undefined' && DILP.toast) {
            DILP.toast[type](title, message);
            return;
        }
        // Fallback: create toast manually
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        const iconMap = { success: 'bi-check-circle-fill', error: 'bi-exclamation-triangle-fill', warning: 'bi-exclamation-triangle-fill', info: 'bi-info-circle-fill' };
        const toast = document.createElement('div');
        toast.className = `dilp-toast toast-${type}`;
        toast.innerHTML = `
            <span class="dilp-toast-icon"><i class="bi ${iconMap[type] || 'bi-info-circle-fill'}"></i></span>
            <div class="dilp-toast-body">
                <div class="dilp-toast-title">${title}</div>
                <div class="dilp-toast-message">${message}</div>
            </div>
            <button class="dilp-toast-close" onclick="this.parentElement.remove()">&times;</button>
        `;
        container.appendChild(toast);
        setTimeout(() => {
            toast.classList.add('toast-hiding');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }
    </script>
</body>
</html>
