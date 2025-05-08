<?php
include 'db.php';

session_start();

// Initialize the success message variable
$successMessage = "";

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Check if the user ID is set in the URL
if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    // Fetch user details from the database
    $sql = "SELECT * FROM tbl_user WHERE u_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    } else {
        echo "User not found.";
        exit();
    }
} else {
    echo "No user ID specified.";
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $type = $_POST['type'];
    $status = $_POST['status'];

    // Debugging: Check the values being submitted
    error_log("Updating user: $user_id, Type: $type, Status: $status");

    // Update user in the database
    $update_sql = "UPDATE tbl_user SET u_fname=?, u_lname=?, u_email=?, u_username=?, u_type=?, u_status=? WHERE u_id=?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssssssi", $firstname, $lastname, $email, $username, $type, $status, $user_id);

    if ($update_stmt->execute()) {
        $successMessage = "User updated successfully!";
    } else {
        $successMessage = "Error updating user: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link rel="stylesheet" href="addU.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <a href="viewU.php" class="back-icon">
                <i class='bx bx-arrow-back'></i>
            </a>
            <h1>Edit User</h1>
            <form method="POST" action="" class="form" id="editUserForm">
                <div class="form-row">
                    <label for="firstname">First Name:</label>
                    <div class="input-box">
                        <input type="text" id="firstname" name="firstname" placeholder="First Name" value="<?php echo htmlspecialchars($user['u_fname']); ?>" required>
                       
                    </div>
                </div>
                <div class="form-row">
                    <label for="lastname">Last Name:</label>
                    <div class="input-box">
                        <input type="text" id="lastname" name="lastname" placeholder="Last Name" value="<?php echo htmlspecialchars($user['u_lname']); ?>" required>
                   
                    </div>
                </div>
                <div class="form-row">
                    <label for="email">Email:</label>
                    <div class="input-box">
                        <input type="email" id="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($user['u_email']); ?>" required>
                     
                    </div>
                </div>
                <div class="form-row">
                    <label for="username">Username:</label>
                    <div class="input-box">
                        <input type="text" id="username" name="username" placeholder="Username" value="<?php echo htmlspecialchars($user['u_username']); ?>" required>
                 
                    </div>
                </div>
                <div class="form-row">
                    <label for="type">User Type:</label>
                    <div class="input-box">
                        <select id="type" name="type" required>
                            <option value="" disabled>Select Type</option>
                            <option value="user" <?php echo ($user['u_type'] == 'technician') ? 'selected' : ''; ?>>Technician</option>
                            <option value="admin" <?php echo ($user['u_type'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                            <option value="staff" <?php echo ($user['u_type'] == 'staff') ? 'selected' : ''; ?>>Staff</option>
                        </select>
                   
                    </div>
                </div>
                <div class="form-row">
                    <label for="status">Account Status:</label>
                    <div class="input-box">
                        <select id="status" name="status" required>
                            <option value="" disabled>Select Status</option>
                            <option value="pending" <?php echo ($user['u_status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="active" <?php echo ($user['u_status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                        </select>
                    
                    </div>
                </div>
                <div class="button-container">
                    <button type="submit" id="submitBtn">Update User</button>
                </div>
            </form>

            <!-- Success Message -->
            <?php if ($successMessage): ?>
                <div class="success-message">
                    <p><?php echo $successMessage; ?></p>
                    <button onclick="window.location.href='viewU.php'">OK</button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>