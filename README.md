# 🏢 Leave Management System

A **comprehensive web-based Leave Management System** that automates leave request submission, approval workflows, and employee leave tracking.

---

## 📋 Table of Contents

- [🔍 Overview](#-overview)
- [✨ Features](#-features)
- [Employee Features](#employee-features)
- [Administrator Features](#administrator-features)
- [🛠 Technology Stack](#-technology-stack)
- [📁 Project Structure](#-project-structure)
- [📥 Installation](#-installation)
- [🚀 Usage](#-usage)
- [📸 Screenshots](#-screenshots)
- [🗃 Database Schema](#-database-schema)
- [🤝 Contributing](#-contributing)
- [📄 License](#-license)

---

## 🔍 Overview

This **Leave Management System** simplifies the leave application and approval process within an organization. It offers separate interfaces for **employees** and **administrators**, allowing employees to apply for leave and admins to manage, approve, and report on those requests.

---

## ✨ Features

### 👤 Employee Features

- 🔐 **Secure Login** and session-based authentication  
- 📝 Manage profile and update password  
- 📊 View leave balance, history, and request status  
- 📅 Apply for various leave types (Sick, Vacation, Emergency, etc.)  
- 📆 Auto-calculate leave days (excluding weekends)

### 🛡️ Administrator Features

- 👥 Manage employee accounts and permissions  
- ✅ Approve or reject leave requests  
- 📄 View and filter all leave applications  
- 📈 Generate leave reports (PDF/Excel)  
- ⚙️ Configure leave types and policies  
- 🧾 Track system activities with logs  

---

## 🛠 Technology Stack

### 🔧 Backend
- **PHP 8.0+** — Core scripting
- **MySQL 5.7+** — Database
- **PDO** — Secure database access

### 🎨 Frontend
- **HTML5 / CSS3** — Structure & Styling  
- **Bootstrap 5.3** — Responsive layout  
- **JavaScript** — Dynamic behavior  
- **Font Awesome 6.0** — Icons  

### 🔐 Security
- Password hashing (`password_hash()`)  
- XSS/SQL Injection protection  
- Secure session management  
- Server & client-side form validation  

---

## 📁 Project Structure

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

---
