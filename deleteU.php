<?php
include 'db.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Check if the user ID is set in the URL
if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    // Prepare the delete SQL statement
    $delete_sql = "DELETE FROM tbl_user WHERE u_id = ?";
    $stmt = $conn->prepare($delete_sql);
    
    if ($stmt === false) {
        die('Prepare failed: ' . htmlspecialchars($conn->error));
    }

    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        // Redirect back to the adminD.php page with a success message
        header("Location: viewU.php?message=User  deleted successfully");
        exit();
    } else {
        // Redirect back with an error message
        header("Location: viewU.php?message=Error deleting user: " . htmlspecialchars($stmt->error));
        exit();
    }
} else {
    // Redirect back if no user ID is specified
    header("Location: viewU.php?message=No user ID specified");
    exit();
}
?>