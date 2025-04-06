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
        echo "User  not found.";
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
        $successMessage = "User  updated successfully!";
    } else {
        $successMessage = "Error updating user: " . $conn->error; // Show error message
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="editU.css">
</head>
<body>

<div class="container">
    <div class="left-section">
        <div class="user-icon">
            <i class="bx bxs-user"></i>
        </div>
        <h2>Welcome!</h2>
        <p>Edit a user in the system.</p>
    </div>

    <div class="right-section">
        <h1>Edit User</h1>

        <form action="" method="POST">
            <div class="row">
                <div class="input-box">
                    <i class="bx bxs-user"></i>
                    <input type="text" name="firstname" placeholder="Firstname" value="<?php echo htmlspecialchars($user['u_fname']); ?>" required>
                </div>
                <div class="input-box">
                    <i class="bx bxs-user"></i>
                    <input type="text" name="lastname" placeholder="Lastname" value="<?php echo htmlspecialchars($user['u_lname']); ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="input-box">
                    <i class="bx bxs-envelope"></i>
                    <input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($user['u_email']); ?>" required>
                </div>
                <div class="input-box">
                    <i class="bx bxs-user"></i>
                    <input type="text" name="username" placeholder="Username" value="<?php echo htmlspecialchars($user['u_username']); ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="input-box">
                    <i class='bx bxs-user'></i>
                    <select name="type" required>
                        <option value="" disabled>Select Type</option>
                        <option value="user" <?php echo ($user['u_type'] == 'user') ? 'selected' : ''; ?>>User </option>
                        <option value="admin" <?php echo ($user['u_type'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                        <option value="staff" <?php echo ($user['u_type'] == 'staff') ? 'selected' : ''; ?>>Staff</option>
                    </select>
                </div>

                <div class="input-box">
                    <i class='bx bxs-check-circle'></i>
                    <select name="status" required>
                        <option value="" disabled>Select Status</option>
                        <option value="pending" <?php echo ($user['u_status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="active" <?php echo ($user['u_status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn">Update</button>
        </form>

        <!-- Success Message -->
        <?php if ($successMessage): ?>
            <div class="message-box">
                <p><?php echo $successMessage; ?></p>
                <button onclick="window.location.href='viewU.php'">OK</button>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>