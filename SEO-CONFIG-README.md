# Guide de Configuration SEO - Le Marvelous

Ce guide explique comment modifier les paramètres SEO lorsque vous changez de domaine.

---

## Fichiers à modifier lors d'un changement de domaine

### 1. `template-v2/index.html`

#### Meta Tags (lignes 11-31)
Recherchez et remplacez `marvelous.mon-agenceweb.fr` par votre nouveau domaine :

```html
<!-- Canonical URL -->
<link rel="canonical" href="https://VOTRE-DOMAINE.com/">

<!-- Open Graph -->
<meta property="og:url" content="https://VOTRE-DOMAINE.com/">
<meta property="og:image" content="https://VOTRE-DOMAINE.com/images/hero.jpg">

<!-- Twitter Card -->
<meta name="twitter:url" content="https://VOTRE-DOMAINE.com/">
<meta name="twitter:image" content="https://VOTRE-DOMAINE.com/images/hero.jpg">
```

#### Schema.org / JSON-LD (lignes 54-118)
Modifiez l'URL dans le bloc `<script type="application/ld+json">` :

```json
{
    "@context": "https://schema.org",
    "@type": "Restaurant",
    "url": "https://VOTRE-DOMAINE.com",
    "image": "https://VOTRE-DOMAINE.com/images/hero.jpg",
    "menu": "https://VOTRE-DOMAINE.com/"
}
```

---

### 2. `template-v2/robots.txt`

Remplacez le domaine complet :

```txt
# Robots.txt for Le Marvelous
# https://VOTRE-DOMAINE.com

User-agent: *
Allow: /

Sitemap: https://VOTRE-DOMAINE.com/template-v2/sitemap.xml
```

**Note** : Si votre site n'est plus dans `/template-v2/` mais à la racine, adaptez les chemins.

---

### 3. `template-v2/sitemap.xml`

Remplacez toutes les URLs :

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>https://VOTRE-DOMAINE.com/</loc>
        <lastmod>2024-12-22</lastmod>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>https://VOTRE-DOMAINE.com/click-collect.html</loc>
        <lastmod>2024-12-22</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc>https://VOTRE-DOMAINE.com/fidelite.html</loc>
        <lastmod>2024-12-22</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
</urlset>
```

**Conseil** : Mettez à jour `<lastmod>` avec la date de modification.

---

### 4. `template-v2/manifest.json`

Pour les PWA (Progressive Web App) :

```json
{
    "name": "Le Marvelous - Commandez en ligne",
    "short_name": "Le Marvelous",
    "start_url": "/",
    "display": "standalone",
    "background_color": "#ffffff",
    "theme_color": "#2ec4b6"
}
```

**Note** : Adaptez `start_url` si le site n'est plus dans un sous-dossier.

---

## Checklist de changement de domaine

- [ ] Modifier `index.html` - canonical URL
- [ ] Modifier `index.html` - Open Graph URLs
- [ ] Modifier `index.html` - Twitter Card URLs
- [ ] Modifier `index.html` - Schema.org URLs
- [ ] Modifier `robots.txt` - domaine + sitemap
- [ ] Modifier `sitemap.xml` - toutes les URLs
- [ ] Modifier `manifest.json` - start_url si nécessaire
- [ ] Vérifier les favicons dans `/images/`
- [ ] Tester avec Google Search Console
- [ ] Soumettre le nouveau sitemap

---

## Configuration du serveur (o2switch)

### Redirection HTTP vers HTTPS

Créez/modifiez `.htaccess` à la racine :

```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### Redirection www vers non-www (ou inverse)

```apache
# www vers non-www
RewriteCond %{HTTP_HOST} ^www\.(.*)$ [NC]
RewriteRule ^(.*)$ https://%1/$1 [R=301,L]
```

### Redirection ancien domaine vers nouveau

```apache
RewriteCond %{HTTP_HOST} ^ancien-domaine\.com$ [NC]
RewriteRule ^(.*)$ https://nouveau-domaine.com/$1 [R=301,L]
```

---

## Outils SEO recommandes

1. **Google Search Console** - https://search.google.com/search-console
   - Soumettre votre sitemap
   - Vérifier l'indexation
   - Voir les erreurs de crawl

2. **Google PageSpeed Insights** - https://pagespeed.web.dev/
   - Tester les Core Web Vitals
   - Optimiser les performances

3. **Schema Markup Validator** - https://validator.schema.org/
   - Vérifier le JSON-LD

4. **Facebook Sharing Debugger** - https://developers.facebook.com/tools/debug/
   - Tester les Open Graph tags

---

## Informations actuelles

| Element | Valeur actuelle |
|---------|-----------------|
| Domaine | marvelous.mon-agenceweb.fr |
| Chemin | /template-v2/ |
| Theme color | #2ec4b6 |
| Langue | fr |

---

## Contact

Pour toute question technique, contactez le developpeur.
