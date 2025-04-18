<?php
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pageActive = isset($_GET['page_active']) ? (int)$_GET['page_active'] : 1;
    $pageArchived = isset($_GET['page_archived']) ? (int)$_GET['page_archived'] : 1;
    $tab = isset($_GET['tab']) ? $_GET['tab'] : 'customers_active';

    if (isset($_POST['add_customer'])) {
        $fname = $_POST['c_fname'];
        $lname = $_POST['c_lname'];
        $area = $_POST['c_area'];
        $contact = $_POST['c_contact'];
        $email = $_POST['c_email'];
        $onu = $_POST['c_onu'];
        $caller = $_POST['c_caller'];
        $address = $_POST['c_address'];
        $rem = $_POST['c_rem'];

        $sql = "INSERT INTO tbl_customer (c_fname, c_lname, c_area, c_contact, c_email, c_date, c_onu, c_caller, c_address, c_rem) 
                VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("sssssssss", $fname, $lname, $area, $contact, $email, $onu, $caller, $address, $rem);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Customer added successfully!";
        } else {
            $_SESSION['error'] = "Error adding customer: " . $stmt->error;
        }
        $stmt->close();
        $tab = 'customers_active';
    } elseif (isset($_POST['edit_customer'])) {
        $id = $_POST['c_id'];
        $fname = $_POST['c_fname'];
        $lname = $_POST['c_lname'];
        $area = $_POST['c_area'];
        $contact = $_POST['c_contact'];
        $email = $_POST['c_email'];
        $onu = $_POST['c_onu'];
        $caller = $_POST['c_caller'];
        $address = $_POST['c_address'];
        $rem = $_POST['c_rem'];

        $sql = "UPDATE tbl_customer SET c_fname=?, c_lname=?, c_area=?, c_contact=?, c_email=?, c_onu=?, c_caller=?, c_address=?, c_rem=? WHERE c_id=?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("sssssssssi", $fname, $lname, $area, $contact, $email, $onu, $caller, $address, $rem, $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Customer updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating customer: " . $stmt->error;
        }
        $stmt->close();
        $tab = 'customers_active';
    } elseif (isset($_POST['archive_customer'])) {
        $id = $_POST['c_id'];
        // Get current c_rem to preserve it
        $sql = "SELECT c_rem FROM tbl_customer WHERE c_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $current_rem = $row['c_rem'] ?? '';
        $stmt->close();

        // Prepend ARCHIVED:
        $new_rem = 'ARCHIVED:' . $current_rem;
        $sql = "UPDATE tbl_customer SET c_rem=? WHERE c_id=?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("si", $new_rem, $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Customer archived successfully!";
        } else {
            $_SESSION['error'] = "Error archiving customer: " . $stmt->error;
        }
        $stmt->close();
        $tab = 'customers_archived';
    } elseif (isset($_POST['unarchive_customer'])) {
        $id = $_POST['c_id'];
        // Get current c_rem to remove ARCHIVED:
        $sql = "SELECT c_rem FROM tbl_customer WHERE c_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $current_rem = $row['c_rem'] ?? '';
        $stmt->close();

        // Remove ARCHIVED: prefix
        $new_rem = preg_replace('/^ARCHIVED:/', '', $current_rem);
        $sql = "UPDATE tbl_customer SET c_rem=? WHERE c_id=?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("si", $new_rem, $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Customer unarchived successfully!";
        } else {
            $_SESSION['error'] = "Error unarchiving customer: " . $stmt->error;
        }
        $stmt->close();
        $tab = 'customers_active';
    } elseif (isset($_POST['delete_customer'])) {
        $id = $_POST['c_id'];
        $sql = "DELETE FROM tbl_customer WHERE c_id=? AND c_rem LIKE 'ARCHIVED:%'";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Customer deleted permanently!";
        } else {
            $_SESSION['error'] = "Error deleting customer: " . $stmt->error;
        }
        $stmt->close();
        $tab = 'customers_archived';
    }

    header("Location: customersT.php?tab=$tab&page_active=$pageActive&page_archived=$pageArchived");
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
    // Active customers
    $pageActive = isset($_GET['page_active']) ? (int)$_GET['page_active'] : 1;
    $offsetActive = ($pageActive - 1) * $limit;
    $totalActiveQuery = "SELECT COUNT(*) AS total FROM tbl_customer WHERE c_rem NOT LIKE 'ARCHIVED:%' OR c_rem IS NULL";
    $totalActiveResult = $conn->query($totalActiveQuery);
    $totalActiveRow = $totalActiveResult->fetch_assoc();
    $totalActive = $totalActiveRow['total'];
    $totalActivePages = ceil($totalActive / $limit);

    // Archived customers
    $pageArchived = isset($_GET['page_archived']) ? (int)$_GET['page_archived'] : 1;
    $offsetArchived = ($pageArchived - 1) * $limit;
    $totalArchivedQuery = "SELECT COUNT(*) AS total FROM tbl_customer WHERE c_rem LIKE 'ARCHIVED:%'";
    $totalArchivedResult = $conn->query($totalArchivedQuery);
    $totalArchivedRow = $totalArchivedResult->fetch_assoc();
    $totalArchived = $totalArchivedRow['total'];
    $totalArchivedPages = ceil($totalArchived / $limit);

    // Fetch active customers
    $sqlActive = "SELECT c_id, c_fname, c_lname, c_area, c_contact, c_email, c_date, c_onu, c_caller, c_address, c_rem 
                  FROM tbl_customer WHERE c_rem NOT LIKE 'ARCHIVED:%' OR c_rem IS NULL LIMIT ?, ?";
    $stmtActive = $conn->prepare($sqlActive);
    $stmtActive->bind_param("ii", $offsetActive, $limit);
    $stmtActive->execute();
    $resultActive = $stmtActive->get_result();
    $stmtActive->close();

    // Fetch archived customers
    $sqlArchived = "SELECT c_id, c_fname, c_lname, c_area, c_contact, c_email, c_date, c_onu, c_caller, c_address, c_rem 
                    FROM tbl_customer WHERE c_rem LIKE 'ARCHIVED:%' LIMIT ?, ?";
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
    <title>Registered Customers</title>
    <link rel="stylesheet" href="customersT.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="wrapper">
    <div class="sidebar glass-container">
        <h2>Task Management</h2>
        <ul>
            <li><a href="staffD.php"><i class="fas fa-ticket-alt"></i> <span>View Tickets</span></a></li>
            <li><a href="assetsT.php"><i class="fas fa-box"></i> <span>View Assets</span></a></li>
            <li><a href="customersT.php" class="active"><i class="fas fa-users"></i> <span>View Customers</span></a></li>
            <li><a href="createTickets.php"><i class="fas fa-file-invoice"></i> <span>Ticket Registration</span></a></li>
            <li><a href="registerAssets.php"><i class="fas fa-plus-circle"></i> <span>Register Assets</span></a></li>
            <li><a href="addC.php"><i class="fas fa-user-plus"></i> <span>Add Customer</span></a></li>
        </ul>
        <footer>
            <a href="index.php" class="back-home"><i class="fas fa-home"></i> <span>Back to Home</span></a>
        </footer>
    </div>

    <div class="container">
        <div class="upper"> 
            <h1>Customers Info</h1>
            <div class="search-container">
                <input type="text" class="search-bar" id="searchInput" placeholder="Search customers..." onkeyup="searchCustomers()">
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
            <div class="active-customers">
                
                <div class="tab-buttons">
                    <button class="tab-btn <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'customers_active') || !isset($_GET['tab']) ? 'active' : ''; ?>" onclick="showTab('customers_active')">
                        Active (<?php echo $totalActive; ?>)
                    </button>
                    <button class="tab-btn <?php echo isset($_GET['tab']) && $_GET['tab'] === 'customers_archived' ? 'active' : ''; ?>" onclick="showTab('customers_archived')">
                        Archived
                        <?php if ($totalArchived > 0): ?>
                            <span class="tab-badge"><?php echo $totalArchived; ?></span>
                        <?php endif; ?>
                    </button>
                </div>
                <div class="customer-actions">
                    <form action="addC.php" method="get" style="display: inline;">
                        <button type="submit" class="add-user-btn"><i class="fas fa-user-plus"></i> Add Customer</button>
                    </form>
                    <button class="action-btn export-btn"><i class="fas fa-download"></i> Export</button>
                </div>
                <table id="active-customers-table">
                    <thead>
                        <tr>
                            <th>Customer ID</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Area</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Date</th>
                            <th>ONU Name</th>
                            <th>Call Id</th>
                            <th>Mac Address</th>
                            <th>Remarks</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($resultActive->num_rows > 0) {
                            while ($row = $resultActive->fetch_assoc()) {
                                echo "<tr> 
                                        <td>{$row['c_id']}</td> 
                                        <td>{$row['c_fname']}</td> 
                                        <td>{$row['c_lname']}</td> 
                                        <td>{$row['c_area']}</td> 
                                        <td>{$row['c_contact']}</td> 
                                        <td>{$row['c_email']}</td> 
                                        <td>{$row['c_date']}</td> 
                                        <td>{$row['c_onu']}</td> 
                                        <td>{$row['c_caller']}</td> 
                                        <td>{$row['c_address']}</td> 
                                        <td>" . htmlspecialchars($row['c_rem'] ?? '', ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td class='action-buttons'>
                                            <a class='view-btn' onclick=\"showViewModal('{$row['c_id']}', '{$row['c_fname']}', '{$row['c_lname']}', '{$row['c_area']}', '{$row['c_contact']}', '{$row['c_email']}', '{$row['c_date']}', '{$row['c_onu']}', '{$row['c_caller']}', '{$row['c_address']}', '" . htmlspecialchars($row['c_rem'] ?? '', ENT_QUOTES, 'UTF-8') . "')\" title='View'><i class='fas fa-eye'></i></a>
                                            <a class='edit-btn' href='editC.php?id=" . htmlspecialchars($row['c_id'], ENT_QUOTES, 'UTF-8') . "' title='Edit'><i class='fas fa-edit'></i></a>
                                            <a class='archive-btn' onclick=\"showArchiveModal('{$row['c_id']}', '{$row['c_fname']} {$row['c_lname']}')\" title='Archive'><i class='fas fa-archive'></i></a>
                                        </td>
                                      </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='12' style='text-align: center;'>No active customers found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
                <div class="pagination">
                    <?php if ($pageActive > 1): ?>
                        <a href="?tab=customers_active&page_active=<?php echo $pageActive - 1; ?>&page_archived=<?php echo $pageArchived; ?>" class="pagination-link"><i class="fas fa-chevron-left"></i></a>
                    <?php else: ?>
                        <span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>
                    <?php endif; ?>
                    <span class="current-page">Page <?php echo $pageActive; ?> of <?php echo $totalActivePages; ?></span>
                    <?php if ($pageActive < $totalActivePages): ?>
                        <a href="?tab=customers_active&page_active=<?php echo $pageActive + 1; ?>&page_archived=<?php echo $pageArchived; ?>" class="pagination-link"><i class="fas fa-chevron-right"></i></a>
                    <?php else: ?>
                        <span class="pagination-link disabled"><i class="fas fa-chevron-right"></i></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="archived-customers">
               
                <div class="tab-buttons">
                    <button class="tab-btn <?php echo isset($_GET['tab']) && $_GET['tab'] === 'customers_active' ? 'active' : ''; ?>" onclick="showTab('customers_active')">
                        Active (<?php echo $totalActive; ?>)
                    </button>
                    <button class="tab-btn <?php echo isset($_GET['tab']) && $_GET['tab'] === 'customers_archived' ? 'active' : ''; ?>" onclick="showTab('customers_archived')">
                        Archived
                        <?php if ($totalArchived > 0): ?>
                            <span class="tab-badge"><?php echo $totalArchived; ?></span>
                        <?php endif; ?>
                    </button>
                </div>
                <table id="archived-customers-table">
                    <thead>
                        <tr>
                            <th>Customer ID</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Area</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Date</th>
                            <th>ONU Name</th>
                            <th>Call Id</th>
                            <th>Mac Address</th>
                            <th>Remarks</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($resultArchived->num_rows > 0) {
                            while ($row = $resultArchived->fetch_assoc()) {
                                // Strip ARCHIVED: for display
                                $display_rem = preg_replace('/^ARCHIVED:/', '', $row['c_rem']);
                                echo "<tr> 
                                        <td>{$row['c_id']}</td> 
                                        <td>{$row['c_fname']}</td> 
                                        <td>{$row['c_lname']}</td> 
                                        <td>{$row['c_area']}</td> 
                                        <td>{$row['c_contact']}</td> 
                                        <td>{$row['c_email']}</td> 
                                        <td>{$row['c_date']}</td> 
                                        <td>{$row['c_onu']}</td> 
                                        <td>{$row['c_caller']}</td> 
                                        <td>{$row['c_address']}</td> 
                                        <td>" . htmlspecialchars($display_rem, ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td class='action-buttons'>
                                            <a class='view-btn' onclick=\"showViewModal('{$row['c_id']}', '{$row['c_fname']}', '{$row['c_lname']}', '{$row['c_area']}', '{$row['c_contact']}', '{$row['c_email']}', '{$row['c_date']}', '{$row['c_onu']}', '{$row['c_caller']}', '{$row['c_address']}', '" . htmlspecialchars($display_rem, ENT_QUOTES, 'UTF-8') . "')\" title='View'><i class='fas fa-eye'></i></a>
                                            <a class='unarchive-btn' onclick=\"showUnarchiveModal('{$row['c_id']}', '{$row['c_fname']} {$row['c_lname']}')\" title='Unarchive'><i class='fas fa-box-open'></i></a>
                                            <a class='delete-btn' onclick=\"showDeleteModal('{$row['c_id']}', '{$row['c_fname']} {$row['c_lname']}')\" title='Delete'><i class='fas fa-trash'></i></a>
                                        </td>
                                      </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='12' style='text-align: center;'>No archived customers found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
                <div class="pagination">
                    <?php if ($pageArchived > 1): ?>
                        <a href="?tab=customers_archived&page_active=<?php echo $pageActive; ?>&page_archived=<?php echo $pageArchived - 1; ?>" class="pagination-link"><i class="fas fa-chevron-left"></i></a>
                    <?php else: ?>
                        <span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>
                    <?php endif; ?>
                    <span class="current-page">Page <?php echo $pageArchived; ?> of <?php echo $totalArchivedPages; ?></span>
                    <?php if ($pageArchived < $totalArchivedPages): ?>
                        <a href="?tab=customers_archived&page_active=<?php echo $pageActive; ?>&page_archived=<?php echo $pageArchived + 1; ?>" class="pagination-link"><i class="fas fa-chevron-right"></i></a>
                    <?php else: ?>
                        <span class="pagination-link disabled"><i class="fas fa-chevron-right"></i></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Customer Modal -->
<div id="viewModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Customer Details</h2>
        </div>
        <div id="viewContent"></div>
        <div class="modal-footer">
            <button class="modal-btn cancel" onclick="closeModal('viewModal')">Close</button>
        </div>
    </div>
</div>

<!-- Archive Customer Modal -->
<div id="archiveModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Archive Customer</h2>
        </div>
        <p>Are you sure you want to archive <span id="archiveCustomerName"></span>?</p>
        <form method="POST" id="archiveForm">
            <input type="hidden" name="c_id" id="archiveCustomerId">
            <input type="hidden" name="archive_customer" value="1">
            <div class="modal-footer">
                <button type="button" class="modal-btn cancel" onclick="closeModal('archiveModal')">Cancel</button>
                <button type="submit" class="modal-btn confirm">Archive</button>
            </div>
        </form>
    </div>
</div>

<!-- Unarchive Customer Modal -->
<div id="unarchiveModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Unarchive Customer</h2>
        </div>
        <p>Are you sure you want to unarchive <span id="unarchiveCustomerName"></span>?</p>
        <form method="POST" id="unarchiveForm">
            <input type="hidden" name="c_id" id="unarchiveCustomerId">
            <input type="hidden" name="unarchive_customer" value="1">
            <div class="modal-footer">
                <button type="button" class="modal-btn cancel" onclick="closeModal('unarchiveModal')">Cancel</button>
                <button type="submit" class="modal-btn confirm">Unarchive</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Customer Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Delete Customer</h2>
        </div>
        <p>Are you sure you want to permanently delete <span id="deleteCustomerName"></span>? This action cannot be undone.</p>
        <form method="POST" id="deleteForm">
            <input type="hidden" name="c_id" id="deleteCustomerId">
            <input type="hidden" name="delete_customer" value="1">
            <div class="modal-footer">
                <button type="button" class="modal-btn cancel" onclick="closeModal('deleteModal')">Cancel</button>
                <button type="submit" class="modal-btn delete">Delete</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab') || 'customers_active';
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

function showTab(tab) {
    const activeSection = document.querySelector('.active-customers');
    const archivedSection = document.querySelector('.archived-customers');

    if (tab === 'customers_active') {
        const activeTabButtons = activeSection.querySelectorAll('.tab-btn');
        activeTabButtons.forEach(button => button.classList.remove('active'));
        const activeButton = Array.from(activeTabButtons).find(button => button.onclick.toString().includes(`showTab('customers_active')`));
        if (activeButton) {
            activeButton.classList.add('active');
        }
        activeSection.style.display = 'block';
        archivedSection.style.display = 'none';
    } else if (tab === 'customers_archived') {
        const archivedTabButtons = archivedSection.querySelectorAll('.tab-btn');
        archivedTabButtons.forEach(button => button.classList.remove('active'));
        const archivedButton = Array.from(archivedTabButtons).find(button => button.onclick.toString().includes(`showTab('customers_archived')`));
        if (archivedButton) {
            archivedButton.classList.add('active');
        }
        activeSection.style.display = 'none';
        archivedSection.style.display = 'block';
    }

    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('tab', tab);
    history.replaceState(null, '', '?' + urlParams.toString());
}

function searchCustomers() {
    const input = document.getElementById('searchInput').value.toLowerCase();

    // Search active customers
    const activeTable = document.getElementById('active-customers-table');
    if (activeTable && document.querySelector('.active-customers').style.display !== 'none') {
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

    // Search archived customers
    const archivedTable = document.getElementById('archived-customers-table');
    if (archivedTable && document.querySelector('.archived-customers').style.display !== 'none') {
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
            activeRows[i].style.display = match ? '' : 'none';
        }
    }
}

function showViewModal(id, fname, lname, area, contact, email, date, onu, caller, address, rem) {
    document.getElementById('viewContent').innerHTML = `
        <div class="customer-details">
            <p><strong>ID:</strong> ${id}</p>
            <p><strong>Name:</strong> ${fname} ${lname}</p>
            <p><strong>Area:</strong> ${area}</p>
            <p><strong>Contact:</strong> ${contact}</p>
            <p><strong>Email:</strong> ${email || 'N/A'}</p>
            <p><strong>Date Added:</strong> ${date}</p>
            <p><strong>ONU Name:</strong> ${onu || 'N/A'}</p>
            <p><strong>Caller ID:</strong> ${caller || 'N/A'}</p>
            <p><strong>MAC Address:</strong> ${address || 'N/A'}</p>
            <p><strong>Remarks:</strong> ${rem || 'N/A'}</p>
        </div>
    `;
    document.getElementById('viewModal').style.display = 'block';
}

function showArchiveModal(id, name) {
    document.getElementById('archiveCustomerId').value = id;
    document.getElementById('archiveCustomerName').innerText = name;
    document.getElementById('archiveModal').style.display = 'block';
}

function showUnarchiveModal(id, name) {
    document.getElementById('unarchiveCustomerId').value = id;
    document.getElementById('unarchiveCustomerName').innerText = name;
    document.getElementById('unarchiveModal').style.display = 'block';
}

function showDeleteModal(id, name) {
    document.getElementById('deleteCustomerId').value = id;
    document.getElementById('deleteCustomerName').innerText = name;
    document.getElementById('deleteModal').style.display = 'block';
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