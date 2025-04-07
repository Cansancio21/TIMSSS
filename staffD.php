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
$userType = '';
$lastName = '';

// Fetch user data based on the logged-in username
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

    // Avatar handling
    $username = $_SESSION['username'];
    $avatarPath = 'default-avatar.png'; // Default avatar
    $avatarFolder = 'uploads/avatars/';
    $userAvatar = $avatarFolder . $username . '.png';

    if (file_exists($userAvatar)) {
        $_SESSION['avatarPath'] = $userAvatar . '?' . time(); // Prevent caching issues
    } else {
        $_SESSION['avatarPath'] = 'default-avatar.png';
    }
    $avatarPath = $_SESSION['avatarPath'];

    // Pagination setup
    $limit = 10; // Number of tickets per page
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;

    // Count total active tickets
    $sqlCount = "SELECT COUNT(*) AS total FROM tbl_ticket WHERE t_status != 'archived'";
    $resultCount = $conn->query($sqlCount);
    $totalTickets = $resultCount->fetch_assoc()['total'];
    $totalPages = ceil($totalTickets / $limit);

    // Count archived tickets
    $sqlArchivedCount = "SELECT COUNT(*) AS totalArchived FROM tbl_ticket WHERE t_status = 'archived'";
    $resultArchivedCount = $conn->query($sqlArchivedCount);
    $totalArchived = $resultArchivedCount->fetch_assoc()['totalArchived'];

    // Fetch active ticket data with pagination
    $sql = "SELECT t_id, t_aname, t_type, t_status, t_details, t_date FROM tbl_ticket WHERE t_status != 'archived' LIMIT $limit OFFSET $offset";
    $result = $conn->query($sql); 

    // Fetch all archived tickets (no pagination)
    $sqlArchived = "SELECT t_id, t_aname, t_type, t_status, t_details, t_date FROM tbl_ticket WHERE t_status = 'archived'";
    $resultArchived = $conn->query($sqlArchived);
} else {
    echo "Database connection failed.";
    exit();
}

// Handle form submissions from modals
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    } elseif (isset($_POST['delete_ticket'])) {
        $t_id = $_POST['t_id'];
        $sql = "DELETE FROM tbl_ticket WHERE t_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $t_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Ticket deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting ticket: " . $stmt->error;
        }
        $stmt->close();
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
    }
    header("Location: staffD.php?page=$page"); // Redirect to current page
    exit();
}
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
            <?php if ($userType === 'staff'): ?>
                <div class="username">
                    Welcome Back, <?php echo htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8'); ?>!
                    <i class="fas fa-user-shield admin-icon"></i>
                </div>
            <?php endif; ?>
            
            <div class="tab-buttons">
                <button class="tab-btn active" onclick="showTab('active')">Active Tickets (<?php echo $totalTickets; ?>)</button>
                <button class="tab-btn" onclick="showTab('archived')">
                    Archived Tickets
                    <?php if ($totalArchived > 0): ?>
                        <span class="tab-badge"><?php echo $totalArchived; ?></span>
                    <?php endif; ?>
                </button>
            </div>
            
            <button class="add-user-btn" onclick="showAddModal()"><i class="fas fa-ticket-alt"></i> Add New Ticket</button>
            
            <!-- Active Tickets Table -->
            <table id="active-tickets-table" class="tickets-table">
                <thead>
                    <tr>
                        <th>ID</th>
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
                    if ($result->num_rows > 0) { 
                        while ($row = $result->fetch_assoc()) { 
                            echo "<tr> 
                                    <td>{$row['t_id']}</td> 
                                    <td>{$row['t_aname']}</td> 
                                    <td>" . ucfirst(strtolower($row['t_type'])) . "</td> 
                                    <td class='status-" . strtolower($row['t_status']) . "'>" . ucfirst(strtolower($row['t_status'])) . "</td>
                                    <td>{$row['t_details']}</td>
                                    <td>{$row['t_date']}</td> 
                                    <td class='action-buttons'>
                                        <a class='view-btn' onclick=\"showViewModal('{$row['t_id']}', '{$row['t_aname']}', '{$row['t_type']}', '{$row['t_status']}', '{$row['t_details']}', '{$row['t_date']}')\" title='View'><i class='fas fa-eye'></i></a>
                                       <a class='edit-btn' href='editT.php?id=" . htmlspecialchars($row['t_id'], ENT_QUOTES, 'UTF-8') . "' title='Edit'><i class='fas fa-edit'></i></a>
                                        <a class='archive-btn' onclick=\"showArchiveModal('{$row['t_id']}', '{$row['t_aname']}')\" title='Archive'><i class='fas fa-archive'></i></a>
                                    </td>
                                  </tr>"; 
                        } 
                    } else { 
                        echo "<tr><td colspan='7' style='text-align: center;'>No active tickets found.</td></tr>"; 
                    } 
                    ?>
                </tbody>
            </table>

            <!-- Archived Tickets Table -->
            <table id="archived-tickets-table" class="tickets-table hidden">
                <thead>
                    <tr>
                        <th>ID</th>
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
                                    <td>{$row['t_aname']}</td> 
                                    <td>" . ucfirst(strtolower($row['t_type'])) . "</td> 
                                    <td class='status-" . strtolower($row['t_status']) . "'>" . ucfirst(strtolower($row['t_status'])) . "</td>
                                    <td>{$row['t_details']}</td>
                                    <td>{$row['t_date']}</td> 
                                    <td class='action-buttons'>
                                        <a class='view-btn' onclick=\"showViewModal('{$row['t_id']}', '{$row['t_aname']}', '{$row['t_type']}', '{$row['t_status']}', '{$row['t_details']}', '{$row['t_date']}')\" title='View'><i class='fas fa-eye'></i></a>
                                        <a class='restore-btn' onclick=\"showRestoreModal('{$row['t_id']}', '{$row['t_aname']}')\" title='Unarchive'><i class='fas fa-trash-restore'></i></a>
                                    </td>
                                  </tr>"; 
                        } 
                    } else { 
                        echo "<tr><td colspan='7' style='text-align: center;'>No archived tickets found.</td></tr>"; 
                    } 
                    ?>
                </tbody>
            </table>

            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>" class="pagination-link"><i class="fas fa-chevron-left"></i></a>
                <?php else: ?>
                    <span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>
                <?php endif; ?>

                <span class="current-page">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>" class="pagination-link"><i class="fas fa-chevron-right"></i></a>
                <?php else: ?>
                    <span class="pagination-link disabled"><i class="fas fa-chevron-right"></i></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add New Ticket</h2>
        </div>
        <form method="POST" class="modal-form">
            <input type="text" name="t_aname" placeholder="Account Name" required>
            <select name="t_type" required>
                <option value="">Select Issue Type</option>
                <option value="hardware">Hardware</option>
                <option value="software">Software</option>
                <option value="network">Network</option>
                <option value="other">Other</option>
            </select>
            <select name="t_status" required>
                <option value="">Select Status</option>
                <option value="open">Open</option>
                <option value="in progress">In Progress</option>
                <option value="resolved">Resolved</option>
            </select>
            <textarea name="t_details" placeholder="Ticket Details" required></textarea>
            <input type="date" name="t_date" required>
            <input type="hidden" name="add_ticket" value="1">
            <div class="modal-footer">
                <button type="button" class="modal-btn cancel" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="modal-btn confirm">Add Ticket</button>
            </div>
        </form>
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



<!-- Delete Ticket Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Delete Ticket</h2>
        </div>
        <p>Are you sure you want to delete ticket for <span id="deleteTicketName"></span>?</p>
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
        <p>Are you sure you want to unarchive ticket for <span id="restoreTicketName"></span>?</p>
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

<script>
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    function showAddModal() {
        document.getElementById('addModal').style.display = 'block';
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
        document.getElementById('archiveTicketName').textContent = aname;
        document.getElementById('archiveModal').style.display = 'block';
    }

    function showRestoreModal(id, aname) {
        document.getElementById('restoreTicketId').value = id;
        document.getElementById('restoreTicketName').textContent = aname;
        document.getElementById('restoreModal').style.display = 'block';
    }

    function searchTickets() {
        const input = document.getElementById('searchInput').value.toLowerCase();
        const activeTable = document.getElementById('active-tickets-table');
        const archivedTable = document.getElementById('archived-tickets-table');
        
        const tables = [activeTable, archivedTable];
        
        tables.forEach(table => {
            if (table.style.display !== 'none') {
                const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
                
                for (let i = 0; i < rows.length; i++) {
                    const cells = rows[i].getElementsByTagName('td');
                    let match = false;
                    for (let j = 0; j < cells.length - 1; j++) { // Exclude action column
                        if (cells[j].textContent.toLowerCase().includes(input)) {
                            match = true;
                            break;
                        }
                    }
                    rows[i].style.display = match ? '' : 'none';
                }
            }
        });
    }

    function showTab(tabName) {
        // Update active tab styling
        document.querySelector('.tab-btn.active').classList.remove('active');
        document.querySelector(`.tab-btn[onclick="showTab('${tabName}')"]`).classList.add('active');
        
        // Show/hide tables
        if (tabName === 'active') {
            document.getElementById('active-tickets-table').classList.remove('hidden');
            document.getElementById('archived-tickets-table').classList.add('hidden');
        } else {
            document.getElementById('active-tickets-table').classList.add('hidden');
            document.getElementById('archived-tickets-table').classList.remove('hidden');
        }
        
        // Reset search when switching tabs
        document.getElementById('searchInput').value = '';
        searchTickets();
    }
</script>

</body>
</html>

<?php 
$conn->close(); // Close the database connection 
?>