<?php
$db = Database::getInstance()->getConnection();

$cqprDateFrom = $dateFrom;
$cqprDateTo = $dateTo;
$regionalOffice = $_GET['regional_office'] ?? 'NIR';
$cqprStatus = $selectedStatus; // whitelisted against $recordStatusOptions['cqpr'] in reports.php

// Conditions use a {t} token for the table alias, swapped in per query below.
// Do not str_replace column names: that also rewrites the :named placeholders.
$dateCondition = '';
$params = [];

if ($cqprDateFrom && $cqprDateTo) {
    $dateCondition = " AND {t}.date_approved BETWEEN :date_from AND :date_to";
    $params[':date_from'] = $cqprDateFrom;
    $params[':date_to'] = $cqprDateTo;
} elseif ($cqprDateFrom) {
    $dateCondition = " AND {t}.date_approved >= :date_from";
    $params[':date_from'] = $cqprDateFrom;
} elseif ($cqprDateTo) {
    $dateCondition = " AND {t}.date_approved <= :date_to";
    $params[':date_to'] = $cqprDateTo;
}

// Province filter from report form. A provincial user without a province sees nothing,
// the same fail-closed rule as the models' provinceScope().
$provinceCondition = '';
if (!$isCrossProvince && empty($sessionProvince)) {
    $provinceCondition = ' AND 1 = 0';
} elseif (!empty($provinceFilter)) {
    $provinceCondition = " AND {t}.province = :province";
    $params[':province'] = $provinceFilter;
}

// Fund source filter (GAA / Centrally Managed Fund / Other), whitelisted in reports.php
$fundCondition = '';
if ($sourceOfFunds !== '') {
    $fundCondition = " AND {t}.source_of_funds = :source_of_funds";
    $params[':source_of_funds'] = $sourceOfFunds;
}

// Status filter for CQPR
$proponentStatusList = "('approved', 'implemented', 'liquidated', 'monitored')";
$beneficiaryStatusList = "('approved', 'implemented', 'monitored')";
if (!empty($cqprStatus)) {
    $proponentStatusList = "(:cqpr_status)";
    $beneficiaryStatusList = "(:cqpr_status)";
    $params[':cqpr_status'] = $cqprStatus;
}

// Get proponents data (Group projects)
$proponentsSql = "SELECT 
    p.id,
    p.project_title as name_nature_of_project,
    p.proponent_name as name_of_implementor,
    p.recipient_barangays as barangay,
    '' as city_municipality,
    p.province,
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
WHERE p.status IN {$proponentStatusList}" . str_replace('{t}', 'p', $dateCondition . $provinceCondition . $fundCondition) . "
ORDER BY p.date_approved DESC";

$stmt = $db->prepare($proponentsSql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$proponents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Re-bind params without :cqpr_status for beneficiaries if status differs
$beneficiaryParams = $params;

// Get beneficiaries data (Individual projects)
// LEFT JOIN proponents so the "Name of Implementor" column can append
// " / PROPONENT NAME" when the beneficiary is linked to a group proponent.
$beneficiariesSql = "SELECT 
    b.id,
    b.project_name as name_nature_of_project,
    CASE
        WHEN p.proponent_name IS NOT NULL AND p.proponent_name <> ''
            THEN CONCAT(b.first_name, ' ', b.last_name, ' / ', p.proponent_name)
        ELSE CONCAT(b.first_name, ' ', b.last_name)
    END as name_of_implementor,
    b.barangay,
    b.municipality as city_municipality,
    b.province,
    '' as district,
    '' as income_class,
    '' as purpose_of_project,
    'I' as type_of_project,
    1 as total_beneficiaries,
    CASE WHEN b.gender = 'Female' THEN 1 ELSE 0 END as female,
    b.type_of_worker as beneficiary_type,
    b.amount_worth as amount_released,
    b.date_approved as date_released,
    b.source_of_funds,
    '' as convergence_project,
    b.status as project_status,
    'beneficiary' as source_table
FROM beneficiaries b
LEFT JOIN proponents p ON p.id = b.proponent_id
WHERE b.status IN {$beneficiaryStatusList}" . str_replace('{t}', 'b', $provinceCondition . $dateCondition . $fundCondition) . "
ORDER BY b.date_approved DESC";

$stmt = $db->prepare($beneficiariesSql);
foreach ($beneficiaryParams as $key => $value) {
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

$cqprNotes = [
    'a' => 'Purpose of Project (Use Acronym): Kabuhayan Formation (F), Kabuhayan Enhancement (E), Kabuhayan Restoration (R)',
    'b' => 'Type of Project (Use Acronym): Individual (I), Group (G)',
    'c' => 'Type of Beneficiaries: Marginalized and Landless Farmers, Marginalized Fisherfolks, Self-employed with Insufficient Income, Parents of Child Laborers, Displaced Workers, among others',
    'd' => 'Convergence Project/Partner: DSWD Project Lawa at Saka, Zero Hunger, DILG KAPAG Project, MMDA Brigade Eskwela, TAU-DLP, WDDP-Prov, among others',
    'e' => 'Project Status: Cheque released to partner; Assistance awarded or released to beneficiaries; Ongoing procurement; Operational; among others',
];
$cqprFundColumns = ['GAA' => 14, 'Centrally Managed Fund' => 15, 'Other' => 16];

// =========================================================
// Excel version of the form (DOLE-QF-COP-05-01), built from the same rows as the HTML table
// =========================================================

// Excel stores dates as days since 1899-12-30
$toExcelDate = static function (?string $date): ?int {
    $parsed = $date ? DateTime::createFromFormat('!Y-m-d', substr($date, 0, 10), new DateTimeZone('UTC')) : false;
    return $parsed ? (new DateTime('1899-12-30', new DateTimeZone('UTC')))->diff($parsed)->days : null;
};
$lastCol = 18;
$rightCell = static fn(string $text): array => array_merge(array_fill(0, $lastCol, null), [$text]);

$sheetRows = [
    $rightCell('DOLE-QF-COP-05-01'),
    $rightCell('Revision No. 07'),
    $rightCell('Effective Date: 24 March 2026'),
    ['Consolidated Quarterly Progress Report'],
    ['DOLE Integrated Livelihood and Emergency Employment Program (DILEEP)'],
    ['From ' . ($cqprDateFrom ? date('F d, Y', strtotime($cqprDateFrom)) : '_______________')
        . ' to ' . ($cqprDateTo ? date('F d, Y', strtotime($cqprDateTo)) : '_______________')],
    [],
    ['DOLE Regional Office No. ' . $regionalOffice],
    ['A. DOLE Integrated Livelihood Program'],
    ['Name & Nature of Project', 'Name of Implementor', 'Project Location', null, null, null, null,
     'Purpose of Project (a)', 'Type of Project (b)', 'Beneficiaries', null, null, 'Amount Released (Php)',
     'Date Released', 'Fund Source', null, null, 'Convergence Project/Partner (d)', 'Project Status (e)'],
    [null, null, 'Barangay', 'City/Municipality', 'Province', 'District', 'Income Class', null, null,
     'Total', 'Female', 'Type (c)', null, null, 'Regular GAA, SN', 'Convergence Fund', 'Fund Others', null, null],
];
$headerRow = 9;
$firstDataRow = count($sheetRows);
foreach ($cqprData as $row) {
    $line = [
        $row['name_nature_of_project'] ?? '', $row['name_of_implementor'] ?? '', $row['barangay'] ?? '',
        $row['city_municipality'] ?? '', $row['province'] ?? '', $row['district'] ?? '', $row['income_class'] ?? '',
        $row['purpose_of_project'] ?? '', $row['type_of_project'] ?? '', (int) ($row['total_beneficiaries'] ?? 0),
        (int) ($row['female'] ?? 0), $row['beneficiary_type'] ?? '', (float) ($row['amount_released'] ?? 0),
        $toExcelDate($row['date_released'] ?? null), null, null, null, $row['convergence_project'] ?? '',
        ucfirst($row['project_status'] ?? ''),
    ];
    if (isset($cqprFundColumns[$row['source_of_funds'] ?? ''])) {
        $line[$cqprFundColumns[$row['source_of_funds']]] = '✓';
    }
    $sheetRows[] = $line;
}
$totalRow = count($sheetRows);
$sheetRows[] = ['TOTAL', null, null, null, null, null, null, null, null, $totalBeneficiaries, $totalFemale, null, $totalAmount];
$sheetRows[] = [];
$sheetRows[] = ['Notes:'];
foreach ($cqprNotes as $letter => $note) {
    $sheetRows[] = ["{$letter} - {$note}"];
}
$sheetRows[] = [];
$sheetRows[] = array_merge(['Prepared by:'], array_fill(1, 11, null), ['Approved by:']);
$sheetRows[] = [];
$sheetRows[] = [];
$sheetRows[] = array_merge(['(Name)'], array_fill(1, 11, null), ['(Name)']);
$sheetRows[] = array_merge(['(Position Title)'], array_fill(1, 11, null), ['Director']);

$merges = [[3, 0, 3, $lastCol], [4, 0, 4, $lastCol], [5, 0, 5, $lastCol], [$totalRow, 0, $totalRow, 8]];
foreach ([0, 1, 7, 8, 12, 13, 17, 18] as $col) {      // header cells spanning both header rows
    $merges[] = [$headerRow, $col, $headerRow + 1, $col];
}
foreach ([[2, 6], [9, 11], [14, 16]] as [$from, $to]) { // Project Location, Beneficiaries, Fund Source
    $merges[] = [$headerRow, $from, $headerRow, $to];
}
for ($r = $totalRow + 3; $r < $totalRow + 3 + count($cqprNotes); $r++) {
    $merges[] = [$r, 0, $r, $lastCol];
}

$cqprSheet = [
    'rows'         => $sheetRows,
    'merges'       => $merges,
    'firstDataRow' => $firstDataRow,
    'totalRow'     => $totalRow,
    'amountCol'    => 12,
    'dateCol'      => 13,
    'recordCount'  => count($cqprData),
    'filename'     => $exportFilename,
];
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
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-start gap-2 no-print">
        <div>
            <h5 class="mb-1">
                <i class="bi bi-file-earmark-spreadsheet" aria-hidden="true"></i> CQPR - Consolidated Quarterly Progress Report
            </h5>
            <?php include __DIR__ . '/report-applied-filters.php'; ?>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" onclick="exportCQPRToExcel()" class="btn btn-sm btn-success" title="Export as Excel (.xlsx)">
                <i class="bi bi-file-earmark-excel" aria-hidden="true"></i> Excel
            </button>
            <button type="button" onclick="window.print(); if (window.logReportExport) logReportExport('print', <?php echo count($cqprData); ?>);" class="btn btn-sm btn-primary" title="Print">
                <i class="bi bi-printer" aria-hidden="true"></i> Print
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
                   to <u>&nbsp;<?php echo $cqprDateTo ? date('F d, Y', strtotime($cqprDateTo)) : '_______________'; ?>&nbsp;</u></p>
            </div>
            
            <p><strong>DOLE Regional Office No.</strong> <u>&nbsp;<?php echo htmlspecialchars($regionalOffice); ?>&nbsp;</u></p>
            <p><strong>A. DOLE Integrated Livelihood Program</strong></p>
            
            <?php if (empty($cqprData)): ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-circle"></i> No records found for the selected filters.
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
                            <?php foreach (array_keys($cqprFundColumns) as $fund): ?>
                            <td class="text-center"><?php echo ($row['source_of_funds'] ?? '') === $fund ? '✓' : ''; ?></td>
                            <?php endforeach; ?>
                            <td><?php echo htmlspecialchars($row['convergence_project'] ?? ''); ?></td>
                            <td class="text-center"><?php echo htmlspecialchars(ucfirst($row['project_status'] ?? '')); ?></td>
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
                <?php foreach ($cqprNotes as $letter => $note): ?>
                <p><sup><?php echo $letter; ?></sup> - <?php echo htmlspecialchars($note); ?></p>
                <?php endforeach; ?>
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

<script>
// SheetJS (window.XLSX) is loaded by reports.php.
function exportCQPRToExcel() {
    const sheet = <?php echo json_encode($cqprSheet, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;
    if (!window.XLSX) {
        DILP.toast.error('Export Error', 'The Excel library did not load. Check your connection and reload the page.');
        return;
    }

    const ws = XLSX.utils.aoa_to_sheet(sheet.rows);
    ws['!merges'] = sheet.merges.map(m => ({ s: { r: m[0], c: m[1] }, e: { r: m[2], c: m[3] } }));
    for (let r = sheet.firstDataRow; r <= sheet.totalRow; r++) {
        const amount = ws[XLSX.utils.encode_cell({ r, c: sheet.amountCol })];
        if (amount && amount.t === 'n') amount.z = '#,##0.00';
        const date = ws[XLSX.utils.encode_cell({ r, c: sheet.dateCol })];
        if (date && date.t === 'n') date.z = 'mm/dd/yyyy';
    }
    ws['!cols'] = [
        {wch: 30}, {wch: 28}, {wch: 16}, {wch: 16}, {wch: 16}, {wch: 8}, {wch: 8},
        {wch: 10}, {wch: 8}, {wch: 8}, {wch: 8}, {wch: 22}, {wch: 14}, {wch: 12},
        {wch: 10}, {wch: 11}, {wch: 9}, {wch: 18}, {wch: 14}
    ];

    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'CQPR Report');
    XLSX.writeFile(wb, `${sheet.filename}_${DILP.export._today()}.xlsx`);

    if (window.logReportExport) logReportExport('excel', sheet.recordCount);
}
</script>
