# Grade Hub PHP/MySQL Installation & Setup Guide

## Quick Start (5 minutes)

### Prerequisites
- PHP 7.4+
- MySQL 5.7+
- Apache with mod_rewrite
- A text editor

### Step 1: Extract Files
Extract the grade-hub-php folder to your web server root:
- Windows: `C:\xampp\htdocs\grade-hub-php` (if using XAMPP)
- Linux: `/var/www/html/grade-hub-php`
- Mac: `/Library/WebServer/Documents/grade-hub-php`

### Step 2: Create Database
```bash
# Open MySQL command line
mysql -u root -p

# Run this SQL
CREATE DATABASE grade_hub;
USE grade_hub;

# Copy and paste the entire content of database/schema.sql
# Or run:
source /path/to/database/schema.sql
```

### Step 3: Configure Database
Edit `config.php`:
```php
define('DB_HOST', 'localhost');      // Your MySQL host
define('DB_USER', 'root');            // Your MySQL username
define('DB_PASS', 'your_password');   // Your MySQL password
define('DB_NAME', 'grade_hub');       // Database name
define('DB_PORT', 3306);              // MySQL port
```

### Step 4: Start Server
```bash
# Navigate to the public directory
cd /path/to/grade-hub-php/public

# Start PHP server
php -S localhost:8000

# Or if using Apache, visit: http://localhost/grade-hub-php/public
```

### Step 5: Access Application
Open your browser and go to:
```
http://localhost:8000/login
```

## Demo Credentials

Use these to test the application:

| Role | Email | Password |
|------|-------|----------|
| Faculty | maria.santos@university.edu | password123 |
| Registrar | ana.reyes@university.edu | password123 |
| Student | carlos.garcia@student.edu | password123 |

## File Structure

```
grade-hub-php/
├── app/
│   ├── controllers/          # API endpoints (not used in current structure)
│   ├── middleware/
│   │   └── Auth.php          # Authentication & authorization
│   └── models/
│       ├── User.php
│       ├── Subject.php
│       ├── GradeEntry.php
│       ├── GradeCorrection.php
│       ├── Enrollment.php
│       └── ActivityLog.php
│
├── config.php                 # Database configuration
│
├── database/
│   ├── schema.sql            # Database structure
│   └── seeders.php           # Sample data
│
├── includes/
│   ├── Database.php          # Database connection class
│   └── ApiResponse.php       # API response helper
│
├── public/                   # Web root (point Apache here)
│   ├── api/
│   │   ├── auth.php          # Authentication endpoints
│   │   ├── grades.php        # Grade management endpoints
│   │   ├── corrections.php   # Grade correction endpoints
│   │   ├── subjects.php      # Subject endpoints
│   │   └── dashboard.php     # Dashboard data endpoints
│   │
│   ├── assets/
│   │   ├── css/
│   │   │   ├── style.css     # Custom styles
│   │   │   └── tailwind.css  # Utility classes
│   │   └── js/
│   │       ├── main.js       # Core functions
│   │       ├── api.js        # API helper
│   │       └── ui.js         # UI helper
│   │
│   ├── login.php             # Login page
│   ├── register.php          # Registration page
│   ├── logout.php            # Logout handler
│   ├── dashboard.php         # Dashboard
│   ├── grades.php            # Student grades
│   ├── subjects.php          # Subject listing
│   ├── grade-encoding.php    # Faculty grade entry
│   ├── grade-verification.php # Registrar approval
│   ├── grade-corrections.php # Correction requests
│   ├── reports.php           # Reports
│   ├── activity-logs.php     # Activity logs
│   └── .htaccess             # Apache routing
│
├── views/
│   ├── base.php              # Main layout template
│   └── layout.php            # Alternative layout
│
├── README.md                 # Full documentation
├── SETUP.md                  # This file
├── .htaccess                 # Apache configuration
└── package.json              # Project metadata
```

## Configuration Details

### Database Configuration (config.php)

```php
// Database credentials
define('DB_HOST', 'localhost');        // MySQL server host
define('DB_USER', 'root');             // MySQL username
define('DB_PASS', '');                 // MySQL password
define('DB_NAME', 'grade_hub');        // Database name
define('DB_PORT', 3306);               // MySQL port

// Application settings
define('SESSION_LIFETIME', 3600);      // Session timeout in seconds
define('APP_NAME', 'Grade Hub');       // Application name
define('APP_URL', 'http://localhost:8000'); // Application URL
define('DEBUG_MODE', true);            // Enable/disable debug mode
```

## Architecture

### Authentication Flow
1. User submits login credentials on `/login.php`
2. Form POST to `/login.php` (same file handles POST)
3. `Auth::login()` validates credentials in `User` model
4. If valid, creates session and logs activity
5. Redirects to `/dashboard`

### API Architecture
- All API endpoints in `/public/api/` folder
- Each endpoint file is standalone (checks auth, processes request)
- Returns JSON response via `ApiResponse::json()`
- Client-side JavaScript calls APIs with fetch()

### Database Queries
- All queries use prepared statements to prevent SQL injection
- UUID v4 used for all primary keys
- Timestamps use MySQL datetime format
- Proper foreign key relationships

## Security Features

✅ Password hashing with bcrypt
✅ Session-based authentication
✅ Prepared statements (SQL injection protection)
✅ HTML entity encoding
✅ CSRF protection ready
✅ XSS protection via content-type headers
✅ Role-based access control (RBAC)

## Adding a New Page

### Example: Adding a "My Reports" page for Faculty

1. **Create the page file**: `public/my-reports.php`
```php
<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/middleware/Auth.php';

Auth::requireRole('faculty');
$user = Auth::getCurrentUser();
?>

<?php ob_start(); ?>
<div class="p-8">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">My Reports</h1>
        <!-- Your content here -->
    </div>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'My Reports - Grade Hub';
include '../views/base.php';
?>
```

2. **Add navigation link in `views/base.php`**:
```php
// In the faculty menu section
['url' => '/my-reports', 'icon' => 'fa-file', 'label' => 'My Reports'],
```

3. **Create API endpoint if needed**: `public/api/faculty-reports.php`

## Troubleshooting

### 404 Not Found Error
- Ensure `.htaccess` is in `/public/` folder
- Check Apache `mod_rewrite` is enabled: `a2enmod rewrite`
- Restart Apache: `sudo systemctl restart apache2`

### 500 Internal Server Error
- Check PHP error log
- Verify database credentials in `config.php`
- Check file permissions: `chmod 755 app includes public`

### Session Not Working
- Verify PHP `session.save_path` is writable
- Check cookies are enabled in browser
- Clear browser cookies and cache

### Database Connection Failed
- Verify MySQL is running
- Check credentials: `mysql -u root -p`
- Ensure database exists: `SHOW DATABASES;`

### Grades Not Loading
- Check API is returning data: Open browser DevTools → Network tab
- Verify user has correct role permissions
- Check database for data: `SELECT * FROM grade_entries;`

## Performance Optimization

### Database
1. Add indexes on frequently queried columns (already in schema.sql)
2. Enable MySQL query cache
3. Regular backups

### PHP
1. Enable opcode caching (PHP 5.5+ has OPCache built-in)
2. Minimize database queries
3. Cache frequently accessed data

### Frontend
1. Minify CSS and JavaScript
2. Compress images
3. Enable gzip compression (htaccess)
4. Use CDN for static assets

## Deployment

### Production Checklist
- [ ] Set `DEBUG_MODE = false` in config.php
- [ ] Use strong database password
- [ ] Enable HTTPS
- [ ] Set `secure` = true in session config
- [ ] Backup database regularly
- [ ] Monitor error logs
- [ ] Update PHP and MySQL
- [ ] Restrict file permissions (644 files, 755 dirs)

### Environment Variables (Optional)
Create `.env` file in root:
```
DB_HOST=localhost
DB_USER=root
DB_PASS=password
DB_NAME=grade_hub
APP_ENV=production
```

Then load in config.php:
```php
$env = parse_ini_file('.env');
define('DB_HOST', $env['DB_HOST']);
```

## Common Tasks

### Backup Database
```bash
mysqldump -u root -p grade_hub > backup.sql
```

### Restore Database
```bash
mysql -u root -p grade_hub < backup.sql
```

### Reset Admin Password
```sql
UPDATE users SET password_hash = PASSWORD('newpassword') 
WHERE email = 'admin@university.edu';
```

### Clear Activity Logs
```sql
DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

## Further Customization

### Changing Colors
Edit `public/assets/css/style.css`:
```css
:root {
    --primary-color: #2563eb;    /* Change this to your brand color */
    --success-color: #16a34a;
    --warning-color: #f59e0b;
    --danger-color: #dc2626;
}
```

### Adding New Roles
1. Update role enum in `database/schema.sql`
2. Update `User` model validation
3. Add role check in Auth middleware
4. Add navigation items in `views/base.php`

### Email Notifications (Future)
Consider using PHPMailer or SwiftMailer:
```php
require 'vendor/autoload.php';
$mail = new PHPMailer(true);
// ... setup and send
```

## Getting Help

1. Check error logs: Check server/browser console
2. Review README.md for detailed documentation
3. Check API responses in browser DevTools
4. Verify database data directly with MySQL client

## Next Steps

1. ✅ Install and setup application
2. ✅ Test with demo accounts
3. ⏭ Add real users and data
4. ⏭ Customize branding/colors
5. ⏭ Deploy to production
6. ⏭ Set up automated backups
7. ⏭ Monitor activity logs regularly

---

**Congratulations!** Your Grade Hub system is now ready to use.
