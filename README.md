# 🎓 EduTrack – Student Management System

A full-stack web app for managing students and courses, built with **PHP (PDO) + MySQL + Tailwind CSS + Vanilla JS**.

**Live site:** `https://marcgajo.infinityfreeapp.com`  

## 🔑 Test Credentials (for grading)

| Role    | Email                | Password    |
|---------|----------------------|-------------|
| Admin   | admin@school.test    | Admin@123   |
| Student | *Register at /register.php or create via Admin → Students* | — |

## ✨ Features

- **Authentication & RBAC** – registration, login, logout; roles: `admin` and `student`
- **Passwords** hashed with `password_hash()` / `password_verify()`; session ID regenerated on login; HttpOnly + SameSite cookies
- **Students CRUD** (admin) – create, read (search, filter by course/status, pagination), update, delete
- **Courses CRUD** (admin) – delete is blocked when students are enrolled (foreign key `RESTRICT`)
- **Admin dashboard** – totals, students-per-course bars, recently added students
- **Student dashboard & profile** – students see their own record, edit contact info, change password
- **Validation** – HTML5 + JavaScript (client) and PHP (server); async email-availability check using `fetch()`
- **Security** – PDO prepared statements (SQL injection), `htmlspecialchars` output escaping (XSS), CSRF tokens on every POST form, role checks on every admin page
- **Responsive UI** – Tailwind CSS, collapsible sidebar on mobile, scrollable tables on small screens

## 🗂 Structure

```
/database/schema.sql      Tables + seed data (admin, courses, sample students)
/includes/config.php      DB credentials (edit this)
/includes/functions.php   PDO, sessions, auth, CSRF, helpers
/includes/header.php      Layout + navigation   /includes/footer.php
/assets/js/app.js         Client-side validation & UI scripts
login.php register.php logout.php check_email.php
dashboard.php students.php student_form.php courses.php profile.php
```

## 🚀 Deploy on InfinityFree

1. Create an account at infinityfree.com → **Create Account** with subdomain `firstname-lastname` (e.g. `juan-dela-cruz.infinityfreeapp.com`).
2. In the control panel → **MySQL Databases** → create a database. Note the host (e.g. `sql123.infinityfree.com`), db name, username, password.
3. Open **phpMyAdmin** → select your database → **Import** → upload `database/schema.sql`.
4. Edit `includes/config.php` with the credentials from step 2.
5. Upload all files (everything except `/database` is needed) into the **`htdocs`** folder using File Manager or FTP.
6. Visit your URL and log in with the admin credentials above.

## 💻 Run locally (XAMPP / Laragon)

1. Copy the folder to `htdocs`, start Apache + MySQL.
2. Create database `sms_db` in phpMyAdmin and import `database/schema.sql`.
3. Keep default `includes/config.php` (root, empty password) → open `http://localhost/sms/`.

## 🧰 Tech Stack

HTML5 · CSS3 + Tailwind CSS (CDN) · JavaScript (ES6) · PHP 8 · MySQL/MariaDB
