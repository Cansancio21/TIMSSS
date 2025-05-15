<?php
session_start();
include 'db.php'; // Include your database connection file

$username = $_SESSION['username'] ?? '';
$userId = $_SESSION['userId'] ?? 0;

if (!$username || !$userId) {
    echo "Unauthorized access. Please log in.";
    exit();
}

// Initialize variables
$firstName = '';
$userType = '';
$avatarPath = 'default-avatar.png';
$avatarFolder = 'Uploads/avatars/';
$userAvatar = $avatarFolder . $username . '.png';

if (file_exists($userAvatar)) {
    $_SESSION['avatarPath'] = $userAvatar . '?' . time();
} else {
    $_SESSION['avatarPath'] = 'default-avatar.png';
}
$avatarPath = $_SESSION['avatarPath'];

// Pagination settings
$limit = 10; // 10 tickets per page
$regularActivePage = isset($_GET['regularActivePage']) ? max(1, (int)$_GET['regularActivePage']) : 1;
$supportActivePage = isset($_GET['supportActivePage']) ? max(1, (int)$_GET['supportActivePage']) : 1;
$regularArchivedPage = isset($_GET['regularArchivedPage']) ? max(1, (int)$_GET['regularArchivedPage']) : 1;
$supportArchivedPage = isset($_GET['supportArchivedPage']) ? max(1, (int)$_GET['supportArchivedPage']) : 1;
$regularActiveOffset = max(0, ($regularActivePage - 1) * $limit); // Ensure non-negative offset
$supportActiveOffset = max(0, ($supportActivePage - 1) * $limit);
$regularArchivedOffset = max(0, ($regularArchivedPage - 1) * $limit);
$supportArchivedOffset = max(0, ($supportArchivedPage - 1) * $limit);
$tab = $_GET['tab'] ?? 'regular'; // Main tab: regular, support, regularArchived, supportArchived
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($conn) {
    // Fetch firstName and userType from the database
    $sqlUser = "SELECT u_fname, u_type FROM tbl_user WHERE u_username = ?";
    $stmt = $conn->prepare($sqlUser);
    if ($stmt === false) {
        error_log("Prepare failed for user query: " . $conn->error);
    } else {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $resultUser = $stmt->get_result();
        if ($resultUser->num_rows > 0) {
            $row = $resultUser->fetch_assoc();
            $firstName = $row['u_fname'] ?: '';
            $userType = $row['u_type'] ?: '';
        } else {
            error_log("No user found for username: $username");
        }
        $stmt->close();
    }

    // Debug mode to log tickets included in counts
    $debugCounts = true;

    // Regular Ticket counts (exclude archived status)
    $sqlOpenTickets = "SELECT COUNT(*) AS openTickets FROM tbl_ticket WHERE t_status = 'open' AND (t_details NOT LIKE 'ARCHIVED:%' OR t_details IS NULL) AND t_status != 'archived'";
    if ($searchTerm) {
        $sqlOpenTickets .= " AND (t_aname LIKE ? OR t_subject LIKE ? OR t_details LIKE ?)";
    }
    $stmtOpenTickets = $conn->prepare($sqlOpenTickets);
    if ($searchTerm) {
        $searchLike = "%$searchTerm%";
        $stmtOpenTickets->bind_param("sss", $searchLike, $searchLike, $searchLike);
    }
    $stmtOpenTickets->execute();
    $resultOpenTickets = $stmtOpenTickets->get_result();
    $openTickets = $resultOpenTickets ? ($resultOpenTickets->fetch_assoc()['openTickets'] ?? 0) : 0;
    $stmtOpenTickets->close();

    // Debug open regular tickets
    if ($debugCounts && $openTickets > 0) {
        $debugSql = "SELECT t_id, t_status, t_details FROM tbl_ticket WHERE t_status = 'open' AND (t_details NOT LIKE 'ARCHIVED:%' OR t_details IS NULL) AND t_status != 'archived'";
        if ($searchTerm) {
            $debugSql .= " AND (t_aname LIKE ? OR t_subject LIKE ? OR t_details LIKE ?)";
        }
        $debugStmt = $conn->prepare($debugSql);
        if ($searchTerm) {
            $debugStmt->bind_param("sss", $searchLike, $searchLike, $searchLike);
        }
        $debugStmt->execute();
        $debugResult = $debugStmt->get_result();
        while ($row = $debugResult->fetch_assoc()) {
            error_log("Open regular ticket counted: ID={$row['t_id']}, Status={$row['t_status']}, Details=" . ($row['t_details'] ?? 'NULL'));
        }
        $debugStmt->close();
    }

    $sqlClosedTickets = "SELECT COUNT(*) AS closedTickets FROM tbl_ticket WHERE t_status = 'closed' AND (t_details NOT LIKE 'ARCHIVED:%' OR t_details IS NULL) AND t_status != 'archived'";
    if ($searchTerm) {
        $sqlClosedTickets .= " AND (t_aname LIKE ? OR t_subject LIKE ? OR t_details LIKE ?)";
    }
    $stmtClosedTickets = $conn->prepare($sqlClosedTickets);
    if ($searchTerm) {
        $searchLike = "%$searchTerm%";
        $stmtClosedTickets->bind_param("sss", $searchLike, $searchLike, $searchLike);
    }
    $stmtClosedTickets->execute();
    $resultClosedTickets = $stmtClosedTickets->get_result();
    $closedTickets = $resultClosedTickets ? ($resultClosedTickets->fetch_assoc()['closedTickets'] ?? 0) : 0;
    $stmtClosedTickets->close();

    // Debug closed regular tickets
    if ($debugCounts && $closedTickets > 0) {
        $debugSql = "SELECT t_id, t_status, t_details FROM tbl_ticket WHERE t_status = 'closed' AND (t_details NOT LIKE 'ARCHIVED:%' OR t_details IS NULL) AND t_status != 'archived'";
        if ($searchTerm) {
            $debugSql .= " AND (t_aname LIKE ? OR t_subject LIKE ? OR t_details LIKE ?)";
        }
        $debugStmt = $conn->prepare($debugSql);
        if ($searchTerm) {
            $debugStmt->bind_param("sss", $searchLike, $searchLike, $searchLike);
        }
        $debugStmt->execute();
        $debugResult = $debugStmt->get_result();
        while ($row = $debugResult->fetch_assoc()) {
            error_log("Closed regular ticket counted: ID={$row['t_id']}, Status={$row['t_status']}, Details=" . ($row['t_details'] ?? 'NULL'));
        }
        $debugStmt->close();
    }

    $sqlArchivedRegular = "SELECT COUNT(*) AS archivedTickets FROM tbl_ticket WHERE t_details LIKE 'ARCHIVED:%' AND t_status != 'archived'";
    if ($searchTerm) {
        $sqlArchivedRegular .= " AND (t_aname LIKE ? OR t_subject LIKE ? OR t_details LIKE ?)";
    }
    $stmtArchivedRegular = $conn->prepare($sqlArchivedRegular);
    if ($searchTerm) {
        $searchLike = "%$searchTerm%";
        $stmtArchivedRegular->bind_param("sss", $searchLike, $searchLike, $searchLike);
    }
    $stmtArchivedRegular->execute();
    $resultArchivedRegular = $stmtArchivedRegular->get_result();
    $archivedRegular = $resultArchivedRegular ? ($resultArchivedRegular->fetch_assoc()['archivedTickets'] ?? 0) : 0;
    $stmtArchivedRegular->close();

    // Support Tickets status breakdown (exclude archived)
    $sqlSupportOpen = "SELECT COUNT(*) AS supportOpen FROM tbl_supp_tickets WHERE s_status = 'Open' AND (s_message NOT LIKE 'ARCHIVED:%' OR s_message IS NULL)";
    if ($searchTerm) {
        $sqlSupportOpen .= " AND (CONCAT(c_fname, ' ', c_lname) LIKE ? OR s_subject LIKE ? OR s_message LIKE ? OR s_ref LIKE ?)";
    }
    $stmtSupportOpen = $conn->prepare($sqlSupportOpen);
    if ($searchTerm) {
        $searchLike = "%$searchTerm%";
        $stmtSupportOpen->bind_param("ssss", $searchLike, $searchLike, $searchLike, $searchLike);
    }
    $stmtSupportOpen->execute();
    $resultSupportOpen = $stmtSupportOpen->get_result();
    $supportOpen = $resultSupportOpen ? ($resultSupportOpen->fetch_assoc()['supportOpen'] ?? 0) : 0;
    $stmtSupportOpen->close();

    // Debug open support tickets
    if ($debugCounts && $supportOpen > 0) {
        $debugSql = "SELECT id, s_status, s_message FROM tbl_supp_tickets WHERE s_status = 'Open' AND (s_message NOT LIKE 'ARCHIVED:%' OR s_message IS NULL)";
        if ($searchTerm) {
            $debugSql .= " AND (CONCAT(c_fname, ' ', c_lname) LIKE ? OR s_subject LIKE ? OR s_message LIKE ? OR s_ref LIKE ?)";
        }
        $debugStmt = $conn->prepare($debugSql);
        if ($searchTerm) {
            $debugStmt->bind_param("ssss", $searchLike, $searchLike, $searchLike, $searchLike);
        }
        $debugStmt->execute();
        $debugResult = $debugStmt->get_result();
        while ($row = $debugResult->fetch_assoc()) {
            error_log("Open support ticket counted: ID={$row['id']}, Status={$row['s_status']}, Message=" . ($row['s_message'] ?? 'NULL'));
        }
        $debugStmt->close();
    }

    $sqlSupportClosed = "SELECT COUNT(*) AS supportClosed FROM tbl_supp_tickets WHERE s_status = 'Closed' AND (s_message NOT LIKE 'ARCHIVED:%' OR s_message IS NULL)";
    if ($searchTerm) {
        $sqlSupportClosed .= " AND (CONCAT(c_fname, ' ', c_lname) LIKE ? OR s_subject LIKE ? OR s_message LIKE ? OR s_ref LIKE ?)";
    }
    $stmtSupportClosed = $conn->prepare($sqlSupportClosed);
    if ($searchTerm) {
        $searchLike = "%$searchTerm%";
        $stmtSupportClosed->bind_param("ssss", $searchLike, $searchLike, $searchLike, $searchLike);
    }
    $stmtSupportClosed->execute();
    $resultSupportClosed = $stmtSupportClosed->get_result();
    $supportClosed = $resultSupportClosed ? ($resultSupportClosed->fetch_assoc()['supportClosed'] ?? 0) : 0;
    $stmtSupportClosed->close();

    // Debug closed support tickets
    if ($debugCounts && $supportClosed > 0) {
        $debugSql = "SELECT id, s_status, s_message FROM tbl_supp_tickets WHERE s_status = 'Closed' AND (s_message NOT LIKE 'ARCHIVED:%' OR s_message IS NULL)";
        if ($searchTerm) {
            $debugSql .= " AND (CONCAT(c_fname, ' ', c_lname) LIKE ? OR s_subject LIKE ? OR s_message LIKE ? OR s_ref LIKE ?)";
        }
        $debugStmt = $conn->prepare($debugSql);
        if ($searchTerm) {
            $debugStmt->bind_param("ssss", $searchLike, $searchLike, $searchLike, $searchLike);
        }
        $debugStmt->execute();
        $debugResult = $debugStmt->get_result();
        while ($row = $debugResult->fetch_assoc()) {
            error_log("Closed support ticket counted: ID={$row['id']}, Status={$row['s_status']}, Message=" . ($row['s_message'] ?? 'NULL'));
        }
        $debugStmt->close();
    }

    $sqlArchivedSupport = "SELECT COUNT(*) AS archivedSupport FROM tbl_supp_tickets WHERE s_message LIKE 'ARCHIVED:%'";
    if ($searchTerm) {
        $sqlArchivedSupport .= " AND (CONCAT(c_fname, ' ', c_lname) LIKE ? OR s_subject LIKE ? OR s_message LIKE ? OR s_ref LIKE ?)";
    }
    $stmtArchivedSupport = $conn->prepare($sqlArchivedSupport);
    if ($searchTerm) {
        $searchLike = "%$searchTerm%";
        $stmtArchivedSupport->bind_param("ssss", $searchLike, $searchLike, $searchLike, $searchLike);
    }
    $stmtArchivedSupport->execute();
    $resultArchivedSupport = $stmtArchivedSupport->get_result();
    $archivedSupport = $resultArchivedSupport ? ($resultArchivedSupport->fetch_assoc()['archivedSupport'] ?? 0) : 0;
    $stmtArchivedSupport->close();

    // Pending tasks
    $pendingTasks = $openTickets + $supportOpen;

    // Handle actions (triggered via POST from modal)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action']) && $_POST['action'] === 'close' && isset($_POST['id']) && isset($_POST['type']) && isset($_POST['technicianFirstName'])) {
            $id = (int)$_POST['id'];
            $type = $_POST['type'];
            $technicianFirstName = trim($_POST['technicianFirstName']);
            $targetTab = ($type === 'regular') ? 'regular' : 'support';

            // Validate technician's first name
            $sql = "SELECT u_fname FROM tbl_user WHERE u_username = ? AND u_type = 'technician'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                if (strtolower($row['u_fname']) !== strtolower($technicianFirstName)) {
                    echo json_encode(['success' => false, 'message' => 'Invalid first name. Please enter your correct first name.']);
                    $stmt->close();
                    exit;
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Techn /

ician not found.']);
                $stmt->close();
                exit;
            }
            $stmt->close();

            if ($type === 'regular') {
                // Check if ticket is open and not archived
                $sql = "SELECT t_status FROM tbl_ticket WHERE t_id = ? AND t_status = 'open' AND t_status != 'archived'";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows === 0) {
                    echo json_encode(['success' => false, 'message' => 'Ticket is not open or does not exist.']);
                    $stmt->close();
                    exit;
                }
                $stmt->close();

                // Update ticket status to closed
                $sql = "UPDATE tbl_ticket SET t_status = 'closed' WHERE t_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    // Log the action
                    $logDescription = "Technician $firstName closed regular ticket ID $id";
                    $sqlLog = "INSERT INTO tbl_logs (l_stamp, l_description) VALUES (NOW(), ?)";
                    $stmtLog = $conn->prepare($sqlLog);
                    $stmtLog->bind_param("s", $logDescription);
                    $stmtLog->execute();
                    $stmtLog->close();

                    $redirect_url = "technicianD.php?tab=" . urlencode($targetTab) .
                                    "&regularActivePage=" . urlencode($regularActivePage) .
                                    "&supportActivePage=" . urlencode($supportActivePage) .
                                    "&regularArchivedPage=" . urlencode($regularArchivedPage) .
                                    "&supportArchivedPage=" . urlencode($supportArchivedPage) .
                                    ($searchTerm ? "&search=" . urlencode($searchTerm) : '');
                    echo json_encode(['success' => true, 'redirect' => $redirect_url]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to close ticket.']);
                }
                $stmt->close();
            } elseif ($type === 'support') {
                // Check if support ticket is open
                $sql = "SELECT s_status FROM tbl_supp_tickets WHERE id = ? AND s_status = 'Open'";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows === 0) {
                    echo json_encode(['success' => false, 'message' => 'Support ticket is not open or does not exist.']);
                    $stmt->close();
                    exit;
                }
                $stmt->close();

                // Update support ticket status to closed
                $sql = "UPDATE tbl_supp_tickets SET s_status = 'Closed' WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    // Log the action
                    $logDescription = "Technician $firstName closed support ticket ID $id";
                    $sqlLog = "INSERT INTO tbl_logs (l_stamp, l_description) VALUES (NOW(), ?)";
                    $stmtLog = $conn->prepare($sqlLog);
                    $stmtLog->bind_param("s", $logDescription);
                    $stmtLog->execute();
                    $stmtLog->close();

                    $redirect_url = "technicianD.php?tab=" . urlencode($targetTab) .
                                    "&regularActivePage=" . urlencode($regularActivePage) .
                                    "&supportActivePage=" . urlencode($supportActivePage) .
                                    "&regularArchivedPage=" . urlencode($regularArchivedPage) .
                                    "&supportArchivedPage=" . urlencode($supportArchivedPage) .
                                    ($searchTerm ? "&search=" . urlencode($searchTerm) : '');
                    echo json_encode(['success' => true, 'redirect' => $redirect_url]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to close support ticket.']);
                }
                $stmt->close();
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid ticket type.']);
            }
            exit;
        } elseif (isset($_POST['action']) && isset($_POST['id']) && isset($_POST['type'])) {
            $id = (int)$_POST['id'];
            $type = trim($_POST['type']);
            $action = trim($_POST['action']);

            // Validate inputs
            if ($id <= 0 || !in_array($type, ['regular', 'support']) || !in_array($action, ['archive', 'unarchive', 'delete'])) {
                error_log("Invalid POST data: id=$id, type=$type, action=$action");
                header("Location: technicianD.php?tab=" . urlencode($tab) . "&error=invalid_action");
                exit;
            }

            // Set targetTab based on action and type
            $targetTab = $tab;
            if ($action === 'archive') {
                $targetTab = ($type === 'regular') ? 'regularArchived' : 'supportArchived';
            } elseif ($action === 'unarchive') {
                $targetTab = ($type === 'regular') ? 'regular' : 'support';
            } elseif ($action === 'delete') {
                $targetTab = ($type === 'regular') ? 'regularArchived' : 'supportArchived';
            }

            // Database operations
            if ($action === 'archive') {
                if ($type === 'regular') {
                    $sql = "SELECT t_details FROM tbl_ticket WHERE t_id = ? AND t_status != 'archived'";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result->num_rows === 0) {
                        error_log("Ticket not found or already archived: id=$id, type=regular");
                        header("Location: technicianD.php?tab=" . urlencode($tab) . "&error=ticket_not_found");
                        $stmt->close();
                        exit;
                    }
                    $row = $result->fetch_assoc();
                    $current_details = $row['t_details'] ?? '';
                    $stmt->close();
                    $new_details = 'ARCHIVED:' . $current_details;
                    $sql = "UPDATE tbl_ticket SET t_details = ? WHERE t_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("si", $new_details, $id);
                } else {
                    $sql = "SELECT s_message FROM tbl_supp_tickets WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result->num_rows === 0) {
                        error_log("Support ticket not found: id=$id");
                        header("Location: technicianD.php?tab=" . urlencode($tab) . "&error=ticket_not_found");
                        $stmt->close();
                        exit;
                    }
                    $row = $result->fetch_assoc();
                    $current_message = $row['s_message'] ?? '';
                    $stmt->close();
                    $new_message = 'ARCHIVED:' . $current_message;
                    $sql = "UPDATE tbl_supp_tickets SET s_message = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("si", $new_message, $id);
                }
            } elseif ($action === 'unarchive') {
                if ($type === 'regular') {
                    $sql = "SELECT t_details FROM tbl_ticket WHERE t_id = ? AND t_status != 'archived'";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result->num_rows === 0) {
                        error_log("Ticket not found or not archived: id=$id, type=regular");
                        header("Location: technicianD.php?tab=" . urlencode($tab) . "&error=ticket_not_found");
                        $stmt->close();
                        exit;
                    }
                    $row = $result->fetch_assoc();
                    $current_details = $row['t_details'] ?? '';
                    $stmt->close();
                    $new_details = preg_replace('/^ARCHIVED:/', '', $current_details);
                    $sql = "UPDATE tbl_ticket SET t_details = ? WHERE t_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("si", $new_details, $id);
                } else {
                    $sql = "SELECT s_message FROM tbl_supp_tickets WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result->num_rows === 0) {
                        error_log("Support ticket not found: id=$id");
                        header("Location: technicianD.php?tab=" . urlencode($tab) . "&error=ticket_not_found");
                        $stmt->close();
                        exit;
                    }
                    $row = $result->fetch_assoc();
                    $current_message = $row['s_message'] ?? '';
                    $stmt->close();
                    $new_message = preg_replace('/^ARCHIVED:/', '', $current_message);
                    $sql = "UPDATE tbl_supp_tickets SET s_message = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("si", $new_message, $id);
                }
            } elseif ($action === 'delete') {
                if ($type === 'regular') {
                    $sql = "DELETE FROM tbl_ticket WHERE t_id = ? AND t_details LIKE 'ARCHIVED:%' AND t_status != 'archived'";
                } else {
                    $sql = "DELETE FROM tbl_supp_tickets WHERE id = ? AND s_message LIKE 'ARCHIVED:%'";
                }
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
            }

            // Execute the statement and handle errors
            if ($stmt->execute()) {
                if ($action !== 'delete' || $stmt->affected_rows > 0) {
                    // Log the action
                    $logDescription = "Technician $firstName performed $action on $type ticket ID $id";
                    $sqlLog = "INSERT INTO tbl_logs (l_stamp, l_description) VALUES (NOW(), ?)";
                    $stmtLog = $conn->prepare($sqlLog);
                    $stmtLog->bind_param("s", $logDescription);
                    $stmtLog->execute();
                    $stmtLog->close();
                }
            } else {
                error_log("Database operation failed: action=$action, type=$type, id=$id, error=" . $stmt->error);
                header("Location: technicianD.php?tab=" . urlencode($tab) . "&error=database_error");
                $stmt->close();
                exit;
            }
            $stmt->close();

            // Build redirect URL with pagination and search
            $redirect_url = "technicianD.php?tab=" . urlencode($targetTab) .
                            "&regularActivePage=" . urlencode($regularActivePage) .
                            "&supportActivePage=" . urlencode($supportActivePage) .
                            "&regularArchivedPage=" . urlencode($regularArchivedPage) .
                            "&supportArchivedPage=" . urlencode($supportArchivedPage) .
                            ($searchTerm ? "&search=" . urlencode($searchTerm) : '');
            header("Location: $redirect_url");
            exit;
        }
    }

    // Pagination for Regular Tickets - Active (exclude archived status)
    $sqlTotalRegularActive = "SELECT COUNT(*) AS total FROM tbl_ticket WHERE (t_details NOT LIKE 'ARCHIVED:%' OR t_details IS NULL) AND t_status != 'archived'";
    if ($searchTerm) {
        $sqlTotalRegularActive .= " AND (t_aname LIKE ? OR t_subject LIKE ? OR t_details LIKE ?)";
    }
    $stmtTotalRegularActive = $conn->prepare($sqlTotalRegularActive);
    if ($searchTerm) {
        $searchLike = "%$searchTerm%";
        $stmtTotalRegularActive->bind_param("sss", $searchLike, $searchLike, $searchLike);
    }
    $stmtTotalRegularActive->execute();
    $resultTotalRegularActive = $stmtTotalRegularActive->get_result();
    $totalRegularActive = $resultTotalRegularActive ? ($resultTotalRegularActive->fetch_assoc()['total'] ?? 0) : 0;
    $stmtTotalRegularActive->close();
    error_log("Total active regular tickets: $totalRegularActive"); // Debug total count
    $totalRegularActivePages = ceil($totalRegularActive / $limit) ?: 1;
    $regularActivePage = min($regularActivePage, $totalRegularActivePages);
    $regularActiveOffset = max(0, ($regularActivePage - 1) * $limit);

    $sqlRegularActive = "SELECT t_id, t_aname, t_subject, t_details, t_status, t_date 
                        FROM tbl_ticket 
                        WHERE (t_details NOT LIKE 'ARCHIVED:%' OR t_details IS NULL) AND t_status != 'archived'";
    if ($searchTerm) {
        $sqlRegularActive .= " AND (t_aname LIKE ? OR t_subject LIKE ? OR t_details LIKE ?)";
    }
    $sqlRegularActive .= " ORDER BY t_date ASC LIMIT ? OFFSET ?";
    $stmtRegularActive = $conn->prepare($sqlRegularActive);
    if ($searchTerm) {
        $stmtRegularActive->bind_param("sssii", $searchLike, $searchLike, $searchLike, $limit, $regularActiveOffset);
    } else {
        $stmtRegularActive->bind_param("ii", $limit, $regularActiveOffset);
    }
    $stmtRegularActive->execute();
    $resultRegularActive = $stmtRegularActive->get_result();

    // Debug fetched tickets
    if ($resultRegularActive && $resultRegularActive->num_rows > 0) {
        $ticketIds = [];
        while ($row = $resultRegularActive->fetch_assoc()) {
            $ticketIds[] = $row['t_id'];
        }
        $resultRegularActive->data_seek(0); // Reset result pointer
        error_log("Tickets fetched for page $regularActivePage: " . implode(', ', $ticketIds));
    } else {
        error_log("No tickets fetched for page $regularActivePage");
    }
    $stmtRegularActive->close();

    // Pagination for Support Tickets - Active (exclude archived)
    $sqlTotalSupportActive = "SELECT COUNT(*) AS total FROM tbl_supp_tickets WHERE (s_message NOT LIKE 'ARCHIVED:%' OR s_message IS NULL)";
    if ($searchTerm) {
        $sqlTotalSupportActive .= " AND (CONCAT(c_fname, ' ', c_lname) LIKE ? OR s_subject LIKE ? OR s_message LIKE ? OR s_ref LIKE ?)";
    }
    $stmtTotalSupportActive = $conn->prepare($sqlTotalSupportActive);
    if ($searchTerm) {
        $searchLike = "%$searchTerm%";
        $stmtTotalSupportActive->bind_param("ssss", $searchLike, $searchLike, $searchLike, $searchLike);
    }
    $stmtTotalSupportActive->execute();
    $resultTotalSupportActive = $stmtTotalSupportActive->get_result();
    $totalSupportActive = $resultTotalSupportActive ? ($resultTotalSupportActive->fetch_assoc()['total'] ?? 0) : 0;
    $stmtTotalSupportActive->close();
    $totalSupportActivePages = ceil($totalSupportActive / $limit) ?: 1;
    $supportActivePage = min($supportActivePage, $totalSupportActivePages);
    $supportActiveOffset = max(0, ($supportActivePage - 1) * $limit);

    $sqlSupportActive = "SELECT id AS t_id, CONCAT(c_fname, ' ', c_lname) AS t_aname, s_subject, s_ref, s_message AS t_details, s_status AS t_status 
                        FROM tbl_supp_tickets 
                        WHERE (s_message NOT LIKE 'ARCHIVED:%' OR s_message IS NULL)";
    if ($searchTerm) {
        $sqlSupportActive .= " AND (CONCAT(c_fname, ' ', c_lname) LIKE ? OR s_subject LIKE ? OR s_message LIKE ? OR s_ref LIKE ?)";
    }
    $sqlSupportActive .= " ORDER BY id ASC LIMIT ? OFFSET ?";
    $stmtSupportActive = $conn->prepare($sqlSupportActive);
    if ($searchTerm) {
        $stmtSupportActive->bind_param("ssssii", $searchLike, $searchLike, $searchLike, $searchLike, $limit, $supportActiveOffset);
    } else {
        $stmtSupportActive->bind_param("ii", $limit, $supportActiveOffset);
    }
    $stmtSupportActive->execute();
    $resultSupportActive = $stmtSupportActive->get_result();
    $stmtSupportActive->close();

    // Pagination for Regular Tickets - Archived
    $sqlTotalRegularArchived = "SELECT COUNT(*) AS total FROM tbl_ticket WHERE t_details LIKE 'ARCHIVED:%' AND t_status != 'archived'";
    if ($searchTerm) {
        $sqlTotalRegularArchived .= " AND (t_aname LIKE ? OR t_subject LIKE ? OR t_details LIKE ?)";
    }
    $stmtTotalRegularArchived = $conn->prepare($sqlTotalRegularArchived);
    if ($searchTerm) {
        $searchLike = "%$searchTerm%";
        $stmtTotalRegularArchived->bind_param("sss", $searchLike, $searchLike, $searchLike);
    }
    $stmtTotalRegularArchived->execute();
    $resultTotalRegularArchived = $stmtTotalRegularArchived->get_result();
    $totalRegularArchived = $resultTotalRegularArchived ? ($resultTotalRegularArchived->fetch_assoc()['total'] ?? 0) : 0;
    $stmtTotalRegularArchived->close();
    $totalRegularArchivedPages = ceil($totalRegularArchived / $limit) ?: 1;
    $regularArchivedPage = min($regularArchivedPage, $totalRegularArchivedPages);
    $regularArchivedOffset = max(0, ($regularArchivedPage - 1) * $limit);

    $sqlRegularArchived = "SELECT t_id, t_aname, t_subject, t_details, t_status, t_date 
                          FROM tbl_ticket 
                          WHERE t_details LIKE 'ARCHIVED:%' AND t_status != 'archived'";
    if ($searchTerm) {
        $sqlRegularArchived .= " AND (t_aname LIKE ? OR t_subject LIKE ? OR t_details LIKE ?)";
    }
    $sqlRegularArchived .= " ORDER BY t_date ASC LIMIT ? OFFSET ?";
    $stmtRegularArchived = $conn->prepare($sqlRegularArchived);
    if ($searchTerm) {
        $stmtRegularArchived->bind_param("sssii", $searchLike, $searchLike, $searchLike, $limit, $regularArchivedOffset);
    } else {
        $stmtRegularArchived->bind_param("ii", $limit, $regularArchivedOffset);
    }
    if (!$stmtRegularArchived->execute()) {
        error_log("Execution failed: " . $stmtRegularArchived->error);
        die("Error executing query for archived regular tickets.");
    }
    $resultRegularArchived = $stmtRegularArchived->get_result();
    if (!$resultRegularArchived) {
        error_log("Failed to get result: " . $stmtRegularArchived->error);
    }
    $stmtRegularArchived->close();

    // Pagination for Support Tickets - Archived
    $sqlTotalSupportArchived = "SELECT COUNT(*) AS total FROM tbl_supp_tickets WHERE s_message LIKE 'ARCHIVED:%'";
    if ($searchTerm) {
        $sqlTotalSupportArchived .= " AND (CONCAT(c_fname, ' ', c_lname) LIKE ? OR s_subject LIKE ? OR s_message LIKE ? OR s_ref LIKE ?)";
    }
    $stmtTotalSupportArchived = $conn->prepare($sqlTotalSupportArchived);
    if ($searchTerm) {
        $searchLike = "%$searchTerm%";
        $stmtTotalSupportArchived->bind_param("ssss", $searchLike, $searchLike, $searchLike, $searchLike);
    }
    $stmtTotalSupportArchived->execute();
    $resultTotalSupportArchived = $stmtTotalSupportArchived->get_result();
    $totalSupportArchived = $resultTotalSupportArchived ? ($resultTotalSupportArchived->fetch_assoc()['total'] ?? 0) : 0;
    $stmtTotalSupportArchived->close();
    $totalSupportArchivedPages = ceil($totalSupportArchived / $limit) ?: 1;
    $supportArchivedPage = min($supportArchivedPage, $totalSupportArchivedPages);
    $supportArchivedOffset = max(0, ($supportArchivedPage - 1) * $limit);

    $sqlSupportArchived = "SELECT id AS t_id, CONCAT(c_fname, ' ', c_lname) AS t_aname, s_subject, s_ref, s_message AS t_details, s_status AS t_status 
                          FROM tbl_supp_tickets 
                          WHERE s_message LIKE 'ARCHIVED:%'";
    if ($searchTerm) {
        $sqlSupportArchived .= " AND (CONCAT(c_fname, ' ', c_lname) LIKE ? OR s_subject LIKE ? OR s_message LIKE ? OR s_ref LIKE ?)";
    }
    $sqlSupportArchived .= " ORDER BY id ASC LIMIT ? OFFSET ?";
    $stmtSupportArchived = $conn->prepare($sqlSupportArchived);
    if ($searchTerm) {
        $stmtSupportArchived->bind_param("ssssii", $searchLike, $searchLike, $searchLike, $searchLike, $limit, $supportArchivedOffset);
    } else {
        $stmtSupportArchived->bind_param("ii", $limit, $supportArchivedOffset);
    }
    $stmtSupportArchived->execute();
    $resultSupportArchived = $stmtSupportArchived->get_result();
    $stmtSupportArchived->close();
} else {
    error_log("Database connection failed.");
    $firstName = '';
    $userType = '';
    $openTickets = $closedTickets = $pendingTasks = $supportOpen = $supportClosed = 0;
    $archivedRegular = $archivedSupport = 0;
    $totalRegularActive = $totalSupportActive = 0;
    $totalRegularArchived = $totalSupportArchived = 0;
    $totalRegularActivePages = $totalSupportActivePages = 1;
    $totalRegularArchivedPages = $totalSupportArchivedPages = 1;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISP Technician Dashboard</title>
    <link rel="stylesheet" href="techniciansD.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .support-tickets-link {
            display: flex;
            align-items: center;
            cursor: pointer;
            padding: 10px;
            color: #fff;
            text-decoration: none;
        }
        .support-tickets-link:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        .support-tickets-input {
            display: none;
            margin: 10px 0 10px 20px;
            align-items: center;
        }
        .support-tickets-input.active {
            display: flex;
        }
        .support-tickets-input input {
            width: 120px;
            padding: 8px;
            margin-right: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: #fff;
            color: #333;
            font-size: 14px;
        }
        .support-tickets-input button {
            padding: 8px 12px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .support-tickets-input button:hover {
            background: #0056b3;
        }
        .close-btn {
            cursor: pointer;
            color: #28a745;
            margin: 0 5px;
            transition: color 0.2s;
        }
        .close-btn:hover {
            color: #218838;
        }
        .close-btn i {
            font-size: 18px;
        }
        .status-open.clickable cujo {
            cursor: pointer;
            text-decoration: none;
            color: #28a745;
        }
        .status-open.clickable:hover {
            text-decoration: underline;
        }
        .modal-btn.confirm:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body> 
<div class="wrapper">
    <div class="sidebar glass-container">
        <h2>Task Management</h2>
        <ul>
            <li><a href="technicianD.php" class="active"><img src="https://img.icons8.com/parakeet/35/dashboard.png" alt="dashboard"/><span>Dashboard</span></a></li>
            <li><a href="assetsT.php"><img src="https://img.icons8.com/matisse/100/view.png" alt="view"/><span>View Assets</span></a></li>
            <li><a href="techBorrowed.php"> <img src="https://img.icons8.com/cotton/35/documents--v1.png" alt="documents--v1"/> <span>Borrowed Records</span></a></li>
        </ul>
        <footer>
            <a href="index.php" class="back-home"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </footer>
    </div>
    <div class="container">
        <div class="upper">
            <h1>Technician Dashboard</h1>
            <div class="search-container">
                <input type="text" class="search-bar" id="searchInput" placeholder="Search tickets..." value="<?php echo htmlspecialchars($searchTerm); ?>" onkeyup="debouncedSearchTickets()">
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

        <!-- Dashboard Cards Section -->
        <div class="dashboard-cards">
            <div class="card">
                <i class="fas fa-tasks text-yellow-500"></i>
                <div class="card-content">
                    <h3>Pending Tasks</h3>
                    <p><strong><?php echo $pendingTasks; ?></strong></p>
                    <p>Regular Open: <?php echo $openTickets; ?> | Support Open: <?php echo $supportOpen; ?></p>
                </div>
            </div>
            <div class="card">
                <i class="fas fa-ticket-alt text-orange-500"></i>
                <div class="card-content">
                    <h3>Regular Tickets</h3>
                    <p>Open: <?php echo $openTickets; ?> | Closed: <?php echo $closedTickets; ?></p>
                    <p>Archived: <?php echo $archivedRegular; ?></p>
                </div>
            </div>
            <div class="card">
                <i class="fas fa-headset text-blue-500"></i>
                <div class="card-content">
                    <h3>Support Tickets</h3>
                    <p>Open: <?php echo $supportOpen; ?> | Closed: <?php echo $supportClosed; ?></p>
                    <p>Archived: <?php echo $archivedSupport; ?></p>
                </div>
            </div>
        </div>

        <!-- Modal STRUCTURE -->
        <div id="actionModal" class="modal">
            <div class="modal-content">
                <div class="modal-header"></div>
                <div class="modal-body"></div>
                <div class="modal-footer"></div>
            </div>
        </div>

        <!-- Close Ticket Modal -->
        <div id="closeModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Close Ticket</h2>
                </div>
                <div class="modal-body">
                    <p>Confirm closing ticket ID <span id="closeTicketIdDisplay"></span> for <span id="closeTicketName"></span>.</p>
                    <label for="technicianFirstName">Enter Your First Name</label>
                    <input type="text" id="technicianFirstName" name="technicianFirstName" required>
                    <p id="closeError" style="color: red; display: none;"></p>
                </div>
                <div class="modal-footer">
                    <button class="modal-btn cancel" onclick="closeModal('closeModal')">Cancel</button>
                    <button class="modal-btn confirm" id="confirmCloseBtn" onclick="submitCloseAction()">Confirm</button>
                </div>
            </div>
        </div>

        <!-- Hidden Form for Action Submission -->
        <form id="actionForm" method="POST" style="display: none;">
            <input type="hidden" name="action" id="formAction">
            <input type="hidden" name="id" id="formId">
            <input type="hidden" name="type" id="formType">
        </form>

        <div class="tab-container">
            <!-- Main Tab Buttons -->
            <div class="main-tab-buttons">
                <button class="tab-button <?php echo in_array($tab, ['regular', 'regularArchived']) ? 'active' : ''; ?>" onclick="openMainTab('regularTickets', '<?php echo $tab === 'regularArchived' ? 'regularArchived' : 'regular'; ?>')">Regular Tickets</button>
                <button class="tab-button <?php echo in_array($tab, ['support', 'supportArchived']) ? 'active' : ''; ?>" onclick="openMainTab('supportTickets', '<?php echo $tab === 'supportArchived' ? 'supportArchived' : 'support'; ?>')">Support Tickets</button>
            </div>

            <!-- Regular Tickets Section -->
            <div id="regularTickets" class="main-tab-content <?php echo in_array($tab, ['regular', 'regularArchived']) ? 'active' : ''; ?>">
                <div class="table-box">
                    <div class="sub-tab-buttons">
                        <button class="tab-button <?php echo $tab === 'regular' ? 'active' : ''; ?>" onclick="openSubTab('regularTicketsContent', 'regular')">Active (<?php echo $totalRegularActive; ?>)</button>
                        <button class="tab-button <?php echo $tab === 'regularArchived' ? 'active' : ''; ?>" onclick="openSubTab('regularArchivedTicketsContent', 'regularArchived')">
                            Archived (<?php echo $totalRegularArchived; ?>)
                            <?php if ($totalRegularArchived > 0): ?>
                                <span class="tab-badge"><?php echo $totalRegularArchived; ?></span>
                            <?php endif; ?>
                        </button>
                    </div>

                    <!-- Active Regular Tickets -->
                    <div id="regularTicketsContent" class="sub-tab-content <?php echo $tab === 'regular' ? 'active' : ''; ?>">
                        <table class="tickets-table" id="regular-active-tickets">
                            <thead>
                                <tr>
                                    <th>Ticket ID</th>
                                    <th>Customer Name</th>
                                    <th>Subject</th>
                                    <th>Message</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($resultRegularActive && $resultRegularActive->num_rows > 0) {
                                    while ($row = $resultRegularActive->fetch_assoc()) {
                                        $display_details = preg_replace('/^ARCHIVED:/', '', $row['t_details'] ?? '');
                                        $ticketData = json_encode([
                                            'id' => $row['t_id'],
                                            'aname' => $row['t_aname'] ?? '',
                                            'subject' => $row['t_subject'] ?? '',
                                            'details' => $display_details,
                                            'status' => ucfirst(strtolower($row['t_status'] ?? '')),
                                            'date' => $row['t_date'] ?? '-'
                                        ], JSON_HEX_QUOT | JSON_HEX_TAG);
                                        echo "<tr>
                                                <td>" . htmlspecialchars($row['t_id']) . "</td>
                                                <td>" . htmlspecialchars($row['t_aname'] ?? '') . "</td>
                                                <td>" . htmlspecialchars($row['t_subject'] ?? '') . "</td>
                                                <td>" . htmlspecialchars($display_details) . "</td>
                                                <td class='status-" . strtolower(str_replace(' ', '-', $row['t_status'] ?? '')) . 
                                                (strtolower($row['t_status']) === 'open' ? " clickable' onclick='openCloseModal({$row['t_id']}, \"" . htmlspecialchars($row['t_aname'], ENT_QUOTES, 'UTF-8') . "\", \"regular\")'" : "'") . 
                                                ">" . ucfirst(strtolower($row['t_status'] ?? '')) . "</td>
                                                <td>" . htmlspecialchars($row['t_date'] ?? '-') . "</td>
                                                <td class='action-buttons'>
                                                    <span class='view-btn' onclick='openModal(\"view\", \"regular\", $ticketData)' title='View'><i class='fas fa-eye'></i></span>
                                                    <span class='archive-btn' onclick='openModal(\"archive\", \"regular\", {\"id\": {$row['t_id']}})' title='Archive'><i class='fas fa-archive'></i></span>
                                                </td>
                                              </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='7' style='text-align: center;'>No active regular tickets found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                        <div class="pagination" id="regular-active-pagination">
                            <?php
                            $paginationParams = "&supportActivePage=$supportActivePage&regularArchivedPage=$regularArchivedPage&supportArchivedPage=$supportArchivedPage" . ($searchTerm ? "&search=" . urlencode($searchTerm) : '');
                            if ($regularActivePage > 1): ?>
                                <a href="?tab=regular&regularActivePage=<?php echo $regularActivePage - 1; ?><?php echo $paginationParams; ?>" class="pagination-link"><i class="fas fa-chevron-left"></i></a>
                            <?php else: ?>
                                <span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>
                            <?php endif; ?>
                            <span class="current-page">Page <?php echo $regularActivePage; ?> of <?php echo $totalRegularActivePages; ?></span>
                            <?php if ($regularActivePage < $totalRegularActivePages): ?>
                                <a href="?tab=regular&regularActivePage=<?php echo $regularActivePage + 1; ?><?php echo $paginationParams; ?>" class="pagination-link"><i class="fas fa-chevron-right"></i></a>
                            <?php else: ?>
                                <span class="pagination-link disabled"><i class="fas fa-chevron-right"></i></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Regular Archived Tickets -->
                    <div id="regularArchivedTicketsContent" class="sub-tab-content <?php echo $tab === 'regularArchived' ? 'active' : ''; ?>">
                        <table class="tickets-table" id="regular-archived-tickets">
                            <thead>
                                <tr>
                                    <th>Ticket ID</th>
                                    <th>Customer Name</th>
                                    <th>Subject</th>
                                    <th>Message</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($resultRegularArchived && $resultRegularArchived->num_rows > 0) {
                                    while ($row = $resultRegularArchived->fetch_assoc()) {
                                        $display_details = preg_replace('/^ARCHIVED:/', '', $row['t_details'] ?? '');
                                        $ticketData = json_encode([
                                            'id' => $row['t_id'],
                                            'aname' => $row['t_aname'] ?? '',
                                            'subject' => $row['t_subject'] ?? '',
                                            'details' => $display_details,
                                            'status' => ucfirst(strtolower($row['t_status'] ?? '')),
                                            'date' => $row['t_date'] ?? '-'
                                        ], JSON_HEX_QUOT | JSON_HEX_TAG);
                                        echo "<tr>
                                                <td>" . htmlspecialchars($row['t_id']) . "</td>
                                                <td>" . htmlspecialchars($row['t_aname'] ?? '') . "</td>
                                                <td>" . htmlspecialchars($row['t_subject'] ?? '') . "</td>
                                                <td>" . htmlspecialchars($display_details) . "</td>
                                                <td class='status-" . strtolower(str_replace(' ', '-', $row['t_status'] ?? '')) . "'>" . ucfirst(strtolower($row['t_status'] ?? '')) . "</td>
                                                <td>" . htmlspecialchars($row['t_date'] ?? '-') . "</td>
                                                <td class='action-buttons'>
                                                    <span class='view-btn' onclick='openModal(\"view\", \"regular\", $ticketData)' title='View'><i class='fas fa-eye'></i></span>
                                                    <span class='unarchive-btn' onclick='openModal(\"unarchive\", \"regular\", {\"id\": {$row['t_id']}})' title='Unarchive'><i class='fas fa-box-open'></i></span>
                                                    <span class='delete-btn' onclick='openModal(\"delete\", \"regular\", {\"id\": {$row['t_id']}})' title='Delete'><i class='fas fa-trash'></i></span>
                                                </td>
                                              </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='7' style='text-align: center;'>No archived regular tickets found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                        <div class="pagination" id="regular-archived-pagination">
                            <?php
                            $paginationParams = "&regularActivePage=$regularActivePage&supportActivePage=$supportActivePage&supportArchivedPage=$supportArchivedPage" . ($searchTerm ? "&search=" . urlencode($searchTerm) : '');
                            if ($regularArchivedPage > 1): ?>
                                <a href="?tab=regularArchived&regularArchivedPage=<?php echo $regularArchivedPage - 1; ?><?php echo $paginationParams; ?>" class="pagination-link"><i class="fas fa-chevron-left"></i></a>
                            <?php else: ?>
                                <span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>
                            <?php endif; ?>
                            <span class="current-page">Page <?php echo $regularArchivedPage; ?> of <?php echo $totalRegularArchivedPages; ?></span>
                            <?php if ($regularArchivedPage < $totalRegularArchivedPages): ?>
                                <a href="?tab=regularArchived&regularArchivedPage=<?php echo $regularArchivedPage + 1; ?><?php echo $paginationParams; ?>" class="pagination-link"><i class="fas fa-chevron-right"></i></a>
                            <?php else: ?>
                                <span class="pagination-link disabled"><i class="fas fa-chevron-right"></i></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Support Tickets Section -->
            <div id="supportTickets" class="main-tab-content <?php echo in_array($tab, ['support', 'supportArchived']) ? 'active' : ''; ?>">
                <div class="table-box">
                    <div class="sub-tab-buttons">
                        <button class="tab-button <?php echo $tab === 'support' ? 'active' : ''; ?>" onclick="openSubTab('supportTicketsContent', 'support')">Active (<?php echo $totalSupportActive; ?>)</button>
                        <button class="tab-button <?php echo $tab === 'supportArchived' ? 'active' : ''; ?>" onclick="openSubTab('supportArchivedTicketsContent', 'supportArchived')">
                            Archived (<?php echo $totalSupportArchived; ?>)
                            <?php if ($totalSupportArchived > 0): ?>
                                <span class="tab-badge"><?php echo $totalSupportArchived; ?></span>
                            <?php endif; ?>
                        </button>
                    </div>

                    <!-- Active Support Tickets -->
                    <div id="supportTicketsContent" class="sub-tab-content <?php echo $tab === 'support' ? 'active' : ''; ?>">
                        <table class="tickets-table" id="support-active-tickets">
                            <thead>
                                <tr>
                                    <th>Ticket ID</th>
                                    <th>Customer Name</th>
                                    <th>Subject</th>
                                    <th>Reference</th>
                                    <th>Message</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($resultSupportActive && $resultSupportActive->num_rows > 0) {
                                    while ($row = $resultSupportActive->fetch_assoc()) {
                                        $display_details = preg_replace('/^ARCHIVED:/', '', $row['t_details'] ?? '');
                                        $ticketData = json_encode([
                                            'id' => $row['t_id'],
                                            'aname' => $row['t_aname'] ?? '',
                                            'subject' => $row['s_subject'] ?? '',
                                            'ref' => $row['s_ref'] ?? '',
                                            'details' => $display_details,
                                            'status' => ucfirst(strtolower($row['t_status'] ?? '')),
                                            'date' => '-'
                                        ], JSON_HEX_QUOT | JSON_HEX_TAG);
                                        echo "<tr>
                                                <td>" . htmlspecialchars($row['t_id']) . "</td>
                                                <td>" . htmlspecialchars($row['t_aname'] ?? '') . "</td>
                                                <td>" . htmlspecialchars($row['s_subject'] ?? '') . "</td>
                                                <td>" . htmlspecialchars($row['s_ref'] ?? '') . "</td>
                                                <td>" . htmlspecialchars($display_details) . "</td>
                                                <td class='status-" . strtolower(str_replace(' ', '-', $row['t_status'] ?? '')) . 
                                                (strtolower($row['t_status']) === 'open' ? " clickable' onclick='openCloseModal({$row['t_id']}, \"" . htmlspecialchars($row['t_aname'], ENT_QUOTES, 'UTF-8') . "\", \"support\")'" : "'") . 
                                                ">" . ucfirst(strtolower($row['t_status'] ?? '')) . "</td>
                                                <td class='action-buttons'>
                                                    <span class='view-btn' onclick='openModal(\"view\", \"support\", $ticketData)' title='View'><i class='fas fa-eye'></i></span>
                                                    <span class='archive-btn' onclick='openModal(\"archive\", \"support\", {\"id\": {$row['t_id']}})' title='Archive'><i class='fas fa-archive'></i></span>
                                                </td>
                                              </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='7' style='text-align: center;'>No active support tickets found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                        <div class="pagination" id="support-active-pagination">
                            <?php
                            $paginationParams = "&regularActivePage=$regularActivePage&regularArchivedPage=$regularArchivedPage&supportArchivedPage=$supportArchivedPage" . ($searchTerm ? "&search=" . urlencode($searchTerm) : '');
                            if ($supportActivePage > 1): ?>
                                <a href="?tab=support&supportActivePage=<?php echo $supportActivePage - 1; ?><?php echo $paginationParams; ?>" class="pagination-link"><i class="fas fa-chevron-left"></i></a>
                            <?php else: ?>
                                <span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>
                            <?php endif; ?>
                            <span class="current-page">Page <?php echo $supportActivePage; ?> of <?php echo $totalSupportActivePages; ?></span>
                            <?php if ($supportActivePage < $totalSupportActivePages): ?>
                                <a href="?tab=support&supportActivePage=<?php echo $supportActivePage + 1; ?><?php echo $paginationParams; ?>" class="pagination-link"><i class="fas fa-chevron-right"></i></a>
                            <?php else: ?>
                                <span class="pagination-link disabled"><i class="fas fa-chevron-right"></i></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Support Archived Tickets -->
                    <div id="supportArchivedTicketsContent" class="sub-tab-content <?php echo $tab === 'supportArchived' ? 'active' : ''; ?>">
                        <table class="tickets-table" id="support-archived-tickets">
                            <thead>
                                <tr>
                                    <th>Ticket ID</th>
                                    <th>Customer Name</th>
                                    <th>Subject</th>
                                    <th>Reference</th>
                                    <th>Message</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($resultSupportArchived && $resultSupportArchived->num_rows > 0) {
                                    while ($row = $resultSupportArchived->fetch_assoc()) {
                                        $display_details = preg_replace('/^ARCHIVED:/', '', $row['t_details'] ?? '');
                                        $ticketData = json_encode([
                                            'id' => $row['t_id'],
                                            'aname' => $row['t_aname'] ?? '',
                                            'subject' => $row['s_subject'] ?? '',
                                            'ref' => $row['s_ref'] ?? '',
                                            'details' => $display_details,
                                            'status' => ucfirst(strtolower($row['t_status'] ?? '')),
                                            'date' => '-'
                                        ], JSON_HEX_QUOT | JSON_HEX_TAG);
                                        echo "<tr>
                                                <td>" . htmlspecialchars($row['t_id']) . "</td>
                                                <td>" . htmlspecialchars($row['t_aname'] ?? '') . "</td>
                                                <td>" . htmlspecialchars($row['s_subject'] ?? '') . "</td>
                                                <td>" . htmlspecialchars($row['s_ref'] ?? '') . "</td>
                                                <td>" . htmlspecialchars($display_details) . "</td>
                                                <td class='status-" . strtolower(str_replace(' ', '-', $row['t_status'] ?? '')) . "'>" . ucfirst(strtolower($row['t_status'] ?? '')) . "</td>
                                                <td class='action-buttons'>
                                                    <span class='view-btn' onclick='openModal(\"view\", \"support\", $ticketData)' title='View'><i class='fas fa-eye'></i></span>
                                                    <span class='unarchive-btn' onclick='openModal(\"unarchive\", \"support\", {\"id\": {$row['t_id']}})' title='Unarchive'><i class='fas fa-box-open'></i></span>
                                                    <span class='delete-btn' onclick='openModal(\"delete\", \"support\", {\"id\": {$row['t_id']}})' title='Delete'><i class='fas fa-trash'></i></span>
                                                </td>
                                              </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='7' style='text-align: center;'>No archived support tickets found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                        <div class="pagination" id="support-archived-pagination">
                            <?php
                            $paginationParams = "&regularActivePage=$regularActivePage&regularArchivedPage=$regularArchivedPage&supportActivePage=$supportActivePage" . ($searchTerm ? "&search=" . urlencode($searchTerm) : '');
                            if ($supportArchivedPage > 1): ?>
                                <a href="?tab=supportArchived&supportArchivedPage=<?php echo $supportArchivedPage - 1; ?><?php echo $paginationParams; ?>" class="pagination-link"><i class="fas fa-chevron-left"></i></a>
                            <?php else: ?>
                                <span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>
                            <?php endif; ?>
                            <span class="current-page">Page <?php echo $supportArchivedPage; ?> of <?php echo $totalSupportArchivedPages; ?></span>
                            <?php if ($supportArchivedPage < $totalSupportArchivedPages): ?>
                                <a href="?tab=supportArchived&supportArchivedPage=<?php echo $supportArchivedPage + 1; ?><?php echo $paginationParams; ?>" class="pagination-link"><i class="fas fa-chevron-right"></i></a>
                            <?php else: ?>
                                <span class="pagination-link disabled"><i class="fas fa-chevron-right"></i></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function openMainTab(tabName, subTab) {
    const mainTabContents = document.getElementsByClassName('main-tab-content');
    for (let i = 0; i < mainTabContents.length; i++) {
        mainTabContents[i].classList.remove('active');
    }
    const mainTabButtons = document.getElementsByClassName('main-tab-buttons')[0].getElementsByClassName('tab-button');
    for (let i = 0; i < mainTabButtons.length; i++) {
        mainTabButtons[i].classList.remove('active');
    }
    document.getElementById(tabName).classList.add('active');
    const activeMainButton = document.querySelector(`[onclick="openMainTab('${tabName}', '${subTab}')"]`);
    if (activeMainButton) {
        activeMainButton.classList.add('active');
    }
    openSubTab(subTab === 'regular' ? 'regularTicketsContent' : 
               subTab === 'regularArchived' ? 'regularArchivedTicketsContent' : 
               subTab === 'support' ? 'supportTicketsContent' : 
               'supportArchivedTicketsContent', subTab);
}

function openSubTab(contentId, tabParam) {
    const mainTabId = contentId.startsWith('regular') ? 'regularTickets' : 'supportTickets';
    const mainTab = document.getElementById(mainTabId);
    const subTabContents = mainTab.getElementsByClassName('sub-tab-content');
    for (let i = 0; i < subTabContents.length; i++) {
        subTabContents[i].classList.remove('active');
    }
    const subTabButtons = mainTab.getElementsByClassName('sub-tab-buttons')[0].getElementsByClassName('tab-button');
    for (let i = 0; i < subTabButtons.length; i++) {
        subTabButtons[i].classList.remove('active');
    }
    const contentElement = document.getElementById(contentId);
    if (contentElement) {
        contentElement.classList.add('active');
    }
    const activeButton = mainTab.querySelector(`[onclick="openSubTab('${contentId}', '${tabParam}')"]`);
    if (activeButton) {
        activeButton.classList.add('active');
    }
    const url = new URL(window.location);
    url.searchParams.set('tab', tabParam);
    window.history.pushState({}, '', url);
}

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
    const activeTab = document.querySelector('.sub-tab-content.active');
    if (!activeTab) return;
    const tab = activeTab.id.includes('regularTicketsContent') ? 'regular' :
                activeTab.id.includes('regularArchivedTicketsContent') ? 'regularArchived' :
                activeTab.id.includes('supportTicketsContent') ? 'support' : 'supportArchived';
    
    const url = new URL(window.location);
    url.searchParams.set('tab', tab);
    if (searchTerm) {
        url.searchParams.set('search', searchTerm);
    } else {
        url.searchParams.delete('search');
    }
    if (tab === 'regular') {
        url.searchParams.set('regularActivePage', page);
    } else if (tab === 'regularArchived') {
        url.searchParams.set('regularArchivedPage', page);
    } else if (tab === 'support') {
        url.searchParams.set('supportActivePage', page);
    } else if (tab === 'supportArchived') {
        url.searchParams.set('supportArchivedPage', page);
    }
    window.location.href = url.toString();
}

const debouncedSearchTickets = debounce(searchTickets, 300);

function openModal(action, type, data) {
    const modal = document.getElementById('actionModal');
    const modalHeader = modal.querySelector('.modal-header');
    const modalBody = modal.querySelector('.modal-body');
    const modalFooter = modal.querySelector('.modal-footer');

    modalHeader.innerHTML = '';
    modalBody.innerHTML = '';
    modalFooter.innerHTML = '';

    if (action === 'view') {
        modalHeader.textContent = `View Ticket #${data.id}`;
        if (type === 'regular') {
            modalBody.innerHTML = `
                <div class="ticket-details">
                    <p><strong>Ticket ID:</strong> ${data.id}</p>
                    <p><strong>Customer Name:</strong> ${data.aname}</p>
                    <p><strong>Subject:</strong> ${data.subject}</p>
                    <p><strong>Message:</strong> ${data.details}</p>
                    <p><strong>Status:</strong> ${data.status}</p>
                    <p><strong>Date:</strong> ${data.date}</p>
                </div>
            `;
        } else {
            modalBody.innerHTML = `
                <div class="ticket-details">
                    <p><strong>Ticket ID:</strong> ${data.id}</p>
                    <p><strong>Customer Name:</strong> ${data.aname}</p>
                    <p><strong>Subject:</strong> ${data.subject}</p>
                    <p><strong>Reference:</strong> ${data.ref}</p>
                    <p><strong>Message:</strong> ${data.details}</p>
                    <p><strong>Status:</strong> ${data.status}</p>
                </div>
            `;
        }
        modalFooter.innerHTML = `
            <button class="modal-btn close" onclick="closeModal('actionModal')">Close</button>
        `;
    } else {
        let actionText = action.charAt(0).toUpperCase() + action.slice(1);
        modalHeader.textContent = `${actionText} Ticket #${data.id}`;
        modalBody.innerHTML = `<p>Are you sure you want to ${action} this ticket?</p>`;
        modalFooter.innerHTML = `
            <button class="modal-btn cancel" onclick="closeModal('actionModal')">Cancel</button>
            <button class="modal-btn confirm" onclick="submitAction('${action}', '${type}', ${data.id})">Confirm</button>
        `;
    }

    modal.style.display = 'block';
    document.body.classList.add('modal-open');
}

function openCloseModal(id, aname, type) {
    document.getElementById('closeTicketIdDisplay').textContent = id;
    document.getElementById('closeTicketName').textContent = aname;
    document.getElementById('technicianFirstName').value = '';
    document.getElementById('closeError').style.display = 'none';
    document.getElementById('closeModal').dataset.ticketType = type;
    document.getElementById('closeModal').style.display = 'block';
    document.body.classList.add('modal-open');
}

function submitCloseAction() {
    const id = document.getElementById('closeTicketIdDisplay').textContent;
    const technicianFirstName = document.getElementById('technicianFirstName').value.trim();
    const type = document.getElementById('closeModal').dataset.ticketType;
    const errorElement = document.getElementById('closeError');
    const confirmBtn = document.getElementById('confirmCloseBtn');

    if (!technicianFirstName) {
        errorElement.textContent = 'Please enter your first name.';
        errorElement.style.display = 'block';
        return;
    }

    confirmBtn.disabled = true;
    confirmBtn.textContent = 'Closing...';

    const formData = new FormData();
    formData.append('action', 'close');
    formData.append('id', id);
    formData.append('type', type);
    formData.append('technicianFirstName', technicianFirstName);

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeModal('closeModal');
            setTimeout(() => {
                window.location.href = data.redirect;
            }, 300);
        } else {
            errorElement.textContent = data.message;
            errorElement.style.display = 'block';
            confirmBtn.disabled = false;
            confirmBtn.textContent = 'Confirm';
        }
    })
    .catch(error => {
        errorElement.textContent = 'An error occurred. Please try again.';
        errorElement.style.display = 'block';
        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Confirm';
        console.error('Error:', error);
    });
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.style.display = 'none';
    document.body.classList.remove('modal-open');
}

function submitAction(action, type, id) {
    document.getElementById('formAction').value = action;
    document.getElementById('formId').value = id;
    document.getElementById('formType').value = type;
    document.getElementById('actionForm').submit();
}

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab') || 'regular';
    if (tab === 'support' || tab === 'supportArchived') {
        openMainTab('supportTickets', tab);
    } else {
        openMainTab('regularTickets', tab);
    }
});
</script>
</body>
</html>

<?php
$conn->close();
?>