<?php
session_start();
include 'db.php'; // Include your database connection file

$username = $_SESSION['username'] ?? '';
$userId = $_SESSION['userId'] ?? 0;

if (!$username || !$userId) {
    echo "Unauthorized access. Please log in.";
    exit();
}

// Get username from session
$username = $_SESSION['username'] ?? 'tech_user';

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
$regularActiveOffset = ($regularActivePage - 1) * $limit;
$supportActiveOffset = ($supportActivePage - 1) * $limit;
$regularArchivedOffset = ($regularArchivedPage - 1) * $limit;
$supportArchivedOffset = ($supportArchivedPage - 1) * $limit;
$tab = $_GET['tab'] ?? 'regular'; // Main tab: regular, support, regularArchived, supportArchived

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

    // Regular Ticket counts
    $sqlOpenTickets = "SELECT COUNT(*) AS openTickets FROM tbl_ticket WHERE t_status = 'open' AND (t_details NOT LIKE 'ARCHIVED:%' OR t_details IS NULL)";
    $resultOpenTickets = $conn->query($sqlOpenTickets);
    $openTickets = $resultOpenTickets ? ($resultOpenTickets->fetch_assoc()['openTickets'] ?? 0) : 0;

    $sqlClosedTickets = "SELECT COUNT(*) AS closedTickets FROM tbl_ticket WHERE t_status = 'closed' AND (t_details NOT LIKE 'ARCHIVED:%' OR t_details IS NULL)";
    $resultClosedTickets = $conn->query($sqlClosedTickets);
    $closedTickets = $resultClosedTickets ? ($resultClosedTickets->fetch_assoc()['closedTickets'] ?? 0) : 0;

    $sqlArchivedRegular = "SELECT COUNT(*) AS archivedTickets FROM tbl_ticket WHERE t_details LIKE 'ARCHIVED:%'";
    $resultArchivedRegular = $conn->query($sqlArchivedRegular);
    $archivedRegular = $resultArchivedRegular ? ($resultArchivedRegular->fetch_assoc()['archivedTickets'] ?? 0) : 0;

    // Support Tickets status breakdown
    $sqlSupportOpen = "SELECT COUNT(*) AS supportOpen FROM tbl_supp_tickets WHERE s_status = 'Open' AND (s_message NOT LIKE 'ARCHIVED:%' OR s_message IS NULL)";
    $resultSupportOpen = $conn->query($sqlSupportOpen);
    $supportOpen = $resultSupportOpen ? ($resultSupportOpen->fetch_assoc()['supportOpen'] ?? 0) : 0;

    $sqlSupportClosed = "SELECT COUNT(*) AS supportClosed FROM tbl_supp_tickets WHERE s_status = 'Closed' AND (s_message NOT LIKE 'ARCHIVED:%' OR s_message IS NULL)";
    $resultSupportClosed = $conn->query($sqlSupportClosed);
    $supportClosed = $resultSupportClosed ? ($resultSupportClosed->fetch_assoc()['supportClosed'] ?? 0) : 0;

    $sqlArchivedSupport = "SELECT COUNT(*) AS archivedSupport FROM tbl_supp_tickets WHERE s_message LIKE 'ARCHIVED:%'";
    $resultArchivedSupport = $conn->query($sqlArchivedSupport);
    $archivedSupport = $resultArchivedSupport ? ($resultArchivedSupport->fetch_assoc()['archivedSupport'] ?? 0) : 0;

    // Pending tasks
    $pendingTasks = $openTickets + $supportOpen;

    // Handle actions (triggered via POST from modal)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['id']) && isset($_POST['type'])) {
        $id = (int)$_POST['id'];
        $type = $_POST['type'];
        $action = $_POST['action'];
        $targetTab = $tab;

        if ($action === 'archive') {
            if ($type === 'regular') {
                $sql = "SELECT t_details FROM tbl_ticket WHERE t_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $current_details = $row['t_details'] ?? '';
                $stmt->close();
                $new_details = 'ARCHIVED:' . $current_details;
                $sql = "UPDATE tbl_ticket SET t_details = ? WHERE t_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $new_details, $id);
                $targetTab = 'regularArchived';
            } else {
                $sql = "SELECT s_message FROM tbl_supp_tickets WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $current_message = $row['s_message'] ?? '';
                $stmt->close();
                $new_message = 'ARCHIVED:' . $current_message;
                $sql = "UPDATE tbl_supp_tickets SET s_message = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $new_message, $id);
                $targetTab = 'supportArchived';
            }
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'unarchive') {
            if ($type === 'regular') {
                $sql = "SELECT t_details FROM tbl_ticket WHERE t_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $current_details = $row['t_details'] ?? '';
                $stmt->close();
                $new_details = preg_replace('/^ARCHIVED:/', '', $current_details);
                $sql = "UPDATE tbl_ticket SET t_details = ? WHERE t_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $new_details, $id);
                $targetTab = 'regular';
            } else {
                $sql = "SELECT s_message FROM tbl_supp_tickets WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $current_message = $row['s_message'] ?? '';
                $stmt->close();
                $new_message = preg_replace('/^ARCHIVED:/', '', $current_message);
                $sql = "UPDATE tbl_supp_tickets SET s_message = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $new_message, $id);
                $targetTab = 'support';
            }
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'delete') {
            if ($type === 'regular') {
                $sql = "DELETE FROM tbl_ticket WHERE t_id = ? AND t_details LIKE 'ARCHIVED:%'";
                $targetTab = 'regularArchived';
            } else {
                $sql = "DELETE FROM tbl_supp_tickets WHERE id = ? AND s_message LIKE 'ARCHIVED:%'";
                $targetTab = 'supportArchived';
            }
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }

        // Fixed typo in redirect URL parameters
        $redirect_url = "technicianD.php?tab=" . urlencode($targetTab) .
                        "&regularActivePage=" . urlencode($regularActivePage) .
                        "&supportActivePage=" . urlencode($supportActivePage) .
                        "&regularArchivedPage=" . urlencode($regularArchivedPage) .
                        "&supportArchivedPage=" . urlencode($supportArchivedPage);
        header("Location: $redirect_url");
        exit;
    }

    // Pagination for Regular Tickets - Active
    $sqlTotalRegularActive = "SELECT COUNT(*) AS total FROM tbl_ticket WHERE (t_details NOT LIKE 'ARCHIVED:%' OR t_details IS NULL)";
    $resultTotalRegularActive = $conn->query($sqlTotalRegularActive);
    $totalRegularActive = $resultTotalRegularActive ? ($resultTotalRegularActive->fetch_assoc()['total'] ?? 0) : 0;
    error_log("Regular Active Total: $totalRegularActive"); // Debug log
    $totalRegularActivePages = ceil($totalRegularActive / $limit) ?: 1;
    $regularActivePage = min($regularActivePage, $totalRegularActivePages);
    $regularActiveOffset = ($regularActivePage - 1) * $limit;

    $sqlRegularActive = "SELECT t_id, t_aname, t_type, t_details, t_status, t_date 
                        FROM tbl_ticket 
                        WHERE (t_details NOT LIKE 'ARCHIVED:%' OR t_details IS NULL) 
                        ORDER BY t_date ASC LIMIT ? OFFSET ?";
    $stmtRegularActive = $conn->prepare($sqlRegularActive);
    $stmtRegularActive->bind_param("ii", $limit, $regularActiveOffset);
    $stmtRegularActive->execute();
    $resultRegularActive = $stmtRegularActive->get_result();
    error_log("Regular Active Rows: " . $resultRegularActive->num_rows); // Debug log
    $stmtRegularActive->close();

    // Pagination for Support Tickets - Active
    $sqlTotalSupportActive = "SELECT COUNT(*) AS total FROM tbl_supp_tickets WHERE (s_message NOT LIKE 'ARCHIVED:%' OR s_message IS NULL)";
    $resultTotalSupportActive = $conn->query($sqlTotalSupportActive);
    $totalSupportActive = $resultTotalSupportActive ? ($resultTotalSupportActive->fetch_assoc()['total'] ?? 0) : 0;
    $totalSupportActivePages = ceil($totalSupportActive / $limit) ?: 1;
    $supportActivePage = min($supportActivePage, $totalSupportActivePages);
    $supportActiveOffset = ($supportActivePage - 1) * $limit;

    $sqlSupportActive = "SELECT id AS t_id, CONCAT(c_fname, ' ', c_lname) AS t_aname, s_type, s_message AS t_details, s_status AS t_status 
                        FROM tbl_supp_tickets 
                        WHERE (s_message NOT LIKE 'ARCHIVED:%' OR s_message IS NULL) 
                        ORDER BY id ASC LIMIT ? OFFSET ?";
    $stmtSupportActive = $conn->prepare($sqlSupportActive);
    $stmtSupportActive->bind_param("ii", $limit, $supportActiveOffset);
    $stmtSupportActive->execute();
    $resultSupportActive = $stmtSupportActive->get_result();
    $stmtSupportActive->close();

    // Pagination for Regular Tickets - Archived
    $sqlTotalRegularArchived = "SELECT COUNT(*) AS total FROM tbl_ticket WHERE t_details LIKE 'ARCHIVED:%'";
    $resultTotalRegularArchived = $conn->query($sqlTotalRegularArchived);
    $totalRegularArchived = $resultTotalRegularArchived ? ($resultTotalRegularArchived->fetch_assoc()['total'] ?? 0) : 0;
    $totalRegularArchivedPages = ceil($totalRegularArchived / $limit) ?: 1;
    $regularArchivedPage = min($regularArchivedPage, $totalRegularArchivedPages);
    $regularArchivedOffset = ($regularArchivedPage - 1) * $limit;

    $sqlRegularArchived = "SELECT t_id, t_aname, t_type, t_details, t_status, t_date 
                          FROM tbl_ticket 
                          WHERE t_details LIKE 'ARCHIVED:%' 
                          ORDER BY t_date ASC LIMIT ? OFFSET ?";
    $stmtRegularArchived = $conn->prepare($sqlRegularArchived);
    $stmtRegularArchived->bind_param("ii", $limit, $regularArchivedOffset);
    $stmtRegularArchived->execute();
    $resultRegularArchived = $stmtRegularArchived->get_result();
    $stmtRegularArchived->close();

    // Pagination for Support Tickets - Archived
    $sqlTotalSupportArchived = "SELECT COUNT(*) AS total FROM tbl_supp_tickets WHERE s_message LIKE 'ARCHIVED:%'";
    $resultTotalSupportArchived = $conn->query($sqlTotalSupportArchived);
    $totalSupportArchived = $resultTotalSupportArchived ? ($resultTotalSupportArchived->fetch_assoc()['total'] ?? 0) : 0;
    $totalSupportArchivedPages = ceil($totalSupportArchived / $limit) ?: 1;
    $supportArchivedPage = min($supportArchivedPage, $totalSupportArchivedPages);
    $supportArchivedOffset = ($supportArchivedPage - 1) * $limit;

    $sqlSupportArchived = "SELECT id AS t_id, CONCAT(c_fname, ' ', c_lname) AS t_aname, s_type, s_message AS t_details, s_status AS t_status 
                          FROM tbl_supp_tickets 
                          WHERE s_message LIKE 'ARCHIVED:%' 
                          ORDER BY id ASC LIMIT ? OFFSET ?";
    $stmtSupportArchived = $conn->prepare($sqlSupportArchived);
    $stmtSupportArchived->bind_param("ii", $limit, $supportArchivedOffset);
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
</head>
<body>
<div class="wrapper">
    <div class="sidebar glass-container">
        <h2>Task Management</h2>
        <ul>
            <li><a href="technicianD.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="staffD.php"><i class="fas fa-users"></i> Regular Tickets</a></li>
            <li><a href="suppT.php"><i class="fas fa-file-archive"></i> Support Tickets</a></li>
            <li><a href="assetsT.php"><i class="fas fa-box"></i>View Assets</a></li>
            <li><a href="techBorrowed.php"><i class="fas fa-box-open"></i>Borrowed Records</a></li>
        </ul>
        <footer>
            <a href="index.php" class="back-home"><i class="fas fa-home"></i> <span>Back to Home</span></a>
        </footer>
    </div>
    <div class="container">
        <div class="upper">
            <h1>Technician Dashboard</h1>
            <div class="search-container">
                <input type="text" class="search-bar" id="searchInput" placeholder="Search tickets..." onkeyup="searchTickets()">
                <span class="search-icon"><i class="fas fa-search"></i></span>
            </div>
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
                                    <th>Type</th>
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
                                            'type' => ucfirst(strtolower($row['t_type'] ?? '')),
                                            'details' => $display_details,
                                            'status' => ucfirst(strtolower($row['t_status'] ?? '')),
                                            'date' => $row['t_date'] ?? '-'
                                        ], JSON_HEX_QUOT | JSON_HEX_TAG);
                                        echo "<tr>
                                                <td>" . htmlspecialchars($row['t_id']) . "</td>
                                                <td>" . htmlspecialchars($row['t_aname'] ?? '') . "</td>
                                                <td>" . htmlspecialchars(ucfirst(strtolower($row['t_type'] ?? ''))) . "</td>
                                                <td>" . htmlspecialchars($display_details) . "</td>
                                                <td class='status-" . strtolower(str_replace(' ', '-', $row['t_status'] ?? '')) . "'>" . ucfirst(strtolower($row['t_status'] ?? '')) . "</td>
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
                        <div class="pagination">
                            <?php if ($regularActivePage > 1): ?>
                                <a href="?tab=regular&regularActivePage=<?php echo $regularActivePage - 1; ?>" class="pagination-link"><i class="fas fa-chevron-left"></i></a>
                            <?php else: ?>
                                <span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>
                            <?php endif; ?>
                            <span class="current-page">Page <?php echo $regularActivePage; ?> of <?php echo $totalRegularActivePages; ?></span>
                            <?php if ($regularActivePage < $totalRegularActivePages): ?>
                                <a href="?tab=regular&regularActivePage=<?php echo $regularActivePage + 1; ?>" class="pagination-link"><i class="fas fa-chevron-right"></i></a>
                            <?php else: ?>
                                <span class="pagination-link disabled"><i class="fas fa-chevron-right"></i></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Regular Archived Tickets -->
                    <div id="regularArchivedTicketsContent" class="sub-tab-content <?php echo $tab === 'regularArchived' ? 'active' : ''; ?>">
                        <h3>Regular Archived Tickets (<?php echo $totalRegularArchived; ?>)</h3>
                        <table class="tickets-table" id="regular-archived-tickets">
                            <thead>
                                <tr>
                                    <th>Ticket ID</th>
                                    <th>Customer Name</th>
                                    <th>Type</th>
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
                                            'type' => ucfirst(strtolower($row['t_type'] ?? '')),
                                            'details' => $display_details,
                                            'status' => ucfirst(strtolower($row['t_status'] ?? '')),
                                            'date' => $row['t_date'] ?? '-'
                                        ], JSON_HEX_QUOT | JSON_HEX_TAG);
                                        echo "<tr>
                                                <td>" . htmlspecialchars($row['t_id']) . "</td>
                                                <td>" . htmlspecialchars($row['t_aname'] ?? '') . "</td>
                                                <td>" . htmlspecialchars(ucfirst(strtolower($row['t_type'] ?? ''))) . "</td>
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
                        <div class="pagination">
                            <?php if ($regularArchivedPage > 1): ?>
                                <a href="?tab=regularArchived&regularArchivedPage=<?php echo $regularArchivedPage - 1; ?>" class="pagination-link"><i class="fas fa-chevron-left"></i></a>
                            <?php else: ?>
                                <span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>
                            <?php endif; ?>
                            <span class="current-page">Page <?php echo $regularArchivedPage; ?> of <?php echo $totalRegularArchivedPages; ?></span>
                            <?php if ($regularArchivedPage < $totalRegularArchivedPages): ?>
                                <a href="?tab=regularArchived&regularArchivedPage=<?php echo $regularArchivedPage + 1; ?>" class="pagination-link"><i class="fas fa-chevron-right"></i></a>
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
                                    <th>Type</th>
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
                                            'type' => ucfirst(strtolower($row['s_type'] ?? '')),
                                            'details' => $display_details,
                                            'status' => ucfirst(strtolower($row['t_status'] ?? '')),
                                            'date' => '-'
                                        ], JSON_HEX_QUOT | JSON_HEX_TAG);
                                        echo "<tr>
                                                <td>" . htmlspecialchars($row['t_id']) . "</td>
                                                <td>" . htmlspecialchars($row['t_aname'] ?? '') . "</td>
                                                <td>" . htmlspecialchars(ucfirst(strtolower($row['s_type'] ?? ''))) . "</td>
                                                <td>" . htmlspecialchars($display_details) . "</td>
                                                <td class='status-" . strtolower(str_replace(' ', '-', $row['t_status'] ?? '')) . "'>" . ucfirst(strtolower($row['t_status'] ?? '')) . "</td>
                                                <td class='action-buttons'>
                                                    <span class='view-btn' onclick='openModal(\"view\", \"support\", $ticketData)' title='View'><i class='fas fa-eye'></i></span>
                                                    <span class='archive-btn' onclick='openModal(\"archive\", \"support\", {\"id\": {$row['t_id']}})' title='Archive'><i class='fas fa-archive'></i></span>
                                                </td>
                                              </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6' style='text-align: center;'>No active support tickets found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                        <div class="pagination">
                            <?php if ($supportActivePage > 1): ?>
                                <a href="?tab=support&supportActivePage=<?php echo $supportActivePage - 1; ?>" class="pagination-link"><i class="fas fa-chevron-left"></i></a>
                            <?php else: ?>
                                <span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>
                            <?php endif; ?>
                            <span class="current-page">Page <?php echo $supportActivePage; ?> of <?php echo $totalSupportActivePages; ?></span>
                            <?php if ($supportActivePage < $totalSupportActivePages): ?>
                                <a href="?tab=support&supportActivePage=<?php echo $supportActivePage + 1; ?>" class="pagination-link"><i class="fas fa-chevron-right"></i></a>
                            <?php else: ?>
                                <span class="pagination-link disabled"><i class="fas fa-chevron-right"></i></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Support Archived Tickets -->
                    <div id="supportArchivedTicketsContent" class="sub-tab-content <?php echo $tab === 'supportArchived' ? 'active' : ''; ?>">
                        <h3>Support Archived Tickets (<?php echo $totalSupportArchived; ?>)</h3>
                        <table class="tickets-table" id="support-archived-tickets">
                            <thead>
                                <tr>
                                    <th>Ticket ID</th>
                                    <th>Customer Name</th>
                                    <th>Type</th>
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
                                            'type' => ucfirst(strtolower($row['s_type'] ?? '')),
                                            'details' => $display_details,
                                            'status' => ucfirst(strtolower($row['t_status'] ?? '')),
                                            'date' => '-'
                                        ], JSON_HEX_QUOT | JSON_HEX_TAG);
                                        echo "<tr>
                                                <td>" . htmlspecialchars($row['t_id']) . "</td>
                                                <td>" . htmlspecialchars($row['t_aname'] ?? '') . "</td>
                                                <td>" . htmlspecialchars(ucfirst(strtolower($row['s_type'] ?? ''))) . "</td>
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
                                    echo "<tr><td colspan='6' style='text-align: center;'>No archived support tickets found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                        <div class="pagination">
                            <?php if ($supportArchivedPage > 1): ?>
                                <a href="?tab=supportArchived&supportArchivedPage=<?php echo $supportArchivedPage - 1; ?>" class="pagination-link"><i class="fas fa-chevron-left"></i></a>
                            <?php else: ?>
                                <span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>
                            <?php endif; ?>
                            <span class="current-page">Page <?php echo $supportArchivedPage; ?> of <?php echo $totalSupportArchivedPages; ?></span>
                            <?php if ($supportArchivedPage < $totalSupportArchivedPages): ?>
                                <a href="?tab=supportArchived&supportArchivedPage=<?php echo $supportArchivedPage + 1; ?>" class="pagination-link"><i class="fas fa-chevron-right"></i></a>
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

function searchTickets() {
    const input = document.getElementById('searchInput').value.toLowerCase();
    const activeTab = document.querySelector('.sub-tab-content.active');
    if (!activeTab) return;
    const table = activeTab.querySelector('.tickets-table');
    if (!table) return;
    const rows = table.querySelector('tbody').getElementsByTagName('tr');
    for (let i = 0; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName('td');
        let match = false;
        for (let j = 0; j < cells.length - 1; j++) {
            if (cells[j].textContent.toLowerCase().includes(input)) {
                match = true;
                break;
            }
        }
        rows[i].style.display = match ? '' : 'none';
    }
}

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
        modalBody.innerHTML = `
            <div class="ticket-details">
                <p><strong>Ticket ID:</strong> ${data.id}</p>
                <p><strong>Customer Name:</strong> ${data.aname}</p>
                <p><strong>Type:</strong> ${data.type}</p>
                <p><strong>Message:</strong> ${data.details}</p>
                <p><strong>Status:</strong> ${data.status}</p>
                ${
                    data.date !== '-'
                        ? `<p><strong>Date:</strong> ${data.date}</p>`
                        : ''
                }
            </div>
        `;
        modalFooter.innerHTML = `
            <button class="modal-btn close" onclick="closeModal()">Close</button>
        `;
    } else {
        let actionText = action.charAt(0).toUpperCase() + action.slice(1);
        modalHeader.textContent = `${actionText} Ticket #${data.id}`;
        modalBody.innerHTML = `<p>Are you sure you want to ${action} this ticket?</p>`;
        modalFooter.innerHTML = `
            <button class="modal-btn cancel" onclick="closeModal()">Cancel</button>
            <button class="modal-btn confirm" onclick="submitAction('${action}', '${type}', ${data.id})">Confirm</button>
        `;
    }

    modal.style.display = 'block';
    document.body.classList.add('modal-open');
}

function closeModal() {
    const modal = document.getElementById('actionModal');
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