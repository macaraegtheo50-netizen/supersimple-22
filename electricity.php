<?php 
include 'config.php'; 

if(!isset($_SESSION['user'])) { 
    header("Location: index.php"); 
    exit(); 
}

$selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
// Fetch only the owners registered for the currently selected month
$elec_owners = mysqli_query($conn, "SELECT * FROM electricity_payments WHERE month_year='$selected_month' ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Electricity Management | LGU Batad</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #0f172a; --accent: #10b981; --bg: #f8fafc; }
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; margin: 0; padding: 25px; }

        .glass-card { 
            background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px);
            border-radius: 20px; padding: 30px; border: 1px solid rgba(255,255,255,0.4);
            box-shadow: 0 10px 30px rgba(0,0,0,0.05); max-width: 98%; margin: auto;
        }

        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        
        /* Table Controls */
        .table-wrapper { overflow-x: auto; border-radius: 12px; border: 1px solid #e2e8f0; background: white; position: relative; }
        .elec-table { width: 100%; border-collapse: collapse; font-size: 12px; }

        /* STICKY COLUMN: Keeps ID and Name visible during scroll */
        .elec-table th:nth-child(1), .elec-table td:nth-child(1) { position: sticky; left: 0; z-index: 10; background: white; width: 30px; }
        .elec-table th:nth-child(2), .elec-table td:nth-child(2) { 
            position: sticky; left: 30px; z-index: 10; background: white; 
            min-width: 170px; text-align: left !important; padding-left: 15px; border-right: 2px solid #f1f5f9;
        }

        .elec-table th { background: #f8fafc; color: #64748b; padding: 12px 5px; text-transform: uppercase; font-size: 10px; border-bottom: 2px solid #edf2f7; }
        .elec-table td { padding: 10px 5px; text-align: center; border-bottom: 1px solid #f1f5f9; }
        .elec-table tr:hover td { background-color: #f8fafc !important; }

        /* UI Elements */
        .check-custom { width: 18px; height: 18px; cursor: pointer; accent-color: var(--accent); }
        .btn { padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; font-size: 13px; transition: 0.2s; }
        .btn-primary { background: var(--primary); color: white; }
        .input-pill { padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 8px; outline: none; }
        
        .badge { padding: 4px 8px; border-radius: 6px; font-weight: bold; font-size: 11px; }
        .badge-qty { background: #f1f5f9; color: #475569; }
        .badge-cash { background: #dcfce7; color: #15803d; }

        @keyframes saveGlow { 0% { background: #dcfce7; } 100% { background: transparent; } }
        .saving-active { animation: saveGlow 0.8s ease-out; }
    </style>
</head>
<body>

<div class="glass-card">
    <div class="header-flex">
        <div>
            <h2 style="margin:0;"><i class="fa-solid fa-bolt" style="color:#eab308"></i> Electricity Tracker</h2>
            <p style="color:#64748b; margin:5px 0 0 0;">Wet & Fish Section | <b><?= date('F Y', strtotime($selected_month)) ?></b></p>
        </div>
        <div style="display:flex; gap:10px;">
            <form method="GET"><input type="month" name="month" value="<?= $selected_month ?>" onchange="this.form.submit()" class="input-pill"></form>
            <a href="dashboard.php" class="btn" style="background:#e2e8f0; color:#475569;">Home</a>
        </div>
    </div>

    <div style="background:#f8fafc; padding:15px; border-radius:12px; margin-bottom:20px; border:1px solid #e2e8f0; display:flex; align-items:center; gap:15px;">
        <span style="font-weight:800; font-size:11px; color:#94a3b8; text-transform:uppercase;">Register Owner</span>
        <form action="actions.php" method="POST" style="display:flex; flex:1; gap:10px;">
            <input type="hidden" name="month_year" value="<?= $selected_month ?>">
            <input type="text" name="new_owner" placeholder="Full Name" required class="input-pill" style="flex:2">
            <input type="number" name="daily_rate" placeholder="Rate (₱)" required class="input-pill" style="flex:1">
            <button type="submit" name="add_elec_owner" class="btn btn-primary">Register Entire Year</button>
        </form>
    </div>

    <div class="table-wrapper">
        <table class="elec-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Stall Owner</th>
                    <th>Rate</th>
                    <?php for($d=1; $d<=31; $d++) echo "<th>$d</th>"; ?>
                    <th>Days</th>
                    <th>Total</th>
                    <th><i class="fa-solid fa-trash"></i></th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $count = 1; 
                while($row = mysqli_fetch_assoc($elec_owners)): 
                    $total_days = 0;
                    for($d=1; $d<=31; $d++) { if($row["day_$d"] == 1) $total_days++; }
                ?>
                <tr>
                    <td><?= $count++ ?></td>
                    <td><strong style="color:var(--primary)"><?= strtoupper(htmlspecialchars($row['stall_name'])) ?></strong></td>
                    <td class="rate-val" data-rate="<?= $row['daily_rate'] ?>">₱<?= number_format($row['daily_rate']) ?></td>
                    
                    <?php for($d=1; $d<=31; $d++): ?>
                    <td>
                        <input type="checkbox" class="check-custom"
                        data-name="<?= htmlspecialchars($row['stall_name']) ?>" 
                        data-month="<?= $selected_month ?>" data-day="<?= $d ?>"
                        onclick="saveUpdate(this)" onmouseenter="dragCheck(this, event)"
                        <?= ($row["day_$d"] == 1) ? "checked" : "" ?>>
                    </td>
                    <?php endfor; ?>

                    <td><span class="badge badge-qty total-cell"><?= $total_days ?></span></td>
                    <td><span class="badge badge-cash amount-cell">₱<?= number_format($total_days * $row['daily_rate']) ?></span></td>
                    <td>
                        <a href="actions.php?remove_elec_global=<?= urlencode($row['stall_name']) ?>&month=<?= $selected_month ?>" 
                           style="color:#f87171;" onclick="return confirm('Delete owner from ALL months?')"><i class="fa-solid fa-circle-xmark"></i></a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
let isDown = false;
document.addEventListener('mousedown', () => isDown = true);
document.addEventListener('mouseup', () => isDown = false);

function dragCheck(el, event) {
    if (isDown) {
        el.checked = !el.checked;
        saveUpdate(el);
    }
}

function saveUpdate(el) {
    const name = el.getAttribute('data-name');
    const month = el.getAttribute('data-month');
    const day = el.getAttribute('data-day');
    const status = el.checked ? 1 : 0;

    updateRowTotals(el);

    fetch('actions.php?update_electricity=1&name=' + encodeURIComponent(name) + 
          '&month=' + month + '&day=' + day + '&status=' + status)
    .then(response => response.text())
    .then(data => {
        if (data.trim() === "Success") {
            let td = el.closest('td');
            td.classList.add('saving-active');
            setTimeout(() => td.classList.remove('saving-active'), 800);
        }
    });
}

function updateRowTotals(el) {
    const row = el.closest('tr');
    const checkboxes = row.querySelectorAll('.check-custom');
    const rate = parseFloat(row.querySelector('.rate-val').getAttribute('data-rate'));
    let count = 0;
    checkboxes.forEach(cb => { if(cb.checked) count++; });
    row.querySelector('.total-cell').innerText = count;
    row.querySelector('.amount-cell').innerText = '₱' + (count * rate).toLocaleString();
}
</script>
</body>
</html>