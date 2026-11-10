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

// তালিকা থেকে সরাসরি অর্ডার ডিলিট করার রিকোয়েস্ট হ্যান্ডেল করা
if (isset($_GET['delete_id'])) {
    $del_order_id = intval($_GET['delete_id']);
    
    // ১. স্টক আগের মতো ফেরত দেওয়া
    $items_res = $conn->query("SELECT product_id, quantity FROM order_items WHERE order_id = $del_order_id");
    while ($it = $items_res->fetch_assoc()) {
        $p_id = $it['product_id'];
        $q = $it['quantity'];
        $conn->query("UPDATE products SET stock = stock + $q WHERE id = $p_id");
    }

    // ২. order_items থেকে আইটেম ডিলিট
    $conn->query("DELETE FROM order_items WHERE order_id = $del_order_id");

    // ৩. orders টেবিল থেকে অর্ডার ডিলিট
    $conn->query("DELETE FROM orders WHERE id = $del_order_id AND sr_id = $sr_id");

    header("Location: orders.php");
    exit();
}

$orders = $conn->query("SELECT o.*, s.shop_name, s.phone FROM orders o JOIN shops s ON o.shop_id = s.id WHERE o.sr_id = $sr_id ORDER BY o.id DESC");
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - Lovia SR Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .sr-layout { display: flex; min-height: 100vh; width: 100%; background: #f4f7f6; }
        .sidebar { width: 260px; background: #34495e; color: white; padding: 20px; display: flex; flex-direction: column; }
        .sidebar h2 { font-size: 20px; margin-bottom: 30px; text-align: center; color: #ecf0f1; border-bottom: 1px solid #4e6d8c; padding-bottom: 15px; }
        .sidebar a { color: #bdc3c7; text-decoration: none; padding: 12px 15px; border-radius: 6px; margin-bottom: 8px; display: block; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: #27ae60; color: white; }
        .main-content { flex: 1; padding: 30px; overflow-y: auto; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; background: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); flex-wrap: wrap; gap: 10px; }
        .btn-add { background: #27ae60; color: white; padding: 10px 18px; border-radius: 6px; text-decoration: none; font-weight: 600; }
        .btn-add:hover { background: #219150; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #34495e; color: white; }
        .btn-view { background: #3498db; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: bold; }
        .btn-view:hover { background: #2980b9; }
        .btn-del { background: #e74c3c; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: bold; margin-left: 5px; }
        .btn-del:hover { background: #c0392b; }
    </style>
</head>
<body>

<div class="sr-layout">
    <div class="sidebar">
        <h2>SR Panel</h2>
        <a href="dashboard.php">ড্যাশবোর্ড</a>
        <a href="shops.php">দোকান ম্যানেজমেন্ট</a>
        <a href="orders.php" class="active">অর্ডার ম্যানেজমেন্ট</a>
        <a href="../logout.php" style="margin-top: auto; background: #e74c3c; color: white; text-align: center;">লগআউট</a>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2>পূর্ববর্তী অর্ডার তালিকা</h2>
            
            <!-- ডান কোণায় ব্যাক ও হোম বাটন -->
            <div style="display: flex; gap: 8px;">
                <a href="javascript:history.back()" style="text-decoration: none; color: #e91e63; font-weight: bold; font-size: 12px; background: white; padding: 5px 10px; border-radius: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: inline-flex; align-items: center; gap: 4px;">
                    ⬅️ পিছে যান
                </a>
                <a href="dashboard.php" style="text-decoration: none; color: #333; font-weight: bold; font-size: 12px; background: white; padding: 5px 10px; border-radius: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: inline-flex; align-items: center; gap: 4px;">
                    🏠 হোম
                </a>
            </div>

            <a href="create_order.php" class="btn-add">+ নতুন অর্ডার তৈরি করুন</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>অর্ডার আইডি</th>
                    <th>দোকানের নাম</th>
                    <th>মোবাইল</th>
                    <th>সর্বমোট বিল</th>
                    <th>তারিখ</th>
                    <th>অ্যাকশন</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($orders->num_rows > 0): ?>
                    <?php while($row = $orders->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?php echo $row['id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($row['shop_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['phone']); ?></td>
                            <td><strong>৳<?php echo number_format($row['total_amount'], 2); ?></strong></td>
                            <td><?php echo $row['order_date']; ?></td>
                            <td>
                                <a href="view_memo.php?order_id=<?php echo $row['id']; ?>" class="btn-view">মেমো দেখুন</a>
                                <a href="orders.php?delete_id=<?php echo $row['id']; ?>" class="btn-del" onclick="return confirm('সতর্কতা: আপনি কি নিশ্চিতভাবে এই অর্ডারটি ডিলিট করতে চান? স্টক রিভার্স হয়ে যাবে।');">ডিলিট</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: #7f8c8d; padding: 20px;">কোনো অর্ডার পাওয়া যায়নি। নতুন অর্ডার করুন।</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>