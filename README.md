# 🏢 Leave Management System
<p align="center">
  <a href="https://github.com/yourusername/leave-management-system/releases">
    <img src="https://img.shields.io/github/v/release/yourusername/leave-management-system?label=RELEASES&style=flat-square&color=blue" alt="Releases">
  </a>
  <a href="https://github.com/yourusername/leave-management-system/actions">
    <img src="https://img.shields.io/github/actions/workflow/status/yourusername/leave-management-system/php.yml?label=BUILD&style=flat-square" alt="Build">
  </a>
  <img src="https://img.shields.io/badge/Download-ZIP-4c4c4c?style=flat-square">
  <img src="https://img.shields.io/badge/Repo%20or%20Workflow%20Not%20Found-red?style=flat-square">
  <a href="https://discord.gg/yourdiscordinvite">
    <img src="https://img.shields.io/discord/123456789012345678?label=DISCORD&style=flat-square&color=5865F2" alt="Discord">
  </a>
</p>
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
├── login.php                        # Login page
├── logout.php                       # Logout process
└── register.php                     # New user registration

---
