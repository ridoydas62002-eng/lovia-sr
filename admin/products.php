<?php
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// প্রোডাক্ট ডিলিট করার কোড
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    
    // ছবি ফাইল ডিলিট করার জন্য আগের নাম তুলে নেওয়া
    $img_res = $conn->query("SELECT image FROM products WHERE id = $del_id");
    if ($img_res->num_rows > 0) {
        $img_row = $img_res->fetch_assoc();
        if (!empty($img_row['image']) && file_exists('../uploads/' . $img_row['image'])) {
            unlink('../uploads/' . $img_row['image']);
        }
    }

    $conn->query("DELETE FROM products WHERE id = $del_id");
    header("Location: products.php?deleted=1");
    exit();
}

$result = $conn->query("SELECT * FROM products ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Lovia Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-layout { display: flex; min-height: 100vh; width: 100%; background: #f4f7f6; }
        .sidebar { width: 260px; background: #2c3e50; color: white; padding: 20px; display: flex; flex-direction: column; }
        .sidebar h2 { font-size: 20px; margin-bottom: 30px; text-align: center; color: #ecf0f1; border-bottom: 1px solid #34495e; padding-bottom: 15px; }
        .sidebar a { color: #bdc3c7; text-decoration: none; padding: 12px 15px; border-radius: 6px; margin-bottom: 8px; display: block; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: #3498db; color: white; }
        .main-content { flex: 1; padding: 30px; overflow-y: auto; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; background: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); flex-wrap: wrap; gap: 10px; }
        .btn-add { background: #27ae60; color: white; padding: 10px 18px; border-radius: 6px; text-decoration: none; font-weight: 600; }
        .btn-add:hover { background: #219150; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; vertical-align: middle; }
        th { background: #34495e; color: white; }
        img.prod-img { width: 45px; height: 45px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd; }
        .btn-edit { background: #3498db; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: bold; margin-right: 5px; display: inline-block; }
        .btn-edit:hover { background: #2980b9; }
        .btn-del { background: #e74c3c; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: bold; display: inline-block; }
        .btn-del:hover { background: #c0392b; }
        .alert { background: #2ecc71; color: white; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; }
    </style>
</head>
<body>

<div class="admin-layout">
    <div class="sidebar">
        <h2>Lovia Admin</h2>
        <a href="dashboard.php">ড্যাশবোর্ড</a>
        <a href="products.php" class="active">প্রোডাক্ট ম্যানেজমেন্ট</a>
        <a href="reports.php">বিক্রি ও স্টক রিপোর্ট</a>
        <a href="../logout.php" style="margin-top: auto; background: #e74c3c; color: white; text-align: center;">লগআউট</a>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2>প্রোডাক্ট তালিকা</h2>
            
            <!-- একদম ডান কোণায় ছোট ব্যাক ও হোম বাটন -->
            <div style="display: flex; gap: 8px;">
                <a href="javascript:history.back()" style="text-decoration: none; color: #e91e63; font-weight: bold; font-size: 12px; background: white; padding: 5px 10px; border-radius: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: inline-flex; align-items: center; gap: 4px;">
                    <i class="fa-solid fa-arrow-left"></i> পিছে যান
                </a>
                <a href="dashboard.php" style="text-decoration: none; color: #333; font-weight: bold; font-size: 12px; background: white; padding: 5px 10px; border-radius: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: inline-flex; align-items: center; gap: 4px;">
                    <i class="fa-solid fa-house"></i> হোম
                </a>
            </div>

            <a href="add_product.php" class="btn-add">+ নতুন প্রোডাক্ট যোগ করুন</a>
        </div>

        <?php if(isset($_GET['success'])): ?>
            <div class="alert">সফলভাবে নতুন প্রোডাক্ট যোগ করা হয়েছে!</div>
        <?php endif; ?>
        <?php if(isset($_GET['updated'])): ?>
            <div class="alert">প্রোডাক্টের তথ্য সফলভাবে আপডেট করা হয়েছে!</div>
        <?php endif; ?>
        <?php if(isset($_GET['deleted'])): ?>
            <div class="alert" style="background: #e74c3c;">প্রোডাক্ট সফলভাবে ডিলিট করা হয়েছে!</div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>ছবি</th>
                    <th>পণ্যের নাম (ইংরেজি ও বাংলা)</th>
                    <th>TP</th>
                    <th>MRP</th>
                    <th>স্টক</th>
                    <th>অ্যাকশন</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php if(!empty($row['image']) && file_exists('../uploads/'.$row['image'])): ?>
                                    <img src="../uploads/<?php echo $row['image']; ?>" class="prod-img" alt="Product">
                                <?php else: ?>
                                    <img src="https://via.placeholder.com/45" class="prod-img" alt="No Image">
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                                <?php if(!empty($row['bn_name'])): ?>
                                    <br><span style="font-size: 12px; color: #27ae60; font-weight: bold;"><?php echo htmlspecialchars($row['bn_name']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>৳<?php echo number_format($row['tp'], 2); ?></td>
                            <td>৳<?php echo number_format($row['mrp'], 2); ?></td>
                            <td>
                                <span style="font-weight: bold; color: <?php echo ($row['stock'] > 0) ? '#27ae60' : '#e74c3c'; ?>">
                                    <?php echo $row['stock']; ?> পিস
                                </span>
                            </td>
                            <td>
                                <a href="edit_product.php?id=<?php echo $row['id']; ?>" class="btn-edit">এডিট</a>
                                <a href="products.php?delete_id=<?php echo $row['id']; ?>" class="btn-del" onclick="return confirm('আপনি কি নিশ্চিত এই প্রোডাক্টটি ডিলিট করতে চান?');">ডিলিট</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: #7f8c8d; padding: 20px;">কোনো প্রোডাক্ট পাওয়া যায়নি। নতুন প্রোডাক্ট যোগ করুন।</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>