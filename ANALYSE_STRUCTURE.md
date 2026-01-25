# ANALYSE COMPLÈTE - STRUCTURE PROJET ATELIER PIZZA

**Date** : 2026-01-18
**Instance** : Atelier Pizza
**Objectif** : Nettoyer et structurer proprement le projet

---

## 📂 STRUCTURE ESSENTIELLE POUR ATELIER PIZZA

### 1. FICHIERS CORE APPLICATION (NE PAS TOUCHER)

```
/home/user/snackapp-/
├── snackup/                              # Core framework
│   ├── backend/
│   │   ├── repositories/
│   │   │   └── MenuRepository.php        # ✅ ESSENTIEL - CRUD MySQL
│   │   ├── Database.php
│   │   └── InstanceManager.php
│   ├── frontend/
│   └── admin/
│
├── admin-panel-v2/                       # Admin interface
│   ├── api/
│   │   └── products.php                  # ✅ ESSENTIEL - API CRUD (CORRIGÉ EN LOCAL)
│   ├── bootstrap.php
│   └── index.php
│
├── instances/
│   └── atelier-pizza/                    # ✅ ESSENTIEL - Config Atelier Pizza
│       ├── backend-config.php            # Config instance
│       ├── assets/
│       └── public/
│
├── database/
│   ├── schema.sql                        # ✅ ESSENTIEL - Schéma MySQL
│   ├── migrations/
│   │   ├── 2026_01_17_add_flavor_to_supplements.sql        # ✅ APPLIQUÉ
│   │   └── 2026_01_17_create_category_supplements.sql      # ✅ APPLIQUÉ
│   └── migrate-json-to-mysql.php         # Script migration (déjà utilisé)
```

---

## 📄 FICHIERS DOCUMENTATION VALIDES (À CONSERVER)

### Documentation Migration MySQL
```
MIGRATION.md                              # ✅ Doc complète migration
PROGRESSION.md                            # ✅ Source de vérité - historique sessions
README_MIGRATION.md                       # ✅ Guide migration
RUNBOOK_MIGRATION.md                      # ✅ Procédures migration
SWITCH_PLAN.md                            # ✅ Plan switch MySQL (Phase 4)
```

### Documentation Instance Atelier Pizza
```
instances/atelier-pizza/
├── README.md                             # ✅ Doc instance
├── INSTALLATION.md                       # ✅ Procédure installation
└── backend-config.php                    # ✅ Config active
```

### Documentation Projet Global
```
ARCHITECTURE-SCALABLE.md                  # ✅ Architecture multi-instance
```

---

## 🗑️ FICHIERS À SUPPRIMER (POLLUTION)

### Patches temporaires (session 2026-01-17)
```
❌ PATCH_assignSupplementsByFlavor.txt    # Patch appliqué → SUPPRIMER
❌ PATCH_useMySQL_true.txt                # Patch appliqué → SUPPRIMER
❌ deploy_fix_categories.sh               # Script one-shot → SUPPRIMER
❌ DEPLOY_products_php.md                 # Procédure déploiement temporaire → SUPPRIMER
```

### Fichiers de session temporaires
```
❌ RESUME_SESSION_2026-01-17.md           # Session terminée → SUPPRIMER
❌ TASK.md                                # TODO temporaire → SUPPRIMER ou ARCHIVER
```

### Tests et debug
```
❌ admin-panel-v2/data/debug-json.txt     # Debug → SUPPRIMER
❌ admin-panel-v2/debug_hours.txt         # Debug → SUPPRIMER
❌ admin-panel-v2/debug_post.txt          # Debug → SUPPRIMER
❌ snackup/admin/data/debug-json.txt      # Debug → SUPPRIMER
❌ database/test_flavor_fix.php           # Test → SUPPRIMER
❌ database/migrations/TEST_FLAVOR_FIX.md # Test doc → SUPPRIMER
```

### Scripts déploiement anciens
```
❌ database/deploy-and-test.sh            # Old script → SUPPRIMER
❌ database/restore-menu.sh               # Old script → VÉRIFIER AVANT SUPPRESSION
❌ database/run-migration-on-server.sh    # Old script → SUPPRIMER
❌ deploy-o2switch.sh                     # Old script → VÉRIFIER AVANT SUPPRESSION
❌ scripts/deploy.sh                      # Old script → VÉRIFIER AVANT SUPPRESSION
```

### Documentation obsolète/redondante
```
❌ DEPLOIEMENT-SSH-O2SWITCH.md            # Redondant avec DEPLOY_products_php.md
❌ PHASE4_MODIFICATIONS.md                # Intégré dans SWITCH_PLAN.md
❌ PULL_REQUEST.md                        # Pas utilisé
❌ COMMANDES-SSH-RAPIDES.txt              # Redondant
❌ PREUVE-ATELIER-PIZZA.md                # Temporaire validation
❌ admin-panel-v2/DEBUG-INSTRUCTIONS.md   # Debug
❌ admin-panel-v2/INSTRUCTIONS-UPLOAD.md  # Temporaire
❌ admin-panel-v2/README.txt              # Redondant
❌ admin-panel-v2/URGENT-REDIRECTION.txt  # Temporaire
```

### Fichiers branches/rapatriement
```
❌ RAPATRIEMENT_BRANCHES.md               # Procédure one-shot → ARCHIVER après usage
❌ README_BRANCHE.md                      # Procédure branche → ARCHIVER
```

---

## ✅ FICHIERS SYSTÈME À CONSERVER

### Git et verrouillage branche
```
✅ .claude-branch-lock                    # Verrouillage branche
✅ verify-branch.sh                       # Script vérification branche
✅ .gitignore
```

### Fichiers instances autres (ne pas toucher)
```
instances/demo/                           # Autre instance
instances/marvelous/                      # Instance source
```

---

## 🎯 STRUCTURE PROPRE FINALE RECOMMANDÉE

```
/home/user/snackapp-/
│
├── 📁 snackup/                           # Core application
├── 📁 admin-panel-v2/                    # Admin panel
├── 📁 instances/
│   └── atelier-pizza/                    # Instance active
├── 📁 database/
│   ├── schema.sql
│   ├── migrations/                       # Migrations validées uniquement
│   └── migrate-json-to-mysql.php
│
├── 📄 MIGRATION.md                       # Doc migration
├── 📄 PROGRESSION.md                     # Source de vérité
├── 📄 README_MIGRATION.md
├── 📄 RUNBOOK_MIGRATION.md
├── 📄 SWITCH_PLAN.md
├── 📄 ARCHITECTURE-SCALABLE.md
│
├── 🔒 .claude-branch-lock
├── 🔒 verify-branch.sh
└── 📁 .git/
```

---

## 📋 PLAN DE NETTOYAGE PROPOSÉ

### Étape 1 : Supprimer fichiers debug/test
```bash
rm -f admin-panel-v2/data/debug-json.txt
rm -f admin-panel-v2/debug_hours.txt
rm -f admin-panel-v2/debug_post.txt
rm -f snackup/admin/data/debug-json.txt
rm -f database/test_flavor_fix.php
rm -f database/migrations/TEST_FLAVOR_FIX.md
```

### Étape 2 : Supprimer patches temporaires
```bash
rm -f PATCH_assignSupplementsByFlavor.txt
rm -f PATCH_useMySQL_true.txt
rm -f deploy_fix_categories.sh
rm -f DEPLOY_products_php.md
```

### Étape 3 : Archiver fichiers session
```bash
mkdir -p archive/session-2026-01-17
mv RESUME_SESSION_2026-01-17.md archive/session-2026-01-17/
mv TASK.md archive/session-2026-01-17/
mv RAPATRIEMENT_BRANCHES.md archive/session-2026-01-17/
mv README_BRANCHE.md archive/session-2026-01-17/
```

### Étape 4 : Supprimer documentation obsolète
```bash
rm -f DEPLOIEMENT-SSH-O2SWITCH.md
rm -f PHASE4_MODIFICATIONS.md
rm -f PULL_REQUEST.md
rm -f COMMANDES-SSH-RAPIDES.txt
rm -f PREUVE-ATELIER-PIZZA.md
rm -f admin-panel-v2/DEBUG-INSTRUCTIONS.md
rm -f admin-panel-v2/INSTRUCTIONS-UPLOAD.md
rm -f admin-panel-v2/README.txt
rm -f admin-panel-v2/URGENT-REDIRECTION.txt
```

### Étape 5 : Vérifier et supprimer scripts anciens (VALIDATION REQUISE)
```bash
# À VÉRIFIER avant suppression
ls -lh database/restore-menu.sh
ls -lh deploy-o2switch.sh
ls -lh scripts/deploy.sh
ls -lh database/deploy-and-test.sh
ls -lh database/run-migration-on-server.sh
```

---

## ⚠️ VALIDATION REQUISE

**AVANT de supprimer quoi que ce soit, répondez** :

1. **Validez-vous le plan de nettoyage ci-dessus ?** (OUI/NON)
2. **Voulez-vous archiver ou supprimer définitivement ?** (ARCHIVER/SUPPRIMER)
3. **Y a-t-il des fichiers listés que vous voulez garder ?** (précisez)

**Aucune action avant votre validation explicite.**
