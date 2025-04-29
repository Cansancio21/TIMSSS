<?php 
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) { 
    header("Location: index.php"); // Redirect to login page if not logged in 
    exit(); 
}

// Check if the customer ID is provided
if (isset($_GET['id'])) {
    $customerId = $_GET['id'];

    // Fetch customer details based on the customer ID
    $sql = "SELECT c_id, c_fname, c_lname, c_address, c_contact, c_email, c_napname, c_napport, c_macaddress, c_status, c_date FROM tbl_customer WHERE c_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $customer = $result->fetch_assoc();
    } else {
        echo "Customer not found.";
        exit();
    }
} else {
    echo "No customer ID provided.";
    exit();
}


// Initialize variables for user data
$username = $_SESSION['username'];
$lastName = '';
$firstName = '';
$userType = '';
$avatarPath = 'default-avatar.png';
$avatarFolder = 'uploads/avatars/';
$userAvatar = $avatarFolder . $username . '.png';

if (file_exists($userAvatar)) {
    $_SESSION['avatarPath'] = $userAvatar . '?' . time(); // Prevent caching issues
} else {
    $_SESSION['avatarPath'] = 'default-avatar.png';
}
$avatarPath = $_SESSION['avatarPath'];

// Fetch user data from the database
if ($conn) {
    $sqlUser = "SELECT u_fname, u_lname, u_type FROM tbl_user WHERE u_username = ?";
    $stmt = $conn->prepare($sqlUser);
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $resultUser = $stmt->get_result();

    if ($resultUser->num_rows > 0) {
        $row = $resultUser->fetch_assoc();
        $firstName = $row['u_fname'];
        $lastName = $row['u_lname'];
        $userType = $row['u_type'];
    }
    $stmt->close();
} else {
    echo "Database connection failed.";
    exit();
}

// Handle form submission for updating the customer
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstName = $_POST['firstname'];
    $lastName = $_POST['lastname'];
    $address = $_POST['address'];
    $contact = $_POST['contact'];
    $email = $_POST['email'];
    $date = $_POST['date'];
    $napname = $_POST['napname'];
    $napport = $_POST['napport'];
    $macAddress = $_POST['macaddress'];
    $status = $_POST['status'];

    // Update the customer in the database
    $sqlUpdate = "UPDATE tbl_customer SET c_fname = ?, c_lname = ?, c_address = ?, c_contact = ?, c_email = ?, c_date = ?, c_napname = ?, c_napport = ?, c_macaddress = ?, c_status = ? WHERE c_id = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param("ssssssssssi", $firstName, $lastName, $address, $contact, $email, $date, $napname, $napport, $macAddress, $status, $customerId);
    
    if ($stmtUpdate->execute()) {
        echo "<script>alert('Customer updated successfully!'); window.location.href='customersT.php';</script>";
    } else {
        echo "<script>alert('Error updating customer.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Customer</title>
    <link rel="stylesheet" href="editC.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
   
</head>
<body>
    <div class="wrapper">
    <div class="sidebar glass-container">
        <h2>Task Management</h2>
        <ul>
            <li><a href="staffD.php"><img src="https://img.icons8.com/plasticine/100/ticket.png" alt="ticket"/><span>View Tickets</span></a></li>
            <li><a href="assetsT.php"><img src="https://img.icons8.com/matisse/100/view.png" alt="view"/><span>View Assets</span></a></li>
            <li><a href="customersT.php"><img src="https://img.icons8.com/color/48/conference-skin-type-7.png" alt="conference-skin-type-7"/> <span>View Customers</span></a></li>
            <li><a href="createTickets.php"><img src="https://img.icons8.com/fluency/48/create-new.png" alt="create-new"/><span>Ticket Registration</span></a></li>
            <li><a href="registerAssets.php"><img src="https://img.icons8.com/fluency/30/insert.png" alt="insert"/><span>Register Assets</span></a></li>
            <li><a href="addC.php"><img src="https://img.icons8.com/officel/40/add-user-male.png" alt="add-user-male"/><span>Add Customer</span></a></li>
        </ul>
        <footer>
        <a href="index.php" class="back-home"><img src="https://img.icons8.com/stickers/35/exit.png" alt="exit"/></i> Logout</a>
        </footer>
    </div>

    <div class="container">
        <div class="upper"> 
            <h1>Edit Customer</h1>
           
            <div class="user-profile">
                <div class="user-icon">
                    <?php 
                    if (!empty($avatarPath) && file_exists(str_replace('?' . time(), '', $avatarPath))) {
                        echo "<img src='" . htmlspecialchars($avatarPath, ENT_QUOTES, 'UTF-8') . "' alt='User Avatar'>";
                    } else {
                        echo "<i class='fas fa-user-circle'></i>";
                    }
                    ?>
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
          
        <div class="alert-container">
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
        </div>

    
    <div class="table-box">
    <h2>Customer Profile</h2>
    <hr class="title-line"> <!-- Add this line -->

    <form action="" method="POST">
           <div class="row">
               <div class="input-box">
                   <i class="bx bxs-user"></i>
                   <label for="firstname">First Name:</label>
                   <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($customer['c_fname']); ?>" required>
               </div>
               <div class="input-box">
                   <i class="bx bxs-user"></i>
                   <label for="lastname">Last Name:</label>
                    <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($customer['c_lname']); ?>" required>
               </div>
               <div class="input-box">
                   <i class="bx bxs-user"></i>
                   <label for="address">Address:</label>
                   <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($customer['c_address']); ?>" required>
               </div>
           </div>

           <div class="row">
               <div class="input-box">
                   <i class="bx bxs-user"></i>
                   <label for="contact">Contact:</label>
                        <input type="text" id="contact" name="contact" value="<?php echo htmlspecialchars($customer['c_contact']); ?>" required>
               </div>
               <div class="input-box">
                   <i class="bx bxs-user"></i>
                   <label for="email">Email:</label>
                   <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($customer['c_email']); ?>" required>
               </div>
               <div class="input-box">
                   <i class="bx bxs-user"></i>
                   <label for="date">Date Applied:</label>
                   <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($customer['c_date']); ?>" required>
               </div>
           </div>

           <h2>Advance Profile</h2>
           <hr class="title-line"> <!-- Add this line -->
           <div class="secondrow">
               <div class="input-box">
                   <i class="bx bxs-user"></i>
                   <label for="napname">Nap Name:</label>
                   <input type="text" id="napname" name="napname" value="<?php echo htmlspecialchars($customer['c_napname']); ?>" required>
               </div>
               <div class="input-box">
                   <i class="bx bxs-user"></i>
                   <label for="napport">Nap Port:</label>
                   <input type="text" id="napport" name="napport" value="<?php echo htmlspecialchars($customer['c_napport']); ?>" required>
               </div>
               <div class="input-box">
                   <i class="bx bxs-user"></i>
                   <label for="macaddress">Mac Address:</label>
        <input type="text" id="macaddress" name="macaddress" value="<?php echo htmlspecialchars($customer['c_macaddress']); ?>" required>
               </div>
               <div class="input-box">
                   <i class="bx bxs-user"></i>
                   <label for="status">Customer Status:</label>
                   <input type="text" id="status" name="status" value="<?php echo htmlspecialchars($customer['c_status']); ?>" required>
               </div>
           </div>
           <div class="button-container">
                    <button type="submit">Update Customer</button>
                </div>
           </form>

           </div>
         
   </div>
   


</div>
</body>
</html>