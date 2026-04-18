-- Normalized users table (no redundant address field)
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(120) NOT NULL,
    role VARCHAR(20) NOT NULL CHECK (role IN ('Student', 'Admin'))
) ENGINE=InnoDB;

-- Student profile data kept in a dedicated table
CREATE TABLE IF NOT EXISTS student_info (
    student_id INT PRIMARY KEY,
    home_address VARCHAR(255),
    emergency_contact VARCHAR(120),
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS grades (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    subject VARCHAR(30) NOT NULL,
    grade_value INT NOT NULL CHECK (grade_value BETWEEN 0 AND 100),
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Seed data with hashed passwords
INSERT INTO users (id, username, password, full_name, role)
VALUES
(1, 'admin', '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9', 'System Admin', 'Admin'),
(2, 'jdoe', '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8', 'John Doe', 'Student')
ON DUPLICATE KEY UPDATE
username = VALUES(username),
password = VALUES(password),
full_name = VALUES(full_name),
role = VALUES(role);

INSERT INTO student_info (student_id, home_address, emergency_contact)
VALUES
(2, '456 College Ave', 'Jane Doe - 555-0112')
ON DUPLICATE KEY UPDATE
home_address = VALUES(home_address),
emergency_contact = VALUES(emergency_contact);

INSERT INTO grades (id, student_id, subject, grade_value)
VALUES
(1, 2, 'Math', 91)
ON DUPLICATE KEY UPDATE
student_id = VALUES(student_id),
subject = VALUES(subject),
grade_value = VALUES(grade_value);
