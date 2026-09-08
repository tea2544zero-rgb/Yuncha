<?php
session_start();
// เชื่อมต่อฐานข้อมูล
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = isset($_POST['action']) ? $_POST['action'] :  '';

    if ($action == 'add') {
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $auth_provider = $_POST['auth_provider'];
        $status = $_POST['status'];

        // Check duplicate phone
        $stmt_check = $conn->prepare("SELECT first_name, last_name FROM customers WHERE phone = ? AND phone != '' AND phone != '-'");
        $stmt_check->execute([$phone]);
        if ($existing = $stmt_check->fetch()) {
            $_SESSION['error'] = 'เบอร์โทรศัพท์นี้ถูกใช้ไปแล้วโดยคุณ ' . $existing['first_name'] . ' ' . $existing['last_name'];
            echo "<script>window.location.href='../customers.php';</script>";
            exit;
        }

        // Check duplicate email
        if(!empty($email)) {
            $stmt_check_e = $conn->prepare("SELECT first_name, last_name FROM customers WHERE email = ? AND email != ''");
            $stmt_check_e->execute([$email]);
            if ($existing_e = $stmt_check_e->fetch()) {
                $_SESSION['error'] = 'อีเมลนี้ถูกใช้ไปแล้วโดยคุณ ' . $existing_e['first_name'] . ' ' . $existing_e['last_name'];
                echo "<script>window.location.href='../customers.php';</script>";
                exit;
            }
        }

        $sql = "INSERT INTO customers (first_name, last_name, phone, email, auth_provider, status) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt->execute([$first_name, $last_name, $phone, $email, $auth_provider, $status])) {
            $_SESSION['success'] = 'เพิ่มข้อมูลลูกค้าใหม่เรียบร้อยแล้ว';
            echo "<script>window.location.href='../customers.php';</script>";
        }
    }
    elseif ($action == 'edit') {
        $customer_id = (int)$_POST['customer_id'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $auth_provider = $_POST['auth_provider'];
        $status = $_POST['status'];

        // Check duplicate phone
        $stmt_check = $conn->prepare("SELECT id FROM customers WHERE phone = ? AND id != ? AND phone != '' AND phone != '-'");
        $stmt_check->execute([$phone, $customer_id]);
        if ($stmt_check->fetch()) {
            $_SESSION['error'] = 'ไม่สามารถใช้เบอร์โทรศัพท์นี้ได้ เนื่องจากซ้ำกับลูกค้ารายอื่น';
            echo "<script>window.location.href='../customers.php';</script>";
            exit;
        }

        $sql = "UPDATE customers SET first_name=?, last_name=?, phone=?, email=?, auth_provider=?, status=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        if ($stmt->execute([$first_name, $last_name, $phone, $email, $auth_provider, $status, $customer_id])) {
            $_SESSION['success'] = 'อัปเดตข้อมูลลูกค้าเรียบร้อยแล้ว';
            echo "<script>window.location.href='../customers.php';</script>";
        }
    }
    elseif ($action == 'delete') {
        $customer_id = (int)$_POST['customer_id'];
        $sql = "UPDATE customers SET status = 'deleted' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt->execute([$customer_id])) {
            $_SESSION['success'] = 'ระงับบัญชีลูกค้าเรียบร้อยแล้ว';
            echo "<script>window.location.href='../customers.php';</script>";
        }
    }
    elseif ($action == 'restore') {
        $customer_id = (int)$_POST['customer_id'];
        $sql = "UPDATE customers SET status = 'active' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt->execute([$customer_id])) {
            $_SESSION['success'] = 'กู้คืนบัญชีลูกค้าเรียบร้อยแล้ว';
            echo "<script>window.location.href='../customers.php';</script>";
        }
    }
}
?>
