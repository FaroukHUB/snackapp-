# 🚀 SNACKUP - Produit Générique

Bienvenue dans le **produit Snackup** ! Ce dossier contient le code générique réutilisable pour créer de nouvelles instances de restaurants.

## 📂 Structure

```
snackup/
├── frontend/       # Templates front génériques (HTML, CSS, JS)
├── backend/        # API et logique métier (PHP, Database)
├── admin/          # Panel d'administration complet
└── database/       # Structure de base de données
```

## 🎯 Utilisation

Pour créer une nouvelle instance :

1. **Ne PAS modifier ce dossier** (c'est le produit générique)
2. Créer un nouveau dossier dans `instances/nom-du-restaurant/`
3. Copier le template de config depuis `instances/demo/`
4. Configurer via l'admin

## ⚠️ IMPORTANT

- ❌ Ne JAMAIS hardcoder des données spécifiques dans ce dossier
- ✅ Toutes les données spécifiques doivent être dans `instances/`
- ✅ Ce code doit rester 100% générique et réutilisable

## 🔗 Instances existantes

- `instances/marvelous/` - Le Marvelous (premier client)
- `instances/demo/` - Instance de démonstration

---

**Snackup** - La solution SaaS pour restaurants
