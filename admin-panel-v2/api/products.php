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

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = mime_content_type($file['tmp_name']) ?: '';
    if (!isset($allowed[$mime])) {
        jsonError('Format image non supporté (jpg/png/webp)');
    }

    $uploadsDir = SNACK_ROOT . '/images/uploads';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0775, true);
    }

    $filename = $baseId . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    $dest = $uploadsDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        jsonError('Échec sauvegarde image');
    }

    return '/images/uploads/' . $filename;
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

            jsonSuccess([
                'menu' => $menuData['menu'] ?? ['categories' => []],
                'supplements' => $supplements
            ]);
        }
    }

    jsonError('Impossible de charger le menu');
}

/* =========================
   POST ACTIONS
   ========================= */

$input = readInput();

if (empty($input['action'])) {
    jsonError('Action manquante');
}

$action = $input['action'];

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

        if ($name === '') {
            jsonError('Nom manquant');
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

        saveMenuRuntime($runtime);
        jsonSuccess(['category' => ['id' => $id, 'name' => $name]]);
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

        if (!$productId) {
            jsonError('ID produit manquant');
        }

        if (!isset($runtime['products'])) $runtime['products'] = [];
        if (!isset($runtime['products'][$productId])) $runtime['products'][$productId] = [];
        $runtime['products'][$productId]['status'] = $newStatus;

        if (isset($runtime['customProducts'][$productId])) {
            $runtime['customProducts'][$productId]['status'] = $newStatus;
        }

        saveMenuRuntime($runtime);

        // Sync vers menu.json pour le site public
        require_once __DIR__ . '/../sync-menu.php';
        syncMenuStatuses();

        jsonSuccess();
        break;

    case 'delete_product':
        $productId = $input['product_id'] ?? null;

        if (!$productId) {
            jsonError('ID produit manquant');
        }

        if (isset($runtime['customProducts'][$productId])) {
            unset($runtime['customProducts'][$productId]);
        }

        if (!isset($runtime['deletedProducts'])) $runtime['deletedProducts'] = [];
        if (!in_array($productId, $runtime['deletedProducts'], true)) {
            $runtime['deletedProducts'][] = $productId;
        }

        saveMenuRuntime($runtime);
        jsonSuccess();
        break;

    case 'add_supplement':
        $name = trim((string)($input['name'] ?? ''));
        $price = (float)($input['price'] ?? 0);

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

        $runtime['supplements']['catalog'][$id] = [
            'id' => $id,
            'name' => $name,
            'price' => $price,
            'status' => 'available'
        ];

        saveMenuRuntime($runtime);
        jsonSuccess(['supplement' => ['id' => $id, 'name' => $name, 'price' => $price]]);
        break;

    case 'update_supplement':
        $id = $input['supplement_id'] ?? null;

        if (!$id || !isset($runtime['supplements']['catalog'][$id])) {
            jsonError('Supplément introuvable');
        }

        if (isset($input['name'])) $runtime['supplements']['catalog'][$id]['name'] = trim($input['name']);
        if (isset($input['price'])) $runtime['supplements']['catalog'][$id]['price'] = (float)$input['price'];
        if (isset($input['status'])) $runtime['supplements']['catalog'][$id]['status'] = $input['status'];

        saveMenuRuntime($runtime);

        // Sync vers menu.json pour le site public
        require_once __DIR__ . '/../sync-menu.php';
        syncMenuStatuses();

        jsonSuccess(['supplement' => $runtime['supplements']['catalog'][$id]]);
        break;

    case 'delete_supplement':
        $id = $input['supplement_id'] ?? null;

        if (!$id || !isset($runtime['supplements']['catalog'][$id])) {
            jsonError('Supplément introuvable');
        }

        unset($runtime['supplements']['catalog'][$id]);

        if (isset($runtime['supplements']['defaultForCategories'])) {
            foreach ($runtime['supplements']['defaultForCategories'] as $catId => &$supIds) {
                $supIds = array_values(array_filter($supIds, fn($s) => $s !== $id));
            }
        }

        saveMenuRuntime($runtime);
        jsonSuccess();
        break;

    default:
        jsonError('Action inconnue');
}
