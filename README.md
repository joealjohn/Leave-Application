## 📁 Project Structure

```
Leave-Application/
├── admin/                  # Admin panel pages
│   ├── dashboard.php      # Admin dashboard with statistics
│   ├── manage_users.php   # User management (add/edit/delete)
│   ├── all_requests.php   # View and manage all leave requests
│   ├── reports.php        # System reports and analytics
│   └── profile.php        # Admin profile management
├── user/                  # User panel pages
│   ├── dashboard.php      # User dashboard with personal stats
│   ├── apply_leave.php    # Leave application form
│   ├── my_requests.php    # User's leave request history
│   └── profile.php        # User profile management
├── includes/              # Shared components and functions
│   ├── config.php         # Database configuration
│   ├── functions.php      # Shared PHP functions
│   ├── admin-navbar.php   # Admin navigation bar
│   ├── user-navbar.php    # User navigation bar
│   └── footer.php         # Site footer
├── assets/                # Static assets
│   ├── css/
│   │   └── style.css      # Custom styles
│   ├── js/
│   │   └── scripts.js     # Custom JavaScript
│   └── images/            # Image assets
├── database/              # Database files
│   └── schema.sql         # Database structure and sample data
├── index.php              # Landing page / home
├── login.php              # User authentication
├── register.php           # User registration (optional)
├── logout.php             # Session termination
├── README.md              # Project documentation
├── TUTORIAL.md            # User guide
├── SETUP.txt              # Setup instructions
└── DATABASE_SETUP.sql     # Complete database setup
```