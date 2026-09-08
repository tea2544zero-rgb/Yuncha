<?php
// api/chat_handler.php
header('Content-Type: application/json; charset=utf-8');

// Removed legacy SQLite path
$chatbot_delay = 1500;
$api_key = '';
$model = 'gemini-1.5-flash';
$hotel_name = 'Yuncha Valley';
$system_prompt = 'คุณคือผู้ช่วยเสมือน (Virtual Assistant) ของ ' . $hotel_name . ' ตอบคำถามด้วยความสุภาพและเป็นมิตร';

// ดึงตั้งค่าจากฐานข้อมูล
try {
    if (true) {
        require_once __DIR__ . '/../dashboard/config/db.php';
        $pdo = $conn;
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('chatbot_delay', 'chatbot_api_key', 'chatbot_model', 'chatbot_system_prompt', 'hotel_name')");
        if ($stmt) {
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            if (!empty($settings['hotel_name'])) {
                $hotel_name = $settings['hotel_name'];
                $system_prompt = 'คุณคือผู้ช่วยเสมือน (Virtual Assistant) ของ ' . $hotel_name . ' ตอบคำถามด้วยความสุภาพและเป็นมิตร';
            }
            if (isset($settings['chatbot_delay']) && is_numeric($settings['chatbot_delay'])) {
                $chatbot_delay = (int)$settings['chatbot_delay'];
            }
            if (!empty($settings['chatbot_api_key'])) {
                $api_key = $settings['chatbot_api_key'];
            }
            if (!empty($settings['chatbot_model'])) {
                $model = $settings['chatbot_model'];
            }
            if (!empty($settings['chatbot_system_prompt'])) {
                $system_prompt = $settings['chatbot_system_prompt'];
            }

            // --- DYNAMIC DATA INJECTION ---
            // ทำให้รองรับ PHP 5.6 ได้อย่างสมบูรณ์แบบ
            $system_prompt .= "\n\n**[ข้อมูลระบบปัจจุบัน]**\n";
            $system_prompt .= "-- ข้อมูลห้องพักและราคา --\n";
            $stmt_rooms = $pdo->query("
                SELECT rt.id, rt.type_name, rt.base_price, rt.high_price, rt.holiday_price,
                       (SELECT details FROM rooms WHERE room_type_id = rt.id LIMIT 1) as description,
                       (SELECT base_price FROM rooms WHERE room_type_id = rt.id AND max_guests = 4 LIMIT 1) as price_4p_base,
                       (SELECT high_price FROM rooms WHERE room_type_id = rt.id AND max_guests = 4 LIMIT 1) as price_4p_high,
                       (SELECT holiday_price FROM rooms WHERE room_type_id = rt.id AND max_guests = 4 LIMIT 1) as price_4p_holiday
                FROM room_types rt
            ");
            if ($stmt_rooms) {
                $rooms = $stmt_rooms->fetchAll(PDO::FETCH_ASSOC);
                $view_map = array(
                    3 => 'วิวภูเขา',
                    4 => 'วิวแม่น้ำ',
                    5 => 'วิวไร่ชา'
                );
                
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                $domainName = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'yuncha.test';
                // Find base path if installed in subfolder (e.g., localhost/yuncha)
                $scriptName = dirname(dirname($_SERVER['SCRIPT_NAME'])); // goes from /yuncha/api to /yuncha
                $scriptName = str_replace('\\', '/', $scriptName);
                if ($scriptName == '/') $scriptName = '';
                $base_url = $protocol . $domainName . $scriptName;

                $index = 1;
                foreach ($rooms as $r) {
                    $p4_base = isset($r['price_4p_base']) ? $r['price_4p_base'] : ($r['base_price'] + 900);
                    $p4_high = isset($r['price_4p_high']) ? $r['price_4p_high'] : ($r['high_price'] + 900);
                    $p4_hol = isset($r['price_4p_holiday']) ? $r['price_4p_holiday'] : ($r['holiday_price'] + 900);
                    
                    $view_text = isset($view_map[$r['id']]) ? $view_map[$r['id']] : '';
                    $desc_parts = explode("\n", trim(strip_tags($r['description'])));
                    $clean_desc = isset($desc_parts[0]) ? trim($desc_parts[0]) : '';
                    $room_link = $base_url . "/room/room.php?id=" . $r['id'];
                    
                    $system_prompt .= $index . ". " . $r['type_name'] . " " . $view_text . "\n";
                    $system_prompt .= $clean_desc . "\n";
                    $system_prompt .= "ราคา 2 ท่าน: " . number_format($r['base_price']) . " / " . number_format($r['high_price']) . " / " . number_format($r['holiday_price']) . " บาท | ราคา 4 ท่าน: " . number_format($p4_base) . " / " . number_format($p4_high) . " / " . number_format($p4_hol) . " บาท\n";
                    $system_prompt .= "ลิงก์หน้าห้องพัก: <a href='" . $room_link . "' target='_blank' style='color:#d97706; font-weight:bold; text-decoration:underline;'>คลิกดูรายละเอียดห้อง " . $r['type_name'] . "</a>\n\n";
                    $index++;
                }
                $system_prompt .= "\n-- ลิงก์ระบบที่สำคัญ --\n";
                $system_prompt .= "ลิงก์หน้าจอง (Booking): <a href='" . $base_url . "/booking.php' target='_blank' style='color:#d97706; font-weight:bold; text-decoration:underline;'>คลิกที่นี่เพื่อเช็คห้องว่างและจองห้องพัก</a>\n";
                $system_prompt .= "ลิงก์เข้าสู่ระบบ/โหลดใบเสร็จ (Login): <a href='" . $base_url . "/login.php' target='_blank' style='color:#d97706; font-weight:bold; text-decoration:underline;'>คลิกที่นี่เพื่อเข้าสู่ระบบลูกค้า</a>\n";
            }
            
            // ข้อมูลติดต่อและนโยบายแบบไดนามิก
            $system_prompt .= "\n-- ข้อมูลติดต่อและนโยบายรีสอร์ท --\n";
            
            $stmt_settings = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('phone', 'email', 'address', 'map_url')");
            if ($stmt_settings) {
                $sys_settings = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);
                if (!empty($sys_settings['phone'])) $system_prompt .= "เบอร์โทรศัพท์: " . $sys_settings['phone'] . "\n";
                if (!empty($sys_settings['email'])) $system_prompt .= "อีเมล: " . $sys_settings['email'] . "\n";
                if (!empty($sys_settings['address'])) $system_prompt .= "ที่อยู่: " . $sys_settings['address'] . "\n";
                
                $raw_map_url = !empty($sys_settings['map_url']) ? $sys_settings['map_url'] : "";
                
                // If the user pasted an iframe, extract the src URL
                if (strpos($raw_map_url, '<iframe') !== false && preg_match('/src=["\']([^"\']+)["\']/', $raw_map_url, $matches)) {
                    $raw_map_url = $matches[1];
                }
                
                // If it's an embed URL, try to make it a normal Maps URL for better UX
                if (strpos($raw_map_url, '/embed') !== false) {
                    $raw_map_url = "https://www.google.com/maps/search/?api=1&query=หมู่บ้านรักไทย+แม่ฮ่องสอน";
                }
                
                $map_url = !empty($raw_map_url) ? $raw_map_url : "https://www.google.com/maps/search/?api=1&query=หมู่บ้านรักไทย+แม่ฮ่องสอน";
                $system_prompt .= "ลิงก์แผนที่ Google Maps: <a href='" . $map_url . "' target='_blank' style='color:#d97706; font-weight:bold; text-decoration:underline;'>คลิกเพื่อดูแผนที่การเดินทาง</a>\n";
                
                $system_prompt .= "ลิงก์หน้าติดต่อเรา: <a href='" . $base_url . "/contact.php' target='_blank' style='color:#d97706; font-weight:bold; text-decoration:underline;'>คลิกดูช่องทางการติดต่อทั้งหมด</a>\n";
                $system_prompt .= "ลิงก์หน้านโยบายรีสอร์ท: <a href='" . $base_url . "/policy.php' target='_blank' style='color:#d97706; font-weight:bold; text-decoration:underline;'>คลิกดูนโยบายของรีสอร์ท</a>\n";
            }
            
            // ข้อมูลกิจกรรมและอาหาร
            $system_prompt .= "\n-- ข้อมูลกิจกรรมและอาหาร --\n";
            $system_prompt .= "บริการ: อาหารจีนยูนนาน และ หมูกระทะ (ไม่มีระบบจองล่วงหน้า แจ้งพนักงานเมื่อเข้าพักเท่านั้น)\n";
            $system_prompt .= "ลิงก์หน้ากิจกรรมและอาหาร: <a href='" . $base_url . "/activities.php' target='_blank' style='color:#d97706; font-weight:bold; text-decoration:underline;'>คลิกดูรายละเอียดกิจกรรมและเมนูอาหาร</a>\n";
            // ---------------------------------
        }
    }
} catch (Exception $e) {
    // กรณีที่เกิดข้อผิดพลาดในการดึงข้อมูล Database ให้ใช้ค่าเริ่มต้น
}

// หน่วงเวลาตามที่ตั้งค่าไว้ในระบบหลังบ้าน (ms -> us)
if ($chatbot_delay > 0) {
    usleep($chatbot_delay * 1000);
}

$message = isset($_POST['message']) ? trim($_POST['message']) : '';
$lang = isset($_POST['lang']) ? trim($_POST['lang']) : 'th';

if (empty($message)) {
    echo json_encode(['status' => 'error', 'message' => 'ไม่มีข้อความส่งมา']);
    exit;
}

if ($lang === 'en') {
    $system_prompt .= "\n\n**[CRITICAL INSTRUCTION FOR ENGLISH USER]**\n";
    $system_prompt .= "The user is browsing the ENGLISH version of the website. You MUST reply ENTIRELY in English. Translate all room names, views, descriptions, policies, and button texts to English before generating your response. Do not use any Thai characters.";
}

// ถ้ามี API Key ของ Gemini ให้เรียกใช้ AI
if (!empty($api_key)) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";
    
    $data = [
        "system_instruction" => [
            "parts" => [
                ["text" => $system_prompt]
            ]
        ],
        "contents" => [
            [
                "parts" => [
                    ["text" => $message]
                ]
            ]
        ],
        "generationConfig" => [
            "temperature" => 0.7,
            "maxOutputTokens" => 800
        ]
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response !== false && $httpcode == 200) {
        $json = json_decode($response, true);
        if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
            $ai_reply = $json['candidates'][0]['content']['parts'][0]['text'];
            
            // Post-process the AI reply to ensure links don't get broken by frontend JS
            // 1. Convert Markdown links: [text](url) -> <a href="url">text</a>
            $ai_reply = preg_replace('/\[([^\]]+)\]\((https?:\/\/[^\)]+)\)/', '<a href="$2" target="_blank" style="color:#d97706; font-weight:bold; text-decoration:underline;">$1</a>', $ai_reply);
            
            // 2. Convert raw URLs not inside href: https://... -> <a href="...">คลิกที่นี่</a>
            $ai_reply = preg_replace('/(?<!href=[\'"])(https?:\/\/[^\s<]+)(?![\'"])/', '<a href="$1" target="_blank" style="color:#d97706; font-weight:bold; text-decoration:underline;">$1</a>', $ai_reply);
            
            echo json_encode(['status' => 'success', 'message' => trim($ai_reply)]);
            exit;
        }
    } else {
        // If API fails, we do NOT exit. We simply let it fall down to the keyword system below.
        // We can optionally log the error to a file if needed.
    }
}

// ระบบคีย์เวิร์ดอย่างง่าย (Fallback กรณีไม่มี API Key หรือ AI ขัดข้อง)
$reply = "";
$msg_lower = strtolower($message);

if (strpos($msg_lower, 'สวัสดี') !== false || strpos($msg_lower, 'hi') !== false || strpos($msg_lower, 'หวัดดี') !== false || strpos($msg_lower, 'ไง') !== false) {
    $reply = "สวัสดีค่ะ! ยินดีต้อนรับสู่ Yuncha Valley Resort ค่ะ มีอะไรให้แอดมินรับใช้ดีคะ?\n\n[ดูประเภทห้องพักและราคา]\n[ราคาตามช่วงเวลา]\n[การเดินทางมาที่รีสอร์ต]";
} elseif (strpos($msg_lower, 'ห้องพัก') !== false || strpos($msg_lower, 'ราคา') !== false || strpos($msg_lower, 'คืนละเท่าไหร่') !== false || strpos($msg_lower, 'room') !== false || strpos($msg_lower, 'rate') !== false || strpos($msg_lower, 'ดูประเภทห้องพักและราคา') !== false || strpos($msg_lower, 'รูปแบบห้องพัก') !== false) {
    $reply = "ห้องพักของเรามี 3 รูปแบบ พร้อมวิวงามๆ ให้เลือกค่ะ:\n\n**1. Tea Valley Room**\nวิวภูเขาอันเงียบสงบ ใกล้ชิดธรรมชาติ\n\n**2. Tea Pavilion**\nวิวแม่น้ำ บรรยากาศร่มรื่นและผ่อนคลาย\n\n**3. The Peak Pavilion**\nวิวไร่ชา มุมมองกว้างไกลที่สุด\n\nราคาห้องพักเริ่มต้นที่ 1,390 บาท ไปจนถึงประมาณ 2,590 บาท/คืน (ขึ้นอยู่กับประเภทห้องและวันเข้าพักค่ะ)\n\n[เช็กห้องว่าง / จองห้องพัก]\n[เข้าพักทั้งหมดกี่ท่านคะ?]\n[สนใจวิวแบบไหนคะ?]";
} elseif (strpos($msg_lower, 'จอง') !== false || strpos($msg_lower, 'ว่างไหม') !== false || strpos($msg_lower, 'เช็คห้อง') !== false || strpos($msg_lower, 'book') !== false) {
    $reply = "ลูกค้าสามารถเช็คห้องว่างและทำการจองผ่านระบบหน้าเว็บไซต์ของเราได้โดยตรงเลยค่ะ สะดวกและรวดเร็วมากๆ หรือหากมีข้อสงสัยเพิ่มเติมสอบถามได้เลยนะคะ\n\n[เช็กห้องว่าง / จองห้องพัก]\n[ติดต่อRESORTโดยตรง]";
} elseif (strpos($msg_lower, 'หิว') !== false || strpos($msg_lower, 'อาหาร') !== false || strpos($msg_lower, 'กิน') !== false || strpos($msg_lower, 'หมูกระทะ') !== false || strpos($msg_lower, 'ชา') !== false || strpos($msg_lower, 'food') !== false) {
    $reply = "ที่รีสอร์ทของเรามีบริการ **เซ็ตหม้อไฟยูนนาน** และ **หมูกระทะปิ้งย่าง** เสิร์ฟให้ฟินๆ ถึงระเบียงห้องพักเลยค่ะ! พร้อมชิมชาหอมๆ ท่ามกลางบรรยากาศหนาวๆ ฟินสุดๆ ไปเลยค่ะ\n\n[ดูประเภทห้องพักและราคา]\n[มีกิจกรรมอะไรให้ทำบ้าง?]";
} elseif (strpos($msg_lower, 'กิจกรรม') !== false || strpos($msg_lower, 'ทำอะไร') !== false || strpos($msg_lower, 'activity') !== false) {
    $reply = "มาพักที่นี่รับรองว่าไม่มีเบื่อค่ะ! เรามีกิจกรรมน่าสนใจเช่น:\n- ชิมชาและเดินชมไร่ชา ถ่ายรูปสวยๆ\n- ปั่นจักรยานรับลมชมทะเลสาบ\n- ใส่ชุดพื้นเมืองถ่ายรูปเก๋ๆ\n\n[สถานที่เที่ยวใกล้ๆ]\n[เช็กห้องว่าง / จองห้องพัก]";
} elseif (strpos($msg_lower, 'เที่ยว') !== false || strpos($msg_lower, 'สถานที่') !== false || strpos($msg_lower, 'ใกล้ๆ') !== false || strpos($msg_lower, 'รอบๆ') !== false) {
    $reply = "นอกจากในรีสอร์ทแล้ว รอบๆ Yuncha Valley ยังมีสถานที่เที่ยวเด่นๆ ให้แวะชมค่ะ:\n- **หมู่บ้านรักไทย** (ขับรถ 10 นาที)\n- **ปางอุ๋ง** ทะเลสาบหมอกสุดโรแมนติก (ขับรถ 25 นาที)\n- **จุดชมวิวหยุนไหล** (ขับรถ 15 นาที)\n\nสนใจสอบถามเส้นทางหรือให้แอดมินเช็คห้องว่างให้เลยไหมคะ?\n\n[การเดินทางมาที่รีสอร์ต]\n[เช็กห้องว่าง / จองห้องพัก]";
} elseif (strpos($msg_lower, 'เดินทาง') !== false || strpos($msg_lower, 'จอดรถ') !== false || strpos($msg_lower, 'แผนที่') !== false || strpos($msg_lower, 'ไปยังไง') !== false || strpos($msg_lower, 'ทางไป') !== false || strpos($msg_lower, 'รถไฟ') !== false || strpos($msg_lower, 'รถบัส') !== false || strpos($msg_lower, 'รถตู้') !== false || strpos($msg_lower, 'เครื่องบิน') !== false || strpos($msg_lower, 'สนามบิน') !== false) {
    $reply = "Yuncha Valley Resort เดินทางสะดวกสบายค่ะ ทางเรามี **ลานจอดรถส่วนตัว** ที่ปลอดภัยเตรียมไว้สำหรับแขกผู้เข้าพักทุกท่านค่ะ คุณสามารถดู Google Maps และรายละเอียดการเดินทางได้จากเมนู \"ติดต่อเรา\" หน้าเว็บได้เลยนะคะ\n\n[ดูประเภทห้องพักและราคา]\n[เช็กห้องว่าง / จองห้องพัก]";
} elseif (strpos($msg_lower, 'ราคาตามช่วงเวลา') !== false) {
    $reply = "ราคาห้องพักของเราจะแบ่งตามช่วงเวลาดังนี้ค่ะ:\n- วันธรรมดา (อา.-พฤ.) จะเป็นราคามาตรฐานคุ้มค่าที่สุดค่ะ\n- วันหยุดสุดสัปดาห์ (ศ.-ส.) และวันหยุดนักขัตฤกษ์ จะมีการปรับราคาขึ้นเล็กน้อยตาม High Season ค่ะ\n\nต้องการดูราคาที่แน่นอนของวันที่ลูกค้าสะดวกเข้าพักไหมคะ?\n\n[เช็กห้องว่าง / จองห้องพัก]\n[ดูประเภทห้องพักและราคา]";
} elseif (strpos($msg_lower, 'ติดต่อ') !== false) {
    $reply = "ติดต่อพนักงานโดยตรงได้ที่เบอร์โทรศัพท์หน้าเว็บ หรือทิ้งเบอร์ติดต่อกลับไว้ที่นี่ได้เลยนะคะ แอดมินจะรีบดูแลให้ทันทีค่ะ\n\n[แผนที่การเดินทาง]\n[ดูประเภทห้องพักและราคา]";
}

if (empty($reply)) {
    $reply = "อุ๊ย! ขออภัยด้วยนะคะ พี่Yunchaยังไม่เข้าใจคำถามนี้ หรือระบบอาจจะขัดข้องนิดหน่อยค่ะ 🛠️ แต่เราพร้อมดูแลเสมอค่ะ สนใจห้องพักแบบไหน สอบถามเพิ่มเติมได้เลยนะคะ\n\n[ดูประเภทห้องพักและราคา]\n[เช็กห้องว่าง / จองห้องพัก]\n[การเดินทางมาที่รีสอร์ต]";
}

echo json_encode([
    'status' => 'success',
    'message' => $reply
]);
