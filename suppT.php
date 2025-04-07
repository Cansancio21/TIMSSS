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
$totalQuery = "SELECT COUNT(*) as total FROM tbl_supp_tickets WHERE c_id = '$c_id'";
$totalResult = mysqli_query($conn, $totalQuery);
$totalRow = mysqli_fetch_assoc($totalResult);
$totalTickets = $totalRow['total'];
$totalPages = ceil($totalTickets / $limit);

// Query with pagination
$query = "SELECT id, c_id, c_lname, c_fname, s_subject, s_type, s_message, s_status 
          FROM tbl_supp_tickets 
          WHERE c_id = '$c_id' 
          ORDER BY id ASC 
          LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query Failed: " . mysqli_error($conn));
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
                        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                <td><?php echo htmlspecialchars($row['c_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['c_lname'] . ', ' . $row['c_fname']); ?></td>
                                <td><?php echo htmlspecialchars($row['s_subject']); ?></td>
                                <td><?php echo htmlspecialchars($row['s_type']); ?></td>
                                <td><?php echo htmlspecialchars($row['s_message']); ?></td>
                                <td onclick="openCloseModal('<?php echo $row['id']; ?>', '<?php echo htmlspecialchars($row['s_status']); ?>')">
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
            <div id="modalBackground" class="modal-background"></div>

            <!-- Modal for Creating Ticket -->
            <div id="createTicketModal" class="modal-content" style="display:none;">
                <span onclick="closeModal()" class="close">×</span>
                <h2>Create New Ticket</h2>
                <form id="createTicketForm" onsubmit="return createTicket(event)">
                    <input type="hidden" name="c_id" value="<?php echo htmlspecialchars($c_id); ?>">
                    <input type="hidden" name="c_lname" value="<?php echo htmlspecialchars($c_lname); ?>">
                    <input type="hidden" name="c_fname" value="<?php echo htmlspecialchars($c_fname); ?>">

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

            <!-- Modal for Closing Ticket -->
            <div id="closeTicketModal" class="modal-content" style="display:none;">
                <span onclick="closeCloseModal()" class="close">×</span>
                <input type="text" id="closeTicketId" placeholder="Enter Ticket ID to close the ticket">
                <div class="button-container">
                    <button class="close-ticket" onclick="confirmClose()">Close Ticket</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentTicketId = null;

        function openCloseModal(ticketId, status) {
            if (status === "Open") {
                currentTicketId = ticketId;
                document.getElementById('closeTicketModal').style.display = 'block';
                document.getElementById('modalBackground').style.display = 'block';
            } else {
                alert("This ticket is already closed.");
            }
        }

        function closeCloseModal() {
            document.getElementById('closeTicketModal').style.display = 'none';
            document.getElementById('modalBackground').style.display = 'none';
        }

        function confirmClose() {
            const confirmationId = document.getElementById('closeTicketId').value;
            if (confirmationId === currentTicketId) {
                const xhr = new XMLHttpRequest();
                xhr.open("POST", "updateS.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function () {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        const logXhr = new XMLHttpRequest();
                        logXhr.open("POST", "log_activity.php", true);
                        logXhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                        logXhr.send("action=close_ticket&ticket_id=" + currentTicketId + "&username=<?php echo $username; ?>");
                        
                        alert("Ticket closed successfully!");
                        location.reload();
                    }
                };
                xhr.send("id=" + currentTicketId + "&status=Closed");
            } else {
                alert("Incorrect ID entered. Ticket not closed.");
            }
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

        function closeModal() {
            document.getElementById('createTicketModal').style.display = 'none';
            document.getElementById('modalBackground').style.display = 'none';
        }

        function createTicket(event) {
            event.preventDefault();
            const formData = new FormData(document.getElementById('createTicketForm'));

            const xhr = new XMLHttpRequest();
            xhr.open("POST", "ticket.php", true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200 && xhr.responseText.trim() === "success") {
                        alert("✅ Ticket created successfully!");
                        closeModal();
                        setTimeout(() => { location.reload(); }, 500);
                    } else {
                        alert("❌ Error creating ticket: " + xhr.responseText);
                    }
                }
            };
            xhr.send(formData);
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