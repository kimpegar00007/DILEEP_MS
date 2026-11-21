<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';
require_once 'models/SharedBudget.php';
require_once 'includes/shared-budget-cards.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requireRole(['admin', 'encoder']);

$sessionProvince = $auth->getProvince();
$isCrossProvince = $auth->isCrossProvince();

$model  = new SharedBudget();
$errors = [];
$isEdit = false;
$budget = null;

if (isset($_GET['id'])) {
    $isEdit = true;
    $budget = $model->findById($_GET['id']);   // province-scoped: false when out of reach
    if (!$budget) {
        header('Location: shared-budgets.php');
        exit;
    }
}

// Province every member of this budget is saved in.
if ($isEdit) {
    $formProvince = $budget['province'];
} elseif (!$isCrossProvince) {
    $formProvince = (string) $sessionProvince;
} else {
    $formProvince = trim($_POST['province'] ?? '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $parsed   = SharedBudget::parseRequest($_POST);
    $province = $isEdit ? $budget['province'] : $model->provinceForNew($parsed['budget']['province']);

    if ($province === null) {
        $errors[] = 'Province is required.';
    } else {
        $errors = $model->validate($parsed, $province, $isEdit ? (int) $budget['id'] : null);
    }

    if (!$errors) {
        $parsed['budget']['province'] = $province;
        if ($isEdit) {
            if ($model->update($budget['id'], $parsed['budget'], $parsed['proponents'], $parsed['beneficiaries'])) {
                header('Location: shared-budget-view.php?id=' . (int) $budget['id'] . '&success=updated');
                exit;
            }
        } else {
            $newId = $model->create($parsed['budget'], $parsed['proponents'], $parsed['beneficiaries']);
            if ($newId) {
                header('Location: shared-budget-view.php?id=' . (int) $newId . '&success=created');
                exit;
            }
        }
        $errors[] = 'Nothing was saved: ' . ($model->getLastError() ?: 'unknown error') . ' Please check the details and try again.';
    }
}

// ---- Values used to draw the form ------------------------------------------------
$formBudget = [
    'title' => '', 'total_budget' => '', 'source_of_funds' => '', 'notes' => '',
];
$proponentCards   = [];
$beneficiaryCards = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Re-draw exactly what the user typed so nothing is lost on a validation error.
    foreach (array_keys($formBudget) as $k) {
        $formBudget[$k] = $_POST[$k] ?? '';
    }
    foreach ((array) ($_POST['proponents'] ?? []) as $i => $p) {
        if (is_array($p)) $proponentCards[(string) $i] = $p;
    }
    foreach ((array) ($_POST['beneficiaries'] ?? []) as $i => $b) {
        if (is_array($b)) $beneficiaryCards[(string) $i] = $b;
    }
} elseif ($isEdit) {
    $formBudget = array_merge($formBudget, $budget);
    foreach ($model->getProponents($budget['id']) as $row) {
        $assoc = $model->getAssociations($row['id']);
        $decoded = json_decode((string) ($row['beneficiary_full_name'] ?? ''), true);
        $row['key']  = 'p' . $row['id'];
        $row['beneficiary_full_names']  = is_array($decoded) ? $decoded
            : array_values(array_filter(array_map('trim', explode(',', (string) ($row['beneficiary_full_name'] ?? '')))));
        $row['association_names']     = array_column($assoc, 'association_name');
        $row['association_addresses'] = array_column($assoc, 'association_address');
        $proponentCards['e' . $row['id']] = $row;
    }
    foreach ($model->getBeneficiaries($budget['id']) as $row) {
        $row['proponent_key'] = !empty($row['proponent_id']) ? 'p' . $row['proponent_id'] : '';
        $beneficiaryCards['e' . $row['id']] = $row;
    }
}

if (!$proponentCards && !$beneficiaryCards) {
    $proponentCards['n1']   = ['key' => 'n1'];
    $beneficiaryCards['n1'] = [];
}

// Office label depends on the province, so the page can relabel when a cross-province user picks one.
$officeLabels = [];
foreach (Auth::PROVINCES as $pName) {
    $officeLabels[$pName] = Auth::getForwardedToOfficeLabel($pName);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? 'Edit' : 'Add'; ?> Shared Budget - DOLE DILEEP Monitoring System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <?php include 'includes/shared-styles.php'; ?>
    <?php include 'includes/notification-styles.php'; ?>
    <style>
        .sb-card { border-left: 4px solid var(--dole-primary); }
        .sb-card.sb-beneficiary { border-left-color: #0d6efd; }
        .sb-card .card-header { background: #f8faf9; }
        .sb-meter-bar { position: sticky; bottom: 0; z-index: 20; box-shadow: 0 -4px 14px rgba(0,0,0,.08); }
        .sb-meter-ok { color: #198754; }
        .sb-meter-bad { color: #dc3545; }
        .sb-progress { height: 8px; }
    </style>
</head>
<body>
    <?php $currentPage = 'shared-budgets'; ?>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-10 ms-sm-auto px-md-4 py-4" id="mainContent" role="main">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-pie-chart"></i> <?php echo $isEdit ? 'Edit' : 'Add New'; ?> Shared Budget</h2>
                    <a href="<?php echo $isEdit ? 'shared-budget-view.php?id=' . (int) $budget['id'] : 'shared-budgets.php'; ?>" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                </div>

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <strong>Please fix the following:</strong>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="alert alert-info d-flex gap-2">
                    <i class="bi bi-info-circle-fill"></i>
                    <div>
                        One budget is shared by several Proponents and Beneficiaries. Give each party its own share;
                        <strong>all shares must add up to the total budget</strong>. Each party is saved as a normal Proponent or Beneficiary record,
                        so it appears in the usual lists and reports, and the budget is counted only once.
                    </div>
                </div>

                <form method="POST" action="" id="sbForm">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="form-section mb-0">
                                <h5><i class="bi bi-cash-coin"></i> Budget</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Budget Title / Reference <span class="text-danger">*</span></label>
                                        <input type="text" name="title" class="form-control" required value="<?php echo sb_h($formBudget['title']); ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Total Budget (&#8369;) <span class="text-danger">*</span></label>
                                        <input type="number" name="total_budget" id="total_budget" class="form-control" step="0.01" min="0.01" required value="<?php echo sb_h($formBudget['total_budget']); ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Province <span class="text-danger">*</span></label>
                                        <?php if ($isCrossProvince && !$isEdit): ?>
                                        <select name="province" id="budget_province" class="form-select" required>
                                            <option value="">Select Province</option>
                                            <?php foreach (Auth::PROVINCES as $pName): ?>
                                            <option value="<?php echo sb_h($pName); ?>" <?php echo $formProvince === $pName ? 'selected' : ''; ?>><?php echo sb_h($pName); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php else: ?>
                                        <input type="hidden" name="province" id="budget_province" value="<?php echo sb_h($formProvince); ?>">
                                        <input type="text" class="form-control bg-light" value="<?php echo sb_h($formProvince ?: 'Not assigned'); ?>" disabled>
                                        <small class="text-muted"><i class="bi bi-lock-fill"></i> <?php echo $isEdit ? 'A budget cannot change province.' : 'Locked to your assigned region.'; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Source of Funds</label>
                                        <select name="source_of_funds" class="form-select">
                                            <option value="">Select Source</option>
                                            <?php foreach (['GAA' => 'GAA (General Appropriation Act)', 'Centrally Managed Fund' => 'Centrally Managed Fund/Central Office', 'Other' => 'Other'] as $val => $lbl): ?>
                                            <option value="<?php echo sb_h($val); ?>" <?php echo $formBudget['source_of_funds'] === $val ? 'selected' : ''; ?>><?php echo sb_h($lbl); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Notes</label>
                                        <textarea name="notes" class="form-control" rows="2"><?php echo sb_h($formBudget['notes']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h4 class="mb-0"><i class="bi bi-building"></i> Proponents</h4>
                        <button type="button" class="btn btn-success btn-sm" id="addProponent"><i class="bi bi-plus-circle"></i> Add Proponent</button>
                    </div>
                    <div id="proponentCards">
                        <?php foreach ($proponentCards as $idx => $v): ?>
                            <?php echo sb_render_proponent_card((string) $idx, $v, $formProvince); ?>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2 mt-4">
                        <h4 class="mb-0"><i class="bi bi-people"></i> Beneficiaries</h4>
                        <button type="button" class="btn btn-primary btn-sm" id="addBeneficiary"><i class="bi bi-plus-circle"></i> Add Beneficiary</button>
                    </div>
                    <div id="beneficiaryCards">
                        <?php foreach ($beneficiaryCards as $idx => $v): ?>
                            <?php echo sb_render_beneficiary_card((string) $idx, $v, $formProvince); ?>
                        <?php endforeach; ?>
                    </div>

                    <div class="card sb-meter-bar mt-4">
                        <div class="card-body py-3">
                            <div class="d-flex flex-wrap align-items-center gap-3">
                                <div class="flex-grow-1" style="min-width:260px;">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span>Allocated <strong id="meterAllocated">&#8369;0.00</strong> of <strong id="meterTotal">&#8369;0.00</strong></span>
                                        <span id="meterStatus" class="fw-semibold"></span>
                                    </div>
                                    <div class="progress sb-progress"><div id="meterBar" class="progress-bar bg-success" style="width:0%"></div></div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary" id="sbSubmit"><i class="bi bi-save"></i> <?php echo $isEdit ? 'Update' : 'Save'; ?> Shared Budget</button>
                                    <a href="<?php echo $isEdit ? 'shared-budget-view.php?id=' . (int) $budget['id'] : 'shared-budgets.php'; ?>" class="btn btn-secondary"><i class="bi bi-x-circle"></i> Cancel</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <template id="tplProponent"><?php echo sb_render_proponent_card('__IDX__', ['key' => '__KEY__'], $formProvince); ?></template>
                <template id="tplBeneficiary"><?php echo sb_render_beneficiary_card('__IDX__', [], $formProvince); ?></template>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include 'includes/notification-script.php'; ?>
    <script>
    (function () {
        const OFFICE_LABELS = <?php echo json_encode($officeLabels, JSON_UNESCAPED_UNICODE); ?>;
        const form = document.getElementById('sbForm');
        const proponentWrap = document.getElementById('proponentCards');
        const beneficiaryWrap = document.getElementById('beneficiaryCards');
        const provinceInput = document.getElementById('budget_province');
        let nextIdx = 1000;
        let nextKey = 1;

        const peso = n => '₱' + (Number(n) || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        // ── Live totals ───────────────────────────────────────────────────
        function updateMeter() {
            const total = Math.round((parseFloat(document.getElementById('total_budget').value) || 0) * 100);
            let allocated = 0;
            document.querySelectorAll('.sb-card').forEach(card => {
                const cents = Math.round((parseFloat(card.querySelector('.share-input').value) || 0) * 100);
                allocated += cents;
                card.querySelector('.sb-card-share').textContent = peso(cents / 100);
            });
            document.getElementById('meterAllocated').textContent = peso(allocated / 100);
            document.getElementById('meterTotal').textContent = peso(total / 100);

            const status = document.getElementById('meterStatus');
            const bar = document.getElementById('meterBar');
            const diff = total - allocated;
            bar.style.width = total > 0 ? Math.min(100, allocated / total * 100) + '%' : '0%';
            if (total > 0 && diff === 0) {
                status.textContent = 'Balanced'; status.className = 'fw-semibold sb-meter-ok'; bar.className = 'progress-bar bg-success';
            } else if (diff > 0) {
                status.textContent = peso(diff / 100) + ' still to assign'; status.className = 'fw-semibold sb-meter-bad'; bar.className = 'progress-bar bg-warning';
            } else {
                status.textContent = peso(-diff / 100) + ' over the total'; status.className = 'fw-semibold sb-meter-bad'; bar.className = 'progress-bar bg-danger';
            }
        }

        // ── Beneficiary → Proponent dropdowns ──────────────────────────────
        function refreshProponentOptions() {
            const options = Array.from(proponentWrap.querySelectorAll('.sb-proponent')).map((card, i) => ({
                key: card.querySelector('.sb-key').value,
                label: (card.querySelector('.sb-name').value.trim() || 'Proponent #' + (i + 1)) + ' (#' + (i + 1) + ')',
            }));
            document.querySelectorAll('.sb-bene-proponent').forEach(select => {
                const current = select.value || select.dataset.selected || '';
                select.innerHTML = '<option value="">Select a proponent in this budget</option>';
                options.forEach(o => {
                    const opt = document.createElement('option');
                    opt.value = o.key; opt.textContent = o.label;
                    select.appendChild(opt);
                });
                select.value = options.some(o => o.key === current) ? current : '';
                // With exactly one proponent there is nothing to choose.
                if (options.length === 1 && !select.value) select.value = options[0].key;
                select.dataset.selected = select.value;
            });
        }

        // ── Per-card behaviour ────────────────────────────────────────────
        function renderNameFields(card) {
            const wrap = card.querySelector('.sb-name-fields');
            if (!wrap) return;
            const count = Math.max(1, parseInt(card.querySelector('.sb-total-ben').value, 10) || 1);
            const saved = JSON.parse(wrap.dataset.names || '[]');
            const current = Array.from(wrap.querySelectorAll('input')).map(i => i.value);
            wrap.innerHTML = '';
            for (let i = 0; i < count; i++) {
                const div = document.createElement('div');
                div.className = 'mb-2';
                const label = document.createElement('label');
                label.className = 'form-label';
                label.textContent = 'Beneficiary Full Name #' + (i + 1);
                const input = document.createElement('input');
                input.type = 'text'; input.className = 'form-control';
                input.name = wrap.dataset.field; input.placeholder = 'Enter beneficiary full name';
                input.value = current[i] || saved[i] || '';
                div.append(label, input);
                wrap.appendChild(div);
            }
        }

        function renderAssociations(card) {
            const container = card.querySelector('.sb-assoc-container');
            const idx = card.dataset.idx;
            const count = parseInt(card.querySelector('.sb-assoc-count').value, 10) || 0;
            const old = Array.from(container.querySelectorAll('.association-row')).map(r => ({
                n: r.querySelector('input[name$="[association_names][]"]').value,
                a: r.querySelector('input[name$="[association_addresses][]"]').value,
            }));
            container.innerHTML = '';
            for (let i = 0; i < count; i++) {
                const row = document.createElement('div');
                row.className = 'row association-row mb-2';
                row.innerHTML =
                    '<div class="col-md-6 mb-2"><label class="form-label">Name of Association #' + (i + 1) + '</label>' +
                    '<input type="text" name="proponents[' + idx + '][association_names][]" class="form-control" placeholder="Enter association name"></div>' +
                    '<div class="col-md-6 mb-2"><label class="form-label">Association Address #' + (i + 1) + '</label>' +
                    '<input type="text" name="proponents[' + idx + '][association_addresses][]" class="form-control" placeholder="Enter association address"></div>';
                row.querySelectorAll('input')[0].value = old[i] ? old[i].n : '';
                row.querySelectorAll('input')[1].value = old[i] ? old[i].a : '';
                container.appendChild(row);
            }
        }

        function updateLiquidation(card) {
            const type = card.querySelector('.sb-ptype').value;
            const turnover = card.querySelector('.sb-turnover').value;
            const preview = card.querySelector('.sb-liquidation-preview');
            if (!turnover) { preview.textContent = ''; return; }
            if (!type) { preview.textContent = 'Select a proponent type to calculate the liquidation deadline'; return; }
            const days = type === 'LGU-associated' ? 10 : 60;
            const d = new Date(turnover + 'T00:00:00'); d.setDate(d.getDate() + days);
            const m = new Date(turnover + 'T00:00:00'); m.setMonth(m.getMonth() + 6);
            // Local date parts (toISOString would shift the day back in timezones ahead of UTC, e.g. Asia/Manila).
            card.querySelector('.sb-monitoring').value = m.getFullYear() + '-' + String(m.getMonth() + 1).padStart(2, '0') + '-' + String(m.getDate()).padStart(2, '0');
            preview.textContent = 'Liquidation deadline: ' + d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) + ' (' + days + ' days from turnover)';
        }

        // ── Location (province → municipality → barangay → coordinates) ───────
        const provinceCodes = {};
        async function getJson(url) { const r = await fetch(url, { headers: { Accept: 'application/json' } }); return r.json(); }
        async function provinceCode(name) {
            if (!name) return null;
            if (!(name in provinceCodes)) {
                const res = await getJson('api/get-locations.php?action=provinces');
                (res.data || []).forEach(p => { provinceCodes[p.name] = p.code; });
            }
            return provinceCodes[name] || null;
        }

        async function loadMunicipalities(card) {
            const muni = card.querySelector('.geo-muni');
            const brgy = card.querySelector('.geo-brgy');
            const wanted = muni.dataset.pending !== undefined ? muni.dataset.pending : (card.dataset.savedMuni || '');
            const code = await provinceCode(provinceInput ? provinceInput.value : '');
            if (!code) {
                muni.innerHTML = '<option value="">Select Province first</option>'; muni.disabled = true;
                brgy.innerHTML = '<option value="">Select Municipality first</option>'; brgy.disabled = true;
                return;
            }
            const res = await getJson('api/get-locations.php?action=cities&province_code=' + encodeURIComponent(code));
            muni.innerHTML = '<option value="">Select Municipality/City</option>';
            (res.data || []).forEach(c => {
                const o = document.createElement('option');
                o.value = c.name; o.textContent = c.name; o.dataset.code = c.code;
                if (c.name === wanted) o.selected = true;
                muni.appendChild(o);
            });
            muni.disabled = false;
            delete muni.dataset.pending;
            if (muni.value) await loadBarangays(card, brgy.dataset.pending !== undefined ? brgy.dataset.pending : (card.dataset.savedBrgy || ''));
            else { brgy.innerHTML = '<option value="">Select Municipality first</option>'; brgy.disabled = true; }
        }

        async function loadBarangays(card, wanted) {
            const muni = card.querySelector('.geo-muni');
            const brgy = card.querySelector('.geo-brgy');
            const code = muni.selectedOptions[0] && muni.selectedOptions[0].dataset.code;
            if (!code) { brgy.innerHTML = '<option value="">Select Municipality first</option>'; brgy.disabled = true; return; }
            brgy.innerHTML = '<option value="">Loading barangays...</option>'; brgy.disabled = true;
            const res = await getJson('api/get-locations.php?action=barangays&city_code=' + encodeURIComponent(code));
            brgy.innerHTML = '<option value="">Select Barangay</option>';
            (res.data || []).forEach(b => {
                const o = document.createElement('option');
                o.value = b.name; o.textContent = b.name;
                if (b.name === wanted) o.selected = true;
                brgy.appendChild(o);
            });
            brgy.disabled = false;
            delete brgy.dataset.pending;
        }

        async function geocode(card) {
            const muni = card.querySelector('.geo-muni').value;
            const status = card.querySelector('.geo-status');
            if (!muni) { status.className = 'geo-status d-block mt-1 text-warning'; status.textContent = 'Select a municipality first.'; return; }
            const params = new URLSearchParams({ municipality: muni, province: provinceInput ? provinceInput.value : '' });
            const brgy = card.querySelector('.geo-brgy').value;
            if (brgy) params.append('barangay', brgy);
            status.className = 'geo-status d-block mt-1 text-info'; status.textContent = 'Fetching coordinates...';
            try {
                const res = await getJson('api/geocode.php?' + params.toString());
                if (res.success) {
                    card.querySelector('.geo-lat').value = res.latitude.toFixed(8);
                    card.querySelector('.geo-lng').value = res.longitude.toFixed(8);
                    status.className = 'geo-status d-block mt-1 text-success'; status.textContent = 'Coordinates filled in.';
                } else {
                    status.className = 'geo-status d-block mt-1 text-warning'; status.textContent = res.message || 'No coordinates found.';
                }
            } catch (e) {
                status.className = 'geo-status d-block mt-1 text-danger'; status.textContent = 'Geocoding failed. Enter coordinates manually.';
            }
        }

        function initCard(card) {
            if (card.dataset.ready) return;
            card.dataset.ready = '1';
            if (card.classList.contains('sb-proponent')) {
                renderNameFields(card);
                updateLiquidation(card);
            }
            loadMunicipalities(card);
        }

        // ── Events (delegated so cloned cards work with no extra wiring) ─────
        form.addEventListener('input', e => {
            const t = e.target;
            const card = t.closest('.sb-card');
            if (t.id === 'total_budget' || t.classList.contains('share-input')) updateMeter();
            if (!card) return;
            if (t.classList.contains('sb-total-ben')) renderNameFields(card);
            if (t.classList.contains('sb-assoc-count')) renderAssociations(card);
            if (t.classList.contains('sb-turnover')) updateLiquidation(card);
            if (t.classList.contains('sb-name')) { card.querySelector('.sb-card-title').textContent = t.value.trim() || 'New proponent'; refreshProponentOptions(); }
            if (t.classList.contains('sb-first') || t.classList.contains('sb-last')) {
                const n = (card.querySelector('.sb-first').value + ' ' + card.querySelector('.sb-last').value).trim();
                card.querySelector('.sb-card-title').textContent = n || 'New beneficiary';
            }
            if (t.classList.contains('sb-male') || t.classList.contains('sb-female') || t.classList.contains('sb-total-ben')) {
                const total = parseInt(card.querySelector('.sb-total-ben').value, 10) || 0;
                const over = (parseInt(card.querySelector('.sb-male').value, 10) || 0) + (parseInt(card.querySelector('.sb-female').value, 10) || 0) > total;
                [card.querySelector('.sb-male'), card.querySelector('.sb-female')].forEach(i => i.setCustomValidity(over ? 'Male + Female cannot exceed Total' : ''));
            }
        });

        form.addEventListener('change', e => {
            const t = e.target;
            const card = t.closest('.sb-card');
            if (t === provinceInput) {
                document.querySelectorAll('.sb-nofo-label').forEach(l => { if (OFFICE_LABELS[t.value]) l.textContent = OFFICE_LABELS[t.value]; });
                document.querySelectorAll('.sb-card').forEach(c => {
                    c.querySelector('.geo-muni').dataset.pending = '';
                    c.querySelector('.geo-brgy').dataset.pending = '';
                    loadMunicipalities(c);
                });
                return;
            }
            if (!card) return;
            if (t.classList.contains('sb-ptype')) updateLiquidation(card);
            if (t.classList.contains('sb-turnover')) updateLiquidation(card);
            if (t.classList.contains('geo-muni')) {
                card.querySelector('.geo-brgy').dataset.pending = '';
                loadBarangays(card, '');
            }
            if (t.classList.contains('geo-brgy') && t.value) geocode(card);
            if (t.classList.contains('sb-bene-proponent')) t.dataset.selected = t.value;
        });

        form.addEventListener('click', e => {
            const fetchBtn = e.target.closest('.geo-fetch');
            if (fetchBtn) { geocode(fetchBtn.closest('.sb-card')); return; }
            const remove = e.target.closest('.sb-remove');
            if (!remove) return;
            const card = remove.closest('.sb-card');
            const isProponent = card.classList.contains('sb-proponent');
            if (isProponent && proponentWrap.querySelectorAll('.sb-proponent').length === 1) { alert('A shared budget needs at least one Proponent.'); return; }
            if (!isProponent && beneficiaryWrap.querySelectorAll('.sb-beneficiary').length === 1) { alert('A shared budget needs at least one Beneficiary.'); return; }
            const msg = remove.dataset.hasId === '1'
                ? 'Remove this record from the shared budget? The record itself stays in the system, but its share no longer counts toward this budget.'
                : 'Remove this card?';
            if (!confirm(msg)) return;
            card.remove();
            updateMeter();
            refreshProponentOptions();
        });

        // Open a collapsed card when the browser needs to show one of its invalid fields.
        form.addEventListener('invalid', e => {
            const body = e.target.closest('.collapse');
            if (body && !body.classList.contains('show')) bootstrap.Collapse.getOrCreateInstance(body, { toggle: false }).show();
        }, true);

        function addCard(templateId, wrap) {
            const idx = nextIdx++;
            const html = document.getElementById(templateId).innerHTML
                .replaceAll('__IDX__', 'j' + idx).replaceAll('__KEY__', 'j' + (nextKey++));
            const holder = document.createElement('div');
            holder.innerHTML = html.trim();
            const card = holder.firstElementChild;
            wrap.appendChild(card);
            initCard(card);
            refreshProponentOptions();
            updateMeter();
            card.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        document.getElementById('addProponent').addEventListener('click', () => addCard('tplProponent', proponentWrap));
        document.getElementById('addBeneficiary').addEventListener('click', () => addCard('tplBeneficiary', beneficiaryWrap));

        // Block saving while the shares and the total disagree, with a clear message.
        form.addEventListener('submit', e => {
            const total = Math.round((parseFloat(document.getElementById('total_budget').value) || 0) * 100);
            let allocated = 0;
            document.querySelectorAll('.share-input').forEach(i => { allocated += Math.round((parseFloat(i.value) || 0) * 100); });
            if (allocated !== total) {
                e.preventDefault();
                alert('The shares add up to ' + peso(allocated / 100) + ' but the total budget is ' + peso(total / 100) + '. They must match exactly before you can save.');
            }
        });

        // Start-up
        document.querySelectorAll('.sb-card').forEach(initCard);
        refreshProponentOptions();
        updateMeter();
    })();
    </script>
    <?php include 'includes/ux-utilities.php'; ?>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
