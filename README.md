# PHP MySQL Vendor/Order/User Management System

## Features
- User, Vendor, and Order management
- PHP backend with MySQL database
- RESTful API endpoints for CRUD operations
- Example JavaScript API usage

## Setup

### 1. Database
- Import `database.sql` into your MySQL server:
  ```
  mysql -u your_user -p your_db < database.sql
  ```
- Update `api/db.php` with your database credentials.

### 2. Backend (PHP)
- Place the `api/` folder in your web server root (e.g., `htdocs` for XAMPP).
- Ensure PHP and PDO MySQL extension are enabled.

### 3. Frontend
- Use the provided HTML files or create your own.
- See `js/api-example.js` for how to connect to the backend using JavaScript.

## API Endpoints
- `api/users.php` (CRUD for users)
- `api/vendors.php` (CRUD for vendors)
- `api/orders.php` (CRUD for orders)

## Example JavaScript Usage
```js
import { getVendors, addVendor } from './js/api-example.js';

getVendors().then(console.log);
addVendor({ name: 'Test Vendor', contact_info: 'test@example.com' }).then(console.log);
```

## Security Notes
- Passwords are hashed using PHP's `password_hash`.
- Always validate and sanitize user input in production.
- Use HTTPS in production environments. 

## Deploy On Render

This project can run on Render as a Docker web service.

### 1. Keep PHP on Render, keep MySQL elsewhere
- Render can run the PHP app with Docker.
- This app still needs a MySQL database.
- If you deploy to Render, use an external MySQL provider and copy its credentials into Render environment variables.

### 2. Required Render environment variables
- `DB_HOST`
- `DB_PORT`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`

Local XAMPP still works because `db.php` falls back to:
- Host: `localhost`
- Port: `3306`
- Database: `sekum_db`
- User: `root`
- Password: empty

### 3. Render setup
1. Push this project to GitHub.
2. In Render, create a new `Web Service`.
3. Connect the repository.
4. Choose the Docker runtime. Render will build from the root `Dockerfile`.
5. Add the database environment variables listed above.
6. Deploy the service.

### 4. App URL
- The root URL now redirects to `frontend/index.html`, so your Render domain opens the main app directly.
- Frontend pages call the API through the `backend/` path, for example `backend/login.php`.
