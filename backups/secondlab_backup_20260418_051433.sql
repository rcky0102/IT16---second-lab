SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `grades`;
DROP TABLE IF EXISTS `student_info`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `role` varchar(20) NOT NULL CHECK (`role` in ('Student','Admin')),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`) VALUES ('1', 'admin', '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9', 'System Admin', 'Admin');
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`) VALUES ('2', 'jdoe', '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8', 'John Doe', 'Student');

CREATE TABLE `student_info` (
  `student_id` int(11) NOT NULL,
  `home_address` varchar(255) DEFAULT NULL,
  `emergency_contact` varchar(120) DEFAULT NULL,
  PRIMARY KEY (`student_id`),
  CONSTRAINT `student_info_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO `student_info` (`student_id`, `home_address`, `emergency_contact`) VALUES ('2', '456 College Ave', 'Jane Doe - 555-0112');

CREATE TABLE `grades` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject` varchar(10) NOT NULL,
  `grade_value` int(11) NOT NULL CHECK (`grade_value` between 0 and 100),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO `grades` (`id`, `student_id`, `subject`, `grade_value`) VALUES ('0', '2', 'new subjec', '85');
INSERT INTO `grades` (`id`, `student_id`, `subject`, `grade_value`) VALUES ('1', '2', 'asdasdasda', '91');
INSERT INTO `grades` (`id`, `student_id`, `subject`, `grade_value`) VALUES ('2', '2', 'Geography', '78');
INSERT INTO `grades` (`id`, `student_id`, `subject`, `grade_value`) VALUES ('3', '2', 'science', '89');

SET FOREIGN_KEY_CHECKS = 1;
