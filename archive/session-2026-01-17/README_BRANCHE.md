# 🔒 VERROUILLAGE DE BRANCHE GIT

## ⚠️ PROBLÈME RÉSOLU

**Problème** : Changement accidentel de branche qui fait perdre du temps et du travail

**Solution** : Verrouillage de la branche avec système de vérification

---

## 📋 BRANCHE ACTIVE (VERROUILLÉE)

```
claude/review-progress-continue-U4j8i
```

**RÈGLES ABSOLUES** :
- ❌ NE JAMAIS changer de branche
- ❌ NE JAMAIS créer de nouvelle branche
- ❌ NE JAMAIS checkout vers une autre branche
- ✅ TOUS les commits doivent être sur cette branche

---

## 🛠️ OUTILS DE VÉRIFICATION

### 1. Fichier de verrouillage

**Fichier** : `.claude-branch-lock`

Ce fichier contient la branche verrouillée et les règles.
À lire au début de CHAQUE session.

### 2. Script de vérification

**Fichier** : `verify-branch.sh`

**Usage** :
```bash
./verify-branch.sh
```

**Résultat** :
- ✅ Affiche "OK" si sur la bonne branche
- ❌ Affiche "ERREUR" et commande de correction si mauvaise branche
- Affiche les 5 derniers commits
- Affiche les commits non pushés

**À exécuter** : Au début de CHAQUE session de travail

### 3. Section en tête de PROGRESSION.md

Le fichier `PROGRESSION.md` contient maintenant une section en haut qui rappelle :
- La branche verrouillée
- Les règles absolues
- Le script de vérification

---

## 📝 PROCÉDURE DE TRAVAIL

### Début de session

```bash
# 1. Vérifier la branche actuelle
./verify-branch.sh

# 2. Si erreur, corriger immédiatement
git checkout claude/review-progress-continue-U4j8i

# 3. Lire les fichiers de contexte
cat .claude-branch-lock
head -20 PROGRESSION.md
```

### Pendant le travail

**AVANT tout commit** :
```bash
# Vérifier qu'on est toujours sur la bonne branche
git branch --show-current
# Doit afficher : claude/review-progress-continue-U4j8i
```

**Si jamais vous voyez une autre branche** :
```bash
# STOP IMMÉDIATEMENT
# Revenir sur la bonne branche
git checkout claude/review-progress-continue-U4j8i
```

---

## 🚨 EN CAS DE CHANGEMENT ACCIDENTEL

**Si vous constatez un changement de branche** :

1. **STOP** - Ne faites rien d'autre
2. Vérifier l'état actuel :
   ```bash
   git branch --show-current
   git status
   git log --oneline -5
   ```
3. Revenir sur la bonne branche :
   ```bash
   git checkout claude/review-progress-continue-U4j8i
   ```
4. Vérifier que le travail est intact :
   ```bash
   git log --oneline -10
   ls -la
   ```

**Si des commits sont sur la mauvaise branche** :
```bash
# 1. Noter le hash du dernier commit
git log --oneline -1

# 2. Revenir sur la bonne branche
git checkout claude/review-progress-continue-U4j8i

# 3. Cherry-pick les commits perdus
git cherry-pick <hash-du-commit>
```

---

## ✅ GARANTIES

Avec ce système :

1. **Fichier .claude-branch-lock** : Source de vérité pour la branche
2. **Script verify-branch.sh** : Vérification automatique
3. **Section PROGRESSION.md** : Rappel visuel permanent
4. **Ce README** : Documentation complète

**Résultat** : Impossible de changer de branche sans le savoir

---

## 📊 ÉTAT ACTUEL

**Branche** : `claude/review-progress-continue-U4j8i`
**Commits locaux** : 8 (non pushés - erreur 403 normale)
**Instance** : Atelier Pizza
**Base de données** : zajr1824_atelierpizza

**Dernière vérification** : 2026-01-18

---

## 🔗 FICHIERS LIÉS

- `.claude-branch-lock` - Fichier de verrouillage
- `verify-branch.sh` - Script de vérification
- `PROGRESSION.md` - Documentation (section branche en tête)
- `TASK.md` - Tâches en cours

**À lire au début de chaque session** : `.claude-branch-lock` + `PROGRESSION.md`
