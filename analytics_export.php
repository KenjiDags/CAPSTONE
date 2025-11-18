<?php
require 'config.php';
require 'auth.php';
// optional role restriction
if (function_exists('require_role')) {
    // require_role('admin');
}

// Only CSV supported for now
$format = isset($_GET['format']) ? $_GET['format'] : 'csv';
if ($format !== 'csv') {
    http_response_code(400);
    echo 'Unsupported format';
    exit;
}

// prepare CSV output
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="low_stock.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['stock_number', 'item_name', 'quantity', 'reorder_point']);

$lowSql = "SELECT stock_number, item_name, COALESCE(calculated_quantity, quantity_on_hand) AS quantity, reorder_point
           FROM items WHERE COALESCE(calculated_quantity, quantity_on_hand) <= reorder_point ORDER BY quantity ASC LIMIT 1000";
$res = $conn->query($lowSql);
while ($row = $res->fetch_assoc()) {
    fputcsv($out, [$row['stock_number'], $row['item_name'], (int)$row['quantity'], (int)$row['reorder_point']]);
}

fclose($out);
exit;

?>
