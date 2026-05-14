<?php
// org-chart-admin.php — Multi-person tier org chart management (super_admin only)
// v2.1: Uses tier + sort_order columns (phase8 migration). Falls back gracefully.
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';

$auth = new Auth();
$auth->requireLogin();

if (!$auth->isSuperAdmin()) {
    header('Location: index.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// ── Ensure phase8 columns exist (idempotent) ─────────────────────────────────
try {
    $db->exec("ALTER TABLE org_chart ADD COLUMN tier TINYINT NOT NULL DEFAULT 0");
} catch (PDOException $e) { /* already exists */ }
try {
    $db->exec("ALTER TABLE org_chart ADD COLUMN sort_order TINYINT NOT NULL DEFAULT 0");
} catch (PDOException $e) { /* already exists */ }
try {
    $db->exec("ALTER TABLE org_chart ADD COLUMN province VARCHAR(100) NOT NULL DEFAULT 'Negros Occidental'");
} catch (PDOException $e) { /* already exists */ }
try {
    $db->exec("ALTER TABLE org_chart DROP INDEX position_order");
} catch (PDOException $e) { /* already dropped or doesn't exist */ }

// Migrate any un-migrated rows (tier = 0 AND sort_order = 0 but position_order set)
$db->exec("UPDATE org_chart SET tier = LEAST(position_order - 1, 3), sort_order = 0
           WHERE tier = 0 AND sort_order = 0 AND position_order > 1");

// ── Provinces managed by this system ─────────────────────────────────────────
$validProvinces = ['Negros Occidental', 'Negros Oriental', 'Siquijor'];

// Default titles per tier (used for seeding)
$tierDefaults = [
    0 => ['Regional Director',       null],
    1 => ['Field Office Head',       null],
    2 => ['DILEEP Focal',            null],
    3 => ['LDS / Office Staff / IT', null],
];

// Ensure Tier 0 (Regional Director) has exactly 1 entry with province = NULL
$tier0Check = $db->prepare("SELECT COUNT(*) FROM org_chart WHERE tier = 0");
$tier0Check->execute();
if ((int)$tier0Check->fetchColumn() === 0) {
    $db->prepare("INSERT INTO org_chart (province, tier, sort_order, position_title, person_name, position_order)
                  VALUES (NULL, 0, 0, ?, NULL, 10)")
       ->execute(['Regional Director']);
}

// Ensure at least 1 default node per province for tiers 1–3 only
foreach ($validProvinces as $prov) {
    foreach ($tierDefaults as $tier => $def) {
        // Skip Tier 0 - already handled above
        if ($tier === 0) continue;
        
        $chk = $db->prepare("SELECT COUNT(*) FROM org_chart WHERE province = ? AND tier = ?");
        $chk->execute([$prov, $tier]);
        if ((int)$chk->fetchColumn() === 0) {
            $db->prepare("INSERT INTO org_chart (province, tier, sort_order, position_title, person_name, position_order)
                          VALUES (?, ?, 0, ?, ?, ?)") 
               ->execute([$prov, $tier, $def[0], $def[1], ($tier + 1) * 10]);
        }
    }
}

// ── Fetch all nodes grouped by province then tier ─────────────────────────────
$stmt = $db->query("SELECT * FROM org_chart ORDER BY province ASC, tier ASC, sort_order ASC, id ASC");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build $byProvince[province][tier][] and separate $tier0Data
$tier0Data = [];
$byProvince = [];
foreach ($validProvinces as $prov) {
    $byProvince[$prov] = [1 => [], 2 => [], 3 => []]; // Tier 0 handled separately
}
foreach ($rows as $row) {
    $t = min((int)$row['tier'], 3);
    
    if ($t === 0) {
        // Tier 0 (Regional Director) - store separately
        $tier0Data[] = $row;
    } else {
        // Tiers 1-3 - store by province
        $prov = $row['province'] ?? 'Negros Occidental';
        if (!isset($byProvince[$prov])) {
            $byProvince[$prov] = [1 => [], 2 => [], 3 => []];
        }
        $byProvince[$prov][$t][] = $row;
    }
}

// ── Tier label map ────────────────────────────────────────────────────────────
$tierLabels = [
    0 => 'Tier 1 — Top Leadership',
    1 => 'Tier 2 — Management',
    2 => 'Tier 3 — Technical Staff',
    3 => 'Tier 4 — Support Staff',
];

// ── Helper: render a single person card ──────────────────────────────────────
function renderPersonCard(array $row, int $tierCount): string {
    $id       = (int)$row['id'];
    $title    = htmlspecialchars($row['position_title']);
    $name     = htmlspecialchars($row['person_name'] ?? '');
    $province = htmlspecialchars($row['province'] ?? 'Negros Occidental');
    $nameDisplay = $name ?: '<span class="text-muted fst-italic">Vacant</span>';
    $canDelete = $tierCount > 1 ? '' : 'disabled';

    $titleJs    = addslashes($row['position_title']);
    $nameJs     = addslashes($row['person_name'] ?? '');
    $provinceJs = addslashes($row['province'] ?? 'Negros Occidental');

    return <<<HTML
    <div class="person-card" id="person-card-{$id}" data-province="{$province}">
        <div class="person-card-body">
            <div class="person-name">{$nameDisplay}</div>
            <div class="person-title">{$title}</div>
        </div>
        <div class="person-card-actions">
            <button class="btn btn-sm btn-outline-primary"
                    onclick="showEditModal({$id}, '{$titleJs}', '{$nameJs}', '{$provinceJs}')"
                    title="Edit">
                <i class="bi bi-pencil"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" {$canDelete}
                    onclick="confirmDelete({$id}, '{$nameJs}', '{$titleJs}')"
                    title="Delete">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
HTML;
}

$currentPage = 'org-chart-admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Org Chart — DOLE DILEEP Monitoring System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <?php include 'includes/shared-styles.php'; ?>
    <?php include 'includes/notification-styles.php'; ?>
    <style>
        /* ── Tier card ── */
        .tier-card {
            background: #fff;
            border-radius: var(--dole-border-radius, 10px);
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
            margin-bottom: 1.25rem;
            overflow: hidden;
        }
        .tier-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .75rem 1.25rem;
            background: linear-gradient(135deg, var(--dole-primary, #003087), var(--dole-secondary, #0056b3));
            color: #fff;
        }
        .tier-card-header h6 { margin: 0; font-weight: 600; }
        .tier-card-header .badge { font-size: .7rem; }
        .tier-card-body {
            padding: 1rem 1.25rem 1.25rem;
            display: flex;
            flex-wrap: wrap;
            gap: .85rem;
        }

        /* ── Person card ── */
        .person-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: .75rem 1rem;
            min-width: 200px;
            flex: 1 1 200px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: .5rem;
            transition: box-shadow .15s;
        }
        .person-card:hover { box-shadow: 0 3px 10px rgba(0,0,0,.12); }
        .person-card-body { flex: 1 1 auto; min-width: 0; }
        .person-name  { font-weight: 600; font-size: .9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .person-title { font-size: .78rem; color: #6c757d; margin-top: 2px; }
        .person-card-actions { display: flex; gap: .35rem; flex-shrink: 0; }
        .person-card-actions .btn { padding: .2rem .45rem; line-height: 1; }

        /* ── Add person btn ── */
        .btn-add-person {
            min-width: 140px;
            flex: 0 0 auto;
            border: 2px dashed #adb5bd;
            background: transparent;
            color: #6c757d;
            border-radius: 8px;
            padding: .6rem 1rem;
            font-size: .85rem;
            align-self: center;
            transition: all .15s;
        }
        .btn-add-person:hover {
            border-color: var(--dole-primary, #003087);
            color: var(--dole-primary, #003087);
            background: #e9f0fc;
        }
        .btn-add-person.d-none { display: none !important; }

        /* ── Province section ── */
        .province-section + .province-section {
            border-top: 2px dashed #dee2e6;
            padding-top: 1rem;
        }
        .province-section-header hr {
            opacity: .25;
        }

        /* ── Instructions panel ── */
        .instructions-panel {
            background: #e9f4ff;
            border-left: 4px solid var(--dole-primary, #003087);
            border-radius: 6px;
            padding: .85rem 1.1rem;
            margin-bottom: 1.5rem;
            font-size: .875rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-10 ms-sm-auto px-md-4 py-4" id="mainContent" role="main">

                <!-- Page header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>Manage Organizational Chart</h2>
                    <a href="about.php" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-eye me-1"></i>View Public Chart
                    </a>
                </div>

                <!-- Toast container -->
                <div id="toastContainer" class="position-fixed top-0 end-0 p-3" style="z-index:1100"></div>

                <!-- Instructions -->
                <div class="instructions-panel">
                    <strong><i class="bi bi-info-circle me-1"></i>Instructions:</strong>
                    <strong>Tier 1</strong> (Regional Director): max 1 person. 
                    <strong>Tiers 2-3</strong>: max 5 people per province. 
                    <strong>Tier 4</strong>: max 3 people per province.
                    Use the <i class="bi bi-plus-circle"></i> <strong>Add Person</strong> button to add entries.
                    All tiers require at least one entry. Click <i class="bi bi-pencil"></i> to edit or
                    <i class="bi bi-trash"></i> to remove a person.
                </div>

                <!-- Tier 0 (Regional Director) - Global Section -->
                <?php if (!empty($tier0Data)): ?>
                <div class="mb-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge text-white" style="background:linear-gradient(135deg,#dc3545,#c82333);font-size:.85rem;padding:.45rem .85rem;border-radius:20px;">
                            <i class="bi bi-star-fill me-1"></i>Regional Level
                        </span>
                        <hr class="flex-grow-1 my-0" style="border-color:#dee2e6;">
                    </div>
                    <?php 
                        $tier = 0;
                        $count = count($tier0Data);
                        $full = ($count >= 1); // Tier 0 max is 1
                    ?>
                    <div class="tier-card" id="tier-card-regional-0">
                        <div class="tier-card-header">
                            <h6><i class="bi bi-person-fill me-2"></i><?= htmlspecialchars($tierLabels[0]) ?></h6>
                            <span class="badge bg-light text-dark" id="tier-count-badge-regional-0">
                                <?= $count ?>/1
                            </span>
                        </div>
                        <div class="tier-card-body" id="tier-body-regional-0">
                            <?php foreach ($tier0Data as $person): ?>
                            <?= renderPersonCard($person, $count) ?>
                            <?php endforeach; ?>
                            <button class="btn-add-person <?= $full ? 'd-none' : '' ?>"
                                    id="btn-add-regional-0"
                                    onclick="showAddModal(0, '')">
                                <i class="bi bi-plus-circle me-1"></i>Add Person
                            </button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Province sections with tier cards -->
                <?php foreach ($byProvince as $province => $tierData): ?>
                <?php $provSlug = preg_replace('/[^a-z0-9]+/', '-', strtolower($province)); ?>
                <div class="province-section mb-4" id="province-section-<?= $provSlug ?>">
                    <div class="province-section-header d-flex align-items-center gap-2 mb-3">
                        <span class="badge text-white" style="background:linear-gradient(135deg,var(--dole-primary,#003087),var(--dole-secondary,#0056b3));font-size:.85rem;padding:.45rem .85rem;border-radius:20px;">
                            <i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($province) ?>
                        </span>
                        <hr class="flex-grow-1 my-0" style="border-color:#dee2e6;">
                    </div>

                    <?php foreach ($tierData as $tier => $people): ?>
                    <?php 
                        $count = count($people);
                        // Tier-specific limits: Tier 3 = max 3, Tiers 1-2 = max 5
                        $maxLimit = ($tier === 3) ? 3 : 5;
                        $full = ($count >= $maxLimit);
                    ?>
                    <div class="tier-card" id="tier-card-<?= $provSlug ?>-<?= $tier ?>">
                        <div class="tier-card-header">
                            <h6><i class="bi bi-people-fill me-2"></i><?= htmlspecialchars($tierLabels[$tier]) ?></h6>
                            <span class="badge bg-light text-dark" id="tier-count-badge-<?= $provSlug ?>-<?= $tier ?>">
                                <?= $count ?>/<?= $maxLimit ?>
                            </span>
                        </div>
                        <div class="tier-card-body" id="tier-body-<?= $provSlug ?>-<?= $tier ?>">
                            <?php foreach ($people as $person): ?>
                            <?= renderPersonCard($person, $count) ?>
                            <?php endforeach; ?>
                            <button class="btn-add-person <?= $full ? 'd-none' : '' ?>"
                                    id="btn-add-<?= $provSlug ?>-<?= $tier ?>"
                                    onclick="showAddModal(<?= $tier ?>, '<?= addslashes($province) ?>')">
                                <i class="bi bi-plus-circle me-1"></i>Add Person
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>

            </main>
        </div>
    </div>

    <!-- ── Add / Edit Modal ──────────────────────────────────────────────── -->
    <div class="modal fade" id="personModal" tabindex="-1" aria-labelledby="personModalLabel" aria-modal="true" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="personModalLabel">Add Person</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="modalAction" value="add_person">
                    <input type="hidden" id="modalPersonId" value="">
                    <input type="hidden" id="modalTier" value="">
                    <input type="hidden" id="modalProvince" value="">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Province</label>
                        <div class="form-control-plaintext ps-2 border rounded bg-light d-flex align-items-center gap-2" id="modalProvinceDisplay" style="min-height:38px;">
                            <i class="bi bi-geo-alt-fill text-primary"></i>
                            <span id="modalProvinceText" class="fw-semibold text-primary">—</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Position Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="modalPositionTitle"
                               placeholder="e.g., DILEEP Focal Person" maxlength="255">
                        <div class="invalid-feedback" id="titleError">Position title is required.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Person Name</label>
                        <input type="text" class="form-control" id="modalPersonName"
                               placeholder="e.g., Juan Dela Cruz (leave blank if vacant)" maxlength="255">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="savePersonBtn" onclick="savePersonModal()">
                        <i class="bi bi-save me-1"></i><span id="saveBtnLabel">Add Person</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Delete Confirmation Modal ─────────────────────────────────────── -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-modal="true" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel"><i class="bi bi-exclamation-triangle me-2"></i>Confirm Delete</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="deleteModalBody">Are you sure you want to remove this person?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                        <i class="bi bi-trash me-1"></i>Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include 'includes/notification-script.php'; ?>
    <?php include 'includes/footer.php'; ?>

    <script>
    // ── Helpers ────────────────────────────────────────────────────────────────
    function showToast(message, type = 'success') {
        const id = 'toast-' + Date.now();
        const bg = type === 'success' ? 'bg-success' : 'bg-danger';
        const icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-x-circle-fill';
        const html = `
            <div id="${id}" class="toast align-items-center text-white ${bg} border-0" role="alert" aria-live="assertive">
                <div class="d-flex">
                    <div class="toast-body"><i class="bi ${icon} me-2"></i>${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>`;
        document.getElementById('toastContainer').insertAdjacentHTML('beforeend', html);
        const toastEl = document.getElementById(id);
        const toast = new bootstrap.Toast(toastEl, { delay: 3500 });
        toast.show();
        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    }

    function setSaving(isSaving) {
        const btn = document.getElementById('savePersonBtn');
        btn.disabled = isSaving;
        btn.innerHTML = isSaving
            ? '<span class="spinner-border spinner-border-sm me-1"></span>Saving…'
            : `<i class="bi bi-save me-1"></i><span id="saveBtnLabel">${document.getElementById('modalAction').value === 'add_person' ? 'Add Person' : 'Save Changes'}</span>`;
    }

    // ── Add modal ─────────────────────────────────────────────────────────────
    function showAddModal(tier, province) {
        document.getElementById('modalAction').value        = 'add_person';
        document.getElementById('modalPersonId').value      = '';
        document.getElementById('modalTier').value          = tier;
        document.getElementById('modalProvince').value      = province || '';
        document.getElementById('modalProvinceText').textContent = province || '—';
        document.getElementById('modalPositionTitle').value = '';
        document.getElementById('modalPersonName').value    = '';
        document.getElementById('personModalLabel').textContent = 'Add Person';
        document.getElementById('saveBtnLabel').textContent     = 'Add Person';
        document.getElementById('modalPositionTitle').classList.remove('is-invalid');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('personModal')).show();
    }

    // ── Edit modal ────────────────────────────────────────────────────────────
    function showEditModal(id, title, name, province) {
        document.getElementById('modalAction').value        = 'update_person';
        document.getElementById('modalPersonId').value      = id;
        document.getElementById('modalTier').value          = '';
        document.getElementById('modalProvince').value      = province || '';
        document.getElementById('modalProvinceText').textContent = province || '—';
        document.getElementById('modalPositionTitle').value = title;
        document.getElementById('modalPersonName').value    = name;
        document.getElementById('personModalLabel').textContent = 'Edit Person';
        document.getElementById('saveBtnLabel').textContent     = 'Save Changes';
        document.getElementById('modalPositionTitle').classList.remove('is-invalid');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('personModal')).show();
    }

    // ── Save (add or update) ──────────────────────────────────────────────────
    function savePersonModal() {
        const action = document.getElementById('modalAction').value;
        const id     = document.getElementById('modalPersonId').value;
        const tier   = document.getElementById('modalTier').value;
        const title  = document.getElementById('modalPositionTitle').value.trim();
        const name   = document.getElementById('modalPersonName').value.trim();

        // Client-side validation
        if (!title) {
            document.getElementById('modalPositionTitle').classList.add('is-invalid');
            return;
        }
        document.getElementById('modalPositionTitle').classList.remove('is-invalid');

        setSaving(true);

        const province = document.getElementById('modalProvince').value;

        const fd = new FormData();
        fd.append('action', action);
        fd.append('position_title', title);
        fd.append('person_name', name);
        if (action === 'add_person') {
            fd.append('tier', tier);
            fd.append('province', province);
        }
        if (action === 'update_person') fd.append('id', id);

        fetch('api/org-chart.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                setSaving(false);
                if (res.success) {
                    bootstrap.Modal.getInstance(document.getElementById('personModal')).hide();
                    showToast(res.message || 'Saved successfully');
                    setTimeout(() => location.reload(), 900);
                } else {
                    showToast(res.message || 'Failed to save', 'error');
                }
            })
            .catch(() => {
                setSaving(false);
                showToast('Network error — please try again', 'error');
            });
    }

    // ── Delete flow ───────────────────────────────────────────────────────────
    let _deleteId = null;
    function confirmDelete(id, name, title) {
        _deleteId = id;
        const label = name || title || 'this person';
        document.getElementById('deleteModalBody').innerHTML =
            `Are you sure you want to remove <strong>${label}</strong> from the org chart?`;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteModal')).show();
    }

    document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
        if (!_deleteId) return;
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Deleting…';

        const fd = new FormData();
        fd.append('action', 'delete_person');
        fd.append('id', _deleteId);

        fetch('api/org-chart.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
                if (res.success) {
                    showToast('Person removed successfully');
                    setTimeout(() => location.reload(), 900);
                } else {
                    showToast(res.message || 'Failed to delete', 'error');
                }
            })
            .catch(() => showToast('Network error — please try again', 'error'))
            .finally(() => {
                this.disabled = false;
                this.innerHTML = '<i class="bi bi-trash me-1"></i>Delete';
            });
    });

    // ── Enter key support in modal ─────────────────────────────────────────────
    document.getElementById('personModal').addEventListener('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            savePersonModal();
        }
    });
    </script>
</body>
</html>
