<?php
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// ফিল্টার ডেট বা এসআর আইডি নেওয়া (যদি সিলেক্ট করা থাকে)
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$sr_filter = isset($_GET['sr_id']) ? intval($_GET['sr_id']) : 0;

// ডাইনামিক কুয়েরি তৈরি
$query = "SELECT o.*, s.shop_name, u.name as sr_name FROM orders o 
          JOIN shops s ON o.shop_id = s.id 
          JOIN users u ON o.sr_id = u.id WHERE 1=1";

if (!empty($from_date) && !empty($to_date)) {
    $query .= " AND DATE(o.order_date) BETWEEN '$from_date' AND '$to_date'";
}
if ($sr_filter > 0) {
    $query .= " AND o.sr_id = $sr_filter";
}

$query .= " ORDER BY o.id DESC";
$orders_result = $conn->query($query);

// মোট বিক্রির পরিমাণ হিসাব
$total_sales_query = "SELECT SUM(total_amount) as grand_total FROM orders o WHERE 1=1";
if (!empty($from_date) && !empty($to_date)) {
    $total_sales_query .= " AND DATE(o.order_date) BETWEEN '$from_date' AND '$to_date'";
}
if ($sr_filter > 0) {
    $total_sales_query .= " AND o.sr_id = $sr_filter";
}
$grand_total = $conn->query($total_sales_query)->fetch_assoc()['grand_total'] ?? 0;

// এসআর লিস্ট ফিল্টারের জন্য
$sr_list = $conn->query("SELECT id, name FROM users WHERE role = 'sr' AND status = 'approved'");

// পণ্যের স্টক রিপোর্ট লিস্ট
$stock_result = $conn->query("SELECT * FROM products ORDER BY stock ASC");
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Lovia Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-layout { display: flex; min-height: 100vh; width: 100%; background: #f4f7f6; }
        .sidebar { width: 260px; background: #2c3e50; color: white; padding: 20px; display: flex; flex-direction: column; }
        .sidebar h2 { font-size: 20px; margin-bottom: 30px; text-align: center; color: #ecf0f1; border-bottom: 1px solid #34495e; padding-bottom: 15px; }
        .sidebar a { color: #bdc3c7; text-decoration: none; padding: 12px 15px; border-radius: 6px; margin-bottom: 8px; display: block; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: #3498db; color: white; }
        .main-content { flex: 1; padding: 30px; overflow-y: auto; }
        .filter-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 25px; display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; }
        .filter-group { display: flex; flexDirection: column; }
        .filter-group label { margin-bottom: 5px; font-weight: 600; font-size: 14px; color: #333; }
        .filter-group input, .filter-group select { padding: 8px 12px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px; }
        .btn-filter { background: #3498db; color: white; border: none; padding: 9px 18px; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .btn-filter:hover { background: #2980b9; }
        .btn-reset { background: #95a5a6; color: white; text-decoration: none; padding: 9px 15px; border-radius: 5px; font-size: 14px; font-weight: bold; display: inline-block; }
        .stats-box { background: #27ae60; color: white; padding: 20px; border-radius: 8px; margin-bottom: 25px; font-size: 18px; font-weight: bold; display: flex; justify-content: space-between; align-items: center; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 40px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background: #34495e; color: white; }
        .section-title { margin-bottom: 15px; color: #2c3e50; font-size: 20px; }
    </style>
</head>
<body>

<div class="admin-layout">
    <div class="sidebar">
        <h2>Lovia Admin</h2>
        <a href="dashboard.php">ড্যাশবোর্ড</a>
        <a href="products.php">প্রোডাক্ট ম্যানেজমেন্ট</a>
        <a href="sr_management.php">SR ম্যানেজমেন্ট</a>
        <a href="reports.php" class="active">বিক্রি ও স্টক রিপোর্ট</a>
        <a href="../logout.php" style="margin-top: auto; background: #e74c3c; color: white; text-align: center;">লগআউট</a>
    </div>

    <div class="main-content">
        <h2 class="section-title">অ্যাডমিন রিপোর্ট ও হিসাব</h2>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap;">
    
    <!-- একদম ডান কোণায় ছোট ব্যাক ও হোম বাটন -->
    <div style="display: flex; gap: 8px;">
        <a href="javascript:history.back()" style="text-decoration: none; color: #e91e63; font-weight: bold; font-size: 12px; background: white; padding: 5px 10px; border-radius: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: inline-flex; align-items: center; gap: 4px;">
            <i class="fa-solid fa-arrow-left"></i> পিছে যান
        </a>
        <a href="dashboard.php" style="text-decoration: none; color: #333; font-weight: bold; font-size: 12px; background: white; padding: 5px 10px; border-radius: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: inline-flex; align-items: center; gap: 4px;">
            <i class="fa-solid fa-house"></i> হোম
        </a>
    </div>
</div>

        <!-- ফিল্টার ফর্ম -->
        <form action="" method="GET" class="filter-card">
            <div class="filter-group">
                <label>যে তারিখ থেকে:</label>
                <input type="date" name="from_date" value="<?php echo htmlspecialchars($from_date); ?>">
            </div>
            <div class="filter-group">
                <label>যে তারিখ পর্যন্ত:</label>
                <input type="date" name="to_date" value="<?php echo htmlspecialchars($to_date); ?>">
            </div>
            <div class="filter-group">
                <label>এসআর নির্বাচন করুন:</label>
                <select name="sr_id">
                    <option value="0">-- সকল এসআর --</option>
                    <?php while($sr = $sr_list->fetch_assoc()): ?>
                        <option value="<?php echo $sr['id']; ?>" <?php echo ($sr_filter == $sr['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($sr['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn-filter">রিপোর্ট দেখুন</button>
                <a href="reports.php" class="btn-reset">রিসেট</a>
            </div>
        </form>

        <!-- মোট বিক্রির সামারি -->
        <div class="stats-box">
            <span>নির্বাচিত পরিসরে মোট বিক্রির পরিমাণ:</span>
            <span>৳<?php echo number_format($grand_total, 2); ?></span>
        </div>

        <!-- অর্ডার রিপোর্ট টেবিল -->
        <h3 class="section-title">অর্ডার ও বিক্রির তালিকা</h3>
        <table>
            <thead>
                <tr>
                    <th>অর্ডার আইডি</th>
                    <th>দোকানের নাম</th>
                    <th>এসআর নাম</th>
                    <th>মোট বিল</th>
                    <th>অর্ডারের তারিখ</th>
                    <th>অ্যাকশন</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($orders_result->num_rows > 0): ?>
                    <?php while($row = $orders_result->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?php echo $row['id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($row['shop_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['sr_name']); ?></td>
                            <td><strong>৳<?php echo number_format($row['total_amount'], 2); ?></strong></td>
                            <td><?php echo $row['order_date']; ?></td>
                            <td>
                                <a href="../sr/view_memo.php?order_id=<?php echo $row['id']; ?>" target="_blank" style="color: #3498db; text-decoration: none; font-weight: bold;">মেমো দেখুন</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: #7f8c8d; padding: 20px;">এই ফিল্টারে কোনো অর্ডার পাওয়া যায়নি।</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- পণ্যের স্টক রিপোর্ট টেবিল -->
        <h3 class="section-title">বর্তমান পণ্যের স্টক রিপোর্ট</h3>
        <table>
            <thead>
                <tr>
                    <th>পণ্যের নাম</th>
                    <th>TP</th>
                    <th>MRP</th>
                    <th>খুচরা মূল্য</th>
                    <th>বর্তমান স্টক</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($stock_result->num_rows > 0): ?>
                    <?php while($prod = $stock_result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($prod['name']); ?></strong></td>
                            <td>৳<?php echo number_format($prod['tp'], 2); ?></td>
                            <td>৳<?php echo number_format($prod['mrp'], 2); ?></td>
                            <td>৳<?php echo number_format($prod['retail_price'], 2); ?></td>
                            <td>
                                <span style="font-weight: bold; color: <?php echo ($prod['stock'] > 10) ? '#27ae60' : (($prod['stock'] > 0) ? '#e67e22' : '#e74c3c'); ?>">
                                    <?php echo $prod['stock']; ?> পিস <?php echo ($prod['stock'] <= 5) ? '(কম স্টক!)' : ''; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #7f8c8d; padding: 20px;">কোনো প্রোডাক্ট পাওয়া যায়নি।</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>