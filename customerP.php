<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: customerP.php");
    exit();
}

$user = $_SESSION['user'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Portal | Task Management</title>
    <link rel="stylesheet" href="portal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="app-container">
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Task Management</h2>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="staffD.php"><i class="fas fa-ticket-alt"></i> View Tickets</a></li>
                <li><a href="view_service_record.php"><i class="fas fa-box"></i> View Assets</a></li>
                <li><a href="customersT.php"><i class="fas fa-users"></i> View Customers</a></li>
                <li><a href="suppT.php"><i class="fas fa-file-invoice"></i> Support Tickets</a></li>
                <li><a href="addC.php"><i class="fas fa-user-plus"></i> Add Customer</a></li>
            </ul>
        </nav>
        <div class="sidebar-footer">
            <a href="index.php" class="home-link"><i class="fas fa-home"></i> Back to Home</a>
        </div>
    </div>

    <main class="main-content">
        <header class="content-header">
            <h1>Welcome, <?php echo htmlspecialchars($user['c_fname'] . ' ' . $user['c_lname']); ?></h1>
        </header>

        <section class="profile-section">
            <div class="section-header">
                <h2>Your Profile Information</h2>
            </div>
            
            <div class="profile-grid">
                <div class="profile-card">
                    <h3>Basic Information</h3>
                    <div class="info-table">
                        <div class="info-row">
                            <span class="info-label">ID No:</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['c_id']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">First Name:</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['c_fname']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Last Name:</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['c_lname']); ?></span>
                        </div>
                    </div>
                </div>

                <div class="profile-card">
                    <h3>Contact Details</h3>
                    <div class="info-table">
                        <div class="info-row">
                            <span class="info-label">Area:</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['c_area']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Contact:</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['c_contact']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email:</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['c_email']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Date of Birth:</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['c_date']); ?></span>
                        </div>
                    </div>
                </div>

                <div class="profile-card">
                    <h3>Additional Information</h3>
                    <div class="info-table">
                        <div class="info-row">
                            <span class="info-label">ONU Name:</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['c_onu']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Caller ID:</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['c_caller']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Address:</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['c_address']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Remarks:</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['c_rem']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>
</body>
</html>