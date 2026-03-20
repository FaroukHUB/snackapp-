-- Création du restaurant Demo (id=99) dans la base de données
-- À exécuter avant la migration JSON → MySQL

-- Vérifier si le restaurant existe déjà
SELECT id, name, slug FROM restaurants WHERE id = 99;

-- Si n'existe pas, créer le restaurant Demo
INSERT INTO restaurants (
    id,
    name,
    slug,
    domain,
    email,
    phone,
    address,
    city,
    postal_code,
    country,
    timezone,
    currency,
    is_active,
    created_at
) VALUES (
    99,
    'Restaurant Demo',
    'demo',
    'demo.mon-agenceweb.fr',
    'demo@mon-agenceweb.fr',
    '+33000000000',
    '1 Rue de la Demo',
    'Paris',
    '75000',
    'France',
    'Europe/Paris',
    'EUR',
    1,
    NOW()
)
ON DUPLICATE KEY UPDATE
    name = 'Restaurant Demo',
    slug = 'demo',
    domain = 'demo.mon-agenceweb.fr',
    updated_at = NOW();

-- Vérifier que le restaurant a été créé
SELECT id, name, slug, domain FROM restaurants WHERE id = 99;
