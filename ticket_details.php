<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$role = $_SESSION["role"];
$error = '';
$success = '';

// Check if ticket ID is provided
if(!isset($_GET['id'])) {
    header("location: tickets.php");
    exit;
}

$ticket_id = $_GET['id'];

// Get ticket information
$sql = "SELECT t.*, u.username, u.email 
        FROM tickets t 
        JOIN users u ON t.user_id = u.id 
        WHERE t.id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $ticket_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) == 0) {
    header("location: tickets.php");
    exit;
}

$ticket = mysqli_fetch_assoc($result);

// Check if user has permission to view this ticket
if($role !== 'admin' && $ticket['user_id'] != $user_id) {
    header("location: tickets.php");
    exit;
}

// Handle status update (admin only)
if($role === 'admin' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $sql = "UPDATE tickets SET status = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $new_status, $ticket_id);
    
    if(mysqli_stmt_execute($stmt)) {
        $success = "Ticket status updated successfully.";
        $ticket['status'] = $new_status;
    } else {
        $error = "Failed to update ticket status.";
    }
}

// Handle reply submission
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_reply'])) {
    $message = trim($_POST['message']);
    
    if(empty($message)) {
        $error = "Please enter a message.";
    } else {
        $sql = "INSERT INTO ticket_replies (ticket_id, user_id, message) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iis", $ticket_id, $user_id, $message);
        
        if(mysqli_stmt_execute($stmt)) {
            $success = "Reply added successfully.";
        } else {
            $error = "Failed to add reply.";
        }
    }
}

// Get ticket replies
$sql = "SELECT r.*, u.username, u.profile_picture 
        FROM ticket_replies r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.ticket_id = ? 
        ORDER BY r.created_at ASC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $ticket_id);
mysqli_stmt_execute($stmt);
$replies = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM System - Ticket Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <a href="dashboard.php" class="text-gray-800 hover:text-indigo-600">
                                <i class="fas fa-cube text-indigo-600 text-2xl"></i>
                                <span class="ml-2 text-xl font-bold">CRM System</span>
                            </a>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <div class="ml-3 relative">
                            <div class="flex items-center space-x-4">
                                <a href="tickets.php" class="text-gray-700 hover:text-indigo-600">
                                    <i class="fas fa-ticket-alt text-xl"></i>
                                </a>
                                <a href="dashboard.php" class="text-gray-700 hover:text-indigo-600">
                                    <i class="fas fa-home text-xl"></i>
                                </a>
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
            <?php if(!empty($error)): ?>
                <div class="mb-4">
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline"><?php echo $error; ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if(!empty($success)): ?>
                <div class="mb-4">
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline"><?php echo $success; ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Ticket Information -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
                <div class="px-4 py-5 sm:px-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                <?php echo htmlspecialchars($ticket['subject']); ?>
                            </h3>
                            <p class="mt-1 max-w-2xl text-sm text-gray-500">
                                Created by <?php echo htmlspecialchars($ticket['username']); ?> on 
                                <?php echo date('M d, Y H:i', strtotime($ticket['created_at'])); ?>
                            </p>
                        </div>
                        <?php if($role === 'admin'): ?>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $ticket_id); ?>" method="post" class="flex items-center space-x-4">
                            <select name="status" class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option value="open" <?php echo $ticket['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                                <option value="in_progress" <?php echo $ticket['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="closed" <?php echo $ticket['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                            </select>
                            <button type="submit" name="update_status" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Update Status
                            </button>
                        </form>
                        <?php else: ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            <?php echo $ticket['status'] === 'open' ? 'bg-green-100 text-green-800' : 
                                ($ticket['status'] === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'); ?>">
                            <?php echo ucfirst($ticket['status']); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                    <div class="prose max-w-none">
                        <?php echo nl2br(htmlspecialchars($ticket['description'])); ?>
                    </div>
                </div>
            </div>

            <!-- Replies Section -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Replies</h3>
                </div>
                <div class="border-t border-gray-200">
                    <ul class="divide-y divide-gray-200">
                        <?php while($reply = mysqli_fetch_assoc($replies)): ?>
                        <li class="px-4 py-4">
                            <div class="flex space-x-3">
                                <div class="flex-shrink-0">
                                    <?php if(!empty($reply['profile_picture'])): ?>
                                        <img class="h-10 w-10 rounded-full" src="<?php echo htmlspecialchars($reply['profile_picture']); ?>" alt="">
                                    <?php else: ?>
                                        <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                            <i class="fas fa-user text-gray-400"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($reply['username']); ?>
                                        </h3>
                                        <p class="text-sm text-gray-500">
                                            <?php echo date('M d, Y H:i', strtotime($reply['created_at'])); ?>
                                        </p>
                                    </div>
                                    <div class="mt-1 text-sm text-gray-700">
                                        <?php echo nl2br(htmlspecialchars($reply['message'])); ?>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                </div>

                <!-- Reply Form -->
                <?php if($ticket['status'] !== 'closed'): ?>
                <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $ticket_id); ?>" method="post">
                        <div>
                            <label for="message" class="block text-sm font-medium text-gray-700">Add Reply</label>
                            <div class="mt-1">
                                <textarea name="message" id="message" rows="3" required class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"></textarea>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" name="submit_reply" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Submit Reply
                            </button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html> 