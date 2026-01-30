<?php
/**
 * Repository pour la gestion des bases de pizza
 */

require_once __DIR__ . '/../Database.php';

class PizzaBaseRepository {

    public static int $restaurantId = 1;

    /**
     * Récupère toutes les bases
     */
    public static function getAll(): array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT id, name, image, sort_order
            FROM pizza_bases
            WHERE restaurant_id = ?
            ORDER BY sort_order ASC, name ASC
        ");
        $stmt->execute([self::$restaurantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère une base par ID
     */
    public static function getById(int $id): ?array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT id, name, image, sort_order
            FROM pizza_bases
            WHERE id = ? AND restaurant_id = ?
        ");
        $stmt->execute([$id, self::$restaurantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Crée une nouvelle base
     */
    public static function create(array $data): int {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            INSERT INTO pizza_bases (restaurant_id, name, image, sort_order)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            self::$restaurantId,
            $data['name'],
            $data['image'] ?? null,
            $data['sort_order'] ?? 0
        ]);
        return (int)$pdo->lastInsertId();
    }

    /**
     * Met à jour une base
     */
    public static function update(int $id, array $data): bool {
        $pdo = Database::getInstance();

        $fields = [];
        $values = [];

        if (isset($data['name'])) {
            $fields[] = 'name = ?';
            $values[] = $data['name'];
        }
        if (array_key_exists('image', $data)) {
            $fields[] = 'image = ?';
            $values[] = $data['image'];
        }
        if (isset($data['sort_order'])) {
            $fields[] = 'sort_order = ?';
            $values[] = $data['sort_order'];
        }

        if (empty($fields)) {
            return false;
        }

        $values[] = $id;
        $values[] = self::$restaurantId;

        $stmt = $pdo->prepare("
            UPDATE pizza_bases
            SET " . implode(', ', $fields) . "
            WHERE id = ? AND restaurant_id = ?
        ");
        $stmt->execute($values);
        return $stmt->rowCount() > 0;
    }

    /**
     * Supprime une base
     */
    public static function delete(int $id): bool {
        $pdo = Database::getInstance();

        // D'abord, mettre à NULL les produits qui utilisent cette base
        $stmt = $pdo->prepare("UPDATE products SET default_base_id = NULL WHERE default_base_id = ?");
        $stmt->execute([$id]);

        // Ensuite supprimer la base
        $stmt = $pdo->prepare("DELETE FROM pizza_bases WHERE id = ? AND restaurant_id = ?");
        $stmt->execute([$id, self::$restaurantId]);
        return $stmt->rowCount() > 0;
    }
}
