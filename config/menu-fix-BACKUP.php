<?php
// Protection anti-vidage menu.json
$menuPath = __DIR__ . '/menu.json';

// Avant CHAQUE sauvegarde, vérifier que les catégories ne sont PAS vides
function validateMenuBeforeSave($menuData) {
    if (empty($menuData['menu']['categories']) || count($menuData['menu']['categories']) === 0) {
        error_log('🚨 ALERTE: Tentative de sauvegarde menu.json avec catégories VIDES - BLOQUÉ');
        return false; // BLOQUER la sauvegarde
    }
    return true;
}
