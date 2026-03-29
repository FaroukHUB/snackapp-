# 📋 VISUALISATION DES MODIFICATIONS SNACKUP

## ✅ POINT 1 - Types de Cuisine (COMPRIS)

### Types de cuisine à ajouter:
- Pâtes
- Riz crousty
- Sandwich
- Sushi
- Desserts (type de cuisine principal)

### Structure des catégories:
Chaque type de cuisine contiendra automatiquement:
- **Boissons** (catégorie commune)
- **Desserts** (catégorie commune)
- + Ses catégories spécifiques

**Exemple pour "Burger":**
```
Burger (Type de cuisine)
├── Burgers classiques
├── Burgers poulet
├── Burgers végétariens
├── 🍹 Boissons (automatique)
└── 🍰 Desserts (automatique)
```

**Exemple pour "Pâtes":**
```
Pâtes (Type de cuisine)
├── Pâtes carbonara
├── Pâtes bolognaise
├── Pâtes végétariennes
├── 🍹 Boissons (automatique)
└── 🍰 Desserts (automatique)
```

---

## ✅ POINT 2 - Nombre de Produits (COMPRIS)

**Pas de limite sur:**
- Nombre de produits par catégorie
- Prix des produits (le vendeur décide)

**Exemple:**
```
Catégorie: Burgers classiques
├── Burger 1 - 8.50€ (prix choisi par le vendeur)
├── Burger 2 - 10.00€
├── Burger 3 - 12.50€
├── Burger 4 - 9.00€
├── ... (autant que le vendeur veut)
└── Burger N - XX.XX€
```

---

## ✅ POINT 3 - Onboarding (COMPRIS)

**L'onboarding s'affichera uniquement pour:**
- ✅ Nouveaux utilisateurs (première connexion)
- ❌ PAS pour les utilisateurs existants

**Détection:**
```javascript
// Vérifier si l'utilisateur a déjà vu l'onboarding
const hasSeenOnboarding = localStorage.getItem('snackup_onboarding_completed');

if (!hasSeenOnboarding && isFirstLogin) {
  // Afficher l'onboarding
  showOnboarding();
} else {
  // Passer directement au dashboard
  goToDashboard();
}
```

---

## ❓ POINT 4 - BESOIN DE VISUALISATION

### Contexte probable: Gestion des catégories dans l'admin

**SCÉNARIO A: Interface d'ajout de type de cuisine**

```
┌─────────────────────────────────────────────────┐
│  🍕 Ajouter un nouveau type de cuisine          │
├─────────────────────────────────────────────────┤
│                                                 │
│  Nom du type de cuisine:                        │
│  ┌─────────────────────────────────────────┐   │
│  │ [Entrer le nom...]                      │   │
│  └─────────────────────────────────────────┘   │
│                                                 │
│  Catégories à inclure (en plus de Boissons     │
│  et Desserts qui sont automatiques):            │
│  ┌─────────────────────────────────────────┐   │
│  │ + Ajouter une catégorie                 │   │
│  │                                          │   │
│  │ ┌─────────────────┐  [Supprimer]       │   │
│  │ │ Catégorie 1     │                     │   │
│  │ └─────────────────┘                     │   │
│  │                                          │   │
│  │ ┌─────────────────┐  [Supprimer]       │   │
│  │ │ Catégorie 2     │                     │   │
│  │ └─────────────────┘                     │   │
│  │                                          │   │
│  └─────────────────────────────────────────┘   │
│                                                 │
│  ℹ️ Boissons et Desserts seront ajoutés        │
│     automatiquement                             │
│                                                 │
│  [Annuler]              [Créer] ✅             │
└─────────────────────────────────────────────────┘
```

**SCÉNARIO B: Liste des types de cuisine**

```
┌─────────────────────────────────────────────────┐
│  📋 Mes types de cuisine                        │
│  [+ Nouveau type de cuisine]                    │
├─────────────────────────────────────────────────┤
│                                                 │
│  🍔 Burger                           [Modifier] │
│     Catégories: 5                    [Gérer]   │
│     Produits: 24                                │
│                                                 │
│  🍕 Pizza                            [Modifier] │
│     Catégories: 4                    [Gérer]   │
│     Produits: 18                                │
│                                                 │
│  🍜 Pâtes                            [Modifier] │
│     Catégories: 3                    [Gérer]   │
│     Produits: 12                                │
│                                                 │
└─────────────────────────────────────────────────┘
```

**EST-CE L'UN DE CES SCÉNARIOS ? OU AUTRE CHOSE ?**

---

## ❓ POINT 5 - BESOIN DE VISUALISATION

### Contexte probable: Gestion des produits

**SCÉNARIO A: Ajout de produit**

```
┌─────────────────────────────────────────────────┐
│  🍽️ Ajouter un produit                          │
├─────────────────────────────────────────────────┤
│                                                 │
│  Type de cuisine:                               │
│  ┌─────────────────────────────────────────┐   │
│  │ [Sélectionner...        ▼]              │   │
│  │ - Burger                                 │   │
│  │ - Pizza                                  │   │
│  │ - Pâtes                                  │   │
│  └─────────────────────────────────────────┘   │
│                                                 │
│  Catégorie:                                     │
│  ┌─────────────────────────────────────────┐   │
│  │ [Sélectionner...        ▼]              │   │
│  │ - Burgers classiques                     │   │
│  │ - Burgers poulet                         │   │
│  │ - Boissons                               │   │
│  │ - Desserts                               │   │
│  └─────────────────────────────────────────┘   │
│                                                 │
│  Nom du produit:                                │
│  ┌─────────────────────────────────────────┐   │
│  │ [Entrer le nom...]                      │   │
│  └─────────────────────────────────────────┘   │
│                                                 │
│  Prix:                                          │
│  ┌─────────────────┐                           │
│  │ [Prix €]        │                           │
│  └─────────────────┘                           │
│                                                 │
│  Description:                                   │
│  ┌─────────────────────────────────────────┐   │
│  │ [Entrer la description...]              │   │
│  │                                          │   │
│  └─────────────────────────────────────────┘   │
│                                                 │
│  Image:                                         │
│  [📁 Choisir une image]                        │
│                                                 │
│  [Annuler]              [Ajouter] ✅           │
└─────────────────────────────────────────────────┘
```

**SCÉNARIO B: Liste des produits avec filtres**

```
┌─────────────────────────────────────────────────┐
│  🍽️ Mes produits                                │
│  [+ Nouveau produit]                            │
├─────────────────────────────────────────────────┤
│  Filtrer par:                                   │
│  Type: [Tous ▼]  Catégorie: [Toutes ▼]        │
│                                                 │
│  ────────────────────────────────────────────  │
│                                                 │
│  🍔 Big Burger                       8.50€      │
│     Catégorie: Burgers classiques               │
│     [Modifier] [Dupliquer] [Supprimer]         │
│                                                 │
│  🍔 Chicken Burger                  10.00€      │
│     Catégorie: Burgers poulet                   │
│     [Modifier] [Dupliquer] [Supprimer]         │
│                                                 │
│  🥤 Coca-Cola                       2.50€       │
│     Catégorie: Boissons                         │
│     [Modifier] [Dupliquer] [Supprimer]         │
│                                                 │
└─────────────────────────────────────────────────┘
```

**EST-CE L'UN DE CES SCÉNARIOS ? OU AUTRE CHOSE ?**

---

## ❓ POINT 6 - BESOIN DE VISUALISATION

### Contexte probable: Organisation des menus

**SCÉNARIO A: Vue client - Sélection du type de cuisine**

```
┌─────────────────────────────────────────────────┐
│            🍽️ SNACKUP                           │
│        Que voulez-vous manger ?                 │
├─────────────────────────────────────────────────┤
│                                                 │
│  ┌─────────────┐  ┌─────────────┐              │
│  │     🍔      │  │     🍕      │              │
│  │   Burger    │  │   Pizza     │              │
│  │  24 produits│  │  18 produits│              │
│  └─────────────┘  └─────────────┘              │
│                                                 │
│  ┌─────────────┐  ┌─────────────┐              │
│  │     🍜      │  │     🍣      │              │
│  │   Pâtes     │  │   Sushi     │              │
│  │  12 produits│  │  15 produits│              │
│  └─────────────┘  └─────────────┘              │
│                                                 │
│  ┌─────────────┐  ┌─────────────┐              │
│  │     🥗      │  │     🍰      │              │
│  │   Sandwich  │  │  Desserts   │              │
│  │   8 produits│  │  10 produits│              │
│  └─────────────┘  └─────────────┘              │
│                                                 │
└─────────────────────────────────────────────────┘
```

**SCÉNARIO B: Vue client - Menu après sélection "Burger"**

```
┌─────────────────────────────────────────────────┐
│  ← Retour      🍔 BURGER                        │
├─────────────────────────────────────────────────┤
│                                                 │
│  📂 Burgers classiques                          │
│  ┌───────────────────────────────────────────┐ │
│  │ Big Burger                        8.50€   │ │
│  │ Description du burger...                  │ │
│  │                              [Ajouter] ➕  │ │
│  └───────────────────────────────────────────┘ │
│  ┌───────────────────────────────────────────┐ │
│  │ Cheese Burger                     9.00€   │ │
│  │ Description du burger...                  │ │
│  │                              [Ajouter] ➕  │ │
│  └───────────────────────────────────────────┘ │
│                                                 │
│  📂 Burgers poulet                              │
│  ┌───────────────────────────────────────────┐ │
│  │ Chicken Burger                   10.00€   │ │
│  │ Description du burger...                  │ │
│  │                              [Ajouter] ➕  │ │
│  └───────────────────────────────────────────┘ │
│                                                 │
│  📂 Boissons                                    │
│  ┌───────────────────────────────────────────┐ │
│  │ Coca-Cola 33cl                    2.50€   │ │
│  │                              [Ajouter] ➕  │ │
│  └───────────────────────────────────────────┘ │
│                                                 │
│  📂 Desserts                                    │
│  ┌───────────────────────────────────────────┐ │
│  │ Tiramisu                          4.50€   │ │
│  │                              [Ajouter] ➕  │ │
│  └───────────────────────────────────────────┘ │
│                                                 │
└─────────────────────────────────────────────────┘
```

**EST-CE L'UN DE CES SCÉNARIOS ? OU AUTRE CHOSE ?**

---

## 📊 STRUCTURE DE DONNÉES PROPOSÉE

### Table: cuisine_types (nouveau)
```sql
CREATE TABLE cuisine_types (
  id INT PRIMARY KEY AUTO_INCREMENT,
  restaurant_id INT,
  name VARCHAR(100),        -- ex: "Burger", "Pizza", "Pâtes"
  icon VARCHAR(50),         -- ex: "🍔", "🍕", "🍜"
  description TEXT,
  is_active TINYINT(1),
  sort_order INT
);
```

### Table: categories (existante - modification)
```sql
ALTER TABLE categories ADD COLUMN cuisine_type_id INT;
ALTER TABLE categories ADD COLUMN is_common TINYINT(1) DEFAULT 0;
-- is_common = 1 pour "Boissons" et "Desserts"
```

### Exemple de données:

**cuisine_types:**
```
id | name      | icon | restaurant_id
---|-----------|------|-------------
1  | Burger    | 🍔   | 3
2  | Pizza     | 🍕   | 3
3  | Pâtes     | 🍜   | 3
4  | Sushi     | 🍣   | 3
5  | Sandwich  | 🥗   | 3
6  | Desserts  | 🍰   | 3
```

**categories:**
```
id | name               | cuisine_type_id | is_common | restaurant_id
---|--------------------|-----------------|-----------|--------------
1  | Burgers classiques | 1               | 0         | 3
2  | Burgers poulet     | 1               | 0         | 3
3  | Boissons           | NULL            | 1         | 3  ← Commune à tous
4  | Desserts           | NULL            | 1         | 3  ← Commune à tous
5  | Pizzas viande      | 2               | 0         | 3
6  | Pizzas végé        | 2               | 0         | 3
```

---

## ❓ QUESTIONS POUR VOUS

**Pour le point 4, voulez-vous:**
- A) Interface admin pour créer/gérer les types de cuisine ?
- B) Interface admin pour organiser les catégories ?
- C) Autre chose ?

**Pour le point 5, voulez-vous:**
- A) Interface admin pour ajouter des produits ?
- B) Interface admin pour gérer les produits existants ?
- C) Autre chose ?

**Pour le point 6, voulez-vous:**
- A) Interface client pour choisir le type de cuisine ?
- B) Interface client pour voir le menu d'un type de cuisine ?
- C) Autre chose ?

**Merci de me dire quel scénario correspond à ce que vous voulez pour chaque point ! 😊**
