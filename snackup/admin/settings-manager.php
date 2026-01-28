<?php
/**
 * Settings Manager - Paramètres généraux, Livraison, Paiement
 * Source de vérité: MySQL
 */
require_once __DIR__ . '/bootstrap.php';
requireAdmin();
$csrfToken = getCsrfToken();
?><!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="<?= e($csrfToken) ?>">
  <title>Admin • Paramètres</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    :root{
      --bg:#0b1220;
      --panel:#0f1a2b;
      --card:rgba(255,255,255,.06);
      --stroke:rgba(255,255,255,.10);
      --text:#e5e7eb;
      --muted:#9ca3af;
      --good:#16a34a;
      --bad:#ef4444;
      --brand:#60a5fa;
      --warning:#f59e0b;
      --shadow: 0 14px 50px rgba(0,0,0,.45);
      --radius: 16px;
    }
    *{box-sizing:border-box}
    body{
      margin:0;
      font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial;
      background: radial-gradient(1200px 800px at 10% 0%, #122044 0%, var(--bg) 60%);
      color:var(--text);
      min-height:100vh;
    }

    .wrap{max-width:1000px;margin:0 auto;padding:24px 16px 64px}
    .topbar{
      display:flex;align-items:flex-end;justify-content:space-between;gap:12px;flex-wrap:wrap;
      margin-bottom:24px;
    }
    .title h1{margin:0;font-size:22px;letter-spacing:.2px}
    .title p{margin:6px 0 0;color:var(--muted);font-size:12px}

    .btn{
      border:1px solid var(--stroke);
      background: linear-gradient(135deg, rgba(255,255,255,.08) 0%, rgba(255,255,255,.04) 100%);
      color:#fff;
      padding:10px 16px;
      border-radius:12px;
      cursor:pointer;
      font-weight:500;
      transition: all .2s ease;
      display:inline-flex;align-items:center;gap:8px;
      user-select:none;
      text-decoration:none;
      font-size:13px;
    }
    .btn:hover{transform: translateY(-2px);background: linear-gradient(135deg, rgba(255,255,255,.12) 0%, rgba(255,255,255,.08) 100%)}
    .btn-primary{background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);border-color: transparent;box-shadow: 0 2px 12px rgba(34,197,94,.4)}
    .btn-primary:hover{background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%)}
    .btn-danger{background: rgba(239,68,68,.12); border-color: rgba(239,68,68,.32);color:#f87171}
    .btn-danger:hover{background: rgba(239,68,68,.2)}
    .btn-sm{padding:6px 12px;font-size:12px}
    .btn-ghost{background:transparent;border-color:var(--stroke)}

    .tabs{display:flex;gap:4px;margin-bottom:24px;border-bottom:1px solid var(--stroke);padding-bottom:0}
    .tab{
      padding:12px 20px;
      color:var(--muted);
      cursor:pointer;
      border-bottom:2px solid transparent;
      margin-bottom:-1px;
      font-weight:500;
      font-size:13px;
      transition:all .2s;
    }
    .tab:hover{color:var(--text)}
    .tab.active{color:var(--brand);border-bottom-color:var(--brand)}

    .panel{
      background: linear-gradient(180deg, rgba(255,255,255,.06) 0%, rgba(255,255,255,.02) 100%);
      border:1px solid var(--stroke);
      border-radius: var(--radius);
      padding:24px;
      margin-bottom:20px;
      display:none;
    }
    .panel.active{display:block}
    .panel-title{
      font-size:16px;font-weight:600;margin:0 0 20px;
      display:flex;align-items:center;gap:10px;
    }
    .panel-title i{color:var(--brand)}

    .form-row{display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:16px;margin-bottom:16px}
    .form-group{margin-bottom:16px}
    .form-group label{display:block;margin-bottom:6px;color:var(--text);font-weight:500;font-size:13px}
    .form-group .hint{color:var(--muted);font-size:11px;margin-top:4px}
    .form-group input,
    .form-group select{
      width:100%;
      background:rgba(0,0,0,.3);
      border:1px solid var(--stroke);
      border-radius:10px;
      color:#fff;
      padding:10px 14px;
      font-size:14px;
    }
    .form-group input:focus,
    .form-group select:focus{
      outline:none;
      border-color:var(--brand);
      box-shadow:0 0 0 3px rgba(96,165,250,.15);
    }
    .form-group input::placeholder{color:var(--muted)}

    .toggle-group{display:flex;align-items:center;gap:12px}
    .toggle{
      position:relative;
      width:48px;height:26px;
      background:rgba(255,255,255,.1);
      border-radius:13px;
      cursor:pointer;
      transition:background .2s;
    }
    .toggle.active{background:var(--good)}
    .toggle::after{
      content:'';
      position:absolute;
      top:3px;left:3px;
      width:20px;height:20px;
      background:#fff;
      border-radius:50%;
      transition:transform .2s;
    }
    .toggle.active::after{transform:translateX(22px)}
    .toggle-label{font-size:14px}

    /* Zones de livraison */
    .zone-list{display:flex;flex-direction:column;gap:12px;margin-bottom:20px}
    .zone-card{
      background:var(--card);
      border:1px solid var(--stroke);
      border-radius:12px;
      padding:16px;
      display:grid;
      grid-template-columns:1fr auto;
      gap:16px;
      align-items:center;
    }
    .zone-info h4{margin:0 0 6px;font-size:14px;font-weight:600}
    .zone-info p{margin:0;color:var(--muted);font-size:12px}
    .zone-badge{
      display:inline-block;
      padding:4px 10px;
      border-radius:20px;
      font-size:11px;
      font-weight:600;
      margin-right:8px;
    }
    .zone-badge.active{background:rgba(34,197,94,.15);color:#4ade80}
    .zone-badge.inactive{background:rgba(239,68,68,.15);color:#f87171}
    .zone-actions{display:flex;gap:8px}

    /* Payment methods */
    .payment-method{
      display:flex;
      align-items:center;
      justify-content:space-between;
      padding:16px;
      background:var(--card);
      border:1px solid var(--stroke);
      border-radius:12px;
      margin-bottom:12px;
    }
    .payment-method-info{display:flex;align-items:center;gap:14px}
    .payment-method-icon{
      width:44px;height:44px;
      background:rgba(255,255,255,.1);
      border-radius:10px;
      display:flex;align-items:center;justify-content:center;
      font-size:20px;
    }
    .payment-method h4{margin:0 0 4px;font-size:14px}
    .payment-method p{margin:0;color:var(--muted);font-size:12px}

    /* Modal */
    .modal{
      display:none;
      position:fixed;
      top:0;left:0;right:0;bottom:0;
      background:rgba(0,0,0,.8);
      z-index:999;
      align-items:center;
      justify-content:center;
    }
    .modal.active{display:flex}
    .modal-content{
      background:var(--panel);
      border:1px solid var(--stroke);
      border-radius:var(--radius);
      max-width:500px;
      width:90%;
      padding:24px;
      box-shadow:0 25px 60px rgba(0,0,0,.5);
    }
    .modal-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
    .modal-header h3{margin:0;font-size:16px}
    .close-btn{background:transparent;border:none;color:var(--muted);cursor:pointer;font-size:20px}

    /* Toast */
    .toast{
      position:fixed;bottom:24px;right:24px;
      background:var(--panel);
      border:1px solid var(--stroke);
      border-radius:12px;
      padding:14px 20px;
      display:flex;align-items:center;gap:12px;
      box-shadow:0 10px 40px rgba(0,0,0,.4);
      transform:translateY(100px);
      opacity:0;
      transition:all .3s ease;
      z-index:9999;
    }
    .toast.show{transform:translateY(0);opacity:1}
    .toast.success{border-left:4px solid var(--good)}
    .toast.error{border-left:4px solid var(--bad)}

    @media(max-width:600px){
      .form-row{grid-template-columns:1fr}
      .zone-card{grid-template-columns:1fr}
      .zone-actions{margin-top:12px}
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="topbar">
      <div class="title">
        <h1><i class="fas fa-cog"></i> Paramètres</h1>
        <p>Configuration générale, livraison et paiements</p>
      </div>
      <a href="index.php" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Retour</a>
    </div>

    <!-- Tabs -->
    <div class="tabs">
      <div class="tab active" data-tab="general"><i class="fas fa-sliders-h"></i> Général</div>
      <div class="tab" data-tab="delivery"><i class="fas fa-truck"></i> Livraison</div>
      <div class="tab" data-tab="payment"><i class="fas fa-credit-card"></i> Paiements</div>
    </div>

    <!-- Panel: Général -->
    <div class="panel active" id="panel-general">
      <h3 class="panel-title"><i class="fas fa-globe"></i> Paramètres régionaux</h3>

      <form id="formGeneral">
        <div class="form-row">
          <div class="form-group">
            <label>Code devise</label>
            <input type="text" name="currency_code" id="currencyCode" placeholder="EUR" maxlength="5">
            <div class="hint">Ex: EUR, DA, USD</div>
          </div>
          <div class="form-group">
            <label>Symbole devise</label>
            <input type="text" name="currency_symbol" id="currencySymbol" placeholder="€" maxlength="5">
            <div class="hint">Ex: €, DA, $</div>
          </div>
          <div class="form-group">
            <label>Position symbole</label>
            <select name="currency_position" id="currencyPosition">
              <option value="after">Après (10 €)</option>
              <option value="before">Avant (€ 10)</option>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Indicatif pays</label>
            <input type="text" name="country_code" id="countryCode" placeholder="+33" maxlength="5">
            <div class="hint">Ex: +33, +213, +1</div>
          </div>
          <div class="form-group">
            <label>Nom du pays</label>
            <input type="text" name="country_name" id="countryName" placeholder="France">
          </div>
        </div>

        <h3 class="panel-title" style="margin-top:32px"><i class="fas fa-star"></i> Programme fidélité</h3>

        <div class="form-row">
          <div class="form-group">
            <label>Euros par point</label>
            <input type="number" name="loyalty_euro_per_point" id="loyaltyEuroPerPoint" step="0.01" min="0" placeholder="10">
            <div class="hint">Combien d'euros = 1 point (ex: 10€ = 1 point)</div>
          </div>
          <div class="form-group">
            <label>Valeur d'un point (€)</label>
            <input type="number" name="loyalty_point_value" id="loyaltyPointValue" step="0.01" min="0" placeholder="1">
            <div class="hint">Combien vaut 1 point en réduction</div>
          </div>
        </div>

        <div class="form-group">
          <div class="toggle-group">
            <div class="toggle" id="toggleLoyalty" data-field="loyalty_enabled"></div>
            <span class="toggle-label">Programme fidélité activé</span>
          </div>
        </div>

        <h3 class="panel-title" style="margin-top:32px"><i class="fas fa-shopping-cart"></i> Commandes</h3>

        <div class="form-row">
          <div class="form-group">
            <label>Montant minimum commande (€)</label>
            <input type="number" name="min_order_amount" id="minOrderAmount" step="0.01" min="0" placeholder="0">
            <div class="hint">0 = pas de minimum</div>
          </div>
          <div class="form-group">
            <label>Livraison gratuite à partir de (€)</label>
            <input type="number" name="free_delivery_threshold" id="freeDeliveryThreshold" step="0.01" min="0" placeholder="">
            <div class="hint">Laisser vide = pas de livraison gratuite</div>
          </div>
        </div>

        <div class="form-group">
          <div class="toggle-group">
            <div class="toggle" id="toggleDelivery" data-field="delivery_enabled"></div>
            <span class="toggle-label">Livraison activée</span>
          </div>
        </div>

        <div class="form-group">
          <div class="toggle-group">
            <div class="toggle" id="togglePickup" data-field="pickup_enabled"></div>
            <span class="toggle-label">Click & Collect activé</span>
          </div>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top:20px">
          <i class="fas fa-save"></i> Enregistrer
        </button>
      </form>
    </div>

    <!-- Panel: Livraison -->
    <div class="panel" id="panel-delivery">
      <h3 class="panel-title"><i class="fas fa-map-marked-alt"></i> Zones de livraison</h3>
      <p style="color:var(--muted);font-size:13px;margin-bottom:20px">
        Définissez vos zones de livraison avec les frais selon la distance. Les zones sont triées par distance croissante.
      </p>

      <div class="zone-list" id="zoneList">
        <!-- Zones chargées dynamiquement -->
      </div>

      <button class="btn btn-primary" id="btnAddZone">
        <i class="fas fa-plus"></i> Ajouter une zone
      </button>
    </div>

    <!-- Panel: Paiements -->
    <div class="panel" id="panel-payment">
      <h3 class="panel-title"><i class="fas fa-wallet"></i> Méthodes de paiement</h3>

      <div class="payment-method">
        <div class="payment-method-info">
          <div class="payment-method-icon"><i class="fas fa-money-bill-wave"></i></div>
          <div>
            <h4>Espèces</h4>
            <p>Paiement en espèces à la livraison/retrait</p>
          </div>
        </div>
        <div class="toggle" id="toggleCash" data-field="cash_enabled"></div>
      </div>

      <div class="payment-method">
        <div class="payment-method-info">
          <div class="payment-method-icon"><i class="fas fa-credit-card"></i></div>
          <div>
            <h4>CB à la livraison</h4>
            <p>Paiement par carte avec TPE mobile</p>
          </div>
        </div>
        <div class="toggle" id="toggleCardDelivery" data-field="card_on_delivery_enabled"></div>
      </div>

      <div class="payment-method">
        <div class="payment-method-info">
          <div class="payment-method-icon" style="color:#635bff"><i class="fab fa-stripe-s"></i></div>
          <div>
            <h4>Paiement en ligne (Stripe)</h4>
            <p>Paiement sécurisé par carte bancaire</p>
          </div>
        </div>
        <div class="toggle" id="toggleOnline" data-field="online_payment_enabled"></div>
      </div>

      <!-- Stripe Config (affiché si online_payment activé) -->
      <div id="stripeConfig" style="margin-top:24px;display:none">
        <h3 class="panel-title"><i class="fab fa-stripe"></i> Configuration Stripe</h3>

        <div class="form-group">
          <label>Mode</label>
          <select name="stripe_mode" id="stripeMode">
            <option value="test">Test (développement)</option>
            <option value="live">Live (production)</option>
          </select>
        </div>

        <div id="stripeTestKeys">
          <div class="form-row">
            <div class="form-group">
              <label>Clé publique (Test)</label>
              <input type="text" name="stripe_public_key_test" id="stripePublicTest" placeholder="pk_test_...">
            </div>
            <div class="form-group">
              <label>Clé secrète (Test)</label>
              <input type="password" name="stripe_secret_key_test" id="stripeSecretTest" placeholder="sk_test_...">
            </div>
          </div>
        </div>

        <div id="stripeLiveKeys" style="display:none">
          <div class="form-row">
            <div class="form-group">
              <label>Clé publique (Live)</label>
              <input type="text" name="stripe_public_key_live" id="stripePublicLive" placeholder="pk_live_...">
            </div>
            <div class="form-group">
              <label>Clé secrète (Live)</label>
              <input type="password" name="stripe_secret_key_live" id="stripeSecretLive" placeholder="sk_live_...">
            </div>
          </div>
        </div>

        <button type="button" class="btn btn-primary" id="btnSaveStripe">
          <i class="fas fa-save"></i> Enregistrer Stripe
        </button>
      </div>
    </div>
  </div>

  <!-- Modal Zone -->
  <div class="modal" id="modalZone">
    <div class="modal-content">
      <div class="modal-header">
        <h3 id="modalZoneTitle">Ajouter une zone</h3>
        <button class="close-btn" onclick="closeModal('modalZone')">&times;</button>
      </div>
      <form id="formZone">
        <input type="hidden" name="id" id="zoneId">
        <div class="form-group">
          <label>Nom de la zone</label>
          <input type="text" name="name" id="zoneName" placeholder="Ex: Centre-ville" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Distance min (km)</label>
            <input type="number" name="min_distance_km" id="zoneMinDist" step="0.1" min="0" value="0" required>
          </div>
          <div class="form-group">
            <label>Distance max (km)</label>
            <input type="number" name="max_distance_km" id="zoneMaxDist" step="0.1" min="0" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Frais de livraison (€)</label>
            <input type="number" name="delivery_fee" id="zoneFee" step="0.01" min="0" value="0" required>
          </div>
          <div class="form-group">
            <label>Temps estimé (min)</label>
            <input type="number" name="estimated_time_min" id="zoneTime" min="1" value="30">
          </div>
        </div>
        <div class="form-group">
          <label>Montant min. pour cette zone (€)</label>
          <input type="number" name="min_order_amount" id="zoneMinOrder" step="0.01" min="0" placeholder="Utiliser le global">
          <div class="hint">Laisser vide pour utiliser le minimum global</div>
        </div>
        <div class="form-group">
          <div class="toggle-group">
            <div class="toggle active" id="toggleZoneActive" data-field="is_active"></div>
            <span class="toggle-label">Zone active</span>
          </div>
        </div>
        <div style="display:flex;gap:12px;margin-top:20px">
          <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
          <button type="button" class="btn btn-ghost" onclick="closeModal('modalZone')">Annuler</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Toast -->
  <div class="toast" id="toast"></div>

  <script>
    const API = 'api/settings.php';
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

    let settings = {};
    let paymentSettings = {};
    let deliveryZones = [];

    // ===== UTILS =====
    function $(sel) { return document.querySelector(sel); }
    function $$(sel) { return document.querySelectorAll(sel); }

    function toast(msg, type = 'success') {
      const t = $('#toast');
      t.className = 'toast ' + type;
      t.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${msg}`;
      t.classList.add('show');
      setTimeout(() => t.classList.remove('show'), 3000);
    }

    async function api(action, data = {}) {
      const res = await fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
        body: JSON.stringify({ action, ...data, csrf_token: CSRF })
      });
      return res.json();
    }

    function openModal(id) { $('#' + id).classList.add('active'); }
    function closeModal(id) { $('#' + id).classList.remove('active'); }

    // ===== TABS =====
    $$('.tab').forEach(tab => {
      tab.addEventListener('click', () => {
        $$('.tab').forEach(t => t.classList.remove('active'));
        $$('.panel').forEach(p => p.classList.remove('active'));
        tab.classList.add('active');
        $('#panel-' + tab.dataset.tab).classList.add('active');
      });
    });

    // ===== TOGGLES =====
    function setupToggle(el, value) {
      if (value) el.classList.add('active');
      else el.classList.remove('active');

      el.addEventListener('click', () => {
        el.classList.toggle('active');
      });
    }

    // ===== LOAD SETTINGS =====
    async function loadSettings() {
      const res = await api('get_admin');
      if (!res.success) {
        toast('Erreur chargement', 'error');
        return;
      }

      settings = res.settings;
      paymentSettings = settings.payment || {};
      deliveryZones = settings.delivery_zones || [];

      // Remplir le formulaire général
      $('#currencyCode').value = settings.currency_code || 'EUR';
      $('#currencySymbol').value = settings.currency_symbol || '€';
      $('#currencyPosition').value = settings.currency_position || 'after';
      $('#countryCode').value = settings.country_code || '+33';
      $('#countryName').value = settings.country_name || 'France';
      $('#loyaltyEuroPerPoint').value = settings.loyalty_euro_per_point || 10;
      $('#loyaltyPointValue').value = settings.loyalty_point_value || 1;
      $('#minOrderAmount').value = settings.min_order_amount || 0;
      $('#freeDeliveryThreshold').value = settings.free_delivery_threshold || '';

      setupToggle($('#toggleLoyalty'), settings.loyalty_enabled == 1);
      setupToggle($('#toggleDelivery'), settings.delivery_enabled == 1);
      setupToggle($('#togglePickup'), settings.pickup_enabled == 1);

      // Payment
      setupToggle($('#toggleCash'), paymentSettings.cash_enabled == 1);
      setupToggle($('#toggleCardDelivery'), paymentSettings.card_on_delivery_enabled == 1);
      setupToggle($('#toggleOnline'), paymentSettings.online_payment_enabled == 1);

      // Stripe config visibility
      updateStripeVisibility();

      $('#stripeMode').value = paymentSettings.stripe_mode || 'test';
      $('#stripePublicTest').value = paymentSettings.stripe_public_key_test || '';
      $('#stripeSecretTest').value = paymentSettings.stripe_secret_key_test ? '••••••••' : '';
      $('#stripePublicLive').value = paymentSettings.stripe_public_key_live || '';
      $('#stripeSecretLive').value = paymentSettings.stripe_secret_key_live ? '••••••••' : '';

      updateStripeModeVisibility();

      // Zones
      renderZones();
    }

    function updateStripeVisibility() {
      const online = $('#toggleOnline').classList.contains('active');
      $('#stripeConfig').style.display = online ? 'block' : 'none';
    }

    function updateStripeModeVisibility() {
      const mode = $('#stripeMode').value;
      $('#stripeTestKeys').style.display = mode === 'test' ? 'block' : 'none';
      $('#stripeLiveKeys').style.display = mode === 'live' ? 'block' : 'none';
    }

    $('#toggleOnline').addEventListener('click', updateStripeVisibility);
    $('#stripeMode').addEventListener('change', updateStripeModeVisibility);

    // ===== SAVE GENERAL SETTINGS =====
    $('#formGeneral').addEventListener('submit', async (e) => {
      e.preventDefault();

      const data = {
        currency_code: $('#currencyCode').value,
        currency_symbol: $('#currencySymbol').value,
        currency_position: $('#currencyPosition').value,
        country_code: $('#countryCode').value,
        country_name: $('#countryName').value,
        loyalty_euro_per_point: parseFloat($('#loyaltyEuroPerPoint').value) || 10,
        loyalty_point_value: parseFloat($('#loyaltyPointValue').value) || 1,
        loyalty_enabled: $('#toggleLoyalty').classList.contains('active') ? 1 : 0,
        delivery_enabled: $('#toggleDelivery').classList.contains('active') ? 1 : 0,
        pickup_enabled: $('#togglePickup').classList.contains('active') ? 1 : 0,
        min_order_amount: parseFloat($('#minOrderAmount').value) || 0,
        free_delivery_threshold: $('#freeDeliveryThreshold').value ? parseFloat($('#freeDeliveryThreshold').value) : null
      };

      const res = await api('update_settings', data);
      if (res.success) {
        toast('Paramètres enregistrés');
      } else {
        toast(res.error || 'Erreur', 'error');
      }
    });

    // ===== SAVE STRIPE =====
    $('#btnSaveStripe').addEventListener('click', async () => {
      const data = {
        cash_enabled: $('#toggleCash').classList.contains('active') ? 1 : 0,
        card_on_delivery_enabled: $('#toggleCardDelivery').classList.contains('active') ? 1 : 0,
        online_payment_enabled: $('#toggleOnline').classList.contains('active') ? 1 : 0,
        stripe_mode: $('#stripeMode').value
      };

      // Only send keys if they've been changed (not masked)
      const testPub = $('#stripePublicTest').value;
      const testSec = $('#stripeSecretTest').value;
      const livePub = $('#stripePublicLive').value;
      const liveSec = $('#stripeSecretLive').value;

      if (testPub && !testPub.includes('•')) data.stripe_public_key_test = testPub;
      if (testSec && !testSec.includes('•')) data.stripe_secret_key_test = testSec;
      if (livePub && !livePub.includes('•')) data.stripe_public_key_live = livePub;
      if (liveSec && !liveSec.includes('•')) data.stripe_secret_key_live = liveSec;

      const res = await api('update_payment_settings', data);
      if (res.success) {
        toast('Configuration paiement enregistrée');
      } else {
        toast(res.error || 'Erreur', 'error');
      }
    });

    // ===== DELIVERY ZONES =====
    function renderZones() {
      const list = $('#zoneList');
      if (deliveryZones.length === 0) {
        list.innerHTML = '<p style="color:var(--muted);text-align:center;padding:40px">Aucune zone configurée</p>';
        return;
      }

      list.innerHTML = deliveryZones.map(zone => `
        <div class="zone-card">
          <div class="zone-info">
            <h4>
              <span class="zone-badge ${zone.is_active == 1 ? 'active' : 'inactive'}">${zone.is_active == 1 ? 'Active' : 'Inactive'}</span>
              ${escapeHtml(zone.name)}
            </h4>
            <p>
              <i class="fas fa-ruler"></i> ${zone.min_distance_km} - ${zone.max_distance_km} km
              &nbsp;•&nbsp;
              <i class="fas fa-euro-sign"></i> ${parseFloat(zone.delivery_fee).toFixed(2)} €
              &nbsp;•&nbsp;
              <i class="fas fa-clock"></i> ~${zone.estimated_time_min} min
              ${zone.min_order_amount ? `&nbsp;•&nbsp;<i class="fas fa-shopping-cart"></i> Min ${zone.min_order_amount}€` : ''}
            </p>
          </div>
          <div class="zone-actions">
            <button class="btn btn-sm" onclick="editZone(${zone.id})"><i class="fas fa-edit"></i></button>
            <button class="btn btn-sm btn-danger" onclick="deleteZone(${zone.id})"><i class="fas fa-trash"></i></button>
          </div>
        </div>
      `).join('');
    }

    function escapeHtml(str) {
      return String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    $('#btnAddZone').addEventListener('click', () => {
      $('#modalZoneTitle').textContent = 'Ajouter une zone';
      $('#formZone').reset();
      $('#zoneId').value = '';
      $('#toggleZoneActive').classList.add('active');
      openModal('modalZone');
    });

    function editZone(id) {
      const zone = deliveryZones.find(z => z.id == id);
      if (!zone) return;

      $('#modalZoneTitle').textContent = 'Modifier la zone';
      $('#zoneId').value = zone.id;
      $('#zoneName').value = zone.name;
      $('#zoneMinDist').value = zone.min_distance_km;
      $('#zoneMaxDist').value = zone.max_distance_km;
      $('#zoneFee').value = zone.delivery_fee;
      $('#zoneTime').value = zone.estimated_time_min;
      $('#zoneMinOrder').value = zone.min_order_amount || '';

      if (zone.is_active == 1) $('#toggleZoneActive').classList.add('active');
      else $('#toggleZoneActive').classList.remove('active');

      openModal('modalZone');
    }

    async function deleteZone(id) {
      if (!confirm('Supprimer cette zone de livraison ?')) return;

      const res = await api('delete_delivery_zone', { id });
      if (res.success) {
        deliveryZones = res.zones;
        renderZones();
        toast('Zone supprimée');
      } else {
        toast(res.error || 'Erreur', 'error');
      }
    }

    $('#formZone').addEventListener('submit', async (e) => {
      e.preventDefault();

      const id = $('#zoneId').value;
      const data = {
        name: $('#zoneName').value,
        min_distance_km: parseFloat($('#zoneMinDist').value),
        max_distance_km: parseFloat($('#zoneMaxDist').value),
        delivery_fee: parseFloat($('#zoneFee').value),
        estimated_time_min: parseInt($('#zoneTime').value) || 30,
        min_order_amount: $('#zoneMinOrder').value ? parseFloat($('#zoneMinOrder').value) : null,
        is_active: $('#toggleZoneActive').classList.contains('active') ? 1 : 0
      };

      let res;
      if (id) {
        res = await api('update_delivery_zone', { id: parseInt(id), ...data });
      } else {
        res = await api('add_delivery_zone', data);
      }

      if (res.success) {
        deliveryZones = res.zones;
        renderZones();
        closeModal('modalZone');
        toast(id ? 'Zone modifiée' : 'Zone ajoutée');
      } else {
        toast(res.error || 'Erreur', 'error');
      }
    });

    // Setup toggle for zone modal
    $('#toggleZoneActive').addEventListener('click', function() {
      this.classList.toggle('active');
    });

    // ===== INIT =====
    loadSettings();
  </script>
</body>
</html>
