
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

// Handle form submissions (only for non-technicians)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $userType !== 'technician') {
    $pageActive = isset($_GET['page_active']) ? (int)$_GET['page_active'] : 1;
    $pageArchived = isset($_GET['page_archived']) ? (int)$_GET['page_archived'] : 1;
    $tab = isset($_GET['tab']) ? $_GET['tab'] : 'active';

    if (isset($_POST['add_ticket'])) {
        $t_aname = $_POST['t_aname'];
        $t_type = $_POST['t_type'];
        $t_status = $_POST['t_status'];
        $t_details = $_POST['t_details'];
        $t_date = $_POST['t_date'];

        $sql = "INSERT INTO tbl_ticket (t_aname, t_type, t_status, t_details, t_date) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $t_aname, $t_type, $t_status, $t_details, $t_date);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Ticket added successfully!";
        } else {
            $_SESSION['error'] = "Error adding ticket: " . $stmt->error;
        }
        $stmt->close();
        $tab = 'active';
    } elseif (isset($_POST['edit_ticket'])) {
        $t_id = $_POST['t_id'];
        $t_aname = $_POST['t_aname'];
        $t_type = $_POST['t_type'];
        $t_status = $_POST['t_status'];
        $t_details = $_POST['t_details'];
        $t_date = $_POST['t_date'];

        $sql = "UPDATE tbl_ticket SET t_aname=?, t_type=?, t_status=?, t_details=?, t_date=? WHERE t_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssi", $t_aname, $t_type, $t_status, $t_details, $t_date, $t_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Ticket updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating ticket: " . $stmt->error;
        }
        $stmt->close();
        $tab = 'active';
    } elseif (isset($_POST['archive_ticket'])) {
        $t_id = $_POST['t_id'];
        $sql = "UPDATE tbl_ticket SET t_status='archived' WHERE t_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $t_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Ticket archived successfully!";
        } else {
            $_SESSION['error'] = "Error archiving ticket: " . $stmt->error;
        }
        $stmt->close();
        $tab = 'archived';
    } elseif (isset($_POST['restore_ticket'])) {
        $t_id = $_POST['t_id'];
        $sql = "UPDATE tbl_ticket SET t_status='open' WHERE t_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $t_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Ticket restored successfully!";
        } else {
            $_SESSION['error'] = "Error restoring ticket: " . $stmt->error;
        }
        $stmt->close();
        $tab = 'active';
    } elseif (isset($_POST['close_ticket'])) {
        $t_id = $_POST['t_id'];
        $sql = "UPDATE tbl_ticket SET t_status='closed' WHERE t_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $t_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Ticket closed successfully!";
        } else {
            $_SESSION['error'] = "Error closing ticket: " . $stmt->error;
        }
        $stmt->close();
        $tab = 'active';
    } elseif (isset($_POST['delete_ticket'])) {
        $t_id = $_POST['t_id'];
        $sql = "DELETE FROM tbl_ticket WHERE t_id=? AND t_status='archived'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $t_id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $_SESSION['message'] = "Ticket deleted successfully!";
            } else {
                $_SESSION['error'] = "Ticket not found or not archived.";
            }
        } else {
            $_SESSION['error'] = "Error deleting ticket: " . $stmt->error;
        }
        $stmt->close();
        $tab = 'archived';
    }

    header("Location: staffD.php?tab=$tab&page_active=$pageActive&page_archived=$pageArchived");
    exit();
}

if ($conn) {
    // Fetch user data
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

    // Pagination setup
    $limit = 10;
    // Active tickets
    $pageActive = isset($_GET['page_active']) ? (int)$_GET['page_active'] : 1;
    $offsetActive = ($pageActive - 1) * $limit;
    $totalActiveQuery = "SELECT COUNT(*) AS total FROM tbl_ticket WHERE t_status != 'archived'";
    $totalActiveResult = $conn->query($totalActiveQuery);
    $totalActiveRow = $totalActiveResult->fetch_assoc();
    $totalActive = $totalActiveRow['total'];
    $totalActivePages = ceil($totalActive / $limit);

    // Archived tickets
    $pageArchived = isset($_GET['page_archived']) ? (int)$_GET['page_archived'] : 1;
    $offsetArchived = ($pageArchived - 1) * $limit;
    $totalArchivedQuery = "SELECT COUNT(*) AS total FROM tbl_ticket WHERE t_status = 'archived'";
    $totalArchivedResult = $conn->query($totalArchivedQuery);
    $totalArchivedRow = $totalArchivedResult->fetch_assoc();
    $totalArchived = $totalArchivedRow['total'];
    $totalArchivedPages = ceil($totalArchived / $limit);

    // Fetch active tickets
    $sqlActive = "SELECT t_id, t_aname, t_type, t_status, t_details, t_date 
                  FROM tbl_ticket WHERE t_status != 'archived' LIMIT ?, ?";
    $stmtActive = $conn->prepare($sqlActive);
    $stmtActive->bind_param("ii", $offsetActive, $limit);
    $stmtActive->execute();
    $resultActive = $stmtActive->get_result();
    $stmtActive->close();

    // Fetch archived tickets
    $sqlArchived = "SELECT t_id, t_aname, t_type, t_status, t_details, t_date 
                    FROM tbl_ticket WHERE t_status = 'archived' LIMIT ?, ?";
    $stmtArchived = $conn->prepare($sqlArchived);
    $stmtArchived->bind_param("ii", $offsetArchived, $limit);
    $stmtArchived->execute();
    $resultArchived = $stmtArchived->get_result();
    $stmtArchived->close();
} else {
    $_SESSION['error'] = "Database connection failed.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard | Ticket Reports</title>
    <link rel="stylesheet" href="staffDs.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
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
            <?php if ($userType !== 'technician'): ?>
                <li><a href="createTickets.php"><i class="fas fa-file-invoice"></i> <span>Ticket Registration</span></a></li>
                <li><a href="registerAssets.php"><i class="fas fa-plus-circle"></i> <span>Register Assets</span></a></li>
                <li><a href="addC.php"><i class="fas fa-user-plus"></i> <span>Add Customer</span></a></li>
            <?php endif; ?>
            <?php if ($userType === 'admin'): ?>
                <li><a href="logs.php"><i class="fas fa-book"></i> <span>View Logs</span></a></li>
            <?php endif; ?>
        </ul>
        <footer>
            <a href="index.php" class="back-home"><i class="fas fa-home"></i> <span>Back to Home</span></a>
        </footer>
    </div>

    <div class="container">
        <div class="upper">
            <h1>Ticket Reports</h1>
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

        <div class="alert-container">
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
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
                    <button class="add-user-btn" onclick="window.location.href='createTickets.php'"><i class="fas fa-ticket-alt"></i> Add New Ticket</button>
                <?php else: ?>
                    <button class="add-user-btn disabled" onclick="showRestrictedMessage()"><i class="fas fa-ticket-alt"></i> Add New Ticket</button>
                <?php endif; ?>
                <table id="active-tickets-table">
                    <thead>
                        <tr>
                            <th>Ticket ID</th>
                            <th>Account Name</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Ticket Details</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($resultActive->num_rows > 0) {
                            while ($row = $resultActive->fetch_assoc()) {
                                $statusClass = 'status-' . strtolower($row['t_status']);
                                $isClickable = ($userType === 'technician' && strtolower($row['t_status']) === 'open');
                                $clickableAttr = $isClickable ? " status-clickable' onclick=\"showCloseModal('{$row['t_id']}', '" . htmlspecialchars($row['t_aname'], ENT_QUOTES, 'UTF-8') . "', '{$row['t_status']}')\"" : "'";
                                
                                echo "<tr> 
                                        <td>{$row['t_id']}</td> 
                                        <td>" . htmlspecialchars($row['t_aname'], ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td>" . ucfirst(strtolower($row['t_type'])) . "</td> 
                                        <td class='$statusClass$clickableAttr>" . ucfirst(strtolower($row['t_status'])) . "</td>
                                        <td>" . htmlspecialchars($row['t_details'], ENT_QUOTES, 'UTF-8') . "</td>
                                        <td>" . htmlspecialchars($row['t_date'], ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td class='action-buttons'>";
                                if ($userType !== 'technician') {
                                    echo "<a class='view-btn' onclick=\"showViewModal('{$row['t_id']}', '" . htmlspecialchars($row['t_aname'], ENT_QUOTES, 'UTF-8') . "', '{$row['t_type']}', '{$row['t_status']}', '" . htmlspecialchars($row['t_details'], ENT_QUOTES, 'UTF-8') . "', '{$row['t_date']}')\" title='View'><i class='fas fa-eye'></i></a>
                                          <a class='edit-btn' href='editT.php?id=" . htmlspecialchars($row['t_id'], ENT_QUOTES, 'UTF-8') . "' title='Edit'><i class='fas fa-edit'></i></a>
                                          <a class='archive-btn' onclick=\"showArchiveModal('{$row['t_id']}', '" . htmlspecialchars($row['t_aname'], ENT_QUOTES, 'UTF-8') . "')\" title='Archive'><i class='fas fa-archive'></i></a>";
                                } else {
                                    echo "<a class='view-btn disabled' onclick='showRestrictedMessage()' title='View'><i class='fas fa-eye'></i></a>
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
                <div class="pagination">
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
                            <th>Type</th>
                            <th>Status</th>
                            <th>Ticket Details</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($resultArchived->num_rows > 0) {
                            while ($row = $resultArchived->fetch_assoc()) {
                                echo "<tr> 
                                        <td>{$row['t_id']}</td> 
                                        <td>" . htmlspecialchars($row['t_aname'], ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td>" . ucfirst(strtolower($row['t_type'])) . "</td> 
                                        <td class='status-" . strtolower($row['t_status']) . "'>" . ucfirst(strtolower($row['t_status'])) . "</td>
                                        <td>" . htmlspecialchars($row['t_details'], ENT_QUOTES, 'UTF-8') . "</td>
                                        <td>" . htmlspecialchars($row['t_date'], ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td class='action-buttons'>";
                                if ($userType !== 'technician') {
                                    echo "<a class='view-btn' onclick=\"showViewModal('{$row['t_id']}', '" . htmlspecialchars($row['t_aname'], ENT_QUOTES, 'UTF-8') . "', '{$row['t_type']}', '{$row['t_status']}', '" . htmlspecialchars($row['t_details'], ENT_QUOTES, 'UTF-8') . "', '{$row['t_date']}')\" title='View'><i class='fas fa-eye'></i></a>
                                          <a class='restore-btn' onclick=\"showRestoreModal('{$row['t_id']}', '" . htmlspecialchars($row['t_aname'], ENT_QUOTES, 'UTF-8') . "')\" title='Restore'><i class='fas fa-trash-restore'></i></a>
                                          <a class='delete-btn' onclick=\"showDeleteModal('{$row['t_id']}', '" . htmlspecialchars($row['t_aname'], ENT_QUOTES, 'UTF-8') . "')\" title='Delete'><i class='fas fa-trash'></i></a>";
                                } else {
                                    echo "<a class='view-btn disabled' onclick='showRestrictedMessage()' title='View'><i class='fas fa-eye'></i></a>
                                          <a class='restore-btn disabled' onclick='showRestrictedMessage()' title='Restore'><i class='fas fa-trash-restore'></i></a>
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
                <div class="pagination">
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
        <div id="viewContent"></div>
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
            <p class="close-ticket-input">
                Enter Ticket Id to close: <span id="closeTicketName"></span>
                <span class="ticket-id-input">
                    <input type="number" name="t_id" id="closeTicketIdInput" placeholder="Ticket ID" required>
                </span>
            </p>
            <input type="hidden" name="close_ticket" value="1">
            <div class="modal-footer">
                <button type="button" class="modal-btn cancel" onclick="closeModal('closeModal')">Cancel</button>
                <button type="submit" class="modal-btn confirm">Close Ticket</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
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
});

function showRestrictedMessage() {
    alert("Only staff can perform this action.");
}

function showTab(tab) {
    const activeSection = document.querySelector('.active-tickets');
    const archivedSection = document.querySelector('.archived-tickets');

    // Update section visibility
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

    // Update tab buttons
    document.querySelectorAll('.tab-btn').forEach(button => {
        button.classList.remove('active');
        if (button.onclick.toString().includes(`showTab('${tab}'`)) {
            button.classList.add('active');
        }
    });

    // Update URL
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('tab', tab);
    history.replaceState(null, '', '?' + urlParams.toString());

    // Reset search
    document.getElementById('searchInput').value = '';
    searchTickets();
}

function searchTickets() {
    const input = document.getElementById('searchInput').value.toLowerCase();

    // Search active tickets
    const activeTable = document.getElementById('active-tickets-table');
    if (activeTable && document.querySelector('.active-tickets').style.display !== 'none') {
        const activeRows = activeTable.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
        for (let i = 0; i < activeRows.length; i++) {
            const cells = activeRows[i].getElementsByTagName('td');
            let match = false;
            for (let j = 0; j < cells.length - 1; j++) {
                if (cells[j].textContent.toLowerCase().includes(input)) {
                    match = true;
                    break;
                }
            }
            activeRows[i].style.display = match ? '' : 'none';
        }
    }

    // Search archived tickets
    const archivedTable = document.getElementById('archived-tickets-table');
    if (archivedTable && document.querySelector('.archived-tickets').style.display !== 'none') {
        const archivedRows = archivedTable.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
        for (let i = 0; i < archivedRows.length; i++) {
            const cells = archivedRows[i].getElementsByTagName('td');
            let match = false;
            for (let j = 0; j < cells.length - 1; j++) {
                if (cells[j].textContent.toLowerCase().includes(input)) {
                    match = true;
                    break;
                }
            }
            archivedRows[i].style.display = match ? '' : 'none';
        }
    }
}

function showViewModal(id, aname, type, status, details, date) {
    document.getElementById('viewContent').innerHTML = `
        <div class="view-details">
            <p><strong>ID:</strong> ${id}</p>
            <p><strong>Account Name:</strong> ${aname}</p>
            <p><strong>Issue Type:</strong> ${type}</p>
            <p><strong>Ticket Status:</strong> <span class="status-${status.toLowerCase()}">${status}</span></p>
            <p><strong>Ticket Details:</strong> ${details}</p>
            <p><strong>Date:</strong> ${date}</p>
        </div>
    `;
    document.getElementById('viewModal').style.display = 'block';
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
    document.getElementById('closeTicketIdInput').value = id;
    document.getElementById('closeTicketName').innerText = aname;
    document.getElementById('closeModal').style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.className === 'modal') {
        event.target.style.display = 'none';
    }
}
</script>
</body>
</html>

<?php $conn->close(); ?>
