# DÉPLOIEMENT products.php - ATELIER PIZZA

**Instance** : Atelier Pizza
**Serveur** : o2switch
**Chemin serveur** : `/home/zajr1824/atelierpizza.mon-agenceweb.fr`
**Fichier à déployer** : `admin-panel-v2/api/products.php`

---

## ⚠️ CORRECTIONS CONTENUES DANS CE FICHIER

**Ligne 210** : `$useMySQL = true;` (activation mode MySQL)
**Ligne 515** : `if (!$useMySQL) {` (début encapsulation bloc JSON)
**Ligne 1812** : `}` (fin encapsulation bloc JSON)

**Résultat attendu** : Plus d'erreur "Catégorie introuvable" lors édition catégories

---

## 📋 PROCÉDURE DE DÉPLOIEMENT

### ÉTAPE 1 : Connexion SSH au serveur

```bash
ssh zajr1824@guide
# ou votre méthode de connexion habituelle
```

**Vérification** : Le prompt doit afficher `[zajr1824@guide atelierpizza.mon-agenceweb.fr]$`

---

### ÉTAPE 2 : Localisation et backup du fichier actuel

```bash
# Aller dans le répertoire du projet
cd /home/zajr1824/atelierpizza.mon-agenceweb.fr

# Vérifier que le fichier existe
ls -lh admin-panel-v2/api/products.php

# Créer un backup avec timestamp
cp admin-panel-v2/api/products.php \
   admin-panel-v2/api/products.php.bak_$(date +%Y%m%d_%H%M%S)

# Vérifier que le backup est créé
ls -lh admin-panel-v2/api/products.php.bak_*
```

**Résultat attendu** : Affiche le backup créé avec la date/heure

---

### ÉTAPE 3 : Transfert du fichier corrigé

**Option A : Via éditeur de fichiers hébergeur** (RECOMMANDÉ si accès web)

1. Ouvrir le gestionnaire de fichiers de l'hébergeur (ISPConfig/Plesk/cPanel)
2. Naviguer vers `/home/zajr1824/atelierpizza.mon-agenceweb.fr/admin-panel-v2/api/`
3. Télécharger le fichier local `admin-panel-v2/api/products.php` corrigé
4. Remplacer le fichier sur le serveur

**Option B : Via SCP** (depuis votre machine locale)

```bash
# Depuis VOTRE MACHINE LOCALE (pas le serveur)
scp /home/user/snackapp-/admin-panel-v2/api/products.php \
    zajr1824@guide:/home/zajr1824/atelierpizza.mon-agenceweb.fr/admin-panel-v2/api/products.php
```

**Option C : Via copier-coller** (si fichier accessible)

1. Sur votre machine locale, copier le contenu de `admin-panel-v2/api/products.php`
2. Sur le serveur SSH :
```bash
cd /home/zajr1824/atelierpizza.mon-agenceweb.fr/admin-panel-v2/api

# Ouvrir l'éditeur (nano, vi, ou autre)
nano products.php

# Coller le contenu complet du fichier corrigé
# Sauvegarder : Ctrl+X, puis Y, puis Entrée (pour nano)
```

---

### ÉTAPE 4 : Vérification syntaxe PHP

```bash
# Retour dans le répertoire du projet
cd /home/zajr1824/atelierpizza.mon-agenceweb.fr

# Vérifier la syntaxe PHP
php -l admin-panel-v2/api/products.php
```

**Résultat attendu** :
```
No syntax errors detected in admin-panel-v2/api/products.php
```

**Si erreur de syntaxe** : STOP → Restaurer le backup (voir section ROLLBACK)

---

### ÉTAPE 5 : Vérification des modifications critiques

```bash
# Vérifier ligne 210 ($useMySQL = true)
sed -n '210p' admin-panel-v2/api/products.php
```

**Résultat attendu** : `$useMySQL = true;`

```bash
# Vérifier ligne 515 (début encapsulation bloc JSON)
sed -n '515p' admin-panel-v2/api/products.php
```

**Résultat attendu** : `if (!$useMySQL) {`

```bash
# Vérifier ligne 1812 (fin encapsulation bloc JSON)
sed -n '1812p' admin-panel-v2/api/products.php
```

**Résultat attendu** : `} // Fin du if (!$useMySQL)` ou juste `}`

**Si une vérification échoue** : STOP → Restaurer le backup (voir section ROLLBACK)

---

### ÉTAPE 6 : Test fonctionnel dans l'admin

1. Ouvrir le navigateur
2. Aller sur `https://atelierpizza.mon-agenceweb.fr/admin-panel-v2/`
3. Se connecter à l'admin
4. Aller dans la section Catégories
5. Cliquer sur "Modifier" sur une catégorie existante

**Résultat attendu** :
- ✅ Aucune erreur "Catégorie introuvable"
- ✅ Formulaire d'édition s'affiche
- ✅ Modification possible et sauvegarde fonctionne

**Si erreur persiste** : STOP → Restaurer le backup (voir section ROLLBACK)

---

## 🔄 ROLLBACK (en cas de problème)

### Restaurer le backup immédiatement

```bash
# Aller dans le répertoire
cd /home/zajr1824/atelierpizza.mon-agenceweb.fr

# Lister les backups disponibles
ls -lt admin-panel-v2/api/products.php.bak_* | head -5

# Restaurer le backup le plus récent (remplacer TIMESTAMP par la vraie valeur)
cp admin-panel-v2/api/products.php.bak_YYYYMMDD_HHMMSS \
   admin-panel-v2/api/products.php

# Vérifier la syntaxe du fichier restauré
php -l admin-panel-v2/api/products.php

# Tester dans l'admin
```

**Après restauration** : Le site doit revenir à l'état précédent (avec l'erreur "Catégorie introuvable")

---

## ✅ CHECKLIST POST-DÉPLOIEMENT

### Sur le serveur SSH

- [ ] Backup créé avec timestamp
- [ ] Fichier transféré avec succès
- [ ] Syntaxe PHP valide (`php -l` = No errors)
- [ ] Ligne 210 : `$useMySQL = true;`
- [ ] Ligne 515 : `if (!$useMySQL) {`
- [ ] Ligne 1812 : `}` (fermeture)

### Dans l'admin panel

- [ ] Connexion admin réussie
- [ ] Section Catégories accessible
- [ ] Édition catégorie existante : aucune erreur
- [ ] Formulaire d'édition s'affiche correctement
- [ ] Modification et sauvegarde fonctionnent
- [ ] Les 9 catégories existantes sont visibles

### Tests supplémentaires

- [ ] Création nouvelle catégorie : fonctionne
- [ ] Suppression catégorie : fonctionne
- [ ] Produits associés visibles (60 produits)
- [ ] Aucune erreur dans logs PHP (optionnel)

---

## 📊 EN CAS DE SUCCÈS

**Mettre à jour la documentation** :

1. Dans `TASK.md` : Changer statut "❌ NON (en attente)" → "✅ OUI (déployé)"
2. Dans `PROGRESSION.md` : Marquer bug tertiaire comme "✅ RÉSOLU ET DÉPLOYÉ"
3. Créer un commit local avec message : "deploy: products.php déployé avec succès sur production"

---

## 🚨 EN CAS D'ÉCHEC

1. **Restaurer le backup** (voir section ROLLBACK)
2. **Noter l'erreur exacte** rencontrée
3. **Documenter le problème** dans PROGRESSION.md
4. **NE PAS forcer** de solution sans analyse
5. **Demander assistance** si nécessaire

---

## 📝 NOTES IMPORTANTES

- **Aucune action Git** n'est requise sur le serveur
- **Aucun redémarrage** de service nécessaire (PHP-FPM continue)
- **Aucune modification** de base de données
- **Le frontend** n'est pas affecté par ce déploiement
- **Les backups** restent disponibles pour rollback futur

---

**Durée estimée** : 5-10 minutes
**Niveau de risque** : Faible (backup automatique créé)
**Réversibilité** : Totale (rollback en 30 secondes)

---

**Dernière mise à jour** : 2026-01-18
