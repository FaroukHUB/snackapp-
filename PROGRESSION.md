# PROGRESSION DU PROJET SNACKAPP

## État Initial - 2026-01-16

### Contexte
Projet SnackApp - Application web de commande de snacks/pizzas

### Architecture Établie
- **product.id** : ID numérique (INT, BDD, relations)
- **product.slug** : Identité métier (string, UI, SEO, featured)

### Bug Identifié
**Problème** : Le modal produit ne s'ouvre pas lors du clic sur produits featured

**Cause Racine** :
- `featured.items` dans `menu.json` contient des **slugs** (strings)
- `Config.getProduct(productId)` utilise une comparaison stricte `p.id === productId`
- `p.id` est numérique (INT)
- `productId` reçu = slug (string)
- => Rupture du contrat d'identité : ID vs SLUG

**Solution Prévue** :
Modifier `Config.getProduct()` pour supporter :
- Lookup par ID si paramètre numérique
- Lookup par slug si paramètre string non-numérique
- Conserver comparaison stricte (pas de ==)

### Règles de Développement
- ❌ Pas de `==` (comparaison stricte uniquement)
- ❌ Pas de modification BDD
- ❌ Pas de refactoring hors périmètre
- ✅ ID numérique pour relations BDD
- ✅ Slug pour identité UI/SEO

---

## Sessions

### [TERMINÉE] Session 2026-01-16 - Phase 0 : Cadrage Migration MySQL
**Objectif** : Cadrage complet de la migration JSON → MySQL

**Statut** : ✅ TERMINÉE

#### Livrable Produit
**Fichier créé** : `MIGRATION.md`

#### Contenu du Document
1. **Inventaire complet** : 21 entités métier identifiées
   - 17 tables existantes (déjà en MySQL)
   - 3 nouvelles tables à créer (delivery_persons, tgtg_baskets, admin_pins)
   - 1 extension optionnelle (product_options)

2. **Schéma MySQL cible** :
   - Structure complète des 21 tables
   - Relations et contraintes (CASCADE, SET NULL, UNIQUE)
   - 2 vues matérialisées (stats, top products)

3. **Règles métier critiques** :
   - Gestion commandes (statuts, calculs)
   - Système fidélité (points, transactions, codes)
   - Codes promo (validation, calcul réduction)
   - Produits et variantes (prix, options spéciales)
   - Clients (identification, adresses JSON, préférences)
   - Tags automatiques (VIP, Régulier, Nouveau)
   - TGTG baskets (disponibilité, expiration)
   - Livreurs (attribution automatique)

4. **Phases de migration** :
   - Phase 1 : Préparation BDD ✅ FAIT
   - Phase 2 : Migration nouvelles entités ⏳ EN ATTENTE
   - Phase 3 : Extensions produits (options) ⏳ EN ATTENTE
   - Phase 4 : Refactoring frontend ⏳ EN ATTENTE
   - Phase 5 : Suppression JSON ⏳ EN ATTENTE
   - Phase 6 : Optimisations ⏳ EN ATTENTE

5. **Entités analysées** :
   - Restaurants (multi-tenant ready)
   - Paramètres restaurant
   - Horaires d'ouverture
   - Catégories produits (crepes-salees, sucres-sales, crepes-sucrees, gaufres, bubble-waffle, boissons-chaudes, sodas-eaux)
   - Produits (prix solo/menu, statuts, options spéciales)
   - Suppléments (liaison N:N avec produits)
   - Clients (7 clients existants dans JSON)
   - Tags clients (segmentation VIP/Régulier/Nouveau)
   - Commandes (structure items + supplements)
   - Système fidélité (transactions, récompenses)
   - Codes promo (validation complexe)
   - FAQ
   - Utilisateurs admin
   - Livreurs (JSON vide actuellement)
   - TGTG baskets (1 panier test)
   - PIN admin (à hacher)

6. **Risques et métriques** :
   - Perte de données : backup obligatoire
   - Downtime : 2-5 minutes estimé
   - Cohérence : scripts de validation
   - Performance : indexes optimisés
   - Métriques succès : intégrité 100%, requêtes < 200ms

#### Fichiers Analysés
- `admin-panel-v2/data/customers.json` (7 clients)
- `admin-panel-v2/data/orders.json` (structure complète)
- `config/menu.json` (7 catégories, ~50 produits)
- `admin-panel-v2/data/loyalty_points.json` (vide)
- `admin-panel-v2/data/livreurs.json` (vide)
- `admin-panel-v2/data/restaurant-status.json`
- `admin-panel-v2/data/settings.json`
- `config/restaurant.json` (config complète)
- `admin-panel-v2/data/tgtg.json` (1 panier test)
- `admin-panel-v2/data/admin-pin.json`
- `database/schema.sql` (schéma existant)
- `database/migrations/*.sql` (4 migrations)
- `database/repositories/*.php` (5 repositories)

#### Règles Respectées
- ✅ Aucun code écrit
- ✅ Aucun fichier existant modifié
- ✅ Aucune question ouverte
- ✅ Document complet et actionnable

---

### [TERMINÉE] Session 2026-01-16 - Correction Lookup Produit
**Objectif** : Correction lookup produit (ID vs SLUG)

**Statut** : ✅ CORRIGÉ

#### Problème Résolu
Le modal produit ne s'ouvrait pas lors du clic sur les produits featured car :
- `featured.items` contient des **slugs** (strings, ex: "margherita-26cm")
- `Config.getProduct()` utilisait une comparaison stricte avec **id numérique**
- Résultat : `null` retourné, modal vide

#### Solution Implémentée
**Fichier modifié** : `snackup/frontend/js/config.js`
**Fonction** : `Config.getProduct(productId)` (lignes 209-233)

**Changements** :
1. Détection automatique du type de paramètre :
   - Si numérique (number ou string "123") → lookup par `id`
   - Si non-numérique (string "margherita-26cm") → lookup par `slug`

2. Conversion sécurisée :
   - String numérique converti en `Number` pour comparaison stricte
   - Regex `/^\d+$/` pour détecter les strings numériques

3. Comparaisons strictes conservées (===) :
   - `p.id === numericId` pour lookup numérique
   - `p.slug === productId` pour lookup par slug

**Code ajouté** :
```javascript
// Détecter si on cherche par ID numérique ou par slug
const isNumericLookup = typeof productId === 'number' ||
                        (typeof productId === 'string' && /^\d+$/.test(productId));

// Convertir en nombre si c'est un string numérique
const numericId = isNumericLookup ? Number(productId) : null;

for (const category of (this.menu?.categories || [])) {
    let product;

    if (isNumericLookup) {
        // Lookup par ID numérique (strict)
        product = category.items?.find(p => p.id === numericId);
    } else {
        // Lookup par slug (strict)
        product = category.items?.find(p => p.slug === productId);
    }

    if (product) {
        return { ...product, categoryId: category.id, categoryName: category.name };
    }
}
```

#### Fichiers Vérifiés (aucune modification nécessaire)
- `snackup/frontend/js/products.js` :
  - `renderFeatured()` ligne 189 : utilise `Config.getProduct(slug)` ✓
  - `openProductModal()` ligne 905 : supporte ID et slug ✓

#### Règle d'Architecture Respectée
- ✅ `product.id` = ID numérique (INT, BDD, relations)
- ✅ `product.slug` = Identité métier (string, UI, SEO, featured)
- ✅ Pas de `==` (comparaisons strictes uniquement)
- ✅ Pas de modification BDD
- ✅ Pas de refactoring hors périmètre

#### Tests Validés (logique)
1. ✅ Lookup par slug : `Config.getProduct("margherita-26cm")`
2. ✅ Lookup par ID : `Config.getProduct(123)`
3. ✅ Lookup par ID string : `Config.getProduct("123")`
4. ✅ Featured products : clic → modal → panier
5. ✅ Produits normaux : fonctionnement inchangé
