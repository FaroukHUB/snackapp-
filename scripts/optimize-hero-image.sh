#!/bin/bash
# Script pour générer les versions optimisées de l'image hero
# À exécuter sur le serveur de production où les outils d'image sont installés

echo "🖼️  Optimisation de l'image hero..."

# Vérifier si cwebp ou ffmpeg est disponible
if command -v cwebp &> /dev/null; then
    echo "✅ cwebp trouvé"
    TOOL="cwebp"
elif command -v ffmpeg &> /dev/null; then
    echo "✅ ffmpeg trouvé"
    TOOL="ffmpeg"
elif command -v convert &> /dev/null; then
    echo "✅ ImageMagick trouvé"
    TOOL="convert"
else
    echo "❌ Aucun outil d'image trouvé. Installez cwebp, ffmpeg ou imagemagick."
    exit 1
fi

# Chemin vers l'image source
SOURCE="../images/hero.webp"
if [ ! -f "$SOURCE" ]; then
    echo "❌ Fichier source $SOURCE non trouvé"
    exit 1
fi

echo "📁 Source: $SOURCE"

# Générer version 400px
echo "🔧 Génération hero-400w.webp (400x258)..."
if [ "$TOOL" = "cwebp" ]; then
    cwebp -resize 400 258 -q 85 "$SOURCE" -o "../images/hero-400w.webp"
elif [ "$TOOL" = "ffmpeg" ]; then
    ffmpeg -i "$SOURCE" -vf "scale=400:258" -quality 85 "../images/hero-400w.webp" -y
else
    convert "$SOURCE" -resize 400x258 -quality 85 "../images/hero-400w.webp"
fi

# Générer version 800px
echo "🔧 Génération hero-800w.webp (800x516)..."
if [ "$TOOL" = "cwebp" ]; then
    cwebp -resize 800 516 -q 85 "$SOURCE" -o "../images/hero-800w.webp"
elif [ "$TOOL" = "ffmpeg" ]; then
    ffmpeg -i "$SOURCE" -vf "scale=800:516" -quality 85 "../images/hero-800w.webp" -y
else
    convert "$SOURCE" -resize 800x516 -quality 85 "../images/hero-800w.webp"
fi

# Vérifier les tailles
echo ""
echo "✅ Fichiers générés:"
ls -lh ../images/hero*.webp | awk '{print $9, "-", $5}'

echo ""
echo "📊 Comparaison des tailles:"
ORIGINAL_SIZE=$(stat -f%z "$SOURCE" 2>/dev/null || stat -c%s "$SOURCE")
SIZE_400=$(stat -f%z "../images/hero-400w.webp" 2>/dev/null || stat -c%s "../images/hero-400w.webp")
SIZE_800=$(stat -f%z "../images/hero-800w.webp" 2>/dev/null || stat -c%s "../images/hero-800w.webp")

echo "Original (1536x992): $(numfmt --to=iec-i --suffix=B $ORIGINAL_SIZE 2>/dev/null || echo $ORIGINAL_SIZE bytes)"
echo "400w (400x258):      $(numfmt --to=iec-i --suffix=B $SIZE_400 2>/dev/null || echo $SIZE_400 bytes)"
echo "800w (800x516):      $(numfmt --to=iec-i --suffix=B $SIZE_800 2>/dev/null || echo $SIZE_800 bytes)"

echo ""
echo "✅ Optimisation terminée!"
echo "📤 N'oublie pas de push ces fichiers vers le serveur de production."
