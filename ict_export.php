<?php
require_once 'config.php';

// Fetch ICT registry data for export
$result = $conn->query("
    SELECT * FROM ict_registry
    ORDER BY date ASC, id ASC
");

// Template requires exactly 10 rows
$templateRows = 10;
$entries = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $entries[] = $row;
    }
}

// Limit to 10 rows
$entries = array_slice($entries, 0, $templateRows);

// Calculate empty rows to fill up to 10
$emptyRows = $templateRows - count($entries);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registry of Semi-Expendable Property Issued</title>
<style>
    @media print {
        body { margin: 0; padding: 0; font-size: 11px; }
        .no-print { display: none; }
        table { page-break-inside: avoid; }
        tr { page-break-inside: avoid; }
        .form-container { border: 2px solid black; }
    }
    body { font-family: Arial, sans-serif; margin: 20px; font-size: 12px; background-color: #f5f5f5; }
    .export-instructions { background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin-bottom: 20px; }
    .export-instructions h3 { margin: 0 0 10px 0; color: #856404; }
    .export-instructions ol { margin: 10px 0; padding-left: 20px; }
    .export-instructions p { margin: 10px 0 0 0; font-weight: bold; color: #856404; }
    .button-container { margin: 20px 0; display: flex; gap: 10px; }
    .print-btn, .back-btn { padding: 10px 20px; border: none; border-radius: 5px; font-size: 14px; font-weight: bold; cursor: pointer; color: white; }
    .print-btn { background-color: #007bff; }
    .back-btn { background-color: #6c757d; }
    .print-btn:hover { background-color: #0056b3; }
    .back-btn:hover { background-color: #545b62; }
    .form-container { background-color: white; border: 2px solid black; padding: 0; }
    .annex-reference { text-align: right; font-weight: bold; font-size: 12px; padding: 10px 15px 5px 0; margin: 0; }
    .form-title { text-align: center; font-weight: bold; margin: 10px 0 15px 0; font-size: 14px; text-transform: uppercase; }
    .form-header { border-collapse: collapse; width: 100%; margin-bottom: 0; }
    .form-header td { border: 1px solid black; padding: 6px 8px; vertical-align: top; }
    .form-header .label { font-weight: bold; width: 140px; background-color: white; }
    .form-header .value { width: 200px; }
    .main-table { width: 100%; border-collapse: collapse; border-top: none; }
    .main-table th, .main-table td { border: 1px solid black; padding: 4px; text-align: center; vertical-align: middle; font-size: 11px; }
    .main-table th { background-color: white; font-weight: bold; }
    .main-table .text-left { text-align: left; }
    .main-table .text-right { text-align: right; }
    .date-col { width: 70px; }
    .ics-col { width: 70px; }
    .property-col { width: 80px; }
    .item-col { width: 280px; }
    .life-col { width: 60px; }
    .issued-qty-col { width: 50px; }
    .officer-col { width: 140px; }
    .returned-qty-col { width: 50px; }
    .returned-officer-col { width: 140px; }
    .reissued-qty-col { width: 50px; }
    .reissued-officer-col { width: 140px; }
    .disposed-qty1-col, .disposed-qty2-col { width: 50px; }
    .amount-col { width: 80px; }
    .remarks-col { width: 80px; }
</style>

</head>
<body>
<div class="no-print">
    <div class="export-instructions">
        <h3>Export Instructions</h3>
        <strong>To save as PDF:</strong>
        <ol>
            <li>Click the "Print/Save as PDF" button below</li>
            <li>In the print dialog, select "Save as PDF" or "Microsoft Print to PDF"</li>
            <li>Choose your destination and click "Save"</li>
        </ol>
        <p>For best results: Use Chrome or Edge browser for optimal PDF formatting.</p>
    </div>
    <div class="button-container">
        <button class="print-btn" onclick="window.print()">🖨️ Print/Save as PDF</button>
        <button class="back-btn" onclick="window.location.href='ict_registry.php'">← Back to Registry</button>
    </div>
</div>

<div class="form-container">
    <div class="annex-reference">Annex A.4</div>
    <div class="form-title">REGISTRY OF SEMI-EXPENDABLE PROPERTY ISSUED</div>

    <table class="form-header">
        <tr>
            <td class="label">Entity Name:</td>
            <td class="value" contenteditable="true">TESDA-CAR</td>
            <td style="width: 100px;"></td>
            <td class="label">Fund Cluster :</td>
            <td class="value" style="width: 80px;" contenteditable="true">101</td>
        </tr>
    </table>

    <table class="form-header" style="margin-top: 0;">
        <tr>
            <td class="label">Semi-Expendable Property:</td>
            <td class="value" contenteditable="true">ICT Equipment</td>
            <td colspan="3"></td>
        </tr>
    </table>

    <table class="main-table">
        <thead>
            <tr>
                <th rowspan="2" class="date-col">Date</th>
                <th colspan="2">Reference</th>
                <th rowspan="2" class="item-col">Item Description</th>
                <th rowspan="2" class="life-col">Estimated Useful Life</th>
                <th colspan="2">Issued</th>
                <th colspan="2">Returned</th>
                <th colspan="2">Re-issued</th>
                <th colspan="2">Disposed</th>
                <th rowspan="2" class="amount-col">Amount (TOTAL)</th>
                <th rowspan="2" class="remarks-col">Remarks</th>
            </tr>
            <tr>
                <th class="ics-col">ICS/RRSP No.</th>
                <th class="property-col">Semi-Expendable Property No.</th>
                <th class="issued-qty-col">Qty.</th>
                <th class="officer-col">Office/Officer</th>
                <th class="returned-qty-col">Qty.</th>
                <th class="returned-officer-col">Office/Officer</th>
                <th class="reissued-qty-col">Qty.</th>
                <th class="reissued-officer-col">Office/Officer</th>
                <th class="disposed-qty1-col">Qty.</th>
                <th class="disposed-qty2-col">Qty.</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($entries as $row): ?>
            <tr>
                <td><?= htmlspecialchars(date('n/j/Y', strtotime($row['date']))) ?></td>
                <td><?= htmlspecialchars($row['reference_no']) ?></td>
                <td><?= htmlspecialchars($row['property_no']) ?></td>
                <td class="text-left"><?= htmlspecialchars($row['item_description']) ?></td>
                <td><?= htmlspecialchars($row['useful_life']) ?></td>
                <td><?= htmlspecialchars($row['issued_qty']) ?></td>
                <td class="text-left"><?= htmlspecialchars($row['issued_officer']) ?></td>
                <td><?= htmlspecialchars($row['returned_qty'] ?: '-') ?></td>
                <td><?= htmlspecialchars($row['returned_officer'] ?: '-') ?></td>
                <td><?= htmlspecialchars($row['reissued_qty'] ?: '-') ?></td>
                <td><?= htmlspecialchars($row['reissued_officer'] ?: '-') ?></td>
                <td><?= htmlspecialchars($row['disposed_qty'] ?: '-') ?></td>
                <td><?= htmlspecialchars($row['balance_qty']) ?></td>
                <td class="text-right"><?= number_format($row['total_amount'], 2) ?></td>
                <td><?= htmlspecialchars($row['remarks'] ?: '-') ?></td>
            </tr>
            <?php endforeach; ?>

            <?php for ($i = 0; $i < $emptyRows; $i++): ?>
            <tr>
                <?php for ($j = 0; $j < 15; $j++): ?>
                <td contenteditable="true"></td>
                <?php endfor; ?>
            </tr>
            <?php endfor; ?>
        </tbody>
    </table>
</div>
</body>
</html>
