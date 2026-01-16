# RUNBOOK MIGRATION JSON → MYSQL

## 📋 ANALYSE DE SÉCURITÉ

### 1. Restaurant ID
- **Défaut** : `1`
- **Paramètre CLI** : `--restaurant-id=X`
- **Utilisation** : Passé à TOUS les repositories
- **Validation** : Vérifier que le restaurant existe AVANT migration (check 1.1)

### 2. Idempotence

| Table | Méthode | Clé unique | Contrainte MySQL |
|-------|---------|------------|------------------|
| **categories** | `getBySlug(restaurant_id, slug)` | (restaurant_id, slug) | ✅ UNIQUE KEY `uk_restaurant_slug` |
| **products** | `getBySlug(restaurant_id, slug)` | (restaurant_id, slug) | ✅ UNIQUE KEY `uk_restaurant_slug` |
| **supplements** | `getAll(restaurant_id)` puis comparaison par **nom** | ⚠️ (restaurant_id, name) | ❌ PAS DE CONTRAINTE UNIQUE |

**⚠️ RISQUE IDENTIFIÉ** :
- Les suppléments n'ont PAS de contrainte UNIQUE sur `(restaurant_id, name)`
- Si deux suppléments ont le même nom → le 2ème sera skipped
- Idempotence repose sur comparaison PHP, pas sur contrainte BDD

### 3. Conversion des Prix

```
JSON (centimes)     →  Conversion       →  MySQL (euros DECIMAL(8,2))
─────────────────────────────────────────────────────────────────────
550                 →  550 / 100        →  5.50
700                 →  700 / 100        →  7.00
1250                →  1250 / 100       →  12.50
```

**Validation** : Checks 2.7 et 2.9 dans CHECK_MIGRATION.sql

---

## ⚡ PROCÉDURE MIGRATION

### PHASE 1 : PRÉ-VÉRIFICATIONS

```bash
# 1.1 Exécuter les checks PRÉ-migration
mysql -u zajr1824_marvelous -p zajr1824_marvelous < CHECK_MIGRATION.sql

# Vérifier manuellement :
# ✓ Restaurant ID 1 existe
# ✓ 0 doublons slug categories
# ✓ 0 doublons slug products
# ⚠ Si doublons supplements → OK (seront skipped)
```

**CRITÈRES GO** :
- ✅ Restaurant existe
- ✅ 0 doublons slug categories
- ✅ 0 doublons slug products

**SI UN CHECK ÉCHOUE** : STOP — Ne pas migrer

---

### PHASE 2 : MIGRATION DRY-RUN

```bash
# 2.1 Test en mode dry-run
php database/migrate-json-to-mysql.php --dry-run --restaurant-id=1

# 2.2 Vérifier les counts affichés
# Attendu :
# - Catégories créées   : 12
# - Produits créés      : 47
# - Suppléments créés   : 40
# - Liaisons créées     : 0 (ou plus)
```

**CRITÈRES GO** :
- ✅ Script s'exécute sans erreur
- ✅ Counts cohérents avec menu.json
- ✅ Aucun warning critique

**SI ERREUR** : STOP — Corriger menu.json ou script

---

### PHASE 3 : MIGRATION PRODUCTION

```bash
# 3.1 MIGRATION RÉELLE (crée backup automatique)
php database/migrate-json-to-mysql.php --restaurant-id=1

# Le script crée automatiquement :
# - backup/menu_YYYY-MM-DD_HH-MM-SS.json
# - backup/menu.runtime_YYYY-MM-DD_HH-MM-SS.json
```

**⏱ DURÉE ESTIMÉE** : 10-30 secondes

**OBSERVER** :
- ✅ Backup créé dans `backup/`
- ✅ Chaque catégorie/produit/supplément loggué
- ✅ Aucune erreur affichée
- ✅ Message final : "✓ MIGRATION TERMINÉE AVEC SUCCÈS"

---

### PHASE 4 : POST-VÉRIFICATIONS

```bash
# 4.1 Exécuter les checks POST-migration
mysql -u zajr1824_marvelous -p zajr1824_marvelous < CHECK_MIGRATION.sql

# 4.2 Vérifier MANUELLEMENT :
# ✓ Check 2.1 : categories_count = 12, products_count = 47, supplements_count = 40
# ✓ Check 2.2 : 0 doublons categories
# ✓ Check 2.3 : 0 doublons products
# ✓ Check 2.4, 2.5, 2.6 : 0 orphelins
# ✓ Check 2.7, 2.9 : prix valides
# ✓ Check 2.8 : JSON valides
```

**CRITÈRES GO** :
- ✅ Tous les checks retournent 0 ligne (sauf 2.1, 2.10, 2.11, 2.12)
- ✅ Counts = attendus
- ✅ Échantillons visuels cohérents

**SI UN CHECK ÉCHOUE** : Voir PLAN DE ROLLBACK

---

### PHASE 5 : VALIDATION FONCTIONNELLE

```bash
# 5.1 Requêtes de validation manuelles
mysql -u zajr1824_marvelous -p zajr1824_marvelous

# Exemples :
SELECT * FROM categories WHERE restaurant_id = 1 LIMIT 5;
SELECT * FROM products WHERE restaurant_id = 1 LIMIT 5;
SELECT * FROM supplements WHERE restaurant_id = 1 LIMIT 5;

# Vérifier visuellement :
# - Noms corrects
# - Slugs corrects
# - Prix en euros (pas en centimes!)
# - Images présentes
```

---

## 🚨 PLAN DE ROLLBACK

### Scénario 1 : Erreur PENDANT la migration

**Symptômes** :
- Script s'arrête avec erreur
- Message "[ERROR]" affiché

**Action** :
```bash
# 1. Noter l'erreur affichée
# 2. La migration est PARTIELLE → données incohérentes

# 3. ROLLBACK COMPLET
mysql -u zajr1824_marvelous -p zajr1824_marvelous << 'EOF'
-- Supprimer TOUTES les données migrées
DELETE FROM product_supplements WHERE product_id IN (SELECT id FROM products WHERE restaurant_id = 1);
DELETE FROM products WHERE restaurant_id = 1;
DELETE FROM categories WHERE restaurant_id = 1;
DELETE FROM supplements WHERE restaurant_id = 1;
EOF

# 4. Corriger l'erreur (menu.json, script, BDD)
# 5. Relancer la migration
```

---

### Scénario 2 : Erreur APRÈS la migration (checks POST échouent)

**Symptômes** :
- Check 2.2 ou 2.3 : doublons détectés
- Check 2.4, 2.5, 2.6 : orphelins détectés
- Check 2.1 : counts != attendus

**Action** :
```bash
# 1. Analyser le problème (quel check échoue?)

# 2. ROLLBACK COMPLET (si données incohérentes)
mysql -u zajr1824_marvelous -p zajr1824_marvelous << 'EOF'
DELETE FROM product_supplements WHERE product_id IN (SELECT id FROM products WHERE restaurant_id = 1);
DELETE FROM products WHERE restaurant_id = 1;
DELETE FROM categories WHERE restaurant_id = 1;
DELETE FROM supplements WHERE restaurant_id = 1;
EOF

# 3. Restaurer les backups JSON (si nécessaire)
# Les backups sont dans backup/menu_YYYY-MM-DD_HH-MM-SS.json

# 4. Analyser la cause racine
# 5. Corriger et relancer
```

---

### Scénario 3 : Données corrompues découvertes plus tard

**Symptômes** :
- Admin panel affiche des erreurs
- Prix incorrects (en centimes au lieu d'euros)
- Produits orphelins

**Action** :
```bash
# 1. Identifier les données corrompues (requêtes SQL)
SELECT * FROM products WHERE price_solo > 100 AND restaurant_id = 1;

# 2. Correction ciblée (si peu de lignes)
UPDATE products SET price_solo = price_solo / 100 WHERE price_solo > 100 AND restaurant_id = 1;

# 3. OU Rollback complet + re-migration
# (voir Scénario 1)
```

---

## 📊 RÉSUMÉ DÉCISION

### ✅ GO si :
1. Tous les checks PRÉ-migration passent
2. Dry-run réussit avec counts attendus
3. Migration production termine sans erreur
4. Tous les checks POST-migration passent
5. Validation fonctionnelle OK

### ❌ NO-GO si :
1. Restaurant n'existe pas
2. Doublons slug détectés PRÉ-migration
3. Dry-run échoue
4. Erreur pendant migration
5. Checks POST-migration échouent
6. Counts != attendus
7. Prix incohérents (< 0.01€ ou > 999.99€)
8. JSON invalides
9. Orphelins détectés

### ⚠️ WARN (continuer avec prudence) si :
1. Doublons name supplements PRÉ-migration → seront skipped
2. Données existantes en BDD → idempotence activée
3. Prix = 0 pour certains produits → vérifier menu.json

---

## 🎯 COMMANDES RAPIDES

```bash
# PRÉ-CHECKS
mysql -u zajr1824_marvelous -p zajr1824_marvelous < CHECK_MIGRATION.sql | grep "PRE:"

# DRY-RUN
php database/migrate-json-to-mysql.php --dry-run --restaurant-id=1

# MIGRATION PROD
php database/migrate-json-to-mysql.php --restaurant-id=1

# POST-CHECKS
mysql -u zajr1824_marvelous -p zajr1824_marvelous < CHECK_MIGRATION.sql | grep "POST:"

# ROLLBACK COMPLET
mysql -u zajr1824_marvelous -p zajr1824_marvelous << 'EOF'
DELETE FROM product_supplements WHERE product_id IN (SELECT id FROM products WHERE restaurant_id = 1);
DELETE FROM products WHERE restaurant_id = 1;
DELETE FROM categories WHERE restaurant_id = 1;
DELETE FROM supplements WHERE restaurant_id = 1;
EOF
```

---

**⚠️ RAPPEL IMPORTANT** :
- Ne PAS activer `$useMySQL` dans le code avant validation complète
- Ne PAS toucher à l'admin ou au frontend pendant la migration
- Faire la migration en heures creuses si possible
- Garder les backups JSON pendant au moins 7 jours

---

**Auteur** : Claude (Phase 3.1 - Validation Sécurité)
**Date** : 2026-01-16
**Version** : 1.0
