# Le Marvelous - Deploiement o2switch

## Etat actuel (21 Dec 2024)

### Probleme principal
Le site sur `marvelous.mon-agenceweb.fr` donne des erreurs 403 sur les fichiers `.json`.
Le serveur o2switch **bloque l'acces direct aux fichiers .json**.

### Solution implementee (mais pas encore deployee)
1. Cree des wrappers PHP : `config/restaurant.php` et `config/menu.php`
2. Modifie `template-v2/js/config.js` pour charger `.php` au lieu de `.json`

---

## Fichiers importants

### ZIP a deployer
`deployments/le-marvelous-deploy-v4.zip` (sur GitHub)

### Identifiants o2switch
- **Serveur**: o2switch cPanel
- **Dossier web**: `Marvelous.mon-agenceweb.fr` (avec M majuscule!)
- **URL site**: https://marvelous.mon-agenceweb.fr/template-v2/
- **URL admin**: https://marvelous.mon-agenceweb.fr/admin-panel-v2/

### Base de donnees MySQL
- **Host**: localhost
- **Database**: zajr1824_marvelous
- **User**: zajr1824_marvelous
- **Password**: Mariagor6!
- **Restaurant ID**: 2 (pas 1!)

### Identifiants admin panel
- **Username**: admin
- **Password**: password

---

## Corrections faites dans ce ZIP

1. **SNACK_RESTAURANT_ID = 2** (au lieu de 1 pour Fabrik)
   - Fichier: `admin-panel-v2/bootstrap.php` ligne 56

2. **Mot de passe MySQL** ajoute
   - Fichier: `database/config.php` ligne 15

3. **Devise DZD** (Dinar Algerien) au lieu de EUR
   - Tous les fichiers admin utilisent "DA" au lieu de "€"

4. **Fix foreign key supplements**
   - `database/repositories/OrderRepository.php` : supplement_id = null

5. **Fix leaderboard fidelite**
   - `database/repositories/LoyaltyRepository.php` : filtre par restaurant_id

6. **Wrappers PHP pour JSON** (serveur bloque .json)
   - `config/restaurant.php` -> lit restaurant.json
   - `config/menu.php` -> lit menu.json
   - `template-v2/js/config.js` -> charge .php au lieu de .json

---

## Instructions de deploiement

1. Telecharger `le-marvelous-deploy-v4.zip` depuis GitHub
2. Sur cPanel o2switch, aller dans Gestionnaire de fichiers
3. Ouvrir `Marvelous.mon-agenceweb.fr` (M majuscule!)
4. Supprimer tout le contenu existant
5. Uploader le ZIP
6. Extraire le ZIP
7. Verifier les permissions: 755 pour dossiers, 644 pour fichiers

```bash
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
```

---

## Tests a faire apres deploiement

1. **Site public**: https://marvelous.mon-agenceweb.fr/template-v2/
   - Doit afficher le menu avec les prix en DA

2. **Admin panel**: https://marvelous.mon-agenceweb.fr/admin-panel-v2/
   - Login: admin / password
   - Verifier que les commandes arrivent avec le bon total
   - Verifier que l'onglet Fidelite montre les clients Le Marvelous (pas Fabrik)

---

## Problemes connus non resolus

- Les onglets Stats et Archive dans l'admin peuvent avoir des problemes de mot de passe
- Images manquantes (hero.jpg, favicon.png, logo)

---

## Contact

Repo GitHub: FaroukHUB/snackapp-
Branche: claude/fix-loyalty-handlers-bug-2SAxt
