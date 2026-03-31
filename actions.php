<?php
include 'config.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// --- 1. AJAX CHECKBOX UPDATE ---
if (isset($_GET['update_electricity'])) {
    $name = mysqli_real_escape_string($conn, $_GET['name']);
    $month = mysqli_real_escape_string($conn, $_GET['month']);
    $day = intval($_GET['day']);
    $status = intval($_GET['status']);
    $col = "day_" . $day;

    $sql = "UPDATE electricity_payments SET $col = $status 
            WHERE stall_name='$name' AND month_year='$month'";
    
    if (mysqli_query($conn, $sql)) {
        echo "Success";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
    exit(); 
}

// --- 2. REGISTER NEW OWNER (FOR ALL 12 MONTHS OF THE YEAR) ---
if (isset($_POST['add_elec_owner'])) {
    $name = mysqli_real_escape_string($conn, $_POST['new_owner']);
    $rate = floatval($_POST['daily_rate']);
    $selected_date = $_POST['month_year']; // e.g., "2026-03"
    $year = substr($selected_date, 0, 4);  // Extracts "2026"

    // Create entries for January (01) through December (12)
    for ($m = 1; $m <= 12; $m++) {
        $month_string = $year . '-' . str_pad($m, 2, "0", STR_PAD_LEFT);

        // Check if record exists for this specific month to avoid duplicates
        $check = mysqli_query($conn, "SELECT id FROM electricity_payments 
                                     WHERE stall_name='$name' AND month_year='$month_string'");
        
        if (mysqli_num_rows($check) == 0) {
            $sql = "INSERT INTO electricity_payments (stall_name, daily_rate, month_year) 
                    VALUES ('$name', '$rate', '$month_string')";
            mysqli_query($conn, $sql);
        }
    }
    
    header("Location: electricity.php?month=$selected_date");
    exit();
}

// --- 3. REMOVE OWNER (GLOBAL REMOVAL FROM ALL MONTHS) ---
if (isset($_GET['remove_elec_global'])) {
    $name = mysqli_real_escape_string($conn, $_GET['remove_elec_global']);
    $month = $_GET['month'];
    
    // Removing the month_year constraint deletes them from the entire year
    mysqli_query($conn, "DELETE FROM electricity_payments WHERE stall_name='$name'");
    
    header("Location: electricity.php?month=$month");
    exit();
}
?>