<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['customer_email']) || !isset($_SESSION['customer_phone'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Removed legacy SQLite path
try {
    require_once __DIR__ . '/../dashboard/config/db.php';
        $pdo = $conn;
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get customer ID
    $stmt_cust = $pdo->prepare("SELECT id FROM customers WHERE email = ? AND phone = ?");
    $stmt_cust->execute([$_SESSION['customer_email'], $_SESSION['customer_phone']]);
    $customer_id = $stmt_cust->fetchColumn();
    
    if (!$customer_id) {
        echo json_encode(['success' => false, 'message' => 'Customer not found']);
        exit;
    }

    $action = isset($_POST['action']) ? $_POST['action'] :  '';
    
    if ($action === 'save_review') {
        $booking_id = $_POST['booking_id'];
        $review_type = isset($_POST['review_type']) ? $_POST['review_type'] :  'room';
        $rating = (int)$_POST['rating'];
        $comment = trim(isset($_POST['comment']) ? $_POST['comment'] :  '');
        $review_id = isset($_POST['review_id']) ? $_POST['review_id'] :  0;
        
        // Verify booking belongs to customer
        $stmt_bk = $pdo->prepare("SELECT room_id FROM bookings WHERE id = ? AND customer_id = ?");
        $stmt_bk->execute([$booking_id, $customer_id]);
        $room_id = $stmt_bk->fetchColumn();
        
        if (!$room_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid booking']);
            exit;
        }

        $target_id = isset($_POST['target_id']) ? (int)$_POST['target_id'] : (($review_type === 'room') ? $room_id : 0);
        
        // Handle file upload (up to 5)
        $image_paths = [];
        if (isset($_FILES['review_images'])) {
            $upload_dir = __DIR__ . '/../uploads/reviews/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $file_count = min(count($_FILES['review_images']['name']), 5); // Max 5 photos
            
            for ($i = 0; $i < $file_count; $i++) {
                if ($_FILES['review_images']['error'][$i] == 0) {
                    $ext = pathinfo($_FILES['review_images']['name'][$i], PATHINFO_EXTENSION);
                    $new_name = 'review_' . uniqid() . '_' . $i . '.' . $ext;
                    if (move_uploaded_file($_FILES['review_images']['tmp_name'][$i], $upload_dir . $new_name)) {
                        $image_paths[] = 'uploads/reviews/' . $new_name;
                    }
                }
            }
        }
        
        $image_path_json = count($image_paths) > 0 ? json_encode($image_paths) : null;
        
        function translateToEnglish($text) {
            $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=auto&tl=en&dt=t&q=" . urlencode(trim($text));
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3); // Max 3 seconds
            $res = curl_exec($ch);
            curl_close($ch);
            
            $json = json_decode($res, true);
            if($json && isset($json[0])) {
                $translated = "";
                foreach($json[0] as $part) {
                    $translated .= $part[0];
                }
                return trim($translated);
            }
            return null;
        }

        $english_comment = null;
        if (!empty($comment)) {
            $english_comment = translateToEnglish($comment);
            if ($english_comment === $comment) $english_comment = null;
        }

        if ($review_id > 0) {
            // Update
            $sql = "UPDATE reviews SET review_type = ?, target_id = ?, rating = ?, comment = ?, english_comment = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND customer_id = ?";
            $params = [$review_type, $target_id, $rating, $comment, $english_comment, $review_id, $customer_id];
            
            if ($image_path_json !== null) {
                $sql = "UPDATE reviews SET review_type = ?, target_id = ?, rating = ?, comment = ?, english_comment = ?, image_path = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND customer_id = ?";
                $params = [$review_type, $target_id, $rating, $comment, $english_comment, $image_path_json, $review_id, $customer_id];
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        } else {
            // Insert
            $sql = "INSERT INTO reviews (customer_id, booking_id, review_type, target_id, rating, comment, english_comment, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$customer_id, $booking_id, $review_type, $target_id, $rating, $comment, $english_comment, $image_path_json]);
        }
        
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($action === 'get_review') {
        $booking_id = $_POST['booking_id'];
        $stmt = $pdo->prepare("SELECT * FROM reviews WHERE booking_id = ? AND customer_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$booking_id, $customer_id]);
        $review = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $review]);
        exit;
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
