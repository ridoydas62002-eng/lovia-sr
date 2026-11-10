<?php
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'sr') {
    header("Location: ../index.php");
    exit();
}

$sr_id = $_SESSION['user_id'];
$search = '';

// সার্চ কোয়েরি হ্যান্ডলিং
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
    $stmt = $conn->prepare("SELECT * FROM shops WHERE sr_id = ? AND (shop_name LIKE ? OR phone LIKE ?) ORDER BY id DESC");
    $search_param = "%" . $search . "%";
    $stmt->bind_param("iss", $sr_id, $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $stmt = $conn->prepare("SELECT * FROM shops WHERE sr_id = ? ORDER BY id DESC");
    $stmt->bind_param("i", $sr_id);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Management - Lovia SR Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .sr-layout { display: flex; min-height: 100vh; width: 100%; background: #f4f7f6; }
        .sidebar { width: 260px; background: #34495e; color: white; padding: 20px; display: flex; flex-direction: column; }
        .sidebar h2 { font-size: 20px; margin-bottom: 30px; text-align: center; color: #ecf0f1; border-bottom: 1px solid #4e6d8c; padding-bottom: 15px; }
        .sidebar a { color: #bdc3c7; text-decoration: none; padding: 12px 15px; border-radius: 6px; margin-bottom: 8px; display: block; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: #27ae60; color: white; }
        .main-content { flex: 1; padding: 30px; overflow-y: auto; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; background: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); flex-wrap: wrap; gap: 15px; }
        .btn-add { background: #27ae60; color: white; padding: 10px 18px; border-radius: 6px; text-decoration: none; font-weight: 600; }
        .btn-add:hover { background: #219150; }
        .search-box { display: flex; gap: 10px; }
        .search-box input { padding: 8px 12px; border: 1px solid #ccc; border-radius: 5px; width: 220px; outline: none; }
        .search-box button { padding: 8px 15px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #34495e; color: white; }
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
        <div class="top-bar">
            <h2>আমার দোকানের তালিকা</h2>
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
            <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                <form action="" method="GET" class="search-box">
                    <input type="text" name="search" placeholder="দোকানের নাম বা মোবাইল..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit">খুঁজুন</button>
                </form>
                <a href="add_shop.php" class="btn-add">+ নতুন দোকান যোগ করুন</a>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>দোকানের নাম</th>
                    <th>মালিকের নাম</th>
                    <th>মোবাইল নম্বর</th>
                    <th>ঠিকানা</th>
                    <th>দোকানের ছবি</th>
                    <th>যোগদানের তারিখ</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['shop_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['owner_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['phone']); ?></td>
                            <td><?php echo htmlspecialchars($row['address']); ?></td>
                            <td><?php echo $row['created_at']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #7f8c8d; padding: 20px;">কোনো দোকান পাওয়া যায়নি। নতুন দোকান যোগ করুন।</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>