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
$today = date('Y-m-d');

$daily_summary = $conn->query("SELECT COUNT(id) as total_orders, SUM(total_amount) as total_sales FROM orders WHERE sr_id = $sr_id AND DATE(order_date) = '$today'");
$summary = $daily_summary->fetch_assoc();

$today_orders = $conn->query("SELECT o.*, COALESCE(s.shop_name, 'সাধারণ দোকান') as shop_name FROM orders o LEFT JOIN shops s ON o.shop_id = s.id WHERE o.sr_id = $sr_id AND DATE(o.order_date) = '$today' ORDER BY o.id DESC");
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ডেইলি সেলস - Lovia</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7f6; margin: 0; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        .header { text-align: center; border-bottom: 2px solid #34495e; padding-bottom: 15px; margin-bottom: 25px; }
        .header h2 { color: #2c3e50; margin: 0 0 5px 0; }
        .header p { color: #7f8c8d; margin: 0; font-size: 14px; }
        
        .cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .card { background: #f8f9fa; border-left: 5px solid #27ae60; padding: 20px; border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.03); }
        .card:nth-child(2) { border-left-color: #3498db; }
        .card h4 { margin: 0 0 10px 0; color: #555; font-size: 15px; }
        .card h2 { margin: 0; color: #2c3e50; font-size: 24px; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; font-size: 14px; }
        th { background: #34495e; color: white; }
        .text-right { text-align: right; }
        
        .btn-box { text-align: center; margin-top: 25px; }
        .btn { padding: 10px 20px; background: #34495e; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block; }
        .btn:hover { background: #2c3e50; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>📅 আজকের ডেইলি সেলস রিপোর্ট (Daily Sales)</h2>
        <p>তারিখ: <?php echo $today; ?></p>
    </div>

    <div class="cards-grid">
        <div class="card">
            <h4>আজকের মোট অর্ডার</h4>
            <h2><?php echo $summary['total_orders'] ? $summary['total_orders'] : 0; ?> টি</h2>
        </div>
        <div class="card">
            <h4>আজকের মোট বিক্রি</h4>
            <h2>৳<?php echo number_format($summary['total_sales'] ? $summary['total_sales'] : 0, 2); ?></h2>
        </div>
    </div>

    <h3 style="color: #2c3e50; border-bottom: 1px solid #ddd; padding-bottom: 8px;">আজকের অর্ডারকৃত মেমোগুলি</h3>
    <table>
        <thead>
            <tr>
                <th>মেমো নং</th>
                <th>দোকানের নাম</th>
                <th>সময়</th>
                <th class="text-right">মোট বিল</th>
                <th style="text-align: center;">অ্যাকশন</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if ($today_orders && $today_orders->num_rows > 0) {
                while($row = $today_orders->fetch_assoc()):
            ?>
                <tr>
                    <td>#<?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['shop_name']); ?></td>
                    <td><?php echo $row['order_date']; ?></td>
                    <td class="text-right">৳<?php echo number_format($row['total_amount'], 2); ?></td>
                    <td style="text-align: center;">
                        <a href="view_memo.php?order_id=<?php echo $row['id']; ?>" style="color: #3498db; text-decoration: none; font-weight: bold;">মেমো দেখুন</a>
                    </td>
                </tr>
            <?php 
                endwhile;
            } else {
                echo '<tr><td colspan="5" style="text-align: center;">আজকে এখনো কোনো অর্ডার করা হয়নি</td></tr>';
            }
            ?>
        </tbody>
    </table>

    <div class="btn-box">
        <a href="dashboard.php" class="btn">🏠 ড্যাশবোর্ডে ফিরে যান</a>
    </div>
</div>

</body>
</html>