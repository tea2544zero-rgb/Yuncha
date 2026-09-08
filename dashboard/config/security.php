<?php
// Set default timezone for the entire application to Bangkok
date_default_timezone_set('Asia/Bangkok');

// Centralized Security & Role-Based Access Control (RBAC) System
// Yuncha Valley Resort Administrative Dashboard

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Check if user is logged in
if (!isset($_SESSION['role']) || !isset($_SESSION['emp_code'])) {
    header('Location: ../login.php');
    exit;
}

$role = $_SESSION['role'];

// 2. Clean and normalize role strings (supports Thai, English, mixed-case, etc.)
$role_str = mb_strtolower($role, 'UTF-8');
$clean_role = 'receptionist'; // Strict secure fallback

if (strpos($role_str, 'admin') !== false || strpos($role_str, 'ดูแลระบบ') !== false) {
    $clean_role = 'admin';
} elseif (strpos($role_str, 'owner') !== false) {
    $clean_role = 'owner';
} elseif (strpos($role_str, 'manager') !== false || strpos($role_str, 'ผู้จัดการ') !== false) {
    $clean_role = 'manager';
} elseif (strpos($role_str, 'account') !== false || strpos($role_str, 'บัญชี') !== false) {
    $clean_role = 'account';
}

// 3. Define permission flags
$is_admin = ($clean_role === 'admin' || $clean_role === 'owner');
$is_manager = ($clean_role === 'manager' || $is_admin);
$is_account = ($clean_role === 'account' || $is_manager);
$is_counter = ($clean_role === 'receptionist' || $is_manager);

// 4. Default page access state
$has_access = true;
