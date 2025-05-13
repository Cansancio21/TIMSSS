<?php
session_start(); // Start session for login management
include 'db.php';

// Include PHPMailer classes
require 'PHPmailer-master/PHPmailer-master/src/Exception.php';
require 'PHPmailer-master/PHPmailer-master/src/PHPMailer.php';
require 'PHPmailer-master/PHPmailer-master/src/SMTP.php';

// Use PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Initialize variables for user data
$username = $_SESSION['username'];
$lastName = '';
$firstName = '';
$userType = '';
$avatarPath = 'default-avatar.png';
$avatarFolder = 'Uploads/avatars/';
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

// Initialize customer form variables
$firstname = $lastname = $address = $contact = $email = $dob = "";
$napname = $napport = $macaddress = $status = "";
$firstnameErr = $lastnameErr = $addressErr = $contactErr = $emailErr = $dobErr = $napnameErr = $napportErr = $macaddressErr = $statusErr = "";
$hasError = false;

// Handle customer registration form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $address = trim($_POST['address']);
    $contact = trim($_POST['contact']);
    $email = trim($_POST['email']);
    $dob = trim($_POST['date']);
    $napname = trim($_POST['napname']);
    $napport = trim($_POST['napport']);
    $macaddress = trim($_POST['macaddress']);
    $status = trim($_POST['status']);

    // Validate inputs (email format validation removed)
    if (!preg_match("/^[a-zA-Z\s-]+$/", $firstname)) {
        $firstnameErr = "First Name should not contain numbers.";
        $hasError = true;
    }
    if (!preg_match("/^[0-9]+$/", $contact)) {
        $contactErr = "Contact must contain numbers only.";
        $hasError = true;
    }
    if (!preg_match("/^[a-zA-Z\s-]+$/", $lastname)) {
        $lastnameErr = "Last Name should not contain numbers.";
        $hasError = true;
    }
    if (!preg_match("/^[a-zA-Z0-9\s,.-]+$/", $address)) { // Allow numbers and common address characters
        $addressErr = "Address contains invalid characters.";
        $hasError = true;
    }
    if (!preg_match("/^[0-9]+$/", $napport)) {
        $napportErr = "Nap Port must contain numbers only.";
        $hasError = true;
    }
    if (!preg_match("/^[a-zA-Z]+$/", $napname)) {
        $napnameErr = "Nap Name must contain letters only.";
        $hasError = true;
    }
    if (!preg_match("/^[a-zA-Z0-9:-]+$/", $macaddress)) { // Allow MAC address format (alphanumeric, colons, hyphens)
        $macaddressErr = "Mac Address should not contain special characters.";
        $hasError = true;
    }
    if (empty($dob)) {
        $dobErr = "Date is required.";
        $hasError = true;
    }
    if (empty($status)) {
        $statusErr = "Status is required.";
        $hasError = true;
    }
    if (empty($email)) {
        $emailErr = "Email is required.";
        $hasError = true;
    }

    // Insert into database if no errors
    if (!$hasError) {
        $sql = "INSERT INTO tbl_customer (c_fname, c_lname, c_address, c_contact, c_email, c_date, c_napname, c_napport, c_macaddress, c_status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("ssssssssss", $firstname, $lastname, $address, $contact, $email, $dob, $napname, $napport, $macaddress, $status);

        if ($stmt->execute()) {
            // Get the inserted customer ID
            $customerId = $conn->insert_id;

            // Send the confirmation email using PHPMailer
            $mail = new PHPMailer(true); // Enable exceptions

            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'jonwilyammayormita@gmail.com'; // Your Gmail address
                $mail->Password = 'mqkcqkytlwurwlks'; // Your Gmail App Password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Recipients
                $mail->setFrom('jonwilyammayormita@gmail.com', 'Your Website');
                $mail->addAddress($email, "$firstname $lastname");

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Welcome to Our Platform!';
                $mail->Body = "
                    <html>
                    <head>
                        <title>Welcome to Our Platform</title>
                    </head>
                    <body>
                        <p>Dear $firstname $lastname,</p>
                        <p>Thank you for registering with us. Your account details are:</p>
                        <p><strong>Customer ID:</strong> $customerId</p>
                        <p><strong>Last Name:</strong> $lastname</p>
                        <p>Please use these credentials to log in to our customer portal by clicking the link below:</p>
                        <p><a href='http://localhost/TIMSSS/customerP.php'>Customer Portal</a></p>
                        <p>Enter your Customer ID and Last Name to access your account.</p>
                        <p>Best regards,<br>Your Platform Team</p>
                    </body>
                    </html>
                ";
                $mail->AltBody = "Dear $firstname $lastname,\n\nThank you for registering with us. Your account details are:\nCustomer ID: $customerId\nLast Name: $lastname\n\nPlease use these credentials to log in to our customer portal at http://localhost/TIMSSS/customerP.php\n\nBest regards,\nYour Platform Team";

                // Send the email
                $mail->send();
                
                // Store success message in session and redirect (PRG pattern)
                $_SESSION['message'] = "Customer has been registered successfully. A confirmation email has been sent.";
                header("Location: customersT.php");
                exit();
            } catch (Exception $e) {
                // Store error message in session and redirect
                $_SESSION['message'] = "Customer registered, but error sending confirmation email: " . $mail->ErrorInfo;
                header("Location: customersT.php");
                exit();
            }
            
            $stmt->close();
        } else {
            die("Execution failed: " . $stmt->error);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Customer</title>
    <link rel="stylesheet" href="addC.css"> 
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
            <li><a href="registerAssets.php"><img src="https://img.icons8.com/fluency/30/insert.png" alt="insert"/><span>Register Assets</span></a></li>
            <li><a href="addC.php" class="active"><img src="https://img.icons8.com/officel/40/add-user-male.png" alt="add-user-male"/><span>Add Customer</span></a></li>
        </ul>
        <footer>
            <a href="index.php" class="back-home"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </footer>
    </div>

    <div class="container">
        <div class="upper"> 
            <h1>Add Customer</h1>
            <div class="user-profile">
                <div class="user-icon">
                    <a href="image.php">
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
            <hr class="title-line">

            <form action="" method="POST" id="customerForm">
                <div class="row">
                    <div class="input-box">
                        <i class="bx bxs-user"></i>
                        <label for="firstname">First Name:</label>
                        <input type="text" id="firstname" name="firstname" placeholder="Enter Firstname" value="<?php echo htmlspecialchars($firstname); ?>">
                        <span class="error"><?php echo $firstnameErr; ?></span>
                    </div>
                    <div class="input-box">
                        <i class="bx bxs-user"></i>
                        <label for="lastname">Last Name:</label>
                        <input type="text" id="lastname" name="lastname" placeholder="Enter Lastname" value="<?php echo htmlspecialchars($lastname); ?>">
                        <span class="error"><?php echo $lastnameErr; ?></span>
                    </div>
                    <div class="input-box">
                        <i class="bx bxs-user"></i>
                        <label for="address">Address:</label>
                        <input type="text" id="address" name="address" placeholder="Enter Address" value="<?php echo htmlspecialchars($address); ?>">
                        <span class="error"><?php echo $addressErr; ?></span>
                    </div>
                </div>

                <div class="row">
                    <div class="input-box">
                        <i class="bx bxs-user"></i>
                        <label for="contact">Contact:</label>
                        <input type="text" id="contact" name="contact" placeholder="Enter Contact" value="<?php echo htmlspecialchars($contact); ?>">
                        <span class="error"><?php echo $contactErr; ?></span>
                    </div>
                    <div class="input-box">
                        <i class="bx bxs-user"></i>
                        <label for="email">Email:</label>
                        <input type="text" id="email" name="email" placeholder="Enter Email" value="<?php echo htmlspecialchars($email); ?>">
                        <span class="error"><?php echo $emailErr; ?></span>
                    </div>
                    <div class="input-box">
                        <i class="bx bxs-user"></i>
                        <label for="date">Date Applied:</label>
                        <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($dob); ?>">
                        <span class="error"><?php echo $dobErr; ?></span>
                    </div>
                </div>

                <h2>Advanced Profile</h2>
                <hr class="title-line">
                <div class="secondrow">
                    <div class="input-box">
                        <i class="bx bxs-user"></i>
                        <label for="napname">Nap Name:</label>
                        <input type="text" id="napname" name="napname" placeholder="Nap Name" value="<?php echo htmlspecialchars($napname); ?>">
                        <span class="error"><?php echo $napnameErr; ?></span>
                    </div>
                    <div class="input-box">
                        <i class="bx bxs-user"></i>
                        <label for="napport">Nap Port:</label>
                        <input type="text" id="napport" name="napport" placeholder="Nap Port" value="<?php echo htmlspecialchars($napport); ?>">
                        <span class="error"><?php echo $napportErr; ?></span>
                    </div>
                    <div class="input-box">
                        <i class="bx bxs-user"></i>
                        <label for="macaddress">Mac Address:</label>
                        <input type="text" id="macaddress" name="macaddress" placeholder="Mac Address" value="<?php echo htmlspecialchars($macaddress); ?>">
                        <span class="error"><?php echo $macaddressErr; ?></span>
                    </div>

                    <div class="input-box">
                        <i class="bx bxs-user"></i>
                        <label for="status">Customer Status:</label>
                        <select id="status" name="status">
                            <option value="" <?php echo ($status === '') ? 'selected' : ''; ?>>Select Status</option>
                            <option value="Active" <?php echo ($status === 'Active') ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo ($status === 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                        <span class="error"><?php echo $statusErr; ?></span>
                    </div>
                </div>

                <div class="button-container">
                    <button type="submit" id="submitBtn">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Prevent multiple form submissions
    document.getElementById('customerForm').addEventListener('submit', function(e) {
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true; // Disable button to prevent multiple clicks
        submitBtn.textContent = 'Submitting...'; // Update button text
    });
</script>
</body>
</html>

<?php
$conn->close();
?>