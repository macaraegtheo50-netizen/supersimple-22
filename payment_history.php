<?php 
include 'config.php'; 

if(!isset($_SESSION['user'])) { 
    header("Location: index.php"); 
    exit(); 
}

if(!isset($_GET['id'])) {
    header("Location: stalls.php");
    exit();
}

$owner_id = intval($_GET['id']);

// --- DELETE LOGIC ---
if(isset($_GET['delete_pay']) && $_SESSION['role'] == 'admin'){
    $pay_id = intval($_GET['delete_pay']);
    // Secure deletion
    $stmt = $conn->prepare("DELETE FROM payments WHERE id = ? AND owner_id = ?");
    $stmt->bind_param("ii", $pay_id, $owner_id);
    if($stmt->execute()){
        echo "<script>alert('Payment record deleted successfully.'); window.location='payment_history.php?id=$owner_id';</script>";
    }
    $stmt->close();
}

// Fetch Owner Details
$owner_query = mysqli_query($conn, "SELECT * FROM stall_owners WHERE id = $owner_id");
$owner = mysqli_fetch_assoc($owner_query);

if(!$owner) {
    echo "Owner not found.";
    exit();
}

// Fetch Payment Records (Ordering by ID desc to see newest first)
$payments = mysqli_query($conn, "SELECT * FROM payments WHERE owner_id = $owner_id ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment History | <?php echo htmlspecialchars($owner['name']); ?></title>
    <style>
        :root {
            --primary: #2c3e50;
            --danger: #e74c3c;
            --success: #27ae60;
        }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; margin: 0; padding: 40px; }
        .container { max-width: 1000px; margin: auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 20px; }
        .owner-info h2 { margin: 0; color: var(--primary); letter-spacing: 1px; }
        .owner-info p { margin: 5px 0 0; color: #7f8c8d; font-size: 0.9rem; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #f8f9fa; color: #34495e; text-align: left; padding: 12px; border-bottom: 2px solid #dee2e6; font-size: 0.75rem; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid #eee; font-size: 0.9rem; vertical-align: middle; }
        tr:hover { background: #fcfcfc; }

        .month-pill { background: #e3f2fd; color: #1976d2; padding: 4px 10px; border-radius: 15px; font-size: 0.8rem; font-weight: bold; border: 1px solid #bbdefb; }
        .penalty { color: var(--danger); font-weight: bold; }
        
        .btn { padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.8rem; font-weight: bold; transition: 0.2s; }
        .btn-print { background: #3498db; color: white; border: none; cursor: pointer; }
        .btn-delete { background: #fff5f5; color: var(--danger); border: 1px solid #feb2b2; margin-left: 10px; }
        .btn-delete:hover { background: var(--danger); color: white; }
        .btn-back { color: #7f8c8d; text-decoration: none; font-size: 0.9rem; margin-bottom: 10px; display: inline-block; }
        
        @media print {
            .btn-print, .btn-delete, .btn-back, th:last-child, td:last-child { display: none; }
            body { padding: 0; background: white; }
            .container { box-shadow: none; width: 100%; max-width: 100%; }
        }
    </style>
</head>
<body>

<div class="container">
    <a href="stalls.php" class="btn-back">← Back to Registry</a>
    
    <div class="header">
        <div class="owner-info">
            <h2><?php echo strtoupper(htmlspecialchars($owner['name'])); ?></h2>
            <p>Stall: <strong><?php echo htmlspecialchars($owner['stall_no']); ?></strong> | Rate: ₱<?php echo number_format($owner['rent'], 2); ?>/mo</p>
        </div>
        <button onclick="window.print()" class="btn btn-print">Print Ledger</button>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date Paid</th>
                <th>OR Number</th>
                <th>Months Covered</th>
                <th>Penalty</th>
                <th>Total Paid</th>
                <th>Collector</th>
                <th style="text-align: center;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if(mysqli_num_rows($payments) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($payments)): 
                    // FIX: Handling the 1970 date issue
                    $raw_date = $row['date_paid'];
                    $formatted_date = ($raw_date && $raw_date != '0000-00-00') ? date('M d, Y', strtotime($raw_date)) : "---";
                ?>
                <tr>
                    <td><?php echo $formatted_date; ?></td>
                    <td><strong><?php echo htmlspecialchars($row['or_no']); ?></strong></td>
                    <td>
                        <span class="month-pill">
                            <?php echo htmlspecialchars($row['month_covered']); ?>
                        </span>
                    </td>
                    <td class="penalty">
                        <?php echo ($row['interest_penalty'] > 0) ? "₱".number_format($row['interest_penalty'], 2) : "-"; ?>
                    </td>
                    <td style="font-weight:bold; color: var(--success);">
                        ₱<?php echo number_format($row['amount_paid'], 2); ?>
                    </td>
                    <td style="font-size: 0.8rem; color: #7f8c8d;"><?php echo htmlspecialchars($row['encoded_by']); ?></td>
                    <td style="text-align: center;">
                        <a href="print_receipt.php?id=<?php echo $row['id']; ?>" target="_blank" title="Print Receipt" style="text-decoration:none; font-size:1.2rem;">🖨️</a>
                        
                        <?php if($_SESSION['role'] == 'admin'): ?>
                            <a href="payment_history.php?id=<?php echo $owner_id; ?>&delete_pay=<?php echo $row['id']; ?>" 
                               class="btn btn-delete" 
                               onclick="return confirm('Are you sure you want to delete this payment record? This cannot be undone.')">
                               Delete
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 50px; color: #95a5a6;">
                        No payment history found for this owner.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>