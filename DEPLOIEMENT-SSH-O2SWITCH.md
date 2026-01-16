# 🚀 Déploiement SSH sur o2switch - Atelier Pizza

## 📍 Informations de Connexion

**Domaine :** atelierpizza.mon-agenceweb.fr
**Hébergeur :** o2switch
**Compte :** zajr1824

### 🔐 Connexion SSH

```bash
# Connexion SSH à o2switch
ssh zajr1824@zajr1824.o2switch.net

# Ou avec le domaine
ssh zajr1824@atelierpizza.mon-agenceweb.fr
```

**Note :** Utilise le mot de passe de ton compte o2switch.

---

## 📦 Déploiement sur o2switch

### Étape 1 : Se Connecter

```bash
ssh zajr1824@zajr1824.o2switch.net
```

### Étape 2 : Aller dans le Répertoire du Site

```bash
# o2switch met généralement les sites dans ~/www ou ~/public_html
cd ~/www
# ou
cd ~/public_html

# Ou si tu as un sous-dossier spécifique
cd ~/www/atelierpizza
```

### Étape 3 : Pull depuis Git

```bash
# Cloner le repo si pas encore fait
git clone https://github.com/FaroukHUB/snackapp-.git atelierpizza
cd atelierpizza

# Ou pull si déjà cloné
cd atelierpizza
git pull origin main
```

### Étape 4 : Checkout la Branch avec l'Architecture Scalable

```bash
# Pull la branch avec l'architecture scalable
git fetch origin
git checkout claude/review-progress-continue-U4j8i

# Ou si tu veux merger dans main d'abord
git checkout main
git merge claude/review-progress-continue-U4j8i
```

### Étape 5 : Vérifier les Permissions

```bash
# Donner les bonnes permissions
chmod 755 scripts/*.sh
chmod 755 scripts/*.php

# Permissions pour les dossiers sensibles
chmod 600 instances/atelier-pizza/backend-config.php
```

### Étape 6 : Tester que Ça Fonctionne

```bash
# Test InstanceManager
php test-instance-manager.php

# Diagnostic Atelier Pizza
php scripts/diagnostic.php atelier-pizza
```

---

## 🔧 Configuration Base de Données o2switch

### Accéder à phpMyAdmin

1. Va sur : https://zajr1824.o2switch.net:2083 (cPanel)
2. Login : zajr1824
3. Cherche "phpMyAdmin"
4. Sélectionne la base : `zajr1824_atelierpizza`

### Vérifier la Config

Le fichier de config est déjà bon :
```php
// instances/atelier-pizza/backend-config.php
'database' => [
    'host' => 'localhost',
    'name' => 'zajr1824_atelierpizza',
    'user' => 'zajr1824_atelierpizza',
    'password' => 'Mariagor6!',
    'charset' => 'utf8mb4'
]
```

---

## 🌐 URLs à Tester

Après déploiement, teste ces URLs :

### API Restaurant
```bash
curl https://atelierpizza.mon-agenceweb.fr/config/restaurant.php
```

**Attendu :**
```json
{
  "name": "L'Atelier Pizza",
  "slug": "atelier-pizza-roubaix",
  "_jsConfig": {
    "currency": "EUR",
    "restaurantId": 3,
    "instanceId": "atelier-pizza-roubaix",
    "instanceName": "atelier-pizza"
  }
}
```

### API Menu
```bash
curl https://atelierpizza.mon-agenceweb.fr/config/menu.php
```

**Attendu :**
```json
{
  "menu": {
    "categories": [...]
  },
  "_meta": {
    "currency": "EUR",
    "restaurantId": 3,
    "instanceId": "atelier-pizza-roubaix",
    "instanceName": "atelier-pizza"
  }
}
```

---

## 🐛 Troubleshooting

### Erreur "Instance configuration not found"

```bash
# Vérifier que instances.json existe
ls -la config/instances.json

# Vérifier le contenu
cat config/instances.json
```

### Erreur de connexion base de données

```bash
# Tester la connexion MySQL
mysql -u zajr1824_atelierpizza -p zajr1824_atelierpizza

# Vérifier que la base existe
mysql -u zajr1824_atelierpizza -p -e "SHOW DATABASES;"
```

### Erreur 500 sur les APIs

```bash
# Voir les logs d'erreur
tail -f ~/logs/error_log

# Ou
tail -f ~/www/atelierpizza/error_log
```

### Permissions

```bash
# Si erreur de permissions
chmod -R 755 ~/www/atelierpizza
chmod 600 ~/www/atelierpizza/instances/*/backend-config.php
```

---

## 🔄 Commandes Rapides de Déploiement

### Déploiement Complet en Une Fois

```bash
# Se connecter
ssh zajr1824@zajr1824.o2switch.net

# Aller dans le dossier
cd ~/www/atelierpizza

# Pull la dernière version
git fetch origin
git checkout claude/review-progress-continue-U4j8i
git pull

# Donner les permissions
chmod 755 scripts/*.sh scripts/*.php

# Tester
php scripts/diagnostic.php atelier-pizza

# Vérifier les APIs
curl https://atelierpizza.mon-agenceweb.fr/config/restaurant.php | jq
```

### Script de Déploiement Automatique

Créer un fichier `deploy-o2switch.sh` :

```bash
#!/bin/bash
# Déploiement automatique sur o2switch

echo "🚀 Déploiement Atelier Pizza sur o2switch"
echo ""

# Pull dernière version
echo "📥 Pull depuis Git..."
git fetch origin
git checkout claude/review-progress-continue-U4j8i
git pull

# Permissions
echo "🔧 Configuration des permissions..."
chmod 755 scripts/*.sh scripts/*.php
chmod 600 instances/atelier-pizza/backend-config.php

# Test
echo "🧪 Test de l'architecture..."
php scripts/diagnostic.php atelier-pizza

echo ""
echo "✅ Déploiement terminé !"
echo ""
echo "🌐 Teste les URLs :"
echo "  - https://atelierpizza.mon-agenceweb.fr/config/restaurant.php"
echo "  - https://atelierpizza.mon-agenceweb.fr/config/menu.php"
```

Puis exécuter :
```bash
chmod +x deploy-o2switch.sh
./deploy-o2switch.sh
```

---

## 📋 Checklist de Déploiement

- [ ] Connexion SSH fonctionne
- [ ] Code git pull/checkout fait
- [ ] Permissions correctes (755 pour scripts, 600 pour configs)
- [ ] Test InstanceManager passe
- [ ] Diagnostic atelier-pizza passe
- [ ] API restaurant.php fonctionne
- [ ] API menu.php fonctionne
- [ ] Base de données connectée
- [ ] Frontend affiche correctement

---

## 🆘 Support o2switch

Si problème technique avec l'hébergement :

- **Support o2switch :** https://www.o2switch.fr/support/
- **cPanel :** https://zajr1824.o2switch.net:2083
- **Webmail :** https://webmail.o2switch.net

---

## ✅ Vérification Finale

Après déploiement, vérifie que :

```bash
# 1. Architecture scalable activée
curl https://atelierpizza.mon-agenceweb.fr/config/restaurant.php | grep -o "instanceName"

# 2. Devise correcte (EUR)
curl https://atelierpizza.mon-agenceweb.fr/config/restaurant.php | grep -o "EUR"

# 3. Restaurant ID correct (3)
curl https://atelierpizza.mon-agenceweb.fr/config/restaurant.php | grep -o '"restaurantId":3'
```

Si ces 3 tests passent, l'architecture scalable est bien déployée ! ✅

---

**🎉 Une fois déployé, l'Atelier Pizza tournera avec l'architecture 100% scalable !**
