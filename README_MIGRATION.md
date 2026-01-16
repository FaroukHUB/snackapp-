# MIGRATION JSON → MYSQL — PHASE 3

## 📋 Vue d'ensemble

Ce document décrit l'utilisation du script de migration `database/migrate-json-to-mysql.php` qui migre les données de `menu.json` et `menu.runtime.json` vers MySQL.

## 🎯 Ce que fait le script

Le script migre automatiquement :
- ✅ **Catégories** : de `menu.json` → table `categories`
- ✅ **Produits** : de `menu.json` → table `products`
- ✅ **Suppléments** : de `menu.json` → table `supplements`
- ✅ **Liaisons produits/suppléments** : → table `product_supplements`

## ⚙️ Prérequis

1. **MySQL configuré** : Le fichier `database/config.php` doit contenir les bonnes credentials
2. **Schéma créé** : Les tables doivent exister (Phase 1)
3. **Repositories disponibles** : Les classes PHP `CategoryRepository`, `ProductRepository`, `SupplementRepository` (Phase 2)

## 🚀 Utilisation

### Mode 1 : Dry-Run (TEST)

**Recommandé pour la première exécution !**

```bash
php database/migrate-json-to-mysql.php --dry-run
```

**Résultat** :
- ✅ Affiche ce qui SERAIT migré
- ❌ N'écrit RIEN en base de données
- ✅ Ne nécessite PAS de connexion MySQL
- ✅ Permet de vérifier le contenu avant migration

### Mode 2 : Production (MIGRATION RÉELLE)

```bash
php database/migrate-json-to-mysql.php
```

**Résultat** :
- ✅ Crée automatiquement un backup dans `backup/`
- ✅ Migre toutes les données en MySQL
- ✅ Idempotent : relançable sans créer de doublons
- ✅ Affiche un résumé détaillé

### Mode 3 : Force (ÉCRASER)

```bash
php database/migrate-json-to-mysql.php --force
```

**Résultat** :
- ⚠️ Écrase les données existantes
- ⚠️ Attention aux données modifiées depuis l'admin !

### Options avancées

```bash
# Spécifier un restaurant différent
php database/migrate-json-to-mysql.php --restaurant-id=2

# Combinaison d'options
php database/migrate-json-to-mysql.php --dry-run --restaurant-id=1
```

## 📊 Résultat attendu

### Exemple en mode dry-run

```
╔════════════════════════════════════════════════╗
║   SNACKAPP - MIGRATION JSON → MYSQL (Phase 3)  ║
╚════════════════════════════════════════════════╝

Restaurant ID : 1
Mode          : DRY-RUN
Force         : NON

=== ÉTAPE 0: Sauvegarde des fichiers JSON ===
[INFO] Mode dry-run : skip backup

=== ÉTAPE 1: Lecture des fichiers JSON ===
[OK]   Fichier menu.json chargé
[INFO]   Version: 2
[INFO]   Dernière mise à jour: 2026-01-05T18:25:11+01:00

=== ÉTAPE 2: Migration des catégories ===
[INFO] Nombre de catégories à migrer : 12
[INFO] [DRY-RUN] Créerait catégorie : Crêpes Salées Signature (crepes-salees-signature)
[INFO] [DRY-RUN] Créerait catégorie : Nos Sucrés et Salés (sucres-sales)
...

╔════════════════════════════════════════════════╗
║              RÉSUMÉ DE LA MIGRATION             ║
╚════════════════════════════════════════════════╝

Catégories créées   : 12
Catégories skipped  : 0

Produits créés      : 47
Produits skipped    : 0

Suppléments créés   : 40
Suppléments skipped : 0

Liaisons créées     : 0
Liaisons skipped    : 0

⚠ MODE DRY-RUN : Aucune modification en BDD
```

### Exemple en mode production

```
╔════════════════════════════════════════════════╗
║   SNACKAPP - MIGRATION JSON → MYSQL (Phase 3)  ║
╚════════════════════════════════════════════════╝

Restaurant ID : 1
Mode          : PRODUCTION
Force         : NON

=== ÉTAPE 0: Sauvegarde des fichiers JSON ===
[OK]   Dossier backup/ créé
[OK]   Sauvegardé : menu_2026-01-16_15-30-45.json
[OK]   Sauvegardé : menu.runtime_2026-01-16_15-30-45.json

=== ÉTAPE 2: Migration des catégories ===
[OK]   Catégorie créée : Crêpes Salées Signature (ID: 1)
[OK]   Catégorie créée : Nos Sucrés et Salés (ID: 2)
...

=== ÉTAPE 6: Vérifications finales ===
[INFO] ✓ Catégories en BDD : 12
[INFO] ✓ Produits en BDD : 47
[INFO] ✓ Suppléments en BDD : 40
[INFO] ✓ Liaisons produits/suppléments : 0
[OK]   Vérifications terminées

✓ MIGRATION TERMINÉE AVEC SUCCÈS
```

## 🔒 Sécurité

### Backups automatiques

Chaque exécution en mode production crée automatiquement des backups horodatés :

```
backup/
├── menu_2026-01-16_15-30-45.json
└── menu.runtime_2026-01-16_15-30-45.json
```

### Idempotence

Le script est **idempotent** : il peut être relancé sans créer de doublons.

**Comment ?**
- Vérifie si un slug existe déjà avant création
- Si existe : skip (log warning) et conserve l'ID MySQL
- Si n'existe pas : création

**Exemple** :
```
[WARN] Catégorie existe déjà : crepes-salees-signature (ID: 1)
[OK]   Catégorie créée : nouvelle-categorie (ID: 13)
```

## ⚠️ Cas particuliers

### Catégories supprimées

Les catégories présentes dans `menu.json` sont migrées même si elles étaient supprimées dans `menu.runtime.json`. Le script ne gère PAS le champ `deleted_at` automatiquement.

### Suppléments sans slug

Les suppléments sont identifiés par **nom** (pas de slug unique). Si deux suppléments ont le même nom, le second sera skipped.

### Prix en centimes

Les prix dans `menu.json` sont en **centimes** (ex: 550 = 5.50€).
Le script convertit automatiquement : `price_solo = priceSolo / 100`

### Options spéciales

Les options spéciales (`viennoiserieOptions`, `beverageOptions`, etc.) sont stockées dans le champ JSON `options_config` de la table `products`.

## 🐛 Dépannage

### Erreur : "Connexion à la base de données impossible"

**Cause** : MySQL n'est pas accessible ou credentials incorrects

**Solution** :
1. Vérifier que MySQL est démarré : `mysql -u root -p`
2. Vérifier `database/config.php`
3. Tester : `php database/migrate.php`

### Erreur : "Fichier menu.json introuvable"

**Cause** : Chemin incorrect

**Solution** :
```bash
# Exécuter depuis la racine du projet
cd /chemin/vers/snackapp
php database/migrate-json-to-mysql.php
```

### Warning : "Catégorie existe déjà"

**Normal !** Le script est idempotent. Si vous relancez la migration, les données existantes sont skipped.

**Pour forcer l'écrasement** :
```bash
php database/migrate-json-to-mysql.php --force
```

## 📈 Prochaines étapes

Après la migration :

1. **Vérifier les données** :
   ```sql
   SELECT COUNT(*) FROM categories;
   SELECT COUNT(*) FROM products;
   SELECT COUNT(*) FROM supplements;
   ```

2. **Tester l'admin** :
   - Ouvrir l'admin panel
   - Vérifier que les catégories/produits s'affichent
   - Modifier un produit et vérifier la sauvegarde

3. **Activer MySQL** (Phase 4) :
   - Modifier les APIs pour lire depuis MySQL
   - Supprimer les lectures JSON
   - Tester le frontend

## 🔗 Références

- **PROGRESSION.md** : État d'avancement du projet
- **MIGRATION.md** : Plan de migration complet
- **database/schema.sql** : Schéma MySQL
- **database/repositories/** : Repositories CRUD

## ⚡ Commandes rapides

```bash
# Test sans risque
php database/migrate-json-to-mysql.php --dry-run

# Migration réelle
php database/migrate-json-to-mysql.php

# Forcer l'écrasement
php database/migrate-json-to-mysql.php --force

# Restaurant spécifique
php database/migrate-json-to-mysql.php --restaurant-id=2
```

---

**Auteur** : Claude (Phase 3 - Migration JSON → MySQL)
**Date** : 2026-01-16
**Version** : 1.0
