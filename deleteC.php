<?php 
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) { 
    header("Location: index.php"); // Redirect to login page if not logged in 
    exit(); 
}

// Check if the customer ID is provided
if (isset($_GET['id'])) {
    $customerId = $_GET['id'];

    // Prepare the delete statement
    $sqlDelete = "DELETE FROM tbl_customer WHERE c_id = ?";
    $stmtDelete = $conn->prepare($sqlDelete);
    $stmtDelete->bind_param("i", $customerId);
    
    if ($stmtDelete->execute()) {
        echo "<script>alert('Customer deleted successfully!'); window.location.href='customersT.php';</script>";
    } else {
        echo "<script>alert('Error deleting customer.'); window.location.href='customersT.php';</script>";
    }
} else {
    echo "<script>alert('No customer ID provided.'); window.location.href='customersT.php';</script>";
}
?>