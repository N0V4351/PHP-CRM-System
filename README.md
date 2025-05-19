# PHP CRM System

A modern, feature-rich Customer Relationship Management (CRM) system built with PHP and MySQL. This system includes user management, ticketing system, quote management, and detailed activity logging.

## Features

- 🔐 Secure User Authentication
- 👥 User Management (Admin Panel)
- 🎫 Ticket Management System
- 💰 Quote Management
- 📊 Activity Logging
- 📱 Responsive Design with Tailwind CSS
- 🔍 Detailed Statistics Dashboard

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- mod_rewrite enabled (for Apache)

## Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/php-crm.git
cd php-crm
```

2. Configure your database in `config.php`:
```php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'your_username');
define('DB_PASSWORD', 'your_password');
define('DB_NAME', 'your_db_name');
```

3. Create a new MySQL database named `crm_database` (or your preferred name)

4. Visit `http://your-domain.com/setup.php` to:
   - Create necessary database tables
   - Set up initial configuration
   - Create admin account

5. After successful setup, delete `setup.php` for security

## Default Admin Credentials

After running setup.php, you can log in with these default credentials:
- Username: `admin`
- Email: admin@crm.com
- Password: `admin123`

**Important:** Please change the default password immediately after first login!

## Directory Structure

```
php-crm/
├── config.php          # Database configuration
├── setup.php          # Initial setup script
├── index.php          # Login page
├── dashboard.php      # Main dashboard
├── tickets.php        # Ticket management
├── quotes.php         # Quote management
├── profile.php        # User profile
└── logout.php         # Logout script
```

## Security Features

- Password hashing using PHP's password_hash()
- Prepared statements to prevent SQL injection
- Session-based authentication
- Input validation and sanitization
- XSS protection
- CSRF protection

## Usage

1. **User Management**
   - Admin can create, edit, and delete users
   - Users can update their profiles
   - Role-based access control

2. **Ticket System**
   - Create and manage support tickets
   - Track ticket status and priority
   - Add comments and updates

3. **Quote Management**
   - Create and send quotes to clients
   - Track quote status
   - Manage quote amounts and details

4. **Activity Logging**
   - Track user actions
   - Monitor system access
   - View detailed activity history

## Customization

The system uses Tailwind CSS for styling, making it easy to customize the look and feel. You can modify the following:

- Color scheme in the CSS classes
- Layout structure in the HTML
- Add new features by extending the existing codebase

## Contributing

Feel free to submit issues and enhancement requests!

## Support

Need help or want to customize this project for your needs?

Contact me on WhatsApp:
- 📱 +1 (720) 506-9966

I can help you with:
- Custom feature development
- System customization
- Bug fixes
- Performance optimization
- Security enhancements
- Any other PHP/MySQL project requirements

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Author

Erfan Noyon
- WhatsApp: +1 (720) 506-9966
- Email: your.email@example.com

---

⭐ Star this repository if you find it useful! 
