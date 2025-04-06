<?php
include 'db.php'; // Ensure db.php contains a valid connection to $conn


$firstnameErr = $lastnameErr = "";
$firstname = $lastname = $email = $username = $password = $type = $status = "";
$successMessage = ""; // Store success message

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data and sanitize
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $type = $_POST['type'];
    $status = $_POST['status'];
    $hasError = false;

    // Validation
    if (!preg_match("/^[a-zA-Z\s-]+$/", $firstname)) {
        $firstnameErr = "Firstname should not contain numbers.";
        $hasError = true;
    }

    if (!preg_match("/^[a-zA-Z\s-]+$/", $lastname)) {
        $lastnameErr = "Lastname should not contain numbers.";
        $hasError = true;
    }

    if (!$hasError) {
        $sql = "INSERT INTO tbl_user (u_fname, u_lname, u_email, u_username, u_password, u_type, u_status)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssssss", $firstname, $lastname, $email, $username, $password, $type, $status);

            if ($stmt->execute()) {
                $successMessage = "User added successfully!";
            } else {
                echo "Error: " . $stmt->error;
            }

            $stmt->close();
        } else {
            echo "Error preparing statement: " . $conn->error;
        }
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="add.css">
</head>
<body>

<div class="wrapper">
    
<div class="container">
    <div class="left-section">
        <div class="user-icon">
            <i class="bx bxs-user"></i>
        </div>
        <h2>Welcome!</h2>
        <p>Add a new user to the system.</p>
    </div>

    <div class="right-section">
        <h1>Add User</h1>

        <form action="" method="POST">
            <div class="row">
                <div class="input-box">
                    <i class="bx bxs-user"></i>
                    <input type="text" name="firstname" placeholder="Firstname" required>
                </div>
                <div class="input-box">
                    <i class="bx bxs-user"></i>
                    <input type="text" name="lastname" placeholder="Lastname" required>
                </div>
            </div>

            <div class="row">
                <div class="input-box">
                    <i class="bx bxs-envelope"></i>
                    <input type="email" name="email" placeholder="Email" required>
                </div>
                <div class="input-box">
                    <i class="bx bxs-user"></i>
                    <input type="text" name="username" placeholder="Username" required>
                </div>
            </div>

            <div class="input-box">
                <i class="bx bxs-lock-alt"></i>
                <input type="password" name="password" placeholder="Password" required>
            </div>

            <div class="row">
                <div class="input-box">
                    <i class='bx bxs-user'></i>
                    <select name="type" required>
                        <option value="" disabled selected>Select Type</option>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                        <option value="staff">Staff</option>
                    </select>
                </div>

                <div class="input-box">
                    <i class='bx bxs-check-circle'></i>
                    <select name="status" required>
                        <option value="" disabled selected>Select Status</option>
                        <option value="pending">Pending</option>
                        <option value="active">Active</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn">Add User</button>
        </form>
    </div>
</div>




</div>


<!-- Success Message (Will Show Only If User is Added) -->
<?php if ($successMessage): ?>
    <div class="message-box">
        <p><?php echo $successMessage; ?></p>
        <button onclick="window.location.href='viewU.php'">OK</button>
    </div>
<?php endif; ?>

</body>
</html>
