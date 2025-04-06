<?php 
include 'db.php'; 
session_start(); 

if (!isset($_SESSION['username'])) { 
    header("Location: index.php");
    exit(); 
}

$username = $_SESSION['username'];
$lastName = '';
$firstName = '';
$userType = '';
$avatarPath = 'default-avatar.png';
$avatarFolder = 'uploads/avatars/';
$userAvatar = $avatarFolder . $username . '.png';

if (file_exists($userAvatar)) {
    $_SESSION['avatarPath'] = $userAvatar . '?' . time();
} else {
    $_SESSION['avatarPath'] = 'default-avatar.png';
}

$avatarPath = $_SESSION['avatarPath'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'active';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $archivedPage = isset($_GET['archived_page']) ? (int)$_GET['archived_page'] : 1;

    if (isset($_POST['add_user'])) {
        $fname = $_POST['u_fname'];
        $lname = $_POST['u_lname'];
        $email = $_POST['u_email'];
        $new_username = $_POST['u_username'];
        $type = $_POST['u_type'];
        $status = $_POST['u_status'];
        $password = password_hash($_POST['u_password'], PASSWORD_DEFAULT);
    
        $sql = "INSERT INTO tbl_user (u_fname, u_lname, u_email, u_username, u_type, u_status, u_password) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("sssssss", $fname, $lname, $email, $new_username, $type, $status, $password);
        if ($stmt->execute()) {
            $_SESSION['message'] = "User added successfully!";
        } else {
            $_SESSION['error'] = "Error adding user: " . $stmt->error;
        }
        $stmt->close();
    
    } elseif (isset($_POST['edit_user'])) {
        $id = $_POST['u_id'];
        $fname = $_POST['u_fname'];
        $lname = $_POST['u_lname'];
        $email = $_POST['u_email'];
        $new_username = $_POST['u_username'];
        $type = $_POST['u_type'];
        $status = $_POST['u_status'];

        $sql = "UPDATE tbl_user SET u_fname=?, u_lname=?, u_email=?, u_username=?, u_type=?, u_status=? WHERE u_id=?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("ssssssi", $fname, $lname, $email, $new_username, $type, $status, $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "User updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating user: " . $stmt->error;
        }
        $stmt->close();
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
    } elseif (isset($_POST['restore_all_users'])) {
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

    // Redirect based on current tab
    if ($currentTab === 'archived') {
        header("Location: viewU.php?tab=archived&archived_page=$archivedPage");
    } else {
        header("Location: viewU.php?tab=active&page=$page");
    }
    exit();
}

if ($conn) {
    $sqlUser = "SELECT u_fname, u_lname, u_type FROM tbl_user WHERE u_username = ?";
    $stmt = $conn->prepare($sqlUser);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $resultUser = $stmt->get_result();

    if ($resultUser->num_rows > 0) {
        $row = $resultUser->fetch_assoc();
        $firstName = $row['u_fname'];
        $lastName = $row['u_lname'];
        $userType = $row['u_type'];
    }

    $limit = 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;

    // Active and pending users pagination
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

    // Archived users pagination
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
} else {
    echo "Database connection failed.";
    exit();
}
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
</head>
<body>
<div class="wrapper">
    <div class="sidebar glass-container">
        <h2>Task Management</h2>
        <ul>
            <li><a href="adminD.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="viewU.php" class="active"><i class="fas fa-users"></i> <span>View Users</span></a></li>
            <li><a href="view_service_record.php"><i class="fas fa-file-alt"></i> <span>Service Records</span></a></li>
            <li><a href="view_incident_report.php"><i class="fas fa-exclamation-triangle"></i> <span>Incident Reports</span></a></li>
            <li><a href="logs.php"><i class="fas fa-book"></i> <span>View Logs</span></a></li>
        </ul>
        <footer>
            <a href="index.php" class="back-home"><i class="fas fa-home"></i> <span>Back to Home</span></a>
        </footer>
    </div>

    <div class="container">
        <div class="upper"> 
            <h1>Registered User</h1>
            <div class="search-container">
                <input type="text" class="search-bar" id="searchInput" placeholder="Search users..." onkeyup="searchUsers()">
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
                    <button class="add-user-btn" onclick="showAddModal()"><i class="fas fa-user-plus"></i> Add New User</button>
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
                    <tbody>
                        <?php 
                        if ($activeAndPendingUsers->num_rows > 0) { 
                            while ($row = $activeAndPendingUsers->fetch_assoc()) { 
                                echo "<tr> 
                                        <td>{$row['u_id']}</td> 
                                        <td>{$row['u_fname']}</td> 
                                        <td>{$row['u_lname']}</td> 
                                        <td>{$row['u_email']}</td> 
                                        <td>{$row['u_username']}</td> 
                                        <td>" . ucfirst(strtolower($row['u_type'])) . "</td> 
                                        <td class='status-" . strtolower($row['u_status']) . "'>" . ucfirst(strtolower($row['u_status'])) . "</td>
                                        <td class='action-buttons'>
                                            <a class='view-btn' onclick=\"showViewModal('{$row['u_id']}', '{$row['u_fname']}', '{$row['u_lname']}', '{$row['u_email']}', '{$row['u_username']}', '{$row['u_type']}', '{$row['u_status']}')\" title='View'><i class='fas fa-eye'></i></a>
                                            <a class='edit-btn' onclick=\"showEditModal('{$row['u_id']}', '{$row['u_fname']}', '{$row['u_lname']}', '{$row['u_email']}', '{$row['u_username']}', '{$row['u_type']}', '{$row['u_status']}')\" title='Edit'><i class='fas fa-edit'></i></a>
                                            <a class='archive-btn' onclick=\"showArchiveModal('{$row['u_id']}', '{$row['u_fname']} {$row['u_lname']}')\" title='Archive'><i class='fas fa-archive'></i></a>
                                        </td>
                                      </tr>"; 
                            } 
                        } else { 
                            echo "<tr><td colspan='8' style='text-align: center;'>No active or pending users found.</td></tr>"; 
                        } 
                        ?>
                    </tbody>
                </table>

                <div class="pagination">
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
                    <tbody>
                        <?php 
                        if ($archivedResult->num_rows > 0) { 
                            while ($row = $archivedResult->fetch_assoc()) { 
                                echo "<tr> 
                                        <td>{$row['u_id']}</td> 
                                        <td>{$row['u_fname']}</td> 
                                        <td>{$row['u_lname']}</td> 
                                        <td>{$row['u_email']}</td> 
                                        <td>{$row['u_username']}</td> 
                                        <td>" . ucfirst(strtolower($row['u_type'])) . "</td> 
                                        <td class='status-" . strtolower($row['u_status']) . "'>" . ucfirst(strtolower($row['u_status'])) . "</td>
                                        <td class='action-buttons'>
                                            <a class='view-btn' onclick=\"showViewModal('{$row['u_id']}', '{$row['u_fname']}', '{$row['u_lname']}', '{$row['u_email']}', '{$row['u_username']}', '{$row['u_type']}', '{$row['u_status']}')\" title='View'><i class='fas fa-eye'></i></a>
                                            <a class='unarchive-btn' onclick=\"showRestoreModal('{$row['u_id']}', '{$row['u_fname']} {$row['u_lname']}')\" title='Unarchive'><i class='fas fa-box-open'></i></a>
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
                <div class="pagination">
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

<!-- Add User Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add New User</h2>
        </div>
        <form method="POST" class="modal-form">
            <input type="text" name="u_fname" placeholder="First Name" required>
            <input type="text" name="u_lname" placeholder="Last Name" required>
            <input type="email" name="u_email" placeholder="Email" required>
            <input type="text" name="u_username" placeholder="Username" required>
            <select name="u_type" required>
                <option value="admin">Admin</option>
                <option value="user">User</option>
            </select>
            <select name="u_status" required>
                <option value="active">Active</option>
                <option value="pending">Pending</option>
            </select>
            <input type="password" name="u_password" placeholder="Password" required>
            <input type="hidden" name="add_user" value="1">
            <div class="modal-footer">
                <button type="button" class="modal-btn cancel" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="modal-btn confirm">Add User</button>
            </div>
        </form>
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

<!-- Edit User Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit User</h2>
        </div>
        <form method="POST" class="modal-form" id="editForm">
            <input type="hidden" name="u_id" id="editUserId">
            <input type="text" name="u_fname" id="editFname" placeholder="First Name" required>
            <input type="text" name="u_lname" id="editLname" placeholder="Last Name" required>
            <input type="email" name="u_email" id="editEmail" placeholder="Email" required>
            <input type="text" name="u_username" id="editUsername" placeholder="Username" required>
            <select name="u_type" id="editType" required>
                <option value="admin">Admin</option>
                <option value="user">User</option>
            </select>
            <select name="u_status" id="editStatus" required>
                <option value="active">Active</option>
                <option value="pending">Pending</option>
            </select>
            <input type="hidden" name="edit_user" value="1">
            <div class="modal-footer">
                <button type="button" class="modal-btn cancel" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="modal-btn confirm">Save Changes</button>
            </div>
        </form>
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

<script>
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
            history.pushState(null, '', '?tab=active&page=<?php echo $page; ?>');
        } else if (tabName === 'archived') {
            archivedTab.classList.add('active');
            buttons[1].classList.add('active');
            history.pushState(null, '', '?tab=archived&archived_page=<?php echo $archivedPage; ?>');
        }
        searchUsers();
    }

    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab') || 'active';
        showTab(tab);
    });

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    function showAddModal() {
        document.getElementById('addModal').style.display = 'block';
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

    function showEditModal(id, fname, lname, email, username, type, status) {
        document.getElementById('editUserId').value = id;
        document.getElementById('editFname').value = fname;
        document.getElementById('editLname').value = lname;
        document.getElementById('editEmail').value = email;
        document.getElementById('editUsername').value = username;
        
        const typeSelect = document.getElementById('editType');
        for (let i = 0; i < typeSelect.options.length; i++) {
            if (typeSelect.options[i].value === type.toLowerCase()) {
                typeSelect.options[i].selected = true;
                break;
            }
        }
        
        const statusSelect = document.getElementById('editStatus');
        for (let i = 0; i < statusSelect.options.length; i++) {
            if (statusSelect.options[i].value === status.toLowerCase()) {
                statusSelect.options[i].selected = true;
                break;
            }
        }
        
        document.getElementById('editModal').style.display = 'block';
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

    function showRestoreAllModal() {
        document.getElementById('restoreAllModal').style.display = 'block';
    }

    function searchUsers() {
        const input = document.getElementById('searchInput').value.toLowerCase();
        const activeTab = document.querySelector('.tab-btn.active').textContent.toLowerCase();
        
        if (activeTab.includes('user')) {
            const activeTable = document.getElementById('active-users-table');
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
        } else if (activeTab.includes('archived')) {
            const archivedTable = document.getElementById('archived-users-table');
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
</script>
</body>
</html>