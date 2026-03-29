# 🍕 PROJET CUISINE TYPES - UNIVERSALISATION SNACKUP

**Date de début:** 2026-03-20
**Branche:** `claude/resume-snackup-context-g4jKx`
**Statut:** 🔍 EXPLORATION EN COURS

---

## 📋 PLAN GÉNÉRAL DU PROJET

### PHASE 1 - EXPLORATION & CONCEPTION ⏳ EN COURS
- [ ] 1.1 - Explorer la structure actuelle du codebase
- [ ] 1.2 - Analyser les tables existantes (categories, products, formules, supplements)
- [ ] 1.3 - Comprendre le système multi-tenant et routing par domaine
- [ ] 1.4 - Identifier les repositories et logique métier existante
- [ ] 1.5 - Modéliser la BDD complète (nouvelles tables + relations)
- [ ] 1.6 - Validation de la modélisation avec le client

### PHASE 2 - MIGRATIONS BDD
- [ ] 2.1 - Créer migration: table `cuisine_types`
- [ ] 2.2 - Créer migration: table `cuisine_type_steps`
- [ ] 2.3 - Créer migration: table `cuisine_type_step_options`
- [ ] 2.4 - Créer migration: table `restaurant_cuisine_types`
- [ ] 2.5 - Créer migration: table `restaurant_cuisine_steps`
- [ ] 2.6 - Créer migration: table `restaurant_step_options`
- [ ] 2.7 - Créer migration: ALTER TABLE `categories` (add cuisine_type_id, is_common)
- [ ] 2.8 - Créer migration DOWN pour rollback complet
- [ ] 2.9 - Créer seeder: peupler `cuisine_types` (tacos, burger, kebab, pizza, etc.)
- [ ] 2.10 - Créer seeder: peupler `cuisine_type_steps` (étapes par défaut par type)
- [ ] 2.11 - Créer seeder: peupler `cuisine_type_step_options` (options exemples)

### PHASE 3 - BACKEND REPOSITORIES
- [ ] 3.1 - Créer `CuisineTypeRepository.php` (lecture types prédéfinis)
- [ ] 3.2 - Créer `RestaurantCuisineRepository.php` (config types/étapes/options par resto)
- [ ] 3.3 - Adapter `MenuRepository.php` (support cuisine_types + rétrocompat)
- [ ] 3.4 - Créer `OnboardingRepository.php` (détection + setup initial)

### PHASE 4 - API ENDPOINTS
- [ ] 4.1 - GET `/api/admin/cuisine-types` (liste types disponibles)
- [ ] 4.2 - GET `/api/admin/restaurant-cuisine-types` (types actifs du resto)
- [ ] 4.3 - POST `/api/admin/restaurant-cuisine-types` (activer un type)
- [ ] 4.4 - DELETE `/api/admin/restaurant-cuisine-types/{id}` (désactiver)
- [ ] 4.5 - PUT `/api/admin/restaurant-cuisine-types/reorder` (réordonner)
- [ ] 4.6 - GET `/api/admin/cuisine-steps/{typeId}` (étapes config d'un type)
- [ ] 4.7 - PUT `/api/admin/cuisine-steps/{stepId}` (modifier config étape)
- [ ] 4.8 - GET `/api/admin/step-options/{stepId}` (options d'une étape)
- [ ] 4.9 - POST `/api/admin/step-options` (ajouter option custom)
- [ ] 4.10 - PUT `/api/admin/step-options/{optionId}` (modifier option)
- [ ] 4.11 - DELETE `/api/admin/step-options/{optionId}` (supprimer option)

### PHASE 5 - ADMIN UI (ONBOARDING)
- [ ] 5.1 - Créer page `onboarding-cuisine-types.php` (sélection initiale)
- [ ] 5.2 - JavaScript: multi-sélection avec preview icônes
- [ ] 5.3 - Validation et création automatique des étapes/options
- [ ] 5.4 - Redirection vers tableau de bord admin après setup

### PHASE 6 - ADMIN UI (GESTION)
- [ ] 6.1 - Créer page `manage-cuisine-types.php` (liste types actifs)
- [ ] 6.2 - Drag & Drop réordonnancement (SortableJS)
- [ ] 6.3 - Bouton "Ajouter un type" (depuis liste prédéfinie)
- [ ] 6.4 - Créer page `edit-cuisine-steps.php` (config étapes d'un type)
- [ ] 6.5 - Interface: activer/désactiver étape
- [ ] 6.6 - Interface: renommer étape (custom_name)
- [ ] 6.7 - Interface: configurer min/max choices, required, allow_removal, price_modifier
- [ ] 6.8 - Interface: gérer les options (add/edit/delete/reorder)
- [ ] 6.9 - Intégrer dans le menu admin existant

### PHASE 7 - ADAPTATION GESTION PRODUITS
- [ ] 7.1 - Modifier `categories.php` (ajouter colonne cuisine_type)
- [ ] 7.2 - Modifier `products.php` (filtrer par type de cuisine)
- [ ] 7.3 - Assurer rétrocompatibilité (catégories sans cuisine_type_id)

### PHASE 8 - FRONTEND CLIENT (VUE MENU)
- [ ] 8.1 - Pas de modification visuelle (client ne voit pas les types)
- [ ] 8.2 - Vérifier que l'affichage existant fonctionne toujours
- [ ] 8.3 - Tester avec catégories mixtes (avec/sans cuisine_type_id)

### PHASE 9 - FRONTEND CLIENT (COMPOSITION PRODUIT)
- [ ] 9.1 - Créer composant JS `ProductComposer.js`
- [ ] 9.2 - Charger les étapes de composition depuis l'API
- [ ] 9.3 - Afficher étapes dynamiques selon le cuisine_type du produit
- [ ] 9.4 - Validation min/max choices
- [ ] 9.5 - Gestion retrait d'ingrédients (allow_removal)
- [ ] 9.6 - Calcul prix avec modificateurs
- [ ] 9.7 - Ajout au panier avec composition détaillée

### PHASE 10 - FEATURE FLAG & DÉPLOIEMENT
- [ ] 10.1 - Ajouter `CUISINE_TYPES_ENABLED` dans `bootstrap.php`
- [ ] 10.2 - Wrapper toutes les nouvelles features avec ce flag
- [ ] 10.3 - Tester avec flag OFF (mode legacy pur)
- [ ] 10.4 - Tester avec flag ON (nouveaux restaurants)
- [ ] 10.5 - Tester avec flag ON (restaurants existants = pas d'onboarding)

### PHASE 11 - TESTS & VALIDATION
- [ ] 11.1 - Test: Nouveau restaurant → onboarding complet
- [ ] 11.2 - Test: Restaurant existant → aucun impact
- [ ] 11.3 - Test: Rollback flag OFF → tout fonctionne
- [ ] 11.4 - Test: Commande avec produit composable
- [ ] 11.5 - Test: Commande avec produit simple (sans étapes)
- [ ] 11.6 - Test: Multi-tenant (plusieurs restaurants en parallèle)

### PHASE 12 - DOCUMENTATION
- [ ] 12.1 - Documenter la structure BDD
- [ ] 12.2 - Guide admin: Gérer les types de cuisine
- [ ] 12.3 - Guide admin: Configurer les étapes
- [ ] 12.4 - Guide technique: Ajouter un nouveau type de cuisine

---

## 🎯 TÂCHE ACTUELLE

**Phase 1.6 - Validation de la modélisation avec le client ⏳**

### ✅ Actions complétées:
- ✅ Analyse complète de l'arborescence des fichiers
- ✅ Identification de tous les repositories existants
- ✅ Compréhension du système multi-tenant (InstanceManager)
- ✅ Analyse du schéma BDD actuel (schema.sql)
- ✅ Modélisation BDD complète créée
- ✅ Documentation DATABASE_MODELING.md générée

### Fichiers explorés:
- ✅ `/snackup/backend/repositories/MenuRepository.php`
- ✅ `/snackup/backend/Database.php`
- ✅ `/snackup/backend/InstanceManager.php`
- ✅ `/snackup/admin/bootstrap.php`
- ✅ `/database/schema.sql`
- ✅ Structure `/snackup/admin/api/`
- ✅ Repository pattern compris

---

## 📝 NOTES D'EXPLORATION

### Architecture identifiée:
- ✅ **Multi-tenant par domaine** (InstanceManager)
  - Fichier `config/instances.json` route chaque domaine vers une config
  - Chaque instance a son `restaurant_id`
  - Bootstrap charge automatiquement l'instance selon `$_SERVER['HTTP_HOST']`

- ✅ **Repository Pattern**
  - Repositories dans `/snackup/backend/repositories/`
  - Existants: MenuRepository, OrderRepository, CustomerRepository, PromoCodeRepository, LoyaltyRepository, RestaurantRepository
  - Tous filtrent par `restaurant_id` via `MenuRepository::$restaurantId`

- ✅ **API REST Structure**
  - Endpoints dans `/snackup/admin/api/`
  - Format: `products.php`, `orders.php`, `customers.php`
  - Méthode: $_SERVER['REQUEST_METHOD'] + switch case
  - Authentification via `bootstrap.php` → `requireAdmin()`

- ✅ **Base de données MySQL**
  - Singleton PDO via `Database::getInstance()`
  - Soft delete généralisé (`deleted_at` TIMESTAMP NULL)
  - Tables: restaurants, categories, products, supplements, formules, orders, customers, loyalty_*

- ✅ **Bootstrap Flow**
  1. `bootstrap.php` inclut `config.php`
  2. `config.php` charge `InstanceManager` et détecte l'instance
  3. Connexion MySQL initialisée avec config instance
  4. Repositories chargés avec `restaurant_id` défini

### Points d'attention:
- ✅ Migration `base_ingredients` appliquée (fix réalisé)
- ✅ Système de soft delete existant (is_active, status, deleted_at)
- ✅ Auto-increment formules peut être INT ou VARCHAR selon l'instance
- ✅ MenuRepository utilise `MenuRepository::$restaurantId` statique
- ✅ CSRF tokens via `generateCsrfToken()` et `validateCsrfToken()`
- ✅ Images converties en WebP automatiquement (optimisation)

### Tables BDD existantes (MySQL):
```
restaurants
├── restaurant_settings
├── opening_hours
├── categories
│   └── products
│       └── product_supplements
├── supplements
├── formules
├── customers
│   ├── customer_tags
│   └── loyalty_transactions
├── orders
│   ├── order_items
│   └── order_item_supplements
├── promo_codes
├── delivery_persons
├── tgtg_baskets
├── admin_users
├── admin_pins
└── faq
```

---

## 🔄 HISTORIQUE DES SESSIONS

### Session 2026-03-20 (actuelle)
- Fix migration `base_ingredients` manquante
- Lancement exploration cuisine types

---

## ⚠️ RÈGLES ABSOLUES

1. **NE JAMAIS CASSER LA PRODUCTION**
2. **Rétrocompatibilité obligatoire** (restaurants existants = 0 impact)
3. **Feature flag** pour rollback instantané
4. **Admin = liberté totale** sur les noms/options/étapes
5. **Pas de framework** - PHP vanilla uniquement
6. **Multi-tenant** - Toujours filtrer par restaurant_id
