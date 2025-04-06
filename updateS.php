<?php
include 'db.php'; // Include database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticketId = htmlspecialchars($_POST['id']);
    $status = htmlspecialchars($_POST['status']);

    // Prepare the SQL statement to prevent SQL injection
    $stmt = $conn->prepare("UPDATE tbl_supp_tickets SET s_status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $ticketId); // "si" means string and integer

    // Execute the statement
    if ($stmt->execute()) {
        echo "success"; // If the update is successful
    } else {
        echo "Error: " . $stmt->error; // If there is an error
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
}
?>