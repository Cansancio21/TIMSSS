<?php
session_start();
include 'db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Handle AJAX request for asset name
if (isset($_GET['id']) && !isset($_GET['page']) && !isset($_GET['deleted']) && !isset($_GET['updated']) && !isset($_GET['action'])) {
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

// Handle AJAX search request
if (isset($_GET['action']) && $_GET['action'] === 'search' && isset($_GET['search'])) {
    $searchTerm = trim($_GET['search']);
    $page = isset($_GET['search_page']) ? (int)$_GET['search_page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    $output = '';

    if ($searchTerm === '') {
        // Fetch default borrowed assets for the current page
        $countSql = "SELECT COUNT(*) as total FROM tbl_borrowed";
        $countResult = $conn->query($countSql);
        $totalRecords = $countResult->fetch_assoc()['total'];
        $totalPages = ceil($totalRecords / $limit);

        $sql = "SELECT b_id, b_assets_name, b_quantity, b_technician_name, b_technician_id, b_date 
                FROM tbl_borrowed 
                LIMIT ?, ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $offset, $limit);
    } else {
        // Count total matching records for pagination
        $countSql = "SELECT COUNT(*) as total FROM tbl_borrowed 
                     WHERE b_assets_name LIKE ? OR b_technician_name LIKE ? OR b_technician_id LIKE ? OR b_date LIKE ?";
        $countStmt = $conn->prepare($countSql);
        $searchWildcard = "%$searchTerm%";
        $countStmt->bind_param("ssss", $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $totalRecords = $countResult->fetch_assoc()['total'];
        $countStmt->close();

        $totalPages = ceil($totalRecords / $limit);

        // Fetch paginated search results
        $sql = "SELECT b_id, b_assets_name, b_quantity, b_technician_name, b_technician_id, b_date 
                FROM tbl_borrowed 
                WHERE b_assets_name LIKE ? OR b_technician_name LIKE ? OR b_technician_id LIKE ? OR b_date LIKE ?
                LIMIT ?, ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssii", $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard, $offset, $limit);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output .= "<tr> 
                          <td>{$row['b_id']}</td> 
                          <td>" . (isset($row['b_assets_name']) ? htmlspecialchars($row['b_assets_name'], ENT_QUOTES, 'UTF-8') : 'N/A') . "</td>  
                          <td>{$row['b_quantity']}</td>
                          <td>{$row['b_technician_name']}</td>
                          <td>{$row['b_technician_id']}</td>    
                          <td>{$row['b_date']}</td> 
                          <td class='action-buttons'>
                              <a class='view-btn' onclick=\"showViewModal('{$row['b_id']}', '" . htmlspecialchars($row['b_assets_name'], ENT_QUOTES, 'UTF-8') . "', '{$row['b_quantity']}', '{$row['b_technician_name']}', '{$row['b_technician_id']}', '{$row['b_date']}')\" title='View'><i class='fas fa-eye'></i></a>
                              <a href='editB.php?id={$row['b_id']}' class='edit-btn' title='Edit'><i class='fas fa-edit'></i></a>
                              <a class='delete-btn' onclick='showDeleteModal({$row['b_id']})' title='Delete'><i class='fas fa-trash'></i></a>
                          </td>
                        </tr>";
        }
    } else {
        $output = "<tr><td colspan='7'>No borrowed assets found.</td></tr>";
    }
    $stmt->close();

    // Add pagination data
    $output .= "<script>
        updatePagination($page, $totalPages, '$searchTerm');
    </script>";

    echo $output;
    exit();
}

$username = $_SESSION['username'] ?? '';
$lastName = '';
$firstName = '';
$userType = '';
$avatarFolder = 'Uploads/avatars/';
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
            <li><a href="deployedT.php"><i class="fas fa-clipboard-check"></i>Deployed Records</a></li>
        </ul>
        <footer>
            <a href="index.php" class="back-home"><i class="fas fa-home"></i> Back to Home</a>
        </footer>
    </div>

    <div class="container">
        <div class="upper"> 
            <h1>Borrowed Assets</h1>
            <div class="search-container">
                <input type="text" class="search-bar" id="searchInput" placeholder="Search borrowed assets..." onkeyup="debouncedSearchBorrowed()">
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
                    Welcome, <?php echo htmlspecialchars($firstName); ?>!
                    <i class="fas fa-user-shield admin-icon"></i>
                </div>
            <?php endif; ?>

            <div class="borrowed">
                <div class="button-container">
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
                                        <td class='action-buttons'>
                                            <a class='view-btn' onclick=\"showViewModal('{$row['b_id']}', '" . htmlspecialchars($row['b_assets_name'], ENT_QUOTES, 'UTF-8') . "', '{$row['b_quantity']}', '{$row['b_technician_name']}', '{$row['b_technician_id']}', '{$row['b_date']}')\" title='View'><i class='fas fa-eye'></i></a>
                                            <a href='editB.php?id={$row['b_id']}' class='edit-btn' title='Edit'><i class='fas fa-edit'></i></a>
                                            <a class='delete-btn' onclick='showDeleteModal({$row['b_id']})' title='Delete'><i class='fas fa-trash'></i></a>
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
<div id="viewModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('viewModal')">×</span>
        <h2>View Borrowed Asset</h2>
        <div id="viewModalContent" style="margin-top: 20px;">
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
        <div class="modal-footer">
            <button type="button" class="modal-btn cancel" onclick="closeModal('deleteModal')">Cancel</button>
            <button type="button" class="modal-btn confirm" onclick="confirmDelete()">Delete</button>
        </div>
    </div>
</div>

<script>
let currentDeleteId = null;
let currentSearchPage = 1;
let defaultPage = <?php echo json_encode($page); ?>;
let updateInterval = null;

// Debounce function to limit search calls
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

function searchBorrowed(page = 1) {
    const searchTerm = document.getElementById('searchInput').value;
    const tbody = document.getElementById('tableBody');
    const paginationContainer = document.getElementById('borrowed-pagination');

    currentSearchPage = page;

    // Create XMLHttpRequest for AJAX
    const xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            tbody.innerHTML = xhr.responseText.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
        }
    };
    xhr.open('GET', `borrowedT.php?action=search&search=${encodeURIComponent(searchTerm)}&search_page=${searchTerm ? page : defaultPage}`, true);
    xhr.send();
}

function updatePagination(currentPage, totalPages, searchTerm) {
    const paginationContainer = document.getElementById('borrowed-pagination');
    let paginationHtml = '';

    if (currentPage > 1) {
        paginationHtml += `<a href="javascript:searchBorrowed(${currentPage - 1})" class="pagination-link"><i class="fas fa-chevron-left"></i></a>`;
    } else {
        paginationHtml += `<span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>`;
    }

    paginationHtml += `<span class="current-page">Page ${currentPage} of ${totalPages}</span>`;

    if (currentPage < totalPages) {
        paginationHtml += `<a href="javascript:searchBorrowed(${currentPage + 1})" class="pagination-link"><i class="fas fa-chevron-right"></i></a>`;
    } else {
        paginationHtml += `<span class="pagination-link disabled"><i class="fas fa-chevron-right"></i></span>`;
    }

    paginationContainer.innerHTML = paginationHtml;
}

// Debounced search function
const debouncedSearchBorrowed = debounce(searchBorrowed, 300);

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
    console.log('showDeleteModal called with id:', id);
    currentDeleteId = id;
    const modal = document.getElementById('deleteModal');
    console.log('Modal element:', modal);
    fetch(`borrowedT.php?id=${id}`)
        .then(response => {
            console.log('Fetch response status:', response.status);
            console.log('Fetch response headers:', response.headers.get('content-type'));
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Fetch response data:', data);
            if (data.assetName) {
                document.getElementById('deleteAssetName').textContent = data.assetName;
            } else {
                console.error('Asset name not found');
                document.getElementById('deleteAssetName').textContent = 'Unknown Asset';
            }
            modal.style.display = 'flex';
            console.log('Modal display set to flex');
        })
        .catch(error => {
            console.error('Error fetching asset name:', error);
            document.getElementById('deleteAssetName').textContent = 'Unknown Asset';
            modal.style.display = 'flex';
            console.log('Modal display set to flex (error case)');
        });
}

function confirmDelete() {
    if (currentDeleteId) {
        console.log('confirmDelete called with id:', currentDeleteId);
        fetch(`deleteB.php?id=${currentDeleteId}`, {
            method: 'GET'
        })
        .then(response => {
            console.log('Delete response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.text();
        })
        .then(data => {
            updateTable();
            closeModal('deleteModal');
            window.location.href = 'borrowedT.php?deleted=true';
        })
        .catch(error => console.error('Error deleting record:', error));
    }
}

function updateTable() {
    const searchTerm = document.getElementById('searchInput').value;
    if (searchTerm) {
        searchBorrowed(currentSearchPage);
    } else {
        fetch(`borrowedT.php?page=${defaultPage}`)
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
}

function closeModal(modalId) {
    console.log('closeModal called for:', modalId);
    document.getElementById(modalId).style.display = 'none';
}

window.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal')) {
        console.log('Clicked outside modal, closing');
        event.target.style.display = 'none';
    }
});

// Initialize auto-update table every 30 seconds
document.addEventListener('DOMContentLoaded', () => {
    updateInterval = setInterval(updateTable, 30000);

    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.classList.add('alert-hidden');
            setTimeout(() => alert.remove(), 500);
        }, 2000);
    });

    // Initialize search on page load if there's a search term
    const searchInput = document.getElementById('searchInput');
    if (searchInput.value) {
        searchBorrowed();
    }
});

// Clear interval when leaving the page
window.addEventListener('beforeunload', () => {
    if (updateInterval) {
        clearInterval(updateInterval);
    }
});
</script>

</body>
</html>

<?php 
$conn->close();
?>