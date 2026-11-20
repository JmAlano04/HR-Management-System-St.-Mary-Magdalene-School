# St. Mary Magdalene School – HRMS

A web-based **Human Resource Management System (HRMS)** with **biometric attendance** built for St. Mary Magdalene School, Kawit, Cavite. It centralizes employee records, attendance, payroll support, and recruitment in one platform with role-based access.

> 🎓 **Capstone Project** – Submitted in partial fulfillment of the requirements for the degree of `<Bachelor of Science in Information Technology / Computer Science>`, `<University / College Name>`, `<School Year, e.g. 2025–2026>`.
>
> Built with **Laravel**.
---

## Table of Contents

- [Features](#features)
- [User Roles](#user-roles)
- [Tech Stack](#tech-stack)
- [Requirements](#requirements)
- [Installation (Step by Step)](#installation-step-by-step)
- [Biometric Attendance Setup](#biometric-attendance-setup)
- [Default Accounts](#default-accounts)
- [Running in Production](#running-in-production)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)
- [License](#license)

---

## Features

- Role-based access control (Admin, HR, Accounting, Employee, Applicant)
- Biometric attendance logging (time in / time out)
- Employee 201 file / profile management
- Leave and attendance monitoring
- Payroll support and accounting reports
- Recruitment portal: job postings, online application, applicant tracking
- Secure login and authentication
- Responsive interface (desktop and mobile)

## User Roles

| Role | Access |
|------|--------|
| **Admin** | Full system control: users, roles, settings, all modules |
| **HR** | Employee records, attendance, leave, job postings, applicant review |
| **Accounting** | Payroll, deductions, financial and attendance-based reports |
| **Employee** | Personal profile, own attendance records, leave requests, payslips |
| **Applicant** | Register, view openings, submit application and requirements |

## Tech Stack

- **Backend:** Laravel `12` (PHP `8.3`)
- **Database:** MySQL
- **Frontend:** Blade, Tailwind CSS, Vite
- **Attendance:** Biometric device integration `ZKTeco x-628-c`

## Requirements

Make sure these are installed first:

- PHP **8.3** or higher (check `composer.json` for the exact version)
- Composer 2.x
- Node.js 18+ and npm
- MySQL 8+ or MariaDB 10.4+
- Git
- PHP extensions: `openssl`, `pdo`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd`

Local options: **XAMPP**, **Laragon** (recommended on Windows), or **Laravel Herd**.

---

## Installation (Step by Step)

### 1. Clone the repository

```bash
git clone https://github.com/JmAlano04/HR-Management-System-St.-Mary-Magdalene-School.git
cd <your-repo>
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Install frontend dependencies

```bash
npm install
```

### 4. Create the environment file

```bash
cp .env.example .env
```

On Windows Command Prompt use: `copy .env.example .env`

### 5. Generate the application key

```bash
php artisan key:generate
```

### 6. Create the database

Create an empty database (for example `smms_db`) using phpMyAdmin, HeidiSQL, or the MySQL CLI:

upload the sql file into database, go in database folder

```sql
CREATE DATABASE smms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 7. Configure `.env`

Open `.env` and update:

```env
APP_NAME="SMMS HRMS"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=Asia/Manila

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smms_db
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=smtp
MAIL_HOST=<smtp-host>
MAIL_PORT=587
MAIL_USERNAME=<email>
MAIL_PASSWORD=<password>
MAIL_FROM_ADDRESS=<email>
MAIL_FROM_NAME="${APP_NAME}"
```

### 8. Run migrations and seeders

```bash
php artisan migrate --seed
```

This creates all tables and seeds the default roles and accounts.

### 9. Link the storage folder

```bash
php artisan storage:link
```

Needed for uploaded files such as profile photos and applicant requirements.

### 10. Build frontend assets

```bash
npm run build
```

For development with hot reload use `npm run dev` instead.

### 11. Start the application

```bash
php artisan serve
```

Open **http://localhost:8000** in your browser.

---

## Biometric Attendance Setup

> Adjust this section to match your device and integration method.

1. Connect the biometric device to the same network as the server.
2. Set the device IP and port in `.env`:

```env
BIOMETRIC_IP=192.168.10.201
BIOMETRIC_PORT=4370
```

3. Make sure the server can reach the device (`ping 192.168.10.201`).
4. Register each employee in the device using the same **Employee ID** as in the HRMS.
5. Sync attendance logs (if using a command or scheduler):

```bash
php artisan attendance:sync
```

6. To sync automatically, add the Laravel scheduler to cron (Linux):

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

On Windows, create a Task Scheduler job that runs `php artisan schedule:run` every minute.

## Default Accounts

After seeding, you can log in with the accounts below. **Change all passwords immediately after first login.**

| Role | Email | Password |
|------|-------|----------|
| Admin | `smms.administrator00@gmail.com` | `password123` |
| HR | `smms.hr00@gmail.com` | `password123` |
| Accounting | `smms.finance.officer@gmail.com` | `password123` |
| Employee | `juan.delacruz@gmail.com` | `password123` |

Applicants register through the **Apply Now** button on the landing page.

> Update this table to match your actual seeders.

---

## Running in Production

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run build
```

Set these in `.env`:

```env
APP_ENV=production
APP_DEBUG=false
```

Point your web server (Nginx or Apache) document root to the `/public` folder, and make `storage` and `bootstrap/cache` writable:

```bash
chmod -R 775 storage bootstrap/cache
```

## Troubleshooting

| Problem | Fix |
|---------|-----|
| `No application encryption key has been specified` | Run `php artisan key:generate` |
| `SQLSTATE[HY000] [1049] Unknown database` | Create the database and check `DB_DATABASE` in `.env` |
| Images/uploads not showing | Run `php artisan storage:link` |
| Styles not loading | Run `npm install && npm run build` |
| Permission denied on `storage` | `chmod -R 775 storage bootstrap/cache` |
| Changes in `.env` not applied | Run `php artisan config:clear` |
| Class not found after pulling updates | Run `composer dump-autoload` |

## Contributing

1. Fork the repository
2. Create a branch: `git checkout -b feature/your-feature`
3. Commit your changes: `git commit -m "Add your feature"`
4. Push to the branch: `git push origin feature/your-feature`
5. Open a Pull Request

## Capstone Information

| | |
|---|---|
| **Project Title** | Human Resource Management System with Biometric Attendance for St. Mary Magdalene School |
| **Client / Partner Institution** | St. Mary Magdalene School (Parochial), Inc., Kawit, Cavite |
| **University / College** | `<University / College Name>` |
| **Program** | `<Course / Program>` |
| **Academic Year** | `<2025–2026>` |
| **Capstone Adviser** | `<Adviser Name>` |

## Development Team

| Name | Role | GitHub |
|------|------|--------|
| `<Full Name>` | Lead Developer | [@username](https://github.com/JmAlano04) |
| `<Full Name>` | Backend Developer | [@username](https://github.com/JmAlano04) |
| `<Full Name>` | UI/UX Designer / Documenter | [@username](https://github.com/JmAlano04) |

**Contact:** `jenmaralano11@gmail.com`

---

**A capstone project developed for St. Mary Magdalene School, Kawit, Cavite.**