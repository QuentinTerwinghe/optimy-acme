-- MySQL Initialization Script for Optimy
-- This script runs once when the MySQL container is first created

-- Ensure UTF8MB4 is used
ALTER DATABASE optimy CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Grant privileges
GRANT ALL PRIVILEGES ON optimy.* TO 'optimy'@'%';
FLUSH PRIVILEGES;

-- Optional: Create additional databases for testing
-- CREATE DATABASE IF NOT EXISTS optimy_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- GRANT ALL PRIVILEGES ON optimy_test.* TO 'optimy'@'%';
-- FLUSH PRIVILEGES;
