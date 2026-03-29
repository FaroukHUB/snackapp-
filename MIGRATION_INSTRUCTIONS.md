# Instructions de Migration: Ajout colonne snackup_context

## ⚠️ IMPORTANT: À exécuter sur le serveur de production

Cette migration ajoute une nouvelle colonne `snackup_context` à la table `products` pour supporter les données enrichies du contexte Snackup.

## 📋 Pré-requis

- Accès SSH au serveur de production
- Accès à la base de données MySQL
- Backup de la base de données (recommandé)

## 🚀 Étapes d'exécution

### Option 1: Via script PHP (Recommandé)

1. **Connectez-vous en SSH au serveur**
   ```bash
   ssh votre-utilisateur@votre-serveur.com
   ```

2. **Naviguez vers le répertoire du projet**
   ```bash
   cd /chemin/vers/snackapp
   ```

3. **Exécutez le script de migration**
   ```bash
   php run-migration.php
   ```

   Vous devriez voir:
   ```
   ✅ Connexion à la base de données réussie
   ✅ Migration réussie !
   ✅ Colonne 'snackup_context' ajoutée à la table 'products'
   ```

### Option 2: Via phpMyAdmin ou MySQL CLI

1. **Connectez-vous à phpMyAdmin ou MySQL CLI**

2. **Sélectionnez la base de données** `zajr1824_atelierpizza`

3. **Exécutez la commande SQL suivante:**
   ```sql
   ALTER TABLE products
   ADD COLUMN snackup_context JSON DEFAULT NULL
   COMMENT 'Contexte Snackup: ingrédients custom, prix custom, etc.';
   ```

4. **Vérifiez que la colonne a été ajoutée:**
   ```sql
   SHOW COLUMNS FROM products LIKE 'snackup_context';
   ```

## ✅ Vérification

Après l'exécution de la migration, vérifiez que tout fonctionne:

1. **Vérifier la structure de la table:**
   ```sql
   DESCRIBE products;
   ```

   Vous devriez voir la colonne `snackup_context` avec le type `JSON`.

2. **Tester l'insertion de données:**
   ```sql
   UPDATE products
   SET snackup_context = '{"test": "ok"}'
   WHERE id = 1;

   SELECT id, name, snackup_context FROM products WHERE id = 1;
   ```

3. **Accéder à l'admin panel** et vérifier que les produits s'affichent correctement.

## 🔄 Rollback (si nécessaire)

En cas de problème, vous pouvez annuler la migration:

```sql
ALTER TABLE products DROP COLUMN snackup_context;
```

⚠️ **Attention:** Cela supprimera toutes les données stockées dans cette colonne.

## 📝 Notes techniques

### Structure de snackup_context

La colonne `snackup_context` peut contenir un objet JSON comme:

```json
{
  "base_ingredients_custom": [
    {
      "ingredient_id": "uuid",
      "ingredient_name": "nom",
      "default_quantity": 1,
      "unit": "g",
      "is_custom": true
    }
  ],
  "custom_price": 5.50,
  "custom_settings": {}
}
```

### Compatibilité

- ✅ **Colonne nullable:** Les produits existants auront `NULL` par défaut
- ✅ **Rétrocompatible:** Le code fonctionne avec ou sans données dans cette colonne
- ✅ **Pas d'impact:** Les produits existants continuent de fonctionner normalement

## 🐛 Dépannage

### Erreur: "Duplicate column name"
→ La colonne existe déjà, rien à faire

### Erreur: "Access denied"
→ Vérifiez les permissions MySQL de l'utilisateur

### Erreur: "Table 'products' doesn't exist"
→ Vérifiez que vous êtes sur la bonne base de données

## 📞 Support

En cas de problème, contactez l'administrateur système ou consultez les logs:
- Logs PHP: `/var/log/apache2/error.log` ou `/var/log/php-fpm/error.log`
- Logs MySQL: `/var/log/mysql/error.log`

## ✅ Checklist finale

- [ ] Backup de la base de données effectué
- [ ] Migration SQL exécutée avec succès
- [ ] Vérification de la structure de la table
- [ ] Test d'insertion/lecture de données JSON
- [ ] Vérification de l'admin panel
- [ ] Code PHP déployé (git pull + commits)
- [ ] Application testée en production

## 🎉 Terminé !

Une fois la migration terminée et vérifiée, votre système supporte maintenant:
- Stockage d'ingrédients de base custom par produit
- Contexte enrichi Snackup par produit
- Flexibilité pour ajouter de nouvelles données métier
