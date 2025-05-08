<?php
session_start(); // Start session to access user data
if (!isset($_SESSION['user'])) {
    header("Location: customerP.php"); // Redirect to login if not logged in
    exit();
}

$user = $_SESSION['user']; // Get user data from session
$username = $user['c_id']; // Using customer ID as identifier

// Initialize variables
$firstName = $user['c_fname'];
$userType = 'customer';
$avatarPath = 'default-avatar.png';
$avatarFolder = 'Uploads/avatars/';
$userAvatar = $avatarFolder . $username . '.png';

if (file_exists($userAvatar)) {
    $_SESSION['avatarPath'] = $userAvatar . '?' . time();
} else {
    $_SESSION['avatarPath'] = 'default-avatar.png';
}
$avatarPath = $_SESSION['avatarPath'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Portal</title>
    <link rel="stylesheet" href="portall.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="wrapper">
<div class="sidebar glass-container">
        <h2>Task Management</h2>
        <ul>
            <li><a href="staffD.php"><i class="fas fa-ticket-alt"></i> View Tickets</a></li>
            <li><a href="view_service_record.php"><i class="fas fa-box"></i> View Assets</a></li>
            <li><a href="customersT.php"><i class="fas fa-box"></i> View Customers</a></li>
            <li><a href="suppT.php"><i class="fas fa-file-invoice"></i> Support tickets</a></li>
            <li><a href="addC.php"><i class="fas fa-user-plus"></i> Add Customer</a></li>
        </ul>
        <footer>
            <a href="index.php" class="back-home"><i class="fas fa-home"></i> Back to Home</a>
        </footer>
    </div>

    <div class="container">
        <div class="upper">
            <h1>Welcome, <?php echo htmlspecialchars($user['c_fname'] . ' ' . $user['c_lname'], ENT_QUOTES, 'UTF-8'); ?></h1>

            <div class="user-profile">
                <div class="user-icon">
                    <a href="images.php">
                        <?php 
                        $cleanAvatarPath = preg_replace('/\?\d+$/', '', $avatarPath);
                        if (!empty($avatarPath) && file_exists($cleanAvatarPath)) {
                            echo "<img src='" . htmlspecialchars($avatarPath, ENT_QUOTES, 'UTF-8') . "' alt='User Avatar'>";
                        } else {
                            echo "<i class='fas fa-user-circle'></i>";
                        }
                        ?>
                    </a>
                </div>
                <div class="user-details">
                    <span><?php echo htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8'); ?></span>
                    <small><?php echo htmlspecialchars(ucfirst($userType), ENT_QUOTES, 'UTF-8'); ?></small>
                </div>
                <a href="settings.php" class="settings-link">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </div>
        </div>

        <div class="table-box">
            <h2>Your Profile Information</h2>
            <hr class="title-line">
            <div class="flex-container">
                <!-- First Table: ID Number -->
                <div class="flex-item">
                    <h3>Basic Information</h3>
                    <table>
                        <tr>
                            <th>ID No:</th>
                            <td><?php echo htmlspecialchars($user['c_id'], ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                        <tr>
                            <th>First Name:</th>
                            <td><?php echo htmlspecialchars($user['c_fname'], ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                        <tr>
                            <th>Last Name:</th>
                            <td><?php echo htmlspecialchars($user['c_lname'], ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    </table>
                </div>

                <!-- Second Table: Address, Contact, Email, Date Applied -->
                <div class="flex-item">
                    <h3>Contact Details</h3>
                    <table>
                        <tr>
                            <th>Address:</th>
                            <td><?php echo htmlspecialchars($user['c_address'], ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                        <tr>
                            <th>Contact:</th>
                            <td><?php echo htmlspecialchars($user['c_contact'], ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td><?php echo htmlspecialchars($user['c_email'], ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                        <tr>
                            <th>Date Applied:</th>
                            <td><?php echo htmlspecialchars($user['c_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    </table>
                </div>

                <!-- Third Table: Nap Name, Nap Port, Mac Address, Status -->
                <div class="flex-item">
                    <h3>Additional Information</h3>
                    <table>
                        <tr>
                            <th>Nap Name:</th>
                            <td><?php echo htmlspecialchars($user['c_napname'], ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                        <tr>
                            <th>Nap Port:</th>
                            <td><?php echo htmlspecialchars($user['c_napport'], ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                        <tr>
                            <th>Mac Address:</th>
                            <td><?php echo htmlspecialchars($user['c_macaddress'], ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td><?php echo htmlspecialchars($user['c_status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <hr class="title-line">
        </div>
    </div>
</div>
</body>
</html>