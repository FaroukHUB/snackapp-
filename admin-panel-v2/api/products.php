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

    // ✅ SÉCURITÉ: Nom de fichier sécurisé avec vérification d'extension
    $filename = $baseId . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];

    // Vérifier qu'il n'y a pas d'extensions dangereuses cachées
    if (preg_match('/\.(php|phtml|php3|php4|php5|phps|phar|htaccess|exe|sh|bat|cmd)/i', $filename)) {
        jsonError('Extension de fichier non autorisée détectée');
    }

    $dest = $uploadsDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        jsonError('Échec sauvegarde image');
    }

    // ✅ SÉCURITÉ: Permissions strictes sur le fichier uploadé
    chmod($dest, 0644);

    return 'images/uploads/' . $filename;
}

/* =========================
   MODE MySQL ou JSON
   ========================= */

// Pour la gestion des produits, on utilise toujours le mode JSON
// car le menu vient du fichier config, pas de la base de données
$useMySQL = false;

/* =========================
   GET: Retourner le menu complet
   ========================= */

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // GET est public (le front en a besoin)
    // Charger directement depuis menu.json (déjà formaté)
    $menuJsonPath = SNACK_ROOT . '/config/menu.json';

    if (file_exists($menuJsonPath)) {
        $menuData = json_decode(file_get_contents($menuJsonPath), true);

        if ($menuData) {
            require_once __DIR__ . '/../config.php';
            $runtime = loadMenuRuntime();

            // Charger les suppléments depuis menu.json, pas le runtime
            $supplements = $menuData['supplements'] ?? [
                'catalog' => [],
                'defaultForCategories' => []
            ];

            // Merger avec les modifications du runtime
            if (!empty($runtime['supplements']['catalog'])) {
                foreach ($runtime['supplements']['catalog'] as $id => $data) {
                    if (isset($supplements['catalog'][$id])) {
                        $supplements['catalog'][$id] = array_merge($supplements['catalog'][$id], $data);
                    } else {
                        $supplements['catalog'][$id] = $data;
                    }
                }
            }

            // Charger les formules (depuis menu.json, avec modifications runtime)
            $formules = $menuData['formules'] ?? [];

            // Appliquer les modifications du runtime aux formules
            if (!empty($runtime['formules'])) {
                foreach ($runtime['formules'] as $id => $patch) {
                    foreach ($formules as &$f) {
                        if ($f['id'] === $id) {
                            $f = array_merge($f, $patch);
                            break;
                        }
                    }
                }
            }

            // Ajouter les formules custom (éviter les doublons)
            if (!empty($runtime['customFormules'])) {
                // Créer un index des IDs existants pour recherche rapide
                $existingIds = array_column($formules, 'id');

                foreach ($runtime['customFormules'] as $f) {
                    // Vérifier si elle n'existe pas déjà
                    if (!in_array($f['id'], $existingIds, true)) {
                        $formules[] = $f;
                        $existingIds[] = $f['id']; // Ajouter à l'index pour éviter duplicatas
                    } else {
                        // Si elle existe, la mettre à jour avec les données du runtime
                        foreach ($formules as &$existing) {
                            if ($existing['id'] === $f['id']) {
                                $existing = array_merge($existing, $f);
                                break;
                            }
                        }
                    }
                }
            }

            // Filtrer les formules supprimées
            if (!empty($runtime['deletedFormules'])) {
                $formules = array_filter($formules, fn($f) => !in_array($f['id'], $runtime['deletedFormules'], true));
                $formules = array_values($formules);
            }

            // Charger le menu et merger avec les customProducts et customCategories du runtime
            $menu = $menuData['menu'] ?? ['categories' => []];

            // Ajouter les catégories custom du runtime
            if (!empty($runtime['customCategories'])) {
                foreach ($runtime['customCategories'] as $customCat) {
                    // Vérifier si la catégorie n'existe pas déjà
                    $exists = false;
                    foreach ($menu['categories'] as $cat) {
                        if ($cat['id'] === $customCat['id']) {
                            $exists = true;
                            break;
                        }
                    }
                    if (!$exists) {
                        $menu['categories'][] = $customCat;
                    }
                }
            }

            // Ajouter les produits custom du runtime aux catégories
            if (!empty($runtime['customProducts'])) {
                foreach ($runtime['customProducts'] as $customProd) {
                    $categoryId = $customProd['categoryId'] ?? null;
                    if ($categoryId) {
                        foreach ($menu['categories'] as &$cat) {
                            if ($cat['id'] === $categoryId) {
                                // Vérifier si le produit n'existe pas déjà
                                $exists = false;
                                foreach ($cat['items'] as $item) {
                                    if ($item['id'] === $customProd['id']) {
                                        $exists = true;
                                        break;
                                    }
                                }
                                if (!$exists) {
                                    $cat['items'][] = $customProd;
                                }
                                break;
                            }
                        }
                    }
                }
            }

            // Appliquer les modifications du runtime aux produits existants
            if (!empty($runtime['products'])) {
                foreach ($menu['categories'] as &$cat) {
                    foreach ($cat['items'] as &$item) {
                        if (isset($runtime['products'][$item['id']])) {
                            $item = array_merge($item, $runtime['products'][$item['id']]);
                        }
                    }
                }
            }

            // Filtrer les produits supprimés
            if (!empty($runtime['deletedProducts'])) {
                foreach ($menu['categories'] as &$cat) {
                    $cat['items'] = array_filter($cat['items'], fn($item) => !in_array($item['id'], $runtime['deletedProducts'], true));
                    $cat['items'] = array_values($cat['items']);
                }
            }

            jsonSuccess([
                'menu' => $menu,
                'supplements' => $supplements,
                'formules' => $formules,
                'featured' => $menuData['featured'] ?? [
                    'enabled' => true,
                    'title' => 'Sélection pour vous',
                    'subtitle' => 'Nos produits les plus appréciés',
                    'items' => []
                ],
                'categoryIcons' => $menuData['categoryIcons'] ?? []
            ]);
        }
    }

    jsonError('Impossible de charger le menu');
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

// 🔒 SÉCURITÉ: Validation CSRF sans logs sensibles
if (!$csrfToken) {
    jsonError('Token CSRF manquant', 403);
}

if (!validateCsrfToken($csrfToken)) {
    error_log('[SÉCURITÉ] Tentative CSRF bloquée - IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    jsonError('Token CSRF invalide - Veuillez recharger la page', 403);
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

    switch ($action) {

        case 'add_category':
            $name = trim((string)($input['name'] ?? ''));
            $description = trim((string)($input['description'] ?? ''));

            if ($name === '') {
                jsonError('Nom manquant');
            }

            try {
                $id = MenuRepository::addCategory(SNACK_RESTAURANT_ID, $name, $description);
                jsonSuccess(['category' => ['id' => $id, 'name' => $name]]);
            } catch (Exception $e) {
                jsonError($e->getMessage());
            }
            break;

        case 'edit_category':
            $categoryId = (string)($input['category_id'] ?? '');
            $name = trim((string)($input['name'] ?? ''));
            $description = trim((string)($input['description'] ?? ''));

            if ($categoryId === '' || $name === '') {
                jsonError('Paramètres manquants');
            }

            try {
                MenuRepository::updateCategory(SNACK_RESTAURANT_ID, $categoryId, $name, $description);
                jsonSuccess(['category' => ['id' => $categoryId, 'name' => $name]]);
            } catch (Exception $e) {
                jsonError($e->getMessage());
            }
            break;

        case 'delete_category':
            $categoryId = (string)($input['category_id'] ?? '');

            if ($categoryId === '') {
                jsonError('ID manquant');
            }

            try {
                MenuRepository::deleteCategory(SNACK_RESTAURANT_ID, $categoryId);
                jsonSuccess(['deleted' => true]);
            } catch (Exception $e) {
                jsonError($e->getMessage());
            }
            break;

        case 'add_product':
            $categoryId = (int)($input['category_id'] ?? 0);
            $name = trim((string)($input['name'] ?? ''));
            $priceSolo = (float)($input['priceSolo'] ?? 0);

            if (!$categoryId || $name === '' || $priceSolo <= 0) {
                jsonError('Champs invalides');
            }

            $baseSlug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name));
            $imagePath = handleImageUpload($baseSlug);

            $supplements = [];
            if (isset($input['supplements'])) {
                $supData = is_string($input['supplements']) ? json_decode($input['supplements'], true) : $input['supplements'];
                if (is_array($supData)) {
                    $supplements = array_map('intval', $supData);
                }
            }

            try {
                $id = MenuRepository::addProduct(SNACK_RESTAURANT_ID, [
                    'category_id' => $categoryId,
                    'name' => $name,
                    'description' => $input['description'] ?? '',
                    'priceSolo' => $priceSolo,
                    'priceMenu' => isset($input['priceMenu']) && $input['priceMenu'] !== '' ? (float)$input['priceMenu'] : null,
                    'image' => $imagePath,
                    'supplements' => $supplements
                ]);
                jsonSuccess(['product' => ['id' => $id, 'category_id' => $categoryId]]);
            } catch (Exception $e) {
                jsonError($e->getMessage());
            }
            break;

        case 'update_product':
            $productId = (int)($input['product_id'] ?? 0);

            if (!$productId) {
                jsonError('ID produit manquant');
            }

            $data = [];
            if (isset($input['name'])) $data['name'] = trim($input['name']);
            if (isset($input['description'])) $data['description'] = $input['description'];
            if (isset($input['priceSolo'])) $data['priceSolo'] = (float)$input['priceSolo'];
            if (isset($input['priceMenu'])) $data['priceMenu'] = $input['priceMenu'] !== '' ? (float)$input['priceMenu'] : null;
            if (isset($input['status'])) $data['status'] = $input['status'];
            if (isset($input['supplements'])) $data['supplements'] = is_array($input['supplements']) ? array_map('intval', $input['supplements']) : [];

            try {
                MenuRepository::updateProduct($productId, $data);
                jsonSuccess(['product' => $data]);
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
                MenuRepository::updateProduct($productId, ['status' => $status]);
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
                jsonSuccess();
            } catch (Exception $e) {
                jsonError($e->getMessage());
            }
            break;

        case 'add_supplement':
            $name = trim((string)($input['name'] ?? ''));
            $price = (float)($input['price'] ?? 0);

            if ($name === '' || $price < 0) {
                jsonError('Nom ou prix invalide');
            }

            try {
                $id = MenuRepository::addSupplement(SNACK_RESTAURANT_ID, $name, $price);
                jsonSuccess(['supplement' => ['id' => $id, 'name' => $name, 'price' => $price]]);
            } catch (Exception $e) {
                jsonError($e->getMessage());
            }
            break;

        case 'update_supplement':
            $id = (int)($input['supplement_id'] ?? 0);

            if (!$id) {
                jsonError('ID supplément manquant');
            }

            $data = [];
            if (isset($input['name'])) $data['name'] = trim($input['name']);
            if (isset($input['price'])) $data['price'] = (float)$input['price'];
            if (isset($input['status'])) $data['status'] = $input['status'];

            try {
                MenuRepository::updateSupplement($id, $data);
                jsonSuccess(['supplement' => $data]);
            } catch (Exception $e) {
                jsonError($e->getMessage());
            }
            break;

        case 'delete_supplement':
            $id = (int)($input['supplement_id'] ?? 0);

            if (!$id) {
                jsonError('ID supplément manquant');
            }

            try {
                MenuRepository::deleteSupplement($id);
                jsonSuccess();
            } catch (Exception $e) {
                jsonError($e->getMessage());
            }
            break;

        default:
            jsonError('Action inconnue');
    }
}

/* ===== Fallback JSON Mode ===== */
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

        saveMenuRuntime($runtime);

        // Sauvegarder l'icône dans menu.json
        $menuPath = SNACK_ROOT . '/config/menu.json';
        if (file_exists($menuPath)) {
            // 🔒 IMPORTANT: Vider le cache de stat pour lire le fichier FRAIS généré par saveMenuRuntime
            clearstatcache(true, $menuPath);
            $menuData = json_decode(file_get_contents($menuPath), true);
            if ($menuData) {
                if (!isset($menuData['categoryIcons'])) {
                    $menuData['categoryIcons'] = [];
                }
                $menuData['categoryIcons'][$id] = $icon;
                // ⚠️ IMPORTANT: Utiliser les MÊMES flags que generatePublicMenuJson()
                file_put_contents($menuPath, json_encode($menuData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

                // 🔄 REGÉNÉRER pour être 100% sûr que tout est sync
                require_once __DIR__ . '/../config.php';
                generatePublicMenuJson(loadMenuRuntime());
            }
        }

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

        $runtime = loadMenuRuntime();
        if (!isset($runtime['customCategories'][$categoryId])) {
            jsonError('Catégorie introuvable');
        }

        $runtime['customCategories'][$categoryId]['name'] = $name;
        $runtime['customCategories'][$categoryId]['description'] = $description;

        // Réassigner les suppléments si le flavor est fourni
        if ($flavor !== '') {
            $supplementsSales = [
                'sup-mix-fromages', 'sup-cheddar', 'sup-camembert',
                'sup-chakchouka', 'sup-pomme-terre', 'sup-oignons-confits',
                'sup-oeuf', 'sup-viande-hachee', 'sup-escalope-poulet', 'sup-jambon'
            ];

            $supplementsSucres = [
                'sup-nutella', 'sup-chocolat', 'sup-confiture', 'sup-creme-noisette',
                'sup-beurre-cacahuete', 'sup-miel', 'sup-caramel', 'sup-speculoos',
                'sup-oursons', 'sup-smarties', 'sup-mnm', 'sup-kitkat',
                'sup-maltesers', 'sup-kinder', 'sup-oreo', 'sup-banane',
                'sup-fraise', 'sup-pomme', 'sup-kiwi', 'sup-ananas',
                'sup-myrtilles', 'sup-framboises', 'sup-noix-coco', 'sup-amandes',
                'sup-noisettes', 'sup-noix', 'sup-chantilly'
            ];

            if ($flavor === 'sale') {
                $runtime['supplements']['defaultForCategories'][$categoryId] = $supplementsSales;
            } else {
                $runtime['supplements']['defaultForCategories'][$categoryId] = $supplementsSucres;
            }
        }

        saveMenuRuntime($runtime);

        // Mettre à jour l'icône dans menu.json
        $menuPath = SNACK_ROOT . '/config/menu.json';
        if (file_exists($menuPath)) {
            clearstatcache(true, $menuPath);
            $menuData = json_decode(file_get_contents($menuPath), true);
            if ($menuData) {
                if (!isset($menuData['categoryIcons'])) {
                    $menuData['categoryIcons'] = [];
                }
                $menuData['categoryIcons'][$categoryId] = $icon;
                file_put_contents($menuPath, json_encode($menuData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

                require_once __DIR__ . '/../config.php';
                generatePublicMenuJson(loadMenuRuntime());
            }
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
        $menuJsonPath = SNACK_ROOT . '/config/menu.json';
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

        saveMenuRuntime($runtime);

        // Supprimer l'icône du menu.json
        $menuPath = SNACK_ROOT . '/config/menu.json';
        if (file_exists($menuPath)) {
            clearstatcache(true, $menuPath);
            $menuData = json_decode(file_get_contents($menuPath), true);
            if ($menuData && isset($menuData['categoryIcons'][$categoryId])) {
                unset($menuData['categoryIcons'][$categoryId]);
                file_put_contents($menuPath, json_encode($menuData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

                require_once __DIR__ . '/../config.php';
                generatePublicMenuJson(loadMenuRuntime());
            }
        }

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

        $runtime['customProducts'][$productId] = [
            'id' => $productId,
            'categoryId' => $categoryId,
            'name' => $name,
            'description' => $input['description'] ?? '',
            'priceSolo' => $priceSolo,
            'priceMenu' => isset($input['priceMenu']) && $input['priceMenu'] !== '' ? (float)$input['priceMenu'] : null,
            'badge' => isset($input['badge']) && $input['badge'] !== '' ? trim($input['badge']) : null,
            'image' => $imagePath,
            'status' => 'available',
            'supplements' => $supplements
        ];

        saveMenuRuntime($runtime);
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
        if (isset($input['priceSolo'])) $patch['priceSolo'] = (float)$input['priceSolo'];
        if (isset($input['priceMenu'])) $patch['priceMenu'] = $input['priceMenu'] !== '' ? (float)$input['priceMenu'] : null;
        if (isset($input['badge'])) $patch['badge'] = $input['badge'] !== '' ? trim($input['badge']) : null;
        if (isset($input['status'])) $patch['status'] = $input['status'];

        // Gérer les suppléments (peuvent être une chaîne JSON depuis FormData)
        if (isset($input['supplements'])) {
            $sups = $input['supplements'];
            if (is_string($sups)) {
                $sups = json_decode($sups, true) ?? [];
            }
            $patch['supplements'] = is_array($sups) ? $sups : [];
        }

        // Gérer l'upload d'image
        $imagePath = handleImageUpload($productId);
        if ($imagePath) {
            $patch['image'] = $imagePath;
        }

        if (isset($runtime['customProducts'][$productId])) {
            $runtime['customProducts'][$productId] = array_merge($runtime['customProducts'][$productId], $patch);
        } else {
            if (!isset($runtime['products'])) $runtime['products'] = [];
            if (!isset($runtime['products'][$productId])) $runtime['products'][$productId] = [];
            $runtime['products'][$productId] = array_merge($runtime['products'][$productId], $patch);
        }

        saveMenuRuntime($runtime);

        // Sync vers menu.json pour le site public
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
        if (!isset($runtime['products'][$productId])) $runtime['products'][$productId] = [];

        // Mettre à jour le statut
        $runtime['products'][$productId]['status'] = $newStatus;
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

        saveMenuRuntime($runtime);

        // Sync vers menu.json pour le site public
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

        // Déterminer le flavor basé sur la catégorie
        $saledCategories = ['fromage', 'legume', 'viande', 'autre'];
        $sucreCategories = ['base', 'croquant', 'fruit', 'prime'];
        $flavor = in_array($category, $saledCategories) ? 'sale' : 'sucre';

        $runtime['supplements']['catalog'][$id] = [
            'id' => $id,
            'name' => $name,
            'price' => $price,
            'status' => 'available',
            'category' => $category,
            'flavor' => $flavor
        ];

        // ✅ Ajouter automatiquement le supplément aux catégories de produits concernées
        if (!isset($runtime['supplements']['defaultForCategories'])) {
            $runtime['supplements']['defaultForCategories'] = [];
        }

        // Déterminer les catégories de produits selon le type de supplément
        $saledCategories = ['fromage', 'legume', 'viande', 'autre'];
        $sucreCategories = ['base', 'croquant', 'fruit', 'prime'];

        $productCategories = [];
        if (in_array($category, $saledCategories)) {
            // Suppléments salés → crêpes salées
            $productCategories = ['crepes-salees-signature'];
        } elseif (in_array($category, $sucreCategories)) {
            // Suppléments sucrés → crêpes sucrées, gaufres, bubble waffle
            $productCategories = ['crepes-sucrees', 'gaufres', 'bubble-waffle'];
        }

        // Ajouter le supplément à chaque catégorie de produits
        foreach ($productCategories as $catId) {
            if (!isset($runtime['supplements']['defaultForCategories'][$catId])) {
                $runtime['supplements']['defaultForCategories'][$catId] = [];
            }
            // Ajouter seulement si pas déjà présent
            if (!in_array($id, $runtime['supplements']['defaultForCategories'][$catId], true)) {
                $runtime['supplements']['defaultForCategories'][$catId][] = $id;
            }
        }

        saveMenuRuntime($runtime);

        // Sync vers menu.json pour le site public
        require_once __DIR__ . '/../sync-menu.php';
        syncMenuStatuses();

        jsonSuccess(['supplement' => ['id' => $id, 'name' => $name, 'price' => $price, 'category' => $category, 'flavor' => $flavor]]);
        break;

    case 'update_supplement':
        $id = $input['supplement_id'] ?? null;

        // Si le supplément n'existe pas dans runtime, le copier depuis menu.json
        if (!isset($runtime['supplements']['catalog'][$id])) {
            $menuJsonPath = SNACK_ROOT . '/config/menu.json';
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
        if (isset($input['price'])) $runtime['supplements']['catalog'][$id]['price'] = (float)$input['price'];
        if (isset($input['status'])) $runtime['supplements']['catalog'][$id]['status'] = $input['status'];
        if (isset($input['category'])) {
            $runtime['supplements']['catalog'][$id]['category'] = $input['category'];
            // Mettre à jour le flavor basé sur la nouvelle catégorie
            $saledCategories = ['fromage', 'legume', 'viande', 'autre'];
            $runtime['supplements']['catalog'][$id]['flavor'] = in_array($input['category'], $saledCategories) ? 'sale' : 'sucre';
        }

        saveMenuRuntime($runtime);

        // Sync vers menu.json pour le site public
        require_once __DIR__ . '/../sync-menu.php';
        syncMenuStatuses();

        jsonSuccess(['supplement' => $runtime['supplements']['catalog'][$id]]);
        break;

    case 'delete_supplement':
        $id = $input['supplement_id'] ?? null;

        // Si le supplément n'existe pas dans runtime, le copier depuis menu.json
        if (!isset($runtime['supplements']['catalog'][$id])) {
            $menuJsonPath = SNACK_ROOT . '/config/menu.json';
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

        saveMenuRuntime($runtime);

        // Sync vers menu.json pour supprimer le supplément du site public
        require_once __DIR__ . '/../sync-menu.php';
        syncMenuStatuses();

        jsonSuccess();
        break;

    // ===== FORMULES =====
    case 'add_formule':
        $name = trim((string)($input['name'] ?? ''));
        $description = trim((string)($input['description'] ?? ''));
        $price = (float)($input['price'] ?? 0);
        $originalPrice = isset($input['originalPrice']) && $input['originalPrice'] !== '' ? (float)$input['originalPrice'] : null;
        $badge = isset($input['badge']) && $input['badge'] !== '' ? trim($input['badge']) : null;
        $status = $input['status'] ?? 'available';

        error_log("[PRODUCTS API] add_formule - name: {$name}, price: {$price}");

        if ($name === '' || $price <= 0) {
            error_log('[PRODUCTS API] ❌ Nom ou prix invalide');
            jsonError('Nom et prix requis');
        }

        // Générer un ID unique
        $baseId = 'formule-' . strtolower(preg_replace('/[^a-z0-9]+/', '-', $name));
        $baseId = trim($baseId, '-');

        // Collecter TOUS les IDs existants (runtime + menu.json)
        $existingIds = [];

        // IDs du runtime customFormules
        if (!empty($runtime['customFormules'])) {
            $existingIds = array_merge($existingIds, array_keys($runtime['customFormules']));
        }

        // IDs du menu.json
        $menuData = json_decode(file_get_contents(SNACK_ROOT . '/config/menu.json'), true);
        foreach (($menuData['formules'] ?? []) as $f) {
            if (isset($f['id'])) {
                $existingIds[] = $f['id'];
            }
        }

        error_log("[PRODUCTS API] IDs existants: " . json_encode($existingIds));

        // Générer un ID unique
        $id = $baseId;
        $i = 2;
        while (in_array($id, $existingIds, true)) {
            $id = $baseId . '-' . $i;
            $i++;
        }

        error_log("[PRODUCTS API] ✅ ID généré: {$id}");

        // Gérer l'upload d'image
        $imagePath = handleFormuleImageUpload($id);

        // Décoder les includes
        $includes = [];
        if (isset($input['includes'])) {
            $incData = is_string($input['includes']) ? json_decode($input['includes'], true) : $input['includes'];
            if (is_array($incData)) {
                $includes = $incData;
            }
        }

        // Calculer l'économie
        $savings = $originalPrice !== null ? round($originalPrice - $price, 2) : null;

        $formule = [
            'id' => $id,
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'originalPrice' => $originalPrice,
            'savings' => $savings,
            'badge' => $badge,
            'image' => $imagePath,
            'includes' => $includes,
            'status' => $status
        ];

        if (!isset($runtime['customFormules'])) {
            $runtime['customFormules'] = [];
        }
        $runtime['customFormules'][$id] = $formule;

        error_log("[PRODUCTS API] ✅ Formule ajoutée au runtime: " . json_encode($formule));

        $saved = saveMenuRuntime($runtime);
        error_log("[PRODUCTS API] Runtime sauvegardé: " . ($saved ? 'OUI' : 'NON'));

        // Sync vers menu.json
        syncFormulesToMenu($runtime);
        error_log("[PRODUCTS API] ✅ Formule synchronisée vers menu.json");

        jsonSuccess(['formule' => $formule]);
        break;

    case 'update_formule':
        $formuleId = $input['formule_id'] ?? null;

        if (!$formuleId) {
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
            if (is_array($incData)) {
                $patch['includes'] = $incData;
            }
        }

        // Calculer savings si on a price et originalPrice
        if (isset($patch['price']) || isset($patch['originalPrice'])) {
            $currentPrice = $patch['price'] ?? null;
            $currentOriginal = $patch['originalPrice'] ?? null;

            if ($currentPrice !== null && $currentOriginal !== null) {
                $patch['savings'] = round($currentOriginal - $currentPrice, 2);
            }
        }

        // Gérer l'upload d'image
        $imagePath = handleFormuleImageUpload($formuleId);
        if ($imagePath) {
            $patch['image'] = $imagePath;
        }

        // Vérifier si c'est une formule custom ou du menu.json
        if (isset($runtime['customFormules'][$formuleId])) {
            $runtime['customFormules'][$formuleId] = array_merge($runtime['customFormules'][$formuleId], $patch);
        } else {
            // C'est une formule du menu.json, on stocke le patch
            if (!isset($runtime['formules'])) $runtime['formules'] = [];
            if (!isset($runtime['formules'][$formuleId])) $runtime['formules'][$formuleId] = [];
            $runtime['formules'][$formuleId] = array_merge($runtime['formules'][$formuleId], $patch);
        }

        saveMenuRuntime($runtime);

        // Sync vers menu.json
        syncFormulesToMenu($runtime);

        jsonSuccess(['formule' => $patch]);
        break;

    case 'delete_formule':
        $formuleId = $input['formule_id'] ?? null;

        if (!$formuleId) {
            jsonError('ID formule manquant');
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

        saveMenuRuntime($runtime);

        // Sync vers menu.json
        syncFormulesToMenu($runtime);

        jsonSuccess();
        break;

    // ===== FEATURED PRODUCTS =====
    case 'update_featured':
        $featuredData = $input['featured'] ?? null;

        if (!$featuredData || !is_array($featuredData)) {
            jsonError('Données featured invalides');
        }

        $featured = [
            'enabled' => $featuredData['enabled'] ?? true,
            'title' => trim($featuredData['title'] ?? 'Sélection pour vous'),
            'subtitle' => trim($featuredData['subtitle'] ?? 'Nos produits les plus appréciés'),
            'items' => $featuredData['items'] ?? []
        ];

        // Sauvegarder dans menu.json
        $menuPath = SNACK_ROOT . '/config/menu.json';
        if (file_exists($menuPath)) {
            $menuData = json_decode(file_get_contents($menuPath), true);
            if ($menuData) {
                $menuData['featured'] = $featured;
                file_put_contents($menuPath, json_encode($menuData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
        }

        jsonSuccess(['featured' => $featured]);
        break;

    case 'add_patisserie_option':
        $name = trim($input['name'] ?? '');
        $price = intval($input['price'] ?? 0);

        if (!$name || $price <= 0) {
            jsonError('Nom et prix requis');
        }

        // Gérer l'upload d'image
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
                    // 🔒 SÉCURITÉ: Permissions 0755 (pas writable par group)
                    mkdir($uploadsDir, 0755, true);
                }

                $newId = 'pat-' . strtolower(str_replace([' ', 'é', 'è', 'ê', 'à', 'ç'], ['', 'e', 'e', 'e', 'a', 'c'], $name));
                $filename = $newId . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
                $dest = $uploadsDir . '/' . $filename;

                if (!move_uploaded_file($file['tmp_name'], $dest)) {
                    jsonError('Échec sauvegarde image');
                }

                $imagePath = 'images/uploads/' . $filename;
            }
        }

        // Trouver le produit pâtisserie dans menu.json
        $menuJsonPath = SNACK_ROOT . '/config/menu.json';
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

        $menuJsonPath = SNACK_ROOT . '/config/menu.json';
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

        $menuJsonPath = SNACK_ROOT . '/config/menu.json';
        $menuData = json_decode(file_get_contents($menuJsonPath), true);

        $found = false;
        foreach ($menuData['menu']['categories'] as &$cat) {
            foreach ($cat['items'] as &$item) {
                if ($item['id'] === $bevType) {
                    if (!isset($item['beverageOptions'])) {
                        $item['beverageOptions'] = [];
                    }
                    $newId = $bevType . '-' . strtolower(str_replace([' ', 'é', 'è', 'ê'], ['', 'e', 'e', 'e'], $name));
                    $item['beverageOptions'][] = [
                        'id' => $newId,
                        'name' => $name,
                        'price' => $price
                    ];
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

        $menuJsonPath = SNACK_ROOT . '/config/menu.json';
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

        $menuJsonPath = SNACK_ROOT . '/config/menu.json';
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

        $menuJsonPath = SNACK_ROOT . '/config/menu.json';
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

    // ✅ SÉCURITÉ: Nom de fichier sécurisé
    $filename = $baseId . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];

    // Vérifier qu'il n'y a pas d'extensions dangereuses
    if (preg_match('/\.(php|phtml|php3|php4|php5|phps|phar|htaccess|exe|sh|bat|cmd)/i', $filename)) {
        jsonError('Extension de fichier non autorisée détectée');
    }

    $dest = $uploadsDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        jsonError('Échec sauvegarde image formule');
    }

    // ✅ SÉCURITÉ: Permissions strictes
    chmod($dest, 0644);

    return 'images/formules/' . $filename;
}

/* =========================
   HELPER: Sync formules to menu.json
   ========================= */
function syncFormulesToMenu(array $runtime): void {
    $menuPath = SNACK_ROOT . '/config/menu.json';
    if (!file_exists($menuPath)) return;

    $menuData = json_decode(file_get_contents($menuPath), true);
    if (!$menuData) return;

    $formules = $menuData['formules'] ?? [];

    // Appliquer les patches
    if (!empty($runtime['formules'])) {
        foreach ($runtime['formules'] as $id => $patch) {
            foreach ($formules as &$f) {
                if ($f['id'] === $id) {
                    $f = array_merge($f, $patch);
                    break;
                }
            }
        }
    }

    // Ajouter les formules custom (éviter les doublons)
    if (!empty($runtime['customFormules'])) {
        // Créer un index des IDs existants pour recherche rapide
        $existingIds = array_column($formules, 'id');

        foreach ($runtime['customFormules'] as $f) {
            // Vérifier si elle n'existe pas déjà
            if (!in_array($f['id'], $existingIds, true)) {
                $formules[] = $f;
                $existingIds[] = $f['id']; // Ajouter à l'index pour éviter duplicatas
            } else {
                // Si elle existe, la mettre à jour avec les données du runtime
                foreach ($formules as &$existing) {
                    if ($existing['id'] === $f['id']) {
                        $existing = array_merge($existing, $f);
                        break;
                    }
                }
            }
        }
    }

    // Supprimer les formules marquées comme supprimées
    if (!empty($runtime['deletedFormules'])) {
        $formules = array_filter($formules, fn($f) => !in_array($f['id'], $runtime['deletedFormules'], true));
        $formules = array_values($formules);
    }

    $menuData['formules'] = $formules;

    file_put_contents($menuPath, json_encode($menuData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}
