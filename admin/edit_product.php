<?php
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error = '';

// বর্তমান প্রোডাক্টের তথ্য ফেচ করা
$prod_res = $conn->query("SELECT * FROM products WHERE id = $id");
if (!$prod_res || $prod_res->num_rows == 0) {
    header("Location: products.php");
    exit();
}
$product = $prod_res->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $brand = trim($_POST['brand'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $bn_name = trim($_POST['bn_name'] ?? '');
    $tp = floatval($_POST['tp'] ?? 0);
    $mrp = floatval($_POST['mrp'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    
    // কার্টুন ও মাস্টার কার্টুন সাইজ
    $cartoon_size = intval($_POST['cartoon_size'] ?? 12);
    $master_cartoon_size = intval($_POST['master_cartoon_size'] ?? 72);

    // অফার ও ডিসকাউন্ট ফিল্ড
    $offer_type = trim($_POST['offer_type'] ?? '');
    $discount_value = trim($_POST['discount_value'] ?? '');
    $gift_item = trim($_POST['gift_item'] ?? '');

    $image_name = $product['image'];

    // নতুন ছবি আপলোড করলে পুরনো ছবি পরিবর্তন ও ডিলিট করা
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['image']['name'];
        $filetmp = $_FILES['image']['tmp_name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $image_name = 'prod_' . time() . '.' . $ext;
            move_uploaded_file($filetmp, '../uploads/' . $image_name);
            
            // পুরনো ছবি মুছে ফেলা
            if (!empty($product['image']) && file_exists('../uploads/' . $product['image'])) {
                unlink('../uploads/' . $product['image']);
            }
        } else {
            $error = "শুধুমাত্র JPG, JPEG, PNG বা WEBP ফরম্যাটের ছবি আপলোড করা যাবে!";
        }
    }

    if (empty($error)) {
        // ডেটাবেস আপডেট কুয়েরি (সকল ফিলਡসহ)
        $stmt = $conn->prepare("UPDATE products SET brand=?, name=?, bn_name=?, tp=?, mrp=?, stock=?, cartoon_size=?, master_cartoon_size=?, offer_type=?, discount_value=?, gift_item=?, image=? WHERE id=?");
        $stmt->bind_param("sssddiiissssi", $brand, $name, $bn_name, $tp, $mrp, $stock, $cartoon_size, $master_cartoon_size, $offer_type, $discount_value, $gift_item, $image_name, $id);
        
        if ($stmt->execute()) {
            header("Location: products.php?updated=1");
            exit();
        } else {
            $error = "প্রোডাক্ট আপডেট করতে সমস্যা হয়েছে! (Error: " . $stmt->error . ")";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Lovia Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .form-container { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); max-width: 600px; margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        .btn-submit { background: #3498db; color: white; border: none; padding: 12px 20px; border-radius: 5px; cursor: pointer; font-size: 16px; width: 100%; font-weight: bold; }
        .btn-submit:hover { background: #2980b9; }
        .back-link { display: inline-block; margin-bottom: 15px; color: #3498db; text-decoration: none; font-weight: 600; }
        img.preview-img { width: 60px; height: 60px; object-fit: cover; border-radius: 5px; border: 1px solid #ddd; margin-top: 5px; }
    </style>
</head>
<body style="display: block; padding: 30px; background: #f4f7f6;">

<div style="max-width: 600px; margin: 0 auto;">
    <a href="products.php" class="back-link">&larr; প্রোডাক্ট লিস্টে ফিরে যান</a>
    <div class="form-container">
        <h2 style="margin-bottom: 20px; color: #2c3e50;">প্রোডাক্ট তথ্য ও অফার এডিট করুন</h2>

        <?php if(!empty($error)): ?>
            <div style="background: #e74c3c; color: white; padding: 10px; border-radius: 5px; margin-bottom: 15px;"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            
            <div class="form-group">
                <label>ব্র্যান্ড:</label>
                <select name="brand">
                    <option value="Sofft" <?php if($product['brand'] == 'Sofft') echo 'selected'; ?>>Sofft</option>
                    <option value="LOVIAA" <?php if($product['brand'] == 'LOVIAA') echo 'selected'; ?>>LOVIAA</option>
                    <option value="Al Henna" <?php if($product['brand'] == 'Al Henna') echo 'selected'; ?>>Al Henna</option>
                    <option value="AirMax" <?php if($product['brand'] == 'AirMax') echo 'selected'; ?>>AirMax</option>
                    <option value="Rex" <?php if($product['brand'] == 'Rex') echo 'selected'; ?>>Rex</option>
                    <option value="All" <?php if($product['brand'] == 'All') echo 'selected'; ?>>All</option>
                </select>
            </div>

            <div class="form-group">
                <label>পণ্যের ইংরেজি নাম</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
            </div>

            <div class="form-group">
                <label>পণ্যের বাংলা নাম</label>
                <input type="text" name="bn_name" value="<?php echo htmlspecialchars($product['bn_name'] ?? ''); ?>" placeholder="যেমন: শেভিং ফোম">
            </div>

            <div class="form-group">
                <label>TP (Trade Price)</label>
                <input type="number" step="0.01" name="tp" value="<?php echo $product['tp']; ?>" required>
            </div>

            <div class="form-group">
                <label>MRP</label>
                <input type="number" step="0.01" name="mrp" value="<?php echo $product['mrp']; ?>" required>
            </div>

            <!-- কার্টুন ও মাস্টার কার্টুন সাইজ এডিট করার ফিল্ড -->
            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label>কার্টুন সাইজ (কয় পিসে ১ কার্টুন?)</label>
                    <input type="number" name="cartoon_size" value="<?php echo $product['cartoon_size'] ?? 12; ?>" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>মাস্টার কার্টুন সাইজ (কয় পিসে ১ মাস্টার?)</label>
                    <input type="number" name="master_cartoon_size" value="<?php echo $product['master_cartoon_size'] ?? 72; ?>" required>
                </div>
            </div>

            <!-- অফার বা ডিসকাউন্ট এডিট করার ফিল্ড -->
            <div class="form-group">
                <label>অফার বা ডিসকাউন্ট:</label>
                <select name="offer_type">
                    <option value="none" <?php if(($product['offer_type'] ?? '') == 'none') echo 'selected'; ?>>কোনো অফার নেই</option>
                    <option value="bogo" <?php if(($product['offer_type'] ?? '') == 'bogo') echo 'selected'; ?>>Buy 1 Get 1 (একটি কিনলে একটি ফ্রি)</option>
                    <option value="discount" <?php if(($product['offer_type'] ?? '') == 'discount') echo 'selected'; ?>>ডিসকাউন্ট বা ছাড়</option>
                    <option value="free" <?php if(($product['offer_type'] ?? '') == 'free') echo 'selected'; ?>>সম্পূর্ণ ফ্রি প্রোডাক্ট</option>
                    <option value="gift" <?php if(($product['offer_type'] ?? '') == 'gift') echo 'selected'; ?>>বিশেষ গিফট যুক্ত</option>
                </select>
            </div>

            <div class="form-group">
                <label>ডিসকাউন্ট মূল্য বা পার্সেন্টেজ:</label>
                <input type="text" name="discount_value" value="<?php echo htmlspecialchars($product['discount_value'] ?? ''); ?>" placeholder="যেমন: ১০% ছাড় বা ৫০ টাকা">
            </div>

            <div class="form-group">
                <label>সাথে কি গিফট আছে?:</label>
                <input type="text" name="gift_item" value="<?php echo htmlspecialchars($product['gift_item'] ?? ''); ?>" placeholder="যেমন: একটি ছোট সোপ ফ্রি">
            </div>

            <div class="form-group">
                <label>বর্তমান স্টক (পরিমাণ)</label>
                <input type="number" name="stock" value="<?php echo $product['stock']; ?>" required>
            </div>

            <div class="form-group">
                <label>পণ্যের ছবি (পরিবর্তন করতে চাইলে নতুন ছবি দিন)</label>
                <?php if(!empty($product['image']) && file_exists('../uploads/'.$product['image'])): ?>
                    <br><img src="../uploads/<?php echo $product['image']; ?>" class="preview-img">
                <?php endif; ?>
                <input type="file" name="image" accept="image/*" style="margin-top: 8px;">
            </div>

            <button type="submit" class="btn-submit">প্রোডাক্ট আপডেট করুন</button>
        </form>
    </div>
</div>

</body>
</html>