<?php
/**
 * Ajouter les icônes manquantes pour TOUTES les catégories
 */

$root = '/home/zajr1824/Marvelous.mon-agenceweb.fr';
$menuPath = $root . '/config/menu.json';

$menu = json_decode(file_get_contents($menuPath), true);

if (!isset($menu['categoryIcons'])) {
    $menu['categoryIcons'] = [];
}

echo "🔧 AJOUT DES ICÔNES MANQUANTES\n";
echo "================================\n\n";

$added = 0;
$existing = 0;

foreach (($menu['menu']['categories'] ?? []) as $cat) {
    $id = $cat['id'] ?? '';
    $name = $cat['name'] ?? '';

    if (!$id) continue;

    if (!isset($menu['categoryIcons'][$id])) {
        // Choisir une icône par défaut selon le nom
        $icon = 'fa-utensils'; // Par défaut

        // Icônes spécifiques selon le nom
        if (stripos($name, 'burger') !== false || stripos($id, 'burger') !== false) {
            $icon = 'fa-hamburger';
        } elseif (stripos($name, 'pizza') !== false || stripos($id, 'pizza') !== false || stripos($id, 'izza') !== false) {
            $icon = 'fa-pizza-slice';
        } elseif (stripos($name, 'pasta') !== false || stripos($id, 'pasta') !== false || stripos($id, 'asta') !== false) {
            $icon = 'fa-bowl-rice';
        } elseif (stripos($name, 'taco') !== false || stripos($id, 'taco') !== false) {
            $icon = 'fa-pepper-hot';
        } elseif (stripos($name, 'crépe') !== false || stripos($id, 'crepe') !== false) {
            $icon = 'fa-stroopwafel';
        } elseif (stripos($name, 'gaufre') !== false || stripos($id, 'gaufre') !== false) {
            $icon = 'fa-cookie';
        } elseif (stripos($name, 'boisson') !== false || stripos($id, 'boisson') !== false) {
            $icon = 'fa-mug-hot';
        } elseif (stripos($name, 'soda') !== false || stripos($id, 'soda') !== false) {
            $icon = 'fa-wine-bottle';
        } elseif (stripos($name, 'jus') !== false || stripos($id, 'jus') !== false) {
            $icon = 'fa-glass-water';
        } elseif (stripos($name, 'enfant') !== false || stripos($id, 'enfant') !== false) {
            $icon = 'fa-child';
        }

        $menu['categoryIcons'][$id] = $icon;
        echo "➕ $id ($name) → $icon\n";
        $added++;
    } else {
        $existing++;
    }
}

// Sauvegarder
file_put_contents($menuPath, json_encode($menu, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

echo "\n================================\n";
echo "✅ Terminé!\n";
echo "   Icônes ajoutées: $added\n";
echo "   Déjà présentes: $existing\n";
echo "\n";
echo "🔄 Rechargez votre site (Ctrl+F5) pour voir les icônes!\n";
