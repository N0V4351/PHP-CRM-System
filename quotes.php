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

// Handle quote creation
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_quote'])) {
    $subject = trim($_POST['subject']);
    $description = trim($_POST['description']);
    $amount = floatval($_POST['amount']);
    
    if(empty($subject) || empty($description) || $amount <= 0) {
        $error = "Please fill all required fields with valid values.";
    } else {
        $sql = "INSERT INTO quotes (user_id, subject, description, amount) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "issd", $user_id, $subject, $description, $amount);
        
        if(mysqli_stmt_execute($stmt)) {
            $success = "Quote request submitted successfully.";
        } else {
            $error = "Something went wrong. Please try again later.";
        }
    }
}

// Handle quote status update (admin only)
if($role === 'admin' && isset($_POST['update_status'])) {
    $quote_id = $_POST['quote_id'];
    $new_status = $_POST['status'];
    
    $sql = "UPDATE quotes SET status = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $new_status, $quote_id);
    
    if(mysqli_stmt_execute($stmt)) {
        $success = "Quote status updated successfully.";
    } else {
        $error = "Failed to update quote status.";
    }
}

// Get quotes
$sql = "SELECT q.*, u.username, u.email 
        FROM quotes q 
        JOIN users u ON q.user_id = u.id";
if($role !== 'admin') {
    $sql .= " WHERE q.user_id = ?";
}
$sql .= " ORDER BY q.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if($role !== 'admin') {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
}
mysqli_stmt_execute($stmt);
$quotes = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM System - Quotes</title>
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
            <div class="px-4 py-6 sm:px-0">
                <div class="flex justify-between items-center">
                    <h1 class="text-2xl font-semibold text-gray-900">Quotes</h1>
                    <button onclick="document.getElementById('createQuoteModal').classList.remove('hidden')" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-plus mr-2"></i>
                        Request Quote
                    </button>
                </div>
            </div>

            <?php if(!empty($error)): ?>
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-4">
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline"><?php echo $error; ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if(!empty($success)): ?>
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-4">
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline"><?php echo $success; ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Quotes List -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white shadow overflow-hidden sm:rounded-md">
                    <ul class="divide-y divide-gray-200">
                        <?php while($quote = mysqli_fetch_assoc($quotes)): ?>
                        <li>
                            <div class="px-4 py-4 sm:px-6">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-indigo-600 truncate">
                                            <?php echo htmlspecialchars($quote['subject']); ?>
                                        </p>
                                        <p class="mt-1 text-sm text-gray-500">
                                            Requested by <?php echo htmlspecialchars($quote['username']); ?> on 
                                            <?php echo date('M d, Y H:i', strtotime($quote['created_at'])); ?>
                                        </p>
                                        <p class="mt-1 text-sm text-gray-900">
                                            Amount: $<?php echo number_format($quote['amount'], 2); ?>
                                        </p>
                                    </div>
                                    <div class="ml-4 flex-shrink-0 flex items-center space-x-4">
                                        <?php if($role === 'admin'): ?>
                                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="flex items-center space-x-2">
                                            <input type="hidden" name="quote_id" value="<?php echo $quote['id']; ?>">
                                            <select name="status" class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                                <option value="pending" <?php echo $quote['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="approved" <?php echo $quote['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                                <option value="rejected" <?php echo $quote['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                            </select>
                                            <button type="submit" name="update_status" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                Update
                                            </button>
                                        </form>
                                        <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            <?php echo $quote['status'] === 'approved' ? 'bg-green-100 text-green-800' : 
                                                ($quote['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                                            <?php echo ucfirst($quote['status']); ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>
        </main>
    </div>

    <!-- Create Quote Modal -->
    <div id="createQuoteModal" class="hidden fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    Request a Quote
                                </h3>
                                <div class="mt-4">
                                    <div class="mb-4">
                                        <label for="subject" class="block text-sm font-medium text-gray-700">Subject</label>
                                        <input type="text" name="subject" id="subject" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    </div>
                                    <div class="mb-4">
                                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                                        <textarea name="description" id="description" rows="4" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                                    </div>
                                    <div class="mb-4">
                                        <label for="amount" class="block text-sm font-medium text-gray-700">Estimated Amount ($)</label>
                                        <input type="number" name="amount" id="amount" step="0.01" min="0" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" name="create_quote" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Submit Request
                        </button>
                        <button type="button" onclick="document.getElementById('createQuoteModal').classList.add('hidden')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 