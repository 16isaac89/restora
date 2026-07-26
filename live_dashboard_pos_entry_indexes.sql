SET @schema_name = DATABASE();

SET @idx_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tbl_sales'
      AND INDEX_NAME = 'idx_dashboard_sales_scope'
);
SET @sql = IF(
    @idx_exists = 0,
    'ALTER TABLE tbl_sales ADD INDEX idx_dashboard_sales_scope (outlet_id, company_id, del_status, sale_date, order_type, order_status)',
    'SELECT ''idx_dashboard_sales_scope already exists'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tbl_sales_details'
      AND INDEX_NAME = 'idx_dashboard_sales_details_scope'
);
SET @sql = IF(
    @idx_exists = 0,
    'ALTER TABLE tbl_sales_details ADD INDEX idx_dashboard_sales_details_scope (outlet_id, del_status, sales_id, food_menu_id)',
    'SELECT ''idx_dashboard_sales_details_scope already exists'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tbl_sale_payments'
      AND INDEX_NAME = 'idx_dashboard_sale_payments_scope'
);
SET @sql = IF(
    @idx_exists = 0,
    'ALTER TABLE tbl_sale_payments ADD INDEX idx_dashboard_sale_payments_scope (outlet_id, del_status, sale_id, payment_id)',
    'SELECT ''idx_dashboard_sale_payments_scope already exists'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tbl_register'
      AND INDEX_NAME = 'idx_register_open_lookup'
);
SET @sql = IF(
    @idx_exists = 0,
    'ALTER TABLE tbl_register ADD INDEX idx_register_open_lookup (user_id, outlet_id, register_status)',
    'SELECT ''idx_register_open_lookup already exists'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tbl_purchase'
      AND INDEX_NAME = 'idx_dashboard_purchase_scope'
);
SET @sql = IF(
    @idx_exists = 0,
    'ALTER TABLE tbl_purchase ADD INDEX idx_dashboard_purchase_scope (outlet_id, del_status, date)',
    'SELECT ''idx_dashboard_purchase_scope already exists'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
