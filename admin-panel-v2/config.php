<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuration
define('ADMIN_PASSWORD', 'fabrik2025');
define('CONFIG_FILE', __DIR__ . '/../config/fabrik-burger.config.js');
define('MENU_RUNTIME_FILE', __DIR__ . '/../config/menu.runtime.json');
define('DATA_DIR', __DIR__ . '/data/');
define('UPLOADS_DIR', '../images/');

// Créer le dossier data s'il n'existe pas
if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}

// Initialiser les fichiers JSON s'ils n'existent pas
$dataFiles = [
    'customers.json' => [],
    'orders.json' => [],
    'loyalty_points.json' => [],
    'settings.json' => [
        'loyalty' => [
            'enabled' => true,
            'pointsPerEuro' => 1,
            'rewardThreshold' => 100
        ],
        'notifications' => [
            'soundEnabled' => true,
            'soundVolume' => 0.8
        ]
    ]
];

foreach ($dataFiles as $file => $defaultData) {
    $filePath = DATA_DIR . $file;
    if (!file_exists($filePath)) {
        file_put_contents($filePath, json_encode($defaultData, JSON_PRETTY_PRINT));
    }
}

// Fonctions utilitaires
function requireLogin() {
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        header('Location: login.php');
        exit;
    }
}

function loadConfig() {
    $content = file_get_contents(CONFIG_FILE);

    // Trouver le début : "window.SNACK_CONFIG = " ou "const SNACK_CONFIG = "
    $pattern = '/(const|window\.)\s*SNACK_CONFIG\s*=\s*/';
    if (!preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
        error_log('❌ [CONFIG] Pattern SNACK_CONFIG non trouvé');
        return null;
    }

    // Position de début du JSON (juste après le "=")
    $startPos = $matches[0][1] + strlen($matches[0][0]);
    $jsonContent = substr($content, $startPos);

    // Enlever le point-virgule final et les espaces
    $jsonContent = rtrim($jsonContent);
    if (substr($jsonContent, -1) === ';') {
        $jsonContent = substr($jsonContent, 0, -1);
    }
    $jsonContent = trim($jsonContent);

    // ⚠️ IMPORTANT: Convertir le JavaScript object notation en JSON valide

    // 1. Enlever les commentaires JavaScript (// ...)
    $jsonContent = preg_replace('/\/\/[^\n]*/', '', $jsonContent);

    // 2. Ajouter des guillemets autour des clés non quotées
    // Ex: id: "value" → "id": "value"
    $jsonContent = preg_replace('/([{,]\s*)([a-zA-Z_][a-zA-Z0-9_]*)\s*:/', '$1"$2":', $jsonContent);

    // 3. Enlever les trailing commas (virgules avant } ou ])
    $jsonContent = preg_replace('/,(\s*[}\]])/', '$1', $jsonContent);

    // 4. Nettoyer les caractères de contrôle invalides (garde \n, \r, \t qui sont OK)
    $jsonContent = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $jsonContent);

    // Parser le JSON avec options permissives
    $decoded = json_decode($jsonContent, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);

    if ($decoded === null) {
        error_log('❌ [CONFIG] Erreur de parsing JSON: ' . json_last_error_msg());
        error_log('❌ [CONFIG] Contenu (100 premiers chars): ' . substr($jsonContent, 0, 100));
        @file_put_contents(DATA_DIR . 'debug-json.txt', $jsonContent);
        return null;
    }

    return $decoded;
}

function saveConfig($config) {
    $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // Lire le fichier existant pour déterminer le format (const ou window.)
    $existingContent = file_get_contents(CONFIG_FILE);
    $prefix = (strpos($existingContent, 'window.SNACK_CONFIG') !== false) ? 'window.SNACK_CONFIG' : 'const SNACK_CONFIG';

    // Garder les commentaires du début si présents
    $header = '';
    if (preg_match('/^(\/\/[^\n]*\n)+/', $existingContent, $headerMatch)) {
        $header = $headerMatch[0] . "\n";
    }

    $js = $header . $prefix . " = " . $json . ";";
    $result = file_put_contents(CONFIG_FILE, $js);

    if ($result === false) {
        error_log('❌ [CONFIG] Erreur lors de l\'écriture du fichier');
    }

    return $result !== false;
}

function loadData($filename) {
    $filePath = DATA_DIR . $filename;
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        return json_decode($content, true);
    }
    return [];
}

function saveData($filename, $data) {
    $filePath = DATA_DIR . $filename;
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents($filePath, $json) !== false;
}

function sendWhatsAppMessage($phone, $message) {
    // Format le numéro (enlever espaces, tirets)
    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
    $encoded = urlencode($message);
    $url = "https://wa.me/{$cleanPhone}?text={$encoded}";
    return $url;
}

// Charger le config global
$GLOBALS['config'] = loadConfig();
$GLOBALS['primaryColor'] = $GLOBALS['config']['branding']['primaryColor'] ?? '#f97316';


// ========= MENU RUNTIME (OVERRIDES ADMIN) =========
// Ne jamais écrire dans CONFIG_FILE (fabrik-burger.config.js) pour les produits.
// Les modifications admin sont stockées dans MENU_RUNTIME_FILE (JSON pur).

function loadMenuRuntime() {
    $path = MENU_RUNTIME_FILE;
    if (!file_exists($path)) {
        return [
            'products' => [],
            'categories' => [],
            'customCategories' => [],
            'customProducts' => [],
            'deletedProducts' => [],
            'deletedCategories' => []
        ];
    }
    $raw = @file_get_contents($path);
    $data = json_decode($raw ?: '{}', true);
    if (!is_array($data)) $data = [];

    // Normaliser la forme (scalable)
    $data['products'] = isset($data['products']) && is_array($data['products']) ? $data['products'] : [];
    $data['categories'] = isset($data['categories']) && is_array($data['categories']) ? $data['categories'] : [];
    $data['customCategories'] = isset($data['customCategories']) && is_array($data['customCategories']) ? $data['customCategories'] : [];
    $data['customProducts'] = isset($data['customProducts']) && is_array($data['customProducts']) ? $data['customProducts'] : [];
    $data['deletedProducts'] = isset($data['deletedProducts']) && is_array($data['deletedProducts']) ? $data['deletedProducts'] : [];
    $data['deletedCategories'] = isset($data['deletedCategories']) && is_array($data['deletedCategories']) ? $data['deletedCategories'] : [];

    return $data;
}

function saveMenuRuntime($runtime) {
    $runtime = is_array($runtime) ? $runtime : [];
    $runtime['products'] = isset($runtime['products']) && is_array($runtime['products']) ? $runtime['products'] : [];
    $runtime['categories'] = isset($runtime['categories']) && is_array($runtime['categories']) ? $runtime['categories'] : [];
    $runtime['customCategories'] = isset($runtime['customCategories']) && is_array($runtime['customCategories']) ? $runtime['customCategories'] : [];
    $runtime['customProducts'] = isset($runtime['customProducts']) && is_array($runtime['customProducts']) ? $runtime['customProducts'] : [];

    $json = json_encode($runtime, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new Exception('Impossible d\'encoder le runtime JSON');
    }

    $dir = dirname(MENU_RUNTIME_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    // Écriture atomique + lock
    $tmp = MENU_RUNTIME_FILE . '.tmp';
    $fp = fopen($tmp, 'wb');
    if (!$fp) {
        throw new Exception('Impossible d\'ouvrir le fichier temporaire runtime');
    }
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        throw new Exception('Impossible de verrouiller le fichier runtime');
    }
    fwrite($fp, $json);
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    rename($tmp, MENU_RUNTIME_FILE);

    // ===== SYNC : Générer menu.json pour le site public =====
    generatePublicMenuJson($runtime);

    return true;
}

/**
 * Génère le fichier menu.json fusionné pour le site public
 * Ce fichier est lu par snack-runtime.js
 */
function generatePublicMenuJson($runtime) {
    $config = loadConfig();
    if (!$config) return false;

    $merged = applyRuntimeToConfig($config, $runtime);

    // Construire le JSON public avec menu + supplements
    $publicData = [
        'version' => 1,
        'lastUpdated' => date('c'),
        'menu' => $merged['menu'] ?? ['categories' => []],
        'supplements' => $runtime['supplements'] ?? [
            'catalog' => [],
            'defaultForCategories' => []
        ]
    ];

    // Ajouter featured si présent dans config
    if (isset($config['featured'])) {
        $publicData['featured'] = $config['featured'];
    }

    // Ajouter formula si présent dans config
    if (isset($config['formula'])) {
        $publicData['formula'] = $config['formula'];
    }

    $menuJsonPath = __DIR__ . '/../config/menu.json';
    $json = json_encode($publicData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($json === false) {
        error_log('❌ [SYNC] Erreur encodage menu.json public');
        return false;
    }

    // Écriture atomique
    $tmp = $menuJsonPath . '.tmp';
    if (file_put_contents($tmp, $json) !== false) {
        rename($tmp, $menuJsonPath);
        error_log('✅ [SYNC] menu.json public mis à jour');
        return true;
    }

    return false;
}

// Fusion runtime -> config (côté PHP admin)
function applyRuntimeToConfig($config, $runtime) {
    if (!is_array($config)) return $config;
    $runtime = is_array($runtime) ? $runtime : [];

    $config['menu'] = isset($config['menu']) && is_array($config['menu']) ? $config['menu'] : [];
    $config['menu']['categories'] = isset($config['menu']['categories']) && is_array($config['menu']['categories']) ? $config['menu']['categories'] : [];

    $deletedProducts = isset($runtime['deletedProducts']) && is_array($runtime['deletedProducts']) ? array_flip($runtime['deletedProducts']) : [];
    $deletedCategories = isset($runtime['deletedCategories']) && is_array($runtime['deletedCategories']) ? array_flip($runtime['deletedCategories']) : [];

    // index catégories + normalisation items
    $catsById = [];
    foreach ($config['menu']['categories'] as $i => $cat) {
        $cid = $cat['id'] ?? null;
        if ($cid) $catsById[$cid] = $i;
        if (!isset($config['menu']['categories'][$i]['items']) || !is_array($config['menu']['categories'][$i]['items'])) {
            $config['menu']['categories'][$i]['items'] = [];
        }
    }

    // ajouter catégories custom
    if (isset($runtime['customCategories']) && is_array($runtime['customCategories'])) {
        foreach ($runtime['customCategories'] as $cid => $cat) {
            $cat = is_array($cat) ? $cat : [];
            $id = $cat['id'] ?? $cid;
            if (!$id) continue;
            if (isset($deletedCategories[$id])) continue;

            if (!isset($catsById[$id])) {
                $config['menu']['categories'][] = [
                    'id' => $id,
                    'name' => $cat['name'] ?? $id,
                    'description' => $cat['description'] ?? '',
                    'items' => isset($cat['items']) && is_array($cat['items']) ? $cat['items'] : []
                ];
                $catsById[$id] = count($config['menu']['categories']) - 1;
            }
        }
    }

    // overrides catégories
    if (isset($runtime['categories']) && is_array($runtime['categories'])) {
        foreach ($runtime['categories'] as $cid => $patch) {
            if (!isset($catsById[$cid])) continue;
            if (isset($deletedCategories[$cid])) continue;
            if (!is_array($patch)) continue;
            $config['menu']['categories'][$catsById[$cid]] = array_replace_recursive($config['menu']['categories'][$catsById[$cid]], $patch);
        }
    }

    // produits custom
    if (isset($runtime['customProducts']) && is_array($runtime['customProducts'])) {
        foreach ($runtime['customProducts'] as $pid => $p) {
            if (!is_array($p)) continue;
            if (isset($deletedProducts[$pid])) continue;

            $categoryId = $p['categoryId'] ?? null;
            if (!$categoryId || !isset($catsById[$categoryId])) continue;
            if (isset($deletedCategories[$categoryId])) continue;

            $catIndex = $catsById[$categoryId];
            $exists = false;
            foreach ($config['menu']['categories'][$catIndex]['items'] as $it) {
                if (($it['id'] ?? null) === $pid) { $exists = true; break; }
            }
            if (!$exists) {
                $config['menu']['categories'][$catIndex]['items'][] = array_merge(['id' => $pid], $p);
            }
        }
    }

    // overrides produits
    $productPatches = isset($runtime['products']) && is_array($runtime['products']) ? $runtime['products'] : [];
    if (!empty($productPatches)) {
        foreach ($config['menu']['categories'] as $ci => $cat) {
            if (!isset($cat['id']) || isset($deletedCategories[$cat['id']])) continue;

            foreach ($config['menu']['categories'][$ci]['items'] as $ii => $item) {
                $pid = $item['id'] ?? null;
                if (!$pid) continue;
                if (isset($deletedProducts[$pid])) { unset($config['menu']['categories'][$ci]['items'][$ii]); continue; }

                if (isset($productPatches[$pid]) && is_array($productPatches[$pid])) {
                    $config['menu']['categories'][$ci]['items'][$ii] = array_replace_recursive($item, $productPatches[$pid]);
                }
            }
            // reindex
            $config['menu']['categories'][$ci]['items'] = array_values($config['menu']['categories'][$ci]['items']);
        }
    } else {
        // même sans patches: appliquer deletions
        foreach ($config['menu']['categories'] as $ci => $cat) {
            if (!isset($cat['items']) || !is_array($cat['items'])) continue;
            $config['menu']['categories'][$ci]['items'] = array_values(array_filter($cat['items'], function($it) use ($deletedProducts) {
                $id = $it['id'] ?? null;
                return !$id || !isset($deletedProducts[$id]);
            }));
        }
    }

    // supprimer catégories marquées supprimées
    $config['menu']['categories'] = array_values(array_filter($config['menu']['categories'], function($cat) use ($deletedCategories) {
        $id = $cat['id'] ?? null;
        return $id && !isset($deletedCategories[$id]);
    }));

    // tri optionnel par order
    usort($config['menu']['categories'], function($a, $b) {
        $ao = isset($a['order']) && is_numeric($a['order']) ? (float)$a['order'] : 0;
        $bo = isset($b['order']) && is_numeric($b['order']) ? (float)$b['order'] : 0;
        return $ao <=> $bo;
    });

    return $config;
}
