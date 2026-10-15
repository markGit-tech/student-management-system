# Student Management System

Live site: https://marcgajo.infinityfreeapp.com

## Admin Test Credentials

- Email: admin@school.test
- Password: Admin@123

## How to Register a Student Account

1. Open https://marcgajo.infinityfreeapp.com/register.php (or click "Register as student" on the login page).
2. Fill in your first name, last name, email, course, and password (at least 8 characters, with letters and numbers).
3. Click **Register**.
4. Log in at the login page using the email and password you just created.
5. A student account can view its own information and edit its contact details and password.

Note: Admins can also create student records (with an optional login) from **Students > Add Student**.

## Project Structure

```
database/schema.sql     - database tables and sample data
includes/               - config, helper functions, header, footer
assets/js/app.js        - client-side scripts
login.php, register.php, logout.php, check_email.php
dashboard.php, students.php, student_form.php, courses.php, profile.php
```
