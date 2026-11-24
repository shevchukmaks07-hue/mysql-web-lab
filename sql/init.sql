-- Ініціалізація БД для лабораторної роботи.
-- Справжній MySQL:  mysql -u root -p < sql/init.sql

CREATE DATABASE IF NOT EXISTS webdb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'webuser'@'%' IDENTIFIED WITH mysql_native_password BY 'webpass';
GRANT SELECT, INSERT ON webdb.* TO 'webuser'@'%';
FLUSH PRIVILEGES;

USE webdb;

CREATE TABLE IF NOT EXISTS students (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    full_name   VARCHAR(100) NOT NULL,
    group_name  VARCHAR(20)  NOT NULL,
    email       VARCHAR(100) NOT NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
