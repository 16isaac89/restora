SET @schema_name = DATABASE();

SET @idx_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tbl_sales_details'
      AND INDEX_NAME = 'idx_food_menu_del_status'
);
SET @sql = IF(
    @idx_exists = 0,
    'ALTER TABLE tbl_sales_details ADD INDEX idx_food_menu_del_status (food_menu_id, del_status)',
    'SELECT ''idx_food_menu_del_status already exists'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tbl_food_menus'
      AND INDEX_NAME = 'idx_parent_del_status'
);
SET @sql = IF(
    @idx_exists = 0,
    'ALTER TABLE tbl_food_menus ADD INDEX idx_parent_del_status (parent_id, del_status)',
    'SELECT ''idx_parent_del_status already exists'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tbl_kitchen_categories'
      AND INDEX_NAME = 'idx_outlet_cat_del_status'
);
SET @sql = IF(
    @idx_exists = 0,
    'ALTER TABLE tbl_kitchen_categories ADD INDEX idx_outlet_cat_del_status (outlet_id, cat_id, del_status)',
    'SELECT ''idx_outlet_cat_del_status already exists'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
