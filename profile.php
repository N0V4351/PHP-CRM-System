<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$error = '';
$success = '';

// Get user information
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Handle profile update
if($_SERVER["REQUEST_METHOD"] == "POST") {
    if(isset($_POST['update_profile'])) {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        
        // Validate email
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } else {
            // Check if email exists for other users
            $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $email, $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            
            if(mysqli_stmt_num_rows($stmt) > 0) {
                $error = "This email is already taken.";
            } else {
                // Update profile
                $sql = "UPDATE users SET full_name = ?, email = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ssi", $full_name, $email, $user_id);
                
                if(mysqli_stmt_execute($stmt)) {
                    $success = "Profile updated successfully.";
                    // Refresh user data
                    $sql = "SELECT * FROM users WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "i", $user_id);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    $user = mysqli_fetch_assoc($result);
                } else {
                    $error = "Something went wrong. Please try again later.";
                }
            }
        }
    }
    
    // Handle password change
    if(isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if($new_password != $confirm_password) {
            $error = "New passwords do not match.";
        } elseif(strlen($new_password) < 6) {
            $error = "Password must have at least 6 characters.";
        } else {
            // Verify current password
            if(password_verify($current_password, $user['password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET password = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "si", $hashed_password, $user_id);
                
                if(mysqli_stmt_execute($stmt)) {
                    $success = "Password changed successfully.";
                } else {
                    $error = "Something went wrong. Please try again later.";
                }
            } else {
                $error = "Current password is incorrect.";
            }
        }
    }
    
    // Handle profile picture upload
    if(isset($_FILES['profile_picture'])) {
        $file = $_FILES['profile_picture'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if($file['error'] === 0) {
            if(!in_array($file['type'], $allowed_types)) {
                $error = "Only JPG, PNG and GIF files are allowed.";
            } elseif($file['size'] > $max_size) {
                $error = "File size must be less than 5MB.";
            } else {
                $upload_dir = 'uploads/profile_pictures/';
                if(!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $new_filename = $user_id . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if(move_uploaded_file($file['tmp_name'], $upload_path)) {
                    // Delete old profile picture if exists
                    if(!empty($user['profile_picture']) && file_exists($user['profile_picture'])) {
                        unlink($user['profile_picture']);
                    }
                    
                    // Update database
                    $sql = "UPDATE users SET profile_picture = ? WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "si", $upload_path, $user_id);
                    
                    if(mysqli_stmt_execute($stmt)) {
                        $success = "Profile picture updated successfully.";
                        $user['profile_picture'] = $upload_path;
                    } else {
                        $error = "Failed to update profile picture in database.";
                        unlink($upload_path);
                    }
                } else {
                    $error = "Failed to upload profile picture.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM System - Profile</title>
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
                <h1 class="text-2xl font-semibold text-gray-900">Profile Settings</h1>
            </div>

            <?php if(!empty($error)): ?>
                <div class="max-w-3xl mx-auto mb-4">
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline"><?php echo $error; ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if(!empty($success)): ?>
                <div class="max-w-3xl mx-auto mb-4">
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline"><?php echo $success; ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <div class="max-w-3xl mx-auto">
                <!-- Profile Picture Section -->
                <div class="bg-white shadow rounded-lg mb-6">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Profile Picture</h3>
                        <div class="mt-4 flex items-center">
                            <div class="flex-shrink-0">
                                <?php if(!empty($user['profile_picture'])): ?>
                                    <img class="h-24 w-24 rounded-full object-cover" src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile picture">
                                <?php else: ?>
                                    <div class="h-24 w-24 rounded-full bg-gray-200 flex items-center justify-center">
                                        <i class="fas fa-user text-gray-400 text-4xl"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="ml-4">
                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                                    <div class="flex items-center">
                                        <input type="file" name="profile_picture" accept="image/*" class="hidden" id="profile_picture">
                                        <label for="profile_picture" class="cursor-pointer bg-white py-2 px-3 border border-gray-300 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                            Change Picture
                                        </label>
                                        <button type="submit" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                            Upload
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Information Section -->
                <div class="bg-white shadow rounded-lg mb-6">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Profile Information</h3>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="mt-4">
                            <div class="grid grid-cols-1 gap-6">
                                <div>
                                    <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                                    <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 bg-gray-50 text-gray-500 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                                    <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                            </div>
                            <div class="mt-6">
                                <button type="submit" name="update_profile" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Change Password Section -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Change Password</h3>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="mt-4">
                            <div class="grid grid-cols-1 gap-6">
                                <div>
                                    <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                                    <input type="password" name="current_password" id="current_password" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                                    <input type="password" name="new_password" id="new_password" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                                    <input type="password" name="confirm_password" id="confirm_password" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                            </div>
                            <div class="mt-6">
                                <button type="submit" name="change_password" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Change Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Preview profile picture before upload
        document.getElementById('profile_picture').addEventListener('change', function(e) {
            if(e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.querySelector('.flex-shrink-0 img') || document.querySelector('.flex-shrink-0 div');
                    if(preview.tagName === 'IMG') {
                        preview.src = e.target.result;
                    } else {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'h-24 w-24 rounded-full object-cover';
                        preview.parentNode.replaceChild(img, preview);
                    }
                }
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    </script>
</body>
</html>