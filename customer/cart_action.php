<?php
// customer/cart_action.php  –  AJAX endpoint for cart operations
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if (!isCustomerLoggedIn()) {
    echo json_encode(['success'=>false,'message'=>'Please log in first','redirect'=>'/restaurant/customer/login.php']);
    exit;
}

$action  = $_POST['action'] ?? '';
$item_id = (int)($_POST['item_id'] ?? 0);
$cid     = $_SESSION['customer_id'];

// Ensure cart exists
$cr = $conn->query("SELECT cart_id FROM Cart WHERE customer_id=$cid");
if ($cr->num_rows === 0) {
    $conn->query("INSERT INTO Cart (customer_id) VALUES ($cid)");
    $cart_id = $conn->insert_id;
} else {
    $cart_id = $cr->fetch_assoc()['cart_id'];
}

function cartTotals($conn, $cart_id, $cid) {
    $r = $conn->query("SELECT SUM(ci.quantity) AS tq,
                               SUM(ci.quantity * mi.price) AS sub
                        FROM CartItem ci
                        JOIN MenuItem mi ON ci.item_id = mi.item_id
                        WHERE ci.cart_id = $cart_id");
    $row = $r->fetch_assoc();
    $sub = (float)($row['sub'] ?? 0);
    $delivery = $sub >= 500 ? 0 : 60;
    return [
        'cart_total_qty' => (int)($row['tq'] ?? 0),
        'subtotal'       => number_format($sub, 0),
        'delivery'       => number_format($delivery, 0),
        'total'          => number_format($sub + $delivery, 0),
    ];
}

if ($action === 'add') {
    $item_id = (int)$item_id;
    // Check item exists
    $ir = $conn->query("SELECT item_id FROM MenuItem WHERE item_id=$item_id AND available=1");
    if ($ir->num_rows === 0) {
        echo json_encode(['success'=>false,'message'=>'Item not available']);
        exit;
    }
    $existing = $conn->query("SELECT cart_item_id, quantity FROM CartItem WHERE cart_id=$cart_id AND item_id=$item_id");
    if ($existing->num_rows > 0) {
        $row = $existing->fetch_assoc();
        $conn->query("UPDATE CartItem SET quantity=quantity+1 WHERE cart_item_id={$row['cart_item_id']}");
    } else {
        $conn->query("INSERT INTO CartItem (cart_id, item_id, quantity) VALUES ($cart_id, $item_id, 1)");
    }
    $totals = cartTotals($conn, $cart_id, $cid);
    echo json_encode(array_merge(['success'=>true,'message'=>'Added to cart'], $totals));

} elseif ($action === 'increase') {
    $conn->query("UPDATE CartItem SET quantity=quantity+1 WHERE cart_id=$cart_id AND item_id=$item_id");
    $nq = $conn->query("SELECT quantity FROM CartItem WHERE cart_id=$cart_id AND item_id=$item_id")->fetch_assoc()['quantity'];
    $totals = cartTotals($conn, $cart_id, $cid);
    echo json_encode(array_merge(['success'=>true,'new_qty'=>$nq], $totals));

} elseif ($action === 'decrease') {
    $cur = $conn->query("SELECT quantity FROM CartItem WHERE cart_id=$cart_id AND item_id=$item_id")->fetch_assoc();
    if ($cur && $cur['quantity'] > 1) {
        $conn->query("UPDATE CartItem SET quantity=quantity-1 WHERE cart_id=$cart_id AND item_id=$item_id");
        $nq = $cur['quantity'] - 1;
    } else {
        $conn->query("DELETE FROM CartItem WHERE cart_id=$cart_id AND item_id=$item_id");
        $nq = 0;
    }
    $totals = cartTotals($conn, $cart_id, $cid);
    echo json_encode(array_merge(['success'=>true,'new_qty'=>$nq], $totals));

} elseif ($action === 'remove') {
    $conn->query("DELETE FROM CartItem WHERE cart_id=$cart_id AND item_id=$item_id");
    $totals = cartTotals($conn, $cart_id, $cid);
    echo json_encode(array_merge(['success'=>true,'new_qty'=>0], $totals));

} else {
    echo json_encode(['success'=>false,'message'=>'Unknown action']);
}
?>
