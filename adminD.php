<?php 
include 'db.php';
session_start(); 

// Check if the user is logged in
if (!isset($_SESSION['username'])) { 
    header("Location: index.php"); // Redirect to login page if not logged in 
    exit(); 
}

// Avatar handling
$username = $_SESSION['username'];
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

// Initialize variables
$firstName = '';
$userType = '';
$totalUsers = 0;
$totalActive = 0;
$totalPending = 0;
$totalCustomers = 0;

// Check database connection
if ($conn) {
    // Fetch user data based on the logged-in username
    $sqlUser = "SELECT u_fname, u_type FROM tbl_user WHERE u_username = ?";
    $stmt = $conn->prepare($sqlUser);
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $resultUser = $stmt->get_result();

    if ($resultUser->num_rows > 0) {
        $row = $resultUser->fetch_assoc();
        $firstName = $row['u_fname'];
        $userType = $row['u_type'];
    }

    // Fetch all users data
    $sql = "SELECT u_id, u_fname, u_lname, u_email, u_username, u_type, u_status FROM tbl_user"; 
    $result = $conn->query($sql); 

    if ($result) {
        $totalUsers = $result->num_rows; // Total registered users
        while ($row = $result->fetch_assoc()) {
            if ($row['u_status'] === 'active') {
                $totalActive++;
            } elseif ($row['u_status'] === 'pending') {
                $totalPending++;
            }
        }
    }

    // Fetch total customers count
    $sqlCustomers = "SELECT COUNT(*) as total FROM tbl_customer"; 
    $resultCustomers = $conn->query($sqlCustomers);

    if ($resultCustomers) {
        $rowCustomers = $resultCustomers->fetch_assoc();
        $totalCustomers = $rowCustomers['total']; // Total customers
    }
} else {
    echo "Database connection failed.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | User Management</title>
    <link rel="stylesheet" href="adminDash.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> 
</head>
<body>
<div class="wrapper">
    <div class="sidebar glass-container">
        <h2>Task Management</h2>
        <ul>
            <li><a href="adminD.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="viewU.php"><i class="fas fa-users"></i> View Users</a></li>
            <li><a href="view_service_record.php"><i class="fas fa-file-alt"></i> View Service Record</a></li>
            <li><a href="logs.php"><i class="fas fa-file-archive"></i> View Logs</a></li>
            <li><a href="borrowedT.php"><i class="fas fa-box-open"></i>Borrowed Records</a></li>
            <li><a href="returnT.php"><i class="fas fa-undo-alt"></i> Return Records</a></li>
            <li><a href="deployedT.php"><i class="fas fa-clipboard-check"></i>Deployed Records</a></li>
        </ul>
        <footer>
        <a href="index.php" class="back-home"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </footer>
    </div>

    <div class="container">
        <div class="upper"> 
            <h1>Admin Dashboard</h1>
            
            <div class="user-profile">
                <div class="user-icon">
                    <a href="image.php">
                        <?php 
                        $cleanAvatarPath = preg_replace('/\?\d+$/', '', $avatarPath);
                        if (!empty($avatarPath) && file_exists($cleanAvatarPath)) {
                            echo "<img src='" . htmlspecialchars($avatarPath, ENT_QUOTES, 'UTF-8') . "' alt='User Avatar'>";
                        } else {
                            echo "<i class='fas fa-user-circle'></i>";
                        }
                        ?>
                    </a>
                </div>
                <div class="user-details">
                    <span><?php echo htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8'); ?></span>
                    <small><?php echo htmlspecialchars(ucfirst($userType), ENT_QUOTES, 'UTF-8'); ?></small>
                </div>
                <a href="settings.php" class="settings-link">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </div>
        </div>
          
        <div class="table-box">
            <div class="welcome-card">
                <h2>Welcome to TIMSS, <?php echo htmlspecialchars($firstName); ?>! 
                    <?php if ($userType === 'admin'): ?>
                        <span class="admin-badge"><i class="fas fa-user-shield"></i> Administrator</span>
                    <?php endif; ?>
                </h2>
                <p>Here is the total breakdown of the TIMS system.</p>
            </div>

            <div class="stat-grid">
                <div class="stat-card">
                    <h3>Total Registered Users</h3>
                    <p><?php echo $totalUsers; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Active Users</h3>
                    <p><?php echo $totalActive; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Pending Users</h3>
                    <p><?php echo $totalPending; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Customers</h3>
                    <p><?php echo $totalCustomers; ?></p>
                </div>
            </div>

            <div class="chart-container">
                <h2>TIMSS</h2>
                <div class="chart-grid">
                    <div class="chart-wrapper">
                        <div class="chart-title">Registered Users</div>
                        <canvas id="registeredUsersChart"></canvas>
                    </div>
                    <div class="chart-wrapper">
                        <div class="chart-title">Active Users</div>
                        <canvas id="activeUsersChart"></canvas>
                    </div>
                    <div class="chart-wrapper">
                        <div class="chart-title">Pending Users</div>
                        <canvas id="pendingUsersChart"></canvas>
                    </div>
                    <div class="chart-wrapper">
                        <div class="chart-title">Registered Customers</div>
                        <canvas id="customersChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function createEnhancedDoughnutChart(ctx, label, dataValue, total, colors) {
        const percentage = ((dataValue / total) * 100).toFixed(1);
        
        // Create percentage display element (without the label div)
        const percentageElement = document.createElement('div');
        percentageElement.className = 'chart-percentage';
        percentageElement.innerHTML = `
            <div class="value">${percentage}%</div>
        `;
        ctx.canvas.parentNode.appendChild(percentageElement);
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: [label, 'Others'],
                datasets: [{
                    data: [dataValue, total - dataValue],
                    backgroundColor: [
                        colors[0],
                        'rgba(200, 200, 200, 0.2)'
                    ],
                    borderColor: [
                        colors[1] || colors[0],
                        'rgba(200, 200, 200, 0.5)'
                    ],
                    borderWidth: 1,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                animation: {
                    animateScale: true,
                    animateRotate: true
                }
            }
        });
    }

    // Calculate a dynamic total for better visualization
    const dynamicTotal = Math.max(<?php echo $totalUsers + $totalActive + $totalPending + $totalCustomers; ?>, 100);

    // Create charts with enhanced styling
    const registeredUsersCtx = document.getElementById('registeredUsersChart').getContext('2d');
    createEnhancedDoughnutChart(registeredUsersCtx, 'Registered Users', <?php echo $totalUsers; ?>, dynamicTotal, 
        ['#6c5ce7', '#4a3fad']);
    
    const activeUsersCtx = document.getElementById('activeUsersChart').getContext('2d');
    createEnhancedDoughnutChart(activeUsersCtx, 'Active Users', <?php echo $totalActive; ?>, dynamicTotal, 
        ['#00b894', '#008c6d']);
    
    const pendingUsersCtx = document.getElementById('pendingUsersChart').getContext('2d');
    createEnhancedDoughnutChart(pendingUsersCtx, 'Pending Users', <?php echo $totalPending; ?>, dynamicTotal, 
        ['#fdcb6e', '#d4a429']);
    
    const customersCtx = document.getElementById('customersChart').getContext('2d');
    createEnhancedDoughnutChart(customersCtx, 'Customers', <?php echo $totalCustomers; ?>, dynamicTotal, 
        ['#e84393', '#b62e6f']);
</script>
</body>
</html>

<?php 
$conn->close(); // Close the database connection 
?>