<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['emp_code'])) {
    header("Location: ../login.php");
    exit();
}

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($booking_id === 0) {
    die("ไม่พบรหัสการจอง");
}

// ดึงข้อมูลตั้งค่าจากระบบ
$stmt_settings = $conn->query("SELECT setting_key, setting_value FROM settings");
$settings = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);

// ดึงข้อมูลการจอง
$sql = "SELECT b.*, 
        c.first_name, c.last_name, c.phone, c.email,
        r.room_number, r.base_price, r.high_price, r.holiday_price, r.extra_bed_price, r.discount_percent, t.type_name as room_type, 
        p.amount as paid_amount, p.payment_method, p.payment_date, p.status as payment_status
        FROM bookings b
        LEFT JOIN customers c ON b.customer_id = c.id
        LEFT JOIN rooms r ON b.room_id = r.id
        LEFT JOIN room_types t ON r.room_type_id = t.id
        LEFT JOIN payments p ON b.id = p.booking_id
        WHERE b.id = ?";

$stmt = $conn->prepare($sql);
$stmt->execute([$booking_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    die("ไม่พบข้อมูลการจองในระบบ");
}

$hotel_name = isset($settings['hotel_name']) ? $settings['hotel_name'] :  'Yuncha Valley';
$hotel_logo = !empty($settings['hotel_logo']) ? 'uploads/' . $settings['hotel_logo'] : '../img/logo.png';
$hotel_address = isset($settings['address']) ? $settings['address'] :  '123 Tea Mountain Rd, Chiang Rai, TH';
$hotel_phone = isset($settings['phone']) ? $settings['phone'] :  '+66 80 342 9396';
$hotel_email = isset($settings['email']) ? $settings['email'] :  'contact@yunchavalley.com';
$receipt_note = isset($settings['receipt_note']) ? $settings['receipt_note'] :  "<li><strong>Check-in:</strong> 14:00 onwards. <strong>Check-out:</strong> before 12:00.</li>\n<li>A security deposit of <strong>500 THB</strong> is required upon check-in.</li>\n<li>Cancellation Policy: Free cancellation up to 7 days before check-in.</li>\n<li>This is a computer-generated document. No signature is required.</li>";

$customer_name = trim($booking['first_name'] . ' ' . $booking['last_name']);
if(empty($customer_name)) $customer_name = 'Walk-in Guest';

// Calculate dynamic pricing breakdown
$weekday_count = 0;
$weekend_count = 0;
$holiday_count = 0;

$inDate = new DateTime($booking['check_in']);
$outDate = new DateTime($booking['check_out']);
$nights = $inDate->diff($outDate)->days;
if($nights <= 0) $nights = 1;

$holiday_dates_arr = array_map('trim', explode(',', isset($settings['holiday_dates']) ? $settings['holiday_dates'] :  ''));

$base_price = (float)(isset($booking['base_price']) ? $booking['base_price'] :  0);
$high_price = (float)(isset($booking['high_price']) ? $booking['high_price'] :  $base_price);
$holiday_price = (float)(isset($booking['holiday_price']) ? $booking['holiday_price'] :  $base_price);

$extra_beds = (int)(isset($booking['extra_bed']) ? $booking['extra_bed'] :  0);
$extra_bed_price = (float)(isset($booking['extra_bed_price']) ? $booking['extra_bed_price'] :  0);

$discount_percent = (float)(isset($booking['discount_percent']) ? $booking['discount_percent'] :  0);

$currentDate = clone $inDate;
for ($i = 0; $i < $nights; $i++) {
    $dateStr = $currentDate->format('Y-m-d');
    $dayOfWeek = $currentDate->format('N');
    
    if (in_array($dateStr, $holiday_dates_arr) && $holiday_price > 0) {
        $holiday_count++;
    } elseif ($dayOfWeek == 5 || $dayOfWeek == 6) {
        $weekend_count++;
    } else {
        $weekday_count++;
    }
    $currentDate->modify('+1 day');
}

$total_calculated = ($weekday_count * $base_price) + ($weekend_count * $high_price) + ($holiday_count * $holiday_price);
$discount_amount = $total_calculated * ($discount_percent / 100);
$extra_bed_total = $nights * $extra_beds * $extra_bed_price;
$total_expected = $total_calculated - $discount_amount + $extra_bed_total;

$adjustment = (float)$booking['total_price'] - $total_expected;

$status_map = ['pending' => 'PENDING', 'confirmed' => 'PAID', 'checked_in' => 'PAID', 'checked_out' => 'PAID', 'cancelled' => 'CANCELLED'];
$status_class = 'status-' . $booking['status'];
// override for checked in/out to look like confirmed visually
if(in_array($booking['status'], ['checked_in', 'checked_out'])) $status_class = 'status-confirmed';

$total_paid = $booking['total_price'];
$subtotal = $total_paid / 1.07;
$vat = $total_paid - $subtotal;

// Calculate per night breakdown roughly for display
$base_night_price = $total_paid / $nights;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?= htmlspecialchars($booking['booking_ref']) ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&family=Noto+Serif+SC:wght@400;500;600&family=Noto+Serif+Thai:wght@300;400;500;600&family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    
    <!-- CDNs -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <style>
        body {
            background-color: #f3f4f6;
            color: #111827;
            font-family: 'Prompt', sans-serif;
        }
        .font-cinzel { font-family: 'Cinzel', serif; }
        .font-serif-thai { font-family: 'Noto Serif Thai', serif; }

        /* A4 Document Setup */
        .receipt-page {
            width: 210mm;
            min-height: 297mm;
            margin: 40px auto;
            background: white;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            padding: 40px 50px;
            position: relative;
        }

        .status-badge {
            font-size: 14px;
            padding: 6px 16px;
            border-radius: 4px;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 2px;
            font-family: 'Cinzel', serif;
        }
        .status-pending { background-color: #fef3c7; border: 1px solid #f59e0b; color: #b45309; }
        .status-confirmed { background-color: #d1fae5; border: 1px solid #10b981; color: #047857; }
        .status-cancelled { background-color: #fee2e2; border: 1px solid #ef4444; color: #b91c1c; }

        @page { size: A4; margin: 0; }
        @media print {
            body { background-color: white !important; margin: 0; padding: 0; }
            .no-print { display: none !important; }
            .receipt-page { 
                margin: 0 !important;
                padding: 15mm !important; /* Fixed padding for print */
                box-shadow: none !important; 
                width: 210mm !important;
                height: 297mm !important; /* Force exact A4 height */
                min-height: auto !important;
                max-height: 297mm !important;
                overflow: hidden !important; /* Prevent anything from spilling out */
                page-break-after: avoid;
                page-break-inside: avoid;
            }
            /* Remove html overflow hidden which causes blank pages sometimes */
        }
    </style>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>.swal2-popup{border-radius:1rem!important;background:#1a1a1a!important;color:#fff!important;border:1px solid #333!important;}</style>
</head>
<body class="antialiased">

    <!-- Action Buttons (No Print) -->
    <div class="fixed bottom-8 right-8 flex flex-col gap-3 no-print z-50">
        <button onclick="window.print()" class="bg-gray-900 hover:bg-black text-white px-6 py-3 rounded-xl shadow-lg flex items-center justify-center gap-2 transition-transform transform hover:scale-105">
            <i class="ph-bold ph-printer text-xl"></i> พิมพ์ใบเสร็จ (Print)
        </button>
        <button onclick="downloadPNG()" class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-3 rounded-xl shadow-lg flex items-center justify-center gap-2 transition-transform transform hover:scale-105">
            <i class="ph-bold ph-image text-xl"></i> ดาวน์โหลดรูป PNG
        </button>
        <button onclick="generatePDF()" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl shadow-lg flex items-center justify-center gap-2 transition-transform transform hover:scale-105">
            <i class="ph-bold ph-file-pdf text-xl"></i> ดาวน์โหลด PDF
        </button>
        <button onclick="window.close()" class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-800 px-6 py-3 rounded-xl shadow-lg flex items-center justify-center gap-2 transition-transform transform hover:scale-105">
            <i class="ph-bold ph-x text-xl"></i> ปิดหน้าต่างนี้
        </button>
    </div>

    <div class="flex flex-col items-center justify-center px-4 pb-20 no-print-padding">
        
        <!-- Luxury A4 White Receipt -->
        <div id="receipt-document" class="receipt-page">
            
            <!-- 1. Header -->
            <div class="flex flex-col md:flex-row justify-between items-start border-b-2 border-gray-100 pb-8 mb-8">
                <div class="flex items-center gap-5">
                    <img src="<?= $hotel_logo ?>" alt="<?= htmlspecialchars($hotel_name) ?> Logo" class="w-20 h-20 object-contain opacity-80 bg-white" onerror="this.src='https://via.placeholder.com/80x80/000000/FFFFFF?text=Logo'">
                    <div>
                        <h2 class="font-cinzel text-3xl font-bold text-gray-900 tracking-widest uppercase leading-none"><?= htmlspecialchars($hotel_name) ?></h2>
                        <p class="font-serif-thai text-sm text-amber-600 mt-1 italic tracking-wider">Luxury Retreat</p>
                        <p class="text-[10px] text-gray-500 mt-2 tracking-widest leading-relaxed">
                            <?= nl2br(htmlspecialchars($hotel_address)) ?><br>
                            T: <?= htmlspecialchars($hotel_phone) ?><br>
                            E: <?= htmlspecialchars($hotel_email) ?><br>
                            W: www.yunchavalley.com
                        </p>
                    </div>
                </div>
                <div class="flex flex-col items-end gap-3 mt-6 md:mt-0 text-right">
                    <h1 class="font-cinzel text-4xl font-bold text-gray-200 tracking-widest uppercase">RECEIPT</h1>
                    <span class="text-xs text-gray-600 font-mono mt-2">ID: <span class="text-gray-900 font-bold"><?= $booking['booking_ref'] ?></span></span>
                    <span class="text-[10px] text-gray-500 font-mono">Date: <?= date('d M Y, H:i', strtotime($booking['created_at'])) ?></span>
                    <span class="text-[10px] text-gray-400 font-mono">Issued by: <?= htmlspecialchars(isset($_SESSION['fname']) ? $_SESSION['fname'] :  'Admin') ?></span>
                </div>
            </div>

            <!-- 2. Guest Info -->
            <div class="grid grid-cols-2 gap-12 mb-10">
                <div>
                    <h3 class="text-xs font-bold text-amber-600 uppercase tracking-widest mb-4 border-b border-gray-200 pb-2 inline-block w-full">Billed To / Guest Info</h3>
                    <div class="flex flex-col gap-2 text-xs font-prompt text-gray-800">
                        <div class="flex justify-between"><span class="text-gray-500">Name:</span> <span class="font-semibold"><?= htmlspecialchars($customer_name) ?></span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Phone:</span> <span class="font-mono"><?= htmlspecialchars(isset($booking['phone']) ? $booking['phone'] :  '-') ?></span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Email:</span> <span class="font-mono"><?= htmlspecialchars(isset($booking['email']) ? $booking['email'] :  '-') ?></span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Passport / ID:</span> <span class="font-mono">-</span></div>
                    </div>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-amber-600 uppercase tracking-widest mb-4 border-b border-gray-200 pb-2 inline-block w-full">Reservation Details</h3>
                    <div class="flex flex-col gap-2 text-xs font-prompt text-gray-800">
                        <div class="flex justify-between"><span class="text-gray-500">Check-in:</span> <span class="font-semibold"><?= (new DateTime($booking['check_in']))->format('d M Y') ?> (14:00)</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Check-out:</span> <span class="font-semibold"><?= (new DateTime($booking['check_out']))->format('d M Y') ?> (12:00)</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Room:</span> <span class="font-semibold"><?= isset($booking['room_type']) ? $booking['room_type'] :  'N/A' ?></span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Room Code:</span> <span class="font-semibold"><?= htmlspecialchars(isset($booking['room_number']) ? $booking['room_number'] :  'N/A') ?></span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Guests:</span> <span class="font-semibold"><?= (int)$booking['adults'] ?> <?= isset($_SESSION['lang']) && $_SESSION['lang'] === 'en' ? 'Persons' : 'ท่าน' ?></span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Nights:</span> <span class="font-semibold"><?= $nights ?> Nights</span></div>
                    </div>
                </div>
            </div>

            <!-- 3. Itemized Table -->
            <div class="mb-10">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b-2 border-gray-800 text-[10px] text-gray-900 uppercase tracking-widest font-cinzel font-bold bg-gray-50">
                            <th class="py-3 px-4 font-bold rounded-tl-lg">Item Description</th>
                            <th class="py-3 px-4 font-bold text-center">Qty (Nights)</th>
                            <th class="py-3 px-4 font-bold text-right">Unit Price</th>
                            <th class="py-3 px-4 font-bold text-right rounded-tr-lg">Amount (THB)</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm font-prompt text-gray-800">
                        <?php if ($weekday_count > 0): ?>
                        <tr class="border-b border-gray-100">
                            <td class="py-4 px-4">
                                <span class="block font-semibold text-gray-900">Room Rate - Weekday</span>
                                <span class="text-[10px] text-gray-500">Sunday - Thursday</span>
                            </td>
                            <td class="py-4 px-4 text-center"><?= $weekday_count ?></td>
                            <td class="py-4 px-4 text-right font-mono"><?= number_format($base_price, 2) ?></td>
                            <td class="py-4 px-4 text-right font-mono text-gray-900"><?= number_format($weekday_count * $base_price, 2) ?></td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php if ($weekend_count > 0): ?>
                        <tr class="border-b border-gray-100">
                            <td class="py-4 px-4">
                                <span class="block font-semibold text-gray-900">Room Rate - Weekend</span>
                                <span class="text-[10px] text-gray-500">Friday - Saturday</span>
                            </td>
                            <td class="py-4 px-4 text-center"><?= $weekend_count ?></td>
                            <td class="py-4 px-4 text-right font-mono"><?= number_format($high_price, 2) ?></td>
                            <td class="py-4 px-4 text-right font-mono text-gray-900"><?= number_format($weekend_count * $high_price, 2) ?></td>
                        </tr>
                        <?php endif; ?>

                        <?php if ($holiday_count > 0): ?>
                        <tr class="border-b border-gray-100">
                            <td class="py-4 px-4">
                                <span class="block font-semibold text-gray-900">Room Rate - Public Holiday</span>
                                <span class="text-[10px] text-gray-500">Peak Season Surcharge</span>
                            </td>
                            <td class="py-4 px-4 text-center"><?= $holiday_count ?></td>
                            <td class="py-4 px-4 text-right font-mono"><?= number_format($holiday_price, 2) ?></td>
                            <td class="py-4 px-4 text-right font-mono text-gray-900"><?= number_format($holiday_count * $holiday_price, 2) ?></td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php if ($extra_beds > 0): ?>
                        <tr class="border-b border-gray-100">
                            <td class="py-4 px-4">
                                <span class="block font-semibold text-gray-900">Extra Bed(s)</span>
                                <span class="text-[10px] text-gray-500"><?= $extra_beds ?> Bed(s) x <?= $nights ?> Night(s)</span>
                            </td>
                            <td class="py-4 px-4 text-center"><?= $extra_beds * $nights ?></td>
                            <td class="py-4 px-4 text-right font-mono"><?= number_format($extra_bed_price, 2) ?></td>
                            <td class="py-4 px-4 text-right font-mono text-gray-900"><?= number_format($extra_beds * $nights * $extra_bed_price, 2) ?></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- 4. Summary -->
            <div class="flex flex-col md:flex-row justify-between items-end gap-8 mb-12">
                
                <!-- Payment Info -->
                <div class="w-full md:w-auto">
                    <h4 class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2 font-cinzel">Payment Method</h4>
                    <div class="border border-gray-200 p-3 rounded-lg flex items-center gap-3">
                        <div class="text-xl">💳</div>
                        <div class="flex flex-col">
                            <span class="text-xs font-bold text-gray-900"><?= isset($booking['payment_method']) ? $booking['payment_method'] :  'Bank Transfer / PromptPay' ?></span>
                            <span class="text-[10px] text-gray-500">Verified & Approved</span>
                        </div>
                    </div>
                </div>

                <!-- Totals -->
                <div class="w-full md:w-1/2 ml-auto">
                    <div class="flex flex-col gap-3 text-sm font-prompt text-gray-800 bg-gray-50 p-6 rounded-xl border border-gray-100">
                        <div class="flex justify-between">
                            <span class="text-gray-500 uppercase tracking-widest text-xs">Subtotal</span>
                            <span class="font-mono"><?= number_format($subtotal, 2) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 uppercase tracking-widest text-xs">VAT (7%) Included</span>
                            <span class="font-mono"><?= number_format($vat, 2) ?></span>
                        </div>
                        <?php if ($discount_amount > 0): ?>
                        <div class="flex justify-between">
                            <span class="text-gray-500 uppercase tracking-widest text-xs">Discount (<?= $discount_percent ?>%)</span>
                            <span class="font-mono text-red-500">-<?= number_format($discount_amount, 2) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (abs($adjustment) > 0.01): ?>
                        <div class="flex justify-between">
                            <span class="text-gray-500 uppercase tracking-widest text-xs">Adjustment</span>
                            <span class="font-mono"><?= $adjustment > 0 ? '+' : '' ?><?= number_format($adjustment, 2) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="flex justify-between items-center border-t border-gray-300 pt-4 mt-2">
                            <span class="text-gray-900 uppercase tracking-widest font-bold font-cinzel text-lg">Total Amount</span>
                            <span class="font-mono text-3xl font-bold text-amber-600">฿<?= number_format($total_paid, 2) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. Notes -->
            <div class="mb-12">
                <h4 class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2 font-cinzel border-b border-gray-200 pb-2 inline-block">Terms & Conditions</h4>
                <ul class="text-[10px] text-gray-600 list-disc pl-4 space-y-1 font-prompt mt-2">
                    <?= $receipt_note ?>
                </ul>
            </div>

            <!-- 6. Footer -->
            <div class="text-center mt-auto border-t border-gray-200 pt-8">
                <p class="font-cinzel text-lg text-amber-600 italic mb-3">"<?= htmlspecialchars(isset($settings['msg_receipt_footer']) ? $settings['msg_receipt_footer'] :  'Thank you for staying with ' . $hotel_name) ?>"</p>
                <div class="flex justify-center items-center gap-2 opacity-50">
                    <div class="w-12 h-[1px] bg-gray-400"></div>
                    <span class="font-cinzel text-lg text-gray-400">茶</span>
                    <div class="w-12 h-[1px] bg-gray-400"></div>
                </div>
                <p class="text-[9px] text-gray-400 mt-4 tracking-widest uppercase"><?= htmlspecialchars(strtoupper($hotel_name)) ?> CO., LTD. | TAX ID: 0123456789012</p>
            </div>
            
        </div>
    </div>

    <script>
        function downloadPNG() {
            const btn = document.querySelector('button[onclick="downloadPNG()"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '⏳ Processing...';
            
            const element = document.getElementById('receipt-document');
            html2canvas(element, { scale: 2, backgroundColor: '#ffffff', useCORS: true }).then(canvas => {
                const link = document.createElement('a');
                link.download = `Receipt_<?= $booking['booking_ref'] ?>.png`;
                link.href = canvas.toDataURL('image/png');
                link.click();
                btn.innerHTML = originalText;
            });
        }

        function generatePDF() {
            const btn = document.querySelector('button[onclick="generatePDF()"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '⏳ Processing...';
            
            const element = document.getElementById('receipt-document');
            
            // Remove box-shadow temporarily for clean PDF edge
            const oldShadow = element.style.boxShadow;
            const oldMargin = element.style.margin;
            element.style.boxShadow = 'none';
            element.style.margin = '0';
            element.style.backgroundColor = '#ffffff';

            const opt = {
                margin:       0,
                filename:     `Receipt_<?= $booking['booking_ref'] ?>.pdf`,
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true, backgroundColor: '#ffffff' },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save().then(() => {
                element.style.boxShadow = oldShadow;
                element.style.margin = oldMargin;
                btn.innerHTML = originalText;
            });
        }
    </script>
</body>
</html>
