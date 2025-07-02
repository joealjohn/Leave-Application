Copilot said: # Leave Management System ![Leave Management System](https://img.shields.io/badge/Status-Complete-brightgreen) ![Version](https://img.shields.io/badge/Version-1.0.0-blue) ![PHP](https://img.shields.io/badge/PHP-8.0+-purple) ![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange) ![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-blueviolet) A comprehensive
Leave Management System
Leave Management System Version PHP MySQL Bootstrap

A comprehensive web-based Leave Management System that automates leave request submission, approval workflows, and employee leave tracking.

📋 Table of Contents
Overview
Features
Technology Stack
Project Structure
Installation
Usage
Screenshots
Database Schema
Contributing
License
🔍 Overview
This Leave Management System simplifies the leave application and approval process within an organization. It provides different interfaces for employees and administrators, allowing employees to apply for leave while giving administrators tools to manage and report on leave applications.

✨ Features
Employee Features
Account Management

Secure login and authentication
Profile management with password updates
View personal leave history and status
Leave Application

Apply for different types of leave (sick, vacation, emergency, personal)
Automatic calculation of working days (excluding weekends)
Check leave request status
View leave balance
Administrator Features
User Management

Create and manage user accounts
Set user permissions and roles
Leave Management

Approve or reject leave requests
View all leave applications with filtering options
Generate comprehensive leave reports
System Administration

Manage leave types and policies
Track system activities through logs
Generate reports and export data (PDF/Excel)
🛠 Technology Stack
Backend
PHP 8.0+: Core server-side scripting
MySQL 5.7+: Database management
PDO: Database connection and secure queries
Frontend
HTML5: Page structure
CSS3: Styling and layout
JavaScript: Client-side validation and dynamic content
Bootstrap 5.3: Responsive design framework
Font Awesome 6.0: Icons and visual elements
Security Features
Password hashing using PHP's password_hash()
Input sanitization to prevent XSS and SQL injection
Session management for secure authentication
Form validation on both client and server sides
📁 Project Structure
Code
leave-application/
├── admin/
│   ├── dashboard.php                # Admin main dashboard
│   ├── employees.php                # Employee management
│   ├── leave_types.php              # Configure leave types
│   ├── manage_requests.php          # Manage leave requests
│   ├── reports.php                  # Generate reports
│   └── view_request.php             # View detailed leave request
├── assets/
│   ├── css/
│   │   ├── style.css                # Custom styles
│   │   └── ...
│   ├── js/
│   │   ├── main.js                  # Custom JavaScript
│   │   └── ...
│   └── images/
│       └── ...
├── includes/
│   ├── admin-navbar.php             # Admin navigation bar
│   ├── footer-scripts.php           # Common footer scripts
│   ├── functions.php                # Core system functions
│   └── user-navbar.php              # User navigation bar
├── user/
│   ├── apply_leave.php              # Apply for leave form
│   ├── dashboard.php                # User dashboard
│   ├── my_requests.php              # View own leave requests
│   └── profile.php                  # User profile management
├── config.php                       # Database configuration
├── index.php                        # Main entry point
├── login.php                        # Login page
├── logout.php                       # Logout process
└── register.php                     # New user registration
📥 Installation
Prerequisites
PHP 8.0 or higher
MySQL 5.7 or higher
Apache/Nginx web server
Composer (optional)
Steps
Clone the repository

bash
git clone https://github.com/yourusername/leave-management-system.git
cd leave-management-system
Database Setup

Create a new MySQL database named leave_management
Import the database schema from database/schema.sql
bash
mysql -u username -p leave_management < database/schema.sql
Configuration

Copy config.sample.php to config.php
Update database connection details in config.php
PHP
$host = 'localhost';
$dbname = 'leave_management';
$username = 'your_db_username';
$password = 'your_db_password';
Web Server Configuration

Configure your web server to point to the project directory
Ensure the uploads directory has write permissions
First Login

Use the default admin credentials:
Username: admin@example.com
Password: admin123
Important: Change the default password immediately after first login
🚀 Usage
Employee Workflow
Login to your account
Navigate to Apply for Leave in the dashboard
Fill in the leave details (type, dates, reason)
Submit the request and wait for approval
Check leave status in My Requests
Administrator Workflow
Login with admin credentials
View pending requests in the admin dashboard
Approve or Reject leave applications
Generate reports by date range, leave type, or employee
Manage employees and system settings
📸 Screenshots
User Dashboard
User Dashboard

Leave Application Form
Leave Application

Admin Dashboard
Admin Dashboard

Leave Reports
Reports

🗃 Database Schema
Core Tables
users

id (PK)
name
email
password
role
department
created_at
updated_at
leave_requests

id (PK)
user_id (FK)
leave_type
start_date
end_date
days
reason
status
approved_by
approved_at
applied_at
leave_balances

id (PK)
user_id (FK)
leave_type
balance
created_at
updated_at
activity_logs

id (PK)
user_id (FK)
action
details
created_at
🤝 Contributing
Contributions are welcome! Please feel free to submit a Pull Request.

Fork the repository
Create your feature branch (git checkout -b feature/amazing-feature)
Commit your changes (git commit -m 'Add some amazing feature')
Push to the branch (git push origin feature/amazing-feature)
Open a Pull Request
📄 License
This project is licensed under the MIT License - see the LICENSE file for details.

© 2025 Leave Management System. Created as a college mini project by Joe Al John.
