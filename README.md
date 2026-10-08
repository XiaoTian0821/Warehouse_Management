# Warehouse Management System

A simple, secure, and professional Warehouse Management System built with PHP 8+, MySQL 8+, and vanilla JavaScript.

## Features

- **User Authentication** - Secure login with hashed passwords and session management
- **Role-Based Access Control** - Admin (full access) and User (read-only) roles
- **Item Management** - Add, edit, delete, search, and filter inventory items
- **Stock Movements** - Record stock IN/OUT with full audit trail
- **Low Stock Alerts** - Visual indicators for items below reorder level
- **Reports** - Filterable stock movement reports with print support
- **User Management** - Admin can add, edit, delete, and manage user accounts
- **API** - JSON API for item search (authenticated)
- **CSRF Protection** - All state-changing POST requests are protected
- **Responsive Design** - Works on desktop, tablet, and mobile

## Technology Stack

- **Backend**: PHP 8+, MySQL 8+, PDO
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **No frameworks** - Pure, lightweight, easy to maintain

## Installation

### Requirements

- PHP 8.0+
- MySQL 8.0+
- Apache (with mod_rewrite) or Nginx
- XAMPP or similar local development environment

### Step 1: Database Setup

1. Start XAMPP (Apache + MySQL)
2. Open phpMyAdmin (`http://localhost/phpmyadmin`)
3. Create a new database named `warehouse_management`
4. Import the SQL file: `database/schema.sql`

### Step 2: Configuration

1. Copy the project to your web root (e.g., `C:\xampp\htdocs\Warehouse_Management\`)
2. Edit `config/config.php` if needed:
   - Update `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`
   - Update `APP_URL` and `BASE_PATH` for your installation

### Step 3: Access the Application

Open your browser and navigate to:
```
http://localhost/Warehouse_Management/
```

## Default Accounts

| Role | Username | Password |
|------|----------|----------|
| Admin | `admin` | `admin123` |
| User | `demo` | `user123` |

> ⚠️ **Important**: Change these passwords before using in production!

## Project Structure

```
Warehouse_Management/
│
├── admin/                  # Admin section
│   ├── dashboard.php
│   ├── items.php
│   ├── movements.php
│   ├── reports.php
│   └── users.php
│
├── api/                    # JSON API
│   └── items.php
│
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── app.js
│
├── config/                 # Configuration
│   ├── config.php
│   └── database.php
│
├── database/               # Database schema
│   └── schema.sql
│
├── includes/               # Shared PHP includes
│   ├── auth.php
│   ├── footer.php
│   ├── functions.php
│   ├── header.php
│   └── init.php
│
├── user/                   # User section (read-only)
│   ├── dashboard.php
│   ├── item-details.php
│   └── items.php
│
├── .htaccess
├── index.php
├── login.php
├── logout.php
└── README.md
```

## Security Features

- ✅ Password hashing with `password_hash()` / `password_verify()`
- ✅ SQL injection protection with PDO prepared statements
- ✅ XSS protection with `htmlspecialchars()` output escaping
- ✅ CSRF token protection on all POST forms
- ✅ Session security with regeneration and HTTP-only cookies
- ✅ Server-side role enforcement (never rely on UI hiding)
- ✅ Input validation and sanitization
- ✅ Database credentials not exposed in page files
- ✅ Protected directories with `.htaccess`

## Development Principles

- Simple, clean, and well-commented code
- No unnecessary complexity or frameworks
- Easy to understand for another developer or AI
- Suitable for both XAMPP and cPanel shared hosting
- Backward compatible - don't break existing functionality

## Future Extensions (Not Yet Implemented)

- Suppliers management
- Purchase Orders
- Goods Receiving
- Multiple warehouses
- Stock transfers
- Barcode/QR code scanning
- CSV/Excel export
- PDF reports
- Email alerts for low stock
- User activity audit logs
- Dashboard charts

## License

This project is provided as-is for educational and commercial use.
