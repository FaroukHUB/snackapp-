# TEST DU FIX "Unknown column type"

## Objectif
Vérifier que la correction du bug "Unknown column 'type'" fonctionne correctement.

## Prérequis

1. **Appliquer la migration** :
   ```bash
   # Via MySQL CLI
   mysql -u zajr1824_marvelous -p zajr1824_marvelous < database/migrations/2026_01_17_add_flavor_to_supplements.sql

   # Ou via PHPMyAdmin
   # Copier/coller le contenu de 2026_01_17_add_flavor_to_supplements.sql
   ```

2. **Vérifier que la colonne existe** :
   ```sql
   DESCRIBE supplements;
   -- Doit afficher la colonne 'flavor' ENUM('sale','sucre','both')
   ```

3. **Vérifier l'index** :
   ```sql
   SHOW INDEX FROM supplements WHERE Key_name = 'idx_restaurant_flavor';
   -- Doit retourner 1 ligne
   ```

## Tests Obligatoires

### Test 1 : Création catégorie salée

**Requête PHP** :
```php
require_once 'snackup/backend/repositories/MenuRepository.php';

$result = MenuRepository::addCategory(
    'Pizzas Salées',
    'Nos délicieuses pizzas salées',
    'pizza',
    'sale'
);

print_r($result);
```

**Résultat attendu** :
- ✅ Pas d'erreur SQL "Unknown column 'type'"
- ✅ Catégorie créée avec ID
- ✅ Suppléments flavor='sale' et flavor='both' auto-assignés

**Vérification SQL** :
```sql
-- Vérifier la catégorie
SELECT * FROM categories WHERE flavor = 'sale' ORDER BY id DESC LIMIT 1;

-- Vérifier les suppléments assignés
SELECT cs.*, s.name, s.flavor
FROM category_supplements cs
JOIN supplements s ON cs.supplement_id = s.id
WHERE cs.category_id = (SELECT id FROM categories WHERE flavor = 'sale' ORDER BY id DESC LIMIT 1);
```

### Test 2 : Création catégorie sucrée

**Requête PHP** :
```php
require_once 'snackup/backend/repositories/MenuRepository.php';

$result = MenuRepository::addCategory(
    'Crêpes Sucrées',
    'Nos délicieuses crêpes sucrées',
    'crepe',
    'sucre'
);

print_r($result);
```

**Résultat attendu** :
- ✅ Pas d'erreur SQL
- ✅ Catégorie créée avec ID
- ✅ Suppléments flavor='sucre' et flavor='both' auto-assignés

**Vérification SQL** :
```sql
-- Vérifier la catégorie
SELECT * FROM categories WHERE flavor = 'sucre' ORDER BY id DESC LIMIT 1;

-- Vérifier les suppléments assignés
SELECT cs.*, s.name, s.flavor
FROM category_supplements cs
JOIN supplements s ON cs.supplement_id = s.id
WHERE cs.category_id = (SELECT id FROM categories WHERE flavor = 'sucre' ORDER BY id DESC LIMIT 1);
```

### Test 3 : CRUD Produits (non régression)

**Requête PHP** :
```php
require_once 'snackup/backend/repositories/MenuRepository.php';

// Créer un produit
$product = MenuRepository::addProduct(
    1, // category_id
    'Pizza Margherita',
    'Sauce tomate, mozzarella, basilic',
    'margherita.jpg',
    8.50,
    12.00
);

// Modifier le produit
MenuRepository::editProduct(
    $product['id'],
    'Pizza Margherita XL',
    'Sauce tomate, mozzarella, basilic - Grande taille',
    'margherita.jpg',
    10.50,
    15.00,
    'available'
);

// Supprimer le produit (soft delete)
MenuRepository::deleteProduct($product['id']);
```

**Résultat attendu** :
- ✅ Création : ID retourné
- ✅ Modification : TRUE
- ✅ Suppression : TRUE
- ✅ Aucune erreur liée à la colonne flavor

## Critères de Succès

| Test | Critère | Statut |
|------|---------|--------|
| Migration | Colonne `flavor` existe | ⬜ |
| Migration | Index `idx_restaurant_flavor` existe | ⬜ |
| Test 1 | Catégorie salée créée sans erreur | ⬜ |
| Test 1 | Suppléments auto-assignés | ⬜ |
| Test 2 | Catégorie sucrée créée sans erreur | ⬜ |
| Test 2 | Suppléments auto-assignés | ⬜ |
| Test 3 | CRUD produits fonctionne | ⬜ |

## Rollback (si nécessaire)

Si la migration pose problème :

```sql
-- Supprimer l'index
DROP INDEX idx_restaurant_flavor ON supplements;

-- Supprimer la colonne
ALTER TABLE supplements DROP COLUMN flavor;
```

Puis revenir à la version précédente de MenuRepository.php.

## Notes

- **Valeur par défaut** : Tous les suppléments existants auront `flavor='both'`
- **Compatibilité** : Les suppléments 'both' sont assignés à TOUTES les catégories
- **Performance** : L'index `idx_restaurant_flavor` optimise les requêtes WHERE flavor
