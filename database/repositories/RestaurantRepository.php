<?php
/**
 * SnackApp v1 - Restaurant Repository
 * Gestion des restaurants et leurs paramètres
 */

require_once __DIR__ . '/../Database.php';

class RestaurantRepository {

    public static function getBySlug(string $slug): ?array {
        return Database::fetchOne(
            "SELECT * FROM restaurants WHERE slug = ? AND is_active = 1",
            [$slug]
        );
    }

    public static function getById(int $id): ?array {
        return Database::fetchOne(
            "SELECT * FROM restaurants WHERE id = ?",
            [$id]
        );
    }

    public static function getSettings(int $restaurantId): ?array {
        return Database::fetchOne(
            "SELECT * FROM restaurant_settings WHERE restaurant_id = ?",
            [$restaurantId]
        );
    }

    public static function updateSettings(int $restaurantId, array $data): bool {
        $existing = self::getSettings($restaurantId);

        if ($existing) {
            return Database::update('restaurant_settings', $data, ['restaurant_id' => $restaurantId]) >= 0;
        } else {
            $data['restaurant_id'] = $restaurantId;
            return Database::insert('restaurant_settings', $data) > 0;
        }
    }

    public static function getOpeningHours(int $restaurantId): array {
        return Database::fetchAll(
            "SELECT * FROM opening_hours WHERE restaurant_id = ? ORDER BY day_of_week",
            [$restaurantId]
        );
    }

    public static function updateOpeningHours(int $restaurantId, array $hours): bool {
        Database::delete('opening_hours', ['restaurant_id' => $restaurantId]);

        foreach ($hours as $i => $hour) {
            Database::insert('opening_hours', [
                'restaurant_id' => $restaurantId,
                'day_of_week' => $i,
                'opens' => $hour['opens'] ?? '18:30',
                'closes' => $hour['closes'] ?? '23:30',
                'is_closed' => $hour['is_closed'] ?? 0
            ]);
        }

        return true;
    }

    public static function isAcceptingOrders(int $restaurantId): bool {
        $settings = self::getSettings($restaurantId);
        return (bool) ($settings['accepting_orders'] ?? true);
    }

    public static function toggleAcceptingOrders(int $restaurantId, ?bool $newStatus = null): bool {
        if ($newStatus === null) {
            $current = self::isAcceptingOrders($restaurantId);
            $newStatus = !$current;
        }

        self::updateSettings($restaurantId, ['accepting_orders' => $newStatus ? 1 : 0]);

        return $newStatus;
    }

    public static function getFaq(int $restaurantId): array {
        return Database::fetchAll(
            "SELECT * FROM faq WHERE restaurant_id = ? ORDER BY sort_order",
            [$restaurantId]
        );
    }

    public static function updateFaq(int $restaurantId, array $items): bool {
        Database::delete('faq', ['restaurant_id' => $restaurantId]);

        foreach ($items as $i => $item) {
            if (!empty($item['question']) && !empty($item['answer'])) {
                Database::insert('faq', [
                    'restaurant_id' => $restaurantId,
                    'question' => $item['question'],
                    'answer' => $item['answer'],
                    'sort_order' => $i
                ]);
            }
        }

        return true;
    }

    public static function getPublicData(int $restaurantId): array {
        $restaurant = self::getById($restaurantId);
        $settings = self::getSettings($restaurantId);
        $hours = self::getOpeningHours($restaurantId);
        $faq = self::getFaq($restaurantId);

        $days = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
        $openingHours = [];
        foreach ($hours as $h) {
            $openingHours[] = [
                'day' => $days[$h['day_of_week']] ?? 'jour',
                'opens' => substr($h['opens'], 0, 5),
                'closes' => substr($h['closes'], 0, 5)
            ];
        }

        $extraPhones = [];
        if (!empty($settings['extra_phones'])) {
            $extraPhones = json_decode($settings['extra_phones'], true) ?? [];
        }

        $extraSocials = [];
        if (!empty($settings['extra_socials'])) {
            $extraSocials = json_decode($settings['extra_socials'], true) ?? [];
        }

        return [
            'name' => $restaurant['name'] ?? '',
            'contact' => [
                'phone' => $settings['phone'] ?? '',
                'whatsappOrdersNumber' => $settings['whatsapp_number'] ?? '',
                'extra_phones' => $extraPhones
            ],
            'location' => [
                'address' => $settings['address'] ?? '',
                'city' => $settings['city'] ?? '',
                'postalCode' => $settings['postal_code'] ?? ''
            ],
            'social' => [
                'instagram' => $settings['instagram'] ?? '',
                'facebook' => $settings['facebook'] ?? '',
                'tiktok' => $settings['tiktok'] ?? '',
                'snapchat' => $settings['snapchat'] ?? '',
                'extra' => $extraSocials
            ],
            'openingHours' => $openingHours,
            'faq' => [
                'title' => 'Questions frequentes',
                'items' => array_map(fn($f) => [
                    'question' => $f['question'],
                    'answer' => $f['answer']
                ], $faq)
            ],
            'accepting_orders' => (bool) ($settings['accepting_orders'] ?? true),
            'whatsapp' => [
                'token' => $settings['whatsapp_token'] ?? '',
                'phoneNumberId' => $settings['whatsapp_phone_id'] ?? '',
                'businessAccountId' => $settings['whatsapp_business_id'] ?? '',
                'configured' => !empty($settings['whatsapp_token'])
            ]
        ];
    }

    public static function getWhatsAppConfig(int $restaurantId): array {
        $settings = self::getSettings($restaurantId);

        return [
            'token' => $settings['whatsapp_token'] ?? '',
            'phoneNumberId' => $settings['whatsapp_phone_id'] ?? '',
            'businessAccountId' => $settings['whatsapp_business_id'] ?? '',
            'configured' => !empty($settings['whatsapp_token'])
        ];
    }

    public static function updateWhatsAppConfig(int $restaurantId, array $config): bool {
        $data = [];

        if (!empty($config['token'])) {
            $data['whatsapp_token'] = $config['token'];
        }
        if (isset($config['phoneNumberId'])) {
            $data['whatsapp_phone_id'] = $config['phoneNumberId'];
        }
        if (isset($config['businessAccountId'])) {
            $data['whatsapp_business_id'] = $config['businessAccountId'];
        }

        if (!empty($data)) {
            return self::updateSettings($restaurantId, $data);
        }

        return false;
    }

    public static function verifyAdmin(int $restaurantId, string $username, string $password): ?array {
        $admin = Database::fetchOne(
            "SELECT * FROM admin_users WHERE restaurant_id = ? AND username = ?",
            [$restaurantId, $username]
        );

        if ($admin && password_verify($password, $admin['password_hash'])) {
            Database::update('admin_users', ['last_login' => date('Y-m-d H:i:s')], ['id' => $admin['id']]);

            unset($admin['password_hash']);
            return $admin;
        }

        return null;
    }
}
