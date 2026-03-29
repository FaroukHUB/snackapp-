# 🔧 ISSUES À RÉSOUDRE - Interface Admin Cuisine Types

**Date:** 2026-03-20
**Session:** https://claude.ai/code/session_01JwCQDUDCt5gFG3hPMVH51R

---

## ❌ PROBLÈMES IDENTIFIÉS

### 1. **Activation impossible - Erreur 500**

**Erreur console navigateur:**
```
POST https://demo.mon-agenceweb.fr/snackup/admin/api/cuisine-types.php 500 (Internal Server Error)
SyntaxError: Failed to execute 'json' on 'Response': Unexpected end of JSON input
```

**Erreur serveur (snackup/admin/api/error_log):**
```
PHP Fatal error: Call to undefined function verifyCsrfToken()
in /home/zajr1824/demo.mon-agenceweb.fr/snackup/admin/api/cuisine-types.php:62
```

**Cause:** La fonction `verifyCsrfToken()` n'existe pas dans bootstrap.php

**Solution à implémenter:**
Regarder comment `snackup/admin/api/products.php` vérifie le CSRF :
```php
$csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
if (!$csrfToken) {
    $csrfToken = $input['csrf_token'] ?? null;
}
// Vérification manuelle au lieu de verifyCsrfToken()
if (!$csrfToken || !isset($_SESSION['csrf_token']) || $csrfToken !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Token CSRF invalide']);
    exit;
}
```

**Fichier à corriger:**
- `/snackup/admin/api/cuisine-types.php` ligne 62

---

### 2. **Bouton "Configurer" ne fonctionne pas**

**Erreur:** La page `cuisine-type-config.php` n'existe pas encore.

**Ligne concernée:**
`cuisine-types-manager.php` ligne ~496 :
```javascript
function configureType(restaurantCuisineTypeId) {
  window.location.href = `cuisine-type-config.php?id=${restaurantCuisineTypeId}`;
}
```

**Solution à implémenter:**
Créer la page `snackup/admin/cuisine-type-config.php` pour :
- Afficher les étapes du type activé
- Permettre de modifier les étapes (custom_name, is_required, min/max choices)
- Gérer les options de chaque étape (ajouter, modifier, supprimer, prix)
- Interface de drag & drop pour réorganiser

---

### 3. **Bouton "Retour" corrigé**

✅ **Résolu:** Pointe maintenant vers `index.php` au lieu de `dashboard.php`

---

## ✅ CE QUI FONCTIONNE

- ✅ Page principale s'affiche correctement
- ✅ Statistiques affichées (types disponibles, activés, étapes)
- ✅ Liste des 11 types de cuisine
- ✅ Distinction visuelle entre types actifs et disponibles
- ✅ Bouton "Retour" fonctionne
- ✅ Design moderne et responsive

---

## 🎯 PROCHAINES ÉTAPES (ordre de priorité)

1. **URGENT:** Corriger la vérification CSRF dans `api/cuisine-types.php`
2. **IMPORTANT:** Créer la page `cuisine-type-config.php` pour la configuration détaillée
3. **BONUS:** Ajouter une page pour gérer l'ordre des types (drag & drop)

---

## 📊 ÉTAT D'AVANCEMENT

- [x] Migrations SQL (6 tables)
- [x] Seed données (11 types + étapes + options)
- [x] CuisineTypeRepository avec copie automatique
- [x] Interface Admin principale (cuisine-types-manager.php)
- [x] API REST basique (cuisine-types.php)
- [x] Fix CSRF vérification ✅
- [x] Page de configuration détaillée ✅
- [ ] Tests complets activation/désactivation
- [ ] Documentation utilisateur

---

## 🔗 FICHIERS CONCERNÉS

```
snackup/admin/
├── cuisine-types-manager.php ✅ (interface principale)
├── cuisine-type-config.php ✅ (créée)
└── api/
    └── cuisine-types.php ✅ (CSRF corrigé + API config ajoutée)

database/
└── repositories/
    └── CuisineTypeRepository.php ✅
```

---

**Note:** Session Git sur branche `claude/resume-snackup-context-g4jKx`
