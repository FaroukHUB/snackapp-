# 🎯 Système de Démo Multi-Sessions

## 📋 Vue d'ensemble

Le système de démo permet à plusieurs restaurateurs de tester simultanément la plateforme SnackUp de manière isolée. Chaque utilisateur obtient sa propre session temporaire avec des données séparées.

## 🔄 Flux utilisateur complet

### 1️⃣ Landing Page (mon-agenceweb.fr)
- Le visiteur découvre l'offre
- Bouton "Tester notre système" → Redirige vers `demo.mon-agenceweb.fr`

### 2️⃣ Onboarding (3 étapes)
**URL:** `demo.mon-agenceweb.fr/snackup/frontend/onboarding.html`

**Étapes:**
1. **Bienvenue** 👋
   - Présentation des avantages
   - 3 valeurs clés : Rapide, Simple, Personnalisé

2. **Configuration** ⚙️
   - Mode de commande (Livraison, Click & Collect, Les deux)
   - Horaires d'ouverture (Standard, Étendu, Personnalisé)

3. **Résumé** ✅
   - Récapitulatif des choix
   - Validation et redirection

**Sortie:** Redirection vers la page de sélection de cuisine

---

### 3️⃣ Sélection des Types de Cuisine
**URL:** `demo.mon-agenceweb.fr/snackup/frontend/cuisine-selection.html`

**Fonctionnalités:**
- Grille visuelle de 12 types de cuisine avec images
- Sélection multiple possible
- **Assistant virtuel intégré** 🤖
  - Guide l'utilisateur à chaque étape
  - Messages contextuels selon les actions
  - Toujours accessible via l'icône flottante

**Types de cuisine disponibles:**
- 🍔 Burgers
- 🍕 Pizza
- 🍣 Sushi
- 🌮 Tacos
- 🍜 Asiatique
- 🥙 Kebab
- 🥗 Poké Bowl
- 🍰 Desserts
- 🥬 Végétarien
- 🥖 Française
- 🦞 Fruits de mer
- 🍖 BBQ & Grillades

**Sortie:** Redirection vers l'admin avec paramètres de session

---

### 4️⃣ Admin Dashboard
**URL:** `demo.mon-agenceweb.fr/snackup/admin/index.php?session={SESSION_ID}&demo=1`

**Fonctionnalités:**
- **Assistant virtuel permanent** 🤖
  - Tour guidé au premier lancement
  - Aide contextuelle disponible
  - Explications pour chaque section

- **Sections disponibles:**
  - 📊 Tableau de bord avec statistiques
  - 📋 Gestion des commandes
  - 🍕 Gestion du menu
  - 👥 Clients et fidélité
  - ⚙️ Paramètres du restaurant

---

## 🔐 Système Multi-Sessions

### Génération de Session ID

Chaque visiteur reçoit un **Session ID unique** :
```
Format: demo_{timestamp}_{random}
Exemple: demo_1710952800_k7j3m9x2p
```

### Isolation des données

- ✅ **Stockage isolé** : Chaque session a son propre fichier JSON
- ✅ **Données temporaires** : Expiration automatique après 24h
- ✅ **Nettoyage automatique** : Suppression des sessions expirées
- ✅ **Pas de conflit** : Sessions complètement indépendantes

### Emplacement des fichiers

```
database/demo-sessions/
├── demo_1710952800_abc123.json
├── demo_1710953000_xyz789.json
└── .gitignore
```

### Structure d'une session

```json
{
  "session_id": "demo_1710952800_abc123",
  "created_at": "2024-03-21 10:30:00",
  "expires_at": "2024-03-22 10:30:00",
  "last_activity": "2024-03-21 12:15:00",
  "config": {
    "deliveryMode": "both",
    "hours": "hours-extended"
  },
  "cuisines": ["burger", "pizza", "desserts"],
  "restaurant_data": {
    "name": "Restaurant Démo",
    "slug": "restaurant-demo",
    "is_demo": true,
    ...
  }
}
```

---

## 🤖 Assistant Virtuel

### Fonctionnalités

1. **Messages contextuels**
   - Bienvenue personnalisée
   - Aide à la sélection
   - Guidage dans l'admin

2. **Interactions**
   - Bulle de message animée
   - Icône flottante toujours accessible
   - Fermeture automatique ou manuelle

3. **Tour guidé de l'admin**
   - Explique chaque section
   - Navigation progressive
   - Peut être ignoré ou repris

### Messages par contexte

**Page cuisine:**
- Bienvenue initiale
- Premier choix → Encouragement
- Choix multiples → Validation
- Prêt à continuer → Redirection

**Page admin:**
- Proposition de tour guidé
- Aide par section (Commandes, Menu, Paramètres)
- Toujours disponible via l'icône

---

## 📁 Architecture des fichiers

```
snackup/
├── frontend/
│   ├── onboarding.html              # Onboarding 3 étapes
│   ├── cuisine-selection.html       # Sélection des cuisines
│   ├── DEMO-SYSTEM-README.md        # Cette documentation
│   ├── js/
│   │   ├── onboarding.js            # Logique onboarding
│   │   ├── cuisine-selection.js     # Logique sélection + session
│   │   └── admin-assistant.js       # Assistant virtuel admin
│   └── images/
│       └── cuisine-types/           # SVG des types de cuisine
│
├── backend/
│   └── session-manager.php          # Gestionnaire de sessions
│
├── admin/
│   └── index.php                    # Admin avec support démo
│
└── database/
    └── demo-sessions/               # Sessions temporaires
        ├── .gitignore
        └── demo_*.json
```

---

## 🚀 Utilisation

### Pour le visiteur

1. **Landing page** → Clic sur "Tester notre système"
2. **Onboarding** → Configuration en 3 étapes
3. **Sélection cuisine** → Choix des types de cuisine
4. **Admin** → Découverte du tableau de bord

### Pour le développeur

**Initialiser une session de démo:**
```javascript
// Automatique lors de l'accès à cuisine-selection.html
const sessionId = getSessionId(); // Génère ou récupère
```

**Vérifier une session PHP:**
```php
require_once 'backend/session-manager.php';

if (isDemoSession()) {
    $sessionId = getCurrentDemoSessionId();
    $session = getDemoSessionManager()->getSession($sessionId);
}
```

**Nettoyer les sessions expirées:**
```php
// Automatique au chargement du SessionManager
// Ou manuellement:
$manager = new DemoSessionManager();
// cleanup() appelé dans le constructeur
```

---

## ⚙️ Configuration

### Durée de session

Par défaut : **24 heures**

Modifier dans `session-manager.php` :
```php
private $sessionDuration = 86400; // En secondes
```

### Nettoyage automatique

Les sessions expirées sont supprimées :
- À chaque chargement du SessionManager
- Au chargement de cuisine-selection.js (frontend)

---

## 🎨 Design & UX

### Cohérence visuelle

- **Palette de couleurs** : Gradient violet-bleu (#667eea → #764ba2)
- **Animations** : Transitions fluides, effets hover
- **Responsive** : Adapté mobile, tablette, desktop
- **Icônes** : Emojis expressifs pour chaque élément

### Assistant virtuel

- **Position** : Fixe en bas à droite
- **Style** : Bulle blanche avec ombre, icône rond gradient
- **Animation** : Slide-in, pulse, bounce
- **Accessibilité** : Toujours visible, facilement fermable

---

## 🔧 Maintenance

### Logs

Les sessions sont loggées dans :
```
database/demo-sessions/demo_*.json
```

### Monitoring

Obtenir le nombre de sessions actives :
```php
$count = getDemoSessionManager()->getActiveSessionsCount();
echo "Sessions actives : " . $count;
```

### Sécurité

- ✅ Validation du format de session ID
- ✅ Expiration automatique
- ✅ Isolation des données
- ✅ Pas d'accès aux données réelles
- ✅ Nettoyage automatique

---

## 📊 Métriques

### Données collectées par session

- Date de création
- Durée de session
- Configuration choisie
- Types de cuisine sélectionnés
- Dernière activité

### Utilisation

Ces données peuvent être analysées pour :
- Comprendre les préférences
- Optimiser le parcours
- Améliorer la conversion

---

## 🐛 Dépannage

### Session non créée

**Problème** : Session ID invalide

**Solution** : Vérifier le format `demo_timestamp_random`

### Session expirée

**Problème** : Redirection ou erreur

**Solution** : Nouvelle session créée automatiquement

### Données non sauvegardées

**Problème** : Permissions fichiers

**Solution** :
```bash
chmod 755 database/demo-sessions/
```

---

## 🎯 Roadmap

### Améliorations futures

- [ ] Analytics détaillées par session
- [ ] Export des données de test
- [ ] Personnalisation des messages assistant
- [ ] Support multi-langues
- [ ] Intégration base de données temporaire

---

## 📞 Support

Pour toute question sur le système de démo :
- 📧 Email technique
- 💬 Documentation en ligne
- 🔧 Issues GitHub

---

**Créé avec ❤️ pour offrir la meilleure expérience de test possible**
