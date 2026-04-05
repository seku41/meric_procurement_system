# Hotel Procurement System

PHP + MySQL procurement app with frontend pages in `frontend/` and PHP APIs in `backend/`.

## Local development

Local XAMPP still works with the defaults in [backend/db.php](/c:/xampp/htdocs/Procurement/backend/db.php):

- `DB_HOST=localhost`
- `DB_PORT=3306`
- `DB_NAME=sekum_db`
- `DB_USER=root`
- `DB_PASS=`
- `DB_SSL_MODE=disable`

## Environment variables

The app accepts both `DB_USER`/`DB_PASS` and `DB_USERNAME`/`DB_PASSWORD`.

```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=sekum_db
DB_USER=root
DB_PASS=
DB_SSL_MODE=disable
DB_CHARSET=utf8mb4
APP_ENV=local
APP_URL=http://localhost/Procurement
MPESA_ENV=sandbox
MPESA_CONSUMER_KEY=
MPESA_CONSUMER_SECRET=
MPESA_SHORTCODE=
MPESA_PASSKEY=
MPESA_CALLBACK_URL=
MPESA_TRANSACTION_TYPE=CustomerPayBillOnline
```

For Aiven on Render, use:

```env
DB_HOST=your-aiven-host
DB_PORT=your-aiven-port
DB_NAME=defaultdb
DB_USER=avnadmin
DB_PASS=your-password
DB_SSL_MODE=require
DB_SSL_VERIFY=false
DB_CHARSET=utf8mb4
APP_ENV=production
APP_URL=https://your-service.onrender.com
MPESA_ENV=sandbox
MPESA_CONSUMER_KEY=your-daraja-consumer-key
MPESA_CONSUMER_SECRET=your-daraja-consumer-secret
MPESA_SHORTCODE=your-test-shortcode
MPESA_PASSKEY=your-daraja-passkey
MPESA_TRANSACTION_TYPE=CustomerPayBillOnline
```

## Deploy on Render + Aiven

### 1. Create the free Aiven MySQL service

1. Sign in to Aiven.
2. Create a new `MySQL` service on the free plan.
3. Copy the service `host`, `port`, `database`, `username`, and `password`.
4. Make sure the target database already exists in Aiven before running this app's setup page.
5. In Aiven networking, allow access from Render. On the free tier you may need to allow `0.0.0.0/0` during setup.

### 2. Push the project to GitHub

1. Create a GitHub repository.
2. Push this project.
3. Keep the root [Dockerfile](/c:/xampp/htdocs/Procurement/Dockerfile) and existing `render.yaml` in the repo.

### 3. Create the Render web service

1. In Render, click `New +` then `Web Service`.
2. Connect the GitHub repository.
3. Choose the `Docker` runtime.
4. Use the free instance type.
5. Add these environment variables in Render:

```env
DB_HOST=your-aiven-host
DB_PORT=your-aiven-port
DB_NAME=your-aiven-database
DB_USER=your-aiven-username
DB_PASS=your-aiven-password
DB_SSL_MODE=require
DB_SSL_VERIFY=false
DB_CHARSET=utf8mb4
APP_ENV=production
```

### 4. Deploy the app

1. Trigger the first Render deploy.
2. Open the Render URL.
3. The root URL is redirected by [index.php](/c:/xampp/htdocs/Procurement/index.php) to `/frontend/index.html`.

### 5. Create the tables

Option A: use the app

1. Open `https://your-service.onrender.com/frontend/setup_database.html`
2. Click `Setup Database`
3. Open `https://your-service.onrender.com/frontend/setup_admin.html`
4. Create the first admin user
5. Log in at `https://your-service.onrender.com/frontend/index.html`

Option B: import your existing local data

1. Export your local XAMPP database:
   ```powershell
   mysqldump -u root -p sekum_db > procurement.sql
   ```
2. Import into Aiven:
   ```powershell
   mysql --host=YOUR_HOST --port=YOUR_PORT --user=YOUR_USER --password --ssl-mode=REQUIRED YOUR_DB < procurement.sql
   ```

### 6. Verify

Check:

- `/`
- `/frontend/index.html`
- `/frontend/vendors.html`
- admin login from the browser UI

## M-Pesa sandbox setup

1. Create a Daraja app in the Safaricom developer portal and choose the sandbox credentials.
2. Copy your `consumer key`, `consumer secret`, `shortcode`, and `passkey`.
3. In Render, add:

```env
APP_URL=https://your-service.onrender.com
MPESA_ENV=sandbox
MPESA_CONSUMER_KEY=...
MPESA_CONSUMER_SECRET=...
MPESA_SHORTCODE=...
MPESA_PASSKEY=...
MPESA_TRANSACTION_TYPE=CustomerPayBillOnline
```

4. Leave `MPESA_CALLBACK_URL` empty if `APP_URL` is correct. The app will use:
   `https://your-service.onrender.com/backend/mpesa.php?action=callback`
5. In the admin dashboard orders screen, use `Pay M-Pesa` on an unpaid order.
6. Enter the sandbox phone number that should receive the STK push.
7. After Safaricom calls the callback URL, the order is updated to `paid` automatically when the sandbox payment succeeds.

## Notes

- [backend/setup_database.php](/c:/xampp/htdocs/Procurement/backend/setup_database.php) now creates tables inside the configured database instead of trying to create a new database on the hosted server.
- If your MySQL provider requires a custom CA file, set `DB_SSL_CA` to the absolute path of that CA bundle inside the container and set `DB_SSL_VERIFY=true`.
- M-Pesa sandbox endpoints are handled in [backend/mpesa.php](/c:/xampp/htdocs/Procurement/backend/mpesa.php) and configured by [backend/mpesa_config.php](/c:/xampp/htdocs/Procurement/backend/mpesa_config.php).
