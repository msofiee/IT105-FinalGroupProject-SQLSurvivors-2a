-- Inventory Management System Database

CREATE DATABASE IF NOT EXISTS inventory_db;
USE inventory_db;

CREATE TABLE Suppliers (
    supplier_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(100),
    contact_email VARCHAR(100),
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100)
);

CREATE TABLE Products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(100),
    category_id INT,
    supplier_id INT,
    price DECIMAL(10,2),
    stock INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES Categories(category_id),
    FOREIGN KEY (supplier_id) REFERENCES Suppliers(supplier_id)
);

CREATE TABLE Customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    order_date DATE,
    total_amount DECIMAL(10,2),
    status VARCHAR(50),
    FOREIGN KEY (customer_id) REFERENCES Customers(customer_id)
);

CREATE TABLE Order_Details (
    detail_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT,
    quantity INT,
    price DECIMAL(10,2),
    FOREIGN KEY (order_id) REFERENCES Orders(order_id),
    FOREIGN KEY (product_id) REFERENCES Products(product_id)
);

-- Sample Data
INSERT INTO Categories (category_name) VALUES
('Electronics'), ('Office Supplies'), ('Furniture');

INSERT INTO Suppliers (supplier_name, contact_email, phone) VALUES
('Tech Supply Co.', 'tech@email.com', '09123456789'),
('Office Depot', 'office@email.com', '09987654321');

INSERT INTO Products (product_name, category_id, supplier_id, price, stock) VALUES
('Laptop', 1, 1, 35000.00, 50),
('Mouse', 1, 1, 500.00, 200);

INSERT INTO Customers (customer_name, email, phone) VALUES
('Juan Dela Cruz', 'juan@email.com', '09111111111');

INSERT INTO Orders (customer_id, order_date, total_amount, status) VALUES
(1, CURDATE(), 35500.00, 'Completed');

INSERT INTO Order_Details (order_id, product_id, quantity, price) VALUES
(1, 1, 1, 35000.00);

-- Indexes
CREATE INDEX idx_order_customer ON Orders(customer_id);
CREATE INDEX idx_product_category ON Products(category_id);
