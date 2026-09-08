<?php
// ไฟล์ api/process-book.php
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // รับค่าจากหน้าเว็บ (ต้องตั้ง name="checkin" ใน HTML ก่อน)
    $checkin = isset($_POST['checkin']) ? $_POST['checkin'] :  '';
    $checkout = isset($_POST['checkout']) ? $_POST['checkout'] :  '';
    
    // ตรงนี้คือส่วนที่คุณจะเขียนคำสั่ง SQL INSERT INTO ลงฐานข้อมูล MySQL
    
    echo json_encode([
        "status" => "success",
        "message" => "รับข้อมูลการจองเรียบร้อยแล้ว!"
    ]);
}
?>
