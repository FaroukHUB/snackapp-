# Intégration Stripe - L'Atelier Pizza Roubaix

## 📋 Prérequis

1. Compte Stripe actif (https://dashboard.stripe.com)
2. Clés API Stripe (test et production)
3. Composer installé sur le serveur o2switch

## 🔧 Installation

### 1. Installer Stripe SDK via Composer

```bash
cd /home/user/snackapp-/
composer require stripe/stripe-php
```

### 2. Configurer les clés Stripe

Éditer `/instances/atelier-pizza/backend-config.php` :

```php
'stripe' => [
    // MODE TEST (pour développement)
    'publishable_key' => 'pk_test_VOTRE_CLE_PUBLIQUE',
    'secret_key' => 'sk_test_VOTRE_CLE_SECRETE',
    'webhook_secret' => 'whsec_VOTRE_WEBHOOK_SECRET',
    'mode' => 'test'

    // MODE PRODUCTION (quand tout fonctionne)
    // 'publishable_key' => 'pk_live_VOTRE_CLE_PUBLIQUE',
    // 'secret_key' => 'sk_live_VOTRE_CLE_SECRETE',
    // 'webhook_secret' => 'whsec_VOTRE_WEBHOOK_SECRET',
    // 'mode' => 'live'
],
```

### 3. Créer les endpoints API

Créer `/snackup/admin/api/create-payment-intent.php` :

```php
<?php
require_once __DIR__ . '/../../backend/PaymentService.php';

header('Content-Type: application/json');

// Charger config instance
$config = require __DIR__ . '/../../../instances/atelier-pizza/backend-config.php';

// Récupérer données POST
$data = json_decode(file_get_contents('php://input'), true);
$orderId = $data['order_id'] ?? null;
$amount = $data['amount'] ?? 0;

if (!$orderId || $amount <= 0) {
    echo json_encode(['error' => 'Données invalides']);
    exit;
}

// Créer intention de paiement
$result = PaymentService::createPaymentIntent($amount, $orderId, $config);

echo json_encode($result);
```

Créer `/snackup/admin/api/stripe-webhook.php` :

```php
<?php
require_once __DIR__ . '/../../backend/PaymentService.php';

header('Content-Type: application/json');

// Charger config instance
$config = require __DIR__ . '/../../../instances/atelier-pizza/backend-config.php';

// Récupérer payload et signature
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

// Traiter webhook
$result = PaymentService::handleWebhook($payload, $signature, $config);

echo json_encode($result);
```

### 4. Configurer le webhook Stripe

1. Aller sur : https://dashboard.stripe.com/webhooks
2. Cliquer "Add endpoint"
3. URL : `https://atelierpizza.mon-agenceweb.fr/snackup/admin/api/stripe-webhook.php`
4. Événements à écouter :
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
   - `charge.refunded`
5. Copier le "Signing secret" dans `backend-config.php`

## 🎨 Frontend - Intégration dans cart.html

### 1. Ajouter Stripe.js

Dans `<head>` de `cart.html` :

```html
<script src="https://js.stripe.com/v3/"></script>
```

### 2. Ajouter le sélecteur de paiement

```html
<div class="payment-method-selector">
  <h3>Moyen de paiement</h3>

  <label>
    <input type="radio" name="payment" value="cash" checked>
    💵 Espèces (paiement à la livraison/retrait)
  </label>

  <label>
    <input type="radio" name="payment" value="card_terminal">
    💳 Carte Bleue (paiement à la livraison/retrait)
  </label>

  <label>
    <input type="radio" name="payment" value="ticket_resto">
    🎫 Ticket Restaurant
  </label>

  <label>
    <input type="radio" name="payment" value="card_online">
    🌐 Payer en ligne maintenant (CB sécurisé)
  </label>
</div>

<!-- Formulaire Stripe (caché par défaut) -->
<div id="stripe-payment-form" style="display:none; margin-top:20px;">
  <div id="card-element" style="padding:15px; border:1px solid #ccc; border-radius:8px;"></div>
  <div id="card-errors" role="alert" style="color:#c10000; margin-top:10px;"></div>
  <button id="pay-button" style="margin-top:15px; padding:12px 24px; background:#c10000; color:white; border:none; border-radius:8px; cursor:pointer;">
    Payer €<span id="amount-display">0.00</span>
  </button>
</div>
```

### 3. JavaScript pour gérer Stripe

```javascript
// Charger config instance
const config = window.SNACK_CONFIG;
const STRIPE_KEY = 'pk_test_VOTRE_CLE'; // À remplacer

// Initialiser Stripe
const stripe = Stripe(STRIPE_KEY);
const elements = stripe.elements();
const cardElement = elements.create('card', {
  style: {
    base: {
      fontSize: '16px',
      color: '#000',
      '::placeholder': {color: '#aaa'}
    }
  }
});

// Montrer/cacher formulaire Stripe
document.querySelectorAll('[name="payment"]').forEach(radio => {
  radio.addEventListener('change', (e) => {
    const form = document.getElementById('stripe-payment-form');
    if (e.target.value === 'card_online') {
      form.style.display = 'block';
      cardElement.mount('#card-element');
    } else {
      form.style.display = 'none';
      cardElement.unmount();
    }
  });
});

// Gérer le paiement
document.getElementById('pay-button').addEventListener('click', async () => {
  const amount = getTotalAmount(); // Fonction existante
  const orderId = createOrderInDatabase(); // Créer commande d'abord

  // 1. Créer intention de paiement
  const response = await fetch('/snackup/admin/api/create-payment-intent.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({order_id: orderId, amount: amount})
  });

  const {client_secret, error} = await response.json();

  if (error) {
    document.getElementById('card-errors').textContent = error;
    return;
  }

  // 2. Confirmer le paiement
  const {paymentIntent, error: confirmError} = await stripe.confirmCardPayment(client_secret, {
    payment_method: {card: cardElement}
  });

  if (confirmError) {
    document.getElementById('card-errors').textContent = confirmError.message;
  } else if (paymentIntent.status === 'succeeded') {
    alert('Paiement réussi ! Commande confirmée.');
    // Rediriger vers page de confirmation
    window.location.href = '/confirmation.html?order=' + orderId;
  }
});
```

## ✅ Tests

### Mode Test
- Carte de test : `4242 4242 4242 4242`
- Date : N'importe quelle date future
- CVC : N'importe quel 3 chiffres

### Cartes de test spéciales
- **Succès** : `4242 4242 4242 4242`
- **Refus** : `4000 0000 0000 0002`
- **3D Secure requis** : `4000 0027 6000 3184`

Voir toutes les cartes de test : https://stripe.com/docs/testing

## 🚀 Passer en production

1. Remplacer `pk_test_` par `pk_live_` dans le frontend
2. Remplacer `sk_test_` par `sk_live_` dans `backend-config.php`
3. Changer `mode` de `test` à `live`
4. Vérifier que le webhook pointe vers la bonne URL

## 📊 Monitoring

Dashboard Stripe : https://dashboard.stripe.com/payments

Vous pouvez y voir :
- Tous les paiements
- Les échecs
- Les remboursements
- Les webhooks reçus

## 🆘 Dépannage

### Erreur "Invalid API Key"
→ Vérifier que les clés sont correctes dans `backend-config.php`

### Webhook ne fonctionne pas
→ Vérifier l'URL et le secret dans le dashboard Stripe

### Paiement bloqué en "pending"
→ Vérifier que le webhook est bien configuré et reçu

## 📞 Support

- Documentation Stripe : https://stripe.com/docs
- Support Stripe : https://support.stripe.com
