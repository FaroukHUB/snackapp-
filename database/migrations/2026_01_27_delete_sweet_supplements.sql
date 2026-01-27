-- =============================================
-- Migration: Suppression des suppléments sucrés (legacy Marvelous)
-- Date: 2026-01-27
-- Description: Les suppléments sucrés ne sont plus utilisés
--              (legacy Marvelous crêperie)
-- =============================================

-- 1. Afficher les suppléments sucrés avant suppression (pour log)
SELECT id, name, flavor, price, status
FROM supplements
WHERE flavor = 'sucre';

-- 2. Supprimer les liaisons product_supplements pour les suppléments sucrés
DELETE ps FROM product_supplements ps
INNER JOIN supplements s ON ps.supplement_id = s.id
WHERE s.flavor = 'sucre';

-- 3. Supprimer les suppléments sucrés
DELETE FROM supplements WHERE flavor = 'sucre';

-- 4. Vérification
SELECT 'Suppléments sucrés restants:' as message, COUNT(*) as count
FROM supplements
WHERE flavor = 'sucre';

-- 5. Résumé final
SELECT flavor, COUNT(*) as count
FROM supplements
GROUP BY flavor;
