<?php
require_once 'config.php';

// Array of SQL queries to create tables
$tables = [
    // Users table
    "CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        profile_picture VARCHAR(255),
        role ENUM('admin', 'user') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",

    // Tickets table
    "CREATE TABLE IF NOT EXISTS tickets (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT,
        subject VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        status ENUM('open', 'in_progress', 'closed') DEFAULT 'open',
        priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",

    // Ticket replies table
    "CREATE TABLE IF NOT EXISTS ticket_replies (
        id INT PRIMARY KEY AUTO_INCREMENT,
        ticket_id INT,
        user_id INT,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",

    // Quotes table
    "CREATE TABLE IF NOT EXISTS quotes (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT,
        subject VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        amount DECIMAL(10,2),
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",

    // User access logs table
    "CREATE TABLE IF NOT EXISTS access_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT,
        action VARCHAR(255) NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",

    // User sessions table
    "CREATE TABLE IF NOT EXISTS user_sessions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT,
        session_token VARCHAR(255) NOT NULL,
        ip_address VARCHAR(45),
        last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )"
];

// Execute each query
foreach($tables as $sql) {
    if(!mysqli_query($conn, $sql)) {
        die("Error creating table: " . mysqli_error($conn));
    }
}

// Create default admin user if not exists
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$check_admin = "SELECT id FROM users WHERE email = 'admin@crm.com'";
$result = mysqli_query($conn, $check_admin);

if(mysqli_num_rows($result) == 0) {
    $create_admin = "INSERT INTO users (username, email, password, full_name, role) 
                    VALUES ('admin', 'admin@crm.com', '$admin_password', 'System Administrator', 'admin')";
    if(!mysqli_query($conn, $create_admin)) {
        die("Error creating admin user: " . mysqli_error($conn));
    }
}

echo "Database setup completed successfully!";
mysqli_close($conn);
?> 