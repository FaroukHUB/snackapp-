<?php
/**
 * SnackApp v1 - PromoCode Repository
 * Gestion des codes promo
 */

require_once __DIR__ . '/../Database.php';

class PromoCodeRepository {

    /**
     * Récupère tous les codes promo d'un restaurant
     */
    public static function getAll(int $restaurantId, bool $activeOnly = false): array {
        $where = "restaurant_id = ?";
        $params = [$restaurantId];

        if ($activeOnly) {
            $where .= " AND is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())";
        }

        return Database::fetchAll(
            "SELECT * FROM promo_codes WHERE {$where} ORDER BY created_at DESC",
            $params
        );
    }

    /**
     * Récupère un code promo par son code
     */
    public static function getByCode(int $restaurantId, string $code): ?array {
        return Database::fetchOne(
            "SELECT * FROM promo_codes WHERE restaurant_id = ? AND code = ?",
            [$restaurantId, strtoupper($code)]
        );
    }

    /**
     * Récupère un code promo par ID
     */
    public static function getById(int $id): ?array {
        return Database::fetchOne("SELECT * FROM promo_codes WHERE id = ?", [$id]);
    }

    /**
     * Crée un nouveau code promo
     */
    public static function create(int $restaurantId, array $data): int {
        $code = strtoupper(trim($data['code'] ?? ''));

        if (empty($code)) {
            throw new Exception("Le code promo est requis");
        }

        // Vérifier si le code existe déjà pour ce restaurant
        $existing = self::getByCode($restaurantId, $code);
        if ($existing) {
            throw new Exception("Ce code promo existe déjà");
        }

        $insertData = [
            'restaurant_id' => $restaurantId,
            'code' => $code,
            'description' => $data['description'] ?? null,
            'discount_type' => $data['discount_type'] ?? 'percent', // percent ou fixed
            'discount_value' => floatval($data['discount_value'] ?? 0),
            'min_order_amount' => isset($data['min_order_amount']) ? floatval($data['min_order_amount']) : null,
            'max_uses' => isset($data['max_uses']) ? intval($data['max_uses']) : null,
            'current_uses' => 0,
            'starts_at' => !empty($data['starts_at']) ? $data['starts_at'] : null,
            'expires_at' => !empty($data['expires_at']) ? $data['expires_at'] : null,
            'is_active' => isset($data['is_active']) ? (bool)$data['is_active'] : true
        ];

        return Database::insert('promo_codes', $insertData);
    }

    /**
     * Met à jour un code promo
     */
    public static function update(int $id, array $data): bool {
        $updateData = [];

        if (isset($data['code'])) {
            $updateData['code'] = strtoupper(trim($data['code']));
        }
        if (isset($data['description'])) {
            $updateData['description'] = $data['description'];
        }
        if (isset($data['discount_type'])) {
            $updateData['discount_type'] = $data['discount_type'];
        }
        if (isset($data['discount_value'])) {
            $updateData['discount_value'] = floatval($data['discount_value']);
        }
        if (isset($data['min_order_amount'])) {
            $updateData['min_order_amount'] = $data['min_order_amount'] !== '' ? floatval($data['min_order_amount']) : null;
        }
        if (isset($data['max_uses'])) {
            $updateData['max_uses'] = $data['max_uses'] !== '' ? intval($data['max_uses']) : null;
        }
        if (isset($data['starts_at'])) {
            $updateData['starts_at'] = $data['starts_at'] !== '' ? $data['starts_at'] : null;
        }
        if (isset($data['expires_at'])) {
            $updateData['expires_at'] = $data['expires_at'] !== '' ? $data['expires_at'] : null;
        }
        if (isset($data['is_active'])) {
            $updateData['is_active'] = (bool)$data['is_active'];
        }

        if (empty($updateData)) {
            return false;
        }

        return Database::update('promo_codes', $updateData, ['id' => $id]) > 0;
    }

    /**
     * Supprime un code promo
     */
    public static function delete(int $id): bool {
        return Database::delete('promo_codes', ['id' => $id]) > 0;
    }

    /**
     * Valide et applique un code promo
     * Retourne les infos du code si valide, sinon null
     */
    public static function validate(int $restaurantId, string $code, float $orderTotal): ?array {
        $promo = self::getByCode($restaurantId, $code);

        if (!$promo) {
            return null; // Code non trouvé
        }

        // Vérifier si actif
        if (!$promo['is_active']) {
            throw new Exception("Ce code promo n'est plus actif");
        }

        // Vérifier date de début
        if ($promo['starts_at'] && strtotime($promo['starts_at']) > time()) {
            throw new Exception("Ce code promo n'est pas encore valide");
        }

        // Vérifier date d'expiration
        if ($promo['expires_at'] && strtotime($promo['expires_at']) < time()) {
            throw new Exception("Ce code promo a expiré");
        }

        // Vérifier nombre d'utilisations max
        if ($promo['max_uses'] !== null && $promo['current_uses'] >= $promo['max_uses']) {
            throw new Exception("Ce code promo a atteint sa limite d'utilisation");
        }

        // Vérifier montant minimum de commande
        if ($promo['min_order_amount'] !== null && $orderTotal < $promo['min_order_amount']) {
            throw new Exception("Commande minimum de " . number_format($promo['min_order_amount'], 0, ',', ' ') . " DA requise");
        }

        return $promo;
    }

    /**
     * Incrémente le compteur d'utilisations
     */
    public static function incrementUsage(int $id): bool {
        return Database::query(
            "UPDATE promo_codes SET current_uses = current_uses + 1 WHERE id = ?",
            [$id]
        )->rowCount() > 0;
    }

    /**
     * Calcule la réduction à partir d'un code promo
     */
    public static function calculateDiscount(array $promo, float $orderTotal): float {
        if ($promo['discount_type'] === 'percent') {
            return $orderTotal * ($promo['discount_value'] / 100);
        } else {
            // Type fixed
            return $promo['discount_value'];
        }
    }

    /**
     * Statistiques des codes promo
     */
    public static function getStats(int $restaurantId): array {
        $stats = Database::fetchOne(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                SUM(current_uses) as total_uses
             FROM promo_codes
             WHERE restaurant_id = ?",
            [$restaurantId]
        );

        return [
            'total' => (int) ($stats['total'] ?? 0),
            'active' => (int) ($stats['active'] ?? 0),
            'total_uses' => (int) ($stats['total_uses'] ?? 0)
        ];
    }
}
