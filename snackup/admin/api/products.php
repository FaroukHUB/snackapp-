<?php
/**
 * SnackApp v1 - Products API
 * Gestion des catégories, produits et suppléments
 * Support MySQL avec fallback JSON
 */

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

/* =========================
   UTILS
   ========================= */

function readInput(): array {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (stripos($contentType, 'multipart/form-data') !== false) {
        return is_array($_POST) ? $_POST : [];
    }

    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

/**
 * Retourne le chemin du fichier menu.json spécifique à l'instance
 *
 * @return string Chemin vers menu.<instanceId>.json
 */
function getMenuJsonPath(): string {
    $instanceId = InstanceManager::getInstanceId();
    return SNACK_ROOT . "/config/menu.$instanceId.json";
}

/**
 * Normalise un prix en remplaçant la virgule par un point avant conversion
 * Gère les prix envoyés avec virgule décimale (ex: "0,09" → 0.09)
 *
 * @param mixed $value Valeur à normaliser (string, int, float)
 * @return float Prix normalisé
 */
function normalizePrice($value): float {
    if (is_string($value)) {
        // Remplacer virgule par point pour format français → anglais
        $value = str_replace(',', '.', $value);
    }
    return (float)$value;
}

/**
 * ⚡ OPTIMISATION: Convertir et optimiser une image en WebP
 *
 * @param string $sourcePath Chemin du fichier source (JPG/PNG/WebP)
 * @param int $quality Qualité WebP (0-100, défaut 85)
 * @param int $maxWidth Largeur max en pixels (défaut 800)
 * @return string Chemin du fichier WebP temporaire créé
 * @throws Exception Si la conversion échoue
 */
function convertToOptimizedWebP(string $sourcePath, int $quality = 85, int $maxWidth = 800): string {
    // Charger l'image source selon son type
    $imageInfo = getimagesize($sourcePath);
    if ($imageInfo === false) {
        throw new Exception('Impossible de lire l\'image source');
    }

    $sourceWidth = $imageInfo[0];
    $sourceHeight = $imageInfo[1];
    $mime = $imageInfo['mime'];

    // Créer la ressource image selon le type
    switch ($mime) {
        case 'image/jpeg':
            $sourceImage = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $sourceImage = imagecreatefrompng($sourcePath);
            break;
        case 'image/webp':
            $sourceImage = imagecreatefromwebp($sourcePath);
            break;
        default:
            throw new Exception('Type MIME non supporté pour conversion: ' . $mime);
    }

    if ($sourceImage === false) {
        throw new Exception('Échec création ressource image');
    }

    // Redimensionner si nécessaire (préserve le ratio)
    if ($sourceWidth > $maxWidth) {
        $ratio = $maxWidth / $sourceWidth;
        $newWidth = $maxWidth;
        $newHeight = (int)($sourceHeight * $ratio);

        // Utiliser imagecopyresampled (plus compatible que imagescale)
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

        // Préserver la transparence pour PNG/WebP
        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
            $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
            imagefilledrectangle($resizedImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        $success = imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);
        imagedestroy($sourceImage);

        if (!$success) {
            imagedestroy($resizedImage);
            throw new Exception('Échec redimensionnement image');
        }

        $sourceImage = $resizedImage;
    }

    // Créer fichier WebP temporaire
    $tempWebP = tempnam(sys_get_temp_dir(), 'webp_') . '.webp';

    // Convertir en WebP avec compression
    $success = imagewebp($sourceImage, $tempWebP, $quality);
    imagedestroy($sourceImage);

    if (!$success || !file_exists($tempWebP)) {
        throw new Exception('Échec création fichier WebP');
    }

    return $tempWebP;
}

function handleImageUpload(string $baseId): ?string {
    $fileKey = null;
    if (!empty($_FILES['imageFile'])) $fileKey = 'imageFile';
    if (!empty($_FILES['image'])) $fileKey = 'image';

    if (!$fileKey) return null;

    $file = $_FILES[$fileKey];

    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        jsonError('Upload invalide');
    }

    // ✅ SÉCURITÉ: Limite de taille (5MB max)
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        jsonError('Image trop volumineuse (maximum 5MB)');
    }

    // ✅ SÉCURITÉ: Validation MIME type stricte
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = mime_content_type($file['tmp_name']) ?: '';
    if (!isset($allowed[$mime])) {
        jsonError('Format image non supporté (jpg/png/webp uniquement)');
    }

    // 🔒 SÉCURITÉ: Vérification magic bytes (signature du fichier)
    $handle = fopen($file['tmp_name'], 'rb');
    $header = fread($handle, 12);
    fclose($handle);

    $isValid = false;
    // JPEG: FF D8 FF
    if (substr($header, 0, 3) === "\xFF\xD8\xFF") $isValid = true;
    // PNG: 89 50 4E 47
    if (substr($header, 0, 4) === "\x89PNG") $isValid = true;
    // WEBP: RIFF...WEBP
    if (substr($header, 0, 4) === "RIFF" && substr($header, 8, 4) === "WEBP") $isValid = true;

    if (!$isValid) {
        jsonError('Fichier image invalide (vérification magic bytes échouée)');
    }

    // ✅ SÉCURITÉ: Permissions sécurisées
    $uploadsDir = SNACK_ROOT . '/images/uploads';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }

    // ⚡ OPTIMISATION: Convertir en WebP optimisé avant sauvegarde
    $webpTempFile = null;
    try {
        $webpTempFile = convertToOptimizedWebP($file['tmp_name'], 85, 800);

        // ✅ SÉCURITÉ: Nom de fichier sécurisé - toujours .webp maintenant
        $filename = $baseId . '-' . bin2hex(random_bytes(4)) . '.webp';

        // Vérifier qu'il n'y a pas d'extensions dangereuses cachées
        if (preg_match('/\.(php|phtml|php3|php4|php5|phps|phar|htaccess|exe|sh|bat|cmd)/i', $filename)) {
            jsonError('Extension de fichier non autorisée détectée');
        }

        $dest = $uploadsDir . '/' . $filename;

        if (!rename($webpTempFile, $dest)) {
            jsonError('Échec sauvegarde image WebP');
        }

        // ✅ SÉCURITÉ: Permissions strictes sur le fichier uploadé
        chmod($dest, 0644);

        return 'images/uploads/' . $filename;

    } catch (Exception $e) {
        // Nettoyer le fichier temporaire en cas d'erreur
        if ($webpTempFile && file_exists($webpTempFile)) {
            @unlink($webpTempFile);
        }
        jsonError('Échec conversion WebP: ' . $e->getMessage());
    }
}

/* =========================
   HELPER: Upload image formule
   ========================= */
function handleFormuleImageUpload(string $baseId): ?string {
    $fileKey = null;
    if (!empty($_FILES['image'])) $fileKey = 'image';
    if (!empty($_FILES['imageFile'])) $fileKey = 'imageFile';

    if (!$fileKey) return null;

    $file = $_FILES[$fileKey];

    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return null;
    }

    // ✅ SÉCURITÉ: Limite de taille (5MB max)
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        jsonError('Image trop volumineuse (maximum 5MB)');
    }

    // ✅ SÉCURITÉ: Validation MIME type stricte
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = mime_content_type($file['tmp_name']) ?: '';
    if (!isset($allowed[$mime])) {
        jsonError('Format image non supporté (jpg/png/webp uniquement)');
    }

    // 🔒 SÉCURITÉ: Vérification magic bytes (signature du fichier)
    $handle = fopen($file['tmp_name'], 'rb');
    $header = fread($handle, 12);
    fclose($handle);

    $isValid = false;
    // JPEG: FF D8 FF
    if (substr($header, 0, 3) === "\xFF\xD8\xFF") $isValid = true;
    // PNG: 89 50 4E 47
    if (substr($header, 0, 4) === "\x89PNG") $isValid = true;
    // WEBP: RIFF...WEBP
    if (substr($header, 0, 4) === "RIFF" && substr($header, 8, 4) === "WEBP") $isValid = true;

    if (!$isValid) {
        jsonError('Fichier image invalide (vérification magic bytes échouée)');
    }

    // ✅ SÉCURITÉ: Permissions sécurisées
    $uploadsDir = SNACK_ROOT . '/images/formules';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }

    // ⚡ OPTIMISATION: Convertir en WebP optimisé avant sauvegarde
    $webpTempFile = null;
    try {
        $webpTempFile = convertToOptimizedWebP($file['tmp_name'], 85, 800);

        // ✅ SÉCURITÉ: Nom de fichier sécurisé - toujours .webp maintenant
        $filename = $baseId . '-' . bin2hex(random_bytes(4)) . '.webp';

        // Vérifier qu'il n'y a pas d'extensions dangereuses cachées
        if (preg_match('/\.(php|phtml|php3|php4|php5|phps|phar|htaccess|exe|sh|bat|cmd)/i', $filename)) {
            jsonError('Extension de fichier non autorisée détectée');
        }

        $dest = $uploadsDir . '/' . $filename;

        if (!rename($webpTempFile, $dest)) {
            jsonError('Échec sauvegarde image WebP formule');
        }

        // ✅ SÉCURITÉ: Permissions strictes sur le fichier uploadé
        chmod($dest, 0644);

        return 'images/formules/' . $filename;

    } catch (Exception $e) {
        // Nettoyer le fichier temporaire en cas d'erreur
        if ($webpTempFile && file_exists($webpTempFile)) {
            @unlink($webpTempFile);
        }
        jsonError('Échec conversion WebP formule: ' . $e->getMessage());
    }
}

/* =========================
   HELPER: Upload icon image (badge catégorie)
   ========================= */
function handleIconImageUpload(string $baseId): ?string {
    if (empty($_FILES['icon_image'])) return null;

    $file = $_FILES['icon_image'];

    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return null;
    }

    // Erreur upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    // ✅ SÉCURITÉ: Limite de taille (2MB max pour icônes)
    $maxSize = 2 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        jsonError('Image icône trop volumineuse (maximum 2MB)');
    }

    // ✅ SÉCURITÉ: Validation MIME type stricte
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = mime_content_type($file['tmp_name']) ?: '';
    if (!isset($allowed[$mime])) {
        jsonError('Format image non supporté (jpg/png/webp uniquement)');
    }

    // 🔒 SÉCURITÉ: Vérification magic bytes
    $handle = fopen($file['tmp_name'], 'rb');
    $header = fread($handle, 12);
    fclose($handle);

    $isValid = false;
    if (substr($header, 0, 3) === "\xFF\xD8\xFF") $isValid = true; // JPEG
    if (substr($header, 0, 4) === "\x89PNG") $isValid = true; // PNG
    if (substr($header, 0, 4) === "RIFF" && substr($header, 8, 4) === "WEBP") $isValid = true; // WEBP

    if (!$isValid) {
        jsonError('Fichier image invalide');
    }

    // ✅ Créer dossier icons
    $uploadsDir = SNACK_ROOT . '/images/icons';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }

    // ⚡ Convertir en WebP optimisé (petite taille pour icônes)
    $webpTempFile = null;
    try {
        $webpTempFile = convertToOptimizedWebP($file['tmp_name'], 90, 200); // 200px max pour icônes

        $filename = 'cat-' . $baseId . '-' . bin2hex(random_bytes(4)) . '.webp';
        $dest = $uploadsDir . '/' . $filename;

        if (!rename($webpTempFile, $dest)) {
            jsonError('Échec sauvegarde image icône');
        }

        chmod($dest, 0644);
        return 'images/icons/' . $filename;

    } catch (Exception $e) {
        if ($webpTempFile && file_exists($webpTempFile)) {
            @unlink($webpTempFile);
        }
        jsonError('Échec conversion image icône: ' . $e->getMessage());
    }
}

/* =========================
   HELPER: Sync formules to menu.json
   ========================= */
function syncFormulesToMenu(array $runtime): void {
    error_log('[syncFormulesToMenu] ========== START ==========');

    try {
        $menuPath = getMenuJsonPath();
        error_log('[syncFormulesToMenu] menuPath: ' . $menuPath);

        if (!file_exists($menuPath)) {
            error_log('[syncFormulesToMenu] ❌ menu.json introuvable');
            return;
        }

        $menuContent = file_get_contents($menuPath);
        if ($menuContent === false) {
            error_log('[syncFormulesToMenu] ❌ Échec lecture menu.json');
            throw new Exception('Échec lecture menu.json');
        }

        $menuData = json_decode($menuContent, true);
        if (!$menuData) {
            error_log('[syncFormulesToMenu] ❌ Échec décodage JSON');
            return;
        }

        $formules = $menuData['formules'] ?? [];
        error_log('[syncFormulesToMenu] Formules AVANT: ' . count($formules));

        // Appliquer les patches
        if (!empty($runtime['formules'])) {
            error_log('[syncFormulesToMenu] Application de ' . count($runtime['formules']) . ' patches');
            foreach ($runtime['formules'] as $id => $patch) {
                foreach ($formules as &$f) {
                    if ($f['id'] === $id) {
                        $f = array_merge($f, $patch);
                        error_log('[syncFormulesToMenu] Patch appliqué à ' . $id);
                        break;
                    }
                }
            }
        }

        // Ajouter les formules custom (éviter les doublons)
        if (!empty($runtime['customFormules'])) {
            error_log('[syncFormulesToMenu] Ajout de ' . count($runtime['customFormules']) . ' formules custom');
            // Créer un index des IDs existants pour recherche rapide
            $existingIds = array_column($formules, 'id');

            foreach ($runtime['customFormules'] as $f) {
                // Vérifier si elle n'existe pas déjà
                if (!in_array($f['id'], $existingIds, true)) {
                    $formules[] = $f;
                    $existingIds[] = $f['id']; // Ajouter à l'index pour éviter duplicatas
                    error_log('[syncFormulesToMenu] Formule ajoutée: ' . $f['id']);
                } else {
                    // Si elle existe, la mettre à jour avec les données du runtime
                    foreach ($formules as &$existing) {
                        if ($existing['id'] === $f['id']) {
                            $existing = array_merge($existing, $f);
                            error_log('[syncFormulesToMenu] Formule mise à jour: ' . $f['id']);
                            break;
                        }
                    }
                }
            }
        }

        // Supprimer les formules marquées comme supprimées
        if (!empty($runtime['deletedFormules'])) {
            $countBefore = count($formules);
            $formules = array_filter($formules, fn($f) => !in_array($f['id'], $runtime['deletedFormules'], true));
            $formules = array_values($formules);
            $countAfter = count($formules);
            error_log('[syncFormulesToMenu] Formules supprimées: ' . ($countBefore - $countAfter));
        }

        $menuData['formules'] = $formules;
        error_log('[syncFormulesToMenu] Formules APRÈS: ' . count($formules));

        $written = file_put_contents($menuPath, json_encode($menuData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        if ($written === false) {
            error_log('[syncFormulesToMenu] ❌ Échec écriture menu.json');
            throw new Exception('Échec écriture menu.json');
        }

        error_log('[syncFormulesToMenu] ✅ Écriture réussie: ' . $written . ' bytes');
        error_log('[syncFormulesToMenu] ========== END ==========');

    } catch (Exception $e) {
        error_log('[syncFormulesToMenu] ❌ ERREUR: ' . $e->getMessage());
        error_log('[syncFormulesToMenu] Stack trace: ' . $e->getTraceAsString());
        throw $e;
    }
}

/**
 * Génère menu.json depuis MySQL après modification
 */
function regenerateMenuJson(): void {
    exec('cd ' . escapeshellarg(SNACK_DB_PATH) . ' && php generateMenuJson.php 2>&1', $output, $returnCode);
    if ($returnCode !== 0) {
        error_log('[PRODUCTS API] ⚠️ Erreur génération menu.json: ' . implode("\n", $output));
    }
}

/* =========================
   MODE MySQL ou JSON
   ========================= */

// ⚠️ MODE MYSQL DÉSACTIVÉ - Retour au mode JSON
// MySQL contient données incomplètes, on utilise menu.json
$useMySQL = true;

/* =========================
   GET: Retourner le menu complet
   ========================= */

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // ✅ MODE MYSQL - Charger depuis la base de données
    if ($useMySQL) {
        // MenuRepository déjà chargé par bootstrap.php

        try {
            // Récupérer toutes les données depuis MySQL
            $categories = MenuRepository::getAllCategories();
            $supplements = MenuRepository::getAllSupplements();
            $categorySupplements = MenuRepository::getCategorySupplements();

            // Formater le menu pour le frontend
            $menu = ['categories' => $categories];

            // Formater les suppléments (avec groupement)
            $supplementsGrouped = MenuRepository::getSupplementsGrouped();
            $supplementGroups = MenuRepository::getSupplementGroups();
            $supplementsFormatted = [
                'catalog' => $supplements,
                'grouped' => $supplementsGrouped,
                'groups' => $supplementGroups, // Groupes distincts pour le select admin
                'defaultForCategories' => $categorySupplements
            ];

            // ✅ Charger formules depuis MySQL
            $formules = MenuRepository::getAllFormules();

            // ✅ Charger featured depuis MySQL (migré)
            $featuredSettings = MenuRepository::getFeaturedSettings();
            $featuredProductIds = MenuRepository::getFeaturedProductIds();
            $featured = [
                'enabled' => $featuredSettings['enabled'] ?? true,
                'title' => $featuredSettings['title'] ?? 'Sélection pour vous',
                'subtitle' => $featuredSettings['subtitle'] ?? 'Nos produits les plus appréciés',
                'items' => $featuredProductIds
            ];

            // Charger categoryIcons depuis menu.json (pas encore migré)
            $menuJsonPath = getMenuJsonPath();
            $categoryIcons = [];
            if (file_exists($menuJsonPath)) {
                $menuData = json_decode(file_get_contents($menuJsonPath), true);
                if ($menuData) {
                    $categoryIcons = $menuData['categoryIcons'] ?? [];
                }
            }

            // ✅ Charger upsell rules depuis MySQL
            $upsellRules = MenuRepository::getAllUpsellRules();

            jsonSuccess([
                'menu' => $menu,
                'supplements' => $supplementsFormatted,
                'formules' => $formules,
                'featured' => $featured,
                'categoryIcons' => $categoryIcons,
                'upsellRules' => $upsellRules
            ]);

        } catch (Exception $e) {
            error_log('[PRODUCTS API] ❌ Erreur MySQL GET: ' . $e->getMessage());
            jsonError('Erreur lors du chargement des données');
        }
    }

    // ✅ MODE JSON - Charger menu.json + appliquer runtime (filtre deletedCategories)
    $menuJsonPath = getMenuJsonPath();

    if (!file_exists($menuJsonPath)) {
        jsonError('menu.json introuvable');
    }

    $menuData = json_decode(file_get_contents($menuJsonPath), true);

    if (!$menuData) {
        jsonError('Erreur lecture menu.json');
    }

    // ✅ FIX: Charger runtime et appliquer deletedCategories
    $runtime = loadMenuRuntime();
    $menuDataFiltered = applyRuntimeToConfig($menuData, $runtime);

    // Retourner les données FILTRÉES (sans catégories/produits supprimés)
    jsonSuccess([
        'menu' => $menuDataFiltered['menu'] ?? [],
        'supplements' => $menuData['supplements'] ?? [],
        'formules' => $menuData['formules'] ?? [],
        'featured' => $menuData['featured'] ?? [],
        'categoryIcons' => $menuData['categoryIcons'] ?? []
    ]);
}

/* =========================
   POST ACTIONS
   ========================= */

// Vérifier l'authentification admin pour les POST
if (!isAdminLoggedIn()) {
    jsonError('Non autorisé', 401);
}

// Vérifier le token CSRF
$csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
if (!$csrfToken) {
    $input = json_decode(file_get_contents('php://input'), true);
    $csrfToken = $input['csrf_token'] ?? null;
}

// 🔒 SÉCURITÉ: Validation CSRF avec logs de diagnostic
if (!$csrfToken) {
    error_log('[CSRF DEBUG] Token manquant - Method: ' . $_SERVER['REQUEST_METHOD'] . ', Content-Type: ' . ($_SERVER['CONTENT_TYPE'] ?? 'none'));
    error_log('[CSRF DEBUG] Session ID: ' . (session_id() ?: 'NO SESSION'));
    error_log('[CSRF DEBUG] Session token présent: ' . (isset($_SESSION['csrf_token']) ? 'OUI' : 'NON'));
    jsonError('Token CSRF manquant - Veuillez recharger la page', 403);
}

if (!validateCsrfToken($csrfToken)) {
    error_log('[CSRF DEBUG] Token invalide - IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    error_log('[CSRF DEBUG] Token reçu (5 premiers caractères): ' . substr($csrfToken, 0, 5) . '...');
    error_log('[CSRF DEBUG] Token session (5 premiers caractères): ' . (isset($_SESSION['csrf_token']) ? substr($_SESSION['csrf_token'], 0, 5) . '...' : 'AUCUN'));
    error_log('[CSRF DEBUG] Session ID: ' . session_id());
    jsonError('Token CSRF invalide - Veuillez recharger la page et vous reconnecter', 403);
}

$input = readInput();

// 🔒 SÉCURITÉ: Validation action sans logs verbeux
if (empty($input['action'])) {
    jsonError('Action manquante - Vérifiez que le paramètre "action" est envoyé');
}

$action = $input['action'];
error_log('[PRODUCTS API] ✅ Action détectée: ' . $action);

/* ===== MySQL Mode ===== */
if ($useMySQL) {
    // MenuRepository déjà chargé par bootstrap.php

    switch ($action) {

        case 'add_category':
            $name = trim((string)($input['name'] ?? ''));
            $description = trim((string)($input['description'] ?? ''));
            $icon = trim((string)($input['icon'] ?? 'fa-utensils'));
            $flavor = trim((string)($input['flavor'] ?? ''));

            if ($name === '') {
                jsonError('Nom manquant');
            }

            if ($flavor && !in_array($flavor, ['sale', 'sucre'], true)) {
                jsonError('Flavor invalide (doit être "sale" ou "sucre")');
            }

            // Gérer upload icon_image
            $baseSlug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name));
            $iconImage = handleIconImageUpload($baseSlug);

            try {
                $result = MenuRepository::addCategory($name, $description, $icon, $flavor, $iconImage);
                // ⚠️ DÉSACTIVÉ: regenerateMenuJson() - Préserve menu.json existant
                // TODO: Fusionner MySQL + ancien menu.json correctement
                // regenerateMenuJson();
                jsonSuccess(['category' => $result]);
            } catch (Exception $e) {
                jsonError($e->getMessage());
            }
            break;

        case 'edit_category':
            // 🔍 DEBUG: Logger ce qui est reçu
            error_log("=== EDIT_CATEGORY MySQL Mode ===");
            error_log("Input brut category_id: " . var_export($input['category_id'] ?? 'NULL', true));

            $categoryId = (int)($input['category_id'] ?? 0);
            $name = trim((string)($input['name'] ?? ''));
            $description = trim((string)($input['description'] ?? ''));
            $icon = trim((string)($input['icon'] ?? 'fa-utensils'));
            $flavor = trim((string)($input['flavor'] ?? ''));
            $clearIconImage = !empty($input['clear_icon_image']);

            error_log("categoryId après (int): " . $categoryId);
            error_log("name: " . $name);
            error_log("icon: " . $icon);

            if (!$categoryId || $name === '') {
                error_log("❌ Paramètres manquants - categoryId: " . $categoryId . ", name: " . $name);
                jsonError('Paramètres manquants (categoryId=' . $categoryId . ')');
            }

            // Gérer upload icon_image ou suppression
            $iconImage = null;
            if ($clearIconImage) {
                $iconImage = ''; // String vide pour supprimer
            } else {
                $baseSlug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name));
                $uploadedImage = handleIconImageUpload($baseSlug);
                if ($uploadedImage) {
                    $iconImage = $uploadedImage;
                }
            }

            try {
                error_log("🔄 Appel MenuRepository::editCategory avec ID: " . $categoryId);
                $success = MenuRepository::editCategory($categoryId, $name, $description, $icon, $flavor, $iconImage);
                error_log("✅ Résultat editCategory: " . var_export($success, true));

                // ⚠️ DÉSACTIVÉ: regenerateMenuJson() - Préserve menu.json existant
                // regenerateMenuJson();
                jsonSuccess(['category' => ['id' => $categoryId, 'name' => $name, 'icon' => $icon, 'icon_image' => $iconImage, 'flavor' => $flavor]]);
            } catch (Exception $e) {
                error_log("❌ Exception editCategory: " . $e->getMessage());
                jsonError($e->getMessage());
            }
            break;

        case 'delete_category':
            $categoryId = (int)($input['category_id'] ?? 0);

            if (!$categoryId) {
                jsonError('ID manquant');
            }

            try {
                MenuRepository::deleteCategory($categoryId);
                // ⚠️ DÉSACTIVÉ: regenerateMenuJson() - Préserve menu.json existant
                // regenerateMenuJson();
                jsonSuccess(['message' => 'Catégorie supprimée']);
            } catch (Exception $e) {
                jsonError($e->getMessage());
            }
            break;

        case 'add_product':
            $categoryId = (int)($input['category_id'] ?? 0);
            $name = trim((string)($input['name'] ?? ''));
            $description = trim((string)($input['description'] ?? ''));
            $priceSolo = (float)($input['priceSolo'] ?? 0);
            $priceMenu = isset($input['priceMenu']) && $input['priceMenu'] !== '' ? (float)$input['priceMenu'] : null;

            if (!$categoryId || $name === '' || $priceSolo <= 0) {
                jsonError('Champs invalides');
            }

            $baseSlug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name));
            $imagePath = handleImageUpload($baseSlug);

            // Gérer baseIngredients (peut être une chaîne JSON depuis FormData)
            $baseIngredients = null;
            if (isset($input['baseIngredients'])) {
                $baseIng = $input['baseIngredients'];
                if (is_string($baseIng)) {
                    $baseIng = json_decode($baseIng, true) ?? [];
                }
                $baseIngredients = is_array($baseIng) ? $baseIng : [];
            }

            // Gérer snackupContext (peut être une chaîne JSON depuis FormData)
            $snackupContext = null;
            if (isset($input['snackupContext'])) {
                $snackupCtx = $input['snackupContext'];
                if (is_string($snackupCtx)) {
                    $snackupCtx = json_decode($snackupCtx, true) ?? null;
                }
                $snackupContext = is_array($snackupCtx) ? $snackupCtx : null;
            }

            try {
                $result = MenuRepository::addProduct($categoryId, $name, $description, $imagePath, $priceSolo, $priceMenu, $baseIngredients, $snackupContext);
                // ⚠️ DÉSACTIVÉ: regenerateMenuJson() - Préserve menu.json existant
                // regenerateMenuJson();
                jsonSuccess(['product' => $result]);
            } catch (Exception $e) {
                jsonError($e->getMessage());
            }
            break;

        case 'edit_product':
        case 'update_product':
            $productId = (int)($input['product_id'] ?? 0);
            $name = trim((string)($input['name'] ?? ''));
            $description = trim((string)($input['description'] ?? ''));
            $priceSolo = (float)($input['priceSolo'] ?? 0);
            $priceMenu = isset($input['priceMenu']) && $input['priceMenu'] !== '' ? (float)$input['priceMenu'] : null;
            $status = $input['status'] ?? 'available';

            if (!$productId) {
                jsonError('ID produit manquant');
            }

            // Gérer upload image si présent
            $baseSlug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name));
            $imagePath = handleImageUpload($baseSlug);
            if (!$imagePath && isset($input['image'])) {
                $imagePath = $input['image']; // Garder l'image envoyée
            }
            // ✅ FIX: Si pas d'image fournie, récupérer l'existante de la BDD
            if (!$imagePath) {
                $pdo = Database::getInstance();
                $stmtImg = $pdo->prepare("SELECT image FROM products WHERE id = ? AND restaurant_id = ?");
                $stmtImg->execute([$productId, SNACK_RESTAURANT_ID]);
                $existingImage = $stmtImg->fetchColumn();
                if ($existingImage) {
                    $imagePath = $existingImage;
                }
            }

            // Gérer baseIngredients (peut être une chaîne JSON depuis FormData)
            $baseIngredients = null;
            if (isset($input['baseIngredients'])) {
                $baseIng = $input['baseIngredients'];
                if (is_string($baseIng)) {
                    $baseIng = json_decode($baseIng, true) ?? [];
                }
                $baseIngredients = is_array($baseIng) ? $baseIng : [];
            }

            // Gérer le badge
            $badge = isset($input['badge']) ? trim($input['badge']) : null;
            if ($badge === '') {
                $badge = null;
            }

            try {
                MenuRepository::editProduct($productId, $name, $description, $imagePath, $priceSolo, $priceMenu, $status, $baseIngredients, $badge);
                // ⚠️ DÉSACTIVÉ: regenerateMenuJson() - Préserve menu.json existant
                // regenerateMenuJson();
                jsonSuccess(['product' => ['id' => $productId, 'name' => $name]]);
            } catch (Exception $e) {
                jsonError($e->getMessage());
            }
            break;

        case 'change_status':
            $productId = (int)($input['product_id'] ?? 0);
            $status = $input['status'] ?? 'available';

            if (!$productId) {
                jsonError('ID produit manquant');
            }

            try {
                // On doit récupérer les infos actuelles du produit
                // Pour l'instant, on fait un simple UPDATE du status
                $pdo = Database::getInstance();
                $stmt = $pdo->prepare("UPDATE products SET status = ? WHERE id = ?");
                $stmt->execute([$status, $productId]);
                // ⚠️ DÉSACTIVÉ: regenerateMenuJson() - Préserve menu.json existant
                // regenerateMenuJson();
                jsonSuccess();
            } catch (Exception $e) {
                jsonError($e->getMessage());
            }
            break;

        case 'delete_product':
            $productId = (int)($input['product_id'] ?? 0);

            if (!$productId) {
                jsonError('ID produit manquant');
            }

            try {
                MenuRepository::deleteProduct($productId);
                // ⚠️ DÉSACTIVÉ: regenerateMenuJson() - Préserve menu.json existant
                // regenerateMenuJson();
                jsonSuccess();
            } catch (Exception $e) {
                jsonError($e->getMessage());
            }
            break;

        // ===== SUPPLÉMENTS (DB-FIRST) =====
        case 'add_supplement':
            $name = trim((string)($input['name'] ?? ''));
            $price = normalizePrice($input['price'] ?? 0);
            $status = $input['status'] ?? 'available';
            $flavor = $input['flavor'] ?? 'sale';
            // Accepter category OU group_name (admin envoie category)
            $groupName = $input['category'] ?? $input['group_name'] ?? 'autres';

            if ($name === '') {
                jsonError('Nom du supplément requis');
            }

            try {
                $supplementId = SupplementRepository::create(SNACK_RESTAURANT_ID, [
                    'name' => $name,
                    'price' => $price,
                    'status' => $status,
                    'flavor' => $flavor,
                    'group_name' => $groupName
                ]);

                // Auto-assigner aux catégories qui ont déjà des suppléments
                $pdo = Database::getInstance();
                $stmt = $pdo->query("SELECT DISTINCT category_id FROM category_supplements");
                $categoryIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

                foreach ($categoryIds as $catId) {
                    $insertStmt = $pdo->prepare("INSERT IGNORE INTO category_supplements (category_id, supplement_id) VALUES (?, ?)");
                    $insertStmt->execute([$catId, $supplementId]);
                }

                $supplement = SupplementRepository::getById($supplementId);
                jsonSuccess(['supplement' => $supplement]);
            } catch (Exception $e) {
                jsonError('Erreur création supplément: ' . $e->getMessage());
            }
            break;

        case 'update_supplement':
            $supplementId = (int)($input['supplement_id'] ?? $input['id'] ?? 0);

            if (!$supplementId) {
                jsonError('ID supplément manquant');
            }

            $updateData = [];
            if (isset($input['name'])) $updateData['name'] = trim((string)$input['name']);
            if (isset($input['price'])) $updateData['price'] = normalizePrice($input['price']);
            if (isset($input['status'])) $updateData['status'] = $input['status'];
            if (isset($input['flavor'])) $updateData['flavor'] = $input['flavor'];
            // Accepter category OU group_name (admin envoie category)
            if (isset($input['category'])) $updateData['group_name'] = $input['category'];
            if (isset($input['group_name'])) $updateData['group_name'] = $input['group_name'];

            if (empty($updateData)) {
                jsonError('Aucune donnée à mettre à jour');
            }

            try {
                $updated = SupplementRepository::update($supplementId, $updateData);
                if ($updated) {
                    $supplement = SupplementRepository::getById($supplementId);
                    jsonSuccess(['supplement' => $supplement]);
                } else {
                    jsonError('Supplément non trouvé ou non modifié');
                }
            } catch (Exception $e) {
                jsonError('Erreur mise à jour supplément: ' . $e->getMessage());
            }
            break;

        case 'delete_supplement':
            $supplementId = (int)($input['supplement_id'] ?? $input['id'] ?? 0);

            if (!$supplementId) {
                jsonError('ID supplément manquant');
            }

            try {
                // Suppression définitive (hard delete)
                $deleted = SupplementRepository::hardDelete($supplementId);
                if ($deleted) {
                    jsonSuccess(['message' => 'Supplément supprimé']);
                } else {
                    jsonError('Supplément non trouvé');
                }
            } catch (Exception $e) {
                jsonError('Erreur suppression supplément: ' . $e->getMessage());
            }
            break;

        case 'delete_sweet_supplements':
            // Supprime tous les suppléments sucrés (legacy Marvelous)
            try {
                $count = SupplementRepository::deleteAllSweet(SNACK_RESTAURANT_ID);
                jsonSuccess(['message' => "Suppléments sucrés supprimés: $count"]);
            } catch (Exception $e) {
                jsonError('Erreur suppression suppléments sucrés: ' . $e->getMessage());
            }
            break;

        case 'upload_supplement_image':
            $supplementId = (int)($input['supplement_id'] ?? 0);

            if (!$supplementId) {
                jsonError('ID supplément manquant');
            }

            $supplement = SupplementRepository::getById($supplementId);
            if (!$supplement) {
                jsonError('Supplément introuvable');
            }

            $imagePath = handleImageUpload('sup-' . $supplementId);
            if (!$imagePath) {
                jsonError('Aucune image fournie');
            }

            try {
                SupplementRepository::update($supplementId, ['image' => $imagePath]);
                $updatedSupplement = SupplementRepository::getById($supplementId);
                jsonSuccess(['supplement' => $updatedSupplement, 'image' => $imagePath]);
            } catch (Exception $e) {
                jsonError('Erreur upload image: ' . $e->getMessage());
            }
            break;

        // ===== FORMULES (DB-FIRST) =====
        case 'add_formule':
            // 🔒 VALIDATION: Rejeter tout formule_id envoyé (création = AUTO_INCREMENT uniquement)
            if (isset($input['formule_id']) && $input['formule_id'] !== '' && $input['formule_id'] !== null) {
                jsonError('Impossible de créer une formule avec un ID pré-défini (AUTO_INCREMENT requis)');
            }

            $name = trim((string)($input['name'] ?? ''));
            $description = trim((string)($input['description'] ?? ''));
            $price = (float)($input['price'] ?? 0);
            $originalPrice = isset($input['originalPrice']) && $input['originalPrice'] !== '' ? (float)$input['originalPrice'] : null;
            $status = $input['status'] ?? 'available';

            if ($name === '' || $price <= 0) {
                jsonError('Nom et prix requis');
            }

            // Gérer l'upload d'image
            $imagePath = handleFormuleImageUpload('formule-' . time());

            // Décoder les includes
            $includes = [];
            if (isset($input['includes'])) {
                $incData = is_string($input['includes']) ? json_decode($input['includes'], true) : $input['includes'];
                if (is_array($incData)) {
                    $includes = $incData;
                }
            }

            try {
                $result = MenuRepository::addFormule($name, $description, $price, $originalPrice, $imagePath, $includes);

                // ✅ VALIDATION: S'assurer que l'ID a été retourné
                if (!isset($result['id']) || !$result['id']) {
                    error_log('[PRODUCTS API] ❌ addFormule n\'a pas retourné d\'ID! Result: ' . json_encode($result));
                    jsonError('Création échouée: ID non retourné par la base de données');
                }

                error_log('[PRODUCTS API] ✅ Formule créée avec ID: ' . $result['id']);
                jsonSuccess(['formule' => $result]);
            } catch (Exception $e) {
                error_log('[PRODUCTS API] ❌ Erreur création formule: ' . $e->getMessage());
                jsonError('Erreur création formule: ' . $e->getMessage());
            }
            break;

        case 'update_formule':
            $formuleId = trim((string)($input['formule_id'] ?? ''));

            if (!$formuleId) {
                jsonError('ID formule manquant');
            }

            $name = trim((string)($input['name'] ?? ''));
            $description = trim((string)($input['description'] ?? ''));
            $price = (float)($input['price'] ?? 0);
            $originalPrice = isset($input['originalPrice']) && $input['originalPrice'] !== '' ? (float)$input['originalPrice'] : null;
            $status = $input['status'] ?? 'available';
            $badge = isset($input['badge']) && $input['badge'] !== '' ? trim((string)$input['badge']) : null;

            if ($name === '' || $price <= 0) {
                jsonError('Nom et prix requis');
            }

            // Gérer l'upload d'image
            $imagePath = handleFormuleImageUpload('formule-' . $formuleId);

            // Décoder les includes
            $includes = null;
            if (isset($input['includes'])) {
                $incData = is_string($input['includes']) ? json_decode($input['includes'], true) : $input['includes'];
                if (is_array($incData)) {
                    $includes = $incData;
                }
            }

            try {
                MenuRepository::editFormule($formuleId, $name, $description, $price, $originalPrice, $status, $imagePath, $includes, $badge);
                jsonSuccess(['formule' => ['id' => $formuleId, 'name' => $name]]);
            } catch (Exception $e) {
                jsonError('Erreur modification formule: ' . $e->getMessage());
            }
            break;

        case 'delete_formule':
            $formuleId = trim((string)($input['formule_id'] ?? ''));

            if (!$formuleId) {
                jsonError('ID formule manquant');
            }

            try {
                MenuRepository::deleteFormule($formuleId);
                jsonSuccess();
            } catch (Exception $e) {
                jsonError('Erreur suppression formule: ' . $e->getMessage());
            }
            break;

        // ANCIEN CODE JSON (GARDÉ POUR RÉFÉRENCE) :
        // Si besoin de restaurer l'ancien comportement JSON, il est ci-dessous :
        /*
        case 'add_formule_json_mode':
        case 'update_formule_json_mode':
        case 'delete_formule_json_mode':
            require_once __DIR__ . '/../config.php';
            $runtime = loadMenuRuntime();

            if ($action === 'add_formule_json_mode') {
                error_log('[PRODUCTS API] ========== add_formule START ==========');
                error_log('[PRODUCTS API] Input reçu: ' . json_encode($input));

                try {
                    $name = trim((string)($input['name'] ?? ''));
                    $price = (float)($input['price'] ?? 0);
                    error_log('[PRODUCTS API] name: ' . $name . ', price: ' . $price);

                    if ($name === '') {
                        error_log('[PRODUCTS API] ❌ Nom de formule requis');
                        jsonError('Nom de formule requis');
                    }
                    if ($price < 0) {
                        error_log('[PRODUCTS API] ❌ Prix invalide: ' . $price);
                        jsonError('Prix invalide');
                    }

                    $baseId = 'formule-' . strtolower(preg_replace('/[^a-z0-9]+/', '-', $name));
                    $baseId = trim($baseId, '-');
                    error_log('[PRODUCTS API] baseId: ' . $baseId);

                    $existingIds = array_keys($runtime['customFormules'] ?? []);
                    $menuData = json_decode(file_get_contents(getMenuJsonPath()), true);
                    foreach (($menuData['formules'] ?? []) as $f) {
                        if (isset($f['id'])) $existingIds[] = $f['id'];
                    }
                    error_log('[PRODUCTS API] existingIds: ' . json_encode($existingIds));

                    $id = $baseId;
                    $i = 2;
                    while (in_array($id, $existingIds, true)) {
                        $id = $baseId . '-' . $i;
                        $i++;
                    }
                    error_log('[PRODUCTS API] ✅ ID généré: ' . $id);

                    // Upload image formule désactivé temporairement (fonction handleFormuleImageUpload undefined)
                    $imagePath = handleFormuleImageUpload($id);
                    error_log('[PRODUCTS API] imagePath: ' . ($imagePath ?? 'NULL'));

                    $includes = [];
                    if (isset($input['includes'])) {
                        $incData = is_string($input['includes']) ? json_decode($input['includes'], true) : $input['includes'];
                        if (is_array($incData)) $includes = $incData;
                    }
                    error_log('[PRODUCTS API] includes: ' . json_encode($includes));

                    $formule = [
                        'id' => $id,
                        'name' => $name,
                        'description' => trim((string)($input['description'] ?? '')),
                        'price' => $price,
                        'originalPrice' => isset($input['originalPrice']) && $input['originalPrice'] !== '' ? (float)$input['originalPrice'] : null,
                        'savings' => null,
                        'badge' => isset($input['badge']) && $input['badge'] !== '' ? trim($input['badge']) : null,
                        'image' => $imagePath,
                        'includes' => $includes,
                        'status' => $input['status'] ?? 'available'
                    ];

                    if ($formule['originalPrice'] !== null) {
                        $formule['savings'] = round($formule['originalPrice'] - $formule['price'], 2);
                    }
                    error_log('[PRODUCTS API] formule construite: ' . json_encode($formule));

                    if (!isset($runtime['customFormules'])) $runtime['customFormules'] = [];
                    $runtime['customFormules'][$id] = $formule;

                    error_log('[PRODUCTS API] Sauvegarde runtime...');
                    saveMenuRuntime($runtime);
                    error_log('[PRODUCTS API] Sync vers menu.json...');
                    syncFormulesToMenu($runtime);
                    error_log('[PRODUCTS API] ✅ add_formule SUCCESS');
                    jsonSuccess(['formule' => $formule]);

                } catch (Throwable $e) {
                    error_log('[PRODUCTS API] ❌❌❌ ERREUR FATALE add_formule ❌❌❌');
                    error_log('[PRODUCTS API] ❌ Type: ' . get_class($e));
                    error_log('[PRODUCTS API] ❌ Message: ' . $e->getMessage());
                    error_log('[PRODUCTS API] ❌ Fichier: ' . $e->getFile());
                    error_log('[PRODUCTS API] ❌ Ligne: ' . $e->getLine());
                    error_log('[PRODUCTS API] ❌ Stack trace complète:');
                    error_log($e->getTraceAsString());
                    jsonError('Erreur création formule: ' . $e->getMessage() . ' (fichier: ' . basename($e->getFile()) . ':' . $e->getLine() . ')');
                }

            } elseif ($action === 'update_formule') {
                error_log('[PRODUCTS API] ========== update_formule START ==========');
                error_log('[PRODUCTS API] Input reçu: ' . json_encode($input));

                try {
                    $formuleId = $input['formule_id'] ?? null;
                    error_log('[PRODUCTS API] formuleId: ' . ($formuleId ?? 'NULL'));

                    if (!$formuleId) {
                        error_log('[PRODUCTS API] ❌ ID formule manquant');
                        jsonError('ID formule manquant');
                    }

                    $patch = [];
                    if (isset($input['name'])) $patch['name'] = trim($input['name']);
                    if (isset($input['description'])) $patch['description'] = trim($input['description']);
                    if (isset($input['price'])) $patch['price'] = (float)$input['price'];
                    if (isset($input['originalPrice'])) {
                        $patch['originalPrice'] = $input['originalPrice'] !== '' ? (float)$input['originalPrice'] : null;
                    }
                    if (isset($input['badge'])) {
                        $patch['badge'] = $input['badge'] !== '' ? trim($input['badge']) : null;
                    }
                    if (isset($input['status'])) $patch['status'] = $input['status'];
                    if (isset($input['includes'])) {
                        $incData = is_string($input['includes']) ? json_decode($input['includes'], true) : $input['includes'];
                        if (is_array($incData)) $patch['includes'] = $incData;
                    }
                    error_log('[PRODUCTS API] patch construit: ' . json_encode($patch));

                    if (isset($patch['price']) || isset($patch['originalPrice'])) {
                        $currentPrice = $patch['price'] ?? null;
                        $currentOriginal = $patch['originalPrice'] ?? null;
                        if ($currentPrice !== null && $currentOriginal !== null) {
                            $patch['savings'] = round($currentOriginal - $currentPrice, 2);
                            error_log('[PRODUCTS API] savings calculé: ' . $patch['savings']);
                        }
                    }

                    error_log('[PRODUCTS API] Tentative upload image...');
                    // Upload image formule désactivé temporairement (fonction handleFormuleImageUpload undefined)
                    $imagePath = handleFormuleImageUpload($formuleId);
                    error_log('[PRODUCTS API] imagePath: ' . ($imagePath ?? 'NULL'));

                    if ($imagePath) {
                        $oldImagePath = null;
                        if (isset($runtime['customFormules'][$formuleId]['image'])) {
                            $oldImagePath = $runtime['customFormules'][$formuleId]['image'];
                        } elseif (isset($runtime['formules'][$formuleId]['image'])) {
                            $oldImagePath = $runtime['formules'][$formuleId]['image'];
                        }

                        if ($oldImagePath && $oldImagePath !== $imagePath) {
                            $fullPath = SNACK_ROOT . '/' . ltrim($oldImagePath, '/');
                            if (file_exists($fullPath) && strpos($oldImagePath, '/formules/') !== false) {
                                @unlink($fullPath);
                                error_log('[PRODUCTS API] Ancienne image supprimée: ' . $fullPath);
                            }
                        }

                        $patch['image'] = $imagePath;
                    }

                    error_log('[PRODUCTS API] Mise à jour runtime...');
                    if (isset($runtime['customFormules'][$formuleId])) {
                        $runtime['customFormules'][$formuleId] = array_merge($runtime['customFormules'][$formuleId], $patch);
                        error_log('[PRODUCTS API] customFormule mise à jour');
                    } else {
                        if (!isset($runtime['formules'])) $runtime['formules'] = [];
                        if (!isset($runtime['formules'][$formuleId])) $runtime['formules'][$formuleId] = [];
                        $runtime['formules'][$formuleId] = array_merge($runtime['formules'][$formuleId], $patch);
                        error_log('[PRODUCTS API] formule runtime créée/mise à jour');
                    }

                    error_log('[PRODUCTS API] Sauvegarde runtime...');
                    saveMenuRuntime($runtime);
                    error_log('[PRODUCTS API] Sync vers menu.json...');
                    syncFormulesToMenu($runtime);
                    error_log('[PRODUCTS API] ✅ update_formule SUCCESS');
                    jsonSuccess(['formule' => $patch]);

                } catch (Exception $e) {
                    error_log('[PRODUCTS API] ❌ ERREUR update_formule: ' . $e->getMessage());
                    error_log('[PRODUCTS API] ❌ Stack trace: ' . $e->getTraceAsString());
                    jsonError('Erreur lors de la modification de la formule: ' . $e->getMessage());
                }

            } elseif ($action === 'delete_formule') {
                $formuleId = $input['formule_id'] ?? null;
                if (!$formuleId) jsonError('ID formule manquant');

                $imagePath = null;
                if (isset($runtime['customFormules'][$formuleId]['image'])) {
                    $imagePath = $runtime['customFormules'][$formuleId]['image'];
                }

                if ($imagePath) {
                    $fullPath = SNACK_ROOT . '/' . ltrim($imagePath, '/');
                    if (file_exists($fullPath) && strpos($imagePath, '/formules/') !== false) {
                        @unlink($fullPath);
                    }
                }

                if (isset($runtime['customFormules'][$formuleId])) {
                    unset($runtime['customFormules'][$formuleId]);
                }

                if (!isset($runtime['deletedFormules'])) {
                    $runtime['deletedFormules'] = [];
                }
                if (!in_array($formuleId, $runtime['deletedFormules'], true)) {
                    $runtime['deletedFormules'][] = $formuleId;
                }

                saveMenuRuntime($runtime);
                syncFormulesToMenu($runtime);
                jsonSuccess();
            }
            break;
        */

        // Featured products section - sauvegarde en DB
        case 'update_featured':
            error_log('[PRODUCTS API] ========== update_featured START ==========');

            $featuredData = $input['featured'] ?? null;
            if (!$featuredData || !is_array($featuredData)) {
                jsonError('Données featured invalides');
            }

            $enabled = $featuredData['enabled'] ?? true;
            $title = trim($featuredData['title'] ?? 'Sélection pour vous');
            $subtitle = trim($featuredData['subtitle'] ?? 'Nos produits les plus appréciés');
            $items = $featuredData['items'] ?? [];

            // Convertir items en array d'entiers
            $productIds = array_map('intval', array_filter($items, 'is_numeric'));

            try {
                // Sauvegarder les paramètres dans featured_settings
                MenuRepository::updateFeaturedSettings($enabled, $title, $subtitle);

                // Sauvegarder les produits featured
                MenuRepository::updateFeaturedProducts($productIds);

                error_log('[PRODUCTS API] ✅ Featured sauvegardé en DB: ' . count($productIds) . ' produits');

                jsonSuccess([
                    'featured' => [
                        'enabled' => $enabled,
                        'title' => $title,
                        'subtitle' => $subtitle,
                        'items' => $productIds
                    ]
                ]);
            } catch (Exception $e) {
                error_log('[PRODUCTS API] ❌ Erreur featured: ' . $e->getMessage());
                jsonError('Erreur sauvegarde featured: ' . $e->getMessage());
            }
            break;

        // ===== UPSELL RULES =====
        case 'add_upsell':
            $whenCategories = $input['when_categories'] ?? [];
            $suggestCategories = $input['suggest_categories'] ?? [];
            $message = trim($input['message'] ?? 'Un petit kiff avec ceci ?');
            $priority = (int)($input['priority'] ?? 1);

            if (empty($whenCategories) || empty($suggestCategories)) {
                jsonError('Catégories requises');
            }

            try {
                $id = MenuRepository::addUpsellRule($whenCategories, $suggestCategories, $message, $priority);
                jsonSuccess(['id' => $id, 'message' => 'Règle d\'upsell créée']);
            } catch (Exception $e) {
                jsonError($e->getMessage());
            }
            break;

        case 'update_upsell':
            $id = (int)($input['id'] ?? 0);
            $whenCategories = $input['when_categories'] ?? [];
            $suggestCategories = $input['suggest_categories'] ?? [];
            $message = trim($input['message'] ?? 'Un petit kiff avec ceci ?');
            $priority = (int)($input['priority'] ?? 1);
            $isActive = $input['is_active'] ?? true;

            if (!$id || empty($whenCategories) || empty($suggestCategories)) {
                jsonError('Paramètres invalides');
            }

            try {
                MenuRepository::updateUpsellRule($id, $whenCategories, $suggestCategories, $message, $priority, $isActive);
                jsonSuccess(['message' => 'Règle d\'upsell mise à jour']);
            } catch (Exception $e) {
                jsonError($e->getMessage());
            }
            break;

        case 'delete_upsell':
            $id = (int)($input['id'] ?? 0);

            if (!$id) {
                jsonError('ID manquant');
            }

            try {
                MenuRepository::deleteUpsellRule($id);
                jsonSuccess(['message' => 'Règle d\'upsell supprimée']);
            } catch (Exception $e) {
                jsonError($e->getMessage());
            }
            break;

        default:
            jsonError('Action inconnue');
    }
}

/* ===== Fallback JSON Mode ===== */
if (!$useMySQL) {
require_once __DIR__ . '/../config.php';
$runtime = loadMenuRuntime();

// ⚠️ Initialiser supplements structure pour éviter les erreurs
// generatePublicMenuJson() vérifiera si c'est vide et utilisera menu.json existant
if (!isset($runtime['supplements'])) {
    $runtime['supplements'] = [
        'catalog' => [],
        'defaultForCategories' => []
    ];
}

switch ($action) {

    case 'add_category':
        $name = trim((string)($input['name'] ?? ''));
        $description = trim((string)($input['description'] ?? ''));
        $flavor = trim((string)($input['flavor'] ?? ''));
        $icon = trim((string)($input['icon'] ?? 'fa-utensils'));

        if ($name === '') {
            jsonError('Nom manquant');
        }

        if ($flavor === '') {
            jsonError('Type de catégorie manquant (salé ou sucré)');
        }

        if (!in_array($flavor, ['sale', 'sucre'], true)) {
            jsonError('Type de catégorie invalide (doit être "sale" ou "sucre")');
        }

        $baseId = $input['id'] ?? strtolower(preg_replace('/[^a-z0-9]+/', '-', $name));
        $baseId = trim((string)$baseId, '-');
        if ($baseId === '') $baseId = 'categorie';

        $existingIds = array_keys($runtime['customCategories'] ?? []);
        $config = loadConfig();
        foreach (($config['menu']['categories'] ?? []) as $cat) {
            $existingIds[] = $cat['id'] ?? '';
        }

        $id = $baseId;
        $i = 2;
        while (in_array($id, $existingIds, true)) {
            $id = $baseId . '-' . $i;
            $i++;
        }

        $runtime['customCategories'][$id] = [
            'id' => $id,
            'name' => $name,
            'description' => $description,
            'flavor' => $flavor, // ✅ Stocker le flavor pour le site
            'items' => []
        ];

        // Assignment automatique des suppléments selon le type de catégorie
        $supplementsSales = [
            'sup-mix-fromages',
            'sup-cheddar',
            'sup-camembert',
            'sup-chakchouka',
            'sup-pomme-terre',
            'sup-oignons-confits',
            'sup-oeuf',
            'sup-viande-hachee',
            'sup-escalope-poulet',
            'sup-jambon'
        ];

        $supplementsSucres = [
            'sup-nutella',
            'sup-chocolat',
            'sup-confiture',
            'sup-creme-noisette',
            'sup-beurre-cacahuete',
            'sup-miel',
            'sup-caramel',
            'sup-speculoos',
            'sup-oursons',
            'sup-smarties',
            'sup-mnm',
            'sup-kitkat',
            'sup-maltesers',
            'sup-kinder',
            'sup-oreo',
            'sup-banane',
            'sup-fraise',
            'sup-pomme',
            'sup-kiwi',
            'sup-ananas',
            'sup-myrtilles',
            'sup-framboises',
            'sup-noix-coco',
            'sup-amandes',
            'sup-noisettes',
            'sup-noix',
            'sup-chantilly'
        ];

        if ($flavor === 'sale') {
            $runtime['supplements']['defaultForCategories'][$id] = $supplementsSales;
        } else {
            $runtime['supplements']['defaultForCategories'][$id] = $supplementsSucres;
        }

        // Sauvegarder l'icône dans menu.json
        $menuPath = getMenuJsonPath();
        if (file_exists($menuPath)) {
            clearstatcache(true, $menuPath);
            $menuData = json_decode(file_get_contents($menuPath), true);
            if ($menuData) {
                if (!isset($menuData['categoryIcons'])) {
                    $menuData['categoryIcons'] = [];
                }
                $menuData['categoryIcons'][$id] = $icon;
                file_put_contents($menuPath, json_encode($menuData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
        }

        // ⚡ OPTIMISATION: Une seule synchronisation à la fin
        saveMenuRuntime($runtime, true);

        jsonSuccess(['category' => ['id' => $id, 'name' => $name, 'icon' => $icon]]);
        break;

    case 'edit_category':
        $categoryId = trim((string)($input['category_id'] ?? ''));
        $name = trim((string)($input['name'] ?? ''));
        $description = trim((string)($input['description'] ?? ''));
        $flavor = trim((string)($input['flavor'] ?? ''));
        $icon = trim((string)($input['icon'] ?? 'fa-utensils'));

        if ($categoryId === '' || $name === '') {
            jsonError('Paramètres manquants');
        }

        // Valider le flavor s'il est fourni
        if ($flavor !== '' && !in_array($flavor, ['sale', 'sucre'], true)) {
            jsonError('Type de catégorie invalide (doit être "sale" ou "sucre")');
        }

        // ✅ UTILISER MYSQL au lieu de menu.json
        try {
            $success = MenuRepository::editCategory($categoryId, $name, $description, $icon, $flavor);

            if (!$success) {
                jsonError('Catégorie introuvable ou échec de la mise à jour');
            }

            // Régénérer menu.json depuis MySQL
            regenerateMenuJson();

            jsonSuccess(['category' => ['id' => $categoryId, 'name' => $name, 'icon' => $icon]]);
        } catch (Exception $e) {
            error_log('[PRODUCTS API] ❌ Erreur edit_category: ' . $e->getMessage());
            jsonError('Erreur lors de la modification de la catégorie');
        }

        jsonSuccess(['category' => ['id' => $categoryId, 'name' => $name, 'icon' => $icon]]);
        break;

    case 'delete_category':
        $categoryId = trim((string)($input['category_id'] ?? ''));

        if ($categoryId === '') {
            jsonError('ID manquant');
        }

        $runtime = loadMenuRuntime();

        // ✅ FIX: Vérifier que la catégorie existe dans menu.json OU dans customCategories
        $menuJsonPath = getMenuJsonPath();
        $categoryExists = false;

        // Vérifier dans customCategories
        if (isset($runtime['customCategories'][$categoryId])) {
            $categoryExists = true;
        }

        // Vérifier dans menu.json
        if (!$categoryExists && file_exists($menuJsonPath)) {
            $menuData = json_decode(file_get_contents($menuJsonPath), true);
            if ($menuData) {
                foreach (($menuData['menu']['categories'] ?? []) as $cat) {
                    if (($cat['id'] ?? '') === $categoryId) {
                        $categoryExists = true;
                        break;
                    }
                }
            }
        }

        if (!$categoryExists) {
            jsonError('Catégorie introuvable dans menu.json et customCategories');
        }

        // Supprimer de customCategories si elle y est
        if (isset($runtime['customCategories'][$categoryId])) {
            unset($runtime['customCategories'][$categoryId]);
        }

        // ✅ FIX: Ajouter à deletedCategories pour empêcher réapparition
        if (!isset($runtime['deletedCategories'])) {
            $runtime['deletedCategories'] = [];
        }
        if (!in_array($categoryId, $runtime['deletedCategories'], true)) {
            $runtime['deletedCategories'][] = $categoryId;
        }

        // Supprimer l'icône du menu.json si elle existe
        $menuPath = getMenuJsonPath();
        if (file_exists($menuPath)) {
            clearstatcache(true, $menuPath);
            $menuData = json_decode(file_get_contents($menuPath), true);
            if ($menuData && isset($menuData['categoryIcons'][$categoryId])) {
                unset($menuData['categoryIcons'][$categoryId]);
                file_put_contents($menuPath, json_encode($menuData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
        }

        // ⚡ OPTIMISATION: Une seule synchronisation à la fin
        saveMenuRuntime($runtime, true);

        jsonSuccess(['deleted' => true]);
        break;

    case 'add_product':
        $categoryId = $input['category_id'] ?? null;
        $name = trim((string)($input['name'] ?? ''));
        $priceSolo = (float)($input['priceSolo'] ?? 0);

        if (!$categoryId || $name === '' || $priceSolo <= 0) {
            jsonError('Champs invalides');
        }

        $baseId = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name));
        $baseId = trim($baseId, '-');
        if ($baseId === '') $baseId = 'produit';

        $imagePath = handleImageUpload($baseId) ?? '';

        $existingIds = array_keys($runtime['customProducts'] ?? []);
        $productId = $baseId;
        $j = 2;
        while (in_array($productId, $existingIds, true)) {
            $productId = $baseId . '-' . $j;
            $j++;
        }

        $supplements = [];
        if (isset($input['supplements'])) {
            $supData = is_string($input['supplements']) ? json_decode($input['supplements'], true) : $input['supplements'];
            if (is_array($supData)) {
                $supplements = array_values($supData);
            }
        }

        $baseIngredients = [];
        if (isset($input['baseIngredients'])) {
            $baseIngData = is_string($input['baseIngredients']) ? json_decode($input['baseIngredients'], true) : $input['baseIngredients'];
            if (is_array($baseIngData)) {
                $baseIngredients = array_values($baseIngData);
            }
        }

        $snackupContext = null;
        if (isset($input['snackupContext'])) {
            $snackupCtxData = is_string($input['snackupContext']) ? json_decode($input['snackupContext'], true) : $input['snackupContext'];
            if (is_array($snackupCtxData)) {
                $snackupContext = $snackupCtxData;
            }
        }

        $runtime['customProducts'][$productId] = [
            'id' => $productId,
            'categoryId' => $categoryId,
            'name' => $name,
            'description' => $input['description'] ?? '',
            'priceSolo' => $priceSolo,
            'priceMenu' => (array_key_exists('priceMenu', $input) && $input['priceMenu'] !== '' && $input['priceMenu'] !== null) ? (float)$input['priceMenu'] : null,
            'badge' => (array_key_exists('badge', $input) && $input['badge'] !== '' && $input['badge'] !== null) ? trim($input['badge']) : null,
            'pricePrefix' => (array_key_exists('pricePrefix', $input) && $input['pricePrefix'] !== '' && $input['pricePrefix'] !== null) ? trim($input['pricePrefix']) : null,
            'image' => $imagePath,
            'status' => 'available',
            'supplements' => $supplements,
            'baseIngredients' => $baseIngredients,
            'snackupContext' => $snackupContext
        ];

        // ⚡ OPTIMISATION: Une seule synchronisation à la fin
        saveMenuRuntime($runtime, true);
        jsonSuccess(['product' => ['id' => $productId, 'category_id' => $categoryId]]);
        break;

    case 'update_product':
        $productId = $input['product_id'] ?? null;

        if (!$productId) {
            jsonError('ID produit manquant');
        }

        $patch = [];
        if (isset($input['name'])) $patch['name'] = trim($input['name']);
        if (isset($input['description'])) $patch['description'] = $input['description'];
        if (isset($input['priceSolo'])) {
            $patch['priceSolo'] = (float)$input['priceSolo'];
            // ✅ FIX: Mettre à jour "price" aussi pour écraser le menu.json de base
            $patch['price'] = (float)$input['priceSolo'];
        }
        // ✅ FIX: Utiliser array_key_exists() au lieu de isset() pour détecter null
        if (array_key_exists('priceMenu', $input)) $patch['priceMenu'] = $input['priceMenu'] !== '' && $input['priceMenu'] !== null ? (float)$input['priceMenu'] : null;
        if (array_key_exists('badge', $input)) $patch['badge'] = $input['badge'] !== '' && $input['badge'] !== null ? trim($input['badge']) : null;
        if (array_key_exists('pricePrefix', $input)) $patch['pricePrefix'] = $input['pricePrefix'] !== '' && $input['pricePrefix'] !== null ? trim($input['pricePrefix']) : null;
        if (isset($input['status'])) $patch['status'] = $input['status'];

        // Gérer les suppléments (peuvent être une chaîne JSON depuis FormData)
        if (isset($input['supplements'])) {
            $sups = $input['supplements'];
            if (is_string($sups)) {
                $sups = json_decode($sups, true) ?? [];
            }
            $patch['supplements'] = is_array($sups) ? $sups : [];
        }

        // Gérer les baseIngredients (ingrédients retirables)
        if (isset($input['baseIngredients'])) {
            $baseIng = $input['baseIngredients'];
            if (is_string($baseIng)) {
                $baseIng = json_decode($baseIng, true) ?? [];
            }
            $patch['baseIngredients'] = is_array($baseIng) ? $baseIng : [];
        }

        // Gérer snackupContext (contexte Snackup: ingrédients custom, prix custom, etc.)
        if (isset($input['snackupContext'])) {
            $snackupCtx = $input['snackupContext'];
            if (is_string($snackupCtx)) {
                $snackupCtx = json_decode($snackupCtx, true) ?? null;
            }
            $patch['snackupContext'] = is_array($snackupCtx) ? $snackupCtx : null;
        }

        // Gérer les variants (Court/Long pour cafés)
        if (isset($input['variants'])) {
            $variants = $input['variants'];
            if (is_string($variants)) {
                $variants = json_decode($variants, true) ?? [];
            }
            $patch['variants'] = is_array($variants) ? $variants : [];
        }

        // Gérer les numéros de capsules (legacy)
        if (isset($input['capsuleNumbers'])) {
            $capsuleNumbers = $input['capsuleNumbers'];
            if (is_string($capsuleNumbers)) {
                $capsuleNumbers = json_decode($capsuleNumbers, true) ?? [];
            }
            $patch['capsuleNumbers'] = is_array($capsuleNumbers) ? $capsuleNumbers : [];
        }

        // Gérer les couleurs de capsules (nouvelle logique)
        if (isset($input['capsuleColors'])) {
            $capsuleColors = $input['capsuleColors'];
            if (is_string($capsuleColors)) {
                $capsuleColors = json_decode($capsuleColors, true) ?? [];
            }
            $patch['capsuleColors'] = is_array($capsuleColors) ? $capsuleColors : [];
        }

        // Gérer l'upload d'image
        $imagePath = handleImageUpload($productId);
        if ($imagePath) {
            // Supprimer l'ancienne image si elle existe
            $oldImagePath = null;
            if (isset($runtime['customProducts'][$productId]['image'])) {
                $oldImagePath = $runtime['customProducts'][$productId]['image'];
            } elseif (isset($runtime['products'][$productId]['image'])) {
                $oldImagePath = $runtime['products'][$productId]['image'];
            }

            if ($oldImagePath && $oldImagePath !== $imagePath) {
                $fullPath = SNACK_ROOT . '/' . ltrim($oldImagePath, '/');
                if (file_exists($fullPath) && strpos($oldImagePath, '/uploads/') !== false) {
                    @unlink($fullPath);
                }
            }

            $patch['image'] = $imagePath;
        }

        if (isset($runtime['customProducts'][$productId])) {
            $runtime['customProducts'][$productId] = array_merge($runtime['customProducts'][$productId], $patch);
        } else {
            if (!isset($runtime['products'])) $runtime['products'] = [];
            if (!isset($runtime['products'][$productId])) $runtime['products'][$productId] = [];
            $runtime['products'][$productId] = array_merge($runtime['products'][$productId], $patch);
        }

        // ⚡ OPTIMISATION: Synchronisation groupée
        saveMenuRuntime($runtime);
        require_once __DIR__ . '/../sync-menu.php';
        syncMenuStatuses();

        jsonSuccess(['product' => $patch]);
        break;

    case 'change_status':
        $productId = $input['product_id'] ?? null;
        $newStatus = $input['status'] ?? 'available';

        error_log("[PRODUCTS API] change_status - productId: {$productId}, newStatus: {$newStatus}");

        if (!$productId) {
            error_log('[PRODUCTS API] ❌ ID produit manquant');
            jsonError('ID produit manquant');
        }

        if (!in_array($newStatus, ['available', 'unavailable'], true)) {
            error_log("[PRODUCTS API] ❌ Statut invalide: {$newStatus}");
            jsonError('Statut invalide (available ou unavailable requis)');
        }

        // Initialiser la structure si nécessaire
        if (!isset($runtime['products'])) $runtime['products'] = [];

        // ✅ FIX: Ne pas écraser les données existantes, juste ajouter/modifier le statut
        if (!isset($runtime['products'][$productId])) {
            $runtime['products'][$productId] = ['status' => $newStatus];
        } else {
            $runtime['products'][$productId]['status'] = $newStatus;
        }
        error_log("[PRODUCTS API] ✅ Statut mis à jour dans runtime pour {$productId}");

        // Si c'est un produit custom, mettre à jour aussi
        if (isset($runtime['customProducts'][$productId])) {
            $runtime['customProducts'][$productId]['status'] = $newStatus;
            error_log("[PRODUCTS API] ✅ Statut mis à jour dans customProducts pour {$productId}");
        }

        // Sauvegarder le runtime
        $saved = saveMenuRuntime($runtime);
        error_log("[PRODUCTS API] Runtime sauvegardé: " . ($saved ? 'OUI' : 'NON'));

        // Sync vers menu.json pour le site public
        require_once __DIR__ . '/../sync-menu.php';
        $syncResult = syncMenuStatuses();
        error_log("[PRODUCTS API] Sync menu.json: " . json_encode($syncResult));

        jsonSuccess(['status' => $newStatus, 'synced' => true]);
        break;

    case 'delete_product':
        $productId = $input['product_id'] ?? null;

        if (!$productId) {
            jsonError('ID produit manquant');
        }

        // Récupérer l'image du produit avant suppression pour la nettoyer
        $imagePath = null;
        if (isset($runtime['customProducts'][$productId]['image'])) {
            $imagePath = $runtime['customProducts'][$productId]['image'];
        } elseif (isset($runtime['products'][$productId]['image'])) {
            $imagePath = $runtime['products'][$productId]['image'];
        }

        // Supprimer l'image si elle existe
        if ($imagePath) {
            $fullPath = SNACK_ROOT . '/' . ltrim($imagePath, '/');
            if (file_exists($fullPath) && strpos($imagePath, '/uploads/') !== false) {
                @unlink($fullPath);
            }
        }

        if (isset($runtime['customProducts'][$productId])) {
            unset($runtime['customProducts'][$productId]);
        }

        if (!isset($runtime['deletedProducts'])) $runtime['deletedProducts'] = [];
        if (!in_array($productId, $runtime['deletedProducts'], true)) {
            $runtime['deletedProducts'][] = $productId;
        }

        // ⚡ OPTIMISATION: Synchronisation groupée
        saveMenuRuntime($runtime);
        require_once __DIR__ . '/../sync-menu.php';
        syncMenuStatuses();

        jsonSuccess();
        break;

    case 'add_supplement':
        $name = trim((string)($input['name'] ?? ''));
        $price = (float)($input['price'] ?? 0);
        $category = trim((string)($input['category'] ?? 'autre'));

        if ($name === '' || $price < 0) {
            jsonError('Nom ou prix invalide');
        }

        $baseId = 'sup-' . strtolower(preg_replace('/[^a-z0-9]+/', '-', $name));
        $existingIds = array_keys($runtime['supplements']['catalog'] ?? []);
        $id = $baseId;
        $i = 2;
        while (in_array($id, $existingIds, true)) {
            $id = $baseId . '-' . $i;
            $i++;
        }

        // Tous les suppléments sont salés (sucrés retirés)
        $runtime['supplements']['catalog'][$id] = [
            'id' => $id,
            'name' => $name,
            'price' => $price,
            'status' => 'available',
            'category' => $category,      // Pour compatibilité admin
            'group_name' => $category,    // Pour DB
            'flavor' => 'sale'            // Toujours salé
        ];

        // ⚡ OPTIMISATION: Synchronisation groupée
        saveMenuRuntime($runtime);
        require_once __DIR__ . '/../sync-menu.php';
        syncMenuStatuses();

        jsonSuccess(['supplement' => ['id' => $id, 'name' => $name, 'price' => $price, 'category' => $category, 'flavor' => $flavor]]);
        break;

    case 'update_supplement':
        $id = $input['supplement_id'] ?? null;

        // Si le supplément n'existe pas dans runtime, le copier depuis menu.json
        if (!isset($runtime['supplements']['catalog'][$id])) {
            $menuJsonPath = getMenuJsonPath();
            if (file_exists($menuJsonPath)) {
                $menuData = json_decode(file_get_contents($menuJsonPath), true);
                if (isset($menuData['supplements']['catalog'][$id])) {
                    // Copier le supplément depuis menu.json vers runtime
                    $runtime['supplements']['catalog'][$id] = $menuData['supplements']['catalog'][$id];
                }
            }
        }

        if (!$id || !isset($runtime['supplements']['catalog'][$id])) {
            jsonError('Supplément introuvable');
        }

        if (isset($input['name'])) $runtime['supplements']['catalog'][$id]['name'] = trim($input['name']);
        if (isset($input['price'])) $runtime['supplements']['catalog'][$id]['price'] = normalizePrice($input['price']);
        if (isset($input['status'])) $runtime['supplements']['catalog'][$id]['status'] = $input['status'];
        if (isset($input['category'])) {
            $runtime['supplements']['catalog'][$id]['category'] = $input['category'];
            $runtime['supplements']['catalog'][$id]['group_name'] = $input['category']; // Sync avec DB
            $runtime['supplements']['catalog'][$id]['flavor'] = 'sale'; // Toujours salé
        }

        // ⚡ OPTIMISATION: Synchronisation groupée
        saveMenuRuntime($runtime);
        require_once __DIR__ . '/../sync-menu.php';
        syncMenuStatuses();

        jsonSuccess(['supplement' => $runtime['supplements']['catalog'][$id]]);
        break;

    case 'delete_supplement':
        $id = $input['supplement_id'] ?? null;

        // Si le supplément n'existe pas dans runtime, le copier depuis menu.json
        if (!isset($runtime['supplements']['catalog'][$id])) {
            $menuJsonPath = getMenuJsonPath();
            if (file_exists($menuJsonPath)) {
                $menuData = json_decode(file_get_contents($menuJsonPath), true);
                if (isset($menuData['supplements']['catalog'][$id])) {
                    // Copier le supplément depuis menu.json vers runtime
                    $runtime['supplements']['catalog'][$id] = $menuData['supplements']['catalog'][$id];
                }
            }
        }

        if (!$id || !isset($runtime['supplements']['catalog'][$id])) {
            jsonError('Supplément introuvable');
        }

        // Supprimer du runtime
        unset($runtime['supplements']['catalog'][$id]);

        // Ajouter à la liste des suppléments supprimés
        if (!isset($runtime['deletedSupplements'])) {
            $runtime['deletedSupplements'] = [];
        }
        if (!in_array($id, $runtime['deletedSupplements'], true)) {
            $runtime['deletedSupplements'][] = $id;
        }

        // Retirer de defaultForCategories
        if (isset($runtime['supplements']['defaultForCategories'])) {
            foreach ($runtime['supplements']['defaultForCategories'] as $catId => &$supIds) {
                $supIds = array_values(array_filter($supIds, fn($s) => $s !== $id));
            }
        }

        // ⚡ OPTIMISATION: Synchronisation groupée
        saveMenuRuntime($runtime);
        require_once __DIR__ . '/../sync-menu.php';
        syncMenuStatuses();

        jsonSuccess();
        break;

    // ===== FORMULES =====
    case 'add_formule':
        error_log('[PRODUCTS API JSON MODE] ========== add_formule START ==========');
        error_log('[PRODUCTS API JSON MODE] Input reçu: ' . json_encode($input));

        try {
            $name = trim((string)($input['name'] ?? ''));
            $description = trim((string)($input['description'] ?? ''));
            $price = normalizePrice($input['price'] ?? 0);
            $originalPrice = isset($input['originalPrice']) && $input['originalPrice'] !== '' ? normalizePrice($input['originalPrice']) : null;
            $status = $input['status'] ?? 'available';

            error_log("[PRODUCTS API JSON MODE] name: {$name}, price: {$price}");

            if ($name === '' || $price <= 0) {
                error_log('[PRODUCTS API JSON MODE] ❌ Nom ou prix invalide');
                jsonError('Nom et prix requis');
            }

            // Générer un ID unique
            $baseId = 'formule-' . strtolower(preg_replace('/[^a-z0-9]+/', '-', $name));
            $baseId = trim($baseId, '-');
            error_log("[PRODUCTS API JSON MODE] baseId: {$baseId}");

            // Collecter TOUS les IDs existants (runtime + menu.json)
            $existingIds = [];

            // IDs du runtime customFormules
            if (!empty($runtime['customFormules'])) {
                $existingIds = array_merge($existingIds, array_keys($runtime['customFormules']));
            }

            // IDs du menu.json
            $menuData = json_decode(file_get_contents(getMenuJsonPath()), true);
            foreach (($menuData['formules'] ?? []) as $f) {
                if (isset($f['id'])) {
                    $existingIds[] = $f['id'];
                }
            }

            error_log("[PRODUCTS API JSON MODE] IDs existants: " . json_encode($existingIds));

            // Générer un ID unique
            $id = $baseId;
            $i = 2;
            while (in_array($id, $existingIds, true)) {
                $id = $baseId . '-' . $i;
                $i++;
            }

            error_log("[PRODUCTS API JSON MODE] ✅ ID généré: {$id}");

            // Gérer l'upload d'image
            error_log('[PRODUCTS API JSON MODE] Tentative upload image...');
            // Upload image formule désactivé temporairement (fonction handleFormuleImageUpload undefined)
            $imagePath = handleFormuleImageUpload($id);
            error_log('[PRODUCTS API JSON MODE] imagePath: ' . ($imagePath ?? 'NULL'));

            // Décoder les includes
            $includes = [];
            if (isset($input['includes'])) {
                $incData = is_string($input['includes']) ? json_decode($input['includes'], true) : $input['includes'];
                if (is_array($incData)) {
                    $includes = $incData;
                }
            }
            error_log('[PRODUCTS API JSON MODE] includes: ' . json_encode($includes));

            // Calculer l'économie
            $savings = $originalPrice !== null ? round($originalPrice - $price, 2) : null;

            $formule = [
                'id' => $id,
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'originalPrice' => $originalPrice,
                'savings' => $savings,
                'image' => $imagePath,
                'includes' => $includes,
                'status' => $status
            ];

            if (!isset($runtime['customFormules'])) {
                $runtime['customFormules'] = [];
            }
            $runtime['customFormules'][$id] = $formule;

            error_log("[PRODUCTS API JSON MODE] ✅ Formule ajoutée au runtime: " . json_encode($formule));

            // ⚡ OPTIMISATION: Synchronisation groupée
            error_log('[PRODUCTS API JSON MODE] Sauvegarde runtime...');
            $saved = saveMenuRuntime($runtime);
            error_log("[PRODUCTS API JSON MODE] Runtime sauvegardé: " . ($saved ? 'OUI' : 'NON'));
            error_log('[PRODUCTS API JSON MODE] Sync vers menu.json...');
            syncFormulesToMenu($runtime);
            error_log("[PRODUCTS API JSON MODE] ✅ Formule synchronisée vers menu.json");

            jsonSuccess(['formule' => $formule]);

        } catch (Throwable $e) {
            error_log('[PRODUCTS API JSON MODE] ❌❌❌ ERREUR FATALE add_formule ❌❌❌');
            error_log('[PRODUCTS API JSON MODE] ❌ Type: ' . get_class($e));
            error_log('[PRODUCTS API JSON MODE] ❌ Message: ' . $e->getMessage());
            error_log('[PRODUCTS API JSON MODE] ❌ Fichier: ' . $e->getFile());
            error_log('[PRODUCTS API JSON MODE] ❌ Ligne: ' . $e->getLine());
            error_log('[PRODUCTS API JSON MODE] ❌ Stack trace complète:');
            error_log($e->getTraceAsString());
            jsonError('Erreur création formule: ' . $e->getMessage() . ' (fichier: ' . basename($e->getFile()) . ':' . $e->getLine() . ')');
        }
        break;

    case 'update_formule':
        error_log('[PRODUCTS API JSON MODE] ========== update_formule START ==========');
        error_log('[PRODUCTS API JSON MODE] Input reçu: ' . json_encode($input));

        try {
            $formuleId = $input['formule_id'] ?? null;
            error_log('[PRODUCTS API JSON MODE] formuleId: ' . ($formuleId ?? 'NULL'));

            if (!$formuleId) {
                error_log('[PRODUCTS API JSON MODE] ❌ ID formule manquant');
                jsonError('ID formule manquant');
            }

            $patch = [];
            if (isset($input['name'])) $patch['name'] = trim($input['name']);
            if (isset($input['description'])) $patch['description'] = trim($input['description']);
            if (isset($input['price'])) $patch['price'] = normalizePrice($input['price']);
            if (isset($input['originalPrice'])) {
                $patch['originalPrice'] = $input['originalPrice'] !== '' ? normalizePrice($input['originalPrice']) : null;
            }
            if (isset($input['status'])) $patch['status'] = $input['status'];
            if (isset($input['includes'])) {
                $incData = is_string($input['includes']) ? json_decode($input['includes'], true) : $input['includes'];
                if (is_array($incData)) {
                    $patch['includes'] = $incData;
                }
            }
            error_log('[PRODUCTS API JSON MODE] patch construit: ' . json_encode($patch));

            // Calculer savings si on a price et originalPrice
            if (isset($patch['price']) || isset($patch['originalPrice'])) {
                $currentPrice = $patch['price'] ?? null;
                $currentOriginal = $patch['originalPrice'] ?? null;

                if ($currentPrice !== null && $currentOriginal !== null) {
                    $patch['savings'] = round($currentOriginal - $currentPrice, 2);
                    error_log('[PRODUCTS API JSON MODE] savings calculé: ' . $patch['savings']);
                }
            }

            // Gérer l'upload d'image
            error_log('[PRODUCTS API JSON MODE] Tentative upload image...');
            // Upload image formule désactivé temporairement (fonction handleFormuleImageUpload undefined)
            $imagePath = handleFormuleImageUpload($formuleId);
            error_log('[PRODUCTS API JSON MODE] imagePath: ' . ($imagePath ?? 'NULL'));

            if ($imagePath) {
                // Supprimer l'ancienne image si elle existe
                $oldImagePath = null;
                if (isset($runtime['customFormules'][$formuleId]['image'])) {
                    $oldImagePath = $runtime['customFormules'][$formuleId]['image'];
                } elseif (isset($runtime['formules'][$formuleId]['image'])) {
                    $oldImagePath = $runtime['formules'][$formuleId]['image'];
                }

                if ($oldImagePath && $oldImagePath !== $imagePath) {
                    $fullPath = SNACK_ROOT . '/' . ltrim($oldImagePath, '/');
                    if (file_exists($fullPath) && strpos($oldImagePath, '/formules/') !== false) {
                        @unlink($fullPath);
                        error_log('[PRODUCTS API JSON MODE] Ancienne image supprimée: ' . $fullPath);
                    }
                }

                $patch['image'] = $imagePath;
            }

            // Vérifier si c'est une formule custom ou du menu.json
            error_log('[PRODUCTS API JSON MODE] Mise à jour runtime...');
            if (isset($runtime['customFormules'][$formuleId])) {
                $runtime['customFormules'][$formuleId] = array_merge($runtime['customFormules'][$formuleId], $patch);
                error_log('[PRODUCTS API JSON MODE] customFormule mise à jour');
            } else {
                // C'est une formule du menu.json, on stocke le patch
                if (!isset($runtime['formules'])) $runtime['formules'] = [];
                if (!isset($runtime['formules'][$formuleId])) $runtime['formules'][$formuleId] = [];
                $runtime['formules'][$formuleId] = array_merge($runtime['formules'][$formuleId], $patch);
                error_log('[PRODUCTS API JSON MODE] formule runtime créée/mise à jour');
            }

            // ⚡ OPTIMISATION: Synchronisation groupée
            error_log('[PRODUCTS API JSON MODE] Sauvegarde runtime...');
            saveMenuRuntime($runtime);
            error_log('[PRODUCTS API JSON MODE] Sync vers menu.json...');
            syncFormulesToMenu($runtime);
            error_log('[PRODUCTS API JSON MODE] ✅ update_formule SUCCESS');

            jsonSuccess(['formule' => $patch]);

        } catch (Exception $e) {
            error_log('[PRODUCTS API JSON MODE] ❌ ERREUR update_formule: ' . $e->getMessage());
            error_log('[PRODUCTS API JSON MODE] ❌ Stack trace: ' . $e->getTraceAsString());
            jsonError('Erreur lors de la modification de la formule: ' . $e->getMessage());
        }
        break;

    case 'delete_formule':
        $formuleId = $input['formule_id'] ?? null;

        if (!$formuleId) {
            jsonError('ID formule manquant');
        }

        // Récupérer l'image de la formule avant suppression pour la nettoyer
        $imagePath = null;
        if (isset($runtime['customFormules'][$formuleId]['image'])) {
            $imagePath = $runtime['customFormules'][$formuleId]['image'];
        }

        // Supprimer l'image si elle existe
        if ($imagePath) {
            $fullPath = SNACK_ROOT . '/' . ltrim($imagePath, '/');
            if (file_exists($fullPath) && strpos($imagePath, '/formules/') !== false) {
                @unlink($fullPath);
            }
        }

        // Supprimer de customFormules si présent
        if (isset($runtime['customFormules'][$formuleId])) {
            unset($runtime['customFormules'][$formuleId]);
        }

        // Ajouter à la liste des supprimées
        if (!isset($runtime['deletedFormules'])) {
            $runtime['deletedFormules'] = [];
        }
        if (!in_array($formuleId, $runtime['deletedFormules'], true)) {
            $runtime['deletedFormules'][] = $formuleId;
        }

        // ⚡ OPTIMISATION: Synchronisation groupée
        saveMenuRuntime($runtime);
        syncFormulesToMenu($runtime);

        jsonSuccess();
        break;

    // ===== FEATURED PRODUCTS =====
    case 'update_featured':
        error_log('[PRODUCTS API JSON MODE] ========== update_featured START ==========');
        error_log('[PRODUCTS API JSON MODE] Input reçu: ' . json_encode($input));

        $featuredData = $input['featured'] ?? null;
        error_log('[PRODUCTS API JSON MODE] featuredData: ' . json_encode($featuredData));

        if (!$featuredData || !is_array($featuredData)) {
            error_log('[PRODUCTS API JSON MODE] ❌ Données featured invalides');
            jsonError('Données featured invalides');
        }

        $featured = [
            'enabled' => $featuredData['enabled'] ?? true,
            'title' => trim($featuredData['title'] ?? 'Sélection pour vous'),
            'subtitle' => trim($featuredData['subtitle'] ?? 'Nos produits les plus appréciés'),
            'items' => $featuredData['items'] ?? []
        ];
        error_log('[PRODUCTS API JSON MODE] featured construit: ' . json_encode($featured));

        // Sauvegarder dans menu.json
        $menuPath = getMenuJsonPath();
        error_log('[PRODUCTS API JSON MODE] menuPath: ' . $menuPath);

        if (file_exists($menuPath)) {
            error_log('[PRODUCTS API JSON MODE] ✅ menu.json existe');
            $menuData = json_decode(file_get_contents($menuPath), true);
            if ($menuData) {
                error_log('[PRODUCTS API JSON MODE] ✅ menu.json décodé correctement');
                error_log('[PRODUCTS API JSON MODE] AVANT écriture - featured ancien: ' . json_encode($menuData['featured'] ?? []));

                $menuData['featured'] = $featured;
                $written = file_put_contents($menuPath, json_encode($menuData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

                error_log('[PRODUCTS API JSON MODE] ✅ file_put_contents retourné: ' . ($written !== false ? $written . ' bytes' : 'ÉCHEC'));

                // Vérifier que le fichier a bien été modifié
                clearstatcache(true, $menuPath);
                $verif = json_decode(file_get_contents($menuPath), true);
                error_log('[PRODUCTS API JSON MODE] APRÈS écriture - featured nouveau: ' . json_encode($verif['featured'] ?? []));
            } else {
                error_log('[PRODUCTS API JSON MODE] ❌ Échec décodage menu.json');
            }
        } else {
            error_log('[PRODUCTS API JSON MODE] ❌ menu.json introuvable');
        }

        error_log('[PRODUCTS API JSON MODE] ========== update_featured END ==========');
        jsonSuccess(['featured' => $featured]);
        break;

    case 'add_patisserie_option':
        $name = trim($input['name'] ?? '');
        $price = intval($input['price'] ?? 0);

        if (!$name || $price <= 0) {
            jsonError('Nom et prix requis');
        }

        // Gérer l'upload d'image avec conversion WebP
        $imagePath = null;
        if (!empty($_FILES['image'])) {
            $file = $_FILES['image'];
            if (isset($file['tmp_name']) && is_uploaded_file($file['tmp_name'])) {
                $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                $mime = mime_content_type($file['tmp_name']) ?: '';
                if (!isset($allowed[$mime])) {
                    jsonError('Format image non supporté (jpg/png/webp)');
                }

                $uploadsDir = SNACK_ROOT . '/images/uploads';
                if (!is_dir($uploadsDir)) {
                    mkdir($uploadsDir, 0755, true);
                }

                // Conversion WebP
                $webpTempFile = null;
                try {
                    $webpTempFile = convertToOptimizedWebP($file['tmp_name'], 85, 800);
                    $newId = 'pat-' . strtolower(str_replace([' ', 'é', 'è', 'ê', 'à', 'ç'], ['', 'e', 'e', 'e', 'a', 'c'], $name));
                    $filename = $newId . '-' . bin2hex(random_bytes(4)) . '.webp';
                    $dest = $uploadsDir . '/' . $filename;

                    if (!rename($webpTempFile, $dest)) {
                        jsonError('Échec sauvegarde image WebP');
                    }
                    chmod($dest, 0644);
                    $imagePath = 'images/uploads/' . $filename;
                } catch (Exception $e) {
                    if ($webpTempFile && file_exists($webpTempFile)) {
                        @unlink($webpTempFile);
                    }
                    jsonError('Échec conversion WebP: ' . $e->getMessage());
                }
            }
        }

        // Trouver le produit pâtisserie dans menu.json
        $menuJsonPath = getMenuJsonPath();
        $menuData = json_decode(file_get_contents($menuJsonPath), true);

        $found = false;
        foreach ($menuData['menu']['categories'] as &$cat) {
            if ($cat['id'] === 'sucres-sales') {
                foreach ($cat['items'] as &$item) {
                    if ($item['id'] === 'patisserie') {
                        if (!isset($item['pâtisserieOptions'])) {
                            $item['pâtisserieOptions'] = [];
                        }
                        $newId = 'pat-' . strtolower(str_replace([' ', 'é', 'è', 'ê', 'à', 'ç'], ['', 'e', 'e', 'e', 'a', 'c'], $name));
                        $newOption = [
                            'id' => $newId,
                            'name' => $name,
                            'price' => $price
                        ];
                        if ($imagePath) {
                            $newOption['image'] = $imagePath;
                        }
                        $item['pâtisserieOptions'][] = $newOption;
                        $found = true;
                        break 2;
                    }
                }
            }
        }

        if (!$found) {
            jsonError('Produit pâtisserie introuvable');
        }

        file_put_contents($menuJsonPath, json_encode($menuData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        require_once __DIR__ . '/../sync-menu.php';
        syncMenuStatuses();
        jsonSuccess(['message' => 'Pâtisserie ajoutée']);
        break;

    case 'delete_patisserie_option':
        $index = intval($input['index'] ?? -1);
        if ($index < 0) jsonError('Index invalide');

        $menuJsonPath = getMenuJsonPath();
        $menuData = json_decode(file_get_contents($menuJsonPath), true);

        $found = false;
        foreach ($menuData['menu']['categories'] as &$cat) {
            if ($cat['id'] === 'sucres-sales') {
                foreach ($cat['items'] as &$item) {
                    if ($item['id'] === 'patisserie' && isset($item['pâtisserieOptions'][$index])) {
                        array_splice($item['pâtisserieOptions'], $index, 1);
                        $found = true;
                        break 2;
                    }
                }
            }
        }

        if (!$found) jsonError('Pâtisserie introuvable');

        file_put_contents($menuJsonPath, json_encode($menuData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        require_once __DIR__ . '/../sync-menu.php';
        syncMenuStatuses();
        jsonSuccess(['message' => 'Pâtisserie supprimée']);
        break;

    case 'add_beverage_option':
        $bevType = $input['beverage_type'] ?? '';
        $name = trim($input['name'] ?? '');
        $price = intval($input['price'] ?? 0);
        if (!$name || !$bevType || $price <= 0) jsonError('Type, nom et prix requis');

        // Upload d'image optionnel avec conversion WebP
        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['image']['tmp_name'];
            $origName = basename($_FILES['image']['name']);

            // Validation MIME type
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $mime = mime_content_type($tmpName) ?: '';
            if (!isset($allowed[$mime])) {
                jsonError('Format image invalide. Utilisez JPG, PNG ou WebP.');
            }
            if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
                jsonError('Image trop volumineuse (max 2MB).');
            }

            $uploadDir = SNACK_ROOT . '/assets/images/beverages/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            // Conversion WebP
            $webpTempFile = null;
            try {
                $webpTempFile = convertToOptimizedWebP($tmpName, 85, 800);
                $safeName = preg_replace('/[^a-z0-9_-]/i', '', pathinfo($origName, PATHINFO_FILENAME));
                $newName = $safeName . '_' . time() . '.webp';
                $targetPath = $uploadDir . $newName;

                if (!rename($webpTempFile, $targetPath)) {
                    jsonError('Échec sauvegarde image WebP.');
                }
                chmod($targetPath, 0644);
                $imagePath = 'assets/images/beverages/' . $newName;
            } catch (Exception $e) {
                if ($webpTempFile && file_exists($webpTempFile)) {
                    @unlink($webpTempFile);
                }
                jsonError('Échec conversion WebP: ' . $e->getMessage());
            }
        }

        $menuJsonPath = getMenuJsonPath();
        $menuData = json_decode(file_get_contents($menuJsonPath), true);

        $found = false;
        foreach ($menuData['menu']['categories'] as &$cat) {
            foreach ($cat['items'] as &$item) {
                if ($item['id'] === $bevType) {
                    if (!isset($item['beverageOptions'])) {
                        $item['beverageOptions'] = [];
                    }
                    $newId = $bevType . '-' . strtolower(str_replace([' ', 'é', 'è', 'ê'], ['', 'e', 'e', 'e'], $name));
                    $newOption = [
                        'id' => $newId,
                        'name' => $name,
                        'price' => $price
                    ];
                    if ($imagePath) {
                        $newOption['image'] = $imagePath;
                    }
                    $item['beverageOptions'][] = $newOption;
                    $found = true;
                    break 2;
                }
            }
        }

        if (!$found) jsonError('Produit boisson introuvable');

        file_put_contents($menuJsonPath, json_encode($menuData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        require_once __DIR__ . '/../sync-menu.php';
        syncMenuStatuses();
        jsonSuccess(['message' => 'Option ajoutée']);
        break;

    case 'delete_beverage_option':
        $bevType = $input['beverage_type'] ?? '';
        $index = intval($input['index'] ?? -1);
        if ($index < 0 || !$bevType) jsonError('Paramètres invalides');

        $menuJsonPath = getMenuJsonPath();
        $menuData = json_decode(file_get_contents($menuJsonPath), true);

        $found = false;
        foreach ($menuData['menu']['categories'] as &$cat) {
            foreach ($cat['items'] as &$item) {
                if ($item['id'] === $bevType && isset($item['beverageOptions'][$index])) {
                    array_splice($item['beverageOptions'], $index, 1);
                    $found = true;
                    break 2;
                }
            }
        }

        if (!$found) jsonError('Option introuvable');

        file_put_contents($menuJsonPath, json_encode($menuData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        require_once __DIR__ . '/../sync-menu.php';
        syncMenuStatuses();
        jsonSuccess(['message' => 'Option supprimée']);
        break;

    case 'update_patisserie_option_status':
        $index = intval($input['index'] ?? -1);
        $status = $input['status'] ?? 'available';
        if ($index < 0 || !in_array($status, ['available', 'unavailable'])) {
            jsonError('Paramètres invalides');
        }

        $menuJsonPath = getMenuJsonPath();
        $menuData = json_decode(file_get_contents($menuJsonPath), true);

        $found = false;
        foreach ($menuData['menu']['categories'] as &$cat) {
            if ($cat['id'] === 'sucres-sales') {
                foreach ($cat['items'] as &$item) {
                    if ($item['id'] === 'patisserie' && isset($item['pâtisserieOptions'][$index])) {
                        $item['pâtisserieOptions'][$index]['status'] = $status;
                        $found = true;
                        break 2;
                    }
                }
            }
        }

        if (!$found) jsonError('Pâtisserie introuvable');

        file_put_contents($menuJsonPath, json_encode($menuData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        require_once __DIR__ . '/../sync-menu.php';
        syncMenuStatuses();
        jsonSuccess(['message' => 'Statut modifié']);
        break;

    case 'update_beverage_option_status':
        $bevType = $input['beverage_type'] ?? '';
        $index = intval($input['index'] ?? -1);
        $status = $input['status'] ?? 'available';
        if ($index < 0 || !$bevType || !in_array($status, ['available', 'unavailable'])) {
            jsonError('Paramètres invalides');
        }

        $menuJsonPath = getMenuJsonPath();
        $menuData = json_decode(file_get_contents($menuJsonPath), true);

        $found = false;
        foreach ($menuData['menu']['categories'] as &$cat) {
            foreach ($cat['items'] as &$item) {
                if ($item['id'] === $bevType && isset($item['beverageOptions'][$index])) {
                    $item['beverageOptions'][$index]['status'] = $status;
                    $found = true;
                    break 2;
                }
            }
        }

        if (!$found) jsonError('Option introuvable');

        file_put_contents($menuJsonPath, json_encode($menuData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        require_once __DIR__ . '/../sync-menu.php';
        syncMenuStatuses();
        jsonSuccess(['message' => 'Statut modifié']);
        break;

    default:
        jsonError('Action inconnue');
}

} // Fin du if (!$useMySQL) - Fallback JSON Mode
