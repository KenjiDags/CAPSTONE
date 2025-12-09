<?php 

function updateAverageCost($conn, $item_id) {
    // Get item info and entries
    $sql = "
        SELECT 
            i.unit_cost,
            i.initial_quantity,
            i.average_unit_cost,
            i.calculated_quantity,
            i.calculated_unit_cost
        FROM items i
        WHERE i.item_id = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$item) return;

    // Get last added positive entry's cost
    $last_entry_stmt = $conn->prepare("
        SELECT unit_cost 
        FROM inventory_entries 
        WHERE item_id = ? AND quantity > 0 
        ORDER BY created_at DESC LIMIT 1
    ");
    $last_entry_stmt->bind_param("i", $item_id);
    $last_entry_stmt->execute();
    $last_entry = $last_entry_stmt->get_result()->fetch_assoc();
    $last_entry_stmt->close();

    // If no entries, just set the average = unit cost
    if (!$last_entry && $item['initial_quantity'] > 0) {
        $avg_cost = $item['unit_cost'];
        $qty = $item['initial_quantity'];
    } else {
        // Determine the base cost for averaging
        if ($item['calculated_quantity'] == 0 && $item['average_unit_cost'] !== null) {
            // Stock was issued completely, start from preserved average
            $base_cost = $item['average_unit_cost'];
        } elseif ($item['initial_quantity'] > 0) {
            // First addition after initial stock
            $base_cost = $item['unit_cost'];
        } else {
            // If no initial quantity but we have an average stored
            $base_cost = $item['average_unit_cost'] ?? $item['unit_cost'];
        }

        $new_cost = $last_entry['unit_cost'] ?? $base_cost;

        // Arithmetic mean with preserved base cost + new entry cost
        $avg_cost = ($base_cost + $new_cost) / 2;

        // Get updated quantity
        $qty_stmt = $conn->prepare("SELECT SUM(quantity) as total_qty FROM inventory_entries WHERE item_id = ?");
        $qty_stmt->bind_param("i", $item_id);
        $qty_stmt->execute();
        $total_qty = $qty_stmt->get_result()->fetch_assoc()['total_qty'] ?? 0;
        $qty_stmt->close();

        $qty = max(0, $item['initial_quantity'] + $total_qty);
    }

    // Save new values
    $update = $conn->prepare("
        UPDATE items
        SET 
            average_unit_cost = ?,
            calculated_unit_cost = ?,
            calculated_quantity = ?
        WHERE item_id = ?
    ");
    $update->bind_param("ddii", $avg_cost, $avg_cost, $qty, $item_id);
    $update->execute();
    $update->close();
}


function logItemHistory($conn, $item_id, ?int $quantity_change = null, string $change_type = 'update', ?int $reference_id = null) {
    // Fetch current item info - try items table first, then semi_expendable_property
    $stmt = $conn->prepare("SELECT * FROM items WHERE item_id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    $stmt->close();

    // If not found in items, try semi_expendable_property
    if (!$item) {
        $stmt = $conn->prepare("SELECT id as item_id, semi_expendable_property_no as stock_number, item_description as item_name, 
                                item_description as description, 'unit' as unit, 0 as reorder_point, 
                                (amount_total / NULLIF(quantity_balance, 0)) as average_unit_cost, 
                                quantity_balance as quantity_on_hand, quantity_balance as initial_quantity
                                FROM semi_expendable_property WHERE id = ?");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();
        $stmt->close();
        
        if (!$item) {
            return; // No such item, skip logging
        }
    }

    // Get previous quantity (latest history) or fallback to initial_quantity
    $prev_stmt = $conn->prepare("SELECT quantity_on_hand FROM item_history WHERE item_id = ? ORDER BY changed_at DESC LIMIT 1");
    $prev_stmt->bind_param("i", $item_id);
    $prev_stmt->execute();
    $prev_result = $prev_stmt->get_result();
    $prev_row = $prev_result->fetch_assoc();
    $prev_stmt->close();

    $previous_quantity = isset($prev_row['quantity_on_hand'])
        ? intval($prev_row['quantity_on_hand'])
        : (isset($item['initial_quantity']) ? intval($item['initial_quantity']) : 0);

    // Current quantity from items table
    $current_quantity = intval($item['quantity_on_hand']);

    // If quantity_change not provided, derive it
    if ($quantity_change === null) {
        $quantity_change = $current_quantity - $previous_quantity;
    }

    // Determine change direction
    $change_direction = match(true) {
        $quantity_change > 0 => 'increase',
        $quantity_change < 0 => 'decrease',
        default              => 'no_change'
    };

    // Determine which reference column to use based on change_type
    $ris_id = null;
    $ics_id = null;
    if ($change_type === 'issued_ics') {
        $ics_id = $reference_id;
    } else {
        $ris_id = $reference_id;
    }

    // Insert into history, including ris_id or ics_id if available
    $insert = $conn->prepare("
        INSERT INTO item_history (
            item_id,
            stock_number,
            item_name,
            description,
            unit,
            reorder_point,
            unit_cost,
            quantity_on_hand,
            quantity_change,
            change_direction,
            change_type,
            ris_id,
            ics_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    // Use average_unit_cost for history records instead of unit_cost
    $unit_cost = $item['average_unit_cost'] ?? $item['unit_cost'];

    $insert->bind_param(
        "issssidiissii",
        $item_id,
        $item['stock_number'],
        $item['item_name'],
        $item['description'],
        $item['unit'],
        $item['reorder_point'],
        $unit_cost,
        $current_quantity,
        $quantity_change,
        $change_direction,
        $change_type,
        $ris_id,
        $ics_id
    );

    $insert->execute();
    $insert->close();
}

?>