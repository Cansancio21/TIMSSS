<?php
include 'db.php';

session_start();
if (!isset($_SESSION['user'])) {
    header("Location: customerP.php");
    exit();
}

$user = $_SESSION['user'];
$c_id = htmlspecialchars($user['c_id']);
$c_lname = htmlspecialchars($user['c_lname']);
$c_fname = htmlspecialchars($user['c_fname']);
$username = "$c_fname $c_lname";

// Avatar handling
$avatarFolder = 'Uploads/avatars/';
$userAvatar = $avatarFolder . $username . '.png';
$avatarPath = file_exists($userAvatar) ? $userAvatar . '?' . time() : 'default-avatar.png';
$_SESSION['avatarPath'] = $avatarPath;

// Set userType
$userType = isset($user['user_type']) ? htmlspecialchars($user['user_type']) : 'customer';

// Pagination setup for active tickets
$limit = 10;
$activePage = isset($_GET['active_page']) ? (int)$_GET['active_page'] : 1;
$activeOffset = ($activePage - 1) * $limit;

// Get total number of active tickets (Open or Closed)
$totalActiveQuery = "SELECT COUNT(*) as total FROM tbl_supp_tickets WHERE c_id = ? AND s_status IN ('Open', 'Closed')";
$stmt = $conn->prepare($totalActiveQuery);
$stmt->bind_param("s", $c_id);
$stmt->execute();
$totalActiveResult = $stmt->get_result();
$totalActiveRow = $totalActiveResult->fetch_assoc();
$totalActiveTickets = $totalActiveRow['total'];
$totalActivePages = ceil($totalActiveTickets / $limit);
$stmt->close();

// Query active tickets
$activeQuery = "SELECT id, c_id, c_lname, c_fname, s_subject, s_type, s_message, s_status 
                FROM tbl_supp_tickets 
                WHERE c_id = ? AND s_status IN ('Open', 'Closed') 
                ORDER BY id ASC 
                LIMIT ? OFFSET ?";
$stmt = $conn->prepare($activeQuery);
$stmt->bind_param("sii", $c_id, $limit, $activeOffset);
$stmt->execute();
$activeResult = $stmt->get_result();
$stmt->close();

// Pagination setup for archived tickets
$archivedPage = isset($_GET['archived_page']) ? (int)$_GET['archived_page'] : 1;
$archivedOffset = ($archivedPage - 1) * $limit;

// Get total number of archived tickets
$totalArchivedQuery = "SELECT COUNT(*) as total FROM tbl_supp_tickets WHERE c_id = ? AND s_status = 'Archived'";
$stmt = $conn->prepare($totalArchivedQuery);
$stmt->bind_param("s", $c_id);
$stmt->execute();
$totalArchivedResult = $stmt->get_result();
$totalArchivedRow = $totalArchivedResult->fetch_assoc();
$totalArchivedTickets = $totalArchivedRow['total'];
$totalArchivedPages = ceil($totalArchivedTickets / $limit);
$stmt->close();

// Query archived tickets
$archivedQuery = "SELECT id, c_id, c_lname, c_fname, s_subject, s_type, s_message, s_status 
                  FROM tbl_supp_tickets 
                  WHERE c_id = ? AND s_status = 'Archived' 
                  ORDER BY id ASC 
                  LIMIT ? OFFSET ?";
$stmt = $conn->prepare($archivedQuery);
$stmt->bind_param("sii", $c_id, $limit, $archivedOffset);
$stmt->execute();
$archivedResult = $stmt->get_result();
$stmt->close();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_ticket'])) {
        $c_id = $_POST['c_id'];
        $c_lname = $_POST['c_lname'];
        $c_fname = $_POST['c_fname'];
        $s_subject = $_POST['subject'];
        $s_type = $_POST['type'];
        $s_message = $_POST['message'];
        $s_status = 'Open';

        $insertQuery = "INSERT INTO tbl_supp_tickets (c_id, c_lname, c_fname, s_subject, s_type, s_message, s_status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("sssssss", $c_id, $c_lname, $c_fname, $s_subject, $s_type, $s_message, $s_status);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Ticket created successfully!";
        } else {
            $_SESSION['error'] = "Error creating ticket: " . $stmt->error;
        }
        $stmt->close();
        header("Location: suppT.php?active_page=$activePage&archived_page=$archivedPage");
        exit();
    } elseif (isset($_POST['close_ticket'])) {
        $ticketId = (int)$_POST['t_id'];
        $currentStatusQuery = "SELECT s_status FROM tbl_supp_tickets WHERE id = ? AND c_id = ?";
        $stmt = $conn->prepare($currentStatusQuery);
        $stmt->bind_param("is", $ticketId, $c_id); 
        $stmt->execute();
        $result = $stmt->get_result();
        $currentStatus = $result->fetch_assoc()['s_status'];
        $stmt->close();
    
        $newStatus = ($currentStatus === 'Open') ? 'Closed' : 'Open';
        $updateQuery = "UPDATE tbl_supp_tickets SET s_status = ? WHERE id = ? AND c_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("sis", $newStatus, $ticketId, $c_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Ticket " . ($newStatus === 'Closed' ? 'closed' : 'reopened') . " successfully!";
        } else {
            $_SESSION['error'] = "Error updating ticket: " . $stmt->error;
        }
        $stmt->close();
        header("Location: suppT.php?active_page=$activePage&archived_page=$archivedPage");
        exit();
    
    } elseif (isset($_POST['archive_ticket'])) {
        $ticketId = (int)$_POST['t_id'];
        $updateQuery = "UPDATE tbl_supp_tickets SET s_status = 'Archived' WHERE id = ? AND c_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("is", $ticketId, $c_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Ticket archived successfully!";
        } else {
            $_SESSION['error'] = "Error archiving ticket: " . $stmt->error;
        }
        $stmt->close();
        header("Location: suppT.php?active_page=$activePage&archived_page=$archivedPage");
        exit();
    } elseif (isset($_POST['unarchive_ticket'])) {
        $ticketId = (int)$_POST['t_id'];
        $updateQuery = "UPDATE tbl_supp_tickets SET s_status = 'Open' WHERE id = ? AND c_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("is", $ticketId, $c_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Ticket unarchived successfully!";
        } else {
            $_SESSION['error'] = "Error unarchiving ticket: " . $stmt->error;
        }
        $stmt->close();
        header("Location: suppT.php?active_page=$activePage&archived_page=$archivedPage");
        exit();
    } elseif (isset($_POST['delete_ticket'])) {
        $ticketId = (int)$_POST['t_id'];
        $deleteQuery = "DELETE FROM tbl_supp_tickets WHERE id = ? AND c_id = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("is", $ticketId, $c_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Ticket deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting ticket: " . $stmt->error;
        }
        $stmt->close();
        header("Location: suppT.php?active_page=$activePage&archived_page=$archivedPage");
        exit();
    } elseif (isset($_POST['edit_ticket'])) {
        $ticketId = (int)$_POST['t_id'];
        $s_type = $_POST['type'];
        $s_message = $_POST['message'];

        $updateQuery = "UPDATE tbl_supp_tickets SET s_type = ?, s_message = ? WHERE id = ? AND c_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ssis", $s_type, $s_message, $ticketId, $c_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Ticket updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating ticket: " . $stmt->error;
        }
        $stmt->close();
        header("Location: suppT.php?active_page=$activePage&archived_page=$archivedPage");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Tickets</title>
    <link rel="stylesheet" href="suppsT.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="wrapper">
        <div class="sidebar glass-container">
            <h2>Task Management</h2>
            <ul>
                <li><a href="staffD.php"><i class="fas fa-ticket-alt"></i> View Tickets</a></li>
                <li><a href="view_service_record.php"><i class="fas fa-box"></i> View Assets</a></li>
                <li><a href="customersT.php"><i class="fas fa-box"></i> View Customers</a></li>
                <li><a href="createTickets.php"><i class="fas fa-file-invoice"></i> Ticket Registration</a></li>
                <li><a href="registerAssets.php"><i class="fas fa-user-plus"></i>Register Assets</a></li>
                <li><a href="logs.php"><i class="fas fa-history"></i> View Logs</a></li>
            </ul>
            <footer>
                <a href="index.php" class="back-home"><i class="fas fa-home"></i> Back to Home</a>
            </footer>
        </div>
        <div class="container">
            <div class="upper"> 
                <h1>Support Tickets</h1>
                <div class="search-container">
                    <input type="text" class="search-bar" id="searchInput" placeholder="Search tickets..." onkeyup="searchUsers()">
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
                        <span><?php echo htmlspecialchars($c_fname); ?></span>
                        <small><?php echo htmlspecialchars(ucfirst($userType)); ?></small>
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

            <!-- Active Tickets Table -->
            <div class="table-box active" id="activeTable">
                <div class="tab-buttons">
                    <button class="tab-btn active" onclick="showTab('active')">Active (<?php echo $totalActiveTickets; ?>)</button>
                    <button class="tab-btn" onclick="showTab('archived')">
                        Archived
                        <?php if ($totalArchivedTickets > 0): ?>
                            <span class="tab-badge"><?php echo $totalArchivedTickets; ?></span>
                        <?php endif; ?>
                    </button>
                    <button type="button" class="create-ticket-btn" onclick="openModal()">Create Ticket</button>
                </div>
                <hr>
                <table>
                    <thead>
                        <tr>
                            <th>Report ID</th>
                            <th>Customer ID</th>
                            <th>Name</th>
                            <th>Subject</th>
                            <th>Type</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $activeResult->fetch_assoc()) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                <td><?php echo htmlspecialchars($row['c_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['c_lname'] . ', ' . $row['c_fname']); ?></td>
                                <td><?php echo htmlspecialchars($row['s_subject']); ?></td>
                                <td><?php echo htmlspecialchars($row['s_type']); ?></td>
                                <td><?php echo htmlspecialchars($row['s_message']); ?></td>
                                <td class="status-clickable status-<?php echo strtolower($row['s_status']); ?>" onclick="openCloseModal('<?php echo $row['id']; ?>', '<?php echo htmlspecialchars($row['c_lname'] . ', ' . $row['c_fname']); ?>', '<?php echo htmlspecialchars($row['s_status']); ?>')">
                                    <?php echo htmlspecialchars($row['s_status']); ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a class="view-btn" onclick="openViewModal(<?php echo htmlspecialchars(json_encode($row)); ?>)" title="View"><i class="fas fa-eye"></i></a>
                                        <a class="edit-btn" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($row)); ?>)" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a class="archive-btn" onclick="openArchiveModal('<?php echo $row['id']; ?>', '<?php echo htmlspecialchars($row['c_lname'] . ', ' . $row['c_fname']); ?>')" title="Archive"><i class="fas fa-archive"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
                <div class="pagination">
                    <?php if ($activePage > 1): ?>
                        <a href="?active_page=<?php echo $activePage - 1; ?>&archived_page=<?php echo $archivedPage; ?>" class="pagination-link"><i class="fas fa-chevron-left"></i></a>
                    <?php else: ?>
                        <span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>
                    <?php endif; ?>
                    <span class="current-page">Page <?php echo $activePage; ?> of <?php echo $totalActivePages; ?></span>
                    <?php if ($activePage < $totalActivePages): ?>
                        <a href="?active_page=<?php echo $activePage + 1; ?>&archived_page=<?php echo $archivedPage; ?>" class="pagination-link"><i class="fas fa-chevron-right"></i></a>
                    <?php else: ?>
                        <span class="pagination-link disabled"><i class="fas fa-chevron-right"></i></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Archived Tickets Table -->
            <div class="table-box" id="archivedTable">
                <div class="tab-buttons">
                    <button class="tab-btn" onclick="showTab('active')">Active (<?php echo $totalActiveTickets; ?>)</button>
                    <button class="tab-btn active" onclick="showTab('archived')">
                        Archived
                        <?php if ($totalArchivedTickets > 0): ?>
                            <span class="tab-badge"><?php echo $totalArchivedTickets; ?></span>
                        <?php endif; ?>
                    </button>
                    <button type="button" class="create-ticket-btn" onclick="openModal()">Create Ticket</button>
                </div>
                <hr>
                <table>
                    <thead>
                        <tr>
                            <th>Report ID</th>
                            <th>Customer ID</th>
                            <th>Name</th>
                            <th>Subject</th>
                            <th>Type</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $archivedResult->fetch_assoc()) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                <td><?php echo htmlspecialchars($row['c_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['c_lname'] . ', ' . $row['c_fname']); ?></td>
                                <td><?php echo htmlspecialchars($row['s_subject']); ?></td>
                                <td><?php echo htmlspecialchars($row['s_type']); ?></td>
                                <td><?php echo htmlspecialchars($row['s_message']); ?></td>
                                <td class="status-<?php echo strtolower($row['s_status']); ?>"><?php echo htmlspecialchars($row['s_status']); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a class="view-btn" onclick="openViewModal(<?php echo htmlspecialchars(json_encode($row)); ?>)" title="View"><i class="fas fa-eye"></i></a>
                                        <a class="unarchive-btn" onclick="openUnarchiveModal('<?php echo $row['id']; ?>', '<?php echo htmlspecialchars($row['c_lname'] . ', ' . $row['c_fname']); ?>')" title="Unarchive"><i class="fas fa-box-open"></i></a>
                                        <a class="delete-btn" onclick="openDeleteModal('<?php echo $row['id']; ?>', '<?php echo htmlspecialchars($row['c_lname'] . ', ' . $row['c_fname']); ?>')" title="Delete"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
                <div class="pagination">
                    <?php if ($archivedPage > 1): ?>
                        <a href="?active_page=<?php echo $activePage; ?>&archived_page=<?php echo $archivedPage - 1; ?>" class="pagination-link"><i class="fas fa-chevron-left"></i></a>
                    <?php else: ?>
                        <span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>
                    <?php endif; ?>
                    <span class="current-page">Page <?php echo $archivedPage; ?> of <?php echo $totalArchivedPages; ?></span>
                    <?php if ($archivedPage < $totalArchivedPages): ?>
                        <a href="?active_page=<?php echo $activePage; ?>&archived_page=<?php echo $archivedPage + 1; ?>" class="pagination-link"><i class="fas fa-chevron-right"></i></a>
                    <?php else: ?>
                        <span class="pagination-link disabled"><i class="fas fa-chevron-right"></i></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Modal Background -->
            <div id="modalBackground" class="modal-background" style="display: none;"></div>

            <!-- Modal for Creating Ticket -->
            <div id="createTicketModal" class="modal-content create-ticket" style="display:none;">
                <span onclick="closeModal()" class="close">×</span>
                <h2>Create New Ticket</h2>
                <form id="createTicketForm" method="POST">
                    <input type="hidden" name="c_id" value="<?php echo htmlspecialchars($c_id); ?>">
                    <input type="hidden" name="c_lname" value="<?php echo htmlspecialchars($c_lname); ?>">
                    <input type="hidden" name="c_fname" value="<?php echo htmlspecialchars($c_fname); ?>">
                    <input type="hidden" name="create_ticket" value="1">
                    <label for="subject">Subject:</label>
                    <input type="text" id="subject" name="subject" readonly>
                    <br>
                    <label for="type">Ticket Type:</label>
                    <select name="type" id="type" required>
                        <option value="Critical" selected>Critical</option>
                        <option value="Minor">Minor</option>
                    </select>
                    <br>
                    <label for="message">Message:</label>
                    <textarea id="message" name="message" required></textarea>
                    <br>
                    <button type="submit">Report Ticket</button>
                </form>
            </div>

            <!-- Modal for Viewing Ticket -->
            <div id="viewTicketModal" class="modal" style="display:none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>View Ticket</h2>
                    </div>
                    <div class="modal-form">
                        <div class="field-row">
                            <label>Report ID:</label>
                            <p id="viewTicketId"></p>
                        </div>
                        <div class="field-row">
                            <label>Customer ID:</label>
                            <p id="viewCustomerId"></p>
                        </div>
                        <div class="field-row">
                            <label>Name:</label>
                            <p id="viewCustomerName"></p>
                        </div>
                        <div class="field-row">
                            <label>Subject:</label>
                            <p id="viewSubject"></p>
                        </div>
                        <div class="field-row">
                            <label>Type:</label>
                            <p id="viewType"></p>
                        </div>
                        <div class="field-row">
                            <label>Message:</label>
                            <p id="viewMessage" style="white-space: pre-wrap;"></p>
                        </div>
                        <div class="field-row">
                            <label>Status:</label>
                            <p id="viewStatus"></p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="modal-btn cancel" onclick="closeModal('viewTicketModal')">Close</button>
                    </div>
                </div>
            </div>

            <!-- Modal for Editing Ticket -->
            <div id="editTicketModal" class="modal" style="display:none;">
                <div class="modal-content">
                    <span onclick="closeModal('editTicketModal')" class="close">×</span>
                    <div class="modal-header">
                        <h2>Edit Ticket</h2>
                    </div>
                    <form class="modal-form" id="editTicketForm" method="POST">
                        <input type="hidden" name="t_id" id="editTicketId">
                        <input type="hidden" name="edit_ticket" value="1">
                        <label for="editCustomerId">Customer ID:</label>
                        <input type="text" id="editCustomerId" readonly>
                        <label for="editCustomerName">Name:</label>
                        <input type="text" id="editCustomerName" readonly>
                        <label for="editSubject">Subject:</label>
                        <input type="text" id="editSubject" readonly>
                        <label for="editType">Ticket Type:</label>
                        <select name="type" id="editType" required>
                            <option value="Critical">Critical</option>
                            <option value="Minor">Minor</option>
                        </select>
                        <label for="editMessage">Message:</label>
                        <textarea id="editMessage" name="message" required></textarea>
                        <div class="modal-footer">
                            <button type="button" class="modal-btn cancel" onclick="closeModal('editTicketModal')">Cancel</button>
                            <button type="submit" class="modal-btn confirm">Update Ticket</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal for Archiving Ticket -->
            <div id="archiveModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <span onclick="closeModal('archiveModal')" class="close">×</span>
                    <div class="modal-header">
                        <h2>Archive Ticket</h2>
                    </div>
                    <p>Are you sure you want to archive ticket <span id="archiveTicketIdDisplay"></span> for <span id="archiveTicketName"></span>?</p>
                    <form class="modal-form" id="archiveForm" method="POST">
                        <input type="hidden" name="t_id" id="archiveTicketId">
                        <input type="hidden" name="archive_ticket" value="1">
                        <div class="modal-footer">
                            <button type="button" class="modal-btn cancel" onclick="closeModal('archiveModal')">Cancel</button>
                            <button type="submit" class="modal-btn confirm">Archive Ticket</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal for Unarchiving Ticket -->
            <div id="unarchiveModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <span onclick="closeModal('unarchiveModal')" class="close">×</span>
                    <div class="modal-header">
                        <h2>Unarchive Ticket</h2>
                    </div>
                    <p>Are you sure you want to unarchive ticket <span id="unarchiveTicketIdDisplay"></span> for <span id="unarchiveTicketName"></span>?</p>
                    <form class="modal-form" id="unarchiveForm" method="POST">
                        <input type="hidden" name="t_id" id="unarchiveTicketId">
                        <input type="hidden" name="unarchive_ticket" value="1">
                        <div class="modal-footer">
                            <button type="button" class="modal-btn cancel" onclick="closeModal('unarchiveModal')">Cancel</button>
                            <button type="submit" class="modal-btn confirm">Unarchive Ticket</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal for Deleting Ticket -->
            <div id="deleteModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <span onclick="closeModal('deleteModal')" class="close">×</span>
                    <div class="modal-header">
                        <h2>Delete Ticket</h2>
                    </div>
                    <p>Are you sure you want to delete ticket <span id="deleteTicketIdDisplay"></span> for <span id="deleteTicketName"></span>? This action cannot be undone.</p>
                    <form class="modal-form" id="deleteForm" method="POST">
                        <input type="hidden" name="t_id" id="deleteTicketId">
                        <input type="hidden" name="delete_ticket" value="1">
                        <div class="modal-footer">
                            <button type="button" class="modal-btn cancel" onclick="closeModal('deleteModal')">Cancel</button>
                            <button type="submit" class="modal-btn delete">Delete Ticket</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Close Ticket Modal -->
            <div id="closeModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 id="closeModalTitle">Close Ticket</h2>
                        <span onclick="closeModal('closeModal')" class="close">×</span>
                    </div>
                    <form method="POST" id="closeForm">
                        <p id="closeModalMessage">Are you sure you want to close ticket <span id="closeTicketIdDisplay"></span> for <span id="closeTicketName"></span>?</p>
                        <input type="hidden" name="t_id" id="closeTicketId">
                        <input type="hidden" name="close_ticket" value="1">
                        <div class="modal-footer">
                            <button type="button" class="modal-btn cancel" onclick="closeModal('closeModal')">Cancel</button>
                            <button type="submit" class="modal-btn confirm" id="closeModalButton">Close Ticket</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab') || 'active';
            showTab(tab);

            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.classList.add('alert-hidden');
                    setTimeout(() => alert.remove(), 500);
                }, 2000);
            });
        });

        function showTab(tab) {
            const activeTable = document.getElementById('activeTable');
            const archivedTable = document.getElementById('archivedTable');
            const allTabButtons = document.querySelectorAll('.tab-btn');

            if (tab === 'active') {
                activeTable.classList.add('active');
                archivedTable.classList.remove('active');
            } else {
                activeTable.classList.remove('active');
                archivedTable.classList.add('active');
            }

            allTabButtons.forEach(button => {
                const buttonTab = button.getAttribute('onclick').match(/'([^']+)'/)[1];
                button.classList.toggle('active', buttonTab === tab);
            });

            const url = new URL(window.location);
            url.searchParams.set('tab', tab);
            window.history.pushState({}, '', url);
        }

        function openModal() {
            const date = new Date();
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            const uniqueNumber = Math.floor(100000 + Math.random() * 900000);
            const subject = `ref#-${day}-${month}-${year}-${uniqueNumber}`;

            document.getElementById('subject').value = subject;
            document.getElementById('createTicketModal').style.display = 'block';
            document.getElementById('modalBackground').style.display = 'block';
        }

        function closeModal(modalId) {
            if (modalId) {
                document.getElementById(modalId).style.display = 'none';
            } else {
                document.getElementById('createTicketModal').style.display = 'none';
            }
            document.getElementById('modalBackground').style.display = 'none';
        }

        function openCloseModal(ticketId, name, status) {
            document.getElementById('closeTicketId').value = ticketId;
            document.getElementById('closeTicketIdDisplay').textContent = ticketId;
            document.getElementById('closeTicketName').textContent = name;
            const isOpen = status.toLowerCase() === 'open';
            document.getElementById('closeModalTitle').textContent = isOpen ? 'Close Ticket' : 'Reopen Ticket';
            document.getElementById('closeModalMessage').textContent = `Are you sure you want to ${isOpen ? 'close' : 'reopen'} ticket ${ticketId} for ${name}?`;
            document.getElementById('closeModalButton').textContent = isOpen ? 'Close Ticket' : 'Reopen Ticket';
            document.getElementById('closeModal').style.display = 'block';
            document.getElementById('modalBackground').style.display = 'block';
        }

        function openViewModal(ticket) {
            document.getElementById('viewTicketId').textContent = ticket.id;
            document.getElementById('viewCustomerId').textContent = ticket.c_id;
            document.getElementById('viewCustomerName').textContent = `${ticket.c_lname}, ${ticket.c_fname}`;
            document.getElementById('viewSubject').textContent = ticket.s_subject;
            document.getElementById('viewType').textContent = ticket.s_type;
            document.getElementById('viewMessage').textContent = ticket.s_message;
            document.getElementById('viewStatus').textContent = ticket.s_status;
            document.getElementById('viewTicketModal').style.display = 'block';
            document.getElementById('modalBackground').style.display = 'block';
        }

        function openEditModal(ticket) {
            document.getElementById('editTicketId').value = ticket.id;
            document.getElementById('editCustomerId').value = ticket.c_id;
            document.getElementById('editCustomerName').value = `${ticket.c_lname}, ${ticket.c_fname}`;
            document.getElementById('editSubject').value = ticket.s_subject;
            document.getElementById('editType').value = ticket.s_type;
            document.getElementById('editMessage').value = ticket.s_message;
            document.getElementById('editTicketModal').style.display = 'block';
            document.getElementById('modalBackground').style.display = 'block';
        }

        function openArchiveModal(ticketId, name) {
            document.getElementById('archiveTicketId').value = ticketId;
            document.getElementById('archiveTicketIdDisplay').textContent = ticketId;
            document.getElementById('archiveTicketName').textContent = name;
            document.getElementById('archiveModal').style.display = 'block';
            document.getElementById('modalBackground').style.display = 'block';
        }

        function openUnarchiveModal(ticketId, name) {
            document.getElementById('unarchiveTicketId').value = ticketId;
            document.getElementById('unarchiveTicketIdDisplay').textContent = ticketId;
            document.getElementById('unarchiveTicketName').textContent = name;
            document.getElementById('unarchiveModal').style.display = 'block';
            document.getElementById('modalBackground').style.display = 'block';
        }

        function openDeleteModal(ticketId, name) {
            document.getElementById('deleteTicketId').value = ticketId;
            document.getElementById('deleteTicketIdDisplay').textContent = ticketId;
            document.getElementById('deleteTicketName').textContent = name;
            document.getElementById('deleteModal').style.display = 'block';
            document.getElementById('modalBackground').style.display = 'block';
        }

        function searchUsers() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            const activeTable = document.getElementById('activeTable');
            const archivedTable = document.getElementById('archivedTable');
            const currentTable = activeTable.classList.contains('active') ? activeTable : archivedTable;
            const rows = currentTable.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let match = false;
                for (let j = 0; j < cells.length; j++) {
                    if (cells[j].textContent.toLowerCase().includes(input)) {
                        match = true;
                        break;
                    }
                }
                rows[i].style.display = match ? '' : 'none';
            }
        }
    </script>
</body>
</html>

<?php
mysqli_close($conn);
?>