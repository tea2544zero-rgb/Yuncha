<?php
// api/chat_config.php
header('Content-Type: application/json; charset=utf-8');

// Removed legacy SQLite path
$config = [
    'chatbot_name' => 'Yuncha AI',
    'chatbot_color' => '#ff003c',
    'chatbot_bg' => 'rgba(248, 250, 252, 0.95)',
    'chatbot_avatar' => ''
];

try {
    if (true) {
        require_once __DIR__ . '/../dashboard/config/db.php';
        $pdo = $conn;
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('chatbot_name', 'chatbot_color', 'chatbot_bg', 'chatbot_avatar')");
        if ($stmt) {
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            if (!empty($settings['chatbot_name'])) $config['chatbot_name'] = $settings['chatbot_name'];
            if (!empty($settings['chatbot_color'])) $config['chatbot_color'] = $settings['chatbot_color'];
            if (!empty($settings['chatbot_bg'])) $config['chatbot_bg'] = $settings['chatbot_bg'];
            if (!empty($settings['chatbot_avatar'])) $config['chatbot_avatar'] = 'dashboard/uploads/' . $settings['chatbot_avatar'];
        }
    }
} catch (Exception $e) {}

echo json_encode($config);
