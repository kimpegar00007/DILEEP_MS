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
// hasRole() also passes super_admin and regional_director, matching the API's write check.
$canEdit = $auth->hasRole(['admin', 'encoder']);
// Deletes are admin-only (encoders can edit but not delete).
$canDelete = $auth->hasRole(['admin']);
$isCrossProvince = $auth->isCrossProvince();

$roleLabels = [
    'super_admin'       => 'Super Admin',
    'admin'             => 'Admin',
    'regional_director' => 'Regional Director',
    'encoder'           => 'Encoder',
    'user'              => 'User',
];

/** Assignee option text; cross-province roles also see each user's province. */
function fieldworkUserLabel(array $u, array $roleLabels, bool $withProvince): string {
    $label = $u['full_name'] . ' (' . ($roleLabels[$u['role']] ?? ucfirst((string) $u['role']));
    if ($withProvince) {
        $label .= ', ' . ($u['province'] ?: 'All provinces');
    }
    return $label . ')';
}
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
            cursor: default;
        }
        .holiday-event .fc-event-title,
        .holiday-event .fc-event-main,
        .fc .fc-event.holiday-event .fc-event-title,
        .fc .fc-event.holiday-event .fc-event-main {
            color: #b02a37 !important;
        }
        /* Past-due activities nobody has closed yet */
        .fc .fc-event.fieldwork-overdue {
            box-shadow: inset 0 0 0 2px #dc3545;
        }
        .fc .fc-event.fieldwork-overdue .fc-event-title::before {
            content: "\26A0\FE0E  ";
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
        .status-dot.overdue   { background-color: #fff; box-shadow: inset 0 0 0 2px #dc3545; }
        .status-dot.holiday   { background-color: #dc3545; opacity: 0.3; }

        /* Clickable stat cards (filter the table) */
        .stat-filter {
            cursor: pointer;
        }
        .stat-filter:focus-visible {
            outline: 3px solid rgba(27, 122, 61, 0.45);
            outline-offset: 2px;
        }
        .stat-filter.stat-filter-active {
            box-shadow: 0 0 0 2px var(--dole-primary);
        }

        /* Table View — Compact */
        .table-fieldwork {
            font-size: 0.82rem;
        }
        .table-fieldwork th {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--dole-secondary);
            border-bottom-width: 2px;
            padding: 0.5rem 0.6rem;
            white-space: nowrap;
        }
        .table-fieldwork td {
            font-size: 0.82rem;
            vertical-align: middle;
            padding: 0.4rem 0.6rem;
        }
        .table-fieldwork .activity-title-cell strong {
            font-size: 0.82rem;
            display: block;
            line-height: 1.3;
        }
        .table-fieldwork .activity-title-cell small {
            font-size: 0.72rem;
            color: #6c757d;
        }
        .table-fieldwork tr.row-overdue td:first-child {
            box-shadow: inset 3px 0 0 #dc3545;
        }
        .badge-fieldwork {
            padding: 0.22rem 0.55rem;
            font-weight: 600;
            font-size: 0.7rem;
            border-radius: 20px;
            letter-spacing: 0.02em;
        }
        .badge-overdue {
            background-color: #f8d7da;
            color: #842029;
            border: 1px solid #f1aeb5;
        }

        /* Dropdown Actions */
        .action-dropdown .btn-action-toggle {
            padding: 0.2rem 0.55rem;
            font-size: 0.75rem;
            border-radius: 6px;
        }
        .action-dropdown .dropdown-menu {
            font-size: 0.78rem;
            min-width: 160px;
            padding: 0.25rem 0;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        }
        .action-dropdown .dropdown-item {
            padding: 0.35rem 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .action-dropdown .dropdown-item i {
            width: 14px;
            text-align: center;
            font-size: 0.78rem;
        }
        .action-dropdown .dropdown-divider {
            margin: 0.2rem 0;
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

        /* Edit modal loading cover */
        .modal-loading {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.85);
            z-index: 5;
            border-radius: inherit;
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
            #filterSearch {
                width: 100% !important;
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
                            <button type="button" class="btn btn-outline-secondary active" id="btnCalendarView" aria-pressed="true" onclick="switchView('calendar')">
                                <i class="bi bi-calendar3"></i> Calendar
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="btnTableView" aria-pressed="false" onclick="switchView('table')">
                                <i class="bi bi-table"></i> Table
                            </button>
                        </div>
                        <?php if ($canEdit): ?>
                        <button type="button" class="btn btn-primary" onclick="openActivityModal()">
                            <i class="bi bi-plus-lg"></i> Add Activity
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Statistics Cards (click one to list those activities in the table) -->
                <?php
                $statCards = [
                    ['key' => 'pending',   'label' => 'Pending',   'color' => '#ffc107', 'icon' => 'bi-hourglass-split text-warning'],
                    ['key' => 'ongoing',   'label' => 'Ongoing',   'color' => '#0d6efd', 'icon' => 'bi-play-circle text-primary'],
                    ['key' => 'overdue',   'label' => 'Overdue',   'color' => '#fd7e14', 'icon' => 'bi-exclamation-triangle text-danger'],
                    ['key' => 'completed', 'label' => 'Completed', 'color' => '#198754', 'icon' => 'bi-check-circle text-success'],
                    ['key' => 'missed',    'label' => 'Missed',    'color' => '#dc3545', 'icon' => 'bi-x-circle text-danger'],
                ];
                ?>
                <div class="row row-cols-2 row-cols-md-3 row-cols-xl-5 g-3 mb-4">
                    <?php foreach ($statCards as $card): ?>
                    <div class="col">
                        <div class="card stat-card stat-filter h-100" style="border-left: 4px solid <?php echo $card['color']; ?>;"
                             role="button" tabindex="0" data-filter-status="<?php echo $card['key']; ?>"
                             aria-label="Show <?php echo $card['label']; ?> activities in the table">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="stat-text-wrap">
                                        <h6 class="card-title text-uppercase mb-1"><?php echo $card['label']; ?></h6>
                                        <h2 class="stat-number mb-0" id="stat<?php echo ucfirst($card['key']); ?>"><?php echo (int) ($stats[$card['key']] ?? 0); ?></h2>
                                    </div>
                                    <i class="bi <?php echo $card['icon']; ?> stat-icon flex-shrink-0"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
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
                                <span class="status-legend-item"><span class="status-dot overdue"></span> Overdue</span>
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
                                <h5 class="mb-0"><i class="bi bi-list-ul"></i> Activity List <small class="text-muted fw-normal" id="activityCount"></small></h5>
                                <div class="d-flex gap-2 flex-wrap">
                                    <select class="form-select form-select-sm" id="filterStatus" style="width: auto;" onchange="loadTableData()" aria-label="Filter by status">
                                        <option value="">All Statuses</option>
                                        <option value="overdue">Overdue</option>
                                        <option value="pending">Pending</option>
                                        <option value="ongoing">Ongoing</option>
                                        <option value="completed">Completed</option>
                                        <option value="missed">Missed</option>
                                    </select>
                                    <select class="form-select form-select-sm" id="filterUser" style="width: auto;" onchange="loadTableData()" aria-label="Filter by assigned user">
                                        <option value="">All Users</option>
                                        <?php foreach ($users as $u): ?>
                                        <option value="<?php echo (int) $u['id']; ?>"><?php echo htmlspecialchars($u['full_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="search" class="form-control form-control-sm" id="filterSearch" placeholder="Search title, description, location..." style="width: 240px;" oninput="debounceSearch()" aria-label="Search activities">
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
                                            <th>Start</th>
                                            <th>End</th>
                                            <th>Status</th>
                                            <th class="text-center" style="width:60px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="activityTableBody">
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
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

    <?php if ($canEdit): ?>
    <!-- Add/Edit Activity Modal -->
    <div class="modal fade" id="activityModal" tabindex="-1" aria-labelledby="activityModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content position-relative">
                <div class="modal-loading d-none" id="activityModalLoading" role="status">
                    <div class="spinner-border spinner-border-sm text-primary"></div>
                    <span class="text-muted">Loading activity...</span>
                </div>
                <div class="modal-header" style="background: linear-gradient(135deg, var(--dole-secondary), var(--dole-primary)); color: #fff;">
                    <h5 class="modal-title" id="activityModalLabel"><i class="bi bi-calendar-plus"></i> Add New Activity</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="activityForm" data-no-loading onsubmit="return saveActivity(event)">
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
                                <select class="form-select" id="activityAssignedUser" name="assigned_user_id" required aria-describedby="assignedUserWarning">
                                    <option value="">Select user...</option>
                                    <?php foreach ($users as $u): ?>
                                    <option value="<?php echo (int) $u['id']; ?>"><?php echo htmlspecialchars(fieldworkUserLabel($u, $roleLabels, $isCrossProvince)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text text-danger d-none" id="assignedUserWarning"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="activityStartDate" class="form-label">Start Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="activityStartDate" name="start_date" required>
                            </div>
                            <div class="col-md-6">
                                <label for="activityEndDate" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="activityEndDate" name="end_date" aria-describedby="endDateHelp">
                                <div class="form-text" id="endDateHelp">Leave blank for a one-day activity.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="activityStatus" class="form-label">Status</label>
                                <select class="form-select" id="activityStatus" name="status" aria-describedby="statusHelp">
                                    <option value="">Auto (from the dates)</option>
                                    <option value="pending">Pending</option>
                                    <option value="ongoing">Ongoing</option>
                                    <option value="completed">Completed</option>
                                    <option value="missed">Missed</option>
                                </select>
                                <div class="form-text" id="statusHelp">If you move the dates and leave the status as it was, it is worked out again from the new dates (Completed stays Completed).</div>
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
    <?php endif; ?>

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

    <?php if ($canEdit): ?>
    <!-- Smart Assistant Reminder Modal -->
    <div class="modal fade" id="assistantModal" tabindex="-1" aria-labelledby="assistantModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="assistantModalLabel"><i class="bi bi-robot"></i> Smart Activity Reminder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="assistantBody">
                    <p class="text-muted mb-3" id="assistantIntro"></p>
                    <div id="assistantActivitiesList"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" onclick="snoozeReminder()">
                        <i class="bi bi-alarm"></i> Remind me later
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js"></script>
    <?php include 'includes/notification-script.php'; ?>
    <?php include 'includes/ux-utilities.php'; ?>

    <script>
    // Global state
    const CAN_EDIT = <?php echo $canEdit ? 'true' : 'false'; ?>;
    const CAN_DELETE = <?php echo $canDelete ? 'true' : 'false'; ?>;
    const API = 'api/fieldwork-schedule-crud.php';
    const SNOOZE_KEY = 'fieldworkReminderSnoozedOn';
    let calendar = null;
    let searchTimeout = null;
    let tableController = null;
    let activityCache = {};       // id -> row from the last table load (titles for confirms)
    let returnToReminder = false; // reopen the reminder after a Reschedule edit closes

    document.addEventListener('DOMContentLoaded', function() {
        initCalendar();
        loadTableData();
        if (CAN_EDIT) {
            checkOverdueActivities();
            document.getElementById('activityStartDate').addEventListener('change', syncEndDateMin);
            document.getElementById('activityModal').addEventListener('hidden.bs.modal', function() {
                if (returnToReminder) {
                    returnToReminder = false;
                    checkOverdueActivities(true);
                }
            });
        }
    });

    // One handler for every action button/link built from data (no ids or titles in inline JS).
    document.addEventListener('click', function(e) {
        const el = e.target.closest('[data-action]');
        if (!el) return;
        e.preventDefault();
        const id = parseInt(el.dataset.id, 10);
        switch (el.dataset.action) {
            case 'view':       viewActivity(id); break;
            case 'edit':       openActivityModal(id); break;
            case 'status':     quickUpdateStatus(id, el.dataset.status, el); break;
            case 'delete':     deleteActivity(id); break;
            case 'reschedule': rescheduleActivity(id); break;
        }
    });

    // Stat cards filter the table.
    document.querySelectorAll('.stat-filter').forEach(card => {
        const apply = () => {
            document.getElementById('filterStatus').value = card.dataset.filterStatus;
            switchView('table');
        };
        card.addEventListener('click', apply);
        card.addEventListener('keydown', e => {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); apply(); }
        });
    });

    function todayStr() {
        const d = new Date();
        return `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`;
    }

    function pad2(n) {
        return String(n).padStart(2, '0');
    }

    // ===================== PH HOLIDAYS =====================
    // Regular and special non-working days that fall on known dates. Holy Week moves
    // with Easter and National Heroes Day is the last Monday of August. Eid holidays
    // and Chinese New Year are proclaimed each year, so they are not listed.
    function easterSunday(year) {
        // Anonymous Gregorian algorithm
        const a = year % 19, b = Math.floor(year / 100), c = year % 100;
        const d = Math.floor(b / 4), e = b % 4, f = Math.floor((b + 8) / 25);
        const g = Math.floor((b - f + 1) / 3), h = (19 * a + b - d - g + 15) % 30;
        const i = Math.floor(c / 4), k = c % 4, l = (32 + 2 * e + 2 * i - h - k) % 7;
        const m = Math.floor((a + 11 * h + 22 * l) / 451);
        const month = Math.floor((h + l - 7 * m + 114) / 31);
        const day = ((h + l - 7 * m + 114) % 31) + 1;
        return new Date(Date.UTC(year, month - 1, day));
    }

    function isoUTC(d) {
        return d.toISOString().slice(0, 10);
    }

    function philippineHolidays(year) {
        const easter = easterSunday(year);
        const shift = days => isoUTC(new Date(easter.getTime() + days * 86400000));
        const lastMondayOfAugust = (() => {
            const d = new Date(Date.UTC(year, 7, 31));
            d.setUTCDate(31 - ((d.getUTCDay() + 6) % 7));
            return isoUTC(d);
        })();
        const fixed = (m, d) => `${year}-${pad2(m)}-${pad2(d)}`;
        return [
            { title: "New Year's Day",                     date: fixed(1, 1) },
            { title: 'EDSA People Power Anniversary',      date: fixed(2, 25) },
            { title: 'Maundy Thursday',                    date: shift(-3) },
            { title: 'Good Friday',                        date: shift(-2) },
            { title: 'Black Saturday',                     date: shift(-1) },
            { title: 'Araw ng Kagitingan',                 date: fixed(4, 9) },
            { title: 'Labor Day',                          date: fixed(5, 1) },
            { title: 'Independence Day',                   date: fixed(6, 12) },
            { title: 'Ninoy Aquino Day',                   date: fixed(8, 21) },
            { title: 'National Heroes Day',                date: lastMondayOfAugust },
            { title: "All Saints' Day",                    date: fixed(11, 1) },
            { title: 'Bonifacio Day',                      date: fixed(11, 30) },
            { title: 'Feast of the Immaculate Conception', date: fixed(12, 8) },
            { title: 'Christmas Eve',                      date: fixed(12, 24) },
            { title: 'Christmas Day',                      date: fixed(12, 25) },
            { title: 'Rizal Day',                          date: fixed(12, 30) },
            { title: 'Last Day of the Year',               date: fixed(12, 31) },
        ];
    }

    function holidayEventsFor(rangeStart, rangeEnd) {
        const events = [];
        for (let y = rangeStart.getFullYear(); y <= rangeEnd.getFullYear(); y++) {
            philippineHolidays(y).forEach(h => events.push({
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
        }
        return events;
    }

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
            events: function(fetchInfo, successCallback) {
                const holidays = holidayEventsFor(fetchInfo.start, fetchInfo.end);
                // Hand FullCalendar exactly one result, whichever of fetch/timeout comes first.
                let settled = false;
                const settle = events => {
                    if (settled) return;
                    settled = true;
                    clearTimeout(timeoutId);
                    successCallback(events);
                };
                const timeoutId = setTimeout(() => {
                    console.error('[Calendar] Fetch timeout - showing holidays only');
                    showToast('error', 'Calendar Error', 'Loading activities took too long. Please try again.');
                    settle(holidays);
                }, 10000);

                fetch(`${API}?action=calendar&start=${encodeURIComponent(fetchInfo.startStr)}&end=${encodeURIComponent(fetchInfo.endStr)}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        // The API returns an array, or {success: false, error} on failure.
                        if (!Array.isArray(data)) {
                            throw new Error((data && data.error) || 'Unexpected response');
                        }
                        settle([...data, ...holidays]);
                    })
                    .catch(err => {
                        console.error('[Calendar] Failed to load calendar events:', err);
                        if (!settled) {
                            showToast('error', 'Calendar Error', 'Failed to load activities. Please refresh the page.');
                        }
                        settle(holidays);
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
                if (info.event.extendedProps.isHoliday) {
                    info.el.setAttribute('title', `PH Holiday: ${info.event.title}`);
                    return;
                }
                const p = info.event.extendedProps;
                let tip = `Status: ${capitalize(p.status || '')}${p.is_overdue ? ' (overdue)' : ''}`;
                if (p.assigned_user) tip += `\nAssigned: ${p.assigned_user}`;
                if (p.location) tip += `\nLocation: ${p.location}`;
                info.el.setAttribute('title', tip);
            }
        });
        calendar.render();
    }

    // ===================== TABLE VIEW =====================
    function loadTableData() {
        const status = document.getElementById('filterStatus')?.value || '';
        const userId = document.getElementById('filterUser')?.value || '';
        const search = (document.getElementById('filterSearch')?.value || '').trim();

        let url = `${API}?action=list`;
        if (status) url += `&status=${encodeURIComponent(status)}`;
        if (userId) url += `&assigned_user_id=${encodeURIComponent(userId)}`;
        if (search) url += `&search=${encodeURIComponent(search)}`;

        document.querySelectorAll('.stat-filter').forEach(c =>
            c.classList.toggle('stat-filter-active', status !== '' && c.dataset.filterStatus === status));

        // Drop the previous request so a slow, older response can't overwrite a newer one.
        if (tableController) tableController.abort();
        tableController = new AbortController();

        const tbody = document.getElementById('activityTableBody');
        const countEl = document.getElementById('activityCount');
        tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4">
            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
            <span class="ms-2 text-muted">Loading activities...</span>
        </td></tr>`;

        fetch(url, { signal: tableController.signal })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.error || 'Failed to load data');
                }
                activityCache = {};
                (data.data || []).forEach(a => { activityCache[a.id] = a; });
                countEl.textContent = `(${data.count || 0})`;
                if (!data.data || data.data.length === 0) {
                    const filtered = status || userId || search;
                    tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted">
                        <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                        ${filtered ? 'No activities match these filters' : 'No activities yet'}
                    </td></tr>`;
                    return;
                }
                tbody.innerHTML = data.data.map(buildTableRow).join('');
            })
            .catch(err => {
                if (err.name === 'AbortError') return;
                console.error('Failed to load table data:', err);
                countEl.textContent = '';
                tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">
                    <i class="bi bi-exclamation-triangle"></i> Failed to load data. <a href="#" onclick="loadTableData(); return false;">Retry</a>
                </td></tr>`;
            });
    }

    function buildTableRow(a) {
        const id = parseInt(a.id, 10);
        const startDate = formatDate(a.start_date);
        const endDate = a.end_date ? formatDate(a.end_date) : '<span class="text-muted">—</span>';
        const statusCell = `<td style="white-space:nowrap">${getStatusBadge(a.status)}${a.is_overdue ? ' ' + overdueBadge() : ''}</td>`;

        let actions;
        if (CAN_EDIT) {
            // Single dropdown: record actions + quick status updates (current status skipped)
            const statusMap = [
                { val: 'pending',   icon: 'bi-arrow-counterclockwise', label: 'Reset to Pending',  cls: 'text-secondary' },
                { val: 'ongoing',   icon: 'bi-play-circle',            label: 'Mark as Ongoing',   cls: 'text-primary'   },
                { val: 'completed', icon: 'bi-check-circle',           label: 'Mark as Completed', cls: 'text-success'   },
                { val: 'missed',    icon: 'bi-x-circle',               label: 'Mark as Missed',    cls: 'text-danger'    },
            ];
            const statusItems = statusMap
                .filter(s => s.val !== a.status)
                .map(s => `<li>
                        <a class="dropdown-item ${s.cls}" href="#" data-action="status" data-id="${id}" data-status="${s.val}">
                            <i class="bi ${s.icon}"></i> ${s.label}
                        </a>
                    </li>`).join('');

            actions = `<td class="text-center">
                <div class="action-dropdown dropdown">
                    <button class="btn btn-sm btn-outline-secondary btn-action-toggle dropdown-toggle"
                            type="button" id="dd-${id}" data-bs-toggle="dropdown"
                            aria-expanded="false" aria-label="Actions">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dd-${id}">
                        <li>
                            <a class="dropdown-item" href="#" data-action="view" data-id="${id}">
                                <i class="bi bi-eye text-info"></i> View Details
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" data-action="edit" data-id="${id}">
                                <i class="bi bi-pencil text-secondary"></i> Edit / Reschedule
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        ${statusItems}
                        ${CAN_DELETE ? `
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="#" data-action="delete" data-id="${id}">
                                <i class="bi bi-trash"></i> Delete
                            </a>
                        </li>` : ''}
                    </ul>
                </div>
            </td>`;
        } else {
            actions = `<td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-secondary btn-action-toggle" data-action="view" data-id="${id}" aria-label="View details">
                    <i class="bi bi-eye"></i>
                </button>
            </td>`;
        }

        return `<tr class="${a.is_overdue ? 'row-overdue' : ''}">
            <td class="activity-title-cell">
                <strong>${escapeHtml(a.title)}</strong>
                ${a.description ? '<small>' + escapeHtml(truncate(a.description, 55)) + '</small>' : ''}
            </td>
            <td>${a.location ? escapeHtml(a.location) : '<span class="text-muted">—</span>'}</td>
            <td>${escapeHtml(a.assigned_user_name || 'N/A')}</td>
            <td style="white-space:nowrap">${startDate}</td>
            <td style="white-space:nowrap">${endDate}</td>
            ${statusCell}
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
        const isCalendar = view === 'calendar';

        calSection.style.display = isCalendar ? '' : 'none';
        tblSection.style.display = isCalendar ? 'none' : '';
        btnCal.classList.toggle('active', isCalendar);
        btnTbl.classList.toggle('active', !isCalendar);
        btnCal.setAttribute('aria-pressed', String(isCalendar));
        btnTbl.setAttribute('aria-pressed', String(!isCalendar));

        if (isCalendar) {
            if (calendar) calendar.updateSize();
        } else {
            loadTableData();
            tblSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    // ===================== CRUD =====================
    function syncEndDateMin() {
        const start = document.getElementById('activityStartDate').value;
        const end = document.getElementById('activityEndDate');
        end.min = start || '';
        if (start && end.value && end.value < start) {
            end.value = start;
        }
    }

    function setActivityFormLoading(loading) {
        document.getElementById('activityModalLoading').classList.toggle('d-none', !loading);
        document.getElementById('saveActivityBtn').disabled = loading;
    }

    function openActivityModal(id = null, dateStr = null) {
        if (!CAN_EDIT) return;
        const form = document.getElementById('activityForm');
        const modalEl = document.getElementById('activityModal');
        form.reset();
        document.getElementById('activityId').value = '';
        const warning = document.getElementById('assignedUserWarning');
        warning.classList.add('d-none');
        warning.textContent = '';

        if (id) {
            // Edit mode: keep Save disabled until the record is loaded, so a failed load
            // can never turn into a new record.
            document.getElementById('activityModalLabel').innerHTML = '<i class="bi bi-pencil"></i> Edit Activity';
            document.getElementById('saveActivityBtn').innerHTML = '<i class="bi bi-check-lg"></i> Update Activity';
            setActivityFormLoading(true);
            bootstrap.Modal.getOrCreateInstance(modalEl).show();

            fetch(`${API}?action=get&id=${encodeURIComponent(id)}`)
                .then(r => r.json())
                .then(data => {
                    if (!data.success || !data.data) {
                        throw new Error(data.error || 'Activity not found');
                    }
                    const a = data.data;
                    document.getElementById('activityId').value = a.id;
                    document.getElementById('activityTitle').value = a.title;
                    document.getElementById('activityDescription').value = a.description || '';
                    document.getElementById('activityLocation').value = a.location || '';
                    document.getElementById('activityStartDate').value = a.start_date;
                    document.getElementById('activityEndDate').value = a.end_date || '';
                    document.getElementById('activityStatus').value = a.status;
                    syncEndDateMin();

                    const userSelect = document.getElementById('activityAssignedUser');
                    userSelect.value = String(a.assigned_user_id);
                    if (userSelect.value !== String(a.assigned_user_id)) {
                        userSelect.value = '';
                        warning.textContent = `${a.assigned_user_name || 'The assigned user'} is inactive or outside your province. Choose another user.`;
                        warning.classList.remove('d-none');
                    }
                    setActivityFormLoading(false);
                })
                .catch(err => {
                    console.error('Failed to load activity:', err);
                    returnToReminder = false;
                    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                    setActivityFormLoading(false);
                    showToast('error', 'Error', 'Could not load this activity. Please try again.');
                });
        } else {
            // Create mode
            document.getElementById('activityModalLabel').innerHTML = '<i class="bi bi-calendar-plus"></i> Add New Activity';
            document.getElementById('saveActivityBtn').innerHTML = '<i class="bi bi-check-lg"></i> Save Activity';
            setActivityFormLoading(false);
            if (dateStr) {
                document.getElementById('activityStartDate').value = dateStr;
            }
            syncEndDateMin();
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
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

        fetch(API, {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('activityModal')).hide();
                showToast('success', action === 'create' ? 'Activity Created' : 'Activity Updated', data.message);
                refreshAll();
            } else {
                const errMsg = data.errors ? data.errors.join('; ') : (data.error || 'Unknown error');
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

    function deleteActivity(id) {
        const title = activityCache[id]?.title || 'this activity';
        if (!confirm(`Are you sure you want to delete "${title}"?\nThis action cannot be undone.`)) return;

        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);

        fetch(API, {
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
        fetch(`${API}?action=get&id=${encodeURIComponent(id)}`)
            .then(r => r.json())
            .then(data => {
                if (!data.success || !data.data) {
                    showToast('error', 'Error', 'Activity not found');
                    return;
                }
                const a = data.data;
                const body = document.getElementById('viewActivityBody');
                body.innerHTML = `
                    <div class="mb-3">
                        <h5 class="fw-bold">${escapeHtml(a.title)}</h5>
                        <div>${getStatusBadge(a.status)}${a.is_overdue ? ' ' + overdueBadge() : ''}</div>
                        ${a.is_overdue ? '<div class="small text-danger mt-1">Past its scheduled dates and not yet marked Completed or Missed.</div>' : ''}
                    </div>
                    ${a.description ? `<div class="mb-3"><label class="text-muted small d-block">Description</label><p class="mb-0" style="white-space: pre-line">${escapeHtml(a.description)}</p></div>` : ''}
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
                const viewId = parseInt(a.id, 10);
                footer.innerHTML = '<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>' +
                    (CAN_EDIT ? `<button type="button" class="btn btn-outline-primary" id="viewEditBtn" data-edit-id="${viewId}">
                            <i class="bi bi-pencil"></i> Edit
                        </button>` : '');
                const modalEl = document.getElementById('viewActivityModal');
                const editBtn = document.getElementById('viewEditBtn');
                if (editBtn) {
                    // Open the edit form only once this modal has finished closing.
                    editBtn.addEventListener('click', () => {
                        modalEl.addEventListener('hidden.bs.modal', () => openActivityModal(viewId), { once: true });
                        bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                    });
                }

                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            })
            .catch(err => {
                console.error('View error:', err);
                showToast('error', 'Error', 'Failed to load activity details');
            });
    }

    function quickUpdateStatus(id, newStatus, triggerEl) {
        if (newStatus === 'missed' && !confirm('Mark this activity as Missed?')) return;
        updateActivityStatus(id, newStatus, triggerEl);
    }

    function updateActivityStatus(id, status, triggerEl) {
        const formData = new FormData();
        formData.append('action', 'update_status');
        formData.append('id', id);
        formData.append('status', status);

        // While a reminder card is saving, lock its buttons so it can't be sent twice.
        const card = document.getElementById(`assistant-card-${id}`);
        const cardButtons = card ? card.querySelectorAll('button') : [];
        cardButtons.forEach(b => { b.disabled = true; });

        fetch(API, {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('success', 'Status Updated', data.message);
                refreshAll();
                if (card) {
                    card.remove();
                    updateReminderIntro();
                    const list = document.getElementById('assistantActivitiesList');
                    if (list.children.length === 0) {
                        bootstrap.Modal.getOrCreateInstance(document.getElementById('assistantModal')).hide();
                        showToast('success', 'All caught up', 'No overdue activities left.');
                    }
                }
            } else {
                cardButtons.forEach(b => { b.disabled = false; });
                showToast('error', 'Update Failed', data.error || 'Unknown error');
            }
        })
        .catch(err => {
            cardButtons.forEach(b => { b.disabled = false; });
            console.error('Status update error:', err);
            showToast('error', 'Error', 'Failed to update status');
        });
    }

    function rescheduleActivity(id) {
        // Close the reminder, open the edit form once it has gone, and come back to the
        // reminder when the edit form closes (saved or cancelled).
        const modalEl = document.getElementById('assistantModal');
        modalEl.addEventListener('hidden.bs.modal', () => {
            returnToReminder = true;
            openActivityModal(id);
        }, { once: true });
        bootstrap.Modal.getOrCreateInstance(modalEl).hide();
    }

    // ===================== SMART ASSISTANT =====================
    // Past-due activities are never closed automatically; the reminder asks an editor
    // whether each one was completed or missed, or lets them reschedule it.
    function reminderSnoozedToday() {
        try {
            return sessionStorage.getItem(SNOOZE_KEY) === todayStr();
        } catch (e) {
            return false;
        }
    }

    function snoozeReminder() {
        try {
            sessionStorage.setItem(SNOOZE_KEY, todayStr());
        } catch (e) { /* storage unavailable: just close */ }
        bootstrap.Modal.getOrCreateInstance(document.getElementById('assistantModal')).hide();
        showToast('info', 'Reminder snoozed', 'Overdue activities stay marked in the calendar and under the Overdue card.');
    }

    function checkOverdueActivities(ignoreSnooze = false) {
        if (!CAN_EDIT || (!ignoreSnooze && reminderSnoozedToday())) return;
        fetch(`${API}?action=overdue`)
            .then(r => r.json())
            .then(data => {
                if (data.success && data.data && data.data.length > 0) {
                    showAssistantReminder(data.data);
                }
            })
            .catch(err => console.error('Overdue check failed:', err));
    }

    function updateReminderIntro() {
        const n = document.getElementById('assistantActivitiesList').children.length;
        document.getElementById('assistantIntro').textContent = n === 1
            ? '1 activity is past its scheduled dates and hasn\'t been marked Completed or Missed:'
            : `${n} activities are past their scheduled dates and haven't been marked Completed or Missed:`;
    }

    function showAssistantReminder(activities) {
        const list = document.getElementById('assistantActivitiesList');
        list.innerHTML = activities.map(a => {
            const id = parseInt(a.id, 10);
            const days = parseInt(a.days_overdue, 10) || 0;
            const dates = a.end_date && a.end_date !== a.start_date
                ? `${formatDate(a.start_date)} – ${formatDate(a.end_date)}`
                : formatDate(a.start_date);
            return `
                <div class="assistant-card" id="assistant-card-${id}">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <div>
                            <div class="activity-title">${escapeHtml(a.title)}</div>
                            <div class="activity-date">
                                ${dates}${a.assigned_user_name ? ' | Assigned: ' + escapeHtml(a.assigned_user_name) : ''}
                                ${a.location ? '<br><i class="bi bi-geo-alt"></i> ' + escapeHtml(a.location) : ''}
                            </div>
                        </div>
                        <span class="badge badge-fieldwork badge-overdue flex-shrink-0">${days} day${days === 1 ? '' : 's'} overdue</span>
                    </div>
                    <p class="mb-2 small text-muted">Was this activity completed?</p>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-sm btn-success" data-action="status" data-id="${id}" data-status="completed">
                            <i class="bi bi-check-lg"></i> Completed
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" data-action="status" data-id="${id}" data-status="missed">
                            <i class="bi bi-x-lg"></i> Missed
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-action="reschedule" data-id="${id}">
                            <i class="bi bi-calendar-plus"></i> Reschedule
                        </button>
                    </div>
                </div>
            `;
        }).join('');
        updateReminderIntro();

        bootstrap.Modal.getOrCreateInstance(document.getElementById('assistantModal')).show();
    }

    // ===================== HELPERS =====================
    // Refresh everything in place; the calendar keeps its month and the table its filters.
    function refreshAll() {
        if (calendar) calendar.refetchEvents();
        loadTableData();
        refreshStats();
        if (typeof fetchNotifications === 'function') fetchNotifications();
    }

    function refreshStats() {
        fetch(`${API}?action=statistics`)
            .then(r => r.json())
            .then(data => {
                if (data.success && data.data) {
                    ['pending', 'ongoing', 'overdue', 'completed', 'missed'].forEach(k => {
                        const el = document.getElementById('stat' + capitalize(k));
                        if (el) el.textContent = data.data[k] || 0;
                    });
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

    function overdueBadge() {
        return '<span class="badge badge-fieldwork badge-overdue"><i class="bi bi-exclamation-triangle"></i> Overdue</span>';
    }

    function formatDate(dateStr) {
        if (!dateStr) return '—';
        const d = new Date(dateStr + 'T00:00:00');
        return d.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
    }

    function formatDateTime(dtStr) {
        if (!dtStr) return '—';
        // MySQL gives "YYYY-MM-DD HH:MM:SS"; the "T" form parses in every browser (Safari too).
        const d = new Date(String(dtStr).replace(' ', 'T'));
        if (isNaN(d)) return escapeHtml(dtStr);
        return d.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    }

    function capitalize(str) {
        return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
    }

    function truncate(str, len) {
        return str.length > len ? str.substring(0, len) + '...' : str;
    }

    // Escapes quotes too, so the result is safe inside attributes as well as text.
    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function showToast(type, title, message) {
        // DILP toast (ux-utilities.php) escapes its text.
        if (typeof DILP !== 'undefined' && DILP.toast) {
            DILP.toast[type](title, message);
            return;
        }
        console[type === 'error' ? 'error' : 'log'](`${title}: ${message}`);
    }
    </script>
</body>
</html>
