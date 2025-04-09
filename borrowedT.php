<?php
session_start();
include 'db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$username = $_SESSION['username'] ?? '';
$lastName = '';
$firstName = '';
$userType = '';
$avatarFolder = 'uploads/avatars/';
$userAvatar = $avatarFolder . $username . '.png';
$defaultAvatar = 'default-avatar.png';

if (!$username) {
    echo "Session username not set.";
    exit();
}

// Fetch user details from database
if ($conn) {
    $sql = "SELECT u_fname, u_lname, u_type FROM tbl_user WHERE u_username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($firstName, $lastName, $userType);
    $stmt->fetch();
    $stmt->close();

    // Pagination setup
    $limit = 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;

    // Fetch total number of borrowed assets
    $countQuery = "SELECT COUNT(*) as total FROM tbl_borrowed";
    $countResult = $conn->query($countQuery);
    $totalRecords = $countResult->fetch_assoc()['total'];
    $totalPages = ceil($totalRecords / $limit);

    // Fetch borrowed assets with pagination
    $sqlBorrowed = "SELECT b_id, b_assets_name, b_quantity, b_technician_name, b_technician_id, b_date 
                    FROM tbl_borrowed 
                    LIMIT ?, ?";
    $stmt = $conn->prepare($sqlBorrowed);
    $stmt->bind_param("ii", $offset, $limit);
    $stmt->execute();
    $resultBorrowed = $stmt->get_result();
} else {
    echo "Database connection failed.";
    exit();
}

// Set avatar path
if (file_exists($userAvatar)) {
    $_SESSION['avatarPath'] = $userAvatar . '?' . time();
} else {
    $_SESSION['avatarPath'] = $defaultAvatar;
}
$avatarPath = $_SESSION['avatarPath'];

// Check for deletion or update success
if (isset($_GET['deleted']) && $_GET['deleted'] == 'true') {
    $_SESSION['message'] = "Record deleted successfully!";
}
if (isset($_GET['updated']) && $_GET['updated'] == 'true') {
    $_SESSION['message'] = "Record updated successfully!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrowed Assets</title>
    <link rel="stylesheet" href="borrowedT.css"> 
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
            <h1>Borrowed Assets</h1>
            <div class="search-container">
                <input type="text" class="search-bar" id="searchInput" placeholder="Search borrowed assets..." onkeyup="searchUsers()">
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

            <div class="borrowed">
                <div class="button-container">
                    <a href="borrowA.php" class="borrow-btn"><i class="fas fa-plus"></i> Borrow</a>
                    <a href="createTickets.php" class="export-btn"><i class="fas fa-download"></i> Export</a>
                </div>
                <table id="borrowedTable">
                    <thead>
                        <tr>
                            <th>Borrowed ID</th>
                            <th>Asset Name</th>
                            <th>Quantity</th>
                            <th>Technician Name</th>
                            <th>Technician ID</th>
                            <th>Borrowed Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php 
                        if ($resultBorrowed && $resultBorrowed->num_rows > 0) { 
                            while ($row = $resultBorrowed->fetch_assoc()) { 
                                echo "<tr> 
                                        <td>{$row['b_id']}</td> 
                                        <td>" . (isset($row['b_assets_name']) ? htmlspecialchars($row['b_assets_name'], ENT_QUOTES, 'UTF-8') : 'N/A') . "</td>  
                                        <td>{$row['b_quantity']}</td>
                                        <td>{$row['b_technician_name']}</td>
                                        <td>{$row['b_technician_id']}</td>    
                                        <td>{$row['b_date']}</td> 
                                        <td>
                                            <a class='view-btn' onclick=\"showViewModal('{$row['b_id']}', '" . htmlspecialchars($row['b_assets_name'], ENT_QUOTES, 'UTF-8') . "', '{$row['b_quantity']}', '{$row['b_technician_name']}', '{$row['b_technician_id']}', '{$row['b_date']}')\" title='View'><i class='fas fa-eye'></i></a>
                                            <a href='editR.php?id={$row['b_id']}' class='edit-btn' title='Edit'><i class='fas fa-edit'></i></a>
                                            <a href='#' class='delete-btn' onclick='showDeleteModal({$row['b_id']})' title='Delete'><i class='fas fa-trash'></i></a>
                                        </td>
                                      </tr>"; 
                            } 
                        } else { 
                            echo "<tr><td colspan='7'>No borrowed assets found.</td></tr>"; 
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
</div>

<!-- View Modal -->
<div id="viewModal" class="modal-background">
    <div class="modal-content">
        <span class="close" onclick="closeModal('viewModal')">×</span>
        <h2>View Borrowed Asset</h2>
        <div id="viewModalContent" style="margin-top: 20px;">
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal-background">
    <div class="modal-content delete-modal-content">
        <span class="close" onclick="closeModal('deleteModal')">×</span>
        <h2>Confirm Deletion</h2>
        <p>Are you sure you want to delete this borrowed asset record?</p>
        <div style="margin-top: 25px;">
            <button onclick="closeModal('deleteModal')">Cancel</button>
            <button onclick="confirmDelete()">Delete</button>
        </div>
    </div>
</div>

<script>
let currentDeleteId = null;

function searchUsers() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toUpperCase();
    const table = document.getElementById('borrowedTable');
    const tr = table.getElementsByTagName('tr');

    for (let i = 1; i < tr.length; i++) {
        let found = false;
        const td = tr[i].getElementsByTagName('td');
        
        for (let j = 0; j < td.length; j++) {
            if (td[j]) {
                const txtValue = td[j].textContent || td[j].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }
        tr[i].style.display = found ? '' : 'none';
    }
}

function showViewModal(id, assetName, quantity, technicianName, technicianId, date) {
    const modalContent = `
        <p><strong>Asset Name:</strong> ${assetName}</p>
        <p><strong>Quantity:</strong> ${quantity}</p>
        <p><strong>Technician Name:</strong> ${technicianName}</p>
        <p><strong>Technician ID:</strong> ${technicianId}</p>
        <p><strong>Borrowed Date:</strong> ${date}</p>
    `;
    document.getElementById('viewModalContent').innerHTML = modalContent;
    document.getElementById('viewModal').style.display = 'flex';
}

function showDeleteModal(id) {
    currentDeleteId = id;
    document.getElementById('deleteModal').style.display = 'flex';
}

function confirmDelete() {
    if (currentDeleteId) {
        fetch(`deleteR.php?id=${currentDeleteId}`, {
            method: 'GET'
        })
        .then(response => response.text())
        .then(data => {
            updateTable();
            closeModal('deleteModal');
            window.location.href = 'borrowedT.php?deleted=true';
        })
        .catch(error => console.error('Error:', error));
    }
}

function updateTable() {
    fetch('borrowedT.php')
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

window.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal-background')) {
        event.target.style.display = 'none';
    }
});

// Auto-update table every 30 seconds
setInterval(updateTable, 30000);

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