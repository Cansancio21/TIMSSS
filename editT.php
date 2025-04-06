<?php 
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) { 
    header("Location: index.php"); // Redirect to login page if not logged in 
    exit(); 
}

// Check if the ticket ID is provided
if (isset($_GET['id'])) {
    $ticketId = $_GET['id'];

    // Fetch ticket details based on the ticket ID
    $sql = "SELECT t_id, t_aname, t_type, t_status, t_details, t_date FROM tbl_ticket WHERE t_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $ticketId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $ticket = $result->fetch_assoc();
    } else {
        echo "Ticket not found.";
        exit();
    }
} else {
    echo "No ticket ID provided.";
    exit();
}

// Handle form submission for updating the ticket
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $accountName = $_POST['account_name'];
    $issueType = $_POST['issue_type'];
    $ticketStatus = $_POST['ticket_status'];
    $ticketDetails = $_POST['ticket_details'];
    $dateIssued = $_POST['date'];

    // Update the ticket in the database
    $sqlUpdate = "UPDATE tbl_ticket SET t_aname = ?, t_type = ?, t_status = ?, t_details = ?, t_date = ? WHERE t_id = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param("sssssi", $accountName, $issueType, $ticketStatus, $ticketDetails, $dateIssued, $ticketId);
    
    if ($stmtUpdate->execute()) {
        echo "<script>alert('Ticket updated successfully!'); window.location.href='staffD.php';</script>";
    } else {
        echo "<script>alert('Error updating ticket.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Ticket</title>
    <link rel="stylesheet" href="create.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <a href="staffD.php" class="back-icon">
                <i class='bx bx-arrow-back'></i>
            </a>
            <h1>Edit Ticket</h1>
            <form method="POST" action="" class="form">
                <div class="form-row">
                    <label for="account_name">Account Name:</label>
                    <input type="text" id="account_name" name="account_name" value="<?php echo htmlspecialchars($ticket['t_aname']); ?>" required>
                </div>
                <div class="form-row">
                    <label for="issue_type">Issue Type:</label>
                    <select id="issue_type" name="issue_type" required>
                        <option value="Critical" <?php echo ($ticket['t_type'] == 'Critical') ? 'selected' : ''; ?>>Critical</option>
                        <option value="Minor" <?php echo ($ticket['t_type'] == 'Minor') ? 'selected' : ''; ?>>Minor</option>
                    </select>
                </div>
                <div class="form-row">
                    <label for="ticket_status">Ticket Status:</label>
                    <select id="ticket_status" name="ticket_status" required>
                        <option value="Open" <?php echo ($ticket['t_status'] == 'Open') ? 'selected' : ''; ?>>Open</option>
                        <option value="Closed" <?php echo ($ticket['t_status'] == 'Closed') ? 'selected' : ''; ?>>Closed</option>
                    </select>
                </div>
                <div class="form-row">
                    <label for="ticket_details">Ticket Details:</label>
                    <textarea name="ticket_details" id="ticket_details" required><?php echo htmlspecialchars($ticket['t_details']); ?></textarea>
                </div>
                <div class="form-row">
                    <label for="date">Date Issued:</label>
                    <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($ticket['t_date']); ?>" required>
                </div>
            
                <button type="submit">Update Ticket</button>
            </form>
        </div>
    </div>
</body>
</html>