# Fix for MenuRepository base_ingredients Error

## Problem
The `MenuRepository.php` file is trying to query a column `base_ingredients` from the `products` table, but this column doesn't exist in your database yet.

## Solution
Run the migration script to add the missing column:

```bash
mysql -u zajr1824_demo -p zajr1824_demo < fix_base_ingredients.sql
```

Or manually run this SQL command:

```sql
ALTER TABLE `products`
ADD COLUMN `base_ingredients` JSON DEFAULT NULL
COMMENT 'Liste des ingrédients que le client peut retirer'
AFTER `price_menu`;
```

## Verification
After running the migration, verify the column exists:

```bash
mysql -u zajr1824_demo -p -e "DESCRIBE products;" zajr1824_demo | grep ingredients
```

You should see output like:
```
base_ingredients    json    YES        NULL
```

## Why This Happened
The migration file `/database/migrations/2026_01_26_add_base_ingredients.sql` exists in the codebase but was never applied to the production database. This is a common issue when deploying code without running migrations.

## Next Steps
After fixing this, make sure to:
1. Always run migrations after deploying new code
2. Check for other pending migrations in `/database/migrations/` folder
3. Consider setting up an automated migration process in your deployment pipeline
