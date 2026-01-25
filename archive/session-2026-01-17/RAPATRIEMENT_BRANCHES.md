# RAPATRIEMENT DES BRANCHES

## 🎯 Objectif

Rapatrier TOUT le travail des autres branches sur la branche verrouillée **SANS changer de branche**.

---

## 📊 Situation Actuelle

**Branche verrouillée** : `claude/review-progress-continue-U4j8i`

**Branches à rapatrier** :
1. `origin/claude/fix-loyalty-handlers-bug-2SAxt` (~20 commits)
2. `origin/claude/setup-marvelous-creperie-Wg8p0` (~20 commits)

**Total** : ~50 commits éparpillés sur 3 branches

---

## 🛠️ Plan de Rapatriement

### Étape 1 : Merge branche fix-loyalty

```bash
# Vérifier qu'on est sur la bonne branche
./verify-branch.sh

# Merger la branche fix-loyalty
git merge origin/claude/fix-loyalty-handlers-bug-2SAxt --no-ff -m "merge: Rapatriement branche fix-loyalty (cart, upsell, formules)"

# Si conflits : les résoudre puis
git add .
git commit
```

### Étape 2 : Merge branche setup-marvelous

```bash
# Merger la branche setup-marvelous
git merge origin/claude/setup-marvelous-creperie-Wg8p0 --no-ff -m "merge: Rapatriement branche setup-marvelous (jus, livraison, options)"

# Si conflits : les résoudre puis
git add .
git commit
```

### Étape 3 : Vérification

```bash
# Vérifier qu'on a tout rapatrié
git log --oneline -50

# Vérifier qu'on est toujours sur la bonne branche
./verify-branch.sh
```

---

## ⚠️ Gestion des Conflits

Si des conflits apparaissent :

1. **Identifier les fichiers en conflit** :
   ```bash
   git status
   ```

2. **Résoudre les conflits** :
   - Ouvrir chaque fichier en conflit
   - Chercher les marqueurs `<<<<<<<`, `=======`, `>>>>>>>`
   - Choisir la bonne version ou fusionner manuellement

3. **Marquer comme résolu** :
   ```bash
   git add <fichier-résolu>
   ```

4. **Finaliser le merge** :
   ```bash
   git commit
   ```

---

## 🔒 Règles Absolues

- ❌ NE JAMAIS changer de branche pendant l'opération
- ✅ Rester sur `claude/review-progress-continue-U4j8i` tout le temps
- ✅ Exécuter `./verify-branch.sh` avant et après chaque merge
- ✅ Créer un commit de merge pour chaque branche rapatriée

---

## 📋 Checklist

### Avant de commencer
- [ ] Vérifier branche actuelle : `./verify-branch.sh`
- [ ] Lire ce document en entier
- [ ] Faire un backup local (optionnel) : `git branch backup-avant-rapatriement`

### Pendant le rapatriement
- [ ] Merge origin/claude/fix-loyalty-handlers-bug-2SAxt
- [ ] Résoudre conflits si nécessaire
- [ ] Merge origin/claude/setup-marvelous-creperie-Wg8p0
- [ ] Résoudre conflits si nécessaire

### Après le rapatriement
- [ ] Vérifier branche : `./verify-branch.sh`
- [ ] Vérifier historique : `git log --oneline -50`
- [ ] Compter les commits : doit être ~50+
- [ ] Mettre à jour PROGRESSION.md

---

## 🎯 Résultat Attendu

Après le rapatriement :

- ✅ Branche verrouillée contient TOUT le travail
- ✅ ~50 commits sur une seule branche
- ✅ Historique complet préservé
- ✅ Aucune perte de travail
- ✅ Plus besoin des autres branches

---

**Prêt à commencer ?**

Exécutez : `./verify-branch.sh` puis suivez les étapes ci-dessus.
