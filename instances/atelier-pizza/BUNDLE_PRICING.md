# Système de Prix Dégressifs (Bundle Pricing)

## 📊 Principe

Le système permet d'offrir des **réductions automatiques** quand le client achète plusieurs produits du même type.

**Exemple Atelier Pizza :**
- 1 Pizza Solo (26cm) = **7,50€**
- 2 Pizzas Solo = **13€** au lieu de 15€ → **Économie de 2€**
- 1 Pizza Duo (31cm) = **9€**
- 2 Pizzas Duo = **15€** au lieu de 18€ → **Économie de 3€**

---

## 🗃️ Structure Base de Données

### Colonnes ajoutées à la table `products`

```sql
bundle_enabled       TINYINT(1)    -- 1 si le bundle est activé, 0 sinon
bundle_quantity      INT           -- Quantité pour activer le bundle (ex: 2)
bundle_price_solo    DECIMAL(10,2) -- Prix total pour le bundle Solo
bundle_price_duo     DECIMAL(10,2) -- Prix total pour le bundle Duo
```

### Exemple de données

```sql
-- Pizza REINE
price_solo = 7.50
price_menu = 9.00  -- Renommé en "Duo" dans le frontend
bundle_enabled = 1
bundle_quantity = 2
bundle_price_solo = 13.00  -- Prix pour 2 pizzas Solo
bundle_price_duo = 15.00   -- Prix pour 2 pizzas Duo
```

---

## 💻 Implémentation Frontend (cart.js)

### 1. Détecter les bundles dans le panier

```javascript
function calculateBundleDiscount() {
  const cart = getCart(); // Fonction existante

  // Grouper les pizzas par taille
  const pizzasSolo = cart.filter(item =>
    item.category_id === 'pizza' && item.size === 'solo'
  );
  const pizzasDuo = cart.filter(item =>
    item.category_id === 'pizza' && item.size === 'duo'
  );

  let discount = 0;

  // Calculer remise pour Pizzas Solo
  if (pizzasSolo.length >= 2) {
    const bundles = Math.floor(pizzasSolo.length / 2);
    const priceWithoutBundle = pizzasSolo.length * 7.50;
    const priceWithBundle = (bundles * 13.00) + ((pizzasSolo.length % 2) * 7.50);
    discount += (priceWithoutBundle - priceWithBundle);
  }

  // Calculer remise pour Pizzas Duo
  if (pizzasDuo.length >= 2) {
    const bundles = Math.floor(pizzasDuo.length / 2);
    const priceWithoutBundle = pizzasDuo.length * 9.00;
    const priceWithBundle = (bundles * 15.00) + ((pizzasDuo.length % 2) * 9.00);
    discount += (priceWithoutBundle - priceWithBundle);
  }

  return discount;
}
```

### 2. Afficher la remise dans le panier

```javascript
function displayCart() {
  const cart = getCart();
  const subtotal = calculateSubtotal();
  const bundleDiscount = calculateBundleDiscount();
  const total = subtotal - bundleDiscount;

  let html = `
    <div class="cart-items">
      ${cart.map(item => `
        <div class="cart-item">
          <span>${item.name} ${item.size === 'solo' ? 'Solo' : 'Duo'}</span>
          <span>${item.price.toFixed(2)}€</span>
        </div>
      `).join('')}
    </div>

    <div class="cart-summary">
      <div class="cart-line">
        <span>Sous-total</span>
        <span>${subtotal.toFixed(2)}€</span>
      </div>

      ${bundleDiscount > 0 ? `
        <div class="cart-line discount">
          <span>🎉 Remise "2 Pizzas"</span>
          <span>-${bundleDiscount.toFixed(2)}€</span>
        </div>
      ` : ''}

      <div class="cart-line total">
        <span><strong>Total</strong></span>
        <span><strong>${total.toFixed(2)}€</strong></span>
      </div>
    </div>
  `;

  document.getElementById('cart-container').innerHTML = html;
}
```

### 3. Badge info sur les produits

```html
<!-- Sur chaque pizza, afficher un badge -->
<div class="product-card">
  <h3>Pizza REINE</h3>
  <p>Mozzarella, jambon de dinde, champignons</p>

  <div class="prices">
    <span>Solo 26cm : 7,50€</span>
    <span>Duo 31cm : 9,00€</span>
  </div>

  <div class="bundle-badge">
    💡 2 Pizzas Solo = 13€ | 2 Pizzas Duo = 15€
  </div>

  <button onclick="addToCart('pizza-reine', 'solo')">Ajouter Solo</button>
  <button onclick="addToCart('pizza-reine', 'duo')">Ajouter Duo</button>
</div>
```

---

## 📐 Exemples de Calculs

### Exemple 1 : 2 Pizzas Solo
```
Pizza REINE Solo    7,50€
Pizza ORIENTALE Solo 7,50€
----------------------------
Sous-total          15,00€
Remise "2 Pizzas"   -2,00€
----------------------------
TOTAL               13,00€ ✅
```

### Exemple 2 : 3 Pizzas Solo
```
Pizza REINE Solo      7,50€
Pizza ORIENTALE Solo  7,50€
Pizza CARNIVORE Solo  7,50€
----------------------------
Sous-total            22,50€
Remise "2 Pizzas"     -2,00€  (pour les 2 premières)
----------------------------
TOTAL                 20,50€ ✅
```

### Exemple 3 : 2 Pizzas Duo
```
Pizza REINE Duo       9,00€
Pizza TEXANE Duo      9,00€
----------------------------
Sous-total            18,00€
Remise "2 Pizzas"     -3,00€
----------------------------
TOTAL                 15,00€ ✅
```

### Exemple 4 : Mix Solo + Duo
```
Pizza REINE Solo      7,50€
Pizza CARNIVORE Solo  7,50€
Pizza TEXANE Duo      9,00€
Pizza KEBAB Duo       9,00€
----------------------------
Sous-total            33,00€
Remise "2 Solo"       -2,00€
Remise "2 Duo"        -3,00€
----------------------------
TOTAL                 28,00€ ✅
```

---

## 🎨 CSS pour le badge

```css
.bundle-badge {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  text-align: center;
  margin-top: 10px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.cart-line.discount {
  color: #10b981;
  font-weight: 600;
}

.cart-line.discount span:first-child::before {
  content: "🎉 ";
}
```

---

## ✅ Checklist d'implémentation

- [x] Migration SQL exécutée (`add_bundle_pricing.sql`)
- [x] Menu rempli avec `bundle_enabled=1` pour toutes les pizzas
- [ ] Fonction `calculateBundleDiscount()` ajoutée à `cart.js`
- [ ] Affichage de la remise dans le panier
- [ ] Badge "2 Pizzas = XX€" sur les cartes produits
- [ ] Tests :
  - [ ] 2 Pizzas Solo = 13€
  - [ ] 2 Pizzas Duo = 15€
  - [ ] 3 Pizzas Solo = 20,50€
  - [ ] Mix Solo + Duo

---

## 🚀 Prochaines étapes

1. Exécuter `populate_menu.sql` pour remplir le menu
2. Implémenter le calcul de remise dans `cart.js`
3. Ajouter les badges visuels sur les produits
4. Tester tous les scénarios
5. Ajouter les photos des produits

---

## 📞 Support

Si tu veux étendre le système à d'autres catégories (ex: pâtes, gratins), il suffit de :
1. Mettre `bundle_enabled=1` pour ces produits
2. Définir `bundle_price_solo` (ou créer une colonne générique `bundle_price`)
3. Adapter la fonction `calculateBundleDiscount()` pour détecter ces produits
