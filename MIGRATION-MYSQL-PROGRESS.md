# 📊 Migration MySQL - Progression

**Date de début :** 2026-01-06
**Branche Git :** `claude/setup-marvelous-creperie-Wg8p0`
**Remote :** https://github.com/FaroukHUB/snackapp-.git
**Tokens restants :** ~104,000 / 200,000

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

## ✅ COMPLETÉ (Session précédente)

1. ✅ Setup Git sur serveur o2switch
2. ✅ Branche server-live créée (snapshot production)
3. ✅ Feature : Assignment auto suppléments (Salé/Sucré)
4. ✅ Fix : Suppression catégories custom
5. ✅ Fix : Préservation categoryIcons

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

## ⏳ À FAIRE - Migration MySQL Complète

### Phase 1 : Préparation (5k tokens)
- [ ] Vérifier tables MySQL existantes
- [ ] Analyser structure actuelle (MenuRepository)
- [ ] Créer script de migration JSON → MySQL

### Phase 2 : Schéma base de données (10k tokens)
- [ ] Table `categories` (id, name, description, icon, flavor, order)
- [ ] Table `products` (id, category_id, name, description, price, image)
- [ ] Table `supplements` (id, name, price, type)
- [ ] Table `category_supplements` (category_id, supplement_id)

### Phase 3 : Migration des données (15k tokens)
- [ ] Migrer catégories de le-marvelous.config.js
- [ ] Migrer customCategories de runtime
- [ ] Migrer produits
- [ ] Migrer suppléments + associations

### Phase 4 : Modification API (30k tokens)
- [ ] `add_category` → INSERT MySQL
- [ ] `edit_category` → UPDATE MySQL
- [ ] `delete_category` → DELETE MySQL (soft delete)
- [ ] `add_product` → INSERT MySQL
- [ ] GET endpoint → SELECT depuis MySQL
- [ ] Supprimer dépendances aux fichiers JSON

### Phase 5 : Tests (20k tokens)
- [ ] Test création catégorie
- [ ] Test suppression catégorie
- [ ] Test ajout produit
- [ ] Test affichage site public
- [ ] Vérifier suppléments auto-assignés

### Phase 6 : Déploiement (10k tokens)
- [ ] Backup complet base de données
- [ ] Déploiement sur serveur
- [ ] Tests en production
- [ ] Rollback si problème

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
