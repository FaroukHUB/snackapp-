<?php
/**
 * SnackApp v1 - Order Repository
 * Gestion des commandes
 */

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/LoyaltyRepository.php';

class OrderRepository {

    /**
     * Récupère les commandes actives (non archivées)
     */
    public static function getActiveOrders(int $restaurantId, int $limit = 50): array {
        $orders = Database::fetchAll(
            "SELECT o.*, c.name as customer_db_name
             FROM orders o
             LEFT JOIN customers c ON o.customer_id = c.id
             WHERE o.restaurant_id = ? AND o.is_archived = 0
             ORDER BY o.created_at DESC
             LIMIT ?",
            [$restaurantId, $limit]
        );

        // Charger les items pour chaque commande
        foreach ($orders as &$order) {
            $order['items'] = self::getOrderItems($order['id']);
        }

        return $orders;
    }

    /**
     * Récupère les commandes archivées
     */
    public static function getArchivedOrders(int $restaurantId, int $limit = 500): array {
        $orders = Database::fetchAll(
            "SELECT o.*
             FROM orders o
             WHERE o.restaurant_id = ? AND o.is_archived = 1
             ORDER BY o.created_at DESC
             LIMIT ?",
            [$restaurantId, $limit]
        );

        foreach ($orders as &$order) {
            $order['items'] = self::getOrderItems($order['id']);
        }

        return $orders;
    }

    /**
     * Récupère les items d'une commande
     */
    public static function getOrderItems(int $orderId): array {
        $items = Database::fetchAll(
            "SELECT oi.*
             FROM order_items oi
             WHERE oi.order_id = ?",
            [$orderId]
        );

        // Charger les suppléments de chaque item
        foreach ($items as &$item) {
            $item['supplements'] = Database::fetchAll(
                "SELECT supplement_name as name, price
                 FROM order_item_supplements
                 WHERE order_item_id = ?",
                [$item['id']]
            );
            // Format compatible avec l'ancien système
            $item['name'] = $item['product_name'];
            $item['price'] = $item['unit_price'];
        }

        return $items;
    }

    /**
     * Crée une nouvelle commande
     */
    public static function createOrder(int $restaurantId, array $data): int {
        // Générer le numéro de commande
        $orderNumber = self::generateOrderNumber($restaurantId);

        // Trouver ou créer le client
        $customerId = null;
        if (!empty($data['customer_phone'])) {
            $customerId = CustomerRepository::findOrCreate($restaurantId, [
                'name' => $data['customer_name'] ?? 'Client',
                'phone' => $data['customer_phone']
            ]);
        }

        // Vérifier si une récompense fidélité est demandée
        $loyaltyRewardId = null;
        $loyaltyPointsUsed = 0;

        if (!empty($data['loyalty_reward_id']) && $customerId) {
            $reward = Database::fetchOne(
                "SELECT * FROM loyalty_rewards WHERE id = ? AND is_active = 1",
                [$data['loyalty_reward_id']]
            );

            if ($reward) {
                // Vérifier que le client a assez de points
                $customerPoints = LoyaltyRepository::getCustomerPoints($customerId);
                if ($customerPoints >= $reward['points_required']) {
                    $loyaltyRewardId = $reward['id'];
                    $loyaltyPointsUsed = $reward['points_required'];
                }
            }
        }

        // Créer la commande
        $orderId = Database::insert('orders', [
            'restaurant_id' => $restaurantId,
            'customer_id' => $customerId,
            'order_number' => $orderNumber,
            'customer_name' => $data['customer_name'] ?? 'Client',
            'customer_phone' => $data['customer_phone'] ?? '',
            'subtotal' => $data['subtotal'] ?? $data['total'] ?? 0,
            'total' => $data['total'] ?? 0,
            'status' => 'pending',
            'notes' => $data['notes'] ?? null,
            'pickup_time' => $data['pickup_time'] ?? null,
            'loyalty_reward_id' => $loyaltyRewardId,
            'loyalty_points_used' => $loyaltyPointsUsed,
            'loyalty_redeemed' => 0
        ]);

        // Ajouter les items
        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $itemId = Database::insert('order_items', [
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'] ?? null,
                    'product_name' => $item['name'],
                    'variant' => $item['variant'] ?? 'solo',
                    'quantity' => $item['quantity'] ?? 1,
                    'unit_price' => $item['price'] ?? 0,
                    'total_price' => ($item['price'] ?? 0) * ($item['quantity'] ?? 1)
                ]);

                // Ajouter les suppléments
                if (!empty($item['supplements'])) {
                    foreach ($item['supplements'] as $sup) {
                        Database::insert('order_item_supplements', [
                            'order_item_id' => $itemId,
                            'supplement_id' => $sup['id'] ?? null,
                            'supplement_name' => $sup['name'] ?? (is_string($sup) ? $sup : ''),
                            'price' => $sup['price'] ?? 0
                        ]);
                    }
                }
            }
        }

        // Mettre à jour les stats du client et ajouter des points fidélité
        if ($customerId) {
            CustomerRepository::incrementStats($customerId, $data['total'] ?? 0);

            // Ajouter des points fidélité (1€ = 1 point par défaut)
            $orderTotal = floatval($data['total'] ?? 0);
            $pointsToAdd = floor($orderTotal); // 1 point par euro

            if ($pointsToAdd > 0) {
                LoyaltyRepository::addPoints(
                    $customerId,
                    $restaurantId,
                    $pointsToAdd,
                    $orderId,
                    'Commande #' . $orderNumber
                );
            }
        }

        return $orderId;
    }

    /**
     * Met à jour le statut d'une commande
     */
    public static function updateStatus(int $orderId, string $status): bool {
        $updateData = ['status' => $status];

        if ($status === 'completed') {
            $updateData['completed_at'] = date('Y-m-d H:i:s');
        }

        return Database::update('orders', $updateData, ['id' => $orderId]) > 0;
    }

    /**
     * Archive une commande
     */
    public static function archiveOrder(int $orderId): bool {
        return Database::update('orders', ['is_archived' => 1], ['id' => $orderId]) > 0;
    }

    /**
     * Archive toutes les commandes terminées
     */
    public static function archiveAllCompleted(int $restaurantId): int {
        return Database::query(
            "UPDATE orders SET is_archived = 1
             WHERE restaurant_id = ? AND status = 'completed' AND is_archived = 0",
            [$restaurantId]
        )->rowCount();
    }

    /**
     * Archive automatiquement les commandes de plus de 24h
     */
    public static function autoArchive(int $restaurantId): int {
        return Database::query(
            "UPDATE orders SET is_archived = 1
             WHERE restaurant_id = ?
               AND status = 'completed'
               AND is_archived = 0
               AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)",
            [$restaurantId]
        )->rowCount();
    }

    /**
     * Récupère une commande par son ID
     */
    public static function getById(int $orderId): ?array {
        $order = Database::fetchOne("SELECT * FROM orders WHERE id = ?", [$orderId]);

        if ($order) {
            $order['items'] = self::getOrderItems($orderId);
        }

        return $order;
    }

    /**
     * Récupère une commande par son numéro
     */
    public static function getByNumber(int $restaurantId, string $orderNumber): ?array {
        $order = Database::fetchOne(
            "SELECT * FROM orders WHERE restaurant_id = ? AND order_number = ?",
            [$restaurantId, $orderNumber]
        );

        if ($order) {
            $order['items'] = self::getOrderItems($order['id']);

            // Ajouter les infos de récompense fidélité si présente
            if (!empty($order['loyalty_reward_id'])) {
                $order['loyalty_reward'] = Database::fetchOne(
                    "SELECT * FROM loyalty_rewards WHERE id = ?",
                    [$order['loyalty_reward_id']]
                );
            }
        }

        return $order;
    }

    /**
     * Déduit les points fidélité quand la commande est terminée
     */
    public static function redeemLoyaltyPoints(int $orderId, int $restaurantId): bool {
        $order = Database::fetchOne(
            "SELECT * FROM orders WHERE id = ? AND restaurant_id = ?",
            [$orderId, $restaurantId]
        );

        if (!$order || !$order['customer_id'] || !$order['loyalty_reward_id'] || $order['loyalty_redeemed']) {
            return false;
        }

        // Récupérer la récompense
        $reward = Database::fetchOne(
            "SELECT * FROM loyalty_rewards WHERE id = ?",
            [$order['loyalty_reward_id']]
        );

        if (!$reward) {
            return false;
        }

        // Déduire les points
        $success = LoyaltyRepository::redeemPoints(
            $order['customer_id'],
            $restaurantId,
            $reward['points_required'],
            $reward['name'] . ' (Commande ' . $order['order_number'] . ')'
        );

        if ($success) {
            // Marquer comme déduit
            Database::update('orders', ['loyalty_redeemed' => 1], ['id' => $orderId]);
        }

        return $success;
    }

    /**
     * Statistiques du jour
     */
    public static function getTodayStats(int $restaurantId): array {
        $stats = Database::fetchOne(
            "SELECT
                COUNT(*) as orders_count,
                COALESCE(SUM(total), 0) as revenue,
                COALESCE(AVG(total), 0) as avg_order,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
             FROM orders
             WHERE restaurant_id = ?
               AND DATE(created_at) = CURDATE()
               AND is_archived = 0",
            [$restaurantId]
        );

        // Produit le plus vendu
        $topProduct = Database::fetchOne(
            "SELECT oi.product_name, SUM(oi.quantity) as qty
             FROM order_items oi
             JOIN orders o ON oi.order_id = o.id
             WHERE o.restaurant_id = ? AND DATE(o.created_at) = CURDATE()
             GROUP BY oi.product_name
             ORDER BY qty DESC
             LIMIT 1",
            [$restaurantId]
        );

        // Heure de pic
        $peakHour = Database::fetchOne(
            "SELECT HOUR(created_at) as hour, COUNT(*) as count
             FROM orders
             WHERE restaurant_id = ? AND DATE(created_at) = CURDATE()
             GROUP BY HOUR(created_at)
             ORDER BY count DESC
             LIMIT 1",
            [$restaurantId]
        );

        return [
            'today' => (int) ($stats['orders_count'] ?? 0),
            'pending' => (int) ($stats['pending'] ?? 0),
            'completed' => (int) ($stats['completed'] ?? 0),
            'revenue' => (float) ($stats['revenue'] ?? 0),
            'avg_order' => (float) ($stats['avg_order'] ?? 0),
            'top_product' => $topProduct['product_name'] ?? null,
            'peak_hour' => $peakHour ? sprintf('%02d:00', $peakHour['hour']) : null
        ];
    }

    /**
     * Génère un numéro de commande unique
     */
    private static function generateOrderNumber(int $restaurantId): string {
        $date = date('Ymd');
        $prefix = 'FB'; // TODO: récupérer depuis restaurant

        // Compter les commandes du jour
        $count = Database::fetchOne(
            "SELECT COUNT(*) as count FROM orders
             WHERE restaurant_id = ? AND DATE(created_at) = CURDATE()",
            [$restaurantId]
        );

        $num = ($count['count'] ?? 0) + 1;

        return sprintf('%s-%s-%03d', $prefix, $date, $num);
    }

    /**
     * Vérifie s'il y a de nouvelles commandes depuis un timestamp
     */
    public static function hasNewOrders(int $restaurantId, string $since): bool {
        $result = Database::fetchOne(
            "SELECT COUNT(*) as count FROM orders
             WHERE restaurant_id = ? AND created_at > ? AND is_archived = 0",
            [$restaurantId, $since]
        );

        return ($result['count'] ?? 0) > 0;
    }

    /**
     * Récupère les nouvelles commandes depuis un timestamp
     */
    public static function getNewOrders(int $restaurantId, string $since): array {
        $orders = Database::fetchAll(
            "SELECT * FROM orders
             WHERE restaurant_id = ? AND created_at > ? AND is_archived = 0
             ORDER BY created_at DESC",
            [$restaurantId, $since]
        );

        foreach ($orders as &$order) {
            $order['items'] = self::getOrderItems($order['id']);
        }

        return $orders;
    }

    /**
     * Statistiques de la semaine
     */
    public static function getWeekStats(int $restaurantId): array {
        $stats = Database::fetchOne(
            "SELECT
                COUNT(*) as orders_count,
                COALESCE(SUM(total), 0) as revenue,
                COALESCE(AVG(total), 0) as avg_order
             FROM orders
             WHERE restaurant_id = ?
               AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)",
            [$restaurantId]
        );

        return [
            'orders' => (int) ($stats['orders_count'] ?? 0),
            'revenue' => (float) ($stats['revenue'] ?? 0),
            'avg_order' => (float) ($stats['avg_order'] ?? 0)
        ];
    }

    /**
     * Statistiques du mois
     */
    public static function getMonthStats(int $restaurantId): array {
        $stats = Database::fetchOne(
            "SELECT
                COUNT(*) as orders_count,
                COALESCE(SUM(total), 0) as revenue,
                COALESCE(AVG(total), 0) as avg_order
             FROM orders
             WHERE restaurant_id = ?
               AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)",
            [$restaurantId]
        );

        return [
            'orders' => (int) ($stats['orders_count'] ?? 0),
            'revenue' => (float) ($stats['revenue'] ?? 0),
            'avg_order' => (float) ($stats['avg_order'] ?? 0)
        ];
    }

    /**
     * Top 5 des produits les plus vendus
     */
    public static function getTopProducts(int $restaurantId, int $days = 30, int $limit = 5): array {
        return Database::fetchAll(
            "SELECT oi.product_name as name, SUM(oi.quantity) as qty, SUM(oi.total_price) as revenue
             FROM order_items oi
             JOIN orders o ON oi.order_id = o.id
             WHERE o.restaurant_id = ? AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY oi.product_name
             ORDER BY qty DESC
             LIMIT ?",
            [$restaurantId, $days, $limit]
        );
    }

    /**
     * Heures de pic (distribution des commandes par heure)
     */
    public static function getPeakHours(int $restaurantId, int $days = 30): array {
        return Database::fetchAll(
            "SELECT HOUR(created_at) as hour, COUNT(*) as count
             FROM orders
             WHERE restaurant_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY HOUR(created_at)
             ORDER BY hour ASC",
            [$restaurantId, $days]
        );
    }

    /**
     * Revenus par jour (7 derniers jours)
     */
    public static function getDailyRevenue(int $restaurantId, int $days = 7): array {
        return Database::fetchAll(
            "SELECT DATE(created_at) as date, COUNT(*) as orders, COALESCE(SUM(total), 0) as revenue
             FROM orders
             WHERE restaurant_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY DATE(created_at)
             ORDER BY date ASC",
            [$restaurantId, $days]
        );
    }

    /**
     * Export des stats en CSV
     */
    public static function exportStatsCSV(int $restaurantId, string $period = 'month'): string {
        $days = match($period) {
            'week' => 7,
            'month' => 30,
            'year' => 365,
            default => 30
        };

        $orders = Database::fetchAll(
            "SELECT o.order_number, o.created_at, o.customer_name, o.customer_phone, o.total, o.status
             FROM orders o
             WHERE o.restaurant_id = ? AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             ORDER BY o.created_at DESC",
            [$restaurantId, $days]
        );

        $csv = "\xEF\xBB\xBF";
        $csv .= "Numéro,Date,Client,Téléphone,Total,Statut\n";

        foreach ($orders as $o) {
            $csv .= sprintf(
                '"%s","%s","%s","%s",%.2f,"%s"' . "\n",
                $o['order_number'],
                $o['created_at'],
                $o['customer_name'],
                $o['customer_phone'],
                $o['total'],
                $o['status']
            );
        }

        return $csv;
    }




}
