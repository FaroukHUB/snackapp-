#!/bin/bash
# Script de vérification de branche
# À exécuter au début de chaque session

EXPECTED_BRANCH="claude/review-progress-continue-U4j8i"
CURRENT_BRANCH=$(git branch --show-current)

echo "========================================="
echo "VÉRIFICATION BRANCHE"
echo "========================================="
echo ""
echo "Branche attendue : $EXPECTED_BRANCH"
echo "Branche actuelle : $CURRENT_BRANCH"
echo ""

if [ "$CURRENT_BRANCH" != "$EXPECTED_BRANCH" ]; then
    echo "❌ ERREUR CRITIQUE : Vous n'êtes PAS sur la bonne branche !"
    echo ""
    echo "Action requise :"
    echo "  git checkout $EXPECTED_BRANCH"
    echo ""
    exit 1
else
    echo "✅ OK : Vous êtes sur la bonne branche"
    echo ""
    git log --oneline -5
    echo ""
    echo "Commits locaux non pushés :"
    git log origin/$EXPECTED_BRANCH..$EXPECTED_BRANCH --oneline 2>/dev/null || echo "(aucun remote trouvé)"
    echo ""
fi
