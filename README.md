# Supervised Driving Experience Tracker

A comprehensive web application for managing and analyzing supervised driving experiences, built with PHP, MySQL, HTML5, CSS3, and JavaScript.

## 📋 Project Overview

This application allows users to:
- Record driving experiences with detailed information (date, time, distance, conditions)
- Track various driving variables (vehicle type, weather, road type, surface, traffic density)
- View and filter all driving experiences
- Analyze driving patterns through interactive statistics and charts
- Manage reference data (add/remove vehicle types, weather conditions, etc.)

## 🚀 Features

### Core Functionality
- ✅ **Add Driving Experience**: Mobile-responsive form with validation
- ✅ **View All Experiences**: Paginated table with advanced filtering
- ✅ **Statistics Dashboard**: Interactive charts using Chart.js
- ✅ **Manage Variables**: CRUD operations for reference data
- ✅ **Responsive Design**: Mobile-first approach with CSS Grid/Flexbox

### Technical Highlights
- **Normalized Database**: 7 lookup tables + main experiences table
- **Security**: Prepared statements to prevent SQL injection
- **Session Management**: PHP sessions for messaging system
- **Modern CSS**: CSS Grid, Flexbox, CSS Variables, Media Queries
- **Data Visualization**: Chart.js for graphs and statistics
- **Form Validation**: Client-side and server-side validation
- **W3C Compliant**: Semantic HTML5 elements

## 📁 Project Structure

```
/opt/lampp/htdocs/myproject/final/
├── config/
│   └── database.php              # Database connection class (Singleton pattern)
├── css/
│   └── style.css                 # Main stylesheet with responsive design
├── database/
│   └── schema.sql                # Database schema and initial data
├── index.php                     # Dashboard/Home page
├── add-experience.php            # Form to add new experience
├── process-experience.php        # Backend processing for form submission
├── view-experiences.php          # View all experiences with filters
├── delete-experience.php         # Delete experience handler
├── statistics.php                # Statistics and charts page
├── manage-variables.php          # Manage reference data (CRUD)
└── README.md                     # This file
```

## 🗄️ Database Schema

### Tables:
1. **vehicle_types** - Vehicle categories (Sedan, SUV, etc.)
2. **time_of_day** - Time periods (Morning Rush, Night, etc.)
3. **surfaces** - Road surfaces (Asphalt-Dry, Ice/Snow, etc.)
4. **road_densities** - Traffic levels (Very Low to Very High)
5. **road_types** - Road categories (Highway, Urban Street, etc.)
6. **weather_conditions** - Weather types (Sunny, Rainy, etc.)
7. **driving_experiences** - Main table with foreign keys to all lookup tables

### Relationships:
- All relationships are **1-to-many** (one vehicle type → many experiences)
- Foreign key constraints maintain referential integrity
- Indexes on date fields for performance

## 🛠️ Installation & Setup

### Prerequisites
- XAMPP/LAMPP (PHP 7.4+, MySQL 5.7+)
- Web browser (Chrome, Firefox, Safari, Edge)

### Step 1: Import Database

1. Start XAMPP/LAMPP:
   ```bash
   sudo /opt/lampp/lampp start
   ```

2. Open phpMyAdmin: `http://localhost/phpmyadmin`

3. Create database or import the schema:
   - Click "New" to create a database named `driving_experience`
   - Or import `database/schema.sql` file

4. Alternatively, run SQL from terminal:
   ```bash
   mysql -u root -p < /opt/lampp/htdocs/myproject/final/database/schema.sql
   ```

### Step 2: Configure Database Connection

Edit `config/database.php` if needed:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Your MySQL password
define('DB_NAME', 'driving_experience');
```

### Step 3: Access the Application

Open your browser and navigate to:
```
http://localhost/myproject/final/
```

## 💻 Usage Guide

### Adding a Driving Experience
1. Click "Add Experience" in the navigation
2. Fill in all required fields (marked with *)
3. Optional: Add start/end locations and notes
4. Click "Save Experience"

### Viewing Experiences
1. Navigate to "View All"
2. Use filters to narrow results:
   - Date range
   - Vehicle type
   - Weather condition
   - Road type
3. View total kilometers displayed at top
4. Edit or delete experiences using action buttons

### Viewing Statistics
1. Click "Statistics" to see:
   - Overall totals (km, trips, averages)
   - Monthly trends (line chart)
   - Weather distribution (doughnut chart)
   - Vehicle usage (bar chart)
   - Road type analysis (horizontal bar)
   - Time of day patterns (radar chart)

### Managing Variables
1. Navigate to "Manage Data"
2. Add new options by typing name and clicking "Add"
3. Remove items by clicking the "×" button
4. Note: Cannot delete items currently in use

## 🎨 Design Features

### Responsive Design
- **Desktop**: Full table view, multi-column grids
- **Tablet**: Adaptive grids, readable tables
- **Mobile**: Single column layout, optimized forms, touch-friendly buttons

### User Experience
- Auto-populated current date/time in forms
- Numeric keypad for number inputs on mobile
- Time of day auto-selection based on start time
- Visual feedback for all actions
- Confirmation dialogs for deletions

### Accessibility
- Semantic HTML5 elements
- Proper heading hierarchy
- Form labels and ARIA attributes
- Keyboard navigation support
- Color contrast compliance

## 🔒 Security Features

- **SQL Injection Prevention**: All queries use prepared statements
- **Input Validation**: Server-side validation for all inputs
- **XSS Prevention**: All outputs escaped with `htmlspecialchars()`
- **CSRF Protection**: Session-based messaging system
- **Database Constraints**: Foreign keys and CHECK constraints

## 📊 Statistics & Analytics

The application provides comprehensive analytics:

1. **Overall Statistics**:
   - Total kilometers driven
   - Total number of trips
   - Average distance per trip
   - Date of first trip

2. **Charts**:
   - Monthly driving trends (kilometers + trips)
   - Weather condition distribution
   - Vehicle type usage comparison
   - Road type preferences
   - Time of day patterns

## 🧪 Testing

### Sample Data
The database schema includes 3 sample driving experiences for testing.

### Test Scenarios
1. **Add Experience**: Test with various input combinations
2. **Filters**: Test date ranges, vehicle types, weather conditions
3. **Pagination**: Add 10+ records to test pagination
4. **Delete**: Test referential integrity (cannot delete used variables)
5. **Mobile View**: Test on different screen sizes

## 📝 Technical Requirements Met

### Mandatory Requirements ✅
- ✅ HTML5 with semantic elements
- ✅ W3C compliant code
- ✅ CSS Grid and Flexbox
- ✅ PHP with MySQL (MySQLi)
- ✅ Prepared statements for security
- ✅ Mobile-responsive forms (media queries)
- ✅ Desktop data display

### Additional Features ✅
- ✅ PHP sessions for application state
- ✅ Chart.js for data visualization
- ✅ Sortable/filterable tables
- ✅ Date filtering and statistics
- ✅ CRUD operations for variables
- ✅ Clean, maintainable code structure

### NOT Using ❌
- ❌ PHP frameworks (Laravel, Symfony)
- ❌ CSS frameworks (Bootstrap) - handwritten CSS only

## 🚀 Deployment

### Local Deployment (XAMPP/LAMPP)
Already configured for local deployment at `/opt/lampp/htdocs/myproject/final/`

### Remote Server Deployment
1. Upload all files to web server via FTP/SSH
2. Import `database/schema.sql` to remote MySQL
3. Update `config/database.php` with remote credentials
4. Ensure PHP 7.4+ and MySQL 5.7+ are available
5. Set proper file permissions (755 for directories, 644 for files)

### Production Considerations
- Enable HTTPS/SSL
- Use environment variables for sensitive data
- Enable error logging (disable display_errors)
- Implement rate limiting
- Add user authentication system
- Regular database backups

## 🐛 Troubleshooting

### Database Connection Error
- Check MySQL is running: `sudo /opt/lampp/lampp status`
- Verify credentials in `config/database.php`
- Ensure database exists: `SHOW DATABASES;`

### Charts Not Displaying
- Check browser console for JavaScript errors
- Ensure CDN is accessible: `https://cdn.jsdelivr.net/npm/chart.js`
- Clear browser cache

### Form Submission Issues
- Check PHP error log: `/opt/lampp/logs/error_log`
- Verify all required fields are filled
- Check database constraints

## 📖 Future Enhancements

Potential additions:
- User authentication and multi-user support
- Export data to PDF/Excel
- Import data from CSV
- Email notifications/reminders
- Mobile app version
- Real-time location tracking
- Supervisor/instructor feedback system
- Achievement badges and milestones
- Weather API integration for auto-population

## 👨‍💻 Development

### Code Standards
- PSR-12 coding standards for PHP
- BEM methodology for CSS naming
- Semantic commit messages
- Inline documentation for complex logic

### Database Optimization
- Indexes on frequently queried columns
- Normalized structure (3NF)
- Prepared statements for all queries
- Connection pooling via Singleton pattern

## 📄 License

This is an educational project for coursework. Feel free to use and modify for learning purposes.

## 🙏 Acknowledgments

- Chart.js for data visualization
- PHP Documentation
- MySQL Documentation
- W3C Web Standards

---

**Version**: 1.0.0  
**Date**: December 2025  
**Author**: Student Project  
**Course**: Web Development - Supervised Driving Experience Management System
