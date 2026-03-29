# 🔒 Configuration Sécurisée des Instances

## ⚠️ PROBLÈME ACTUEL

Les fichiers `backend-config.php` contiennent des mots de passe et sont actuellement **trackés dans Git**.

C'est une **faille de sécurité critique** car :
- Les mots de passe sont visibles dans l'historique Git
- Tout accès au dépôt donne accès aux credentials
- Les mots de passe peuvent être exposés publiquement

## 🎯 SOLUTION IMMÉDIATE (Pour le serveur de production)

Sur votre serveur, modifiez directement les fichiers `backend-config.php` avec les vrais mots de passe :

```bash
# Éditer chaque fichier
nano instances/demo/backend-config.php
nano instances/atelier-pizza/backend-config.php
nano instances/marvelous/backend-config.php

# Remplacer 'CHANGE_ME' par le vrai mot de passe
# Ces changements locaux ne seront PAS commitées si vous ne faites pas de git add
```

## 🚀 SOLUTION À LONG TERME (Recommandé)

### Option 1: Fichiers .local.php (Recommandé)

1. Créer des fichiers de configuration locaux non trackés :
   ```bash
   cp instances/demo/backend-config.php instances/demo/backend-config.local.php
   # Éditer le fichier .local.php avec les vrais credentials
   ```

2. Modifier `InstanceManager.php` pour charger `.local.php` en priorité :
   ```php
   $localConfig = "{$instancePath}/backend-config.local.php";
   $defaultConfig = "{$instancePath}/backend-config.php";

   if (file_exists($localConfig)) {
       return require $localConfig;
   }
   return require $defaultConfig;
   ```

3. Les fichiers `.local.php` sont déjà dans `.gitignore` ✅

### Option 2: Variables d'environnement

1. Créer un fichier `.env` à la racine (déjà dans `.gitignore`) :
   ```env
   DB_PASSWORD_DEMO=VotreMotDePasse
   DB_PASSWORD_ATELIER=AutreMotDePasse
   ```

2. Modifier `backend-config.php` pour utiliser les variables d'environnement :
   ```php
   'password' => getenv('DB_PASSWORD_DEMO') ?: 'CHANGE_ME',
   ```

### Option 3: Retirer les fichiers de Git (Le plus sûr mais breaking)

⚠️ **ATTENTION** : Cette option casse les déploiements existants !

```bash
# 1. Créer des fichiers .example
cp instances/demo/backend-config.php instances/demo/backend-config.example.php
cp instances/atelier-pizza/backend-config.php instances/atelier-pizza/backend-config.example.php

# 2. Retirer les vrais fichiers de Git
git rm --cached instances/*/backend-config.php

# 3. Ajouter au .gitignore
echo "instances/*/backend-config.php" >> .gitignore

# 4. Commiter
git commit -m "Security: Remove credentials from Git"

# 5. Sur chaque serveur, copier .example vers le fichier réel
cp backend-config.example.php backend-config.php
nano backend-config.php  # Configurer les vrais credentials
```

## 📋 CHECKLIST DE SÉCURITÉ

- [ ] Les mots de passe dans Git sont des placeholders (CHANGE_ME)
- [ ] Les vrais mots de passe sont configurés uniquement sur le serveur
- [ ] Le fichier .gitignore bloque les fichiers sensibles
- [ ] Les mots de passe sont forts et uniques par instance
- [ ] L'historique Git ne contient pas de mots de passe (sinon: git filter-branch)

## 🔑 ROTATION DES MOTS DE PASSE

Si des mots de passe ont été commitées dans Git :

1. **Changer IMMÉDIATEMENT tous les mots de passe** sur les bases de données
2. Nettoyer l'historique Git (complexe, voir `git filter-branch` ou BFG Repo-Cleaner)
3. Forcer le push de l'historique nettoyé
4. Informer tous les développeurs de re-cloner le dépôt

## 📚 RESSOURCES

- [OWASP: Password Storage Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html)
- [GitHub: Removing sensitive data](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/removing-sensitive-data-from-a-repository)
