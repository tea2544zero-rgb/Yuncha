<?php
// ไฟล์นี้เป็น API สำหรับให้เว็บไซต์หลัก (หน้าบ้าน) เข้ามาดึงรูปสไลด์แบนเนอร์ไปแสดง
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 

$upload_dir = dirname(__DIR__) . '/uploads/banners/';
$banners = [];

if (is_dir($upload_dir)) {
    $files = scandir($upload_dir);
    foreach ($files as $file) {
        // กรองเอาเฉพาะไฟล์ภาพที่แอดมินอัปโหลดมาเท่านั้น (หลีกเลี่ยงไฟล์ขยะหรือระบบ)
        if (preg_match('/^banner_[a-f0-9]{16}\.(jpg|png)$/', $file)) {
            $banners[] = [
                'url' => 'uploads/banners/' . $file,
                'filename' => $file
            ];
        }
    }
}

echo json_encode(['success' => true, 'data' => $banners]);
