<?php
session_start();
include 'db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Handle form submissions (e.g., delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_asset'])) {
    $id = (int)$_POST['b_id'];
    $sql = "DELETE FROM tbl_borrowed WHERE b_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('Prepare failed: ' . $conn->error);
        $_SESSION['error'] = "Error deleting record: " . $conn->error;
    } else {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Record deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting record: " . $stmt->error;
        }
        $stmt->close();
    }
    header("Location: techBorrowed.php?page=" . (isset($_GET['page']) ? (int)$_GET['page'] : 1) . (isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''));
    exit();
}

// Handle AJAX request for asset name
if (isset($_GET['id']) && !isset($_GET['page']) && !isset($_GET['deleted']) && !isset($_GET['updated']) && !isset($_GET['search'])) {
    error_log('AJAX handler triggered for id: ' . $_GET['id']);
    header('Content-Type: application/json');
    $id = (int)$_GET['id'];
    $sql = "SELECT b_assets_name FROM tbl_borrowed WHERE b_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('Prepare failed: ' . $conn->error);
        echo json_encode(['assetName' => null, 'error' => 'Prepare failed: ' . $conn->error]);
        $conn->close();
        exit();
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode(['assetName' => $row['b_assets_name']]);
    } else {
        echo json_encode(['assetName' => null]);
    }
    
    $stmt->close();
    $conn->close();
    exit();
}

$username = $_SESSION['username'] ?? '';
$lastName = '';
$firstName = '';
$userType = '';
$avatarFolder = 'Uploads/avatars/';
$userAvatar = $avatarFolder . $username . '.png';
$defaultAvatar = 'default-avatar.png';
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

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
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($page - 1) * $limit;

    // Fetch total number of borrowed assets
    $countQuery = "SELECT COUNT(*) as total FROM tbl_borrowed";
    if ($searchTerm) {
        $countQuery .= " WHERE b_assets_name LIKE ? OR b_technician_name LIKE ? OR b_technician_id LIKE ?";
    }
    $stmtCount = $conn->prepare($countQuery);
    if ($searchTerm) {
        $searchLike = "%$searchTerm%";
        $stmtCount->bind_param("sss", $searchLike, $searchLike, $searchLike);
    }
    $stmtCount->execute();
    $countResult = $stmtCount->get_result();
    $totalRecords = $countResult->fetch_assoc()['total'];
    $totalPages = ceil($totalRecords / $limit) ?: 1;
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $limit;
    $stmtCount->close();

    // Fetch borrowed assets with pagination
    $sqlBorrowed = "SELECT b_id, b_assets_name, b_quantity, b_technician_name, b_technician_id, b_date 
                    FROM tbl_borrowed";
    if ($searchTerm) {
        $sqlBorrowed .= " WHERE b_assets_name LIKE ? OR b_technician_name LIKE ? OR b_technician_id LIKE ?";
    }
    $sqlBorrowed .= " LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sqlBorrowed);
    if ($searchTerm) {
        $stmt->bind_param("sssii", $searchLike, $searchLike, $searchLike, $limit, $offset);
    } else {
        $stmt->bind_param("ii", $limit, $offset);
    }
    $stmt->execute();
    $resultBorrowed = $stmt->get_result();
    $stmt->close();
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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Borrowed Assets</title>
    <link rel="stylesheet" href="techsB.css"> 
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
    </style>
</head>
<body>
<div class="wrapper">
    <div class="sidebar glass-container">
        <h2>Task Management</h2>
        <ul>
            <li><a href="technicianD.php"><img src="https://img.icons8.com/parakeet/35/dashboard.png" alt="dashboard"/><span>Dashboard</span></a></li>
            <li><a href="staffD.php"><img src="https://img.icons8.com/plasticine/100/ticket.png" alt="ticket"/><span>Regular Tickets</span></a></li>
            <li><a href="javascript:void(0)" class="support-tickets-link" onclick="toggleSupportInput()"> <img src="https://img.icons8.com/plasticine/100/ticket.png" alt="ticket"/></i><span>Support Tickets</span></a>
                <div class="support-tickets-input" id="supportTicketInput">
                    <input type="text" id="supportCustomerId" placeholder="Enter Customer ID" required>
                    <button onclick="goToSupportTicket()" title="View Support Tickets"><i class="fas fa-arrow-right"></i></button>
                </div>
            </li>
            <li><a href="assetsT.php"><img src="https://img.icons8.com/matisse/100/view.png" alt="view"/><span>View Assets</span></a></li>
            <li><a href="techBorrowed.php" class="active"> <img src="https://img.icons8.com/cotton/35/documents--v1.png" alt="documents--v1"/> <span>Borrowed Records</span></a></li>
        </ul>
        <footer>
          <a href="index.php" class="back-home"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </footer>
    </div>

    <div class="container">
        <div class="upper"> 
            <h1>Borrowed Assets</h1>
            <div class="search-container">
                <input type="text" class="search-bar" id="searchInput" placeholder="Search borrowed assets..." value="<?php echo htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8'); ?>" onkeyup="debouncedSearchAssets()">
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
                <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
        </div>

        <div class="table-box glass-container">
            <?php if ($userType === 'admin'): ?>
                <div class="username">
                    Welcome, <?php echo htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8'); ?>!
                    <i class="fas fa-user-shield admin-icon"></i>
                </div>
            <?php endif; ?>

            <div class="borrowed">
                <div class="button-container">
                    <a href="borrowA.php" class="borrow-btn"><i class="fas fa-plus"></i> Borrow</a>
                    <a href="return.php" class="return-btn"><i class="fas fa-undo"></i> Return</a>
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
                                        <td>" . htmlspecialchars($row['b_assets_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8') . "</td>  
                                        <td>" . htmlspecialchars($row['b_quantity'], ENT_QUOTES, 'UTF-8') . "</td>
                                        <td>" . htmlspecialchars($row['b_technician_name'], ENT_QUOTES, 'UTF-8') . "</td>
                                        <td>" . htmlspecialchars($row['b_technician_id'], ENT_QUOTES, 'UTF-8') . "</td>    
                                        <td>" . htmlspecialchars($row['b_date'], ENT_QUOTES, 'UTF-8') . "</td> 
                                        <td>
                                            <a class='view-btn' onclick=\"showViewModal('{$row['b_id']}', '" . htmlspecialchars($row['b_assets_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['b_quantity'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['b_technician_name'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['b_technician_id'], ENT_QUOTES, 'UTF-8') . "', '" . htmlspecialchars($row['b_date'], ENT_QUOTES, 'UTF-8') . "')\" title='View'><i class='fas fa-eye'></i></a>
                                            <a href='editBR.php?id={$row['b_id']}' class='edit-btn' title='Edit'><i class='fas fa-edit'></i></a>
                                            <a class='delete-btn' onclick=\"showDeleteModal('{$row['b_id']}', '" . htmlspecialchars($row['b_assets_name'] ?? 'Unknown Asset', ENT_QUOTES, 'UTF-8') . "')\" title='Delete'><i class='fas fa-trash'></i></a>
                                        </td>
                                      </tr>"; 
                            } 
                        } else { 
                            echo "<tr><td colspan='7'>No borrowed assets found.</td></tr>"; 
                        } 
                        ?>
                    </tbody>
                </table>

                <div class="pagination" id="borrowed-pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo $searchTerm ? '&search=' . urlencode($searchTerm) : ''; ?>" class="pagination-link"><i class="fas fa-chevron-left"></i></a>
                    <?php else: ?>
                        <span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>
                    <?php endif; ?>
                    <span class="current-page">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo $searchTerm ? '&search=' . urlencode($searchTerm) : ''; ?>" class="pagination-link"><i class="fas fa-chevron-right"></i></a>
                    <?php else: ?>
                        <span class="pagination-link disabled"><i class="fas fa-chevron-right"></i></span>
                    <?php endif; ?>
                </div>
            </div>       
        </div>
    </div>
</div>

<!-- View Modal -->
<div id="viewModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>View Borrowed Asset</h2>
        </div>
        <div id="viewModalContent" style="margin-top: 20px;"></div>
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
        <p>Are you sure you want to delete the borrowed asset: <span id="deleteAssetName"></span>?</p>
        <form method="POST" id="deleteForm">
            <input type="hidden" name="b_id" id="deleteAssetId">
            <input type="hidden" name="delete_asset" value="1">
            <div class="modal-footer">
                <button type="button" class="modal-btn cancel" onclick="closeModal('deleteModal')">Cancel</button>
                <button type="submit" class="modal-btn confirm">Delete</button>
            </div>
        </form>
    </div>
</div>

<script>
let updateInterval = null;

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

function searchAssets(page = 1) {
    const searchTerm = document.getElementById('searchInput').value;
    const url = new URL(window.location);
    url.searchParams.set('page', page);
    if (searchTerm) {
        url.searchParams.set('search', searchTerm);
    } else {
        url.searchParams.delete('search');
    }
    window.location.href = url.toString();
}

const debouncedSearchAssets = debounce(searchAssets, 300);

function showViewModal(id, assetName, quantity, technicianName, technicianId, date) {
    console.log('showViewModal called with id:', id);
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

function showDeleteModal(id, assetName) {
    console.log('showDeleteModal called with id:', id, 'assetName:', assetName);
    document.getElementById('deleteAssetId').value = id;
    document.getElementById('deleteAssetName').textContent = assetName;
    document.getElementById('deleteModal').style.display = 'flex';
}

function updateTable() {
    const searchTerm = document.getElementById('searchInput').value;
    const url = `techBorrowed.php?page=<?php echo $page; ?>${searchTerm ? '&search=' + encodeURIComponent(searchTerm) : ''}`;
    fetch(url)
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
    console.log('closeModal called for:', modalId);
    document.getElementById(modalId).style.display = 'none';
}

function toggleSupportInput() {
    console.log('toggleSupportInput called');
    const inputDiv = document.getElementById('supportTicketInput');
    inputDiv.classList.toggle('active');
}

function goToSupportTicket() {
    console.log('goToSupportTicket called');
    const customerId = document.getElementById('supportCustomerId').value;
    if (customerId) {
        window.location.href = `supportTickets.php?customer_id=${encodeURIComponent(customerId)}`;
    } else {
        alert('Please enter a Customer ID');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // Handle alert fade-out
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.classList.add('alert-hidden');
            setTimeout(() => alert.remove(), 500);
        }, 2000);
    });

    // Start auto-update table
    updateInterval = setInterval(updateTable, 30000);
});

// Clear interval when leaving the page
window.addEventListener('beforeunload', () => {
    if (updateInterval) {
        clearInterval(updateInterval);
    }
});

window.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal')) {
        console.log('Clicked outside modal, closing');
        event.target.style.display = 'none';
    }
});
</script>

</body>
</html>

<?php 
$conn->close();
?>