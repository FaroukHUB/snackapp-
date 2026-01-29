<?php
/**
 * SettingsRepository - Gestion MySQL pour paramètres restaurant, livraison par VILLE, paiement
 * Source de vérité: MySQL (pas de JSON hardcodé)
 */
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../InstanceManager.php';

class SettingsRepository {
    public static $restaurantId = 3; // Par défaut, sera surchargé par InstanceManager

    /**
     * Récupère tous les settings du restaurant (pour le frontend/cart)
     */
    public static function getPublicSettings(): array {
        $pdo = Database::getInstance();

        // Settings restaurant
        $stmt = $pdo->prepare("
            SELECT
                rs.currency_code, rs.currency_symbol, rs.currency_position,
                rs.country_code, rs.country_name,
                rs.loyalty_enabled, rs.loyalty_euro_per_point, rs.loyalty_point_value,
                rs.delivery_enabled, rs.pickup_enabled,
                rs.min_order_amount, rs.free_delivery_threshold,
                r.name as restaurant_name, r.logo
            FROM restaurant_settings rs
            JOIN restaurants r ON r.id = rs.restaurant_id
            WHERE rs.restaurant_id = ?
        ");
        $stmt->execute([self::$restaurantId]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // Villes de livraison (actives uniquement)
        $settings['delivery_cities'] = self::getDeliveryCities();

        // Payment settings (version publique - pas les clés secrètes)
        $settings['payment'] = self::getPublicPaymentSettings();

        return $settings;
    }

    /**
     * Récupère tous les settings pour l'admin (avec plus de détails)
     */
    public static function getAdminSettings(): array {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare("
            SELECT *
            FROM restaurant_settings
            WHERE restaurant_id = ?
        ");
        $stmt->execute([self::$restaurantId]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $settings['delivery_cities'] = self::getAllDeliveryCities();
        $settings['payment'] = self::getPaymentSettings();

        return $settings;
    }

    // ========================================
    // VILLES DE LIVRAISON
    // ========================================

    /**
     * Récupère les villes de livraison actives (pour le frontend)
     */
    public static function getDeliveryCities(): array {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare("
            SELECT id, city_name, postal_code, delivery_fee,
                   min_order_amount, estimated_time_min, is_home_city
            FROM delivery_cities
            WHERE restaurant_id = ? AND is_active = 1
            ORDER BY is_home_city DESC, sort_order ASC, city_name ASC
        ");
        $stmt->execute([self::$restaurantId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Récupère toutes les villes (admin)
     */
    public static function getAllDeliveryCities(): array {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare("
            SELECT id, city_name, postal_code, delivery_fee,
                   min_order_amount, estimated_time_min, is_active, is_home_city, sort_order
            FROM delivery_cities
            WHERE restaurant_id = ?
            ORDER BY is_home_city DESC, sort_order ASC, city_name ASC
        ");
        $stmt->execute([self::$restaurantId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Ajouter une ville de livraison
     */
    public static function addDeliveryCity(array $data): int {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare("
            INSERT INTO delivery_cities
            (restaurant_id, city_name, postal_code, delivery_fee, min_order_amount, estimated_time_min, is_active, is_home_city, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            self::$restaurantId,
            $data['city_name'] ?? 'Nouvelle ville',
            $data['postal_code'] ?? null,
            $data['delivery_fee'] ?? 0,
            $data['min_order_amount'] ?? null,
            $data['estimated_time_min'] ?? 30,
            $data['is_active'] ?? 1,
            $data['is_home_city'] ?? 0,
            $data['sort_order'] ?? 0
        ]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * Modifier une ville de livraison
     */
    public static function updateDeliveryCity(int $id, array $data): bool {
        $pdo = Database::getInstance();

        $fields = [];
        $values = [];

        $allowedFields = ['city_name', 'postal_code', 'delivery_fee',
                          'min_order_amount', 'estimated_time_min', 'is_active', 'is_home_city', 'sort_order'];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }

        if (empty($fields)) return false;

        $values[] = $id;
        $values[] = self::$restaurantId;

        $stmt = $pdo->prepare("
            UPDATE delivery_cities
            SET " . implode(', ', $fields) . "
            WHERE id = ? AND restaurant_id = ?
        ");

        return $stmt->execute($values);
    }

    /**
     * Supprimer une ville de livraison
     */
    public static function deleteDeliveryCity(int $id): bool {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare("
            DELETE FROM delivery_cities
            WHERE id = ? AND restaurant_id = ?
        ");

        return $stmt->execute([$id, self::$restaurantId]);
    }

    /**
     * Récupère les frais de livraison pour une ville
     */
    public static function getDeliveryFeeForCity(string $cityName): ?array {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare("
            SELECT id, city_name, delivery_fee, estimated_time_min, min_order_amount
            FROM delivery_cities
            WHERE restaurant_id = ? AND is_active = 1 AND LOWER(city_name) = LOWER(?)
        ");
        $stmt->execute([self::$restaurantId, $cityName]);
        $city = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$city) return null;

        return [
            'city_id' => $city['id'],
            'city_name' => $city['city_name'],
            'fee' => (float) $city['delivery_fee'],
            'estimated_time' => (int) $city['estimated_time_min'],
            'min_order' => $city['min_order_amount'] ? (float) $city['min_order_amount'] : null
        ];
    }

    // ========================================
    // PAIEMENTS
    // ========================================

    /**
     * Payment settings (version publique - sans clés secrètes)
     */
    public static function getPublicPaymentSettings(): array {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare("
            SELECT
                cash_enabled, card_on_delivery_enabled, online_payment_enabled,
                stripe_mode,
                CASE WHEN stripe_mode = 'live' THEN stripe_public_key_live ELSE stripe_public_key_test END as stripe_public_key,
                require_payment_upfront, allow_partial_payment, partial_payment_percent
            FROM payment_settings
            WHERE restaurant_id = ?
        ");
        $stmt->execute([self::$restaurantId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'cash_enabled' => true,
            'card_on_delivery_enabled' => false,
            'online_payment_enabled' => false
        ];
    }

    /**
     * Payment settings complets (admin)
     */
    public static function getPaymentSettings(): array {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare("
            SELECT *
            FROM payment_settings
            WHERE restaurant_id = ?
        ");
        $stmt->execute([self::$restaurantId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    // ========================================
    // SETTINGS GÉNÉRAUX
    // ========================================

    /**
     * Mettre à jour les settings restaurant
     */
    public static function updateSettings(array $data): bool {
        $pdo = Database::getInstance();

        $allowedFields = [
            'currency_code', 'currency_symbol', 'currency_position',
            'country_code', 'country_name',
            'loyalty_enabled', 'loyalty_euro_per_point', 'loyalty_point_value',
            'delivery_enabled', 'pickup_enabled',
            'min_order_amount', 'free_delivery_threshold'
        ];

        $fields = [];
        $values = [];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }

        if (empty($fields)) return false;

        $values[] = self::$restaurantId;

        $stmt = $pdo->prepare("
            UPDATE restaurant_settings
            SET " . implode(', ', $fields) . "
            WHERE restaurant_id = ?
        ");

        return $stmt->execute($values);
    }

    /**
     * Mettre à jour les settings paiement (INSERT si n'existe pas)
     */
    public static function updatePaymentSettings(array $data): bool {
        $pdo = Database::getInstance();

        $allowedFields = [
            'cash_enabled', 'card_on_delivery_enabled', 'online_payment_enabled',
            'stripe_mode', 'stripe_public_key_test', 'stripe_secret_key_test',
            'stripe_public_key_live', 'stripe_secret_key_live', 'stripe_webhook_secret',
            'require_payment_upfront', 'allow_partial_payment', 'partial_payment_percent'
        ];

        $insertFields = ['restaurant_id'];
        $insertValues = [self::$restaurantId];
        $updateParts = [];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $insertFields[] = $field;
                $insertValues[] = $data[$field];
                $updateParts[] = "$field = VALUES($field)";
            }
        }

        if (count($insertFields) <= 1) {
            error_log("[updatePaymentSettings] Aucun champ valide. Data: " . json_encode($data));
            return false;
        }

        $placeholders = implode(', ', array_fill(0, count($insertFields), '?'));
        $fieldList = implode(', ', $insertFields);
        $updateList = implode(', ', $updateParts);

        $sql = "INSERT INTO payment_settings ($fieldList) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $updateList";
        error_log("[updatePaymentSettings] SQL: $sql");
        error_log("[updatePaymentSettings] Values: " . json_encode($insertValues));

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($insertValues);

        error_log("[updatePaymentSettings] Result: " . ($result ? 'true' : 'false'));
        return $result;
    }
}
