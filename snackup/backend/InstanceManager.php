<?php
/**
 * InstanceManager - Gestionnaire centralisé des instances multi-restaurant
 *
 * Ce gestionnaire permet de :
 * - Détecter automatiquement l'instance selon le domaine
 * - Charger dynamiquement la configuration de chaque instance
 * - Centraliser toute la logique de routing multi-instance
 * - Garantir la scalabilité : ajouter une instance = éditer instances.json uniquement
 *
 * @author SnackApp Platform
 * @version 2.0.0
 */

class InstanceManager {

    /**
     * @var array Configuration des instances chargée depuis instances.json
     */
    private static $instancesConfig = null;

    /**
     * @var string Nom de l'instance courante
     */
    private static $currentInstance = null;

    /**
     * @var array Configuration complète de l'instance courante
     */
    private static $currentConfig = null;

    /**
     * Initialise le gestionnaire d'instances
     *
     * @param string|null $configPath Chemin vers instances.json (optionnel)
     * @throws Exception Si le fichier instances.json n'existe pas ou est invalide
     */
    public static function init($configPath = null) {
        if (self::$instancesConfig !== null) {
            return; // Déjà initialisé
        }

        // Charger instances.json
        if ($configPath === null) {
            $configPath = __DIR__ . '/../../config/instances.json';
        }

        if (!file_exists($configPath)) {
            throw new Exception("Fichier de configuration instances non trouvé : $configPath");
        }

        $content = file_get_contents($configPath);
        self::$instancesConfig = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Erreur de parsing JSON dans instances.json : " . json_last_error_msg());
        }

        if (!isset(self::$instancesConfig['instances']) || empty(self::$instancesConfig['instances'])) {
            throw new Exception("Aucune instance définie dans instances.json");
        }
    }

    /**
     * Détecte l'instance actuelle selon le domaine HTTP
     *
     * Algorithme :
     * 1. Récupère le hostname depuis $_SERVER
     * 2. Parcourt toutes les instances et leurs domaines
     * 3. Retourne la première instance qui match (exact ou partiel)
     * 4. Fallback sur l'instance par défaut si aucun match
     *
     * @return string Nom de l'instance détectée
     */
    public static function detectInstance() {
        self::init();

        if (self::$currentInstance !== null) {
            return self::$currentInstance; // Déjà détecté
        }

        // Récupérer le hostname
        $host = strtolower($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '');

        // Fallback pour environnement local/dev
        if (empty($host) || $host === 'localhost' || strpos($host, '127.0.0.1') !== false) {
            self::$currentInstance = self::$instancesConfig['default'] ?? 'atelier-pizza';
            return self::$currentInstance;
        }

        // Parcourir toutes les instances pour trouver un match
        foreach (self::$instancesConfig['instances'] as $instanceName => $instanceData) {
            // Vérifier que l'instance est activée
            if (isset($instanceData['enabled']) && !$instanceData['enabled']) {
                continue;
            }

            // Vérifier tous les domaines de cette instance
            if (isset($instanceData['domains']) && is_array($instanceData['domains'])) {
                foreach ($instanceData['domains'] as $domain) {
                    $domain = strtolower($domain);

                    // Match exact
                    if ($host === $domain) {
                        self::$currentInstance = $instanceName;
                        return self::$currentInstance;
                    }

                    // Match partiel (si strict_domain_matching = false)
                    if (!self::isStrictDomainMatching() && strpos($host, $domain) !== false) {
                        self::$currentInstance = $instanceName;
                        return self::$currentInstance;
                    }

                    // Match partiel inverse (pour sous-domaines)
                    if (!self::isStrictDomainMatching() && strpos($domain, $host) !== false) {
                        self::$currentInstance = $instanceName;
                        return self::$currentInstance;
                    }
                }
            }
        }

        // Aucun match trouvé : utiliser l'instance par défaut
        self::$currentInstance = self::$instancesConfig['default'] ?? array_key_first(self::$instancesConfig['instances']);

        return self::$currentInstance;
    }

    /**
     * Charge la configuration complète de l'instance courante
     *
     * @return array Configuration de l'instance (database, app, stripe, email, theme)
     * @throws Exception Si la configuration de l'instance n'existe pas
     */
    public static function loadConfig() {
        if (self::$currentConfig !== null) {
            return self::$currentConfig; // Déjà chargé
        }

        $instanceName = self::detectInstance();
        $instanceData = self::getInstanceData($instanceName);

        if (!isset($instanceData['config_path'])) {
            throw new Exception("Chemin de configuration non défini pour l'instance '$instanceName'");
        }

        $configPath = __DIR__ . '/../../' . $instanceData['config_path'];

        if (!file_exists($configPath)) {
            throw new Exception("Fichier de configuration introuvable : $configPath (instance: $instanceName)");
        }

        self::$currentConfig = require $configPath;

        if (!is_array(self::$currentConfig)) {
            throw new Exception("Configuration invalide pour l'instance '$instanceName' (doit retourner un array)");
        }

        return self::$currentConfig;
    }

    /**
     * Retourne le nom de l'instance courante
     *
     * @return string Nom de l'instance
     */
    public static function getCurrentInstance() {
        return self::detectInstance();
    }

    /**
     * Retourne les données d'une instance depuis instances.json
     *
     * @param string $instanceName Nom de l'instance
     * @return array|null Données de l'instance ou null si inexistante
     */
    public static function getInstanceData($instanceName) {
        self::init();
        return self::$instancesConfig['instances'][$instanceName] ?? null;
    }

    /**
     * Retourne toutes les instances disponibles
     *
     * @return array Liste des instances avec leurs configurations
     */
    public static function getAllInstances() {
        self::init();
        return self::$instancesConfig['instances'];
    }

    /**
     * Vérifie si une instance existe et est activée
     *
     * @param string $instanceName Nom de l'instance
     * @return bool True si l'instance existe et est activée
     */
    public static function isInstanceEnabled($instanceName) {
        $data = self::getInstanceData($instanceName);

        if ($data === null) {
            return false;
        }

        return !isset($data['enabled']) || $data['enabled'] === true;
    }

    /**
     * Retourne le restaurant ID de l'instance courante
     *
     * @return int Restaurant ID
     * @throws Exception Si le restaurant_id n'est pas défini
     */
    public static function getRestaurantId() {
        $config = self::loadConfig();

        if (!isset($config['app']['restaurant_id'])) {
            throw new Exception("restaurant_id non défini dans la configuration de l'instance " . self::getCurrentInstance());
        }

        return (int) $config['app']['restaurant_id'];
    }

    /**
     * Retourne l'ID de l'instance courante
     *
     * @return string Instance ID
     */
    public static function getInstanceId() {
        $config = self::loadConfig();
        return $config['app']['instance_id'] ?? self::getCurrentInstance();
    }

    /**
     * Retourne la devise de l'instance courante
     *
     * @return string Code devise (EUR, DA, USD, etc.)
     */
    public static function getCurrency() {
        $config = self::loadConfig();
        return $config['app']['currency'] ?? 'EUR';
    }

    /**
     * Retourne la configuration de la base de données de l'instance courante
     *
     * @return array Configuration database (host, name, user, password, charset)
     * @throws Exception Si la configuration database n'existe pas
     */
    public static function getDatabaseConfig() {
        $config = self::loadConfig();

        if (!isset($config['database'])) {
            throw new Exception("Configuration database manquante pour l'instance " . self::getCurrentInstance());
        }

        return $config['database'];
    }

    /**
     * Retourne la configuration thème de l'instance courante
     *
     * @return array|null Configuration theme ou null si non défini
     */
    public static function getThemeConfig() {
        $config = self::loadConfig();
        return $config['theme'] ?? null;
    }

    /**
     * Vérifie si le mode strict de matching des domaines est activé
     *
     * @return bool True si strict mode activé
     */
    private static function isStrictDomainMatching() {
        self::init();
        return self::$instancesConfig['settings']['strict_domain_matching'] ?? false;
    }

    /**
     * Réinitialise le gestionnaire (utile pour les tests)
     */
    public static function reset() {
        self::$instancesConfig = null;
        self::$currentInstance = null;
        self::$currentConfig = null;
    }

    /**
     * Retourne des informations de debug sur l'instance courante
     *
     * @return array Informations de debug
     */
    public static function getDebugInfo() {
        return [
            'host' => $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'unknown',
            'detected_instance' => self::getCurrentInstance(),
            'restaurant_id' => self::getRestaurantId(),
            'instance_id' => self::getInstanceId(),
            'currency' => self::getCurrency(),
            'config_loaded' => self::$currentConfig !== null,
            'all_instances' => array_keys(self::getAllInstances())
        ];
    }
}
