<?php
require_once 'config/security.php';
if (!$is_account) { die("Access Denied"); }
require_once 'config/db.php';

$view_mode = isset($_GET['view']) ? $_GET['view'] :  'daily';
$selected_month = isset($_GET['month']) ? $_GET['month'] :  date('Y-m');
$today = date('Y-m-d');
$current_year = date('Y', strtotime($selected_month));
$current_month_num = date('m', strtotime($selected_month));

$filename_date = date('d-m-') . (date('Y') + 543);
if($view_mode === 'daily') {
    $tx_sql = "SELECT * FROM transactions WHERE transaction_date='$today' ORDER BY transaction_time DESC, id DESC";
    $filename = $filename_date . ".csv";
} else {
    $tx_sql = "SELECT * FROM transactions WHERE strftime('%m', transaction_date)='$current_month_num' AND strftime('%Y', transaction_date)='$current_year' ORDER BY transaction_date DESC, transaction_time DESC, id DESC";
    $filename = $filename_date . ".csv";
}

$tx_res = $conn->query($tx_sql);
$transactions = $tx_res ? $tx_res->fetchAll(PDO::FETCH_ASSOC) : [];

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
// Add UTF-8 BOM so Excel reads Thai characters correctly
fwrite($output, "\xEF\xBB\xBF");

fputcsv($output, ['วันที่ (Date)', 'เวลา (Time)', 'ประเภท (Type)', 'หมวดหมู่ (Category)', 'จำนวนเงินสุทธิ (Net Amount)', 'ช่องทาง (Method)', 'รายละเอียด (Notes)', 'รหัสอ้างอิง (Ref No)']);

foreach ($transactions as $t) {
    $type_text = ($t['transaction_type'] == 'income') ? 'รายรับ' : 'รายจ่าย';
    fputcsv($output, [
        date('d/m/Y', strtotime($t['transaction_date'])),
        date('H:i', strtotime($t['transaction_time'])),
        $type_text,
        $t['category'],
        $t['net_amount'],
        $t['payment_method'],
        $t['notes'],
        $t['reference_no']
    ]);
}
fclose($output);
exit;
?>
