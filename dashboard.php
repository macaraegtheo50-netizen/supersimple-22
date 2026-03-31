<?php 
include 'config.php'; 

if(!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

// 1. Setup Time Variables
$current_day = (int)date('j');
$days_in_month = (int)date('t'); 
$current_month_label = date('F Y');

// FIX: Use 'first day of next month' to avoid the 31st-day overflow bug
$next_month_dt = new DateTime('first day of next month');
$next_month_label = $next_month_dt->format('F Y');
$next_month_name = $next_month_dt->format('F');

// 2. Get Past Dues (Late for the current month)
$past_dues_query = "SELECT s.name, s.stall_no, s.due_day 
                    FROM stall_owners s 
                    WHERE s.due_day < $current_day 
                    AND s.id NOT IN (
                        SELECT owner_id FROM payments WHERE month_covered = '$current_month_label'
                    )";
$past_dues_res = mysqli_query($conn, $past_dues_query);
$past_count = mysqli_num_rows($past_dues_res);

// 3. Get Upcoming Dues (7-Day window for next month)
$upcoming_list = [];
// Only show upcoming dues for next month if we are in the last 7 days of this month
if ($current_day > ($days_in_month - 7)) {
    $upcoming_query = "SELECT s.name, s.stall_no, s.due_day 
                       FROM stall_owners s 
                       WHERE s.due_day <= 7 
                       AND s.id NOT IN (
                           SELECT owner_id FROM payments WHERE month_covered = '$next_month_label'
                       )";
    $upcoming_res = mysqli_query($conn, $upcoming_query);
    while($row = mysqli_fetch_assoc($upcoming_res)) {
        $upcoming_list[] = $row;
    }
}
$upcoming_count = count($upcoming_list);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Batad Public Market</title>
    <style>
        :root {
            --sidebar-width: 260px;
            --primary-color: #2c3e50;
            --accent-color: #3498db;
            --danger-color: #e74c3c;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --bg-light: #f4f7f6;
        }

        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: var(--bg-light); display: flex; }

        /* Sidebar */
        .sidebar { width: var(--sidebar-width); background: var(--primary-color); height: 100vh; color: white; position: fixed; display: flex; flex-direction: column; }
        .sidebar-header { padding: 20px; text-align: center; background: rgba(0,0,0,0.2); font-size: 1.2rem; font-weight: bold; }
        .sidebar-menu { flex-grow: 1; padding-top: 20px; }
        .sidebar-menu a { display: block; padding: 15px 25px; color: #bdc3c7; text-decoration: none; transition: 0.3s; border-left: 4px solid transparent; }
        .sidebar-menu a:hover { background: rgba(255,255,255,0.1); color: white; border-left: 4px solid var(--accent-color); }

        /* Main Content */
        .main-content { margin-left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); min-height: 100vh; }
        header { background: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .role-badge { background: var(--accent-color); color: white; padding: 2px 10px; border-radius: 20px; font-size: 0.8rem; margin-left: 10px; }
        .logout-btn { background: var(--danger-color); color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 0.9rem; }

        .content-padding { padding: 30px; }

        /* Notice Boxes */
        .notice-container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .notice-box { padding: 20px; border-radius: 10px; color: white; position: relative; overflow: hidden; }
        .past-due { background: var(--danger-color); }
        .upcoming-due { background: var(--warning-color); }
        .notice-box h4 { margin: 0; font-size: 0.9rem; text-transform: uppercase; opacity: 0.9; }
        .notice-box .count { font-size: 2.5rem; font-weight: bold; display: block; margin: 10px 0; }
        .notice-box ul { margin: 0; padding-left: 18px; font-size: 0.85rem; list-style-type: square; }
        .notice-box .empty-msg { font-size: 0.9rem; font-style: italic; opacity: 0.8; }

        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); text-align: center; text-decoration: none; color: #333; transition: 0.2s; }
        .card:hover { transform: translateY(-5px); }
        .card h3 { margin: 0; color: var(--primary-color); font-size: 1.1rem; }
        .card p { color: #777; font-size: 0.85rem; margin-top: 8px; }

        .staff-note { background: #fff3cd; border: 1px solid #ffeeba; padding: 15px; border-radius: 5px; color: #856404; margin-bottom: 20px; font-size: 0.9rem; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">BATAD MARKET</div>
        <div class="sidebar-menu">
            <a href="dashboard.php">🏠 Dashboard</a>
            <a href="stalls.php">📦 Stall Owners</a>
            <?php if($_SESSION['role'] != 'staff'): ?>
                <a href="payment.php">💰 Payments</a>
                <a href="electricity.php">⚡ Electricity</a>
            <?php endif; ?>
            <?php if($_SESSION['role'] == 'admin'): ?>
                <a href="manage_users.php">⚙️ Manage Users</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="main-content">
        <header>
            <div class="user-info">
                Welcome, <strong><?php echo $_SESSION['user']; ?></strong>
                <span class="role-badge"><?php echo ucfirst($_SESSION['role']); ?></span>
            </div>
            <a href="logout.php" class="logout-btn" onclick="return confirm('Logout?')">Logout</a>
        </header>

        <div class="content-padding">
            <h2>Market Overview - <?php echo date('F d, Y'); ?></h2>

            <?php if($_SESSION['role'] == 'staff'): ?>
                <div class="staff-note">
                    <strong>Notice:</strong> Your account is set to <strong>View-Only</strong>.
                </div>
            <?php endif; ?>

            <div class="notice-container">
                <div class="notice-box past-due">
                    <h4>Past Dues (LATE)</h4>
                    <span class="count"><?php echo $past_count; ?></span>
                    <?php if($past_count > 0): ?>
                        <ul>
                            <?php while($row = mysqli_fetch_assoc($past_dues_res)): ?>
                                <li><?php echo $row['name']; ?> (Stall <?php echo $row['stall_no']; ?>) - Due: Day <?php echo $row['due_day']; ?></li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p class="empty-msg">All accounts are current for <?php echo date('F'); ?>.</p>
                    <?php endif; ?>
                </div>

                <div class="notice-box upcoming-due">
                    <h4>Upcoming (First 7 Days of <?php echo $next_month_name; ?>)</h4>
                    <span class="count"><?php echo $upcoming_count; ?></span>
                    <?php if($upcoming_count > 0): ?>
                        <ul>
                            <?php foreach($upcoming_list as $row): ?>
                                <li><?php echo $row['name']; ?> (Stall <?php echo $row['stall_no']; ?>) - Due: <?php echo $next_month_name; ?> <?php echo $row['due_day']; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="empty-msg">No dues expected in the first week of <?php echo $next_month_name; ?>.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="stats-grid">
                <a href="stalls.php" class="card">
                    <h3>Stall Owners</h3>
                    <p>Register and manage vendors.</p>
                </a>

                <?php if($_SESSION['role'] != 'staff'): ?>
                    <a href="payment.php" class="card">
                        <h3>Payments</h3>
                        <p>Record rent collections.</p>
                    </a>
                    <a href="electricity.php" class="card">
                        <h3>Electricity</h3>
                        <p>Fish vendor consumption.</p>
                    </a>
                <?php endif; ?>

                <?php if($_SESSION['role'] == 'admin'): ?>
                    <a href="manage_users.php" class="card" style="border-top: 4px solid var(--danger-color);">
                        <h3>System Users</h3>
                        <p>Control access levels.</p>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>