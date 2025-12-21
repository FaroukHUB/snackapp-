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
     * Récupère toutes les récompenses actives
     */
    public static function getRewards(int $restaurantId): array {
        return Database::fetchAll(
            "SELECT * FROM loyalty_rewards WHERE restaurant_id = ? AND is_active = 1 ORDER BY points_required ASC",
            [$restaurantId]
        );
    }
    
    /**
     * Récupère toutes les récompenses (actives et inactives) pour l'admin
     */
    public static function getAllRewards(int $restaurantId): array {
        return Database::fetchAll(
            "SELECT * FROM loyalty_rewards WHERE restaurant_id = ? ORDER BY points_required ASC",
            [$restaurantId]
        );
    }
    
    /**
     * Ajoute une récompense
     */
    public static function addReward(int $restaurantId, array $data): int {
        return Database::insert('loyalty_rewards', [
            'restaurant_id' => $restaurantId,
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'points_required' => (int) $data['points_required'],
            'reward_type' => $data['reward_type'] ?? 'discount_percent',
            'reward_value' => (float) ($data['reward_value'] ?? 0),
            'is_active' => 1
        ]);
    }
    
    /**
     * Modifie une récompense
     */
    public static function updateReward(int $rewardId, array $data): bool {
        return Database::update('loyalty_rewards', [
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'points_required' => (int) $data['points_required'],
            'reward_type' => $data['reward_type'],
            'reward_value' => (float) ($data['reward_value'] ?? 0),
            'is_active' => (int) ($data['is_active'] ?? 1)
        ], ['id' => $rewardId]) > 0;
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
     * Classement des clients par points
     * Lit depuis le fichier JSON des clients
     */
    public static function getLeaderboard(int $restaurantId, int $limit = 10): array {
        // Chemin vers le fichier JSON des clients
        $jsonPath = __DIR__ . '/../../admin-panel-v2/data/customers.json';

        if (!file_exists($jsonPath)) {
            return [];
        }

        $content = file_get_contents($jsonPath);
        $customers = json_decode($content, true);

        if (!is_array($customers)) {
            return [];
        }

        // Filtrer les clients avec des points > 0
        $customersWithPoints = array_filter($customers, function($c) {
            return isset($c['loyalty_points']) && $c['loyalty_points'] > 0;
        });

        // Trier par points décroissants
        usort($customersWithPoints, function($a, $b) {
            return ($b['loyalty_points'] ?? 0) - ($a['loyalty_points'] ?? 0);
        });

        // Limiter les résultats
        $leaderboard = array_slice($customersWithPoints, 0, $limit);

        // Formater les résultats pour correspondre au format attendu
        return array_map(function($c) {
            return [
                'id' => $c['id'] ?? '',
                'name' => $c['name'] ?? 'Client',
                'phone' => $c['phone'] ?? '',
                'loyalty_points' => (int) ($c['loyalty_points'] ?? 0),
                'orders_count' => (int) ($c['orders_count'] ?? 0),
                'total_spent' => (float) ($c['total_spent'] ?? 0)
            ];
        }, $leaderboard);
    }
}
