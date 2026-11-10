<?php
// সেশন নোটিশ বন্ধ করতে এটি চেক করা হয়েছে
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ডাটাবেস কানেকশন ফাইল যুক্ত করা
if (file_exists('../db.php')) {
    include '../db.php';
} else if (file_exists('db.php')) {
    include 'db.php';
} else if (file_exists('../config/db.php')) {
    include '../config/db.php';
}

if(isset($_POST['upload'])) {
    if(isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] == 0) {
        $imageName = $_FILES['banner_image']['name'];
        $tmpName = $_FILES['banner_image']['tmp_name'];
        
        // ছবি সেভ হওয়ার ফোল্ডার পাথ
        $uploadFolder = "../uploads/"; 
        if (!file_exists($uploadFolder)) {
            mkdir($uploadFolder, 0777, true);
        }
        
        $uniqueImageName = time() . '_' . basename($imageName);
        $targetPath = $uploadFolder . $uniqueImageName;
        
        if(move_uploaded_file($tmpName, $targetPath)) {
            // ডাটাবেসে সঠিকভাবে নাম সেভ করা
            $query = "INSERT INTO banners (image_path) VALUES ('$uniqueImageName')";
            if(mysqli_query($conn, $query)) {
                echo "<script>alert('ব্যানার সফলভাবে আপলোড হয়েছে!'); window.location.href='add_banner.php';</script>";
            } else {
                echo "<script>alert('ডাটাবেস এরর: " . mysqli_error($conn) . "');</script>";
            }
        } else {
            echo "<script>alert('ফোল্ডারে ছবি আপলোড হয়নি!');</script>";
        }
    } else {
        echo "<script>alert('দয়া করে একটি ছবি সিলেক্ট করুন!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>ব্যানার আপলোড - অ্যাডমিন প্যানেল</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; padding: 20px; }
        .form-box { max-width: 450px; background: #fff; padding: 25px; margin: auto; border-radius: 10px; box-shadow: 0 3px 8px rgba(0,0,0,0.1); }
        h3 { color: #2c3e50; margin-top: 0; }
        input[type="file"] { margin: 15px 0; width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        button { background: #e91e63; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 14px; width: 100%; }
        button:hover { background: #d81b60; }
        .back-link { display: block; text-align: center; margin-top: 15px; color: #555; text-decoration: none; font-size: 13px; }
    </style>
</head>
<body>
    <div class="form-box">
        <h3>অ্যাডমিন প্যানেল: ব্যানার আপলোড</h3>
        <p style="font-size: 13px; color: #666;">এখান থেকে ছবি আপলোড করলে তা এসআর (SR) প্যানেলের ব্যানারে শো করবে।</p>
        
        <form action="" method="POST" enctype="multipart/form-data">
            <label><b>ব্যানার বা প্রডাক্টের ছবি সিলেক্ট করুন:</b></label>
            <input type="file" name="banner_image" accept="image/*" required>
            <button type="submit" name="upload">ব্যানার আপলোড করুন</button>
        </form>
        
        <a href="dashboard.php" class="back-link">← অ্যাডমিন ড্যাশবোর্ডে ফিরে যান</a>
    </div>
</body>
</html>