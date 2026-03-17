# Hotel Procurement System - Complete Requirements & Setup Guide

## System Requirements

### 1. Server Requirements

#### Web Server
- **Apache** 2.4+ (or Nginx)
- **PHP** 7.4 or higher (8.0+ recommended)
- **MySQL** 5.7+ or **MariaDB** 10.3+

#### PHP Extensions Required
- `pdo` - PHP Data Objects
- `pdo_mysql` - MySQL PDO driver
- `json` - JSON support
- `mbstring` - Multibyte string support (recommended)
- `openssl` - For secure password hashing

#### Recommended Setup
- **XAMPP** (Windows/Mac/Linux) - Includes Apache, PHP, MySQL
- **WAMP** (Windows) - Alternative to XAMPP
- **MAMP** (Mac) - Alternative to XAMPP
- **LAMP** (Linux) - Apache, MySQL, PHP stack

### 2. Database Requirements

#### Database Configuration
- **Database Name**: `sekum_db`
- **Character Set**: `utf8mb4`
- **Collation**: `utf8mb4_unicode_ci`
- **MySQL User**: `root` (default XAMPP)
- **MySQL Password**: (empty by default in XAMPP)

#### Database Tables Required
1. **users** - User authentication and management
2. **vendors** - Vendor information and registration
3. **orders** - Order management and tracking

### 3. File Structure Requirements

```
Procurement/
├── index.html              # Main application interface
├── login.php               # Login API endpoint
├── db.php                  # Database connection configuration
├── users.php               # User management API
├── vendors.php             # Vendor management API
├── orders.php              # Order management API
├── setup_database.php      # Database setup script
├── setup_database.html     # Database setup interface
├── setup_admin.html        # Admin user creation interface
├── database.sql            # Complete database schema
├── css/
│   └── style.css          # Stylesheet (if exists)
└── js/                     # JavaScript files (if exists)
```

### 4. Browser Requirements

- **Modern browsers** with JavaScript enabled:
  - Chrome 90+
  - Firefox 88+
  - Edge 90+
  - Safari 14+
- **JavaScript** must be enabled
- **Local Storage** support required (for inventory management)

### 5. Network Requirements

- **Local Development**: `http://localhost/Procurement/`
- **CORS**: Configured for cross-origin requests (if needed)
- **API Endpoints**: All PHP files must be accessible via HTTP

## Database Schema Details

### Table: users
**Purpose**: Store user accounts and authentication information

**Columns**:
- `id` (INT, PRIMARY KEY, AUTO_INCREMENT)
- `username` (VARCHAR(50), UNIQUE, NOT NULL)
- `password` (VARCHAR(255), NOT NULL) - Hashed using PHP password_hash()
- `email` (VARCHAR(100), UNIQUE, NOT NULL)
- `role` (ENUM: 'admin', 'vendor', 'customer', DEFAULT 'vendor')
- `created_at` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)

**Indexes**:
- PRIMARY KEY on `id`
- UNIQUE on `username`
- UNIQUE on `email`

### Table: vendors
**Purpose**: Store vendor registration and product information

**Columns**:
- `id` (INT, PRIMARY KEY, AUTO_INCREMENT)
- `name` (VARCHAR(100), NOT NULL)
- `contact_info` (VARCHAR(255))
- `id_number` (VARCHAR(50), UNIQUE)
- `supply_items` (TEXT) - JSON array of items vendor can supply
- `items` (TEXT) - Additional items information
- `prices` (TEXT) - Pricing information
- `specifications` (TEXT) - Product specifications
- `size` (VARCHAR(50))
- `color` (VARCHAR(50))
- `location` (VARCHAR(255))
- `delivery_method` (VARCHAR(100))
- `delivery_date` (DATE)
- `payment_method` (VARCHAR(100))
- `payment_details` (TEXT) - JSON object with payment details
- `images` (TEXT) - JSON array of image URLs/paths
- `status` (ENUM: 'pending', 'approved', 'declined', DEFAULT 'pending')
- `user_id` (INT, FOREIGN KEY to users.id)
- `created_at` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)

**Indexes**:
- PRIMARY KEY on `id`
- UNIQUE on `id_number`
- FOREIGN KEY on `user_id` REFERENCES `users(id)` ON DELETE SET NULL

### Table: orders
**Purpose**: Store order information and tracking

**Columns**:
- `id` (INT, PRIMARY KEY, AUTO_INCREMENT)
- `vendor_id` (INT, NOT NULL, FOREIGN KEY to vendors.id)
- `user_id` (INT) - Optional user who placed order
- `product` (VARCHAR(100), NOT NULL)
- `quantity` (INT, NOT NULL)
- `unit_price` (DECIMAL(10,2), NOT NULL)
- `total_price` (DECIMAL(10,2), NOT NULL)
- `specifications` (TEXT)
- `size` (VARCHAR(50))
- `color` (VARCHAR(50))
- `payment_method` (VARCHAR(50))
- `payment_details` (TEXT) - JSON object
- `vendor_id_number` (VARCHAR(50))
- `location` (VARCHAR(255))
- `delivery_method` (VARCHAR(100))
- `delivery_date` (DATE)
- `deadline` (DATE)
- `status` (ENUM: 'pending', 'processing', 'completed', 'cancelled', DEFAULT 'pending')
- `payment_status` (ENUM: 'pending', 'paid', DEFAULT 'pending')
- `order_date` (DATETIME, DEFAULT CURRENT_TIMESTAMP)

**Indexes**:
- PRIMARY KEY on `id`
- FOREIGN KEY on `vendor_id` REFERENCES `vendors(id)` ON DELETE CASCADE

## Setup Instructions

### Step 1: Install Web Server Stack
1. Download and install **XAMPP** from https://www.apachefriends.org/
2. Start **Apache** and **MySQL** services from XAMPP Control Panel
3. Verify MySQL is running on port 3306

### Step 2: Configure Database Connection
1. Open `db.php`
2. Verify database credentials match your MySQL setup:
   ```php
   $host = 'localhost';
   $db   = 'sekum_db';
   $user = 'root';
   $pass = '';
   ```

### Step 3: Setup Database
**Option A: Using Setup Script (Recommended)**
1. Place all files in `C:\xampp\htdocs\Procurement\`
2. Open browser: `http://localhost/Procurement/setup_database.html`
3. Click "Setup Database" button
4. Wait for success message

**Option B: Manual Setup**
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Create database: `sekum_db`
3. Import `database.sql` file
4. Or run SQL commands manually

### Step 4: Create Admin User
1. After database setup, go to: `http://localhost/Procurement/setup_admin.html`
2. Default credentials are pre-filled:
   - Username: `admin`
   - Password: `admin123`
   - Email: `admin@hotel.com`
3. Click "Create Admin User"
4. Note: Change password after first login in production!

### Step 5: Access Application
1. Open: `http://localhost/Procurement/index.html`
2. Login with admin credentials
3. System is ready to use!

## Security Considerations

### Development Environment
- Default MySQL password is empty (acceptable for local development)
- Admin credentials are default (change in production!)

### Production Environment
1. **Change MySQL password** - Set strong password in `db.php`
2. **Change admin password** - Use strong, unique password
3. **Enable HTTPS** - Use SSL certificate
4. **File permissions** - Restrict access to sensitive files
5. **Input validation** - Already implemented in PHP files
6. **SQL injection protection** - Using prepared statements (already implemented)
7. **Password hashing** - Using PHP `password_hash()` (already implemented)

## Troubleshooting

### Database Connection Failed
- Check MySQL service is running
- Verify credentials in `db.php`
- Check MySQL port (default: 3306)
- Verify database `sekum_db` exists

### Login Not Working
- Verify admin user exists in database
- Check `login.php` file path is correct
- Check browser console for JavaScript errors
- Verify API endpoint returns JSON

### Tables Not Created
- Check MySQL user has CREATE TABLE permissions
- Verify database exists before running setup
- Check for SQL syntax errors in setup script
- Review PHP error logs

### API Endpoints Not Responding
- Verify PHP files are in correct location
- Check Apache is running
- Verify file permissions (readable)
- Check PHP error logs: `C:\xampp\php\logs\php_error_log`

## Features Overview

### Admin Features
- User management (create, update, delete users)
- Vendor registration approval/decline
- Order management and tracking
- Inventory management (localStorage-based)
- Stock control and monitoring
- Payment tracking
- Delivery management

### Vendor Features (if implemented)
- Vendor registration
- Product submission
- Order viewing

## API Endpoints

### Authentication
- `POST /login.php` - User login

### Users
- `GET /users.php` - List all users
- `GET /users.php?id={id}` - Get user by ID
- `POST /users.php` - Create new user
- `PUT /users.php?id={id}` - Update user
- `DELETE /users.php?id={id}` - Delete user

### Vendors
- `GET /vendors.php` - List all vendors
- `GET /vendors.php?id={id}` - Get vendor by ID
- `POST /vendors.php` - Create new vendor
- `PUT /vendors.php?id={id}` - Update vendor
- `DELETE /vendors.php?id={id}` - Delete vendor

### Orders
- `GET /orders.php` - List all orders
- `GET /orders.php?id={id}` - Get order by ID
- `POST /orders.php` - Create new order
- `PUT /orders.php?id={id}` - Update order
- `DELETE /orders.php?id={id}` - Delete order

## Support & Maintenance

### Regular Maintenance Tasks
1. Backup database regularly
2. Monitor error logs
3. Update PHP and MySQL versions
4. Review and rotate passwords
5. Clean up old orders/vendors as needed

### Backup Database
```bash
mysqldump -u root -p sekum_db > backup_$(date +%Y%m%d).sql
```

### Restore Database
```bash
mysql -u root -p sekum_db < backup_YYYYMMDD.sql
```
