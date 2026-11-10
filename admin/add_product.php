<?php
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $brand = trim($_POST['brand'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $bn_name = trim($_POST['bn_name'] ?? ''); // নতুন যুক্ত করা বাংলা নাম
    $tp = floatval($_POST['tp'] ?? 0);
    $mrp = floatval($_POST['mrp'] ?? 0);
    $retail_price = floatval($_POST['retail_price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    
    // কার্টুন ও মাস্টার কার্টুন সাইজ
    $cartoon_size = intval($_POST['cartoon_size'] ?? 12);
    $master_cartoon_size = intval($_POST['master_cartoon_size'] ?? 72);

    $offer_type = trim($_POST['offer_type'] ?? '');
    $discount_value = trim($_POST['discount_value'] ?? '');
    $gift_item = trim($_POST['gift_item'] ?? '');

    $image_name = '';

    // ছবি আপলোড হ্যান্ডলিং
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['image']['name'];
        $filetmp = $_FILES['image']['tmp_name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $image_name = 'prod_' . time() . '.' . $ext;
            move_uploaded_file($filetmp, '../uploads/' . $image_name);
        } else {
            $error = "শুধুমাত্র JPG, JPEG, PNG বা WEBP ফরম্যাটের ছবি আপলোড করা যাবে!";
        }
    }

    if (empty($error)) {
        // ডেটাবেস কুয়েরিতে bn_name কলাম যুক্ত করা হয়েছে
        $stmt = $conn->prepare("INSERT INTO products (brand, name, bn_name, image, tp, mrp, retail_price, stock, cartoon_size, master_cartoon_size, offer_type, discount_value, gift_item) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssdddiissss", $brand, $name, $bn_name, $image_name, $tp, $mrp, $retail_price, $stock, $cartoon_size, $master_cartoon_size, $offer_type, $discount_value, $gift_item);
        
        if ($stmt->execute()) {
            header("Location: products.php?success=1");
            exit();
        } else {
            $error = "প্রোডাক্ট সংরক্ষণ করতে সমস্যা হয়েছে! (Database Error: " . $stmt->error . ")";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Lovia Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .form-container { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); max-width: 600px; margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        .btn-submit { background: #27ae60; color: white; border: none; padding: 12px 20px; border-radius: 5px; cursor: pointer; font-size: 16px; width: 100%; }
        .btn-submit:hover { background: #219150; }
        .back-link { display: inline-block; margin-bottom: 15px; color: #3498db; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body style="display: block; padding: 30px;">

<div style="max-width: 600px; margin: 0 auto;">
    <a href="products.php" class="back-link">&larr; প্রোডাক্ট লিস্টে ফিরে যান</a>
    <div class="form-container">
        <h2 style="margin-bottom: 20px; color: #2c3e50;">নতুন প্রোডাক্ট যুক্ত করুন</h2>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap;">
    
    <!-- একদম ডান কোণায় ছোট ব্যাক ও হোম বাটন -->
    <div style="display: flex; gap: 8px;">
        <a href="javascript:history.back()" style="text-decoration: none; color: #e91e63; font-weight: bold; font-size: 12px; background: white; padding: 5px 10px; border-radius: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: inline-flex; align-items: center; gap: 4px;">
            পিছে যান
        </a>
        <a href="dashboard.php" style="text-decoration: none; color: #333; font-weight: bold; font-size: 12px; background: white; padding: 5px 10px; border-radius: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: inline-flex; align-items: center; gap: 4px;">
            হোম
        </a>
    </div>
</div>

        <?php if(!empty($error)): ?>
            <div style="background: #e74c3c; color: white; padding: 10px; border-radius: 5px; margin-bottom: 15px;"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- মূল ফর্ম শুরু -->
        <form action="" method="POST" enctype="multipart/form-data">
            
            <!-- ব্র্যান্ড সিলেক্ট অপশন -->
            <div class="form-group">
                <label>ব্র্যান্ড সিলেক্ট করুন:</label>
                <select name="brand">
                    <option value="Sofft">Sofft</option>
                    <option value="LOVIAA">LOVIAA</option>
                    <option value="Al Henna">Al Henna</option>
                    <option value="AirMax">AirMax</option>
                    <option value="Rex">Rex</option>
                    <option value="All">All</option>
                </select>
            </div>

            <div class="form-group">
                <label>পণ্যের ইংরেজি নাম (Product Name)</label>
                <input type="text" name="name" required placeholder="পণ্যের ইংরেজি নাম লিখুন">
            </div>

            <!-- নতুন যোগ করা বাংলা নামের ঘর -->
            <div class="form-group">
                <label>পণ্যের বাংলা নাম (Bangla Name)</label>
                <input type="text" name="bn_name" placeholder="যেমন: শেভিং ফোম লেমন">
            </div>

            <div class="form-group">
                <label>পণ্যের ছবি</label>
                <input type="file" name="image" accept="image/*">
            </div>

            <div class="form-group">
                <label>TP (Trade Price)</label>
                <input type="number" step="0.01" name="tp" required placeholder="০.০০">
            </div>

            <div class="form-group">
                <label>MRP</label>
                <input type="number" step="0.01" name="mrp" required placeholder="০.০০">
            </div>

            <!-- কার্টুন ও মাস্টার কার্টুন সাইজ ইনপুট ফিল্ড -->
            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label>কার্টুন সাইজ (কয় পিসে ১ কার্টুন?)</label>
                    <input type="number" name="cartoon_size" value="12" required placeholder="যেমন: ১২">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>মাস্টার কার্টুন সাইজ (কয় পিসে ১ মাস্টার?)</label>
                    <input type="number" name="master_cartoon_size" value="72" required placeholder="যেমন: ৭২">
                </div>
            </div>

            <!-- ১. অফার টাইপ -->
            <div class="form-group">
                <label>অফার বা ডিসকাউন্ট:</label>
                <select name="offer_type">
                    <option value="none">কোনো অফার নেই</option>
                    <option value="bogo">Buy 1 Get 1 (একটি কিনলে একটি ফ্রি)</option>
                    <option value="discount">ডিসকাউন্ট বা ছাড়</option>
                    <option value="free">সম্পূর্ণ ফ্রি প্রোডাক্ট</option>
                    <option value="gift">বিশেষ গিফট যুক্ত</option>
                </select>
            </div>

            <!-- ২. ডিসকাউন্টের পরিমাণ -->
            <div class="form-group">
                <label>ডিসকাউন্ট মূল্য বা পার্সেন্টেজ:</label>
                <input type="text" name="discount_value" placeholder="যেমন: ১০% ছাড় বা ৫০ টাকা">
            </div>

            <!-- ৩. ফ্রি গিফট -->
            <div class="form-group">
                <label>সাথে কি গিফট আছে?:</label>
                <input type="text" name="gift_item" placeholder="যেমন: একটি ছোট সোপ ফ্রি">
            </div>

            <div class="form-group">
                <label>বর্তমান স্টক (পরিমাণ)</label>
                <input type="number" name="stock" required placeholder="যেমন: ১০০">
            </div>

            <button type="submit" class="btn-submit">প্রোডাক্ট সংরক্ষণ করুন</button>
        </form>
    </div>
</div>

</body>
</html>