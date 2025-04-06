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

    // Prepare the delete statement
    $sqlDelete = "DELETE FROM tbl_ticket WHERE t_id = ?";
    $stmtDelete = $conn->prepare($sqlDelete);
    $stmtDelete->bind_param("i", $ticketId);
    
    if ($stmtDelete->execute()) {
        echo "<script>alert('Ticket deleted successfully!'); window.location.href='staffD.php';</script>";
    } else {
        echo "<script>alert('Error deleting ticket.'); window.location.href='staffD.php';</script>";
    }
} else {
    echo "<script>alert('No ticket ID provided.'); window.location.href='staffD.php';</script>";
}
?>