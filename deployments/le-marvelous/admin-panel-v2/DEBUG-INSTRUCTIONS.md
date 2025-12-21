# 🔧 Instructions de Debug - Problème "Ajouter Produit"

## 📌 Problèmes signalés
1. ❌ **Bouton "Ajouter produit" ne fonctionne pas**
2. ✅ **Webhook WhatsApp toujours connecté** (confirmé OK dans le code)

---

## 🧪 ÉTAPE 1 : Test API Simple

1. **Ouvre cette page** : `https://snack.mon-agenceweb.fr/admin-panel-v2/test-api.php`

2. **Clique sur les 3 boutons** dans l'ordre :
   - ✅ Test 1 : Connexion API
   - ✅ Test 2 : Ajout produit
   - ✅ Test 3 : Vérification config file

3. **Note les résultats** :
   - Si Test 1 ✅ → API fonctionne
   - Si Test 2 ✅ → Ajout produit fonctionne
   - Si Test 3 montre "Inscriptible: NON" ❌ → Problème de permissions

---

## 🔍 ÉTAPE 2 : Debug dans Products Manager

1. **Va sur** : `https://snack.mon-agenceweb.fr/admin-panel-v2/products-manager.php`

2. **Appuie sur F12** pour ouvrir la console JavaScript

3. **Clique sur "Ajouter"** et remplis le formulaire

4. **Clique sur "Enregistrer"** et regarde la console

5. **Tu devrais voir** :
   ```
   🔧 [DEBUG] Début saveProduct()
   📦 [DEBUG] Données à envoyer: {action: "add", name: "...", ...}
   📡 [DEBUG] Statut HTTP: 200
   ✅ [DEBUG] Réponse API: {success: true, ...}
   ```

6. **Si tu vois une erreur** :
   - Copie le message d'erreur complet
   - Prends un screenshot de la console
   - Envoie-moi ça

---

## ✅ ÉTAPE 3 : Vérifier le Webhook WhatsApp

Le webhook est **TOUJOURS CONNECTÉ** et fonctionne normalement. Code vérifié dans `script.js` lignes 782-797 :

```javascript
// 1️⃣ Envoyer au webhook admin
fetch('admin-panel-v2/webhook.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify(orderData)
})
.then(r => r.json())
.then(data => console.log('✅ Commande enregistrée:', data))
.catch(err => console.warn('⚠️ Erreur webhook:', err));

// 2️⃣ Ouvrir WhatsApp immédiatement
window.open(`https://wa.me/${restoPhone}?text=${encoded}`, "_blank");
```

**Comment tester** :
1. Va sur le site public : `https://snack.mon-agenceweb.fr`
2. Ajoute un produit au panier
3. Clique sur "Envoyer au resto"
4. Vérifie que :
   - ✅ WhatsApp s'ouvre
   - ✅ Message pré-rempli
   - ✅ Commande apparaît dans l'admin

---

## 📊 Logs Serveur

Si les tests échouent, vérifie les logs PHP :
- O2switch cPanel → Fichiers → Logs → error_log
- Cherche les lignes avec `[PRODUCTS API]`

---

## 🆘 Si rien ne fonctionne

Envoie-moi :
1. Screenshot de la console (F12) quand tu cliques sur "Enregistrer"
2. Résultats des 3 tests de test-api.php
3. Message d'erreur exact qui s'affiche

---

## ✅ Améliorations apportées

1. ✅ Ajout de console.log() partout dans products-manager.php
2. ✅ Ajout d'error_log() dans api/products.php
3. ✅ Meilleure gestion des erreurs HTTP
4. ✅ Page de test test-api.php pour debug
5. ✅ Vérification des permissions sur config file
6. ✅ Messages d'erreur plus explicites

---

**Créé le** : 2025-12-14
**Version** : 1.0
