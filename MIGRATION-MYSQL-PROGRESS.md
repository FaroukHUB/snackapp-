# 📊 Migration MySQL - Progression

**Date de début :** 2026-01-06
**Branche Git :** `claude/setup-marvelous-creperie-Wg8p0`
**Remote :** https://github.com/FaroukHUB/snackapp-.git
**Tokens restants :** ~77,000 / 200,000 ⚠️

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

### Migration MySQL (en cours)
1. ✅ **Phase 1:** Migration SQL schéma
   - Colonnes `icon`, `flavor`, `deleted_at` ajoutées à `categories`
   - Colonne `deleted_at` ajoutée à `products`
   - Colonnes `slug`, `type` ajoutées à `supplements`
   - Table `category_supplements` créée
   - Commit: `7816f41`

2. ✅ **Phase 2:** Script migration données créé
   - Script `database/migrate-json-to-mysql.php`
   - Migre catégories, produits, suppléments, associations
   - Commit: `a4269e1`, `82f2394`

---

## 🔄 EN COURS - Problème loadConfig()

**Erreur actuelle:** `loadConfig()` échoue dans le script de migration

**Cause:** `le-marvelous.config.js` ne peut pas être parsé correctement

**Solution à implémenter:** Lire directement `menu.json` au lieu de `le-marvelous.config.js`

---

## 🔄 EN COURS

### Problème actuel : Catégories ne se suppriment pas

**Diagnostic :**
- Backend fonctionne (catégories ajoutées à `deletedCategories`)
- `generatePublicMenuJson()` échoue → `loadConfig()` retourne null
- Le menu est actuellement en **fichiers JSON**, pas MySQL
- Migration MySQL **incomplète** ou **non faite**

**Fichiers impliqués :**
- `config/le-marvelous.config.js` (16K) - menu de base
- `config/menu.runtime.json` - modifications admin
- `config/menu.json` (47K) - menu public fusionné

---

## ⏳ À FAIRE - Suite Migration

### IMMÉDIAT (~3k tokens)
- [ ] **Fix script migration:** Lire menu.json directement
- [ ] **Exécuter migration données** vers MySQL
- [ ] **Vérifier données** dans MySQL

### Phase 3 : Modification API (~30k tokens) **⚠️ CRITIQUE**
- [ ] Modifier `add_category` → INSERT MySQL + assignment suppléments
- [ ] Modifier `edit_category` → UPDATE MySQL
- [ ] Modifier `delete_category` → Soft delete MySQL
- [ ] Modifier `add_product` → INSERT MySQL
- [ ] **Modifier GET endpoint** → SELECT depuis MySQL (pas fichiers)
- [ ] Supprimer appels à loadConfig/generatePublicMenuJson

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

**Dernière mise à jour :** En attente de décision utilisateur (fix rapide ou migration complète)
