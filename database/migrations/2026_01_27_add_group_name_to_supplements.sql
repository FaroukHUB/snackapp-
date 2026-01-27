-- =============================================
-- Migration: Ajout du champ group_name aux suppléments
-- Date: 2026-01-27
-- Description: Permet de grouper les suppléments par catégorie
--              (viande, legumes, fromages, sauces, autres)
-- =============================================

-- Ajouter la colonne group_name si elle n'existe pas
SET @dbname = DATABASE();
SET @tablename = 'supplements';
SET @columnname = 'group_name';
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @dbname
     AND TABLE_NAME = @tablename
     AND COLUMN_NAME = @columnname) > 0,
    'SELECT 1',
    CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname,
           "` ENUM('viande', 'legumes', 'fromages', 'sauces', 'autres') NOT NULL DEFAULT 'autres' ",
           "COMMENT 'Groupe de supplément pour affichage groupé' AFTER `flavor`")
));

PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Ajouter un index sur group_name pour les requêtes groupées
SET @indexname = 'idx_restaurant_group';
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = @dbname
     AND TABLE_NAME = @tablename
     AND INDEX_NAME = @indexname) > 0,
    'SELECT 1',
    CONCAT('ALTER TABLE `', @tablename, '` ADD INDEX `', @indexname, '` (`restaurant_id`, `group_name`)')
));

PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- =============================================
-- Post-migration: Assigner les groupes aux suppléments existants
-- Basé sur les noms (heuristique)
-- =============================================

-- Viandes
UPDATE supplements SET group_name = 'viande'
WHERE group_name = 'autres'
AND (
    name LIKE '%poulet%' OR name LIKE '%boeuf%' OR name LIKE '%viande%'
    OR name LIKE '%bacon%' OR name LIKE '%jambon%' OR name LIKE '%chorizo%'
    OR name LIKE '%merguez%' OR name LIKE '%kebab%' OR name LIKE '%saucisse%'
    OR name LIKE '%thon%' OR name LIKE '%anchois%' OR name LIKE '%crevette%'
    OR name LIKE '%pepperoni%' OR name LIKE '%salami%' OR name LIKE '%lardon%'
);

-- Légumes
UPDATE supplements SET group_name = 'legumes'
WHERE group_name = 'autres'
AND (
    name LIKE '%oignon%' OR name LIKE '%champignon%' OR name LIKE '%poivron%'
    OR name LIKE '%olive%' OR name LIKE '%tomate%' OR name LIKE '%artichaut%'
    OR name LIKE '%mais%' OR name LIKE '%maïs%' OR name LIKE '%salade%'
    OR name LIKE '%roquette%' OR name LIKE '%épinard%' OR name LIKE '%courgette%'
    OR name LIKE '%aubergine%' OR name LIKE '%piment%' OR name LIKE '%jalapeño%'
);

-- Fromages
UPDATE supplements SET group_name = 'fromages'
WHERE group_name = 'autres'
AND (
    name LIKE '%mozzarella%' OR name LIKE '%parmesan%' OR name LIKE '%fromage%'
    OR name LIKE '%cheddar%' OR name LIKE '%emmental%' OR name LIKE '%gorgonzola%'
    OR name LIKE '%chèvre%' OR name LIKE '%feta%' OR name LIKE '%ricotta%'
    OR name LIKE '%raclette%' OR name LIKE '%bleu%' OR name LIKE '%gruyère%'
);

-- Sauces
UPDATE supplements SET group_name = 'sauces'
WHERE group_name = 'autres'
AND (
    name LIKE '%sauce%' OR name LIKE '%crème%' OR name LIKE '%pesto%'
    OR name LIKE '%huile%' OR name LIKE '%vinaigrette%' OR name LIKE '%ketchup%'
    OR name LIKE '%mayo%' OR name LIKE '%moutarde%' OR name LIKE '%barbecue%'
    OR name LIKE '%aigre%' OR name LIKE '%épicée%'
);

-- Vérification
SELECT group_name, COUNT(*) as count FROM supplements GROUP BY group_name ORDER BY group_name;
