<?php
// includes/shared-budget-cards.php
// HTML for the Proponent and Individual cards inside shared-budget-form.php.
// Every field of proponent-form.php and beneficiary-form.php is here, with names
// prefixed proponents[<idx>][...] / beneficiaries[<idx>][...] so one form can hold many.
// "__IDX__" / "__KEY__" are placeholders the page's JavaScript replaces when it clones a card.

function sb_h($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Checked state for a checkbox list whose posted/stored value may be an array or a ", "-joined string. */
function sb_selected_list($value): array {
    if (is_array($value)) {
        return array_map('trim', $value);
    }
    $value = trim((string) $value);
    return $value === '' ? [] : array_map('trim', explode(',', $value));
}

function sb_render_proponent_card(string $idx, array $v, string $province): string {
    $n = "proponents[{$idx}]";
    $selBeneficiaryTypes = sb_selected_list($v['type_of_beneficiaries'] ?? []);
    $selWorkerTypes      = sb_selected_list($v['type_of_workers'] ?? []);
    $names               = array_values((array) ($v['beneficiary_full_names'] ?? []));
    $assocNames          = array_values((array) ($v['association_names'] ?? []));
    $assocAddresses      = array_values((array) ($v['association_addresses'] ?? []));
    $status              = $v['status'] ?? 'pending';
    $title               = trim(($v['proponent_name'] ?? '') . '');
    ob_start();
?>
<div class="card sb-card sb-proponent mb-3" data-kind="proponent" data-idx="<?php echo sb_h($idx); ?>">
    <div class="card-header d-flex align-items-center gap-2 flex-wrap">
        <span class="badge bg-success"><i class="bi bi-building"></i> Proponent</span>
        <strong class="sb-card-title flex-grow-1"><?php echo $title !== '' ? sb_h($title) : 'New proponent'; ?></strong>
        <span class="badge bg-light text-dark border sb-card-share">&#8369;0.00</span>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#sb-body-p<?php echo sb_h($idx); ?>" aria-expanded="true">
            <i class="bi bi-chevron-expand"></i> Show / hide
        </button>
        <button type="button" class="btn btn-sm btn-outline-danger sb-remove" data-has-id="<?php echo !empty($v['id']) ? '1' : '0'; ?>">
            <i class="bi bi-x-circle"></i> Remove
        </button>
    </div>
    <div class="collapse show" id="sb-body-p<?php echo sb_h($idx); ?>">
    <div class="card-body">
        <input type="hidden" name="<?php echo $n; ?>[id]" value="<?php echo sb_h($v['id'] ?? ''); ?>">
        <input type="hidden" name="<?php echo $n; ?>[key]" class="sb-key" value="<?php echo sb_h($v['key'] ?? ''); ?>">

        <div class="form-section">
            <h5><i class="bi bi-info-circle"></i> Basic Information</h5>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Proponent Type <span class="text-danger">*</span></label>
                    <select name="<?php echo $n; ?>[proponent_type]" class="form-select sb-ptype" required>
                        <option value="">Select Type</option>
                        <?php foreach (SharedBudget::PROPONENT_TYPES as $t): ?>
                        <option value="<?php echo sb_h($t); ?>" <?php echo ($v['proponent_type'] ?? '') === $t ? 'selected' : ''; ?>><?php echo sb_h($t); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">LGU: 10 days liquidation | Non-LGU: 60 days</small>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Date Received (DILP)</label>
                    <input type="date" name="<?php echo $n; ?>[date_received]" class="form-control" value="<?php echo sb_h($v['date_received'] ?? ''); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Control Number</label>
                    <input type="text" name="<?php echo $n; ?>[control_number]" class="form-control" value="<?php echo sb_h($v['control_number'] ?? ''); ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">District</label>
                    <input type="text" name="<?php echo $n; ?>[district]" class="form-control" value="<?php echo sb_h($v['district'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <div class="form-section">
            <h5><i class="bi bi-building"></i> Proponent Details</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Name of Proponent/ACP <span class="text-danger">*</span></label>
                    <input type="text" name="<?php echo $n; ?>[proponent_name]" class="form-control sb-name" required value="<?php echo sb_h($v['proponent_name'] ?? ''); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Project Title <span class="text-danger">*</span></label>
                    <input type="text" name="<?php echo $n; ?>[project_title]" class="form-control" required value="<?php echo sb_h($v['project_title'] ?? ''); ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Share of the budget (&#8369;) <span class="text-danger">*</span></label>
                    <input type="number" name="<?php echo $n; ?>[amount]" class="form-control share-input" step="0.01" min="0.01" required value="<?php echo sb_h($v['amount'] ?? ''); ?>">
                    <small class="text-muted">Saved as this proponent's amount.</small>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Category <span class="text-danger">*</span></label>
                    <select name="<?php echo $n; ?>[category]" class="form-select" required>
                        <option value="">Select Category</option>
                        <?php foreach (SharedBudget::CATEGORIES as $c): ?>
                        <option value="<?php echo sb_h($c); ?>" <?php echo ($v['category'] ?? '') === $c ? 'selected' : ''; ?>><?php echo sb_h($c); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Number of Associations</label>
                    <input type="number" name="<?php echo $n; ?>[number_of_associations]" class="form-control sb-assoc-count" min="0" value="<?php echo sb_h($v['number_of_associations'] ?? ''); ?>">
                </div>
            </div>
            <div class="sb-assoc-container">
                <?php foreach ($assocNames as $i => $an): ?>
                <div class="row association-row mb-2">
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Name of Association #<?php echo $i + 1; ?></label>
                        <input type="text" name="<?php echo $n; ?>[association_names][]" class="form-control" placeholder="Enter association name" value="<?php echo sb_h($an); ?>">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Association Address #<?php echo $i + 1; ?></label>
                        <input type="text" name="<?php echo $n; ?>[association_addresses][]" class="form-control" placeholder="Enter association address" value="<?php echo sb_h($assocAddresses[$i] ?? ''); ?>">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-section">
            <h5><i class="bi bi-people"></i> Beneficiary Information</h5>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Total Beneficiaries <span class="text-danger">*</span></label>
                    <input type="number" name="<?php echo $n; ?>[total_beneficiaries]" class="form-control sb-total-ben" min="1" required value="<?php echo sb_h($v['total_beneficiaries'] ?? ''); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Male Beneficiaries</label>
                    <input type="number" name="<?php echo $n; ?>[male_beneficiaries]" class="form-control sb-male" min="0" value="<?php echo sb_h($v['male_beneficiaries'] ?? ''); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Female Beneficiaries</label>
                    <input type="number" name="<?php echo $n; ?>[female_beneficiaries]" class="form-control sb-female" min="0" value="<?php echo sb_h($v['female_beneficiaries'] ?? ''); ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Full Name(s) of Beneficiaries</label>
                    <div class="sb-name-fields" data-names="<?php echo sb_h(json_encode($names, JSON_UNESCAPED_UNICODE)); ?>" data-field="<?php echo sb_h($n); ?>[beneficiary_full_names][]"></div>
                    <small class="text-muted">Fields are generated automatically based on the Total Beneficiaries value.</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Recipient Barangays/ACPs</label>
                    <input type="text" name="<?php echo $n; ?>[recipient_barangays]" class="form-control" value="<?php echo sb_h($v['recipient_barangays'] ?? ''); ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Type of Beneficiaries</label>
                    <div class="row g-2">
                        <?php foreach (SharedBudget::BENEFICIARY_TYPE_OPTIONS as $i => $opt): ?>
                        <div class="col-sm-6 col-lg-4"><div class="form-check">
                            <input class="form-check-input" type="checkbox" name="<?php echo $n; ?>[type_of_beneficiaries][]" id="p<?php echo sb_h($idx); ?>_bt_<?php echo $i; ?>" value="<?php echo sb_h($opt); ?>" <?php echo in_array($opt, $selBeneficiaryTypes, true) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="p<?php echo sb_h($idx); ?>_bt_<?php echo $i; ?>"><?php echo sb_h($opt); ?></label>
                        </div></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Kinds Beneficiaries</label>
                    <div class="row g-2">
                        <?php foreach (SharedBudget::WORKER_TYPE_OPTIONS as $i => $opt): ?>
                        <div class="col-sm-6 col-lg-4"><div class="form-check">
                            <input class="form-check-input" type="checkbox" name="<?php echo $n; ?>[type_of_workers][]" id="p<?php echo sb_h($idx); ?>_wt_<?php echo $i; ?>" value="<?php echo sb_h($opt); ?>" <?php echo in_array($opt, $selWorkerTypes, true) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="p<?php echo sb_h($idx); ?>_wt_<?php echo $i; ?>"><?php echo sb_h($opt); ?></label>
                        </div></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h5><i class="bi bi-calendar-check"></i> Process Dates</h5>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">DILP Application - Date Received</label>
                    <input type="date" name="<?php echo $n; ?>[letter_of_intent_date]" class="form-control" value="<?php echo sb_h($v['letter_of_intent_date'] ?? ''); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Date Forwarded to NIR for RPMT</label>
                    <input type="date" name="<?php echo $n; ?>[date_forwarded_to_ro6]" class="form-control" value="<?php echo sb_h($v['date_forwarded_to_ro6'] ?? ''); ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label">RPMT Findings</label>
                    <textarea name="<?php echo $n; ?>[rpmt_findings]" class="form-control" rows="2"><?php echo sb_h($v['rpmt_findings'] ?? ''); ?></textarea>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Date Complied by Proponent</label>
                    <input type="date" name="<?php echo $n; ?>[date_complied_by_proponent_nofo]" class="form-control" value="<?php echo sb_h($v['date_complied_by_proponent_nofo'] ?? ''); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label sb-nofo-label"><?php echo sb_h(Auth::getForwardedToOfficeLabel($province)); ?></label>
                    <input type="date" name="<?php echo $n; ?>[date_forwarded_to_nofo]" class="form-control" value="<?php echo sb_h($v['date_forwarded_to_nofo'] ?? ''); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Date Approved</label>
                    <input type="date" name="<?php echo $n; ?>[date_approved]" class="form-control" value="<?php echo sb_h($v['date_approved'] ?? ''); ?>">
                </div>
            </div>
            <p class="text-muted small mb-0"><i class="bi bi-info-circle"></i> Application return history can be added in the Proponent form after saving.</p>
        </div>

        <div class="form-section">
            <h5><i class="bi bi-cash-stack"></i> Financial Information</h5>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Date of Check Release</label>
                    <input type="date" name="<?php echo $n; ?>[date_check_release]" class="form-control" value="<?php echo sb_h($v['date_check_release'] ?? ''); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Check Number</label>
                    <input type="text" name="<?php echo $n; ?>[check_number]" class="form-control" value="<?php echo sb_h($v['check_number'] ?? ''); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Check Date Issued</label>
                    <input type="date" name="<?php echo $n; ?>[check_date_issued]" class="form-control" value="<?php echo sb_h($v['check_date_issued'] ?? ''); ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Official Receipt (OR) Number</label>
                    <input type="text" name="<?php echo $n; ?>[or_number]" class="form-control" value="<?php echo sb_h($v['or_number'] ?? ''); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">OR Date Issued</label>
                    <input type="date" name="<?php echo $n; ?>[or_date_issued]" class="form-control" value="<?php echo sb_h($v['or_date_issued'] ?? ''); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Source of Funds</label>
                    <select name="<?php echo $n; ?>[source_of_funds]" class="form-select">
                        <option value="">Select Source</option>
                        <option value="GAA" <?php echo ($v['source_of_funds'] ?? '') === 'GAA' ? 'selected' : ''; ?>>GAA (General Appropriation Act)</option>
                        <option value="Centrally Managed Fund" <?php echo ($v['source_of_funds'] ?? '') === 'Centrally Managed Fund' ? 'selected' : ''; ?>>Centrally Managed Fund/Central Office</option>
                        <option value="Other" <?php echo ($v['source_of_funds'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h5><i class="bi bi-clipboard-check"></i> Implementation &amp; Monitoring</h5>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Date of Turn-over</label>
                    <input type="date" name="<?php echo $n; ?>[date_turnover]" class="form-control sb-turnover" value="<?php echo sb_h($v['date_turnover'] ?? ''); ?>">
                    <small class="text-muted">Triggers liquidation deadline and monitoring date</small>
                    <small class="sb-liquidation-preview text-info d-block mt-1" style="font-weight:600;"></small>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Date Implemented</label>
                    <input type="date" name="<?php echo $n; ?>[date_implemented]" class="form-control" value="<?php echo sb_h($v['date_implemented'] ?? ''); ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Date Liquidated</label>
                    <input type="date" name="<?php echo $n; ?>[date_liquidated]" class="form-control" value="<?php echo sb_h($v['date_liquidated'] ?? ''); ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Date of Monitoring</label>
                    <input type="date" name="<?php echo $n; ?>[date_monitoring]" class="form-control sb-monitoring" value="<?php echo sb_h($v['date_monitoring'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <div class="form-section">
            <h5><i class="bi bi-geo-alt"></i> Location (For Map Visualization)</h5>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Municipality/City</label>
                    <select class="form-select geo-muni" disabled><option value="">Select Province first</option></select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Barangay</label>
                    <select class="form-select geo-brgy" disabled><option value="">Select Municipality first</option></select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Latitude</label>
                    <input type="text" name="<?php echo $n; ?>[latitude]" class="form-control geo-lat" placeholder="e.g., 10.5" value="<?php echo sb_h($v['latitude'] ?? ''); ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Longitude</label>
                    <input type="text" name="<?php echo $n; ?>[longitude]" class="form-control geo-lng" placeholder="e.g., 123.0" value="<?php echo sb_h($v['longitude'] ?? ''); ?>">
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary geo-fetch"><i class="bi bi-geo-alt-fill"></i> Auto-fill Coordinates</button>
            <small class="geo-status d-block mt-1"></small>
        </div>

        <div class="form-section mb-0">
            <h5><i class="bi bi-flag"></i> Status &amp; Comments</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Current Status <span class="text-danger">*</span></label>
                    <select name="<?php echo $n; ?>[status]" class="form-select" required>
                        <?php foreach (SharedBudget::STATUSES_PROPONENT as $s): ?>
                        <option value="<?php echo $s; ?>" <?php echo $status === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-12 mb-3">
                    <label class="form-label">Noted Findings/Comments</label>
                    <textarea name="<?php echo $n; ?>[noted_findings]" class="form-control" rows="3"><?php echo sb_h($v['noted_findings'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>
<?php
    return ob_get_clean();
}

function sb_render_beneficiary_card(string $idx, array $v, string $province): string {
    $n = "beneficiaries[{$idx}]";
    $selBeneficiaryTypes = sb_selected_list($v['type_of_beneficiaries'] ?? []);
    $selWorkerTypes      = sb_selected_list($v['type_of_worker'] ?? []);
    $status              = $v['status'] ?? 'pending';
    $title               = trim(($v['first_name'] ?? '') . ' ' . ($v['last_name'] ?? ''));
    ob_start();
?>
<div class="card sb-card sb-beneficiary mb-3" data-kind="beneficiary" data-idx="<?php echo sb_h($idx); ?>"
     data-saved-muni="<?php echo sb_h($v['municipality'] ?? ''); ?>" data-saved-brgy="<?php echo sb_h($v['barangay'] ?? ''); ?>">
    <div class="card-header d-flex align-items-center gap-2 flex-wrap">
        <span class="badge bg-primary"><i class="bi bi-person"></i> Individual</span>
        <strong class="sb-card-title flex-grow-1"><?php echo $title !== '' ? sb_h($title) : 'New individual'; ?></strong>
        <span class="badge bg-light text-dark border sb-card-share">&#8369;0.00</span>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#sb-body-b<?php echo sb_h($idx); ?>" aria-expanded="true">
            <i class="bi bi-chevron-expand"></i> Show / hide
        </button>
        <button type="button" class="btn btn-sm btn-outline-danger sb-remove" data-has-id="<?php echo !empty($v['id']) ? '1' : '0'; ?>">
            <i class="bi bi-x-circle"></i> Remove
        </button>
    </div>
    <div class="collapse show" id="sb-body-b<?php echo sb_h($idx); ?>">
    <div class="card-body">
        <input type="hidden" name="<?php echo $n; ?>[id]" value="<?php echo sb_h($v['id'] ?? ''); ?>">

        <div class="form-section">
            <h5><i class="bi bi-person"></i> Personal Information</h5>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" name="<?php echo $n; ?>[last_name]" class="form-control sb-last" required value="<?php echo sb_h($v['last_name'] ?? ''); ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="<?php echo $n; ?>[first_name]" class="form-control sb-first" required value="<?php echo sb_h($v['first_name'] ?? ''); ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Middle Name</label>
                    <input type="text" name="<?php echo $n; ?>[middle_name]" class="form-control" value="<?php echo sb_h($v['middle_name'] ?? ''); ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Suffix</label>
                    <input type="text" name="<?php echo $n; ?>[suffix]" class="form-control" placeholder="Jr., Sr., III" value="<?php echo sb_h($v['suffix'] ?? ''); ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Gender <span class="text-danger">*</span></label>
                    <select name="<?php echo $n; ?>[gender]" class="form-select" required>
                        <option value="">Select Gender</option>
                        <option value="Male" <?php echo ($v['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo ($v['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Municipality/City <span class="text-danger">*</span></label>
                    <select name="<?php echo $n; ?>[municipality]" class="form-select geo-muni" required disabled><option value="">Select Province first</option></select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Barangay <span class="text-danger">*</span></label>
                    <select name="<?php echo $n; ?>[barangay]" class="form-select geo-brgy" required disabled><option value="">Select Municipality first</option></select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Contact Number</label>
                    <input type="text" name="<?php echo $n; ?>[contact_number]" class="form-control" value="<?php echo sb_h($v['contact_number'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <div class="form-section">
            <h5><i class="bi bi-briefcase"></i> Project Information</h5>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Project Name <span class="text-danger">*</span></label>
                    <input type="text" name="<?php echo $n; ?>[project_name]" class="form-control" required value="<?php echo sb_h($v['project_name'] ?? ''); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Share of the budget (&#8369;) <span class="text-danger">*</span></label>
                    <input type="number" name="<?php echo $n; ?>[amount_worth]" class="form-control share-input" step="0.01" min="0.01" required value="<?php echo sb_h($v['amount_worth'] ?? ''); ?>">
                    <small class="text-muted">Saved as this individual's amount.</small>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Source of Funds</label>
                    <select name="<?php echo $n; ?>[source_of_funds]" class="form-select">
                        <option value="">Select Source</option>
                        <option value="GAA" <?php echo ($v['source_of_funds'] ?? '') === 'GAA' ? 'selected' : ''; ?>>GAA (General Appropriation Act)</option>
                        <option value="Centrally Managed Fund" <?php echo ($v['source_of_funds'] ?? '') === 'Centrally Managed Fund' ? 'selected' : ''; ?>>Centrally Managed Fund/Central Office</option>
                        <option value="Other" <?php echo ($v['source_of_funds'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Linked Proponent <span class="text-danger">*</span></label>
                    <select name="<?php echo $n; ?>[proponent_key]" class="form-select sb-bene-proponent" required data-selected="<?php echo sb_h($v['proponent_key'] ?? ''); ?>">
                        <option value="">Select a proponent in this budget</option>
                    </select>
                    <small class="text-muted">Choose from the proponents listed above.</small>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Kinds of Workers</label>
                    <div class="row g-2">
                        <?php foreach (SharedBudget::WORKER_TYPE_OPTIONS as $i => $opt): ?>
                        <div class="col-sm-6 col-lg-4"><div class="form-check">
                            <input class="form-check-input" type="checkbox" name="<?php echo $n; ?>[type_of_worker][]" id="b<?php echo sb_h($idx); ?>_wt_<?php echo $i; ?>" value="<?php echo sb_h($opt); ?>" <?php echo in_array($opt, $selWorkerTypes, true) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="b<?php echo sb_h($idx); ?>_wt_<?php echo $i; ?>"><?php echo sb_h($opt); ?></label>
                        </div></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Type of Beneficiaries</label>
                    <div class="row g-2">
                        <?php foreach (SharedBudget::BENEFICIARY_TYPE_OPTIONS as $i => $opt): ?>
                        <div class="col-sm-6 col-lg-4"><div class="form-check">
                            <input class="form-check-input" type="checkbox" name="<?php echo $n; ?>[type_of_beneficiaries][]" id="b<?php echo sb_h($idx); ?>_bt_<?php echo $i; ?>" value="<?php echo sb_h($opt); ?>" <?php echo in_array($opt, $selBeneficiaryTypes, true) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="b<?php echo sb_h($idx); ?>_bt_<?php echo $i; ?>"><?php echo sb_h($opt); ?></label>
                        </div></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label">Noted Findings</label>
                    <textarea name="<?php echo $n; ?>[noted_findings]" class="form-control" rows="2"><?php echo sb_h($v['noted_findings'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h5><i class="bi bi-calendar-check"></i> Process Dates</h5>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Date Complied by Proponent</label>
                    <input type="date" name="<?php echo $n; ?>[date_complied_by_proponent]" class="form-control" value="<?php echo sb_h($v['date_complied_by_proponent'] ?? ''); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Date Forwarded to NIR for RPMT</label>
                    <input type="date" name="<?php echo $n; ?>[date_forwarded_to_ro6]" class="form-control" value="<?php echo sb_h($v['date_forwarded_to_ro6'] ?? ''); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Date Approved</label>
                    <input type="date" name="<?php echo $n; ?>[date_approved]" class="form-control" value="<?php echo sb_h($v['date_approved'] ?? ''); ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label">RPMT Findings</label>
                    <textarea name="<?php echo $n; ?>[rpmt_findings]" class="form-control" rows="2"><?php echo sb_h($v['rpmt_findings'] ?? ''); ?></textarea>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label sb-nofo-label"><?php echo sb_h(Auth::getForwardedToOfficeLabel($province)); ?></label>
                    <input type="date" name="<?php echo $n; ?>[date_forwarded_to_nofo]" class="form-control" value="<?php echo sb_h($v['date_forwarded_to_nofo'] ?? ''); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Date of Turn-over</label>
                    <input type="date" name="<?php echo $n; ?>[date_turnover]" class="form-control" value="<?php echo sb_h($v['date_turnover'] ?? ''); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Date of Monitoring</label>
                    <input type="date" name="<?php echo $n; ?>[date_monitoring]" class="form-control" value="<?php echo sb_h($v['date_monitoring'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <div class="form-section">
            <h5><i class="bi bi-geo-alt"></i> Location (For Map Visualization)</h5>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Latitude</label>
                    <input type="text" name="<?php echo $n; ?>[latitude]" class="form-control geo-lat" placeholder="e.g., 10.5" value="<?php echo sb_h($v['latitude'] ?? ''); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Longitude</label>
                    <input type="text" name="<?php echo $n; ?>[longitude]" class="form-control geo-lng" placeholder="e.g., 123.0" value="<?php echo sb_h($v['longitude'] ?? ''); ?>">
                </div>
                <div class="col-md-4 mb-3 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-primary w-100 geo-fetch"><i class="bi bi-geo-alt-fill"></i> Auto-fill Coordinates</button>
                </div>
            </div>
            <small class="geo-status d-block"></small>
        </div>

        <div class="form-section mb-0">
            <h5><i class="bi bi-flag"></i> Status</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Current Status <span class="text-danger">*</span></label>
                    <select name="<?php echo $n; ?>[status]" class="form-select" required>
                        <?php foreach (SharedBudget::STATUSES_BENEFICIARY as $s): ?>
                        <option value="<?php echo $s; ?>" <?php echo $status === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>
<?php
    return ob_get_clean();
}
