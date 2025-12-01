<?php
require_once 'config.php'; // your DB connection

header('Content-Type: application/json');

$query = $_GET['query'] ?? '';
$query = trim($query);

if ($query === '') {
    echo json_encode([]);
    exit;
}

// Only search ICT Equipment
$stmt = $conn->prepare("
    SELECT 
        item_description AS description,
        semi_expendable_property_no AS property_no,
        amount_total AS unit_cost
    FROM semi_expendable_property
    WHERE category = 'ICT Equipment'
      AND item_description LIKE CONCAT('%', ?, '%')
    LIMIT 10
");
$stmt->bind_param("s", $query);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);