# 🔄 Enrichissement Automatique des Profils Clients

## Vue d'ensemble

Le système CRM enrichit automatiquement les profils clients en analysant l'historique des commandes, tout en préservant les données saisies manuellement par l'administrateur.

---

## 📊 Données Automatiques vs Manuelles

### ✅ Enrichissement AUTOMATIQUE (depuis les commandes)

Ces données sont **calculées et mises à jour automatiquement**:

| Donnée | Source | Mise à jour |
|--------|--------|-------------|
| **Nombre de commandes** | Table `orders` | Temps réel via SQL |
| **Total dépensé** | Table `orders` | Temps réel via SQL |
| **Points fidélité** | Table `customers` | Incrémenté à chaque commande |
| **Panier moyen** | Calculé: `total_spent / orders_count` | Temps réel (calcul) |
| **Adresses de livraison** | Extraction depuis `orders.delivery_address` | Script d'enrichissement |
| **Produits favoris (top 3)** | Analyse `order_items` | Calcul à l'affichage |
| **Dernière commande** | `last_order_at` | Temps réel via SQL |

### 🖊️ Données MANUELLES (saisie admin)

Ces données sont **uniquement remplies par l'administrateur**:

| Donnée | Usage |
|--------|-------|
| **Tags** | Segmentation (VIP, Régulier, Inactif, etc.) |
| **Notes admin** | Notes privées sur le client |
| **Allergies** | Informations médicales importantes |
| **Préférences** | Notes, instructions de livraison personnalisées |

---

## 🚀 Utilisation

### Enrichir tous les profils depuis les commandes

**Étape 1: Rendre le script exécutable**
```bash
cd ~/Marvelous.mon-agenceweb.fr
chmod +x ENRICHIR-CLIENTS-AUTO.sh
```

**Étape 2: Exécuter l'enrichissement**
```bash
./ENRICHIR-CLIENTS-AUTO.sh
```

**Résultat**:
- ✅ Extraction des adresses uniques depuis les commandes
- ✅ Ajout max 2 adresses par client (Maison, Bureau)
- ✅ Préservation des adresses déjà enregistrées
- ✅ Pas de doublons

### Exemple de résultat

```
============================================
🔄 ENRICHISSEMENT AUTOMATIQUE CLIENTS
============================================

📊 Analyse des commandes...

✅ 20 clients trouvés

---
Client: Farouk Etsaalbi (#4)
  ✅ 2 adresse(s) ajoutée(s)
---
Client: Oum salman (#1)
  ✅ 1 adresse(s) ajoutée(s)
---
Client: Test (#3)
  ℹ️  Aucune commande avec adresse

============================================
📊 RÉSUMÉ
============================================
Clients traités: 20
Adresses ajoutées: 15
Erreurs: 0

✅ Enrichissement terminé!
```

---

## 🛡️ Sécurité et Conflits

### Gestion des conflits

Le système évite automatiquement les conflits:

1. **Limite stricte**: Max 2 adresses par client
2. **Dédoublonnage**: Une adresse identique n'est jamais ajoutée 2 fois
3. **Préservation**: Les adresses déjà enregistrées sont conservées
4. **Priorité**: Les nouvelles adresses ne remplacent jamais les anciennes

### Préservation des données manuelles

**Garanties**:
- ✅ Tags personnalisés → **Jamais modifiés**
- ✅ Notes admin → **Jamais modifiées**
- ✅ Allergies/préférences → **Jamais modifiées**
- ✅ Adresses manuelles → **Jamais supprimées**

---

## 📋 Détails Techniques

### Extraction des adresses

```sql
SELECT DISTINCT delivery_address, delivery_instructions
FROM orders
WHERE customer_id = ?
  AND delivery_address IS NOT NULL
  AND delivery_address != ''
ORDER BY created_at DESC
LIMIT 10
```

**Logique**:
1. Récupère les 10 dernières commandes du client
2. Extrait les adresses uniques
3. Compare avec les adresses déjà enregistrées
4. Ajoute max 2 nouvelles adresses (si pas de doublon)
5. Met à jour le profil client

### Calcul des produits favoris

```sql
SELECT
    oi.product_name as name,
    COUNT(*) as count,
    SUM(oi.quantity) as total_quantity
FROM order_items oi
JOIN orders o ON oi.order_id = o.id
WHERE o.customer_id = ?
GROUP BY oi.product_name
ORDER BY count DESC
LIMIT 3
```

**Logique**:
- Calculé à la volée lors de l'affichage du profil client
- Pas stocké en base (toujours à jour)
- Top 3 des produits les plus commandés

---

## 🔄 Quand enrichir?

### Recommandations

**Enrichir maintenant (une fois)**:
```bash
./ENRICHIR-CLIENTS-AUTO.sh
```
→ Extrait toutes les adresses depuis l'historique existant

**Ensuite**:
- ✅ Les nouvelles commandes enregistrent automatiquement le profil client
- ✅ Nombre de commandes mis à jour automatiquement
- ✅ Points ajoutés automatiquement
- ✅ **Mais les adresses ne sont pas auto-ajoutées** (nécessite re-exécution du script)

**Options pour l'avenir**:
1. **Manuel**: Re-exécuter le script périodiquement (ex: 1x/mois)
2. **Automatique**: Créer un cron job (à discuter)
3. **Hybride**: Script + ajout manuel via interface CRM

---

## 📝 Logs et Vérification

Après enrichissement, vérifiez dans la BDD:

```sql
-- Voir les clients avec adresses
SELECT
    c.name,
    c.addresses
FROM customers c
WHERE c.addresses IS NOT NULL
  AND c.addresses != '[]';
```

Ou directement dans l'interface CRM:
→ `https://marvelous.mon-agenceweb.fr/admin-panel-v2/clients.php`

---

## ⚠️ Important

1. **Backup automatique**: Le script ne modifie que la colonne `addresses`
2. **Pas de suppression**: Les données existantes sont toujours préservées
3. **Limite stricte**: Max 2 adresses par client (règle métier)
4. **Exécution sûre**: Peut être exécuté plusieurs fois sans risque

---

## 🎯 Résultat Final

Après enrichissement, chaque profil client contient:

**Données automatiques**:
- ✅ Statistiques (commandes, dépenses, panier moyen)
- ✅ Points fidélité à jour
- ✅ Top 3 produits favoris
- ✅ Adresses de livraison (max 2)

**Données manuelles** (à remplir via CRM):
- 🖊️ Tags de segmentation
- 🖊️ Notes admin privées
- 🖊️ Allergies/préférences
- 🖊️ Ajustements manuels

**Interface CRM complète** → Tout visible et modifiable sur `clients.php` ✨
