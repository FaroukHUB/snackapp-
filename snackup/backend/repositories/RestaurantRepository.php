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
            "SELECT * FROM opening_hours WHERE restaurant_id = ? ORDER BY day_of_week, slot_number",
            [$restaurantId]
        );
    }

    /**
     * Récupère les horaires groupés par jour avec tous les créneaux
     */
    public static function getOpeningHoursGrouped(int $restaurantId): array {
        $rows = self::getOpeningHours($restaurantId);
        $grouped = [];

        foreach ($rows as $row) {
            $day = (int) $row['day_of_week'];
            if (!isset($grouped[$day])) {
                $grouped[$day] = [
                    'day_of_week' => $day,
                    'is_closed' => (bool) $row['is_closed'],
                    'slots' => []
                ];
            }
            $grouped[$day]['slots'][] = [
                'opens' => substr($row['opens'], 0, 5),
                'closes' => substr($row['closes'], 0, 5)
            ];
        }

        // S'assurer que tous les jours existent (0-6)
        for ($i = 0; $i < 7; $i++) {
            if (!isset($grouped[$i])) {
                $grouped[$i] = [
                    'day_of_week' => $i,
                    'is_closed' => false,
                    'slots' => [['opens' => '18:30', 'closes' => '23:30']]
                ];
            }
        }

        ksort($grouped);
        return array_values($grouped);
    }

    /**
     * Met à jour les horaires avec support de plusieurs créneaux par jour
     */
    public static function updateOpeningHours(int $restaurantId, array $hours): bool {
        Database::delete('opening_hours', ['restaurant_id' => $restaurantId]);

        foreach ($hours as $dayIndex => $dayData) {
            // Support ancien format (single slot) et nouveau format (multiple slots)
            if (isset($dayData['slots']) && is_array($dayData['slots'])) {
                // Nouveau format avec plusieurs créneaux
                foreach ($dayData['slots'] as $slotIndex => $slot) {
                    if (!empty($slot['opens']) && !empty($slot['closes'])) {
                        Database::insert('opening_hours', [
                            'restaurant_id' => $restaurantId,
                            'day_of_week' => $dayIndex,
                            'slot_number' => $slotIndex,
                            'opens' => $slot['opens'],
                            'closes' => $slot['closes'],
                            'is_closed' => $dayData['is_closed'] ?? 0
                        ]);
                    }
                }
            } else {
                // Ancien format avec un seul créneau
                Database::insert('opening_hours', [
                    'restaurant_id' => $restaurantId,
                    'day_of_week' => $dayIndex,
                    'slot_number' => 0,
                    'opens' => $dayData['opens'] ?? '18:30',
                    'closes' => $dayData['closes'] ?? '23:30',
                    'is_closed' => $dayData['is_closed'] ?? 0
                ]);
            }
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
        $hoursGrouped = self::getOpeningHoursGrouped($restaurantId);
        $faq = self::getFaq($restaurantId);

        $days = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
        $openingHours = [];
        foreach ($hoursGrouped as $h) {
            $dayIndex = $h['day_of_week'];
            $slots = $h['slots'] ?? [['opens' => '18:30', 'closes' => '23:30']];
            $openingHours[] = [
                'day' => $days[$dayIndex] ?? 'jour',
                'opens' => $slots[0]['opens'] ?? '18:30',
                'closes' => $slots[0]['closes'] ?? '23:30',
                'slots' => $slots
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

    // ==========================================
    // DELIVERY PLATFORMS
    // ==========================================

    public static function getDeliveryPlatforms(int $restaurantId): array {
        return Database::fetchAll(
            "SELECT * FROM delivery_platforms WHERE restaurant_id = ? ORDER BY sort_order, id",
            [$restaurantId]
        );
    }

    public static function addDeliveryPlatform(int $restaurantId, array $data): int {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $data['name'] ?? 'platform'));
        $slug = trim($slug, '-');

        // Vérifier unicité du slug
        $existing = Database::fetchOne(
            "SELECT id FROM delivery_platforms WHERE restaurant_id = ? AND slug = ?",
            [$restaurantId, $slug]
        );
        if ($existing) {
            $slug .= '-' . rand(100, 999);
        }

        return Database::insert('delivery_platforms', [
            'restaurant_id' => $restaurantId,
            'name' => $data['name'] ?? '',
            'slug' => $slug,
            'url' => $data['url'] ?? '',
            'icon' => $data['icon'] ?? $slug,
            'is_enabled' => isset($data['enabled']) ? ($data['enabled'] ? 1 : 0) : 1,
            'sort_order' => $data['sort_order'] ?? 0
        ]);
    }

    public static function updateDeliveryPlatform(int $platformId, array $data): bool {
        $updateData = [];
        if (isset($data['name'])) $updateData['name'] = $data['name'];
        if (isset($data['url'])) $updateData['url'] = $data['url'];
        if (isset($data['icon'])) $updateData['icon'] = $data['icon'];
        if (isset($data['enabled'])) $updateData['is_enabled'] = $data['enabled'] ? 1 : 0;
        if (isset($data['sort_order'])) $updateData['sort_order'] = $data['sort_order'];

        if (empty($updateData)) return false;

        return Database::update('delivery_platforms', $updateData, ['id' => $platformId]) >= 0;
    }

    public static function deleteDeliveryPlatform(int $platformId): bool {
        return Database::delete('delivery_platforms', ['id' => $platformId]) > 0;
    }

    public static function toggleDeliveryPlatform(int $platformId): bool {
        $platform = Database::fetchOne("SELECT is_enabled FROM delivery_platforms WHERE id = ?", [$platformId]);
        if (!$platform) return false;

        $newStatus = $platform['is_enabled'] ? 0 : 1;
        return Database::update('delivery_platforms', ['is_enabled' => $newStatus], ['id' => $platformId]) >= 0;
    }

    // ==========================================
    // THEME SETTINGS
    // ==========================================

    public static function getTheme(int $restaurantId): array {
        $settings = self::getSettings($restaurantId);
        return [
            'primary' => $settings['theme_primary'] ?? '#e63946',
            'primaryDark' => $settings['theme_primary_dark'] ?? '#d62839',
            'secondary' => $settings['theme_secondary'] ?? '#1a1a2e',
            'accent' => $settings['theme_accent'] ?? '#ff6fae',
            'background' => '#f5f5f5',
            'cardBackground' => '#ffffff',
            'textPrimary' => '#111111',
            'textSecondary' => '#666666',
            'success' => '#27ae60',
            'error' => '#e74c3c',
            'buttonRadius' => '12px',
            'cardRadius' => '16px'
        ];
    }

    public static function updateTheme(int $restaurantId, array $colors): bool {
        $data = [];
        if (isset($colors['primary'])) $data['theme_primary'] = $colors['primary'];
        if (isset($colors['primaryDark'])) $data['theme_primary_dark'] = $colors['primaryDark'];
        if (isset($colors['secondary'])) $data['theme_secondary'] = $colors['secondary'];
        if (isset($colors['accent'])) $data['theme_accent'] = $colors['accent'];

        if (empty($data)) return false;

        return self::updateSettings($restaurantId, $data);
    }

    // ==========================================
    // DELIVERY TOGGLE
    // ==========================================

    public static function isDeliveryEnabled(int $restaurantId): bool {
        $settings = self::getSettings($restaurantId);
        return (bool) ($settings['delivery_enabled'] ?? true);
    }

    public static function toggleDelivery(int $restaurantId): bool {
        $current = self::isDeliveryEnabled($restaurantId);
        $newStatus = !$current;
        self::updateSettings($restaurantId, ['delivery_enabled' => $newStatus ? 1 : 0]);
        return $newStatus;
    }
}
