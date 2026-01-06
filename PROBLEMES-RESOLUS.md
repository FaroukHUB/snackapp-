# ✅ PROBLÈMES RÉSOLUS - Session 2026-01-06

## 1. ❌ HTTP 500 Admin Panel
- **Cause :** `MenuRepository::getFullMenu()` appelé mais méthode inexistante
- **Fix :** Lecture directe depuis menu.json
- **Commit :** `363937b`

## 2. ❌ Suppression catégories (admin visible, site non)
- **Cause :** GET API ne filtrait pas `deletedCategories`
- **Fix :** Appliquer `applyRuntimeToConfig()` dans GET
- **Commit :** `f709288`

## 3. ❌ Suppléments salés manquants dans modal
- **Cause :** Liste EN DUR dans config.js (seulement 'crepes-salees-signature')
- **Fix :** Toutes catégories = salé par défaut (sauf 3 sucrées)
- **Commit :** En cours de push

## 4. ⚠️ Polling notifications trop agressif
- **Avant :** 6 requêtes/min
- **Après :** 1 requête/min (60s)
- **Commits :** `bf0da74`, `88a882d`

---

**Tokens utilisés :** ~160k / 200k
**État final :** Système JSON fonctionnel, MySQL abandonné
