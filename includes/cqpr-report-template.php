<?php
$db = Database::getInstance()->getConnection();

$cqprDateFrom = $dateFrom;
$cqprDateTo = $dateTo;
$regionalOffice = $_GET['regional_office'] ?? 'NIR';

$dateCondition = '';
$params = [];

if ($cqprDateFrom && $cqprDateTo) {
    $dateCondition = " AND date_approved BETWEEN :date_from AND :date_to";
    $params[':date_from'] = $cqprDateFrom;
    $params[':date_to'] = $cqprDateTo;
} elseif ($cqprDateFrom) {
    $dateCondition = " AND date_approved >= :date_from";
    $params[':date_from'] = $cqprDateFrom;
} elseif ($cqprDateTo) {
    $dateCondition = " AND date_approved <= :date_to";
    $params[':date_to'] = $cqprDateTo;
}

// Get proponents data (Group projects)
$proponentsSql = "SELECT 
    p.id,
    p.project_title as name_nature_of_project,
    p.proponent_name as name_of_implementor,
    p.recipient_barangays as barangay,
    '' as city_municipality,
    'Negros Occidental' as province,
    p.district,
    '' as income_class,
    CASE p.category 
        WHEN 'Formation' THEN 'F'
        WHEN 'Enhancement' THEN 'E'
        WHEN 'Restoration' THEN 'R'
        ELSE ''
    END as purpose_of_project,
    'G' as type_of_project,
    p.total_beneficiaries,
    p.female_beneficiaries as female,
    p.type_of_beneficiaries as beneficiary_type,
    p.amount as amount_released,
    p.date_check_release as date_released,
    p.source_of_funds,
    '' as convergence_project,
    p.status as project_status,
    'proponent' as source_table
FROM proponents p
WHERE p.status IN ('approved', 'implemented', 'liquidated', 'monitored')" . $dateCondition . "
ORDER BY p.date_approved DESC";

$stmt = $db->prepare($proponentsSql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$proponents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get beneficiaries data (Individual projects)
$beneficiariesSql = "SELECT 
    b.id,
    b.project_name as name_nature_of_project,
    CONCAT(b.first_name, ' ', b.last_name) as name_of_implementor,
    b.barangay,
    b.municipality as city_municipality,
    'Negros Occidental' as province,
    '' as district,
    '' as income_class,
    '' as purpose_of_project,
    'I' as type_of_project,
    1 as total_beneficiaries,
    CASE WHEN b.gender = 'Female' THEN 1 ELSE 0 END as female,
    b.type_of_worker as beneficiary_type,
    b.amount_worth as amount_released,
    b.date_approved as date_released,
    '' as source_of_funds,
    '' as convergence_project,
    b.status as project_status,
    'beneficiary' as source_table
FROM beneficiaries b
WHERE b.status IN ('approved', 'implemented', 'monitored')" . $dateCondition . "
ORDER BY b.date_approved DESC";

$stmt = $db->prepare($beneficiariesSql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$beneficiaries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Combine records
$cqprData = array_merge($proponents, $beneficiaries);

// Calculate totals
$totalBeneficiaries = 0;
$totalFemale = 0;
$totalAmount = 0;

foreach ($cqprData as $record) {
    $totalBeneficiaries += (int) $record['total_beneficiaries'];
    $totalFemale += (int) $record['female'];
    $totalAmount += (float) $record['amount_released'];
}
?>

<style>
    .cqpr-report {
        font-family: Arial, sans-serif;
        font-size: 10px;
    }
    .cqpr-header {
        text-align: center;
        margin-bottom: 20px;
    }
    .cqpr-header h4 {
        margin: 5px 0;
        font-size: 12px;
    }
    .cqpr-header .doc-ref {
        text-align: right;
        font-size: 9px;
    }
    .cqpr-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9px;
    }
    .cqpr-table th, .cqpr-table td {
        border: 1px solid #000;
        padding: 3px 5px;
        vertical-align: middle;
    }
    .cqpr-table th {
        background-color: #f0f0f0;
        font-weight: bold;
        text-align: center;
    }
    .cqpr-table .text-center { text-align: center; }
    .cqpr-table .text-right { text-align: right; }
    .cqpr-notes {
        margin-top: 15px;
        font-size: 9px;
    }
    .cqpr-signatures {
        margin-top: 30px;
        display: flex;
        justify-content: space-between;
    }
    .cqpr-signature-block {
        width: 45%;
    }
    @media print {
        .cqpr-report { font-size: 8px; }
        .cqpr-table { font-size: 7px; }
        .no-print { display: none !important; }
    }
</style>

<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center no-print">
        <h5 class="mb-0">
            <i class="bi bi-file-earmark-spreadsheet"></i> CQPR - Consolidated Quarterly Progress Report
            <?php if ($cqprDateFrom && $cqprDateTo): ?>
            <small class="text-muted">(<?php echo date('M d, Y', strtotime($cqprDateFrom)); ?> - <?php echo date('M d, Y', strtotime($cqprDateTo)); ?>)</small>
            <?php endif; ?>
        </h5>
        <div class="d-flex gap-2">
            <button onclick="exportCQPRToExcel()" class="btn btn-sm btn-success" title="Export as Excel">
                <i class="bi bi-file-earmark-excel"></i> Excel
            </button>
            <button onclick="window.print()" class="btn btn-sm btn-primary" title="Print">
                <i class="bi bi-printer"></i> Print
            </button>
        </div>
    </div>
    <div class="card-body p-2">
        <div class="cqpr-report" id="cqprReportContent">
            <div class="cqpr-header">
                <div class="doc-ref">
                    <strong>DOLE-QF-COP-05-01</strong><br>
                    Revision No. 07<br>
                    Effective Date: 24 March 2026
                </div>
                <h4><strong>Consolidated Quarterly Progress Report</strong></h4>
                <h4>DOLE Integrated Livelihood and Emergency Employment Program (DILEEP)</h4>
                <p>From <u>&nbsp;<?php echo $cqprDateFrom ? date('F d, Y', strtotime($cqprDateFrom)) : '_______________'; ?>&nbsp;</u> 
                   to <u>&nbsp;<?php echo $cqprDateTo ? date('F d, Y', strtotime($cqprDateTo)) : '_______________'; ?>&nbsp;</u>, 20<?php echo date('y'); ?></p>
            </div>
            
            <p><strong>DOLE Regional Office No.</strong> <u>&nbsp;<?php echo htmlspecialchars($regionalOffice); ?>&nbsp;</u></p>
            <p><strong>A. DOLE Integrated Livelihood Program</strong></p>
            
            <?php if (empty($cqprData)): ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-circle"></i> No records found for the selected date range.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="cqpr-table" id="cqprTable">
                    <thead>
                        <tr>
                            <th rowspan="2">Name & Nature of Project</th>
                            <th rowspan="2">Name of Implementor</th>
                            <th colspan="5">Project Location</th>
                            <th rowspan="2">Purpose of Project<sup>a</sup></th>
                            <th rowspan="2">Type of Project<sup>b</sup></th>
                            <th colspan="3">Beneficiaries</th>
                            <th rowspan="2">Amount Released (Php)</th>
                            <th rowspan="2">Date Released</th>
                            <th colspan="3">Fund Source</th>
                            <th rowspan="2">Convergence Project/Partner<sup>d</sup></th>
                            <th rowspan="2">Project Status<sup>e</sup></th>
                        </tr>
                        <tr>
                            <th>Barangay</th>
                            <th>City/Municipality</th>
                            <th>Province</th>
                            <th>District</th>
                            <th>Income Class</th>
                            <th>Total</th>
                            <th>Female</th>
                            <th>Type<sup>c</sup></th>
                            <th>Regular GAA, SN</th>
                            <th>Convergence Fund</th>
                            <th>Fund Others</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cqprData as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['name_nature_of_project'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['name_of_implementor'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['barangay'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['city_municipality'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['province'] ?? ''); ?></td>
                            <td class="text-center"><?php echo htmlspecialchars($row['district'] ?? ''); ?></td>
                            <td class="text-center"><?php echo htmlspecialchars($row['income_class'] ?? ''); ?></td>
                            <td class="text-center"><?php echo htmlspecialchars($row['purpose_of_project'] ?? ''); ?></td>
                            <td class="text-center"><?php echo htmlspecialchars($row['type_of_project'] ?? ''); ?></td>
                            <td class="text-center"><?php echo number_format($row['total_beneficiaries'] ?? 0); ?></td>
                            <td class="text-center"><?php echo number_format($row['female'] ?? 0); ?></td>
                            <td><?php echo htmlspecialchars($row['beneficiary_type'] ?? ''); ?></td>
                            <td class="text-right"><?php echo number_format($row['amount_released'] ?? 0, 2); ?></td>
                            <td class="text-center"><?php echo $row['date_released'] ? date('m/d/Y', strtotime($row['date_released'])) : ''; ?></td>
                            <td class="text-center"><?php echo ($row['source_of_funds'] === 'GAA') ? '✓' : ''; ?></td>
                            <td class="text-center"><?php echo ($row['source_of_funds'] === 'Centrally Managed Fund') ? '✓' : ''; ?></td>
                            <td class="text-center"><?php echo ($row['source_of_funds'] === 'Other') ? '✓' : ''; ?></td>
                            <td><?php echo htmlspecialchars($row['convergence_project'] ?? ''); ?></td>
                            <td class="text-center"><?php echo ucfirst($row['project_status'] ?? ''); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="fw-bold">
                            <td colspan="9" class="text-center"><strong>TOTAL</strong></td>
                            <td class="text-center"><strong><?php echo number_format($totalBeneficiaries); ?></strong></td>
                            <td class="text-center"><strong><?php echo number_format($totalFemale); ?></strong></td>
                            <td></td>
                            <td class="text-right"><strong><?php echo number_format($totalAmount, 2); ?></strong></td>
                            <td colspan="6"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <div class="cqpr-notes">
                <p><strong>Notes:</strong></p>
                <p><sup>a</sup> - Purpose of Project (Use Acronym): Kabuhayan Formation (F), Kabuhayan Enhancement (E), Kabuhayan Restoration (R)</p>
                <p><sup>b</sup> - Type of Project (Use Acronym): Individual (I), Group (G)</p>
                <p><sup>c</sup> - Type of Beneficiaries: Marginalized and Landless Farmers, Marginalized Fisherfolks, Self-employed with Insufficient Income, Parents of Child Laborers, Displaced Workers, among others</p>
                <p><sup>d</sup> - Convergence Project/Partner: DSWD Project Lawa at Saka, Zero Hunger, DILG KAPAG Project, MMDA Brigade Eskwela, TAU-DLP, WDDP-Prov, among others</p>
                <p><sup>e</sup> - Project Status: Cheque released to partner; Assistance awarded or released to beneficiaries; Ongoing procurement; Operational; among others</p>
            </div>
            
            <div class="cqpr-signatures">
                <div class="cqpr-signature-block">
                    <p>Prepared by:</p>
                    <br><br>
                    <p style="border-top: 1px solid #000; display: inline-block; min-width: 200px;">(Name)</p>
                    <p>(Position Title)</p>
                </div>
                <div class="cqpr-signature-block text-end">
                    <p>Approved by:</p>
                    <br><br>
                    <p style="border-top: 1px solid #000; display: inline-block; min-width: 200px;">(Name)</p>
                    <p>Director</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
function exportCQPRToExcel() {
    const table = document.getElementById('cqprTable');
    if (!table) {
        alert('No data to export');
        return;
    }
    
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.table_to_sheet(table);
    
    // Set column widths
    ws['!cols'] = [
        {wch: 25}, {wch: 20}, {wch: 15}, {wch: 15}, {wch: 15}, {wch: 8}, {wch: 8},
        {wch: 8}, {wch: 8}, {wch: 8}, {wch: 8}, {wch: 15}, {wch: 12}, {wch: 10},
        {wch: 8}, {wch: 8}, {wch: 8}, {wch: 15}, {wch: 10}
    ];
    
    XLSX.utils.book_append_sheet(wb, ws, 'CQPR Report');
    
    const dateStr = new Date().toISOString().slice(0, 10);
    XLSX.writeFile(wb, `CQPR_Report_${dateStr}.xlsx`);
}
</script>
