
<?php
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Initialize variables
$firstName = '';
$lastName = '';
$userType = '';
$avatarPath = 'default-avatar.png';
$avatarFolder = 'Uploads/avatars/';
$userAvatar = $avatarFolder . $_SESSION['username'] . '.png';

if (file_exists($userAvatar)) {
    $_SESSION['avatarPath'] = $userAvatar . '?' . time();
} else {
    $_SESSION['avatarPath'] = 'default-avatar.png';
}
$avatarPath = $_SESSION['avatarPath'];

// Fetch user data
$sqlUser = "SELECT u_fname, u_lname, u_type FROM tbl_user WHERE u_username = ?";
$stmt = $conn->prepare($sqlUser);
if (!$stmt) {
    error_log("Prepare failed for user details: " . $conn->error);
    $_SESSION['error'] = "Database error fetching user details.";
    header("Location: index.php");
    exit();
}
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$resultUser = $stmt->get_result();
if ($resultUser->num_rows > 0) {
    $row = $resultUser->fetch_assoc();
    $firstName = $row['u_fname'] ?: 'Unknown';
    $lastName = $row['u_lname'] ?: '';
    $userType = strtolower($row['u_type']) ?: 'staff';
    error_log("User fetched: username={$_SESSION['username']}, userType=$userType");
    
    // Log staff login if userType is staff and not already logged for this session
    if ($userType === 'staff' && !isset($_SESSION['login_logged'])) {
        $logDescription = "Staff $firstName has successfully logged in";
        $sqlLog = "INSERT INTO tbl_logs (l_stamp, l_description) VALUES (NOW(), ?)";
        $stmtLog = $conn->prepare($sqlLog);
        $stmtLog->bind_param("s", $logDescription);
        $stmtLog->execute();
        $stmtLog->close();
        $_SESSION['login_logged'] = true; // Prevent multiple login logs
    }
} else {
    error_log("User not found for username: {$_SESSION['username']}");
    $_SESSION['error'] = "User not found.";
    header("Location: index.php");
    exit();
}
$stmt->close();

// Initialize variables for add ticket validation
$accountname = $subject = $issuedetails = $dob = $issuetype = $ticketstatus = "";
$accountnameErr = $subjectErr = $issuedetailsErr = $dobErr = $issuetypeError = $ticketstatusErr = "";
$hasError = false;

// Check for pre-filled account name from query parameter
if (isset($_GET['aname']) && !empty($_GET['aname'])) {
    $accountname = urldecode(trim($_GET['aname']));
    // Validate account name against tbl_customer
    $nameParts = explode(" ", $accountname);
    if (count($nameParts) >= 2) {
        $firstNameCustomer = $nameParts[0];
        $lastNameCustomer = implode(" ", array_slice($nameParts, 1)); // Handle multi-word last names

        $sql = "SELECT COUNT(*) FROM tbl_customer WHERE c_fname = ? AND c_lname = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ss", $firstNameCustomer, $lastNameCustomer);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();
            if ($count == 0) {
                $accountnameErr = "Account Name does not exist in customer database.";
                $accountname = ""; // Clear invalid account name
            }
        } else {
            $accountnameErr = "Database error: Unable to validate account name.";
            $accountname = "";
        }
    } else {
        $accountnameErr = "Account Name must consist of first and last name.";
        $accountname = "";
    }
}

// Handle AJAX search request
if (isset($_GET['action']) && $_GET['action'] === 'search' && isset($_GET['search']) && isset($_GET['tab'])) {
    header('Content-Type: application/json');
    $searchTerm = trim($_GET['search']);
    $tab = $_GET['tab'] === 'archived' ? 'archived' : 'active';
    $page = isset($_GET['search_page']) ? max(1, (int)$_GET['search_page']) : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    $output = '';

    if ($tab === 'active') {
        $statusCondition = "t_status != 'archived'";
    } else {
        $statusCondition = "t_status = 'archived'";
    }

    if ($searchTerm === '') {
        // Fetch default tickets for the current page
        $countSql = "SELECT COUNT(*) as total FROM tbl_ticket WHERE $statusCondition";
        $countResult = $conn->query($countSql);
        $totalRecords = $countResult->fetch_assoc()['total'];
        $totalPages = ceil($totalRecords / $limit);

        $sql = "SELECT t_id, t_aname, t_subject, t_status, t_details, t_date 
                FROM tbl_ticket 
                WHERE $statusCondition 
                LIMIT ?, ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $offset, $limit);
    } else {
        // Count total matching records for pagination
        $countSql = "SELECT COUNT(*) as total FROM tbl_ticket 
                     WHERE $statusCondition AND (t_aname LIKE ? OR t_subject LIKE ? OR t_status LIKE ? OR t_details LIKE ? OR t_date LIKE ?)";
        $countStmt = $conn->prepare($countSql);
        $searchWildcard = "%$searchTerm%";
        $countStmt->bind_param("sssss", $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $totalRecords = $countResult->fetch_assoc()['total'];
        $countStmt->close();

        $totalPages = ceil($totalRecords / $limit);

        // Fetch paginated search results
        $sql = "SELECT t_id, t_aname, t_subject, t_status, t_details, t_date 
                FROM tbl_ticket 
                WHERE $statusCondition AND (t_aname LIKE ? OR t_subject LIKE ? OR t_status LIKE ? OR t_details LIKE ? OR t_date LIKE ?)
                LIMIT ?, ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssii", $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard, $offset, $limit);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $statusClass = 'status-' . strtolower($row['t_status']);
            $isClickable = ($userType === 'technician' && strtolower($row['t_status']) === 'open' && $tab === 'active');
            $clickableAttr = $isClickable ? " status-clickable' onclick=\"showCloseModal('{$row['t_id']}', '" . htmlspecialchars($row['t_aname'], ENT_QUOTES, 'UTF-8') . "', '{$row['t_status']}')\"" : "'";

            $output .= "<tr> 
                          <td>{$row['t_id']}</td> 
                          <td>" . htmlspecialchars($row['t_aname'], ENT_QUOTES, 'UTF-8') . "</td> 
                          <td>" . htmlspecialchars($row['t_subject'], ENT_QUOTES, 'UTF-8') . "</td> 
                          <td class='$statusClass$clickableAttr>" . ucfirst(strtolower($row['t_status'])) . "</td>
                          <td>" . htmlspecialchars($row['t_details'], ENT_QUOTES, 'UTF-8') . "</td>
                          <td>" . htmlspecialchars($row['t_date'], ENT_QUOTES, 'UTF-8') . "</td> 
                          <td class='action-buttons'>";
            if ($userType !== 'technician') {
                if ($tab === 'active') {
                    $output .= "<a class='view-btn' href='#' title='View'><i class='fas fa-eye'></i></a>
                                <a class='edit-btn' onclick=\"showEditTicketModal('{$row['t_id']}', '" . htmlspecialchars($row['t_aname'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['t_subject'], ENT_QUOTES, 'UTF-8') . "', '{$row['t_status']}', '" . htmlspecialchars($row['t_details'], ENT_QUOTES, 'UTF-8') . "', '{$row['t_date']}')\" title='Edit'><i class='fas fa-edit'></i></a>
                                <a class='archive-btn' onclick=\"showArchiveModal('{$row['t_id']}', '" . htmlspecialchars($row['t_aname'], ENT_QUOTES, 'UTF-8') . "')\" title='Archive'><i class='fas fa-archive'></i></a>";
                } else {
                    $output .= "<a class='view-btn' href='#' title='View'><i class='fas fa-eye'></i></a>
                                <a class='restore-btn' onclick=\"showRestoreModal('{$row['t_id']}', '" . htmlspecialchars($row['t_aname'], ENT_QUOTES, 'UTF-8') . "')\" title='Unarchive'><i class='fas fa-box-open'></i></a>
                                <a class='delete-btn' onclick=\"showDeleteModal('{$row['t_id']}', '" . htmlspecialchars($row['t_aname'], ENT_QUOTES, 'UTF-8') . "')\" title='Delete'><i class='fas fa-trash'></i></a>";
                }
            } else {
                error_log("Rendering disabled buttons for technician: t_id={$row['t_id']}, tab=$tab");
                if ($tab === 'active') {
                    $output .= "<a class='view-btn' href='#' title='View'><i class='fas fa-eye'></i></a>
                                <a class='edit-btn disabled' onclick='showRestrictedMessage()' title='Edit'><i class='fas fa-edit'></i></a>
                                <a class='archive-btn disabled' onclick='showRestrictedMessage()' title='Archive'><i class='fas fa-archive'></i></a>";
                } else {
                    $output .= "<a class='view-btn' href='#' title='View'><i class='fas fa-eye'></i></a>
                                <a class='restore-btn disabled' onclick='showRestrictedMessage()' title='Unarchive'><i class='fas fa-box-open'></i></a>
                                <a class='delete-btn disabled' onclick='showRestrictedMessage()' title='Delete'><i class='fas fa-trash'></i></a>";
                }
            }
            $output .= "</td></tr>";
        }
    } else {
        $output = "<tr><td colspan='7' style='text-align: center;'>No tickets found.</td></tr>";
    }
    $stmt->close();

    $response = [
        'html' => $output,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'tab' => $tab,
        'searchTerm' => $searchTerm
    ];
    echo json_encode($response);
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pageActive = isset($_GET['page_active']) ? (int)$_GET['page_active'] : 1;
    $pageArchived = isset($_GET['page_archived']) ? (int)$_GET['page_archived'] : 1;
    $tab = isset($_GET['tab']) ? $_GET['tab'] : 'active';

    if (isset($_POST['add_ticket']) && $userType !== 'technician') {
        $accountname = trim($_POST['account_name']);
        $subject = trim($_POST['ticket_subject']);
        $issuedetails = trim($_POST['ticket_details']);
        $ticketstatus = 'Open'; // Hardcode as Open for new tickets
        $dob = trim($_POST['date']);

        // Validate account name (no numbers, must have first and last name)
        if (empty($accountname)) {
            $accountnameErr = "Account Name is required.";
            $hasError = true;
        } elseif (!preg_match("/^[a-zA-Z\s-]+$/", $accountname)) {
            $accountnameErr = "Account Name should not contain numbers or special characters.";
            $hasError = true;
        } else {
            $nameParts = explode(" ", $accountname);
            if (count($nameParts) < 2) {
                $accountnameErr = "Account Name must consist of first and foremost.";
                $hasError = true;
            } else {
                $firstNameCustomer = $nameParts[0];
                $lastNameCustomer = implode(" ", array_slice($nameParts, 1));

                $sql = "SELECT COUNT(*) FROM tbl_customer WHERE c_fname = ? AND c_lname = ?";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    if (isset($_POST['ajax']) && $_POST['ajax'] == 'true') {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'errors' => ['general' => 'Prepare failed: ' . $conn->error]]);
                        exit();
                    }
                    die("Prepare failed: " . $conn->error);
                }
                $stmt->bind_param("ss", $firstNameCustomer, $lastNameCustomer);
                $stmt->execute();
                $stmt->bind_result($count);
                $stmt->fetch();
                $stmt->close();

                if ($count == 0) {
                    $accountnameErr = "Account Name does not exist in customer database.";
                    $hasError = true;
                }
            }
        }

        // Validate subject (no numbers)
        if (empty($subject)) {
            $subjectErr = "Subject is required.";
            $hasError = true;
        } elseif (!preg_match("/^[a-zA-Z\s-]+$/", $subject)) {
            $subjectErr = "Subject should not contain numbers or special characters.";
            $hasError = true;
        }

        // Validate other required fields
        if (empty($issuedetails)) {
            $issuedetailsErr = "Ticket Details are required.";
            $hasError = true;
        }
        if (empty($dob)) {
            $dobErr = "Date Issued is required.";
            $hasError = true;
        }

        // Insert into database if no errors
        if (!$hasError) {
            $sql = "INSERT INTO tbl_ticket (t_aname, t_details, t_subject, t_status, t_date) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                if (isset($_POST['ajax']) && $_POST['ajax'] == 'true') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'errors' => ['general' => 'Prepare failed: ' . $conn->error]]);
                    exit();
                }
                die("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("sssss", $accountname, $issuedetails, $subject, $ticketstatus, $dob);
            if ($stmt->execute()) {
                // Log add action for staff
                if ($userType === 'staff') {
                    $logDescription = "Staff $firstName added ticket for $accountname";
                    $sqlLog = "INSERT INTO tbl_logs (l_stamp, l_description) VALUES (NOW(), ?)";
                    $stmtLog = $conn->prepare($sqlLog);
                    $stmtLog->bind_param("s", $logDescription);
                    $stmtLog->execute();
                    $stmtLog->close();
                }
                if (isset($_POST['ajax']) && $_POST['ajax'] == 'true') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Ticket has been registered successfully.']);
                    exit();
                }
                $_SESSION['message'] = "Ticket has been registered successfully.";
            } else {
                if (isset($_POST['ajax']) && $_POST['ajax'] == 'true') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'errors' => ['general' => 'Execution failed: ' . $stmt->error]]);
                    exit();
                }
                die("Execution failed: " . $stmt->error);
            }
            $stmt->close();
        } else {
            if (isset($_POST['ajax']) && $_POST['ajax'] == 'true') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'errors' => [
                        'account_name' => $accountnameErr,
                        'ticket_subject' => $subjectErr,
                        'ticket_details' => $issuedetailsErr,
                        'date' => $dobErr
                    ]
                ]);
                exit();
            }
        }
    } elseif (isset($_POST['edit_ticket']) && $userType !== 'technician') {
        $ticketId = (int)$_POST['t_id'];
        $accountName = trim($_POST['account_name']);
        $subject = trim($_POST['ticket_subject']);
        $ticketStatus = trim($_POST['ticket_status']); // Fetched from hidden input
        $ticketDetails = trim($_POST['ticket_details']);
        $dateIssued = trim($_POST['date']);
        $errors = [];

        // Fetch current ticket details
        $sql = "SELECT t_id, t_aname, t_subject, t_status, t_details, t_date FROM tbl_ticket WHERE t_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $ticketId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $ticket = $result->fetch_assoc();
        } else {
            if (isset($_POST['ajax']) && $_POST['ajax'] == 'true') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'errors' => ['general' => 'Ticket not found.']]);
                exit();
            }
            $_SESSION['error'] = "Ticket not found.";
            header("Location: staffD.php?tab=$tab&page_active=$pageActive&page_archived=$pageArchived");
            exit();
        }
        $stmt->close();

        // Validation
        if (empty($accountName)) {
            $errors['account_name'] = "Account Name is required.";
        } elseif (!preg_match("/^[a-zA-Z\s-]+$/", $accountName)) {
            $errors['account_name'] = "Account Name should not contain numbers or special characters.";
        } else {
            $nameParts = explode(" ", $accountName);
            if (count($nameParts) < 2) {
                $errors['account_name'] = "Account Name must consist of first and last name.";
            } else {
                $firstNameCustomer = $nameParts[0];
                $lastNameCustomer = implode(" ", array_slice($nameParts, 1));
                $sql = "SELECT COUNT(*) FROM tbl_customer WHERE c_fname = ? AND c_lname = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss", $firstNameCustomer, $lastNameCustomer);
                $stmt->execute();
                $stmt->bind_result($count);
                $stmt->fetch();
                $stmt->close();
                if ($count == 0) {
                    $errors['account_name'] = "Account Name does not exist in customer database.";
                }
            }
        }

        if (empty($subject)) {
            $errors['ticket_subject'] = "Subject is required.";
        } elseif (!preg_match("/^[a-zA-Z\s-]+$/", $subject)) {
            $errors['ticket_subject'] = "Subject should not contain numbers or special characters.";
        }

        if (empty($ticketDetails)) {
            $errors['ticket_details'] = "Ticket Details are required.";
        }

        if (empty($dateIssued)) {
            $errors['date'] = "Date Issued is required.";
        }

        if (empty($errors)) {
            // Check for changes in account_name, subject, and ticket_details
            $logParts = [];
            if ($accountName !== $ticket['t_aname']) {
                $logParts[] = "account name";
            }
            if ($subject !== $ticket['t_subject']) {
                $logParts[] = "subject";
            }
            if ($ticketDetails !== $ticket['t_details']) {
                $logParts[] = "ticket details";
            }

            // Update the ticket in the database
            $sqlUpdate = "UPDATE tbl_ticket SET t_aname = ?, t_subject = ?, t_status = ?, t_details = ?, t_date = ? WHERE t_id = ?";
            $stmtUpdate = $conn->prepare($sqlUpdate);
            if (!$stmtUpdate) {
                if (isset($_POST['ajax']) && $_POST['ajax'] == 'true') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'errors' => ['general' => 'Prepare failed: ' . $conn->error]]);
                    exit();
                }
                die("Prepare failed: " . $conn->error);
            }
            $stmtUpdate->bind_param("sssssi", $accountName, $subject, $ticketStatus, $ticketDetails, $dateIssued, $ticketId);

            if ($stmtUpdate->execute()) {
                // Log changes if any, only for staff
                if ($userType === 'staff' && !empty($logParts)) {
                    $logDescription = "Staff $firstName edited ticket ID $ticketId " . implode(" and ", $logParts);
                    $sqlLog = "INSERT INTO tbl_logs (l_stamp, l_description) VALUES (NOW(), ?)";
                    $stmtLog = $conn->prepare($sqlLog);
                    $stmtLog->bind_param("s", $logDescription);
                    $stmtLog->execute();
                    $stmtLog->close();
                }
                if (isset($_POST['ajax']) && $_POST['ajax'] == 'true') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Ticket updated successfully!']);
                    exit();
                }
                $_SESSION['message'] = "Ticket updated successfully!";
            } else {
                if (isset($_POST['ajax']) && $_POST['ajax'] == 'true') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'errors' => ['general' => 'Error updating ticket: ' . $stmtUpdate->error]]);
                    exit();
                }
                $_SESSION['error'] = "Error updating ticket: " . $stmtUpdate->error;
            }
            $stmtUpdate->close();
        } else {
            if (isset($_POST['ajax']) && $_POST['ajax'] == 'true') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'errors' => $errors]);
                exit();
            }
            $_SESSION['error'] = implode(", ", $errors);
        }
    } elseif (isset($_POST['archive_ticket']) && $userType !== 'technician') {
        $t_id = $_POST['t_id'];
        $sql = "UPDATE tbl_ticket SET t_status='archived' WHERE t_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $t_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Ticket archived successfully!";
            // Log archive action for staff
            if ($userType === 'staff') {
                $logDescription = "Staff $firstName archived ticket ID $t_id";
                $sqlLog = "INSERT INTO tbl_logs (l_stamp, l_description) VALUES (NOW(), ?)";
                $stmtLog = $conn->prepare($sqlLog);
                $stmtLog->bind_param("s", $logDescription);
                $stmtLog->execute();
                $stmtLog->close();
            }
        } else {
            $_SESSION['error'] = "Error archiving ticket: " . $stmt->error;
            error_log("Error archiving ticket: " . $stmt->error);
        }
        $stmt->close();
    } elseif (isset($_POST['restore_ticket']) && $userType !== 'technician') {
        $t_id = $_POST['t_id'];
        $sql = "UPDATE tbl_ticket SET t_status='open' WHERE t_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $t_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Ticket restored successfully!";
            // Log restore action for staff
            if ($userType === 'staff') {
                $logDescription = "Staff $firstName unarchived ticket ID $t_id";
                $sqlLog = "INSERT INTO tbl_logs (l_stamp, l_description) VALUES (NOW(), ?)";
                $stmtLog = $conn->prepare($sqlLog);
                $stmtLog->bind_param("s", $logDescription);
                $stmtLog->execute();
                $stmtLog->close();
            }
        } else {
            $_SESSION['error'] = "Error restoring ticket: " . $stmt->error;
            error_log("Error restoring ticket: " . $stmt->error);
        }
        $stmt->close();
        $tab = 'active';
    } elseif (isset($_POST['close_ticket']) && $userType === 'technician') {
        $t_id = $_POST['t_id'];
        // Fetch ticket details to get t_aname
        $sqlTicket = "SELECT t_aname FROM tbl_ticket WHERE t_id = ?";
        $stmtTicket = $conn->prepare($sqlTicket);
        $stmtTicket->bind_param("i", $t_id);
        $stmtTicket->execute();
        $resultTicket = $stmtTicket->get_result();
        $ticket = $resultTicket->fetch_assoc();
        $stmtTicket->close();

        if ($ticket) {
            $t_aname = $ticket['t_aname'];
            // Try to fetch user details from tbl_user
            $sqlUserCheck = "SELECT u_fname, u_lname FROM tbl_user WHERE u_username = ?";
            $stmtUserCheck = $conn->prepare($sqlUserCheck);
            $stmtUserCheck->bind_param("s", $t_aname);
            $stmtUserCheck->execute();
            $resultUserCheck = $stmtUserCheck->get_result();
            if ($resultUserCheck->num_rows > 0) {
                $user = $resultUserCheck->fetch_assoc();
                $userFirstName = $user['u_fname'];
                $userLastName = $user['u_lname'];
            } else {
                // If not found in tbl_user, try tbl_customer
                $sqlCustomer = "SELECT c_fname, c_lname FROM tbl_customer WHERE CONCAT(c_fname, ' ', c_lname) = ?";
                $stmtCustomer = $conn->prepare($sqlCustomer);
                $stmtCustomer->bind_param("s", $t_aname);
                $stmtCustomer->execute();
                $resultCustomer = $stmtCustomer->get_result();
                if ($resultCustomer->num_rows > 0) {
                    $customer = $resultCustomer->fetch_assoc();
                    $userFirstName = $customer['c_fname'];
                    $userLastName = $customer['c_lname'];
                } else {
                    $userFirstName = $t_aname;
                    $userLastName = '';
                }
                $stmtCustomer->close();
            }
            $stmtUserCheck->close();

            // Update ticket status
            $sql = "UPDATE tbl_ticket SET t_status='closed' WHERE t_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $t_id);
            if ($stmt->execute()) {
                $_SESSION['message'] = "Ticket closed successfully!";
                // Log the action
                $logDescription = "Technician closed ticket ID $t_id for user $userFirstName $userLastName";
                $sqlLog = "INSERT INTO tbl_logs (l_stamp, l_description) VALUES (NOW(), ?)";
                $stmtLog = $conn->prepare($sqlLog);
                $stmtLog->bind_param("s", $logDescription);
                $stmtLog->execute();
                $stmtLog->close();
            } else {
                $_SESSION['error'] = "Error closing ticket: " . $stmt->error;
                error_log("Error closing ticket: " . $stmt->error);
            }
            $stmt->close();
        } else {
            $_SESSION['error'] = "Ticket not found.";
            error_log("Ticket not found for t_id: $t_id");
        }
        $tab = 'active';
    } elseif (isset($_POST['delete_ticket']) && $userType !== 'technician') {
        $t_id = $_POST['t_id'];
        $sql = "DELETE FROM tbl_ticket WHERE t_id=? AND t_status='archived'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $t_id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $_SESSION['message'] = "Ticket deleted successfully!";
                // Log delete action for staff
                if ($userType === 'staff') {
                    $logDescription = "Staff $firstName deleted ticket ID $t_id";
                    $sqlLog = "INSERT INTO tbl_logs (l_stamp, l_description) VALUES (NOW(), ?)";
                    $stmtLog = $conn->prepare($sqlLog);
                    $stmtLog->bind_param("s", $logDescription);
                    $stmtLog->execute();
                    $stmtLog->close();
                }
            } else {
                $_SESSION['error'] = "Ticket not found or not archived.";
                error_log("Ticket not found or not archived for t_id: $t_id");
            }
        } else {
            $_SESSION['error'] = "Error deleting ticket: " . $stmt->error;
            error_log("Error deleting ticket: " . $stmt->error);
        }
        $stmt->close();
        $tab = 'archived';
    }

    // Redirect if not AJAX
    if (!(isset($_POST['ajax']) && $_POST['ajax'] == 'true')) {
        header("Location: staffD.php?tab=$tab&page_active=$pageActive&page_archived=$pageArchived");
        exit();
    }
}

// Pagination setup
$limit = 10;
// Active tickets
$pageActive = isset($_GET['page_active']) ? max(1, (int)$_GET['page_active']) : 1;
$offsetActive = ($pageActive - 1) * $limit;
$totalActiveQuery = "SELECT COUNT(*) AS total FROM tbl_ticket WHERE t_status != 'archived'";
$totalActiveResult = $conn->query($totalActiveQuery);
$totalActiveRow = $totalActiveResult->fetch_assoc();
$totalActive = $totalActiveRow['total'];
$totalActivePages = ceil($totalActive / $limit);

// Archived tickets
$pageArchived = isset($_GET['page_archived']) ? max(1, (int)$_GET['page_archived']) : 1;
$offsetArchived = ($pageArchived - 1) * $limit;
$totalArchivedQuery = "SELECT COUNT(*) AS total FROM tbl_ticket WHERE t_status = 'archived'";
$totalArchivedResult = $conn->query($totalArchivedQuery);
$totalArchivedRow = $totalArchivedResult->fetch_assoc();
$totalArchived = $totalArchivedRow['total'];
$totalArchivedPages = ceil($totalArchived / $limit);

// Fetch active tickets
$sqlActive = "SELECT t_id, t_aname, t_subject, t_status, t_details, t_date 
              FROM tbl_ticket WHERE t_status != 'archived' LIMIT ?, ?";
$stmtActive = $conn->prepare($sqlActive);
$stmtActive->bind_param("ii", $offsetActive, $limit);
$stmtActive->execute();
$resultActive = $stmtActive->get_result();
$stmtActive->close();

// Fetch archived tickets
$sqlArchived = "SELECT t_id, t_aname, t_subject, t_status, t_details, t_date 
                FROM tbl_ticket WHERE t_status = 'archived' LIMIT ?, ?";
$stmtArchived = $conn->prepare($sqlArchived);
$stmtArchived->bind_param("ii", $offsetArchived, $limit);
$stmtArchived->execute();
$resultArchived = $stmtArchived->get_result();
$stmtArchived->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard | Ticket Reports</title>
    <link rel="stylesheet" href="staffD.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&display=swap" rel="stylesheet">
</head>
<body>
<div class="wrapper">
    <div class="sidebar glass-container">
        <h2><img src="image/logo.png" alt="Tix Net Icon" class="sidebar-icon">TixNet Pro</h2>
        <ul>
            <li><a href="staffD.php" class="active"><img src="https://img.icons8.com/plasticine/100/ticket.png" alt="ticket"/><span>View Tickets</span></a></li>
            <li><a href="assetsT.php"><img src="https://img.icons8.com/matisse/100/view.png" alt="view"/><span>View Assets</span></a></li>
            <li>
                <?php if ($userType !== 'technician'): ?>
                    <a href="customersT.php"><img src="https://img.icons8.com/color/48/conference-skin-type-7.png" alt="conference-skin-type-7"/><span>View Customers</span></a>
                <?php else: ?>
                    <a href="#" class="disabled" onclick="showRestrictedMessage()"><img src="https://img.icons8.com/color/48/conference-skin-type-7.png" alt="conference-skin-type-7"/><span>View Customers</span></a>
                <?php endif; ?>
            </li>
            <li>
                <?php if ($userType !== 'technician'): ?>
                    <a href="registerAssets.php"><img src="https://img.icons8.com/fluency/30/insert.png" alt="insert"/><span>Register Assets</span></a>
                <?php else: ?>
                    <a href="#" class="disabled" onclick="showRestrictedMessage()"><img src="https://img.icons8.com/fluency/30/insert.png" alt="insert"/><span>Register Assets</span></a>
                <?php endif; ?>
            </li>
            <li>
                <?php if ($userType !== 'technician'): ?>
                    <a href="addC.php"><img src="https://img.icons8.com/officel/40/add-user-male.png" alt="add-user-male"/><span>Add Customer</span></a>
                <?php else: ?>
                    <a href="#" class="disabled" onclick="showRestrictedMessage()"><img src="https://img.icons8.com/officel/40/add-user-male.png" alt="add-user-male"/><span>Add Customer</span></a>
                <?php endif; ?>
            </li>
        </ul>
        <footer>
            <a href="index.php" class="back-home"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </footer>
    </div>

    <div class="container">
        <div class="upper">
            <h1>Ticket Reports</h1>
            <div class="search-container">
                <input type="text" class="search-bar" id="searchInput" placeholder="Search tickets..." onkeyup="debouncedSearchTickets()">
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
                <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['message'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['message']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?></div>
            <?php endif; ?>
        </div>

        <div class="table-box glass-container">
            <?php if ($userType === 'staff' || $userType === 'technician'): ?>
                <div class="username">
                    Welcome to TIMS, <?php echo htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8'); ?>!
                    <i class="fas fa-user-shield admin-icon"></i>
                </div>
            <?php endif; ?>

            <div class="active-tickets <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'active') || !isset($_GET['tab']) ? 'active' : ''; ?>">
                <div class="tab-buttons">
                    <button class="tab-btn" onclick="showTab('active')">Active (<?php echo $totalActive; ?>)</button>
                    <button class="tab-btn" onclick="showTab('archived')">
                        Archived
                        <?php if ($totalArchived > 0): ?>
                            <span class="tab-badge"><?php echo $totalArchived; ?></span>
                        <?php endif; ?>
                    </button>
                </div>
                <?php if ($userType !== 'technician'): ?>
                    <button class="add-user-btn" onclick="showAddTicketModal()"><i class="fas fa-ticket-alt"></i> Add New Ticket</button>
                <?php else: ?>
                    <button class="add-user-btn disabled" onclick="showRestrictedMessage()"><i class="fas fa-ticket-alt"></i> Add New Ticket</button>
                <?php endif; ?>
                <table id="active-tickets-table">
                    <thead>
                        <tr>
                            <th>Ticket ID</th>
                            <th>Account Name</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Ticket Details</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="active-table-body">
                        <?php
                        if ($resultActive->num_rows > 0) {
                            while ($row = $resultActive->fetch_assoc()) {
                                $statusClass = 'status-' . strtolower($row['t_status']);
                                $isClickable = ($userType === 'technician' && strtolower($row['t_status']) === 'open');
                                $clickableAttr = $isClickable ? " status-clickable' onclick=\"showCloseModal('{$row['t_id']}', '" . htmlspecialchars($row['t_aname'], ENT_QUOTES, 'UTF-8') . "', '{$row['t_status']}')\"" : "'";

                                echo "<tr> 
                                        <td>{$row['t_id']}</td> 
                                        <td>" . htmlspecialchars($row['t_aname'], ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td>" . htmlspecialchars($row['t_subject'], ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td class='$statusClass$clickableAttr>" . ucfirst(strtolower($row['t_status'])) . "</td>
                                        <td>" . htmlspecialchars($row['t_details'], ENT_QUOTES, 'UTF-8') . "</td>
                                        <td>" . htmlspecialchars($row['t_date'], ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td class='action-buttons'>";
                                if ($userType !== 'technician') {
                                    echo "<a class='view-btn' href='#' title='View'><i class='fas fa-eye'></i></a>
                                          <a class='edit-btn' onclick=\"showEditTicketModal('{$row['t_id']}', '" . htmlspecialchars($row['t_aname'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['t_subject'], ENT_QUOTES, 'UTF-8') . "', '{$row['t_status']}', '" . htmlspecialchars($row['t_details'], ENT_QUOTES, 'UTF-8') . "', '{$row['t_date']}')\" title='Edit'><i class='fas fa-edit'></i></a>
                                          <a class='archive-btn' onclick=\"showArchiveModal('{$row['t_id']}', '" . htmlspecialchars($row['t_aname'], ENT_QUOTES, 'UTF-8') . "')\" title='Archive'><i class='fas fa-archive'></i></a>";
                                } else {
                                    error_log("Rendering disabled buttons for technician: t_id={$row['t_id']}, tab=active");
                                    echo "<a class='view-btn' href='#' title='View'><i class='fas fa-eye'></i></a>
                                          <a class='edit-btn disabled' onclick='showRestrictedMessage()' title='Edit'><i class='fas fa-edit'></i></a>
                                          <a class='archive-btn disabled' onclick='showRestrictedMessage()' title='Archive'><i class='fas fa-archive'></i></a>";
                                }
                                echo "</td>
                                      </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' style='text-align: center;'>No active tickets found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
                <div class="pagination" id="active-pagination">
                    <?php if ($pageActive > 1): ?>
                        <a href="?tab=active&page_active=<?php echo $pageActive - 1; ?>&page_archived=<?php echo $pageArchived; ?>" class="pagination-link"><i class="fas fa-chevron-left"></i></a>
                    <?php else: ?>
                        <span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>
                    <?php endif; ?>
                    <span class="current-page">Page <?php echo $pageActive; ?> of <?php echo $totalActivePages; ?></span>
                    <?php if ($pageActive < $totalActivePages): ?>
                        <a href="?tab=active&page_active=<?php echo $pageActive + 1; ?>&page_archived=<?php echo $pageArchived; ?>" class="pagination-link"><i class="fas fa-chevron-right"></i></a>
                    <?php else: ?>
                        <span class="pagination-link disabled"><i class="fas fa-chevron-right"></i></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="archived-tickets <?php echo isset($_GET['tab']) && $_GET['tab'] === 'archived' ? 'active' : ''; ?>">
                <div class="tab-buttons">
                    <button class="tab-btn" onclick="showTab('active')">Active (<?php echo $totalActive; ?>)</button>
                    <button class="tab-btn" onclick="showTab('archived')">
                        Archived
                        <?php if ($totalArchived > 0): ?>
                            <span class="tab-badge"><?php echo $totalArchived; ?></span>
                        <?php endif; ?>
                    </button>
                </div>
                <table id="archived-tickets-table">
                    <thead>
                        <tr>
                            <th>Ticket ID</th>
                            <th>Account Name</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Ticket Details</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="archived-table-body">
                        <?php
                        if ($resultArchived->num_rows > 0) {
                            while ($row = $resultArchived->fetch_assoc()) {
                                echo "<tr> 
                                        <td>{$row['t_id']}</td> 
                                        <td>" . htmlspecialchars($row['t_aname'], ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td>" . htmlspecialchars($row['t_subject'], ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td class='status-" . strtolower($row['t_status']) . "'>" . ucfirst(strtolower($row['t_status'])) . "</td>
                                        <td>" . htmlspecialchars($row['t_details'], ENT_QUOTES, 'UTF-8') . "</td>
                                        <td>" . htmlspecialchars($row['t_date'], ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td class='action-buttons'>";
                                if ($userType !== 'technician') {
                                    echo "<a class='view-btn' href='#' title='View'><i class='fas fa-eye'></i></a>
                                          <a class='restore-btn' onclick=\"showRestoreModal('{$row['t_id']}', '" . htmlspecialchars($row['t_aname'], ENT_QUOTES, 'UTF-8') . "')\" title='Unarchive'><i class='fas fa-box-open'></i></a>
                                          <a class='delete-btn' onclick=\"showDeleteModal('{$row['t_id']}', '" . htmlspecialchars($row['t_aname'], ENT_QUOTES, 'UTF-8') . "')\" title='Delete'><i class='fas fa-trash'></i></a>";
                                } else {
                                    error_log("Rendering disabled buttons for technician: t_id={$row['t_id']}, tab=archived");
                                    echo "<a class='view-btn' href='#' title='View'><i class='fas fa-eye'></i></a>
                                          <a class='restore-btn disabled' onclick='showRestrictedMessage()' title='Unarchive'><i class='fas fa-box-open'></i></a>
                                          <a class='delete-btn disabled' onclick='showRestrictedMessage()' title='Delete'><i class='fas fa-trash'></i></a>";
                                }
                                echo "</td>
                                      </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' style='text-align: center;'>No archived tickets found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
                <div class="pagination" id="archived-pagination">
                    <?php if ($pageArchived > 1): ?>
                        <a href="?tab=archived&page_active=<?php echo $pageActive; ?>&page_archived=<?php echo $pageArchived - 1; ?>" class="pagination-link"><i class="fas fa-chevron-left"></i></a>
                    <?php else: ?>
                        <span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>
                    <?php endif; ?>
                    <span class="current-page">Page <?php echo $pageArchived; ?> of <?php echo $totalArchivedPages; ?></span>
                    <?php if ($pageArchived < $totalArchivedPages): ?>
                        <a href="?tab=archived&page_active=<?php echo $pageActive; ?>&page_archived=<?php echo $pageArchived + 1; ?>" class="pagination-link"><i class="fas fa-chevron-right"></i></a>
                    <?php else: ?>
                        <span class="pagination-link disabled"><i class="fas fa-chevron-right"></i></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Ticket Modal -->
<div id="viewModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Ticket Details</h2>
           
        </div>
        <div id="viewContent" class="view-details"></div>
        <div class="modal-footer">
            <button class="modal-btn cancel" onclick="closeModal('viewModal')">Close</button>
        </div>
    </div>
</div>

<!-- Archive Ticket Modal -->
<div id="archiveModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Archive Ticket</h2>
        </div>
        <p>Are you sure you want to archive ticket for <span id="archiveTicketName"></span>?</p>
        <form method="POST" id="archiveForm">
            <input type="hidden" name="t_id" id="archiveTicketId">
            <input type="hidden" name="archive_ticket" value="1">
            <div class="modal-footer">
                <button type="button" class="modal-btn cancel" onclick="closeModal('archiveModal')">Cancel</button>
                <button type="submit" class="modal-btn confirm">Archive</button>
            </div>
        </form>
    </div>
</div>

<!-- Restore Ticket Modal -->
<div id="restoreModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Restore Ticket</h2>
        </div>
        <p>Are you sure you want to restore ticket for <span id="restoreTicketName"></span>?</p>
        <form method="POST" id="restoreForm">
            <input type="hidden" name="t_id" id="restoreTicketId">
            <input type="hidden" name="restore_ticket" value="1">
            <div class="modal-footer">
                <button type="button" class="modal-btn cancel" onclick="closeModal('restoreModal')">Cancel</button>
                <button type="submit" class="modal-btn confirm">Restore</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Ticket Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Delete Ticket</h2>
        </div>
        <p>Are you sure you want to permanently delete ticket for <span id="deleteTicketName"></span>? This action cannot be undone.</p>
        <form method="POST" id="deleteForm">
            <input type="hidden" name="t_id" id="deleteTicketId">
            <input type="hidden" name="delete_ticket" value="1">
            <div class="modal-footer">
                <button type="button" class="modal-btn cancel" onclick="closeModal('deleteModal')">Cancel</button>
                <button type="submit" class="modal-btn confirm">Delete</button>
            </div>
        </form>
    </div>
</div>

<!-- Close Ticket Modal -->
<div id="closeModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Close Ticket</h2>
        </div>
        <form method="POST" id="closeForm">
            <p>Confirm closing ticket ID <span id="closeTicketIdDisplay"></span> for <span id="closeTicketName"></span>?</p>
            <input type="hidden" name="t_id" id="closeTicketId">
            <input type="hidden" name="close_ticket" value="1">
            <div class="modal-footer">
                <button type="button" class="modal-btn cancel" onclick="closeModal('closeModal')">Cancel</button>
                <button type="submit" class="modal-btn confirm">Close Ticket</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Ticket Modal -->
<div id="addTicketModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add New Ticket</h2>
        </div>
        <form method="POST" id="addTicketForm" class="modal-form">
            <input type="hidden" name="add_ticket" value="1">
            <input type="hidden" name="ajax" value="true">

            <label for="account_name">Account Name</label>
            <input type="text" name="account_name" id="account_name" value="<?php echo htmlspecialchars($accountname, ENT_QUOTES, 'UTF-8'); ?>" required>
            <span class="error" id="account_name_error"><?php echo $accountnameErr; ?></span>

            <label for="ticket_subject">Subject</label>
            <input type="text" name="ticket_subject" id="ticket_subject" value="<?php echo htmlspecialchars($subject, ENT_QUOTES, 'UTF-8'); ?>" required>
            <span class="error" id="ticket_subject_error"><?php echo $subjectErr; ?></span>

            <label for="ticket_details">Ticket Details</label>
            <textarea name="ticket_details" id="ticket_details" required><?php echo htmlspecialchars($issuedetails, ENT_QUOTES, 'UTF-8'); ?></textarea>
            <span class="error" id="ticket_details_error"><?php echo $issuedetailsErr; ?></span>

            <label for="ticket_status">Ticket Status</label>
            <input type="text" name="ticket_status" id="ticket_status" value="Open" readonly>
            <span class="error" id="ticket_status_error"></span>

            <label for="date">Date Issued</label>
            <input type="date" name="date" id="date" value="<?php echo htmlspecialchars($dob, ENT_QUOTES, 'UTF-8'); ?>" required>
            <span class="error" id="date_error"><?php echo $dobErr; ?></span>

            <div class="modal-footer">
                <button type="button" class="modal-btn cancel" onclick="closeModal('addTicketModal')">Cancel</button>
                <button type="submit" class="modal-btn confirm">Add Ticket</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Ticket Modal -->
<div id="editTicketModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Ticket</h2>
        </div>
        <form method="POST" id="editTicketForm" class="modal-form">
            <input type="hidden" name="edit_ticket" value="1">
            <input type="hidden" name="ajax" value="true">
            <input type="hidden" name="t_id" id="edit_t_id">

            <label for="edit_account_name">Account Name</label>
            <input type="text" name="account_name" id="edit_account_name" required>
            <span class="error" id="edit_account_name_error"></span>

            <label for="edit_ticket_subject">Subject</label>
            <input type="text" name="ticket_subject" id="edit_ticket_subject" required>
            <span class="error" id="edit_ticket_subject_error"></span>

            <label for="edit_ticket_status">Ticket Status</label>
            <input type="text" name="ticket_status" id="edit_ticket_status" required>
            <span class="error" id="edit_ticket_status_error"></span>

            <label for="edit_ticket_details">Ticket Details</label>
            <textarea name="ticket_details" id="edit_ticket_details" required></textarea>
            <span class="error" id="edit_ticket_details_error"></span>

            <label for="edit_date">Date Issued</label>
            <input type="date" name="date" id="edit_date" required>
            <span class="error" id="edit_date_error"></span>

            <div class="modal-footer">
                <button type="button" class="modal-btn cancel" onclick="closeModal('editTicketModal')">Cancel</button>
                <button type="submit" class="modal-btn confirm">Update Ticket</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentSearchPage = 1;
let defaultPageActive = <?php echo json_encode($pageActive); ?>;
let defaultPageArchived = <?php echo json_encode($pageArchived); ?>;
const userType = '<?php echo $userType; ?>';

document.addEventListener('DOMContentLoaded', () => {
    console.log('Page loaded, initializing staffD.php, userType=' + userType);
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab') || 'active';
    showTab(tab);

    // Handle alert messages disappearing after 2 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.classList.add('alert-hidden');
            setTimeout(() => alert.remove(), 500);
        }, 2000);
    });

    // Initialize search on page load if there's a search term
    const searchInput = document.getElementById('searchInput');
    if (searchInput.value) {
        console.log('Search input has value, triggering searchTickets');
        searchTickets();
    }

    // Prevent navigation to restricted pages for technicians
    if (userType === 'technician') {
        document.querySelectorAll('.sidebar a').forEach(link => {
            const href = link.getAttribute('href');
            if (href === 'customersT.php' || href === 'registerAssets.php' || href === 'addC.php') {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    showRestrictedMessage();
                });
            }
        });
    }
});

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

function searchTickets(page = 1) {
    const searchTerm = document.getElementById('searchInput').value;
    const activeSection = document.querySelector('.active-tickets');
    const archivedSection = document.querySelector('.archived-tickets');
    const tab = activeSection.classList.contains('active') ? 'active' : 'archived';
    const tbody = tab === 'active' ? document.getElementById('active-table-body') : document.getElementById('archived-table-body');
    const paginationContainer = tab === 'active' ? document.getElementById('active-pagination') : document.getElementById('archived-pagination');
    const defaultPageToUse = tab === 'active' ? defaultPageActive : defaultPageArchived;

    currentSearchPage = page;

    console.log(`Searching tickets: tab=${tab}, page=${page}, searchTerm=${searchTerm}`);

    const xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                tbody.innerHTML = response.html;
                updatePagination(response.currentPage, response.totalPages, response.searchTerm, tab === 'active' ? 'active-pagination' : 'archived-pagination');
            } catch (e) {
                console.error('Error parsing JSON:', e, xhr.responseText);
            }
        }
    };
    xhr.open('GET', `staffD.php?action=search&tab=${tab}&search=${encodeURIComponent(searchTerm)}&search_page=${searchTerm ? page : defaultPageToUse}`, true);
    xhr.send();
}

function updatePagination(currentPage, totalPages, searchTerm, paginationId) {
    const paginationContainer = document.getElementById(paginationId);
    let paginationHtml = '';

    if (currentPage > 1) {
        paginationHtml += `<a href="javascript:searchTickets(${currentPage - 1})" class="pagination-link"><i class="fas fa-chevron-left"></i></a>`;
    } else {
        paginationHtml += `<span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>`;
    }

    paginationHtml += `<span class="current-page">Page ${currentPage} of ${totalPages}</span>`;

    if (currentPage < totalPages) {
        paginationHtml += `<a href="javascript:searchTickets(${currentPage + 1})" class="pagination-link"><i class="fas fa-chevron-right"></i></a>`;
    } else {
        paginationHtml += `<span class="pagination-link disabled"><i class="fas fa-chevron-right"></i></span>`;
    }

    paginationContainer.innerHTML = paginationHtml;
}

const debouncedSearchTickets = debounce(searchTickets, 300);

function showRestrictedMessage() {
    console.log('Restricted action attempted by technician');
    alert("Only staff can perform this action.");
}

function showTab(tab) {
    console.log('Switching to tab:', tab);
    const activeSection = document.querySelector('.active-tickets');
    const archivedSection = document.querySelector('.archived-tickets');

    if (tab === 'active') {
        activeSection.classList.add('active');
        archivedSection.classList.remove('active');
        activeSection.style.display = 'block';
        archivedSection.style.display = 'none';
    } else if (tab === 'archived') {
        activeSection.classList.remove('active');
        archivedSection.classList.add('active');
        activeSection.style.display = 'none';
        archivedSection.style.display = 'block';
    }

    document.querySelectorAll('.tab-btn').forEach(button => {
        button.classList.remove('active');
        if (button.onclick.toString().includes(`showTab('${tab}'`)) {
            button.classList.add('active');
        }
    });

    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('tab', tab);
    history.replaceState(null, '', '?' + urlParams.toString());

    document.getElementById('searchInput').value = '';
    searchTickets();
}

function showViewModal(id, aname, subject, status, details, date) {
    console.log(`Opening view modal for ticket ID=${id}`);
    try {
        // Escape HTML to prevent XSS
        const escapeHTML = (str) => {
            return str.replace(/&/g, '&amp;')
                     .replace(/</g, '&lt;')
                     .replace(/>/g, '&gt;')
                     .replace(/"/g, '&quot;')
                     .replace(/'/g, '&#039;');
        };

        // Populate modal content
        document.getElementById('viewContent').innerHTML = `
            <p><strong>ID:</strong> ${escapeHTML(id.toString())}</p>
            <p><strong>Account Name:</strong> ${escapeHTML(aname)}</p>
            <p><strong>Subject:</strong> ${escapeHTML(subject)}</p>
            <p><strong>Ticket Status:</strong> <span class="status-${escapeHTML(status.toLowerCase())}">${escapeHTML(status)}</span></p>
            <p><strong>Ticket Details:</strong> ${escapeHTML(details)}</p>
            <p><strong>Date:</strong> ${escapeHTML(date)}</p>
        `;
        
        // Show the modal
        const modal = document.getElementById('viewModal');
        if (modal) {
            modal.style.display = 'block';
        } else {
            console.error('View modal element not found');
        }
    } catch (error) {
        console.error('Error in showViewModal:', error);
    }
}

function showArchiveModal(id, aname) {
    document.getElementById('archiveTicketId').value = id;
    document.getElementById('archiveTicketName').innerText = aname;
    document.getElementById('archiveModal').style.display = 'block';
}

function showRestoreModal(id, aname) {
    document.getElementById('restoreTicketId').value = id;
    document.getElementById('restoreTicketName').innerText = aname;
    document.getElementById('restoreModal').style.display = 'block';
}

function showDeleteModal(id, aname) {
    document.getElementById('deleteTicketId').value = id;
    document.getElementById('deleteTicketName').innerText = aname;
    document.getElementById('deleteModal').style.display = 'block';
}

function showCloseModal(id, aname, status) {
    if (status.toLowerCase() === 'closed') {
        alert("This ticket is already closed!");
        return;
    }
    console.log(`Opening close modal for ticket ID=${id}, Account Name=${aname}`);
    document.getElementById('closeTicketId').value = id;
    document.getElementById('closeTicketIdDisplay').innerText = id;
    document.getElementById('closeTicketName').innerText = aname;
    document.getElementById('closeModal').style.display = 'block';
}

function showAddTicketModal() {
    document.getElementById('addTicketForm').reset();
    document.querySelectorAll('#addTicketForm .error').forEach(span => span.textContent = '');
    document.getElementById('addTicketModal').style.display = 'block';
}

function showEditTicketModal(id, aname, subject, status, details, date) {
    document.getElementById('edit_t_id').value = id;
    document.getElementById('edit_account_name').value = aname;
    document.getElementById('edit_ticket_subject').value = subject;
    document.getElementById('edit_ticket_status').value = status;
    document.getElementById('edit_ticket_details').value = details;
    document.getElementById('edit_date').value = date;
    document.querySelectorAll('#editTicketForm .error').forEach(span => span.textContent = '');
    document.getElementById('editTicketModal').style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

window.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
});

// Event delegation for view buttons
document.addEventListener('click', function(event) {
    const target = event.target.closest('.view-btn');
    if (target) {
        event.preventDefault();
        const row = target.closest('tr');
        if (row) {
            const id = row.cells[0].textContent;
            const aname = row.cells[1].textContent;
            const subject = row.cells[2].textContent;
            const status = row.cells[3].textContent;
            const details = row.cells[4].textContent;
            const date = row.cells[5].textContent;
            showViewModal(id, aname, subject, status, details, date);
        }
    }
});

// Handle Add Ticket form submission via AJAX
document.getElementById('addTicketForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'staffD.php', true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            const alertContainer = document.querySelector('.alert-container');

            // Clear previous error messages
            document.querySelectorAll('#addTicketForm .error').forEach(span => span.textContent = '');

            if (response.success) {
                // Show success alert
                const alert = document.createElement('div');
                alert.className = 'alert alert-success';
                alert.textContent = response.message;
                alertContainer.appendChild(alert);
                setTimeout(() => {
                    alert.classList.add('alert-hidden');
                    setTimeout(() => alert.remove(), 500);
                }, 2000);
                closeModal('addTicketModal');
                searchTickets(defaultPageActive);
            } else if (response.errors) {
                // Display errors below respective fields inside the modal only
                for (const [field, error] of Object.entries(response.errors)) {
                    if (error) {
                        document.getElementById(`${field}_error`).textContent = error;
                    }
                }
            }
        }
    };
    xhr.send(formData);
});

// Handle Edit Ticket form submission via AJAX
document.getElementById('editTicketForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'staffD.php', true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            const alertContainer = document.querySelector('.alert-container');

            // Clear previous error messages
            document.querySelectorAll('#editTicketForm .error').forEach(span => span.textContent = '');

            if (response.success) {
                // Show success alert
                const alert = document.createElement('div');
                alert.className = 'alert alert-success';
                alert.textContent = response.message;
                alertContainer.appendChild(alert);
                setTimeout(() => {
                    alert.classList.add('alert-hidden');
                    setTimeout(() => alert.remove(), 500);
                }, 2000);
                closeModal('editTicketModal');
                searchTickets(defaultPageActive);
            } else if (response.errors) {
                // Display errors below respective fields inside the modal only
                for (const [field, error] of Object.entries(response.errors)) {
                    if (error) {
                        document.getElementById(`edit_${field}_error`).textContent = error;
                    }
                }
            }
        }
    };
    xhr.send(formData);
});
</script>
</body>
</html>
