<?php
include 'db.php'; // Include database connection
session_start();

// Check if admin is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header("Location: index.php");
    exit();
}

// Fetch user data
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

// Fetch user details from tbl_user
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
    $stmt->close();
}

// Set up pagination
$logs_per_page = 10; // Changed from 20 to 10
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $logs_per_page;

// Get total number of logs
$total_logs_query = "SELECT COUNT(*) as total FROM tbl_logs";
$total_logs_result = $conn->query($total_logs_query);
$total_logs = $total_logs_result->fetch_assoc()['total'];
$total_pages = ceil($total_logs / $logs_per_page);

// Fetch logs with pagination
$log_query = "SELECT * FROM tbl_logs ORDER BY l_stamp ASC LIMIT $offset, $logs_per_page";
$logResult = $conn->query($log_query);

if (!$logResult) {
    die("Error fetching logs: " . $conn->error . " Query: " . $log_query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs</title>
    <link rel="stylesheet" href="logs.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
            <h1>System Logs</h1>
            <div class="search-container">
                <input type="text" class="search-bar" id="searchInput" placeholder="Search logs..." onkeyup="searchUsers()">
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

        <div class="table-box">
            <table>
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Activity Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($logResult->num_rows > 0): ?>
                        <?php while ($logRow = $logResult->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($logRow['l_stamp']) ?></td>
                                <td><?= htmlspecialchars($logRow['l_description']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2" style="text-align: center;">No logs found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($current_page > 1): ?>
                        <a href="?page=<?= $current_page - 1 ?><?= isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : '' ?>">«</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?= $i ?><?= isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : '' ?>" <?= $i == $current_page ? 'class="active"' : '' ?>>
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($current_page < $total_pages): ?>
                        <a href="?page=<?= $current_page + 1 ?><?= isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : '' ?>">»</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$conn->close();
?>
</body>
</html>
