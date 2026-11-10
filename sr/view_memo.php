<?php
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'sr') {
    header("Location: ../index.php");
    exit();
}

$sr_id = $_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// বর্তমান মেমো বা অর্ডার ডিলিট করার রিকোয়েস্ট হ্যান্ডেল করা
if (isset($_POST['delete_single_order'])) {
    $del_order_id = intval($_POST['order_id'] ?? 0);
    
    if ($del_order_id > 0) {
        // ১. স্টক আবার আগের মতো মূল স্টকে ফেরত দেওয়া
        $items_res = $conn->query("SELECT product_id, quantity FROM order_items WHERE order_id = $del_order_id");
        if ($items_res) {
            while ($it = $items_res->fetch_assoc()) {
                $p_id = $it['product_id'];
                $q = $it['quantity'];
                $conn->query("UPDATE products SET stock = stock + $q WHERE id = $p_id");
            }
        }

        // ২. ডাটাবেস থেকে অর্ডার আইটেম ও মূল অর্ডার টেবিল থেকে চিরতরে ডিলিট করা (যা এসআর ও অ্যাডমিন উভয় প্যানেল থেকেই মুছে ফেলবে)
        $conn->query("DELETE FROM order_items WHERE order_id = $del_order_id");
        $conn->query("DELETE FROM orders WHERE id = $del_order_id");

        // ৩. এই পেজেই থাকা (তালিকার অন্য কোনো মেমো থাকলে সেটি লোড করা)
        $next_order_res = $conn->query("SELECT id FROM orders WHERE sr_id = $sr_id ORDER BY id DESC LIMIT 1");
        if ($next_order_res && $next_order_res->num_rows > 0) {
            $next_ord = $next_order_res->fetch_assoc();
            header("Location: view_memo.php?order_id=" . $next_ord['id']);
        } else {
            header("Location: create_order.php");
        }
        exit();
    }
}

// তারিখ কলাম যাচাই করা (order_date অথবা created_at)
$date_col = "order_date";
$chk_col = $conn->query("SHOW COLUMNS FROM orders LIKE 'order_date'");
if (!$chk_col || $chk_col->num_rows == 0) {
    $date_col = "created_at";
}

$orders_query = $conn->query("SELECT o.*, COALESCE(s.shop_name, 'সাধারণ দোকান') as shop_name 
FROM orders o 
LEFT JOIN shops s ON o.shop_id = s.id 
WHERE o.sr_id = $sr_id 
ORDER BY o.id DESC");

if ($order_id <= 0 && $orders_query && $orders_query->num_rows > 0) {
    $orders_query->data_seek(0);
    $first_order = $orders_query->fetch_assoc();
    $order_id = $first_order['id'];
    $orders_query->data_seek(0);
}

// বর্তমান অর্ডারের দৈনিক সিরিয়াল নম্বর হিসাব করা
$daily_memo_no = 1;
if ($order_id > 0) {
    $ord_info_res = $conn->query("SELECT $date_col as o_date FROM orders WHERE id = $order_id");
    if ($ord_info_res && $ord_info_res->num_rows > 0) {
        $ord_row = $ord_info_res->fetch_assoc();
        $full_date = $ord_row['o_date'];
        $only_date = date('Y-m-d', strtotime($full_date));

        $serial_res = $conn->query("SELECT COUNT(*) as serial_count FROM orders WHERE sr_id = $sr_id AND DATE($date_col) = '$only_date' AND id <= $order_id");
        if ($serial_res) {
            $serial_row = $serial_res->fetch_assoc();
            $daily_memo_no = $serial_row['serial_count'];
        }
    }
}

$order_query = $conn->query("SELECT o.*, 
COALESCE(s.shop_name, 'সাধারণ দোকান') as shop_name, 
COALESCE(s.owner_name, 'প্রযোজ্য নয়') as owner_name, 
COALESCE(s.phone, '') as phone, 
COALESCE(s.address, '') as address, 
COALESCE(u.name, 'SR') as sr_name 
FROM orders o 
LEFT JOIN shops s ON o.shop_id = s.id 
LEFT JOIN users u ON o.sr_id = u.id 
WHERE o.id = $order_id");

$order = ($order_query && $order_query->num_rows > 0) ? $order_query->fetch_assoc() : null;

$items_query = null;
if ($order_id > 0) {
    $items_query = $conn->query("SELECT oi.*, p.name as product_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = $order_id");
}
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>মেমো ম্যানেজমেন্ট - Lovia</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7f6; margin: 0; padding: 20px; }
        .main-container { max-width: 950px; margin: 0 auto; }
        .top-nav-bar { display: flex; justify-content: space-between; align-items: center; background: white; padding: 12px 20px; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.05); margin-bottom: 15px; flex-wrap: wrap; gap: 10px; }
        .top-btn { background: #34495e; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 14px; font-weight: bold; display: inline-flex; align-items: center; gap: 5px; }
        .top-btn:hover { background: #2c3e50; }
        .selector-box { background: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.05); margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; }
        .selector-box label { font-weight: bold; color: #2c3e50; font-size: 15px; }
        .selector-box select { padding: 9px 15px; border-radius: 5px; border: 1px solid #ccc; font-size: 14px; width: 320px; outline: none; background: #fff; cursor: pointer; }
        .memo-container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        .memo-header { text-align: center; border-bottom: 2px solid #34495e; padding-bottom: 15px; margin-bottom: 20px; }
        .memo-header h2 { color: #2c3e50; margin-bottom: 5px; font-size: 24px; }
        .memo-header p { color: #7f8c8d; font-size: 13px; margin: 0; }
        .memo-info { display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 14px; color: #333; flex-wrap: wrap; gap: 15px; }
        .memo-info div { line-height: 1.6; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; font-size: 14px; }
        th { background: #34495e; color: white; }
        .text-right { text-align: right; }
        .total-section { text-align: right; font-size: 16px; font-weight: bold; margin-bottom: 25px; }
        .btn-box { text-align: center; display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
        .btn { padding: 10px 18px; border-radius: 5px; text-decoration: none; font-weight: bold; cursor: pointer; border: none; font-size: 14px; }
        .btn-print { background: #3498db; color: white; }
        .btn-new { background: #27ae60; color: white; }
        .btn-edit { background: #f39c12; color: white; }
        .btn-edit:hover { background: #d68910; }
        .btn-dashboard { background: #e74c3c; color: white; }
        .btn-delete { background: #c0392b; color: white; }
        .btn-delete:hover { background: #a93226; }
        @media print {
            body { background: white; padding: 0; }
            .top-nav-bar, .selector-box, .btn-box { display: none; }
            .memo-container { box-shadow: none; padding: 0; width: 100%; }
        }
    </style>
</head>
<body>

<div class="main-container">
    <div class="top-nav-bar">
        <a href="dashboard.php" class="top-btn">🏠 ড্যাশবোর্ড (হোম)</a>
        
        <div style="display: flex; gap: 8px;">
            <a href="javascript:history.back()" style="text-decoration: none; color: #e91e63; font-weight: bold; font-size: 12px; background: white; padding: 5px 10px; border-radius: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: inline-flex; align-items: center; gap: 4px;">
                ⬅️ পিছে যান
            </a>
            <a href="dashboard.php" style="text-decoration: none; color: #333; font-weight: bold; font-size: 12px; background: white; padding: 5px 10px; border-radius: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: inline-flex; align-items: center; gap: 4px;">
                🏠 হোম
            </a>
        </div>

        <a href="create_order.php" class="top-btn" style="background: #27ae60;">➕ নতুন অর্ডার দিন</a>
    </div>

    <!-- মেমো সিলেক্ট করার ড্রপডাউন বক্স -->
    <div class="selector-box">
        <div>
            <label for="memoSelect">🏪 যে মেমো দেখতে বা ডিলিট করতে চান:</label><br>
            <select id="memoSelect" onchange="location.href='view_memo.php?order_id=' + this.value;" style="margin-top: 5px;">
                <?php 
                if ($orders_query && $orders_query->num_rows > 0) {
                    $orders_query->data_seek(0);
                    while ($row = $orders_query->fetch_assoc()) {
                        // ড্রপডাউনেও দৈনিক সিরিয়াল হিসাব করা
                        $r_id = $row['id'];
                        $r_date = date('Y-m-d', strtotime($row[$date_col] ?? 'now'));
                        $r_serial_res = $conn->query("SELECT COUNT(*) as s_cnt FROM orders WHERE sr_id = $sr_id AND DATE($date_col) = '$r_date' AND id <= $r_id");
                        $r_s_no = ($r_serial_res) ? $r_serial_res->fetch_assoc()['s_cnt'] : $r_id;

                        $selected = ($r_id == $order_id) ? 'selected' : '';
                        echo '<option value="' . $r_id . '" ' . $selected . '>মেমো #' . $r_s_no . ' - ' . htmlspecialchars($row['shop_name']) . ' (৳' . number_format($row['total_amount'], 2) . ')</option>';
                    }
                } else {
                    echo '<option value="">কোনো মেমো পাওয়া যায়নি</option>';
                }
                ?>
            </select>
        </div>
        <div>
            <a href="orders.php" style="background: #34495e; color: white; padding: 10px 15px; border-radius: 5px; text-decoration: none; font-size: 13px; font-weight: bold;">📋 অর্ডার লিস্ট</a>
        </div>
    </div>

    <div class="memo-container">
        <?php if ($order): ?>
            <div class="memo-header">
                <h2>Lovia SR Order Manager</h2>
                <p>অফিশিয়াল সেলস মেমো / ইনভয়েস</p>
            </div>

            <div class="memo-info">
                <div>
                    <strong>দোকানের নাম:</strong> <?php echo htmlspecialchars($order['shop_name']); ?><br>
                    <strong>মালিক:</strong> <?php echo htmlspecialchars($order['owner_name']); ?><br>
                    <strong>মোবাইল:</strong> <?php echo htmlspecialchars($order['phone']); ?><br>
                    <strong>ঠিকানা:</strong> <?php echo htmlspecialchars($order['address']); ?>
                </div>
                <div style="text-align: right;">
                    <strong>মেমো নং:</strong> #<?php echo $daily_memo_no; ?><br>
                    <strong>তারিখ:</strong> <?php echo $order[$date_col]; ?><br>
                    <strong>এসআর নাম:</strong> <?php echo htmlspecialchars($order['sr_name']); ?>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ক্রম</th>
                        <th>পণ্যের নাম</th>
                        <th>পরিমাণ</th>
                        <th>দর (TP)</th>
                        <th class="text-right">মোট দাম</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = 1; 
                    if ($items_query && $items_query->num_rows > 0) {
                        while($item = $items_query->fetch_assoc()): 
                    ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td><?php echo $item['quantity']; ?> পিস</td>
                            <td>৳<?php echo number_format($item['tp'], 2); ?></td>
                            <td class="text-right">৳<?php echo number_format($item['total_price'], 2); ?></td>
                        </tr>
                    <?php 
                        endwhile; 
                    } else {
                        echo '<tr><td colspan="5" style="text-align:center;">কোনো পণ্য পাওয়া যায়নি</td></tr>';
                    }
                    ?>
                </tbody>
            </table>

            <div class="total-section">
                সর্বমোট বিল: ৳<?php echo number_format($order['total_amount'], 2); ?>
            </div>

            <div class="btn-box">
                <button type="button" onclick="window.print();" class="btn btn-print">🖨️ মেমো প্রিন্ট করুন</button>
                <a href="create_order.php" class="btn btn-new">➕ নতুন অর্ডার দিন</a>
                
                <!-- এডিট মেমো বাটন -->
                <a href="edit_order.php?order_id=<?php echo $order_id; ?>" class="btn btn-edit">✏️ এই মেমো এডিট করুন</a>

                <!-- ডিলিট ফর্ম (সঠিক অর্ডার আইডি সহ) -->
                <form action="" method="POST" style="display: inline;" onsubmit="return confirm('আপনি কি নিশ্চিত এই মেমোটি ডিলিট করতে চান? এটি এসআর ও অ্যাডমিন উভয় প্যানেল থেকেই চিরতরে মুছে যাবে।');">
                    <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                    <button type="submit" name="delete_single_order" class="btn btn-delete">🗑️ এই মেমো ডিলিট করুন</button>
                </form>

                <a href="dashboard.php" class="btn btn-dashboard">🏠 ড্যাশবোর্ডে ফিরে যান</a>
            </div>
        <?php else: ?>
            <div style="text-align:center; padding:40px;">
                <h3>⚠️ কোনো মেমো পাওয়া যায়নি</h3>
                <p>দয়া করে আগে একটি নতুন অর্ডার তৈরি করুন।</p>
                <br>
                <a href="dashboard.php" class="btn btn-dashboard">🏠 ড্যাশবোর্ডে ফিরে যান</a>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>