<?php
session_start(); // Start session to access user data
if (!isset($_SESSION['user'])) {
    header("Location: customerP.php"); // Redirect to login if not logged in
    exit();
}

$user = $_SESSION['user']; // Get user data from session
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Portal</title>
    <link rel="stylesheet" href="portal.css">
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
            <h1>Welcome, <?php echo htmlspecialchars($user['c_fname'] . ' ' . $user['c_lname']); ?></h1>
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
                            <td><?php echo htmlspecialchars($user['c_id']); ?></td>
                        </tr>
                        <tr>
                            <th>First Name:</th>
                            <td><?php echo htmlspecialchars($user['c_fname']); ?></td>
                        </tr>
                        <tr>
                            <th>Last Name:</th>
                            <td><?php echo htmlspecialchars($user['c_lname']); ?></td>
                        </tr>
                    </table>
                </div>

                <!-- Second Table: Area, Contact, Email, Date of Birth -->
                <div class="flex-item">
                    <h3>Contact Details</h3>
                    <table>
                        <tr>
                            <th>Area:</th>
                            <td><?php echo htmlspecialchars($user['c_area']); ?></td>
                        </tr>
                        <tr>
                            <th>Contact:</th>
                            <td><?php echo htmlspecialchars($user['c_contact']); ?></td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td><?php echo htmlspecialchars($user['c_email']); ?></td>
                        </tr>
                        <tr>
                            <th>Date of Birth:</th>
                            <td><?php echo htmlspecialchars($user['c_date']); ?></td>
                        </tr>
                    </table>
                </div>

                <!-- Third Table: ONU Name, Caller ID, Address, Remarks -->
                <div class="flex-item">
                    <h3>Additional Information</h3>
                    <table>
                        <tr>
                            <th>ONU Name:</th>
                            <td><?php echo htmlspecialchars($user['c_onu']); ?></td>
                        </tr>
                        <tr>
                            <th>Caller ID:</th>
                            <td><?php echo htmlspecialchars($user['c_caller']); ?></td>
                        </tr>
                        <tr>
                            <th>Address:</th>
                            <td><?php echo htmlspecialchars($user['c_address']); ?></td>
                        </tr>
                        <tr>
                            <th>Remarks:</th>
                            <td><?php echo htmlspecialchars($user['c_rem']); ?></td>
                        </tr>
                        
                    </table>
                    
                </div>
            </div>
            <hr class="title-line">
        </div>
       
</div>
</body>
</html>
