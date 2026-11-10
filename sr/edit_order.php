<?php
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'sr') {
    header("Location: ../index.php");
    exit();
}

$sr_id = $_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// অর্ডার ও শপ ডেটা ফেচ করা
$order_res = $conn->query("SELECT * FROM orders WHERE id = $order_id AND sr_id = $sr_id");
if (!$order_res || $order_res->num_rows == 0) {
    header("Location: orders.php");
    exit();
}
$order = $order_res->fetch_assoc();

$shops = $conn->query("SELECT * FROM shops WHERE sr_id = $sr_id ORDER BY shop_name ASC");
$products = $conn->query("SELECT * FROM products");

// ফর্ম সাবমিট হলে স্টক রিসেট করে নতুন ডেটা আপডেট করা
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_shop_id = intval($_POST['shop_id']);
    $all_quantities = isset($_POST['quantity']) ? $_POST['quantity'] : [];

    if ($new_shop_id <= 0) {
        $error = "দয়া করে একটি দোকান নির্বাচন করুন!";
    } else {
        $valid_items = [];
        foreach ($all_quantities as $p_id => $qty) {
            $q = intval($qty);
            if ($q > 0) {
                $valid_items[intval($p_id)] = $q;
            }
        }

        if (empty($valid_items)) {
            $error = "কমপক্ষে একটি পণ্যের পরিমাণ ১ বা তার বেশি দিন!";
        } else {
            // ১. পুরনো অর্ডারের স্টক আগে প্রোডাক্ট টেবিলে ফিরিয়ে দেওয়া (Revert stock)
            $old_items = $conn->query("SELECT product_id, quantity FROM order_items WHERE order_id = $order_id");
            while ($oi = $old_items->fetch_assoc()) {
                $p_id = $oi['product_id'];
                $old_q = $oi['quantity'];
                $conn->query("UPDATE products SET stock = stock + $old_q WHERE id = $p_id");
            }

            // ২. পুরনো অর্ডার আইটেমগুলো মুছে ফেলা
            $conn->query("DELETE FROM order_items WHERE order_id = $order_id");

            // ৩. নতুন পরিমাণ অনুযায়ী হিসাব ও স্টক কমানো
            $total_amount = 0;
            $new_items_data = [];

            foreach ($valid_items as $p_id => $qty) {
                $p_res = $conn->query("SELECT tp FROM products WHERE id = $p_id");
                if ($p_res->num_rows > 0) {
                    $p_row = $p_res->fetch_assoc();
                    $tp = $p_row['tp'];
                    $item_total = $tp * $qty;
                    $total_amount += $item_total;

                    $new_items_data[] = [
                        'product_id' => $p_id,
                        'quantity' => $qty,
                        'tp' => $tp,
                        'total_price' => $item_total
                    ];
                }
            }

            // ৪. মূল অর্ডার টেবিল আপডেট করা
            $update_ord = $conn->prepare("UPDATE orders SET shop_id = ?, total_amount = ? WHERE id = ?");
            $update_ord->bind_param("idi", $new_shop_id, $total_amount, $order_id);
            $update_ord->execute();

            // ৫. নতুন অর্ডার আইটেম ইনসার্ট করা এবং স্টক কমানো
            foreach ($new_items_data as $item) {
                $p_id = $item['product_id'];
                $q = $item['quantity'];
                $tp = $item['tp'];
                $t_price = $item['total_price'];

                $ins_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, tp, total_price) VALUES (?, ?, ?, ?, ?)");
                $ins_item->bind_param("iiidd", $order_id, $p_id, $q, $tp, $t_price);
                $ins_item->execute();

                // নতুন স্টক কমানো
                $conn->query("UPDATE products SET stock = stock - $q WHERE id = $p_id");
            }

            header("Location: view_memo.php?order_id=" . $order_id);
            exit();
        }
    }
}

// এই অর্ডারে আগে কি পরিমাণ ছিল তা লোড করার জন্য অ্যারে তৈরি
$existing_items = [];
$ex_res = $conn->query("SELECT product_id, quantity FROM order_items WHERE order_id = $order_id");
while ($ex = $ex_res->fetch_assoc()) {
    $existing_items[$ex['product_id']] = $ex['quantity'];
}
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>মেমো এডিট করুন - Lovia SR</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .sr-layout { display: flex; min-height: 100vh; width: 100%; background: #f4f7f6; }
        .sidebar { width: 260px; background: #34495e; color: white; padding: 20px; display: flex; flex-direction: column; }
        .sidebar h2 { font-size: 20px; margin-bottom: 30px; text-align: center; color: #ecf0f1; border-bottom: 1px solid #4e6d8c; padding-bottom: 15px; }
        .sidebar a { color: #bdc3c7; text-decoration: none; padding: 12px 15px; border-radius: 6px; margin-bottom: 8px; display: block; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: #27ae60; color: white; }
        .main-content { flex: 1; padding: 30px; overflow-y: auto; }
        .form-card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; }
        .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 15px; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; vertical-align: middle; }
        th { background: #34495e; color: white; }
        img.prod-thumb { width: 40px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; }
        .qty-box { display: flex; gap: 5px; align-items: center; flex-wrap: wrap; }
        .qty-box input { width: 55px; padding: 5px; border: 1px solid #ccc; border-radius: 4px; text-align: center; font-weight: bold; }
        .qty-box span { font-size: 10px; color: #555; display: block; text-align: center; }
        .live-total-bar { background: #2c3e50; color: white; padding: 15px 25px; border-radius: 8px; font-size: 20px; font-weight: bold; text-align: right; margin-bottom: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .btn-order { background: #f39c12; color: white; border: none; padding: 12px 25px; border-radius: 6px; font-size: 16px; font-weight: bold; cursor: pointer; float: right; }
        .btn-order:hover { background: #d68910; }
    </style>
</head>
<body>

<div class="sr-layout">
    <div class="sidebar">
        <h2>SR Panel</h2>
        <a href="dashboard.php">ড্যাশবোর্ড</a>
        <a href="shops.php">দোকান ম্যানেজমেন্ট</a>
        <a href="orders.php">অর্ডার ম্যানেজমেন্ট</a>
        <a href="../logout.php" style="margin-top: auto; background: #e74c3c; color: white; text-align: center;">লগআউট</a>
    </div>

    <div class="main-content">
        <h2 style="margin-bottom: 20px; color: #2c3e50;">মেমো এডিট করুন (মেমো আইডি: #<?php echo $order_id; ?>)</h2>
        
        <div style="margin-bottom: 20px;">
            <a href="view_memo.php?order_id=<?php echo $order_id; ?>" style="text-decoration: none; color: #34495e; font-weight: bold; font-size: 13px; background: white; padding: 8px 15px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: inline-flex; align-items: center; gap: 5px;">
                ⬅️ মেমোতে ফিরে যান
            </a>
        </div>

        <?php if(!empty($error)): ?>
            <div style="background: #e74c3c; color: white; padding: 12px; border-radius: 6px; margin-bottom: 20px;"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="" method="POST" id="editOrderForm">
            <div class="form-card" style="max-width: 500px;">
                <div class="form-group">
                    <label>দোকান পরিবর্তন করুন</label>
                    <select name="shop_id" required>
                        <option value="">-- দোকান সিলেক্ট করুন --</option>
                        <?php while($shop = $shops->fetch_assoc()): ?>
                            <option value="<?php echo $shop['id']; ?>" <?php if($shop['id'] == $order['shop_id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($shop['shop_name']); ?> (<?php echo htmlspecialchars($shop['phone']); ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            
            <h3 style="margin-bottom: 15px; color: #2c3e50;">পণ্যের পরিমাণ ও কার্টুন সংশোধন করুন</h3>

            <table>
                <thead>
                    <tr>
                        <th>ছবি</th>
                        <th>পণ্যের নাম</th>
                        <th>TP (দাম)</th>
                        <th>MRP</th>
                        <th>পরিমাণ (পিস / কার্টুন / মাস্টার কার্টুন)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($products->num_rows > 0): 
                        while($prod = $products->fetch_assoc()): 
                            $p_id = $prod['id'];
                            $tp = floatval($prod['tp']);
                            
                            $cartoon_size = isset($prod['cartoon_size']) && intval($prod['cartoon_size']) > 0 ? intval($prod['cartoon_size']) : 12;
                            $master_size = isset($prod['master_cartoon_size']) && intval($prod['master_cartoon_size']) > 0 ? intval($prod['master_cartoon_size']) : 72;
                            
                            $ex_qty = isset($existing_items[$p_id]) ? $existing_items[$p_id] : 0;
                            $m_carton = floor($ex_qty / $master_size);
                            $rem_after_m = $ex_qty % $master_size;
                            $carton = floor($rem_after_m / $cartoon_size);
                            $pcs = $rem_after_m % $cartoon_size;
                    ?>
                        <tr class="product-row" data-tp="<?php echo $tp; ?>">
                            <td>
                                <?php if(!empty($prod['image']) && file_exists('../uploads/'.$prod['image'])): ?>
                                    <img src="../uploads/<?php echo $prod['image']; ?>" class="prod-thumb" alt="Product">
                                <?php else: ?>
                                    <img src="https://via.placeholder.com/40" class="prod-thumb" alt="No Image">
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($prod['name']); ?></strong>
                                <?php if(!empty($prod['bn_name'])): ?>
                                    <br><span style="font-size: 12px; color: #27ae60; font-weight: bold;"><?php echo htmlspecialchars($prod['bn_name']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>৳<?php echo number_format($tp, 2); ?></td>
                            <td>৳<?php echo number_format($prod['mrp'], 2); ?></td>
                            <td>
                                <div class="qty-box">
                                    <div>
                                        <input type="number" value="<?php echo $pcs; ?>" min="0" class="pcs-input" oninput="calculateTotal(<?php echo $p_id; ?>, <?php echo $cartoon_size; ?>, <?php echo $master_size; ?>)">
                                        <span>পিস</span>
                                    </div>
                                    <div>+</div>
                                    <div>
                                        <input type="number" value="<?php echo $carton; ?>" min="0" class="carton-input" oninput="calculateTotal(<?php echo $p_id; ?>, <?php echo $cartoon_size; ?>, <?php echo $master_size; ?>)">
                                        <span>কার্টুন (<?php echo $cartoon_size; ?>p)</span>
                                    </div>
                                    <div>+</div>
                                    <div>
                                        <input type="number" value="<?php echo $m_carton; ?>" min="0" class="master-input" oninput="calculateTotal(<?php echo $p_id; ?>, <?php echo $cartoon_size; ?>, <?php echo $master_size; ?>)">
                                        <span>মাস্টার (<?php echo $master_size; ?>p)</span>
                                    </div>
                                </div>
                                <input type="hidden" name="quantity[<?php echo $p_id; ?>]" id="total_qty_<?php echo $p_id; ?>" value="<?php echo $ex_qty; ?>" class="total-qty-hidden">
                            </td>
                        </tr>
                    <?php endwhile; endif; ?>
                </tbody>
            </table>

            <!-- লাইভ মোট অ্যামাউন্ট বার -->
            <div class="live-total-bar">
                সর্বমোট এডিটেড বিল: ৳<span id="grandTotal">0.00</span>
            </div>

            <button type="submit" class="btn-order">🔄 মেমো আপডেট ও সংশোধন করুন</button>
        </form>
    </div>
</div>

<script>
function calculateTotal(prodId, cartoonSize, masterSize) {
    let row = event.target.closest('tr');
    let pcs = parseInt(row.querySelector('.pcs-input').value) || 0;
    let carton = parseInt(row.querySelector('.carton-input').value) || 0;
    let master = parseInt(row.querySelector('.master-input').value) || 0;

    let totalPcs = pcs + (carton * cartoonSize) + (master * masterSize);
    document.getElementById('total_qty_' + prodId).value = totalPcs;

    updateGrandTotal();
}

function updateGrandTotal() {
    let grandTotal = 0;
    let rows = document.querySelectorAll('.product-row');
    
    rows.forEach(row => {
        let tp = parseFloat(row.getAttribute('data-tp')) || 0;
        let qty = parseInt(row.querySelector('.total-qty-hidden').value) || 0;
        grandTotal += (tp * qty);
    });

    document.getElementById('grandTotal').innerText = grandTotal.toFixed(2);
}

// পেজ লোড হওয়ার সাথে সাথেই বর্তমান মোট অ্যামাউন্ট ক্যালকুলেট করে দেখানোর জন্য
window.onload = function() {
    updateGrandTotal();
};
</script>

</body>
</html>