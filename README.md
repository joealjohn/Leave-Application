# Leave Management System

A comprehensive leave management system designed to streamline the process of applying for, managing, and approving leave requests within an organization.

## Features

- **User Authentication**: Secure login and registration system
- **User Dashboard**: Overview of leave requests and statuses
- **Admin Dashboard**: Comprehensive view of all leave requests and system stats
- **Leave Application**: Simple form for employees to request time off
- **Leave Approval**: Interface for admins to review and respond to leave requests
- **Reporting**: Generate reports on leave data
- **Activity Logging**: Track all system activities

## Installation with XAMPP

1. **Install XAMPP**
    - Download XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
    - Install XAMPP following the installation wizard instructions
    - Start the Apache and MySQL services from the XAMPP Control Panel

2. **Clone/Download the Repository**
    - Clone or download this repository to the `htdocs` folder in your XAMPP installation directory
    - Typically located at `C:\xampp\htdocs\` on Windows or `/Applications/XAMPP/htdocs/` on Mac
    - Rename the folder to `leave-management-system` or your preferred name

3. **Set Up the Database**
    - Open your web browser and go to `http://localhost/phpmyadmin/`
    - Create a new database named `mmnss_leave_management`
    - Either:
        - Click on the "Import" tab, choose the `setup-database.sql` file from the project folder, and click "Go" to import the database structure and initial data
    - Or:
        - Navigate to `http://localhost/leave-management-system/setup.php` to automatically set up the database

4. **Configure Database Connection (if needed)**
    - If your database credentials differ from the default settings, edit `/config/database.php` with your database credentials
    - Default settings are:
      ```php
      $host = 'localhost';
      $dbname = 'mmnss_leave_management';
      $username = 'root';
      $password = '';
      ```

5. **Access the Application**
    - Open your web browser and navigate to `http://localhost/leave-management-system/`
    - Login with the test accounts created during setup:
        - Admin: admin@gmail.com / admin@12345
        - User: test@gmail.com / test@1234

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- XAMPP 7.4.0 or higher (recommended for easy setup)

## Test Accounts

- **Admin Account**
    - Email: admin@gmail.com
    - Password: admin@12345

- **User Account**
    - Email: test@gmail.com
    - Password: test@1234

## Created Date and Author

- **Date**: 2025-06-29
- **Author**: ACE

## License

This project is open-source and available under the MIT License.