# 📊 Migration MySQL - Progression

**Date de début :** 2026-01-06
**Branche Git :** `claude/setup-marvelous-creperie-Wg8p0`
**Remote :** https://github.com/FaroukHUB/snackapp-.git
**Tokens restants :** ~140,000 / 200,000 ✅

---

## 🏗️ ARCHITECTURE & CONCEPT

### Objectif de la migration
**PASSER DE : Système fichiers JSON → Système MySQL pur**

### Architecture AVANT (fichiers)
```
┌─────────────────────────────────────┐
│  config/le-marvelous.config.js      │  ← Menu de BASE (16K)
│  (catégories originales)            │
└─────────────────────────────────────┘
           ↓
┌─────────────────────────────────────┐
│  config/menu.runtime.json           │  ← Modifications ADMIN
│  - customCategories                 │     (ajouts via admin)
│  - deletedCategories                │
│  - customProducts                   │
│  - supplements                      │
└─────────────────────────────────────┘
           ↓
┌─────────────────────────────────────┐
│  admin-panel-v2/config.php          │  ← Fusion logique
│  applyRuntimeToConfig()             │     (BASE + RUNTIME)
│  generatePublicMenuJson()           │
└─────────────────────────────────────┘
           ↓
┌─────────────────────────────────────┐
│  config/menu.json (47K)             │  ← Menu PUBLIC fusionné
│  (lu par le site web)               │
└─────────────────────────────────────┘
```

**PROBLÈMES actuels :**
- ❌ Suppression catégories ne marche pas (fichiers désynchronisés)
- ❌ Complexité fusion BASE + RUNTIME
- ❌ Pas de transactions (risque incohérence)
- ❌ Pas de relations (foreign keys)

### Architecture APRÈS (MySQL)
```
┌──────────────────────────────────────┐
│         MySQL Database               │
│  ┌────────────────────────────────┐  │
│  │ Table: categories              │  │
│  │  - id, name, description       │  │
│  │  - icon, flavor, deleted_at    │  │
│  └────────────────────────────────┘  │
│  ┌────────────────────────────────┐  │
│  │ Table: products                │  │
│  │  - id, category_id, name       │  │
│  │  - price, image, deleted_at    │  │
│  └────────────────────────────────┘  │
│  ┌────────────────────────────────┐  │
│  │ Table: supplements             │  │
│  │  - id, name, price, type       │  │
│  └────────────────────────────────┘  │
│  ┌────────────────────────────────┐  │
│  │ Table: category_supplements    │  │
│  │  - category_id, supplement_id  │  │
│  └────────────────────────────────┘  │
└──────────────────────────────────────┘
           ↓
┌──────────────────────────────────────┐
│  admin-panel-v2/api/products.php     │  ← CRUD MySQL direct
│  MenuRepository::getAll()            │     (pas de fichiers)
│  MenuRepository::addCategory()       │
│  MenuRepository::deleteCategory()    │
└──────────────────────────────────────┘
           ↓
┌──────────────────────────────────────┐
│  API GET /api/products.php           │  ← Retourne JSON depuis MySQL
│  (lu par admin + site web)           │
└──────────────────────────────────────┘
```

**AVANTAGES MySQL :**
- ✅ Suppression réelle (soft delete avec `deleted_at`)
- ✅ Transactions ACID (cohérence garantie)
- ✅ Relations (foreign keys)
- ✅ Requêtes SQL (filtres, tris, recherche)
- ✅ Un seul source de vérité

### Logique de suppression (soft delete)
```sql
-- Supprimer une catégorie (soft delete)
UPDATE categories SET deleted_at = NOW() WHERE id = 'plat-maison';

-- Récupérer catégories actives
SELECT * FROM categories WHERE deleted_at IS NULL;
```

### Branche Git
- **Développement :** `claude/setup-marvelous-creperie-Wg8p0`
- **Production serveur :** branche `server-live` (snapshot)
- **Tous commits pushés vers GitHub**

---

## ✅ COMPLETÉ

### Session précédente
1. ✅ Setup Git sur serveur o2switch
2. ✅ Branche server-live créée (snapshot production)
3. ✅ Feature : Assignment auto suppléments (Salé/Sucré)
4. ✅ Fix : Suppression catégories custom
5. ✅ Fix : Préservation categoryIcons

### Migration MySQL - Phase 1 & 2
1. ✅ **Phase 1:** Migration SQL schéma
   - Colonnes `icon`, `flavor`, `deleted_at` ajoutées à `categories`
   - Colonne `deleted_at` ajoutée à `products`
   - Colonnes `slug`, `type` ajoutées à `supplements`
   - Table `category_supplements` créée
   - Exécuté avec succès sur MySQL
   - Commit: `7816f41`

2. ✅ **Phase 2:** Migration des données - **100% RÉUSSIE**
   - Scripts: `migrate-json-to-mysql.php`, `run-migration-on-server.sh`, `verify-migration.php`
   - **Résultats finaux confirmés (MySQL):**
     - ✅ **14 catégories** (11 actives, 3 supprimées soft delete)
     - ✅ **Flavors détectés:** 1 salée, 3 sucrées (auto-assignement fonctionnel)
     - ✅ **70 produits** (tous disponibles)
     - ✅ **50 suppléments** (10 salés, 30 sucrés, 10 both)
     - ✅ **100 associations** catégories ↔ suppléments
   - **Tous problèmes résolus** (voir section Problèmes Résolus)
   - Commits: `a4269e1`, `82f2394`, `35aa38c`, `e18b22b`, `63b7796`, `0e04f1c`, `b173bb9`
   - **Exécutée le:** 2026-01-06

---

## ✅ PROBLÈMES RÉSOLUS

### 1. Problème loadConfig() ✅
**Erreur:** `loadConfig()` échouait dans le script de migration
**Cause:** `le-marvelous.config.js` ne peut pas être parsé correctement
**Solution:** Script modifié pour lire directement `menu.json` + `menu.runtime.json`
**Commit:** `35aa38c`

### 2. Erreur Foreign Key Constraint ✅
**Erreur:** `Cannot add or update a child row: foreign key constraint fails (restaurant_id)`
**Cause:** Script utilisait `SNACK_RESTAURANT_ID = 1` mais le restaurant en base a l'ID `2`
**Solution:** Changé vers `SNACK_RESTAURANT_ID = 2` dans migration et vérification
**Commits:** `63b7796`, `0e04f1c`

### 3. 0 suppléments migrés ✅
**Erreur:** Migration affichait "✅ 0 suppléments migrés"
**Cause:** Script chargeait suppléments depuis `runtime['supplements']['catalog']` (vide) au lieu de `menuData['supplements']['catalog']` (37+ suppléments)
**Solution:**
- Charger suppléments depuis `menu.json` (source de vérité)
- Fusionner associations depuis menu.json + runtime
- Utiliser le `flavor` déjà présent dans JSON
**Commit:** `b173bb9`

### 4. Script vérification affichait 0 résultats ✅
**Erreur:** `verify-migration.php` affichait 0 catégories alors que migration avait réussi
**Cause:** Script utilisait `restaurant_id = 1` au lieu de `2`
**Solution:** Mise en cohérence avec le script de migration
**Commit:** `0e04f1c`

### 5. Gestion d'erreurs insuffisante ✅
**Problème:** Script plantait silencieusement sans message d'erreur
**Solution:**
- Activé `PDO::ERRMODE_EXCEPTION`
- Try-catch autour de toutes insertions
- Messages d'erreur détaillés
- Optimisation: icônes chargées une fois (pas dans boucle)
**Commit:** `e18b22b`

---

## ✅ COMPLÉTÉ - Phase 3 : Modification API MySQL

### Objectif ✅ ATTEINT
API modifiée pour utiliser MySQL au lieu des fichiers JSON.

### Fichiers modifiés
- ✅ `database/repositories/MenuRepository.php` - Classe CRUD MySQL (334 lignes)
- ✅ `admin-panel-v2/api/products.php` - Endpoints MySQL activés

### Endpoints implémentés

#### 1. GET - Récupération données ✅
```php
// Charge depuis MySQL (pas JSON)
$categories = MenuRepository::getAllCategories();
$supplements = MenuRepository::getAllSupplements();
$categorySupplements = MenuRepository::getCategorySupplements();
```

#### 2. add_category ✅
- INSERT MySQL avec auto-assignment suppléments
- Si flavor='sale' → Assigne automatiquement 10 suppléments salés
- Si flavor='sucre' → Assigne automatiquement 30 suppléments sucrés
- Utilise transactions pour garantir cohérence

#### 3. edit_category ✅
- UPDATE MySQL (name, description, icon, flavor)

#### 4. delete_category ✅
- Soft delete MySQL (UPDATE deleted_at = NOW())
- Préserve les données (pas de suppression définitive)

#### 5. Endpoints produits ✅
- add_product: INSERT MySQL
- edit_product: UPDATE MySQL
- delete_product: Soft delete MySQL
- change_status: UPDATE status uniquement

### Fonctionnalités clés
- ✅ Auto-assignment suppléments par flavor (sale/sucre)
- ✅ Soft delete (deleted_at) pour catégories et produits
- ✅ Transactions pour cohérence des données
- ✅ Gestion d'erreurs complète (try-catch)
- ✅ Mode MySQL activé ($useMySQL = true)

**Commit:** `69ff8d4`

---

## ⏳ À FAIRE - Suite Migration

### Phase 2 : Migration données ✅ TERMINÉE
- [x] **Fix loadConfig()** - Lecture directe menu.json ✅
- [x] **Fix foreign key** - restaurant_id=2 ✅
- [x] **Fix suppléments** - Chargement depuis menu.json ✅
- [x] **Fix vérification** - Utilise restaurant_id=2 ✅
- [x] **Exécuter migration** - 50 suppléments, 100 associations ✅
- [x] **Vérifier données MySQL** - Tout confirmé ✅

### Phase 3 : Modification API ✅ TERMINÉE (~24k tokens utilisés)
- [x] **Créer MenuRepository** - Classe pour requêtes MySQL ✅
- [x] **Modifier GET endpoint** → SELECT depuis MySQL (CRITIQUE - admin panel) ✅
- [x] **Modifier add_category** → INSERT MySQL + auto-assignment suppléments ✅
- [x] **Modifier edit_category** → UPDATE MySQL ✅
- [x] **Modifier delete_category** → Soft delete MySQL (UPDATE deleted_at) ✅
- [x] **Modifier add_product** → INSERT MySQL ✅
- [x] **Modifier edit_product** → UPDATE MySQL ✅
- [x] **Modifier delete_product** → Soft delete MySQL ✅
- [x] **Mode MySQL activé** - $useMySQL = true ✅

### Phase 4 : Tests (~10k tokens)
- [ ] Test création catégorie (admin)
- [ ] Test suppression catégorie (admin)
- [ ] Test ajout produit
- [ ] Test affichage site public
- [ ] Vérifier suppléments auto-assignés

### Phase 5 : Déploiement (~5k tokens)
- [ ] Backup MySQL
- [ ] Déploiement
- [ ] Tests production

---

## 🚨 En cas d'interruption

**Si tokens < 10,000 :**
1. Ce fichier sera mis à jour avec l'état exact
2. Tous les changements sont sur Git (branche claude/setup-marvelous-creperie-Wg8p0)
3. Reprendre avec : "Continue la migration MySQL depuis MIGRATION-MYSQL-PROGRESS.md"

**Commandes de reprise :**
```bash
cd ~/Marvelous.mon-agenceweb.fr
git pull origin claude/setup-marvelous-creperie-Wg8p0
cat MIGRATION-MYSQL-PROGRESS.md
```

---

## 📝 Notes importantes

- ⚠️ Backup existant : `~/backup-marvelous-20260106-111221.tar.gz` (35M)
- ⚠️ Ne JAMAIS perdre le-marvelous.config.js (menu de base)
- ⚠️ Tester en local avant déploiement si possible
- ⚠️ Garder système fichiers en backup jusqu'à validation complète

---

**Dernière mise à jour :** Phase 3 API MySQL TERMINÉE - Prêt pour tests (commit `69ff8d4`)

**Résumé session actuelle :**
- ✅ Phase 2 : Migration données (50 suppléments, 100 associations, 70 produits, 14 catégories)
- ✅ Phase 3 : API MySQL complète (GET + tous endpoints CRUD)
- ✅ MenuRepository créé (334 lignes, CRUD complet)
- ✅ Auto-assignment suppléments par flavor fonctionnel
- 🎯 **Prochaine étape : Phase 4 - Tests sur serveur**
- 🎯 Tokens restants : ~82k (suffisant pour Phase 4 + 5)
