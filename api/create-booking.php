<?php
// api/create-booking.php
header('Content-Type: application/json');
session_start();

// Removed legacy SQLite path
$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] :  'th';

if (false) {
    echo json_encode(['success' => false, 'message' => 'Database not found.']);
    exit;
}

try {
    require_once __DIR__ . '/../dashboard/config/db.php';
        $pdo = $conn;
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Load settings
    $stmt_settings = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $site_settings = $stmt_settings ? $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR) : [];
    $hotel_name = isset($site_settings['hotel_name']) ? $site_settings['hotel_name'] :  'Yuncha Valley Resort';
    $email = isset($site_settings['email']) ? $site_settings['email'] :  'stay@yunchavalley.com';

    // Sanitize input
    $frontend_room_id = isset($_POST['room_id']) ? trim($_POST['room_id']) : '';
    $customer_name = isset($_POST['name']) ? htmlspecialchars(trim($_POST['name'])) : '';
    $customer_email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
    $customer_phone = isset($_POST['phone']) ? htmlspecialchars(trim($_POST['phone'])) : '';
    $check_in = isset($_POST['check_in']) ? trim($_POST['check_in']) : '';
    $check_out = isset($_POST['check_out']) ? trim($_POST['check_out']) : '';
    $room_size = isset($_POST['room_size']) ? (int)$_POST['room_size'] : 2;
    $extra_bed = isset($_POST['extra_bed']) ? (int)$_POST['extra_bed'] : 0;

    // Validation
    if (empty($frontend_room_id) || empty($customer_name) || empty($customer_email) || empty($customer_phone) || empty($check_in) || empty($check_out)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
        exit;
    }

    if (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address format.']);
        exit;
    }

    $inDate = new DateTime($check_in);
    $outDate = new DateTime($check_out);
    $today = new DateTime((new DateTime())->format('Y-m-d'));

    if ($inDate < $today) {
        echo json_encode(['success' => false, 'message' => 'Check-in date cannot be in the past.']);
        exit;
    }

    if ($outDate <= $inDate) {
        echo json_encode(['success' => false, 'message' => 'Check-out date must be after check-in date.']);
        exit;
    }

    $nights = $inDate->diff($outDate)->days;

    // Map frontend room string to database room_type_id
    // The frontend now passes the numeric room_type_id directly
    $room_type_id = (int)$frontend_room_id;
    if ($room_type_id <= 0) {
        $room_type_id = 1; // Fallback
    }

    // Get room type details and max extra bed price from physical rooms
    $stmtType = $pdo->prepare("
        SELECT rt.*, MAX(r.extra_bed_price) as extra_bed_price 
        FROM room_types rt 
        LEFT JOIN rooms r ON rt.id = r.room_type_id 
        WHERE rt.id = ?
        GROUP BY rt.id
    ");
    $stmtType->execute([$room_type_id]);
    $roomType = $stmtType->fetch(PDO::FETCH_ASSOC);

    if (!$roomType) {
        echo json_encode(['success' => false, 'message' => 'Selected room type does not exist.']);
        exit;
    }

    // Overbooking prevention: Find a free physical room
    $findRoomQuery = "
        SELECT id FROM rooms 
        WHERE room_type_id = ? 
        AND status NOT IN ('deleted', 'maintenance')
        AND max_guests = ?
        AND id NOT IN (
            SELECT room_id FROM bookings 
            WHERE status != 'cancelled' 
            AND check_in < ? AND check_out > ?
        ) 
        ORDER BY discount_percent DESC LIMIT 1
    ";
    $stmtFindRoom = $pdo->prepare($findRoomQuery);
    $stmtFindRoom->execute([$room_type_id, $room_size, $check_out, $check_in]);
    $freeRoom = $stmtFindRoom->fetch(PDO::FETCH_ASSOC);

    if (!$freeRoom) {
        echo json_encode(['success' => false, 'message' => 'The selected room is no longer available for these dates.']);
        exit;
    }
    
    $assigned_room_id = $freeRoom['id'];

    // Get the exact base_price and discount percent for this assigned room
    $stmtRoomInfo = $pdo->prepare("SELECT base_price, discount_percent FROM rooms WHERE id = ?");
    $stmtRoomInfo->execute([$assigned_room_id]);
    $roomInfo = $stmtRoomInfo->fetch(PDO::FETCH_ASSOC);
    $actual_base_price = $roomInfo ? $roomInfo['base_price'] : $roomType['base_price'];
    $discount_percent = $roomInfo ? (int)$roomInfo['discount_percent'] : 0;
    
    $markup = max(0, $actual_base_price - $roomType['base_price']);

    // Get Holiday Dates
    $stmtHolidays = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'holiday_dates'");
    $holiday_str = $stmtHolidays->fetchColumn();
    $holiday_dates = [];
    if ($holiday_str) {
        $holiday_dates = array_map('trim', explode(',', $holiday_str));
    }

    // Calculate Price
    $totalPrice = 0;
    $currentDate = clone $inDate;
    
    $weekday_count = 0;
    $weekend_count = 0;
    $holiday_count = 0;
    $weekday_price = $actual_base_price;
    $weekend_price = $roomType['high_price'] + $markup;
    $holiday_price = !empty($roomType['holiday_price']) ? ($roomType['holiday_price'] + $markup) : $weekend_price;
    $extra_bed_price = isset($roomType['extra_bed_price']) ? $roomType['extra_bed_price'] : 500;

    for ($i = 0; $i < $nights; $i++) {
        $dayOfWeek = $currentDate->format('N'); // 1 (Mon) - 7 (Sun)
        $dateStr = $currentDate->format('Y-m-d');
        $isHoliday = in_array($dateStr, $holiday_dates);
        
        if ($isHoliday && !empty($roomType['holiday_price']) && $roomType['holiday_price'] > 0) {
            $totalPrice += $holiday_price;
            $holiday_count++;
        } else if ($dayOfWeek == 5 || $dayOfWeek == 6) { // Friday or Saturday
            $totalPrice += $weekend_price;
            $weekend_count++;
        } else {
            $totalPrice += $weekday_price;
            $weekday_count++;
        }
        $currentDate->modify('+1 day');
    }

    // Apply discount
    if ($discount_percent > 0) {
        $discount_amount = round(($totalPrice * $discount_percent) / 100);
        $totalPrice -= $discount_amount;
    }

    // Add extra bed price
    $extra_bed_count = $extra_bed;
    $extra_bed_total = $extra_bed_count * (isset($roomType['extra_bed_price']) ? $roomType['extra_bed_price'] :  500) * $nights;
    $totalPrice += $extra_bed_total;
    
    $guests = $room_size + $extra_bed;

    // Handle File Upload for Payment Slip
    $payment_slip_url = '';
    if (isset($_FILES['slip']) && $_FILES['slip']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['slip']['tmp_name'];
        $fileName = $_FILES['slip']['name'];
        $fileSize = $_FILES['slip']['size'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
        if (!in_array($fileExtension, $allowedExtensions) || $fileSize > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Invalid file or size exceeds 5MB.']);
            exit;
        }

        $uploadDir = dirname(__DIR__) . '/uploads/slips/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $newFileName = 'slip_' . date('Ymd_His') . '_' . bin2hex(openssl_random_pseudo_bytes(4)) . '.' . $fileExtension;
        
        if (move_uploaded_file($fileTmpPath, $uploadDir . $newFileName)) {
            $payment_slip_url = 'uploads/slips/' . $newFileName;
        } else {
            echo json_encode(['success' => false, 'message' => 'Error uploading slip.']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Please upload your payment slip.']);
        exit;
    }

    // Handle File Upload for Passport/ID (Will just store it in a generic way for now)
    $passport_id_url = '';
    if (isset($_FILES['passport_id']) && $_FILES['passport_id']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['passport_id']['tmp_name'];
        $fileName = $_FILES['passport_id']['name'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'pdf'])) {
            $uploadDirId = dirname(__DIR__) . '/uploads/ids/';
            if (!is_dir($uploadDirId)) mkdir($uploadDirId, 0777, true);
            $newFileNameId = 'id_' . date('Ymd_His') . '_' . bin2hex(openssl_random_pseudo_bytes(4)) . '.' . $fileExtension;
            if (move_uploaded_file($fileTmpPath, $uploadDirId . $newFileNameId)) {
                $passport_id_url = 'uploads/ids/' . $newFileNameId;
            }
        }
    }

    // Customer Handling
    $stmtCust = $pdo->prepare("SELECT id FROM customers WHERE email = ? OR phone = ?");
    $stmtCust->execute([$customer_email, $customer_phone]);
    $customer = $stmtCust->fetch(PDO::FETCH_ASSOC);

    if ($customer) {
        $customer_id = $customer['id'];
        // Update to returning customer
        $pdo->prepare("UPDATE customers SET is_returning = 1 WHERE id = ?")->execute([$customer_id]);
    } else {
        $names = explode(' ', $customer_name, 2);
        $first_name = $names[0];
        $last_name = isset($names[1]) ? $names[1] :  '';
        
        $stmtInsertCust = $pdo->prepare("INSERT INTO customers (first_name, last_name, phone, email, auth_provider) VALUES (?, ?, ?, ?, 'Website')");
        $stmtInsertCust->execute([$first_name, $last_name, $customer_phone, $customer_email]);
        $customer_id = $pdo->lastInsertId();
    }

    // Generate Unique Booking Code (ID)
    $bookingRef = 'YC-' . strtoupper(bin2hex(openssl_random_pseudo_bytes(3)));

    // Insert into Bookings
    $insertQuery = "
        INSERT INTO bookings (booking_ref, customer_id, room_id, check_in, check_out, adults, extra_bed, total_price, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ";
    $stmtInsert = $pdo->prepare($insertQuery);
    $stmtInsert->execute([
        $bookingRef, $customer_id, $assigned_room_id, $check_in, $check_out, $guests, $extra_bed_count, $totalPrice
    ]);
    $booking_db_id = $pdo->lastInsertId();

    // Insert into Payments
    $insertPayment = $pdo->prepare("INSERT INTO payments (booking_id, amount, payment_method, status, slip_image) VALUES (?, ?, 'Bank Transfer', 'pending', ?)");
    $insertPayment->execute([$booking_db_id, $totalPrice, $payment_slip_url]);

    // Send Confirmation Email
    $to = $customer_email;
    $subject = "Booking Confirmation #$bookingRef - " . $hotel_name;
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: " . $hotel_name . " <" . $email . ">\r\n";
    
    $emailBody = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #ddd; padding: 20px; border-radius: 10px;'>
        <div style='text-align: center; margin-bottom: 20px;'>
            <h2 style='color: #d97706; margin: 0;'>$hotel_name</h2>
            <p style='color: #666; margin: 5px 0 0 0;'>Booking Confirmation</p>
        </div>
        <hr style='border: none; border-top: 1px solid #eee;'>
        <div style='margin-bottom: 20px;'>
            <p><strong>Booking ID:</strong> <span style='color: #d97706;'>$bookingRef</span></p>
            <p><strong>Guest Name:</strong> $customer_name</p>
            <p><strong>Room:</strong> {$roomType['type_name']}</p>
            <p><strong>Check-in:</strong> $check_in</p>
            <p><strong>Check-out:</strong> $check_out</p>
            <p><strong>Total Price:</strong> <span style='color: #059669; font-weight: bold;'>฿" . number_format($totalPrice, 2) . "</span></p>
        </div>
        <div style='background-color: #f9fafb; padding: 15px; border-radius: 8px; font-size: 14px; color: #555;'>
            <p style='margin: 0;'><strong>Note:</strong> We are verifying your payment slip. You can log in to our website at any time to check your booking status or download the official receipt.</p>
        </div>
    </div>";
    @mail($to, $subject, $emailBody, $headers);

    // Auto-login customer
    $_SESSION['customer_email'] = $customer_email;
    $_SESSION['customer_phone'] = $customer_phone;
    $_SESSION['customer_name'] = $customer_name;
    $_SESSION['customer_id'] = $customer_id;

    echo json_encode([
        'success' => true,
        'booking_id' => $bookingRef,
        'room_name' => $roomType['type_name'],
        'check_in' => $check_in,
        'check_out' => $check_out,
        'total_price' => $totalPrice,
        'customer_name' => $customer_name,
        'customer_email' => $customer_email,
        'customer_phone' => $customer_phone,
        'guests' => $guests,
        'nights' => $nights,
        'weekday_count' => $weekday_count,
        'weekday_price' => $weekday_price,
        'weekend_count' => $weekend_count,
        'weekend_price' => $weekend_price,
        'holiday_count' => $holiday_count,
        'holiday_price' => $holiday_price,
        'extra_beds' => $extra_bed_count,
        'extra_bed_price' => $extra_bed_price,
        'discount_percent' => $discount_percent,
        'created_at' => date('Y-m-d H:i:s'),
        'redirect_url' => 'receipt.php?id=' . $bookingRef, // Kept for legacy, but frontend will intercept
        'message' => $lang === 'en' ? 'Booking submitted successfully!' : 'ส่งข้อมูลการจองเรียบร้อยแล้ว!'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
