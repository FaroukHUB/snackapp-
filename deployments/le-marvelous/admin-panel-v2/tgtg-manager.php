<?php
require_once 'config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$config = loadConfig();
$restaurantName = $config['restaurant']['name'] ?? 'Restaurant';
$primaryColor = $config['branding']['primaryColor'] ?? '#c58a3a';

// Charger les offres TGTG existantes
$tgtgOffers = loadData('tgtg.json') ?? [];

// Filtrer les offres valides
$now = time();
$activeOffers = array_filter($tgtgOffers, function($offer) use ($now) {
    return strtotime($offer['expires_at']) > $now && $offer['quantity_available'] > 0;
});
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AntiGaspi - <?php echo htmlspecialchars($restaurantName); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; background: #1a1a2e; color: #fff; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: #2a2a3e; padding: 15px 20px; margin-bottom: 20px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; }
        .btn { background: <?php echo $primaryColor; ?>; color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 14px; }
        .btn-success { background: #10b981; }
        .btn-danger { background: #ef4444; }
        .card { background: #2a2a3e; padding: 20px; margin-bottom: 15px; border-radius: 12px; }
        .offer-item { display: flex; justify-content: space-between; align-items: center; padding: 15px; background: #1e293b; border-radius: 8px; margin-bottom: 10px; border-left: 4px solid #10b981; }
        .offer-name { font-weight: bold; font-size: 16px; color: #10b981; }
        .offer-price { color: <?php echo $primaryColor; ?>; font-weight: bold; margin-top: 5px; }
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 1000; justify-content: center; align-items: center; }
        .modal.active { display: flex; }
        .modal-content { background: #2a2a3e; padding: 30px; border-radius: 12px; max-width: 500px; width: 90%; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: #9ca3af; font-size: 14px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #444; border-radius: 8px; background: #1e293b; color: white; font-size: 14px; }
        .info-box { background: #10b981; background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 20px; border-radius: 12px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-leaf"></i> AntiGaspi</h1>
                <p style="color: #9ca3af; font-size: 12px;">Vendez vos invendus à prix réduit</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <button onclick="showAddModal()" class="btn btn-success"><i class="fas fa-plus"></i> Nouvelle offre</button>
                <a href="index.php" class="btn">Retour</a>
            </div>
        </div>

        <div class="info-box">
            <h3 style="margin-bottom: 10px;">🌱 Qu'est-ce que AntiGaspi ?</h3>
            <p style="font-size: 14px; line-height: 1.6;">
                Créez des offres de dernière minute pour vendre vos invendus à prix réduit et éviter le gaspillage. 
                Les clients voient ces offres sur la page <a href="../tgtg.php" style="color: white; text-decoration: underline;" target="_blank">AntiGaspi</a> du site.
            </p>
        </div>

        <div class="card">
            <h2 style="margin-bottom: 15px; color: #10b981;">Offres actives (<?php echo count($activeOffers); ?>)</h2>
            
            <?php if (empty($activeOffers)): ?>
                <div style="text-align: center; padding: 40px; color: #9ca3af;">
                    <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 10px;"></i>
                    <p>Aucune offre active</p>
                    <p style="font-size: 13px; margin-top: 5px;">Créez une offre pour vendre vos invendus</p>
                </div>
            <?php else: ?>
                <?php foreach ($activeOffers as $offer): ?>
                    <div class="offer-item">
                        <div style="flex: 1;">
                            <div class="offer-name"><?php echo htmlspecialchars($offer['product_name']); ?></div>
                            <div style="color: #9ca3af; font-size: 13px; margin-top: 5px;">
                                <?php echo htmlspecialchars($offer['description'] ?? ''); ?>
                            </div>
                            <div class="offer-price" style="margin-top: 8px;">
                                <span style="text-decoration: line-through; color: #9ca3af; font-size: 14px;">
                                    <?php echo number_format($offer['original_price'], 2); ?>€
                                </span>
                                <span style="font-size: 18px; margin-left: 10px;">
                                    <?php echo number_format($offer['discount_price'], 2); ?>€
                                </span>
                            </div>
                            <div style="color: #9ca3af; font-size: 12px; margin-top: 5px;">
                                Quantité: <?php echo $offer['quantity_available']; ?>/<?php echo $offer['quantity_total']; ?> | 
                                Retrait: <?php echo htmlspecialchars($offer['pickup_time']); ?>
                            </div>
                        </div>
                        <div>
                            <button onclick="removeOffer('<?php echo $offer['id']; ?>')" class="btn btn-danger" style="padding: 8px 16px;">
                                <i class="fas fa-trash"></i> Supprimer
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Créer offre -->
    <div id="offerModal" class="modal">
        <div class="modal-content">
            <h2>Créer une offre AntiGaspi</h2>
            <form id="offerForm" onsubmit="saveOffer(event)">
                <div class="form-group">
                    <label>Nom de l'offre</label>
                    <input type="text" name="product_name" placeholder="Ex: Panier surprise" required>
                    <small style="color: #9ca3af; font-size: 12px;">Ce que le client verra</small>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="2" placeholder="Ex: Assortiment de burgers et accompagnements du jour"></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label>Prix normal (€)</label>
                        <input type="number" step="0.01" name="original_price" placeholder="15.00" required>
                    </div>

                    <div class="form-group">
                        <label>Prix réduit (€)</label>
                        <input type="number" step="0.01" name="discount_price" placeholder="5.00" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Quantité disponible</label>
                    <input type="number" name="quantity" min="1" placeholder="5" required>
                </div>

                <div class="form-group">
                    <label>Heure de retrait</label>
                    <input type="text" name="pickup_time" placeholder="Ex: 20h00 - 21h00" required>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-success" style="flex: 1;">Créer l'offre</button>
                    <button type="button" onclick="closeModal()" class="btn btn-danger" style="flex: 1;">Annuler</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showAddModal() {
            document.getElementById('offerModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('offerModal').classList.remove('active');
            document.getElementById('offerForm').reset();
        }

        function saveOffer(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);
            data.action = 'tgtg_add';

            fetch('api/products.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(r => r.json())
            .then(result => {
                if (result.success) {
                    alert('✅ Offre créée ! Elle est maintenant visible sur la page AntiGaspi.');
                    window.location.reload();
                } else {
                    alert('❌ Erreur: ' + result.error);
                }
            })
            .catch(err => alert('❌ Erreur: ' + err));
        }

        function removeOffer(offerId) {
            if (!confirm('Supprimer cette offre ?')) return;

            fetch('api/products.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'tgtg_remove',
                    offer_id: offerId
                })
            })
            .then(r => r.json())
            .then(result => {
                if (result.success) {
                    alert('✅ Offre supprimée');
                    window.location.reload();
                } else {
                    alert('❌ Erreur: ' + result.error);
                }
            });
        }
    </script>
</body>
</html>
