<?php
session_start(); // Start session for login management
include 'db.php';

// Handle AJAX status check
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['username']) && !isset($_POST['login']) && !isset($_POST['firstname'])) {
    header('Content-Type: application/json');
    $username = trim($_POST['username']);
    
    $sql = "SELECT u_status FROM tbl_user WHERE u_username = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['error' => 'Prepare failed']);
        exit;
    }
    
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        echo json_encode(['status' => $row['u_status']]);
    } else {
        echo json_encode(['error' => 'User not found']);
    }
    
    $stmt->close();
    exit;
}

// Initialize variables as empty (ensures fields are empty on initial load)
$firstname = $lastname = $email = $username = "";
$type = $status = ""; 

$firstnameErr = $lastnameErr = $loginError = $passwordError = $usernameError = "";
$hasError = false;

// User Registration
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['firstname'])) {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $type = trim($_POST['type']);
    $status = trim($_POST['status']);

    // Validate firstname (should not contain numbers)
    if (!preg_match("/^[a-zA-Z\s-]+$/", $firstname)) {
        $firstnameErr = "Firstname should not contain numbers.";
        $hasError = true;
    }

    // Validate lastname (should not contain numbers)
    if (!preg_match("/^[a-zA-Z\s-]+$/", $lastname)) {   
        $lastnameErr = "Lastname should not contain numbers.";
        $hasError = true;
    }

    // Check if username already exists
    $sql = "SELECT u_id FROM tbl_user WHERE u_username = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $usernameError = "Username already exists.";
        $hasError = true;
    }

    // Validate password (must contain letters, numbers, and special characters)
    if (!preg_match("/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/", $password)) {
        $passwordError = "Password is weak.";
        $hasError = true;
    } else {
        // Hash the password if validation passes
        $password = password_hash($password, PASSWORD_BCRYPT);
    }

    if (!$hasError) {
        $sql = "INSERT INTO tbl_user (u_fname, u_lname, u_email, u_username, u_password, u_type, u_status)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("sssssss", $firstname, $lastname, $email, $username, $password, $type, $status);

        if ($stmt->execute()) {
            echo "<script type='text/javascript'>
                alert('Registration successful! Please log in.');
            </script>";
        } else {
            die("Execution failed: " . $stmt->error);
        }

        $stmt->close();
    }
}

// Initialize status message variable
$statusMessage = "";
$statusClass = "";

// User Login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Check if username exists
    $sql = "SELECT u_id, u_username, u_password, u_type, u_status FROM tbl_user WHERE u_username = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("s", $username);
    
    if ($stmt->execute() === false) {
        die("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // Username exists
        $row = $result->fetch_assoc();

        // Set status message and class based on account status
        if (strtolower($row['u_status']) === "pending") {
            $statusMessage = "Your account is pending.";
            $statusClass = "pending";
        } elseif (strtolower($row['u_status']) === "active") {
            $statusMessage = "Your account is active.";
            $statusClass = "active";
        }

        // Proceed with password verification only if status is active
        if (strtolower($row['u_status']) === "pending") {
            // No additional error message; statusMessage handles it
        } elseif (password_verify($password, $row['u_password'])) {
            // Successful login
            $_SESSION['username'] = $row['u_username'];
            $_SESSION['user_type'] = $row['u_type'];
            $_SESSION['logged_in'] = true; // Flag to track login

            // Redirect based on user type
            if ($row['u_type'] == 'admin') {
                header("Location: viewU.php");
                exit();
            } elseif ($row['u_type'] == 'staff') {
                header("Location: staffD.php");
                exit();
            } elseif ($row['u_type'] == 'technician') {
                header("Location: technicianD.php");
                exit();
            }
        } else {
            $passwordError = "Incorrect password. Try again.";
        }
    } else {
        $loginError = "Incorrect username. Try again.";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration & Login</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="indexs.css">
    <script>
        // Function to validate password strength
        function validatePassword() {
            const passwordInput = document.getElementById('password');
            const passwordError = document.getElementById('passwordError');
            const password = passwordInput.value;

            // Regular expression for strong password
            const strongPasswordPattern = /^(?=.*[a-zA-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

            if (strongPasswordPattern.test(password)) {
                passwordError.textContent = "Password is strong.";
                passwordError.style.color = "green";
            } else {
                passwordError.textContent = "Password is weak.";
                passwordError.style.color = "red";
            }
        }

        // Toggle password visibility and status polling
        document.addEventListener('DOMContentLoaded', function () {
            // Password visibility toggles
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const loginPasswordInput = document.getElementById('loginPassword');

            togglePassword.addEventListener('click', function () {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.classList.toggle('bx-show');
                this.classList.toggle('bx-hide');
            });

            const toggleLoginPassword = document.getElementById('toggleLoginPassword');
            toggleLoginPassword.addEventListener('click', function () {
                const type = loginPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                loginPasswordInput.setAttribute('type', type);
                this.classList.toggle('bx-show');
                this.classList.toggle('bx-hide');
            });

            // Fade out status message only for active status after 3 seconds
            const statusMessage = document.querySelector('.status-message');
            if (statusMessage && statusMessage.classList.contains('active')) {
                setTimeout(() => {
                    statusMessage.classList.add('fade-out');
                    setTimeout(() => {
                        statusMessage.style.display = 'none';
                    }, 500); // Match transition duration
                }, 3000); // 3 seconds
            }

            // Poll for status updates
            function checkStatus() {
                const usernameInput = document.querySelector('input[name="username"]').value;
                if (usernameInput) {
                    fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'username=' + encodeURIComponent(usernameInput)
                    })
                    .then(response => response.json())
                    .then(data => {
                        const statusDiv = document.querySelector('.status-message');
                        if (data.status) {
                            const newStatus = data.status.toLowerCase();
                            const message = newStatus === 'pending' ? 'Your account is pending.' : 'Your account is active.';
                            const statusClass = newStatus === 'pending' ? 'pending' : 'active';

                            if (!statusDiv) {
                                const newDiv = document.createElement('div');
                                newDiv.className = `status-message ${statusClass}`;
                                newDiv.textContent = message;
                                document.querySelector('.form-box.login h1').insertAdjacentElement('afterend', newDiv);
                                if (newStatus === 'active') {
                                    setTimeout(() => {
                                        newDiv.classList.add('fade-out');
                                        setTimeout(() => {
                                            newDiv.style.display = 'none';
                                        }, 500);
                                    }, 3000);
                                }
                            } else if (statusDiv.textContent !== message) {
                                statusDiv.textContent = message;
                                statusDiv.className = `status-message ${statusClass}`;
                                statusDiv.style.display = 'block';
                                statusDiv.classList.remove('fade-out');
                                if (newStatus === 'active') {
                                    setTimeout(() => {
                                        statusDiv.classList.add('fade-out');
                                        setTimeout(() => {
                                            statusDiv.style.display = 'none';
                                        }, 500);
                                    }, 3000);
                                }
                            }
                        }
                    })
                    .catch(error => console.error('Error checking status:', error));
                }
            }

            // Check status every 2 seconds if username is entered
            setInterval(checkStatus, 2000);
        });
    </script>
</head>
<body>
    <div class="container <?php echo ($hasError) ? 'active' : ''; ?>">
        <!-- Login Form -->
        <div class="form-box login">
            <form action="" method="POST">
                <h1>Login</h1>
                <?php if (!empty($statusMessage)) { ?>
                    <div class="status-message <?php echo $statusClass; ?>">
                        <?php echo htmlspecialchars($statusMessage); ?>
                    </div>
                <?php } ?>
                <div class="input-box">
                    <input type="text" name="username" placeholder="Username" required>
                    <i class='bx bxs-user'></i>
                    <?php if (!empty($loginError)) echo "<p class='error-message'>$loginError</p>"; ?>
                </div>
                <div class="input-box">
                    <input type="password" id="loginPassword" name="password" placeholder="Password" required>
                    <i class='bx bxs-lock-alt' id="toggleLoginPassword" style="cursor: pointer;"></i>
                    <?php if (!empty($passwordError)) echo "<p class='error-message'>$passwordError</p>"; ?>
                </div> 
                <button type="submit" name="login" class="btn">Login</button>
                <p>or login with social platform</p>
                <div class="social-icons">
                    <a href="#"><i class='bx bxl-google'></i></a>
                    <a href="#"><i class='bx bxl-facebook'></i></a>
                    <a href="#"><i class='bx bxl-github'></i></a>
                    <a href="#"><i class='bx bxl-linkedin'></i></a>
                </div>
            </form>
        </div>

        <!-- Registration Form -->
        <div class="form-box register">
            <form action="" method="POST">
                <h1>Registration</h1>
                <div class="input-box">
                    <input type="text" name="firstname" placeholder="Firstname" value="<?php echo htmlspecialchars($firstname); ?>" required>
                    <i class="bx bxs-user firstname-icon"></i>
                    <?php if (!empty($firstnameErr)) echo "<span class='error'>$firstnameErr</span>"; ?>
                </div>
                <div class="input-box">
                    <input type="text" name="lastname" placeholder="Lastname" value="<?php echo htmlspecialchars($lastname); ?>" required>
                    <i class="bx bxs-user lastname-icon"></i>
                    <?php if (!empty($lastnameErr)) echo "<span class='error'>$lastnameErr</span>"; ?>
                </div>
                <div class="input-box">
                    <input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($email); ?>" required>
                    <i class="bx bxs-envelope email-icon"></i>
                </div>
                <div class="input-box">
                    <input type="text" name="username" placeholder="Username" value="<?php echo htmlspecialchars($username); ?>" required>
                    <i class="bx bxs-user username-icon"></i>
                    <?php if (!empty($usernameError)) echo "<span class='error'>$usernameError</span>"; ?>
                </div>            
                <div class="input-box">
                    <input type="password" id="password" name="password" placeholder="Password" oninput="validatePassword()" required>
                    <span id="passwordError" class="error"><?php echo $passwordError; ?></span>
                    <i class='bx bxs-lock-alt password-icon' id="togglePassword" style="cursor: pointer;"></i>
                </div>
                <div class="input-box">
                    <select name="type" required>
                        <option value="" disabled selected>Select Type</option>
                        <option value="technician" <?php if ($type == 'technician') echo 'selected'; ?>>Technician</option>
                        <option value="admin" <?php if ($type == 'admin') echo 'selected'; ?>>Admin</option>
                        <option value="staff" <?php if ($type == 'staff') echo 'selected'; ?>>Staff</option>
                    </select>
                    <i class='bx bxs-user type-icon'></i>
                </div>
                <div class="input-box">
                    <select name="status" required>
                        <option value="" disabled selected>Select Status</option>
                        <option value="pending" <?php if ($status == 'pending') echo 'selected'; ?>>Pending</option>
                        <option value="active" <?php if ($status == 'active') echo 'selected'; ?>>Active</option>
                    </select>
                    <i class='bx bxs-check-circle status-icon'></i>
                </div>
                <button type="submit" class="button">Register</button>
            </form>
        </div>

        <!-- Toggle Panels -->
        <div class="toggle-box">
            <div class="toggle-panel toggle-left">
                <h1>Hello Welcome!</h1>
                <p>Don't have an account?</p>
                <button class="btn register-btn">Register</button>
            </div>
            <div class="toggle-panel toggle-right">
                <h1>Welcome Back!</h1>
                <p>Already have an account?</p>
                <button class="btn login-btn">Login</button>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            // Toggle between Login & Register
            const container = document.querySelector(".container");
            const registerBtn = document.querySelector(".register-btn");
            const loginBtn = document.querySelector(".login-btn");

            registerBtn.addEventListener("click", () => {
                container.classList.add("active");
            });

            loginBtn.addEventListener("click", () => {
                container.classList.remove("active");
            });
        });
    </script>
</body>
</html>