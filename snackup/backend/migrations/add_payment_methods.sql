-- Migration : Ajout des moyens de paiement + paiement en ligne Stripe
-- Date : 2025-01-13
-- Instance : Atelier Pizza Roubaix (applicable à toutes les instances)

-- Ajouter colonnes pour gérer les moyens de paiement
ALTER TABLE orders
ADD COLUMN IF NOT EXISTS payment_method ENUM(
  'cash',           -- Espèces
  'card_terminal',  -- CB au TPE (livraison/sur place)
  'card_online',    -- CB en ligne (Stripe)
  'ticket_resto'    -- Ticket Restaurant
) DEFAULT 'cash' AFTER payment_type;

-- Ajouter colonne pour le statut du paiement
ALTER TABLE orders
ADD COLUMN IF NOT EXISTS payment_status ENUM(
  'pending',        -- En attente
  'paid',           -- Payé
  'failed',         -- Échec
  'refunded'        -- Remboursé
) DEFAULT 'pending' AFTER payment_method;

-- Ajouter colonne pour l'ID de transaction Stripe
ALTER TABLE orders
ADD COLUMN IF NOT EXISTS transaction_id VARCHAR(255) NULL AFTER payment_status;

-- Ajouter index pour optimiser les requêtes
ALTER TABLE orders
ADD INDEX idx_payment_method (payment_method);

ALTER TABLE orders
ADD INDEX idx_payment_status (payment_status);

ALTER TABLE orders
ADD INDEX idx_transaction_id (transaction_id);

-- Commentaires
ALTER TABLE orders
MODIFY COLUMN payment_method ENUM('cash','card_terminal','card_online','ticket_resto')
COMMENT 'Moyen de paiement choisi par le client';

ALTER TABLE orders
MODIFY COLUMN payment_status ENUM('pending','paid','failed','refunded')
COMMENT 'Statut du paiement (en attente, payé, échoué, remboursé)';

ALTER TABLE orders
MODIFY COLUMN transaction_id VARCHAR(255)
COMMENT 'ID de transaction Stripe (ex: pi_xxxxx)';
