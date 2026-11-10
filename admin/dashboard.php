<?php
session_start();

// ডাটাবেস কানেকশন (সঠিক পাথ চেক করা)
if (file_exists('../db.php')) {
    include '../db.php';
} elseif (file_exists('../../db.php')) {
    include '../../db.php';
} elseif (file_exists('db.php')) {
    include 'db.php';
} else {
    $conn = mysqli_connect("localhost", "root", "", "lovia_db");
}

$admin_name = isset($_SESSION['username']) ? $_SESSION['username'] : 'Ridoy';

// ভেরিয়েবলগুলোর ডিফল্ট মান শূন্য সেট করা
$today_sales = 0;
$monthly_sales = 0;

// সেফটি চেকসহ আজকের ও মাসিক সেলস নিখুঁত হিসাব
$today_query = "SHOW TABLES LIKE 'orders'";
$check_table = mysqli_query($conn, $today_query);
if($check_table && mysqli_num_rows($check_table) > 0) {
    // আজকের সেলস (order_date অথবা created_at কলামের নাম যাই হোক না কেন কাজ করার জন্য নিরাপদ কুয়েরি)
    $res_today = mysqli_query($conn, "SELECT SUM(total_amount) as daily_total FROM orders WHERE DATE(order_date) = CURDATE() OR DATE(created_at) = CURDATE()");
    if($res_today) {
        $row_t = mysqli_fetch_assoc($res_today);
        $today_sales = $row_t['daily_total'] ? $row_t['daily_total'] : 0;
    }

    // মাসিক সেলস
    $res_month = mysqli_query($conn, "SELECT SUM(total_amount) as monthly_total FROM orders WHERE (MONTH(order_date) = MONTH(CURDATE()) AND YEAR(order_date) = YEAR(CURDATE())) OR (MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()))");
    if($res_month) {
        $row_m = mysqli_fetch_assoc($res_month);
        $monthly_sales = $row_m['monthly_total'] ? $row_m['monthly_total'] : 0;
    }
}
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elite Cosmetica - Admin Dashboard</title>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f8f9fa; color: #333; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        
        /* Mobile Frame Wrapper (বিকাশ অ্যাপের মতো মোবাইল লুক) */
        .app-container { width: 100%; max-width: 450px; background: #f4f6f9; min-height: 100vh; box-shadow: 0 0 20px rgba(0,0,0,0.1); position: relative; overflow-x: hidden; padding-bottom: 30px; }

        /* Header Banner (bKash Pink Style with Gradient) */
        .header { background: linear-gradient(135deg, #e91e63, #c2185b); color: white; padding: 25px 20px 45px 20px; border-bottom-left-radius: 25px; border-bottom-right-radius: 25px; box-shadow: 0 4px 15px rgba(233, 30, 99, 0.3); }
        .header-top { display: flex; justify-content: space-between; align-items: center; }
        .logo-area { display: flex; align-items: center; gap: 12px; }
        .logo-icon { font-size: 22px; background: rgba(255,255,255,0.2); width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; border-radius: 50%; backdrop-filter: blur(5px); }
        .user-welcome { font-size: 13px; opacity: 0.9; letter-spacing: 0.5px; }
        .user-name { font-size: 20px; font-weight: 700; letter-spacing: 0.5px; }
        .logout-btn { color: white; font-size: 18px; background: rgba(255,255,255,0.15); width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; border-radius: 50%; text-decoration: none; transition: 0.3s; }
        .logout-btn:hover { background: rgba(255,255,255,0.3); }

        /* Main Content Container */
        .content-body { padding: 0 15px; margin-top: -25px; }
        
        /* Grid Card (bKash App Icon Layout Style) */
        .grid-card { background: white; border-radius: 18px; padding: 20px 15px; box-shadow: 0 6px 20px rgba(0,0,0,0.06); margin-bottom: 15px; }
        .section-title { font-size: 13px; font-weight: 700; color: #888; text-transform: uppercase; margin-bottom: 15px; letter-spacing: 0.5px; border-left: 3px solid #e91e63; padding-left: 8px; }
        
        .menu-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; text-align: center; }
        .menu-item { text-decoration: none; color: #333; display: flex; flex-direction: column; align-items: center; transition: transform 0.2s ease; }
        .menu-item:active { transform: scale(0.95); }
        
        .icon-box { width: 52px; height: 52px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 8px; color: white; box-shadow: 0 4px 10px rgba(0,0,0,0.1); transition: 0.3s; }
        .menu-item:hover .icon-box { transform: translateY(-3px); box-shadow: 0 6px 15px rgba(0,0,0,0.15); }
        
        .menu-label { font-size: 11px; font-weight: 600; color: #444; line-height: 1.3; }

        /* Unique Vibrant Colors for Icons */
        .bg-banner { background: linear-gradient(135deg, #ff4081, #c51162); }
        .bg-product { background: linear-gradient(135deg, #00b0ff, #0091ea); }
        .bg-sr { background: linear-gradient(135deg, #00e676, #00c853); }
        .bg-reports { background: linear-gradient(135deg, #7c4dff, #651fff); }

        /* Stats Overview Cards (দৈনিক ও মাসিক সেলস) */
        .stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 15px; }
        .stat-card { background: white; padding: 15px; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.04); border-left: 4px solid #e91e63; display: flex; flex-direction: column; justify-content: space-between; }
        .stat-title { font-size: 12px; color: #666; font-weight: 600; display: flex; align-items: center; gap: 6px; }
        .stat-number { font-size: 18px; font-weight: 800; color: #222; margin-top: 6px; }

        /* Full Width Report Card for SR Daily Summary */
        .full-card { background: white; padding: 16px; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.04); border-left: 4px solid #7c4dff; margin-bottom: 15px; }
        .full-card-title { font-size: 13px; font-weight: 700; color: #444; margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
        .full-card-desc { font-size: 12px; color: #666; line-height: 1.4; }
        .btn-link { display: inline-block; margin-top: 10px; font-size: 12px; font-weight: 700; color: #7c4dff; text-decoration: none; }
        .btn-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <div class="app-container">
        <!-- হেডার সেকশন -->
        <div class="header">
            <div class="header-top">
                <div class="logo-area">
                    <div class="logo-icon">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                    <div>
                        <div class="user-welcome">স্বাগতম,</div>
                        <div class="user-name"><?php echo htmlspecialchars($admin_name); ?></div>
                    </div>
                </div>
                <a href="../logout.php" class="logout-btn" title="লগআউট"><i class="fa-solid fa-power-off"></i></a>
            </div>
        </div>

        <div class="content-body">
            <!-- বিকাশ স্টাইল আইকন মেনু গ্রিড -->
            <div class="grid-card">
                <div class="section-title">অ্যাডমিন কন্ট্রোল প্যানেল</div>
                <div class="menu-grid">
                    
                    <!-- ব্যানার আপলোড অপশন -->
                    <a href="add_banner.php" class="menu-item">
                        <div class="icon-box bg-banner">
                            <i class="fa-solid fa-images"></i>
                        </div>
                        <span class="menu-label">ব্যানার আপলোড</span>
                    </a>

                    <!-- প্রোডাক্ট ম্যানেজমেন্ট -->
                    <a href="products.php" class="menu-item">
                        <div class="icon-box bg-product">
                            <i class="fa-solid fa-boxes-stacked"></i>
                        </div>
                        <span class="menu-label">প্রোডাক্ট স্টক</span>
                    </a>

                    <!-- SR টিম ম্যানেজমেন্ট -->
                    <a href="sr_management.php" class="menu-item">
                        <div class="icon-box bg-sr">
                            <i class="fa-solid fa-users-gear"></i>
                        </div>
                        <span class="menu-label">SR টিম</span>
                    </a>

                    <!-- সেলস রিপোর্ট -->
                    <a href="reports.php" class="menu-item">
                        <div class="icon-box bg-reports">
                            <i class="fa-solid fa-chart-pie"></i>
                        </div>
                        <span class="menu-label">রিপোর্টস</span>
                    </a>

                </div>
            </div>

            <!-- স্ট্যাটাস ও সেলস সামারি কার্ড -->
            <div class="stats-grid">
                <!-- আজকের মোট সেলস (টাকায়) -->
                <div class="stat-card" style="border-left-color: #00b0ff;">
                    <div class="stat-title"><i class="fa-solid fa-bangladeshi-taka-sign" style="color:#00b0ff;"></i> আজকের সেলস</div>
                    <div class="stat-number">৳ <?php echo number_format($today_sales); ?></div>
                </div>

                <!-- মাসিক সেলস -->
                <div class="stat-card" style="border-left-color: #00c853;">
                    <div class="stat-title"><i class="fa-solid fa-calendar-days" style="color:#00c853;"></i> মাসিক সেলস</div>
                    <div class="stat-number">৳ <?php echo number_format($monthly_sales); ?></div>
                </div>
            </div>

            <!-- এসআর দৈনিক রিপোর্ট ও সামারি কার্ড -->
            <div class="full-card">
                <div class="full-card-title">
                    <i class="fa-solid fa-clipboard-user" style="color: #7c4dff; font-size: 16px;"></i>
                    এসআর (SR) দৈনিক রিপোর্ট ও সামারি
                </div>
                <div class="full-card-desc">
                    সকল এসআর-এর আজকের দৈনিক অর্ডার, ভিজিট রিপোর্ট এবং কালেকশন সামারি একত্রে দেখতে এখানে প্রবেশ করুন।
                </div>
                <a href="reports.php" class="btn-link">বিস্তারিত রিপোর্ট দেখুন <i class="fa-solid fa-arrow-right"></i></a>
            </div>

        </div>
    </div>

</body>
</html>