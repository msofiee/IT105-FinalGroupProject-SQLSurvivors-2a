USE inventory;

DELIMITER $$

-- TRANSACTION 1: Create a sale with ACID rollback (atomic stock deduction + sales insert)
-- If stock is insufficient or any error occurs, the whole operation is rolled back.
CREATE PROCEDURE create_sale_with_stock(
    IN p_customer_id INT,
    IN p_product_id INT,
    IN p_quantity INT
)
BEGIN
    DECLARE v_unit_price DECIMAL(10,2);
    DECLARE v_current_stock INT;
    DECLARE v_total_amount DECIMAL(10,2);

    -- Rollback on any SQL error
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- Lock the product row to ensure isolation
    SELECT unit_price, current_stock
      INTO v_unit_price, v_current_stock
    FROM products
    WHERE product_id = p_product_id
    FOR UPDATE;

    -- If product does not exist, SELECT will leave variables NULL
    IF v_current_stock IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Product not found';
    END IF;

    IF v_current_stock < p_quantity THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Insufficient stock';
    END IF;

    SET v_total_amount = v_unit_price * p_quantity;

    -- Insert sale
    INSERT INTO sales (customer_id, sale_date, total_amount)
    VALUES (p_customer_id, NOW(), v_total_amount);

    -- Update product stock
    UPDATE products
    SET current_stock = current_stock - p_quantity
    WHERE product_id = p_product_id;

    COMMIT;
END$$

DELIMITER ;

-- Example usage (uncomment to run):
-- CALL create_sale_with_stock(2, 1, 1);


