<?php 
include 'config.php'; 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security Check
if(!isset($_SESSION['user']) || $_SESSION['role'] == 'staff') { 
    header("Location: dashboard.php"); 
    exit(); 
}

// --- CATCH DATA FROM STALLS.PHP ---
$preselected_id = isset($_GET['owner_id']) ? intval($_GET['owner_id']) : "";
$preselected_name = isset($_GET['owner_name']) ? $_GET['owner_name'] : "";

$current_day = (int)date('j'); 
$current_month = (int)date('n');
$current_year = (int)date('Y');

// Handle Form Submission
if(isset($_POST['save_pay'])){
    $owner_id = intval($_POST['owner_id']);
    $or_no = mysqli_real_escape_string($conn, $_POST['or_no']);
    $interest_total = floatval($_POST['interest_amount']);
    $total_collected = floatval($_POST['total_amount']); 
    $selected_start_month = (int)$_POST['start_month']; 
    $selected_start_year = (int)$_POST['start_year']; 
    $user = $_SESSION['user'];

    if($total_collected <= 0) {
        echo "<script>alert('Cannot process payment for 0 amount.'); window.location='payment.php';</script>";
        exit();
    }

    $q = mysqli_query($conn, "SELECT rent FROM stall_owners WHERE id = '$owner_id'");
    $owner_data = mysqli_fetch_assoc($q);
    $monthly_rent = $owner_data['rent'];

    $num_months = ($monthly_rent > 0) ? floor($total_collected / $monthly_rent) : 1;
    if($num_months < 1) $num_months = 1;

    $interest_per_row = ($interest_total > 0) ? ($interest_total / $num_months) : 0;
    $last_id = 0;

    for($i = 0; $i < $num_months; $i++) {
        $target_date = new DateTime("$selected_start_year-$selected_start_month-01");
        $target_date->modify("+$i month");
        $month_label = $target_date->format('F Y'); 

        $sql = "INSERT INTO payments (owner_id, or_no, month_covered, amount_paid, interest_penalty, encoded_by, date_paid) 
                VALUES ('$owner_id', '$or_no', '$month_label', '$monthly_rent', '$interest_per_row', '$user', NOW())";
        mysqli_query($conn, $sql);
        $last_id = mysqli_insert_id($conn);
    }

    if($last_id > 0){
        echo "<script>
                alert('Recorded $num_months month(s) successfully.');
                window.open('print_receipt.php?id=$last_id', '_blank');
                window.location='dashboard.php';
              </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment | LGU Batad</title>
    <style>
        :root { --primary: #2c3e50; --success: #27ae60; --danger: #e74c3c; --info: #3498db; --warning: #f39c12; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; margin: 0; padding: 20px; display: flex; justify-content: center; }
        .pay-card { background: white; padding: 30px; border-radius: 15px; width: 100%; max-width: 500px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; font-size: 0.8rem; color: #555; text-transform: uppercase; }
        input, select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; font-size: 1rem; }
        .debt-box { padding: 15px; margin: 15px 0; display: none; border-radius: 8px; border-left: 5px solid; }
        .debt-danger { background: #fff5f5; border-color: var(--danger); }
        .debt-success { background: #f0fff4; border-color: var(--success); color: #155724; }
        .debt-info { background: #f0f7ff; border-color: var(--info); color: #0c5460; }
        .total-val { font-size: 1.5rem; font-weight: bold; float: right; }
        button { width: 100%; padding: 15px; background: var(--success); color: white; border: none; border-radius: 10px; cursor: pointer; font-size: 1.1rem; font-weight: bold; transition: 0.2s; }
        button:disabled { background: #ccc; cursor: not-allowed; }
        .back-link { text-decoration: none; color: #888; font-size: 0.8rem; display: block; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="pay-card">
    <a href="stalls.php" class="back-link">← Back to Registry</a>
    <h2 style="color: var(--primary); margin-top: 0;">Market Collection</h2>

    <form method="POST" id="paymentForm">
        <div class="form-group">
            <label>SEARCH OWNER NAME</label>
            <input list="owners_list" id="owner_search" oninput="calculateDebt()" placeholder="Type name..." required autocomplete="off">
            <datalist id="owners_list">
                <?php 
                $res = mysqli_query($conn, "SELECT id, name, stall_no, rent, due_day FROM stall_owners");
                while($o = mysqli_fetch_assoc($res)){
                    $oid = $o['id'];
                    $p_check = mysqli_query($conn, "SELECT month_covered FROM payments WHERE owner_id='$oid' ORDER BY id DESC LIMIT 1");
                    $last_m = 0; $last_y = 0;
                    if($p_row = mysqli_fetch_assoc($p_check)){
                        $last_date = strtotime($p_row['month_covered']);
                        $last_m = (int)date('n', $last_date);
                        $last_y = (int)date('Y', $last_date);
                    }
                    echo "<option data-id='{$o['id']}' data-rent='{$o['rent']}' data-due='{$o['due_day']}' data-lastm='$last_m' data-lasty='$last_y' value='".strtoupper($o['name'])." (Stall {$o['stall_no']})'>";
                }
                ?>
            </datalist>
            <input type="hidden" name="owner_id" id="actual_owner_id" value="<?= $preselected_id ?>">
        </div>

        <div class="form-group">
            <label>STARTING MONTH</label>
            <div style="display: flex; gap: 10px;">
                <select name="start_month" id="start_month" onchange="calculateDebt()">
                    <?php for($m=1; $m<=12; $m++) echo "<option value='$m' ".($m==$current_month?'selected':'').">".date('F', mktime(0,0,0,$m,1))."</option>"; ?>
                </select>
                <select name="start_year" id="start_year" onchange="calculateDebt()">
                    <option value="<?= $current_year-1 ?>"><?= $current_year-1 ?></option>
                    <option value="<?= $current_year ?>" selected><?= $current_year ?></option>
                    <option value="<?= $current_year+1 ?>"><?= $current_year+1 ?></option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>OR NUMBER</label>
            <input type="text" name="or_no" required placeholder="Enter OR #">
        </div>

        <div id="debt_box" class="debt-box">
            <div>Status: <span id="lbl_months" style="font-weight:bold;">0</span></div>
            <div id="breakdown">
                <div>Rent Subtotal: <span id="lbl_rent" style="font-weight:bold;">₱0.00</span></div>
                <div>Penalty (25%): <span id="lbl_interest" style="font-weight:bold;">₱0.00</span></div>
            </div>
            <hr>
            <div style="font-weight:bold;">TOTAL DUE: <span id="lbl_total" class="total-val">₱0.00</span></div>
            <div style="clear:both;"></div>
        </div>

        <div class="form-group">
            <label>AMOUNT TO COLLECT</label>
            <input type="number" name="total_amount" id="total_amount" step="0.01" required style="font-size: 1.8rem; color: var(--success); font-weight: bold; border: 2px solid var(--success);">
            <input type="hidden" name="interest_amount" id="interest_amount">
        </div>

        <button type="submit" name="save_pay" id="btnSubmit">CONFIRM PAYMENT</button>
    </form>
</div>

<script>
// --- AUTO-FILL ON PAGE LOAD ---
window.onload = function() {
    const preName = "<?= $preselected_name ?>";
    if(preName !== "") {
        document.getElementById('owner_search').value = preName;
        calculateDebt();
    }
};

function calculateDebt() {
    const input = document.getElementById('owner_search');
    const options = document.getElementById('owners_list').options;
    const debtBox = document.getElementById('debt_box');
    const breakdown = document.getElementById('breakdown');
    const btnSubmit = document.getElementById('btnSubmit');
    let selected = null;

    for (let i = 0; i < options.length; i++) {
        if (options[i].value === input.value) {
            selected = options[i];
            break;
        }
    }

    if (!selected) {
        debtBox.style.display = "none";
        btnSubmit.disabled = true;
        return;
    }

    const rent = parseFloat(selected.getAttribute('data-rent'));
    const dueDay = parseInt(selected.getAttribute('data-due'));
    const lastPaidM = parseInt(selected.getAttribute('data-lastm'));
    const lastPaidY = parseInt(selected.getAttribute('data-lasty'));
    document.getElementById('actual_owner_id').value = selected.getAttribute('data-id');

    const startMonth = parseInt(document.getElementById('start_month').value);
    const startYear = parseInt(document.getElementById('start_year').value);
    
    const now = new Date();
    const currMonth = now.getMonth() + 1;
    const currYear = now.getFullYear();
    const currDay = now.getDate();

    debtBox.style.display = "block";
    breakdown.style.display = "block";
    btnSubmit.disabled = false;
    btnSubmit.innerText = "CONFIRM PAYMENT";

    if (startYear < lastPaidY || (startYear === lastPaidY && startMonth <= lastPaidM)) {
        debtBox.className = "debt-box debt-success";
        document.getElementById('lbl_months').innerText = "ALREADY PAID";
        document.getElementById('lbl_total').innerText = "₱0.00";
        document.getElementById('total_amount').value = "0.00";
        breakdown.style.display = "none";
        btnSubmit.disabled = true;
        btnSubmit.innerText = "MONTH ALREADY CLEARED";
        return;
    }

    if (startYear > currYear || (startYear === currYear && startMonth > currMonth)) {
        debtBox.className = "debt-box debt-info";
        document.getElementById('lbl_months').innerText = "ADVANCE PAYMENT";
        let subtotalRent = rent;
        let subtotalInterest = 0;
        let grandTotal = subtotalRent + subtotalInterest;
        document.getElementById('lbl_rent').innerText = "₱" + subtotalRent.toLocaleString(undefined, {minimumFractionDigits: 2});
        document.getElementById('lbl_interest').innerText = "₱0.00";
        document.getElementById('lbl_total').innerText = "₱" + grandTotal.toLocaleString(undefined, {minimumFractionDigits: 2});
        document.getElementById('total_amount').value = grandTotal.toFixed(2);
        document.getElementById('interest_amount').value = "0.00";
        return;
    }

    debtBox.className = "debt-box debt-danger";
    let diff = (currYear - startYear) * 12 + (currMonth - startMonth);
    if (currDay > dueDay) { diff += 1; }
    if (diff < 1) diff = 1;

    let subtotalRent = rent * diff;
    let subtotalInterest = (rent * 0.25) * diff;
    let grandTotal = subtotalRent + subtotalInterest;

    document.getElementById('lbl_months').innerText = diff + " Month(s) Due";
    document.getElementById('lbl_rent').innerText = "₱" + subtotalRent.toLocaleString(undefined, {minimumFractionDigits: 2});
    document.getElementById('lbl_interest').innerText = "₱" + subtotalInterest.toLocaleString(undefined, {minimumFractionDigits: 2});
    document.getElementById('lbl_total').innerText = "₱" + grandTotal.toLocaleString(undefined, {minimumFractionDigits: 2});
    document.getElementById('total_amount').value = grandTotal.toFixed(2);
    document.getElementById('interest_amount').value = subtotalInterest.toFixed(2);
}
</script>

</body>
</html>