<?php
session_start();
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = isset($_POST['action']) ? $_POST['action'] :  '';

    try {
        if ($action == 'add') {
            $fname = isset($_POST['first_name']) ? $_POST['first_name'] :  '';
            $lname = isset($_POST['last_name']) ? $_POST['last_name'] :  '';
            $role = isset($_POST['role']) ? $_POST['role'] :  '';
            $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);

            // แยก Prefix ตามคำสั่ง: หัวหน้า (Manager/Admin) รัน T, พนักงาน (Counter) รัน P
            $prefix = ($role === 'manager' || $role === 'admin') ? 'T' : 'P';

            // ค้นหา ID ล่าสุดของกลุ่มนั้นๆ เพื่อรันต่อ
            $stmt_last = $conn->prepare("SELECT emp_code FROM employees WHERE emp_code LIKE ? ORDER BY id DESC LIMIT 1");
            $stmt_last->execute([$prefix . '%']);
            $row_last = $stmt_last->fetch(PDO::FETCH_ASSOC);
            
            if($row_last && isset($row_last['emp_code'])) {
                $last_code = $row_last['emp_code'];
                // ตัดตัวอักษรหน้าออก แล้วบวก 1 (เช่น จาก P01 กลายเป็น 2 -> P02)
                $new_num = str_pad((int)substr($last_code, 1) + 1, 2, '0', STR_PAD_LEFT);
            } else {
                $new_num = '01'; // ถ้ายังไม่มีเลยให้เริ่มที่ 01
            }
            $new_emp_code = $prefix . $new_num;

            $stmt = $conn->prepare("INSERT INTO employees (emp_code, first_name, last_name, role, password) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$new_emp_code, $fname, $lname, $role, $pass]);
            
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><style>body{background:#0f0f0f;color:#fff;font-family:sans-serif}.swal2-popup{border-radius:1rem!important;background:#1a1a1a!important;color:#fff!important;border:1px solid #333!important}</style><script>document.addEventListener('DOMContentLoaded', ()=>Swal.fire({title:'แจ้งเตือน',text:'เพิ่มพนักงานรหัส $new_emp_code สำเร็จ',icon:'warning',confirmButtonColor:'#d97706',background:'#1a1a1a',color:'#fff'}).then(()=>window.location.href='../employees.php'))</script>";

        } elseif ($action == 'edit') {
            $id = (int)$_POST['id'];
            $fname = isset($_POST['first_name']) ? $_POST['first_name'] :  '';
            $lname = isset($_POST['last_name']) ? $_POST['last_name'] :  '';
            $role = isset($_POST['role']) ? $_POST['role'] :  '';
            
            if(!empty($_POST['password'])) {
                $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE employees SET first_name=?, last_name=?, role=?, password=? WHERE id=?");
                $stmt->execute([$fname, $lname, $role, $pass, $id]);
            } else {
                $stmt = $conn->prepare("UPDATE employees SET first_name=?, last_name=?, role=? WHERE id=?");
                $stmt->execute([$fname, $lname, $role, $id]);
            }

            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><style>body{background:#0f0f0f;color:#fff;font-family:sans-serif}.swal2-popup{border-radius:1rem!important;background:#1a1a1a!important;color:#fff!important;border:1px solid #333!important}</style><script>document.addEventListener('DOMContentLoaded', ()=>Swal.fire({title:'แจ้งเตือน',text:'อัปเดตข้อมูลสำเร็จ',icon:'warning',confirmButtonColor:'#d97706',background:'#1a1a1a',color:'#fff'}).then(()=>window.location.href='../employees.php'))</script>";

        } elseif ($action == 'delete') {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("DELETE FROM employees WHERE id=?");
            $stmt->execute([$id]);
            
            echo "<script>window.location.href='../employees.php';</script>";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
