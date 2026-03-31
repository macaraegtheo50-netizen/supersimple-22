<?php 
include 'config.php'; 

// Check if user is logged in and has permission
if(!isset($_SESSION['user']) || $_SESSION['role'] == 'staff') { 
    header("Location: stalls.php"); 
    exit(); 
}

$id = 0;
$row = [];

// 1. FETCH CURRENT DATA
if(isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $res = mysqli_query($conn, "SELECT * FROM stall_owners WHERE id = $id");
    $row = mysqli_fetch_assoc($res);

    if(!$row) {
        echo "<script>alert('Record not found'); window.location='stalls.php';</script>";
        exit();
    }
}

// 2. UPDATE LOGIC
if(isset($_POST['update_stall'])){
    $stall_no = mysqli_real_escape_string($conn, $_POST['stall_no']);
    $name = mysqli_real_escape_string($conn, $_POST['owner_name']);
    $rent = floatval($_POST['rent']);
    $due = intval($_POST['due_day']);
    $start = $_POST['start_date'];

    $sql = "UPDATE stall_owners SET 
            stall_no = '$stall_no', 
            name = '$name', 
            rent = '$rent', 
            due_day = '$due', 
            start_date = '$start' 
            WHERE id = $id";
    
    if(mysqli_query($conn, $sql)){
        echo "<script>alert('Update Successful!'); window.location='stalls.php';</script>";
    } else {
        echo "<script>alert('Error updating record: " . mysqli_error($conn) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Stall Owner | Batad Market</title>
    <style>
        :root { --primary: #2c3e50; --accent: #3498db; --success: #27ae60; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .edit-card { background: white; padding: 40px; border-radius: 15px; width: 500px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 0.75rem; font-weight: bold; color: #555; text-transform: uppercase; margin-bottom: 8px; }
        input { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box; font-size: 1rem; }
        .btn-save { background: var(--success); color: white; border: none; padding: 15px; width: 100%; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 1rem; }
        .btn-cancel { display: block; text-align: center; margin-top: 15px; color: #7f8c8d; text-decoration: none; font-size: 0.9rem; }
    </style>
</head>
<body>

<div class="edit-card">
    <h2 style="margin-top:0; color: var(--primary);">✏️ Edit Stall Owner</h2>
    <p style="color:#666; margin-bottom: 30px;">Updating records for: <strong><?php echo $row['name']; ?></strong></p>
    
    <form method="POST">
        <div class="form-group">
            <label>Stall Number</label>
            <input type="text" name="stall_no" value="<?php echo htmlspecialchars($row['stall_no']); ?>" required>
        </div>

        <div class="form-group">
            <label>Owner Name</label>
            <input type="text" name="owner_name" value="<?php echo htmlspecialchars($row['name']); ?>" required>
        </div>

        <div class="form-group">
            <label>Monthly Rent (₱)</label>
            <input type="number" name="rent" step="0.01" value="<?php echo $row['rent']; ?>" required>
        </div>

        <div class="form-group">
            <label>Due Day (1-31)</label>
            <input type="number" name="due_day" min="1" max="31" value="<?php echo $row['due_day']; ?>" required>
        </div>

        <div class="form-group">
            <label>Start Date</label>
            <input type="date" name="start_date" value="<?php echo $row['start_date']; ?>" required>
        </div>

        <button type="submit" name="update_stall" class="btn-save">SAVE CHANGES</button>
        <a href="stalls.php" class="btn-cancel">Cancel and Go Back</a>
    </form>
</div>

</body>
</html>