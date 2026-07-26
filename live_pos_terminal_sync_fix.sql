SET @db_name := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='tbl_running_orders' AND COLUMN_NAME='company_id') = 0,
  'ALTER TABLE tbl_running_orders ADD COLUMN company_id INT(11) NOT NULL DEFAULT 0 AFTER user_id',
  'SELECT ''tbl_running_orders.company_id already exists'''
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='tbl_running_orders' AND COLUMN_NAME='outlet_id') = 0,
  'ALTER TABLE tbl_running_orders ADD COLUMN outlet_id INT(11) NOT NULL DEFAULT 0 AFTER company_id',
  'SELECT ''tbl_running_orders.outlet_id already exists'''
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='tbl_running_orders' AND COLUMN_NAME='counter_id') = 0,
  'ALTER TABLE tbl_running_orders ADD COLUMN counter_id INT(11) NOT NULL DEFAULT 0 AFTER outlet_id',
  'SELECT ''tbl_running_orders.counter_id already exists'''
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE tbl_running_orders ro
JOIN tbl_kitchen_sales ks ON ks.sale_no = ro.sale_no AND ks.del_status = 'Live'
SET
  ro.company_id = ks.company_id,
  ro.outlet_id = ks.outlet_id,
  ro.counter_id = ks.counter_id
WHERE ro.del_status = 'Live'
  AND (ro.company_id = 0 OR ro.outlet_id = 0 OR ro.counter_id = 0);

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='tbl_running_orders' AND INDEX_NAME='idx_running_orders_user_scope') = 0,
  'ALTER TABLE tbl_running_orders ADD INDEX idx_running_orders_user_scope (user_id, company_id, outlet_id, counter_id, del_status)',
  'SELECT ''idx_running_orders_user_scope already exists'''
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='tbl_running_orders' AND INDEX_NAME='idx_running_orders_sale_scope') = 0,
  'ALTER TABLE tbl_running_orders ADD INDEX idx_running_orders_sale_scope (sale_no, company_id, outlet_id, counter_id, del_status)',
  'SELECT ''idx_running_orders_sale_scope already exists'''
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='tbl_kitchen_sales' AND INDEX_NAME='idx_kitchen_sales_cashier_counter_sync') = 0,
  'ALTER TABLE tbl_kitchen_sales ADD INDEX idx_kitchen_sales_cashier_counter_sync (company_id, outlet_id, counter_id, del_status, is_accept, pull_update_cashier)',
  'SELECT ''idx_kitchen_sales_cashier_counter_sync already exists'''
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='tbl_kitchen_sales' AND INDEX_NAME='idx_kitchen_sales_sale_counter') = 0,
  'ALTER TABLE tbl_kitchen_sales ADD INDEX idx_kitchen_sales_sale_counter (sale_no(100), company_id, outlet_id, counter_id, del_status)',
  'SELECT ''idx_kitchen_sales_sale_counter already exists'''
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='tbl_sales' AND INDEX_NAME='idx_sales_sale_counter') = 0,
  'ALTER TABLE tbl_sales ADD INDEX idx_sales_sale_counter (sale_no(100), company_id, outlet_id, counter_id, del_status)',
  'SELECT ''idx_sales_sale_counter already exists'''
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE tbl_running_order_tables rot
JOIN tbl_kitchen_sales ks ON ks.sale_no = rot.sale_no AND ks.outlet_id = rot.outlet_id AND ks.del_status = 'Live'
SET rot.company_id = ks.company_id
WHERE rot.del_status = 'Live'
  AND rot.company_id = 0;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='tbl_running_order_tables' AND INDEX_NAME='idx_running_order_tables_sale_scope') = 0,
  'ALTER TABLE tbl_running_order_tables ADD INDEX idx_running_order_tables_sale_scope (sale_no, outlet_id, company_id, del_status)',
  'SELECT ''idx_running_order_tables_sale_scope already exists'''
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
