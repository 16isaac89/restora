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
      AND TABLE_NAME = 'tbl_ingredients'
      AND INDEX_NAME = 'idx_ingredients_company_live'
);
SET @sql = IF(
    @idx_exists = 0,
    'ALTER TABLE tbl_ingredients ADD INDEX idx_ingredients_company_live (company_id, del_status, id)',
    'SELECT ''idx_ingredients_company_live already exists'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tbl_purchase_ingredients'
      AND INDEX_NAME = 'idx_inventory_purchase_ingredient_scope'
);
SET @sql = IF(
    @idx_exists = 0,
    'ALTER TABLE tbl_purchase_ingredients ADD INDEX idx_inventory_purchase_ingredient_scope (ingredient_id, outlet_id, del_status)',
    'SELECT ''idx_inventory_purchase_ingredient_scope already exists'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tbl_sale_consumptions_of_menus'
      AND INDEX_NAME = 'idx_inventory_menu_consumption_scope'
);
SET @sql = IF(
    @idx_exists = 0,
    'ALTER TABLE tbl_sale_consumptions_of_menus ADD INDEX idx_inventory_menu_consumption_scope (ingredient_id, outlet_id, del_status)',
    'SELECT ''idx_inventory_menu_consumption_scope already exists'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tbl_sale_consumptions_of_modifiers_of_menus'
      AND INDEX_NAME = 'idx_inventory_modifier_consumption_scope'
);
SET @sql = IF(
    @idx_exists = 0,
    'ALTER TABLE tbl_sale_consumptions_of_modifiers_of_menus ADD INDEX idx_inventory_modifier_consumption_scope (ingredient_id, outlet_id, del_status)',
    'SELECT ''idx_inventory_modifier_consumption_scope already exists'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tbl_waste_ingredients'
      AND INDEX_NAME = 'idx_inventory_waste_scope'
);
SET @sql = IF(
    @idx_exists = 0,
    'ALTER TABLE tbl_waste_ingredients ADD INDEX idx_inventory_waste_scope (ingredient_id, outlet_id, del_status)',
    'SELECT ''idx_inventory_waste_scope already exists'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tbl_inventory_adjustment_ingredients'
      AND INDEX_NAME = 'idx_inventory_adjustment_scope'
);
SET @sql = IF(
    @idx_exists = 0,
    'ALTER TABLE tbl_inventory_adjustment_ingredients ADD INDEX idx_inventory_adjustment_scope (ingredient_id, outlet_id, del_status, consumption_status)',
    'SELECT ''idx_inventory_adjustment_scope already exists'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tbl_production_ingredients'
      AND INDEX_NAME = 'idx_inventory_production_scope'
);
SET @sql = IF(
    @idx_exists = 0,
    'ALTER TABLE tbl_production_ingredients ADD INDEX idx_inventory_production_scope (ingredient_id, outlet_id, del_status, status)',
    'SELECT ''idx_inventory_production_scope already exists'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tbl_transfer_ingredients'
      AND INDEX_NAME = 'idx_inventory_transfer_to_scope'
);
SET @sql = IF(
    @idx_exists = 0,
    'ALTER TABLE tbl_transfer_ingredients ADD INDEX idx_inventory_transfer_to_scope (ingredient_id, to_outlet_id, del_status, status, transfer_type)',
    'SELECT ''idx_inventory_transfer_to_scope already exists'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tbl_transfer_ingredients'
      AND INDEX_NAME = 'idx_inventory_transfer_from_scope'
);
SET @sql = IF(
    @idx_exists = 0,
    'ALTER TABLE tbl_transfer_ingredients ADD INDEX idx_inventory_transfer_from_scope (ingredient_id, from_outlet_id, del_status, status, transfer_type)',
    'SELECT ''idx_inventory_transfer_from_scope already exists'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tbl_transfer_received_ingredients'
      AND INDEX_NAME = 'idx_inventory_transfer_received_to_scope'
);
SET @sql = IF(
    @idx_exists = 0,
    'ALTER TABLE tbl_transfer_received_ingredients ADD INDEX idx_inventory_transfer_received_to_scope (ingredient_id, to_outlet_id, del_status, status)',
    'SELECT ''idx_inventory_transfer_received_to_scope already exists'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tbl_transfer_received_ingredients'
      AND INDEX_NAME = 'idx_inventory_transfer_received_from_scope'
);
SET @sql = IF(
    @idx_exists = 0,
    'ALTER TABLE tbl_transfer_received_ingredients ADD INDEX idx_inventory_transfer_received_from_scope (ingredient_id, from_outlet_id, del_status, status)',
    'SELECT ''idx_inventory_transfer_received_from_scope already exists'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tbl_food_menu_categories'
      AND INDEX_NAME = 'idx_pos_categories_sort'
);
SET @sql = IF(
    @idx_exists = 0,
    'ALTER TABLE tbl_food_menu_categories ADD INDEX idx_pos_categories_sort (company_id, del_status, order_by)',
    'SELECT ''idx_pos_categories_sort already exists'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tbl_food_menus_modifiers'
      AND INDEX_NAME = 'idx_pos_food_menu_modifiers_scope'
);
SET @sql = IF(
    @idx_exists = 0,
    'ALTER TABLE tbl_food_menus_modifiers ADD INDEX idx_pos_food_menu_modifiers_scope (company_id, del_status, id)',
    'SELECT ''idx_pos_food_menu_modifiers_scope already exists'' AS message'
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
