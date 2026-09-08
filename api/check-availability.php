<?php
// api/check-availability.php
header('Content-Type: application/json');
session_start();

// Removed legacy SQLite path

if (false) {
    echo json_encode(['success' => false, 'message' => 'Database not found.']);
    exit;
}

try {
    require_once __DIR__ . '/../dashboard/config/db.php';
        $pdo = $conn;
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $check_in = isset($_GET['check_in']) ? trim($_GET['check_in']) : '';
    $check_out = isset($_GET['check_out']) ? trim($_GET['check_out']) : '';
    $frontend_room_id = isset($_GET['room_id']) ? trim($_GET['room_id']) : '';
    $room_size = isset($_GET['room_size']) ? (int)$_GET['room_size'] : 2;
    $extra_bed = isset($_GET['extra_bed']) ? (int)$_GET['extra_bed'] : 0;

    if (empty($check_in) || empty($check_out)) {
        echo json_encode(['success' => false, 'message' => 'Please select check-in and check-out dates.']);
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

    // Fetch room types with max_guests and extra_bed_price
    $stmtTypes = $pdo->query("
        SELECT rt.id, rt.type_name, rt.base_price, rt.high_price, rt.holiday_price,
               MAX(r.max_guests) as max_guests, MAX(r.extra_bed_price) as extra_bed_price, MAX(r.details) as details
        FROM room_types rt
        LEFT JOIN rooms r ON rt.id = r.room_type_id
        GROUP BY rt.id
    ");
    $roomTypes = $stmtTypes->fetchAll(PDO::FETCH_ASSOC);

    $results = [];
    foreach ($roomTypes as $type) {
        $image_url = 'img/m8.png'; // Default fallback

        $typeName = strtolower($type['type_name']);
        if (strpos($typeName, 'tea valley') !== false) {
            $image_url = 'img/mk1/mk1.png';
        } elseif (strpos($typeName, 'tea pavilion') !== false || strpos($typeName, 'lake pavilion') !== false) {
            $image_url = 'img/tp/tp1.png';
        } elseif (strpos($typeName, 'peak pavilion') !== false || strpos($typeName, 'peak residence') !== false) {
            $image_url = 'img/ls/ls1.png';
        } else {
            // Fetch actual image from DB
            $stmtImg = $pdo->prepare("
                SELECT ri.image_path 
                FROM room_images ri 
                JOIN rooms r ON ri.room_id = r.id 
                WHERE r.room_type_id = ? 
                ORDER BY ri.is_primary DESC, ri.id ASC LIMIT 1
            ");
            $stmtImg->execute([$type['id']]);
            $db_img = $stmtImg->fetchColumn();
            if ($db_img) {
                $image_url = 'dashboard/' . ltrim($db_img, '/');
            }
        }

        if (!empty($frontend_room_id) && $frontend_room_id != $type['id']) continue;

        // Total physical rooms for this type that are available (not deleted, not maintenance)
        $stmtTotalRooms = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE room_type_id = ? AND status NOT IN ('deleted', 'maintenance') AND max_guests = ?");
        $stmtTotalRooms->execute([$type['id'], $room_size]);
        $totalRooms = $stmtTotalRooms->fetchColumn();

        // Booked rooms for this type
        $stmtBooked = $pdo->prepare("
            SELECT COUNT(DISTINCT r.id) FROM rooms r
            JOIN bookings b ON r.id = b.room_id
            WHERE r.room_type_id = ?
              AND b.status != 'cancelled'
              AND b.check_in < ? 
              AND b.check_out > ?
              AND r.max_guests = ?
        ");
        $stmtBooked->execute([$type['id'], $check_out, $check_in, $room_size]);
        $bookedCount = $stmtBooked->fetchColumn();

        $rooms_available = max(0, $totalRooms - $bookedCount);
        $available = ($rooms_available > 0);
        $alternative_message = '';
        $alt_available = false;

        if (!$available) {
            $other_size = ($room_size == 2) ? 4 : 2;
            $stmtAltTotal = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE room_type_id = ? AND status NOT IN ('deleted', 'maintenance') AND max_guests = ?");
            $stmtAltTotal->execute([$type['id'], $other_size]);
            $altTotal = $stmtAltTotal->fetchColumn();

            $stmtAltBooked = $pdo->prepare("
                SELECT COUNT(DISTINCT r.id) FROM rooms r
                JOIN bookings b ON r.id = b.room_id
                WHERE r.room_type_id = ? AND b.status != 'cancelled' AND b.check_in < ? AND b.check_out > ? AND r.max_guests = ?
            ");
            $stmtAltBooked->execute([$type['id'], $check_out, $check_in, $other_size]);
            $altBooked = $stmtAltBooked->fetchColumn();

            if (($altTotal - $altBooked) > 0) {
                $alt_available = true;
                $alternative_message = ($room_size == 2) 
                    ? "ห้องสำหรับ 2 ท่านเต็มแล้ว แนะนำห้องพักสำหรับ 4 ท่านที่ยังว่างอยู่" 
                    : "ห้องสำหรับ 4 ท่านเต็มแล้ว แนะนำห้องพักสำหรับ 2 ท่านที่ยังว่างอยู่";
            }
        }

        // Fetch specific price for this room size
        $stmtPrice = $pdo->prepare("SELECT base_price, high_price, holiday_price FROM rooms WHERE room_type_id = ? AND max_guests = ? LIMIT 1");
        $stmtPrice->execute([$type['id'], $room_size]);
        $room_pricing = $stmtPrice->fetch(PDO::FETCH_ASSOC);
        
        if ($room_pricing) {
            $actual_base_price = $room_pricing['base_price'] ?: $type['base_price'];
            $actual_high_price = $room_pricing['high_price'] ?: $type['high_price'];
            $actual_holiday_price = $room_pricing['holiday_price'] ?: $type['holiday_price'];
        } else {
            $actual_base_price = $type['base_price'];
            $actual_high_price = $type['high_price'];
            $actual_holiday_price = $type['holiday_price'];
        }

        // Calculate pricing
        $typeTotal = 0;
        $weekday_count = 0;
        $weekend_count = 0;
        
        // Get Holiday Dates
        $stmtHolidays = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'holiday_dates'");
        $holiday_str = $stmtHolidays->fetchColumn();
        $holiday_dates = [];
        if ($holiday_str) {
            $holiday_dates = array_map('trim', explode(',', $holiday_str));
        }

        $currentDate = clone $inDate;
        for ($i = 0; $i < $nights; $i++) {
            $dayOfWeek = $currentDate->format('N'); // 1 (Mon) - 7 (Sun)
            $dateStr = $currentDate->format('Y-m-d');
            $isHoliday = in_array($dateStr, $holiday_dates);
            
            if ($isHoliday && !empty($actual_holiday_price) && $actual_holiday_price > 0) {
                $typeTotal += $actual_holiday_price;
            } else if ($dayOfWeek == 5 || $dayOfWeek == 6) { // Friday or Saturday
                $typeTotal += $actual_high_price;
                $weekend_count++;
            } else {
                $typeTotal += $actual_base_price;
                $weekday_count++;
            }
            $currentDate->modify('+1 day');
        }

        // Get Max Discount Percent for available AND UNBOOKED rooms of this type
        $stmtDiscount = $pdo->prepare("
            SELECT MAX(discount_percent) FROM rooms 
            WHERE room_type_id = ? AND status = 'available' AND max_guests = ?
            AND id NOT IN (
                SELECT room_id FROM bookings b
                WHERE b.status != 'cancelled' AND b.check_in < ? AND b.check_out > ?
            )
        ");
        $stmtDiscount->execute([$type['id'], $room_size, $check_out, $check_in]);
        $maxDiscount = (int)$stmtDiscount->fetchColumn();

        $originalTotal = $typeTotal;
        $discountAmount = 0;
        if ($maxDiscount > 0) {
            $discountAmount = round(($typeTotal * $maxDiscount) / 100);
            $typeTotal -= $discountAmount;
        }

        // Add extra bed price
        $extra_bed_price = 0;
        if ($extra_bed > 0) {
            $stmtExtraBed = $pdo->prepare("SELECT MAX(extra_bed_price) FROM rooms WHERE room_type_id = ? AND max_guests = ?");
            $stmtExtraBed->execute([$type['id'], $room_size]);
            $extraBedRate = $stmtExtraBed->fetchColumn();
            if (!$extraBedRate) {
                $stmtExtraBed2 = $pdo->prepare("SELECT MAX(extra_bed_price) FROM rooms WHERE room_type_id = ?");
                $stmtExtraBed2->execute([$type['id']]);
                $extraBedRate = $stmtExtraBed2->fetchColumn() ?: 500;
            }
            $extra_bed_price = $extraBedRate * $extra_bed * $nights;
            $typeTotal += $extra_bed_price;
            $originalTotal += $extra_bed_price; // Extra bed price is not discounted
        }

        $raw_details = explode("|||", isset($type['details']) ? $type['details'] :  "");
        $index_details = isset($raw_details[0]) ? $raw_details[0] :  "";
        $details_parts = explode("\n", $index_details);
        $details_th = trim(isset($details_parts[0]) ? $details_parts[0] :  "");
        $details_en = trim(isset($details_parts[1]) ? $details_parts[1] :  $details_th);

        $highlights = isset($raw_details[2]) ? $raw_details[2] :  "";
        $hl_parts = explode("\n", $highlights);
        $hl_th = trim(isset($hl_parts[0]) ? $hl_parts[0] :  "");
        $hl_en = trim(isset($hl_parts[1]) ? $hl_parts[1] :  $hl_th);

        // Fetch amenities
        $stmtAm = $pdo->prepare("
            SELECT DISTINCT ra.amenity_code 
            FROM room_amenities ra 
            JOIN rooms r ON ra.room_id = r.id 
            WHERE r.room_type_id = ?
        ");
        $stmtAm->execute([$type['id']]);
        $amenity_codes = $stmtAm->fetchAll(PDO::FETCH_COLUMN);

        $results[] = [
            'room_id' => $type['id'],
            'name_th' => $type['type_name'],
            'name_en' => $type['type_name'],
            'details_th' => $details_th,
            'details_en' => $details_en,
            'price_per_night' => $type['base_price'],
            'capacity' => $room_size + $extra_bed,
            'image_url' => $image_url,
            'available' => $available,
            'alternative_message' => $alternative_message,
            'alt_available' => $alt_available,
            'rooms_available' => $rooms_available,
            'nights' => $nights,
            'total_price' => $typeTotal,
            'room_price_total' => $typeTotal - $extra_bed_price,
            'extra_bed_price_total' => $extra_bed_price,
            'original_price' => $originalTotal,
            'discount_percent' => $maxDiscount,
            'discount_amount' => $discountAmount,
            'weekday_count' => $weekday_count,
            'weekend_count' => $weekend_count,
            'holiday_count' => 0,
            'highlights_th' => $hl_th,
            'highlights_en' => $hl_en,
            'amenities' => $amenity_codes
        ];
    }

    echo json_encode([
        'success' => true,
        'results' => $results
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
