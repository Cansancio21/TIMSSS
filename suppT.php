<?php
include 'db.php';

session_start();
if (!isset($_SESSION['user'])) {
    header("Location: customerP.php");
    exit();
}

$user = $_SESSION['user'];
$c_id = htmlspecialchars($user['c_id']);
$c_lname = htmlspecialchars($user['c_lname']);
$c_fname = htmlspecialchars($user['c_fname']);
$username = "$c_fname $c_lname";

// Avatar handling using the defined $username
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

// Set userType with a fallback, assuming it might be in session data
$userType = isset($user['user_type']) ? htmlspecialchars($user['user_type']) : 'customer';

// Pagination setup
$limit = 10; // Number of tickets per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total number of tickets
$totalQuery = "SELECT COUNT(*) as total FROM tbl_supp_tickets WHERE c_id = ?";
$stmt = $conn->prepare($totalQuery);
$stmt->bind_param("s", $c_id);
$stmt->execute();
$totalResult = $stmt->get_result();
$totalRow = $totalResult->fetch_assoc();
$totalTickets = $totalRow['total'];
$totalPages = ceil($totalTickets / $limit);
$stmt->close();

// Query with pagination
$query = "SELECT id, c_id, c_lname, c_fname, s_subject, s_type, s_message, s_status 
          FROM tbl_supp_tickets 
          WHERE c_id = ? 
          ORDER BY id ASC 
          LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("sii", $c_id, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die("Query Failed: " . mysqli_error($conn));
}

// Handle form submissions (create or close ticket)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['close_ticket'])) {
        $ticketId = (int)$_POST['t_id'];
        $updateQuery = "UPDATE tbl_supp_tickets SET s_status = 'Closed' WHERE id = ? AND c_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("is", $ticketId, $c_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Ticket closed successfully!";
        } else {
            $_SESSION['error'] = "Error closing ticket: " . $stmt->error;
        }
        $stmt->close();
        header("Location: suppT.php?page=$page");
        exit();
    } elseif (isset($_POST['create_ticket'])) {
        $c_id = $_POST['c_id'];
        $c_lname = $_POST['c_lname'];
        $c_fname = $_POST['c_fname'];
        $s_subject = $_POST['subject'];
        $s_type = $_POST['type'];
        $s_message = $_POST['message'];
        $s_status = 'Open'; // Default status for new tickets

        $insertQuery = "INSERT INTO tbl_supp_tickets (c_id, c_lname, c_fname, s_subject, s_type, s_message, s_status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("sssssss", $c_id, $c_lname, $c_fname, $s_subject, $s_type, $s_message, $s_status);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Ticket created successfully!";
        } else {
            $_SESSION['error'] = "Error creating ticket: " . $stmt->error;
        }
        $stmt->close();
        header("Location: suppT.php?page=$page");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Tickets</title>
    <link rel="stylesheet" href="suppsT.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
   
</head>
<body>
    <div class="wrapper">
    <div class="sidebar glass-container">
            <h2>Task Management</h2>
            <ul>
                <li><a href="staffD.php"><i class="fas fa-ticket-alt"></i> View Tickets</a></li>
                <li><a href="view_service_record.php"><i class="fas fa-box"></i> View Assets</a></li>
                <li><a href="customersT.php"><i class="fas fa-box"></i> View Customers</a></li>
                <li><a href="createTickets.php"><i class="fas fa-file-invoice"></i> Ticket Registration</a></li>
                <li><a href="registerAssets.php"><i class="fas fa-user-plus"></i>Register Assets</a></li>
                <li><a href="logs.php"><i class="fas fa-history"></i> View Logs</a></li>
            </ul>
            <footer>
                <a href="index.php" class="back-home"><i class="fas fa-home"></i> Back to Home</a>
            </footer>
        </div>
        <div class="container">
        <div class="upper"> 
    <h1>Support Tickets</h1>
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
                    <span><?php echo htmlspecialchars($c_fname); ?></span>
                    <small><?php echo htmlspecialchars(ucfirst($userType)); ?></small>
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

      
            <div class="button-container">
                <button type="button" onclick="openModal()">Create Ticket</button>
            </div>
            <div class="table-box">
                <h2>Customer Support Requests</h2>
                <hr>
                <table>
                    <thead>
                        <tr>
                            <th>Report ID</th>
                            <th>Customer ID</th>
                            <th>Name</th>
                            <th>Subject</th>
                            <th>Type</th>
                            <th>Message</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                <td><?php echo htmlspecialchars($row['c_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['c_lname'] . ', ' . $row['c_fname']); ?></td>
                                <td><?php echo htmlspecialchars($row['s_subject']); ?></td>
                                <td><?php echo htmlspecialchars($row['s_type']); ?></td>
                                <td><?php echo htmlspecialchars($row['s_message']); ?></td>
                                <td class="status-clickable" onclick="openCloseModal('<?php echo $row['id']; ?>', '<?php echo htmlspecialchars($row['c_lname'] . ', ' . $row['c_fname']); ?>', '<?php echo htmlspecialchars($row['s_status']); ?>')">
                                    <?php echo htmlspecialchars($row['s_status']); ?>
                                </td>
                            </tr>
                        <?php } ?>
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

            <!-- Modal Background -->
            <div id="modalBackground" class="modal-background" style="display: none;"></div>

            <!-- Modal for Creating Ticket -->
            <div id="createTicketModal" class="modal-content" style="display:none;">
                <span onclick="closeModal()" class="close">×</span>
                <h2>Create New Ticket</h2>
                <form id="createTicketForm" method="POST">
                    <input type="hidden" name="c_id" value="<?php echo htmlspecialchars($c_id); ?>">
                    <input type="hidden" name="c_lname" value="<?php echo htmlspecialchars($c_lname); ?>">
                    <input type="hidden" name="c_fname" value="<?php echo htmlspecialchars($c_fname); ?>">
                    <input type="hidden" name="create_ticket" value="1">

                    <label for="subject">Subject:</label>
                    <input type="text" id="subject" name="subject" readonly>
                    <br>

                    <label for="type">Ticket Type:</label>
                    <select name="type" id="type" required>
                        <option value="Critical" selected>Critical</option>
                        <option value="Minor">Minor</option>
                    </select>
                    <br>

                    <label for="message">Message:</label>
                    <textarea id="message" name="message" required></textarea>
                    <br>
                    <button type="submit">Report Ticket</button>
                </form>
            </div>

            <!-- Close Ticket Modal -->
            <div id="closeModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Close Ticket</h2>
                    </div>
                    <p>Enter Report Id to close <span id="closeTicketName"></span>:</p>
                    <form method="POST" id="closeForm">
                        <input type="number" name="t_id" id="closeTicketIdInput" placeholder="Ticket ID" required>
                        <input type="hidden" name="close_ticket" value="1">
                        <div class="modal-footer">
                            <button type="button" class="modal-btn cancel" onclick="closeModal('closeModal')">Cancel</button>
                            <button type="submit" class="modal-btn confirm">Close Ticket</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab') || 'active';
            showTab(tab);

            // Handle alert messages disappearing after 2 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.classList.add('alert-hidden');
                    setTimeout(() => alert.remove(), 500); // Remove after fade-out
                }, 2000); // 2 seconds delay
            });

            // Ensure modals are closed on page load
            document.getElementById('closeModal').style.display = 'none';
            document.getElementById('createTicketModal').style.display = 'none';
            document.getElementById('modalBackground').style.display = 'none';
        });

        function showTab(tab) {
            // Placeholder: Add logic here if you implement tabs later
            console.log(`Showing tab: ${tab}`);
        }

        function openModal() {
            const date = new Date();
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            const uniqueNumber = Math.floor(100000 + Math.random() * 900000);
            const subject = `ref#-${day}-${month}-${year}-${uniqueNumber}`;

            document.getElementById('subject').value = subject;
            document.getElementById('createTicketModal').style.display = 'block';
            document.getElementById('modalBackground').style.display = 'block';
        }

        function closeModal(modalId) {
            if (modalId === 'closeModal') {
                document.getElementById('closeModal').style.display = 'none';
            } else {
                document.getElementById('createTicketModal').style.display = 'none';
            }
            document.getElementById('modalBackground').style.display = 'none';
        }

        function openCloseModal(ticketId, name, status) {
            if (status.toLowerCase() === 'closed') {
                alert("This ticket is already closed.");
                return;
            }
            document.getElementById('closeTicketIdInput').value = ticketId; // Pre-fill the ID
            document.getElementById('closeTicketName').textContent = name;
            document.getElementById('closeModal').style.display = 'block';
            document.getElementById('modalBackground').style.display = 'block';
        }

        function searchUsers() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            const table = document.querySelector('.table-box table');
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) {
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
    </script>
</body>
</html>

<?php
mysqli_close($conn);
?>
