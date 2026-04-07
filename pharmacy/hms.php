-- 1. Categorize your spending (Salaries, Rent, Stock, etc.)
CREATE TABLE IF NOT EXISTS expense_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL,
    description TEXT
) ENGINE=InnoDB;

-- 2. Insert default categories for a hospital
INSERT IGNORE INTO expense_categories (category_name) VALUES 
('Medical Stock Purchase'), 
('Staff Salaries'), 
('Utilities (Power/Water)'), 
('Rent'), 
('Equipment Maintenance'),
('Marketing');

-- 3. The Corrected Expenses Table
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    description TEXT,
    expense_date DATE NOT NULL,
    payment_method ENUM('Cash', 'Bank Transfer', 'M-Pesa', 'Cheque') DEFAULT 'Cash',
    reference_no VARCHAR(50), 
    recorded_by INT NOT NULL,
    po_id INT NULL, -- Optional link to a Purchase Order
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_category FOREIGN KEY (category_id) REFERENCES expense_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;


-- 1. Suppliers Table
CREATE TABLE IF NOT EXISTS vendors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    category ENUM('Medicines', 'Equipment', 'General') DEFAULT 'Medicines',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Expense Categories
CREATE TABLE IF NOT EXISTS expense_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO expense_categories (category_name) VALUES ('Stock Purchase'), ('Salaries'), ('Rent'), ('Utilities');

-- 3. Expenses Table (Corrected)
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    description TEXT,
    expense_date DATE NOT NULL,
    payment_method ENUM('Cash', 'Bank Transfer', 'M-Pesa', 'Cheque') DEFAULT 'Cash',
    recorded_by INT NOT NULL,
    po_id INT NULL,
    FOREIGN KEY (category_id) REFERENCES expense_categories(id)
) ENGINE=InnoDB;


-- Enhancing the existing table for production
ALTER TABLE accounting_entries 
ADD COLUMN payment_method ENUM('Cash', 'M-Pesa', 'Bank Transfer', 'Cheque') DEFAULT 'Cash' AFTER note,
ADD COLUMN reference_id VARCHAR(50) NULL AFTER payment_method, -- Invoice # or PO #
ADD INDEX (account),
ADD INDEX (created_at);


CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    category ENUM('Medicines', 'Lab Reagents', 'Equipment', 'General') DEFAULT 'Medicines',
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    category ENUM('Medicines', 'Lab Reagents', 'Equipment', 'General') DEFAULT 'Medicines',
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS purchase_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    qty INT NOT NULL,
    unit_cost DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('Pending', 'Received', 'Cancelled') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
) ENGINE=InnoDB;

ALTER TABLE purchase_order_items 
ADD COLUMN unit_price DECIMAL(15, 2) NOT NULL DEFAULT 0.00 AFTER quantity,
ADD COLUMN line_total DECIMAL(15, 2) NOT NULL DEFAULT 0.00 AFTER unit_price;

DROP TABLE IF EXISTS purchase_order_items;
DROP TABLE IF EXISTS purchase_orders;

CREATE TABLE purchase_orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  supplier_id INT NOT NULL,
  user_id INT DEFAULT NULL, 
  order_date DATE NOT NULL,
  total_amount DECIMAL(15, 2) DEFAULT 0.00,
  status ENUM('Pending', 'Received', 'Cancelled') DEFAULT 'Pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE purchase_order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  purchase_order_id INT NOT NULL,
  item_name VARCHAR(255) NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  unit_price DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  line_total DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;
ALTER TABLE purchase_order_items ADD COLUMN received_qty INT DEFAULT 0 AFTER quantity;