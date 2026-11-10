<?php
// এসআর প্যানেল থেকে সঠিক পাথ দিয়ে ডেটাবেস কানেকশন যুক্ত করা
if (file_exists('../db.php')) {
    include '../db.php';
} else if (file_exists('db.php')) {
    include 'db.php';
} else if (file_exists('../config/db.php')) {
    include '../config/db.php';
} else if (file_exists('config/db.php')) {
    include 'config/db.php';
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'SR';
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>SR Dashboard - Elite Cosmetica Ltd</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 15px; }
        .dashboard-container { max-width: 600px; margin: auto; background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .welcome-text { font-size: 18px; color: #2c3e50; font-weight: bold; margin-bottom: 20px; text-align: center; }
        
        /* মূল লাল/গোলাপি ব্যানার */
        .main-banner {
            width: 100%;
            background: linear-gradient(135deg, #d32f2f, #e91e63);
            color: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 15px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
            box-sizing: border-box;
        }
        .main-banner h2 { margin: 0 0 5px 0; font-size: 20px; }
        .main-banner p { margin: 0; font-size: 13px; }

        /* ডাইনামিক ইমেজ ব্যানার স্লাইডার */
        .image-slider-box {
            width: 100%;
            height: 160px;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
            margin-bottom: 25px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
            border: 1px solid #eee;
        }
        .slider-img {
            position: absolute;
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
            top: 0;
            left: 0;
        }
        .slider-img.active {
            display: block;
        }
        .slider-fallback {
            position: absolute;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #6c5ce7, #8e44ad);
            color: white;
            display: none;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 10px;
            box-sizing: border-box;
            top: 0;
            left: 0;
        }
        .slider-fallback.active {
            display: flex;
        }
        .slider-fallback h4 { margin: 0 0 5px 0; font-size: 16px; }
        .slider-fallback p { margin: 0; font-size: 12px; }

        /* সেকশন হেডিং ও মেনু গ্রিড */
        .section-title { font-size: 14px; font-weight: bold; color: #333; margin: 20px 0 10px 5px; border-left: 3px solid #d32f2f; padding-left: 8px; }
        .menu-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 10px; }
        .menu-item { display: flex; flex-direction: column; align-items: center; text-decoration: none; background: #fff; padding: 10px 5px; border-radius: 10px; transition: 0.2s; border: 1px solid #f9f9f9; }
        .menu-item:hover { transform: translateY(-3px); background: #fdfdfd; }
        .menu-icon { width: 50px; height: 50px; background-color: #fdf2f4; border-radius: 50%; display: flex; justify-content: center; align-items: center; margin-bottom: 8px; box-shadow: 0 2px 4px rgba(220, 0, 115, 0.1); }
        .menu-title { font-size: 12px; color: #333; text-align: center; font-weight: 500; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <div class="welcome-text">স্বাগতম, SR (<?php echo htmlspecialchars($user_name); ?>)!</div>

    <!-- মূল লাল ব্যানার -->
    <div class="main-banner">
        <h2>Elite Cosmetica Ltd</h2>
        <p>আমাদের প্রিমিয়াম কসমেটিকস ও কনফেকশনারি পণ্য এখন আপনার হাতের মুঠোয়</p>
    </div>
<!-- ইমেজ ব্যানার স্লাইডার -->
    <div class="image-slider-box" id="bannerSlider">
        <?php
        $banner_query = "SELECT * FROM banners ORDER BY id DESC";
        $banner_result = mysqli_query($conn, $banner_query);
        $has_images = false;

        if ($banner_result && mysqli_num_rows($banner_result) > 0) {
            $is_first = true;
            while ($banner = mysqli_fetch_assoc($banner_result)) {
                // নিশ্চিত করা যে image_path ফাঁকা নয়
                if (!empty($banner['image_path'])) {
                    $has_images = true;
                    $active_class = $is_first ? 'active' : '';
                    echo '<img src="../uploads/' . htmlspecialchars($banner['image_path']) . '" class="slider-img ' . $active_class . '" alt="Banner">';
                    $is_first = false;
                }
            }
        }

        // যদি ডাটাবেসে সত্যিকারের কোনো ছবি না থাকে তবেই কেবল এই মেসেজটি দেখাবে
        if (!$has_images) {
            echo '<div class="slider-fallback active" style="display:flex; justify-content:center; align-items:center; background:#f8f9fa; color:#666; height:100%; position:absolute; width:100%; top:0; left:0;">
                    <h4>কোনো ব্যানার আপলোড করা হয়নি</h4>
                  </div>';
        }
        ?>
    </div>

    <!-- কার্যক্রম ও অর্ডার সেকশন -->
    <div class="section-title">কার্যক্রম ও অর্ডার</div>
    <div class="menu-grid">
        <a href="create_order.php" class="menu-item">
            <div class="menu-icon"><i class="fa-solid fa-cart-shopping" style="color: #e91e63;"></i></div>
            <span class="menu-title">নতুন অর্ডার</span>
        </a>
        <a href="orders.php" class="menu-item">
            <div class="menu-icon"><i class="fa-solid fa-file-invoice" style="color: #e91e63;"></i></div>
            <span class="menu-title">অর্ডার তালিকা</span>
        </a>
        <a href="view_memo.php" class="menu-item">
            <div class="menu-icon"><i class="fa-solid fa-note-sticky" style="color: #e91e63;"></i></div>
            <span class="menu-title">সেভ মেমো</span>
        </a>
        <a href="summary.php" class="menu-item">
            <div class="menu-icon"><i class="fa-solid fa-chart-pie" style="color: #e91e63;"></i></div>
            <span class="menu-title">সামারি</span>
        </a>
    </div>

    <!-- দোকান ব্যবস্থাপনা সেকশন -->
    <div class="section-title">দোকান ব্যবস্থাপনা</div>
    <div class="menu-grid">
        <a href="add_shop.php" class="menu-item">
            <div class="menu-icon"><i class="fa-solid fa-store" style="color: #e91e63;"></i></div>
            <span class="menu-title">নতুন দোকান</span>
        </a>
        <a href="shops.php" class="menu-item">
            <div class="menu-icon"><i class="fa-solid fa-shop" style="color: #e91e63;"></i></div>
            <span class="menu-title">দোকানের তালিকা</span>
        </a>
    </div>

    <!-- স্টক ও পণ্য সেকশন -->
    <div class="section-title">স্টক ও পণ্য</div>
    <div class="menu-grid">
        <a href="products.php" class="menu-item">
            <div class="menu-icon"><i class="fa-solid fa-box-open" style="color: #e91e63;"></i></div>
            <span class="menu-title">পণ্য তালিকা</span>
        </a>
        <a href="current_stock.php" class="menu-item">
            <div class="menu-icon"><i class="fa-solid fa-boxes-stacked" style="color: #e91e63;"></i></div>
            <span class="menu-title">কারেন্ট স্টক</span>
        </a>
        <a href="stock_report.php" class="menu-item">
            <div class="menu-icon"><i class="fa-solid fa-chart-column" style="color: #e91e63;"></i></div>
            <span class="menu-title">স্টক রিপোর্ট</span>
        </a>
    </div>

    <!-- রিপোর্ট ও অন্যান্য সেকশন -->
    <div class="section-title">রিপোর্ট ও অন্যান্য</div>
    <div class="menu-grid">
        <a href="daily_sales.php" class="menu-item">
            <div class="menu-icon"><i class="fa-solid fa-calendar-day" style="color: #e91e63;"></i></div>
            <span class="menu-title">ডেইলি সেলস</span>
        </a>
        <a href="monthly_sales.php" class="menu-item">
            <div class="menu-icon"><i class="fa-solid fa-calendar-days" style="color: #e91e63;"></i></div>
            <span class="menu-title">মাসিক সেলস</span>
        </a>
        <a href="profile.php" class="menu-item">
            <div class="menu-icon"><i class="fa-solid fa-user" style="color: #e91e63;"></i></div>
            <span class="menu-title">প্রোফাইল</span>
        </a>
        <a href="../logout.php" class="menu-item">
            <div class="menu-icon"><i class="fa-solid fa-right-from-bracket" style="color: #e91e63;"></i></div>
            <span class="menu-title">লগআউট</span>
        </a>
    </div>
</div>

<!-- অটোমেটিক স্লাইডার জাভাস্ক্রিপ্ট -->
<script>
    let currentSlide = 0;
    // শুধু যেগুলো আসল ছবি (.slider-img) কেবল সেগুলোই নেবে, অন্য কিছু নয়
    const slides = document.querySelectorAll('.image-slider-box .slider-img');
    
    function autoSlideBanner() {
        if (slides.length > 1) {
            slides[currentSlide].classList.remove('active');
            currentSlide = (currentSlide + 1) % slides.length;
            slides[currentSlide].classList.add('active');
        }
    }
    
    // যদি ১টির বেশি ছবি থাকে তবেই প্রতি ৩ সেকেন্ড পর পর স্লাইড হবে
    if (slides.length > 1) {
        setInterval(autoSlideBanner, 6000);
    }
</script>

</body>
</html>