<?php
session_start();
include 'db.php';

// Check if the ID is set in the URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Prepare the DELETE statement
    $sql = "DELETE FROM tbl_borrowed WHERE b_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Redirect back to the borrowedT.php page with a success message
        echo "<script type='text/javascript'>
                alert('Record deleted successfully.');
                window.location.href = 'borrowedT.php'; // Redirect to borrowedT.php
              </script>";
    } else {
        die("Execution failed: " . $stmt->error);
    }

    $stmt->close();
} else {
    // If no ID is provided, redirect back to the borrowedT.php page
    header("Location: borrowedT.php");
    exit();
}

$conn->close();
?>