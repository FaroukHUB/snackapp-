# 🔐 Guide SSH O2switch - Déploiement Instance Demo

## 📋 Informations de connexion

- **Serveur** : `guide` (o2switch)
- **Username** : `zajr1824`
- **Domaine** : `demo.mon-agenceweb.fr`
- **Base de données** : `zajr1824_demo`
- **User MySQL** : `zajr1824_demo`

---

## 🔌 Connexion SSH

### Depuis votre machine locale :

```bash
ssh zajr1824@ssh.o2switch.net
# OU
ssh zajr1824@guide.o2switch.net
```

Entrez votre mot de passe O2switch quand demandé.

---

## 📂 Structure des dossiers O2switch

Sur le serveur O2switch, la structure typique est :

```
/home/zajr1824/
├── public_html/                    # Racine web principale
│   ├── demo.mon-agenceweb.fr/      # Dossier du domaine demo
│   └── atelierpizza.mon-agenceweb.fr/  # Autre instance
├── logs/                           # Logs Apache/PHP
├── tmp/                            # Fichiers temporaires
└── backups/                        # Backups
```

### Commandes pour explorer :

```bash
# Voir où vous êtes
pwd

# Lister le contenu
ls -la

# Aller dans le dossier web principal
cd ~/public_html

# Voir les domaines configurés
ls -la

# Aller dans le dossier demo
cd demo.mon-agenceweb.fr
```

---

## 🗄️ Connexion MySQL sur O2switch

### Test de connexion MySQL :

```bash
mysql -u zajr1824_demo -p
# Entrez le mot de passe quand demandé
```

### Une fois connecté à MySQL :

```sql
-- Voir les bases disponibles
SHOW DATABASES;

-- Utiliser la base demo
USE zajr1824_demo;

-- Voir les tables existantes
SHOW TABLES;

-- Voir la structure d'une table
DESCRIBE products;

-- Quitter MySQL
EXIT;
```

---

## 📤 Upload des fichiers depuis local vers O2switch

### Option 1 : SCP (Secure Copy)

```bash
# Uploader un fichier
scp /local/path/file.php zajr1824@ssh.o2switch.net:~/public_html/demo.mon-agenceweb.fr/

# Uploader un dossier complet
scp -r /local/path/folder zajr1824@ssh.o2switch.net:~/public_html/demo.mon-agenceweb.fr/

# Exemple : Uploader le script de migration
scp database/migrate-json-to-mysql.php zajr1824@ssh.o2switch.net:~/public_html/demo.mon-agenceweb.fr/database/
```

### Option 2 : RSYNC (plus efficace)

```bash
# Synchroniser tout le projet (exclut .git, node_modules, etc.)
rsync -avz --exclude='.git' --exclude='node_modules' --exclude='temp.zip' \
  /home/user/snackapp-/ \
  zajr1824@ssh.o2switch.net:~/public_html/demo.mon-agenceweb.fr/

# Synchroniser uniquement le dossier database
rsync -avz database/ zajr1824@ssh.o2switch.net:~/public_html/demo.mon-agenceweb.fr/database/
```

### Option 3 : Git (recommandé)

```bash
# Sur le serveur O2switch, dans le dossier du site :
cd ~/public_html/demo.mon-agenceweb.fr

# Cloner ou pull depuis le repo
git pull origin claude/resume-snackup-context-g4jKx
```

---

## 🚀 Déploiement de l'instance Demo

### Étape 1 : Se connecter au serveur

```bash
ssh zajr1824@ssh.o2switch.net
```

### Étape 2 : Vérifier l'emplacement du site

```bash
cd ~/public_html/demo.mon-agenceweb.fr
ls -la
```

### Étape 3 : Vérifier la configuration backend

```bash
cat instances/demo/backend-config.php
```

Vérifier que le mot de passe MySQL est correct (pas `CHANGE_ME`).

### Étape 4 : Tester la connexion MySQL

```bash
php -r "
\$config = require 'instances/demo/backend-config.php';
try {
    \$pdo = new PDO(
        'mysql:host=' . \$config['database']['host'] . ';dbname=' . \$config['database']['dbname'],
        \$config['database']['user'],
        \$config['database']['password']
    );
    echo 'MySQL OK' . PHP_EOL;
} catch (PDOException \$e) {
    echo 'Erreur: ' . \$e->getMessage() . PHP_EOL;
}
"
```

### Étape 5 : Migrer les données JSON → MySQL

```bash
# Lancer la migration en mode dry-run d'abord
php database/migrate-json-to-mysql.php --dry-run --restaurant-id=99

# Si OK, lancer la vraie migration
php database/migrate-json-to-mysql.php --restaurant-id=99 --force
```

---

## 🔍 Vérification après migration

### Vérifier les données dans MySQL :

```bash
mysql -u zajr1824_demo -p zajr1824_demo
```

```sql
-- Compter les catégories
SELECT COUNT(*) FROM categories WHERE restaurant_id = 99;

-- Compter les produits
SELECT COUNT(*) FROM products WHERE restaurant_id = 99;

-- Voir les catégories
SELECT id, name, slug FROM categories WHERE restaurant_id = 99;

-- Voir quelques produits
SELECT id, name, price_solo, status FROM products WHERE restaurant_id = 99 LIMIT 10;
```

---

## 📝 Commandes SSH utiles

```bash
# Voir l'espace disque utilisé
df -h

# Voir la taille d'un dossier
du -sh demo.mon-agenceweb.fr/

# Voir les logs PHP
tail -f ~/logs/error_log

# Voir les processus en cours
ps aux | grep php

# Version PHP utilisée
php -v

# Test de syntaxe PHP
php -l fichier.php
```

---

## ⚠️ Sécurité

- ✅ Ne JAMAIS commit les mots de passe dans Git
- ✅ Utiliser `.gitignore` pour exclure les configs sensibles
- ✅ Changer le mot de passe MySQL si `CHANGE_ME`
- ✅ Configurer HTTPS via cPanel O2switch
- ✅ Activer les backups automatiques

---

## 🆘 En cas de problème

### Erreur de connexion MySQL
- Vérifier le mot de passe dans `backend-config.php`
- Vérifier que la base `zajr1824_demo` existe dans cPanel
- Vérifier les permissions de l'utilisateur MySQL

### Erreur 500
- Vérifier les logs : `tail ~/logs/error_log`
- Vérifier les permissions : `chmod 755 dossiers` et `chmod 644 fichiers`

### Site inaccessible
- Vérifier la configuration du domaine dans cPanel
- Vérifier le fichier `.htaccess`

---

**Fait avec ❤️ pour l'Atelier Pizza**
