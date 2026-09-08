<?php
try {
    // Use SQLite Database (Compatible with PHP 5.6+)
    $db_path = dirname(dirname(__DIR__)) . '/database/yuncha_valley.sqlite';
    $conn = new PDO('sqlite:' . $db_path);
    // Enable exceptions for errors
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Use emulated prepares to allow replacing mysqli parameter binding easily if needed, but we'll try to use proper PDO
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // ==========================================
    // 🌙 Auto No-Show System (เที่ยงคืน)
    // ==========================================
    $today_date = date('Y-m-d');
    try {
        $stmt_check_ns = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'last_auto_noshow_date'");
        $last_ns = $stmt_check_ns ? $stmt_check_ns->fetchColumn() : null;
        
        if ($last_ns !== $today_date) {
            // เปลี่ยนบิลที่เช็คอินเมื่อวานแต่ไม่มา ให้เป็น no_show
            $stmt_ns = $conn->prepare("UPDATE bookings SET status = 'no_show' WHERE status IN ('pending', 'confirmed') AND check_in < ?");
            $stmt_ns->execute([$today_date]);
            
            $stmt_upd_ns = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'last_auto_noshow_date'");
            $stmt_upd_ns->execute([$today_date]);
            if ($stmt_upd_ns->rowCount() == 0) {
                $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('last_auto_noshow_date', ?)")->execute([$today_date]);
            }
        }
    } catch(PDOException $e) {}

    // ==========================================
    // 🧹 Auto Cleanup System (Pseudo-Cron)
    // ==========================================
    // หากถึงเวลา 12:00 ของทุกวัน ระบบจะบังคับเช็คเอาท์ เคลียร์สถานะห้องที่ค้าง และยกเลิก No-show เพื่อคืนห้องว่าง
    $current_time = date('H:i');
    if ($current_time >= '12:00') {
        try {
            $stmt_check = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'last_auto_clean_date'");
            $last_run = $stmt_check ? $stmt_check->fetchColumn() : null;
            
            if ($last_run !== $today_date) {
                $conn->beginTransaction();
                
                // 1. Auto Checkout บิลที่ลืมเช็คเอาท์ (ที่ถึงกำหนดออกแล้ว)
                $stmt_co = $conn->prepare("UPDATE bookings SET status = 'checked_out' WHERE status = 'checked_in' AND check_out <= ?");
                $stmt_co->execute([$today_date]);
                
                // 2. Auto Clean ห้องที่สถานะค้างเป็น 'cleaning' ให้กลายเป็น 'available' 
                // ยกเว้นห้องที่ 'maintenance' จะไม่ถูกเปลี่ยน
                $stmt_clean = $conn->prepare("UPDATE rooms SET status = 'available' WHERE status = 'cleaning'");
                $stmt_clean->execute();
                
                // 3. ปล่อยห้องว่างสำหรับคนที่ไม่มา (No-Show) ของเมื่อวาน (เปลี่ยนเป็น cancelled ห้องจะได้ว่างให้คนอื่น Walk-in)
                $stmt_free_ns = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE status = 'no_show' AND check_in < ?");
                $stmt_free_ns->execute([$today_date]);
                
                // 3. บันทึกว่าวันนี้รันไปแล้ว จะได้ไม่รันซ้ำอีก
                $stmt_upd = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'last_auto_clean_date'");
                $stmt_upd->execute([$today_date]);
                if ($stmt_upd->rowCount() == 0) {
                    $stmt_ins = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('last_auto_clean_date', ?)");
                    $stmt_ins->execute([$today_date]);
                }
                
                $conn->commit();
            }
        } catch(Exception $e) {
            if($conn->inTransaction()) $conn->rollBack();
        }
    }

} catch (PDOException $e) {
    die("เชื่อมต่อฐานข้อมูลล้มเหลว: " . $e->getMessage());
}
?>
