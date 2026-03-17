# Quick Setup Checklist

## ✅ Pre-Installation Requirements

- [ ] **XAMPP/WAMP/MAMP** installed and running
- [ ] **Apache** service started
- [ ] **MySQL** service started
- [ ] **PHP 7.4+** installed (included in XAMPP)
- [ ] Project files copied to `C:\xampp\htdocs\Procurement\`

## ✅ Database Setup

### Option 1: Automated Setup (Recommended)
- [ ] Open browser: `http://localhost/Procurement/setup_database.html`
- [ ] Click **"Setup Database"** button
- [ ] Verify success message appears
- [ ] All 3 tables (users, vendors, orders) created

### Option 2: Manual Setup
- [ ] Open phpMyAdmin: `http://localhost/phpmyadmin`
- [ ] Create database: `sekum_db`
- [ ] Import `database.sql` file
- [ ] Verify tables exist

## ✅ Admin User Creation

- [ ] Go to: `http://localhost/Procurement/setup_admin.html`
- [ ] Default credentials are pre-filled:
  - Username: `admin`
  - Password: `admin123`
  - Email: `admin@hotel.com`
- [ ] Click **"Create Admin User"**
- [ ] Verify success message

## ✅ Configuration Check

- [ ] Verify `db.php` has correct credentials:
  ```php
  $host = 'localhost';
  $db   = 'sekum_db';
  $user = 'root';
  $pass = '';
  ```
- [ ] Verify `login.php` exists in same folder as `index.html`
- [ ] Check `index.html` uses `fetch('login.php')` (not `api/login.php`)

## ✅ Testing

- [ ] Open: `http://localhost/Procurement/index.html`
- [ ] Login with admin credentials
- [ ] Verify dashboard loads
- [ ] Test vendor creation
- [ ] Test order creation

## ✅ Troubleshooting

If login fails:
- [ ] Check browser console for errors (F12)
- [ ] Verify MySQL is running
- [ ] Check admin user exists: `SELECT * FROM users WHERE username='admin'`
- [ ] Verify `login.php` file path is correct
- [ ] Check PHP error logs

If database setup fails:
- [ ] Verify MySQL service is running
- [ ] Check MySQL user has CREATE permissions
- [ ] Verify port 3306 is not blocked
- [ ] Check MySQL error logs

## 📋 System Requirements Summary

| Component | Requirement |
|-----------|-------------|
| Web Server | Apache 2.4+ |
| PHP | 7.4+ (8.0+ recommended) |
| MySQL | 5.7+ or MariaDB 10.3+ |
| PHP Extensions | PDO, PDO_MySQL, JSON |
| Browser | Modern browser with JavaScript enabled |
| Storage | LocalStorage support (for inventory) |

## 🎯 Quick Start Commands

### Start XAMPP Services
```
1. Open XAMPP Control Panel
2. Click "Start" for Apache
3. Click "Start" for MySQL
```

### Access Points
- **Main App**: `http://localhost/Procurement/index.html`
- **Database Setup**: `http://localhost/Procurement/setup_database.html`
- **Admin Setup**: `http://localhost/Procurement/setup_admin.html`
- **phpMyAdmin**: `http://localhost/phpmyadmin`

### Default Credentials
- **Username**: `admin`
- **Password**: `admin123`
- **Email**: `admin@hotel.com`

⚠️ **Important**: Change default password in production!

## 📚 Documentation

For detailed information, see:
- `SYSTEM_REQUIREMENTS.md` - Complete system documentation
- `database.sql` - Complete database schema
- `setup_database.php` - Database setup script
