<?php
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$firstName = '';
$userType = '';

if ($conn) {
    // Fetch user data based on the logged-in username
    $sqlUser = "SELECT u_fname, u_type FROM tbl_user WHERE u_username = ?";
    $stmt = $conn->prepare($sqlUser);
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $resultUser = $stmt->get_result();

    if ($resultUser->num_rows > 0) {
        $row = $resultUser->fetch_assoc();
        $firstName = $row['u_fname'];
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
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="customerT.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .table-box {
            display: flex;
            flex-direction: column;
        }

        /* Keep the table content to the top, and push the pagination to the bottom */
        .table-box table {
            flex-grow: 1; /* This will make the table take up the available space */
            margin-bottom: 10px; /* Ensure space for pagination */
        }

        /* Pagination styling */
        .pagination {
            text-align: center; /* Center the pagination links */
            padding: 10px 0;
            width: 100%; /* Full width */
            margin-left: 45%;
        }

        .pagination-link {
            display: inline-block;
            margin: 0 5px;
            padding: 8px 12px;
            background-color: #007bff; /* Bootstrap primary color */
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .pagination-link:hover {
            background-color: #0056b3; /* Darker shade on hover */
        }

        .pagination-link.active {
            background-color: #0056b3; /* Active page color */
            font-weight: bold; /* Bold text for active page */
        }

        .disabled {
            background-color: #ccc; /* Gray background for disabled */
            pointer-events: none; /* Disable click events */
            color: #666; /* Gray text */
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="sidebar">
        <h2>Task Management</h2>
        <ul>
            <li><a href="staffD.php"><i class="fas fa-ticket-alt"></i> View Tickets</a></li>
            <li><a href="assetsT.php"><i class="fas fa-box"></i> View Assets</a></li>
            <li><a href="createTickets.php"><i class="fas fa-file-invoice"></i> Ticket Registration</a></li>
            <li><a href="addC.php"><i class="fas fa-user-plus"></i> Add Customer</a></li>
            <li><a href="assetsT.php"><i class="fas fa-user-plus"></i> Register Assets</a></li>
        </ul>
        <footer>
            <a href="index.php" class="back-home"><i class="fas fa-home"></i> Back to Home</a>
        </footer>
    </div>

    <div class="container">
        <div class="upper">
            <h1>Customers Info</h1>
        </div>
        <div class="search-container">
            <input type="text" class="search-bar" placeholder="Search...">
            <span class="search-icon">🔍</span>
        </div>

        <div class="table-box">
            <?php if ($userType === 'staff'): ?>
                <div class="username">
                    Welcome Back, <?php echo htmlspecialchars($firstName); ?>!
                    <i class="fas fa-user-shield admin-icon"></i>
                </div>
            <?php endif; ?>
            <h2>Reports</h2>
            <a href="addC.php" class="add-btn"><i class="fas fa-user-plus"></i> Add Customer</a>
            <a href="export.php" class="export-btn"><i class="fas fa-download"></i> Export</a>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
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
                                    <td>
                                        <a href='editC.php?id={$row['c_id']}'><i class='fas fa-edit'></i></a>
                                        <a href='deleteC.php?id={$row['c_id']}'><i class='fas fa-trash'></i></a>
                                    </td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='12'>No customers found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>

            <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>" class="pagination-link">&lt;</a> <!-- Previous Page -->
            <?php else: ?>
                <span class="pagination-link disabled">&lt;</span> <!-- Disabled Previous -->
            <?php endif; ?>

            <span class="current-page">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>

            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>" class="pagination-link">&gt;</a> <!-- Next Page -->
            <?php else: ?>
                <span class="pagination-link disabled">&gt;</span> <!-- Disabled Next -->
            <?php endif; ?>
        </div>
        </div>
    </div>
</div>
</body>
</html>

<?php
$conn->close();
?>
