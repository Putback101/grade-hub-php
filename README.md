# Grade Hub - PHP/MySQL Version

Complete Grade Management System with PHP, MySQL, HTML, CSS, and JavaScript.

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache with mod_rewrite enabled
- Composer (optional, for dependency management)

## Installation

### 1. Database Setup

```bash
# Import the database schema
mysql -u root -p < database/schema.sql

# Or manually create the database and run the SQL commands
mysql -u root -p
CREATE DATABASE grade_hub;
USE grade_hub;
# ... paste contents of database/schema.sql
```

### 2. Configuration

Edit `config.php` with your database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('DB_NAME', 'grade_hub');
```

### 3. Directory Permissions

Make sure the following directories are writable:

```bash
chmod 755 public/
chmod 755 app/
chmod 755 views/
```

### 4. Start Development Server

```bash
# Using PHP built-in server
php -S localhost:8000 -t public/

# Or configure Apache to point to the public/ directory
```

Visit `http://localhost:8000/login` to access the application.

## Project Structure

```
grade-hub-php/
├── app/
│   ├── controllers/        # API endpoints
│   ├── middleware/         # Authentication & middleware
│   └── models/            # Data models
├── config.php             # Configuration file
├── database/
│   └── schema.sql         # Database schema
├── includes/
│   ├── Database.php       # Database connection class
│   └── ApiResponse.php    # API response helper
├── public/
│   ├── api/              # REST API endpoints
│   ├── assets/
│   │   ├── css/          # Stylesheets
│   │   └── js/           # JavaScript files
│   ├── login.php         # Login page
│   ├── register.php      # Registration page
│   ├── dashboard.php     # Dashboard
│   ├── grades.php        # Grades page
│   └── ... (other pages)
├── views/
│   ├── base.php          # Base layout template
│   └── layout.php        # Alternative layout
└── .htaccess             # Apache configuration
```

## Features

### User Roles

1. **Student**
   - View own grades
   - Filter by status
   - Request grade corrections

2. **Faculty**
   - Encode student grades
   - Submit grades for approval
   - View assigned subjects
   - Track grade status

3. **Registrar**
   - Approve/reject submitted grades
   - Review grade corrections
   - Generate reports
   - View activity logs

4. **Admin**
   - Manage all users
   - Manage subjects
   - System-wide reports
   - Activity monitoring

### Core Functionality

- User Authentication (Login/Register)
- Role-based Access Control (RBAC)
- Grade Encoding and Management
- Grade Approval Workflow
- Grade Correction System
- Activity Logging
- Dashboard with Statistics
- Responsive Design

## API Endpoints

### Authentication

- `POST /api/auth.php?action=login` - Login
- `POST /api/auth.php?action=register` - Register
- `POST /api/auth.php?action=logout` - Logout
- `GET /api/auth.php?action=profile` - Get current user

### Grades

- `GET /api/grades.php?action=list` - Get grades (with filters)
- `GET /api/grades.php?action=pending` - Get pending approvals
- `POST /api/grades.php?action=update` - Update grade
- `POST /api/grades.php?action=submit` - Submit grades
- `POST /api/grades.php?action=approve` - Approve grade
- `POST /api/grades.php?action=reject` - Reject grade

### Subjects

- `GET /api/subjects.php?action=list` - Get all subjects
- `GET /api/subjects.php?action=faculty` - Get faculty subjects
- `POST /api/subjects.php?action=create` - Create subject

### Corrections

- `GET /api/corrections.php?action=list` - Get correction requests
- `POST /api/corrections.php?action=request` - Request correction
- `POST /api/corrections.php?action=approve` - Approve correction
- `POST /api/corrections.php?action=reject` - Reject correction

### Dashboard

- `GET /api/dashboard.php?action=dashboard` - Get dashboard stats
- `GET /api/dashboard.php?action=recent` - Get recent activity

## Demo Credentials

After running the setup, use these credentials to test:

### Faculty
- Email: maria.santos@university.edu
- Password: password123

### Registrar
- Email: ana.reyes@university.edu
- Password: password123

### Student
- Email: carlos.garcia@student.edu
- Password: password123

## Security

The application includes:

- Password hashing with bcrypt
- Session-based authentication
- CSRF protection ready
- Input validation and sanitization
- SQL injection prevention with prepared statements
- XSS protection
- HTTP security headers

## Customization

### Adding New Features

1. Create model in `app/models/`
2. Create API endpoint in `public/api/`
3. Create view/page in `public/`
4. Update navigation in `views/base.php`

### Styling

- Edit `public/assets/css/style.css` for custom styles
- Modify `public/assets/css/tailwind.css` for utility classes
- Update colors in CSS root variables

### JavaScript

- `public/assets/js/main.js` - Core functionality
- `public/assets/js/api.js` - API helper functions
- `public/assets/js/ui.js` - UI helper functions

## Troubleshooting

### 403 Forbidden Error
- Ensure `.htaccess` is in the public directory
- Check Apache mod_rewrite is enabled

### Database Connection Failed
- Verify credentials in `config.php`
- Ensure MySQL service is running
- Check database exists

### Session Issues
- Clear browser cookies
- Check PHP session settings in `php.ini`
- Ensure `includes/` directory is accessible

## Development

### Adding a New Page

1. Create PHP file in `public/`
2. Add authentication check
3. Include base layout
4. Add sidebar navigation
5. Create API endpoint if needed

Example:
```php
<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/middleware/Auth.php';

Auth::requireRole('admin');
$user = Auth::getCurrentUser();
?>

<?php ob_start(); ?>
<!-- Page content here -->
<?php
$content = ob_get_clean();
$pageTitle = 'Page Title - Grade Hub';
include '../views/base.php';
?>
```

## Performance Tips

1. Enable MySQL query caching
2. Use prepared statements (already implemented)
3. Implement pagination for large result sets
4. Cache frequently accessed data
5. Use CDN for static assets
6. Enable gzip compression (htaccess)

## Support

For issues or questions, check the following:

1. PHP error logs
2. MySQL error logs
3. Browser console for JavaScript errors
4. Network tab for API requests

## License

This project is provided as-is for educational and commercial use.

## Next Steps

- Add email notifications
- Implement PDF export for reports
- Add chart visualizations
- Create mobile app
- Add two-factor authentication
- Implement real-time notifications
- Add grade templates
- Create batch import functionality
