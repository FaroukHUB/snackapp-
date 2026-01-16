# 📋 PROGRESSION ATELIER PIZZA
**Instance:** atelierpizza.mon-agenceweb.fr
**SSH:** atelierpizza.mon-agenceweb.fr (chemin exact à confirmer)

---

## 📅 2026-01-16 - Session Actuelle

### ❌ PROBLÈME IDENTIFIÉ
**Date:** 2026-01-16 10:30
**Tâche:** Diagnostic menu qui ne s'affiche pas
**Statut:** ⚠️ EN COURS

**Erreur initiale:**
- Le menu ne s'affiche pas sur atelierpizza.mon-agenceweb.fr
- Cause : J'ai travaillé sur Marvelous par erreur (instance terminée)

**Actions réalisées (ERRONÉES):**
- ❌ Travaillé sur instance Marvelous au lieu d'Atelier Pizza
- ❌ Mergé vers server-live (branche pour Marvelous)

**PROCHAINE ÉTAPE:**
1. Identifier précisément le problème sur **atelierpizza.mon-agenceweb.fr**
2. Vérifier l'état des fichiers de config Atelier Pizza
3. Tester les APIs menu/restaurant pour Atelier Pizza
4. Corriger UNIQUEMENT les fichiers d'Atelier Pizza

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

**Dernière mise à jour:** 2026-01-16 10:35
**Mis à jour par:** Claude (session U4j8i)
