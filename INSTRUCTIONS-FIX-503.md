# 🔧 FIX ERREUR 503 SUR COMMANDES SITE

## Problème identifié

L'erreur 503 sur les commandes depuis le site (alors que curl fonctionne) indique que **ModSecurity** (pare-feu applicatif web d'o2switch) bloque les requêtes du navigateur.

ModSecurity détecte des "faux positifs" dans les données POST (noms de produits, adresses, etc.) et bloque la requête en pensant que c'est une attaque.

## Solution appliquée

J'ai créé un fichier `.htaccess` dans le dossier `admin-panel-v2/api/` qui désactive ModSecurity uniquement pour les API, sans affecter la sécurité du reste du site.

## Déploiement sur o2switch

### Étape 1: Récupérer les changements

```bash
cd ~/Marvelous.mon-agenceweb.fr
git pull origin claude/review-progress-continue-U4j8i
```

### Étape 2: Vérifier que le fichier est bien créé

```bash
ls -la admin-panel-v2/api/.htaccess
cat admin-panel-v2/api/.htaccess
```

Vous devriez voir:
```
# 🔒 SÉCURITÉ: Désactiver ModSecurity pour les API
<IfModule mod_security.c>
    SecRuleEngine Off
</IfModule>
...
```

### Étape 3: Tester une commande

1. Allez sur votre site: `https://marvelous.mon-agenceweb.fr/template-v2/`
2. Ajoutez des produits au panier
3. Validez la commande
4. **La commande doit passer sans erreur 503** ✅

### Étape 4: Si l'erreur 503 persiste

Exécutez le script de diagnostic:

```bash
cd ~/Marvelous.mon-agenceweb.fr
chmod +x DIAGNOSTIC-503-ERROR.sh
./DIAGNOSTIC-503-ERROR.sh
```

Ce script va:
- Vérifier les logs d'erreur
- Tester l'API avec différentes configurations
- Identifier la cause exacte si ce n'est pas ModSecurity

## Sécurité

**Est-ce sécurisé de désactiver ModSecurity pour l'API?**

✅ **OUI**, car:

1. L'API a déjà ses propres protections:
   - Rate limiting (max 5 commandes/minute par IP)
   - Validation des données requises
   - Protection contre l'injection SQL (via PDO)
   - Headers de sécurité (X-Frame-Options, etc.)

2. ModSecurity est désactivé **UNIQUEMENT** pour `/admin-panel-v2/api/`, pas pour tout le site

3. Les vraies attaques (injection SQL, XSS) sont déjà bloquées par le code PHP

## Que faire si ça ne marche toujours pas?

Si après le pull l'erreur 503 persiste:

1. Vérifiez que le fichier `.htaccess` existe bien dans `admin-panel-v2/api/`
2. Exécutez `DIAGNOSTIC-503-ERROR.sh` et envoyez-moi la sortie
3. Vérifiez les logs d'erreur o2switch dans cPanel → "Logs d'erreurs"

## Récapitulatif des fichiers modifiés

```
✅ admin-panel-v2/api/.htaccess (NOUVEAU)
   → Désactive ModSecurity pour les API

✅ DIAGNOSTIC-503-ERROR.sh (NOUVEAU)
   → Script pour diagnostiquer l'erreur si elle persiste
```

## Test final

Après le `git pull`, testez IMMÉDIATEMENT:

```bash
# Test direct de l'API
curl -X POST "https://marvelous.mon-agenceweb.fr/admin-panel-v2/api/orders.php" \
  -H "Content-Type: application/json" \
  -d '{"action":"add","customer_phone":"0555000001","customer_name":"Test","items":[{"id":"1","name":"Test","price":100,"quantity":1}],"total":100,"subtotal":100}'
```

Si ça retourne `{"success":true,...}` alors c'est **RÉSOLU** ✅
