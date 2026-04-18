-- Manual backup and restore script for secondlab
-- Backup
DROP TABLE IF EXISTS users_backup;
DROP TABLE IF EXISTS student_info_backup;
DROP TABLE IF EXISTS grades_backup;

CREATE TABLE users_backup LIKE users;
INSERT INTO users_backup SELECT * FROM users;

CREATE TABLE student_info_backup LIKE student_info;
INSERT INTO student_info_backup SELECT * FROM student_info;

CREATE TABLE grades_backup LIKE grades;
INSERT INTO grades_backup SELECT * FROM grades;

-- Restore
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE grades;
TRUNCATE TABLE student_info;
TRUNCATE TABLE users;

INSERT INTO users SELECT * FROM users_backup;
INSERT INTO student_info SELECT * FROM student_info_backup;
INSERT INTO grades SELECT * FROM grades_backup;
SET FOREIGN_KEY_CHECKS = 1;
