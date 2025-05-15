<?php
session_start();

// Include PHPMailer dependencies
require 'PHPmailer-master/PHPmailer-master/src/Exception.php';
require 'PHPmailer-master/PHPmailer-master/src/PHPMailer.php';
require 'PHPmailer-master/PHPmailer-master/src/SMTP.php';

// Use PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$username = $_SESSION['username'];

// Initialize user detail variables
$firstName = '';
$lastName = '';
$userType = '';
$avatarFolder = 'Uploads/avatars/';
$userAvatar = $avatarFolder . $username . '.png';

// Initialize avatarPath from session or default
$avatarPath = isset($_SESSION['avatarPath']) ? $_SESSION['avatarPath'] : 'default-avatar.png';

// Update session only if the file exists and differs from current session value
$cleanAvatarPath = preg_replace('/\?\d+$/', '', $avatarPath);
if (file_exists($userAvatar) && $cleanAvatarPath !== $userAvatar) {
    $_SESSION['avatarPath'] = $userAvatar . '?' . time();
} elseif (!file_exists($cleanAvatarPath) && $avatarPath !== 'default-avatar.png') {
    $_SESSION['avatarPath'] = 'default-avatar.png';
}
$avatarPath = $_SESSION['avatarPath'];

// Debugging: Log avatar path and file existence
error_log("viewU.php: avatarPath=$avatarPath, cleanAvatarPath=$cleanAvatarPath, file_exists=" . (file_exists($cleanAvatarPath) ? 'Yes' : 'No'));

// Fetch user details for display
if ($conn) {
    $sqlUser = "SELECT u_fname, u_lname, u_type FROM tbl_user WHERE u_username = ?";
    $stmt = $conn->prepare($sqlUser);
    if (!$stmt) {
        error_log("Prepare failed for user details: " . $conn->error);
        $_SESSION['error'] = "Database error fetching user details.";
    } else {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $resultUser = $stmt->get_result();
        if ($resultUser->num_rows > 0) {
            $row = $resultUser->fetch_assoc();
            $firstName = $row['u_fname'];
            $lastName = $row['u_lname'];
            $userType = $row['u_type'];
        } else {
            error_log("No user found for username: $username");
            $_SESSION['error'] = "User not found in database.";
        }
        $stmt->close();
    }
} else {
    error_log("Database connection failed.");
    $_SESSION['error'] = "Database connection failed.";
}

// Initialize variables for add user validation
$firstnameErr = $lastnameErr = $emailErr = $usernameErr = $passwordErr = "";
$firstname = $lastname = $email = $username = $password = $type = $status = "";
$hasError = false;

// Check if tbl_user exists
$result = $conn->query("SHOW TABLES LIKE 'tbl_user'");
if ($result->num_rows == 0) {
    die("Error: Table 'tbl_user' does not exist in the 'task_management' database.");
}

// Handle AJAX search request
if (isset($_GET['action']) && $_GET['action'] === 'search' && isset($_GET['search'])) {
    header('Content-Type: application/json');
    $searchTerm = trim($_GET['search']);
    $tab = isset($_GET['tab']) ? $_GET['tab'] : 'active';
    $page = isset($_GET['search_page']) ? (int)$_GET['search_page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    $output = '';
    $response = ['html' => '', 'currentPage' => $page, 'totalPages' => 0, 'tab' => $tab, 'searchTerm' => $searchTerm];

    if ($tab === 'active') {
        if ($searchTerm === '') {
            $countSql = "SELECT COUNT(*) as total FROM tbl_user WHERE u_status IN ('active', 'pending')";
            $countResult = $conn->query($countSql);
            $totalUsers = $countResult->fetch_assoc()['total'];
            $totalPages = ceil($totalUsers / $limit);

            $sql = "SELECT u_id, u_fname, u_lname, u_email, u_username, u_type, u_status FROM tbl_user 
                    WHERE u_status IN ('active', 'pending') 
                    LIMIT ?, ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $offset, $limit);
        } else {
            $countSql = "SELECT COUNT(*) as total FROM tbl_user 
                        WHERE u_status IN ('active', 'pending') 
                        AND (u_fname LIKE ? OR u_lname LIKE ? OR u_email LIKE ? OR u_username LIKE ?)";
            $countStmt = $conn->prepare($countSql);
            $searchWildcard = "%$searchTerm%";
            $countStmt->bind_param("ssss", $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard);
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $totalUsers = $countResult->fetch_assoc()['total'];
            $countStmt->close();

            $totalPages = ceil($totalUsers / $limit);

            $sql = "SELECT u_id, u_fname, u_lname, u_email, u_username, u_type, u_status FROM tbl_user 
                    WHERE u_status IN ('active', 'pending') 
                    AND (u_fname LIKE ? OR u_lname LIKE ? OR u_email LIKE ? OR u_username LIKE ?)
                    LIMIT ?, ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssii", $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard, $offset, $limit);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output .= "<tr> 
                            <td>{$row['u_id']}</td> 
                            <td>" . htmlspecialchars($row['u_fname'], ENT_QUOTES, 'UTF-8') . "</td> 
                            <td>" . htmlspecialchars($row['u_lname'], ENT_QUOTES, 'UTF-8') . "</td> 
                            <td>" . htmlspecialchars($row['u_email'], ENT_QUOTES, 'UTF-8') . "</td> 
                            <td>" . htmlspecialchars($row['u_username'], ENT_QUOTES, 'UTF-8') . "</td> 
                            <td>" . ucfirst(strtolower($row['u_type'])) . "</td> 
                            <td class='status-" . strtolower($row['u_status']) . "'>" . ucfirst(strtolower($row['u_status'])) . "</td>
                            <td class='action-buttons'>
                                <a class='view-btn' onclick=\"showViewModal('{$row['u_id']}', '" . htmlspecialchars($row['u_fname'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['u_lname'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['u_email'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['u_username'], ENT_QUOTES, 'UTF-8') . "', '{$row['u_type']}', '{$row['u_status']}')\" title='View'><i class='fas fa-eye'></i></a>
                                <a class='edit-btn' onclick=\"showEditUserModal('{$row['u_id']}', '" . htmlspecialchars($row['u_fname'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['u_lname'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['u_email'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['u_username'], ENT_QUOTES, 'UTF-8') . "', '{$row['u_type']}', '{$row['u_status']}')\" title='Edit'><i class='fas fa-edit'></i></a>
                                <a class='archive-btn' onclick=\"showArchiveModal('{$row['u_id']}', '" . htmlspecialchars($row['u_fname'] . ' ' . $row['u_lname'], ENT_QUOTES, 'UTF-8') . "')\" title='Archive'><i class='fas fa-archive'></i></a>
                            </td>
                            </tr>";
            }
        } else {
            $output = "<tr><td colspan='8' style='text-align: center;'>No active or pending users found.</td></tr>";
        }
        $stmt->close();

        $response['html'] = $output;
        $response['totalPages'] = $totalPages;
    } else {
        if ($searchTerm === '') {
            $countSql = "SELECT COUNT(*) as total FROM tbl_archive";
            $countResult = $conn->query($countSql);
            $totalUsers = $countResult->fetch_assoc()['total'];
            $totalPages = ceil($totalUsers / $limit);

            $sql = "SELECT u_id, u_fname, u_lname, u_email, u_username, u_type, u_status FROM tbl_archive 
                    LIMIT ?, ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $offset, $limit);
        } else {
            $countSql = "SELECT COUNT(*) as total FROM tbl_archive 
                        WHERE u_fname LIKE ? OR u_lname LIKE ? OR u_email LIKE ? OR u_username LIKE ?";
            $countStmt = $conn->prepare($countSql);
            $searchWildcard = "%$searchTerm%";
            $countStmt->bind_param("ssss", $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard);
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $totalUsers = $countResult->fetch_assoc()['total'];
            $countStmt->close();

            $totalPages = ceil($totalUsers / $limit);

            $sql = "SELECT u_id, u_fname, u_lname, u_email, u_username, u_type, u_status FROM tbl_archive 
                    WHERE u_fname LIKE ? OR u_lname LIKE ? OR u_email LIKE ? OR u_username LIKE ?
                    LIMIT ?, ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssii", $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard, $offset, $limit);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output .= "<tr> 
                            <td>{$row['u_id']}</td> 
                            <td>" . htmlspecialchars($row['u_fname'], ENT_QUOTES, 'UTF-8') . "</td> 
                            <td>" . htmlspecialchars($row['u_lname'], ENT_QUOTES, 'UTF-8') . "</td> 
                            <td>" . htmlspecialchars($row['u_email'], ENT_QUOTES, 'UTF-8') . "</td> 
                            <td>" . htmlspecialchars($row['u_username'], ENT_QUOTES, 'UTF-8') . "</td> 
                            <td>" . ucfirst(strtolower($row['u_type'])) . "</td> 
                            <td class='status-" . strtolower($row['u_status']) . "'>" . ucfirst(strtolower($row['u_status'])) . "</td>
                            <td class='action-buttons'>
                                <a class='view-btn' onclick=\"showViewModal('{$row['u_id']}', '" . htmlspecialchars($row['u_fname'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['u_lname'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['u_email'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['u_username'], ENT_QUOTES, 'UTF-8') . "', '{$row['u_type']}', '{$row['u_status']}')\" title='View'><i class='fas fa-eye'></i></a>
                                <a class='unarchive-btn' onclick=\"showRestoreModal('{$row['u_id']}', '" . htmlspecialchars($row['u_fname'] . ' ' . $row['u_lname'], ENT_QUOTES, 'UTF-8') . "')\" title='Unarchive'><i class='fas fa-box-open'></i></a>
                                <a class='delete-btn' onclick=\"showDeleteModal('{$row['u_id']}', '" . htmlspecialchars($row['u_fname'] . ' ' . $row['u_lname'], ENT_QUOTES, 'UTF-8') . "')\" title='Delete'><i class='fas fa-trash'></i></a>
                            </td>
                            </tr>";
            }
        } else {
            $output = "<tr><td colspan='8' style='text-align: center;'>No archived users found.</td></tr>";
        }
        $stmt->close();

        $response['html'] = $output;
        $response['totalPages'] = $totalPages;
    }

    echo json_encode($response);
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'active';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $archivedPage = isset($_GET['archived_page']) ? (int)$_GET['archived_page'] : 1;

    if (isset($_POST['add_user'])) {
        // Get form data and sanitize
        $firstname = trim($_POST['firstname']);
        $lastname = trim($_POST['lastname']);
        $email = trim($_POST['email']);
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $type = trim($_POST['type']);
        $status = trim($_POST['status']);

        // Validation
        if (!preg_match("/^[a-zA-Z\s-]+$/", $firstname)) {
            $firstnameErr = "Firstname should not contain numbers.";
            $hasError = true;
        }

        if (!preg_match("/^[a-zA-Z\s-]+$/", $lastname)) {
            $lastnameErr = "Lastname should not contain numbers.";
            $hasError = true;
        }

        if (empty($email)) {
            $emailErr = "Email is required.";
            $hasError = true;
        }

        if (empty($username)) {
            $usernameErr = "Username is required.";
            $hasError = true;
        }

        if (!preg_match("/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/", $password)) {
            $passwordErr = "Password must be at least 8 characters with letters, numbers, and special characters.";
            $hasError = true;
        }

        if (empty($type)) {
            $hasError = true;
        }

        if (empty($status)) {
            $hasError = true;
        }

        if (!$hasError) {
            // Log submission for debugging
            $log = date('Y-m-d H:i:s') . " - Attempted to add user: $firstname $lastname, $email, $username\n";
            file_put_contents('user_submission_log.txt', $log, FILE_APPEND);

            // Hash the password
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);

            $sql = "INSERT INTO tbl_user (u_fname, u_lname, u_email, u_username, u_password, u_type, u_status)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                if (isset($_POST['ajax']) && $_POST['ajax'] == 'true') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
                    exit();
                }
                die("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("sssssss", $firstname, $lastname, $email, $username, $passwordHash, $type, $status);

            if ($stmt->execute()) {
                // Send the confirmation email using PHPMailer
                $mail = new PHPMailer(true);
                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'jonwilyammayormita@gmail.com';
                    $mail->Password = 'mqkcqkytlwurwlks';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    // Recipients
                    $mail->setFrom('jonwilyammayormita@gmail.com', 'Your Website');
                    $mail->addAddress($email, "$firstname $lastname");

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Your Account Has Been Created';
                    $mail->Body = "
                        <html>
                        <head>
                            <title>Your Account Details</title>
                        </head>
                        <body>
                            <p>Dear $firstname $lastname,</p>
                            <p>Your account has been successfully created. Here are your login credentials:</p>
                            <p><strong>Username:</strong> $username</p>
                            <p><strong>Password:</strong> $password</p>
                            <p>Please use these credentials to log in to our system by clicking the link below:</p>
                            <p><a href='http://localhost/TIMSSS/index.php'>Login Page</a></p>
                            <p>For security reasons, we recommend changing your password after first login.</p>
                            <p>Best regards,<br>Your System Administrator</p>
                        </body>
                        </html>
                    ";
                    $mail->AltBody = "Dear $firstname $lastname,\n\nYour account has been successfully created. Here are your login credentials:\nUsername: $username\nPassword: $password\n\nPlease use these credentials to log in to our system at http://localhost/TIMSSS/index.php\n\nFor security reasons, we recommend changing your password after first login.\n\nBest regards,\nYour System Administrator";

                    // Send the email
                    $mail->send();

                    if (isset($_POST['ajax']) && $_POST['ajax'] == 'true') {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'message' => 'User has been registered successfully. A confirmation email has been sent.']);
                        exit();
                    }
                    $_SESSION['message'] = "User has been registered successfully. A confirmation email has been sent.";
                } catch (Exception $e) {
                    if (isset($_POST['ajax']) && $_POST['ajax'] == 'true') {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'message' => 'User registered, but error sending confirmation email: ' . $mail->ErrorInfo]);
                        exit();
                    }
                    $_SESSION['message'] = "User registered, but error sending confirmation email: " . $mail->ErrorInfo;
                }
            } else {
                if (isset($_POST['ajax']) && $_POST['ajax'] == 'true') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Execution failed: ' . $stmt->error]);
                    exit();
                }
                die("Execution failed: " . $stmt->error);
            }
            $stmt->close();
        } else {
            if (isset($_POST['ajax']) && $_POST['ajax'] == 'true') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Validation errors: ' . implode(', ', array_filter([$firstnameErr, $lastnameErr, $emailErr, $usernameErr, $passwordErr]))]);
                exit();
            }
        }
    } elseif (isset($_POST['edit_user'])) {
        $user_id = $_POST['u_id'];
        $firstname = trim($_POST['firstname']);
        $lastname = trim($_POST['lastname']);
        $email = trim($_POST['email']);
        $username = trim($_POST['username']);
        $type = trim($_POST['type']);
        $status = trim($_POST['status']);

        // Debugging: Check the values being submitted
        error_log("Updating user: $user_id, Type: $type, Status: $status");

        // Update user in the database
        $update_sql = "UPDATE tbl_user SET u_fname=?, u_lname=?, u_email=?, u_username=?, u_type=?, u_status=? WHERE u_id=?";
        $update_stmt = $conn->prepare($update_sql);
        if (!$update_stmt) {
            if (isset($_POST['ajax']) && $_POST['ajax'] == 'true') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
                exit();
            }
            die("Prepare failed: " . $conn->error);
        }
        $update_stmt->bind_param("ssssssi", $firstname, $lastname, $email, $username, $type, $status, $user_id);

        if ($update_stmt->execute()) {
            if (isset($_POST['ajax']) && $_POST['ajax'] == 'true') {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'User updated successfully!']);
                exit();
            }
            $_SESSION['message'] = "User updated successfully!";
        } else {
            if (isset($_POST['ajax']) && $_POST['ajax'] == 'true') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Error updating user: ' . $conn->error]);
                exit();
            }
            $_SESSION['error'] = "Error updating user: " . $conn->error;
        }
        $update_stmt->close();
    } elseif (isset($_POST['archive_user'])) {
        $id = $_POST['u_id'];
        $sql = "INSERT INTO tbl_archive (u_id, u_fname, u_lname, u_email, u_username, u_password, u_type, u_status) 
                SELECT u_id, u_fname, u_lname, u_email, u_username, u_password, u_type, u_status 
                FROM tbl_user WHERE u_id=?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        $sql = "DELETE FROM tbl_user WHERE u_id=?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "User archived successfully!";
        } else {
            $_SESSION['error'] = "Error archiving user: " . $stmt->error;
        }
        $stmt->close();
    } elseif (isset($_POST['restore_user'])) {
        $id = $_POST['u_id'];

        // Check if the u_id already exists in tbl_user
        $checkSql = "SELECT u_id FROM tbl_user WHERE u_id = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $_SESSION['error'] = "User with ID $id already exists in active users!";
        } else {
            $sql = "INSERT INTO tbl_user (u_id, u_fname, u_lname, u_email, u_username, u_password, u_type, u_status) 
                    SELECT u_id, u_fname, u_lname, u_email, u_username, u_password, u_type, u_status 
                    FROM tbl_archive WHERE u_id=?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                die("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $sql = "DELETE FROM tbl_archive WHERE u_id=?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                die("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['message'] = "User unarchived successfully!";
            } else {
                $_SESSION['error'] = "Error unarchiving user: " . $stmt->error;
            }
            $stmt->close();
        }
        $checkStmt->close();
    } elseif (isset($_POST['restore_all_users'])) {
        // Fetch all archived users to check for duplicates
        $checkSql = "SELECT u_id FROM tbl_archive";
        $checkResult = $conn->query($checkSql);
        $duplicateIds = [];
        while ($row = $checkResult->fetch_assoc()) {
            $id = $row['u_id'];
            $existsSql = "SELECT u_id FROM tbl_user WHERE u_id = ?";
            $existsStmt = $conn->prepare($existsSql);
            $existsStmt->bind_param("i", $id);
            $existsStmt->execute();
            $existsResult = $existsStmt->get_result();
            if ($existsResult->num_rows > 0) {
                $duplicateIds[] = $id;
            }
            $existsStmt->close();
        }

        if (!empty($duplicateIds)) {
            $_SESSION['error'] = "Cannot restore all users. Duplicate IDs found: " . implode(", ", $duplicateIds);
        } else {
            $sql = "INSERT INTO tbl_user (u_id, u_fname, u_lname, u_email, u_username, u_password, u_type, u_status) 
                    SELECT u_id, u_fname, u_lname, u_email, u_username, u_password, u_type, u_status 
                    FROM tbl_archive";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                die("Prepare failed: " . $conn->error);
            }
            $stmt->execute();
            $stmt->close();

            $sql = "DELETE FROM tbl_archive";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                die("Prepare failed: " . $conn->error);
            }
            if ($stmt->execute()) {
                $_SESSION['message'] = "All users restored successfully!";
            } else {
                $_SESSION['error'] = "Error restoring all users: " . $stmt->error;
            }
            $stmt->close();
        }
    } elseif (isset($_POST['delete_user'])) {
        $id = $_POST['u_id'];

        $sql = "DELETE FROM tbl_archive WHERE u_id=?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "User deleted from archive successfully!";
        } else {
            $_SESSION['error'] = "Error deleting user: " . $stmt->error;
        }
        $stmt->close();
    }

    // Redirect based on current tab if not AJAX
    if (!(isset($_POST['ajax']) && $_POST['ajax'] == 'true')) {
        if ($currentTab === 'archived') {
            header("Location: viewU.php?tab=archived&archived_page=$archivedPage");
        } else {
            header("Location: viewU.php?tab=active&page=$page");
        }
        exit();
    }
}

// Pagination for active and pending users
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$countQuery = "SELECT COUNT(*) as total FROM tbl_user WHERE u_status IN ('active', 'pending')";
$countResult = $conn->query($countQuery);
$totalUsers = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalUsers / $limit);

$sql = "SELECT u_id, u_fname, u_lname, u_email, u_username, u_type, u_status FROM tbl_user WHERE u_status IN ('active', 'pending') LIMIT ?, ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("ii", $offset, $limit);
$stmt->execute();
$activeAndPendingUsers = $stmt->get_result();
$stmt->close();

// Pagination for archived users
$archivedPage = isset($_GET['archived_page']) ? (int)$_GET['archived_page'] : 1;
$archivedOffset = ($archivedPage - 1) * $limit;

$archivedCountQuery = "SELECT COUNT(*) as total FROM tbl_archive";
$archivedCountResult = $conn->query($archivedCountQuery);
$totalArchived = $archivedCountResult->fetch_assoc()['total'];
$totalArchivedPages = ceil($totalArchived / $limit);

$archivedUsersQuery = "SELECT u_id, u_fname, u_lname, u_email, u_username, u_type, u_status FROM tbl_archive LIMIT ?, ?";
$stmt = $conn->prepare($archivedUsersQuery);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("ii", $archivedOffset, $limit);
$stmt->execute();
$archivedResult = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | User Management</title>
    <link rel="stylesheet" href="viewU.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&display=swap" rel="stylesheet">
</head>
<body>
<div class="wrapper">
    <div class="sidebar glass-container">
        <h2><img src="image/logo.png" alt="Tix Net Icon" class="sidebar-icon">TixNet Pro</h2>
        <ul>
            <li><a href="adminD.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="viewU.php" class="active"><i class="fas fa-users"></i> <span>View Users</span></a></li>
            <li><a href="view_service_record.php"><i class="fas fa-wrench"></i> <span> Service Record</span></a></li>
            <li><a href="logs.php"><i class="fas fa-file-alt"></i> <span>View Logs</span></a></li>
            <li><a href="borrowedT.php"><i class="fas fa-book"></i> <span>Borrowed Records</span></a></li>
            <li><a href="returnT.php"><i class="fas fa-undo"></i> <span>Returned Records</span></a></li>
            <li><a href="deployedT.php"><i class="fas fa-rocket"></i> <span>Deploy Records</span></a></li>
        </ul>
        <footer>
            <a href="index.php" class="back-home"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </footer>
    </div>

    <div class="container">
        <div class="upper"> 
            <h1>Registered User</h1>
            <div class="search-container">
                <input type="text" class="search-bar" id="searchInput" placeholder="Search users..." onkeyup="debouncedSearchUsers()">
                <span class="search-icon"><i class="fas fa-search"></i></span>
            </div>
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

        <div class="table-box glass-container">
            <?php if ($userType === 'admin'): ?>
                <div class="username">
                    Welcome, <?php echo htmlspecialchars($firstName); ?>!
                    <i class="fas fa-user-shield admin-icon"></i>
                </div>
            <?php endif; ?>
            
            <div class="tab-buttons">
                <button class="tab-btn active" onclick="showTab('active')">User (<?php echo $totalUsers; ?>)</button>
                <button class="tab-btn" onclick="showTab('archived')">
                    Archived
                    <?php if ($totalArchived > 0): ?>
                        <span class="tab-badge"><?php echo $totalArchived; ?></span>
                    <?php endif; ?>
                </button>
            </div>
            
            <div id="active-users-tab" class="active">
                <div>
                    <button class="add-user-btn" onclick="showAddUserModal()"><i class="fas fa-user-plus"></i> Add New User</button>
                </div>
                <table id="active-users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Firstname</th>
                            <th>Lastname</th>
                            <th>Email</th>
                            <th>Username</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="active-users-tbody">
                        <?php 
                        if ($activeAndPendingUsers->num_rows > 0) { 
                            while ($row = $activeAndPendingUsers->fetch_assoc()) { 
                                echo "<tr> 
                                        <td>{$row['u_id']}</td> 
                                        <td>" . htmlspecialchars($row['u_fname'], ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td>" . htmlspecialchars($row['u_lname'], ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td>" . htmlspecialchars($row['u_email'], ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td>" . htmlspecialchars($row['u_username'], ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td>" . ucfirst(strtolower($row['u_type'])) . "</td> 
                                        <td class='status-" . strtolower($row['u_status']) . "'>" . ucfirst(strtolower($row['u_status'])) . "</td>
                                        <td class='action-buttons'>
                                            <a class='view-btn' onclick=\"showViewModal('{$row['u_id']}', '" . htmlspecialchars($row['u_fname'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['u_lname'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['u_email'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['u_username'], ENT_QUOTES, 'UTF-8') . "', '{$row['u_type']}', '{$row['u_status']}')\" title='View'><i class='fas fa-eye'></i></a>
                                            <a class='edit-btn' onclick=\"showEditUserModal('{$row['u_id']}', '" . htmlspecialchars($row['u_fname'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['u_lname'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['u_email'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['u_username'], ENT_QUOTES, 'UTF-8') . "', '{$row['u_type']}', '{$row['u_status']}')\" title='Edit'><i class='fas fa-edit'></i></a>
                                            <a class='archive-btn' onclick=\"showArchiveModal('{$row['u_id']}', '" . htmlspecialchars($row['u_fname'] . ' ' . $row['u_lname'], ENT_QUOTES, 'UTF-8') . "')\" title='Archive'><i class='fas fa-archive'></i></a>
                                        </td>
                                    </tr>"; 
                            } 
                        } else { 
                            echo "<tr><td colspan='8' style='text-align: center;'>No active or pending users found.</td></tr>"; 
                        } 
                        ?>
                    </tbody>
                </table>

                <div class="pagination" id="active-users-pagination">
                    <?php if ($page > 1): ?>
                        <a href="?tab=active&page=<?php echo $page - 1; ?>" class="pagination-link"><i class="fas fa-chevron-left"></i></a>
                    <?php else: ?>
                        <span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>
                    <?php endif; ?>

                    <span class="current-page">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>

                    <?php if ($page < $totalPages): ?>
                        <a href="?tab=active&page=<?php echo $page + 1; ?>" class="pagination-link"><i class="fas fa-chevron-right"></i></a>
                    <?php else: ?>
                        <span class="pagination-link disabled"><i class="fas fa-chevron-right"></i></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div id="archived-users-tab">
                <div class="archive-header">
                    <?php if ($totalArchived > 0): ?>
                        <button class="restore-all-btn" onclick="showRestoreAllModal()"><i class="fas fa-box-open"></i> Restore All</button>
                    <?php endif; ?>
                </div>
                <table id="archived-users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Firstname</th>
                            <th>Lastname</th>
                            <th>Email</th>
                            <th>Username</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="archived-users-tbody">
                        <?php 
                        if ($archivedResult->num_rows > 0) { 
                            while ($row = $archivedResult->fetch_assoc()) { 
                                echo "<tr> 
                                        <td>{$row['u_id']}</td> 
                                        <td>" . htmlspecialchars($row['u_fname'], ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td>" . htmlspecialchars($row['u_lname'], ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td>" . htmlspecialchars($row['u_email'], ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td>" . htmlspecialchars($row['u_username'], ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td>" . ucfirst(strtolower($row['u_type'])) . "</td> 
                                        <td class='status-" . strtolower($row['u_status']) . "'>" . ucfirst(strtolower($row['u_status'])) . "</td>
                                        <td class='action-buttons'>
                                            <a class='view-btn' onclick=\"showViewModal('{$row['u_id']}', '" . htmlspecialchars($row['u_fname'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['u_lname'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['u_email'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['u_username'], ENT_QUOTES, 'UTF-8') . "', '{$row['u_type']}', '{$row['u_status']}')\" title='View'><i class='fas fa-eye'></i></a>
                                            <a class='unarchive-btn' onclick=\"showRestoreModal('{$row['u_id']}', '" . htmlspecialchars($row['u_fname'] . ' ' . $row['u_lname'], ENT_QUOTES, 'UTF-8') . "')\" title='Unarchive'><i class='fas fa-box-open'></i></a>
                                            <a class='delete-btn' onclick=\"showDeleteModal('{$row['u_id']}', '" . htmlspecialchars($row['u_fname'] . ' ' . $row['u_lname'], ENT_QUOTES, 'UTF-8') . "')\" title='Delete'><i class='fas fa-trash'></i></a>
                                        </td>
                                    </tr>"; 
                            } 
                        } else { 
                            echo "<tr><td colspan='8' style='text-align: center;'>No archived users found.</td></tr>"; 
                        } 
                        ?>
                    </tbody>
                </table>
                
                <?php if ($totalArchived > 0): ?>
                <div class="pagination" id="archived-users-pagination">
                    <?php if ($archivedPage > 1): ?>
                        <a href="?tab=archived&archived_page=<?php echo $archivedPage - 1; ?>" class="pagination-link"><i class="fas fa-chevron-left"></i></a>
                    <?php else: ?>
                        <span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>
                    <?php endif; ?>

                    <span class="current-page">Page <?php echo $archivedPage; ?> of <?php echo $totalArchivedPages; ?></span>

                    <?php if ($archivedPage < $totalArchivedPages): ?>
                        <a href="?tab=archived&archived_page=<?php echo $archivedPage + 1; ?>" class="pagination-link"><i class="fas fa-chevron-right"></i></a>
                    <?php else: ?>
                        <span class="pagination-link disabled"><i class="fas fa-chevron-right"></i></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- View User Modal -->
<div id="viewModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>User Details</h2>
        </div>
        <div id="viewContent"></div>
        <div class="modal-footer">
            <button class="modal-btn cancel" onclick="closeModal('viewModal')">Close</button>
        </div>
    </div>
</div>

<!-- Archive User Modal -->
<div id="archiveModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Archive User</h2>
        </div>
        <p>Are you sure you want to archive <span id="archiveUserName"></span>?</p>
        <form method="POST" id="archiveForm">
            <input type="hidden" name="u_id" id="archiveUserId">
            <input type="hidden" name="archive_user" value="1">
            <div class="modal-footer">
                <button type="button" class="modal-btn cancel" onclick="closeModal('archiveModal')">Cancel</button>
                <button type="submit" class="modal-btn confirm">Archive</button>
            </div>
        </form>
    </div>
</div>

<!-- Restore User Modal -->
<div id="restoreModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Unarchive User</h2>
        </div>
        <p>Are you sure you want to unarchive <span id="restoreUserName"></span>?</p>
        <form method="POST" id="restoreForm">
            <input type="hidden" name="u_id" id="restoreUserId">
            <input type="hidden" name="restore_user" value="1">
            <div class="modal-footer">
                <button type="button" class="modal-btn cancel" onclick="closeModal('restoreModal')">Cancel</button>
                <button type="submit" class="modal-btn confirm">Unarchive</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete User Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Delete User</h2>
        </div>
        <p>Are you sure you want to delete <span id="deleteUserName"></span> from the archive? This action cannot be undone.</p>
        <form method="POST" id="deleteForm">
            <input type="hidden" name="u_id" id="deleteUserId">
            <input type="hidden" name="delete_user" value="1">
            <div class="modal-footer">
                <button type="button" class="modal-btn cancel" onclick="closeModal('deleteModal')">Cancel</button>
                <button type="submit" class="modal-btn confirm">Delete</button>
            </div>
        </form>
    </div>
</div>

<!-- Restore All Users Modal -->
<div id="restoreAllModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Restore All Users</h2>
        </div>
        <p>Are you sure you want to restore all archived users?</p>
        <form method="POST" id="restoreAllForm">
            <input type="hidden" name="restore_all_users" value="1">
            <div class="modal-footer">
                <button type="button" class="modal-btn cancel" onclick="closeModal('restoreAllModal')">Cancel</button>
                <button type="submit" class="modal-btn confirm">Restore All</button>
            </div>
        </form>
    </div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add New User</h2>
        </div>
        <form method="POST" id="addUserForm" class="modal-form">
            <input type="hidden" name="add_user" value="1">
            <input type="hidden" name="ajax" value="true">
            <label for="add_firstname">First Name</label>
            <input type="text" name="firstname" id="add_firstname" required>
            <span class="error"><?php echo $firstnameErr; ?></span>
            <label for="add_lastname">Last Name</label>
            <input type="text" name="lastname" id="add_lastname" required>
            <span class="error"><?php echo $lastnameErr; ?></span>
            <label for="add_email">Email</label>
            <input type="email" name="email" id="add_email" required>
            <span class="error"><?php echo $emailErr; ?></span>
            <label for="add_username">Username</label>
            <input type="text" name="username" id="add_username" required>
            <span class="error"><?php echo $usernameErr; ?></span>
            <label for="add_password">Password</label>
            <input type="password" name="password" id="add_password" required>
            <span class="error"><?php echo $passwordErr; ?></span>
            <label for="add_type">Account Type</label>
            <select name="type" id="add_type" required>
                <option value="admin">Staff</option>
                <option value="user">Technician</option>
            </select>
            <label for="add_status">Account Status</label>
            <select name="status" id="add_status" required>
                <option value="active">Active</option>
                <option value="pending">Pending</option>
            </select>
            <div class="modal-footer">
                <button type="button" class="modal-btn cancel" onclick="closeModal('addUserModal')">Cancel</button>
                <button type="submit" class="modal-btn confirm">Add User</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit User</h2>
        </div>
        <form method="POST" id="editUserForm" class="modal-form">
            <input type="hidden" name="edit_user" value="1">
            <input type="hidden" name="ajax" value="true">
            <input type="hidden" name="u_id" id="edit_u_id">
            <label for="edit_firstname">First Name</label>
            <input type="text" name="firstname" id="edit_firstname" required>
            <label for="edit_lastname">Last Name</label>
            <input type="text" name="lastname" id="edit_lastname" required>
            <label for="edit_email">Email</label>
            <input type="email" name="email" id="edit_email" required>
            <label for="edit_username">Username</label>
            <input type="text" name="username" id="edit_username" required>
            <label for="edit_type">Type</label>
            <select name="type" id="edit_type" required>
                <option value="admin">Admin</option>
                <option value="user">User</option>
            </select>
            <label for="edit_status">Status</label>
            <select name="status" id="edit_status" required>
                <option value="active">Active</option>
                <option value="pending">Pending</option>
            </select>
            <div class="modal-footer">
                <button type="button" class="modal-btn cancel" onclick="closeModal('editUserModal')">Cancel</button>
                <button type="submit" class="modal-btn confirm">Update User</button>
            </div>
        </form>
    </div>
</div>

<script>
    let currentSearchPage = 1;
    let defaultPage = <?php echo json_encode($page); ?>;
    let defaultArchivedPage = <?php echo json_encode($archivedPage); ?>;

    // Debounce function to limit search calls
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    function showTab(tabName) {
        const activeTab = document.getElementById('active-users-tab');
        const archivedTab = document.getElementById('archived-users-tab');
        const buttons = document.querySelectorAll('.tab-btn');

        activeTab.classList.remove('active');
        archivedTab.classList.remove('active');
        buttons.forEach(btn => btn.classList.remove('active'));

        if (tabName === 'active') {
            activeTab.classList.add('active');
            buttons[0].classList.add('active');
            history.pushState(null, '', '?tab=active&page=' + defaultPage);
        } else if (tabName === 'archived') {
            archivedTab.classList.add('active');
            buttons[1].classList.add('active');
            history.pushState(null, '', '?tab=archived&archived_page=' + defaultArchivedPage);
        }
        searchUsers();
    }

    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab') || 'active';
        showTab(tab);

        // Alert handling
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.classList.add('alert-hidden');
                setTimeout(() => alert.remove(), 500);
            }, 2000);
        });
    });

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    function showViewModal(id, fname, lname, email, username, type, status) {
        document.getElementById('viewContent').innerHTML = `
            <p><strong>ID:</strong> ${id}</p>
            <p><strong>First Name:</strong> ${fname}</p>
            <p><strong>Last Name:</strong> ${lname}</p>
            <p><strong>Email:</strong> ${email}</p>
            <p><strong>Username:</strong> ${username}</p>
            <p><strong>Type:</strong> ${type}</p>
            <p><strong>Status:</strong> ${status}</p>
        `;
        document.getElementById('viewModal').style.display = 'block';
    }

    function showArchiveModal(id, name) {
        document.getElementById('archiveUserId').value = id;
        document.getElementById('archiveUserName').innerText = name;
        document.getElementById('archiveModal').style.display = 'block';
    }

    function showRestoreModal(id, name) {
        document.getElementById('restoreUserId').value = id;
        document.getElementById('restoreUserName').innerText = name;
        document.getElementById('restoreModal').style.display = 'block';
    }

    function showDeleteModal(id, name) {
        document.getElementById('deleteUserId').value = id;
        document.getElementById('deleteUserName').innerText = name;
        document.getElementById('deleteModal').style.display = 'block';
    }

    function showRestoreAllModal() {
        document.getElementById('restoreAllModal').style.display = 'block';
    }

    function showAddUserModal() {
        document.getElementById('addUserForm').reset();
        document.getElementById('addUserModal').style.display = 'block';
    }

    function showEditUserModal(id, fname, lname, email, username, type, status) {
        document.getElementById('edit_u_id').value = id;
        document.getElementById('edit_firstname').value = fname;
        document.getElementById('edit_lastname').value = lname;
        document.getElementById('edit_email').value = email;
        document.getElementById('edit_username').value = username;
        document.getElementById('edit_type').value = type.toLowerCase();
        document.getElementById('edit_status').value = status.toLowerCase();
        document.getElementById('editUserModal').style.display = 'block';
    }

    function updatePagination(currentPage, totalPages, tab, searchTerm) {
        const paginationContainer = tab === 'active' ? document.getElementById('active-users-pagination') : document.getElementById('archived-users-pagination');
        let paginationHtml = '';

        if (currentPage > 1) {
            paginationHtml += `<a href="javascript:searchUsers(${currentPage - 1})" class="pagination-link"><i class="fas fa-chevron-left"></i></a>`;
        } else {
            paginationHtml += `<span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>`;
        }

        paginationHtml += `<span class="current-page">Page ${currentPage} of ${totalPages}</span>`;

        if (currentPage < totalPages) {
            paginationHtml += `<a href="javascript:searchUsers(${currentPage + 1})" class="pagination-link"><i class="fas fa-chevron-right"></i></a>`;
        } else {
            paginationHtml += `<span class="pagination-link disabled"><i class="fas fa-chevron-right"></i></span>`;
        }

        paginationContainer.innerHTML = paginationHtml;
    }

    function searchUsers(page = 1) {
        const searchTerm = document.getElementById('searchInput').value;
        const activeTab = document.querySelector('.tab-btn.active').textContent.toLowerCase();
        const tab = activeTab.includes('user') ? 'active' : 'archived';
        const tbody = tab === 'active' ? document.getElementById('active-users-tbody') : document.getElementById('archived-users-tbody');
        const defaultPageToUse = tab === 'active' ? defaultPage : defaultArchivedPage;

        currentSearchPage = page;

        const xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    tbody.innerHTML = response.html;
                    updatePagination(response.currentPage, response.totalPages, response.tab, response.searchTerm);
                } catch (e) {
                    console.error('Error parsing JSON:', e, xhr.responseText);
                }
            }
        };
        xhr.open('GET', `viewU.php?action=search&search=${encodeURIComponent(searchTerm)}&tab=${tab}&search_page=${searchTerm ? page : defaultPageToUse}`, true);
        xhr.send();
    }

    // Handle Add User form submission via AJAX
    document.getElementById('addUserForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'viewU.php', true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                const alertContainer = document.querySelector('.alert-container');
                const alert = document.createElement('div');
                alert.className = `alert ${response.success ? 'alert-success' : 'alert-error'}`;
                alert.textContent = response.message;
                alertContainer.appendChild(alert);
                setTimeout(() => {
                    alert.classList.add('alert-hidden');
                    setTimeout(() => alert.remove(), 500);
                }, 2000);
                if (response.success) {
                    closeModal('addUserModal');
                    searchUsers(defaultPage);
                }
            }
        };
        xhr.send(formData);
    });

    // Handle Edit User form submission via AJAX
    document.getElementById('editUserForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'viewU.php', true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                const alertContainer = document.querySelector('.alert-container');
                const alert = document.createElement('div');
                alert.className = `alert ${response.success ? 'alert-success' : 'alert-error'}`;
                alert.textContent = response.message;
                alertContainer.appendChild(alert);
                setTimeout(() => {
                    alert.classList.add('alert-hidden');
                    setTimeout(() => alert.remove(), 500);
                }, 2000);
                if (response.success) {
                    closeModal('editUserModal');
                    searchUsers(defaultPage);
                }
            }
        };
        xhr.send(formData);
    });

    // Debounced search function
    const debouncedSearchUsers = debounce(searchUsers, 300);
</script>
</body>
</html>

<?php
$conn->close();
?>