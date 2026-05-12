DELIMITER $$

-- Trigger for AFTER INSERT on products
CREATE TRIGGER `products_after_insert` AFTER INSERT ON `products` 
FOR EACH ROW
BEGIN
  INSERT INTO audit_log(action_type, table_name, row_id, old_value, new_value)
  VALUES (
    'INSERT',
    'products',
    NEW.product_id,
    NULL,
    JSON_OBJECT(
      'sku', NEW.sku,
      'product_name', NEW.product_name,
      'category_id', NEW.category_id,
      'supplier_id', NEW.supplier_id,
      'unit_price', NEW.unit_price,
      'current_stock', NEW.current_stock
    )
  );
END$$

-- Trigger for AFTER UPDATE on products
CREATE TRIGGER `products_after_update` AFTER UPDATE ON `products` 
FOR EACH ROW
BEGIN
  INSERT INTO audit_log(action_type, table_name, row_id, old_value, new_value)
  VALUES (
    'UPDATE',
    'products',
    NEW.product_id,
    JSON_OBJECT(
      'sku', OLD.sku,
      'product_name', OLD.product_name,
      'category_id', OLD.category_id,
      'supplier_id', OLD.supplier_id,
      'unit_price', OLD.unit_price,
      'current_stock', OLD.current_stock
    ),
    JSON_OBJECT(
      'sku', NEW.sku,
      'product_name', NEW.product_name,
      'category_id', NEW.category_id,
      'supplier_id', NEW.supplier_id,
      'unit_price', NEW.unit_price,
      'current_stock', NEW.current_stock
    )
  );
END$$

-- Trigger for AFTER DELETE on products
CREATE TRIGGER `products_after_delete` AFTER DELETE ON `products` 
FOR EACH ROW
BEGIN
  INSERT INTO audit_log(action_type, table_name, row_id, old_value, new_value)
  VALUES (
    'DELETE',
    'products',
    OLD.product_id,
    JSON_OBJECT(
      'sku', OLD.sku,
      'product_name', OLD.product_name,
      'category_id', OLD.category_id,
      'supplier_id', OLD.supplier_id,
      'unit_price', OLD.unit_price,
      'current_stock', OLD.current_stock
    ),
    NULL
  );
END$$

DELIMITER ;