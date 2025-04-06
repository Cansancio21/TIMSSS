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

// Fetch tickets without logging
$query = "SELECT id, c_id, c_lname, c_fname, s_subject, s_type, s_message, s_status FROM tbl_supp_tickets WHERE c_id = '$c_id' ORDER BY id ASC";
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
    <link rel="stylesheet" href="suppT.css"> 
</head>
<body>
    <div class="wrapper">
    <div class="sidebar">
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
        </div>  
            
    <div class="button-container">
        <button type="button" onclick="openModal()">Create Ticket</button>
    </div>
            <div class="table-box">
                <h2>Customer Support Requests</h2>
                <hr>
                <table border="1">
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
            </div>

            <!-- Modal for Creating Ticket -->
            <div id="modalBackground" class="modal-background"></div>
            <div id="createTicketModal" class="modal-content" style="display:none;">
                <span onclick="closeModal()" class="close">&times;</span>
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
                <span onclick="closeCloseModal()" class="close">&times;</span>
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
                        // Log ticket closure
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
    </script>
</body>
</html>

<?php
mysqli_close($conn);
?>