<?php
session_start();

// Debug: Log session variables
error_log("Session data: username=" . ($_SESSION['username'] ?? 'unset'));

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    error_log("No session found, redirecting to index.php");
    header("Location: index.php");
    exit();
}

// Determine user identifier
$username = $_SESSION['username'];
$userType = 'staff'; // Default

$avatarFolder = 'Uploads/avatars/';
$generatedFolder = 'Uploads/generated/';

// Create directories if they don’t exist
foreach ([$avatarFolder, $generatedFolder] as $folder) {
    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }
}

// Default avatar
$avatarPath = 'default-avatar.png';
$userAvatar = $avatarFolder . $username . '.png';
if (file_exists($userAvatar)) {
    $avatarPath = $userAvatar . '?' . time();
}

// Connect to the database to fetch user type
include 'db.php'; // Ensure this file establishes $conn
$sqlUser = "SELECT u_type FROM tbl_user WHERE u_username = ?";
$stmt = $conn->prepare($sqlUser);
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    echo "<script>alert('Error fetching user data. Please try again later.'); window.location.href='index.php';</script>";
    exit();
}
$stmt->bind_param("s", $username);
$stmt->execute();
$resultUser = $stmt->get_result();
if ($resultUser->num_rows > 0) {
    $row = $resultUser->fetch_assoc();
    $userType = strtolower($row['u_type']);
    error_log("Fetched userType for $username: $userType");
} else {
    error_log("No user found for username: $username, defaulting to staff");
    $userType = 'staff';
}
$stmt->close();

// Debug: Log the determined userType
error_log("Determined userType: $userType");

// Handle avatar upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $uploadFile = $_FILES['avatar'];
    $targetFile = $avatarFolder . $username . '.png';
    $imageFileType = strtolower(pathinfo($uploadFile['name'], PATHINFO_EXTENSION));

    // Validate image type and size
    if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
        if ($uploadFile['size'] <= 5000000) { // 5MB limit
            if (move_uploaded_file($uploadFile['tmp_name'], $targetFile)) {
                $_SESSION['avatarPath'] = $targetFile . '?' . time();
                error_log("Uploaded avatar: $targetFile, Session avatarPath: {$_SESSION['avatarPath']}, UserType: $userType");

                // Explicit redirect based on user type
                if ($userType === 'admin') {
                    $redirectUrl = 'viewU.php';
                } elseif ($userType === 'technician') {
                    $redirectUrl = 'technicianD.php';
                } else {
                    $redirectUrl = 'staffD.php';
                }
                error_log("Redirecting $userType to: $redirectUrl");
                echo "<script>alert('Avatar uploaded successfully!'); window.location.href='$redirectUrl';</script>";
                exit();
            } else {
                error_log("Failed to move uploaded file: " . $uploadFile['error']);
                echo "<script>alert('Error uploading avatar: Unable to move file.');</script>";
            }
        } else {
            echo "<script>alert('File size exceeds 5MB limit.');</script>";
        }
    } else {
        echo "<script>alert('Invalid image format. Please upload JPG, PNG, or GIF images.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="views.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: #f8f9fa;
        }
        .outer-table-box {
            width: 90%;
            padding: 20px;
            margin: 20px;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .inner-table-box {
            width: 100%;
            height: 500px;
            padding: 10px;
            margin-top: -20px;
            position: relative;
            overflow: hidden;
        }
        .inner-table-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
        }
        .button-container {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        .button-container button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
        }
        #uploadBtn {
            background-color: #4CAF50;
            color: white;
        }
        #uploadBtn:hover {
            background-color: #45a049;
        }
        #generateBtn {
            background-color: #008CBA;
            color: white;
        }
        #generateBtn:hover {
            background-color: #007399;
        }
        input[type="file"] {
            display: none;
        }
    </style>
</head>
<body>
    <div class="outer-table-box table-box glass-container">
        <h2>Manage Avatar</h2>
        <div class="button-container">
            <form id="uploadForm" enctype="multipart/form-data" method="POST">
                <input type="file" id="avatarInput" name="avatar" accept="image/*">
                <button type="button" id="uploadBtn">Upload Image</button>
            </form>
            <form id="generateForm" enctype="multipart/form-data">
                <input type="file" id="generateInput" name="generated_image" accept="image/*">
                <button type="button" id="generateBtn">Generate Image</button>
            </form>
        </div>
        <div class="inner-table-box table-box glass-container">
            <img id="previewImage" src="<?php echo htmlspecialchars($avatarPath, ENT_QUOTES, 'UTF-8'); ?>" alt="Current Avatar">
        </div>
    </div>

    <script>
        // Generate button triggers file input
        document.getElementById('generateBtn').addEventListener('click', () => {
            document.getElementById('generateInput').click();
        });

        // Preview selected image and copy to avatarInput
        document.getElementById('generateInput').addEventListener('change', (event) => {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    document.getElementById('previewImage').src = e.target.result;
                };
                reader.readAsDataURL(file);

                // Clear and update avatarInput with the new file
                const avatarInput = document.getElementById('avatarInput');
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                avatarInput.files = dataTransfer.files;

                // Debugging: Log the file name to confirm
                console.log('Selected file:', file.name);
            }
        });

        // Upload button submits form if file is selected
        document.getElementById('uploadBtn').addEventListener('click', () => {
            const avatarInput = document.getElementById('avatarInput');
            if (avatarInput.files.length > 0) {
                // Debugging: Log the file name being uploaded
                console.log('Uploading file:', avatarInput.files[0].name);
                document.getElementById('uploadForm').submit();
            } else {
                alert('Please select an image first using the Generate Image button.');
            }
        });
    </script>
</body>
</html>