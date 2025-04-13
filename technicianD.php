<?php
session_start();
include 'db.php'; // Include your database connection file

// Get username from session
$username = $_SESSION['username'] ?? 'tech_user';

// Initialize variables
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

// Pagination settings
$limit = 10; // 10 tickets per page
$regularPage = isset($_GET['regularPage']) ? (int)$_GET['regularPage'] : 1;
$supportPage = isset($_GET['supportPage']) ? (int)$_GET['supportPage'] : 1;
$regularOffset = ($regularPage - 1) * $limit;
$supportOffset = ($supportPage - 1) * $limit;

if ($conn) {
    // Fetch firstName and userType from the database
    $sqlUser = "SELECT u_fname, u_type FROM tbl_user WHERE u_username = ?";
    $stmt = $conn->prepare($sqlUser);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $resultUser = $stmt->get_result();
    
    if ($resultUser->num_rows > 0) {
        $row = $resultUser->fetch_assoc();
        $firstName = $row['u_fname'] ?: '';
        $userType = $row['u_type'] ?: '';
    }
    $stmt->close();

    // Regular Ticket counts
    $sqlOpenTickets = "SELECT COUNT(*) AS openTickets FROM tbl_ticket WHERE t_status = 'open'";
    $resultOpenTickets = $conn->query($sqlOpenTickets);
    $openTickets = $resultOpenTickets->fetch_assoc()['openTickets'];

    $sqlClosedTickets = "SELECT COUNT(*) AS closedTickets FROM tbl_ticket WHERE t_status = 'closed'";
    $resultClosedTickets = $conn->query($sqlClosedTickets);
    $closedTickets = $resultClosedTickets->fetch_assoc()['closedTickets'];

    // Support Tickets status breakdown
    $sqlSupportOpen = "SELECT COUNT(*) AS supportOpen FROM tbl_supp_tickets WHERE s_status = 'Open'";
    $resultSupportOpen = $conn->query($sqlSupportOpen);
    $supportOpen = $resultSupportOpen->fetch_assoc()['supportOpen'];

    $sqlSupportClosed = "SELECT COUNT(*) AS supportClosed FROM tbl_supp_tickets WHERE s_status = 'Closed'";
    $resultSupportClosed = $conn->query($sqlSupportClosed);
    $supportClosed = $resultSupportClosed->fetch_assoc()['supportClosed'];

    // Pending tasks (open regular tickets + open support tickets)
    $pendingTasks = $openTickets + $supportOpen;

    // Pagination for Regular Tickets
    $sqlTotalRegular = "SELECT COUNT(*) AS total FROM tbl_ticket";
    $resultTotalRegular = $conn->query($sqlTotalRegular);
    $totalRegular = $resultTotalRegular->fetch_assoc()['total'];
    $totalRegularPages = ceil($totalRegular / $limit);

    // Updated query to include t_type and t_date
    $sqlTickets = "SELECT t_id, t_aname, t_type, t_details, t_status, t_date FROM tbl_ticket ORDER BY t_date ASC LIMIT ? OFFSET ?";
    $stmtTickets = $conn->prepare($sqlTickets);
    $stmtTickets->bind_param("ii", $limit, $regularOffset);
    $stmtTickets->execute();
    $resultTickets = $stmtTickets->get_result();
    $stmtTickets->close();

    // Pagination for Support Tickets
    $sqlTotalSupport = "SELECT COUNT(*) AS total FROM tbl_supp_tickets";
    $resultTotalSupport = $conn->query($sqlTotalSupport);
    $totalSupport = $resultTotalSupport->fetch_assoc()['total'];
    $totalSupportPages = ceil($totalSupport / $limit);

    // Updated query to include s_type
    $sqlSuppTickets = "SELECT id AS t_id, CONCAT(c_fname, ' ', c_lname) AS t_aname, s_type, s_message AS t_details, s_status AS t_status 
                      FROM tbl_supp_tickets 
                      ORDER BY id ASC LIMIT ? OFFSET ?";
    $stmtSuppTickets = $conn->prepare($sqlSuppTickets);
    $stmtSuppTickets->bind_param("ii", $limit, $supportOffset);
    $stmtSuppTickets->execute();
    $resultSuppTickets = $stmtSuppTickets->get_result();
    $stmtSuppTickets->close();
} else {
    echo "Database connection failed.";
    $firstName = '';
    $userType = '';
    $openTickets = $closedTickets = $pendingTasks = $supportOpen = $supportClosed = 0;
    $totalRegularPages = $totalSupportPages = 1; // Fallback for pagination
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISP Technician Dashboard</title>
    <link rel="stylesheet" href="technicianD.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<div class="wrapper">
    <div class="sidebar glass-container">
        <h2>Task Management</h2>
        <ul>
            <li><a href="technicianD.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="staffD.php"><i class="fas fa-users"></i> Regular Tickets</a></li>
            <li><a href="suppT.php"><i class="fas fa-file-archive"></i> Support Tickets</a></li>
        </ul>
        <footer>
            <a href="index.php" class="back-home"><i class="fas fa-home"></i> <span>Back to Home</span></a>
        </footer>
    </div>
    <div class="container">
        <div class="upper"> 
            <h1>Technician Dashboard</h1>
            <div class="search-container">
                <input type="text" class="search-bar" id="searchInput" placeholder="Search users..." onkeyup="searchTickets()">
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

        <!-- Dashboard Cards Section -->
        <div class="dashboard-cards">
            <div class="card">
                <i class="fas fa-tasks text-yellow-500"></i>
                <div class="card-content">
                    <h3>Pending Tasks</h3>
                    <p><strong><?php echo $pendingTasks; ?></strong></p>
                    <p>Regular Open: <?php echo $openTickets; ?> | Support Open: <?php echo $supportOpen; ?></p>
                </div>
            </div>
            <div class="card">
                <i class="fas fa-ticket-alt text-orange-500"></i>
                <div class="card-content">
                    <h3>Regular Tickets</h3>
                    <p>Open: <?php echo $openTickets; ?></p>
                    <p>Closed: <?php echo $closedTickets; ?></p>
                </div>
            </div>
            <div class="card">
                <i class="fas fa-headset text-blue-500"></i>
                <div class="card-content">
                    <h3>Support Tickets</h3>
                    <p>Open: <?php echo $supportOpen; ?></p>
                    <p>Closed: <?php echo $supportClosed; ?></p>
                </div>
            </div>
        </div>

        <div class="tab-container">
            <!-- Regular Tickets Tab -->
            <div id="regularTickets" class="tab-content active">
                <div class="table-box">
                    <div class="tab-buttons">
                        <button class="tab-button active" onclick="openTab('regularTickets')">Regular Tickets</button>
                        <button class="tab-button" onclick="openTab('supportTickets')">Support Tickets</button>
                    </div>
                    <table class="tickets-table">
                        <thead>
                            <tr>
                                <th>Ticket ID</th>
                                <th>Customer Name</th>
                                <th>Type</th>
                                <th>Message</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody id="regularTicketTableBody">
                            <?php 
                            if ($resultTickets->num_rows > 0) {
                                while ($row = $resultTickets->fetch_assoc()) {
                                    echo "<tr>
                                            <td>{$row['t_id']}</td>
                                            <td>" . htmlspecialchars($row['t_aname']) . "</td>
                                            <td>" . htmlspecialchars(ucfirst(strtolower($row['t_type']))) . "</td>
                                            <td>" . htmlspecialchars($row['t_details']) . "</td>
                                            <td class='status-" . strtolower(str_replace(' ', '-', $row['t_status'])) . "'>" . ucfirst(strtolower($row['t_status'])) . "</td>
                                            <td>" . htmlspecialchars($row['t_date']) . "</td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' style='text-align: center;'>No regular ticket messages found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                    <div class="pagination">
                        <?php if ($regularPage > 1): ?>
                            <a href="?regularPage=<?php echo $regularPage - 1; ?>&supportPage=<?php echo $supportPage; ?>" class="pagination-link"><i class="fas fa-chevron-left"></i></a>
                        <?php else: ?>
                            <span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>
                        <?php endif; ?>
                        <span class="current-page">Page <?php echo $regularPage; ?> of <?php echo $totalRegularPages; ?></span>
                        <?php if ($regularPage < $totalRegularPages): ?>
                            <a href="?regularPage=<?php echo $regularPage + 1; ?>&supportPage=<?php echo $supportPage; ?>" class="pagination-link"><i class="fas fa-chevron-right"></i></a>
                        <?php else: ?>
                            <span class="pagination-link disabled"><i class="fas fa-chevron-right"></i></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Support Tickets Tab -->
            <div id="supportTickets" class="tab-content">
                <div class="table-box">
                    <div class="tab-buttons">
                        <button class="tab-button" onclick="openTab('regularTickets')">Regular Tickets</button>
                        <button class="tab-button active" onclick="openTab('supportTickets')">Support Tickets</button>
                    </div>
                    <table class="tickets-table">
                        <thead>
                            <tr>
                                <th>Ticket ID</th>
                                <th>Customer Name</th>
                                <th>Type</th>
                                <th>Message</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="supportTicketTableBody">
                            <?php 
                            if ($resultSuppTickets->num_rows > 0) {
                                while ($row = $resultSuppTickets->fetch_assoc()) {
                                    echo "<tr>
                                            <td>{$row['t_id']}</td>
                                            <td>" . htmlspecialchars($row['t_aname']) . "</td>
                                            <td>" . htmlspecialchars(ucfirst(strtolower($row['s_type']))) . "</td>
                                            <td>" . htmlspecialchars($row['t_details']) . "</td>
                                            <td class='status-" . strtolower(str_replace(' ', '-', $row['t_status'])) . "'>" . ucfirst(strtolower($row['t_status'])) . "</td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' style='text-align: center;'>No support ticket messages found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                    <div class="pagination">
                        <?php if ($supportPage > 1): ?>
                            <a href="?regularPage=<?php echo $regularPage; ?>&supportPage=<?php echo $supportPage - 1; ?>" class="pagination-link"><i class="fas fa-chevron-left"></i></a>
                        <?php else: ?>
                            <span class="pagination-link disabled"><i class="fas fa-chevron-left"></i></span>
                        <?php endif; ?>
                        <span class="current-page">Page <?php echo $supportPage; ?> of <?php echo $totalSupportPages; ?></span>
                        <?php if ($supportPage < $totalSupportPages): ?>
                            <a href="?regularPage=<?php echo $regularPage; ?>&supportPage=<?php echo $supportPage + 1; ?>" class="pagination-link"><i class="fas fa-chevron-right"></i></a>
                        <?php else: ?>
                            <span class="pagination-link disabled"><i class="fas fa-chevron-right"></i></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function searchTickets() {
    const input = document.getElementById('searchInput').value.toLowerCase();
    const activeTab = document.querySelector('.tab-content.active');
    const tableBody = activeTab.querySelector('tbody');
    const rows = tableBody.getElementsByTagName('tr');

    for (let i = 0; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName('td');
        let match = false;
        for (let j = 0; j < cells.length; j++) {
            if (cells[j].textContent.toLowerCase().includes(input)) {
                match = true;
                break;
            }
        }
        rows[i].style.display = match ? '' : 'none';
    }
}

function openTab(tabName) {
    const tabContents = document.getElementsByClassName('tab-content');
    for (let i = 0; i < tabContents.length; i++) {
        tabContents[i].classList.remove('active');
    }
    const tabButtons = document.getElementsByClassName('tab-button');
    for (let i = 0; i < tabButtons.length; i++) {
        tabButtons[i].classList.remove('active');
    }
    document.getElementById(tabName).classList.add('active');
    const activeButtons = document.querySelectorAll(`[onclick="openTab('${tabName}')"]`);
    activeButtons.forEach(button => button.classList.add('active'));
}
</script>
</body>
</html>

<?php
$conn->close(); // Close the database connection
?>