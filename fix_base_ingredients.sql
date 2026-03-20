-- Fix: Add missing base_ingredients column to products table
-- This migration needs to be applied to fix the MenuRepository error

-- Check if column exists first
SET @dbname = DATABASE();
SET @tablename = "products";
SET @columnname = "base_ingredients";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 'Column already exists' AS status;",
  "ALTER TABLE `products` ADD COLUMN `base_ingredients` JSON DEFAULT NULL COMMENT 'Liste des ingrédients que le client peut retirer' AFTER `price_menu`;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Verify the column was added
SELECT 'Migration complete - base_ingredients column ready' AS status;
SHOW COLUMNS FROM products LIKE 'base_ingredients';
