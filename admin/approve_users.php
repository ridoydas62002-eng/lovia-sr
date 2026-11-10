<?php
session_start();

// সরাসরি ডেটাবেস কানেকশন
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'lovia_db'; // আপনার ডেটাবেসের নাম

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// অ্যাডমিন কিনা চেক করা (প্রয়োজনে এই লাইনটি সাময়িকভাবে কমেন্ট করে রাখতে পারেন যদি সেশন সমস্যা করে)
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     header("Location: ../index.php");
//     exit();
// }

// এপ্রুভ বা ডিলিট করার অ্যাকশন
if (isset($_GET['action']) && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action == 'approve') {
        $stmt = $conn->prepare("UPDATE users SET status = 'approved' WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    } elseif ($action == 'reject') {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }
    header("Location: approve_users.php");
    exit();
}

// পেন্ডিং ইউজারদের তালিকা নিয়ে আসা
$result = $conn->query("SELECT * FROM users WHERE status = 'pending'");
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>SR Approval - Elite Cosmetica Ltd</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; }
        .container { max-width: 900px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h2 { color: #2c3e50; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: center; }
        th { background-color: #2c3e50; color: white; }
        .btn-approve { background-color: #27ae60; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-weight: bold; }
        .btn-reject { background-color: #c0392b; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-weight: bold; }
        .back-btn { display: inline-block; margin-bottom: 15px; color: #2980b9; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <a href="dashboard.php" class="back-btn">&larr; ড্যাশবোর্ডে ফিরে যান</a>
    <h2>নতুন এসআর (SR) অনুমোদন প্যানেল</h2>
    
    <table>
        <tr>
            <th>নাম</th>
            <th>ইউজারনেম</th>
            <th>ভূমিকা</th>
            <th>অবস্থা</th>
            <th>অ্যাকশন</th>
        </tr>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['username']); ?></td>
                <td><?php echo strtoupper($row['role']); ?></td>
                <td style="color: #e67e22; font-weight: bold;"><?php echo ucfirst($row['status']); ?></td>
                <td>
                    <a href="approve_users.php?action=approve&id=<?php echo $row['id']; ?>" class="btn-approve" onclick="return confirm('আপনি কি এই এসআর-কে অনুমোদন দিতে চান?');">এপ্রুভ করুন</a>
                    <a href="approve_users.php?action=reject&id=<?php echo $row['id']; ?>" class="btn-reject" onclick="return confirm('আপনি কি এটি ডিলিট বা বাতিল করতে চান?');">ডিলিট</a>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="5" style="color: #e74c3c; font-weight: bold; padding: 20px;">বর্তমানে কোনো পেন্ডিং এসআর রেজিস্ট্রেশন নেই।</td>
            </tr>
        <?php endif; ?>
    </table>
</div>

</body>
</html>