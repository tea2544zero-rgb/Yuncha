<?php
session_start();
require_once '../config/db.php';

// ??????????????????????????????????????? QR
$upload_dir = '../uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // ????????? Checkbox (??????????????????? ??????????????? $_POST)


        // ??? UPSERT ?????????? Key ???????????????????????????????????????? DB ??? Manual
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON CONFLICT(setting_key) DO UPDATE SET setting_value=excluded.setting_value");
        
        // ?????????????????????????????????????????????????????????
        $stmt_old = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('hotel_logo', 'qr_image', 'chatbot_avatar')");
        $old_files = $stmt_old->fetchAll(PDO::FETCH_KEY_PAIR);

        // Loop ????????? text ???????
        foreach ($_POST as $key => $value) {
            if (is_array($value)) continue; // ?????????????? Array ???? policy_th, policy_en ??????
            $stmt->execute([$key, $value]);
        }

        // ??????????????????????? policies ??????????????
        if (isset($_POST['policy_title_th']) && isset($_POST['policy_title_en']) && isset($_POST['policy_th']) && isset($_POST['policy_en'])) {
            // Removed legacy SQLite path
            if (true) {
                require_once __DIR__ . '/../../dashboard/config/db.php';
        $pdoPolicy = $conn;
                $pdoPolicy->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $stmtPolicy = $pdoPolicy->prepare("UPDATE policies SET category_name_th = ?, category_name_en = ?, content_th = ?, content_en = ? WHERE id = ?");
                foreach ($_POST['policy_th'] as $id => $content_th) {
                    $title_th = isset($_POST['policy_title_th'][$id]) ? $_POST['policy_title_th'][$id] :  '';
                    $title_en = isset($_POST['policy_title_en'][$id]) ? $_POST['policy_title_en'][$id] :  '';
                    $content_en = isset($_POST['policy_en'][$id]) ? $_POST['policy_en'][$id] :  '';
                    $stmtPolicy->execute([$title_th, $title_en, $content_th, $content_en, $id]);
                }
            }
        }
        
        // ??????????????????????? activities ??? act_settings
        // Removed legacy SQLite path
        if (true) {
            require_once __DIR__ . '/../../dashboard/config/db.php';
        $pdoAct = $conn;
            $pdoAct->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // ??????????? act_settings
            if (isset($_POST['act_set_th']) && isset($_POST['act_set_en'])) {
                $stmtActSet = $pdoAct->prepare("UPDATE act_settings SET setting_value_th = ?, setting_value_en = ? WHERE setting_key = ?");
                foreach ($_POST['act_set_th'] as $key => $val_th) {
                    $val_en = isset($_POST['act_set_en'][$key]) ? $_POST['act_set_en'][$key] :  '';
                    $stmtActSet->execute([$val_th, $val_en, $key]);
                }
            }
            
            // ??????????? activities
            if (isset($_POST['act_title_th']) && isset($_POST['act_title_en']) && isset($_POST['act_sub_th']) && isset($_POST['act_sub_en']) && isset($_POST['act_desc_th']) && isset($_POST['act_desc_en'])) {
                $stmtAct = $pdoAct->prepare("UPDATE activities SET title_th = ?, title_en = ?, sub_th = ?, sub_en = ?, desc_th = ?, desc_en = ? WHERE id = ?");
                foreach ($_POST['act_title_th'] as $id => $title_th) {
                    $title_en = isset($_POST['act_title_en'][$id]) ? $_POST['act_title_en'][$id] :  '';
                    $sub_th = isset($_POST['act_sub_th'][$id]) ? $_POST['act_sub_th'][$id] :  '';
                    $sub_en = isset($_POST['act_sub_en'][$id]) ? $_POST['act_sub_en'][$id] :  '';
                    $desc_th = isset($_POST['act_desc_th'][$id]) ? $_POST['act_desc_th'][$id] :  '';
                    $desc_en = isset($_POST['act_desc_en'][$id]) ? $_POST['act_desc_en'][$id] :  '';
                    $stmtAct->execute([$title_th, $title_en, $sub_th, $sub_en, $desc_th, $desc_en, $id]);
                }
            }
        }
        
        // ????????????????? (?????)
        if (!empty($_FILES['hotel_logo']['name']) && $_FILES['hotel_logo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['hotel_logo']['name'], PATHINFO_EXTENSION));
            $logo_name = "hotel_logo_" . time() . "." . $ext;
            if (move_uploaded_file($_FILES['hotel_logo']['tmp_name'], $upload_dir . $logo_name)) {
                // ?????????
                if (!empty($old_files['hotel_logo']) && file_exists($upload_dir . $old_files['hotel_logo'])) {
                    @unlink($upload_dir . $old_files['hotel_logo']);
                }
                $stmt->execute(['hotel_logo', $logo_name]);
            }
        }
        
        if (!empty($_FILES['qr_image']['name']) && $_FILES['qr_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['qr_image']['name'], PATHINFO_EXTENSION));
            $qr_name = "qr_image_" . time() . "." . $ext;
            if (move_uploaded_file($_FILES['qr_image']['tmp_name'], $upload_dir . $qr_name)) {
                // ?????????
                if (!empty($old_files['qr_image']) && file_exists($upload_dir . $old_files['qr_image'])) {
                    @unlink($upload_dir . $old_files['qr_image']);
                }
                $stmt->execute(['qr_image', $qr_name]);
            }
        }

        if (!empty($_FILES['chatbot_avatar']['name']) && $_FILES['chatbot_avatar']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['chatbot_avatar']['name'], PATHINFO_EXTENSION));
            $avatar_name = "chatbot_avatar_" . time() . "." . $ext;
            if (move_uploaded_file($_FILES['chatbot_avatar']['tmp_name'], $upload_dir . $avatar_name)) {
                if (!empty($old_files['chatbot_avatar']) && file_exists($upload_dir . $old_files['chatbot_avatar'])) {
                    @unlink($upload_dir . $old_files['chatbot_avatar']);
                }
                $stmt->execute(['chatbot_avatar', $avatar_name]);
            }
        }
        


        
        // ?????? Log ??????????????????
        $emp_name = isset($_SESSION['emp_name']) ? $_SESSION['emp_name'] :  'System';
        $emp_code = isset($_SESSION['emp_code']) ? $_SESSION['emp_code'] :  'SYS';
        $user_fullname = $emp_code . ' (' . $emp_name . ')';
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] :  'Unknown';
        
        $log_stmt = $conn->prepare("INSERT INTO security_logs (user_name, action, ip_address, status) VALUES (?, '???????????????????? (Update Settings)', ?, 'Success')");
        $log_stmt->execute([$user_fullname, $ip]);

        $active_tab = isset($_POST['active_tab']) ? $_POST['active_tab'] :  'general';
        echo "<script>window.location.href='../settings.php?tab=' + encodeURIComponent('$active_tab') + '&success=settings';</script>";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>


