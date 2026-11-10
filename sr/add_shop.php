<?php
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'sr') {
    header("Location: ../index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $shop_name = trim($_POST['shop_name']);
    $owner_name = trim($_POST['owner_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $sr_id = $_SESSION['user_id'];
    $shop_image = '';

    // ছবি আপলোড হ্যান্ডেল করা
    if (isset($_FILES['shop_image']) && $_FILES['shop_image']['error'] == 0) {
        $target_dir = "../uploads/shops/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_extension = pathinfo($_FILES['shop_image']['name'], PATHINFO_EXTENSION);
        $file_name = "shop_" . time() . "_" . rand(1000, 9999) . "." . $file_extension;
        $target_file = $target_dir . $file_name;

        $allowed_types = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array(strtolower($file_extension), $allowed_types)) {
            if (move_uploaded_file($_FILES['shop_image']['tmp_name'], $target_file)) {
                $shop_image = $file_name;
            }
        }
    }

    if (!empty($shop_name) && !empty($owner_name) && !empty($phone)) {
        $stmt = $conn->prepare("INSERT INTO shops (shop_name, owner_name, phone, address, sr_id, shop_image) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssis", $shop_name, $owner_name, $phone, $address, $sr_id, $shop_image);

        if ($stmt->execute()) {
            $success = "নতুন দোকান সফলভাবে যুক্ত করা হয়েছে!";
        } else {
            $error = "দোকান যুক্ত করতে সমস্যা হয়েছে, আবার চেষ্টা করুন।";
        }
    } else {
        $error = "দোকানের নাম, মালিকের নাম এবং মোবাইল নম্বর বাধ্যতামূলক!";
    }
}
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Shop - Lovia SR Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .sr-layout { display: flex; min-height: 100vh; width: 100%; background: #f4f7f6; }
        .sidebar { width: 260px; background: #34495e; color: white; padding: 20px; display: flex; flex-direction: column; }
        .sidebar h2 { font-size: 20px; margin-bottom: 30px; text-align: center; color: #ecf0f1; border-bottom: 1px solid #4e6d8c; padding-bottom: 15px; }
        .sidebar a { color: #bdc3c7; text-decoration: none; padding: 12px 15px; border-radius: 6px; margin-bottom: 8px; display: block; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: #27ae60; color: white; }
        .main-content { flex: 1; padding: 30px; overflow-y: auto; }
        .form-container { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); max-width: 600px; margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 15px; }
        .form-group textarea { resize: vertical; height: 80px; }
        .btn-submit { background: #27ae60; color: white; border: none; padding: 12px 20px; border-radius: 5px; cursor: pointer; font-size: 16px; width: 100%; }
        .btn-submit:hover { background: #219150; }
        .back-link { display: inline-block; margin-bottom: 15px; color: #3498db; text-decoration: none; font-weight: 600; }
        
        /* ক্যামেরা ও ছবি আপলোড বক্সের স্টাইল */
        .camera-box { border: 2px dashed #3498db; padding: 15px; text-align: center; border-radius: 6px; background: #f8f9fa; cursor: pointer; position: relative; }
        .camera-box input[type="file"] { position: absolute; width: 100%; height: 100%; top: 0; left: 0; opacity: 0; cursor: pointer; }
        .preview-img { max-width: 120px; max-height: 120px; margin-top: 10px; border-radius: 5px; display: none; border: 1px solid #ddd; }
    </style>
</head>
<body>

<div class="sr-layout">
    <div class="sidebar">
        <h2>SR Panel</h2>
        <a href="dashboard.php">ড্যাশবোর্ড</a>
        <a href="shops.php" class="active">দোকান ম্যানেজমেন্ট</a>
        <a href="../logout.php" style="margin-top: auto; background: #e74c3c; color: white; text-align: center;">লগআউট</a>
    </div>

    <div class="main-content">
        <div style="max-width: 600px; margin: 0 auto;">
            <a href="shops.php" class="back-link">&larr; দোকানের তালিকায় ফিরে যান</a>
            <div class="form-container">
                <h2 style="margin-bottom: 20px; color: #2c3e50;">নতুন দোকান যোগ করুন</h2>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap;">
    
    <!-- একদম ডান কোণায় ছোট ব্যাক ও হোম বাটন -->
    <div style="display: flex; gap: 8px;">
        <a href="javascript:history.back()" style="text-decoration: none; color: #e91e63; font-weight: bold; font-size: 12px; background: white; padding: 5px 10px; border-radius: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: inline-flex; align-items: center; gap: 4px;">
            ⬅️ পিছে যান
        </a>
        <a href="dashboard.php" style="text-decoration: none; color: #333; font-weight: bold; font-size: 12px; background: white; padding: 5px 10px; border-radius: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: inline-flex; align-items: center; gap: 4px;">
            🏠 হোম
        </a>
    </div>
</div>

                <?php if(!empty($error)): ?>
                    <div style="background: #e74c3c; color: white; padding: 10px; border-radius: 5px; margin-bottom: 15px;"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if(!empty($success)): ?>
                    <div style="background: #27ae60; color: white; padding: 10px; border-radius: 5px; margin-bottom: 15px;"><?php echo $success; ?></div>
                <?php endif; ?>

                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>দোকানের নাম</label>
                        <input type="text" name="shop_name" required placeholder="যেমন: ভাই ভাই স্টোর">
                    </div>
                    <div class="form-group">
                        <label>দোকান মালিকের নাম</label>
                        <input type="text" name="owner_name" required placeholder="মালিকের নাম লিখুন">
                    </div>
                    <div class="form-group">
                        <label>মোবাইল নম্বর</label>
                        <input type="text" name="phone" required placeholder="০১৭xxxxxxxx">
                    </div>
                    <div class="form-group">
                        <label>ঠিকানা / এলাকা</label>
                        <textarea name="address" placeholder="দোকানের ঠিকানা বা বাজারের নাম লিখুন"></textarea>
                    </div>

                    <!-- ক্যামেরা ও গ্যালারি থেকে ছবি তোলার অপশন -->
                    <div class="form-group">
                        <label>📸 দোকানের ছবি (ক্যামেরা দিয়ে তুলুন অথবা গ্যালারি থেকে নিন)</label>
                        <div class="camera-box">
                            <span style="color: #2980b9; font-weight: bold; font-size: 14px;">📷 ছবি তুলতে বা সিলেক্ট করতে এখানে ক্লিক করুন</span>
                            <!-- accept="image/*" এবং capture="environment" থাকার কারণে মোবাইলে সরাসরি ক্যামেরা বা গ্যালারি অপশন আসবে -->
                            <input type="file" name="shop_image" id="shopImageInput" accept="image/*" capture="environment" onchange="previewImage(event)">
                        </div>
                        <div style="text-align: center;">
                            <img id="imagePreview" class="preview-img" src="#" alt="Shop Preview">
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">দোকান সংরক্ষণ করুন</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// ছবি সিলেক্ট বা তোলার পর প্রিভিউ দেখানোর জন্য জাভাস্ক্রিপ্ট
function previewImage(event) {
    var reader = new FileReader();
    reader.onload = function(){
        var output = document.getElementById('imagePreview');
        output.src = reader.result;
        output.style.display = 'block';
    };
    reader.readAsDataURL(event.target.files[0]);
}
</script>

</body>
</html>