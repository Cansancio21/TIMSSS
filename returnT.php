<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) { 
    header("Location: index.php");
    exit(); 
}

// Get user details for the header
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

// Check for success messages
if (isset($_GET['deleted']) && $_GET['deleted'] == 'true') {
    $_SESSION['message'] = "Record deleted successfully!";
}
if (isset($_GET['updated']) && $_GET['updated'] == 'true') {
    $_SESSION['message'] = "Record updated successfully!";
}

// Get user info for header
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

    // Pagination setup
    $limit = 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;

    // Count total records for pagination
    $countQuery = "SELECT COUNT(*) as total FROM tbl_returned";
    $countResult = $conn->query($countQuery);
    $totalRecords = $countResult->fetch_assoc()['total'];
    $totalPages = ceil($totalRecords / $limit);

    // Main query with pagination
    $sqlBorrowed = "SELECT r_id, r_assets_name, r_quantity, r_technician_name, r_technician_id, r_date FROM tbl_returned LIMIT ?, ?";
    $stmt = $conn->prepare($sqlBorrowed);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("ii", $offset, $limit);
    $stmt->execute();
    $resultBorrowed = $stmt->get_result();
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
    <title>Returned Assets</title>
    <link rel="stylesheet" href="returnT.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="wrapper">
    <div class="sidebar glass-container">
        <h2>Task Management</h2>
        <ul>
            <li><a href="adminD.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="viewU.php"><i class="fas fa-users"></i> View Users</a></li>
            <li><a href="view_service_record.php"><i class="fas fa-file-alt"></i> View Service Record</a></li>
            <li><a href="logs.php"><i class="fas fa-file-archive"></i> View Logs</a></li>
            <li><a href="borrowedT.php"><i class="fas fa-box-open"></i>Borrowed Records</a></li>
            <li><a href="returnT.php"><i class="fas fa-undo-alt"></i> Return Records</a></li>
        </ul>
        <footer>
            <a href="index.php" class="back-home"><i class="fas fa-home"></i> Back to Home</a>
        </footer>
    </div>

    <div class="container">
        <div class="upper">
            <h1>Returned Assets</h1>
            <div class="search-container">
                <input type="text" class="search-bar" id="searchInput" placeholder="Search returned assets..." onkeyup="searchAssets()">
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
            
            <div class="action-buttons">
                <button class="action-btn export-btn"><i class="fas fa-download"></i> Export</button>
            </div>
            
            <table id="returned-assets-table">
                <thead>
                    <tr>
                        <th>Return ID</th>
                        <th>Asset Name</th>
                        <th>Quantity</th>
                        <th>Technician Name</th>
                        <th>Technician ID</th>
                        <th>Return Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php 
                    if ($resultBorrowed && $resultBorrowed->num_rows > 0) { 
                        while ($row = $resultBorrowed->fetch_assoc()) { 
                            echo "<tr> 
                                    <td>{$row['r_id']}</td> 
                                    <td>" . htmlspecialchars($row['r_assets_name'], ENT_QUOTES, 'UTF-8') . "</td>  
                                    <td>{$row['r_quantity']}</td>
                                    <td>" . htmlspecialchars($row['r_technician_name'], ENT_QUOTES, 'UTF-8') . "</td>
                                    <td>{$row['r_technician_id']}</td>    
                                    <td>{$row['r_date']}</td> 
                                    <td class='action-buttons'>
                                        <a class='view-btn' onclick=\"showViewModal('{$row['r_id']}', '" . htmlspecialchars($row['r_assets_name'], ENT_QUOTES, 'UTF-8') . "', '{$row['r_quantity']}', '" . htmlspecialchars($row['r_technician_name'], ENT_QUOTES, 'UTF-8') . "', '{$row['r_technician_id']}', '{$row['r_date']}')\" title='View'><i class='fas fa-eye'></i></a>
                                        <a href='editB.php?id={$row['r_id']}' class='edit-btn' title='Edit'><i class='fas fa-edit'></i></a>
                                        <a class='delete-btn' onclick=\"showDeleteModal('{$row['r_id']}', '" . htmlspecialchars($row['r_assets_name'], ENT_QUOTES, 'UTF-8') . "')\" title='Delete'><i class='fas fa-trash'></i></a>
                                    </td>
                                  </tr>"; 
                        } 
                    } else { 
                        echo "<tr><td colspan='7' style='text-align: center;'>No returned assets found.</td></tr>"; 
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

<!-- View Asset Modal -->
<div id="viewModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Asset Details</h2>
        </div>
        <div id="viewContent"></div>
        <div class="modal-footer">
            <button class="modal-btn cancel" onclick="closeModal('viewModal')">Close</button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Delete Asset</h2>
        </div>
        <p>Are you sure you want to delete the returned asset: <span id="deleteAssetName"></span>?</p>
        <div class="modal-footer">
            <button type="button" class="modal-btn cancel" onclick="closeModal('deleteModal')">Cancel</button>
            <button type="button" class="modal-btn confirm" onclick="confirmDelete()">Delete</button>
        </div>
    </div>
</div>

<script>
let currentDeleteId = null;

function searchAssets() {
    const input = document.getElementById('searchInput').value.toLowerCase();
    const table = document.getElementById('returned-assets-table');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    
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

function showViewModal(id, name, quantity, techName, techId, date) {
    document.getElementById('viewContent').innerHTML = `
        <div class="view-details">
            <p><strong>Return ID:</strong> ${id}</p>
            <p><strong>Asset Name:</strong> ${name}</p>
            <p><strong>Quantity:</strong> ${quantity}</p>
            <p><strong>Technician Name:</strong> ${techName}</p>
            <p><strong>Technician ID:</strong> ${techId}</p>
            <p><strong>Return Date:</strong> ${date}</p>
        </div>
    `;
    document.getElementById('viewModal').style.display = 'block';
}

function showDeleteModal(id, name) {
    currentDeleteId = id;
    document.getElementById('deleteAssetName').textContent = name;
    document.getElementById('deleteModal').style.display = 'block';
}

function confirmDelete() {
    if (currentDeleteId) {
        fetch(`deleteB.php?id=${currentDeleteId}`, {
            method: 'GET'
        })
        .then(response => response.text())
        .then(data => {
            updateTable();
            closeModal('deleteModal');
            window.location.href = 'returnT.php?deleted=true';
        })
        .catch(error => console.error('Error:', error));
    }
}

function updateTable() {
    fetch('returnT.php')
    .then(response => response.text())
    .then(data => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(data, 'text/html');
        const newTableBody = doc.querySelector('#tableBody');
        const currentTableBody = document.querySelector('#tableBody');
        currentTableBody.innerHTML = newTableBody.innerHTML;
    })
    .catch(error => console.error('Error updating table:', error));
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

window.onclick = function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    });
}

// Handle alert fade-out
const alerts = document.querySelectorAll('.alert');
alerts.forEach(alert => {
    setTimeout(() => {
        alert.classList.add('alert-hidden');
        setTimeout(() => alert.remove(), 500); // Remove after fade-out (500ms)
    }, 2000); // 2 seconds delay before starting fade-out
});
</script>
</body>
</html>

<?php 
$conn->close();
?>