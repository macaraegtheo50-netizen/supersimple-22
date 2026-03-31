<?php 
include 'config.php';
if(!isset($_GET['id'])) { die("No Receipt ID provided."); }

$id = $_GET['id'];
$query = "SELECT p.*, o.name, o.stall_no, o.rent 
          FROM payments p 
          JOIN stall_owners o ON p.owner_id = o.id 
          WHERE p.payment_id = '$id'"; // Ensure your primary key matches 'payment_id'

$res = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($res);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print OR #<?= $data['or_number'] ?></title>
    <style>
        @media print { .no-print { display: none; } }
        body { font-family: 'Courier New', Courier, monospace; background: #f4f4f4; padding: 20px; }
        .receipt-container { 
            background: white; 
            width: 400px; 
            margin: 0 auto; 
            padding: 30px; 
            border: 1px dashed #333; 
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h2 { margin: 0; font-size: 1.2rem; }
        .header p { margin: 2px 0; font-size: 0.8rem; }
        
        .details { margin-bottom: 20px; line-height: 1.6; font-size: 0.9rem; }
        .details div { display: flex; justify-content: space-between; }
        
        .total-section { border-top: 1px solid #000; padding-top: 10px; margin-top: 10px; font-weight: bold; }
        .footer { margin-top: 30px; text-align: center; font-size: 0.75rem; border-top: 1px dashed #ccc; padding-top: 10px; }
        
        .btn-print { 
            display: block; width: 200px; margin: 20px auto; padding: 10px; 
            background: #2c3e50; color: white; border: none; cursor: pointer; text-align: center;
        }
    </style>
</head>
<body>

<button class="btn-print no-print" onclick="window.print()">🖨️ Click to Print Receipt</button>

<div class="receipt-container">
    <div class="header">
        <h2>MUNICIPALITY OF BATAD</h2>
        <p>Office of the Municipal Treasurer</p>
        <p><strong>OFFICIAL RECEIPT</strong></p>
    </div>

    <div class="details">
        <div><span>OR Number:</span> <strong><?= $data['or_number'] ?></strong></div>
        <div><span>Date:</span> <span><?= date('M d, Y') ?></span></div>
        <hr>
        <div><span>Payor:</span> <strong><?= strtoupper($data['name']) ?></strong></div>
        <div><span>Stall No:</span> <span><?= $data['stall_no'] ?></span></div>
        <div><span>For Month:</span> <span><?= date('F Y', strtotime($data['month_covered'])) ?></span></div>
    </div>

    <div class="details">
        <div><span>Monthly Rent:</span> <span>₱<?= number_format($data['amount_paid'] - $data['interest_penalty'], 2) ?></span></div>
        <div><span>Late Interest (25%):</span> <span>₱<?= number_format($data['interest_penalty'], 2) ?></span></div>
        
        <div class="total-section">
            <span>TOTAL PAID:</span>
            <span>₱<?= number_format($data['amount_paid'], 2) ?></span>
        </div>
    </div>

    <div class="footer">
        <p>Encoded by: <?= $data['encoded_by'] ?></p>
        <p><em>This serves as your official proof of payment.</em></p>
    </div>
</div>

</body>
</html>