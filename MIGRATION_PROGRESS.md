# Migration: Ajout colonne snackup_context à products

## Objectif
Ajouter une colonne JSON `snackup_context` dans la table `products` pour stocker :
- Ingrédients de base custom
- Prix personnalisés
- Autres configurations spécifiques au contexte Snackup

## Statut: 🚧 EN COURS

## Étapes

### ✅ Étape 0: Préparation
- [x] Plan créé
- [x] Fichier de suivi créé

### ✅ Étape 1: Migration Base de données
- [x] Créer fichier migration SQL (2026-01-26-add-snackup-context.sql)
- [x] Commit migration

### ✅ Étape 2: Mise à jour Code PHP
- [x] Mettre à jour MenuRepository.php (getProductsByCategory, editProduct, addProduct)
- [x] Mettre à jour API products.php (add_product, edit_product, update_product)
- [x] Commit code

### ⏳ Étape 3: Tests
- [ ] Tester la migration
- [ ] Vérifier le fonctionnement
- [ ] Commit final

## Structure snackup_context
```json
{
  "base_ingredients_custom": [
    {
      "ingredient_id": "uuid",
      "ingredient_name": "nom",
      "default_quantity": 1,
      "unit": "g/ml/pièce",
      "is_custom": true
    }
  ],
  "custom_price": 5.50,
  "custom_settings": {}
}
```

## Notes importantes
- Migration ADDITIVE uniquement (pas de suppression)
- Colonne nullable (NULL par défaut)
- Compatible avec anciens produits
- Commits fréquents pour sécuriser
