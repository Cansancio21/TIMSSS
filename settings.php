<?php 
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) { 
    header("Location: index.php"); // Redirect to login page if not logged in 
    exit(); 
}

$username = $_SESSION['username'];

// Default avatar
$avatarPath = 'default-avatar.png'; 
$avatarFolder = 'uploads/avatars/';

// Ensure the avatars directory exists
if (!is_dir($avatarFolder)) {
    mkdir($avatarFolder, 0777, true);
}

// Check if user has a custom avatar
$userAvatar = $avatarFolder . $username . '.png';
if (file_exists($userAvatar)) {
    $avatarPath = $userAvatar . '?' . time(); // Force browser to reload new image
}

// Handle avatar upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $uploadFile = $_FILES['avatar'];
    $targetFile = $avatarFolder . $username . '.png'; 
    $imageFileType = strtolower(pathinfo($uploadFile['name'], PATHINFO_EXTENSION));

    // Check if the uploaded file is a valid image type
    if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif', 'jfif'])) {
        // Move the uploaded file to the target directory
        if (move_uploaded_file($uploadFile['tmp_name'], $targetFile)) {
            // Update the session with the new avatar path
            $_SESSION['avatarPath'] = 'uploads/icons/' . $username . '.png' . '?' . time();
            echo "<script>alert('Avatar uploaded successfully!'); window.location.href='settings.php';</script>";
            exit();
        } else {
            echo "<script>alert('Error uploading avatar.');</script>";
        }
    } else {
        echo "<script>alert('Invalid image format. Please upload JPG, PNG, or GIF images.');</script>";
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['old_password'], $_POST['new_password'], $_POST['confirm_password'])) {
    $oldPassword = $_POST['old_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Fetch current password from database
    $stmt = $conn->prepare("SELECT u_password FROM tbl_user WHERE u_username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($storedPassword);
    $stmt->fetch();
    $stmt->close();

    if (password_verify($oldPassword, $storedPassword)) {
        if ($newPassword === $confirmPassword) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE tbl_user SET u_password = ? WHERE u_username = ?");
            $stmt->bind_param("ss", $hashedPassword, $username);
            if ($stmt->execute()) {
                $_SESSION['password_updated'] = true; // Prevent logout
                echo "<script>alert('Password changed successfully!'); window.location.href='index.php';</script>";
                exit();
            } else {
                echo "<script>alert('Error updating password.');</script>";
            }
            $stmt->close();
        } else {
            echo "<script>alert('New passwords do not match.');</script>";
        }
    } else {
        echo "<script>alert('Incorrect old password.');</script>";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="setting.css">
    <script>
        function togglePasswordVisibility(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            icon.classList.toggle('bx-show');
            icon.classList.toggle('bx-hide');
        }

        function previewAvatar(event) {
            const file = event.target.files[0];
            const reader = new FileReader();
            const defaultUserIcon = document.getElementById('defaultUserIcon');
            
            reader.onload = function(e) {
                const avatarPreview = document.getElementById('avatarPreview');
                avatarPreview.src = e.target.result;
                defaultUserIcon.style.display = 'none';
            }

            if (file) {
                reader.readAsDataURL(file);
            }
        }

        window.onload = function() {
            const avatarPath = '<?php echo htmlspecialchars($avatarPath); ?>';
            if (avatarPath !== 'default-avatar.png') {
                document.getElementById('defaultUserIcon').style.display = 'none';
                document.getElementById('avatarPreview').src = avatarPath;
            }
        };
    </script>
</head>
<body>
    <div class="container">
        <div class="left-side" style="position: relative;">
            <a href="index.php" class="back-arrow">
                <i class='bx bx-arrow-back'></i>
            </a>
            <div class="user-icon" style="cursor: pointer; position: relative;" onclick="document.getElementById('avatarInput').click();">
                <img id="avatarPreview" src="<?php echo htmlspecialchars($avatarPath); ?>" alt="User Avatar" style="width: 100px; height: 100px; border-radius: 50%;">
                <i class='bx bxs-user-circle' id="defaultUserIcon" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 40px; color: white;"></i>
            </div>
            <p>Click the user icon to choose your avatar.</p>
            <h2>Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
            <p>Manage your account settings and security preferences here.</p>
        </div>

        <div class="right-side">
            <form action="" method="POST" enctype="multipart/form-data">
                <h1>Account Settings</h1>

                <input type="file" id="avatarInput" name="avatar" accept="image/*" style="display: none;" onchange="previewAvatar(event); this.form.submit();">

                <div class="input-box">
                    <i class='bx bxs-lock-alt'></i>
                    <input type="password" id="old_password" name="old_password" placeholder="Old Password" required>
                    <i class='bx bx-show' id="toggleOldPassword" onclick="togglePasswordVisibility('old_password', 'toggleOldPassword')"></i>
                </div>
                <div class="input-box">
                    <i class='bx bxs-lock-alt'></i>
                    <input type="password" id="new_password" name="new_password" placeholder="New Password" required>
                    <i class='bx bx-show' id="toggleNewPassword" onclick="togglePasswordVisibility('new_password', 'toggleNewPassword')"></i>
                </div>
                <div class="input-box">
                    <i class='bx bxs-lock-alt'></i>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                    <i class='bx bx-show' id="toggleConfirmPassword" onclick="togglePasswordVisibility('confirm_password', 'toggleConfirmPassword')"></i>
                </div>

                <button type="submit" class="btn">Save Changes</button>

                <div class="settings-icons">
                    <a href="#"><i class='bx bxs-user'></i> Account</a>
                    <a href="#"><i class='bx bxs-lock'></i> Security</a>
                    <a href="#"><i class='bx bxs-bell'></i> Notifications</a>
                    <a href="#"><i class='bx bxs-cog'></i> Preferences</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
