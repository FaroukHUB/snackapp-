# TÂCHES EN COURS - Session 2026-01-17

## 🎯 Objectif Global
Corriger le bug CRUD catégories pour permettre l'édition des catégories existantes dans l'admin panel de l'instance **Atelier Pizza**.

---

## ✅ Tâches Terminées

### 1. Bug Primaire : "Unknown column 'type'" ✅ RÉSOLU
- **Problème** : Erreur SQL lors création catégories avec flavor
- **Cause** : Colonne `type` ou `flavor` manquante dans table `supplements`
- **Solution** :
  - ✅ Migration `2026_01_17_add_flavor_to_supplements.sql` créée
  - ✅ Migration appliquée sur production (DB: zajr1824_atelierpizza)
  - ✅ Code MenuRepository.php modifié (type → flavor)
  - ✅ Colonne `flavor ENUM('sale','sucre','both')` ajoutée
  - ✅ Index `idx_restaurant_flavor` créé

### 2. Bug Secondaire : Table 'category_supplements' manquante ✅ RÉSOLU
- **Problème** : Table `category_supplements` n'existe pas
- **Cause** : Migration officielle jamais appliquée
- **Solution** :
  - ✅ Migration minimale `2026_01_17_create_category_supplements.sql` créée
  - ✅ Migration appliquée sur production
  - ✅ Table créée avec structure N:N (category_id, supplement_id)
  - ✅ Feature auto_category_supplements désactivée pour Atelier Pizza
  - ✅ Code MenuRepository.php protégé avec check feature flag
  - ✅ Config instance modifiée (features.auto_category_supplements=false)

### 3. Bug Tertiaire : "Catégorie introuvable" lors édition ⏳ EN COURS DE DÉPLOIEMENT
- **Problème** : Impossible d'éditer les catégories existantes
- **Cause** : `$useMySQL = false` dans products.php ligne 210
- **Diagnostic** :
  - ✅ 9 catégories MySQL identifiées pour restaurant_id=3
  - ✅ 60 produits associés confirmés
  - ✅ Admin utilise mode JSON au lieu de MenuRepository
  - ✅ Code modifié EN LOCAL (commit 7b108ec)
- **Solution** :
  - ✅ Modification locale : `$useMySQL = true`
  - ⏳ **EN ATTENTE** : Déploiement sur serveur de production
  - ⏳ **BLOQUEUR** : Chemin exact du fichier products.php sur serveur inconnu

---

## ⏳ Tâches En Cours

### URGENT : Déployer le fix $useMySQL = true sur le serveur

**Objectif** : Modifier `admin-panel-v2/api/products.php` ligne 210 sur le serveur de production

**Bloqueur actuel** : Chemin du fichier introuvable
- ❌ Tenté : `/var/www/clients/client1/web6/web/admin-panel-v2/api/products.php` → No such file
- ❌ Git push : Impossible (erreur 403 - session ID mismatch)
- 🔍 **Action immédiate** : Localiser le bon chemin du fichier

**Commandes à exécuter sur le serveur SSH** :

```bash
# Option 1 : Depuis le répertoire actuel (si dans ~/atelierpizza.mon-agenceweb.fr)
ls -la admin-panel-v2/api/products.php

# Option 2 : Recherche depuis home
find ~ -name "products.php" | grep admin

# Option 3 : Vérifier le répertoire courant
pwd
ls -la
```

**Une fois le chemin trouvé** :

```bash
# Backup (sécurité)
cp CHEMIN/admin-panel-v2/api/products.php CHEMIN/admin-panel-v2/api/products.php.bak

# Appliquer le patch
sed -i 's/\$useMySQL = false;/\$useMySQL = true;/' CHEMIN/admin-panel-v2/api/products.php

# Vérifier
sed -n '210p' CHEMIN/admin-panel-v2/api/products.php
# Doit afficher : $useMySQL = true;
```

**Référence** : Voir `PATCH_useMySQL_true.txt` pour instructions détaillées

---

## 📋 Tâches Suivantes (après déploiement)

### Tests Fonctionnels Post-Déploiement

**Test 1 : Édition catégorie existante**
- [ ] Recharger l'admin panel
- [ ] Cliquer sur "Modifier" sur une catégorie existante
- [ ] Vérifier : aucune erreur "Catégorie introuvable"
- [ ] Modifier le nom ou la description
- [ ] Sauvegarder
- [ ] Vérifier que la modification est enregistrée

**Test 2 : Affichage des catégories MySQL**
- [ ] Vérifier que les 9 catégories existantes apparaissent dans l'admin
- [ ] Vérifier que les produits associés (60 produits) sont visibles

**Test 3 : Création nouvelle catégorie**
- [ ] Créer une nouvelle catégorie
- [ ] Ajouter au moins 1 produit dedans
- [ ] Vérifier qu'elle apparaît sur le site frontend

### Finalisation Session

**Si tous les tests passent** :
- [ ] Mettre à jour PROGRESSION.md (bug tertiaire → ✅ RÉSOLU)
- [ ] Mettre à jour TASK.md (marquer toutes les tâches comme complètes)
- [ ] Marquer la session 2026-01-17 comme TERMINÉE
- [ ] Documenter les résultats des tests

**Si un test échoue** :
- [ ] Analyser l'erreur
- [ ] Documenter le problème dans PROGRESSION.md
- [ ] Exécuter rollback si nécessaire
- [ ] Planifier correction

---

## 📊 Résumé de l'État

| Bug | Statut | Commit | Déployé |
|-----|--------|--------|---------|
| Unknown column 'type' | ✅ RÉSOLU | f1f4216 | ✅ OUI (migration appliquée) |
| Table category_supplements | ✅ RÉSOLU | c08c2bd | ✅ OUI (migration appliquée) |
| $useMySQL = false | ⏳ EN COURS | 7b108ec | ❌ NON (en attente) |

**Commits locaux non pushés** : 4 (erreur 403 git push)

**Déploiement manuel nécessaire** : 1 fichier (`admin-panel-v2/api/products.php`)

---

## 🔧 Fichiers de Référence

- **PROGRESSION.md** : Documentation complète de la session
- **PATCH_useMySQL_true.txt** : Instructions déploiement mode MySQL
- **PATCH_assignSupplementsByFlavor.txt** : Instructions désactivation auto-assign
- **deploy_fix_categories.sh** : Script automatisé (si utilisable)

---

## 🚨 Points d'Attention

1. **Git push impossible** : Branche `claude/review-progress-continue-U4j8i` a un session ID mismatch
   - Solution : Déploiement manuel via SSH obligatoire

2. **Chemin serveur inconnu** : Structure du serveur différente de la structure locale
   - Action : Localiser le bon chemin avant toute modification

3. **Backup obligatoire** : Toujours créer un backup avant modification sur le serveur
   - Commande : `cp fichier.php fichier.php.bak_$(date +%Y%m%d_%H%M%S)`

4. **Catégories vides** : Les catégories sans produits ne s'affichent pas sur le site
   - Comportement normal, pas un bug

---

**Dernière mise à jour** : 2026-01-17
**Prochaine action** : Localiser le chemin exact de products.php sur le serveur
