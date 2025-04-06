<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $c_id = htmlspecialchars($_POST['c_id']);
    $c_lname = htmlspecialchars($_POST['c_lname']);
    $c_fname = htmlspecialchars($_POST['c_fname']);
    $subject = htmlspecialchars($_POST['subject']);
    $message = htmlspecialchars($_POST['message']);
    $type = htmlspecialchars($_POST['type']);
    $username = "$c_fname $c_lname";

    // Prepare the SQL statement for ticket insertion
    $stmt = $conn->prepare("INSERT INTO tbl_supp_tickets (c_id, c_lname, c_fname, s_subject, s_message, s_type, s_status) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $status = "Open";
    $stmt->bind_param("issssss", $c_id, $c_lname, $c_fname, $subject, $message, $type, $status);

    if ($stmt->execute()) {
        // Log the ticket creation with timestamp
        $log_description = "user \"$username\" created ticket with $subject";
        $log_stmt = $conn->prepare("INSERT INTO tbl_logs (l_description, l_stamp) VALUES (?, NOW())");
        $log_stmt->bind_param("s", $log_description);
        $log_stmt->execute();
        $log_stmt->close();
        
        echo "success";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    header("HTTP/1.1 405 Method Not Allowed");
    echo "Invalid request method";
}
?>