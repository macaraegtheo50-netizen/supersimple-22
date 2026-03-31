<?php 
include 'config.php'; 

if(!isset($_SESSION['user'])) { 
    header("Location: index.php"); 
    exit(); 
}

$can_edit = ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'encoder');

// --- DELETE LOGIC ---
if(isset($_GET['delete']) && $_SESSION['role'] == 'admin'){
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM stall_owners WHERE id=$id");
    echo "<script>alert('Record Deleted'); window.location='stalls.php';</script>";
    exit();
}

// --- SAVE / ENCODE LOGIC ---
if(isset($_POST['save_stall']) && $can_edit){
    $stall_id = mysqli_real_escape_string($conn, $_POST['stall_id']);
    $name = mysqli_real_escape_string($conn, $_POST['owner_name']);
    $start = $_POST['start_date'];
    $rent = $_POST['rent'];
    $due = $_POST['due_day'];

    $sql = "INSERT INTO stall_owners (stall_no, name, start_date, rent, due_day) 
            VALUES ('$stall_id', '$name', '$start', '$rent', '$due')";
    
    if(mysqli_query($conn, $sql)){
        echo "<script>alert('Encoded Successfully!'); window.location='stalls.php';</script>";
    }
}

$search = "";
if(isset($_GET['search'])){
    $search = mysqli_real_escape_string($conn, $_GET['search']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stall Registry | Batad Market</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #0f172a;
            --accent: #3b82f6;
            --success: #10b981;
            --history: #7c3aed; /* Purple for history */
            --bg: #f8fafc;
            --glass: rgba(255, 255, 255, 0.7);
        }

        * { box-sizing: border-box; }

        body { 
            font-family: 'Inter', system-ui, -apple-system, sans-serif; 
            background: radial-gradient(circle at top right, #e2e8f0, #f8fafc);
            margin: 0; 
            padding: 2vw; 
            color: var(--primary);
            min-height: 100vh;
        }

        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 20px;
        }

        .title-group h1 { 
            margin: 0; 
            font-size: clamp(1.5rem, 4vw, 2rem); 
            letter-spacing: -0.02em; 
        }

        .glass-section {
            background: var(--glass);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.03);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
        }

        .input-group label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .input-style {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: white;
            font-size: 14px;
            transition: 0.2s;
        }

        .input-style:focus {
            border-color: var(--accent);
            outline: none;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        .table-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .scroll-area {
            max-height: 550px; 
            overflow: auto;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 14px;
        }

        thead th {
            position: sticky;
            top: 0;
            z-index: 50;
            background: #f8fafc;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }

        /* Sticky Columns for ID and Name */
        th:nth-child(1), td:nth-child(1) { position: sticky; left: 0; z-index: 40; background: inherit; width: 100px; }
        th:nth-child(2), td:nth-child(2) { 
            position: sticky; 
            left: 100px; 
            z-index: 40; 
            background: inherit; 
            border-right: 2px solid #f1f5f9;
            min-width: 250px;
        }

        thead th:nth-child(1), thead th:nth-child(2) { z-index: 60; background: #f8fafc; }

        td {
            padding: 15px;
            border-bottom: 1px solid #f1f5f9;
            background: white;
            white-space: nowrap;
        }

        tr:hover td { background: #f8fafc !important; }

        .status-pill {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
        }
        .status-Paid { background: #dcfce7; color: #166534; }
        .status-Pending { background: #fef9c3; color: #854d0e; }
        .status-Late { background: #fee2e2; color: #991b1b; }

        .btn {
            padding: 10px 16px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: 0.2s;
        }

        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }

        .btn-action { color: #64748b; background: #f1f5f9; padding: 8px 12px; border-radius: 8px; }
        .btn-action:hover { background: #e2e8f0; color: var(--primary); transform: translateY(-2px); }

        .scroll-area::-webkit-scrollbar { width: 6px; height: 6px; }
        .scroll-area::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body>

<div class="main-container">
    <header class="page-header">
        <div class="title-group">
            <h1><i class="fa-solid fa-building-columns"></i> Stall Registry</h1>
            <p style="color: #64748b; margin-top: 5px;">Municipal Treasury Office | Batad, Iloilo</p>
        </div>
        
        <div style="display: flex; gap: 12px; align-items: center;">
            <form method="GET" style="display: flex; gap: 8px;">
                <input type="text" name="search" class="input-style" placeholder="Search records..." value="<?= htmlspecialchars($search) ?>" style="width: 250px;">
                <button type="submit" class="btn btn-primary">Filter</button>
            </form>
            <a href="dashboard.php" class="btn" style="background: white; border: 1px solid #e2e8f0; color: #475569;">Dashboard</a>
        </div>
    </header>

    <?php if($can_edit): ?>
    <section class="glass-section">
        <form method="POST">
            <div class="form-grid">
                <div class="input-group">
                    <label>Stall ID</label>
                    <input type="text" name="stall_id" class="input-style" placeholder="e.g. FISH-01" required>
                </div>
                <div class="input-group">
                    <label>Owner Name</label>
                    <input type="text" name="owner_name" class="input-style" placeholder="Last Name, First Name" required>
                </div>
                <div class="input-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" class="input-style" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="input-group">
                    <label>Monthly Rent</label>
                    <input type="number" name="rent" class="input-style" step="0.01" placeholder="0.00" required>
                </div>
                <div class="input-group">
                    <label>Due Day</label>
                    <input type="number" name="due_day" class="input-style" min="1" max="31" placeholder="1-31" required>
                </div>
            </div>
            <button type="submit" name="save_stall" class="btn btn-primary" style="width: 100%; margin-top: 20px; justify-content: center;">
                <i class="fa-solid fa-plus"></i> REGISTER TENANT
            </button>
        </form>
    </section>
    <?php endif; ?>

    <div class="table-card">
        <div class="scroll-area">
            <table>
                <thead>
                    <tr>
                        <th>Stall ID</th>
                        <th>Tenant Name</th>
                        <th>Started</th>
                        <th>Monthly Rent</th>
                        <th>Due Day</th>
                        <th>Status</th>
                        <th style="text-align: right;">Management</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $today = (int)date('j');
                    $sql = "SELECT * FROM stall_owners";
                    if($search != "") $sql .= " WHERE stall_no LIKE '%$search%' OR name LIKE '%$search%'";
                    $sql .= " ORDER BY stall_no ASC";
                    $res = mysqli_query($conn, $sql);
                    
                    while($row = mysqli_fetch_assoc($res)):
                        $oid = $row['id'];
                        $label = strtoupper($row['name']) . " (" . $row['stall_no'] . ")";
                        
                        // Check Payment
                        $m = date('F'); $y = date('Y');
                        $chk = mysqli_query($conn, "SELECT id FROM payments WHERE owner_id='$oid' AND month_covered LIKE '%$m%' AND month_covered LIKE '%$y%'");
                        $paid = mysqli_num_rows($chk) > 0;
                        $status = $paid ? "Paid" : (($today > $row['due_day']) ? "Late" : "Pending");
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($row['stall_no']) ?></strong></td>
                        <td style="font-weight: 600;"><?= strtoupper(htmlspecialchars($row['name'])) ?></td>
                        <td style="color: #64748b;"><?= date("M d, Y", strtotime($row['start_date'])) ?></td>
                        <td style="color: #10b981; font-weight: 700;">₱<?= number_format($row['rent'], 2) ?></td>
                        <td>Day <?= $row['due_day'] ?></td>
                        <td><span class="status-pill status-<?= $status ?>"><?= $status ?></span></td>
                        <td style="text-align: right;">
                            <div style="display:flex; gap:5px; justify-content:flex-end;">
                                <a href="payment.php?owner_id=<?= $row['id'] ?>&owner_name=<?= urlencode($label) ?>" class="btn btn-action" style="color: var(--success);" title="Collect Payment"><i class="fa-solid fa-receipt"></i></a>
                                
                                <a href="payment_history.php?id=<?= $row['id'] ?>" class="btn btn-action" style="color: var(--history);" title="View History"><i class="fa-solid fa-clock-rotate-left"></i></a>
                                
                                <a href="edit_stall.php?id=<?= $row['id'] ?>" class="btn btn-action" title="Edit Owner"><i class="fa-solid fa-pen-to-square"></i></a>
                                
                                <?php if($_SESSION['role'] == 'admin'): ?>
                                    <a href="stalls.php?delete=<?= $row['id'] ?>" class="btn btn-action" style="color: #ef4444;" onclick="return confirm('Delete record?')"><i class="fa-solid fa-trash"></i></a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>