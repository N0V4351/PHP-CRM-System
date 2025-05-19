<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../index.php");
    exit;
}

$error = '';
$success = '';

// Handle user deletion
if(isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    
    // Don't allow deleting the last admin
    $sql = "SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'";
    $result = mysqli_query($conn, $sql);
    $admin_count = mysqli_fetch_assoc($result)['admin_count'];
    
    if($admin_count <= 1) {
        $error = "Cannot delete the last admin user.";
    } else {
        $sql = "DELETE FROM users WHERE id = ? AND role != 'admin'";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        
        if(mysqli_stmt_execute($stmt)) {
            $success = "User deleted successfully.";
        } else {
            $error = "Failed to delete user.";
        }
    }
}

// Handle role update
if(isset($_POST['update_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['role'];
    
    // Don't allow changing the last admin's role
    if($new_role !== 'admin') {
        $sql = "SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin' AND id != ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $admin_count = mysqli_fetch_assoc($result)['admin_count'];
        
        if($admin_count < 1) {
            $error = "Cannot change the last admin's role.";
        } else {
            $sql = "UPDATE users SET role = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $new_role, $user_id);
            
            if(mysqli_stmt_execute($stmt)) {
                $success = "User role updated successfully.";
            } else {
                $error = "Failed to update user role.";
            }
        }
    } else {
        $sql = "UPDATE users SET role = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $new_role, $user_id);
        
        if(mysqli_stmt_execute($stmt)) {
            $success = "User role updated successfully.";
        } else {
            $error = "Failed to update user role.";
        }
    }
}

// Get all users
$sql = "SELECT * FROM users ORDER BY created_at DESC";
$users = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM System - User Management</title>
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
                            <a href="../dashboard.php" class="text-gray-800 hover:text-indigo-600">
                                <i class="fas fa-cube text-indigo-600 text-2xl"></i>
                                <span class="ml-2 text-xl font-bold">CRM System</span>
                            </a>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <div class="ml-3 relative">
                            <div class="flex items-center space-x-4">
                                <a href="../dashboard.php" class="text-gray-700 hover:text-indigo-600">
                                    <i class="fas fa-home text-xl"></i>
                                </a>
                                <a href="../profile.php" class="text-gray-700 hover:text-indigo-600">
                                    <i class="fas fa-user-circle text-xl"></i>
                                </a>
                                <a href="../logout.php" class="text-gray-700 hover:text-indigo-600">
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
                <h1 class="text-2xl font-semibold text-gray-900">User Management</h1>
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

            <!-- Users List -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white shadow overflow-hidden sm:rounded-md">
                    <ul class="divide-y divide-gray-200">
                        <?php while($user = mysqli_fetch_assoc($users)): ?>
                        <li>
                            <div class="px-4 py-4 sm:px-6">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center">
                                            <?php if(!empty($user['profile_picture'])): ?>
                                                <img class="h-10 w-10 rounded-full" src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="">
                                            <?php else: ?>
                                                <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                    <i class="fas fa-user text-gray-400"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="ml-4">
                                                <p class="text-sm font-medium text-indigo-600 truncate">
                                                    <?php echo htmlspecialchars($user['username']); ?>
                                                </p>
                                                <p class="text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($user['email']); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="ml-4 flex-shrink-0 flex items-center space-x-4">
                                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="flex items-center space-x-2">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <select name="role" class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                                <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            </select>
                                            <button type="submit" name="update_role" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                Update Role
                                            </button>
                                        </form>
                                        <?php if($user['role'] !== 'admin'): ?>
                                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="flex items-center">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="delete_user" onclick="return confirm('Are you sure you want to delete this user?')" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                                Delete
                                            </button>
                                        </form>
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
</body>
</html> 