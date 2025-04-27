<?php
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Check if the ticket ID is provided
if (isset($_GET['id'])) {
    $ticketId = (int)$_GET['id'];

    // Fetch ticket details based on the ticket ID
    $sql = "SELECT t_id, t_aname, t_type, t_status, t_details, t_date FROM tbl_ticket WHERE t_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $ticketId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $ticket = $result->fetch_assoc();
    } else {
        echo "<script>alert('Ticket not found.'); window.location.href='staffD.php';</script>";
        exit();
    }
    $stmt->close();
} else {
    echo "<script>alert('No ticket ID provided.'); window.location.href='staffD.php';</script>";
    exit();
}

// Handle form submission for updating the ticket
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $accountName = trim($_POST['account_name']);
    $issueType = trim($_POST['issue_type']);
    $ticketStatus = trim($_POST['ticket_status']);
    $ticketDetails = trim($_POST['ticket_details']);
    $dateIssued = trim($_POST['date']);
    $errors = [];

    // Validation
    if (empty($accountName)) {
        $errors['account_name'] = "Account Name is required.";
    }
    if (empty($issueType)) {
        $errors['issue_type'] = "Issue Type is required.";
    }
    if (empty($ticketStatus)) {
        $errors['ticket_status'] = "Ticket Status is required.";
    }
    if (empty($ticketDetails)) {
        $errors['ticket_details'] = "Ticket Details are required.";
    }
    if (empty($dateIssued)) {
        $errors['date'] = "Date Issued is required.";
    }

    // Prevent status change for closed or open tickets
    if ($ticket['t_status'] === 'Closed' && ($ticketStatus === 'Open' || $ticketStatus === 'Closed')) {
        $errors['ticket_status'] = "Cannot change status of a closed ticket to 'Open' or 'Closed'.";
    }
    if ($ticket['t_status'] === 'Open' && ($ticketStatus === 'Open' || $ticketStatus === 'Closed')) {
        $errors['ticket_status'] = "Cannot change status of an open ticket to 'Open' or 'Closed'.";
    }

    if (empty($errors)) {
        // Update the ticket in the database
        $sqlUpdate = "UPDATE tbl_ticket SET t_aname = ?, t_type = ?, t_status = ?, t_details = ?, t_date = ? WHERE t_id = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bind_param("sssssi", $accountName, $issueType, $ticketStatus, $ticketDetails, $dateIssued, $ticketId);

        if ($stmtUpdate->execute()) {
            echo "<script>alert('Ticket updated successfully!'); window.location.href='staffD.php?tab=active';</script>";
        } else {
            echo "<script>alert('Error updating ticket: " . addslashes($stmtUpdate->error) . "');</script>";
        }
        $stmtUpdate->close();
    } else {
        // Store errors to display
        $errorMessage = implode("\\n", array_values($errors));
        echo "<script>alert('Errors:\\n" . addslashes($errorMessage) . "');</script>";
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
    <style>
        .input-box {
            position: relative;
            width: 100%;
        }
        .input-box i {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 20px;
        }
       
        .input-box input, .input-box select, .input-box textarea {
            width: 100%;
            padding-right: 50px; /* Space for icon */
            box-sizing: border-box;
        }
        .error {
            color: red;
            font-size: 0.9em;
            margin-top: 5px;
            display: block;
        }
        textarea {
            height: 100px;
            resize: vertical;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <a href="staffD.php?tab=active" class="back-icon">
                <i class='bx bx-arrow-back'></i>
            </a>
            <h1>Edit Ticket</h1>
            <form method="POST" action="" class="form">
                <div class="form-row">
                    <label for="account_name">Account Name:</label>
                    <div class="input-box">
                        <input type="text" id="account_name" name="account_name" value="<?php echo htmlspecialchars($ticket['t_aname']); ?>" required>
                        <i class='bx bxs-user account-icon'></i>
                    </div>
                    <?php if (isset($errors['account_name'])): ?>
                        <span class="error"><?php echo htmlspecialchars($errors['account_name']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="form-row">
                    <label for="issue_type">Issue Type:</label>
                    <div class="input-box">
                        <select id="issue_type" name="issue_type" required>
                            <option value="Critical" <?php echo ($ticket['t_type'] == 'Critical') ? 'selected' : ''; ?>>Critical</option>
                            <option value="Minor" <?php echo ($ticket['t_type'] == 'Minor') ? 'selected' : ''; ?>>Minor</option>
                        </select>
                        <i class='bx bxs-error issue-icon'></i>
                    </div>
                    <?php if (isset($errors['issue_type'])): ?>
                        <span class="error"><?php echo htmlspecialchars($errors['issue_type']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="form-row">
                    <label for="ticket_status">Ticket Status:</label>
                    <div class="input-box">
                        <select id="ticket_status" name="ticket_status" required <?php echo ($ticket['t_status'] === 'Closed' || $ticket['t_status'] === 'Open') ? 'disabled' : ''; ?>>
                            <?php if ($ticket['t_status'] === 'Closed'): ?>
                                <option value="Closed" selected>Closed</option>
                            <?php elseif ($ticket['t_status'] === 'Open'): ?>
                                <option value="Open" selected>Open</option>
                            <?php else: ?>
                                <option value="Open" <?php echo ($ticket['t_status'] == 'Open') ? 'selected' : ''; ?>>Open</option>
                                <option value="Closed" <?php echo ($ticket['t_status'] == 'Closed') ? 'selected' : ''; ?>>Closed</option>
                            <?php endif; ?>
                        </select>
                        <i class='bx bxs-check-circle status-icon'></i>
                    </div>
                    <?php if ($ticket['t_status'] === 'Closed'): ?>
                        <input type="hidden" name="ticket_status" value="Closed">
                        <span class="error">Closed tickets cannot have their status changed.</span>
                    <?php elseif ($ticket['t_status'] === 'Open'): ?>
                        <input type="hidden" name="ticket_status" value="Open">
                        <span class="error">Open tickets cannot have their status changed.</span>
                    <?php endif; ?>
                    <?php if (isset($errors['ticket_status'])): ?>
                        <span class="error"><?php echo htmlspecialchars($errors['ticket_status']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="form-row">
                    <label for="ticket_details">Ticket Details:</label>
                    <div class="input-box">
                        <textarea name="ticket_details" id="ticket_details" required><?php echo htmlspecialchars($ticket['t_details']); ?></textarea>
                        <i class='bx bxs-detail details-icon'></i>
                    </div>
                    <?php if (isset($errors['ticket_details'])): ?>
                        <span class="error"><?php echo htmlspecialchars($errors['ticket_details']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="form-row">
                    <label for="date">Date Issued:</label>
                    <div class="input-box">
                        <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($ticket['t_date']); ?>" required>
                        <i class='bx bxs-calendar date-icon'></i>
                    </div>
                    <?php if (isset($errors['date'])): ?>
                        <span class="error"><?php echo htmlspecialchars($errors['date']); ?></span>
                    <?php endif; ?>
                </div>
                <button type="submit">Update Ticket</button>
            </form>
        </div>
    </div>
</body>
</html>

<?php $conn->close(); ?>