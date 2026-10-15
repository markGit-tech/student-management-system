-- Student Management System schema + seed data
-- Import via phpMyAdmin (select your database first, then Import).
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,              -- bcrypt hash from password_hash()
  role ENUM('admin','student') NOT NULL DEFAULT 'student',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS courses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) NOT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS students (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED DEFAULT NULL,
  student_no VARCHAR(20) NOT NULL UNIQUE,
  first_name VARCHAR(60) NOT NULL,
  last_name VARCHAR(60) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  phone VARCHAR(20) DEFAULT NULL,
  birthdate DATE DEFAULT NULL,
  gender ENUM('Male','Female','Other') DEFAULT NULL,
  address VARCHAR(255) DEFAULT NULL,
  course_id INT UNSIGNED NOT NULL,
  year_level TINYINT UNSIGNED NOT NULL DEFAULT 1,
  status ENUM('active','inactive','graduated') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_student_user  FOREIGN KEY (user_id)  REFERENCES users(id)   ON DELETE SET NULL,
  CONSTRAINT fk_student_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Default admin  ->  admin@school.test / Admin@123
INSERT IGNORE INTO users (id, name, email, password, role) VALUES
(1, 'System Administrator', 'admin@school.test', '$2y$10$abcdefghijklmnopqrstuu/upWyhZ274lBZWYJfXc6LK9mdvmbT9a', 'admin');

INSERT IGNORE INTO courses (id, code, name, description) VALUES
(1, 'BSIT', 'BS Information Technology', 'Software, networking and systems'),
(2, 'BSCS', 'BS Computer Science', 'Algorithms, theory and computing'),
(3, 'BSBA', 'BS Business Administration', 'Management and entrepreneurship'),
(4, 'BSED', 'BS Education', 'Teacher education program');

INSERT IGNORE INTO students (student_no, first_name, last_name, email, phone, birthdate, gender, address, course_id, year_level, status) VALUES
('2026-0001','Juan','Dela Cruz','juan@example.com','09171234567','2004-03-12','Male','Makati City',1,2,'active'),
('2026-0002','Maria','Santos','maria@example.com','09181234567','2003-07-25','Female','Quezon City',2,3,'active'),
('2026-0003','Jose','Reyes','jose@example.com','09191234567','2002-11-02','Male','Pasig City',3,4,'active'),
('2026-0004','Ana','Garcia','ana@example.com','09201234567','2005-01-18','Female','Taguig City',1,1,'active'),
('2026-0005','Pedro','Bautista','pedro@example.com','09211234567','2001-09-09','Male','Manila',4,4,'graduated'),
('2026-0006','Lea','Mendoza','lea@example.com','09221234567','2004-05-30','Female','Mandaluyong',2,2,'inactive');
