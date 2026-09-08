<?php
session_start();

// ตรวจสอบสิทธิ์ (เฉพาะ Manager เท่านั้น)
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'manager') {
    die("Access Denied: เฉพาะผู้จัดการเท่านั้นที่สามารถสำรองฐานข้อมูลได้");
}

$filename = 'backup_yuncha_valley_' . date('Y_m_d_His') . '.sql';

// ตั้งค่า Header สำหรับบังคับดาวน์โหลดไฟล์
header('Content-Description: File Transfer');
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');

// ใช้ mysqldump เพื่อส่งออกฐานข้อมูล
// ต้องตรวจสอบพาธของ mysqldump ถ้าไม่ได้อยู่ใน PATH
$cmd = "mysqldump -u root yuncha_valley";
passthru($cmd);
exit;
?>
