<?php
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'sr') {
    header("Location: ../index.php");
    exit();
}

// স্টক রিপোর্ট ডেটাবেস থেকে আনা
$report_query = $conn->query("SELECT * FROM products ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>স্টক রিপোর্ট - Lovia</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7f6; margin: 0; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        .header { text-align: center; border-bottom: 2px solid #34495e; padding-bottom: 15px; margin-bottom: 25px; }
        .header h2 { color: #2c3e50; margin: 0 0 5px 0; }
        .header p { color: #7f8c8d; margin: 0; font-size: 14px; }
        
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
        <h2>📈 পণ্যের স্টক রিপোর্ট (Stock Report)</h2>
        <p>সামগ্রিক পণ্যের মজুত ও বিবরণী</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>ক্রম</th>
                <th>পণ্যের নাম</th>
                <th class="text-right">ট্রেড প্রাইস (TP)</th>
                <th class="text-right">মজুত পরিমাণ</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if ($report_query && $report_query->num_rows > 0) {
                $i = 1;
                while($row = $report_query->fetch_assoc()):
            ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td class="text-right">৳<?php echo number_format($row['tp'], 2); ?></td>
                    <td class="text-right"><?php echo isset($row['stock']) ? $row['stock'] : 'স্টকে আছে'; ?> পিস</td>
                </tr>
            <?php 
                endwhile;
            } else {
                echo '<tr><td colspan="4" style="text-align: center;">কোনো রিপোর্ট ডেটা পাওয়া যায়নি</td></tr>';
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