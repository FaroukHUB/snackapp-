# 🔍 Problème: Enrichissement automatique des adresses

## Diagnostic

Le script d'enrichissement `ENRICHIR-CLIENTS-AUTO.sh` échoue avec l'erreur:

```
❌ Erreur: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'delivery_address' in 'SELECT'
```

## Cause

**La table `orders` ne contient PAS de colonne `delivery_address`** !

### Structure actuelle de la table orders:
- ✅ `customer_name`
- ✅ `customer_phone`
- ✅ `notes` ← Possiblement l'adresse ici?
- ✅ `pickup_time`
- ❌ `delivery_address` ← **N'EXISTE PAS**
- ❌ `delivery_instructions` ← **N'EXISTE PAS**

## Impact

### Sur les commandes existantes:
- ❌ **Aucune adresse de livraison n'est stockée explicitement**
- 🤔 L'adresse pourrait être dans le champ `notes` (à vérifier)
- ⚠️  Le script d'enrichissement ne peut PAS extraire les adresses des anciennes commandes

### Sur les futures commandes:
- ⚠️  Le frontend ne capture pas l'adresse dans un champ dédié
- ⚠️  L'API ne sauvegarde pas l'adresse de livraison

## Solution

### 1️⃣ Appliquer la migration SQL (OBLIGATOIRE)

Cette migration ajoute les colonnes manquantes à la table `orders`:

```bash
cd ~/Marvelous.mon-agenceweb.fr
git pull origin claude/review-progress-continue-U4j8i
chmod +x APPLIQUER-MIGRATION-DELIVERY-ADDRESS.sh
./APPLIQUER-MIGRATION-DELIVERY-ADDRESS.sh
```

**Ce que fait la migration:**
- ✅ Ajoute colonne `delivery_address` (VARCHAR 500)
- ✅ Ajoute colonne `delivery_instructions` (TEXT)
- ✅ Crée un index de recherche
- ✅ Fait un backup de la table avant modification

### 2️⃣ Modifier le frontend pour capturer l'adresse (RECOMMANDÉ)

**Fichier à modifier**: `template-v2/cart.html`

Actuellement, le frontend a un mode "Livraison" mais ne capture pas explicitement l'adresse.

**Actions nécessaires:**
1. Ajouter un champ `<textarea id="deliveryAddress">` dans le formulaire
2. Ajouter un champ `<textarea id="deliveryInstructions">` pour les instructions
3. Modifier `CartPage.submitOrder()` pour envoyer l'adresse à l'API
4. Valider que l'adresse est saisie si mode = 'delivery'

### 3️⃣ Modifier l'API pour sauvegarder l'adresse

**Fichier à modifier**: `database/repositories/OrderRepository.php`

Ligne 131-145, ajouter les champs dans l'INSERT:

```php
$orderId = Database::insert('orders', [
    'restaurant_id' => $restaurantId,
    'customer_id' => $customerId,
    'order_number' => $orderNumber,
    'customer_name' => $data['customer_name'] ?? 'Client',
    'customer_phone' => $data['customer_phone'] ?? '',
    'delivery_address' => $data['delivery_address'] ?? null,      // ← AJOUTER
    'delivery_instructions' => $data['delivery_instructions'] ?? null,  // ← AJOUTER
    'subtotal' => $data['subtotal'] ?? $data['total'] ?? 0,
    'total' => $data['total'] ?? 0,
    // ...
]);
```

### 4️⃣ Re-tester le script d'enrichissement

Une fois la migration appliquée et le frontend modifié:

```bash
./ENRICHIR-CLIENTS-AUTO.sh
```

**Résultat attendu:**
- ✅ Le script ne plantera plus
- ⚠️  **MAIS** il ne trouvera aucune adresse sur les anciennes commandes
- ✅ Il pourra extraire les adresses des **nouvelles commandes** passées après modification

## Option alternative: Extraire depuis `notes`

Si les anciennes commandes ont l'adresse dans le champ `notes`, on peut:

1. Créer un script pour migrer les adresses depuis `notes` vers `delivery_address`
2. Utiliser regex ou parsing pour extraire l'adresse du texte libre

**Exemple de requête SQL:**
```sql
-- Vérifier si des adresses sont dans notes
SELECT id, order_number, customer_name, notes
FROM orders
WHERE notes IS NOT NULL
  AND notes LIKE '%rue%'
LIMIT 10;
```

## Recommandation

**Ordre d'exécution recommandé:**

1. ✅ Appliquer la migration SQL (MAINTENANT)
2. ✅ Modifier le frontend pour capturer l'adresse (AVANT prochaines commandes)
3. ✅ Modifier l'API pour sauvegarder l'adresse (AVANT prochaines commandes)
4. ⏳ Attendre que des commandes soient passées avec l'adresse
5. ✅ Re-exécuter le script d'enrichissement (PLUS TARD)

## FAQ

**Q: Le script d'enrichissement fonctionnera-t-il après la migration?**
A: Oui, il ne plantera plus. MAIS il ne trouvera aucune adresse tant que de nouvelles commandes ne seront pas passées.

**Q: Peut-on récupérer les adresses des anciennes commandes?**
A: Seulement si elles sont dans le champ `notes`. Il faudrait un script de parsing manuel.

**Q: Combien de temps avant que l'enrichissement soit utile?**
A: Dès que 1-2 clients auront passé des commandes avec le nouveau système.

---

**Créé le**: 2026-01-10
**Problème**: Colonne `delivery_address` inexistante
**Solution**: Migration SQL + modification frontend + API
