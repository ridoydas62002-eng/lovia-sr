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
$shops = $conn->query("SELECT * FROM shops WHERE sr_id = $sr_id ORDER BY shop_name ASC");
$selected_brand = isset($_GET['brand']) ? $_GET['brand'] : 'Sofft';

// ব্র্যান্ড ফিল্টার অনুযায়ী প্রোডাক্ট ফেচ করা (All হলে সব দেখাবে)
if ($selected_brand == 'All') {
    $products = $conn->query("SELECT * FROM products");
} else {
    $products = $conn->query("SELECT * FROM products WHERE brand = '$selected_brand'");
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $shop_id = intval($_POST['shop_id']);
    $all_quantities = isset($_POST['quantity']) ? $_POST['quantity'] : []; 

    if ($shop_id <= 0) {
        $error = "দয়া করে একটি দোকান নির্বাচন করুন!";
    } else {
        $valid_items = [];
        foreach ($all_quantities as $prod_id => $qty) {
            $qty = intval($qty);
            if ($qty > 0) {
                $valid_items[intval($prod_id)] = $qty;
            }
        }

        if (empty($valid_items)) {
            $error = "কমপক্ষে একটি পণ্যের পরিমাণ ১ বা তার বেশি দিন!";
        } else {
            $total_amount = 0;
            $order_items_data = [];

            foreach ($valid_items as $prod_id => $qty) {
                $p_res = $conn->query("SELECT tp FROM products WHERE id = $prod_id");
                if ($p_res->num_rows > 0) {
                    $p_row = $p_res->fetch_assoc();
                    $tp = $p_row['tp'];
                    $item_total = $tp * $qty;
                    $total_amount += $item_total;

                    $order_items_data[] = [
                        'product_id' => $prod_id,
                        'quantity' => $qty,
                        'tp' => $tp,
                        'total_price' => $item_total
                    ];
                }
            }

            // মূল অর্ডার টেবিলে একটি কমন মেমো তৈরি হবে
            $stmt = $conn->prepare("INSERT INTO orders (shop_id, sr_id, total_amount) VALUES (?, ?, ?)");
            $stmt->bind_param("iid", $shop_id, $sr_id, $total_amount);
            
            if ($stmt->execute()) {
                $order_id = $stmt->insert_id;

                foreach ($order_items_data as $item) {
                    $p_id = $item['product_id'];
                    $q = $item['quantity'];
                    $tp = $item['tp'];
                    $t_price = $item['total_price'];

                    $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, tp, total_price) VALUES (?, ?, ?, ?, ?)");
                    $item_stmt->bind_param("iiidd", $order_id, $p_id, $q, $tp, $t_price);
                    $item_stmt->execute();

                    // স্টক কমানো
                    $conn->query("UPDATE products SET stock = stock - $q WHERE id = $p_id");
                }

                echo "<script>localStorage.removeItem('saved_shop_id'); window.location.href='view_memo.php?order_id=" . $order_id . "';</script>";
                exit();
            } else {
                $error = "অর্ডার সম্পন্ন করতে সমস্যা হয়েছে!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Order - Lovia SR Panel</title>
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
        .btn-order { background: #27ae60; color: white; border: none; padding: 12px 25px; border-radius: 6px; font-size: 16px; font-weight: bold; cursor: pointer; float: right; }
        .btn-order:hover { background: #219150; }
    </style>
</head>
<body>

<div class="sr-layout">
    <div class="sidebar">
        <h2>SR Panel</h2>
        <a href="dashboard.php">ড্যাশবোর্ড</a>
        <a href="shops.php">দোকান ম্যানেজমেন্ট</a>
        <a href="orders.php" class="active">অর্ডার ম্যানেজমেন্ট</a>
        <a href="../logout.php" style="margin-top: auto; background: #e74c3c; color: white; text-align: center;">লগআউট</a>
    </div>

    <div class="main-content">
        <h2 style="margin-bottom: 20px; color: #2c3e50;">নতুন অর্ডার তৈরি করুন</h2>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap;">
            <div style="display: flex; gap: 8px;">
                <a href="javascript:history.back()" style="text-decoration: none; color: #e91e63; font-weight: bold; font-size: 12px; background: white; padding: 5px 10px; border-radius: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: inline-flex; align-items: center; gap: 4px;">
                    ⬅️ পিছে যান
                </a>
                <a href="dashboard.php" style="text-decoration: none; color: #333; font-weight: bold; font-size: 12px; background: white; padding: 5px 10px; border-radius: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: inline-flex; align-items: center; gap: 4px;">
                    🏠 হোম
                </a>
            </div>
        </div>

        <?php if(!empty($error)): ?>
            <div style="background: #e74c3c; color: white; padding: 12px; border-radius: 6px; margin-bottom: 20px;"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="" method="POST" id="orderForm" onsubmit="prepareSubmit()">
            <div class="form-card" style="max-width: 500px;">
                <div class="form-group">
                    <label>দোকান নির্বাচন করুন</label>
                    <select name="shop_id" id="shopSelect" required onchange="saveShop(this.value)">
                        <option value="">-- দোকান সিলেক্ট করুন --</option>
                        <?php while($shop = $shops->fetch_assoc()): ?>
                            <option value="<?php echo $shop['id']; ?>"><?php echo htmlspecialchars($shop['shop_name']); ?> (<?php echo htmlspecialchars($shop['phone']); ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <!-- ব্র্যান্ড ফিল্টার বাটন -->
            <div style="display: flex; gap: 8px; overflow-x: auto; padding: 10px 5px; margin-bottom: 15px;">
                <?php 
                $brands = ['Sofft', 'LOVIAA', 'Al Henna', 'AirMax', 'Rex', 'All'];
                foreach($brands as $b) {
                    $active_bg = ($selected_brand == $b) ? '#27ae60' : '#e91e63';
                    echo '<a href="create_order.php?brand=' . $b . '" style="padding: 8px 15px; background: ' . $active_bg . '; color: white; border-radius: 20px; text-decoration: none; font-size: 13px; font-weight: bold;">' . $b . '</a>';
                }
                ?>
            </div>
            
            <h3 style="margin-bottom: 15px; color: #2c3e50;">পণ্যের তালিকা ও পরিমাণ (<?php echo $selected_brand; ?>)</h3>
            
            <div id="hiddenInputsContainer"></div>

            <table>
                <thead>
                    <tr>
                        <th>ছবি</th>
                        <th>পণ্যের নাম (ইংরেজি ও বাংলা)</th>
                        <th>TP (দাম)</th>
                        <th>MRP</th>
                        <th>স্টক</th>
                        <th>পরিমাণ (পিস / কার্টুন / মাস্টার কার্টুন)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($products->num_rows > 0): ?>
                        <?php while($prod = $products->fetch_assoc()): 
                            $cartoon_size = isset($prod['cartoon_size']) && intval($prod['cartoon_size']) > 0 ? intval($prod['cartoon_size']) : 12;
                            $master_size = isset($prod['master_cartoon_size']) && intval($prod['master_cartoon_size']) > 0 ? intval($prod['master_cartoon_size']) : 72;
                        ?>
                            <tr>
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
                                <td>৳<?php echo number_format($prod['tp'], 2); ?></td>
                                <td>৳<?php echo number_format($prod['mrp'], 2); ?></td>
                                <td>
                                    <span style="color: <?php echo ($prod['stock'] > 0) ? '#27ae60' : '#e74c3c'; ?>; font-weight: bold;">
                                        <?php echo $prod['stock']; ?> পিস
                                    </span>
                                </td>
                                <td>
                                    <div class="qty-box">
                                        <div>
                                            <input type="number" value="0" min="0" class="pcs-input" data-id="<?php echo $prod['id']; ?>" oninput="calculateTotal(<?php echo $prod['id']; ?>, <?php echo $cartoon_size; ?>, <?php echo $master_size; ?>)">
                                            <span>পিস</span>
                                        </div>
                                        <div>+</div>
                                        <div>
                                            <input type="number" value="0" min="0" class="carton-input" data-id="<?php echo $prod['id']; ?>" oninput="calculateTotal(<?php echo $prod['id']; ?>, <?php echo $cartoon_size; ?>, <?php echo $master_size; ?>)">
                                            <span>কার্টুন (<?php echo $cartoon_size; ?>p)</span>
                                        </div>
                                        <div>+</div>
                                        <div>
                                            <input type="number" value="0" min="0" class="master-input" data-id="<?php echo $prod['id']; ?>" oninput="calculateTotal(<?php echo $prod['id']; ?>, <?php echo $cartoon_size; ?>, <?php echo $master_size; ?>)">
                                            <span>মাস্টার (<?php echo $master_size; ?>p)</span>
                                        </div>
                                    </div>
                                    <!-- মূল কোয়ান্টিটি ইনপুট ফিল্ড (যা সাবমিট হবে) -->
                                    <input type="hidden" name="quantity[<?php echo $prod['id']; ?>]" id="total_qty_<?php echo $prod['id']; ?>" value="0">
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #7f8c8d; padding: 20px;">কোনো প্রোডাক্ট পাওয়া যায়নি।</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <button type="submit" class="btn-order">অর্ডার কনফার্ম ও মেমো তৈরি করুন</button>
        </form>
    </div>
</div>

<script>
function saveShop(val) {
    if(val) {
        localStorage.setItem('saved_shop_id', val);
    }
}

window.onload = function() {
    let savedShop = localStorage.getItem('saved_shop_id');
    if(savedShop) {
        document.getElementById('shopSelect').value = savedShop;
    }
    loadStoredQtys();
};

function calculateTotal(prodId, cartoonSize, masterSize) {
    let totalInput = document.getElementById('total_qty_' + prodId);
    let row = totalInput.closest('tr');
    
    let pcs = parseInt(row.querySelector('.pcs-input').value) || 0;
    let carton = parseInt(row.querySelector('.carton-input').value) || 0;
    let master = parseInt(row.querySelector('.master-input').value) || 0;

    let totalPcs = pcs + (carton * cartoonSize) + (master * masterSize);
    totalInput.value = totalPcs;

    let allQtys = JSON.parse(localStorage.getItem('saved_qtys') || '{}');
    if (totalPcs > 0) {
        allQtys[prodId] = {
            pcs: pcs,
            carton: carton,
            master: master,
            total: totalPcs
        };
    } else {
        delete allQtys[prodId];
    }
    localStorage.setItem('saved_qtys', JSON.stringify(allQtys));
}

function loadStoredQtys() {
    let allQtys = JSON.parse(localStorage.getItem('saved_qtys') || '{}');
    for (let prodId in allQtys) {
        let totalInput = document.getElementById('total_qty_' + prodId);
        if (totalInput) {
            let row = totalInput.closest('tr');
            let data = allQtys[prodId];
            row.querySelector('.pcs-input').value = data.pcs || 0;
            row.querySelector('.carton-input').value = data.carton || 0;
            row.querySelector('.master-input').value = data.master || 0;
            totalInput.value = data.total || 0;
        }
    }
}

function prepareSubmit() {
    let allQtys = JSON.parse(localStorage.getItem('saved_qtys') || '{}');
    let container = document.getElementById('hiddenInputsContainer');
    container.innerHTML = '';

    for (let prodId in allQtys) {
        let existingInput = document.getElementById('total_qty_' + prodId);
        if (!existingInput || parseInt(existingInput.value) <= 0) {
            let hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'quantity[' + prodId + ']';
            hiddenInput.value = allQtys[prodId].total;
            container.appendChild(hiddenInput);
        }
    }
    localStorage.removeItem('saved_qtys');
}
</script>

</body>
</html>