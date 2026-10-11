-- Creates the dedicated database used by the API integration test suite and
-- grants the application user access to it.
--
-- The MySQL image runs every file in /docker-entrypoint-initdb.d ONLY when the
-- data directory is empty (i.e. on the first start with a fresh volume).
-- After changing this file you must reset the volume:
--
--   docker compose down -v
--   docker compose up -d
--
-- The application user (MYSQL_USER) already exists here; the official image
-- created it and granted it access to MYSQL_DATABASE only, so we grant access
-- to the test database as well.

CREATE DATABASE IF NOT EXISTS `products-api-test`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

GRANT ALL PRIVILEGES ON `products-api-test`.* TO 'productsuser'@'%';

FLUSH PRIVILEGES;
