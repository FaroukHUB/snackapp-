# Mise à jour : Pages Dynamiques Click & Collect et Fidélité

## 📅 Date : 2026-03-19

## ✨ Résumé

Les pages **click-collect.html** et **fidelite.html** sont maintenant **100% dynamiques** et récupèrent leurs données depuis la base de données au lieu de contenu hardcodé.

## 🎯 Problème résolu

Avant cette mise à jour, les pages contenaient du contenu hardcodé :
- ❌ Horaires de retrait hardcodés (Lun-Jeu 18h30-23h30, etc.)
- ❌ Adresse hardcodée (110 Rue des Postes, 59000 Lille)
- ❌ Téléphone hardcodé (03 74 45 54 27)
- ❌ Système de points hardcodé (100 DA = 1 POINT)
- ❌ Récompenses hardcodées (500pts, 800pts, 1500pts, 2500pts)

## ✅ Solution implémentée

### 1. Modification de `config/restaurant.php`

Ajout des données dynamiques dans la réponse JSON :

```php
// Horaires d'ouverture depuis la base de données
$response['openingHours'] = [...]

// Paramètres de fidélité
$response['loyalty'] = [
    'enabled' => true,
    'pointsPerCurrencyUnit' => 1,        // Nombre de points par unité monétaire
    'currencyUnit' => 100,                // Unité monétaire (100 DA, 1 EUR, etc.)
    'rewards' => [...]                    // Liste des récompenses depuis la DB
]
```

### 2. Modification de `snackup/frontend/js/dynamic-content.js`

Ajout de nouvelles fonctions pour remplacer le contenu dynamiquement :

#### Fonctions ajoutées :

- **`updateOpeningHours()`** : Remplace les horaires de retrait hardcodés
  - Groupe intelligemment les jours avec les mêmes horaires
  - Gère les jours fermés
  - Format : "Lun - Jeu : 18h30 - 23h30"

- **`updateAddress()`** : Remplace l'adresse hardcodée
  - Récupère `location.address`, `location.postalCode`, `location.city`
  - Format : "Adresse<br>Code postal Ville"

- **`updateContactInfo()`** : Remplace le téléphone hardcodé
  - Utilise `contact.phoneDisplay` ou `contact.phone`

- **`updateLoyaltySystem()`** : Remplace le système de points
  - Met à jour "100 DA = 1 POINT" dynamiquement
  - Calcule automatiquement l'exemple (1500 DA = 15 points)
  - Adapte le texte "Gagnez X points par Y DA"

- **`updateLoyaltyRewards()`** : Remplace les récompenses hardcodées
  - Récupère les récompenses depuis `loyalty.rewards`
  - Affiche le nom, la description, les points requis et l'icône

### 3. Migration SQL

Fichier : `snackup/backend/migrations/add_loyalty_currency_unit.sql`

Colonnes ajoutées :

```sql
-- Dans restaurant_settings
ALTER TABLE restaurant_settings
    ADD COLUMN loyalty_currency_unit INT UNSIGNED DEFAULT 100
    COMMENT 'Unité monétaire pour 1 point (ex: 100 DA = 1 point)';

-- Dans loyalty_rewards
ALTER TABLE loyalty_rewards
    ADD COLUMN icon VARCHAR(50) DEFAULT 'fa-gift'
    COMMENT 'Icône FontAwesome (ex: fa-cookie, fa-burger)';
```

## 🚀 Utilisation

### Pour configurer les horaires d'ouverture

Les horaires sont dans la table `opening_hours` :

```sql
-- Exemple : Lundi ouvert de 18h30 à 23h30
INSERT INTO opening_hours (restaurant_id, day_of_week, opens, closes, is_closed)
VALUES (1, 0, '18:30:00', '23:30:00', 0);

-- 0 = Lundi, 1 = Mardi, ..., 6 = Dimanche
```

### Pour configurer le système de fidélité

Dans la table `restaurant_settings` :

```sql
UPDATE restaurant_settings
SET
    loyalty_enabled = 1,                    -- Activer la fidélité
    loyalty_points_per_euro = 1,           -- 1 point par unité
    loyalty_currency_unit = 100            -- 100 DA = 1 point
WHERE restaurant_id = 1;
```

**Exemples de configuration :**

| Configuration | Points | Unité | Résultat |
|---------------|--------|-------|----------|
| Algérie (DA) | 1 | 100 | 100 DA = 1 point |
| France (EUR) | 10 | 1 | 1 EUR = 10 points |
| USA (USD) | 5 | 1 | 1 USD = 5 points |

### Pour ajouter/modifier des récompenses

Dans la table `loyalty_rewards` :

```sql
INSERT INTO loyalty_rewards
    (restaurant_id, name, description, points_required, icon, is_active)
VALUES
    (1, 'Cookie offert', 'Un délicieux cookie maison', 500, 'fa-cookie', 1),
    (1, 'Boisson offerte', 'Boisson au choix', 800, 'fa-glass-water', 1),
    (1, 'Burger offert', 'Burger au choix', 1500, 'fa-burger', 1);
```

**Icônes FontAwesome disponibles :**
- `fa-cookie` - Cookie
- `fa-glass-water` - Boisson
- `fa-burger` - Burger
- `fa-box` - Menu/Boîte
- `fa-gift` - Cadeau
- `fa-star` - Étoile
- `fa-pizza-slice` - Pizza
- `fa-ice-cream` - Glace

## 📊 Structure des données

### API Response (`config/restaurant.php`)

```json
{
  "openingHours": [
    {
      "day": "Lun",
      "opens": "18:30",
      "closes": "23:30",
      "isClosed": false
    }
  ],
  "loyalty": {
    "enabled": true,
    "pointsPerCurrencyUnit": 1,
    "currencyUnit": 100,
    "rewards": [
      {
        "id": 1,
        "name": "Cookie offert",
        "description": "Un délicieux cookie maison",
        "pointsRequired": 500,
        "icon": "fa-cookie"
      }
    ]
  }
}
```

## 🔧 Migration

Pour appliquer les changements sur une instance existante :

1. **Exécuter la migration SQL** :
   ```bash
   mysql -u root -p snackapp < snackup/backend/migrations/add_loyalty_currency_unit.sql
   ```

2. **Configurer les paramètres** :
   - Ajouter les horaires d'ouverture dans `opening_hours`
   - Configurer `loyalty_currency_unit` dans `restaurant_settings`
   - Ajouter des icônes aux récompenses dans `loyalty_rewards`

3. **Vider le cache si nécessaire** :
   - Rafraîchir la page (Ctrl+F5)
   - Vérifier la console JavaScript pour les logs

## 📝 Notes importantes

- ✅ Les modifications sont **rétrocompatibles** : si une colonne n'existe pas, les valeurs par défaut sont utilisées
- ✅ L'instance **demo** utilise des données statiques hardcodées (pas de DB)
- ✅ Le script `dynamic-content.js` est chargé automatiquement dans toutes les pages HTML
- ✅ Les logs de débogage sont visibles dans la console du navigateur

## 🐛 Dépannage

### Les horaires ne s'affichent pas

1. Vérifier que la table `opening_hours` contient des données :
   ```sql
   SELECT * FROM opening_hours WHERE restaurant_id = 1;
   ```

2. Vérifier la console JavaScript (F12) pour les logs :
   ```
   ✅ [Dynamic Content] Horaires mis à jour
   ```

### Les récompenses ne s'affichent pas

1. Vérifier que la table `loyalty_rewards` contient des données :
   ```sql
   SELECT * FROM loyalty_rewards WHERE restaurant_id = 1 AND is_active = 1;
   ```

2. Vérifier que la migration a été exécutée :
   ```sql
   SHOW COLUMNS FROM loyalty_rewards LIKE 'icon';
   ```

### Le système de points affiche toujours "100 DA = 1 POINT"

1. Vérifier la configuration dans `restaurant_settings` :
   ```sql
   SELECT loyalty_currency_unit, loyalty_points_per_euro
   FROM restaurant_settings
   WHERE restaurant_id = 1;
   ```

2. Si NULL, exécuter la migration ou définir manuellement :
   ```sql
   UPDATE restaurant_settings
   SET loyalty_currency_unit = 100
   WHERE restaurant_id = 1;
   ```

## 🎉 Résultat

Les pages **click-collect.html** et **fidelite.html** affichent maintenant automatiquement :
- ✅ Les vrais horaires d'ouverture depuis la DB
- ✅ La vraie adresse depuis la DB
- ✅ Le vrai téléphone depuis la DB
- ✅ Le système de points personnalisé (100 DA, 1 EUR, etc.)
- ✅ Les récompenses depuis la DB avec leurs icônes

**Toutes les instances utilisent maintenant leurs propres données sans modification manuelle du code !**
