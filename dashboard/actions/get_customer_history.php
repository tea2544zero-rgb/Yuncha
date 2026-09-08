<?php
require_once '../config/security.php';
require_once '../config/db.php';

header('Content-Type: application/json');

$customer_id = (int)(isset($_GET['id']) ? $_GET['id'] :  0);
if(!$customer_id) { echo json_encode(['success' => false]); exit; }

try {
    $stmt_c = $conn->prepare("SELECT first_name, last_name, phone, email, created_at FROM customers WHERE id = ?");
    $stmt_c->execute([$customer_id]);
    $customer = $stmt_c->fetch(PDO::FETCH_ASSOC);

    if(!$customer) { echo json_encode(['success' => false, 'error' => 'Customer not found']); exit; }

    $stmt_b = $conn->prepare("
        SELECT b.id, b.booking_ref, b.check_in, b.check_out, b.status, b.total_price, b.created_at, r.room_number, t.type_name, p.slip_image
        FROM bookings b
        LEFT JOIN rooms r ON b.room_id = r.id
        LEFT JOIN room_types t ON r.room_type_id = t.id
        LEFT JOIN payments p ON b.id = p.booking_id
        WHERE b.customer_id = ?
        ORDER BY b.check_in DESC
    ");
    $stmt_b->execute([$customer_id]);
    $bookings = $stmt_b->fetchAll(PDO::FETCH_ASSOC);

    $total_spent = 0;
    $no_shows = 0;
    $formatted_bookings = [];

    foreach($bookings as $b) {
        if(in_array($b['status'], ['confirmed', 'checked_in', 'checked_out'])) {
            $total_spent += $b['total_price'];
        }
        if($b['status'] == 'cancelled' || $b['status'] == 'canceled') {
            $no_shows++;
        }
        
        $formatted_bookings[] = [
            'id' => $b['booking_ref'],
            'created_at' => date('d/m/Y H:i', strtotime($b['created_at'])),
            'room_name' => (isset($b['type_name']) ? $b['type_name'] :  'ไม่ระบุ') . ' ' . ($b['room_number'] ? '('.$b['room_number'].')' : ''),
            'check_in' => date('d/m/Y', strtotime($b['check_in'])),
            'check_out' => date('d/m/Y', strtotime($b['check_out'])),
            'final_price' => $b['total_price'],
            'status' => $b['status'],
            'slip_image' => $b['slip_image']
        ];
    }

    echo json_encode([
        'success' => true,
        'customer_phone' => $customer['phone'],
        'total_stays' => count($bookings),
        'total_spent' => $total_spent,
        'total_canceled' => $no_shows,
        'bookings' => $formatted_bookings
    ]);
} catch (Exception $e) {
    file_put_contents('debug.txt', $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
