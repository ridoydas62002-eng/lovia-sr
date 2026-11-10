<?php
session_start();
// ডেটাবেস কানেকশন বা সেশন চেক কোড
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>আমার প্রোফাইল</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 15px;">

    <!-- টপ নেভিগেশন ও ব্যাক/হোম বাটন -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="color: #2c3e50; margin: 0; font-size: 20px;">আমার প্রোফাইল</h2>
        <div style="display: flex; gap: 8px;">
            <a href="javascript:history.back()" style="text-decoration: none; color: #e91e63; font-weight: bold; font-size: 12px; background: white; padding: 5px 10px; border-radius: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"><i class="fa-solid fa-arrow-left"></i> পিছে যান</a>
            <a href="dashboard.php" style="text-decoration: none; color: #333; font-weight: bold; font-size: 12px; background: white; padding: 5px 10px; border-radius: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"><i class="fa-solid fa-house"></i> হোম</a>
        </div>
    </div>

    <!-- প্রোফাইল কার্ড -->
    <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); max-width: 400px; margin: 0 auto; text-align: center;">
        <div style="font-size: 60px; color: #3498db; margin-bottom: 10px;">
            <i class="fa-solid fa-circle-user"></i>
        </div>
        <h3 style="margin: 5px 0; color: #333;"><?php echo $_SESSION['user_name'] ?? 'SR User'; ?></h3>
        <p style="color: #777; margin-bottom: 15px;">সেলস রিপ্রেজেন্টেটিভ (SR)</p>
        
        <hr style="border: 0; border-top: 1px solid #eee; margin: 15px 0;">
        
        <div style="text-align: left; font-size: 14px; color: #555; line-height: 2;">
            <p><i class="fa-solid fa-phone" style="width: 20px;"></i> মোবাইল: ০১৫৮০৬৫১৯৭৩</p>
            <p><i class="fa-solid fa-id-badge" style="width: 20px;"></i> রোল: SR (Sales)</p>
        </div>
    </div>

</body>
</html>