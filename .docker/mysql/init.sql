-- MySQL Initialization Script for ACME Corp
-- This script runs once when the MySQL container is first created

-- Ensure UTF8MB4 is used
ALTER DATABASE acme_corp CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Grant privileges
GRANT ALL PRIVILEGES ON acme_corp.* TO 'acme_corp'@'%';
FLUSH PRIVILEGES;

-- Optional: Create additional databases for testing
-- CREATE DATABASE IF NOT EXISTS acme_corp_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- GRANT ALL PRIVILEGES ON acme_corp_test.* TO 'acme_corp'@'%';
-- FLUSH PRIVILEGES;
