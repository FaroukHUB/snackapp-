<?php

require_once __DIR__ . '/../Database.php';

class LoyaltyRepository {
    
    /**
     * Récupère les points d'un client
     */
    public static function getCustomerPoints(int $customerId): int {
        $result = Database::fetchOne(
            "SELECT loyalty_points FROM customers WHERE id = ?",
            [$customerId]
        );
        return (int) ($result['loyalty_points'] ?? 0);
    }
    
    /**
     * Ajoute des points à un client (après une commande)
     */
    public static function addPoints(int $customerId, int $restaurantId, int $points, ?int $orderId = null, string $description = 'Achat'): bool {
        // Ajouter la transaction
        Database::insert('loyalty_transactions', [
            'customer_id' => $customerId,
            'restaurant_id' => $restaurantId,
            'order_id' => $orderId,
            'points' => $points,
            'type' => 'earn',
            'description' => $description
        ]);
        
        // Mettre à jour les points du client
        return Database::query(
            "UPDATE customers SET loyalty_points = loyalty_points + ? WHERE id = ?",
            [$points, $customerId]
        ) !== false;
    }
    
    /**
     * Utilise des points pour une récompense
     */
    public static function redeemPoints(int $customerId, int $restaurantId, int $points, string $rewardName): bool {
        $currentPoints = self::getCustomerPoints($customerId);
        
        if ($currentPoints < $points) {
            return false;
        }
        
        // Ajouter la transaction (points négatifs)
        Database::insert('loyalty_transactions', [
            'customer_id' => $customerId,
            'restaurant_id' => $restaurantId,
            'points' => -$points,
            'type' => 'redeem',
            'description' => 'Échange: ' . $rewardName
        ]);
        
        // Retirer les points du client
        return Database::query(
            "UPDATE customers SET loyalty_points = loyalty_points - ? WHERE id = ?",
            [$points, $customerId]
        ) !== false;
    }
    
    /**
     * Récupère l'historique des transactions d'un client
     */
    public static function getTransactions(int $customerId, int $limit = 20): array {
        return Database::fetchAll(
            "SELECT * FROM loyalty_transactions WHERE customer_id = ? ORDER BY created_at DESC LIMIT ?",
            [$customerId, $limit]
        );
    }
    
    /**
     * Récupère toutes les récompenses actives avec infos produit
     */
    public static function getRewards(int $restaurantId): array {
        return Database::fetchAll(
            "SELECT lr.*,
                    p.name AS product_name,
                    p.image AS product_image,
                    p.price_solo AS product_price
             FROM loyalty_rewards lr
             LEFT JOIN products p ON lr.product_id = p.id
             WHERE lr.restaurant_id = ? AND lr.is_active = 1
             ORDER BY lr.points_required ASC",
            [$restaurantId]
        );
    }

    /**
     * Récupère toutes les récompenses (actives et inactives) pour l'admin
     */
    public static function getAllRewards(int $restaurantId): array {
        return Database::fetchAll(
            "SELECT lr.*,
                    p.name AS product_name,
                    p.image AS product_image
             FROM loyalty_rewards lr
             LEFT JOIN products p ON lr.product_id = p.id
             WHERE lr.restaurant_id = ?
             ORDER BY lr.points_required ASC",
            [$restaurantId]
        );
    }
    
    /**
     * Ajoute une récompense
     */
    public static function addReward(int $restaurantId, array $data): int {
        $insertData = [
            'restaurant_id' => $restaurantId,
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'points_required' => (int) $data['points_required'],
            'reward_type' => $data['reward_type'] ?? 'discount_percent',
            'reward_value' => (float) ($data['reward_value'] ?? 0),
            'is_active' => 1
        ];

        // Ajouter product_id si fourni
        if (!empty($data['product_id'])) {
            $insertData['product_id'] = (int) $data['product_id'];
        }

        // Ajouter image personnalisée si fournie
        if (!empty($data['image'])) {
            $insertData['image'] = $data['image'];
        }

        return Database::insert('loyalty_rewards', $insertData);
    }

    /**
     * Modifie une récompense
     */
    public static function updateReward(int $rewardId, array $data): bool {
        $updateData = [
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'points_required' => (int) $data['points_required'],
            'reward_type' => $data['reward_type'],
            'reward_value' => (float) ($data['reward_value'] ?? 0),
            'is_active' => (int) ($data['is_active'] ?? 1)
        ];

        // Mettre à jour product_id (peut être null pour désassocier)
        $updateData['product_id'] = !empty($data['product_id']) ? (int) $data['product_id'] : null;

        // Mettre à jour image si fournie (peut être vide pour supprimer)
        if (isset($data['image'])) {
            $updateData['image'] = $data['image'] ?: null;
        }

        return Database::update('loyalty_rewards', $updateData, ['id' => $rewardId]) > 0;
    }

    /**
     * Récupère tous les produits pour le dropdown de sélection
     */
    public static function getAllProducts(int $restaurantId): array {
        return Database::fetchAll(
            "SELECT p.id, p.name, p.image, p.price_solo, c.name AS category_name
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.restaurant_id = ? AND p.status = 'available'
             ORDER BY c.sort_order, p.sort_order, p.name",
            [$restaurantId]
        );
    }
    
    /**
     * Supprime une récompense
     */
    public static function deleteReward(int $rewardId): bool {
        return Database::delete('loyalty_rewards', ['id' => $rewardId]) > 0;
    }
    
    /**
     * Récupère la config fidélité du restaurant
     */
    public static function getConfig(int $restaurantId): array {
        $result = Database::fetchOne(
            "SELECT loyalty_enabled, loyalty_points_per_euro FROM restaurant_settings WHERE restaurant_id = ?",
            [$restaurantId]
        );
        return [
            'enabled' => (bool) ($result['loyalty_enabled'] ?? true),
            'points_per_euro' => (int) ($result['loyalty_points_per_euro'] ?? 1)
        ];
    }
    
    /**
     * Met à jour la config fidélité
     */
    public static function updateConfig(int $restaurantId, bool $enabled, int $pointsPerEuro): bool {
        return Database::query(
            "UPDATE restaurant_settings SET loyalty_enabled = ?, loyalty_points_per_euro = ? WHERE restaurant_id = ?",
            [$enabled ? 1 : 0, $pointsPerEuro, $restaurantId]
        ) !== false;
    }
    
    /**
     * Classement des clients par points (MySQL avec filtre restaurant_id)
     */
    public static function getLeaderboard(int $restaurantId, int $limit = 10): array {
        return Database::fetchAll(
            "SELECT id, name, phone, loyalty_points, orders_count, total_spent
             FROM customers
             WHERE restaurant_id = ? AND loyalty_points > 0
             ORDER BY loyalty_points DESC
             LIMIT ?",
            [$restaurantId, $limit]
        );
    }
}
