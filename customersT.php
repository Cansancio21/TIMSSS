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
$avatarFolder = 'uploads/avatars/';
$userAvatar = $avatarFolder . $_SESSION['username'] . '.png';

if (file_exists($userAvatar)) {
    $_SESSION['avatarPath'] = $userAvatar . '?' . time();
} else {
    $_SESSION['avatarPath'] = 'default-avatar.png';
}

$avatarPath = $_SESSION['avatarPath'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

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
    } elseif (isset($_POST['delete_customer'])) {
        $id = $_POST['c_id'];

        // First archive the customer
        $sql = "INSERT INTO tbl_customer_archive 
                SELECT * FROM tbl_customer WHERE c_id=?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // Then delete from main table
        $sql = "DELETE FROM tbl_customer WHERE c_id=?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Customer archived successfully!";
        } else {
            $_SESSION['error'] = "Error archiving customer: " . $stmt->error;
        }
        $stmt->close();
    }

    header("Location: customersT.php?page=$page");
    exit();
}

if ($conn) {
    // Fetch user data based on the logged-in username
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

    // Pagination setup
    $limit = 10; // Number of customers per page
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Get current page
    $offset = ($page - 1) * $limit; // Offset calculation

    // Fetch total number of customers
    $totalCustomersQuery = "SELECT COUNT(*) AS total FROM tbl_customer";
    $totalResult = $conn->query($totalCustomersQuery);
    $totalRow = $totalResult->fetch_assoc();
    $totalCustomers = $totalRow['total'];
    $totalPages = ceil($totalCustomers / $limit); // Total pages

    // Fetch paginated customer data
    $sql = "SELECT c_id, c_fname, c_lname, c_area, c_contact, c_email, c_date, c_onu, c_caller, c_address, c_rem 
            FROM tbl_customer LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $offset, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
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
    <title>Registered Customer </title>
    <link rel="stylesheet" href="customerT.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
            <div class="customer-actions">
            <form action="addC.php" method="get" style="display: inline;">
    <button type="submit" class="add-user-btn"><i class="fas fa-user-plus"></i> Add Customer</button>
</form>
                <button class="action-btn export-btn"><i class="fas fa-download"></i> Export</button>
            </div>
            
            <table id="customers-table">
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
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
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
                                    <td>{$row['c_rem']}</td> 
                                    <td class='action-buttons'>
                                        <a class='view-btn' onclick=\"showViewModal('{$row['c_id']}', '{$row['c_fname']}', '{$row['c_lname']}', '{$row['c_area']}', '{$row['c_contact']}', '{$row['c_email']}', '{$row['c_date']}', '{$row['c_onu']}', '{$row['c_caller']}', '{$row['c_address']}', '{$row['c_rem']}')\" title='View'><i class='fas fa-eye'></i></a>
                                        <a class='edit-btn' href='editC.php?id=" . htmlspecialchars($row['c_id'], ENT_QUOTES, 'UTF-8') . "' title='Edit'><i class='fas fa-edit'></i></a>
                                        <a class='delete-btn' onclick=\"showDeleteModal('{$row['c_id']}', '{$row['c_fname']} {$row['c_lname']}')\" title='Delete'><i class='fas fa-trash'></i></a>
                                    </td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='12' style='text-align: center;'>No customers found.</td></tr>";
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



<!-- Delete Customer Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Archive Customer</h2>
        </div>
        <p>Are you sure you want to archive <span id="deleteCustomerName"></span>?</p>
        <form method="POST" id="deleteForm">
            <input type="hidden" name="c_id" id="deleteCustomerId">
            <input type="hidden" name="delete_customer" value="1">
            <div class="modal-footer">
                <button type="button" class="modal-btn cancel" onclick="closeModal('deleteModal')">Cancel</button>
                <button type="submit" class="modal-btn delete">Archive</button>
            </div>
        </form>
    </div>
</div>

<script>
    function searchCustomers() {
        const input = document.getElementById('searchInput').value.toLowerCase();
        const table = document.getElementById('customers-table');
        const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
        
        for (let i = 0; i < rows.length; i++) {
            const cells = rows[i].getElementsByTagName('td');
            let match = false;
            for (let j = 0; j < cells.length - 1; j++) { // Skip the last cell (actions)
                if (cells[j].textContent.toLowerCase().includes(input)) {
                    match = true;
                    break;
                }
            }
            rows[i].style.display = match ? '' : 'none';
        }
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
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

    function showEditModal(id, fname, lname, area, contact, email, onu, caller, address, rem) {
        document.getElementById('editCustomerId').value = id;
        document.getElementById('editFname').value = fname;
        document.getElementById('editLname').value = lname;
        document.getElementById('editArea').value = area;
        document.getElementById('editContact').value = contact;
        document.getElementById('editEmail').value = email || '';
        document.getElementById('editOnu').value = onu || '';
        document.getElementById('editCaller').value = caller || '';
        document.getElementById('editAddress').value = address || '';
        document.getElementById('editRem').value = rem || '';
        
        document.getElementById('editModal').style.display = 'block';
    }

    function showDeleteModal(id, name) {
        document.getElementById('deleteCustomerId').value = id;
        document.getElementById('deleteCustomerName').innerText = name;
        document.getElementById('deleteModal').style.display = 'block';
    }

    // Close modal when clicking outside of it
    window.onclick = function(event) {
        if (event.target.className === 'modal') {
            event.target.style.display = 'none';
        }
    }
</script>
</body>
</html>