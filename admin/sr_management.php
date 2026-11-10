<?php
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// SR অনুমোদন করা (Approve)
if (isset($_GET['approve_id'])) {
    $app_id = intval($_GET['approve_id']);
    $conn->query("UPDATE users SET status = 'approved' WHERE id = $app_id AND role = 'sr'");
    header("Location: sr_management.php?approved=1");
    exit();
}

// SR ডিলিট বা বাতিল করা
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM users WHERE id = $del_id AND role = 'sr'");
    header("Location: sr_management.php?deleted=1");
    exit();
}

$sr_list = $conn->query("SELECT * FROM users WHERE role = 'sr' ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SR Management - Lovia Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-layout { display: flex; min-height: 100vh; width: 100%; background: #f4f7f6; }
        .sidebar { width: 260px; background: #2c3e50; color: white; padding: 20px; display: flex; flex-direction: column; }
        .sidebar h2 { font-size: 20px; margin-bottom: 30px; text-align: center; color: #ecf0f1; border-bottom: 1px solid #34495e; padding-bottom: 15px; }
        .sidebar a { color: #bdc3c7; text-decoration: none; padding: 12px 15px; border-radius: 6px; margin-bottom: 8px; display: block; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: #3498db; color: white; }
        .main-content { flex: 1; padding: 30px; overflow-y: auto; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; background: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #34495e; color: white; }
        .btn-approve { background: #27ae60; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 13px; }
        .btn-approve:hover { background: #219150; }
        .btn-del { background: #e74c3c; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 13px; margin-left: 5px; }
        .btn-del:hover { background: #c0392b; }
        .alert { background: #2ecc71; color: white; padding: 12px; border-radius: 6px; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="admin-layout">
    <div class="sidebar">
        <h2>Lovia Admin</h2>
        <a href="dashboard.php">ড্যাশবোর্ড</a>
        <a href="products.php">প্রোডাক্ট ম্যানেজমেন্ট</a>
        <a href="sr_management.php" class="active">SR ম্যানেজমেন্ট</a>
        <a href="reports.php">বিক্রি ও স্টক রিপোর্ট</a>
        <a href="../logout.php" style="margin-top: auto; background: #e74c3c; color: white; text-align: center;">লগআউট</a>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2>SR তালিকা ও অনুমোদন প্যানেল</h2>
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
            <span>মোট এসআর: <?php echo $sr_list->num_rows; ?> জন</span>
        </div>

        <?php if(isset($_GET['approved'])): ?>
            <div class="alert">SR অ্যাকাউন্ট সফলভাবে অনুমোদন করা হয়েছে!</div>
        <?php endif; ?>
        <?php if(isset($_GET['deleted'])): ?>
            <div class="alert" style="background: #e74c3c;">SR অ্যাকাউন্ট মুছে ফেলা হয়েছে!</div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>নাম</th>
                    <th>ইউজারনেম</th>
                    <th>স্ট্যাটাস</th>
                    <th>রেজিস্ট্রেশনের তারিখ</th>
                    <th>অ্যাকশন</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($sr_list->num_rows > 0): ?>
                    <?php while($row = $sr_list->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td>
                                <?php if($row['status'] == 'approved'): ?>
                                    <span style="color: #27ae60; font-weight: bold;">অনুমোদিত (Approved)</span>
                                <?php else: ?>
                                    <span style="color: #e67e22; font-weight: bold;">পেন্ডিং (Pending)</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $row['created_at']; ?></td>
                            <td>
                                <?php if($row['status'] == 'pending'): ?>
                                    <a href="sr_management.php?approve_id=<?php echo $row['id']; ?>" class="btn-approve">অনুমোদন দিন</a>
                                <?php endif; ?>
                                <a href="sr_management.php?delete_id=<?php echo $row['id']; ?>" class="btn-del" onclick="return confirm('আপনি কি নিশ্চিত এই SR অ্যাকাউন্ট ডিলিট করতে চান?');">ডিলিট</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #7f8c8d; padding: 20px;">কোনো SR রেজিস্টার করেনি।</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>