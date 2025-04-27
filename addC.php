<?php
session_start(); // Start session for login management
include 'db.php';

// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader for PHPMailer
require 'vendor/autoload.php'; // Adjust path if vendor is elsewhere

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
$firstname = $lastname = $area = $contact = $email = $dob = "";
$ONU = $caller = $address = $remarks = "";
$firstnameErr = $lastnameErr = $areaErr = $contactErr = $emailErr = $dobErr = $ONUErr = $callerErr = $addressErr = $remarksErr = "";
$hasError = false;

// Handle customer registration form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $area = trim($_POST['area']);
    $contact = trim($_POST['contact']);
    $email = trim($_POST['email']);
    $dob = trim($_POST['date']);
    $ONU = trim($_POST['ONU']);
    $caller = trim($_POST['caller']);
    $address = trim($_POST['address']);
    $remarks = trim($_POST['remarks']);

    // Validate inputs
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
    if (!preg_match("/^[a-zA-Z\s-]+$/", $area)) { // Fixed regex
        $areaErr = "Area should not contain numbers.";
        $hasError = true;
    }
    if (!preg_match("/^[0-9]+$/", $caller)) {
        $callerErr = "Caller ID must contain numbers only.";
        $hasError = true;
    }
    if (!preg_match("/^[a-zA-Z0-9\s-]+$/", $ONU)) {
        $ONUErr = "ONU Name should not contain special characters.";
        $hasError = true;
    }
    if (!preg_match("/^[a-zA-Z0-9\s-]+$/", $address)) {
        $addressErr = "Mac Address should not contain special characters.";
        $hasError = true;
    }
    if (!preg_match("/^[a-zA-Z0-9\s-]+$/", $remarks)) {
        $remarksErr = "Remarks should not contain special characters.";
        $hasError = true;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailErr = "Invalid email format.";
        $hasError = true;
    }
    if (empty($dob)) {
        $dobErr = "Date is required.";
        $hasError = true;
    }

    // Insert into database if no errors
    if (!$hasError) {
        $sql = "INSERT INTO tbl_customer (c_fname, c_lname, c_area, c_contact, c_email, c_date, c_onu, c_caller, c_address, c_rem)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("ssssssssss", $firstname, $lastname, $area, $contact, $email, $dob, $ONU, $caller, $address, $remarks);

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
                $mail->AltBody = "Dear $firstname $lastname,\n\nThank you for registering with us. Your account details are:\nCustomer ID: $customerId\nLast Name: $lastname\n\nPlease use these credentials to log in to our customer portal at http://localhost/customerP.php\n\nBest regards,\nYour Platform Team";

                // Send the email
                $mail->send();
                
                // Set success message and redirect
                echo "<script type='text/javascript'>
                        alert('Customer has been registered successfully. A confirmation email has been sent.');
                        window.location.href = 'customersT.php';
                      </script>";
            } catch (Exception $e) {
                echo "<script type='text/javascript'>
                        alert('Customer registered, but error sending confirmation email: " . addslashes($mail->ErrorInfo) . "');
                        window.location.href = 'customersT.php';
                      </script>";
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
    <link rel="stylesheet" href="addCu.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="wrapper">
    <div class="sidebar glass-container">
        <h2>Task Management</h2>
        <ul>
            <li><a href="staffD.php" class="active"><i class="fas fa-ticket-alt"></i> <span>View Tickets</span></a></li>
            <li><a href="assetsT.php"><i class="fas fa-box"></i> <span>View Assets</span></a></li>
            <li><a href="customersT.php"><i class="fas fa-users"></i> <span>View Customers</span></a></li>
            <li><a href="createTickets.php"><i class="fas fa-file-invoice"></i> <span>Ticket Registration</span></a></li>
            <li><a href="registerAssets.php"><i class="fas fa-plus-circle"></i> <span>Register Assets</span></a></li>
            <li><a href="addC.php"><i class="fas fa-user-plus"></i> <span>Add Customer</span></a></li>
        </ul>
        <footer>
            <a href="index.php" class="back-home"><i class="fas fa-home"></i> Back to Home</a>
        </footer>
    </div>

    <div class="container">
        <div class="upper"> 
            <h1>Add Customer</h1>
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
            <hr class="title-line">

            <form action="" method="POST">
                <div class="row">
                    <div class="input-box">
                        <i class="bx bxs-user"></i>
                        <label for="firstname">First Name:</label>
                        <input type="text" name="firstname" placeholder="Enter Firstname" value="<?php echo htmlspecialchars($firstname); ?>">
                        <span class="error"><?php echo $firstnameErr; ?></span>
                    </div>
                    <div class="input-box">
                        <i class="bx bxs-user"></i>
                        <label for="lastname">Last Name:</label>
                        <input type="text" name="lastname" placeholder="Enter Lastname" value="<?php echo htmlspecialchars($lastname); ?>">
                        <span class="error"><?php echo $lastnameErr; ?></span>
                    </div>
                    <div class="input-box">
                        <i class="bx bxs-user"></i>
                        <label for="area">Area:</label>
                        <input type="text" name="area" placeholder="Enter Area" value="<?php echo htmlspecialchars($area); ?>">
                        <span class="error"><?php echo $areaErr; ?></span>
                    </div>
                </div>

                <div class="row">
                    <div class="input-box">
                        <i class="bx bxs-user"></i>
                        <label for="contact">Contact:</label>
                        <input type="text" name="contact" placeholder="Enter Contact" value="<?php echo htmlspecialchars($contact); ?>">
                        <span class="error"><?php echo $contactErr; ?></span>
                    </div>
                    <div class="input-box">
                        <i class="bx bxs-user"></i>
                        <label for="email">Email:</label>
                        <input type="email" name="email" placeholder="Enter Email" value="<?php echo htmlspecialchars($email); ?>">
                        <span class="error"><?php echo $emailErr; ?></span>
                    </div>
                    <div class="input-box">
                        <i class="bx bxs-user"></i>
                        <label for="date">Date:</label>
                        <input type="date" name="date" placeholder="Enter Subscription Date" value="<?php echo htmlspecialchars($dob); ?>">
                        <span class="error"><?php echo $dobErr; ?></span>
                    </div>
                </div>

                <h2>Advance Profile</h2>
                <hr class="title-line">
                <div class="secondrow">
                    <div class="input-box">
                        <i class="bx bxs-user"></i>
                        <label for="ONU">ONU Name:</label>
                        <input type="text" name="ONU" placeholder="ONU Name" value="<?php echo htmlspecialchars($ONU); ?>">
                        <span class="error"><?php echo $ONUErr; ?></span>
                    </div>
                    <div class="input-box">
                        <i class="bx bxs-user"></i>
                        <label for="caller">Caller ID:</label>
                        <input type="text" name="caller" placeholder="Caller ID" value="<?php echo htmlspecialchars($caller); ?>">
                        <span class="error"><?php echo $callerErr; ?></span>
                    </div>
                    <div class="input-box">
                        <i class="bx bxs-user"></i>
                        <label for="address">Mac Address:</label>
                        <input type="text" name="address" placeholder="Mac Address" value="<?php echo htmlspecialchars($address); ?>">
                        <span class="error"><?php echo $addressErr; ?></span>
                    </div>
                    <div class="input-box">
                        <i class="bx bxs-user"></i>
                        <label for="remarks">Remarks:</label>
                        <input type="text" name="remarks" placeholder="Remarks" value="<?php echo htmlspecialchars($remarks); ?>">
                        <span class="error"><?php echo $remarksErr; ?></span>
                    </div>
                </div>
                <div class="button-container">
                    <button type="submit">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>

<?php
$conn->close();
?>