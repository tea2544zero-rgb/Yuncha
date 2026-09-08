<?php
// login_process.php
session_start();
header('Content-Type: application/json');

// --- 1. Rate Limiting (Brute Force Protection) ---
$max_attempts = 5;
$lockout_time = 30; // seconds

if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= $max_attempts) {
    if (time() - $_SESSION['last_attempt_time'] < $lockout_time) {
        $remaining = $lockout_time - (time() - $_SESSION['last_attempt_time']);
        echo json_encode(['success' => false, 'message' => "Too many failed attempts. Please try again in {$remaining} seconds."]);
        exit;
    } else {
        $_SESSION['login_attempts'] = 0;
    }
}

// --- 2. CSRF Protection ---
$token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] :  '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    echo json_encode(['success' => false, 'message' => 'CSRF Token Validation Failed.']);
    exit;
}

// --- 3. Database Connection ---
// Removed legacy SQLite path
try {
    require_once __DIR__ . '/dashboard/config/db.php';
        $pdo = $conn;
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database Connection Failed.']);
    exit;
}

// --- 4. Process Guest Login ---
$user_type = isset($_POST['user_type']) ? $_POST['user_type'] :  'guest';

if ($user_type === 'guest') {
    $phone = trim(isset($_POST['phone']) ? $_POST['phone'] :  '');
    $booking_ref = trim(isset($_POST['booking_ref']) ? $_POST['booking_ref'] :  '');
    
    if (empty($phone) || empty($booking_ref)) {
        echo json_encode(['success' => false, 'message' => 'Please provide both Booking ID and Phone Number.']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT c.* FROM bookings b JOIN customers c ON b.customer_id = c.id WHERE b.booking_ref = ? AND c.phone = ?");
    $stmt->execute([$booking_ref, $phone]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($customer) {
        // Success
        $_SESSION['login_attempts'] = 0;
        $_SESSION['guest_logged_in'] = true; // Legacy support
        $_SESSION['customer_id'] = $customer['id'];
        $_SESSION['customer_name'] = trim($customer['first_name'] . ' ' . $customer['last_name']);
        $_SESSION['customer_email'] = $customer['email'];
        $_SESSION['customer_phone'] = $customer['phone'];
        $_SESSION['last_activity'] = time();
        
        echo json_encode(['success' => true, 'redirect' => 'my-bookings.php']);
    } else {
        // Failed
        $_SESSION['login_attempts'] = (isset($_SESSION['login_attempts']) ? $_SESSION['login_attempts'] :  0) + 1;
        $_SESSION['last_attempt_time'] = time();
        echo json_encode(['success' => false, 'message' => 'No matching reservation found. Please check your details.']);
    }
} else if ($user_type === 'staff') {
    $emp_code = isset($_POST['emp_code']) ? strtoupper(trim($_POST['emp_code'])) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($emp_code) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please provide both Employee Code and Password.']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE emp_code = :emp_code AND status = 'active'");
    $stmt->execute(['emp_code' => $emp_code]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        if (password_verify($password, $user['password_hash']) || $password === $user['password']) {
            $_SESSION['login_attempts'] = 0;
            $_SESSION['emp_id'] = $user['id'];
            $_SESSION['emp_code'] = $user['emp_code'];
            $_SESSION['emp_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['profile_pic'] = $user['profile_pic'];
            
            echo json_encode(['success' => true, 'redirect' => 'dashboard/dashboard.php']);
        } else {
            $_SESSION['login_attempts'] = (isset($_SESSION['login_attempts']) ? $_SESSION['login_attempts'] : 0) + 1;
            $_SESSION['last_attempt_time'] = time();
            echo json_encode(['success' => false, 'message' => 'รหัสผ่านไม่ถูกต้อง (Invalid password)']);
        }
    } else {
        $_SESSION['login_attempts'] = (isset($_SESSION['login_attempts']) ? $_SESSION['login_attempts'] : 0) + 1;
        $_SESSION['last_attempt_time'] = time();
        echo json_encode(['success' => false, 'message' => 'ไม่พบรหัสพนักงานนี้ (Employee not found)']);
    }
}
?>
