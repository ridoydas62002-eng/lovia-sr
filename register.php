<?php
// সরাসরি ডেটাবেস কানেকশন (লোকালহোস্ট, ইউজার, পাসওয়ার্ড ও ডেটাবেসের নাম)
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'lovia_db'; // আপনার ডেটাবেসের নাম (যদি অন্য নাম হয় এখানে পরিবর্তন করতে পারেন)

$conn = new mysqli($host, $user, $pass, $dbname);

// কানেকশন চেক করা
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = 'sr';
    $status = 'pending'; // নতুন রেজিস্ট্রেশন পেন্ডিং থাকবে

    if (!empty($name) && !empty($username) && !empty($password)) {
        // পাসওয়ার্ড সিকিউর করা
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // ইউজারনেম আগে থেকে আছে কিনা চেক করা
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = "এই ইউজারনেমটি ইতিমধ্যে ব্যবহার করা হয়েছে! অন্য ইউজারনেম দিন।";
        } else {
            // ডেটাবেসে নতুন এসআর সেভ করা
            $stmt = $conn->prepare("INSERT INTO users (name, username, password, role, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $username, $hashed_password, $role, $status);

            if ($stmt->execute()) {
                $message = "রেজিস্ট্রেশন সফল হয়েছে! অ্যাডমিন অনুমোদন দিলে আপনি লগইন করতে পারবেন।";
            } else {
                $error = "ডেটাবেসে সংরক্ষণ করতে সমস্যা হয়েছে: " . $conn->error;
            }
        }
    } else {
        $error = "দয়া করে সবগুলো ঘর পূরণ করুন!";
    }
}
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>SR Registration - Elite Cosmetica Ltd</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .reg-container { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #2c3e50; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn-submit { width: 100%; padding: 10px; background-color: #27ae60; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; }
        .btn-submit:hover { background-color: #219150; }
        .msg { padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-size: 14px; }
        .success { background-color: #d4edda; color: #155724; }
        .danger { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

<div class="reg-container">
    <div style="text-align: center; margin-bottom: 20px;">
        <img src="assets/icons/logo-512.png" alt="Logo" style="width: 60px; height: 60px; border-radius: 50%;">
        <h2 style="margin: 10px 0 0 0; color: #2c3e50;">SR রেজিস্ট্রেশন</h2>
        <p style="font-size: 13px; color: #7f8c8d; margin-top: 5px;">Elite Cosmetica Ltd</p>
    </div>

    <?php if(!empty($message)): ?>
        <div class="msg success"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php if(!empty($error)): ?>
        <div class="msg danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="form-group">
            <label>আপনার নাম</label>
            <input type="text" name="name" required placeholder="পুরো নাম লিখুন">
        </div>
        <div class="form-group">
            <label>ইউজারনেম (Username)</label>
            <input type="text" name="username" required placeholder="ইউজারনেম লিখুন">
        </div>
        <div style="margin-bottom: 15px;">
    <label style="display: block; margin-bottom: 5px; font-weight: bold;">মোবাইল নম্বর (বাধ্যতামূলক):</label>
    <input type="text" name="phone" required placeholder="যেমন: +880" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
</div>
        <div class="form-group">
            <label>পাসওয়ার্ড</label>
            <input type="password" name="password" required placeholder="পাসওয়ার্ড লিখুন">
        </div>
        <button type="submit" class="btn-submit">রেজিস্ট্রেশন করুন</button>
    </form>

    <div style="text-align: center; margin-top: 15px;">
        <a href="index.php" style="color: #2980b9; text-decoration: none; font-size: 14px;">লগইন পেজে ফিরে যান</a>
    </div>
</div>

</body>
</html>