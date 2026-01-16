-- =============================================
-- CHECK_MIGRATION.sql
-- Checks de sécurité PRÉ et POST migration
-- =============================================

-- =============================================
-- 1. CHECKS PRÉ-MIGRATION (à exécuter AVANT)
-- =============================================

-- 1.1 Vérifier que le restaurant cible existe
-- ⚠️ CRITICAL : Si 0 résultat → STOP
SELECT
    '✓ Restaurant existe' as check_name,
    id,
    name,
    slug,
    is_active
FROM restaurants
WHERE id = 1;  -- ⚠️ Remplacer par le restaurant_id cible si différent

-- RÉSULTAT ATTENDU : 1 ligne (ex: id=1, name='Le Marvelous', is_active=1)
-- SI 0 LIGNE : STOP — Restaurant n'existe pas


-- 1.2 Vérifier l'état initial des tables (vide ou données existantes?)
-- ⚠️ Si > 0 → risque de conflit avec idempotence
SELECT
    'PRE: Comptage tables' as check_name,
    (SELECT COUNT(*) FROM categories WHERE restaurant_id = 1 AND deleted_at IS NULL) as categories_count,
    (SELECT COUNT(*) FROM products WHERE restaurant_id = 1 AND deleted_at IS NULL) as products_count,
    (SELECT COUNT(*) FROM supplements WHERE restaurant_id = 1 AND deleted_at IS NULL) as supplements_count,
    (SELECT COUNT(*) FROM product_supplements WHERE deleted_at IS NULL) as links_count;

-- RÉSULTAT ATTENDU (1ère migration) : 0, 0, 0, 0
-- RÉSULTAT ATTENDU (re-migration) : X, Y, Z, W (données existantes)


-- 1.3 Vérifier les doublons potentiels de slug (categories)
-- ⚠️ CRITICAL : Si > 0 résultat → STOP (contrainte UNIQUE sera violée)
SELECT
    '⚠ Doublons slug categories' as check_name,
    restaurant_id,
    slug,
    COUNT(*) as duplicates
FROM categories
WHERE restaurant_id = 1 AND deleted_at IS NULL
GROUP BY restaurant_id, slug
HAVING COUNT(*) > 1;

-- RÉSULTAT ATTENDU : 0 ligne
-- SI > 0 LIGNE : STOP — Doublons détectés, migration impossible


-- 1.4 Vérifier les doublons potentiels de slug (products)
-- ⚠️ CRITICAL : Si > 0 résultat → STOP
SELECT
    '⚠ Doublons slug products' as check_name,
    restaurant_id,
    slug,
    COUNT(*) as duplicates
FROM products
WHERE restaurant_id = 1 AND deleted_at IS NULL
GROUP BY restaurant_id, slug
HAVING COUNT(*) > 1;

-- RÉSULTAT ATTENDU : 0 ligne
-- SI > 0 LIGNE : STOP — Doublons détectés, migration impossible


-- 1.5 Vérifier les doublons potentiels de nom (supplements)
-- ⚠️ WARNING : Si > 0 résultat → Les doublons seront skipped
-- Note: Les suppléments n'ont PAS de contrainte UNIQUE sur (restaurant_id, name)
SELECT
    '⚠ Doublons name supplements' as check_name,
    restaurant_id,
    name,
    COUNT(*) as duplicates
FROM supplements
WHERE restaurant_id = 1 AND deleted_at IS NULL
GROUP BY restaurant_id, name
HAVING COUNT(*) > 1;

-- RÉSULTAT ATTENDU : 0 ligne
-- SI > 0 LIGNE : WARNING — Suppléments existants seront skipped (idempotence par nom)


-- 1.6 Vérifier la validité du JSON options_config existant
-- ⚠️ Si erreur → Données corrompues
SELECT
    '✓ Validité JSON options_config' as check_name,
    id,
    slug,
    name
FROM products
WHERE restaurant_id = 1
  AND options_config IS NOT NULL
  AND JSON_VALID(options_config) = 0;

-- RÉSULTAT ATTENDU : 0 ligne
-- SI > 0 LIGNE : WARNING — JSON corrompu sur ces produits


-- =============================================
-- 2. CHECKS POST-MIGRATION (à exécuter APRÈS)
-- =============================================

-- 2.1 Vérifier les counts attendus
-- ✓ Valider le nombre de lignes migrées
SELECT
    'POST: Comptage tables' as check_name,
    (SELECT COUNT(*) FROM categories WHERE restaurant_id = 1 AND deleted_at IS NULL) as categories_count,
    (SELECT COUNT(*) FROM products WHERE restaurant_id = 1 AND deleted_at IS NULL) as products_count,
    (SELECT COUNT(*) FROM supplements WHERE restaurant_id = 1 AND deleted_at IS NULL) as supplements_count,
    (SELECT COUNT(*) FROM product_supplements WHERE deleted_at IS NULL) as links_count;

-- RÉSULTAT ATTENDU (selon dry-run) :
-- categories_count  : 12
-- products_count    : 47
-- supplements_count : 40
-- links_count       : 0 (ou plus selon menu.json)


-- 2.2 Vérifier l'absence de doublons slug (categories)
-- ✓ Garantir UNIQUE KEY respectée
SELECT
    '✓ Pas de doublons categories' as check_name,
    restaurant_id,
    slug,
    COUNT(*) as duplicates
FROM categories
WHERE restaurant_id = 1 AND deleted_at IS NULL
GROUP BY restaurant_id, slug
HAVING COUNT(*) > 1;

-- RÉSULTAT ATTENDU : 0 ligne
-- SI > 0 LIGNE : ERREUR CRITIQUE — Migration a créé des doublons


-- 2.3 Vérifier l'absence de doublons slug (products)
-- ✓ Garantir UNIQUE KEY respectée
SELECT
    '✓ Pas de doublons products' as check_name,
    restaurant_id,
    slug,
    COUNT(*) as duplicates
FROM products
WHERE restaurant_id = 1 AND deleted_at IS NULL
GROUP BY restaurant_id, slug
HAVING COUNT(*) > 1;

-- RÉSULTAT ATTENDU : 0 ligne
-- SI > 0 LIGNE : ERREUR CRITIQUE — Migration a créé des doublons


-- 2.4 Vérifier l'intégrité référentielle (products → categories)
-- ✓ Tous les produits doivent avoir une catégorie valide
SELECT
    '✓ Intégrité products → categories' as check_name,
    p.id,
    p.slug as product_slug,
    p.category_id,
    'ORPHELIN' as error
FROM products p
LEFT JOIN categories c ON p.category_id = c.id
WHERE p.restaurant_id = 1
  AND p.deleted_at IS NULL
  AND c.id IS NULL;

-- RÉSULTAT ATTENDU : 0 ligne
-- SI > 0 LIGNE : ERREUR CRITIQUE — Produits orphelins (catégorie inexistante)


-- 2.5 Vérifier l'intégrité référentielle (product_supplements → products)
-- ✓ Toutes les liaisons doivent pointer vers des produits valides
SELECT
    '✓ Intégrité liaisons → products' as check_name,
    ps.product_id,
    ps.supplement_id,
    'ORPHELIN' as error
FROM product_supplements ps
LEFT JOIN products p ON ps.product_id = p.id
WHERE ps.deleted_at IS NULL
  AND p.id IS NULL;

-- RÉSULTAT ATTENDU : 0 ligne
-- SI > 0 LIGNE : ERREUR CRITIQUE — Liaisons orphelines (produit inexistant)


-- 2.6 Vérifier l'intégrité référentielle (product_supplements → supplements)
-- ✓ Toutes les liaisons doivent pointer vers des suppléments valides
SELECT
    '✓ Intégrité liaisons → supplements' as check_name,
    ps.product_id,
    ps.supplement_id,
    'ORPHELIN' as error
FROM product_supplements ps
LEFT JOIN supplements s ON ps.supplement_id = s.id
WHERE ps.deleted_at IS NULL
  AND s.id IS NULL;

-- RÉSULTAT ATTENDU : 0 ligne
-- SI > 0 LIGNE : ERREUR CRITIQUE — Liaisons orphelines (supplément inexistant)


-- 2.7 Vérifier la validité des prix (> 0 et cohérents)
-- ✓ Tous les prix doivent être > 0 et price_solo <= price_menu
SELECT
    '✓ Validité des prix' as check_name,
    id,
    slug,
    name,
    price_solo,
    price_menu,
    CASE
        WHEN price_solo <= 0 THEN 'price_solo <= 0'
        WHEN price_menu IS NOT NULL AND price_menu < price_solo THEN 'price_menu < price_solo'
        ELSE 'OK'
    END as error
FROM products
WHERE restaurant_id = 1
  AND deleted_at IS NULL
  AND (
    price_solo <= 0
    OR (price_menu IS NOT NULL AND price_menu < price_solo)
  );

-- RÉSULTAT ATTENDU : 0 ligne
-- SI > 0 LIGNE : WARNING — Prix incohérents (vérifier les données JSON sources)


-- 2.8 Vérifier la validité du JSON options_config
-- ✓ Tous les JSON doivent être valides
SELECT
    '✓ Validité JSON options_config' as check_name,
    id,
    slug,
    name,
    options_config
FROM products
WHERE restaurant_id = 1
  AND deleted_at IS NULL
  AND options_config IS NOT NULL
  AND JSON_VALID(options_config) = 0;

-- RÉSULTAT ATTENDU : 0 ligne
-- SI > 0 LIGNE : ERREUR — JSON corrompu


-- 2.9 Vérifier la cohérence des prix (conversion centimes → euros)
-- ✓ Les prix doivent être dans une plage raisonnable (0.01€ à 999.99€)
SELECT
    '✓ Plage prix raisonnable' as check_name,
    id,
    slug,
    name,
    price_solo,
    price_menu,
    CASE
        WHEN price_solo < 0.01 THEN 'price_solo trop bas'
        WHEN price_solo > 999.99 THEN 'price_solo trop haut'
        WHEN price_menu IS NOT NULL AND price_menu < 0.01 THEN 'price_menu trop bas'
        WHEN price_menu IS NOT NULL AND price_menu > 999.99 THEN 'price_menu trop haut'
        ELSE 'OK'
    END as error
FROM products
WHERE restaurant_id = 1
  AND deleted_at IS NULL
  AND (
    price_solo < 0.01
    OR price_solo > 999.99
    OR (price_menu IS NOT NULL AND (price_menu < 0.01 OR price_menu > 999.99))
  );

-- RÉSULTAT ATTENDU : 0 ligne
-- SI > 0 LIGNE : WARNING — Prix hors plage (erreur conversion?)


-- 2.10 Liste des catégories migrées (pour validation visuelle)
-- ✓ Afficher toutes les catégories créées
SELECT
    'POST: Catégories migrées' as check_name,
    id,
    slug,
    name,
    sort_order,
    is_active
FROM categories
WHERE restaurant_id = 1
  AND deleted_at IS NULL
ORDER BY sort_order ASC;

-- RÉSULTAT ATTENDU : 12 lignes (selon dry-run)


-- 2.11 Échantillon de produits migrés (pour validation visuelle)
-- ✓ Afficher les 10 premiers produits
SELECT
    'POST: Échantillon produits' as check_name,
    p.id,
    p.slug,
    p.name,
    p.price_solo,
    p.price_menu,
    p.status,
    c.name as category_name
FROM products p
JOIN categories c ON p.category_id = c.id
WHERE p.restaurant_id = 1
  AND p.deleted_at IS NULL
ORDER BY p.id ASC
LIMIT 10;

-- RÉSULTAT ATTENDU : 10 lignes


-- 2.12 Échantillon de suppléments migrés (pour validation visuelle)
-- ✓ Afficher les 10 premiers suppléments
SELECT
    'POST: Échantillon suppléments' as check_name,
    id,
    name,
    price,
    status,
    sort_order
FROM supplements
WHERE restaurant_id = 1
  AND deleted_at IS NULL
ORDER BY sort_order ASC
LIMIT 10;

-- RÉSULTAT ATTENDU : 10 lignes


-- =============================================
-- RÉSUMÉ DÉCISION
-- =============================================

/*
CRITÈRES GO / NO-GO :

PRÉ-MIGRATION :
✓ GO   : Restaurant existe (check 1.1)
✓ GO   : 0 doublons slug categories (check 1.3)
✓ GO   : 0 doublons slug products (check 1.4)
⚠ WARN : Doublons name supplements (check 1.5) → seront skipped

POST-MIGRATION :
✓ GO   : Counts attendus (12 cat, 47 prod, 40 supp) (check 2.1)
✓ GO   : 0 doublons (checks 2.2, 2.3)
✓ GO   : 0 orphelins (checks 2.4, 2.5, 2.6)
✓ GO   : Prix valides (checks 2.7, 2.9)
✓ GO   : JSON valides (check 2.8)

ROLLBACK SI :
❌ ERREUR CRITIQUE détectée dans checks POST-migration
❌ Counts != attendus
❌ Doublons créés
❌ Orphelins détectés
*/
