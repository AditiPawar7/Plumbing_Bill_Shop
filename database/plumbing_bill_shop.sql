-- Plumbing Bill Shop - complete database script (MySQL / MariaDB, XAMPP)
-- Import via phpMyAdmin: Import tab -> choose this file -> Go

CREATE DATABASE IF NOT EXISTS Plumbing_Bill_Shop
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE Plumbing_Bill_Shop;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS bill_details, bills, purchase_details, purchases, products,
  customers, dealers, subcategories, categories, units, users;
SET FOREIGN_KEY_CHECKS = 1;

-- NOTE: names are NOT UNIQUE in the database because soft-deleted rows must not
-- block re-adding the same name. Duplicate checks are done in PHP.

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','worker') NOT NULL DEFAULT 'worker',
  is_deleted TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  is_deleted TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE subcategories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  is_deleted TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_sub_category FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB;

CREATE TABLE units (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL,
  is_deleted TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  category_id INT NOT NULL,
  subcategory_id INT NULL,
  unit_id INT NOT NULL,
  purchase_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  selling_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  quantity DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  min_stock DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  is_deleted TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_prod_category FOREIGN KEY (category_id) REFERENCES categories(id),
  CONSTRAINT fk_prod_sub FOREIGN KEY (subcategory_id) REFERENCES subcategories(id),
  CONSTRAINT fk_prod_unit FOREIGN KEY (unit_id) REFERENCES units(id)
) ENGINE=InnoDB;

CREATE TABLE customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NULL,
  address VARCHAR(255) NULL,
  is_deleted TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE dealers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NULL,
  address VARCHAR(255) NULL,
  gst_no VARCHAR(20) NULL,
  is_deleted TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE purchases (
  id INT AUTO_INCREMENT PRIMARY KEY,
  dealer_id INT NOT NULL,
  purchase_date DATE NOT NULL,
  total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  created_by INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pur_dealer FOREIGN KEY (dealer_id) REFERENCES dealers(id),
  CONSTRAINT fk_pur_user FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE purchase_details (
  id INT AUTO_INCREMENT PRIMARY KEY,
  purchase_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity DECIMAL(10,2) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL,
  CONSTRAINT fk_pd_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id),
  CONSTRAINT fk_pd_product FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;

CREATE TABLE bills (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bill_no VARCHAR(30) NOT NULL UNIQUE,
  customer_id INT NOT NULL,
  bill_date DATE NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  discount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  tax_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  grand_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  created_by INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_bill_customer FOREIGN KEY (customer_id) REFERENCES customers(id),
  CONSTRAINT fk_bill_user FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE bill_details (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bill_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity DECIMAL(10,2) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL,
  CONSTRAINT fk_bd_bill FOREIGN KEY (bill_id) REFERENCES bills(id),
  CONSTRAINT fk_bd_product FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;

-- ---------- Sample data ----------
-- Logins: admin / admin123   and   worker / worker123
INSERT INTO users (name, username, password, role) VALUES
('Shop Admin',  'admin',  '$2y$10$rkDGSRfBLCRQ3c5F6CHDEu4eBXIUbR0FflSn5G1l5V.jqKj76uCq2', 'admin'),
('Shop Worker', 'worker', '$2y$10$6cMvMddBUqtfDzzNKUyzF.p5Tx0OlVruBj0gsWcWe0sIt5L8EmgOu', 'worker');

INSERT INTO units (name) VALUES ('Piece'),('Box'),('Meter'),('Feet'),('Kg'),('Packet');
INSERT INTO categories (name) VALUES ('Pipes'),('Fittings'),('Taps & Valves'),('Bathroom Accessories'),('Tanks'),('Tools & Consumables');
INSERT INTO subcategories (category_id, name) VALUES
(1,'PVC Pipes'),(1,'CPVC Pipes'),(2,'Elbows'),(2,'Tees'),(3,'Taps'),(3,'Valves'),(4,'Showers'),(5,'Water Tanks'),(6,'Tapes');

INSERT INTO products (name, category_id, subcategory_id, unit_id, purchase_price, selling_price, quantity, min_stock) VALUES
('PVC Pipe 1 inch',     1,1,3, 90.00,  120.00, 200, 50),
('PVC Pipe 2 inch',     1,1,3,160.00,  210.00, 120, 40),
('CPVC Pipe 1/2 inch',  1,2,3,110.00,  150.00,  80, 30),
('Elbow Joint',         2,3,1, 15.00,   25.00, 300, 60),
('Tee Joint',           2,4,1, 22.00,   35.00, 250, 60),
('Water Tap (Basin)',   3,5,1,320.00,  450.00,  40, 10),
('Ball Valve 1/2 inch', 3,6,1,130.00,  180.00,  60, 15),
('Shower Head',         4,7,1,420.00,  600.00,  25, 10),
('Water Tank 500 L',    5,8,1,3800.00,4800.00,   6,  3),
('Teflon Tape',         6,9,1, 10.00,   20.00, 150, 40);

INSERT INTO customers (name, phone, address) VALUES
('Walk-in Customer', NULL, NULL),
('Ramesh Patil', '9876543210', 'Karad, Maharashtra'),
('Sunita Jadhav', '9123456780', 'Satara, Maharashtra');

INSERT INTO dealers (name, phone, address, gst_no) VALUES
('Shree Sanitary Traders', '9822000001', 'Pune', '27ABCDE1234F1Z5'),
('Om Pipe Suppliers',      '9822000002', 'Kolhapur', '27PQRSX5678L1Z2');
