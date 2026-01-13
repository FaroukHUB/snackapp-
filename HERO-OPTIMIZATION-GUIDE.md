# 🚀 Optimisation Image Hero - Guide

## ✅ Changements Appliqués

### HTML Modifié (`template-v2/index.html`)

```html
<img src="../images/hero-800w.webp"
     srcset="../images/hero-400w.webp 400w,
             ../images/hero-800w.webp 800w,
             ../images/hero.webp 1536w"
     sizes="(max-width: 768px) 100vw, 1400px"
     alt="Le Marvelous Diner 50's Ouled Moussa"
     class="hero-image"
     id="heroImage"
     width="1536"
     height="992"
     fetchpriority="high"
     loading="eager">
```

### Attributs Ajoutés
- ✅ **srcset** : 3 versions d'image (400w, 800w, 1536w)
- ✅ **sizes** : Indique au navigateur quelle taille charger
- ✅ **width/height** : Dimensions intrinsèques (évite CLS)
- ✅ **fetchpriority="high"** : Priorité maximale pour LCP
- ✅ **loading="eager"** : Chargement immédiat (pas de lazy)

---

## 📦 Fichiers à Générer

### 1. **hero-400w.webp** (Mobile 1x)
- **Dimensions** : 400 x 258 px
- **Ratio** : 1.548:1 (préservé)
- **Qualité** : 85%
- **Taille estimée** : 12-15 Ko
- **Usage** : Mobile normal (viewport ≤768px)

### 2. **hero-800w.webp** (Mobile 2x + Desktop small)
- **Dimensions** : 800 x 516 px
- **Ratio** : 1.548:1 (préservé)
- **Qualité** : 85%
- **Taille estimée** : 35-45 Ko
- **Usage** : Mobile retina + petits desktops

### 3. **hero.webp** (Original - déjà existant)
- **Dimensions** : 1536 x 992 px
- **Ratio** : 1.548:1
- **Taille actuelle** : ~150-200 Ko
- **Usage** : Grands desktops + écrans retina

---

## 🛠️ Comment Générer les Images

### Option 1 : Script Automatique (Recommandé)

```bash
cd /chemin/vers/snackapp/scripts
./optimize-hero-image.sh
```

Le script détecte automatiquement l'outil disponible (cwebp, ffmpeg, ou ImageMagick) et génère les versions optimisées.

### Option 2 : Commandes Manuelles

#### Avec `cwebp` (meilleur qualité/taille pour WebP)
```bash
cd images
cwebp -resize 400 258 -q 85 hero.webp -o hero-400w.webp
cwebp -resize 800 516 -q 85 hero.webp -o hero-800w.webp
```

#### Avec `ffmpeg`
```bash
cd images
ffmpeg -i hero.webp -vf "scale=400:258" -quality 85 hero-400w.webp -y
ffmpeg -i hero.webp -vf "scale=800:516" -quality 85 hero-800w.webp -y
```

#### Avec `ImageMagick` (convert)
```bash
cd images
convert hero.webp -resize 400x258 -quality 85 hero-400w.webp
convert hero.webp -resize 800x516 -quality 85 hero-800w.webp
```

### Option 3 : Outil en Ligne

Si aucun outil n'est installé, utilise un outil en ligne :
1. Télécharge `hero.webp` depuis le serveur
2. Va sur https://squoosh.app ou https://imageresizer.com
3. Redimensionne en 400x258 et 800x516 (qualité 85%)
4. Sauvegarde au format WebP
5. Renomme en `hero-400w.webp` et `hero-800w.webp`
6. Upload vers `/images/`

---

## 📊 Impact Attendu

### Avant Optimisation
- **LCP** : ~3.0s (image 1536x992 = ~150-200 Ko)
- **Image téléchargée** : 1536x992 px
- **Image affichée** : 388x259 px
- **Gaspillage** : 75% de données inutiles

### Après Optimisation
- **LCP** : ~1.2-1.5s (économie de 50%)
- **Mobile** : 12-15 Ko au lieu de 150-200 Ko (93% plus léger!)
- **Desktop** : 35-45 Ko au lieu de 150-200 Ko (77% plus léger!)
- **Bonus** : fetchpriority="high" améliore encore le LCP

---

## ✅ Checklist

- [x] HTML modifié avec srcset + sizes
- [x] Attributs fetchpriority="high" et loading="eager" ajoutés
- [x] width/height intrinsèques ajoutés (évite CLS)
- [ ] **RESTE À FAIRE** : Générer hero-400w.webp et hero-800w.webp
- [ ] **RESTE À FAIRE** : Upload vers serveur production (/images/)
- [ ] **RESTE À FAIRE** : Tester sur mobile et desktop
- [ ] **RESTE À FAIRE** : Refaire un audit Lighthouse

---

## 🧪 Test

Après avoir uploadé les images :

1. **Hard refresh** : Ctrl + Shift + R
2. **Inspecter** : DevTools > Network > Images
3. **Vérifier** : Quelle version est chargée selon viewport
4. **Lighthouse** : Mesurer le nouveau LCP

### Vérifications
- ✅ Mobile (≤768px) charge `hero-400w.webp`
- ✅ Desktop charge `hero-800w.webp` ou `hero.webp`
- ✅ LCP vert (< 2.5s) dans Lighthouse
- ✅ Design identique (aucun changement visuel)

---

## 🎯 Résultat Attendu

**Score Lighthouse Performance** : +15 à +25 points grâce à :
- LCP réduit de 50%
- Pas de CLS (width/height définis)
- Chargement prioritaire (fetchpriority)
- Images adaptées au viewport (srcset/sizes)
