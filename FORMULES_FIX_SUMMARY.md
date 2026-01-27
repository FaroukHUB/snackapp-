# Fix: Gestion des formules avec colonne id VARCHAR

## Problème identifié

La table `formules` dans l'instance **atelier-pizza** (restaurant_id=3) avait une colonne `id` de type `varchar(191)` au lieu de `INT UNSIGNED AUTO_INCREMENT` comme prévu dans la migration initiale.

Cela causait les problèmes suivants:
1. ✗ Création de formule: retournait success mais la formule n'était pas persistée (lastInsertId() renvoyait 0 ou vide)
2. ✗ Suppression de formule: erreur "ID formule manquant" car l'ID était casté en int
3. ✗ Front renvoie `formules: []` malgré `loadedFrom="database"`

## Solution implémentée

### 1. Détection automatique du type de colonne id

**Fichier:** `snackup/backend/repositories/MenuRepository.php`

- Ajout de `detectIdType()`: détecte si la colonne id est INT ou VARCHAR
- Ajout de `generateFormuleId()`: génère un ID unique pour VARCHAR (format: `formule-{timestamp}-{random}`)
- Modification de `addFormule()`:
  - Si `id` est VARCHAR: génère un ID unique et l'insère explicitement
  - Si `id` est INT: utilise AUTO_INCREMENT classique avec lastInsertId()

### 2. Suppression des casts (int) dans l'API admin

**Fichier:** `snackup/admin/api/products.php`

- `update_formule`: change `(int)($input['formule_id'])` → `trim((string)($input['formule_id']))`
- `delete_formule`: change `(int)($input['formule_id'])` → `trim((string)($input['formule_id']))`

### 3. Scripts de diagnostic et migration

**Nouveaux fichiers:**

- `diagnostic-formules.php`: script web pour vérifier la structure de la table et les données
  - URL: `https://votre-domaine.com/diagnostic-formules.php`
  - Affiche: type de colonne id, nombre de formules, liste des formules existantes

- `database/migrations/2026_01_27_fix_formules_id_type.sql`: migration optionnelle
  - Change id de VARCHAR(191) vers INT UNSIGNED AUTO_INCREMENT
  - ⚠️ ATTENTION: supprime toutes les formules existantes (TRUNCATE)
  - À n'appliquer que si vous voulez revenir au schéma INT standard

## Résultat attendu

✅ **Création de formule**: la formule est persistée avec un ID généré (string ou int selon le type)
✅ **Suppression de formule**: fonctionne avec les deux types d'ID
✅ **Front (config/menu.php)**: renvoie toutes les formules avec `status='available'`
✅ **Admin**: affiche les formules après reload

## Vérification

1. Exécuter `diagnostic-formules.php` via le navigateur pour voir:
   - Type de colonne id actuel
   - Nombre de formules disponibles
   - Liste des formules

2. Créer une formule dans l'admin:
   - Vérifier qu'elle apparaît après reload admin
   - Vérifier qu'elle apparaît sur le front

3. Supprimer une formule:
   - Vérifier qu'elle disparaît (status='unavailable')
   - Pas d'erreur "ID manquant"

## Architecture

**Classes MenuRepository:**
- Front (config/menu.php) → `/snackup/backend/repositories/MenuRepository.php`
- Admin (products.php) → `/snackup/backend/repositories/MenuRepository.php` (via bootstrap.php)

Les deux utilisent la même classe, donc les corrections s'appliquent partout.

## Note sur la structure DB

La migration originale `2026_01_26_create_formules_table.sql` définit:
```sql
`id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
```

Si votre table a `id` VARCHAR(191), cela signifie soit:
- La table a été créée manuellement avec un schéma différent
- Une migration ultérieure a modifié la structure
- Un script d'initialisation spécifique à l'instance a été utilisé

Le code adapté fonctionne maintenant avec les deux types.
