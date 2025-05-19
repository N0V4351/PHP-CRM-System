<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

// Get user information
$user_id = $_SESSION["user_id"];
$username = $_SESSION["username"];
$role = $_SESSION["role"];

// Get statistics for dashboard
$stats = array();

// Get total users count (admin only)
if($role === 'admin') {
    $sql = "SELECT COUNT(*) as total FROM users WHERE role = 'user'";
    $result = mysqli_query($conn, $sql);
    $stats['total_users'] = mysqli_fetch_assoc($result)['total'];
}

// Get total tickets with creator info
$sql = "SELECT COUNT(*) as total, 
        GROUP_CONCAT(DISTINCT u.username) as creators 
        FROM tickets t 
        JOIN users u ON t.user_id = u.id";
if($role !== 'admin') {
    $sql .= " WHERE t.user_id = ?";
}
$stmt = mysqli_prepare($conn, $sql);
if($role !== 'admin') {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$ticket_stats = mysqli_fetch_assoc($result);
$stats['total_tickets'] = $ticket_stats['total'];
$stats['ticket_creators'] = $ticket_stats['creators'];

// Get total quotes with creator info
$sql = "SELECT COUNT(*) as total, 
        GROUP_CONCAT(DISTINCT u.username) as creators 
        FROM quotes q 
        JOIN users u ON q.user_id = u.id";
if($role !== 'admin') {
    $sql .= " WHERE q.user_id = ?";
}
$stmt = mysqli_prepare($conn, $sql);
if($role !== 'admin') {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$quote_stats = mysqli_fetch_assoc($result);
$stats['total_quotes'] = $quote_stats['total'];
$stats['quote_creators'] = $quote_stats['creators'];

// Get recent tickets with more details
$sql = "SELECT t.*, u.username, u.email 
        FROM tickets t 
        JOIN users u ON t.user_id = u.id";
if($role !== 'admin') {
    $sql .= " WHERE t.user_id = ?";
}
$sql .= " ORDER BY t.created_at DESC LIMIT 5";
$stmt = mysqli_prepare($conn, $sql);
if($role !== 'admin') {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
}
mysqli_stmt_execute($stmt);
$recent_tickets = mysqli_stmt_get_result($stmt);

// Get recent quotes with more details
$sql = "SELECT q.*, u.username, u.email 
        FROM quotes q 
        JOIN users u ON q.user_id = u.id";
if($role !== 'admin') {
    $sql .= " WHERE q.user_id = ?";
}
$sql .= " ORDER BY q.created_at DESC LIMIT 5";
$stmt = mysqli_prepare($conn, $sql);
if($role !== 'admin') {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
}
mysqli_stmt_execute($stmt);
$recent_quotes = mysqli_stmt_get_result($stmt);

// Get access logs for admin with pagination
$logs_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $logs_per_page;

if($role === 'admin') {
    // Get total count of logs
    $sql_count = "SELECT COUNT(*) as total FROM access_logs";
    $total_logs = mysqli_fetch_assoc(mysqli_query($conn, $sql_count))['total'];
    $total_pages = ceil($total_logs / $logs_per_page);

    // Get paginated logs
    $sql = "SELECT l.*, u.username, u.email 
            FROM access_logs l 
            JOIN users u ON l.user_id = u.id 
            ORDER BY l.created_at DESC 
            LIMIT ? OFFSET ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $logs_per_page, $offset);
    mysqli_stmt_execute($stmt);
    $access_logs = mysqli_stmt_get_result($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM System - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <i class="fas fa-cube text-indigo-600 text-2xl"></i>
                            <span class="ml-2 text-xl font-bold text-gray-800">CRM System</span>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <div class="ml-3 relative">
                            <div class="flex items-center space-x-4">
                                <a href="profile.php" class="text-gray-700 hover:text-indigo-600">
                                    <i class="fas fa-user-circle text-xl"></i>
                                </a>
                                <a href="logout.php" class="text-gray-700 hover:text-indigo-600">
                                    <i class="fas fa-sign-out-alt text-xl"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Welcome Section -->
            <div class="px-4 py-6 sm:px-0">
                <div class="flex justify-between items-center">
                    <h1 class="text-2xl font-semibold text-gray-900">Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
                    <div class="flex space-x-4">
                        <a href="tickets.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <i class="fas fa-ticket-alt mr-2"></i>
                            Create Ticket
                        </a>
                        <a href="quotes.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <i class="fas fa-file-invoice-dollar mr-2"></i>
                            Create Quote
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Total Users Card (Admin Only) -->
                <?php if($role === 'admin'): ?>
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                                <i class="fas fa-users text-white text-2xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Users</dt>
                                    <dd class="text-lg font-semibold text-gray-900"><?php echo $stats['total_users']; ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Total Tickets Card -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                <i class="fas fa-ticket-alt text-white text-2xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Tickets</dt>
                                    <dd class="text-lg font-semibold text-gray-900"><?php echo $stats['total_tickets']; ?></dd>
                                    <dd class="text-sm text-gray-500">
                                        Created by: <?php echo htmlspecialchars($stats['ticket_creators']); ?>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Quotes Card -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                                <i class="fas fa-file-invoice-dollar text-white text-2xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Quotes</dt>
                                    <dd class="text-lg font-semibold text-gray-900"><?php echo $stats['total_quotes']; ?></dd>
                                    <dd class="text-sm text-gray-500">
                                        Created by: <?php echo htmlspecialchars($stats['quote_creators']); ?>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Section -->
            <div class="mt-8 grid grid-cols-1 gap-5 lg:grid-cols-2">
                <!-- Recent Tickets -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Tickets</h3>
                    </div>
                    <div class="border-t border-gray-200">
                        <ul class="divide-y divide-gray-200">
                            <?php while($ticket = mysqli_fetch_assoc($recent_tickets)): ?>
                            <li class="px-4 py-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-indigo-600 truncate">
                                            <?php echo htmlspecialchars($ticket['subject']); ?>
                                        </p>
                                        <p class="mt-1 text-sm text-gray-500">
                                            Status: <span class="font-medium"><?php echo ucfirst($ticket['status']); ?></span>
                                        </p>
                                    </div>
                                    <div class="ml-4 flex-shrink-0">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            <?php echo $ticket['status'] === 'open' ? 'bg-green-100 text-green-800' : 
                                                ($ticket['status'] === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'); ?>">
                                            <?php echo ucfirst($ticket['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                </div>

                <!-- Recent Quotes -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Quotes</h3>
                    </div>
                    <div class="border-t border-gray-200">
                        <ul class="divide-y divide-gray-200">
                            <?php while($quote = mysqli_fetch_assoc($recent_quotes)): ?>
                            <li class="px-4 py-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-indigo-600 truncate">
                                            <?php echo htmlspecialchars($quote['subject']); ?>
                                        </p>
                                        <p class="mt-1 text-sm text-gray-500">
                                            Amount: $<?php echo number_format($quote['amount'], 2); ?>
                                        </p>
                                    </div>
                                    <div class="ml-4 flex-shrink-0">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            <?php echo $quote['status'] === 'approved' ? 'bg-green-100 text-green-800' : 
                                                ($quote['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                                            <?php echo ucfirst($quote['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Access Logs Section (Admin Only) -->
            <?php if($role === 'admin'): ?>
            <div class="mt-8">
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Activity Logs</h3>
                    </div>
                    <div class="border-t border-gray-200">
                        <ul class="divide-y divide-gray-200">
                            <?php while($log = mysqli_fetch_assoc($access_logs)): ?>
                            <li class="px-4 py-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($log['username']); ?>
                                            <span class="text-gray-500">(<?php echo htmlspecialchars($log['email']); ?>)</span>
                                        </p>
                                        <p class="mt-1 text-sm text-gray-500">
                                            <?php echo htmlspecialchars($log['action']); ?> - 
                                            <?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?>
                                        </p>
                                    </div>
                                    <div class="ml-4 flex-shrink-0">
                                        <span class="text-sm text-gray-500">
                                            IP: <?php echo htmlspecialchars($log['ip_address']); ?>
                                        </span>
                                    </div>
                                </div>
                            </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                    <!-- Pagination -->
                    <?php if($total_pages > 1): ?>
                    <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <?php if($current_page > 1): ?>
                            <a href="?page=<?php echo $current_page - 1; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Previous
                            </a>
                            <?php endif; ?>
                            <?php if($current_page < $total_pages): ?>
                            <a href="?page=<?php echo $current_page + 1; ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Next
                            </a>
                            <?php endif; ?>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing
                                    <span class="font-medium"><?php echo $offset + 1; ?></span>
                                    to
                                    <span class="font-medium"><?php echo min($offset + $logs_per_page, $total_logs); ?></span>
                                    of
                                    <span class="font-medium"><?php echo $total_logs; ?></span>
                                    results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <?php if($current_page > 1): ?>
                                    <a href="?page=<?php echo $current_page - 1; ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Previous</span>
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $start_page = max(1, $current_page - 2);
                                    $end_page = min($total_pages, $current_page + 2);
                                    
                                    if($start_page > 1) {
                                        echo '<a href="?page=1" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>';
                                        if($start_page > 2) {
                                            echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                        }
                                    }
                                    
                                    for($i = $start_page; $i <= $end_page; $i++) {
                                        $active_class = $i === $current_page ? 'bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white text-gray-700 hover:bg-gray-50';
                                        echo '<a href="?page=' . $i . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium ' . $active_class . '">' . $i . '</a>';
                                    }
                                    
                                    if($end_page < $total_pages) {
                                        if($end_page < $total_pages - 1) {
                                            echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                        }
                                        echo '<a href="?page=' . $total_pages . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">' . $total_pages . '</a>';
                                    }
                                    ?>
                                    
                                    <?php if($current_page < $total_pages): ?>
                                    <a href="?page=<?php echo $current_page + 1; ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Next</span>
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                    <?php endif; ?>
                                </nav>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html> 