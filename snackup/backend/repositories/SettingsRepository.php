<?php
/**
 * SettingsRepository - Gestion MySQL pour paramètres restaurant, livraison, paiement
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

        // Zones de livraison
        $settings['delivery_zones'] = self::getDeliveryZones();

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

        $settings['delivery_zones'] = self::getDeliveryZones();
        $settings['payment'] = self::getPaymentSettings();

        return $settings;
    }

    /**
     * Récupère les zones de livraison actives
     */
    public static function getDeliveryZones(): array {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare("
            SELECT id, name, min_distance_km, max_distance_km,
                   delivery_fee, min_order_amount, estimated_time_min, is_active
            FROM delivery_zones
            WHERE restaurant_id = ? AND is_active = 1
            ORDER BY min_distance_km ASC, sort_order ASC
        ");
        $stmt->execute([self::$restaurantId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Récupère toutes les zones (admin)
     */
    public static function getAllDeliveryZones(): array {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare("
            SELECT id, name, min_distance_km, max_distance_km,
                   delivery_fee, min_order_amount, estimated_time_min, is_active, sort_order
            FROM delivery_zones
            WHERE restaurant_id = ?
            ORDER BY sort_order ASC, min_distance_km ASC
        ");
        $stmt->execute([self::$restaurantId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Ajouter une zone de livraison
     */
    public static function addDeliveryZone(array $data): int {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare("
            INSERT INTO delivery_zones
            (restaurant_id, name, min_distance_km, max_distance_km, delivery_fee, min_order_amount, estimated_time_min, is_active, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            self::$restaurantId,
            $data['name'] ?? 'Nouvelle zone',
            $data['min_distance_km'] ?? 0,
            $data['max_distance_km'] ?? 5,
            $data['delivery_fee'] ?? 0,
            $data['min_order_amount'] ?? null,
            $data['estimated_time_min'] ?? 30,
            $data['is_active'] ?? 1,
            $data['sort_order'] ?? 0
        ]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * Modifier une zone de livraison
     */
    public static function updateDeliveryZone(int $id, array $data): bool {
        $pdo = Database::getInstance();

        $fields = [];
        $values = [];

        $allowedFields = ['name', 'min_distance_km', 'max_distance_km', 'delivery_fee',
                          'min_order_amount', 'estimated_time_min', 'is_active', 'sort_order'];

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
            UPDATE delivery_zones
            SET " . implode(', ', $fields) . "
            WHERE id = ? AND restaurant_id = ?
        ");

        return $stmt->execute($values);
    }

    /**
     * Supprimer une zone de livraison
     */
    public static function deleteDeliveryZone(int $id): bool {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare("
            DELETE FROM delivery_zones
            WHERE id = ? AND restaurant_id = ?
        ");

        return $stmt->execute([$id, self::$restaurantId]);
    }

    /**
     * Calcule les frais de livraison selon la distance
     */
    public static function calculateDeliveryFee(float $distanceKm): ?array {
        $zones = self::getDeliveryZones();

        foreach ($zones as $zone) {
            $min = (float) $zone['min_distance_km'];
            $max = (float) $zone['max_distance_km'];

            if ($distanceKm >= $min && $distanceKm <= $max) {
                return [
                    'zone_id' => $zone['id'],
                    'zone_name' => $zone['name'],
                    'fee' => (float) $zone['delivery_fee'],
                    'estimated_time' => (int) $zone['estimated_time_min'],
                    'min_order' => $zone['min_order_amount'] ? (float) $zone['min_order_amount'] : null
                ];
            }
        }

        // Aucune zone trouvée = livraison non disponible
        return null;
    }

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
     * Mettre à jour les settings paiement
     */
    public static function updatePaymentSettings(array $data): bool {
        $pdo = Database::getInstance();

        $allowedFields = [
            'cash_enabled', 'card_on_delivery_enabled', 'online_payment_enabled',
            'stripe_mode', 'stripe_public_key_test', 'stripe_secret_key_test',
            'stripe_public_key_live', 'stripe_secret_key_live', 'stripe_webhook_secret',
            'require_payment_upfront', 'allow_partial_payment', 'partial_payment_percent'
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

        // Upsert: insert if not exists, update if exists
        $fieldsList = implode(', ', array_keys(array_intersect_key($data, array_flip($allowedFields))));
        $placeholders = implode(', ', array_fill(0, count($fields), '?'));

        $stmt = $pdo->prepare("
            INSERT INTO payment_settings (restaurant_id, " . implode(', ', array_intersect(array_keys($data), $allowedFields)) . ")
            VALUES (?, " . implode(', ', array_fill(0, count(array_intersect_key($data, array_flip($allowedFields))), '?')) . ")
            ON DUPLICATE KEY UPDATE " . implode(', ', $fields)
        ");

        // Simplifier: juste faire un UPDATE
        $stmt = $pdo->prepare("
            UPDATE payment_settings
            SET " . implode(', ', $fields) . "
            WHERE restaurant_id = ?
        ");

        return $stmt->execute($values);
    }
}
