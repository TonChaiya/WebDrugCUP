-- Schema for MySQL (phpMyAdmin/XAMPP)
-- Create database (if not created):
-- CREATE DATABASE web_cup CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `news`;
CREATE TABLE `news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` text NOT NULL,
  `content` longtext NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `date_posted` datetime NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `documents`;
CREATE TABLE `documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `category` varchar(100) DEFAULT NULL,
  `drive_id` varchar(255) DEFAULT NULL,
  `drive_url` text,
  `uploaded_at` datetime DEFAULT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `views` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admins table for admin login
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL UNIQUE,
  `password_hash` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- To create an admin, generate the password hash with PHP and insert, e.g.:
-- php -r "echo password_hash('yourpassword', PASSWORD_DEFAULT).PHP_EOL;"
-- Then in SQL console:
-- INSERT INTO admins (username, password_hash) VALUES ('admin', '$2y$...');

-- Sample data
INSERT INTO `news` (`title`,`content`,`image`,`date_posted`,`category`) VALUES
('งานเปิดตัวโครงการ','รายละเอียดเบื้องต้นของการเปิดตัวโครงการ สาระสำคัญ และการเข้าร่วมกิจกรรม','',NOW(),'ประกาศ'),
('รับสมัครอาสาสมัคร','เชิญชวนผู้สนใจสมัครเป็นอาสาสมัครเพื่อร่วมกิจกรรมต่าง ๆ','',NOW(),'ข่าว');

INSERT INTO `documents` (`title`,`description`,`category`,`drive_id`,`drive_url`,`uploaded_at`,`tags`,`views`) VALUES
('แบบฟอร์มขออนุมัติ','แบบฟอร์มสำหรับการขออนุมัติการดำเนินงาน','แบบฟอร์ม','drive-file-id-1','https://drive.google.com/file/d/drive-file-id-1/view?usp=sharing',NOW(),'form,approval',0),
('แผนปฏิบัติการ 2568','แผนปฏิบัติการประจำปี 2568','แผนงาน','drive-file-id-2','https://drive.google.com/file/d/drive-file-id-2/view?usp=sharing',NOW(),'plan,2568',0);
