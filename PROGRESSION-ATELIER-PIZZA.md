# 📋 PROGRESSION ATELIER PIZZA
**Instance:** atelierpizza.mon-agenceweb.fr
**SSH:** atelierpizza.mon-agenceweb.fr (chemin exact à confirmer)

---

## 📅 2026-01-16 - Session Actuelle

### ✅ PROBLÈME RÉSOLU
**Date:** 2026-01-16 10:30 - 11:00
**Tâche:** Fix menu qui ne s'affiche pas sur atelierpizza.mon-agenceweb.fr
**Statut:** ✅ PRÊT À DÉPLOYER

**Diagnostic effectué:**
- ✅ Menu ne s'affiche pas car mot de passe MySQL manquant
- ✅ Ligne 12 de `instances/atelier-pizza/backend-config.php` contenait un placeholder
- ✅ Base de données créée mais scripts SQL non exécutés

**Actions réalisées:**
1. ✅ Créé fichier de progression `PROGRESSION-ATELIER-PIZZA.md`
2. ✅ Mis à jour mot de passe MySQL dans `backend-config.php`
3. ✅ Créé script de déploiement complet `DEPLOIEMENT-ATELIER-PIZZA.sh`
4. ✅ Script inclut :
   - Déploiement code via git
   - Exécution scripts SQL (init_restaurant.sql, populate_menu.sql)
   - Tests automatiques des APIs
   - Instructions de vérification

**Fichiers modifiés:**
- `instances/atelier-pizza/backend-config.php` → Mot de passe MySQL ajouté
- `DEPLOIEMENT-ATELIER-PIZZA.sh` → Script de déploiement SSH (NOUVEAU)
- `PROGRESSION-ATELIER-PIZZA.md` → Documentation (NOUVEAU)

**PROCHAINE ÉTAPE:**
1. Commiter et pusher les changements
2. Se connecter en SSH à atelierpizza.mon-agenceweb.fr
3. Exécuter `bash DEPLOIEMENT-ATELIER-PIZZA.sh`
4. Vérifier que le menu s'affiche sur le site

---

## 📝 Notes Importantes

**⚠️ RÈGLES STRICTES:**
- ✅ Travailler UNIQUEMENT sur Atelier Pizza
- ❌ NE JAMAIS toucher à Marvelous (projet terminé)
- 📄 Mettre à jour ce fichier après CHAQUE tâche
- 🔄 Lire ce fichier au début de chaque session

**Configuration Atelier Pizza:**
- Restaurant ID: 3
- Base de données: zajr1824_atelierpizza
- Utilisateur MySQL: zajr1824_atelierpizza
- Mot de passe: VOTRE_MOT_DE_PASSE_ICI (à configurer)
- Devise: EUR
- Timezone: Europe/Paris

**Fichiers critiques:**
- `/instances/atelier-pizza/backend-config.php`
- `/instances/atelier-pizza/config.js`
- `/config/menu.php` (détection multi-instance)
- `/config/restaurant.php` (détection multi-instance)

---

## 🎯 TODO - Prochaines Actions

- [ ] Tester accès SSH à atelierpizza.mon-agenceweb.fr
- [ ] Vérifier mot de passe MySQL dans backend-config.php
- [ ] Tester API menu.php en production
- [ ] Tester API restaurant.php en production
- [ ] Corriger les erreurs identifiées
- [ ] Déployer les corrections

---

## 📊 Résumé Session

**Durée:** ~30 minutes
**Commits:** 3
- docs: Ajouter fichier de progression Atelier Pizza
- fix: Ajouter mot de passe MySQL Atelier Pizza
- feat: Script déploiement complet Atelier Pizza

**Impact:** Critique - Débloque affichage du menu
**Risque:** Minimal - Configuration uniquement

---

**Dernière mise à jour:** 2026-01-16 11:00
**Mis à jour par:** Claude (session U4j8i)
