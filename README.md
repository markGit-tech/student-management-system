# 🎓 EduTrack – Student Management System

A full-stack web app for managing students and courses, built with **PHP (PDO) + MySQL + Tailwind CSS + Vanilla JS**.

**Live site:** `https://marcgajo.infinityfreeapp.com`  

## 🔑 Test Credentials (for grading)

| Role    | Email                | Password    |
|---------|----------------------|-------------|
| Admin   | admin@school.test    | Admin@123   |
| Student | *Register at /register.php or create via Admin → Students* | — |


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



## 💻 Run locally (XAMPP / Laragon)

1. Copy the folder to `htdocs`, start Apache + MySQL.
2. Create database `sms_db` in phpMyAdmin and import `database/schema.sql`.
3. Keep default `includes/config.php` (root, empty password) → open `http://localhost/sms/`.
